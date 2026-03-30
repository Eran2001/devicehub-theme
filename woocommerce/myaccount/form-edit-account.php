<?php
/**
 * Edit account form — DeviceHub override
 *
 * Wraps personal info fields in a card section; password fieldset is 2-col grid.
 * Based on WooCommerce template version 10.5.0.
 *
 * @package DeviceHub
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>

<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

    <?php do_action( 'woocommerce_edit_account_form_start' ); ?>

    <!-- ── Personal info card ─────────────────────────────────────── -->
    <div class="devhub-account-section">

        <p class="woocommerce-form-row form-row form-row-first">
            <label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" aria-required="true" />
        </p>

        <p class="woocommerce-form-row form-row form-row-last">
            <label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" aria-required="true" />
        </p>

        <p class="woocommerce-form-row form-row form-row-wide">
            <label for="account_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" aria-required="true" />
            <span class="devhub-field-hint"><em><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ); ?></em></span>
        </p>

        <p class="woocommerce-form-row form-row form-row-wide">
            <label for="account_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span></label>
            <input type="email" class="woocommerce-Input input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" aria-required="true" />
        </p>

        <?php do_action( 'woocommerce_edit_account_form_fields' ); ?>

    </div>

    <!-- ── Password change card ───────────────────────────────────── -->
    <fieldset>
        <legend><?php esc_html_e( 'Password change', 'woocommerce' ); ?></legend>

        <p class="woocommerce-form-row form-row form-row-first">
            <label for="password_current"><?php esc_html_e( 'Current password', 'woocommerce' ); ?></label>
            <input type="password" class="woocommerce-Input input-text" name="password_current" id="password_current" autocomplete="current-password" />
        </p>

        <p class="woocommerce-form-row form-row form-row-last">
            <label for="password_1"><?php esc_html_e( 'New password', 'woocommerce' ); ?></label>
            <input type="password" class="woocommerce-Input input-text" name="password_1" id="password_1" autocomplete="new-password" />
        </p>

        <p class="woocommerce-form-row form-row form-row-wide">
            <label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
            <input type="password" class="woocommerce-Input input-text" name="password_2" id="password_2" autocomplete="new-password" />
        </p>
    </fieldset>

    <?php do_action( 'woocommerce_edit_account_form' ); ?>

    <!-- ── Submit ─────────────────────────────────────────────────── -->
    <p class="devhub-form-submit">
        <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
        <button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>">
            <?php esc_html_e( 'Save changes', 'woocommerce' ); ?>
        </button>
        <input type="hidden" name="action" value="save_account_details" />
    </p>

    <?php do_action( 'woocommerce_edit_account_form_end' ); ?>

</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
