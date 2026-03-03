<?php
/**
 * YTrip Wishlist System
 *
 * Handles user wishlists with:
 * - User meta storage for logged-in users
 * - Cookie storage for guests
 * - Merge on login
 * - AJAX handlers
 * - REST API endpoints
 *
 * Logged-in users: list is stored in user meta; they see it in the Wishlist tab
 * of the page that uses the [ytrip_dashboard] shortcode.
 * Guests: list is stored in a cookie (no separate page); they get toast feedback
 * when adding/removing; optional "Saved (X)" drawer shows cookie-based list.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wishlist Class
 */
class YTrip_Wishlist {

	/**
	 * Cookie name for guest wishlist.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'ytrip_wishlist';

	/**
	 * User meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_ytrip_wishlist';

	/**
	 * Cookie expiration in days.
	 *
	 * @var int
	 */
	const COOKIE_EXPIRY_DAYS = 30;

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
		// AJAX handlers.
		add_action( 'wp_ajax_ytrip_add_to_wishlist', array( $this, 'ajax_add_to_wishlist' ) );
		add_action( 'wp_ajax_nopriv_ytrip_add_to_wishlist', array( $this, 'ajax_add_to_wishlist' ) );
		add_action( 'wp_ajax_ytrip_remove_from_wishlist', array( $this, 'ajax_remove_from_wishlist' ) );
		add_action( 'wp_ajax_nopriv_ytrip_remove_from_wishlist', array( $this, 'ajax_remove_from_wishlist' ) );
		add_action( 'wp_ajax_ytrip_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );
		add_action( 'wp_ajax_nopriv_ytrip_toggle_wishlist', array( $this, 'ajax_toggle_wishlist' ) );

		// Merge on login.
		add_action( 'wp_login', array( $this, 'merge_on_login' ), 10, 2 );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Guest wishlist drawer (trigger + panel in footer).
		add_action( 'wp_footer', array( $this, 'render_guest_wishlist_drawer' ), 20 );
	}

	// =========================================================================
	// Core Methods
	// =========================================================================

	/**
	 * Add tour to wishlist.
	 *
	 * @param int      $tour_id Tour ID.
	 * @param int|null $user_id User ID (null for guest).
	 * @return bool Success.
	 */
	public function add_to_wishlist( int $tour_id, ?int $user_id = null ) {
		// Validate tour exists.
		if ( ! get_post( $tour_id ) || get_post_type( $tour_id ) !== 'ytrip_tour' ) {
			return false;
		}

		if ( $user_id ) {
			return $this->add_to_user_wishlist( $tour_id, $user_id );
		}

		return $this->add_to_cookie_wishlist( $tour_id );
	}

	/**
	 * Remove tour from wishlist.
	 *
	 * @param int      $tour_id Tour ID.
	 * @param int|null $user_id User ID (null for guest).
	 * @return bool Success.
	 */
	public function remove_from_wishlist( int $tour_id, ?int $user_id = null ) {
		if ( $user_id ) {
			return $this->remove_from_user_wishlist( $tour_id, $user_id );
		}

		return $this->remove_from_cookie_wishlist( $tour_id );
	}

	/**
	 * Toggle tour in wishlist.
	 *
	 * @param int      $tour_id Tour ID.
	 * @param int|null $user_id User ID (null for guest).
	 * @return array Result with 'added' boolean.
	 */
	public function toggle_wishlist( int $tour_id, ?int $user_id = null ) {
		if ( $this->is_in_wishlist( $tour_id, $user_id ) ) {
			$this->remove_from_wishlist( $tour_id, $user_id );
			return array( 'added' => false );
		}

		$this->add_to_wishlist( $tour_id, $user_id );
		return array( 'added' => true );
	}

	/**
	 * Check if tour is in wishlist.
	 *
	 * @param int      $tour_id Tour ID.
	 * @param int|null $user_id User ID (null for guest).
	 * @return bool In wishlist.
	 */
	public function is_in_wishlist( int $tour_id, ?int $user_id = null ) {
		$wishlist = $this->get_wishlist( $user_id );
		return in_array( $tour_id, $wishlist, true );
	}

	/**
	 * Get wishlist.
	 *
	 * @param int|null $user_id User ID (null for guest).
	 * @return array Tour IDs.
	 */
	public function get_wishlist( ?int $user_id = null ) {
		if ( $user_id ) {
			return $this->get_user_wishlist( $user_id );
		}

		return $this->get_cookie_wishlist();
	}

