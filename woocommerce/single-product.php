<?php
/**
 * Single Product — DeviceHub override
 *
 * Custom full-width layout: gallery left, info right, tabs below.
 * Image: always placeholder SVG (build phase).
 * Variations: pa_color (swatches) + pa_storage (pill buttons).
 * Bundles: hardcoded — wire to devhub_bundles post meta in Phase 2.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_single_product');

global $product;
$product = wc_get_product(get_the_ID());
if (!$product)
    return;

// ── 1. Data ───────────────────────────────────────────────────────────────────

$is_variable = $product->is_type('variable');
$attributes = $product->get_attributes();
$variation_attributes = $is_variable ? $product->get_variation_attributes() : [];
$color_slugs = $variation_attributes['pa_color'] ?? [];
$storage_slugs = $variation_attributes['pa_storage'] ?? [];

// Color name → hex lookup
$color_hex_map = [
    'black' => '#1a1a1a',
    'blue' => '#2563eb',
    'gold' => '#d4a017',
    'gray' => '#9ca3af',
    'grey' => '#9ca3af',
    'green' => '#16a34a',
    'purple' => '#7c3aed',
    'red' => '#dc2626',
    'rose-gold' => '#b76e79',
    'silver' => '#c0c0c0',
    'white' => '#f3f3f3',
];

$colors = [];
foreach ($color_slugs as $slug) {
    $term = get_term_by('slug', $slug, 'pa_color');
    if (!$term)
        continue;
    $key = sanitize_title($term->name);
    $colors[] = [
        'slug' => $slug,
        'name' => $term->name,
        'hex' => $color_hex_map[$key] ?? '#cccccc',
    ];
}

$storages = [];
foreach ($storage_slugs as $slug) {
    $term = get_term_by('slug', $slug, 'pa_storage');
    if (!$term)
        continue;
    $storages[] = ['slug' => $slug, 'name' => $term->name];
}

// All available variations serialised for JS resolution
$available_variations = '[]';
if ($is_variable) {
    $raw = array_map(function ($v) {
        return [
            'id' => $v['variation_id'],
            'attributes' => $v['attributes'],
            'price' => $v['display_price'],
            'in_stock' => $v['is_in_stock'],
        ];
    }, $product->get_available_variations());
    $available_variations = wp_json_encode($raw);
}

// Bundle packages — hardcoded (Phase 2: replace with devhub_bundles post meta)
$bundles = [
    ['name' => "Hutch 2X\nData 10 GB", 'data' => '10 GB', 'voice' => '60 minutes', 'price' => 'Rs. 249', 'url' => '#'],
    ['name' => "Hutch 2x\nData 20 GB", 'data' => '20 GB', 'voice' => '160 minutes', 'price' => 'Rs. 599', 'url' => '#'],
    ['name' => "Hutch 2x\nData 30 GB", 'data' => '30 GB', 'voice' => '200 minutes', 'price' => 'Rs. 849', 'url' => '#'],
    ['name' => "Hutch 2x\nData 50 GB", 'data' => '50 GB', 'voice' => '300 minutes', 'price' => 'Rs. 1,249', 'url' => '#'],
];

// Quick stats — pull from available product attributes
$quick_stats_config = [
    'pa_screen-diagonal' => ['label' => 'Screen size', 'icon' => 'fas fa-mobile-alt'],
    'pa_battery-capacity' => ['label' => 'Battery capacity', 'icon' => 'fas fa-battery-full'],
    'pa_built-in-memory' => ['label' => 'Built-in Memory', 'icon' => 'fas fa-memory'],
    'pa_brand' => ['label' => 'Brand', 'icon' => 'fas fa-tag'],
];

$quick_stats = [];
foreach ($quick_stats_config as $attr_key => $config) {
    if (!isset($attributes[$attr_key]))
        continue;
    $terms = $attributes[$attr_key]->get_terms();
    if (empty($terms))
        continue;
    $quick_stats[] = array_merge($config, [
        'value' => implode(', ', array_map(fn($t) => $t->name, $terms)),
    ]);
}

// Full specs table
$specs = [];
foreach ($attributes as $attr_key => $attribute) {
    $terms = $attribute->get_terms();
    if (empty($terms))
        continue;
    $attr_id = wc_attribute_taxonomy_id_by_name($attr_key);
    $attr_obj = $attr_id ? wc_get_attribute($attr_id) : null;
    $label = $attr_obj
        ? $attr_obj->name
        : ucwords(str_replace(['pa_', '-'], ['', ' '], $attr_key));
    $specs[] = [
        'label' => $label,
        'value' => implode(', ', array_map(fn($t) => $t->name, $terms)),
    ];
}

// $placeholder_img = DEVHUB_URI . '/assets/images/Original-Img.svg';
$placeholder_img = DEVHUB_URI . '/assets/images/Original-Img.svg';
$main_img = get_the_post_thumbnail_url($product->get_id(), 'woocommerce_single') ?: $placeholder_img;
$gallery_ids = $product->get_gallery_image_ids();
$thumb_imgs = array_map(fn($id) => wp_get_attachment_image_url($id, 'woocommerce_thumbnail'), $gallery_ids);
if (empty($thumb_imgs))
    $thumb_imgs = [$placeholder_img, $placeholder_img, $placeholder_img];

// ── 2. Markup ─────────────────────────────────────────────────────────────────
?>

<div class="devhub-single" data-variations="<?php echo esc_attr($available_variations); ?>">
    <div class="wf-container">

        <div class="devhub-single__layout">

            <!-- ── Gallery (left) ──────────────────────────────────────────── -->
            <div class="devhub-single__gallery">

                <div class="devhub-single__main-image">
                    <img src="<?php echo esc_url($main_img); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
                </div>

                <div class="devhub-single__thumbnails">
                    <?php foreach ($thumb_imgs as $i => $thumb): ?>
                        <button class="devhub-single__thumb<?php echo $i === 0 ? ' devhub-single__thumb--active' : ''; ?>"
                            type="button"
                            aria-label="<?php echo esc_attr(sprintf(__('View image %d', 'devicehub-theme'), $i + 1)); ?>">
                            <img src="<?php echo esc_url($thumb); ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="devhub-single__safe-checkout">
                    <p class="devhub-single__safe-checkout-label">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <?php esc_html_e('Guaranteed safe Checkout', 'devicehub-theme'); ?>
                    </p>
                    <div class="devhub-single__payment-icons">
                        <span class="devhub-single__payment-badge">Visa</span>
                        <span class="devhub-single__payment-badge">Mastercard</span>
                        <span class="devhub-single__payment-badge">Amex</span>
                        <span class="devhub-single__payment-badge devhub-single__payment-badge--koko">KOKO</span>
                        <span class="devhub-single__payment-badge devhub-single__payment-badge--webx">WebXPay</span>
                    </div>
                </div>

            </div>

            <!-- ── Info (right) ────────────────────────────────────────────── -->
            <div class="devhub-single__info">

                <h1 class="devhub-single__title">
                    <?php echo esc_html($product->get_name()); ?>
                </h1>

                <div class="devhub-single__price-row">
                    <div class="devhub-single__price">
                        <?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </div>
                    <span
                        class="devhub-single__stock devhub-single__stock--<?php echo $product->is_in_stock() ? 'in' : 'out'; ?>">
                        <span class="devhub-single__stock-dot" aria-hidden="true"></span>
                        <?php echo $product->is_in_stock()
                            ? esc_html__('In stock', 'devicehub-theme')
                            : esc_html__('Out of stock', 'devicehub-theme'); ?>
                    </span>
                </div>

                <?php if (!empty($colors)): ?>
                    <div class="devhub-single__option-group">
                        <p class="devhub-single__option-label"><?php esc_html_e('Select color', 'devicehub-theme'); ?></p>
                        <div class="devhub-single__color-swatches" role="group"
                            aria-label="<?php esc_attr_e('Color options', 'devicehub-theme'); ?>">
                            <?php foreach ($colors as $color): ?>
                                <button class="devhub-single__color-swatch" type="button"
                                    data-value="<?php echo esc_attr($color['slug']); ?>"
                                    style="background-color:<?php echo esc_attr($color['hex']); ?>;"
                                    title="<?php echo esc_attr($color['name']); ?>"
                                    aria-label="<?php echo esc_attr($color['name']); ?>">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($storages)): ?>
                    <div class="devhub-single__option-group">
                        <p class="devhub-single__option-label">
                            <?php esc_html_e('Choose your storage', 'devicehub-theme'); ?>
                        </p>
                        <div class="devhub-single__storage-options" role="group"
                            aria-label="<?php esc_attr_e('Storage options', 'devicehub-theme'); ?>">
                            <?php foreach ($storages as $storage): ?>
                                <button class="devhub-single__storage-btn" type="button"
                                    data-value="<?php echo esc_attr($storage['slug']); ?>">
                                    <?php echo esc_html($storage['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ── Bundle packages ──────────────────────────────────── -->
                <?php if (!empty($bundles)): ?>
                    <div class="devhub-single__bundles">
                        <p class="devhub-single__option-label">
                            <?php esc_html_e('Optional Bundle Packages', 'devicehub-theme'); ?>
                        </p>
                        <div class="devhub-single__bundles-slider">
                            <button class="devhub-single__bundle-arrow devhub-single__bundle-arrow--prev"
                                id="devhubBundlePrev" type="button"
                                aria-label="<?php esc_attr_e('Previous bundle', 'devicehub-theme'); ?>">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <div class="devhub-single__bundles-viewport">
                                <div class="devhub-single__bundles-track" id="devhubBundlesTrack">
                                    <?php foreach ($bundles as $idx => $bundle): ?>
                                        <div
                                            class="devhub-single__bundle-card<?php echo $idx === 0 ? ' devhub-single__bundle-card--active' : ''; ?>">
                                            <div class="devhub-single__bundle-top">
                                                <div class="devhub-single__bundle-icon" aria-hidden="true">
                                                    <i class="fas fa-box-open"></i>
                                                </div>
                                                <span class="devhub-single__bundle-name">
                                                    <?php echo nl2br(esc_html($bundle['name'])); ?>
                                                </span>
                                            </div>
                                            <div class="devhub-single__bundle-meta">
                                                <div class="devhub-single__bundle-row">
                                                    <span
                                                        class="devhub-single__bundle-meta-key"><?php esc_html_e('Internet data', 'devicehub-theme'); ?></span>
                                                    <span
                                                        class="devhub-single__bundle-meta-val"><?php echo esc_html($bundle['data']); ?></span>
                                                </div>
                                                <div class="devhub-single__bundle-row">
                                                    <span
                                                        class="devhub-single__bundle-meta-key"><?php esc_html_e('Voice', 'devicehub-theme'); ?></span>
                                                    <span
                                                        class="devhub-single__bundle-meta-val"><?php echo esc_html($bundle['voice']); ?></span>
                                                </div>
                                            </div>
                                            <div class="devhub-single__bundle-footer">
                                                <div class="devhub-single__bundle-plan">
                                                    <p class="devhub-single__bundle-plan-label">
                                                        <?php esc_html_e('Monthly Plan', 'devicehub-theme'); ?>
                                                    </p>
                                                    <p class="devhub-single__bundle-price">
                                                        <?php echo esc_html($bundle['price']); ?>
                                                    </p>
                                                </div>
                                                <a href="<?php echo esc_url($bundle['url']); ?>"
                                                    class="devhub-single__bundle-link">
                                                    <?php esc_html_e('View Details', 'devicehub-theme'); ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="devhub-single__bundle-arrow" id="devhubBundleNext" type="button"
                                aria-label="<?php esc_attr_e('Next bundle', 'devicehub-theme'); ?>">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ── Cart form ────────────────────────────────────────── -->
                <?php do_action('woocommerce_before_add_to_cart_form'); ?>

                <form class="devhub-single__cart-form cart" method="post" enctype="multipart/form-data"
                    action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>">

                    <?php if ($is_variable): ?>
                        <input type="hidden" name="variation_id" id="devhubVariationId" value="">
                        <?php foreach ($variation_attributes as $attr_name => $options): ?>
                            <input type="hidden" name="<?php echo esc_attr('attribute_' . sanitize_title($attr_name)); ?>"
                                id="devhubAttr_<?php echo esc_attr(sanitize_title($attr_name)); ?>" value="">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <input type="hidden" name="quantity" value="1">

                    <div class="devhub-single__actions">
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>"
                            class="devhub-single__btn devhub-single__btn--cart">
                            <?php esc_html_e('Add to Cart', 'devicehub-theme'); ?>
                        </button>
                        <button type="button" class="devhub-single__btn devhub-single__btn--buy">
                            <?php esc_html_e('Buy Now', 'devicehub-theme'); ?>
                        </button>
                    </div>

                </form>

                <?php do_action('woocommerce_after_add_to_cart_form'); ?>

            </div><!-- /.devhub-single__info -->

        </div><!-- /.devhub-single__layout -->

        <!-- ── Tabs ──────────────────────────────────────────────────────── -->
        <div class="devhub-single__tabs">

            <div class="devhub-single__tab-nav" role="tablist">
                <button class="devhub-single__tab-btn" role="tab" aria-selected="false"
                    aria-controls="devhubTabFeatures" data-tab="features">
                    <?php esc_html_e('Features', 'devicehub-theme'); ?>
                </button>
                <button class="devhub-single__tab-btn devhub-single__tab-btn--active" role="tab" aria-selected="true"
                    aria-controls="devhubTabSpecs" data-tab="specs">
                    <?php esc_html_e('Specifications', 'devicehub-theme'); ?>
                </button>
            </div>

            <div class="devhub-single__tab-panel" id="devhubTabFeatures" role="tabpanel" hidden>
                <div class="devhub-single__features-content">
                    <?php echo wp_kses_post($product->get_description()); ?>
                </div>
            </div>

            <div class="devhub-single__tab-panel devhub-single__tab-panel--active" id="devhubTabSpecs" role="tabpanel">

                <?php if (!empty($quick_stats)): ?>
                    <div class="devhub-single__quick-stats">
                        <?php foreach ($quick_stats as $stat): ?>
                            <div class="devhub-single__quick-stat">
                                <i class="<?php echo esc_attr($stat['icon']); ?>" aria-hidden="true"></i>
                                <span class="devhub-single__quick-stat-label"><?php echo esc_html($stat['label']); ?></span>
                                <span class="devhub-single__quick-stat-value"><?php echo esc_html($stat['value']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($specs)): ?>
                    <table class="devhub-single__specs-table">
                        <tbody>
                            <?php foreach ($specs as $spec): ?>
                                <tr>
                                    <td class="devhub-single__spec-label"><?php echo esc_html($spec['label']); ?></td>
                                    <td class="devhub-single__spec-value"><?php echo esc_html($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>

        </div><!-- /.devhub-single__tabs -->

    </div><!-- /.wf-container -->
</div><!-- /.devhub-single -->

<?php do_action('woocommerce_after_single_product'); ?>