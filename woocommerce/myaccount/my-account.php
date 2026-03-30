<?php
/**
 * My Account page — DeviceHub override
 *
 * Two-column layout: sidebar (user info + nav) + main content.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$display_name = $current_user->display_name ?: $current_user->user_login;
$user_email   = $current_user->user_email;
$logout_url   = wc_get_account_endpoint_url( 'customer-logout' );
?>

<div class="devhub-account-wrap wf-container">

    <aside class="devhub-account-sidebar">

        <div class="devhub-account-user-card">
            <div class="devhub-account-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="44" height="44" aria-hidden="true">
                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v2h20v-2c0-3.33-6.67-5-10-5z"/>
                </svg>
            </div>
            <div class="devhub-account-user-info">
                <strong class="devhub-account-user-name"><?php echo esc_html( $display_name ); ?></strong>
                <span class="devhub-account-user-email"><?php echo esc_html( $user_email ); ?></span>
                <a href="<?php echo esc_url( $logout_url ); ?>" class="devhub-account-logout">Logout</a>
            </div>
        </div>

        <?php do_action( 'woocommerce_account_navigation' ); ?>

    </aside>

    <main class="devhub-account-content">
        <?php do_action( 'woocommerce_account_content' ); ?>
    </main>

</div>
