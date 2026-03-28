<?php
/**
 * DeviceHub — Home Page Sections
 *
 * Registers and renders: hero, category showcase, pre-order banner.
 * Each add_action → one function. One function per section.
 *
 * Banner content (eyebrow/title/subtitle) comes from WP Customizer options
 * so editors can update it without touching code.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;


// ── Hero section ──────────────────────────────────────────────────────────────

add_action('devhub_hero_section', 'devhub_render_hero_section');

function devhub_render_hero_section(): void
{
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
        'exclude' => get_option('default_product_cat'),
    ]);

    if (is_wp_error($categories))
        return;

    $banner_eyebrow = get_theme_mod('devhub_hero_eyebrow', 'GALAXY SALE IS LIVE NOW');
    $banner_title   = get_theme_mod('devhub_hero_title', 'Galaxy S24 | S24+');
    $banner_subtitle = get_theme_mod('devhub_hero_subtitle', 'Get up to $1,000 in trade-in credit from participating carriers. Terms apply.');
    ?>
    <section class="devhub-hero" aria-label="<?php esc_attr_e('Hero banner', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <div class="devhub-hero__inner">

                <!-- Category sidebar -->
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

                <!-- Banner -->
                <div class="devhub-hero__banner">
                    <div class="devhub-hero__banner-content">
                        <?php if ($banner_eyebrow): ?>
                            <p class="devhub-hero__eyebrow"><?php echo esc_html($banner_eyebrow); ?></p>
                        <?php endif; ?>
                        <?php if ($banner_title): ?>
                            <h2 class="devhub-hero__title"><?php echo esc_html($banner_title); ?></h2>
                        <?php endif; ?>
                        <?php if ($banner_subtitle): ?>
                            <p class="devhub-hero__subtitle"><?php echo esc_html($banner_subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="devhub-hero__center-img" aria-hidden="true">
                        <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/Hero-Section-Center-Img.svg'); ?>" alt="">
                    </div>
                </div>

                <!-- Right device image -->
                <div class="devhub-hero__right-img" aria-hidden="true">
                    <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/Hero-Section-Right-Img.svg'); ?>" alt="">
                </div>

            </div>
        </div>
    </section>
    <?php
}


// ── Category showcase section ─────────────────────────────────────────────────

add_action('devhub_categories_section', 'devhub_render_categories_section');

function devhub_render_categories_section(): void
{
    // Dummy data — wire WooCommerce later
    $categories = [
        ['name' => 'Smart Watches', 'from' => 'LKR 7,000',    'img' => 'SmartWatch.svg'],
        ['name' => 'Cameras',       'from' => 'LKR 20,000',   'img' => 'Cameras.svg'],
        ['name' => 'Headphones',    'from' => 'LKR 12,000',   'img' => 'HeadPhones.svg'],
        ['name' => 'Kettle',        'from' => 'LKR 7,000',    'img' => 'Kettle.svg'],
        ['name' => 'Gaming Set',    'from' => 'LKR 10,000',   'img' => 'GamingSet.svg'],
        ['name' => 'Laptops & PC',  'from' => 'LKR 120,000',  'img' => 'Laptop.svg'],
        ['name' => 'Smartphones',   'from' => 'LKR 20,000',   'img' => 'SmartPhones.svg'],
        ['name' => 'iPhones',       'from' => 'LKR 50,000',   'img' => 'IPhones.svg'],
    ];
    ?>
    <section class="devhub-categories" aria-label="<?php esc_attr_e('Shop by category', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <div class="devhub-categories__inner">

                <!-- Left promo image -->
                <div class="devhub-categories__left" aria-hidden="true">
                    <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/CategoryLeftImg.svg'); ?>" alt="">
                </div>

                <!-- Category grid -->
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


// ── Pre-order banner section ──────────────────────────────────────────────────

add_action('devhub_preorder_section', 'devhub_render_preorder_section');

function devhub_render_preorder_section(): void
{
    ?>
    <section class="devhub-preorder" aria-label="<?php esc_attr_e('Pre-order', 'devicehub-theme'); ?>">
        <div class="wf-container">
            <img src="<?php echo esc_url(DEVHUB_URI . '/assets/images/PreOrderGroupImg.svg'); ?>"
                alt="<?php esc_attr_e('Pre Order — be the first to own', 'devicehub-theme'); ?>"
                class="devhub-preorder__img">
        </div>
    </section>
    <?php
}