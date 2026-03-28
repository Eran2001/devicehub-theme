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

        <?php if (apply_filters('woocommerce_show_page_title', true)): ?>
            <div class="devhub-archive__header">
                <h1 class="devhub-archive__title"><?php woocommerce_page_title(); ?></h1>
                <?php woocommerce_breadcrumb(); ?>
            </div>
        <?php endif; ?>

        <div class="devhub-archive__layout">

            <!-- ── Sidebar ───────────────────────────────────────────────── -->
            <aside class="devhub-archive__sidebar">
                <div class="devhub-archive__filters">

                    <!-- Brand (pwb-brand — custom taxonomy, filtered via pre_get_posts) -->
                    <?php devhub_archive_filter_group('Brand', 'pwb-brand', 'filter_brand'); ?>

                    <!-- WooCommerce product attribute filters (pa_* — handled natively by WC) -->
                    <?php
                    $wc_attrs = wc_get_attribute_taxonomies();
                    foreach ($wc_attrs as $attr):
                        devhub_archive_filter_group(
                            $attr->attribute_label,
                            'pa_' . $attr->attribute_name,
                            'filter_' . $attr->attribute_name
                        );
                    endforeach;
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
                                $img = devhub_get_product_placeholder_img($product);
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