<?php
/**
 * DeviceHub - Checkout delivery method fields for WooCommerce Blocks.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

const DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD = 'devicehub/delivery_method';
const DEVHUB_CHECKOUT_PICKUP_STORE_FIELD    = 'devicehub/pickup_store';
const DEVHUB_ORDER_DELIVERY_METHOD_META     = 'delivery_method';
const DEVHUB_ORDER_DELIVERY_METHOD_LABEL    = 'delivery_method_label';
const DEVHUB_ORDER_PICKUP_STORE_LABEL       = 'pickup_store_label';
const DEVHUB_ORDER_PICKUP_STORE_ADDRESS     = 'pickup_store_address';
const DEVHUB_ORDER_PICKUP_STORE_DETAILS     = 'pickup_store_details';

add_action( 'woocommerce_init', 'devhub_register_checkout_delivery_fields' );
add_action( 'woocommerce_blocks_validate_location_contact_fields', 'devhub_validate_checkout_delivery_fields', 10, 3 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'devhub_store_checkout_delivery_meta', 10, 2 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'devhub_save_delivery_type_meta', 20, 2 );
// Runs AFTER WC address validation + payment — safe to clear shipping here.
add_action( 'woocommerce_store_api_checkout_order_processed', 'devhub_clear_shipping_for_pickup', 10, 1 );
// Ensure payment_method_title is populated and generate a transaction ID for COD.
add_action( 'woocommerce_store_api_checkout_order_processed', 'devhub_ensure_payment_details', 20, 1 );
add_filter( 'woocommerce_rest_prepare_shop_order_object', 'devhub_strip_legacy_delivery_meta_from_rest', 10, 3 );
add_filter( 'option_woocommerce_checkout_phone_field', 'devhub_force_checkout_phone_field_required' );
add_filter( 'woocommerce_get_country_locale_default', 'devhub_require_checkout_phone_fields' );
add_filter( 'woocommerce_get_country_locale', 'devhub_require_checkout_phone_fields' );

/**
 * Write delivery_type order meta (HOME_DELIVERY or STORE_PICKUP) for the backend.
 *
 * Reads the custom delivery method additional field set by the JS UI.
 *
 * @param WC_Order        $order   Order being processed.
 * @param WP_REST_Request $request Checkout request.
 */
function devhub_save_delivery_type_meta( WC_Order $order, WP_REST_Request $request ): void {
	$fields          = (array) $request->get_param( 'additional_fields' );
	$delivery_method = sanitize_text_field( (string) ( $fields[ DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD ] ?? 'home_delivery' ) );

	$order->update_meta_data( 'delivery_type', 'pickup' === $delivery_method ? 'STORE_PICKUP' : 'HOME_DELIVERY' );
}

/**
 * Clear shipping address for store pickup orders after WC validation is complete.
 *
 * devhub_store_checkout_delivery_meta (priority 10) already saved _devhub_delivery_method
 * before this fires, so we can read it directly from order meta.
 *
 * @param WC_Order $order Processed order.
 */
function devhub_clear_shipping_for_pickup( WC_Order $order ): void {
	$delivery_method = devhub_get_order_delivery_method( $order );

	if ( 'pickup' !== $delivery_method ) {
		return;
	}

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
	$order->save();
}

/**
 * Ensure payment_method_title is set and generate a transaction ID for COD orders.
 *
 * WooCommerce does not set payment_method_title for COD on some configurations,
 * and never generates a transaction_id for cash payments.
 *
 * @param WC_Order $order Processed order.
 */
function devhub_ensure_payment_details( WC_Order $order ): void {
	$changed        = false;
	$payment_method = $order->get_payment_method();

	// Set payment_method_title from the gateway if empty.
	if ( '' === $order->get_payment_method_title() && '' !== $payment_method ) {
		$gateways = WC()->payment_gateways()->payment_gateways();

		if ( isset( $gateways[ $payment_method ] ) ) {
			$order->set_payment_method_title( $gateways[ $payment_method ]->get_title() );
			$changed = true;
		}
	}

	// Generate a transaction reference for COD (Cash on Delivery has no electronic ID).
	if ( '' === $order->get_transaction_id() && 'cod' === $payment_method ) {
		$order->set_transaction_id( 'COD-' . strtoupper( wp_generate_password( 12, false, false ) ) );
		$changed = true;
	}

	if ( $changed ) {
		$order->save();
	}
}

add_filter(
	'woocommerce_get_default_value_for_' . DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD,
	static function ( $value ) {
		return $value ?: 'home_delivery';
	},
	10,
	1
);

/**
 * Register hidden block-checkout fields that store delivery UI state.
 */
