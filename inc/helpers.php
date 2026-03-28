<?php
/**
 * DeviceHub — Helpers
 *
 * Reusable utility functions.
 * Rules:
 *  - No output (no echo, no markup) — return values only
 *  - No add_action / add_filter calls — that's hooks.php
 *  - Exception: devhub_render_product_card() outputs markup
 *    because it's a template renderer called from hooks, not a data helper.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;


// ── Product helpers ───────────────────────────────────────────────────────────

/**
 * Get the discount percentage between regular and sale price.
 * Returns 0 if the product is not on sale or regular price is 0.
 */
function devhub_get_discount_percent(WC_Product $product): int
{
    if (!$product->is_on_sale())
        return 0;

    $regular = (float) $product->get_regular_price();
    $sale = (float) $product->get_sale_price();

    if ($regular <= 0)
        return 0;

    return (int) round((($regular - $sale) / $regular) * 100);
}

/**
 * Get the lowest price across all products in a WooCommerce category.
 * Used for "From Rs.X" display in category cards.
 *
 * @param int $term_id  product_cat term ID
 * @return float|null   null if no products found
 */
function devhub_get_category_min_price(int $term_id): ?float
{
    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_key' => '_price',
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $term_id,
            ]
        ],
        'fields' => 'ids',
    ]);

    if (empty($query->posts))
        return null;

    $product = wc_get_product($query->posts[0]);
    return $product ? (float) $product->get_price() : null;
}

/**
 * Get brand slugs for a product as a space-separated string.
 * Used for JS-based brand filtering via data-brands attribute.
 */
function devhub_get_product_brand_slugs(int $product_id): string
{
    $terms = wp_get_post_terms($product_id, 'pwb-brand', ['fields' => 'slugs']);
    return (!is_wp_error($terms) && !empty($terms)) ? implode(' ', $terms) : '';
}

/**
 * Check whether a product has a bundle package configured.
 * Bundle data stored in post meta 'devhub_bundles'.
 */
function devhub_product_has_bundle(int $product_id): bool
{
    return !empty(get_post_meta($product_id, 'devhub_bundles', true));
}


/**
 * Return the local placeholder SVG URL for a product.
 * Broadband-category products get the router image; everything else gets the phone image.
 */
function devhub_get_product_placeholder_img(WC_Product $product): string
{
    if (has_term('broadband', 'product_cat', $product->get_id())) {
        return DEVHUB_URI . '/assets/images/Original-Router-Img.svg';
    }
    return DEVHUB_URI . '/assets/images/Original-Img.svg';
}


// ── Template renderer ─────────────────────────────────────────────────────────

/**
 * Render a single product card.
 *
 * This is the one shared card template used on:
 *  - Home products section
 *  - Archive / shop grid
 *  - Search results
 *
 * Why here and not template-parts/?
 * Because it's called programmatically from within WP_Query loops
 * in hook files. get_template_part() doesn't accept arguments cleanly
 * without globals or set_query_var — passing a $product object directly
 * is simpler and explicit.
 *
 * @param WC_Product $product
 */
function devhub_render_product_card(WC_Product $product, string $img_override = ''): void
{
    $img_url = $img_override !== '' ? $img_override : wp_get_attachment_image_url($product->get_image_id(), 'devhub-card');
    $discount = devhub_get_discount_percent($product);
    $in_stock = $product->is_in_stock();
    $has_bundle = devhub_product_has_bundle($product->get_id());
    $brand_slugs = devhub_get_product_brand_slugs($product->get_id());
    $permalink = $product->get_permalink();
    $name = $product->get_name();
    ?>
    <div class="devhub-product-card" data-brands="<?php echo esc_attr($brand_slugs); ?>">

        <?php if ($discount > 0): ?>
            <span class="devhub-product-card__badge" aria-label="<?php echo esc_attr($discount); ?> percent off">
                OFF
                <?php echo esc_html($discount); ?>%
            </span>
        <?php endif; ?>

        <a href="<?php echo esc_url($permalink); ?>" class="devhub-product-card__img-wrap">
            <?php if ($img_url): ?>
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="devhub-product-card__img-placeholder" aria-hidden="true"></div>
            <?php endif; ?>
        </a>

        <div class="devhub-product-card__body">
            <a href="<?php echo esc_url($permalink); ?>" class="devhub-product-card__name">
                <?php echo esc_html($name); ?>
            </a>

            <?php if ($has_bundle): ?>
                <span class="devhub-product-card__bundle">Bundle Package</span>
            <?php endif; ?>

            <div class="devhub-product-card__price">
                <?php echo wp_kses_post($product->get_price_html()); ?>
            </div>

            <span class="devhub-product-card__stock devhub-product-card__stock--<?php echo $in_stock ? 'in' : 'out'; ?>">
                <?php echo $in_stock ? esc_html__('In stock', 'devicehub-theme') : esc_html__('Out of stock', 'devicehub-theme'); ?>
            </span>
        </div>

    </div>
    <?php
}