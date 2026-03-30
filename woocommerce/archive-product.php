<?php
/*
 * Archive Product — DeviceHub override
 *
 * Layout: sidebar filters (left) + product grid (right).
 * All product data from WooCommerce; images are local SVG placeholders.
 * Filtering: WooCommerce handles pa_* attribute params natively.
 *             pwb-brand is handled by devhub_filter_archive_by_brand() in inc/hooks.php.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;


/**
 * Render category links as a filter group.
 * Clicking a category navigates to its archive URL — PHP then renders
 * the relevant attribute filters for that category automatically.
 */
function devhub_archive_category_group(): void
{
    // Slugs that are utility/internal categories, not browsable sections.
    // Add to this list whenever you create a category that shouldn't appear in the filter.
    $excluded_slugs = ['flash-sale', 'new-arrivals', 'test'];

    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'orderby' => 'name',
        'exclude' => [get_option('default_product_cat')],
    ]);

    if (empty($terms) || is_wp_error($terms)) {
        return;
    }

    $terms = array_values(array_filter($terms, fn($t) => !in_array($t->slug, $excluded_slugs, true)));

    if (empty($terms)) {
        return;
    }

    $current_cat = is_product_category() ? get_queried_object() : null;
    $shop_url = get_permalink(wc_get_page_id('shop'));
    ?>
    <div class="devhub-filter-group">
        <button class="devhub-filter-group__toggle" type="button" aria-expanded="true">
            Category
            <i class="fas fa-chevron-up" aria-hidden="true"></i>
        </button>
        <ul class="devhub-filter-group__list">
            <li>
                <a href="<?php echo esc_url($shop_url); ?>"
                    class="devhub-filter-option<?php echo !$current_cat ? ' devhub-filter-option--active' : ''; ?>">
                    <span class="devhub-filter-option__check" aria-hidden="true">
                        <?php if (!$current_cat): ?><i class="fas fa-check"></i><?php endif; ?>
                    </span>
                    <span class="devhub-filter-option__name">All Products</span>
                </a>
            </li>
            <?php foreach ($terms as $term):
                $is_active = $current_cat && $current_cat->term_id === $term->term_id;
                ?>
                <li>
                    <a href="<?php echo esc_url(get_term_link($term)); ?>"
                        class="devhub-filter-option<?php echo $is_active ? ' devhub-filter-option--active' : ''; ?>">
                        <span class="devhub-filter-option__check" aria-hidden="true">
                            <?php if ($is_active): ?><i class="fas fa-check"></i><?php endif; ?>
                        </span>
                        <span class="devhub-filter-option__name"><?php echo esc_html($term->name); ?></span>
                        <span class="devhub-filter-option__count"><?php echo esc_html($term->count); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}


/**
 * Render one collapsible filter group.
 * Uses link-based URL toggling — no JS required for filtering itself.
 *
 * @param string $label     Display label (e.g. "Brand")
 * @param string $taxonomy  Taxonomy slug (e.g. "pwb-brand", "pa_screen-type")
 * @param string $url_param GET param name (e.g. "filter_brand", "filter_screen-type")
 */
function devhub_archive_filter_group(string $label, string $taxonomy, string $url_param): void
{
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'orderby' => 'name',
    ]);

    if (empty($terms) || is_wp_error($terms)) {
        return;
    }

    $raw = sanitize_text_field(wp_unslash($_GET[$url_param] ?? ''));
    $active = $raw !== '' ? array_filter(array_map('sanitize_title', explode(',', $raw))) : [];
    ?>
    <div class="devhub-filter-group">
        <button class="devhub-filter-group__toggle" type="button" aria-expanded="true">
            <?php echo esc_html($label); ?>
            <i class="fas fa-chevron-up" aria-hidden="true"></i>
        </button>
        <ul class="devhub-filter-group__list">
            <?php foreach ($terms as $term):
                $is_active = in_array($term->slug, $active, true);
                $new_vals = $is_active
                    ? array_values(array_diff($active, [$term->slug]))
                    : array_merge($active, [$term->slug]);
                $base = remove_query_arg('paged');
                $href = $new_vals
                    ? add_query_arg($url_param, implode(',', $new_vals), $base)
                    : remove_query_arg($url_param, $base);
                ?>
                <li>
                    <a href="<?php echo esc_url($href); ?>"
                        class="devhub-filter-option<?php echo $is_active ? ' devhub-filter-option--active' : ''; ?>">
                        <span class="devhub-filter-option__check" aria-hidden="true">
                            <?php if ($is_active): ?><i class="fas fa-check"></i><?php endif; ?>
                        </span>
                        <span class="devhub-filter-option__name"><?php echo esc_html($term->name); ?></span>
                        <span class="devhub-filter-option__count"><?php echo esc_html($term->count); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
