<?php
/**
 * View Order — DeviceHub override
 *
 * Shows the details of a particular order on the account page.
 * Status summary paragraph is intentionally suppressed.
 *
 * Based on WooCommerce template version 10.6.0.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>

<?php /*
<p>
<?php
echo wp_kses_post(
    apply_filters(
        'woocommerce_order_details_status',
        sprintf(
            esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
            '<mark class="order-number">' . $order->get_order_number() . '</mark>',
            '<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
            '<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
        ),
        $order
    )
);
?>
</p>
*/ ?>

<?php if ( $notes ) : ?>
    <h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
    <ol class="woocommerce-OrderUpdates commentlist notes">
        <?php foreach ( $notes as $note ) : ?>
        <li class="woocommerce-OrderUpdate comment note">
            <div class="woocommerce-OrderUpdate-inner comment_container">
                <div class="woocommerce-OrderUpdate-text comment-text">
                    <p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                    <div class="woocommerce-OrderUpdate-description description">
                        <?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="clear"></div>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
