<?php
/**
 * DeviceHub — Flash Sale Section
 *
 * Static dummy UI — wire WooCommerce data later.
 * Image: assets/images/Original-Img.svg
 * Countdown: flash-countdown.js reads data-countdown attribute.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

add_action('devhub_flash_section', 'devhub_render_flash_section');

function devhub_render_flash_section(): void
{
    $img = DEVHUB_URI . '/assets/images/Original-Img.svg';
    $end_ts = time() + 3 * DAY_IN_SECONDS;
    $end_date = gmdate('Y-m-d\TH:i:s', $end_ts);

    $cards = [
        [
            'modifier' => 'green',
            'name' => 'Apple iPhone 16 Pro 512GB Pink (MQ233)',
            'price' => 'Rs.500,000',
        ],
        [
            'modifier' => 'yellow',
            'name' => 'Apple iPhone 16 Pro 512GB Pink (MQ233)',
            'price' => 'Rs.500,000',
        ],
    ];
    ?>
    <section class="devhub-flash" aria-label="<?php esc_attr_e('Flash sale', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <div class="devhub-flash__grid">
                <?php foreach ($cards as $card): ?>
                    <div class="devhub-flash__card devhub-flash__card--<?php echo esc_attr($card['modifier']); ?>">

                        <div class="devhub-flash__img-wrap">
                            <div class="devhub-flash__img-circle">
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($card['name']); ?>">
                            </div>
                        </div>

                        <div class="devhub-flash__body">
                            <div class="devhub-flash__timer-wrap">
                                <div class="devhub-flash__timer-labels" aria-hidden="true">
                                    <span><?php esc_html_e('Days', 'devicehub-theme'); ?></span>
                                    <span><?php esc_html_e('Hours', 'devicehub-theme'); ?></span>
                                    <span><?php esc_html_e('Minutes', 'devicehub-theme'); ?></span>
                                    <span><?php esc_html_e('Seconds', 'devicehub-theme'); ?></span>
                                </div>
                                <div class="devhub-flash__timer" data-countdown="<?php echo esc_attr($end_date); ?>"
                                    aria-live="polite">
                                    <span class="devhub-flash__time-part" data-part="days">00</span>
                                    <span class="devhub-flash__sep" aria-hidden="true">:</span>
                                    <span class="devhub-flash__time-part" data-part="hours">00</span>
                                    <span class="devhub-flash__sep" aria-hidden="true">:</span>
                                    <span class="devhub-flash__time-part" data-part="minutes">00</span>
                                    <span class="devhub-flash__sep" aria-hidden="true">:</span>
                                    <span class="devhub-flash__time-part" data-part="seconds">00</span>
                                </div>
                            </div>

                            <div class="devhub-flash__info">
                                <p class="devhub-flash__name"><?php echo esc_html($card['name']); ?></p>
                                <p class="devhub-flash__price"><?php echo esc_html($card['price']); ?></p>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}