<?php
/**
 * Store pickup code support for WooCommerce orders.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

const DEVHUB_PICKUP_CODE_META_KEY = '_devhub_pickup_code';

add_action( 'woocommerce_payment_complete', 'devhub_maybe_generate_pickup_code', 20, 1 );
add_action( 'woocommerce_order_status_processing', 'devhub_maybe_generate_pickup_code', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'devhub_maybe_generate_pickup_code', 20, 1 );

add_action( 'woocommerce_order_details_after_order_table', 'devhub_render_pickup_code_order_details', 10, 1 );
add_action( 'woocommerce_email_order_meta', 'devhub_render_pickup_code_email', 10, 4 );
add_action( 'woocommerce_admin_order_data_after_order_details', 'devhub_render_pickup_code_admin', 10, 1 );

/**
 * Generate and persist a pickup code once a pickup order has been paid.
 *
 * @param int $order_id WooCommerce order ID.
 * @return void
 */
function devhub_maybe_generate_pickup_code( int $order_id ): void {
	$order = wc_get_order( $order_id );

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	devhub_ensure_pickup_code( $order );
}

/**
 * Check whether an order is a store pickup order.
 *
 * Falls back to persisted pickup-store meta so older orders still qualify.
 *
 * @param WC_Order $order WooCommerce order.
 * @return bool
 */
function devhub_is_pickup_order( WC_Order $order ): bool {
	$delivery_method = devhub_get_pickup_delivery_method( $order );

	if ( 'pickup' === $delivery_method ) {
		return true;
	}

	$pickup_store = sanitize_text_field( (string) $order->get_meta( '_wc_other/' . DEVHUB_CHECKOUT_PICKUP_STORE_FIELD, true ) );

	if ( '' !== $pickup_store ) {
		return true;
	}

	return '' !== trim( (string) $order->get_meta( '_devhub_pickup_store_label', true ) );
}

/**
 * Read the persisted delivery method for a checkout-block order.
 *
 * WooCommerce Blocks saves contact/order fields under the "other" group,
 * so we check both our custom summary meta and the native checkout-field meta.
 *
 * @param WC_Order $order WooCommerce order.
 * @return string
 */
function devhub_get_pickup_delivery_method( WC_Order $order ): string {
	$delivery_method = sanitize_text_field( (string) $order->get_meta( '_devhub_delivery_method', true ) );

	if ( '' !== $delivery_method ) {
		return $delivery_method;
	}

	return sanitize_text_field( (string) $order->get_meta( '_wc_other/' . DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD, true ) );
}

/**
 * Read the pickup code stored against an order.
 *
 * @param WC_Order $order WooCommerce order.
 * @return string
 */
function devhub_get_pickup_code( WC_Order $order ): string {
	return strtoupper( sanitize_text_field( (string) $order->get_meta( DEVHUB_PICKUP_CODE_META_KEY, true ) ) );
}

/**
 * Ensure a pickup order has a code and return it.
 *
 * This is used both by lifecycle hooks and by render hooks so older orders or
 * timing edge cases still end up with a persisted code when first accessed.
 *
 * @param WC_Order $order WooCommerce order.
 * @return string
 */
function devhub_ensure_pickup_code( WC_Order $order ): string {
	$existing_code = devhub_get_pickup_code( $order );

	if ( '' !== $existing_code ) {
		return $existing_code;
	}

	if ( ! devhub_is_pickup_order( $order ) ) {
		return '';
	}

	if ( $order->has_status( [ 'failed', 'cancelled', 'refunded' ] ) ) {
		return '';
	}

	$code = devhub_generate_unique_pickup_code();

	$order->update_meta_data( DEVHUB_PICKUP_CODE_META_KEY, $code );
	$order->save_meta_data();
	$order->add_order_note(
		sprintf(
			/* translators: %s: pickup code */
			__( 'Store pickup code generated: %s', 'devicehub-theme' ),
			$code
		)
	);

	return $code;
}

/**
 * Create a unique pickup code.
 *
 * @return string
 */
function devhub_generate_unique_pickup_code(): string {
	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$code = 'PU-' . strtoupper( wp_generate_password( 8, false, false ) );

		$matches = wc_get_orders(
			[
				'limit'      => 1,
				'return'     => 'ids',
				'meta_key'   => DEVHUB_PICKUP_CODE_META_KEY,
				'meta_value' => $code,
			]
		);

		if ( empty( $matches ) ) {
			return $code;
		}
	}

	return 'PU-' . strtoupper( uniqid() );
}

/**
 * Render the pickup code on customer-facing order details pages.
 *
 * Skipped on the order-received (thank-you) page because the code is already
 * rendered inside the overview list in woocommerce/checkout/thankyou.php.
 *
 * @param WC_Order $order WooCommerce order.
 * @return void
 */
function devhub_render_pickup_code_order_details( WC_Order $order ): void {
	// Already shown in the overview ul on the thank-you page.
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}

	if ( ! devhub_is_pickup_order( $order ) ) {
		return;
	}

	$code = devhub_ensure_pickup_code( $order );

	if ( '' === $code ) {
		return;
	}

	$pickup_store = sanitize_text_field( (string) $order->get_meta( '_devhub_pickup_store_label', true ) );

	echo '<section class="devhub-pickup-code">';
	echo '<h2>' . esc_html__( 'Store pickup code', 'devicehub-theme' ) . '</h2>';
	echo '<p><strong>' . esc_html( $code ) . '</strong></p>';

	if ( '' !== $pickup_store ) {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: store name */
				__( 'Present this code when collecting from %s.', 'devicehub-theme' ),
				$pickup_store
			)
		) . '</p>';
	}

	echo '</section>';
}

/**
 * Render the pickup code in WooCommerce emails.
 *
 * @param WC_Order        $order         WooCommerce order.
 * @param bool            $sent_to_admin Whether the email goes to admin.
 * @param bool            $plain_text    Whether the email is plain text.
 * @param WC_Email|string $email         Email object when available.
 * @return void
 */
function devhub_render_pickup_code_email( WC_Order $order, bool $sent_to_admin, bool $plain_text, $email ): void {
	unset( $sent_to_admin, $email );

	if ( ! devhub_is_pickup_order( $order ) ) {
		return;
	}

	$code = devhub_ensure_pickup_code( $order );

	if ( '' === $code ) {
		return;
	}

	if ( $plain_text ) {
		echo "\n" . sprintf(
			/* translators: %s: pickup code */
			esc_html__( 'Store pickup code: %s', 'devicehub-theme' ),
			$code
		) . "\n";
		return;
	}

	echo '<h2>' . esc_html__( 'Store pickup code', 'devicehub-theme' ) . '</h2>';
	echo '<p><strong>' . esc_html( $code ) . '</strong></p>';
}

/**
 * Render the pickup code in the WooCommerce admin order screen.
 *
 * @param WC_Order $order WooCommerce order.
 * @return void
 */
function devhub_render_pickup_code_admin( WC_Order $order ): void {
	if ( ! devhub_is_pickup_order( $order ) ) {
		return;
	}

	$code = devhub_ensure_pickup_code( $order );

	if ( '' === $code ) {
		return;
	}

	echo '<p><strong>' . esc_html__( 'Store pickup code:', 'devicehub-theme' ) . '</strong> ' . esc_html( $code ) . '</p>';
}
