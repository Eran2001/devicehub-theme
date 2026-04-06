<?php
/**
 * DeviceHub - Checkout delivery method fields for WooCommerce Blocks.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

const DEVHUB_CHECKOUT_DELIVERY_METHOD_FIELD = 'devicehub/delivery_method';
const DEVHUB_CHECKOUT_PICKUP_STORE_FIELD    = 'devicehub/pickup_store';

add_action( 'woocommerce_init', 'devhub_register_checkout_delivery_fields' );
add_action( 'woocommerce_blocks_validate_location_contact_fields', 'devhub_validate_checkout_delivery_fields', 10, 3 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'devhub_store_checkout_delivery_meta', 10, 2 );

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

	$order->update_meta_data( '_devhub_delivery_method', $delivery_method );
	$order->update_meta_data( '_devhub_delivery_method_label', 'pickup' === $delivery_method ? __( 'Pick Up at Store', 'devicehub-theme' ) : __( 'Home Delivery', 'devicehub-theme' ) );

	if ( 'pickup' === $delivery_method && isset( $location_map[ $pickup_store ] ) ) {
		$location = $location_map[ $pickup_store ];
		$order->update_meta_data( '_devhub_pickup_store_label', $location['label'] );
		$order->update_meta_data( '_devhub_pickup_store_address', $location['address'] );
		$order->update_meta_data( '_devhub_pickup_store_details', $location['details'] );
	} else {
		$order->delete_meta_data( '_devhub_pickup_store_label' );
		$order->delete_meta_data( '_devhub_pickup_store_address' );
		$order->delete_meta_data( '_devhub_pickup_store_details' );
	}
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
