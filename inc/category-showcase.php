<?php
/**
 * DeviceHub - Category Showcase
 *
 * Admin-managed category items and left banner for the homepage
 * category showcase section.
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;

define('DEVHUB_CAT_FROM_PRICE_META', '_devhub_cat_from_price');
define('DEVHUB_CAT_LINK_META', '_devhub_cat_link');
define('DEVHUB_CAT_BANNER_OPTION', 'devhub_cat_showcase_banner_id');

// ── Register CPT ──────────────────────────────────────────────────────────────

add_action('init', 'devhub_register_cat_showcase');

function devhub_register_cat_showcase(): void
{
    register_post_type('devhub_cat_item', [
        'labels' => [
            'name'                  => __('Category Showcase', 'devicehub-theme'),
            'singular_name'         => __('Category Item', 'devicehub-theme'),
            'menu_name'             => __('Category Showcase', 'devicehub-theme'),
            'add_new'               => __('Add Item', 'devicehub-theme'),
            'add_new_item'          => __('Add New Category Item', 'devicehub-theme'),
            'edit_item'             => __('Edit Category Item', 'devicehub-theme'),
            'new_item'              => __('New Category Item', 'devicehub-theme'),
            'view_item'             => __('View Category Item', 'devicehub-theme'),
            'search_items'          => __('Search Category Items', 'devicehub-theme'),
            'not_found'             => __('No category items found.', 'devicehub-theme'),
            'not_found_in_trash'    => __('No category items found in Trash.', 'devicehub-theme'),
            'featured_image'        => __('Category Image', 'devicehub-theme'),
            'set_featured_image'    => __('Set Category Image', 'devicehub-theme'),
            'remove_featured_image' => __('Remove Category Image', 'devicehub-theme'),
            'use_featured_image'    => __('Use as Category Image', 'devicehub-theme'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 60,
        'menu_icon'           => 'dashicons-grid-view',
        'supports'            => ['title', 'thumbnail', 'page-attributes'],
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => false,
    ]);
}

// ── Meta Boxes ────────────────────────────────────────────────────────────────

add_action('add_meta_boxes_devhub_cat_item', 'devhub_add_cat_item_meta_boxes');

function devhub_add_cat_item_meta_boxes(): void
{
    add_meta_box(
        'devhub-cat-item-settings',
        __('Category Settings', 'devicehub-theme'),
        'devhub_render_cat_item_settings_box',
        'devhub_cat_item',
        'normal',
        'high'
    );

    add_meta_box(
        'devhub-cat-item-help',
        __('Category Item Guide', 'devicehub-theme'),
        'devhub_render_cat_item_help_box',
        'devhub_cat_item',
        'side',
        'high'
    );
}

function devhub_render_cat_item_settings_box(WP_Post $post): void
{
    wp_nonce_field('devhub_save_cat_item', 'devhub_cat_item_nonce');

    $from_price = (string) get_post_meta($post->ID, DEVHUB_CAT_FROM_PRICE_META, true);
    $link       = (string) get_post_meta($post->ID, DEVHUB_CAT_LINK_META, true);
    ?>
    <p>
        <label for="devhub-cat-from-price">
            <strong><?php esc_html_e('Starting Price', 'devicehub-theme'); ?></strong>
        </label><br>
        <input
            id="devhub-cat-from-price"
            type="number"
            name="devhub_cat_from_price"
            value="<?php echo esc_attr($from_price); ?>"
            placeholder="e.g. 7000"
            min="0"
            step="1"
            style="width:100%;margin-top:8px;"
        >
        <span class="description"><?php esc_html_e('Enter the number only (e.g. 7000). The currency symbol and comma formatting are added automatically.', 'devicehub-theme'); ?></span>
    </p>

    <p style="margin-top:16px;">
        <label for="devhub-cat-link">
            <strong><?php esc_html_e('Category Link', 'devicehub-theme'); ?></strong>
        </label><br>
        <input
            id="devhub-cat-link"
            type="url"
            name="devhub_cat_link"
            value="<?php echo esc_attr($link); ?>"
            placeholder="https://example.com/product-category/smart-watches"
            style="width:100%;margin-top:8px;"
        >
        <span class="description"><?php esc_html_e('Where users go when they click this category tile. Use the WooCommerce category page URL.', 'devicehub-theme'); ?></span>
    </p>
    <?php
}

function devhub_render_cat_item_help_box(): void
{
    echo '<p>' . esc_html__('Required before publish:', 'devicehub-theme') . '</p>';
    echo '<ul style="list-style:disc;padding-left:18px;margin:0;">';
    echo '<li>' . esc_html__('Category Image (Featured Image)', 'devicehub-theme') . '</li>';
    echo '<li>' . esc_html__('Starting Price', 'devicehub-theme') . '</li>';
    echo '<li>' . esc_html__('Category Link', 'devicehub-theme') . '</li>';
    echo '</ul>';
    echo '<p style="margin-top:12px;">' . esc_html__('Use the Order field in Page Attributes to control which items appear first. Up to 8 items are shown on the homepage.', 'devicehub-theme') . '</p>';
}

// ── Save Meta ─────────────────────────────────────────────────────────────────

add_action('save_post_devhub_cat_item', 'devhub_save_cat_item_meta', 10, 2);

function devhub_save_cat_item_meta(int $post_id, WP_Post $post): void
{
    if (!isset($_POST['devhub_cat_item_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['devhub_cat_item_nonce'])), 'devhub_save_cat_item')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $from_price = sanitize_text_field(wp_unslash($_POST['devhub_cat_from_price'] ?? ''));
    if ($from_price !== '') {
        update_post_meta($post_id, DEVHUB_CAT_FROM_PRICE_META, $from_price);
    } else {
        delete_post_meta($post_id, DEVHUB_CAT_FROM_PRICE_META);
    }

    $link = esc_url_raw(wp_unslash($_POST['devhub_cat_link'] ?? ''));
    if ($link !== '') {
        update_post_meta($post_id, DEVHUB_CAT_LINK_META, $link);
    } else {
        delete_post_meta($post_id, DEVHUB_CAT_LINK_META);
    }
}

// ── Title Placeholder ─────────────────────────────────────────────────────────

add_filter('enter_title_here', 'devhub_cat_item_title_placeholder', 10, 2);

function devhub_cat_item_title_placeholder(string $placeholder, WP_Post $post): string
{
    if ($post->post_type === 'devhub_cat_item') {
        return __('Category display name (e.g. Smart Watches)', 'devicehub-theme');
    }

    return $placeholder;
}

// ── Admin Columns ─────────────────────────────────────────────────────────────

add_filter('manage_devhub_cat_item_posts_columns', 'devhub_cat_item_columns');

function devhub_cat_item_columns(array $columns): array
{
    return [
        'cb'         => $columns['cb'] ?? '',
        'thumbnail'  => __('Image', 'devicehub-theme'),
        'title'      => __('Category Name', 'devicehub-theme'),
        'from_price' => __('Starting Price', 'devicehub-theme'),
        'cat_link'   => __('Link', 'devicehub-theme'),
        'menu_order' => __('Order', 'devicehub-theme'),
        'date'       => __('Date', 'devicehub-theme'),
    ];
}

add_action('manage_devhub_cat_item_posts_custom_column', 'devhub_render_cat_item_column', 10, 2);

function devhub_render_cat_item_column(string $column, int $post_id): void
{
    if ($column === 'thumbnail') {
        if (has_post_thumbnail($post_id)) {
            echo get_the_post_thumbnail($post_id, [60, 60], ['style' => 'width:60px;height:60px;object-fit:contain;border-radius:4px;background:#f5f5f5;padding:4px;']);
        } else {
            esc_html_e('No image', 'devicehub-theme');
        }
    }

    if ($column === 'from_price') {
        $price = (string) get_post_meta($post_id, DEVHUB_CAT_FROM_PRICE_META, true);
        echo esc_html($price !== '' ? $price : '—');
    }

    if ($column === 'cat_link') {
        $link = (string) get_post_meta($post_id, DEVHUB_CAT_LINK_META, true);
        if ($link !== '') {
            echo '<a href="' . esc_url($link) . '" target="_blank" rel="noopener">' . esc_html__('View', 'devicehub-theme') . '</a>';
        } else {
            echo '—';
        }
    }

    if ($column === 'menu_order') {
        echo esc_html((string) get_post_field('menu_order', $post_id));
    }
}

// ── Showcase Settings Page (left banner) ─────────────────────────────────────

add_action('admin_menu', 'devhub_cat_showcase_settings_menu');

function devhub_cat_showcase_settings_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=devhub_cat_item',
        __('Showcase Settings', 'devicehub-theme'),
        __('Showcase Settings', 'devicehub-theme'),
        'manage_options',
        'devhub-cat-showcase-settings',
        'devhub_render_cat_showcase_settings_page'
    );
}

add_action('admin_enqueue_scripts', 'devhub_cat_showcase_enqueue_media');

function devhub_cat_showcase_enqueue_media(string $hook): void
{
    if ($hook !== 'devhub_cat_item_page_devhub-cat-showcase-settings') {
        return;
    }

    wp_enqueue_media();
}

function devhub_render_cat_showcase_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (
        isset($_POST['devhub_cat_showcase_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['devhub_cat_showcase_nonce'])), 'devhub_save_cat_showcase')
    ) {
        $banner_id = absint($_POST['devhub_cat_banner_id'] ?? 0);
        if ($banner_id > 0) {
            update_option(DEVHUB_CAT_BANNER_OPTION, $banner_id);
        } else {
            delete_option(DEVHUB_CAT_BANNER_OPTION);
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'devicehub-theme') . '</p></div>';
    }

    $banner_id  = (int) get_option(DEVHUB_CAT_BANNER_OPTION, 0);
    $banner_url = $banner_id > 0 ? wp_get_attachment_image_url($banner_id, 'medium') : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Category Showcase Settings', 'devicehub-theme'); ?></h1>
        <p><?php esc_html_e('Controls the left-side banner image in the Category Showcase section on the homepage.', 'devicehub-theme'); ?></p>

        <form method="post">
            <?php wp_nonce_field('devhub_save_cat_showcase', 'devhub_cat_showcase_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="devhub-cat-banner-id">
                            <?php esc_html_e('Left Banner Image', 'devicehub-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <div id="devhub-cat-banner-preview" style="margin-bottom:12px;">
                            <?php if ($banner_url !== ''): ?>
                                <img
                                    src="<?php echo esc_url($banner_url); ?>"
                                    alt=""
                                    style="max-width:220px;border-radius:6px;display:block;"
                                >
                            <?php endif; ?>
                        </div>

                        <input
                            type="hidden"
                            name="devhub_cat_banner_id"
                            id="devhub-cat-banner-id"
                            value="<?php echo esc_attr((string) $banner_id); ?>"
                        >

                        <button type="button" class="button" id="devhub-cat-banner-select">
                            <?php esc_html_e('Select Image', 'devicehub-theme'); ?>
                        </button>

                        <?php if ($banner_id > 0): ?>
                            <button type="button" class="button" id="devhub-cat-banner-remove" style="margin-left:8px;">
                                <?php esc_html_e('Remove Image', 'devicehub-theme'); ?>
                            </button>
                        <?php endif; ?>

                        <p class="description" style="margin-top:8px;">
                            <?php esc_html_e('Recommended: tall image (e.g. 300 × 260 px). Displays to the left of the 8 category tiles.', 'devicehub-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'devicehub-theme')); ?>
        </form>
    </div>

    <script>
    (function ($) {
        var frame;

        $('#devhub-cat-banner-select').on('click', function () {
            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: '<?php echo esc_js(__('Select Left Banner Image', 'devicehub-theme')); ?>',
                button: { text: '<?php echo esc_js(__('Use this image', 'devicehub-theme')); ?>' },
                multiple: false,
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#devhub-cat-banner-id').val(attachment.id);
                $('#devhub-cat-banner-preview').html(
                    '<img src="' + attachment.url + '" alt="" style="max-width:220px;border-radius:6px;display:block;">'
                );
                $('#devhub-cat-banner-remove').show();
            });

            frame.open();
        });

        $('#devhub-cat-banner-remove').on('click', function () {
            $('#devhub-cat-banner-id').val('');
            $('#devhub-cat-banner-preview').html('');
            $(this).hide();
        });
    }(jQuery));
    </script>
    <?php
}

// ── Data Helpers ──────────────────────────────────────────────────────────────

function devhub_get_cat_showcase_items(): array
{
    return get_posts([
        'post_type'      => 'devhub_cat_item',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'orderby'        => [
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ],
        'no_found_rows'  => true,
    ]);
}

function devhub_get_cat_showcase_banner_url(): string
{
    $banner_id = (int) get_option(DEVHUB_CAT_BANNER_OPTION, 0);
    if ($banner_id <= 0) {
        return '';
    }

    return (string) wp_get_attachment_image_url($banner_id, 'full');
}
