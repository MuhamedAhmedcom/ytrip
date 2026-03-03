<?php
/**
 * YTrip Homepage Builder Configuration
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

// Create Homepage Builder Options Panel
CSF::createOptions( 'ytrip_homepage', array(
	'menu_title'      => esc_html__( 'Homepage Builder', 'ytrip' ),
	'menu_slug'       => 'ytrip-homepage',
	'menu_icon'       => 'dashicons-admin-home',
	'menu_position'   => 3,
	'framework_title' => esc_html__( 'YTrip Homepage Builder', 'ytrip' ),
	'theme'           => 'dark',
	'footer_text'     => esc_html__( 'Thank you for using YTrip', 'ytrip' ),
) );

// Section: Homepage Sections Manager
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Sections Manager', 'ytrip' ),
	'icon'   => 'fa fa-th-large',
	'fields' => array(
		array(
			'id'      => 'homepage_sections',
			'type'    => 'sorter',
			'title'   => esc_html__( 'Drag to Reorder Sections', 'ytrip' ),
			'desc'    => esc_html__( 'Drag sections between Enabled and Disabled to control which sections appear on your homepage.', 'ytrip' ),
			'default' => array(
				'enabled'  => array(
					'hero_slider'    => esc_html__( 'Hero Slider', 'ytrip' ),
					'search_form'    => esc_html__( 'Search & Filter', 'ytrip' ),
					'featured_tours' => esc_html__( 'Featured Tours', 'ytrip' ),
					'destinations'   => esc_html__( 'Popular Destinations', 'ytrip' ),
					'categories'     => esc_html__( 'Tour Categories', 'ytrip' ),
					'testimonials'   => esc_html__( 'Customer Reviews', 'ytrip' ),
					'stats'          => esc_html__( 'Statistics Counter', 'ytrip' ),
					'blog'           => esc_html__( 'Latest Blog Posts', 'ytrip' ),
				),
				'disabled' => array(
					'video_banner'   => esc_html__( 'Video Banner', 'ytrip' ),
					'promo_banner'   => esc_html__( 'Promotional Banner', 'ytrip' ),
					'partners'       => esc_html__( 'Partners/Sponsors', 'ytrip' ),
					'instagram_feed' => esc_html__( 'Instagram Feed', 'ytrip' ),
				),
			),
		),
	),
) );

// Section: General Settings
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'General Settings', 'ytrip' ),
	'icon'   => 'fa fa-cogs',
	'fields' => array(
		array(
			'id'      => 'homepage_design',
			'type'    => 'select',
			'title'   => esc_html__( 'Homepage Design', 'ytrip' ),
			'desc'    => esc_html__( 'Switch between design presets. Travel Concept uses a light, modern style inspired by travel UI trends.', 'ytrip' ),
			'options' => array(
				'default'        => esc_html__( 'Default', 'ytrip' ),
				'travel_concept'  => esc_html__( 'Travel Concept', 'ytrip' ),
			),
			'default' => 'default',
		),
		array(
			'id'      => 'homepage_layout',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Homepage Layout', 'ytrip' ),
			'desc'    => esc_html__( 'Choose the overall homepage layout structure.', 'ytrip' ),
			'options' => array(
				'modern'  => esc_html__( 'Modern', 'ytrip' ),
				'classic' => esc_html__( 'Classic', 'ytrip' ),
				'search'  => esc_html__( 'Search Focused', 'ytrip' ),
			),
			'default' => 'modern',
		),
		array(
			'id'      => 'homepage_width',
			'type'    => 'select',
			'title'   => esc_html__( 'Content Width', 'ytrip' ),
			'desc'    => esc_html__( 'Select the content width for homepage sections.', 'ytrip' ),
			'options' => array(
				'boxed' => esc_html__( 'Boxed (1200px)', 'ytrip' ),
				'wide'  => esc_html__( 'Wide (1400px)', 'ytrip' ),
				'full'  => esc_html__( 'Full Width', 'ytrip' ),
			),
			'default' => 'wide',
		),
		array(
			'id'      => 'replace_content',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Replace Theme Content', 'ytrip' ),
			'desc'    => esc_html__( 'Enable this to HIDE the default page content (e.g. from your theme) and show ONLY the YTrip sections. Useful if you see duplicate content.', 'ytrip' ),
			'default' => false,
		),
	),
) );

// Section: Hero Slider
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Hero Slider', 'ytrip' ),
	'icon'   => 'fa fa-images',
	'fields' => array(
		array(
			'id'      => 'hero_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Hero Slider', 'ytrip' ),
			'desc'    => esc_html__( 'Enable or disable the main hero slider section.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'hero_overlay_color',
			'type'       => 'color',
			'title'      => esc_html__( 'Overlay Color', 'ytrip' ),
			'desc'       => esc_html__( 'Color applied over the hero slide images to improve text readability.', 'ytrip' ),
			'dependency' => array( 'hero_enable', '==', 'true' ),
			'default'    => '#000000',
		),
		array(
			'id'         => 'hero_overlay_opacity',
			'type'       => 'slider',
			'title'      => esc_html__( 'Overlay Opacity', 'ytrip' ),
			'desc'       => esc_html__( 'Opacity of the overlay (0 = transparent, 100 = solid).', 'ytrip' ),
			'min'        => 0,
			'max'        => 100,
			'step'       => 5,
			'unit'       => '%',
			'dependency' => array( 'hero_enable', '==', 'true' ),
			'default'    => 50,
		),
		array(
			'id'         => 'hero_slides',
			'type'       => 'group',
			'title'      => esc_html__( 'Slides', 'ytrip' ),
			'desc'       => esc_html__( 'Add slides with background images and content.', 'ytrip' ),
			'dependency' => array( 'hero_enable', '==', 'true' ),
			'fields'     => array(
				array(
					'id'    => 'slide_image',
					'type'  => 'media',
					'title' => esc_html__( 'Background Image', 'ytrip' ),
					'desc'  => esc_html__( 'Recommended size: 1920x800 pixels.', 'ytrip' ),
				),
				array(
					'id'    => 'slide_title',
					'type'  => 'text',
					'title' => esc_html__( 'Title', 'ytrip' ),
				),
				array(
					'id'    => 'slide_subtitle',
					'type'  => 'textarea',
					'title' => esc_html__( 'Subtitle', 'ytrip' ),
				),
				array(
					'id'     => 'button_1',
					'type'   => 'fieldset',
					'title'  => esc_html__( 'Primary Button', 'ytrip' ),
					'fields' => array(
						array(
							'id'    => 'text',
							'type'  => 'text',
							'title' => esc_html__( 'Button Text', 'ytrip' ),
						),
						array(
							'id'    => 'link',
							'type'  => 'link',
							'title' => esc_html__( 'Button Link', 'ytrip' ),
						),
						array(
							'id'      => 'style',
							'type'    => 'button_set',
							'title'   => esc_html__( 'Button Style', 'ytrip' ),
							'options' => array(
								'primary'   => esc_html__( 'Primary', 'ytrip' ),
								'secondary' => esc_html__( 'Secondary', 'ytrip' ),
								'outline'   => esc_html__( 'Outline', 'ytrip' ),
							),
							'default' => 'primary',
						),
					),
				),
			),
		),
	),
) );

// Section: Search Form
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Search Form', 'ytrip' ),
	'icon'   => 'fa fa-search',
	'fields' => array(
		array(
			'id'      => 'search_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Search Form', 'ytrip' ),
			'desc'    => esc_html__( 'Show the tour search form on homepage.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'search_style',
			'type'       => 'button_set',
			'title'      => esc_html__( 'Search Form Style', 'ytrip' ),
			'desc'       => esc_html__( 'Select the search form design. Pill: horizontal white bar with icons and integrated Search button (booking-style).', 'ytrip' ),
			'dependency' => array( 'search_enable', '==', 'true' ),
			'options'    => array(
				'style_1' => esc_html__( 'Style 1', 'ytrip' ),
				'style_2' => esc_html__( 'Style 2', 'ytrip' ),
				'style_3' => esc_html__( 'Style 3', 'ytrip' ),
				'style_4' => esc_html__( 'Ultra Modern', 'ytrip' ),
				'pill'    => esc_html__( 'Pill / Booking', 'ytrip' ),
			),
			'default'    => 'style_1',
		),
		array(
			'id'         => 'search_form_fields',
			'type'       => 'checkbox',
			'title'      => esc_html__( 'Form Fields', 'ytrip' ),
			'desc'       => esc_html__( 'Choose which fields to show in the search form. Order is fixed: Location → Date range → Guests → Search button.', 'ytrip' ),
			'dependency' => array( 'search_enable', '==', 'true' ),
			'options'    => array(
				'destination' => esc_html__( 'Destination / Location', 'ytrip' ),
				'date_range'  => esc_html__( 'Date range (Check-in & Check out)', 'ytrip' ),
				'guests'      => esc_html__( 'Number of guests', 'ytrip' ),
			),
			'default'    => array( 'destination', 'date_range', 'guests' ),
		),
	),
) );

// Section: Featured Tours
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Featured Tours', 'ytrip' ),
	'icon'   => 'fa fa-star',
	'fields' => array(
		array(
			'id'      => 'featured_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Featured Tours', 'ytrip' ),
			'desc'    => esc_html__( 'Show featured tours section on homepage.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'featured_section_title',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Title', 'ytrip' ),
			'default'    => 'Featured Tours',
			'dependency' => array( 'featured_enable', '==', 'true' ),
		),
		array(
			'id'         => 'featured_section_subtitle',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Subtitle', 'ytrip' ),
			'default'    => 'Discover our most popular travel experiences',
			'dependency' => array( 'featured_enable', '==', 'true' ),
		),
		array(
			'id'         => 'featured_selection',
			'type'       => 'button_set',
			'title'      => esc_html__( 'Tours Selection', 'ytrip' ),
			'desc'       => esc_html__( 'Choose how to select tours to display.', 'ytrip' ),
			'dependency' => array( 'featured_enable', '==', 'true' ),
			'options'    => array(
				'auto'   => esc_html__( 'Automatic (Latest)', 'ytrip' ),
				'manual' => esc_html__( 'Manual Selection', 'ytrip' ),
			),
			'default'    => 'auto',
		),
		array(
			'id'         => 'featured_tours',
			'type'       => 'select',
			'title'      => esc_html__( 'Select Tours', 'ytrip' ),
			'multiple'   => true,
			'chosen'     => true,
			'ajax'       => true,
			'options'    => 'posts',
			'query_args' => array(
				'post_type' => 'ytrip_tour',
			),
			'dependency' => array( 'featured_selection', '==', 'manual' ),
		),
		array(
			'id'         => 'featured_count',
			'type'       => 'number',
			'title'      => esc_html__( 'Number of Tours', 'ytrip' ),
			'default'    => 6,
			'min'        => 1,
			'max'        => 24,
			'dependency' => array( 'featured_selection', '==', 'auto' ),
		),
	),
) );

// Section: Destinations
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Destinations', 'ytrip' ),
	'icon'   => 'fa fa-map',
	'fields' => array(
		array(
			'id'      => 'destinations_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Destinations', 'ytrip' ),
			'desc'    => esc_html__( 'Show popular destinations section.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'destinations_title',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Title', 'ytrip' ),
			'default'    => 'Popular Destinations',
			'dependency' => array( 'destinations_enable', '==', 'true' ),
		),
		array(
			'id'         => 'destinations_subtitle',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Subtitle', 'ytrip' ),
			'default'    => '',
			'desc'       => esc_html__( 'Optional. Leave blank to use default.', 'ytrip' ),
			'dependency' => array( 'destinations_enable', '==', 'true' ),
		),
		array(
			'id'         => 'destinations_count',
			'type'       => 'number',
			'title'      => esc_html__( 'Number of Destinations', 'ytrip' ),
			'default'    => 5,
			'min'        => 1,
			'max'        => 12,
			'dependency' => array( 'destinations_enable', '==', 'true' ),
		),
		array(
			'id'         => 'destinations_layout',
			'type'       => 'select',
			'title'      => esc_html__( 'Destinations Layout', 'ytrip' ),
			'desc'       => esc_html__( 'Display as current grid styles or as a carousel with circular cards and arrows.', 'ytrip' ),
			'options'    => array(
				'default'  => esc_html__( 'As now (grid styles)', 'ytrip' ),
				'carousel' => esc_html__( 'Carousel (circular cards with arrows)', 'ytrip' ),
			),
			'default'    => 'default',
			'dependency' => array( 'destinations_enable', '==', 'true' ),
		),
		array(
			'id'         => 'destinations_carousel_autoplay',
			'type'       => 'switcher',
			'title'      => esc_html__( 'Carousel autoplay', 'ytrip' ),
			'desc'       => esc_html__( 'Enable auto-advance (loop) of the destinations carousel.', 'ytrip' ),
			'default'    => true,
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'carousel' ),
			),
		),
		array(
			'id'         => 'destinations_carousel_delay',
			'type'       => 'number',
			'title'      => esc_html__( 'Carousel scroll interval (seconds)', 'ytrip' ),
			'desc'       => esc_html__( 'Time between each auto-advance when autoplay is on.', 'ytrip' ),
			'default'    => 5,
			'min'        => 2,
			'max'        => 60,
			'unit'       => 's',
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'carousel' ),
			),
		),
		array(
			'id'         => 'destinations_carousel_items_desktop',
			'type'       => 'number',
			'title'      => esc_html__( 'Items visible (desktop)', 'ytrip' ),
			'desc'       => esc_html__( 'Number of destination items visible at once on desktop (e.g. 1024px and up).', 'ytrip' ),
			'default'    => 6,
			'min'        => 1,
			'max'        => 12,
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'carousel' ),
			),
		),
		array(
			'id'         => 'destinations_carousel_items_tablet',
			'type'       => 'number',
			'title'      => esc_html__( 'Items visible (tablet)', 'ytrip' ),
			'desc'       => esc_html__( 'Number of items visible on tablet (768px – 1023px).', 'ytrip' ),
			'default'    => 4,
			'min'        => 1,
			'max'        => 8,
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'carousel' ),
			),
		),
		array(
			'id'         => 'destinations_carousel_items_mobile',
			'type'       => 'number',
			'title'      => esc_html__( 'Items visible (mobile)', 'ytrip' ),
			'desc'       => esc_html__( 'Number of items visible on mobile (under 768px).', 'ytrip' ),
			'default'    => 2,
			'min'        => 1,
			'max'        => 4,
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'carousel' ),
			),
		),
		array(
			'id'         => 'destinations_style',
			'type'       => 'select',
			'title'      => esc_html__( 'Display Style', 'ytrip' ),
			'desc'       => esc_html__( 'Choose how destinations are displayed when layout is "As now (grid styles)".', 'ytrip' ),
			'options'    => array(
				'bento'   => esc_html__( 'Bento (asymmetric grid, one hero card)', 'ytrip' ),
				'grid'    => esc_html__( 'Uniform Grid (equal cards)', 'ytrip' ),
				'strip'   => esc_html__( 'Horizontal Strip (one large + small cards)', 'ytrip' ),
				'minimal' => esc_html__( 'Minimal (compact list with images)', 'ytrip' ),
			),
			'default'    => 'bento',
			'dependency' => array(
				array( 'destinations_enable', '==', 'true' ),
				array( 'destinations_layout', '==', 'default' ),
			),
		),
	),
) );

// Section: Tour Categories
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Tour Categories', 'ytrip' ),
	'icon'   => 'fa fa-folder',
	'fields' => array(
		array(
			'id'      => 'categories_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Categories Section', 'ytrip' ),
			'desc'    => esc_html__( 'Show tour categories on homepage.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'categories_title',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Title', 'ytrip' ),
			'default'    => 'Tour Categories',
			'dependency' => array( 'categories_enable', '==', 'true' ),
		),
		array(
			'id'         => 'categories_subtitle',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Subtitle', 'ytrip' ),
			'default'    => '',
			'desc'       => esc_html__( 'Optional. Leave blank to use default.', 'ytrip' ),
			'dependency' => array( 'categories_enable', '==', 'true' ),
		),
		array(
			'id'         => 'categories_count',
			'type'       => 'number',
			'title'      => esc_html__( 'Number of Categories', 'ytrip' ),
			'default'    => 6,
			'min'        => 1,
			'max'        => 12,
			'dependency' => array( 'categories_enable', '==', 'true' ),
		),
		array(
			'id'         => 'categories_style',
			'type'       => 'select',
			'title'      => esc_html__( 'Display Style', 'ytrip' ),
			'desc'       => esc_html__( 'Choose how categories are displayed on the homepage.', 'ytrip' ),
			'options'    => array(
				'grid'    => esc_html__( 'Grid (icon cards)', 'ytrip' ),
				'chips'   => esc_html__( 'Chips (horizontal scroll, compact)', 'ytrip' ),
				'featured'=> esc_html__( 'Featured (first category large with image)', 'ytrip' ),
				'minimal' => esc_html__( 'Minimal (compact list with colored icons)', 'ytrip' ),
			),
			'default'    => 'grid',
			'dependency' => array( 'categories_enable', '==', 'true' ),
		),
	),
) );

// Section: Testimonials
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Testimonials', 'ytrip' ),
	'icon'   => 'fa fa-comments',
	'fields' => array(
		array(
			'id'      => 'testimonials_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Testimonials', 'ytrip' ),
			'desc'    => esc_html__( 'Show customer testimonials section.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'testimonials_title',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Title', 'ytrip' ),
			'default'    => 'What Our Travelers Say',
			'dependency' => array( 'testimonials_enable', '==', 'true' ),
		),
		array(
			'id'         => 'testimonials',
			'type'       => 'group',
			'title'      => esc_html__( 'Testimonials', 'ytrip' ),
			'dependency' => array( 'testimonials_enable', '==', 'true' ),
			'fields'     => array(
				array(
					'id'    => 'name',
					'type'  => 'text',
					'title' => esc_html__( 'Customer Name', 'ytrip' ),
				),
				array(
					'id'    => 'role',
					'type'  => 'text',
					'title' => esc_html__( 'Customer Role', 'ytrip' ),
				),
				array(
					'id'    => 'content',
					'type'  => 'textarea',
					'title' => esc_html__( 'Testimonial Text', 'ytrip' ),
				),
				array(
					'id'      => 'rating',
					'type'    => 'slider',
					'title'   => esc_html__( 'Rating', 'ytrip' ),
					'min'     => 1,
					'max'     => 5,
					'default' => 5,
				),
				array(
					'id'    => 'image',
					'type'  => 'media',
					'title' => esc_html__( 'Customer Photo', 'ytrip' ),
				),
			),
		),
	),
) );

// Section: Statistics
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Statistics', 'ytrip' ),
	'icon'   => 'fa fa-chart-bar',
	'fields' => array(
		array(
			'id'      => 'stats_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Statistics', 'ytrip' ),
			'desc'    => esc_html__( 'Show statistics counter section.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'stats_items',
			'type'       => 'group',
			'title'      => esc_html__( 'Statistics Items', 'ytrip' ),
			'dependency' => array( 'stats_enable', '==', 'true' ),
			'fields'     => array(
				array(
					'id'    => 'number',
					'type'  => 'number',
					'title' => esc_html__( 'Number', 'ytrip' ),
				),
				array(
					'id'    => 'suffix',
					'type'  => 'text',
					'title' => esc_html__( 'Suffix', 'ytrip' ),
					'desc'  => esc_html__( 'e.g. +, K, M', 'ytrip' ),
				),
				array(
					'id'    => 'label',
					'type'  => 'text',
					'title' => esc_html__( 'Label', 'ytrip' ),
				),
				array(
					'id'      => 'icon',
					'type'    => 'select',
					'title'   => esc_html__( 'Icon', 'ytrip' ),
					'options' => array(
						'compass' => esc_html__( 'Compass (Tours)', 'ytrip' ),
						'map'     => esc_html__( 'Map (Destinations)', 'ytrip' ),
						'users'   => esc_html__( 'Users (Travelers)', 'ytrip' ),
						'award'   => esc_html__( 'Award (Experience)', 'ytrip' ),
					),
				),
			),
		),
	),
) );

// Section: Blog
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Blog Posts', 'ytrip' ),
	'icon'   => 'fa fa-newspaper',
	'fields' => array(
		array(
			'id'      => 'blog_enable',
			'type'    => 'switcher',
			'title'   => esc_html__( 'Enable Blog Section', 'ytrip' ),
			'desc'    => esc_html__( 'Show latest blog posts on homepage.', 'ytrip' ),
			'default' => true,
		),
		array(
			'id'         => 'blog_title',
			'type'       => 'text',
			'title'      => esc_html__( 'Section Title', 'ytrip' ),
			'default'    => 'Travel Tips & Stories',
			'dependency' => array( 'blog_enable', '==', 'true' ),
		),
		array(
			'id'         => 'blog_count',
			'type'       => 'number',
			'title'      => esc_html__( 'Number of Posts', 'ytrip' ),
			'default'    => 3,
			'min'        => 1,
			'max'        => 12,
			'dependency' => array( 'blog_enable', '==', 'true' ),
		),
	),
) );

// Section: Import/Export
CSF::createSection( 'ytrip_homepage', array(
	'title'  => esc_html__( 'Import / Export', 'ytrip' ),
	'icon'   => 'fa fa-download',
	'fields' => array(
		array(
			'id'    => 'backup',
			'type'  => 'backup',
			'title' => esc_html__( 'Backup & Restore', 'ytrip' ),
			'desc'  => esc_html__( 'Export your settings to a JSON file or import settings from a backup.', 'ytrip' ),
		),
	),
) );

// Show notice after save so changes are reflected when viewing the homepage.
add_action( 'csf_ytrip_homepage_saved', 'ytrip_homepage_builder_after_save', 10, 1 );
function ytrip_homepage_builder_after_save( $data ) {
	set_transient( 'ytrip_homepage_settings_saved', 1, 60 );
}

add_action( 'admin_notices', 'ytrip_homepage_builder_saved_notice' );
function ytrip_homepage_builder_saved_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || strpos( $screen->id, 'ytrip-homepage' ) === false ) {
		return;
	}
	if ( ! get_transient( 'ytrip_homepage_settings_saved' ) ) {
		return;
	}
	delete_transient( 'ytrip_homepage_settings_saved' );
	$url = home_url( '/' );
	$link = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'View homepage', 'ytrip' ) . '</a>';
	echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( sprintf( __( 'Homepage settings saved. %s to see changes instantly.', 'ytrip' ), $link ) ) . '</p></div>';
}
