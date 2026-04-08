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
 * Build the checkout URL that records the guest-checkout selection.
 */
function devhub_get_guest_checkout_continue_url(): string {
	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' );

	return add_query_arg( 'devhub_guest_checkout', '1', $checkout_url );
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
