<?php
/**
 * Mobile OTP authentication for DeviceHub.
 *
 * Provides a phone-number login/signup flow for WooCommerce customers.
 * Actual SMS delivery is delegated to the `devhub_send_mobile_otp` filter so
 * production can plug in Twilio, Vonage, or another provider later.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

const DEVHUB_MOBILE_OTP_TTL = 10 * MINUTE_IN_SECONDS;
const DEVHUB_MOBILE_OTP_RESEND_COOLDOWN = 60;
const DEVHUB_MOBILE_OTP_MAX_ATTEMPTS = 5;

add_action( 'wp_ajax_nopriv_devhub_send_mobile_otp', 'devhub_ajax_send_mobile_otp' );
add_action( 'wp_ajax_nopriv_devhub_verify_mobile_otp', 'devhub_ajax_verify_mobile_otp' );

/**
 * Normalize a phone number for storage and lookup.
 */
function devhub_normalize_phone_number( string $phone ): string {
	$phone = trim( wp_strip_all_tags( $phone ) );

	if ( '' === $phone ) {
		return '';
	}

	$has_plus = str_starts_with( $phone, '+' );
	$digits   = preg_replace( '/\D+/', '', $phone );

	if ( ! is_string( $digits ) ) {
		return '';
	}

	return $has_plus ? '+' . $digits : $digits;
}

/**
 * Validate whether a normalized phone number is acceptable for OTP login.
 */
function devhub_is_valid_mobile_number( string $phone ): bool {
	$digits = ltrim( $phone, '+' );
	$length = strlen( $digits );

	return $length >= 9 && $length <= 15;
}

/**
 * Mask a phone number for UI messages.
 */
function devhub_mask_mobile_number( string $phone ): string {
	$prefix = str_starts_with( $phone, '+' ) ? '+' : '';
	$digits = ltrim( $phone, '+' );
	$length = strlen( $digits );

	if ( $length <= 4 ) {
		return $phone;
	}

	return $prefix . str_repeat( '*', max( 0, $length - 4 ) ) . substr( $digits, -4 );
}

/**
 * Transient key for the OTP state.
 */
function devhub_get_mobile_otp_key( string $phone ): string {
	return 'devhub_mobile_otp_' . md5( $phone );
}

/**
 * Determine whether the current site is a local/dev environment.
 */
function devhub_is_local_mobile_auth_environment(): bool {
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	$host        = wp_parse_url( home_url(), PHP_URL_HOST );
	$host        = is_string( $host ) ? strtolower( $host ) : '';

	if ( in_array( $environment, [ 'local', 'development' ], true ) ) {
		return true;
	}

	return '' !== $host && (
		'localhost' === $host
		|| str_ends_with( $host, '.local' )
		|| str_ends_with( $host, '.test' )
	);
}

/**
 * Default redirect after successful auth.
 */
function devhub_get_auth_success_redirect_url(): string {
	if (
		function_exists( 'wc_get_checkout_url' )
		&& function_exists( 'devhub_should_require_checkout_auth' )
		&& devhub_should_require_checkout_auth()
	) {
		return wc_get_checkout_url();
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return home_url( '/' );
}

/**
 * Find an existing user by normalized phone number.
 */
function devhub_get_user_by_mobile_number( string $phone ): ?WP_User {
	$users = get_users(
		[
			'number'     => 1,
			'meta_key'   => '_devhub_phone_normalized',
			'meta_value' => $phone,
			'fields'     => 'all',
		]
	);

	if ( ! empty( $users[0] ) && $users[0] instanceof WP_User ) {
		return $users[0];
	}

	$legacy_users = get_users(
		[
			'number'     => 1,
			'meta_key'   => 'billing_phone',
			'meta_value' => $phone,
			'fields'     => 'all',
		]
	);

	if ( empty( $legacy_users[0] ) || ! $legacy_users[0] instanceof WP_User ) {
		return null;
	}

	update_user_meta( $legacy_users[0]->ID, '_devhub_phone_normalized', $phone );

	return $legacy_users[0];
}

/**
 * Generate a unique username for a mobile-auth customer.
 */
function devhub_generate_mobile_username( string $phone ): string {
	$digits   = ltrim( $phone, '+' );
	$base     = sanitize_user( 'mobile_' . substr( $digits, -10 ), true );
	$username = '' !== $base ? $base : 'mobile_user';
	$suffix   = 1;

	while ( username_exists( $username ) ) {
		$username = sanitize_user( $base . '_' . $suffix, true );
		++$suffix;
	}

	return $username;
}

/**
 * Create a WooCommerce customer for a verified mobile number.
 *
 * WordPress does not require an email address at the DB level, so a
 * mobile-first customer can be created and prompted to add email later.
 */
function devhub_create_mobile_customer( string $phone ): WP_User|WP_Error {
	$user_id = wp_insert_user(
		[
			'user_login'   => devhub_generate_mobile_username( $phone ),
			'user_pass'    => wp_generate_password( 32, true, true ),
			'user_email'   => '',
			'display_name' => $phone,
			'nickname'     => $phone,
			'role'         => 'customer',
		]
	);

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, '_devhub_phone_normalized', $phone );

	$user = get_user_by( 'id', $user_id );

	if ( ! $user instanceof WP_User ) {
		return new WP_Error(
			'devhub_mobile_user_missing',
			__( 'Your mobile account was created, but the session could not be completed. Please try again.', 'devicehub-theme' )
		);
	}

	return $user;
}

