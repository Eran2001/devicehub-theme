<?php
/**
 * Email OTP verification for WooCommerce account registration.
 *
 * Adds an email-verification step to the existing registration form while
 * preserving WooCommerce's core account-creation flow.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

const DEVHUB_EMAIL_REGISTRATION_OTP_TTL = 10 * MINUTE_IN_SECONDS;
const DEVHUB_EMAIL_REGISTRATION_OTP_RESEND_COOLDOWN = 60;
const DEVHUB_EMAIL_REGISTRATION_OTP_MAX_ATTEMPTS = 5;

add_action( 'wp_ajax_nopriv_devhub_send_email_registration_otp', 'devhub_ajax_send_email_registration_otp' );
add_filter( 'woocommerce_registration_errors', 'devhub_validate_email_registration_otp', 10, 3 );
add_action( 'woocommerce_created_customer', 'devhub_clear_email_registration_otp_after_customer_creation' );

/**
 * Normalize an email address for OTP state lookup.
 */
function devhub_normalize_registration_email( string $email ): string {
	$email = sanitize_email( wp_unslash( $email ) );

	return '' !== $email ? strtolower( $email ) : '';
}

/**
 * Mask an email address for UI copy.
 */
function devhub_mask_registration_email( string $email ): string {
	$parts = explode( '@', $email, 2 );

	if ( 2 !== count( $parts ) ) {
		return $email;
	}

	$local  = $parts[0];
	$domain = $parts[1];

	if ( strlen( $local ) <= 2 ) {
		$masked_local = substr( $local, 0, 1 ) . '*';
	} else {
		$masked_local = substr( $local, 0, 1 ) . str_repeat( '*', max( 1, strlen( $local ) - 2 ) ) . substr( $local, -1 );
	}

	return $masked_local . '@' . $domain;
}

/**
 * Transient key for a registration email OTP session.
 */
function devhub_get_email_registration_otp_key( string $email ): string {
	return 'devhub_email_reg_otp_' . md5( $email );
}

/**
 * Store a new registration email OTP state.
 */
function devhub_store_email_registration_otp_state( string $email, string $otp ): array {
	$state = [
		'email'      => $email,
		'otp_hash'   => wp_hash_password( $otp ),
		'expires_at' => time() + DEVHUB_EMAIL_REGISTRATION_OTP_TTL,
		'sent_at'    => time(),
		'attempts'   => 0,
		'verified'   => false,
	];

	set_transient( devhub_get_email_registration_otp_key( $email ), $state, DEVHUB_EMAIL_REGISTRATION_OTP_TTL );

	return $state;
}

/**
 * Send the registration email OTP.
 */
function devhub_dispatch_email_registration_otp( string $email, string $otp ): true|WP_Error {
	$subject = sprintf(
		/* translators: %s: Site name. */
		__( 'Your %s verification code', 'devicehub-theme' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
	);

	$message = sprintf(
		/* translators: %1$s: OTP code. %2$d: Minutes until expiration. */
		__( "Your DeviceHub verification code is %1\$s.\n\nIt expires in %2\$d minutes.\n\nIf you did not request this code, you can ignore this email.", 'devicehub-theme' ),
		$otp,
		(int) floor( DEVHUB_EMAIL_REGISTRATION_OTP_TTL / MINUTE_IN_SECONDS )
	);

	$headers = [
		'Content-Type: text/plain; charset=UTF-8',
	];

	$subject = (string) apply_filters( 'devhub_email_registration_otp_subject', $subject, $email, $otp );
	$message = (string) apply_filters( 'devhub_email_registration_otp_message', $message, $email, $otp );
	$headers = apply_filters( 'devhub_email_registration_otp_headers', $headers, $email, $otp );

	$sent = wp_mail( $email, $subject, $message, $headers );

	if ( $sent ) {
		return true;
	}

	return new WP_Error(
		'devhub_email_otp_delivery_failed',
		__( 'We could not send the verification email right now. Please try again shortly.', 'devicehub-theme' )
	);
}

/**
 * AJAX: send a registration email OTP.
 */
function devhub_ajax_send_email_registration_otp(): void {
	check_ajax_referer( 'devhub_email_registration_otp', 'nonce' );

	if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Account registration is currently unavailable.', 'devicehub-theme' ),
			],
			403
		);
	}

	$raw_email = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
	$email     = devhub_normalize_registration_email( $raw_email );

	if ( ! is_email( $email ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Enter a valid email address to continue.', 'devicehub-theme' ),
			],
			400
		);
	}

	if ( email_exists( $email ) ) {
		wp_send_json_error(
			[
				'message' => __( 'An account already exists with this email address. Use email login instead.', 'devicehub-theme' ),
			],
			409
		);
	}

	$existing_state = get_transient( devhub_get_email_registration_otp_key( $email ) );

	if ( is_array( $existing_state ) && ! empty( $existing_state['sent_at'] ) ) {
		$remaining = DEVHUB_EMAIL_REGISTRATION_OTP_RESEND_COOLDOWN - ( time() - (int) $existing_state['sent_at'] );

		if ( $remaining > 0 ) {
			wp_send_json_error(
				[
					/* translators: %d: Remaining resend cooldown in seconds. */
					'message' => sprintf( __( 'Please wait %d seconds before requesting another code.', 'devicehub-theme' ), $remaining ),
				],
				429
			);
		}
	}

	$otp      = (string) wp_rand( 100000, 999999 );
	$state    = devhub_store_email_registration_otp_state( $email, $otp );
	$delivery = devhub_dispatch_email_registration_otp( $email, $otp );

	if ( is_wp_error( $delivery ) ) {
		delete_transient( devhub_get_email_registration_otp_key( $email ) );

		wp_send_json_error(
			[
				'message' => $delivery->get_error_message(),
			],
			500
		);
	}

	wp_send_json_success(
		[
			'email'       => $email,
			'maskedEmail' => devhub_mask_registration_email( $email ),
			'expiresIn'   => max( 1, (int) ( $state['expires_at'] - time() ) ),
			'message'     => sprintf(
				/* translators: %s: Masked email address. */
				__( 'We sent a 6-digit code to %s. Enter it below to finish creating your account.', 'devicehub-theme' ),
				devhub_mask_registration_email( $email )
			),
		]
	);
}

