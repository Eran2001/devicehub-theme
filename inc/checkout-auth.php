<?php
/**
 * Checkout auth gate for block checkout.
 *
 * Reuses the existing My Account auth UI on checkout for unauthenticated users
 * and enforces the same requirement in WooCommerce block checkout requests.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'devhub_capture_guest_checkout_selection', 5 );
add_filter( 'render_block_woocommerce/checkout', 'devhub_render_checkout_auth_gate', 10, 2 );
add_filter( 'woocommerce_checkout_registration_required', 'devhub_force_checkout_auth_requirement' );
add_filter( 'woocommerce_checkout_registration_enabled', 'devhub_disable_checkout_inline_registration' );
add_action( 'woocommerce_login_form_end', 'devhub_render_checkout_auth_redirect_field' );
add_action( 'woocommerce_register_form_end', 'devhub_render_checkout_auth_redirect_field' );
add_action( 'wp_ajax_nopriv_devhub_guest_details', 'devhub_handle_guest_details' );
add_action( 'wp_ajax_devhub_guest_details', 'devhub_handle_guest_details' );

/**
 * Persist the explicit "continue as guest" choice in the WooCommerce session.
 */
function devhub_capture_guest_checkout_selection(): void {
	if ( is_user_logged_in() ) {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->__unset( 'devhub_guest_checkout' );
		}

		return;
	}

	if ( ! isset( $_GET['devhub_guest_checkout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$is_guest_checkout = '1' === sanitize_text_field( wp_unslash( $_GET['devhub_guest_checkout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	WC()->session->set( 'devhub_guest_checkout', $is_guest_checkout );
}

/**
 * Determine whether the current customer explicitly chose guest checkout.
 */
function devhub_has_guest_checkout_selection(): bool {
	if ( is_user_logged_in() ) {
		return false;
	}

	if ( isset( $_GET['devhub_guest_checkout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '1' === sanitize_text_field( wp_unslash( $_GET['devhub_guest_checkout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return function_exists( 'WC' ) && WC()->session ? true === WC()->session->get( 'devhub_guest_checkout', false ) : false;
}

/**
 * Detect whether the current request is the Store API checkout endpoint.
 */
function devhub_is_store_api_checkout_request(): bool {
	if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( '' === $request_uri ) {
		return false;
	}

	$rest_prefix = '/' . trim( rest_get_url_prefix(), '/' ) . '/wc/store/';

	return str_contains( $request_uri, $rest_prefix ) && str_contains( $request_uri, '/checkout' );
}

/**
 * Determine if checkout should be auth-gated.
 */
function devhub_should_require_checkout_auth(): bool {
	if ( is_user_logged_in() ) {
		return false;
	}

	if ( devhub_has_guest_checkout_selection() ) {
		return false;
	}

	if ( devhub_is_store_api_checkout_request() ) {
		return true;
	}

	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return false;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return false;
	}

	return true;
}

/**
 * Replace the checkout block with the existing auth UI for unauthenticated users.
 *
 * @param string $content Block content.
 * @param array  $block   Parsed block data.
 */
function devhub_render_checkout_auth_gate( string $content, array $block ): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $content;
	}

	if ( ! devhub_should_require_checkout_auth() ) {
		return $content;
	}

	if ( ! empty( $block['attrs']['isPreview'] ) ) {
		return $content;
	}

	ob_start();
	?>
	<div class="devhub-checkout-auth">
		<?php wc_get_template( 'myaccount/form-login.php' ); ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Force WooCommerce Blocks checkout to require an authenticated user.
 *
 * @param bool $required Existing requirement.
 */
function devhub_force_checkout_auth_requirement( bool $required ): bool {
	if ( devhub_has_guest_checkout_selection() ) {
		return false;
	}

	return devhub_should_require_checkout_auth() ? true : $required;
}

/**
 * Disable inline checkout registration when the checkout auth gate is active.
 *
 * @param bool $enabled Existing setting.
 */
function devhub_disable_checkout_inline_registration( bool $enabled ): bool {
	if ( devhub_has_guest_checkout_selection() ) {
		return false;
	}

	return devhub_should_require_checkout_auth() ? false : $enabled;
}

/**
 * Keep login/register success redirects on the checkout page.
 */
function devhub_render_checkout_auth_redirect_field(): void {
	if ( ! devhub_should_require_checkout_auth() ) {
		return;
	}

	echo '<input type="hidden" name="redirect" value="' . esc_attr( wc_get_checkout_url() ) . '" />';
}

/**
 * AJAX: Validate and store full guest details in the WooCommerce customer session.
 * Covers FR-10: Full name, Mobile, Email, Delivery address, Billing address (if different).
 */
function devhub_handle_guest_details(): void {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'devhub_guest_checkout' ) ) {
		wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh and try again.', 'devicehub-theme' ) ], 403 );
	}

	// ── Collect ───────────────────────────────────────────────────────────────
	$full_name        = isset( $_POST['full_name'] )         ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) )         : '';
	$phone            = isset( $_POST['phone'] )             ? sanitize_text_field( wp_unslash( $_POST['phone'] ) )             : '';
	$email            = isset( $_POST['email'] )             ? sanitize_email( wp_unslash( $_POST['email'] ) )                  : '';
	$billing_address_1 = isset( $_POST['billing_address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_address_1'] ) ) : '';
	$billing_address_2 = isset( $_POST['billing_address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_address_2'] ) ) : '';
	$billing_city      = isset( $_POST['billing_city'] )      ? sanitize_text_field( wp_unslash( $_POST['billing_city'] ) )      : '';
	$billing_postcode  = isset( $_POST['billing_postcode'] )  ? sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ) )  : '';
	$shipping_same     = ! empty( $_POST['shipping_same'] );

	// Shipping address — defaults to billing when same
	$shipping_address_1 = $shipping_same ? $billing_address_1 : ( isset( $_POST['shipping_address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address_1'] ) ) : '' );
	$shipping_address_2 = $shipping_same ? $billing_address_2 : ( isset( $_POST['shipping_address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address_2'] ) ) : '' );
	$shipping_city      = $shipping_same ? $billing_city      : ( isset( $_POST['shipping_city'] )      ? sanitize_text_field( wp_unslash( $_POST['shipping_city'] ) )      : '' );
	$shipping_postcode  = $shipping_same ? $billing_postcode  : ( isset( $_POST['shipping_postcode'] )  ? sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ) )  : '' );

	// ── Validate ──────────────────────────────────────────────────────────────
	if ( '' === $full_name ) {
		wp_send_json_error( [ 'message' => __( 'Please enter your full name.', 'devicehub-theme' ), 'field' => 'full_name' ] );
	}

	if ( '' === $phone || ! preg_match( '/^[0-9+\s\-()\x{00B7}]{7,20}$/u', $phone ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid mobile number.', 'devicehub-theme' ), 'field' => 'phone' ] );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'devicehub-theme' ), 'field' => 'email' ] );
	}

	if ( '' === $billing_address_1 ) {
		wp_send_json_error( [ 'message' => __( 'Please enter your billing address.', 'devicehub-theme' ), 'field' => 'billing_address_1' ] );
	}

	if ( '' === $billing_city ) {
		wp_send_json_error( [ 'message' => __( 'Please enter your billing city.', 'devicehub-theme' ), 'field' => 'billing_city' ] );
	}

	if ( '' === $billing_postcode ) {
		wp_send_json_error( [ 'message' => __( 'Please enter your billing postal code.', 'devicehub-theme' ), 'field' => 'billing_postcode' ] );
	}

	if ( ! $shipping_same ) {
		if ( '' === $shipping_address_1 ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your shipping address.', 'devicehub-theme' ), 'field' => 'shipping_address_1' ] );
		}
		if ( '' === $shipping_city ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your shipping city.', 'devicehub-theme' ), 'field' => 'shipping_city' ] );
		}
		if ( '' === $shipping_postcode ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your shipping postal code.', 'devicehub-theme' ), 'field' => 'shipping_postcode' ] );
		}
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->customer ) {
		wp_send_json_error( [ 'message' => __( 'Session unavailable. Please refresh and try again.', 'devicehub-theme' ) ] );
	}

	// ── Store in WC customer session (auto-fills checkout) ────────────────────
	$name_parts = explode( ' ', $full_name, 2 );
	$first_name = $name_parts[0];
	$last_name  = $name_parts[1] ?? '';

	WC()->customer->set_first_name( $first_name );
	WC()->customer->set_last_name( $last_name );

	// Billing (always collected)
	WC()->customer->set_billing_first_name( $first_name );
	WC()->customer->set_billing_last_name( $last_name );
	WC()->customer->set_billing_email( $email );
	WC()->customer->set_billing_phone( $phone );
	WC()->customer->set_billing_address_1( $billing_address_1 );
	WC()->customer->set_billing_address_2( $billing_address_2 );
	WC()->customer->set_billing_city( $billing_city );
	WC()->customer->set_billing_postcode( $billing_postcode );
	WC()->customer->set_billing_country( 'LK' );

	// Shipping (same as billing or separate)
	WC()->customer->set_shipping_first_name( $first_name );
	WC()->customer->set_shipping_last_name( $last_name );
	WC()->customer->set_shipping_address_1( $shipping_address_1 );
	WC()->customer->set_shipping_address_2( $shipping_address_2 );
	WC()->customer->set_shipping_city( $shipping_city );
	WC()->customer->set_shipping_postcode( $shipping_postcode );
	WC()->customer->set_shipping_country( 'LK' );

	WC()->customer->save();
	WC()->session->set( 'devhub_guest_checkout', true );

	// Ensure the session cookie is written to the browser so it persists
	// across page navigations after the AJAX response.
	if ( method_exists( WC()->session, 'set_customer_session_cookie' ) ) {
		WC()->session->set_customer_session_cookie( true );
	}

	wp_send_json_success( [ 'redirect' => wc_get_checkout_url() ] );
}
