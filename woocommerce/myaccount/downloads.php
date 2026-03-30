<?php
/**
 * Downloads — DeviceHub override
 *
 * Shows downloadable products or a branded empty-state fallback.
 * Based on WooCommerce template version 7.8.0.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action( 'woocommerce_before_account_downloads', $has_downloads );
?>

<?php if ( $has_downloads ) : ?>

    <?php do_action( 'woocommerce_before_available_downloads' ); ?>
    <?php do_action( 'woocommerce_available_downloads', $downloads ); ?>
    <?php do_action( 'woocommerce_after_available_downloads' ); ?>

<?php else : ?>

    <div class="devhub-empty-state">

        <!-- Icon stack -->
        <div class="devhub-empty-state__icon-wrap">
            <div class="devhub-empty-state__layer devhub-empty-state__layer--1"></div>
            <div class="devhub-empty-state__layer devhub-empty-state__layer--2"></div>
            <div class="devhub-empty-state__card">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </div>
            <div class="devhub-empty-state__badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <div class="devhub-empty-state__text">
            <h4><?php esc_html_e( 'No downloads available yet.', 'woocommerce' ); ?></h4>
            <p><?php esc_html_e( "You haven't purchased any downloadable products yet. Browse our catalog to find products with downloadable content.", 'devicehub-theme' ); ?></p>
        </div>

        <!-- Actions -->
        <div class="devhub-empty-state__actions">
            <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="devhub-empty-state__btn devhub-empty-state__btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php esc_html_e( 'Browse Products', 'woocommerce' ); ?>
            </a>
        </div>

    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
