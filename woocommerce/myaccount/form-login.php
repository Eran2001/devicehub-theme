<?php
/**
 * Login Form
 *
 * DeviceHub custom auth UI that preserves WooCommerce login/register behavior.
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined('ABSPATH') || exit;

$registration_enabled = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$initial_panel = 'chooser';
$posted_panel = isset($_POST['devhub_auth_panel']) && is_string($_POST['devhub_auth_panel']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
	? sanitize_key(wp_unslash($_POST['devhub_auth_panel'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
	: '';

if (isset($_POST['register']) && $registration_enabled) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$initial_panel = 'register';
} elseif (isset($_POST['login'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$initial_panel = 'email';
}

$asset_base = get_theme_file_uri('/assets/images/');
$auth_icons = [
	'google' => $asset_base . 'google.png',
	'facebook' => $asset_base . 'facebook.png',
	'mobile' => $asset_base . 'mobile.png',
	'mail' => $asset_base . 'mail.png',
];
$auth_redirect_url = function_exists( 'devhub_get_auth_success_redirect_url' )
	? devhub_get_auth_success_redirect_url()
	: wc_get_page_permalink( 'myaccount' );

do_action('woocommerce_before_customer_login_form');
?>

<div class="devhub-auth" data-devhub-auth data-initial-panel="<?php echo esc_attr($initial_panel); ?>">
	<div class="devhub-auth__shell">
		<div class="devhub-auth__card">
			<!-- <div class="devhub-auth__brand">
				<img
					class="devhub-auth__brand-logo"
					src="<?php echo esc_url(get_theme_file_uri('/assets/images/HUTCHMainLogo.svg')); ?>"
					alt="<?php esc_attr_e('HUTCH', 'devicehub-theme'); ?>"
				/>
			</div> -->

			<section class="devhub-auth__panel" data-devhub-panel="chooser" aria-labelledby="devhub-auth-chooser-title">
				<div class="devhub-auth__intro">
					<h2 id="devhub-auth-chooser-title"><?php esc_html_e('Login', 'devicehub-theme'); ?></h2>
					<p><?php esc_html_e('Use your email, mobile number, or social sign-in to continue.', 'devicehub-theme'); ?>
					</p>
				</div>

				<div class="devhub-auth__options">
					<button type="button" class="devhub-auth__option" data-devhub-placeholder
						data-devhub-message="<?php esc_attr_e('Google sign-in will be connected later. Use email login or continue as a guest for now.', 'devicehub-theme'); ?>">
						<img class="devhub-auth__option-icon" src="<?php echo esc_url($auth_icons['google']); ?>" alt=""
							aria-hidden="true" />
						<span><?php esc_html_e('Sign up with Google', 'devicehub-theme'); ?></span>
					</button>

					<button type="button" class="devhub-auth__option" data-devhub-placeholder
						data-devhub-message="<?php esc_attr_e('Facebook sign-in will be connected later. Use email login or continue as a guest for now.', 'devicehub-theme'); ?>">
						<img class="devhub-auth__option-icon" src="<?php echo esc_url($auth_icons['facebook']); ?>"
							alt="" aria-hidden="true" />
						<span><?php esc_html_e('Sign up with Facebook', 'devicehub-theme'); ?></span>
					</button>

					<button type="button" class="devhub-auth__option" data-devhub-auth-open="mobile-request">
						<img class="devhub-auth__option-icon" src="<?php echo esc_url($auth_icons['mobile']); ?>" alt=""
							aria-hidden="true" />
						<span><?php esc_html_e('Sign up with Mobile', 'devicehub-theme'); ?></span>
					</button>

					<button type="button" class="devhub-auth__option" data-devhub-auth-open="email">
						<img class="devhub-auth__option-icon" src="<?php echo esc_url($auth_icons['mail']); ?>" alt=""
							aria-hidden="true" />
						<span><?php esc_html_e('Continue with Email', 'devicehub-theme'); ?></span>
					</button>
				</div>

				<div class="devhub-auth__divider"><?php esc_html_e('OR', 'devicehub-theme'); ?></div>

				<button type="button" class="devhub-auth__option devhub-auth__option--ghost"
					data-devhub-auth-open="guest">
					<span><?php esc_html_e('Guest Checkout', 'devicehub-theme'); ?></span>
				</button>

				<p class="devhub-auth__status" data-devhub-status hidden></p>
			</section>

			<section class="devhub-auth__panel" data-devhub-panel="guest" aria-labelledby="devhub-auth-login-title">
				<button type="button" class="devhub-auth__back" data-devhub-auth-open="chooser">
					<span aria-hidden="true">&larr;</span>
					<span><?php esc_html_e('Back to sign-in options', 'devicehub-theme'); ?></span>
				</button>

				<h2 class="devhub-auth__title" id="devhub-auth-login-title">
					<?php esc_html_e('Guest checkout', 'devicehub-theme'); ?>
				</h2>
				<p class="devhub-auth__subtitle">
					<?php esc_html_e('Continue to checkout without signing in. You can create an account later if you want.', 'devicehub-theme'); ?>
				</p>

				<div class="devhub-auth__form">
					<form method="get" action="<?php echo esc_url(wc_get_checkout_url()); ?>">
						<input type="hidden" name="devhub_guest_checkout" value="1" />
						<p class="devhub-auth__subtitle">
							<?php esc_html_e('You will enter delivery and billing details at checkout without logging in.', 'devicehub-theme'); ?>
						</p>
						<p class="form-row">
							<button type="submit"
								class="woocommerce-button button devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
								<?php esc_html_e('Continue as Guest', 'devicehub-theme'); ?>
							</button>
						</p>
					</form>
				</div>

				<p class="devhub-auth__footer">
					<?php esc_html_e('Already have an account?', 'devicehub-theme'); ?>
					<button type="button"
						data-devhub-auth-open="email"><?php esc_html_e('Use email login', 'devicehub-theme'); ?></button>
				</p>
			</section>

			<section class="devhub-auth__panel" data-devhub-panel="email" aria-labelledby="devhub-auth-email-title">
				<button type="button" class="devhub-auth__back" data-devhub-auth-open="chooser">
					<span aria-hidden="true">&larr;</span>
					<span><?php esc_html_e('Back to sign-in options', 'devicehub-theme'); ?></span>
				</button>

				<h2 class="devhub-auth__title" id="devhub-auth-email-title">
					<?php esc_html_e('Email login', 'devicehub-theme'); ?>
				</h2>
				<p class="devhub-auth__subtitle">
					<?php esc_html_e('Use the email address on your account and your password to log in.', 'devicehub-theme'); ?>
				</p>

				<div class="devhub-auth__form">
					<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
						<input type="hidden" name="devhub_auth_panel" value="email" />

						<?php do_action('woocommerce_login_form_start'); ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label
								for="email_login_email"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span
									class="required" aria-hidden="true">*</span><span
									class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
							<input type="email" class="woocommerce-Input woocommerce-Input--text input-text"
								name="username" id="email_login_email" autocomplete="email"
								value="<?php echo (!empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
								required
								aria-required="true" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</p>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="email_login_password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span
									class="required" aria-hidden="true">*</span><span
									class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
							<input class="woocommerce-Input woocommerce-Input--text input-text" type="password"
								name="password" id="email_login_password" autocomplete="current-password" required
								aria-required="true" />
						</p>

						<?php do_action('woocommerce_login_form'); ?>

						<div class="form-row devhub-auth__actions">
							<label
								class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
								<input class="woocommerce-form__input woocommerce-form__input-checkbox"
									name="rememberme" type="checkbox" id="email_login_rememberme" value="forever" />
								<span><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
							</label>
							<p class="woocommerce-LostPassword lost_password devhub-auth__forgot">
								<a
									href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot password?', 'devicehub-theme'); ?></a>
							</p>
							<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
						</div>
						<p class="form-row">
							<button type="submit"
								class="woocommerce-button button woocommerce-form-login__submit devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
								name="login"
								value="<?php esc_attr_e('Log in', 'woocommerce'); ?>"><?php esc_html_e('Continue', 'devicehub-theme'); ?></button>
						</p>

						<?php do_action('woocommerce_login_form_end'); ?>

					</form>
				</div>

				<?php if ($registration_enabled): ?>
					<p class="devhub-auth__footer">
						<?php esc_html_e('Don’t have an account?', 'devicehub-theme'); ?>
						<button type="button"
							data-devhub-auth-open="register"><?php esc_html_e('Create an account', 'devicehub-theme'); ?></button>
					</p>
				<?php endif; ?>
			</section>

			<section class="devhub-auth__panel" data-devhub-panel="mobile-request" aria-labelledby="devhub-auth-mobile-title">
				<button type="button" class="devhub-auth__back" data-devhub-auth-open="chooser">
					<span aria-hidden="true">&larr;</span>
					<span><?php esc_html_e('Back to sign-in options', 'devicehub-theme'); ?></span>
				</button>

				<h2 class="devhub-auth__title" id="devhub-auth-mobile-title">
					<?php esc_html_e('Mobile sign in', 'devicehub-theme'); ?>
				</h2>
				<p class="devhub-auth__subtitle">
					<?php esc_html_e('Use your mobile number to sign in or create your account with a one-time code.', 'devicehub-theme'); ?>
				</p>

				<div class="devhub-auth__form">
					<form class="devhub-auth__mobile-form" data-devhub-mobile-request novalidate>
						<input type="hidden" name="action" value="devhub_send_mobile_otp" />
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'devhub_mobile_auth' ) ); ?>" />
						<input type="hidden" name="redirect" value="<?php echo esc_url( $auth_redirect_url ); ?>" />

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="mobile_login_phone"><?php esc_html_e('Mobile number', 'devicehub-theme'); ?>&nbsp;<span
									class="required" aria-hidden="true">*</span><span
									class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
							<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text"
								name="phone" id="mobile_login_phone" autocomplete="tel" inputmode="tel" required
								aria-required="true" />
						</p>

						<p class="devhub-auth__subtitle">
							<?php esc_html_e('We will send a 6-digit OTP to this mobile number.', 'devicehub-theme'); ?>
						</p>

						<p class="devhub-auth__status" data-devhub-mobile-request-status hidden></p>

						<p class="form-row">
							<button type="submit"
								class="button devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
								<?php esc_html_e('Send OTP', 'devicehub-theme'); ?>
							</button>
						</p>
					</form>
				</div>

				<p class="devhub-auth__footer">
					<?php esc_html_e('Prefer email instead?', 'devicehub-theme'); ?>
					<button type="button"
						data-devhub-auth-open="email"><?php esc_html_e('Use email login', 'devicehub-theme'); ?></button>
				</p>
			</section>

			<section class="devhub-auth__panel" data-devhub-panel="mobile-verify" aria-labelledby="devhub-auth-mobile-verify-title">
				<button type="button" class="devhub-auth__back" data-devhub-auth-open="mobile-request">
					<span aria-hidden="true">&larr;</span>
					<span><?php esc_html_e('Back to mobile number', 'devicehub-theme'); ?></span>
				</button>

				<h2 class="devhub-auth__title" id="devhub-auth-mobile-verify-title">
					<?php esc_html_e('Enter OTP', 'devicehub-theme'); ?>
				</h2>
				<p class="devhub-auth__subtitle" data-devhub-mobile-verify-copy>
					<?php esc_html_e('Enter the 6-digit code sent to your mobile number.', 'devicehub-theme'); ?>
				</p>

				<div class="devhub-auth__form">
					<form class="devhub-auth__mobile-form" data-devhub-mobile-verify novalidate>
						<input type="hidden" name="action" value="devhub_verify_mobile_otp" />
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'devhub_mobile_auth' ) ); ?>" />
						<input type="hidden" name="phone" value="" />
						<input type="hidden" name="redirect" value="<?php echo esc_url( $auth_redirect_url ); ?>" />

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="mobile_login_otp"><?php esc_html_e('One-time password', 'devicehub-theme'); ?>&nbsp;<span
									class="required" aria-hidden="true">*</span><span
									class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
								name="otp" id="mobile_login_otp" autocomplete="one-time-code" inputmode="numeric"
								pattern="[0-9]*" maxlength="6" required aria-required="true" />
						</p>

						<p class="devhub-auth__status" data-devhub-mobile-verify-status hidden></p>
						<p class="devhub-auth__status" data-devhub-mobile-debug hidden></p>

						<p class="form-row">
							<button type="submit"
								class="button devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
								<?php esc_html_e('Verify and Continue', 'devicehub-theme'); ?>
							</button>
						</p>
					</form>
				</div>

				<p class="devhub-auth__footer">
					<?php esc_html_e('Didn’t receive the code?', 'devicehub-theme'); ?>
					<button type="button" data-devhub-mobile-resend><?php esc_html_e('Resend OTP', 'devicehub-theme'); ?></button>
				</p>
				<p class="devhub-auth__footer">
					<?php esc_html_e('Need a different number?', 'devicehub-theme'); ?>
					<button type="button"
						data-devhub-auth-open="mobile-request"><?php esc_html_e('Change mobile number', 'devicehub-theme'); ?></button>
				</p>
			</section>

			<?php if ($registration_enabled): ?>
				<section class="devhub-auth__panel" data-devhub-panel="register"
					aria-labelledby="devhub-auth-register-title">
					<button type="button" class="devhub-auth__back" data-devhub-auth-open="chooser">
						<span aria-hidden="true">&larr;</span>
						<span><?php esc_html_e('Back to sign-in options', 'devicehub-theme'); ?></span>
					</button>

					<h2 class="devhub-auth__title" id="devhub-auth-register-title">
						<?php esc_html_e('Register', 'woocommerce'); ?>
					</h2>
					<p class="devhub-auth__subtitle">
						<?php esc_html_e('Create an account with the existing WooCommerce registration flow.', 'devicehub-theme'); ?>
					</p>

					<div class="devhub-auth__form">
						<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>
							<input type="hidden" name="devhub_auth_panel" value="register" />

							<?php do_action('woocommerce_register_form_start'); ?>

							<?php if ('no' === get_option('woocommerce_registration_generate_username')): ?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="reg_username"><?php esc_html_e('Username', 'woocommerce'); ?>&nbsp;<span
											class="required" aria-hidden="true">*</span><span
											class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
									<input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
										name="username" id="reg_username" autocomplete="username"
										value="<?php echo (!empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
										required
										aria-required="true" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</p>
							<?php endif; ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="reg_email"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span
										class="required" aria-hidden="true">*</span><span
										class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
								<input type="email" class="woocommerce-Input woocommerce-Input--text input-text"
									name="email" id="reg_email" autocomplete="email"
									value="<?php echo (!empty($_POST['email']) && is_string($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>"
									required aria-required="true" />
							</p>

							<?php if ('no' === get_option('woocommerce_registration_generate_password')): ?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="reg_password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span
											class="required" aria-hidden="true">*</span><span
											class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
									<input type="password" class="woocommerce-Input woocommerce-Input--text input-text"
										name="password" id="reg_password" autocomplete="new-password" required
										aria-required="true" />
								</p>
							<?php else: ?>
								<p class="devhub-auth__subtitle">
									<?php esc_html_e('A link to set a new password will be sent to your email address.', 'woocommerce'); ?>
								</p>
							<?php endif; ?>

							<?php do_action('woocommerce_register_form'); ?>

							<p class="woocommerce-form-row form-row">
								<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
								<button type="submit"
									class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit devhub-auth__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
									name="register"
									value="<?php esc_attr_e('Register', 'woocommerce'); ?>"><?php esc_html_e('Create account', 'devicehub-theme'); ?></button>
							</p>

							<?php do_action('woocommerce_register_form_end'); ?>

						</form>
					</div>

					<p class="devhub-auth__footer">
						<?php esc_html_e('Already have an account?', 'devicehub-theme'); ?>
						<button type="button"
							data-devhub-auth-open="email"><?php esc_html_e('Go to email login', 'devicehub-theme'); ?></button>
					</p>
				</section>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
