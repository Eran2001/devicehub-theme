<?php
/**
 * Template Name: Frontpage
 *
 * @package DeviceHub
 */

get_header();

do_action('devhub_hero_section');
do_action('devhub_flash_section');
do_action('devhub_products_section');
do_action('devhub_categories_section');
do_action('devhub_preorder_section');
do_action('devhub_broadbands_section');

get_footer();