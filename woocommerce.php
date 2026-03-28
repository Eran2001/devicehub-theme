<?php
/**
 * WooCommerce page template — DeviceHub override
 *
 * Replaces the Shopire woocommerce_content() call so we can use
 * our own archive layout (sidebar + grid) and single product template.
 *
 * @package DeviceHub
 */

get_header();

if (is_singular('product')) {
    // Single product — WooCommerce handles this; our override comes later
    while (have_posts()) {
        the_post();
        wc_get_template_part('content', 'single-product');
    }
} else {
    // Archive / shop / category — our custom layout
    // woocommerce/archive-product.php owns its own .wf-container
    get_template_part('woocommerce/archive-product');
}

get_footer();
