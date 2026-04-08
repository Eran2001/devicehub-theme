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

/**
 * Check whether WooCommerce is available before calling its helpers/conditionals.
 */
function devhub_has_woocommerce(): bool
{
    return class_exists('WooCommerce');
}

function devhub_is_shop_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_shop') && is_shop();
}

function devhub_is_product_category_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_product_category') && is_product_category();
}

function devhub_is_product_tag_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_product_tag') && is_product_tag();
}

function devhub_is_product_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_product') && is_product();
}

function devhub_is_cart_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_cart') && is_cart();
}

function devhub_is_checkout_page(): bool
{
    return devhub_has_woocommerce() && function_exists('is_checkout') && is_checkout();
}

function devhub_is_account_context(): bool
{
    return devhub_has_woocommerce() && function_exists('is_account_page') && is_account_page();
}

function devhub_has_catalog_data(): bool
{
    return devhub_has_woocommerce() && post_type_exists('product') && taxonomy_exists('product_cat') && function_exists('wc_get_product');
}


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

/**
 * Build normalized gallery image data for custom single-product galleries.
 */
function devhub_build_gallery_image_data(int $attachment_id, string $fallback_alt = '', string $placeholder_img = ''): ?array
{
    if ($attachment_id <= 0) {
        return null;
    }

    $main_src = wp_get_attachment_image_url($attachment_id, 'woocommerce_single')
        ?: wp_get_attachment_image_url($attachment_id, 'full')
        ?: $placeholder_img;

    $thumb_src = wp_get_attachment_image_url($attachment_id, 'woocommerce_thumbnail')
        ?: $main_src;

    if (!$main_src || !$thumb_src) {
        return null;
    }

    $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    if ($alt === '') {
        $alt = trim((string) get_the_title($attachment_id));
    }
    if ($alt === '') {
        $alt = $fallback_alt;
    }

    return [
        'id' => $attachment_id,
        'main_src' => $main_src,
        'thumb_src' => $thumb_src,
        'alt' => $alt,
    ];
}

/**
 * Return the parent product gallery for the custom single-product template.
 */
function devhub_get_product_gallery_data(WC_Product $product, string $placeholder_img): array
{
    $image_ids = array_values(array_unique(array_filter(array_merge(
        [(int) $product->get_image_id()],
        array_map('intval', $product->get_gallery_image_ids())
    ))));

    $gallery = [];
    foreach ($image_ids as $image_id) {
        $image = devhub_build_gallery_image_data($image_id, $product->get_name(), $placeholder_img);
        if ($image) {
            $gallery[] = $image;
        }
    }

    if (!empty($gallery)) {
        return $gallery;
    }

    return [[
        'id' => 0,
        'main_src' => $placeholder_img,
        'thumb_src' => $placeholder_img,
        'alt' => $product->get_name(),
    ]];
}

/**
 * Normalize Woo/variation-gallery plugin image payloads for the custom gallery.
 */