	/**
	 * Get wishlist count.
	 *
	 * @param int|null $user_id User ID (null for guest).
	 * @return int Count.
	 */
	public function get_wishlist_count( ?int $user_id = null ) {
		return count( $this->get_wishlist( $user_id ) );
	}

	/**
	 * Clear wishlist.
	 *
	 * @param int|null $user_id User ID (null for guest).
	 * @return bool Success.
	 */
	public function clear_wishlist( ?int $user_id = null ) {
		if ( $user_id ) {
			return delete_user_meta( $user_id, self::META_KEY );
		}

		return $this->set_cookie_wishlist( array() );
	}

	// =========================================================================
	// User Wishlist (Logged In)
	// =========================================================================

	/**
	 * Get user wishlist.
	 *
	 * @param int $user_id User ID.
	 * @return array Tour IDs.
	 */
	private function get_user_wishlist( int $user_id ) {
		$wishlist = get_user_meta( $user_id, self::META_KEY, true );
		return is_array( $wishlist ) ? array_map( 'absint', $wishlist ) : array();
	}

	/**
	 * Save user wishlist.
	 *
	 * @param array $wishlist Tour IDs.
	 * @param int   $user_id  User ID.
	 * @return bool Success.
	 */
	private function save_user_wishlist( array $wishlist, int $user_id ) {
		$wishlist = array_values( array_unique( array_map( 'absint', $wishlist ) ) );
		return (bool) update_user_meta( $user_id, self::META_KEY, $wishlist );
	}

	/**
	 * Add to user wishlist.
	 *
	 * @param int $tour_id Tour ID.
	 * @param int $user_id User ID.
	 * @return bool Success.
	 */
	private function add_to_user_wishlist( int $tour_id, int $user_id ) {
		$wishlist = $this->get_user_wishlist( $user_id );

		if ( in_array( $tour_id, $wishlist, true ) ) {
			return true; // Already in wishlist.
		}

		$wishlist[] = $tour_id;
		return $this->save_user_wishlist( $wishlist, $user_id );
	}

	/**
	 * Remove from user wishlist.
	 *
	 * @param int $tour_id Tour ID.
	 * @param int $user_id User ID.
	 * @return bool Success.
	 */
	private function remove_from_user_wishlist( int $tour_id, int $user_id ) {
		$wishlist = $this->get_user_wishlist( $user_id );
		$wishlist = array_diff( $wishlist, array( $tour_id ) );
		return $this->save_user_wishlist( $wishlist, $user_id );
	}

	// =========================================================================
	// Cookie Wishlist (Guest)
	// =========================================================================

