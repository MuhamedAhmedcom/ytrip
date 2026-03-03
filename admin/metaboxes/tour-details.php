<?php
/**
 * YTrip Tour Details Metabox
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

$prefix = 'ytrip_tour_details';

CSF::createMetabox( $prefix, array(
	'title'     => esc_html__( 'Tour Details', 'ytrip' ),
	'post_type' => 'ytrip_tour',
	'context'   => 'normal',
	'priority'  => 'high',
	'data_type' => 'serialize',
) );

// ============================================================
// TAB 1: Basic Information
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Basic Info', 'ytrip' ),
	'icon'   => 'fa fa-info-circle',
	'fields' => array(
		array(
			'id'      => 'tour_code',
			'type'    => 'text',
			'title'   => esc_html__( 'Tour Code', 'ytrip' ),
			'desc'    => esc_html__( 'Unique identifier for this tour (e.g., TR-001).', 'ytrip' ),
		),
		array(
			'id'     => 'tour_duration',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Duration', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'days',
					'type'    => 'number',
					'title'   => esc_html__( 'Days', 'ytrip' ),
					'default' => 1,
				),
				array(
					'id'      => 'nights',
					'type'    => 'number',
					'title'   => esc_html__( 'Nights', 'ytrip' ),
					'default' => 0,
				),
				array(
					'id'      => 'hours',
					'type'    => 'number',
					'title'   => esc_html__( 'Hours', 'ytrip' ),
					'desc'    => esc_html__( 'For day tours less than 24 hours.', 'ytrip' ),
					'default' => 0,
				),
			),
		),
		array(
			'id'     => 'group_size',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Group Size', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'min',
					'type'    => 'number',
					'title'   => esc_html__( 'Minimum', 'ytrip' ),
					'default' => 1,
				),
				array(
					'id'      => 'max',
					'type'    => 'number',
					'title'   => esc_html__( 'Maximum', 'ytrip' ),
					'default' => 50,
				),
			),
		),
		array(
			'id'      => 'difficulty',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Difficulty Level', 'ytrip' ),
			'options' => array(
				'easy'      => esc_html__( 'Easy', 'ytrip' ),
				'moderate'  => esc_html__( 'Moderate', 'ytrip' ),
				'difficult' => esc_html__( 'Difficult', 'ytrip' ),
				'expert'    => esc_html__( 'Expert', 'ytrip' ),
			),
			'default' => 'moderate',
		),
		array(
			'id'      => 'tour_type',
			'type'    => 'select',
			'title'   => esc_html__( 'Tour Type', 'ytrip' ),
			'options' => array(
				'group'   => esc_html__( 'Group Tour', 'ytrip' ),
				'private' => esc_html__( 'Private Tour', 'ytrip' ),
				'self'    => esc_html__( 'Self-Guided', 'ytrip' ),
			),
			'default' => 'group',
		),
		array(
			'id'     => 'age_restriction',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Age Restriction', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'min_age',
					'type'    => 'number',
					'title'   => esc_html__( 'Minimum Age', 'ytrip' ),
					'default' => 0,
				),
				array(
					'id'      => 'max_age',
					'type'    => 'number',
					'title'   => esc_html__( 'Maximum Age', 'ytrip' ),
					'default' => 99,
				),
			),
		),
		array(
			'id'      => 'languages',
			'type'    => 'select',
			'title'   => esc_html__( 'Languages Available', 'ytrip' ),
			'options' => array(
				'en' => 'English',
				'ar' => 'Arabic',
				'de' => 'German',
				'fr' => 'French',
				'es' => 'Spanish',
				'it' => 'Italian',
				'zh' => 'Chinese',
				'ru' => 'Russian',
			),
			'multiple' => true,
			'chosen'   => true,
			'default'  => array( 'en' ),
		),
	),
) );

// ============================================================
// TAB 2: Highlights
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Highlights', 'ytrip' ),
	'icon'   => 'fa fa-star',
	'fields' => array(
		array(
			'id'       => 'highlights',
			'type'     => 'repeater',
			'title'    => esc_html__( 'Tour Highlights', 'ytrip' ),
			'subtitle' => esc_html__( 'Key features that make this tour special.', 'ytrip' ),
			'fields'   => array(
				array(
					'id'    => 'highlight',
					'type'  => 'text',
					'title' => esc_html__( 'Highlight', 'ytrip' ),
				),
			),
		),
		array(
			'id'    => 'short_description',
			'type'  => 'textarea',
			'title' => esc_html__( 'Short Description', 'ytrip' ),
			'desc'  => esc_html__( 'Brief summary for tour cards (150-200 characters).', 'ytrip' ),
		),
	),
) );

// ============================================================
// TAB 3: Itinerary
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Itinerary', 'ytrip' ),
	'icon'   => 'fa fa-calendar',
	'fields' => array(
		array(
			'id'     => 'itinerary',
			'type'   => 'group',
			'title'  => esc_html__( 'Daily Program', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'day_number',
					'type'    => 'number',
					'title'   => esc_html__( 'Day', 'ytrip' ),
					'default' => 1,
				),
				array(
					'id'    => 'day_title',
					'type'  => 'text',
					'title' => esc_html__( 'Title', 'ytrip' ),
				),
				array(
					'id'    => 'day_description',
					'type'  => 'wp_editor',
					'title' => esc_html__( 'Description', 'ytrip' ),
				),
				array(
					'id'    => 'day_image',
					'type'  => 'media',
					'title' => esc_html__( 'Image', 'ytrip' ),
				),
				array(
					'id'     => 'meals',
					'type'   => 'checkbox',
					'title'  => esc_html__( 'Meals Included', 'ytrip' ),
					'options' => array(
						'breakfast' => esc_html__( 'Breakfast', 'ytrip' ),
						'lunch'     => esc_html__( 'Lunch', 'ytrip' ),
						'dinner'    => esc_html__( 'Dinner', 'ytrip' ),
					),
				),
				array(
					'id'    => 'accommodation',
					'type'  => 'text',
					'title' => esc_html__( 'Accommodation', 'ytrip' ),
				),
				array(
					'id'     => 'activities',
					'type'   => 'repeater',
					'title'  => esc_html__( 'Activities', 'ytrip' ),
					'fields' => array(
						array(
							'id'    => 'time',
							'type'  => 'text',
							'title' => esc_html__( 'Time', 'ytrip' ),
						),
						array(
							'id'    => 'activity',
							'type'  => 'text',
							'title' => esc_html__( 'Activity', 'ytrip' ),
						),
					),
				),
			),
		),
	),
) );

// ============================================================
// TAB 4: Included / Excluded
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Included / Excluded', 'ytrip' ),
	'icon'   => 'fa fa-check',
	'fields' => array(
		array(
			'id'     => 'included',
			'type'   => 'repeater',
			'title'  => esc_html__( 'What\'s Included', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'item',
					'type'  => 'text',
					'title' => esc_html__( 'Item', 'ytrip' ),
				),
				array(
					'id'      => 'icon',
					'type'    => 'select',
					'title'   => esc_html__( 'Icon', 'ytrip' ),
					'options' => array(
						'check'       => esc_html__( 'Check', 'ytrip' ),
						'hotel'       => esc_html__( 'Hotel', 'ytrip' ),
						'utensils'    => esc_html__( 'Meals', 'ytrip' ),
						'plane'       => esc_html__( 'Flight', 'ytrip' ),
						'bus'         => esc_html__( 'Transport', 'ytrip' ),
						'map-marker'  => esc_html__( 'Guide', 'ytrip' ),
						'ticket'      => esc_html__( 'Tickets', 'ytrip' ),
						'camera'      => esc_html__( 'Photography', 'ytrip' ),
						'wifi'        => esc_html__( 'WiFi', 'ytrip' ),
						'first-aid'   => esc_html__( 'Insurance', 'ytrip' ),
					),
					'default' => 'check',
				),
			),
		),
		array(
			'id'     => 'excluded',
			'type'   => 'repeater',
			'title'  => esc_html__( 'What\'s Excluded', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'item',
					'type'  => 'text',
					'title' => esc_html__( 'Item', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// TAB 5: Gallery & Media (one Top section control + tour gallery)
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Gallery & Media', 'ytrip' ),
	'icon'   => 'fa fa-images',
	'fields' => array(
		array(
			'type'    => 'subheading',
			'content' => esc_html__( 'Hero / Top section', 'ytrip' ),
		),
		array(
			'id'      => 'hero_gallery_mode',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Top section', 'ytrip' ),
			'desc'    => esc_html__( 'Single image: one image (featured or first gallery) as full-width hero. Slider: multiple images in hero as a slider. Carousel: multiple images in hero as a carousel.', 'ytrip' ),
			'options' => array(
				'single_image' => esc_html__( 'Single image (hero)', 'ytrip' ),
				'slider'       => esc_html__( 'Slider', 'ytrip' ),
				'carousel'     => esc_html__( 'Carousel', 'ytrip' ),
			),
			'default' => 'single_image',
		),
		array(
			'type'    => 'subheading',
			'content' => esc_html__( 'Images', 'ytrip' ),
		),
		array(
			'id'    => 'tour_gallery',
			'type'  => 'gallery',
			'title' => esc_html__( 'Tour gallery', 'ytrip' ),
			'desc'  => esc_html__( 'Select images. One image = full-width hero; multiple = slider or carousel in hero (see Top section above).', 'ytrip' ),
		),
		array(
			'type'    => 'subheading',
			'content' => esc_html__( 'Video & 360°', 'ytrip' ),
		),
		array(
			'id'    => 'tour_video',
			'type'  => 'text',
			'title' => esc_html__( 'Video URL', 'ytrip' ),
			'desc'  => esc_html__( 'YouTube or Vimeo video URL.', 'ytrip' ),
		),
		array(
			'id'    => 'virtual_tour',
			'type'  => 'text',
			'title' => esc_html__( '360° Virtual Tour', 'ytrip' ),
			'desc'  => esc_html__( 'Embed URL for 360-degree virtual tour.', 'ytrip' ),
		),
	),
) );

// ============================================================
// TAB 6: Location & Map
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Location', 'ytrip' ),
	'icon'   => 'fa fa-map-marker',
	'fields' => array(
		array(
			'id'    => 'meeting_point',
			'type'  => 'text',
			'title' => esc_html__( 'Meeting Point', 'ytrip' ),
			'desc'  => esc_html__( 'Address or location name where tour begins.', 'ytrip' ),
		),
		array(
			'id'    => 'meeting_time',
			'type'  => 'text',
			'title' => esc_html__( 'Meeting Time', 'ytrip' ),
		),
		array(
			'id'    => 'end_point',
			'type'  => 'text',
			'title' => esc_html__( 'End Point', 'ytrip' ),
			'desc'  => esc_html__( 'Where the tour ends (leave empty if same as meeting point).', 'ytrip' ),
		),
		array(
			'id'    => 'map_location',
			'type'  => 'map',
			'title' => esc_html__( 'Map Location', 'ytrip' ),
		),
		array(
			'id'     => 'tour_route',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Tour Route/Stops', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'stop_name',
					'type'  => 'text',
					'title' => esc_html__( 'Stop Name', 'ytrip' ),
				),
				array(
					'id'    => 'stop_description',
					'type'  => 'textarea',
					'title' => esc_html__( 'Description', 'ytrip' ),
				),
				array(
					'id'    => 'stop_duration',
					'type'  => 'text',
					'title' => esc_html__( 'Duration at Stop', 'ytrip' ),
				),
				array(
					'id'    => 'stop_location',
					'type'  => 'map',
					'title' => esc_html__( 'Location', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// TAB 7: Pricing & Booking
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Pricing', 'ytrip' ),
	'icon'   => 'fa fa-tag',
	'fields' => array(
		array(
			'id'      => 'booking_method',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Booking Method', 'ytrip' ),
			'options' => array(
				'woocommerce' => esc_html__( 'WooCommerce Booking', 'ytrip' ),
				'inquiry'     => esc_html__( 'Inquiry Form', 'ytrip' ),
				'external'    => esc_html__( 'External Link', 'ytrip' ),
			),
			'default' => 'woocommerce',
		),
		array(
			'id'         => 'external_booking_url',
			'type'       => 'text',
			'title'      => esc_html__( 'External Booking URL', 'ytrip' ),
			'dependency' => array( 'booking_method', '==', 'external' ),
		),
		array(
			'id'         => 'inquiry_email',
			'type'       => 'text',
			'title'      => esc_html__( 'Inquiry Email', 'ytrip' ),
			'dependency' => array( 'booking_method', '==', 'inquiry' ),
		),
		array(
			'id'     => 'pricing',
			'type'   => 'fieldset',
			'title'  => esc_html__( 'Base Pricing', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'regular_price',
					'type'  => 'number',
					'title' => esc_html__( 'Regular Price', 'ytrip' ),
				),
				array(
					'id'    => 'sale_price',
					'type'  => 'number',
					'title' => esc_html__( 'Sale Price', 'ytrip' ),
				),
				array(
					'id'      => 'price_type',
					'type'    => 'select',
					'title'   => esc_html__( 'Price Type', 'ytrip' ),
					'options' => array(
						'per_person' => esc_html__( 'Per Person', 'ytrip' ),
						'per_group'  => esc_html__( 'Per Group', 'ytrip' ),
					),
					'default' => 'per_person',
				),
			),
		),
		array(
			'id'     => 'person_types',
			'type'   => 'group',
			'title'  => esc_html__( 'Person Types', 'ytrip' ),
			'desc'   => esc_html__( 'Different pricing for adults, children, etc.', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'type_label',
					'type'  => 'text',
					'title' => esc_html__( 'Label', 'ytrip' ),
					'desc'  => esc_html__( 'e.g. Adult, Child, Infant', 'ytrip' ),
				),
				array(
					'id'    => 'price',
					'type'  => 'number',
					'title' => esc_html__( 'Price', 'ytrip' ),
				),
				array(
					'id'      => 'min_age',
					'type'    => 'number',
					'title'   => esc_html__( 'Min Age', 'ytrip' ),
					'default' => 0,
				),
				array(
					'id'      => 'max_age',
					'type'    => 'number',
					'title'   => esc_html__( 'Max Age', 'ytrip' ),
					'default' => 99,
				),
			),
		),
		array(
			'id'     => 'seasonal_pricing',
			'type'   => 'group',
			'title'  => esc_html__( 'Seasonal Pricing', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'season_name',
					'type'  => 'text',
					'title' => esc_html__( 'Season Name', 'ytrip' ),
				),
				array(
					'id'    => 'start_date',
					'type'  => 'date',
					'title' => esc_html__( 'Start Date', 'ytrip' ),
				),
				array(
					'id'    => 'end_date',
					'type'  => 'date',
					'title' => esc_html__( 'End Date', 'ytrip' ),
				),
				array(
					'id'    => 'price_adjustment',
					'type'  => 'number',
					'title' => esc_html__( 'Price Adjustment (%)', 'ytrip' ),
					'desc'  => esc_html__( 'Positive = increase, Negative = decrease', 'ytrip' ),
				),
			),
		),
		array(
			'id'     => 'group_discounts',
			'type'   => 'group',
			'title'  => esc_html__( 'Group Discounts', 'ytrip' ),
			'fields' => array(
				array(
					'id'      => 'min_persons',
					'type'    => 'number',
					'title'   => esc_html__( 'Minimum Persons', 'ytrip' ),
					'default' => 5,
				),
				array(
					'id'      => 'discount_percent',
					'type'    => 'number',
					'title'   => esc_html__( 'Discount (%)', 'ytrip' ),
					'default' => 10,
				),
			),
		),
	),
) );

// ============================================================
// TAB 8: Availability
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Availability', 'ytrip' ),
	'icon'   => 'fa fa-calendar-check',
	'fields' => array(
		array(
			'id'      => 'availability_type',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Availability Type', 'ytrip' ),
			'options' => array(
				'always'    => esc_html__( 'Always Available', 'ytrip' ),
				'dates'     => esc_html__( 'Specific Dates', 'ytrip' ),
				'days'      => esc_html__( 'Specific Days', 'ytrip' ),
			),
			'default' => 'always',
		),
		array(
			'id'         => 'available_days',
			'type'       => 'checkbox',
			'title'      => esc_html__( 'Available Days', 'ytrip' ),
			'options'    => array(
				'monday'    => esc_html__( 'Monday', 'ytrip' ),
				'tuesday'   => esc_html__( 'Tuesday', 'ytrip' ),
				'wednesday' => esc_html__( 'Wednesday', 'ytrip' ),
				'thursday'  => esc_html__( 'Thursday', 'ytrip' ),
				'friday'    => esc_html__( 'Friday', 'ytrip' ),
				'saturday'  => esc_html__( 'Saturday', 'ytrip' ),
				'sunday'    => esc_html__( 'Sunday', 'ytrip' ),
			),
			'dependency' => array( 'availability_type', '==', 'days' ),
		),
		array(
			'id'     => 'available_dates',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Available Dates', 'ytrip' ),
			'dependency' => array( 'availability_type', '==', 'dates' ),
			'fields' => array(
				array(
					'id'    => 'date',
					'type'  => 'date',
					'title' => esc_html__( 'Date', 'ytrip' ),
				),
				array(
					'id'      => 'slots',
					'type'    => 'number',
					'title'   => esc_html__( 'Available Slots', 'ytrip' ),
					'default' => 20,
				),
			),
		),
		array(
			'id'     => 'blocked_dates',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Blocked Dates', 'ytrip' ),
			'desc'   => esc_html__( 'Dates when this tour is not available.', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'date',
					'type'  => 'date',
					'title' => esc_html__( 'Date', 'ytrip' ),
				),
				array(
					'id'    => 'reason',
					'type'  => 'text',
					'title' => esc_html__( 'Reason', 'ytrip' ),
				),
			),
		),
		array(
			'id'      => 'max_bookings_per_day',
			'type'    => 'number',
			'title'   => esc_html__( 'Max Bookings Per Day', 'ytrip' ),
			'default' => 20,
		),
		array(
			'id'     => 'start_times',
			'type'   => 'repeater',
			'title'  => esc_html__( 'Start Times', 'ytrip' ),
			'desc'   => esc_html__( 'Available start times for this tour.', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'time',
					'type'  => 'text',
					'title' => esc_html__( 'Time', 'ytrip' ),
					'desc'  => esc_html__( 'e.g. 09:00 AM', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// TAB 9: FAQ
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'FAQ', 'ytrip' ),
	'icon'   => 'fa fa-question-circle',
	'fields' => array(
		array(
			'id'     => 'faq',
			'type'   => 'group',
			'title'  => esc_html__( 'Frequently Asked Questions', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'question',
					'type'  => 'text',
					'title' => esc_html__( 'Question', 'ytrip' ),
				),
				array(
					'id'    => 'answer',
					'type'  => 'textarea',
					'title' => esc_html__( 'Answer', 'ytrip' ),
				),
			),
		),
	),
) );

// ============================================================
// TAB 10: Related Tours
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Related Tours', 'ytrip' ),
	'icon'   => 'fa fa-link',
	'fields' => array(
		array(
			'id'      => 'related_mode',
			'type'    => 'button_set',
			'title'   => esc_html__( 'Related Tours Mode', 'ytrip' ),
			'options' => array(
				'auto'   => esc_html__( 'Automatic', 'ytrip' ),
				'manual' => esc_html__( 'Manual Selection', 'ytrip' ),
			),
			'default' => 'auto',
		),
		array(
			'id'         => 'related_taxonomy',
			'type'       => 'select',
			'title'      => esc_html__( 'Match By', 'ytrip' ),
			'options'    => array(
				'ytrip_destination' => esc_html__( 'Destination', 'ytrip' ),
				'ytrip_category'    => esc_html__( 'Category', 'ytrip' ),
			),
			'default'    => 'ytrip_destination',
			'dependency' => array( 'related_mode', '==', 'auto' ),
		),
		array(
			'id'         => 'related_count',
			'type'       => 'number',
			'title'      => esc_html__( 'Number of Tours', 'ytrip' ),
			'default'    => 4,
			'dependency' => array( 'related_mode', '==', 'auto' ),
		),
		array(
			'id'          => 'related_tours',
			'type'        => 'select',
			'title'       => esc_html__( 'Select Tours', 'ytrip' ),
			'multiple'    => true,
			'chosen'      => true,
			'ajax'        => true,
			'options'     => 'posts',
			'query_args'  => array(
				'post_type' => 'ytrip_tour',
			),
			'dependency'  => array( 'related_mode', '==', 'manual' ),
		),
	),
) );

// ============================================================
// TAB 11: Additional Info
// ============================================================
CSF::createSection( $prefix, array(
	'title'  => esc_html__( 'Additional Info', 'ytrip' ),
	'icon'   => 'fa fa-file-alt',
	'fields' => array(
		array(
			'id'    => 'things_to_bring',
			'type'  => 'textarea',
			'title' => esc_html__( 'What to Bring', 'ytrip' ),
		),
		array(
			'id'    => 'know_before_you_go',
			'type'  => 'wp_editor',
			'title' => esc_html__( 'Know Before You Go', 'ytrip' ),
		),
		array(
			'id'    => 'cancellation_policy',
			'type'  => 'wp_editor',
			'title' => esc_html__( 'Cancellation Policy', 'ytrip' ),
		),
		array(
			'id'    => 'custom_fields',
			'type'  => 'repeater',
			'title' => esc_html__( 'Custom Fields', 'ytrip' ),
			'desc'  => esc_html__( 'Add any additional custom information.', 'ytrip' ),
			'fields' => array(
				array(
					'id'    => 'label',
					'type'  => 'text',
					'title' => esc_html__( 'Label', 'ytrip' ),
				),
				array(
					'id'    => 'value',
					'type'  => 'text',
					'title' => esc_html__( 'Value', 'ytrip' ),
				),
				array(
					'id'    => 'icon',
					'type'  => 'icon',
					'title' => esc_html__( 'Icon', 'ytrip' ),
				),
			),
		),
	),
) );
