<?php
/**
 * My Account navigation — DeviceHub override
 *
 * Custom nav with icons + "Your account" accordion group.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

// Check if any "Your account" sub-item is currently active.
$account_sub_endpoints = [ 'edit-account', 'edit-address', 'payment-methods' ];
$account_group_open    = false;
foreach ( $account_sub_endpoints as $ep ) {
    if ( strpos( wc_get_account_menu_item_classes( $ep ), 'is-active' ) !== false ) {
        $account_group_open = true;
        break;
    }
}
?>

<nav class="devhub-account-nav" aria-label="<?php esc_attr_e( 'My account navigation', 'woocommerce' ); ?>">
    <ul>

        <!-- Dashboard -->
        <li class="devhub-account-nav__item <?php echo esc_attr( wc_get_account_menu_item_classes( 'dashboard' ) ); ?>">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>">
                <span class="devhub-account-nav__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </span>
                <?php esc_html_e( 'Dashboard', 'woocommerce' ); ?>
            </a>
        </li>

        <!-- Downloads -->
        <li class="devhub-account-nav__item <?php echo esc_attr( wc_get_account_menu_item_classes( 'downloads' ) ); ?>">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'downloads' ) ); ?>">
                <span class="devhub-account-nav__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                        <polyline points="8 10 12 14 16 10"/>
                    </svg>
                </span>
                <?php esc_html_e( 'Downloads', 'woocommerce' ); ?>
            </a>
        </li>

        <!-- Orders -->
        <li class="devhub-account-nav__item <?php echo esc_attr( wc_get_account_menu_item_classes( 'orders' ) ); ?>">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
                <span class="devhub-account-nav__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </span>
                <?php esc_html_e( 'Orders', 'woocommerce' ); ?>
            </a>
        </li>

        <!-- Your account — accordion group -->
        <li class="devhub-account-nav__item devhub-account-nav__group<?php echo $account_group_open ? ' is-open' : ''; ?>">
            <button class="devhub-account-nav__group-btn" type="button" aria-expanded="<?php echo $account_group_open ? 'true' : 'false'; ?>">
                <span class="devhub-account-nav__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <circle cx="9" cy="12" r="2.5"/>
                        <line x1="14" y1="10" x2="20" y2="10"/>
                        <line x1="14" y1="14" x2="18" y2="14"/>
                    </svg>
                </span>
                <?php esc_html_e( 'Your account', 'devicehub-theme' ); ?>
                <span class="devhub-account-nav__chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </span>
            </button>
            <ul class="devhub-account-nav__sub">
                <li class="<?php echo esc_attr( wc_get_account_menu_item_classes( 'edit-account' ) ); ?>">
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">
                        <?php esc_html_e( 'Account info', 'devicehub-theme' ); ?>
                    </a>
                </li>
                <li class="<?php echo esc_attr( wc_get_account_menu_item_classes( 'edit-address' ) ); ?>">
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>">
                        <?php esc_html_e( 'Addresses', 'woocommerce' ); ?>
                    </a>
                </li>
                <li class="<?php echo esc_attr( wc_get_account_menu_item_classes( 'payment-methods' ) ); ?>">
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'payment-methods' ) ); ?>">
                        <?php esc_html_e( 'Payment method', 'devicehub-theme' ); ?>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Your Gift Cards -->
        <li class="devhub-account-nav__item <?php echo esc_attr( wc_get_account_menu_item_classes( 'gift-cards' ) ); ?>">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'gift-cards' ) ); ?>">
                <span class="devhub-account-nav__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <path d="M16 7V5a2 2 0 0 0-4 0v2"/>
                        <path d="M8 7V5a2 2 0 0 1 4 0v2"/>
                        <line x1="12" y1="7" x2="12" y2="21"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                    </svg>
                </span>
                <?php esc_html_e( 'Your Gift Cards', 'devicehub-theme' ); ?>
            </a>
        </li>

    </ul>
</nav>

