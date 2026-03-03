<?php
/**
 * YTrip Map View Class
 *
 * Handles map view functionality for tour archives using Leaflet.js
 * Features: marker clustering, custom popups, sidebar list, filter integration
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map View Class
 */
class YTrip_Map_View {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// REST API for tour locations.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// AJAX handler.
		add_action( 'wp_ajax_ytrip_get_tour_locations', array( $this, 'ajax_get_locations' ) );
		add_action( 'wp_ajax_nopriv_ytrip_get_tour_locations', array( $this, 'ajax_get_locations' ) );

		// Shortcode for map view.
		add_shortcode( 'ytrip_tours_map', array( $this, 'render_map_shortcode' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route( 'ytrip/v1', '/map/locations', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_locations' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'destination' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'category'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'min_price'   => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'max_price'   => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		) );
	}

	/**
	 * REST API callback: Get tour locations.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
	public function rest_get_locations( \WP_REST_Request $request ): \WP_REST_Response {
		$args = array(
			'destination' => $request->get_param( 'destination' ),
			'category'    => $request->get_param( 'category' ),
			'min_price'   => $request->get_param( 'min_price' ),
			'max_price'   => $request->get_param( 'max_price' ),
		);

		$locations = $this->get_tour_locations( $args );

		return new \WP_REST_Response( array(
			'success'   => true,
			'locations' => $locations,
			'count'     => count( $locations ),
		), 200 );
	}

	/**
	 * AJAX handler: Get tour locations.
	 *
	 * @return void
	 */
	public function ajax_get_locations() {
		$args = array(
			'destination' => isset( $_POST['destination'] ) ? sanitize_text_field( $_POST['destination'] ) : '',
			'category'    => isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '',
			'min_price'   => isset( $_POST['min_price'] ) ? absint( $_POST['min_price'] ) : 0,
			'max_price'   => isset( $_POST['max_price'] ) ? absint( $_POST['max_price'] ) : 0,
			'tour_date'   => isset( $_POST['tour_date'] ) ? sanitize_text_field( $_POST['tour_date'] ) : '',
			'date_from'   => isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '',
			'date_to'     => isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '',
		);

		$locations = $this->get_tour_locations( $args );

		wp_send_json_success( array(
			'locations' => $locations,
			'count'     => count( $locations ),
		) );
	}

	/**
	 * Get tour locations for map display.
	 *
	 * @param array $filters Filter arguments.
	 * @return array Tour location data.
	 */
	public function get_tour_locations( array $filters = array() ) {
		$args = array(
			'post_type'      => 'ytrip_tour',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(),
			'tax_query'      => array(),
		);

		// Apply filters.
		if ( ! empty( $filters['destination'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'ytrip_destination',
				'field'    => 'slug',
				'terms'    => $filters['destination'],
			);
		}

		if ( ! empty( $filters['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'ytrip_category',
				'field'    => 'slug',
				'terms'    => $filters['category'],
			);
		}

		if ( ! empty( $filters['min_price'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_ytrip_price',
				'value'   => $filters['min_price'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $filters['max_price'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_ytrip_price',
				'value'   => $filters['max_price'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $args['tax_query'] ) ) {
			$args['tax_query']['relation'] = 'AND';
		}

		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query']['relation'] = 'AND';
		}

		$tours = get_posts( $args );
		$locations = array();

		foreach ( $tours as $tour ) {
			$location_data = $this->get_tour_location_data( $tour->ID );
			
			if ( $location_data ) {
				$locations[] = $location_data;
			}
		}

		return $locations;
	}

	/**
	 * Get location data for a single tour.
	 *
	 * @param int $tour_id Tour post ID.
	 * @return array|null Location data or null if no coordinates.
	 */
	private function get_tour_location_data( int $tour_id ) {
		// Get coordinates.
		$lat = get_post_meta( $tour_id, '_ytrip_latitude', true );
		$lng = get_post_meta( $tour_id, '_ytrip_longitude', true );

		// Skip tours without valid coordinates.
		if ( empty( $lat ) || empty( $lng ) ) {
			// Try to get destination coordinates as fallback.
			$destinations = wp_get_post_terms( $tour_id, 'ytrip_destination' );
			if ( ! empty( $destinations ) && ! is_wp_error( $destinations ) ) {
				$dest = $destinations[0];
				$lat = get_term_meta( $dest->term_id, '_ytrip_latitude', true );
				$lng = get_term_meta( $dest->term_id, '_ytrip_longitude', true );
			}
		}

		if ( empty( $lat ) || empty( $lng ) ) {
			return null;
		}

		// Get tour data.
		$price = get_post_meta( $tour_id, '_ytrip_price', true );
		$duration = get_post_meta( $tour_id, '_ytrip_duration', true );
		$rating = get_post_meta( $tour_id, '_ytrip_rating', true );
		$destination = '';

		$destinations = wp_get_post_terms( $tour_id, 'ytrip_destination' );
		if ( ! empty( $destinations ) && ! is_wp_error( $destinations ) ) {
			$destination = $destinations[0]->name;
		}

		return array(
			'id'          => $tour_id,
			'title'       => get_the_title( $tour_id ),
			'url'         => get_permalink( $tour_id ),
			'thumbnail'   => get_the_post_thumbnail_url( $tour_id, 'medium' ),
			'lat'         => (float) $lat,
			'lng'         => (float) $lng,
			'price'       => $price ? (float) $price : 0,
			'price_html'  => $price ? wc_price( $price ) : '',
			'duration'    => $duration ?: '',
			'rating'      => $rating ? (float) $rating : 0,
			'destination' => $destination,
		);
	}

	/**
	 * Render map shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_map_shortcode( array $atts = array() ) {
		$atts = shortcode_atts( array(
			'height'      => '600px',
			'show_list'   => 'true',
			'destination' => '',
			'category'    => '',
			'limit'       => -1,
		), $atts, 'ytrip_tours_map' );

		// Enqueue assets.
		$this->enqueue_map_assets();

		$filters = array(
			'destination' => $atts['destination'],
			'category'    => $atts['category'],
		);

		$locations = $this->get_tour_locations( $filters );

		ob_start();
		?>
		<div class="ytrip-map-view-container" style="height: <?php echo esc_attr( $atts['height'] ); ?>">
			<div id="ytrip-tours-map"></div>
			
			<?php if ( $atts['show_list'] === 'true' ) : ?>
			<div class="ytrip-map-sidebar">
				<div class="ytrip-map-sidebar__header">
					<h3 class="ytrip-map-sidebar__title">
						<?php 
						printf( 
							esc_html( _n( '%d Tour', '%d Tours', count( $locations ), 'ytrip' ) ), 
							count( $locations ) 
						); 
						?>
					</h3>
					<button type="button" class="ytrip-map-sidebar__close" aria-label="<?php esc_attr_e( 'Close', 'ytrip' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
				<div class="ytrip-map-sidebar__list" id="ytrip-map-tour-list">
					<?php foreach ( $locations as $location ) : ?>
					<div class="ytrip-map-tour-item" data-tour-id="<?php echo esc_attr( $location['id'] ); ?>">
						<div class="ytrip-map-tour-item__thumb">
							<?php if ( $location['thumbnail'] ) : ?>
							<img src="<?php echo esc_url( $location['thumbnail'] ); ?>" alt="<?php echo esc_attr( $location['title'] ); ?>">
							<?php endif; ?>
						</div>
						<div class="ytrip-map-tour-item__info">
							<h4 class="ytrip-map-tour-item__title"><?php echo esc_html( $location['title'] ); ?></h4>
							<span class="ytrip-map-tour-item__price"><?php echo wp_kses_post( $location['price_html'] ); ?></span>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof YTripMap !== 'undefined' && typeof YTripMap.init === 'function') {
				YTripMap.init(<?php echo wp_json_encode( $locations ); ?>);
			}
		});
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Enqueue map assets.
	 *
	 * @return void
	 */
	public function enqueue_map_assets() {
		$provider = function_exists( 'ytrip_get_setting' ) 
			? ytrip_get_setting( 'map_provider', 'openstreetmap' ) 
			: 'openstreetmap';

		// Get settings for localization.
		$default_lat = function_exists( 'ytrip_get_setting' ) 
			? ytrip_get_setting( 'default_latitude', 25.276987 ) 
			: 25.276987;
		$default_lng = function_exists( 'ytrip_get_setting' ) 
			? ytrip_get_setting( 'default_longitude', 55.296249 ) 
			: 55.296249;
		$default_zoom = function_exists( 'ytrip_get_setting' ) 
			? ytrip_get_setting( 'default_zoom', 5 ) 
			: 5;

		if ( $provider === 'google' ) {
			$this->enqueue_google_maps_assets( $default_lat, $default_lng, $default_zoom );
		} else {
			$this->enqueue_leaflet_assets( $default_lat, $default_lng, $default_zoom );
		}
	}

	/**
	 * Enqueue Leaflet (OpenStreetMap) assets.
	 *
	 * @param float $lat Default latitude.
	 * @param float $lng Default longitude.
	 * @param int   $zoom Default zoom.
	 * @return void
	 */
	private function enqueue_leaflet_assets( float $lat, float $lng, int $zoom ) {
		// Leaflet CSS - LOCAL
		wp_enqueue_style(
			'leaflet',
			YTRIP_URL . 'assets/vendor/leaflet/leaflet.css',
			array(),
			'1.9.4'
		);

		// Leaflet MarkerCluster CSS - LOCAL
		wp_enqueue_style(
			'leaflet-markercluster',
			YTRIP_URL . 'assets/vendor/leaflet/MarkerCluster.css',
			array( 'leaflet' ),
			'1.4.1'
		);

		wp_enqueue_style(
			'leaflet-markercluster-default',
			YTRIP_URL . 'assets/vendor/leaflet/MarkerCluster.Default.css',
			array( 'leaflet-markercluster' ),
			'1.4.1'
		);

		// Leaflet JS - LOCAL
		wp_enqueue_script(
			'leaflet',
			YTRIP_URL . 'assets/vendor/leaflet/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		// Leaflet MarkerCluster JS - LOCAL
		wp_enqueue_script(
			'leaflet-markercluster',
			YTRIP_URL . 'assets/vendor/leaflet/leaflet.markercluster.js',
			array( 'leaflet' ),
			'1.4.1',
			true
		);

		// YTrip Map JS (Leaflet).
		wp_enqueue_script(
			'ytrip-map-view',
			YTRIP_URL . 'assets/js/map-view.js',
			array( 'jquery', 'leaflet', 'leaflet-markercluster' ),
			YTRIP_VERSION,
			true
		);

		wp_localize_script( 'ytrip-map-view', 'ytripMap', array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'ytrip_map_nonce' ),
			'provider'    => 'openstreetmap',
			'tileUrl'     => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			'defaultLat'  => $lat,
			'defaultLng'  => $lng,
			'defaultZoom' => $zoom,
			'strings'     => array(
				'viewDetails' => __( 'View Details', 'ytrip' ),
				'perPerson'   => __( 'per person', 'ytrip' ),
				'noTours'     => __( 'No tours found in this area.', 'ytrip' ),
			),
		) );
	}

	/**
	 * Enqueue Google Maps assets.
	 *
	 * @param float $lat Default latitude.
	 * @param float $lng Default longitude.
	 * @param int   $zoom Default zoom.
	 * @return void
	 */
	private function enqueue_google_maps_assets( float $lat, float $lng, int $zoom ) {
		$api_key = function_exists( 'ytrip_get_setting' ) 
			? ytrip_get_setting( 'google_maps_api', '' ) 
			: '';

		if ( empty( $api_key ) ) {
			// Fallback to Leaflet if no API key.
			$this->enqueue_leaflet_assets( $lat, $lng, $zoom );
			return;
		}

		// Google Maps JS.
		wp_enqueue_script(
			'google-maps',
			'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places',
			array(),
			null,
			true
		);

		// MarkerClusterer for Google Maps (bundled locally).
		wp_enqueue_script(
			'google-maps-markerclusterer',
			YTRIP_URL . 'assets/vendor/markerclusterer/markerclusterer.min.js',
			array( 'google-maps' ),
			'2.0.0',
			true
		);

		// YTrip Map JS (Google).
		wp_enqueue_script(
			'ytrip-map-view',
			YTRIP_URL . 'assets/js/map-view-google.js',
			array( 'jquery', 'google-maps', 'google-maps-markerclusterer' ),
			YTRIP_VERSION,
			true
		);

		wp_localize_script( 'ytrip-map-view', 'ytripMap', array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'ytrip_map_nonce' ),
			'provider'    => 'google',
			'apiKey'      => $api_key,
			'defaultLat'  => $lat,
			'defaultLng'  => $lng,
			'defaultZoom' => $zoom,
			'strings'     => array(
				'viewDetails' => __( 'View Details', 'ytrip' ),
				'perPerson'   => __( 'per person', 'ytrip' ),
				'noTours'     => __( 'No tours found in this area.', 'ytrip' ),
			),
		) );
	}

	/**
	 * Render map toggle button for archive toolbar.
	 *
	 * @return string HTML.
	 */
	public static function render_map_toggle() {
		ob_start();
		?>
		<button type="button" class="ytrip-map-toggle" id="ytrip-map-toggle">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
				<line x1="8" y1="2" x2="8" y2="18"></line>
				<line x1="16" y1="6" x2="16" y2="22"></line>
			</svg>
			<span><?php esc_html_e( 'Map View', 'ytrip' ); ?></span>
		</button>
		<?php
		return ob_get_clean();
	}
}

// Initialize.
YTrip_Map_View::instance();
