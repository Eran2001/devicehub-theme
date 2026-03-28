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
    if (! is_shop() && ! is_product_category() && ! is_product_tag()) return;

    $raw = sanitize_text_field(wp_unslash($_GET['filter_brand'] ?? ''));
    if ($raw === '') return;

    $slugs = array_values(array_filter(array_map('sanitize_title', explode(',', $raw))));
    if (empty($slugs)) return;

    $tax_query   = (array) $query->get('tax_query');
    $tax_query[] = [
        'taxonomy' => 'pwb-brand',
        'field'    => 'slug',
        'terms'    => $slugs,
        'operator' => 'IN',
    ];
    $query->set('tax_query', $tax_query);
}


// ── WooCommerce — force our archive-product template ─────────────────────────
// woocommerce_locate_template fires via wc_get_template() — used by archive.
// Single product uses wc_get_template_part() which calls locate_template()
// directly, so content-single-product.php is picked up from the theme
// woocommerce/ folder automatically — no filter needed for it.

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
    if (!is_shop() && !is_product_category())
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
        return 'LKR';
    return $symbol;
}, 10, 2);