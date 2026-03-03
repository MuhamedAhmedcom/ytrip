<?php
/**
 * YTrip Query Optimizer
 *
 * Optimizes database queries for maximum performance.
 * Implements caching, efficient WP_Query arguments, and batch processing.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Optimizer Class
 */
class YTrip_Query_Optimizer {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	private $cache_group = 'ytrip_queries';

	/**
	 * Cache expiration (seconds).
	 *
	 * @var int
	 */
	private $cache_expiration = 3600;

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
		// Optimize archive queries.
		add_action( 'pre_get_posts', array( $this, 'optimize_archive_query' ) );

		// Clear cache on tour changes.
		add_action( 'save_post_ytrip_tour', array( $this, 'clear_tour_cache' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'clear_tour_cache' ) );
		add_action( 'set_object_terms', array( $this, 'clear_taxonomy_cache' ), 10, 4 );
	}

	/**
	 * Optimize archive/taxonomy query.
	 *
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function optimize_archive_query( \WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Only optimize YTrip queries.
		if ( ! $query->is_post_type_archive( 'ytrip_tour' ) && ! $query->is_tax( array( 'ytrip_destination', 'ytrip_category' ) ) ) {
			return;
		}

		// Disable SQL_CALC_FOUND_ROWS if pagination is handled client-side.
		$settings = get_option( 'ytrip_settings', array() );
		if ( ! empty( $settings['infinite_scroll'] ) ) {
			$query->set( 'no_found_rows', true );
		}

		// Optimize meta query caching.
		$query->set( 'update_post_meta_cache', true );
		$query->set( 'update_post_term_cache', true );

		// Limit fields if only displaying cards.
		if ( wp_doing_ajax() && isset( $_POST['action'] ) && $_POST['action'] === 'ytrip_load_more' ) {
			$query->set( 'no_found_rows', true );
		}
	}

	/**
	 * Get tours with optimized query.
	 *
	 * @param array $args Query arguments.
	 * @return array Tours data.
	 */
	public function get_tours( array $args = array() ) {
		$cache_key = 'tours_' . md5( wp_json_encode( $args ) );

		// Try cache first.
		$cached = $this->get_cache( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$defaults = array(
			'post_type'              => 'ytrip_tour',
			'post_status'            => 'publish',
			'posts_per_page'         => 12,
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// If we only need IDs for further processing.
		if ( ! empty( $args['fields_only'] ) ) {
			$args['fields'] = 'ids';
			unset( $args['fields_only'] );
		}

		$query = new \WP_Query( $args );
		$tours = array();

		if ( $query->have_posts() ) {
			// Pre-fetch all meta in one query.
			if ( $args['fields'] !== 'ids' ) {
				$post_ids = wp_list_pluck( $query->posts, 'ID' );
				$this->prime_meta_cache( $post_ids );
			}

			foreach ( $query->posts as $post ) {
				$tours[] = $this->format_tour_data( $post );
			}
		}

		$result = array(
			'tours'       => $tours,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
		);

		// Cache result.
		$this->set_cache( $cache_key, $result );

		return $result;
	}

	/**
	 * Get single tour with all data.
	 *
	 * @param int $tour_id Tour ID.
	 * @return array|null Tour data or null.
	 */
	public function get_tour( int $tour_id ) {
		$cache_key = 'tour_' . $tour_id;

		$cached = $this->get_cache( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$post = get_post( $tour_id );
		if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
			return null;
		}

		$tour = $this->format_tour_data( $post, true );

		$this->set_cache( $cache_key, $tour );

		return $tour;
	}

	/**
	 * Format tour post into data array.
	 *
	 * @param \WP_Post|int $post Post object or ID.
	 * @param bool         $full Include all meta data.
	 * @return array Tour data.
	 */
	private function format_tour_data( $post, bool $full = false ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		$tour_id = $post->ID;

		$data = array(
			'id'        => $tour_id,
			'title'     => $post->post_title,
			'excerpt'   => get_the_excerpt( $post ),
			'permalink' => get_permalink( $tour_id ),
			'thumbnail' => get_the_post_thumbnail_url( $tour_id, 'large' ),
			'price'     => get_post_meta( $tour_id, '_ytrip_price', true ),
			'duration'  => get_post_meta( $tour_id, '_ytrip_duration', true ),
			'rating'    => $this->get_tour_rating( $tour_id ),
		);

		if ( $full ) {
			$data['content']     = apply_filters( 'the_content', $post->post_content );
			$data['gallery']     = get_post_meta( $tour_id, '_ytrip_gallery', true );
			$data['itinerary']   = get_post_meta( $tour_id, '_ytrip_itinerary', true );
			$data['highlights']  = get_post_meta( $tour_id, '_ytrip_highlights', true );
			$data['included']    = get_post_meta( $tour_id, '_ytrip_included', true );
			$data['excluded']    = get_post_meta( $tour_id, '_ytrip_excluded', true );
			$data['destination'] = wp_get_post_terms( $tour_id, 'ytrip_destination', array( 'fields' => 'names' ) );
			$data['category']    = wp_get_post_terms( $tour_id, 'ytrip_category', array( 'fields' => 'names' ) );
		}

		return $data;
	}

	/**
	 * Get cached tour rating.
	 *
	 * @param int $tour_id Tour ID.
	 * @return array Rating data.
	 */
	private function get_tour_rating( int $tour_id ) {
		$cache_key = 'rating_' . $tour_id;

		$cached = $this->get_cache( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$rating = array(
			'average' => (float) get_post_meta( $tour_id, '_ytrip_rating_average', true ) ?: 0,
			'count'   => (int) get_post_meta( $tour_id, '_ytrip_rating_count', true ) ?: 0,
		);

		$this->set_cache( $cache_key, $rating, 1800 ); // 30 min cache.

		return $rating;
	}

	/**
	 * Prime meta cache for multiple posts.
	 *
	 * @param array $post_ids Post IDs.
	 * @return void
	 */
	private function prime_meta_cache( array $post_ids ) {
		if ( empty( $post_ids ) ) {
			return;
		}

		// This fetches all meta for these posts in one query.
		update_meta_cache( 'post', $post_ids );
	}

	/**
	 * Get destinations with tour counts (optimized).
	 *
	 * @param array $args Arguments.
	 * @return array Destinations.
	 */
	public function get_destinations( array $args = array() ) {
		$cache_key = 'destinations_' . md5( wp_json_encode( $args ) );

		$cached = $this->get_cache( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$defaults = array(
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$terms = get_terms( array_merge( $args, array( 'taxonomy' => 'ytrip_destination' ) ) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$destinations = array();
		foreach ( $terms as $term ) {
			$destinations[] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'count'       => $term->count,
				'description' => $term->description,
				'link'        => get_term_link( $term ),
				'image'       => get_term_meta( $term->term_id, '_ytrip_image', true ),
			);
		}

		$this->set_cache( $cache_key, $destinations );

		return $destinations;
	}

	/**
	 * Get featured tours (heavily cached).
	 *
	 * @param int $limit Number of tours.
	 * @return array Tours.
	 */
	public function get_featured_tours( int $limit = 6 ) {
		return $this->get_tours( array(
			'posts_per_page' => $limit,
			'meta_key'       => '_ytrip_featured',
			'meta_value'     => '1',
			'no_found_rows'  => true,
		) )['tours'];
	}

	/**
	 * Get popular tours by views or bookings.
	 *
	 * @param int $limit Number of tours.
	 * @return array Tours.
	 */
	public function get_popular_tours( int $limit = 6 ) {
		return $this->get_tours( array(
			'posts_per_page' => $limit,
			'meta_key'       => '_ytrip_booking_count',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		) )['tours'];
	}

	// =========================================================================
	// Caching
	// =========================================================================

	/**
	 * Get cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value or false.
	 */
	private function get_cache( string $key ) {
		// Try object cache first (Redis/Memcached).
		$cached = wp_cache_get( $key, $this->cache_group );
		if ( false !== $cached ) {
			return $cached;
		}

		// Fall back to transients.
		return get_transient( $this->cache_group . '_' . $key );
	}

	/**
	 * Set cache value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $expiration Expiration in seconds.
	 * @return void
	 */
	private function set_cache( string $key, $value, int $expiration = 0 ) {
		$expiration = $expiration ?: $this->cache_expiration;

		// Set in object cache.
		wp_cache_set( $key, $value, $this->cache_group, $expiration );

		// Also set transient as fallback.
		set_transient( $this->cache_group . '_' . $key, $value, $expiration );
	}

	/**
	 * Clear tour cache.
	 *
	 * @param int           $post_id Post ID.
	 * @param \WP_Post|null $post Post object.
	 * @return void
	 */
	public function clear_tour_cache( int $post_id, ?\WP_Post $post = null ) {
		if ( $post && $post->post_type !== 'ytrip_tour' ) {
			return;
		}

		// Clear specific tour cache.
		wp_cache_delete( 'tour_' . $post_id, $this->cache_group );
		delete_transient( $this->cache_group . '_tour_' . $post_id );
		delete_transient( $this->cache_group . '_rating_' . $post_id );

		// Clear list caches.
		$this->clear_list_caches();
	}

	/**
	 * Clear taxonomy cache.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms Terms.
	 * @param array  $tt_ids Term taxonomy IDs.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function clear_taxonomy_cache( int $object_id, array $terms, array $tt_ids, string $taxonomy ) {
		if ( ! in_array( $taxonomy, array( 'ytrip_destination', 'ytrip_category' ), true ) ) {
			return;
		}

		// Clear destination/category caches.
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_{$this->cache_group}_destinations%'
			OR option_name LIKE '_transient_timeout_{$this->cache_group}_destinations%'"
		);

		$this->clear_list_caches();
	}

	/**
	 * Clear all list caches.
	 *
	 * @return void
	 */
	private function clear_list_caches() {
		global $wpdb;

		// Clear transients.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_{$this->cache_group}_tours_%'
			OR option_name LIKE '_transient_timeout_{$this->cache_group}_tours_%'"
		);

		// Clear object cache group.
		wp_cache_flush_group( $this->cache_group );
	}
}

// Initialize.
YTrip_Query_Optimizer::instance();

/**
 * Helper function to get query optimizer instance.
 *
 * @return YTrip_Query_Optimizer
 */
function ytrip_queries(): YTrip_Query_Optimizer {
	return YTrip_Query_Optimizer::instance();
}
