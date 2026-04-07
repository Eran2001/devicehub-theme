<?php
/**
 * DeviceHub - Checkout delivery helpers for WooCommerce Blocks.
 *
 * The native WooCommerce Local Pickup shipping method handles all pickup UI
 * and writes pickup data to shipping_lines automatically. No custom additional
 * fields are registered here.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

// Constants kept for backward-compat with pickup-code.php and old orders.
const DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD = 'devicehub/delivery_method';
const DEVHUB_CHECKOUT_PICKUP_STORE_FIELD    = 'devicehub/pickup_store';

add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'devhub_save_delivery_type_meta', 20, 2 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'devhub_clear_shipping_for_pickup', 30, 2 );

/**
 * Write delivery_type order meta so the backend can read HOME_DELIVERY or STORE_PICKUP.
 *
 * @param WC_Order        $order   Order being processed.
 * @param WP_REST_Request $request Checkout request.
 */
function devhub_save_delivery_type_meta( WC_Order $order, WP_REST_Request $request ): void {
	$delivery_type = 'HOME_DELIVERY';

	foreach ( $order->get_shipping_methods() as $shipping_item ) {
		if ( 'pickup_location' === $shipping_item->get_method_id() ) {
			$delivery_type = 'STORE_PICKUP';
			break;
		}
	}

	$order->update_meta_data( 'delivery_type', $delivery_type );
}

/**
 * Clear shipping address fields for store pickup orders.
 *
 * WooCommerce Blocks copies billing → shipping when local pickup is selected.
 * The backend expects shipping to be empty for pickup orders.
 *
 * @param WC_Order        $order   Order being processed.
 * @param WP_REST_Request $request Checkout request.
 */
function devhub_clear_shipping_for_pickup( WC_Order $order, WP_REST_Request $_request ): void {
	foreach ( $order->get_shipping_methods() as $shipping_item ) {
		if ( 'pickup_location' === $shipping_item->get_method_id() ) {
			$order->set_shipping_first_name( '' );
			$order->set_shipping_last_name( '' );
			$order->set_shipping_company( '' );
			$order->set_shipping_address_1( '' );
			$order->set_shipping_address_2( '' );
			$order->set_shipping_city( '' );
			$order->set_shipping_state( '' );
			$order->set_shipping_postcode( '' );
			$order->set_shipping_country( '' );
			$order->set_shipping_phone( '' );
			return;
		}
	}
}

/**
 * Return enabled pickup locations from the WooCommerce Local Pickup option.
 *
 * @return array<int, array<string, string>>
 */
function devhub_get_checkout_pickup_locations(): array {
	$locations = get_option( 'pickup_location_pickup_locations', [] );
	$formatted = [];

	if ( ! is_array( $locations ) ) {
		return $formatted;
	}

	foreach ( $locations as $index => $location ) {
		if ( empty( $location['enabled'] ) ) {
			continue;
		}

		$name    = sanitize_text_field( (string) ( $location['name'] ?? '' ) );
		$details = wp_strip_all_tags( (string) ( $location['details'] ?? '' ) );
		$address = devhub_format_checkout_pickup_address( (array) ( $location['address'] ?? [] ) );

		if ( '' === $name ) {
			continue;
		}

		$formatted[] = [
			'value'   => 'pickup_store_' . (int) $index,
			'label'   => $name,
			'name'    => $name,
			'address' => $address,
			'details' => $details,
		];
	}

	return $formatted;
}

/**
 * Format a pickup address into a single readable line.
 *
 * @param array $address Raw pickup location address data.
 * @return string
 */
function devhub_format_checkout_pickup_address( array $address ): string {
	$parts = array_filter(
		array_map(
			static function ( $part ) {
				return trim( wp_strip_all_tags( (string) $part ) );
			},
			[
				$address['address_1'] ?? '',
				$address['address_2'] ?? '',
				$address['city'] ?? '',
				$address['state'] ?? '',
				$address['postcode'] ?? '',
				$address['country'] ?? '',
			]
		)
	);

	return implode( ', ', $parts );
}