/**
 * Determine if OTP validation should run for the current registration post.
 */
function devhub_should_validate_email_registration_otp(): bool {
	$panel = isset( $_POST['devhub_auth_panel'] ) ? sanitize_key( wp_unslash( $_POST['devhub_auth_panel'] ) ) : '';

	return 'register' === $panel;
}

/**
 * Validate the posted registration email OTP before WooCommerce creates the user.
 *
 * @param WP_Error $errors Existing validation errors.
 * @param string   $username Submitted username.
 * @param string   $email Submitted email.
 */
function devhub_validate_email_registration_otp( WP_Error $errors, string $username, string $email ): WP_Error {
	if ( ! devhub_should_validate_email_registration_otp() ) {
		return $errors;
	}

	$email = devhub_normalize_registration_email( $email );

	if ( '' === $email || ! is_email( $email ) ) {
		return $errors;
	}

	if ( email_exists( $email ) ) {
		return $errors;
	}

	$key   = devhub_get_email_registration_otp_key( $email );
	$state = get_transient( $key );

	if ( ! is_array( $state ) || empty( $state['otp_hash'] ) || empty( $state['expires_at'] ) ) {
		$errors->add(
			'devhub_email_otp_missing',
			__( 'Verify your email address with the code we sent before creating your account.', 'devicehub-theme' )
		);

		return $errors;
	}

	if ( time() > (int) $state['expires_at'] ) {
		delete_transient( $key );
		$errors->add(
			'devhub_email_otp_expired',
			__( 'Your email verification code has expired. Request a new code and try again.', 'devicehub-theme' )
		);

		return $errors;
	}

	if ( ! empty( $state['verified'] ) ) {
		return $errors;
	}

	$attempts = isset( $state['attempts'] ) ? (int) $state['attempts'] : 0;

	if ( $attempts >= DEVHUB_EMAIL_REGISTRATION_OTP_MAX_ATTEMPTS ) {
		delete_transient( $key );
		$errors->add(
			'devhub_email_otp_attempts',
			__( 'Too many incorrect verification attempts. Request a new code and try again.', 'devicehub-theme' )
		);

		return $errors;
	}

	$otp = isset( $_POST['devhub_email_otp'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['devhub_email_otp'] ) ) : '';
	$otp = is_string( $otp ) ? $otp : '';

	if ( 6 !== strlen( $otp ) ) {
		$errors->add(
			'devhub_email_otp_required',
			__( 'Enter the 6-digit email verification code to create your account.', 'devicehub-theme' )
		);

		return $errors;
	}

	if ( ! wp_check_password( $otp, (string) $state['otp_hash'] ) ) {
		++$attempts;
		$state['attempts'] = $attempts;
		$ttl               = max( 1, (int) $state['expires_at'] - time() );

		set_transient( $key, $state, $ttl );

		$errors->add(
			'devhub_email_otp_invalid',
			DEVHUB_EMAIL_REGISTRATION_OTP_MAX_ATTEMPTS - $attempts > 0
				? sprintf(
					/* translators: %d: Remaining OTP attempts. */
					__( 'Incorrect email verification code. You have %d attempt(s) remaining.', 'devicehub-theme' ),
					DEVHUB_EMAIL_REGISTRATION_OTP_MAX_ATTEMPTS - $attempts
				)
				: __( 'Incorrect email verification code. Request a new code and try again.', 'devicehub-theme' )
		);

		return $errors;
	}

	$state['verified']    = true;
	$state['verified_at'] = time();
	$ttl                  = max( 1, (int) $state['expires_at'] - time() );

	set_transient( $key, $state, $ttl );

	return $errors;
}

/**
 * Remove OTP state once the customer account is created.
 */
function devhub_clear_email_registration_otp_after_customer_creation( int $customer_id ): void {
	$user = get_user_by( 'id', $customer_id );

	if ( ! $user instanceof WP_User || empty( $user->user_email ) ) {
		return;
	}

	delete_transient( devhub_get_email_registration_otp_key( devhub_normalize_registration_email( $user->user_email ) ) );
	update_user_meta( $customer_id, '_devhub_email_verified_at', time() );
}
