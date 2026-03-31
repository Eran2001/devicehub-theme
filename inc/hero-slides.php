<?php
/**
 * DeviceHub - Hero Slides
 *
 * Registers an admin-managed post type used by the homepage hero slider.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

add_action('init', 'devhub_register_hero_slides');

function devhub_register_hero_slides(): void
{
    register_post_type('devhub_hero_slide', [
        'labels' => [
            'name'                  => __('Hero Slides', 'devicehub-theme'),
            'singular_name'         => __('Hero Slide', 'devicehub-theme'),
            'menu_name'             => __('Hero Slides', 'devicehub-theme'),
            'add_new'               => __('Add Slide', 'devicehub-theme'),
            'add_new_item'          => __('Add New Hero Slide', 'devicehub-theme'),
            'edit_item'             => __('Edit Hero Slide', 'devicehub-theme'),
            'new_item'              => __('New Hero Slide', 'devicehub-theme'),
            'view_item'             => __('View Hero Slide', 'devicehub-theme'),
            'search_items'          => __('Search Hero Slides', 'devicehub-theme'),
            'not_found'             => __('No hero slides found.', 'devicehub-theme'),
            'not_found_in_trash'    => __('No hero slides found in Trash.', 'devicehub-theme'),
            'featured_image'        => __('Hero Banner Image', 'devicehub-theme'),
            'set_featured_image'    => __('Set Hero Banner Image', 'devicehub-theme'),
            'remove_featured_image' => __('Remove Hero Banner Image', 'devicehub-theme'),
            'use_featured_image'    => __('Use as Hero Banner Image', 'devicehub-theme'),
        ],
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 58,
        'menu_icon'          => 'dashicons-images-alt2',
        'supports'           => ['title', 'thumbnail', 'page-attributes'],
        'exclude_from_search'=> true,
        'publicly_queryable' => false,
        'show_in_nav_menus'  => false,
        'show_in_rest'       => false,
    ]);
}

add_action('add_meta_boxes_devhub_hero_slide', 'devhub_add_hero_slide_help_box');

function devhub_add_hero_slide_help_box(): void
{
    add_meta_box(
        'devhub-hero-slide-help',
        __('Hero Slide Guide', 'devicehub-theme'),
        'devhub_render_hero_slide_help_box',
        'devhub_hero_slide',
        'side',
        'high'
    );
}

function devhub_render_hero_slide_help_box(): void
{
    echo '<p>' . esc_html__('Upload the slide image using the Hero Banner Image box.', 'devicehub-theme') . '</p>';
    echo '<p>' . esc_html__('Use the title only as an internal admin label.', 'devicehub-theme') . '</p>';
    echo '<p>' . esc_html__('Use the Order field in Page Attributes to control slide order.', 'devicehub-theme') . '</p>';
}

add_filter('enter_title_here', 'devhub_hero_slide_title_placeholder', 10, 2);

function devhub_hero_slide_title_placeholder(string $placeholder, WP_Post $post): string
{
    if ($post->post_type === 'devhub_hero_slide') {
        return __('Slide name for admin only', 'devicehub-theme');
    }

    return $placeholder;
}

add_filter('manage_devhub_hero_slide_posts_columns', 'devhub_hero_slide_columns');

function devhub_hero_slide_columns(array $columns): array
{
    $columns = [
        'cb'         => $columns['cb'] ?? '',
        'thumbnail'  => __('Image', 'devicehub-theme'),
        'title'      => __('Slide Name', 'devicehub-theme'),
        'menu_order' => __('Order', 'devicehub-theme'),
        'date'       => __('Date', 'devicehub-theme'),
    ];

    return $columns;
}

add_action('manage_devhub_hero_slide_posts_custom_column', 'devhub_render_hero_slide_column', 10, 2);

function devhub_render_hero_slide_column(string $column, int $post_id): void
{
    if ($column === 'thumbnail') {
        if (has_post_thumbnail($post_id)) {
            echo get_the_post_thumbnail($post_id, [80, 48], ['style' => 'width:80px;height:48px;object-fit:cover;border-radius:6px;']);
        } else {
            esc_html_e('No image', 'devicehub-theme');
        }
    }

    if ($column === 'menu_order') {
        echo esc_html((string) get_post_field('menu_order', $post_id));
    }
}
