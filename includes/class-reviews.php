<?php
/**
 * YTrip Reviews System
 *
 * Complete reviews implementation with:
 * - Custom database table for ratings
 * - Star rating system (1-5)
 * - Multiple rating categories (Service, Value, Guide)
 * - Moderation workflow (Pending/Approved/Rejected)
 * - Purchase verification
 * - Rate limiting
 * - REST API endpoints
 * - Schema.org integration
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reviews System Class
 */
class YTrip_Reviews {

	/**
	 * Database table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE_NAME = 'ytrip_ratings';

	/**
	 * Review statuses.
	 */
	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';

	/**
	 * Rate limit: reviews per user per day.
	 *
	 * @var int
	 */
	const RATE_LIMIT_PER_DAY = 5;

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
		// Database.
		register_activation_hook( YTRIP_FILE, array( $this, 'create_table' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ytrip_submit_review', array( $this, 'ajax_submit_review' ) );
		add_action( 'wp_ajax_ytrip_helpful_vote', array( $this, 'ajax_helpful_vote' ) );

		// Admin.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_ytrip_moderate_review', array( $this, 'ajax_moderate_review' ) );

		// Schema.
		add_filter( 'ytrip_schema_data', array( $this, 'add_rating_to_schema' ), 10, 2 );

		// Shortcode.
		add_shortcode( 'ytrip_reviews', array( $this, 'render_reviews_shortcode' ) );
	}

	/**
	 * Create database table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tour_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED DEFAULT NULL,
			overall_rating DECIMAL(2,1) NOT NULL,
			service_rating DECIMAL(2,1) DEFAULT NULL,
			value_rating DECIMAL(2,1) DEFAULT NULL,
			guide_rating DECIMAL(2,1) DEFAULT NULL,
			review_title VARCHAR(255) DEFAULT NULL,
			review_text LONGTEXT DEFAULT NULL,
			photos LONGTEXT DEFAULT NULL,
			helpful_yes INT UNSIGNED DEFAULT 0,
			helpful_no INT UNSIGNED DEFAULT 0,
			status VARCHAR(20) DEFAULT 'pending',
			admin_notes TEXT DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent VARCHAR(255) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_user_tour (user_id, tour_id),
			KEY idx_tour_id (tour_id),
			KEY idx_user_id (user_id),
			KEY idx_status (status),
			KEY idx_created_at (created_at),
			KEY idx_overall_rating (overall_rating)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Store DB version.
		update_option( 'ytrip_reviews_db_version', '1.0.0' );
	}

	/**
	 * Get table name with prefix.
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	// =========================================================================
	// CRUD Operations
	// =========================================================================

	/**
	 * Add a new review.
	 *
	 * @param array $data Review data.
	 * @return int|WP_Error Review ID on success, WP_Error on failure.
	 */
	public function add_review( array $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['tour_id'] ) || empty( $data['user_id'] ) || empty( $data['overall_rating'] ) ) {
			return new WP_Error( 'missing_required', __( 'Missing required fields.', 'ytrip' ) );
		}

		$tour_id = absint( $data['tour_id'] );
		$user_id = absint( $data['user_id'] );

		// Check if user can review.
		$can_review = $this->can_review( $tour_id, $user_id );
		if ( is_wp_error( $can_review ) ) {
			return $can_review;
		}

		// Check rate limit.
		if ( ! $this->check_rate_limit( $user_id ) ) {
			return new WP_Error( 'rate_limited', __( 'You have submitted too many reviews today. Please try again tomorrow.', 'ytrip' ) );
		}

		// Validate rating.
		$overall_rating = floatval( $data['overall_rating'] );
		if ( $overall_rating < 1 || $overall_rating > 5 ) {
			return new WP_Error( 'invalid_rating', __( 'Rating must be between 1 and 5.', 'ytrip' ) );
		}

