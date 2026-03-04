<?php
/**
 * YTrip Main Settings Configuration
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

$prefix = 'ytrip_settings';

// Create Main Settings Panel
CSF::createOptions( $prefix, array(
	'menu_title'      => esc_html__( 'YTrip Settings', 'ytrip' ),
	'menu_slug'       => 'ytrip-settings',
	'framework_title' => esc_html__( 'YTrip Plugin Settings', 'ytrip' ),
	'menu_type'       => 'menu',
	'menu_icon'       => 'dashicons-airplane',
	'menu_position'   => 25,
	'show_bar_menu'   => true,
	'theme'           => 'light',
	'footer_text'     => esc_html__( 'Thank you for using YTrip Travel Booking Plugin', 'ytrip' ),
) );

// ============================================================
// SECTION: Brand Colors
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Brand Colors', 'ytrip' ),
	'icon'   => 'fa fa-palette',
	'fields' => array(
		array(
			'id'     => 'brand_colors',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Brand Colors', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'primary',
					'type'    => 'color',
					'title'   => esc_html__( 'Primary', 'ytrip' ),
					'default' => '#0f4c81',
				),
				array(
					'id'      => 'secondary',
					'type'    => 'color',
					'title'   => esc_html__( 'Secondary', 'ytrip' ),
					'default' => '#ff6b6b',
				),
				array(
					'id'      => 'accent',
					'type'    => 'color',
					'title'   => esc_html__( 'Accent', 'ytrip' ),
					'default' => '#f9a825',
				),
			),
		),
		array(
			'id'     => 'base_colors',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Base Colors', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'background',
					'type'    => 'color',
					'title'   => esc_html__( 'Background', 'ytrip' ),
					'default' => '#f8fafc',
				),
				array(
					'id'      => 'surface',
					'type'    => 'color',
					'title'   => esc_html__( 'Surface', 'ytrip' ),
					'default' => '#ffffff',
				),
				array(
					'id'      => 'text',
					'type'    => 'color',
					'title'   => esc_html__( 'Text', 'ytrip' ),
					'default' => '#1e293b',
				),
				array(
					'id'      => 'heading',
					'type'    => 'color',
					'title'   => esc_html__( 'Heading', 'ytrip' ),
					'default' => '#0f172a',
				),
			),
		),
	),
) );

// ============================================================
// SECTION: Typography
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Typography', 'ytrip' ),
	'icon'   => 'fa fa-font',
	'fields' => array(
		array(
			'id'           => 'body_typography',
			'type'         => 'typography',
			'title'        => esc_html__( 'Body Font', 'ytrip' ),
			'font_family'  => true,
			'font_weight'  => true,
			'font_size'    => true,
			'line_height'  => true,
			'google_fonts' => true,
			'default'      => array(
				'font-family' => 'Inter',
				'font-weight' => '400',
				'font-size'   => '16',
				'line-height' => '1.6',
				'unit'        => 'px',
			),
		),
		array(
			'id'           => 'heading_typography',
			'type'         => 'typography',
			'title'        => esc_html__( 'Heading Font', 'ytrip' ),
			'google_fonts' => true,
			'default'      => array(
				'font-family' => 'Poppins',
				'font-weight' => '600',
			),
		),
		array(
			'id'     => 'font_sizes',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Font Sizes', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'h1',
					'type'    => 'number',
					'title'   => 'H1',
					'default' => 48,
					'unit'    => 'px',
				),
				array(
					'id'      => 'h2',
					'type'    => 'number',
					'title'   => 'H2',
					'default' => 36,
					'unit'    => 'px',
				),
				array(
					'id'      => 'h3',
					'type'    => 'number',
					'title'   => 'H3',
					'default' => 28,
					'unit'    => 'px',
				),
				array(
					'id'      => 'h4',
					'type'    => 'number',
					'title'   => 'H4',
					'default' => 24,
					'unit'    => 'px',
				),
			),
		),
	),
) );

// ============================================================
// SECTION: Layout & Spacing
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Layout & Spacing', 'ytrip' ),
	'icon'   => 'fa fa-arrows-alt',
	'fields' => array(
		array(
			'id'      => 'section_spacing',
			'type'    => 'slider',
			'title'   => esc_html__( 'Section Spacing', 'ytrip' ),
			'min'     => 40,
			'max'     => 120,
			'step'    => 8,
			'unit'    => 'px',
			'default' => 80,
		),
		array(
			'id'      => 'border_radius',
			'type'    => 'slider',
			'title'   => esc_html__( 'Border Radius', 'ytrip' ),
			'min'     => 0,
			'max'     => 24,
			'step'    => 2,
			'unit'    => 'px',
			'default' => 12,
		),
		array(
			'id'      => 'container_width',
			'type'    => 'slider',
			'title'   => esc_html__( 'Container Width', 'ytrip' ),
			'min'     => 1000,
			'max'     => 1600,
			'step'    => 20,
			'unit'    => 'px',
			'default' => 1200,
		),
		array(
			'id'      => 'card_gap',
			'type'    => 'slider',
			'title'   => esc_html__( 'Card Gap', 'ytrip' ),
			'min'     => 16,
			'max'     => 48,
			'step'    => 4,
			'unit'    => 'px',
			'default' => 24,
		),
	),
) );

// ============================================================
// SECTION: Tour Cards
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Tour Cards', 'ytrip' ),
	'icon'   => 'fa fa-clone',
	'fields' => array(
		array(
			'id'      => 'tour_card_style',
			'type'    => 'select',
			'title'   => esc_html__( 'Card Template', 'ytrip' ),
			'desc'    => esc_html__( 'Choose which card design to use for tour listings (archive and homepage). Applies site-wide with no code edits.', 'ytrip' ),
			'options' => array(
				'style_1'  => esc_html__( 'Overlay Gradient', 'ytrip' ),
				'style_2'  => esc_html__( 'Classic White', 'ytrip' ),
				'style_3'  => esc_html__( 'Modern Shadow', 'ytrip' ),
				'style_4'  => esc_html__( 'Minimal Border', 'ytrip' ),
				'style_5'  => esc_html__( 'Glassmorphism', 'ytrip' ),
				'style_6'  => esc_html__( 'Hover Zoom', 'ytrip' ),
				'style_7'  => esc_html__( 'Split Content', 'ytrip' ),
				'style_8'  => esc_html__( 'Badge Corner', 'ytrip' ),
				'style_9'  => esc_html__( 'Horizontal', 'ytrip' ),
				'style_10' => esc_html__( 'Compact Grid', 'ytrip' ),
			),
			'default' => 'style_3',
		),
		array(
			'id'      => 'card_style',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Card Style (legacy)', 'ytrip' ),
			'desc'    => esc_html__( 'Used by some shortcodes. For archive/homepage card design use Card Template above.', 'ytrip' ),
			'options' => array(
				'standard' => esc_html__( 'Standard', 'ytrip' ),
				'modern'   => esc_html__( 'Modern', 'ytrip' ),
				'minimal'  => esc_html__( 'Minimal', 'ytrip' ),
				'overlay'  => esc_html__( 'Overlay', 'ytrip' ),
			),
			'default' => 'modern',
		),
		array(
			'id'      => 'card_image_ratio',
			'type'    => 'select',
			'title'   => esc_html__( 'Image Ratio', 'ytrip' ),
			'options' => array(
				'4:3'  => '4:3',
				'16:9' => '16:9',
				'3:2'  => '3:2',
				'1:1'  => '1:1 (Square)',
			),
			'default' => '4:3',
		),
		array(
			'id'      => 'card_shadow',
			'type'    => 'select',
			'title'   => esc_html__( 'Card Shadow', 'ytrip' ),
			'options' => array(
				'none'   => esc_html__( 'None', 'ytrip' ),
				'small'  => esc_html__( 'Small', 'ytrip' ),
				'medium' => esc_html__( 'Medium', 'ytrip' ),
				'large'  => esc_html__( 'Large', 'ytrip' ),
			),
			'default' => 'medium',
		),
		array(
			'id'      => 'card_hover_effect',
			'type'    => 'select',
			'title'   => esc_html__( 'Hover Effect', 'ytrip' ),
			'options' => array(
				'none'      => esc_html__( 'None', 'ytrip' ),
				'lift'      => esc_html__( 'Lift', 'ytrip' ),
				'zoom'      => esc_html__( 'Image Zoom', 'ytrip' ),
				'both'      => esc_html__( 'Lift + Zoom', 'ytrip' ),
			),
			'default' => 'both',
		),
		array(
			'id'      => 'card_show_rating',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Rating', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'card_show_price',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Price', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'card_show_duration',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Duration', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'card_show_location',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Location', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'card_show_wishlist',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Wishlist Button', 'ytrip' ),
			'default' => true,
		),
	),
) );

// ============================================================
// SECTION: Archive Page
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Archive Page', 'ytrip' ),
	'icon'   => 'fa fa-th',
	'fields' => array(
		array(
			'id'      => 'archive_layout',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Layout', 'ytrip' ),
			'options' => array(
				'grid' => esc_html__( 'Grid', 'ytrip' ),
				'list' => esc_html__( 'List', 'ytrip' ),
			),
			'default' => 'grid',
		),
		array(
			'id'      => 'archive_columns',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Columns', 'ytrip' ),
			'options' => array(
				'2' => '2',
				'3' => '3',
				'4' => '4',
			),
			'default' => '3',
		),
		array(
			'id'      => 'archive_per_page',
			'type'    => 'number',
			'title'   => esc_html__( 'Tours Per Page', 'ytrip' ),
			'default' => 12,
			'min'     => 4,
			'max'     => 48,
		),
		array(
			'id'      => 'archive_sidebar',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Sidebar', 'ytrip' ),
			'options' => array(
				'none'  => esc_html__( 'None', 'ytrip' ),
				'left'  => esc_html__( 'Left', 'ytrip' ),
				'right' => esc_html__( 'Right', 'ytrip' ),
			),
			'default' => 'left',
		),
		array(
			'id'      => 'archive_show_filters',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Filters', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'archive_show_sorting',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Sorting', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'archive_pagination_style',
			'type'    => 'select',
			'title'   => esc_html__( 'Pagination Style', 'ytrip' ),
			'desc'    => esc_html__( 'Choose how visitors navigate between pages of tour results. Numbered: classic page numbers. Load More: button appends results. Infinite Scroll: loads automatically as user scrolls.', 'ytrip' ),
			'options' => array(
				'numbered' => esc_html__( 'Numbered Pages', 'ytrip' ),
				'loadmore' => esc_html__( 'Click to Load More', 'ytrip' ),
				'infinite' => esc_html__( 'Infinite Scroll', 'ytrip' ),
			),
			'default' => 'numbered',
		),
		array(
			'id'      => 'archive_filter_style',
			'type'    => 'select',
			'title'   => esc_html__( 'Filter Bar & Sidebar Style', 'ytrip' ),
			'desc'    => esc_html__( 'Applies to the archive filter sidebar and toolbar appearance.', 'ytrip' ),
			'options' => array(
				'modern'  => esc_html__( 'Modern', 'ytrip' ),
				'classic' => esc_html__( 'Classic', 'ytrip' ),
				'minimal' => esc_html__( 'Minimal', 'ytrip' ),
			),
			'default' => 'modern',
		),
		array(
			'id'      => 'archive_template',
			'type'    => 'image_select',
			'title'   => esc_html__( 'Archive UI Template', 'ytrip' ),
			'desc'    => esc_html__( 'Choose the archive page layout and header style. Default: large header with optional background. Minimal: compact title bar. Fullwidth: no sidebar, content full width.', 'ytrip' ),
			'options' => array(
				'default'   => YTRIP_URL . 'assets/images/admin/layouts/archive-default.svg',
				'minimal'   => YTRIP_URL . 'assets/images/admin/layouts/archive-minimal.svg',
				'fullwidth' => YTRIP_URL . 'assets/images/admin/layouts/archive-fullwidth.svg',
			),
			'default' => 'default',
		),
		array(
			'id'      => 'transparent_header',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Transparent Header', 'ytrip' ),
			'desc'    => esc_html__( 'On tour archive and single tour pages, makes the theme header transparent so the page banner shows through, and adds top padding so content is not hidden under the header. If your theme header does not become transparent, add custom CSS in Developer settings targeting your theme header selector under body.ytrip-transparent-header.', 'ytrip' ),
			'default' => false,
		),
	),
) );

// ============================================================
// SECTION: Single Tour
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Single Tour', 'ytrip' ),
	'icon'   => 'fa fa-file',
	'fields' => array(
		array(
			'id'      => 'single_tour_layout',
			'type'    => 'image_select',
			'title'   => esc_html__( 'Single Tour Design', 'ytrip' ),
			'desc'    => esc_html__( 'Choose which layout template to use for single tour pages. Default = base theme template. Layout 1–5 = built-in premium designs. Hero slider is automatic when 2+ images are set.', 'ytrip' ),
			'options' => array(
				'default_page' => YTRIP_URL . 'assets/images/admin/layouts/single-default.png',
				'layout_1'     => YTRIP_URL . 'assets/images/admin/layouts/single-layout-1.png',
				'layout_2'     => YTRIP_URL . 'assets/images/admin/layouts/single-layout-2.png',
				'layout_3'     => YTRIP_URL . 'assets/images/admin/layouts/single-layout-3.png',
				'layout_4'     => YTRIP_URL . 'assets/images/admin/layouts/single-layout-4.png',
				'layout_5'     => YTRIP_URL . 'assets/images/admin/layouts/single-layout-5.png',
			),
			'default' => 'layout_1',
		),
		array(
			'id'      => 'single_gallery_style',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Gallery Style', 'ytrip' ),
			'options' => array(
				'slider'  => esc_html__( 'Slider', 'ytrip' ),
				'grid'    => esc_html__( 'Grid', 'ytrip' ),
				'masonry' => esc_html__( 'Masonry', 'ytrip' ),
			),
			'default' => 'slider',
		),
		array(
			'id'      => 'single_hero_gallery_mode',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Hero with Gallery', 'ytrip' ),
			'desc'    => esc_html__( 'When a tour has a gallery and Hero Display is set to Slider/Carousel, show hero as:', 'ytrip' ),
			'options' => array(
				'slider'   => esc_html__( 'Slider', 'ytrip' ),
				'carousel' => esc_html__( 'Carousel', 'ytrip' ),
			),
			'default' => 'slider',
		),
		array(
			'id'      => 'single_booking_position',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Booking Form Position', 'ytrip' ),
			'options' => array(
				'sidebar' => esc_html__( 'Sidebar', 'ytrip' ),
				'bottom'  => esc_html__( 'Below Content', 'ytrip' ),
			),
			'default' => 'sidebar',
		),
		array(
			'id'      => 'single_show_map',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Map', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'single_show_reviews',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Reviews', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'single_show_related',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Related Tours', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'single_related_count',
			'type'    => 'number',
			'title'   => esc_html__( 'Related Tours Count', 'ytrip' ),
			'default' => 4,
			'min'     => 2,
			'max'     => 8,
		),
		array(
			'id'      => 'single_tabs_show_icons',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Icons in Content Tabs', 'ytrip' ),
			'desc'    => esc_html__( 'Display an icon next to each tab label (Overview, Itinerary, Included, etc.) for a clearer, modern look.', 'ytrip' ),
			'default' => false,
		),
	),
) );

// ============================================================
// SECTION: Booking
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Booking', 'ytrip' ),
	'icon'   => 'fa fa-calendar-check',
	'fields' => array(
		array(
			'id'      => 'booking_instant',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Instant Booking', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'booking_calendar',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Show Calendar', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'booking_min_days',
			'type'    => 'number',
			'title'   => esc_html__( 'Min Days in Advance', 'ytrip' ),
			'default' => 1,
			'min'     => 0,
			'max'     => 30,
		),
		array(
			'id'      => 'booking_max_guests',
			'type'    => 'number',
			'title'   => esc_html__( 'Max Guests', 'ytrip' ),
			'default' => 20,
			'min'     => 1,
			'max'     => 100,
		),
		array(
			'id'      => 'booking_require_deposit',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Require Deposit', 'ytrip' ),
			'default' => false,
		),
		array(
			'id'         => 'booking_deposit_percent',
			'type'       => 'slider',
			'title'      => esc_html__( 'Deposit Percentage', 'ytrip' ),
			'min'        => 10,
			'max'        => 100,
			'step'       => 5,
			'unit'       => '%',
			'default'    => 30,
			'dependency' => array( 'booking_require_deposit', '==', 'true' ),
		),
	),
) );

// ============================================================
// SECTION: SEO & Schema
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'SEO & Schema', 'ytrip' ),
	'icon'   => 'fa fa-search',
	'fields' => array(
		array(
			'id'      => 'schema_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Schema', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'schema_type',
			'type'       => 'select',
			'title'      => esc_html__( 'Schema Type', 'ytrip' ),
			'dependency' => array( 'schema_enable', '==', 'true' ),
			'options'    => array(
				'TravelAction' => 'TravelAction',
				'TouristTrip'  => 'TouristTrip',
				'Event'        => 'Event',
				'Product'      => 'Product',
			),
			'default'    => 'TravelAction',
		),
		array(
			'id'         => 'schema_conflict_mode',
			'type'       => 'switcher',
			'title'      => esc_html__( 'Schema Conflict Mode', 'ytrip' ),
			'dependency' => array( 'schema_enable', '==', 'true' ),
			'default'    => true,
		),
		array(
			'id'      => 'enable_breadcrumbs',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Breadcrumbs', 'ytrip' ),
			'default' => true,
		),
	),
) );

// ============================================================
// SECTION: Performance
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Performance', 'ytrip' ),
	'icon'   => 'fa fa-tachometer-alt',
	'fields' => array(
		array(
			'id'      => 'enable_lazy_load',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Lazy Load Images', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'enable_db_maintenance',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Database Maintenance', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'      => 'enable_minification',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Minification', 'ytrip' ),
			'default' => false,
		),
		array(
			'id'      => 'enable_cache',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Caching', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'cache_duration',
			'type'       => 'number',
			'title'      => esc_html__( 'Cache Duration (hours)', 'ytrip' ),
			'default'    => 24,
			'min'        => 1,
			'max'        => 168,
			'dependency' => array( 'enable_cache', '==', 'true' ),
		),
	),
) );

// ============================================================
// SECTION: General
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'General', 'ytrip' ),
	'icon'   => 'fa fa-cogs',
	'fields' => array(
		array(
			'id'      => 'currency',
			'type'    => 'select',
			'title'   => esc_html__( 'Currency', 'ytrip' ),
			'options' => array(
				'USD' => 'USD ($)',
				'EUR' => 'EUR (€)',
				'GBP' => 'GBP (£)',
				'AED' => 'AED',
				'SAR' => 'SAR',
				'EGP' => 'EGP',
			),
			'default' => 'EUR',
		),
		array(
			'id'      => 'date_format',
			'type'    => 'select',
			'title'   => esc_html__( 'Date Format', 'ytrip' ),
			'options' => array(
				'd/m/Y'  => 'DD/MM/YYYY',
				'm/d/Y'  => 'MM/DD/YYYY',
				'Y-m-d'  => 'YYYY-MM-DD',
				'F j, Y' => 'Month Day, Year',
			),
			'default' => 'd/m/Y',
		),
		array(
			'id'      => 'language',
			'type'    => 'select',
			'title'   => esc_html__( 'Default Language', 'ytrip' ),
			'options' => array(
				'en' => 'English',
				'ar' => 'Arabic',
				'de' => 'German',
				'fr' => 'French',
				'es' => 'Spanish',
			),
			'default' => 'en',
		),
		array(
			'id'      => 'default_image',
			'type'    => 'media',
			'title'   => esc_html__( 'Default Tour Image', 'ytrip' ),
			'desc'    => esc_html__( 'Recommended dimensions: 1920x1080px (16:9 ratio) for optimal hero display.', 'ytrip' ),
		),
		array(
			'id'     => 'url_slugs',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'URL Slugs', 'ytrip' ),
			'desc'   => esc_html__( 'Customize the URL slugs for tours, destinations and categories. After saving, the plugin automatically flushes rewrite rules so new URLs work immediately.', 'ytrip' ),
			'fields' => array(
				array(
					'id'          => 'slug_tour',
					'type'        => 'text',
					'title'       => esc_html__( 'Tour URL Slug', 'ytrip' ),
					'default'     => 'tours',
					'placeholder' => 'tours',
					'desc'        => esc_html__( 'e.g. tours → yoursite.com/tours/', 'ytrip' ),
				),
				array(
					'id'          => 'slug_destination',
					'type'        => 'text',
					'title'       => esc_html__( 'Destination URL Slug', 'ytrip' ),
					'default'     => 'destination',
					'placeholder' => 'destination',
					'desc'        => esc_html__( 'e.g. destination → yoursite.com/destination/europe/', 'ytrip' ),
				),
				array(
					'id'          => 'slug_category',
					'type'        => 'text',
					'title'       => esc_html__( 'Category URL Slug', 'ytrip' ),
					'default'     => 'tour-category',
					'placeholder' => 'tour-category',
					'desc'        => esc_html__( 'e.g. tour-category → yoursite.com/tour-category/safari/', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// SECTION: Developer
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Developer', 'ytrip' ),
	'icon'   => 'fa fa-code',
	'fields' => array(
		array(
			'id'      => 'debug_mode',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Debug Mode', 'ytrip' ),
			'default' => false,
		),
		array(
			'id'         => 'debug_log_level',
			'type'       => 'select',
			'title'      => esc_html__( 'Log Level', 'ytrip' ),
			'dependency' => array( 'debug_mode', '==', 'true' ),
			'options'    => array(
				'error'   => 'Error',
				'warning' => 'Warning',
				'info'    => 'Info',
				'debug'   => 'Debug',
			),
			'default'    => 'error',
		),
		array(
			'id'         => 'log_to_file',
			'type'       => 'switcher',
			'title'      => esc_html__( 'Log to File', 'ytrip' ),
			'dependency' => array( 'debug_mode', '==', 'true' ),
			'default'    => false,
		),
		array(
			'id'    => 'custom_css_tabbed',
			'type'  => 'tabbed',
			'title' => esc_html__( 'Custom CSS', 'ytrip' ),
			'tabs'  => array(
				array(
					'title'  => esc_html__( 'General', 'ytrip' ),
					'fields' => array(
						array(
							'id'       => 'custom_css',
							'type'     => 'code_editor',
							'title'    => esc_html__( 'Custom CSS — General', 'ytrip' ),
							'desc'     => esc_html__( 'CSS applied on all screen sizes.', 'ytrip' ),
							'settings' => array(
								'mode'  => 'css',
								'theme' => 'monokai',
							),
						),
					),
				),
				array(
					'title'  => esc_html__( 'Tablet', 'ytrip' ),
					'fields' => array(
						array(
							'id'       => 'custom_css_tablet',
							'type'     => 'code_editor',
							'title'    => esc_html__( 'Custom CSS — Tablet (≤ 1024px)', 'ytrip' ),
							'desc'     => esc_html__( 'Wrapped automatically in @media (max-width: 1024px). You can also write your own breakpoints.', 'ytrip' ),
							'settings' => array(
								'mode'  => 'css',
								'theme' => 'monokai',
							),
						),
					),
				),
				array(
					'title'  => esc_html__( 'Mobile', 'ytrip' ),
					'fields' => array(
						array(
							'id'       => 'custom_css_mobile',
							'type'     => 'code_editor',
							'title'    => esc_html__( 'Custom CSS — Mobile (≤ 768px)', 'ytrip' ),
							'desc'     => esc_html__( 'Wrapped automatically in @media (max-width: 768px). You can also write your own breakpoints.', 'ytrip' ),
							'settings' => array(
								'mode'  => 'css',
								'theme' => 'monokai',
							),
						),
					),
				),
			),
		),
		array(
			'id'      => 'custom_js',
			'type'    => 'code_editor',
			'title'   => esc_html__( 'Custom JavaScript', 'ytrip' ),
			'desc'    => esc_html__( 'Added in the footer via wp_footer. Wrap in DOMContentLoaded or document.ready as needed.', 'ytrip' ),
			'settings' => array(
				'mode'  => 'javascript',
				'theme' => 'monokai',
			),
		),
	),
) );

// ============================================================
// SECTION: Maps, Booking & Performance (unified Settings API form)
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Maps, Booking & Performance', 'ytrip' ),
	'icon'   => 'fa fa-map',
	'fields' => array(
		array(
			'type'    => 'content',
			'content' => class_exists( 'YTrip_Settings' ) ? YTrip_Settings::instance()->get_embedded_settings_form_html() : '',
		),
	),
) );

// ============================================================
// SECTION: Import / Export
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Import / Export', 'ytrip' ),
	'icon'   => 'fa fa-download',
	'fields' => array(
		array(
			'id'    => 'backup',
			'type'  => 'backup',
			'title' => esc_html__( 'Backup & Restore', 'ytrip' ),
		),
	),
) );