function devhub_register_checkout_delivery_fields(): void {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	$always_hidden = [
		'type' => 'object',
	];

	woocommerce_register_additional_checkout_field(
		[
			'id'            => DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD,
			'label'         => __( 'Delivery method', 'devicehub-theme' ),
			'location'      => 'contact',
			'type'          => 'select',
			'hidden'        => $always_hidden,
			'options'       => [
				[
					'value' => 'home_delivery',
					'label' => __( 'Home Delivery', 'devicehub-theme' ),
				],
				[
					'value' => 'pickup',
					'label' => __( 'Pick Up at Store', 'devicehub-theme' ),
				],
			],
			'sanitize_callback' => static function ( $value ) {
				$value = sanitize_text_field( (string) $value );
				return in_array( $value, [ 'home_delivery', 'pickup' ], true ) ? $value : 'home_delivery';
			},
		]
	);

	woocommerce_register_additional_checkout_field(
		[
			'id'            => DEVHUB_CHECKOUT_PICKUP_STORE_FIELD,
			'label'         => __( 'Pickup store', 'devicehub-theme' ),
			'location'      => 'contact',
			'type'          => 'select',
			'hidden'        => $always_hidden,
			'options'       => devhub_get_checkout_pickup_store_options(),
			'sanitize_callback' => static function ( $value ) {
				return sanitize_text_field( (string) $value );
			},
		]
	);
}

/**
 * Force WooCommerce Blocks to treat checkout phone as required.
 *
 * @param mixed $value Stored WooCommerce option value.
 * @return string
 */
function devhub_force_checkout_phone_field_required( $value ): string {
	return 'required';
}

/**
 * Mark phone fields as required in WooCommerce locale field definitions.
 *
 * @param array $locale_fields Locale-configured checkout fields.
 * @return array
 */
function devhub_require_checkout_phone_fields( array $locale_fields ): array {
	if ( isset( $locale_fields['phone'] ) && is_array( $locale_fields['phone'] ) ) {
		$locale_fields['phone']['required'] = true;
	}

	if ( isset( $locale_fields['billing_phone'] ) && is_array( $locale_fields['billing_phone'] ) ) {
		$locale_fields['billing_phone']['required'] = true;
	}

	if ( isset( $locale_fields['shipping_phone'] ) && is_array( $locale_fields['shipping_phone'] ) ) {
		$locale_fields['shipping_phone']['required'] = true;
	}

	return $locale_fields;
}

/**
 * Return pickup locations formatted for the checkout UI.
 *
 * @return array<int, array<string, string>>
 */
