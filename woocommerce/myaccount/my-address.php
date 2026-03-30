<?php
/**
 * My Addresses — DeviceHub override
 *
 * Uses clean devhub- markup so Shopire float/col styles never interfere.
 * Based on WooCommerce template version 9.3.0.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __('Billing address', 'woocommerce'),
            'shipping' => __('Shipping address', 'woocommerce'),
        ],
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        ['billing' => __('Billing address', 'woocommerce')],
        $customer_id
    );
}
?>

<p class="devhub-addresses__intro">
    <?php echo apply_filters('woocommerce_my_account_my_address_description', esc_html__('The following addresses will be used on the checkout page by default.', 'woocommerce')); // phpcs:ignore ?>
</p>

<div class="devhub-addresses-grid">

    <?php foreach ($get_addresses as $name => $title):
        $address = wc_get_account_formatted_address($name);
        $edit_url = wc_get_endpoint_url('edit-address', $name);
        $edit_label = $address ? sprintf(__('Edit %s', 'woocommerce'), $title) : sprintf(__('Add %s', 'woocommerce'), $title);
        ?>

        <div class="devhub-address-card">
            <div class="devhub-address-card__header">
                <h3 class="devhub-address-card__title"><?php echo esc_html($title); ?></h3>
                <a href="<?php echo esc_url($edit_url); ?>" class="devhub-address-card__edit"
                    title="<?php echo esc_attr($edit_label); ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    <!-- <?php echo esc_html($edit_label); ?> -->
                </a>
            </div>
            <address class="devhub-address-card__body">
                <?php
                if ($address) {
                    echo wp_kses_post($address);
                } else {
                    esc_html_e('You have not set up this type of address yet.', 'woocommerce');
                }
                do_action('woocommerce_my_account_after_my_address', $name);
                ?>
            </address>
        </div>

    <?php endforeach; ?>

</div>