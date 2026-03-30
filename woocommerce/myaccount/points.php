<?php
/**
 * Points Collected — DeviceHub account page
 *
 * Shows points balance and history (via WooCommerce Points & Rewards plugin)
 * or a branded empty-state fallback matching the Downloads fallback standard.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

$user_id      = get_current_user_id();
$points       = 0;
$points_log   = [];
$has_points   = false;

// WooCommerce Points & Rewards plugin support.
if ( class_exists( 'WC_Points_Rewards_Manager' ) ) {
    $points     = (int) WC_Points_Rewards_Manager::get_users_points( $user_id );
    $has_points = $points > 0;

    if ( $has_points && class_exists( 'WC_Points_Rewards_Points_Log' ) ) {
        $points_log = WC_Points_Rewards_Points_Log::get_points_log_entries_for_user( $user_id, 10 );
    }
}

do_action( 'woocommerce_before_account_points', $has_points );
?>

<?php if ( $has_points ) : ?>

    <div class="devhub-points">

        <div class="devhub-points__balance">
            <span class="devhub-points__balance-label"><?php esc_html_e( 'Your points balance', 'devicehub-theme' ); ?></span>
            <span class="devhub-points__balance-value"><?php echo esc_html( number_format( $points ) ); ?></span>
            <span class="devhub-points__balance-unit"><?php esc_html_e( 'pts', 'devicehub-theme' ); ?></span>
        </div>

        <?php if ( ! empty( $points_log ) ) : ?>
            <h3><?php esc_html_e( 'Points history', 'devicehub-theme' ); ?></h3>
            <table class="woocommerce-table woocommerce-table--points shop_table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'woocommerce' ); ?></th>
                        <th><?php esc_html_e( 'Event', 'devicehub-theme' ); ?></th>
                        <th><?php esc_html_e( 'Points', 'devicehub-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $points_log as $entry ) : ?>
                        <tr>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->date ) ) ); ?></td>
                            <td><?php echo esc_html( $entry->description ); ?></td>
                            <td><?php echo esc_html( ( $entry->points > 0 ? '+' : '' ) . number_format( $entry->points ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

<?php else : ?>

    <div class="devhub-empty-state">

        <!-- Icon stack -->
        <div class="devhub-empty-state__icon-wrap">
            <div class="devhub-empty-state__layer devhub-empty-state__layer--1"></div>
            <div class="devhub-empty-state__layer devhub-empty-state__layer--2"></div>
            <div class="devhub-empty-state__card">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
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
            <h4><?php esc_html_e( 'No points collected yet.', 'devicehub-theme' ); ?></h4>
            <p><?php esc_html_e( "You haven't earned any reward points yet. Complete purchases to start collecting points and unlock exclusive rewards.", 'devicehub-theme' ); ?></p>
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

<?php do_action( 'woocommerce_after_account_points', $has_points ); ?>