function devhub_get_checkout_pickup_locations(): array {
	$locations  = get_option( 'pickup_location_pickup_locations', [] );
	$formatted  = [];

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
 * Map pickup locations into select options for the hidden field.
 *
 * @return array<int, array<string, string>>
 */
function devhub_get_checkout_pickup_store_options(): array {
	$options = [
		[
			'value' => '',
			'label' => __( 'Select a store', 'devicehub-theme' ),
		],
	];

	foreach ( devhub_get_checkout_pickup_locations() as $location ) {
		$options[] = [
			'value' => $location['value'],
			'label' => $location['label'],
		];
	}

	return $options;
}

/**
 * Validate the custom delivery fields stored in the checkout contact location.
 *
 * @param WP_Error $errors Validation errors.
 * @param array    $fields Submitted fields for the contact location.
 * @param string   $group  Field group.
 */
function devhub_validate_checkout_delivery_fields( WP_Error $errors, $fields, string $group ): void {
	if ( 'other' !== $group ) {
		return;
	}

	$fields          = is_array( $fields ) ? $fields : [];
	$delivery_method = sanitize_text_field( (string) ( $fields[ DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD ] ?? 'home_delivery' ) );
	$pickup_store    = sanitize_text_field( (string) ( $fields[ DEVHUB_CHECKOUT_PICKUP_STORE_FIELD ] ?? '' ) );
	$locations       = devhub_get_checkout_pickup_locations();
	$location_map    = [];

	foreach ( $locations as $location ) {
		$location_map[ $location['value'] ] = $location;
	}

	if ( ! in_array( $delivery_method, [ 'home_delivery', 'pickup' ], true ) ) {
		$errors->add(
			'devhub_invalid_delivery_method',
			__( 'Please select a valid delivery method.', 'devicehub-theme' ),
			[
				'location' => 'contact',
				'key'      => DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD,
			]
		);
		return;
	}

	if ( 'pickup' !== $delivery_method ) {
		return;
	}

	if ( empty( $location_map ) ) {
		$errors->add(
			'devhub_pickup_unavailable',
			__( 'Store pickup is not available right now. Please choose Home Delivery.', 'devicehub-theme' ),
			[
				'location' => 'contact',
				'key'      => DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD,
			]
		);
		return;
	}

	if ( '' === $pickup_store || ! isset( $location_map[ $pickup_store ] ) ) {
		$errors->add(
			'devhub_pickup_store_required',
			__( 'Please select a pickup store to continue.', 'devicehub-theme' ),
			[
				'location' => 'contact',
				'key'      => DEVHUB_CHECKOUT_PICKUP_STORE_FIELD,
			]
		);
	}
}

/**
 * Persist a human-readable pickup summary alongside the hidden field values.
 *
 * @param WC_Order        $order   Order being updated.
 * @param WP_REST_Request $request Checkout request.
 */
function devhub_store_checkout_delivery_meta( WC_Order $order, WP_REST_Request $request ): void {
	$fields          = (array) $request->get_param( 'additional_fields' );
	$delivery_method = sanitize_text_field( (string) ( $fields[ DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD ] ?? 'home_delivery' ) );
	$pickup_store    = sanitize_text_field( (string) ( $fields[ DEVHUB_CHECKOUT_PICKUP_STORE_FIELD ] ?? '' ) );
	devhub_update_order_delivery_meta( $order, $delivery_method, $pickup_store );
}

/**
 * Persist DeviceHub delivery meta on an order.
 *
 * Shared by the Store API checkout flow and the custom checkout-to-payment
 * handoff.
 *
 * @param WC_Order $order           Order object.
 * @param string   $delivery_method Selected delivery method.
 * @param string   $pickup_store    Selected pickup location value.
 * @return void
 */
function devhub_update_order_delivery_meta( WC_Order $order, string $delivery_method, string $pickup_store ): void {
	$location_map = [];

	foreach ( devhub_get_checkout_pickup_locations() as $location ) {
		$location_map[ $location['value'] ] = $location;
	}

	$order->update_meta_data( DEVHUB_ORDER_DELIVERY_METHOD_META, $delivery_method );
	$order->update_meta_data( DEVHUB_ORDER_DELIVERY_METHOD_LABEL, 'pickup' === $delivery_method ? __( 'Pick Up at Store', 'devicehub-theme' ) : __( 'Home Delivery', 'devicehub-theme' ) );

	if ( 'pickup' === $delivery_method && isset( $location_map[ $pickup_store ] ) ) {
		$location = $location_map[ $pickup_store ];
		$order->update_meta_data( DEVHUB_ORDER_PICKUP_STORE_LABEL, $location['label'] );
		$order->update_meta_data( DEVHUB_ORDER_PICKUP_STORE_ADDRESS, $location['address'] );
		$order->update_meta_data( DEVHUB_ORDER_PICKUP_STORE_DETAILS, $location['details'] );
	} else {
		$order->delete_meta_data( DEVHUB_ORDER_PICKUP_STORE_LABEL );
		$order->delete_meta_data( DEVHUB_ORDER_PICKUP_STORE_ADDRESS );
		$order->delete_meta_data( DEVHUB_ORDER_PICKUP_STORE_DETAILS );
	}

	$order->delete_meta_data( '_devhub_delivery_method' );
	$order->delete_meta_data( '_devhub_delivery_method_label' );
	$order->delete_meta_data( '_devhub_pickup_store_label' );
	$order->delete_meta_data( '_devhub_pickup_store_address' );
	$order->delete_meta_data( '_devhub_pickup_store_details' );
}

/**
 * Read the stored delivery method using the current meta key with legacy fallback.
 *
 * @param WC_Order $order Order object.
 * @return string
 */
function devhub_get_order_delivery_method( WC_Order $order ): string {
	$delivery_method = sanitize_text_field( (string) $order->get_meta( DEVHUB_ORDER_DELIVERY_METHOD_META, true ) );

	if ( '' !== $delivery_method ) {
		return $delivery_method;
	}

	return sanitize_text_field( (string) $order->get_meta( '_devhub_delivery_method', true ) );
}

/**
 * Remove legacy _devhub_* delivery meta from REST order responses.
 *
 * @param WP_REST_Response $response REST response.
 * @param WC_Order         $order    Order object.
 * @param WP_REST_Request  $request  REST request.
 * @return WP_REST_Response
 */
function devhub_strip_legacy_delivery_meta_from_rest( WP_REST_Response $response, WC_Order $order, WP_REST_Request $request ): WP_REST_Response {
	$data = $response->get_data();

	if ( empty( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) ) {
		return $response;
	}

	$data['meta_data'] = array_values(
		array_filter(
			$data['meta_data'],
			static function ( $meta_item ) {
				if ( ! is_array( $meta_item ) ) {
					return true;
				}

				$key = (string) ( $meta_item['key'] ?? '' );

				return 0 !== strpos( $key, '_devhub_' );
			}
		)
	);

	$response->set_data( $data );

	return $response;
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
