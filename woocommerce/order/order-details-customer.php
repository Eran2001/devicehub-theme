<?php
/**
 * Order Customer Details — DeviceHub override
 *
 * Shows billing (and optionally shipping) address in a labelled grid layout
 * inside a rounded card, matching the DeviceHub account card style.
 *
 * Based on WooCommerce template version 8.7.0.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();

/**
 * Build a field grid from an address type ('billing' or 'shipping').
 *
 * @param WC_Order $order
 * @param string   $type 'billing' | 'shipping'
 * @return array   [ ['label' => '', 'value' => ''], ... ]
 */
function devhub_address_fields( WC_Order $order, string $type ): array {
    $get = fn( string $field ) => call_user_func( [ $order, "get_{$type}_{$field}" ] );

    $first = $get( 'first_name' );
    $last  = $get( 'last_name' );

    $fields = [];

    if ( $first || $last ) {
        $fields[] = [ 'label' => __( 'Contact', 'devicehub-theme' ), 'value' => trim( "$first $last" ) ];
    }

    if ( $addr1 = $get( 'address_1' ) ) {
        $label = $get( 'address_2' )
            ? __( 'Address line 1', 'devicehub-theme' )
            : __( 'Address', 'devicehub-theme' );
        $fields[] = [ 'label' => $label, 'value' => $addr1 ];
    }

    if ( $addr2 = $get( 'address_2' ) ) {
        $fields[] = [ 'label' => __( 'Address line 2', 'devicehub-theme' ), 'value' => $addr2 ];
    }

    if ( $city = $get( 'city' ) ) {
        $fields[] = [ 'label' => __( 'City', 'devicehub-theme' ), 'value' => $city ];
    }

    if ( $state = $get( 'state' ) ) {
        $fields[] = [ 'label' => __( 'State', 'devicehub-theme' ), 'value' => $state ];
    }

    if ( $postcode = $get( 'postcode' ) ) {
        $fields[] = [ 'label' => __( 'Postcode', 'devicehub-theme' ), 'value' => $postcode ];
    }

    if ( $country_code = $get( 'country' ) ) {
        $countries = WC()->countries->get_countries();
        $fields[]  = [
            'label' => __( 'Country', 'devicehub-theme' ),
            'value' => $countries[ $country_code ] ?? $country_code,
        ];
    }

    if ( $type === 'billing' ) {
        if ( $phone = $order->get_billing_phone() ) {
            $fields[] = [ 'label' => __( 'Phone', 'devicehub-theme' ), 'value' => $phone ];
        }
        if ( $email = $order->get_billing_email() ) {
            $fields[] = [ 'label' => __( 'Email', 'devicehub-theme' ), 'value' => $email ];
        }
    }

    return $fields;
}
?>

<section class="woocommerce-customer-details devhub-order-customer-details">

    <?php
    $sections = [ 'billing' => __( 'Billing address', 'woocommerce' ) ];
    if ( $show_shipping ) {
        $sections['shipping'] = __( 'Shipping address', 'woocommerce' );
    }

    foreach ( $sections as $type => $title ) :
        $fields = devhub_address_fields( $order, $type );
        if ( empty( $fields ) ) continue;
    ?>

    <div class="devhub-order-address-card">
        <h2 class="devhub-order-address-card__title"><?php echo esc_html( $title ); ?></h2>
        <div class="devhub-order-address-grid">
            <?php foreach ( $fields as $field ) : ?>
                <div class="devhub-order-address-field">
                    <span class="devhub-order-address-field__label"><?php echo esc_html( strtoupper( $field['label'] ) ); ?></span>
                    <span class="devhub-order-address-field__value"><?php echo esc_html( $field['value'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php do_action( 'woocommerce_order_details_after_customer_address', $type, $order ); ?>
    </div>

    <?php endforeach; ?>

    <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

</section>
