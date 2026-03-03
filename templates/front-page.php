<?php
/**
 * YTrip Front Page Template
 *
 * Used when "Replace content" is enabled so the homepage shows only YTrip sections
 * regardless of theme structure. Bypasses the_content so hero, search, destinations, etc. always render.
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$builder = YTrip_Homepage_Builder::instance();
$builder->render_all_sections();

get_footer();
