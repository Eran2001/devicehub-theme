<?php
/**
 * Template Name: Frontpage
 *
 * @package DeviceHub
 */

get_header();

do_action('devhub_hero_section');
do_action('devhub_flash_section');
do_action('devhub_mobile_phones_section');
do_action('devhub_categories_section');
do_action('devhub_before_broadbands_banner_section');
do_action('devhub_broadbands_section');
do_action('devhub_before_electronics_banner_section');
do_action('devhub_electronics_section');
do_action('devhub_before_accessories_banner_section');
do_action('devhub_accessories_section');

get_footer();
