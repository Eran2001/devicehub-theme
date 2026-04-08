<?php
/**
 * DeviceHub — Hooks
 *
 * All add_action / remove_action / add_filter overrides that
 * modify the parent Shopire theme and WooCommerce globally.
 *
 * Rules:
 *  - No markup output here — only hook registration
 *  - Page-section hooks (hero, flash, products) live in hooks/*.php
 *  - WooCommerce template overrides live in woocommerce/*.php
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;


// ── Logo ──────────────────────────────────────────────────────────────────────

remove_action('shopire_site_logo', 'shopire_site_logo');
add_action('shopire_site_logo', 'devhub_render_logo');

function devhub_render_logo(): void
{
    ?>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="devhub-logo">
        <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/HUTCHMainLogo.svg'); ?>"
            alt="<?php esc_attr_e('HUTCH Device Hub', 'devicehub-theme'); ?>" height="36" width="auto">
    </a>
    <?php
}


// ── Header — remove Shopire elements not used in DeviceHub ───────────────────

// Top bar (social icons, free shipping text)
remove_action('shopire_site_header', 'shopire_site_header');
add_action('shopire_site_header', '__return_false');

// Nav links (Home, Cart, Checkout)
remove_action('shopire_site_header_navigation', 'shopire_site_header_navigation');
add_action('shopire_site_header_navigation', '__return_false');

// Flash sale button
remove_action('shopire_header_button', 'shopire_header_button');
add_action('shopire_header_button', '__return_false');

// Phone contact on right side
remove_action('shopire_header_contact', 'shopire_header_contact');
add_action('shopire_header_contact', '__return_false');


// ── Shopire page-title banner — suppress on WooCommerce pages ─────────────────
// The banner is replaced by compact inline breadcrumb bars in the WC templates.

add_filter('theme_mod_shopire_hs_site_breadcrumb', function ($val) {
    return (devhub_is_product_page() || devhub_is_product_category_page() || devhub_is_shop_page() || devhub_is_cart_page() || devhub_is_checkout_page() || devhub_is_account_context() || is_404()) ? '0' : $val;
}, 20);


// ── Page bar — inject on cart / checkout (account uses template override) ────

add_action('woocommerce_before_cart', 'devhub_render_page_bar', 5);
add_action('woocommerce_before_checkout_form', 'devhub_render_page_bar', 5);

function devhub_render_page_bar(): void
{
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    $title = (devhub_is_cart_page() || devhub_is_checkout_page() || devhub_is_account_context())
        ? get_the_title()
        : woocommerce_page_title(false);
    ?>
    <div class="devhub-page-bar wf-container">
        <?php woocommerce_breadcrumb(); ?>
        <h1 class="devhub-page-bar__title"><?php echo esc_html($title); ?></h1>
    </div>
    <?php
}


// ── WooCommerce archive title — remove "Category:" prefix ─────────────────────

add_filter('woocommerce_page_title', function ($title) {
    if (is_product_category()) {
        return single_cat_title('', false);
    }
    return $title;
});


// ── Header — add Orders icon before cart ─────────────────────────────────────

add_action('shopire_woo_cart', 'devhub_render_orders_icon', 5);

function devhub_render_orders_icon(): void
{
    if (!class_exists('WooCommerce'))
        return;
    ?>
    <li class="wf_navbar-cart-item">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="wf_navbar-cart-icon"
            title="<?php esc_attr_e('Orders', 'devicehub-theme'); ?>">
            <span class="cart_icon">
                <i class="far fa-box-open" aria-hidden="true"></i>
            </span>
            <span class="screen-reader-text">
                <?php esc_html_e('Orders', 'devicehub-theme'); ?>
            </span>
        </a>
    </li>
    <?php
}


// ── WooCommerce — archive products per page ───────────────────────────────────

add_filter('loop_shop_per_page', fn() => 9, 20);


// ── WooCommerce — brand filter via URL param ?filter_brand=slug1,slug2 ────────
// pwb-brand is a custom taxonomy (PWB Brands plugin), not a pa_* attribute,
// so WooCommerce's built-in layered nav doesn't handle it — we do it here.

add_action('pre_get_posts', 'devhub_filter_archive_by_brand');

function devhub_filter_archive_by_brand(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query()) return;
    if (!devhub_is_shop_page() && !devhub_is_product_category_page() && !devhub_is_product_tag_page()) return;

    $raw = sanitize_text_field(wp_unslash($_GET['filter_brand'] ?? ''));
    if ($raw === '') return;

    $slugs = array_values(array_filter(array_map('sanitize_title', explode(',', $raw))));
    if (empty($slugs)) return;

    $brand_tax_query = ['relation' => 'OR'];

    if (taxonomy_exists('pwb-brand')) {
        $brand_tax_query[] = [
            'taxonomy' => 'pwb-brand',
            'field' => 'slug',
            'terms' => $slugs,
            'operator' => 'IN',
        ];
    }

    if (taxonomy_exists('pa_brand')) {
        $brand_tax_query[] = [
            'taxonomy' => 'pa_brand',
            'field' => 'slug',
            'terms' => $slugs,
            'operator' => 'IN',
        ];
    }

    if (count($brand_tax_query) === 1) return;

    $tax_query   = (array) $query->get('tax_query');
    $tax_query[] = $brand_tax_query;
    $query->set('tax_query', $tax_query);
}


// ── WooCommerce — force our archive-product template ─────────────────────────
// woocommerce_locate_template fires via wc_get_template() — used by archive.
// Single product uses wc_get_template_part() which calls locate_template()
// directly, so content-single-product.php is picked up from the theme
// woocommerce/ folder automatically — no filter needed for it.

add_action('pre_get_posts', 'devhub_search_products_only');

function devhub_search_products_only(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query() || ! $query->is_search()) {
        return;
    }

    if (empty($query->get('post_type'))) {
        $query->set('post_type', ['product']);
    }
}

add_filter('woocommerce_locate_template', 'devhub_locate_template', 10, 3);

function devhub_locate_template(string $template, string $template_name, string $template_path): string
{
    if ($template_name !== 'archive-product.php') return $template;

    $custom = DEVHUB_DIR . '/woocommerce/archive-product.php';
    return file_exists($custom) ? $custom : $template;
}


// ── Debug — template path comment in <head> (remove before production) ────────

add_action('wp_head', 'devhub_debug_template_comment');

function devhub_debug_template_comment(): void
{
    if (!devhub_is_shop_page() && !devhub_is_product_category_page())
        return;
    if (!current_user_can('administrator'))
        return; // Only show to admins

    global $template;
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<!-- DEVHUB TEMPLATE: ' . esc_html($template) . ' -->' . PHP_EOL;
}

// ── WooCommerce — Buy Now redirect to checkout ────────────────────────────────
// product.js adds devhub_buy_now=1 to the cart form before submitting.
// We catch it here and redirect to checkout instead of back to the product page.

add_filter('woocommerce_add_to_cart_redirect', 'devhub_buy_now_redirect');

function devhub_buy_now_redirect(string $url): string
{
    if (! empty($_POST['devhub_buy_now'])) {
        return wc_get_checkout_url();
    }
    return $url;
}


// Override LKR currency symbol to display as 'LKR' instead of රු
add_filter('woocommerce_currency_symbol', function (string $symbol, string $currency): string {
    if ($currency === 'LKR')
        return 'Rs.';
    return $symbol;
}, 10, 2);


// ── Cart / Checkout / Account — force no sidebar (full container width) ────────
add_filter('woocommerce_price_format', function (string $format): string {
    if (get_woocommerce_currency() === 'LKR') {
        return '%1$s %2$s';
    }

    return $format;
});

add_filter('theme_mod_shopire_default_pg_sidebar_option', function ($value) {
    if (is_string($value) && (devhub_is_cart_page() || devhub_is_checkout_page() || devhub_is_account_context())) {
        return 'no_sidebar';
    }
    return $value;
});


// ── Custom account endpoints: Wishlist, Coupons, Points, Dispute ──────────────
// NOTE: After activating, go to Settings > Permalinks and click Save to flush rewrite rules.

add_action('init', 'devhub_register_account_endpoints');
add_action('after_switch_theme', 'devhub_schedule_rewrite_flush');
add_action('init', 'devhub_maybe_flush_rewrite_rules', 20);

function devhub_register_account_endpoints(): void
{
    add_rewrite_endpoint('wishlist',    EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('coupons',     EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('points',      EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('dispute',     EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('gift-cards',  EP_ROOT | EP_PAGES);
}

function devhub_schedule_rewrite_flush(): void
{
    update_option('devhub_flush_rewrite_rules', '1');
}

function devhub_maybe_flush_rewrite_rules(): void
{
    if (get_option('devhub_flush_rewrite_rules') !== '1') {
        return;
    }

    devhub_register_account_endpoints();
    flush_rewrite_rules();
    delete_option('devhub_flush_rewrite_rules');
}

// Register endpoints with WooCommerce so wc_get_account_endpoint_url() works
add_filter('woocommerce_account_menu_items', 'devhub_custom_account_menu_items');

function devhub_custom_account_menu_items(array $items): array
{
    $items['wishlist']   = __('Wishlist',          'devicehub-theme');
    $items['coupons']    = __('Coupons',           'devicehub-theme');
    $items['points']     = __('Points Collected',  'devicehub-theme');
    $items['dispute']    = __('Dispute',           'devicehub-theme');
    $items['gift-cards'] = __('Your Gift Cards',   'devicehub-theme');
    return $items;
}

// Wire content for each endpoint
add_action('woocommerce_account_wishlist_endpoint', function (): void {
    include DEVHUB_DIR . '/woocommerce/myaccount/wishlist.php';
});

add_action('woocommerce_account_coupons_endpoint', function (): void {
    include DEVHUB_DIR . '/woocommerce/myaccount/coupons.php';
});

add_action('woocommerce_account_points_endpoint', function (): void {
    include DEVHUB_DIR . '/woocommerce/myaccount/points.php';
});

add_action('woocommerce_account_dispute_endpoint', function (): void {
    include DEVHUB_DIR . '/woocommerce/myaccount/dispute.php';
});

add_action('woocommerce_account_gift-cards_endpoint', function (): void {
    include DEVHUB_DIR . '/woocommerce/myaccount/gift-cards.php';
});
