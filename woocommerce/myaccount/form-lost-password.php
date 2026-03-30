<?php
/**
 * Lost password form
 *
 * DeviceHub auth-card override that preserves WooCommerce reset behavior.
 *
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

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

			<h2 class="devhub-auth__title"><?php esc_html_e('Forgot Password', 'devicehub-theme'); ?></h2>
			<p class="devhub-auth__subtitle">
				<?php
				echo wp_kses_post(
					apply_filters(
						'woocommerce_lost_password_message',
						esc_html__(
							'Enter your username or email address and we will send you a link to create a new password.',
							'devicehub-theme'
						)
					)
				);
				?>
			</p>

			<div class="devhub-auth__form">
				<form method="post" class="woocommerce-ResetPassword lost_reset_password">
					<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
						<label for="user_login"><?php esc_html_e('Username or email', 'woocommerce'); ?>&nbsp;<span
								class="required" aria-hidden="true">*</span><span
								class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
						<input class="woocommerce-Input woocommerce-Input--text input-text" type="text"
							name="user_login" id="user_login" autocomplete="username" required aria-required="true" />
					</p>

					<?php do_action('woocommerce_lostpassword_form'); ?>

					<p class="woocommerce-form-row form-row">
						<input type="hidden" name="wc_reset_password" value="true" />
						<button type="submit"
							class="woocommerce-Button button devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
							value="<?php esc_attr_e('Reset password', 'woocommerce'); ?>"><?php esc_html_e('Reset password', 'woocommerce'); ?></button>
					</p>

					<?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
				</form>
			</div>
		</div>
	</div>
</div>

<?php do_action('woocommerce_after_lost_password_form'); ?>