	/**
	 * Get cookie wishlist.
	 *
	 * @return array Tour IDs.
	 */
	private function get_cookie_wishlist() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return array();
		}

		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$wishlist = json_decode( $cookie, true );

		return is_array( $wishlist ) ? array_map( 'absint', $wishlist ) : array();
	}

	/**
	 * Set cookie wishlist.
	 *
	 * @param array $wishlist Tour IDs.
	 * @return bool Success.
	 */
	private function set_cookie_wishlist( array $wishlist ) {
		$wishlist = array_values( array_unique( array_map( 'absint', $wishlist ) ) );
		$expiry = time() + ( self::COOKIE_EXPIRY_DAYS * DAY_IN_SECONDS );

		// Cannot set cookie if headers already sent.
		if ( headers_sent() ) {
			return false;
		}

		setcookie(
			self::COOKIE_NAME,
			wp_json_encode( $wishlist ),
			$expiry,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		// Also set for immediate use in same request.
		$_COOKIE[ self::COOKIE_NAME ] = wp_json_encode( $wishlist );

		return true;
	}

	/**
	 * Add to cookie wishlist.
	 *
	 * @param int $tour_id Tour ID.
	 * @return bool Success.
	 */
	private function add_to_cookie_wishlist( int $tour_id ) {
		$wishlist = $this->get_cookie_wishlist();

		if ( in_array( $tour_id, $wishlist, true ) ) {
			return true;
		}

		$wishlist[] = $tour_id;
		return $this->set_cookie_wishlist( $wishlist );
	}

	/**
	 * Remove from cookie wishlist.
	 *
	 * @param int $tour_id Tour ID.
	 * @return bool Success.
	 */
	private function remove_from_cookie_wishlist( int $tour_id ) {
		$wishlist = $this->get_cookie_wishlist();
		$wishlist = array_diff( $wishlist, array( $tour_id ) );
		return $this->set_cookie_wishlist( $wishlist );
	}

	// =========================================================================
	// Merge on Login
	// =========================================================================

	/**
	 * Merge guest wishlist to user wishlist on login.
	 *
	 * @param string   $user_login User login.
	 * @param \WP_User $user       User object.
	 * @return void
	 */
	public function merge_on_login( string $user_login, \WP_User $user ) {
		$guest_wishlist = $this->get_cookie_wishlist();

		if ( empty( $guest_wishlist ) ) {
			return;
		}

		$user_wishlist = $this->get_user_wishlist( $user->ID );
		$merged = array_unique( array_merge( $user_wishlist, $guest_wishlist ) );

		$this->save_user_wishlist( $merged, $user->ID );

		// Clear guest cookie.
		$this->set_cookie_wishlist( array() );
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX: Add to wishlist.
	 *
	 * @return void
	 */
	public function ajax_add_to_wishlist() {
		if ( ! check_ajax_referer( 'ytrip_wishlist_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;

		if ( ! $tour_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tour.', 'ytrip' ) ) );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : null;
		$result = $this->add_to_wishlist( $tour_id, $user_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Added to wishlist!', 'ytrip' ),
				'count'   => $this->get_wishlist_count( $user_id ),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to add to wishlist.', 'ytrip' ) ) );
	}

	/**
	 * AJAX: Remove from wishlist.
	 *
	 * @return void
	 */
	public function ajax_remove_from_wishlist() {
		if ( ! check_ajax_referer( 'ytrip_wishlist_nonce', 'security', false ) ) {
			// Fallback to dashboard nonce.
			if ( ! check_ajax_referer( 'ytrip_dashboard_nonce', 'security', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
			}
		}

		$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;

		if ( ! $tour_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tour.', 'ytrip' ) ) );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : null;
		$result = $this->remove_from_wishlist( $tour_id, $user_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Removed from wishlist.', 'ytrip' ),
				'count'   => $this->get_wishlist_count( $user_id ),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to remove from wishlist.', 'ytrip' ) ) );
	}

	/**
	 * AJAX: Toggle wishlist.
	 *
	 * @return void
	 */
	public function ajax_toggle_wishlist() {
		if ( ! check_ajax_referer( 'ytrip_wishlist_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;

		if ( ! $tour_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tour.', 'ytrip' ) ) );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : null;
		$result = $this->toggle_wishlist( $tour_id, $user_id );

		wp_send_json_success( array(
			'added'   => $result['added'],
			'message' => $result['added']
				? __( 'Added to wishlist!', 'ytrip' )
				: __( 'Removed from wishlist.', 'ytrip' ),
			'count'   => $this->get_wishlist_count( $user_id ),
		) );
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
		register_rest_route( 'ytrip/v1', '/wishlist', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_wishlist' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_add_to_wishlist' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'tour_id' => array( 'required' => true, 'type' => 'integer' ),
				),
			),
		) );

		register_rest_route( 'ytrip/v1', '/wishlist/(?P<tour_id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'rest_remove_from_wishlist' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * REST: Get wishlist.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_wishlist( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = is_user_logged_in() ? get_current_user_id() : null;
		$wishlist = $this->get_wishlist( $user_id );

		// Get tour data.
		$tours = array();
		foreach ( $wishlist as $tour_id ) {
			$tour = get_post( $tour_id );
			if ( $tour && 'publish' === $tour->post_status ) {
				$tours[] = array(
					'id'        => $tour_id,
					'title'     => $tour->post_title,
					'url'       => get_permalink( $tour_id ),
					'thumbnail' => get_the_post_thumbnail_url( $tour_id, 'medium' ),
					'price'     => get_post_meta( $tour_id, '_ytrip_base_price', true ),
				);
			}
		}

		return new \WP_REST_Response( array(
			'count' => count( $tours ),
			'tours' => $tours,
		), 200 );
	}

	/**
	 * REST: Add to wishlist.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_add_to_wishlist( \WP_REST_Request $request ) {
		$tour_id = (int) $request->get_param( 'tour_id' );
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		$result = $this->add_to_wishlist( $tour_id, $user_id );

		if ( $result ) {
			return new \WP_REST_Response( array(
				'success' => true,
				'count'   => $this->get_wishlist_count( $user_id ),
			), 200 );
		}

		return new \WP_Error( 'add_failed', __( 'Failed to add to wishlist.', 'ytrip' ), array( 'status' => 400 ) );
	}

	/**
	 * REST: Remove from wishlist.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_remove_from_wishlist( \WP_REST_Request $request ) {
		$tour_id = (int) $request->get_param( 'tour_id' );
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		$result = $this->remove_from_wishlist( $tour_id, $user_id );

		if ( $result ) {
			return new \WP_REST_Response( array(
				'success' => true,
				'count'   => $this->get_wishlist_count( $user_id ),
			), 200 );
		}

		return new \WP_Error( 'remove_failed', __( 'Failed to remove from wishlist.', 'ytrip' ), array( 'status' => 400 ) );
	}

	// =========================================================================
	// Scripts
	// =========================================================================

	/**
	 * Enqueue wishlist scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$load = is_singular( 'ytrip_tour' )
			|| is_post_type_archive( 'ytrip_tour' )
			|| is_tax( 'ytrip_destination' )
			|| is_tax( 'ytrip_category' )
			|| is_front_page()
			|| is_home();
		if ( ! $load && is_page() && get_post() && has_shortcode( get_post()->post_content, 'ytrip_dashboard' ) ) {
			$load = true;
		}
		if ( ! $load ) {
			return;
		}

		wp_enqueue_script(
			'ytrip-wishlist',
			YTRIP_URL . 'assets/js/wishlist.js',
			array( 'jquery' ),
			YTRIP_VERSION,
			true
		);

		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		wp_localize_script( 'ytrip-wishlist', 'ytripWishlist', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'restUrl'  => rest_url( 'ytrip/v1/wishlist' ),
			'nonce'    => wp_create_nonce( 'ytrip_wishlist_nonce' ),
			'wishlist' => $this->get_wishlist( $user_id ),
			'isGuest'  => ! is_user_logged_in(),
			'strings'  => array(
				'added'     => __( 'Added to wishlist!', 'ytrip' ),
				'removed'   => __( 'Removed from wishlist.', 'ytrip' ),
				'error'     => __( 'An error occurred.', 'ytrip' ),
				'saved'     => __( 'Saved', 'ytrip' ),
				'savedTours' => __( 'Saved tours', 'ytrip' ),
				'empty'     => __( 'No saved tours yet.', 'ytrip' ),
			),
		) );
	}

	/**
	 * Output guest wishlist drawer trigger and panel (only when not logged in and on tour-related pages).
	 *
	 * @return void
	 */
	public function render_guest_wishlist_drawer() {
		if ( is_user_logged_in() ) {
			return;
		}
		$load = is_singular( 'ytrip_tour' )
			|| is_post_type_archive( 'ytrip_tour' )
			|| is_tax( 'ytrip_destination' )
			|| is_tax( 'ytrip_category' )
			|| is_front_page()
			|| is_home();
		if ( ! $load && is_page() && get_post() && has_shortcode( get_post()->post_content, 'ytrip_dashboard' ) ) {
			$load = true;
		}
		if ( ! $load || ! wp_script_is( 'ytrip-wishlist', 'enqueued' ) ) {
			return;
		}
		$count = $this->get_wishlist_count( null );
		?>
		<div id="ytrip-guest-wishlist-drawer" class="ytrip-guest-wishlist" aria-hidden="true">
			<button type="button" class="ytrip-guest-wishlist-trigger" aria-label="<?php esc_attr_e( 'View saved tours', 'ytrip' ); ?>">
				<span class="ytrip-guest-wishlist-trigger__icon">&#9829;</span>
				<span class="ytrip-guest-wishlist-trigger__label"><?php esc_html_e( 'Saved', 'ytrip' ); ?></span>
				<span class="ytrip-wishlist-count ytrip-guest-wishlist-count<?php echo $count > 0 ? ' has-items' : ''; ?>"><?php echo (int) $count; ?></span>
			</button>
			<div class="ytrip-guest-wishlist-drawer" role="dialog" aria-label="<?php esc_attr_e( 'Saved tours', 'ytrip' ); ?>">
				<div class="ytrip-guest-wishlist-drawer__header">
					<h3 class="ytrip-guest-wishlist-drawer__title"><?php esc_html_e( 'Saved tours', 'ytrip' ); ?></h3>
					<button type="button" class="ytrip-guest-wishlist-drawer__close" aria-label="<?php esc_attr_e( 'Close', 'ytrip' ); ?>">&times;</button>
				</div>
				<div class="ytrip-guest-wishlist-drawer__body">
					<div class="ytrip-guest-wishlist-drawer__list"></div>
					<p class="ytrip-guest-wishlist-drawer__empty" style="display:none;"><?php esc_html_e( 'No saved tours yet.', 'ytrip' ); ?></p>
				</div>
			</div>
			<div class="ytrip-guest-wishlist-backdrop" aria-hidden="true"></div>
		</div>
		<?php
	}
}

// Initialize.
YTrip_Wishlist::instance();