/**
 * Store a new OTP state for a phone number.
 */
function devhub_store_mobile_otp_state( string $phone, string $otp ): array {
	$state = [
		'phone'      => $phone,
		'otp_hash'   => wp_hash_password( $otp ),
		'expires_at' => time() + DEVHUB_MOBILE_OTP_TTL,
		'sent_at'    => time(),
		'attempts'   => 0,
	];

	set_transient( devhub_get_mobile_otp_key( $phone ), $state, DEVHUB_MOBILE_OTP_TTL );

	return $state;
}

/**
 * Send the OTP over the configured delivery channel.
 */
function devhub_dispatch_mobile_otp( string $phone, string $otp ): array|WP_Error {
	$message = sprintf(
		/* translators: %1$s: One-time code. %2$d: Minutes until expiration. */
		__( 'Your DeviceHub verification code is %1$s. It expires in %2$d minutes.', 'devicehub-theme' ),
		$otp,
		(int) floor( DEVHUB_MOBILE_OTP_TTL / MINUTE_IN_SECONDS )
	);

	$delivery = apply_filters(
		'devhub_send_mobile_otp',
		false,
		[
			'phone'   => $phone,
			'otp'     => $otp,
			'message' => $message,
		]
	);

	if ( is_wp_error( $delivery ) ) {
		return $delivery;
	}

	if ( false !== $delivery ) {
		return [
			'sent' => true,
		];
	}

	if ( devhub_is_local_mobile_auth_environment() ) {
		return [
			'sent'     => true,
			'debug_otp' => $otp,
		];
	}

	return new WP_Error(
		'devhub_mobile_delivery_missing',
		__( 'Mobile OTP delivery is not configured yet. Connect an SMS provider before using this flow in production.', 'devicehub-theme' )
	);
}

/**
 * AJAX: send a mobile OTP.
 */
function devhub_ajax_send_mobile_otp(): void {
	check_ajax_referer( 'devhub_mobile_auth', 'nonce' );

	$raw_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$phone     = devhub_normalize_phone_number( $raw_phone );

	if ( ! devhub_is_valid_mobile_number( $phone ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Enter a valid mobile number to continue.', 'devicehub-theme' ),
			],
			400
		);
	}

	$existing_state = get_transient( devhub_get_mobile_otp_key( $phone ) );

	if ( is_array( $existing_state ) && ! empty( $existing_state['sent_at'] ) ) {
		$remaining = DEVHUB_MOBILE_OTP_RESEND_COOLDOWN - ( time() - (int) $existing_state['sent_at'] );

		if ( $remaining > 0 ) {
			wp_send_json_error(
				[
					/* translators: %d: Remaining resend cooldown in seconds. */
					'message' => sprintf( __( 'Please wait %d seconds before requesting another OTP.', 'devicehub-theme' ), $remaining ),
				],
				429
			);
		}
	}

	$otp            = (string) wp_rand( 100000, 999999 );
	$state          = devhub_store_mobile_otp_state( $phone, $otp );
	$delivery       = devhub_dispatch_mobile_otp( $phone, $otp );
	$masked_phone   = devhub_mask_mobile_number( $phone );
	$expires_in     = max( 1, (int) ( $state['expires_at'] - time() ) );

	if ( is_wp_error( $delivery ) ) {
		delete_transient( devhub_get_mobile_otp_key( $phone ) );

		wp_send_json_error(
			[
				'message' => $delivery->get_error_message(),
			],
			500
		);
	}

	$response = [
		'phone'       => $phone,
		'maskedPhone' => $masked_phone,
		'expiresIn'   => $expires_in,
		'message'     => sprintf(
			/* translators: %s: Masked phone number. */
			__( 'Enter the 6-digit code sent to %s.', 'devicehub-theme' ),
			$masked_phone
		),
	];

	if ( ! empty( $delivery['debug_otp'] ) ) {
		$response['debugOtp'] = (string) $delivery['debug_otp'];
	}

	wp_send_json_success( $response );
}

