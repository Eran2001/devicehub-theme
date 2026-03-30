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
$customer    = new WC_Customer( $customer_id );

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

$countries = WC()->countries;

$get_state_name = static function ( string $country_code, string $state_code ) use ( $countries ): string {
    if ( $country_code === '' || $state_code === '' ) {
        return '';
    }

    $states = $countries->get_states( $country_code );
    return $states[ $state_code ] ?? $state_code;
};

$get_address_details = static function ( string $type ) use ( $customer, $countries, $get_state_name ): array {
    $first_name   = trim( (string) $customer->{"get_{$type}_first_name"}() );
    $last_name    = trim( (string) $customer->{"get_{$type}_last_name"}() );
    $company      = trim( (string) $customer->{"get_{$type}_company"}() );
    $address_1    = trim( (string) $customer->{"get_{$type}_address_1"}() );
    $address_2    = trim( (string) $customer->{"get_{$type}_address_2"}() );
    $city         = trim( (string) $customer->{"get_{$type}_city"}() );
    $country_code = trim( (string) $customer->{"get_{$type}_country"}() );
    $state_code   = trim( (string) $customer->{"get_{$type}_state"}() );
    $phone        = 'billing' === $type ? trim( (string) $customer->get_billing_phone() ) : '';

    $country_name = $country_code !== '' ? ( $countries->countries[ $country_code ] ?? $country_code ) : '';
    $state_name   = $get_state_name( $country_code, $state_code );
    $full_name    = trim( $first_name . ' ' . $last_name );

    $details = array_filter(
        [
            [
                'label' => __( 'Contact', 'devicehub-theme' ),
                'value' => $full_name,
                'wide'  => true,
            ],
            [
                'label' => __( 'Company', 'devicehub-theme' ),
                'value' => $company,
                'wide'  => true,
            ],
            [
                'label' => __( 'Address line 1', 'devicehub-theme' ),
                'value' => $address_1,
                'wide'  => true,
            ],
            [
                'label' => __( 'Address line 2', 'devicehub-theme' ),
                'value' => $address_2,
                'wide'  => true,
            ],
            [
                'label' => __( 'City', 'devicehub-theme' ),
                'value' => $city,
            ],
            [
                'label' => __( 'State', 'devicehub-theme' ),
                'value' => $state_name,
            ],
            [
                'label' => __( 'Country', 'devicehub-theme' ),
                'value' => $country_name,
                'wide'  => true,
            ],
            [
                'label' => __( 'Phone', 'devicehub-theme' ),
                'value' => $phone,
                'wide'  => true,
            ],
        ],
        static function ( array $item ): bool {
            return ! empty( $item['value'] );
        }
    );
    return array_values( $details );
};
?>

<p class="devhub-addresses__intro">
    <?php echo apply_filters('woocommerce_my_account_my_address_description', esc_html__('The following addresses will be used on the checkout page by default.', 'woocommerce')); // phpcs:ignore ?>
</p>

<div class="devhub-addresses-grid">

    <?php foreach ($get_addresses as $name => $title):
        $address = wc_get_account_formatted_address($name);
        $edit_url = wc_get_endpoint_url('edit-address', $name);
        $edit_label = $address ? sprintf(__('Edit %s', 'woocommerce'), $title) : sprintf(__('Add %s', 'woocommerce'), $title);
        $details = $get_address_details( $name );
        ?>

        <div class="devhub-address-card">
            <div class="devhub-address-card__header">
                <div class="devhub-address-card__heading">
                    <h3 class="devhub-address-card__title"><?php echo esc_html($title); ?></h3>
                    <p class="devhub-address-card__meta">
                        <?php echo esc_html( 'billing' === $name ? __( 'Used for billing and account communication', 'devicehub-theme' ) : __( 'Used for delivery and shipping updates', 'devicehub-theme' ) ); ?>
                    </p>
                </div>
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
            <div class="devhub-address-card__body">
                <?php
                if ($address) {
                    ?>
                    <div class="devhub-address-card__details">
                        <?php foreach ( $details as $detail ) : ?>
                            <div class="devhub-address-detail<?php echo ! empty( $detail['wide'] ) ? ' is-wide' : ''; ?>">
                                <span class="devhub-address-detail__label"><?php echo esc_html( $detail['label'] ); ?></span>
                                <span class="devhub-address-detail__value"><?php echo esc_html( $detail['value'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                } else {
                    ?>
                    <p class="devhub-address-card__empty"><?php esc_html_e('You have not set up this type of address yet.', 'woocommerce'); ?></p>
                    <?php
                }
                do_action('woocommerce_my_account_after_my_address', $name);
                ?>
            </div>
        </div>

    <?php endforeach; ?>

</div>
