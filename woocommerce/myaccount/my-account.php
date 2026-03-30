<?php
/**
 * My Account page — DeviceHub override
 *
 * Adds devhub-page-bar (breadcrumb + title) above the account layout.
 * Based on WooCommerce template version 3.5.0.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

$title = get_the_title();
?>

<div class="devhub-page-bar wf-container">
    <?php woocommerce_breadcrumb(); ?>
    <h1 class="devhub-page-bar__title"><?php echo esc_html($title); ?></h1>
</div>

<?php do_action('woocommerce_account_navigation'); ?>

<div class="woocommerce-MyAccount-content">
    <?php do_action('woocommerce_account_content'); ?>
</div>
