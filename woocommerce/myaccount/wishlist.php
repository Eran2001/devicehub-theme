<?php
/**
 * Wishlist — DeviceHub account page
 *
 * Shows wishlist items (via YITH WooCommerce Wishlist plugin) or a
 * branded empty-state fallback matching the Downloads fallback standard.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

// Try to pull wishlist items from YITH WooCommerce Wishlist plugin.
$wishlist_products = [];

if ( function_exists( 'YITH_WCWL' ) ) {
    $args     = [ 'user_id' => get_current_user_id(), 'is_default' => 'yes' ];
    $wishlist = YITH_WCWL()->get_wishlist( $args );
    if ( $wishlist && method_exists( $wishlist, 'get_items' ) ) {
        $wishlist_products = $wishlist->get_items();
    }
}

$has_items = ! empty( $wishlist_products );

do_action( 'woocommerce_before_account_wishlist', $has_items );
?>

<?php if ( $has_items ) : ?>

    <div class="devhub-wishlist-grid woocommerce">
        <?php foreach ( $wishlist_products as $item ) :
            $product = wc_get_product( $item->get_product_id() );
            if ( ! $product || ! $product->is_visible() ) continue;
            devhub_render_product_card( $product );
        endforeach; ?>
    </div>

<?php else : ?>

    <div class="devhub-empty-state">

        <!-- Icon stack -->
        <div class="devhub-empty-state__icon-wrap">
            <div class="devhub-empty-state__layer devhub-empty-state__layer--1"></div>
            <div class="devhub-empty-state__layer devhub-empty-state__layer--2"></div>
            <div class="devhub-empty-state__card">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
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
            <h4><?php esc_html_e( 'Your wishlist is empty.', 'devicehub-theme' ); ?></h4>
            <p><?php esc_html_e( "You haven't saved any products to your wishlist yet. Browse our catalog and tap the heart icon to save products for later.", 'devicehub-theme' ); ?></p>
        </div>

        <!-- Actions -->
        <div class="devhub-empty-state__actions">
            <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="devhub-empty-state__btn devhub-empty-state__btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php esc_html_e( 'Browse Products', 'devicehub-theme' ); ?>
            </a>
        </div>

    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_wishlist', $has_items ); ?>
