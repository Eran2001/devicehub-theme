<?php
/**
 * DeviceHub - Home Page Sections
 *
 * Registers and renders hero, category showcase, and promo banners.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

add_action('devhub_hero_section', 'devhub_render_hero_section');

function devhub_render_hero_section(): void
{
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
        'exclude' => get_option('default_product_cat'),
    ]);

    if (is_wp_error($categories)) {
        return;
    }

    $hero_slides = get_posts([
        'post_type' => 'devhub_hero_slide',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => [
            'menu_order' => 'ASC',
            'date' => 'DESC',
        ],
        'no_found_rows' => true,
    ]);

    $hero_slides = array_values(array_filter($hero_slides, static function (WP_Post $slide): bool {
        return has_post_thumbnail($slide);
    }));

    $slide_count = count($hero_slides);
    ?>
    <section class="devhub-hero" aria-label="<?php esc_attr_e('Hero banner', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <div class="devhub-hero__inner">

                <nav class="devhub-hero__categories"
                    aria-label="<?php esc_attr_e('Product categories', 'devicehub-theme'); ?>">
                    <div class="devhub-hero__cat-header">
                        <i class="fas fa-list-ul" aria-hidden="true"></i>
                        <span><?php esc_html_e('All Categories', 'devicehub-theme'); ?></span>
                        <i class="fas fa-chevron-up devhub-hero__cat-toggle" aria-hidden="true"></i>
                    </div>
                    <ul class="devhub-hero__cat-list">
                        <?php foreach ($categories as $cat):
                            $children = get_terms([
                                'taxonomy' => 'product_cat',
                                'parent' => $cat->term_id,
                                'hide_empty' => false,
                            ]);
                            $has_children = !is_wp_error($children) && !empty($children);
                            ?>
                            <li class="devhub-hero__cat-item<?php echo $has_children ? ' has-children' : ''; ?>">
                                <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                    <?php if ($has_children): ?>
                                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if ($has_children): ?>
                                    <ul class="devhub-hero__cat-sub">
                                        <?php foreach ($children as $child): ?>
                                            <li>
                                                <a href="<?php echo esc_url(get_term_link($child)); ?>">
                                                    <?php echo esc_html($child->name); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <div class="devhub-hero__banner">
                    <?php if ($slide_count > 0): ?>
                        <div class="devhub-hero__slider" id="devhubHeroSlider">
                            <div class="devhub-hero__viewport">
                                <div class="devhub-hero__track">
                                    <?php foreach ($hero_slides as $index => $slide):
                                        $slide_image_id = (int) get_post_thumbnail_id($slide);
                                        $slide_title = trim((string) get_the_title($slide));
                                        ?>
                                        <article class="devhub-hero__slide"
                                            aria-label="<?php echo esc_attr(sprintf(__('Hero slide %1$d of %2$d', 'devicehub-theme'), $index + 1, $slide_count)); ?>">
                                            <div class="devhub-hero__slide-media">
                                                <?php
                                                echo wp_get_attachment_image(
                                                    $slide_image_id,
                                                    'full',
                                                    false,
                                                    [
                                                        'class' => 'devhub-hero__slide-image',
                                                        'alt' => $slide_title !== '' ? $slide_title : __('Hero banner', 'devicehub-theme'),
                                                        'loading' => $index === 0 ? 'eager' : 'lazy',
                                                        'decoding' => 'async',
                                                    ]
                                                );
                                                ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($slide_count > 1): ?>
                                <button class="devhub-hero__arrow devhub-hero__arrow--prev" id="devhubHeroPrev"
                                    type="button" aria-label="<?php esc_attr_e('Previous slide', 'devicehub-theme'); ?>">
                                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                </button>
                                <button class="devhub-hero__arrow devhub-hero__arrow--next" id="devhubHeroNext"
                                    type="button" aria-label="<?php esc_attr_e('Next slide', 'devicehub-theme'); ?>">
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>

                                <div class="devhub-hero__dots" role="tablist"
                                    aria-label="<?php esc_attr_e('Hero slide navigation', 'devicehub-theme'); ?>">
                                    <?php foreach ($hero_slides as $index => $slide): ?>
                                        <button class="devhub-hero__dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                            type="button"
                                            aria-label="<?php echo esc_attr(sprintf(__('Go to slide %d', 'devicehub-theme'), $index + 1)); ?>"
                                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                            data-slide-index="<?php echo esc_attr((string) $index); ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="devhub-hero__empty">
                            <?php if (current_user_can('edit_theme_options')): ?>
                                <p><?php esc_html_e('Add images in Dashboard > Hero Slides to populate this area.', 'devicehub-theme'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
    <?php
}

add_action('devhub_categories_section', 'devhub_render_categories_section');

function devhub_render_categories_section(): void
{
    $categories = [
        ['name' => 'Smart Watches', 'from' => 'LKR 7,000', 'img' => 'SmartWatch.svg'],
        ['name' => 'Cameras', 'from' => 'LKR 20,000', 'img' => 'Cameras.svg'],
        ['name' => 'Headphones', 'from' => 'LKR 12,000', 'img' => 'HeadPhones.svg'],
        ['name' => 'Kettle', 'from' => 'LKR 7,000', 'img' => 'Kettle.svg'],
        ['name' => 'Gaming Set', 'from' => 'LKR 10,000', 'img' => 'GamingSet.svg'],
        ['name' => 'Laptops & PC', 'from' => 'LKR 120,000', 'img' => 'Laptop.svg'],
        ['name' => 'Smartphones', 'from' => 'LKR 20,000', 'img' => 'SmartPhones.svg'],
        ['name' => 'iPhones', 'from' => 'LKR 50,000', 'img' => 'IPhones.svg'],
    ];
    ?>
    <section class="devhub-categories" aria-label="<?php esc_attr_e('Shop by category', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <div class="devhub-categories__inner">

                <div class="devhub-categories__left" aria-hidden="true">
                    <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/CategoryLeftImg.svg'); ?>" alt="">
                </div>

                <div class="devhub-categories__grid">
                    <?php foreach ($categories as $cat):
                        $img_url = DEVHUB_URI . '/assets/images/' . $cat['img'];
                        ?>
                        <a href="#" class="devhub-categories__item">
                            <div class="devhub-categories__item-info">
                                <p class="devhub-categories__item-name"><?php echo esc_html($cat['name']); ?></p>
                                <p class="devhub-categories__item-from">
                                    <?php esc_html_e('From', 'devicehub-theme'); ?><br>
                                    <?php echo esc_html($cat['from']); ?>
                                </p>
                            </div>
                            <div class="devhub-categories__item-img">
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($cat['name']); ?>" loading="lazy">
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </section>
    <?php
}

add_action('devhub_before_broadbands_banner_section', function (): void {
    devhub_render_promo_banner_section('before_broadbands', __('Promo banner before broad bands', 'devicehub-theme'));
});

add_action('devhub_before_electronics_banner_section', function (): void {
    devhub_render_promo_banner_section('before_electronics', __('Promo banner before electronics', 'devicehub-theme'));
});

add_action('devhub_before_accessories_banner_section', function (): void {
    devhub_render_promo_banner_section('before_accessories', __('Promo banner before accessories', 'devicehub-theme'));
});

function devhub_render_promo_banner_section(string $placement, string $aria_label): void
{
    $banners = devhub_get_promo_banners_by_placement($placement);

    if (empty($banners)) {
        return;
    }

    foreach ($banners as $banner) {
        $banner_id = (int) $banner->ID;
        $image_id = (int) get_post_thumbnail_id($banner_id);

        if ($image_id <= 0) {
            continue;
        }

        $link = devhub_get_promo_banner_link($banner_id);
        $title = trim((string) get_the_title($banner_id));
        ?>
        <section class="devhub-preorder" aria-label="<?php echo esc_attr($aria_label); ?>">
            <div class="wf-container">
                <?php if ($link !== ''): ?>
                    <a href="<?php echo esc_url($link); ?>" class="devhub-preorder__link">
                <?php endif; ?>

                <?php
                echo wp_get_attachment_image(
                    $image_id,
                    'full',
                    false,
                    [
                        'class' => 'devhub-preorder__img',
                        'alt' => $title !== '' ? $title : __('Promo banner', 'devicehub-theme'),
                        'loading' => 'lazy',
                        'decoding' => 'async',
                    ]
                );
                ?>

                <?php if ($link !== ''): ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}
