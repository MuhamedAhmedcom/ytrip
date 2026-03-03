<?php
/**
 * YTrip Taxonomy Meta Fields
 *
 * @package YTrip
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CSF' ) ) {
	return;
}

// ============================================================
// DESTINATION TAXONOMY META
// ============================================================
CSF::createTaxonomyOptions( 'ytrip_destination_meta', array(
	'taxonomy'  => 'ytrip_destination',
	'data_type' => 'serialize',
) );

CSF::createSection( 'ytrip_destination_meta', array(
	'fields' => array(
		array(
			'id'    => 'destination_image',
			'type'  => 'media',
			'title' => esc_html__( 'Featured Image', 'ytrip' ),
			'desc'  => esc_html__( 'Main image for this destination (recommended: 800x600px).', 'ytrip' ),
		),
		array(
			'id'    => 'destination_banner',
			'type'  => 'media',
			'title' => esc_html__( 'Banner Image', 'ytrip' ),
			'desc'  => esc_html__( 'Wide banner image for archive pages (recommended: 1920x400px).', 'ytrip' ),
		),
		array(
			'id'    => 'destination_gallery',
			'type'  => 'gallery',
			'title' => esc_html__( 'Gallery', 'ytrip' ),
			'desc'  => esc_html__( 'Additional images for this destination.', 'ytrip' ),
		),
		array(
			'id'    => 'destination_icon',
			'type'  => 'icon',
			'title' => esc_html__( 'Icon', 'ytrip' ),
		),
		array(
			'id'    => 'destination_color',
			'type'  => 'color',
			'title' => esc_html__( 'Theme Color', 'ytrip' ),
			'desc'  => esc_html__( 'Accent color for this destination.', 'ytrip' ),
		),
		array(
			'id'    => 'short_description',
			'type'  => 'textarea',
			'title' => esc_html__( 'Short Description', 'ytrip' ),
			'desc'  => esc_html__( 'Brief description for cards (150 characters max).', 'ytrip' ),
		),
		array(
			'id'    => 'full_description',
			'type'  => 'wp_editor',
			'title' => esc_html__( 'Full Description', 'ytrip' ),
		),
		array(
			'id'    => 'map_location',
			'type'  => 'map',
			'title' => esc_html__( 'Map Location', 'ytrip' ),
		),
		array(
			'id'    => 'country',
			'type'  => 'text',
			'title' => esc_html__( 'Country', 'ytrip' ),
		),
		array(
			'id'    => 'continent',
			'type'  => 'select',
			'title' => esc_html__( 'Continent', 'ytrip' ),
			'options' => array(
				'africa'        => esc_html__( 'Africa', 'ytrip' ),
				'asia'          => esc_html__( 'Asia', 'ytrip' ),
				'europe'        => esc_html__( 'Europe', 'ytrip' ),
				'north_america' => esc_html__( 'North America', 'ytrip' ),
				'south_america' => esc_html__( 'South America', 'ytrip' ),
				'oceania'       => esc_html__( 'Oceania', 'ytrip' ),
				'antarctica'    => esc_html__( 'Antarctica', 'ytrip' ),
			),
		),
		array(
			'id'    => 'climate',
			'type'  => 'text',
			'title' => esc_html__( 'Climate', 'ytrip' ),
			'desc'  => esc_html__( 'e.g. Tropical, Mediterranean, Desert', 'ytrip' ),
		),
		array(
			'id'    => 'best_time_to_visit',
			'type'  => 'text',
			'title' => esc_html__( 'Best Time to Visit', 'ytrip' ),
			'desc'  => esc_html__( 'e.g. March to October', 'ytrip' ),
		),
		array(
			'id'    => 'timezone',
			'type'  => 'text',
			'title' => esc_html__( 'Timezone', 'ytrip' ),
			'desc'  => esc_html__( 'e.g. GMT+2', 'ytrip' ),
		),
		array(
			'id'    => 'currency',
			'type'  => 'text',
			'title' => esc_html__( 'Local Currency', 'ytrip' ),
		),
		array(
			'id'    => 'language',
			'type'  => 'text',
			'title' => esc_html__( 'Language', 'ytrip' ),
		),
		array(
			'id'      => 'is_featured',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Featured Destination', 'ytrip' ),
			'default' => false,
		),
		array(
			'id'      => 'display_order',
			'type'    => 'number',
			'title'   => esc_html__( 'Display Order', 'ytrip' ),
			'default' => 0,
		),
		array(
			'id'     => 'travel_tips',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Travel Tips', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'tip',
					'type'  => 'text',
					'title' => esc_html__( 'Tip', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// CATEGORY TAXONOMY META
// ============================================================
CSF::createTaxonomyOptions( 'ytrip_category_meta', array(
	'taxonomy'  => 'ytrip_category',
	'data_type' => 'serialize',
) );

CSF::createSection( 'ytrip_category_meta', array(
	'fields' => array(
		array(
			'id'    => 'category_image',
			'type'  => 'media',
			'title' => esc_html__( 'Featured Image', 'ytrip' ),
			'desc'  => esc_html__( 'Main image for this category (recommended: 600x400px).', 'ytrip' ),
		),
		array(
			'id'    => 'category_banner',
			'type'  => 'media',
			'title' => esc_html__( 'Banner Image', 'ytrip' ),
			'desc'  => esc_html__( 'Wide banner image for archive pages (recommended: 1920x400px).', 'ytrip' ),
		),
		array(
			'id'    => 'category_icon',
			'type'  => 'icon',
			'title' => esc_html__( 'Icon', 'ytrip' ),
			'desc'  => esc_html__( 'Icon to display in navigation and cards.', 'ytrip' ),
		),
		array(
			'id'    => 'category_color',
			'type'  => 'color',
			'title' => esc_html__( 'Theme Color', 'ytrip' ),
			'desc'  => esc_html__( 'Accent color for this category.', 'ytrip' ),
		),
		array(
			'id'    => 'short_description',
			'type'  => 'textarea',
			'title' => esc_html__( 'Short Description', 'ytrip' ),
			'desc'  => esc_html__( 'Brief description for cards.', 'ytrip' ),
		),
		array(
			'id'    => 'full_description',
			'type'  => 'wp_editor',
			'title' => esc_html__( 'Full Description', 'ytrip' ),
		),
		array(
			'id'      => 'difficulty_range',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Typical Difficulty', 'ytrip' ),
			'options' => array(
				'easy'      => esc_html__( 'Easy', 'ytrip' ),
				'moderate'  => esc_html__( 'Moderate', 'ytrip' ),
				'difficult' => esc_html__( 'Difficult', 'ytrip' ),
				'mixed'     => esc_html__( 'Various', 'ytrip' ),
			),
			'default' => 'mixed',
		),
		array(
			'id'      => 'is_featured',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Featured Category', 'ytrip' ),
			'default' => false,
		),
		array(
			'id'      => 'display_order',
			'type'    => 'number',
			'title'   => esc_html__( 'Display Order', 'ytrip' ),
			'default' => 0,
		),
		array(
			'id'     => 'highlights',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Category Highlights', 'ytrip' ),
			'desc'   => esc_html__( 'Key features of tours in this category.', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'highlight',
					'type'  => 'text',
					'title' => esc_html__( 'Highlight', 'ytrip' ),
				),
			),
		),
		array(
			'id'    => 'seo_title',
			'type'  => 'text',
			'title' => esc_html__( 'SEO Title', 'ytrip' ),
		),
		array(
			'id'    => 'seo_description',
			'type'  => 'textarea',
			'title' => esc_html__( 'SEO Description', 'ytrip' ),
		),
	),
) );
