<?php
/**
 * Dispute — DeviceHub account page
 *
 * Shows open disputes or a branded empty-state fallback matching the
 * Downloads fallback standard. No standard WC plugin covers disputes,
 * so this always falls back until a custom plugin is wired up.
 *
 * To integrate a dispute plugin: replace the $disputes block below
 * and set $has_disputes = true when records exist.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

// TODO: replace with real dispute plugin data when available.
$disputes     = [];
$has_disputes = false;

do_action( 'woocommerce_before_account_disputes', $has_disputes );
?>

<?php if ( $has_disputes ) : ?>

    <table class="woocommerce-table woocommerce-table--disputes shop_table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Dispute #', 'devicehub-theme' ); ?></th>
                <th><?php esc_html_e( 'Order', 'woocommerce' ); ?></th>
                <th><?php esc_html_e( 'Opened', 'devicehub-theme' ); ?></th>
                <th><?php esc_html_e( 'Status', 'devicehub-theme' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $disputes as $dispute ) : ?>
                <tr>
                    <td><?php echo esc_html( $dispute['id'] ); ?></td>
                    <td><?php echo esc_html( $dispute['order'] ); ?></td>
                    <td><?php echo esc_html( $dispute['date'] ); ?></td>
                    <td><?php echo esc_html( $dispute['status'] ); ?></td>
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
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="devhub-empty-state__badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <div class="devhub-empty-state__text">
            <h4><?php esc_html_e( 'No open disputes.', 'devicehub-theme' ); ?></h4>
            <p><?php esc_html_e( "You don't have any active disputes. If you have an issue with an order, please contact our support team and we'll help resolve it.", 'devicehub-theme' ); ?></p>
        </div>

        <!-- Actions -->
        <div class="devhub-empty-state__actions">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="devhub-empty-state__btn devhub-empty-state__btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php esc_html_e( 'View Orders', 'woocommerce' ); ?>
            </a>
        </div>

    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_disputes', $has_disputes ); ?>