		// Prepare data.
		$insert_data = array(
			'tour_id'        => $tour_id,
			'user_id'        => $user_id,
			'order_id'       => isset( $data['order_id'] ) ? absint( $data['order_id'] ) : null,
			'overall_rating' => $overall_rating,
			'service_rating' => isset( $data['service_rating'] ) ? floatval( $data['service_rating'] ) : null,
			'value_rating'   => isset( $data['value_rating'] ) ? floatval( $data['value_rating'] ) : null,
			'guide_rating'   => isset( $data['guide_rating'] ) ? floatval( $data['guide_rating'] ) : null,
			'review_title'   => isset( $data['review_title'] ) ? sanitize_text_field( $data['review_title'] ) : null,
			'review_text'    => isset( $data['review_text'] ) ? sanitize_textarea_field( $data['review_text'] ) : null,
			'photos'         => isset( $data['photos'] ) ? wp_json_encode( array_map( 'absint', (array) $data['photos'] ) ) : null,
			'status'         => self::STATUS_PENDING,
			'ip_address'     => $this->get_client_ip(),
			'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
		);

		$formats = array( '%d', '%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s' );

		$result = $wpdb->insert( $this->get_table_name(), $insert_data, $formats );

		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to save review.', 'ytrip' ) );
		}

		$review_id = $wpdb->insert_id;

		// Clear rating cache.
		$this->clear_rating_cache( $tour_id );

		// Trigger action.
		do_action( 'ytrip_review_submitted', $review_id, $insert_data );

		// Notify admin.
		$this->notify_admin_new_review( $review_id );

		return $review_id;
	}

	/**
	 * Get a single review by ID.
	 *
	 * @param int $review_id Review ID.
	 * @return object|null Review object or null.
	 */
	public function get_review( int $review_id ) {
		global $wpdb;

		$review = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()} WHERE id = %d",
				$review_id
			)
		);

		return $review ?: null;
	}

	/**
	 * Get reviews for a tour.
	 *
	 * @param int   $tour_id Tour ID.
	 * @param array $args    Query arguments.
	 * @return array Reviews with pagination info.
	 */
	public function get_tour_reviews( int $tour_id, array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => self::STATUS_APPROVED,
			'per_page' => 10,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		// Build WHERE clause.
		$where = array( 'tour_id = %d' );
		$values = array( $tour_id );

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		$where_clause = implode( ' AND ', $where );

		// Get total count.
		$count_sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->get_table_name()} WHERE {$where_clause}",
			...$values
		);
		$total = (int) $wpdb->get_var( $count_sql );

		// Validate orderby.
		$allowed_orderby = array( 'created_at', 'overall_rating', 'helpful_yes' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Get reviews.
		$prepare_values = array_merge( $values, array( absint( $args['per_page'] ), $offset ) );
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} 
			WHERE {$where_clause} 
			ORDER BY {$orderby} {$order} 
			LIMIT %d OFFSET %d",
			...$prepare_values
		);

		$reviews = $wpdb->get_results( $sql );

		// Enrich with user data.
		foreach ( $reviews as $review ) {
			$user = get_userdata( $review->user_id );
			$review->user_name = $user ? $user->display_name : __( 'Anonymous', 'ytrip' );
			$review->user_avatar = get_avatar_url( $review->user_id, array( 'size' => 60 ) );
			$review->is_verified = ! empty( $review->order_id );
			$review->photos = $review->photos ? json_decode( $review->photos, true ) : array();
		}

		return array(
			'reviews'    => $reviews,
			'total'      => $total,
			'pages'      => ceil( $total / absint( $args['per_page'] ) ),
			'page'       => absint( $args['page'] ),
			'per_page'   => absint( $args['per_page'] ),
		);
	}

	/**
	 * Update review status (moderation).
	 *
	 * @param int    $review_id   Review ID.
	 * @param string $status      New status.
	 * @param string $admin_notes Optional admin notes.
	 * @return bool Success.
	 */
	public function update_status( int $review_id, string $status, string $admin_notes = '' ) {
		global $wpdb;

		if ( ! in_array( $status, array( self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED ), true ) ) {
			return false;
		}

		$review = $this->get_review( $review_id );
		if ( ! $review ) {
			return false;
		}

		$result = $wpdb->update(
			$this->get_table_name(),
			array(
				'status'      => $status,
				'admin_notes' => sanitize_textarea_field( $admin_notes ),
			),
			array( 'id' => $review_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$this->clear_rating_cache( $review->tour_id );
			do_action( 'ytrip_review_status_changed', $review_id, $status, $review );
			return true;
		}

		return false;
	}

	/**
	 * Delete a review.
	 *
	 * @param int $review_id Review ID.
	 * @return bool Success.
	 */
	public function delete_review( int $review_id ) {
		global $wpdb;

		$review = $this->get_review( $review_id );
		if ( ! $review ) {
			return false;
		}

		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => $review_id ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$this->clear_rating_cache( $review->tour_id );
			return true;
		}

		return false;
	}

	// =========================================================================
	// Rating Calculations
	// =========================================================================

	/**
	 * Get aggregate rating for a tour.
	 *
	 * @param int $tour_id Tour ID.
	 * @return array Rating data.
	 */
	public function get_tour_rating( int $tour_id ) {
		$cache_key = 'ytrip_rating_' . $tour_id;
		$cached = wp_cache_get( $cache_key, 'ytrip_reviews' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as review_count,
					AVG(overall_rating) as average_rating,
					AVG(service_rating) as average_service,
					AVG(value_rating) as average_value,
					AVG(guide_rating) as average_guide
				FROM {$this->get_table_name()}
				WHERE tour_id = %d AND status = %s",
				$tour_id,
				self::STATUS_APPROVED
			),
			ARRAY_A
		);

		$rating_distribution = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT FLOOR(overall_rating) as stars, COUNT(*) as count
				FROM {$this->get_table_name()}
				WHERE tour_id = %d AND status = %s
				GROUP BY FLOOR(overall_rating)
				ORDER BY stars DESC",
				$tour_id,
				self::STATUS_APPROVED
			),
			ARRAY_A
		);

		$distribution = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		foreach ( $rating_distribution as $row ) {
			$stars = (int) $row['stars'];
			if ( isset( $distribution[ $stars ] ) ) {
				$distribution[ $stars ] = (int) $row['count'];
			}
		}

		$data = array(
			'count'           => (int) $result['review_count'],
			'average'         => round( (float) $result['average_rating'], 1 ),
			'service_average' => round( (float) $result['average_service'], 1 ),
			'value_average'   => round( (float) $result['average_value'], 1 ),
			'guide_average'   => round( (float) $result['average_guide'], 1 ),
			'distribution'    => $distribution,
		);

		wp_cache_set( $cache_key, $data, 'ytrip_reviews', HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Clear rating cache for a tour.
	 *
	 * @param int $tour_id Tour ID.
	 * @return void
	 */
	private function clear_rating_cache( int $tour_id ) {
		wp_cache_delete( 'ytrip_rating_' . $tour_id, 'ytrip_reviews' );
	}

	// =========================================================================
	// Access Control
	// =========================================================================

	/**
	 * Check if user can review a tour.
	 *
	 * @param int $tour_id Tour ID.
	 * @param int $user_id User ID.
	 * @return true|WP_Error True if can review, WP_Error otherwise.
	 */
	public function can_review( int $tour_id, int $user_id ) {
		// Must be logged in.
		if ( 0 === $user_id ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to write a review.', 'ytrip' ) );
		}

		// Check for existing review.
		global $wpdb;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->get_table_name()} WHERE tour_id = %d AND user_id = %d",
				$tour_id,
				$user_id
			)
		);

		if ( $existing ) {
			return new WP_Error( 'already_reviewed', __( 'You have already reviewed this tour.', 'ytrip' ) );
		}

		// Check purchase verification (optional but recommended).
		$settings = get_option( 'ytrip_settings', array() );
		$require_purchase = $settings['require_purchase_for_review'] ?? false;

		if ( $require_purchase ) {
			$has_purchased = $this->has_purchased_tour( $tour_id, $user_id );
			if ( ! $has_purchased ) {
				return new WP_Error( 'not_purchased', __( 'You must book this tour before you can review it.', 'ytrip' ) );
			}
		}

		return true;
	}

	/**
	 * Check if user has purchased a tour.
	 *
	 * @param int $tour_id Tour ID.
	 * @param int $user_id User ID.
	 * @return int|false Order ID if purchased, false otherwise.
	 */
	public function has_purchased_tour( int $tour_id, int $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return false;
		}

		// Get linked WooCommerce product.
		$product_id = get_post_meta( $tour_id, '_ytrip_product_id', true );
		if ( ! $product_id ) {
			return false;
		}

		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => 1,
			'return'      => 'ids',
		) );

		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			foreach ( $order->get_items() as $item ) {
				if ( (int) $item->get_product_id() === (int) $product_id ) {
					return $order_id;
				}
			}
		}

		return false;
	}

	/**
	 * Check rate limit.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if under limit.
	 */
	private function check_rate_limit( int $user_id ) {
		global $wpdb;

		$today_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->get_table_name()} 
				WHERE user_id = %d AND DATE(created_at) = CURDATE()",
				$user_id
			)
		);

		return (int) $today_count < self::RATE_LIMIT_PER_DAY;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle multiple IPs (X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * Handle review submission via AJAX.
	 *
	 * @return void
	 */
	public function ajax_submit_review() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'ytrip_review_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ytrip' ) ) );
		}

		$data = array(
			'tour_id'        => isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0,
			'user_id'        => $user_id,
			'overall_rating' => isset( $_POST['overall_rating'] ) ? floatval( $_POST['overall_rating'] ) : 0,
			'service_rating' => isset( $_POST['service_rating'] ) ? floatval( $_POST['service_rating'] ) : null,
			'value_rating'   => isset( $_POST['value_rating'] ) ? floatval( $_POST['value_rating'] ) : null,
			'guide_rating'   => isset( $_POST['guide_rating'] ) ? floatval( $_POST['guide_rating'] ) : null,
			'review_title'   => isset( $_POST['review_title'] ) ? sanitize_text_field( wp_unslash( $_POST['review_title'] ) ) : '',
			'review_text'    => isset( $_POST['review_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_text'] ) ) : '',
			'photos'         => isset( $_POST['photos'] ) ? array_map( 'absint', (array) $_POST['photos'] ) : array(),
		);

		// Check for verified purchase.
		$order_id = $this->has_purchased_tour( $data['tour_id'], $user_id );
		if ( $order_id ) {
			$data['order_id'] = $order_id;
		}

		$result = $this->add_review( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'   => __( 'Thank you! Your review has been submitted and is pending approval.', 'ytrip' ),
			'review_id' => $result,
		) );
	}

	/**
	 * Handle helpful vote via AJAX.
	 *
	 * @return void
	 */
	public function ajax_helpful_vote() {
		if ( ! check_ajax_referer( 'ytrip_review_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		$vote = isset( $_POST['vote'] ) ? sanitize_key( $_POST['vote'] ) : '';

		if ( ! in_array( $vote, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid vote.', 'ytrip' ) ) );
		}

		// Check if already voted (using transient).
		$user_key = is_user_logged_in() ? 'u' . get_current_user_id() : 'ip' . md5( $this->get_client_ip() );
		$voted_key = 'ytrip_voted_' . $review_id . '_' . $user_key;

		if ( get_transient( $voted_key ) ) {
			wp_send_json_error( array( 'message' => __( 'You have already voted on this review.', 'ytrip' ) ) );
		}

		global $wpdb;
		$column = 'yes' === $vote ? 'helpful_yes' : 'helpful_no';

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->get_table_name()} SET {$column} = {$column} + 1 WHERE id = %d",
				$review_id
			)
		);

		if ( $result ) {
			set_transient( $voted_key, 1, WEEK_IN_SECONDS );
			wp_send_json_success( array( 'message' => __( 'Thank you for your feedback!', 'ytrip' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to record vote.', 'ytrip' ) ) );
	}

	/**
	 * Handle moderation via AJAX (admin).
	 *
	 * @return void
	 */
	public function ajax_moderate_review() {
		if ( ! check_ajax_referer( 'ytrip_admin_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ytrip' ) ) );
		}

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		$action = isset( $_POST['action_type'] ) ? sanitize_key( $_POST['action_type'] ) : '';
		$notes = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( 'approve' === $action ) {
			$status = self::STATUS_APPROVED;
		} elseif ( 'reject' === $action ) {
			$status = self::STATUS_REJECTED;
		} elseif ( 'delete' === $action ) {
			if ( $this->delete_review( $review_id ) ) {
				wp_send_json_success( array( 'message' => __( 'Review deleted.', 'ytrip' ) ) );
			}
			wp_send_json_error( array( 'message' => __( 'Failed to delete review.', 'ytrip' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'ytrip' ) ) );
		}

		if ( $this->update_status( $review_id, $status, $notes ) ) {
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %s: Status */
					__( 'Review %s.', 'ytrip' ),
					$status
				),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to update review.', 'ytrip' ) ) );
	}

	// =========================================================================
	// Admin
	// =========================================================================

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=ytrip_tour',
			__( 'Reviews', 'ytrip' ),
			__( 'Reviews', 'ytrip' ),
			'manage_options',
			'ytrip-reviews',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin reviews page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		global $wpdb;

		// Get filter.
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 20;
		$offset = ( $paged - 1 ) * $per_page;

		// Build query.
		$where = '1=1';
		$values = array();

		if ( $status_filter && in_array( $status_filter, array( 'pending', 'approved', 'rejected' ), true ) ) {
			$where .= ' AND status = %s';
			$values[] = $status_filter;
		}

		$total = $wpdb->get_var(
			$values
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE {$where}", ...$values )
				: "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE {$where}"
		);

		$reviews = $wpdb->get_results(
			$values
				? $wpdb->prepare(
					"SELECT r.*, p.post_title as tour_title 
					FROM {$this->get_table_name()} r 
					LEFT JOIN {$wpdb->posts} p ON r.tour_id = p.ID 
					WHERE {$where} 
					ORDER BY r.created_at DESC 
					LIMIT %d OFFSET %d",
					...array_merge( $values, array( $per_page, $offset ) )
				)
				: $wpdb->prepare(
					"SELECT r.*, p.post_title as tour_title 
					FROM {$this->get_table_name()} r 
					LEFT JOIN {$wpdb->posts} p ON r.tour_id = p.ID 
					WHERE {$where} 
					ORDER BY r.created_at DESC 
					LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
		);

		// Count by status.
		$counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$this->get_table_name()} GROUP BY status",
			OBJECT_K
		);

		include YTRIP_PATH . 'admin/views/reviews-admin.php';
	}

	/**
	 * Notify admin of new review.
	 *
	 * @param int $review_id Review ID.
	 * @return void
	 */
	private function notify_admin_new_review( int $review_id ) {
		$review = $this->get_review( $review_id );
		if ( ! $review ) {
			return;
		}

		$tour = get_post( $review->tour_id );
		$user = get_userdata( $review->user_id );

		$subject = sprintf(
			/* translators: %s: Tour title */
			__( '[YTrip] New review submitted for %s', 'ytrip' ),
			$tour ? $tour->post_title : __( 'Unknown Tour', 'ytrip' )
		);

		$message = sprintf(
			/* translators: 1: User name, 2: Tour title, 3: Rating, 4: Admin URL */
			__(
				"A new review has been submitted:\n\n" .
				"User: %1\$s\n" .
				"Tour: %2\$s\n" .
				"Rating: %3\$s/5\n\n" .
				"Review and moderate: %4\$s",
				'ytrip'
			),
			$user ? $user->display_name : __( 'Unknown', 'ytrip' ),
			$tour ? $tour->post_title : __( 'Unknown Tour', 'ytrip' ),
			$review->overall_rating,
			admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews' )
		);

		wp_mail( get_option( 'admin_email' ), $subject, $message );
	}

	// =========================================================================
	// REST API
	// =========================================================================

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'ytrip/v1', '/tours/(?P<tour_id>\d+)/reviews', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_reviews' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'tour_id'  => array( 'required' => true, 'type' => 'integer' ),
					'page'     => array( 'type' => 'integer', 'default' => 1 ),
					'per_page' => array( 'type' => 'integer', 'default' => 10 ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_create_review' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'tour_id'        => array( 'required' => true, 'type' => 'integer' ),
					'overall_rating' => array( 'required' => true, 'type' => 'number', 'minimum' => 1, 'maximum' => 5 ),
					'review_text'    => array( 'type' => 'string' ),
				),
			),
		) );

		register_rest_route( 'ytrip/v1', '/tours/(?P<tour_id>\d+)/rating', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'rest_get_rating' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * REST permission check.
	 *
	 * @return bool
	 */
	public function rest_permission_check() {
		return is_user_logged_in();
	}

	/**
	 * REST: Get reviews for a tour.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_reviews( WP_REST_Request $request ): WP_REST_Response {
		$tour_id = (int) $request->get_param( 'tour_id' );
		$result = $this->get_tour_reviews( $tour_id, array(
			'page'     => (int) $request->get_param( 'page' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
		) );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * REST: Create a review.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_create_review( WP_REST_Request $request ) {
		$data = array(
			'tour_id'        => (int) $request->get_param( 'tour_id' ),
			'user_id'        => get_current_user_id(),
			'overall_rating' => (float) $request->get_param( 'overall_rating' ),
			'service_rating' => $request->get_param( 'service_rating' ),
			'value_rating'   => $request->get_param( 'value_rating' ),
			'guide_rating'   => $request->get_param( 'guide_rating' ),
			'review_title'   => $request->get_param( 'review_title' ),
			'review_text'    => $request->get_param( 'review_text' ),
		);

		$order_id = $this->has_purchased_tour( $data['tour_id'], $data['user_id'] );
		if ( $order_id ) {
			$data['order_id'] = $order_id;
		}

		$result = $this->add_review( $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array(
			'success'   => true,
			'review_id' => $result,
			'message'   => __( 'Review submitted successfully.', 'ytrip' ),
		), 201 );
	}

	/**
	 * REST: Get rating summary.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_rating( WP_REST_Request $request ): WP_REST_Response {
		$tour_id = (int) $request->get_param( 'tour_id' );
		$rating = $this->get_tour_rating( $tour_id );

		return new WP_REST_Response( $rating, 200 );
	}

	// =========================================================================
	// Schema Integration
	// =========================================================================

	/**
	 * Add rating to Schema.org data.
	 *
	 * @param array $schema  Existing schema data.
	 * @param int   $tour_id Tour ID.
	 * @return array Modified schema.
	 */
	public function add_rating_to_schema( array $schema, int $tour_id ) {
		$rating = $this->get_tour_rating( $tour_id );

		if ( $rating['count'] > 0 ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $rating['average'],
				'reviewCount' => $rating['count'],
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		return $schema;
	}

	// =========================================================================
	// Shortcode
	// =========================================================================

	/**
	 * Render reviews shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_reviews_shortcode( array $atts = array() ) {
		$atts = shortcode_atts( array(
			'tour_id'  => get_the_ID(),
			'per_page' => 10,
		), $atts, 'ytrip_reviews' );

		$tour_id = absint( $atts['tour_id'] );
		if ( ! $tour_id ) {
			return '';
		}

		ob_start();
		$this->render_reviews( $tour_id, absint( $atts['per_page'] ) );
		return ob_get_clean();
	}

	/**
	 * Render reviews HTML.
	 *
	 * @param int $tour_id  Tour ID.
	 * @param int $per_page Reviews per page.
	 * @return void
	 */
	public function render_reviews( int $tour_id, int $per_page = 10 ) {
		$rating = $this->get_tour_rating( $tour_id );
		$reviews_data = $this->get_tour_reviews( $tour_id, array( 'per_page' => $per_page ) );
		$can_review = $this->can_review( $tour_id, get_current_user_id() );

		include YTRIP_PATH . 'templates/parts/reviews-list.php';
	}
}

// Initialize.
YTrip_Reviews::instance();