/**
 * AJAX: verify OTP, then log in or create the customer.
 */
function devhub_ajax_verify_mobile_otp(): void {
	check_ajax_referer( 'devhub_mobile_auth', 'nonce' );

	$raw_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$phone     = devhub_normalize_phone_number( $raw_phone );
	$otp       = isset( $_POST['otp'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['otp'] ) ) : '';
	$otp       = is_string( $otp ) ? $otp : '';

	if ( ! devhub_is_valid_mobile_number( $phone ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Enter a valid mobile number first.', 'devicehub-theme' ),
			],
			400
		);
	}

	if ( 6 !== strlen( $otp ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Enter the 6-digit OTP sent to your mobile.', 'devicehub-theme' ),
			],
			400
		);
	}

	$key   = devhub_get_mobile_otp_key( $phone );
	$state = get_transient( $key );

	if ( ! is_array( $state ) || empty( $state['otp_hash'] ) || empty( $state['expires_at'] ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Your OTP session has expired. Request a new code and try again.', 'devicehub-theme' ),
			],
			400
		);
	}

	if ( time() > (int) $state['expires_at'] ) {
		delete_transient( $key );

		wp_send_json_error(
			[
				'message' => __( 'This OTP has expired. Request a new code and try again.', 'devicehub-theme' ),
			],
			400
		);
	}

	$attempts = isset( $state['attempts'] ) ? (int) $state['attempts'] : 0;

	if ( $attempts >= DEVHUB_MOBILE_OTP_MAX_ATTEMPTS ) {
		delete_transient( $key );

		wp_send_json_error(
			[
				'message' => __( 'Too many incorrect OTP attempts. Request a new code and try again.', 'devicehub-theme' ),
			],
			429
		);
	}

	if ( ! wp_check_password( $otp, (string) $state['otp_hash'] ) ) {
		++$attempts;
		$state['attempts'] = $attempts;
		$ttl               = max( 1, (int) $state['expires_at'] - time() );

		set_transient( $key, $state, $ttl );

		wp_send_json_error(
			[
				'message' => DEVHUB_MOBILE_OTP_MAX_ATTEMPTS - $attempts > 0
					? sprintf(
						/* translators: %d: Remaining OTP attempts. */
						__( 'Incorrect OTP. You have %d attempt(s) remaining.', 'devicehub-theme' ),
						DEVHUB_MOBILE_OTP_MAX_ATTEMPTS - $attempts
					)
					: __( 'Incorrect OTP. Request a new code and try again.', 'devicehub-theme' ),
			],
			400
		);
	}

	delete_transient( $key );

	$user = devhub_get_user_by_mobile_number( $phone );

	if ( ! $user instanceof WP_User ) {
		$user = devhub_create_mobile_customer( $phone );
	}

	if ( is_wp_error( $user ) ) {
		wp_send_json_error(
			[
				'message' => $user->get_error_message(),
			],
			500
		);
	}

	update_user_meta( $user->ID, 'billing_phone', $phone );
	update_user_meta( $user->ID, '_devhub_phone_normalized', $phone );

	wp_clear_auth_cookie();
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );

	$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : '';
	$redirect = '' !== $redirect ? $redirect : devhub_get_auth_success_redirect_url();

	wp_send_json_success(
		[
			'redirect' => $redirect,
			'message'  => __( 'Mobile verification complete.', 'devicehub-theme' ),
		]
	);
}
