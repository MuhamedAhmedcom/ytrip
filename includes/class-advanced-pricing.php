<?php
/**
 * YTrip Advanced Pricing Class
 *
 * Handles dynamic pricing features including:
 * - Early Bird Discounts
 * - Last Minute Deals
 * - Seasonal Pricing
 * - Group Discounts
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Pricing Class
 */
class YTrip_Advanced_Pricing {

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
		// Filter tour price.
		add_filter( 'ytrip_tour_price', array( $this, 'apply_dynamic_pricing' ), 10, 3 );
		add_filter( 'ytrip_display_price', array( $this, 'get_display_price' ), 10, 2 );

		// Admin meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_pricing_meta_box' ) );
		add_action( 'save_post_ytrip_tour', array( $this, 'save_pricing_meta' ), 10, 2 );

		// Add pricing badge to tour cards.
		add_action( 'ytrip_card_badges', array( $this, 'render_pricing_badge' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	// =========================================================================
	// Dynamic Pricing Logic
	// =========================================================================

	/**
	 * Apply dynamic pricing based on tour date and booking time.
	 *
	 * @param float  $price     Original price.
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date (Y-m-d format).
	 * @return float Modified price.
	 */
	public function apply_dynamic_pricing( float $price, int $tour_id, string $tour_date = '' ) {
		if ( empty( $tour_date ) ) {
			$tour_date = isset( $_POST['tour_date'] ) ? sanitize_text_field( $_POST['tour_date'] ) : '';
		}

		$discount = $this->calculate_discount( $tour_id, $tour_date );

		if ( $discount > 0 ) {
			$price = $price * ( 1 - ( $discount / 100 ) );
		}

		return round( $price, 2 );
	}

	/**
	 * Calculate total discount percentage.
	 *
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date.
	 * @return float Discount percentage.
	 */
	public function calculate_discount( int $tour_id, string $tour_date = '' ) {
		$total_discount = 0.0;

		// Early Bird Discount.
		$early_bird = $this->get_early_bird_discount( $tour_id, $tour_date );
		$total_discount += $early_bird;

		// Last Minute Discount.
		$last_minute = $this->get_last_minute_discount( $tour_id, $tour_date );
		$total_discount += $last_minute;

		// Seasonal Discount.
		$seasonal = $this->get_seasonal_discount( $tour_id, $tour_date );
		$total_discount += $seasonal;

		// Cap maximum discount.
		$max_discount = (float) get_post_meta( $tour_id, '_ytrip_max_discount', true );
		if ( $max_discount <= 0 ) {
			$max_discount = 50; // Default max 50%.
		}

		return min( $total_discount, $max_discount );
	}

	/**
	 * Get Early Bird discount.
	 *
	 * Early Bird applies when booking is made X days before tour date.
	 *
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date.
	 * @return float Discount percentage.
	 */
	public function get_early_bird_discount( int $tour_id, string $tour_date ) {
		$enabled = get_post_meta( $tour_id, '_ytrip_early_bird_enabled', true );
		if ( $enabled !== 'yes' ) {
			return 0.0;
		}

		if ( empty( $tour_date ) ) {
			return 0.0;
		}

		$days_before = (int) get_post_meta( $tour_id, '_ytrip_early_bird_days', true );
		$discount = (float) get_post_meta( $tour_id, '_ytrip_early_bird_discount', true );

		if ( $days_before <= 0 || $discount <= 0 ) {
			return 0.0;
		}

		$tour_timestamp = strtotime( $tour_date );
		$now_timestamp = strtotime( 'today' );
		$days_until_tour = floor( ( $tour_timestamp - $now_timestamp ) / DAY_IN_SECONDS );

		if ( $days_until_tour >= $days_before ) {
			return $discount;
		}

		return 0.0;
	}

	/**
	 * Get Last Minute discount.
	 *
	 * Last Minute applies when tour is within X days from now.
	 *
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date.
	 * @return float Discount percentage.
	 */
	public function get_last_minute_discount( int $tour_id, string $tour_date ) {
		$enabled = get_post_meta( $tour_id, '_ytrip_last_minute_enabled', true );
		if ( $enabled !== 'yes' ) {
			return 0.0;
		}

		if ( empty( $tour_date ) ) {
			return 0.0;
		}

		$days_before = (int) get_post_meta( $tour_id, '_ytrip_last_minute_days', true );
		$discount = (float) get_post_meta( $tour_id, '_ytrip_last_minute_discount', true );
		$min_spots = (int) get_post_meta( $tour_id, '_ytrip_last_minute_min_spots', true );

		if ( $days_before <= 0 || $discount <= 0 ) {
			return 0.0;
		}

		$tour_timestamp = strtotime( $tour_date );
		$now_timestamp = strtotime( 'today' );
		$days_until_tour = floor( ( $tour_timestamp - $now_timestamp ) / DAY_IN_SECONDS );

		// Check if within last minute period and tour is in the future.
		if ( $days_until_tour >= 0 && $days_until_tour <= $days_before ) {
			// Check minimum available spots if set.
			if ( $min_spots > 0 ) {
				$available = $this->get_available_spots( $tour_id, $tour_date );
				if ( $available < $min_spots ) {
					return 0.0; // Not enough spots, no last minute deal.
				}
			}
			return $discount;
		}

		return 0.0;
	}

	/**
	 * Get Seasonal discount.
	 *
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date.
	 * @return float Discount percentage.
	 */
	public function get_seasonal_discount( int $tour_id, string $tour_date ) {
		$seasonal_rules = get_post_meta( $tour_id, '_ytrip_seasonal_pricing', true );
		
		if ( empty( $seasonal_rules ) || ! is_array( $seasonal_rules ) ) {
			return 0.0;
		}

		$check_date = ! empty( $tour_date ) ? $tour_date : gmdate( 'Y-m-d' );

		foreach ( $seasonal_rules as $rule ) {
			if ( empty( $rule['start_date'] ) || empty( $rule['end_date'] ) ) {
				continue;
			}

			if ( $check_date >= $rule['start_date'] && $check_date <= $rule['end_date'] ) {
				// Apply seasonal discount (can be positive for discount or negative for surcharge).
				return isset( $rule['discount'] ) ? (float) $rule['discount'] : 0.0;
			}
		}

		return 0.0;
	}

	/**
	 * Get available spots for a tour on a specific date.
	 *
	 * @param int    $tour_id Tour ID.
	 * @param string $date    Date.
	 * @return int Available spots.
	 */
	private function get_available_spots( int $tour_id, string $date ) {
		$max_capacity = (int) get_post_meta( $tour_id, '_ytrip_max_capacity', true );
		$booked = (int) get_post_meta( $tour_id, '_ytrip_booked_' . str_replace( '-', '', $date ), true );

		if ( $max_capacity <= 0 ) {
			$max_capacity = 999; // Unlimited.
		}

		return max( 0, $max_capacity - $booked );
	}

	// =========================================================================
	// Display Methods
	// =========================================================================

	/**
	 * Get display price with any applicable discounts.
	 *
	 * @param array $price_data Price data array.
	 * @param int   $tour_id    Tour ID.
	 * @return array Modified price data with discount info.
	 */
	public function get_display_price( array $price_data, int $tour_id ) {
		$original_price = (float) get_post_meta( $tour_id, '_ytrip_price', true );
		
		// Get best available discount.
		$discount_info = $this->get_best_discount_info( $tour_id );

		if ( $discount_info['discount'] > 0 ) {
			$price_data['has_discount'] = true;
			$price_data['original_price'] = $original_price;
			$price_data['discount_percent'] = $discount_info['discount'];
			$price_data['discount_type'] = $discount_info['type'];
			$price_data['discount_label'] = $discount_info['label'];
			$price_data['final_price'] = round( $original_price * ( 1 - $discount_info['discount'] / 100 ), 2 );
		} else {
			$price_data['has_discount'] = false;
			$price_data['final_price'] = $original_price;
		}

		return $price_data;
	}

	/**
	 * Get best available discount info.
	 *
	 * @param int $tour_id Tour ID.
	 * @return array Discount info.
	 */
	public function get_best_discount_info( int $tour_id ) {
		$discounts = array();

		// Check Early Bird (for upcoming dates).
		$early_bird_enabled = get_post_meta( $tour_id, '_ytrip_early_bird_enabled', true );
		if ( $early_bird_enabled === 'yes' ) {
			$discount = (float) get_post_meta( $tour_id, '_ytrip_early_bird_discount', true );
			$days = (int) get_post_meta( $tour_id, '_ytrip_early_bird_days', true );
			if ( $discount > 0 ) {
				$discounts[] = array(
					'type'     => 'early_bird',
					'discount' => $discount,
					'label'    => sprintf( __( 'Early Bird -%d%%', 'ytrip' ), $discount ),
					'priority' => 1,
				);
			}
		}

		// Check Last Minute.
		$last_minute_enabled = get_post_meta( $tour_id, '_ytrip_last_minute_enabled', true );
		if ( $last_minute_enabled === 'yes' ) {
			$discount = (float) get_post_meta( $tour_id, '_ytrip_last_minute_discount', true );
			if ( $discount > 0 ) {
				$discounts[] = array(
					'type'     => 'last_minute',
					'discount' => $discount,
					'label'    => sprintf( __( 'Last Minute -%d%%', 'ytrip' ), $discount ),
					'priority' => 2,
				);
			}
		}

		if ( empty( $discounts ) ) {
			return array(
				'discount' => 0,
				'type'     => '',
				'label'    => '',
			);
		}

		// Return highest priority discount.
		usort( $discounts, function( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		} );

		return $discounts[0];
	}

	/**
	 * Render pricing badge on tour card.
	 *
	 * @param int $tour_id Tour ID.
	 * @return void
	 */
	public function render_pricing_badge( int $tour_id ) {
		$discount_info = $this->get_best_discount_info( $tour_id );

		if ( $discount_info['discount'] <= 0 ) {
			return;
		}

		$badge_class = 'ytrip-badge ytrip-badge--discount';
		if ( $discount_info['type'] === 'early_bird' ) {
			$badge_class .= ' ytrip-badge--early-bird';
		} elseif ( $discount_info['type'] === 'last_minute' ) {
			$badge_class .= ' ytrip-badge--last-minute';
		}

		?>
		<span class="<?php echo esc_attr( $badge_class ); ?>">
			<?php echo esc_html( $discount_info['label'] ); ?>
		</span>
		<?php
	}

	// =========================================================================
	// Admin Meta Box
	// =========================================================================

	/**
	 * Add pricing meta box.
	 *
	 * @return void
	 */
	public function add_pricing_meta_box() {
		add_meta_box(
			'ytrip_advanced_pricing',
			__( 'Advanced Pricing', 'ytrip' ),
			array( $this, 'render_pricing_meta_box' ),
			'ytrip_tour',
			'normal',
			'default'
		);
	}

	/**
	 * Render pricing meta box.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_pricing_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'ytrip_pricing_meta', 'ytrip_pricing_nonce' );

		$early_bird_enabled = get_post_meta( $post->ID, '_ytrip_early_bird_enabled', true );
		$early_bird_days = get_post_meta( $post->ID, '_ytrip_early_bird_days', true ) ?: 30;
		$early_bird_discount = get_post_meta( $post->ID, '_ytrip_early_bird_discount', true ) ?: 10;

		$last_minute_enabled = get_post_meta( $post->ID, '_ytrip_last_minute_enabled', true );
		$last_minute_days = get_post_meta( $post->ID, '_ytrip_last_minute_days', true ) ?: 7;
		$last_minute_discount = get_post_meta( $post->ID, '_ytrip_last_minute_discount', true ) ?: 15;
		$last_minute_min_spots = get_post_meta( $post->ID, '_ytrip_last_minute_min_spots', true ) ?: 3;

		$max_discount = get_post_meta( $post->ID, '_ytrip_max_discount', true ) ?: 50;
		?>
		<style>
			.ytrip-pricing-section {
				padding: 15px;
				border: 1px solid #ddd;
				border-radius: 4px;
				margin-bottom: 15px;
				background: #fafafa;
			}
			.ytrip-pricing-section h4 {
				margin: 0 0 10px;
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.ytrip-pricing-section h4 input {
				margin: 0;
			}
			.ytrip-pricing-fields {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 15px;
				margin-top: 15px;
			}
			.ytrip-pricing-field label {
				display: block;
				font-weight: 600;
				margin-bottom: 5px;
			}
			.ytrip-pricing-field input {
				width: 100%;
			}
			.ytrip-pricing-field small {
				color: #666;
				display: block;
				margin-top: 3px;
			}
		</style>

		<!-- Early Bird -->
		<div class="ytrip-pricing-section">
			<h4>
				<input type="checkbox" 
				       name="ytrip_early_bird_enabled" 
				       id="ytrip_early_bird_enabled" 
				       value="yes"
				       <?php checked( $early_bird_enabled, 'yes' ); ?>>
				<label for="ytrip_early_bird_enabled">
					<?php esc_html_e( 'Early Bird Discount', 'ytrip' ); ?>
				</label>
			</h4>
			<p class="description">
				<?php esc_html_e( 'Reward customers who book in advance with a discount.', 'ytrip' ); ?>
			</p>
			<div class="ytrip-pricing-fields">
				<div class="ytrip-pricing-field">
					<label for="ytrip_early_bird_days"><?php esc_html_e( 'Days Before Tour', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_early_bird_days" 
					       id="ytrip_early_bird_days" 
					       value="<?php echo esc_attr( $early_bird_days ); ?>"
					       min="1" max="365">
					<small><?php esc_html_e( 'Booking must be made this many days before tour.', 'ytrip' ); ?></small>
				</div>
				<div class="ytrip-pricing-field">
					<label for="ytrip_early_bird_discount"><?php esc_html_e( 'Discount %', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_early_bird_discount" 
					       id="ytrip_early_bird_discount" 
					       value="<?php echo esc_attr( $early_bird_discount ); ?>"
					       min="1" max="100" step="0.1">
				</div>
			</div>
		</div>

		<!-- Last Minute -->
		<div class="ytrip-pricing-section">
			<h4>
				<input type="checkbox" 
				       name="ytrip_last_minute_enabled" 
				       id="ytrip_last_minute_enabled" 
				       value="yes"
				       <?php checked( $last_minute_enabled, 'yes' ); ?>>
				<label for="ytrip_last_minute_enabled">
					<?php esc_html_e( 'Last Minute Deals', 'ytrip' ); ?>
				</label>
			</h4>
			<p class="description">
				<?php esc_html_e( 'Offer discounts for tours departing soon to fill remaining spots.', 'ytrip' ); ?>
			</p>
			<div class="ytrip-pricing-fields">
				<div class="ytrip-pricing-field">
					<label for="ytrip_last_minute_days"><?php esc_html_e( 'Days Until Tour', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_last_minute_days" 
					       id="ytrip_last_minute_days" 
					       value="<?php echo esc_attr( $last_minute_days ); ?>"
					       min="1" max="30">
					<small><?php esc_html_e( 'Discount applies when tour is within this many days.', 'ytrip' ); ?></small>
				</div>
				<div class="ytrip-pricing-field">
					<label for="ytrip_last_minute_discount"><?php esc_html_e( 'Discount %', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_last_minute_discount" 
					       id="ytrip_last_minute_discount" 
					       value="<?php echo esc_attr( $last_minute_discount ); ?>"
					       min="1" max="100" step="0.1">
				</div>
				<div class="ytrip-pricing-field">
					<label for="ytrip_last_minute_min_spots"><?php esc_html_e( 'Min Available Spots', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_last_minute_min_spots" 
					       id="ytrip_last_minute_min_spots" 
					       value="<?php echo esc_attr( $last_minute_min_spots ); ?>"
					       min="0" max="100">
					<small><?php esc_html_e( 'Only apply if this many spots are still available. 0 = always apply.', 'ytrip' ); ?></small>
				</div>
			</div>
		</div>

		<!-- Settings -->
		<div class="ytrip-pricing-section">
			<h4><?php esc_html_e( 'Discount Settings', 'ytrip' ); ?></h4>
			<div class="ytrip-pricing-fields">
				<div class="ytrip-pricing-field">
					<label for="ytrip_max_discount"><?php esc_html_e( 'Maximum Combined Discount %', 'ytrip' ); ?></label>
					<input type="number" 
					       name="ytrip_max_discount" 
					       id="ytrip_max_discount" 
					       value="<?php echo esc_attr( $max_discount ); ?>"
					       min="1" max="100">
					<small><?php esc_html_e( 'Cap total discount to prevent excessive reductions.', 'ytrip' ); ?></small>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save pricing meta.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_pricing_meta( int $post_id, \WP_Post $post ) {
		if ( ! isset( $_POST['ytrip_pricing_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ytrip_pricing_nonce'] ) ), 'ytrip_pricing_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Early Bird.
		update_post_meta( $post_id, '_ytrip_early_bird_enabled', 
			isset( $_POST['ytrip_early_bird_enabled'] ) ? 'yes' : 'no' );
		
		if ( isset( $_POST['ytrip_early_bird_days'] ) ) {
			update_post_meta( $post_id, '_ytrip_early_bird_days', absint( $_POST['ytrip_early_bird_days'] ) );
		}
		
		if ( isset( $_POST['ytrip_early_bird_discount'] ) ) {
			update_post_meta( $post_id, '_ytrip_early_bird_discount', floatval( $_POST['ytrip_early_bird_discount'] ) );
		}

		// Last Minute.
		update_post_meta( $post_id, '_ytrip_last_minute_enabled', 
			isset( $_POST['ytrip_last_minute_enabled'] ) ? 'yes' : 'no' );
		
		if ( isset( $_POST['ytrip_last_minute_days'] ) ) {
			update_post_meta( $post_id, '_ytrip_last_minute_days', absint( $_POST['ytrip_last_minute_days'] ) );
		}
		
		if ( isset( $_POST['ytrip_last_minute_discount'] ) ) {
			update_post_meta( $post_id, '_ytrip_last_minute_discount', floatval( $_POST['ytrip_last_minute_discount'] ) );
		}
		
		if ( isset( $_POST['ytrip_last_minute_min_spots'] ) ) {
			update_post_meta( $post_id, '_ytrip_last_minute_min_spots', absint( $_POST['ytrip_last_minute_min_spots'] ) );
		}

		// Max discount.
		if ( isset( $_POST['ytrip_max_discount'] ) ) {
			update_post_meta( $post_id, '_ytrip_max_discount', absint( $_POST['ytrip_max_discount'] ) );
		}
	}

	// =========================================================================
	// REST API
	// =========================================================================

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route( 'ytrip/v1', '/pricing/calculate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_calculate_price' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'tour_id'   => array(
					'required' => true,
					'type'     => 'integer',
				),
				'tour_date' => array(
					'required' => true,
					'type'     => 'string',
				),
				'guests'    => array(
					'type'    => 'integer',
					'default' => 1,
				),
			),
		) );
	}

	/**
	 * REST API: Calculate price.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response Response.
	 */
	public function rest_calculate_price( \WP_REST_Request $request ): \WP_REST_Response {
		$tour_id = (int) $request->get_param( 'tour_id' );
		$tour_date = sanitize_text_field( $request->get_param( 'tour_date' ) );
		$guests = (int) $request->get_param( 'guests' );

		if ( ! get_post( $tour_id ) ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'message' => __( 'Tour not found.', 'ytrip' ),
			), 404 );
		}

		$original_price = (float) get_post_meta( $tour_id, '_ytrip_price', true );
		$discount = $this->calculate_discount( $tour_id, $tour_date );
		$final_price = $this->apply_dynamic_pricing( $original_price, $tour_id, $tour_date );

		return new \WP_REST_Response( array(
			'success'        => true,
			'original_price' => $original_price,
			'discount'       => $discount,
			'final_price'    => $final_price,
			'total'          => $final_price * max( 1, $guests ),
			'guests'         => $guests,
			'discount_info'  => $this->get_discount_breakdown( $tour_id, $tour_date ),
		), 200 );
	}

	/**
	 * Get discount breakdown.
	 *
	 * @param int    $tour_id   Tour ID.
	 * @param string $tour_date Tour date.
	 * @return array Breakdown.
	 */
	public function get_discount_breakdown( int $tour_id, string $tour_date ) {
		return array(
			'early_bird'  => $this->get_early_bird_discount( $tour_id, $tour_date ),
			'last_minute' => $this->get_last_minute_discount( $tour_id, $tour_date ),
			'seasonal'    => $this->get_seasonal_discount( $tour_id, $tour_date ),
		);
	}
}

// Initialize.
YTrip_Advanced_Pricing::instance();
