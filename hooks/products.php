<?php
/**
 * DeviceHub — Product Sections (Dummy)
 *
 * Fully hardcoded dummy data — wire WooCommerce later.
 * Image: assets/images/Original-Img.svg
 *
 * @package DeviceHub
 */

defined('ABSPATH') || exit;


function devhub_render_product_section_dummy(string $title, string $section_id, array $brands, array $products, string $img = ''): void
{
    if ($img === '') {
        $img = DEVHUB_URI . '/assets/images/Original-Img.svg';
    }
    ?>
    <section class="devhub-products" id="<?php echo esc_attr($section_id); ?>" aria-label="<?php echo esc_attr($title); ?>">
        <div class="wf-container">

            <div class="devhub-products__header">
                <h2 class="devhub-products__title"><?php echo esc_html($title); ?></h2>
                <div class="devhub-products__brands" role="group">
                    <button class="devhub-brand-tab devhub-brand-tab--active" data-brand="all"
                        data-section="<?php echo esc_attr($section_id); ?>" aria-pressed="true">All</button>
                        <?php foreach ($brands as $brand): ?>
                        <button class="devhub-brand-tab" data-brand="<?php echo esc_attr(sanitize_title($brand)); ?>"
                            data-section="<?php echo esc_attr($section_id); ?>"
                            aria-pressed="false"><?php echo esc_html($brand); ?></button>
                        <?php endforeach; ?>
                </div>
            </div>

            <div class="devhub-products__grid" id="<?php echo esc_attr($section_id); ?>-grid">
                    <?php foreach ($products as $p): ?>
                    <div class="devhub-product-card" data-brands="<?php echo esc_attr(sanitize_title($p['brand'])); ?>">

                            <?php if (!empty($p['discount'])): ?>
                            <span class="devhub-product-card__badge">OFF <?php echo esc_html($p['discount']); ?>%</span>
                            <?php endif; ?>

                        <a href="#" class="devhub-product-card__img-wrap">
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p['name']); ?>" loading="lazy">
                        </a>

                        <div class="devhub-product-card__body">
                            <a href="#" class="devhub-product-card__name"><?php echo esc_html($p['name']); ?></a>

                                <?php if (!empty($p['bundle'])): ?>
                                <span class="devhub-product-card__bundle">Bundle Package</span>
                                <?php endif; ?>

                            <div class="devhub-product-card__price">
                                    <?php if (!empty($p['old_price'])): ?>
                                    <del><?php echo esc_html($p['old_price']); ?></del>
                                    <?php endif; ?>
                                <ins><?php echo esc_html($p['price']); ?></ins>
                            </div>

                            <span class="devhub-product-card__stock devhub-product-card__stock--in">
                                &#9679; In stock
                            </span>
                        </div>

                    </div>
                    <?php endforeach; ?>
            </div>

            <div class="devhub-products__footer">
                <a href="#" class="devhub-products__view-all">
                    View All <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

        </div>
    </section>
        <?php
}


// ── Mobile Phones ─────────────────────────────────────────────────────────────

add_action('devhub_products_section', 'devhub_render_mobile_phones_section');

function devhub_render_mobile_phones_section(): void
{
    devhub_render_product_section_dummy(
        'Mobile Phones',
        'devhub-mobile-phones',
        ['Apple', 'Samsung', 'Vivo', 'Redmi', 'OnePlus'],
        [
            ['name' => 'Apple iPhone 16 Pro 512GB Pink', 'brand' => 'Apple', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => true],
            ['name' => 'Apple iPhone 15 Pro 1TB White', 'brand' => 'Apple', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'Samsung Galaxy S23 Ultra 256GB', 'brand' => 'Samsung', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => true],
            ['name' => 'Samsung Galaxy A16 4G 8GB RAM', 'brand' => 'Samsung', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => true],
            ['name' => 'Redmi Note 14 Pro Plus 12GB RAM', 'brand' => 'Redmi', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'OnePlus 13R 12GB RAM 256GB', 'brand' => 'OnePlus', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'Vivo V40 Pro 5G 12GB RAM 256GB', 'brand' => 'Vivo', 'price' => 'LKR 153,900', 'old_price' => 'LKR 162,000', 'discount' => '5', 'bundle' => true],
            ['name' => 'Apple iPhone 14 Pro Max 1TB Gray', 'brand' => 'Apple', 'price' => 'LKR 153,900', 'old_price' => 'LKR 162,000', 'discount' => '5', 'bundle' => true],
        ]
    );
}


// ── Broad Bands ───────────────────────────────────────────────────────────────

add_action('devhub_broadbands_section', 'devhub_render_broadbands_section');

function devhub_render_broadbands_section(): void
{
    devhub_render_product_section_dummy(
        'Broad Bands',
        'devhub-broad-bands',
        ['TP-Link', 'Huawei', 'ZTE'],
        [
            ['name' => 'TP-Link 300 Mbps 4G LTE Router TL-MR100', 'brand' => 'TP-Link', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => true],
            ['name' => 'TP-Link AC1200 Dual Band WiFi Router', 'brand' => 'TP-Link', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'Huawei B535 4G+ LTE Cat7 Router', 'brand' => 'Huawei', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => true],
            ['name' => 'Huawei CPE Pro 2 5G Router H122-373', 'brand' => 'Huawei', 'price' => 'LKR 153,900', 'old_price' => 'LKR 162,000', 'discount' => '5', 'bundle' => true],
            ['name' => 'ZTE MF286D 4G LTE CPE Router', 'brand' => 'ZTE', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'ZTE MC801A 5G Indoor CPE Router', 'brand' => 'ZTE', 'price' => 'LKR 130,000', 'old_price' => 'LKR 149,000', 'discount' => '20', 'bundle' => true],
            ['name' => 'TP-Link Deco XE75 WiFi 6E Mesh System', 'brand' => 'TP-Link', 'price' => 'LKR 153,900', 'old_price' => '', 'discount' => '', 'bundle' => false],
            ['name' => 'Huawei WiFi AX3 Pro Dual-Core Router', 'brand' => 'Huawei', 'price' => 'LKR 153,900', 'old_price' => 'LKR 162,000', 'discount' => '5', 'bundle' => false],
        ],
        DEVHUB_URI . '/assets/images/Original-Router-Img.svg'
    );
}