?>

<div class="devhub-archive">
    <div class="wf-container">

        <div class="devhub-page-bar">
            <?php woocommerce_breadcrumb(); ?>
            <?php if (apply_filters('woocommerce_show_page_title', true)): ?>
                <h1 class="devhub-page-bar__title"><?php woocommerce_page_title(); ?></h1>
            <?php endif; ?>
        </div>

        <div class="devhub-archive__layout">

            <!-- ── Sidebar ───────────────────────────────────────────────── -->
            <aside class="devhub-archive__sidebar">
                <div class="devhub-archive__filters">

                    <!-- Category links — clicking navigates to the category archive -->
                    <?php devhub_archive_category_group(); ?>

                    <!-- Brand (always shown — applies to all categories) -->
                    <?php devhub_archive_filter_group('Brand', 'pwb-brand', 'filter_brand'); ?>

                    <!-- Category-specific attribute filters -->
                    <?php
                    // Map category slug → relevant attribute filters.
                    // 'taxonomy' must match a registered pa_* taxonomy in WooCommerce.
                    // Adjust slugs to match what you've created under Products → Attributes.
                    $category_attribute_map = [
                        'mobile-phones' => [
                            ['label' => 'Built-in Memory', 'taxonomy' => 'pa_built-in-memory', 'param' => 'filter_built-in-memory'],
                            ['label' => 'Storage', 'taxonomy' => 'pa_storage', 'param' => 'filter_storage'],
                            ['label' => 'Battery Capacity', 'taxonomy' => 'pa_battery-capacity', 'param' => 'filter_battery-capacity'],
                            ['label' => 'Screen Type', 'taxonomy' => 'pa_screen-type', 'param' => 'filter_screen-type'],
                            ['label' => 'Screen Diagonal', 'taxonomy' => 'pa_screen-diagonal', 'param' => 'filter_screen-diagonal'],
                            ['label' => 'Color', 'taxonomy' => 'pa_color', 'param' => 'filter_color'],
                        ],
                        'broad-bands' => [
                            ['label' => 'Color', 'taxonomy' => 'pa_color', 'param' => 'filter_color'],
                            ['label' => 'Material', 'taxonomy' => 'pa_material', 'param' => 'filter_material'],
                        ],
                        'electronics' => [
                            ['label' => 'Color', 'taxonomy' => 'pa_color', 'param' => 'filter_color'],
                            ['label' => 'Material', 'taxonomy' => 'pa_material', 'param' => 'filter_material'],
                        ],
                        'accessories' => [
                            ['label' => 'Color', 'taxonomy' => 'pa_color', 'param' => 'filter_color'],
                            ['label' => 'Size', 'taxonomy' => 'pa_size', 'param' => 'filter_size'],
                            ['label' => 'Material', 'taxonomy' => 'pa_material', 'param' => 'filter_material'],
                        ],
                    ];

                    $current_cat = is_product_category() ? get_queried_object() : null;

                    if ($current_cat && isset($category_attribute_map[$current_cat->slug])) {
                        foreach ($category_attribute_map[$current_cat->slug] as $attr) {
                            devhub_archive_filter_group($attr['label'], $attr['taxonomy'], $attr['param']);
                        }
                    }
                    ?>

                </div>
            </aside>

            <!-- ── Main ──────────────────────────────────────────────────── -->
            <div class="devhub-archive__main">

                <div class="devhub-archive__toolbar">
                    <?php woocommerce_result_count(); ?>
                    <?php woocommerce_catalog_ordering(); ?>
                </div>

                <?php if (woocommerce_product_loop()): ?>

                    <div class="devhub-archive__grid">
                        <?php
                        while (have_posts()):
                            the_post();
                            $product = wc_get_product(get_the_ID());
                            if ($product) {
                                // $img = devhub_get_product_placeholder_img($product);
                                // devhub_render_product_card($product, $img);
                                $img = get_the_post_thumbnail_url($product->get_id(), 'woocommerce_single') ?: devhub_get_product_placeholder_img($product);
                                devhub_render_product_card($product, $img);
                            }
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>

                    <div class="devhub-archive__pagination">
                        <?php woocommerce_pagination(); ?>
                    </div>

                <?php else: ?>
                    <?php do_action('woocommerce_no_products_found'); ?>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>