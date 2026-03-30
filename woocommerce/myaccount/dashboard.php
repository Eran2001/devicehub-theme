<?php
/**
 * My Account dashboard — DeviceHub override
 *
 * Shows welcome text + quick-stat cards grid.
 * Counts marked TODO: replace with real plugin/WC data when available.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();

// TODO: replace 0 values with real counts from WC / plugin APIs
$stats = [
    [
        'key'   => 'downloads',
        'label' => __( 'DOWNLOADS', 'woocommerce' ),
        'count' => 0, // TODO: count customer downloadable products
        'url'   => wc_get_account_endpoint_url( 'downloads' ),
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    ],
    [
        'key'   => 'orders',
        'label' => __( 'ORDERS', 'woocommerce' ),
        'count' => count( wc_get_orders( [ 'customer' => $user_id, 'limit' => -1, 'return' => 'ids' ] ) ),
        'url'   => wc_get_account_endpoint_url( 'orders' ),
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><polyline points="9 11 12 14 17 9"/></svg>',
    ],
    [
        'key'   => 'wishlist',
        'label' => __( 'WISHLIST', 'devicehub-theme' ),
        'count' => 0, // TODO: YITH Wishlist or similar
        'url'   => '#',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
    ],
    [
        'key'   => 'coupons',
        'label' => __( 'COUPONES', 'devicehub-theme' ),
        'count' => 0, // TODO: customer coupon count
        'url'   => '#',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    ],
    [
        'key'   => 'points',
        'label' => __( 'POINTS COLLECTED', 'devicehub-theme' ),
        'count' => 0, // TODO: WooCommerce Points & Rewards or similar
        'url'   => '#',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    ],
    [
        'key'   => 'dispute',
        'label' => __( 'Dispute', 'devicehub-theme' ),
        'count' => 0, // TODO: open dispute count
        'url'   => '#',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ],
];
?>

<div class="devhub-dashboard">

    <h2 class="devhub-dashboard__title"><?php esc_html_e( 'Your dashboard', 'woocommerce' ); ?></h2>
    <hr class="devhub-dashboard__divider">

    <p class="devhub-dashboard__intro">
        <?php
        printf(
            wp_kses_post( __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' ) ),
            esc_url( wc_get_account_endpoint_url( 'orders' ) ),
            esc_url( wc_get_account_endpoint_url( 'edit-address' ) ),
            esc_url( wc_get_account_endpoint_url( 'edit-account' ) )
        );
        ?>
    </p>

    <div class="devhub-dashboard-cards">
        <?php foreach ( $stats as $stat ) : ?>
            <a href="<?php echo esc_url( $stat['url'] ); ?>" class="devhub-dashboard-card">
                <?php if ( $stat['count'] > 0 ) : ?>
                    <span class="devhub-dashboard-card__badge"><?php echo esc_html( $stat['count'] ); ?></span>
                <?php endif; ?>
                <span class="devhub-dashboard-card__icon" aria-hidden="true">
                    <?php echo $stat['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted inline SVG ?>
                </span>
                <span class="devhub-dashboard-card__label"><?php echo esc_html( $stat['label'] ); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

</div>
