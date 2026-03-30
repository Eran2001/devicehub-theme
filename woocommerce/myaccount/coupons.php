<?php
/**
 * Coupons — DeviceHub account page
 *
 * Shows coupons restricted to the current customer's email, or a
 * branded empty-state fallback matching the Downloads fallback standard.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$user       = wp_get_current_user();
$user_email = $user->user_email;

// Query published coupons that have this customer's email in their restriction list.
$coupon_posts = get_posts( [
    'post_type'      => 'shop_coupon',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => 'customer_email',
            'value'   => $user_email,
            'compare' => 'LIKE',
        ],
    ],
] );

$has_coupons = ! empty( $coupon_posts );

do_action( 'woocommerce_before_account_coupons', $has_coupons );
?>

<?php if ( $has_coupons ) : ?>

    <table class="woocommerce-table woocommerce-table--coupons shop_table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Code', 'woocommerce' ); ?></th>
                <th><?php esc_html_e( 'Discount', 'woocommerce' ); ?></th>
                <th><?php esc_html_e( 'Expires', 'woocommerce' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $coupon_posts as $coupon_post ) :
                $coupon      = new WC_Coupon( $coupon_post->post_title );
                $expiry_date = $coupon->get_date_expires();
                $discount    = $coupon->get_discount_type() === 'percent'
                    ? $coupon->get_amount() . '%'
                    : wc_price( $coupon->get_amount() );
            ?>
                <tr>
                    <td><code><?php echo esc_html( strtoupper( $coupon_post->post_title ) ); ?></code></td>
                    <td><?php echo wp_kses_post( $discount ); ?></td>
                    <td>
                        <?php
                        if ( $expiry_date ) {
                            echo esc_html( date_i18n( get_option( 'date_format' ), $expiry_date->getTimestamp() ) );
                        } else {
                            esc_html_e( 'No expiry', 'woocommerce' );
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else : ?>

    <div class="devhub-empty-state">

        <!-- Icon stack -->
        <div class="devhub-empty-state__icon-wrap">
            <div class="devhub-empty-state__layer devhub-empty-state__layer--1"></div>
            <div class="devhub-empty-state__layer devhub-empty-state__layer--2"></div>
            <div class="devhub-empty-state__card">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
            </div>
            <div class="devhub-empty-state__badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <div class="devhub-empty-state__text">
            <h4><?php esc_html_e( 'No coupons available.', 'devicehub-theme' ); ?></h4>
            <p><?php esc_html_e( "You don't have any coupons assigned to your account right now. Keep an eye on your email for exclusive discount codes.", 'devicehub-theme' ); ?></p>
        </div>

        <!-- Actions -->
        <div class="devhub-empty-state__actions">
            <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="devhub-empty-state__btn devhub-empty-state__btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php esc_html_e( 'Browse Products', 'devicehub-theme' ); ?>
            </a>
        </div>

    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_coupons', $has_coupons ); ?>