function devhub_get_variation_gallery_data(array $variation, string $fallback_alt, string $placeholder_img): array
{
    $gallery = [];
    $seen = [];

    if (!empty($variation['variation_gallery_images']) && is_array($variation['variation_gallery_images'])) {
        foreach ($variation['variation_gallery_images'] as $image) {
            if (!is_array($image)) {
                continue;
            }

            $id = isset($image['image_id']) ? (int) $image['image_id'] : 0;
            $main_src = (string) ($image['full_src'] ?? $image['src'] ?? $placeholder_img);
            $thumb_src = (string) ($image['gallery_thumbnail_src'] ?? $image['src'] ?? $main_src);
            $alt = trim((string) ($image['alt'] ?? $image['title'] ?? $fallback_alt));
            $key = $id > 0 ? 'id:' . $id : 'src:' . $main_src;

            if ($main_src === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $gallery[] = [
                'id' => $id,
                'main_src' => $main_src,
                'thumb_src' => $thumb_src ?: $main_src,
                'alt' => $alt,
            ];
        }
    }

    if (!empty($gallery)) {
        return $gallery;
    }

    if (!empty($variation['image']) && is_array($variation['image'])) {
        $image = $variation['image'];
        $main_src = (string) ($image['full_src'] ?? $image['src'] ?? $placeholder_img);
        if ($main_src !== '') {
            return [[
                'id' => isset($image['id']) ? (int) $image['id'] : 0,
                'main_src' => $main_src,
                'thumb_src' => (string) ($image['gallery_thumbnail_src'] ?? $image['thumb_src'] ?? $image['src'] ?? $main_src),
                'alt' => trim((string) ($image['alt'] ?? $image['title'] ?? $fallback_alt)),
            ]];
        }
    }

    return [];
}

/**
 * Normalize a raw hex color string from term meta.
 */
function devhub_normalize_hex_color(string $value, string $fallback = '#cccccc'): string
{
    $value = trim($value);

    if ($value === '') {
        return $fallback;
    }

    $with_hash = sanitize_hex_color($value);
    if ($with_hash) {
        return $with_hash;
    }

    $no_hash = sanitize_hex_color_no_hash($value);
    if ($no_hash) {
        return '#' . $no_hash;
    }

    return $fallback;
}

/**
 * Resolve Woo product color terms into swatch-ready UI data.
 *
 * Reads the real color value from term meta saved by Woo Variation Swatches.
 */
function devhub_get_product_color_options(WC_Product $product): array
{
    $attributes = $product->get_attributes();

    if (
        !$product->is_type('variable')
        || !taxonomy_exists('pa_color')
        || !isset($attributes['pa_color'])
    ) {
        return [];
    }

    $variation_attributes = $product->get_variation_attributes();
    $color_slugs = $variation_attributes['pa_color'] ?? [];
    if (empty($color_slugs) || is_wp_error($color_slugs)) {
        return [];
    }

    $colors = [];
    foreach ($color_slugs as $slug) {
        $term = get_term_by('slug', $slug, 'pa_color');
        if (!$term instanceof WP_Term) {
            continue;
        }

        $raw_hex = (string) get_term_meta($term->term_id, 'product_attribute_color', true);

        $colors[] = [
            'slug' => $slug,
            'name' => $term->name,
            'hex' => devhub_normalize_hex_color($raw_hex),
        ];
    }

    return $colors;
}


// ── Template renderer ─────────────────────────────────────────────────────────

/**
 * Format product-card prices so variable products don't show awkward min-max ranges.
 *
 * Cards look cleaner with a single starting price than a wrapped range.
 */
function devhub_get_product_card_price_html(WC_Product $product): string
{
    if (!$product->is_type('variable')) {
        return (string) $product->get_price_html();
    }

    $min_price = (float) $product->get_variation_price('min', true);
    $max_price = (float) $product->get_variation_price('max', true);

    if ($min_price <= 0 || abs($max_price - $min_price) < 0.01) {
        return (string) $product->get_price_html();
    }

    $min_regular = (float) $product->get_variation_regular_price('min', true);
    $from_label = sprintf(
        '<span class="devhub-product-card__price-prefix">%s</span>',
        esc_html__('From', 'devicehub-theme')
    );

    if ($min_regular > $min_price) {
        return $from_label . ' <del>' . wc_price($min_regular) . '</del> <ins>' . wc_price($min_price) . '</ins>';
    }

    return $from_label . ' ' . wc_price($min_price);
}

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
                <?php echo wp_kses_post(devhub_get_product_card_price_html($product)); ?>
            </div>

            <span class="devhub-product-card__stock devhub-product-card__stock--<?php echo $in_stock ? 'in' : 'out'; ?>">
                <?php echo $in_stock ? esc_html__('In stock', 'devicehub-theme') : esc_html__('Out of stock', 'devicehub-theme'); ?>
            </span>
        </div>

    </div>
    <?php
}
