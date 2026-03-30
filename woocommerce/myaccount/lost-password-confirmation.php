<?php
/**
 * Lost password confirmation text.
 *
 * DeviceHub auth-card override for the reset-sent state.
 *
 * @package WooCommerce\Templates
 * @version 3.9.0
 */

defined('ABSPATH') || exit;
?>

<?php wc_print_notice(esc_html__('Password reset email has been sent.', 'woocommerce')); ?>

<div class="devhub-auth">
	<div class="devhub-auth__shell">
		<div class="devhub-auth__card">
			<!-- <div class="devhub-auth__brand">
				<img
					class="devhub-auth__brand-logo"
					src="<?php echo esc_url(get_theme_file_uri('/assets/images/HUTCHMainLogo.svg')); ?>"
					alt="<?php esc_attr_e('HUTCH', 'devicehub-theme'); ?>"
				/>
			</div> -->

			<a class="devhub-auth__back" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
				<span aria-hidden="true">&larr;</span>
				<span><?php esc_html_e('Back to sign-in options', 'devicehub-theme'); ?></span>
			</a>

			<h2 class="devhub-auth__title"><?php esc_html_e('Check Your Email', 'devicehub-theme'); ?></h2>
			<p class="devhub-auth__subtitle">
				<?php esc_html_e('Your password reset request has been received.', 'devicehub-theme'); ?></p>

			<?php do_action('woocommerce_before_lost_password_confirmation_message'); ?>

			<p class="devhub-auth__confirmation">
				<?php
				echo esc_html(
					apply_filters(
						'woocommerce_lost_password_confirmation_message',
						esc_html__(
							'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.',
							'woocommerce'
						)
					)
				);
				?>
			</p>

			<?php do_action('woocommerce_after_lost_password_confirmation_message'); ?>
		</div>
	</div>
</div>
