<?php
/**
 * YTrip User Dashboard
 *
 * Provides frontend dashboard for logged-in users to:
 * - View booking history
 * - See upcoming tours
 * - Manage wishlist
 * - Download tickets
 * - Manage reviews
 * - Edit profile
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Dashboard Class
 */
class YTrip_User_Dashboard {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Available dashboard tabs.
	 *
	 * @var array
	 */
	private $tabs = array();

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
		$this->init_tabs();
		$this->init_hooks();
	}

	/**
	 * Initialize dashboard tabs.
	 *
	 * @return void
	 */
	private function init_tabs() {
		$this->tabs = array(
			'bookings' => array(
				'title'    => __( 'My Bookings', 'ytrip' ),
				'icon'     => 'calendar',
				'callback' => array( $this, 'render_bookings_tab' ),
			),
			'upcoming' => array(
				'title'    => __( 'Upcoming Tours', 'ytrip' ),
				'icon'     => 'map-pin',
				'callback' => array( $this, 'render_upcoming_tab' ),
			),
			'wishlist' => array(
				'title'    => __( 'Wishlist', 'ytrip' ),
				'icon'     => 'heart',
				'callback' => array( $this, 'render_wishlist_tab' ),
			),
			'reviews'  => array(
				'title'    => __( 'My Reviews', 'ytrip' ),
				'icon'     => 'star',
				'callback' => array( $this, 'render_reviews_tab' ),
			),
			'tickets'  => array(
				'title'    => __( 'Tickets', 'ytrip' ),
				'icon'     => 'ticket',
				'callback' => array( $this, 'render_tickets_tab' ),
			),
			'profile'  => array(
				'title'    => __( 'Profile', 'ytrip' ),
				'icon'     => 'user',
				'callback' => array( $this, 'render_profile_tab' ),
			),
		);

		$this->tabs = apply_filters( 'ytrip_dashboard_tabs', $this->tabs );
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Shortcode.
		add_shortcode( 'ytrip_dashboard', array( $this, 'render_dashboard_shortcode' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ytrip_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
		add_action( 'wp_ajax_ytrip_update_profile', array( $this, 'ajax_update_profile' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	// =========================================================================
	// Main Dashboard
	// =========================================================================

	/**
	 * Render dashboard shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_dashboard_shortcode( array $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			return $this->render_login_message();
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'bookings';
		if ( ! isset( $this->tabs[ $current_tab ] ) ) {
			$current_tab = 'bookings';
		}

		ob_start();
		?>
		<div class="ytrip-dashboard">
			<!-- Dashboard Header -->
			<div class="ytrip-dashboard-header">
				<div class="ytrip-dashboard-user">
					<?php echo get_avatar( get_current_user_id(), 80 ); ?>
					<div class="ytrip-dashboard-user-info">
						<h2><?php echo esc_html( wp_get_current_user()->display_name ); ?></h2>
						<span class="ytrip-dashboard-user-since">
							<?php
							printf(
								/* translators: %s: Date user registered */
								esc_html__( 'Member since %s', 'ytrip' ),
								esc_html( date_i18n( 'F Y', strtotime( wp_get_current_user()->user_registered ) ) )
							);
							?>
						</span>
					</div>
				</div>
				<div class="ytrip-dashboard-stats">
					<?php $this->render_dashboard_stats(); ?>
				</div>
			</div>

			<!-- Dashboard Navigation -->
			<nav class="ytrip-dashboard-nav">
				<ul class="ytrip-dashboard-tabs">
					<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
						<li class="ytrip-dashboard-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>">
								<?php echo wp_kses_post( $this->get_icon( $tab['icon'] ) ); ?>
								<span><?php echo esc_html( $tab['title'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<!-- Dashboard Content -->
			<div class="ytrip-dashboard-content">
				<?php
				if ( isset( $this->tabs[ $current_tab ]['callback'] ) && is_callable( $this->tabs[ $current_tab ]['callback'] ) ) {
					call_user_func( $this->tabs[ $current_tab ]['callback'] );
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render login message for non-authenticated users.
	 *
	 * @return string HTML output.
	 */
	private function render_login_message() {
		ob_start();
		?>
		<div class="ytrip-dashboard-login">
			<div class="ytrip-dashboard-login-box">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
				<h2><?php esc_html_e( 'Please Log In', 'ytrip' ); ?></h2>
				<p><?php esc_html_e( 'You need to be logged in to view your dashboard.', 'ytrip' ); ?></p>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="ytrip-btn ytrip-btn-primary">
					<?php esc_html_e( 'Log In', 'ytrip' ); ?>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render dashboard statistics.
	 *
	 * @return void
	 */
	private function render_dashboard_stats() {
		$user_id = get_current_user_id();
		$stats = $this->get_user_stats( $user_id );
		?>
		<div class="ytrip-stat">
			<span class="ytrip-stat-value"><?php echo esc_html( $stats['total_bookings'] ); ?></span>
			<span class="ytrip-stat-label"><?php esc_html_e( 'Total Bookings', 'ytrip' ); ?></span>
		</div>
		<div class="ytrip-stat">
			<span class="ytrip-stat-value"><?php echo esc_html( $stats['upcoming_tours'] ); ?></span>
			<span class="ytrip-stat-label"><?php esc_html_e( 'Upcoming Tours', 'ytrip' ); ?></span>
		</div>
		<div class="ytrip-stat">
			<span class="ytrip-stat-value"><?php echo esc_html( $stats['reviews'] ); ?></span>
			<span class="ytrip-stat-label"><?php esc_html_e( 'Reviews', 'ytrip' ); ?></span>
		</div>
		<div class="ytrip-stat">
			<span class="ytrip-stat-value"><?php echo esc_html( $stats['wishlist'] ); ?></span>
			<span class="ytrip-stat-label"><?php esc_html_e( 'Wishlist', 'ytrip' ); ?></span>
		</div>
		<?php
	}

	/**
	 * Get user statistics.
	 *
	 * @param int $user_id User ID.
	 * @return array Statistics.
	 */
	private function get_user_stats( int $user_id ) {
		global $wpdb;

		$cache_key = 'ytrip_user_stats_' . $user_id;
		$stats = wp_cache_get( $cache_key, 'ytrip_dashboard' );

		if ( false !== $stats ) {
			return $stats;
		}

		// Total bookings.
		$total_bookings = 0;
		$upcoming_tours = 0;

		if ( function_exists( 'wc_get_orders' ) ) {
			$orders = wc_get_orders( array(
				'customer_id' => $user_id,
				'status'      => array( 'completed', 'processing', 'on-hold' ),
				'limit'       => -1,
				'return'      => 'ids',
			) );

			$total_bookings = count( $orders );

			// Count upcoming.
			foreach ( $orders as $order_id ) {
				$tour_date = get_post_meta( $order_id, '_ytrip_tour_date', true );
				if ( $tour_date && strtotime( $tour_date ) > time() ) {
					$upcoming_tours++;
				}
			}
		}

		// Reviews count.
		$reviews = 0;
		$ratings_table = $wpdb->prefix . 'ytrip_ratings';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $ratings_table ) ) === $ratings_table ) {
			$reviews = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$ratings_table} WHERE user_id = %d",
					$user_id
				)
			);
		}

		// Wishlist count.
		$wishlist = get_user_meta( $user_id, '_ytrip_wishlist', true );
		$wishlist_count = is_array( $wishlist ) ? count( $wishlist ) : 0;

		$stats = array(
			'total_bookings' => $total_bookings,
			'upcoming_tours' => $upcoming_tours,
			'reviews'        => $reviews,
			'wishlist'       => $wishlist_count,
		);

		wp_cache_set( $cache_key, $stats, 'ytrip_dashboard', HOUR_IN_SECONDS );

		return $stats;
	}

	// =========================================================================
	// Bookings Tab
	// =========================================================================

	/**
	 * Render bookings tab.
	 *
	 * @return void
	 */
	public function render_bookings_tab() {
		$bookings = $this->get_user_bookings( get_current_user_id() );
		?>
		<div class="ytrip-dashboard-bookings">
			<h3><?php esc_html_e( 'All Bookings', 'ytrip' ); ?></h3>
			
			<?php if ( empty( $bookings ) ) : ?>
				<div class="ytrip-empty-state">
					<?php echo wp_kses_post( $this->get_icon( 'calendar', 48 ) ); ?>
					<p><?php esc_html_e( 'You haven\'t made any bookings yet.', 'ytrip' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>" class="ytrip-btn ytrip-btn-primary">
						<?php esc_html_e( 'Browse Tours', 'ytrip' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="ytrip-bookings-list">
					<?php foreach ( $bookings as $booking ) : ?>
						<?php $this->render_booking_card( $booking ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get user bookings.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Bookings.
	 */
	public function get_user_bookings( int $user_id, array $args = array() ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$defaults = array(
			'status'   => array( 'completed', 'processing', 'on-hold', 'pending', 'cancelled', 'refunded' ),
			'limit'    => 20,
			'page'     => 1,
			'upcoming' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => $args['status'],
			'limit'       => $args['limit'],
			'paged'       => $args['page'],
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$bookings = array();

		foreach ( $orders as $order ) {
			$tour_id = get_post_meta( $order->get_id(), '_ytrip_tour_id', true );
			if ( ! $tour_id ) {
				continue;
			}

			$tour_date = get_post_meta( $order->get_id(), '_ytrip_tour_date', true );
			$tour_time = get_post_meta( $order->get_id(), '_ytrip_tour_time', true );
			$persons = get_post_meta( $order->get_id(), '_ytrip_persons', true );
			$ticket_code = get_post_meta( $order->get_id(), '_ytrip_ticket_code', true );

			// Filter upcoming if requested.
			if ( $args['upcoming'] && $tour_date && strtotime( $tour_date ) < time() ) {
				continue;
			}

			$tour = get_post( $tour_id );
			if ( ! $tour ) {
				continue;
			}

			$bookings[] = array(
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'order_status' => $order->get_status(),
				'order_date'   => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
				'order_total'  => $order->get_total(),
				'tour_id'      => $tour_id,
				'tour_title'   => $tour->post_title,
				'tour_image'   => get_the_post_thumbnail_url( $tour_id, 'medium' ),
				'tour_date'    => $tour_date,
				'tour_time'    => $tour_time,
				'persons'      => $persons,
				'ticket_code'  => $ticket_code,
				'is_upcoming'  => $tour_date && strtotime( $tour_date ) > time(),
				'is_past'      => $tour_date && strtotime( $tour_date ) < time(),
			);
		}

		return $bookings;
	}

	/**
	 * Render booking card.
	 *
	 * @param array $booking Booking data.
	 * @return void
	 */
	private function render_booking_card( array $booking ) {
		$status_labels = array(
			'completed'  => __( 'Completed', 'ytrip' ),
			'processing' => __( 'Confirmed', 'ytrip' ),
			'on-hold'    => __( 'On Hold', 'ytrip' ),
			'pending'    => __( 'Pending', 'ytrip' ),
			'cancelled'  => __( 'Cancelled', 'ytrip' ),
			'refunded'   => __( 'Refunded', 'ytrip' ),
		);
		?>
		<div class="ytrip-booking-card ytrip-booking-status-<?php echo esc_attr( $booking['order_status'] ); ?>">
			<div class="ytrip-booking-image">
				<?php if ( $booking['tour_image'] ) : ?>
					<img src="<?php echo esc_url( $booking['tour_image'] ); ?>" alt="<?php echo esc_attr( $booking['tour_title'] ); ?>">
				<?php else : ?>
					<div class="ytrip-booking-image-placeholder">
						<?php echo wp_kses_post( $this->get_icon( 'image', 32 ) ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $booking['is_upcoming'] ) : ?>
					<span class="ytrip-booking-badge ytrip-booking-upcoming"><?php esc_html_e( 'Upcoming', 'ytrip' ); ?></span>
				<?php endif; ?>
			</div>
			
			<div class="ytrip-booking-details">
				<h4 class="ytrip-booking-title">
					<a href="<?php echo esc_url( get_permalink( $booking['tour_id'] ) ); ?>">
						<?php echo esc_html( $booking['tour_title'] ); ?>
					</a>
				</h4>
				
				<div class="ytrip-booking-meta">
					<?php if ( $booking['tour_date'] ) : ?>
						<span class="ytrip-booking-date">
							<?php echo wp_kses_post( $this->get_icon( 'calendar', 14 ) ); ?>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['tour_date'] ) ) ); ?>
							<?php if ( $booking['tour_time'] ) : ?>
								<?php echo esc_html( ' - ' . $booking['tour_time'] ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					
					<?php if ( $booking['persons'] ) : ?>
						<span class="ytrip-booking-persons">
							<?php echo wp_kses_post( $this->get_icon( 'users', 14 ) ); ?>
							<?php
							$total_persons = 0;
							if ( is_array( $booking['persons'] ) ) {
								$total_persons = array_sum( $booking['persons'] );
							}
							printf(
								/* translators: %d: Number of guests */
								esc_html( _n( '%d Guest', '%d Guests', $total_persons, 'ytrip' ) ),
								$total_persons
							);
							?>
						</span>
					<?php endif; ?>
					
					<span class="ytrip-booking-order">
						<?php echo wp_kses_post( $this->get_icon( 'hash', 14 ) ); ?>
						<?php echo esc_html( $booking['order_number'] ); ?>
					</span>
				</div>
				
				<div class="ytrip-booking-footer">
					<span class="ytrip-booking-status ytrip-status-<?php echo esc_attr( $booking['order_status'] ); ?>">
						<?php echo esc_html( $status_labels[ $booking['order_status'] ] ?? ucfirst( $booking['order_status'] ) ); ?>
					</span>
					<span class="ytrip-booking-price">
						<?php echo wp_kses_post( wc_price( $booking['order_total'] ) ); ?>
					</span>
				</div>
			</div>
			
			<div class="ytrip-booking-actions">
				<?php if ( $booking['ticket_code'] && in_array( $booking['order_status'], array( 'completed', 'processing' ), true ) ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'ytrip_download_ticket' => $booking['order_id'], 'nonce' => wp_create_nonce( 'ytrip_ticket_' . $booking['order_id'] ) ), home_url() ) ); ?>" class="ytrip-btn ytrip-btn-small">
						<?php echo wp_kses_post( $this->get_icon( 'download', 14 ) ); ?>
						<?php esc_html_e( 'Ticket', 'ytrip' ); ?>
					</a>
				<?php endif; ?>
				
				<a href="<?php echo esc_url( get_permalink( $booking['tour_id'] ) ); ?>" class="ytrip-btn ytrip-btn-small ytrip-btn-outline">
					<?php esc_html_e( 'View Tour', 'ytrip' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Upcoming Tours Tab
	// =========================================================================

	/**
	 * Render upcoming tours tab.
	 *
	 * @return void
	 */
	public function render_upcoming_tab() {
		$bookings = $this->get_user_bookings( get_current_user_id(), array( 'upcoming' => true ) );
		?>
		<div class="ytrip-dashboard-upcoming">
			<h3><?php esc_html_e( 'Upcoming Tours', 'ytrip' ); ?></h3>
			
			<?php if ( empty( $bookings ) ) : ?>
				<div class="ytrip-empty-state">
					<?php echo wp_kses_post( $this->get_icon( 'map-pin', 48 ) ); ?>
					<p><?php esc_html_e( 'No upcoming tours at the moment.', 'ytrip' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>" class="ytrip-btn ytrip-btn-primary">
						<?php esc_html_e( 'Book a Tour', 'ytrip' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="ytrip-upcoming-grid">
					<?php foreach ( $bookings as $booking ) : ?>
						<?php $this->render_upcoming_card( $booking ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render upcoming tour card.
	 *
	 * @param array $booking Booking data.
	 * @return void
	 */
	private function render_upcoming_card( array $booking ) {
		$days_until = $booking['tour_date'] ? floor( ( strtotime( $booking['tour_date'] ) - time() ) / DAY_IN_SECONDS ) : 0;
		?>
		<div class="ytrip-upcoming-card">
			<div class="ytrip-upcoming-countdown">
				<span class="ytrip-countdown-value"><?php echo esc_html( max( 0, $days_until ) ); ?></span>
				<span class="ytrip-countdown-label"><?php esc_html_e( 'days left', 'ytrip' ); ?></span>
			</div>
			
			<div class="ytrip-upcoming-image">
				<?php if ( $booking['tour_image'] ) : ?>
					<img src="<?php echo esc_url( $booking['tour_image'] ); ?>" alt="<?php echo esc_attr( $booking['tour_title'] ); ?>">
				<?php endif; ?>
			</div>
			
			<div class="ytrip-upcoming-info">
				<h4><?php echo esc_html( $booking['tour_title'] ); ?></h4>
				<p class="ytrip-upcoming-date">
					<?php echo wp_kses_post( $this->get_icon( 'calendar', 14 ) ); ?>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['tour_date'] ) ) ); ?>
					<?php if ( $booking['tour_time'] ) : ?>
						<span class="ytrip-upcoming-time">
							<?php echo wp_kses_post( $this->get_icon( 'clock', 14 ) ); ?>
							<?php echo esc_html( $booking['tour_time'] ); ?>
						</span>
					<?php endif; ?>
				</p>
				
				<?php if ( $booking['ticket_code'] ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'ytrip_download_ticket' => $booking['order_id'], 'nonce' => wp_create_nonce( 'ytrip_ticket_' . $booking['order_id'] ) ), home_url() ) ); ?>" class="ytrip-btn ytrip-btn-primary">
						<?php echo wp_kses_post( $this->get_icon( 'download', 14 ) ); ?>
						<?php esc_html_e( 'Download Ticket', 'ytrip' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Wishlist Tab
	// =========================================================================

	/**
	 * Render wishlist tab.
	 *
	 * @return void
	 */
	public function render_wishlist_tab() {
		$wishlist = $this->get_user_wishlist( get_current_user_id() );
		?>
		<div class="ytrip-dashboard-wishlist">
			<h3><?php esc_html_e( 'My Wishlist', 'ytrip' ); ?></h3>
			
			<?php if ( empty( $wishlist ) ) : ?>
				<div class="ytrip-empty-state">
					<?php echo wp_kses_post( $this->get_icon( 'heart', 48 ) ); ?>
					<p><?php esc_html_e( 'Your wishlist is empty.', 'ytrip' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>" class="ytrip-btn ytrip-btn-primary">
						<?php esc_html_e( 'Explore Tours', 'ytrip' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="ytrip-wishlist-grid">
					<?php foreach ( $wishlist as $tour_id ) : ?>
						<?php $this->render_wishlist_card( $tour_id ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get user wishlist.
	 *
	 * @param int $user_id User ID.
	 * @return array Tour IDs.
	 */
	public function get_user_wishlist( int $user_id ) {
		$wishlist = get_user_meta( $user_id, '_ytrip_wishlist', true );
		return is_array( $wishlist ) ? array_filter( $wishlist ) : array();
	}

	/**
	 * Render wishlist card.
	 *
	 * @param int $tour_id Tour ID.
	 * @return void
	 */
	private function render_wishlist_card( int $tour_id ) {
		$tour = get_post( $tour_id );
		if ( ! $tour || 'publish' !== $tour->post_status ) {
			return;
		}

		$price = get_post_meta( $tour_id, '_ytrip_base_price', true );
		$rating_data = class_exists( 'YTrip_Reviews' ) ? YTrip_Reviews::instance()->get_tour_rating( $tour_id ) : array( 'average' => 0, 'count' => 0 );
		?>
		<div class="ytrip-wishlist-card" data-tour-id="<?php echo esc_attr( $tour_id ); ?>">
			<div class="ytrip-wishlist-image">
				<?php if ( has_post_thumbnail( $tour_id ) ) : ?>
					<a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>">
						<?php echo get_the_post_thumbnail( $tour_id, 'medium' ); ?>
					</a>
				<?php endif; ?>
				<button type="button" class="ytrip-wishlist-remove" data-tour-id="<?php echo esc_attr( $tour_id ); ?>" title="<?php esc_attr_e( 'Remove from wishlist', 'ytrip' ); ?>">
					<?php echo wp_kses_post( $this->get_icon( 'x', 16 ) ); ?>
				</button>
			</div>
			
			<div class="ytrip-wishlist-info">
				<h4>
					<a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>">
						<?php echo esc_html( $tour->post_title ); ?>
					</a>
				</h4>
				
				<?php if ( $rating_data['count'] > 0 ) : ?>
					<div class="ytrip-wishlist-rating">
						<span class="ytrip-stars">★</span>
						<span><?php echo esc_html( $rating_data['average'] ); ?></span>
						<span class="ytrip-wishlist-reviews-count">(<?php echo esc_html( $rating_data['count'] ); ?>)</span>
					</div>
				<?php endif; ?>
				
				<div class="ytrip-wishlist-footer">
					<?php if ( $price ) : ?>
						<span class="ytrip-wishlist-price">
							<?php
							printf(
								/* translators: %s: Price */
								esc_html__( 'From %s', 'ytrip' ),
								wp_kses_post( wc_price( $price ) )
							);
							?>
						</span>
					<?php endif; ?>
					<a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>" class="ytrip-btn ytrip-btn-small ytrip-btn-primary">
						<?php esc_html_e( 'Book Now', 'ytrip' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Reviews Tab
	// =========================================================================

	/**
	 * Render reviews tab.
	 *
	 * @return void
	 */
	public function render_reviews_tab() {
		$reviews = $this->get_user_reviews( get_current_user_id() );
		?>
		<div class="ytrip-dashboard-reviews">
			<h3><?php esc_html_e( 'My Reviews', 'ytrip' ); ?></h3>
			
			<?php if ( empty( $reviews ) ) : ?>
				<div class="ytrip-empty-state">
					<?php echo wp_kses_post( $this->get_icon( 'star', 48 ) ); ?>
					<p><?php esc_html_e( 'You haven\'t written any reviews yet.', 'ytrip' ); ?></p>
				</div>
			<?php else : ?>
				<div class="ytrip-reviews-list">
					<?php foreach ( $reviews as $review ) : ?>
						<?php $this->render_review_card( $review ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get user reviews.
	 *
	 * @param int $user_id User ID.
	 * @return array Reviews.
	 */
	private function get_user_reviews( int $user_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ytrip_ratings';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, p.post_title as tour_title
				FROM {$table} r
				LEFT JOIN {$wpdb->posts} p ON r.tour_id = p.ID
				WHERE r.user_id = %d
				ORDER BY r.created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Render review card.
	 *
	 * @param object $review Review data.
	 * @return void
	 */
	private function render_review_card( object $review ) {
		$status_labels = array(
			'pending'  => __( 'Pending', 'ytrip' ),
			'approved' => __( 'Published', 'ytrip' ),
			'rejected' => __( 'Rejected', 'ytrip' ),
		);
		?>
		<div class="ytrip-review-card ytrip-review-status-<?php echo esc_attr( $review->status ); ?>">
			<div class="ytrip-review-header">
				<h4>
					<a href="<?php echo esc_url( get_permalink( $review->tour_id ) ); ?>">
						<?php echo esc_html( $review->tour_title ); ?>
					</a>
				</h4>
				<span class="ytrip-review-status ytrip-status-<?php echo esc_attr( $review->status ); ?>">
					<?php echo esc_html( $status_labels[ $review->status ] ?? $review->status ); ?>
				</span>
			</div>
			
			<div class="ytrip-review-rating">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="ytrip-star <?php echo $i <= $review->overall_rating ? 'filled' : ''; ?>">★</span>
				<?php endfor; ?>
				<span class="ytrip-review-date">
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?>
				</span>
			</div>
			
			<?php if ( $review->review_title ) : ?>
				<h5 class="ytrip-review-title"><?php echo esc_html( $review->review_title ); ?></h5>
			<?php endif; ?>
			
			<?php if ( $review->review_text ) : ?>
				<p class="ytrip-review-content"><?php echo esc_html( $review->review_text ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Tickets Tab
	// =========================================================================

	/**
	 * Render tickets tab.
	 *
	 * @return void
	 */
	public function render_tickets_tab() {
		$bookings = $this->get_user_bookings( get_current_user_id(), array(
			'status' => array( 'completed', 'processing' ),
		) );

		$tickets = array_filter( $bookings, function( $b ) {
			return ! empty( $b['ticket_code'] );
		} );
		?>
		<div class="ytrip-dashboard-tickets">
			<h3><?php esc_html_e( 'My Tickets', 'ytrip' ); ?></h3>
			
			<?php if ( empty( $tickets ) ) : ?>
				<div class="ytrip-empty-state">
					<?php echo wp_kses_post( $this->get_icon( 'ticket', 48 ) ); ?>
					<p><?php esc_html_e( 'No tickets available yet.', 'ytrip' ); ?></p>
				</div>
			<?php else : ?>
				<div class="ytrip-tickets-list">
					<?php foreach ( $tickets as $ticket ) : ?>
						<?php $this->render_ticket_card( $ticket ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render ticket card.
	 *
	 * @param array $ticket Ticket/booking data.
	 * @return void
	 */
	private function render_ticket_card( array $ticket ) {
		?>
		<div class="ytrip-ticket-card <?php echo $ticket['is_past'] ? 'ytrip-ticket-past' : ''; ?>">
			<div class="ytrip-ticket-left">
				<div class="ytrip-ticket-tour">
					<h4><?php echo esc_html( $ticket['tour_title'] ); ?></h4>
					<p class="ytrip-ticket-date">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket['tour_date'] ) ) ); ?>
						<?php if ( $ticket['tour_time'] ) : ?>
							- <?php echo esc_html( $ticket['tour_time'] ); ?>
						<?php endif; ?>
					</p>
				</div>
				<div class="ytrip-ticket-code">
					<span class="ytrip-ticket-label"><?php esc_html_e( 'Ticket Code', 'ytrip' ); ?></span>
					<code><?php echo esc_html( $ticket['ticket_code'] ); ?></code>
				</div>
			</div>
			<div class="ytrip-ticket-right">
				<?php if ( $ticket['is_past'] ) : ?>
					<span class="ytrip-ticket-used"><?php esc_html_e( 'Past', 'ytrip' ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'ytrip_download_ticket' => $ticket['order_id'], 'nonce' => wp_create_nonce( 'ytrip_ticket_' . $ticket['order_id'] ) ), home_url() ) ); ?>" class="ytrip-btn ytrip-btn-primary">
						<?php echo wp_kses_post( $this->get_icon( 'download', 14 ) ); ?>
						<?php esc_html_e( 'Download', 'ytrip' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Profile Tab
	// =========================================================================

	/**
	 * Render profile tab.
	 *
	 * @return void
	 */
	public function render_profile_tab() {
		$user = wp_get_current_user();
		?>
		<div class="ytrip-dashboard-profile">
			<h3><?php esc_html_e( 'Profile Settings', 'ytrip' ); ?></h3>
			
			<form id="ytrip-profile-form" class="ytrip-profile-form">
				<?php wp_nonce_field( 'ytrip_update_profile', 'ytrip_profile_nonce' ); ?>
				
				<div class="ytrip-form-row">
					<div class="ytrip-form-group">
						<label for="ytrip-first-name"><?php esc_html_e( 'First Name', 'ytrip' ); ?></label>
						<input type="text" id="ytrip-first-name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" class="ytrip-input">
					</div>
					<div class="ytrip-form-group">
						<label for="ytrip-last-name"><?php esc_html_e( 'Last Name', 'ytrip' ); ?></label>
						<input type="text" id="ytrip-last-name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" class="ytrip-input">
					</div>
				</div>
				
				<div class="ytrip-form-group">
					<label for="ytrip-display-name"><?php esc_html_e( 'Display Name', 'ytrip' ); ?></label>
					<input type="text" id="ytrip-display-name" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" class="ytrip-input" required>
				</div>
				
				<div class="ytrip-form-group">
					<label for="ytrip-email"><?php esc_html_e( 'Email Address', 'ytrip' ); ?></label>
					<input type="email" id="ytrip-email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" class="ytrip-input" required>
				</div>
				
				<div class="ytrip-form-group">
					<label for="ytrip-phone"><?php esc_html_e( 'Phone Number', 'ytrip' ); ?></label>
					<input type="tel" id="ytrip-phone" name="phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ); ?>" class="ytrip-input">
				</div>
				
				<hr>
				
				<h4><?php esc_html_e( 'Change Password', 'ytrip' ); ?></h4>
				<p class="ytrip-form-hint"><?php esc_html_e( 'Leave blank to keep your current password.', 'ytrip' ); ?></p>
				
				<div class="ytrip-form-group">
					<label for="ytrip-current-password"><?php esc_html_e( 'Current Password', 'ytrip' ); ?></label>
					<input type="password" id="ytrip-current-password" name="current_password" class="ytrip-input" autocomplete="current-password">
				</div>
				
				<div class="ytrip-form-row">
					<div class="ytrip-form-group">
						<label for="ytrip-new-password"><?php esc_html_e( 'New Password', 'ytrip' ); ?></label>
						<input type="password" id="ytrip-new-password" name="new_password" class="ytrip-input" autocomplete="new-password">
					</div>
					<div class="ytrip-form-group">
						<label for="ytrip-confirm-password"><?php esc_html_e( 'Confirm New Password', 'ytrip' ); ?></label>
						<input type="password" id="ytrip-confirm-password" name="confirm_password" class="ytrip-input" autocomplete="new-password">
					</div>
				</div>
				
				<div class="ytrip-form-actions">
					<button type="submit" class="ytrip-btn ytrip-btn-primary">
						<?php esc_html_e( 'Save Changes', 'ytrip' ); ?>
					</button>
				</div>
				
				<div class="ytrip-profile-messages" id="ytrip-profile-messages"></div>
			</form>
		</div>
		<?php
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * Handle profile update via AJAX.
	 *
	 * @return void
	 */
	public function ajax_update_profile() {
		if ( ! check_ajax_referer( 'ytrip_update_profile', 'ytrip_profile_nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ytrip' ) ) );
		}

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );

		// Update basic info.
		$userdata = array(
			'ID'           => $user_id,
			'first_name'   => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'    => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'display_name' => isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '',
		);

		// Handle email change.
		$new_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( $new_email && $new_email !== $user->user_email ) {
			// Check if email is already in use.
			if ( email_exists( $new_email ) ) {
				wp_send_json_error( array( 'message' => __( 'This email is already in use.', 'ytrip' ) ) );
			}
			$userdata['user_email'] = $new_email;
		}

		// Handle password change.
		$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
		$new_password = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

		if ( ! empty( $new_password ) ) {
			// Verify current password.
			if ( empty( $current_password ) || ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Current password is incorrect.', 'ytrip' ) ) );
			}

			// Check password match.
			if ( $new_password !== $confirm_password ) {
				wp_send_json_error( array( 'message' => __( 'New passwords do not match.', 'ytrip' ) ) );
			}

			// Check password strength.
			if ( strlen( $new_password ) < 8 ) {
				wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'ytrip' ) ) );
			}

			$userdata['user_pass'] = $new_password;
		}

		// Update user.
		$result = wp_update_user( $userdata );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update phone.
		if ( isset( $_POST['phone'] ) ) {
			update_user_meta( $user_id, 'billing_phone', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Profile updated successfully.', 'ytrip' ) ) );
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
		register_rest_route( 'ytrip/v1', '/dashboard/bookings', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_bookings' ),
			'permission_callback' => array( $this, 'rest_permission_check' ),
		) );

		register_rest_route( 'ytrip/v1', '/dashboard/wishlist', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_wishlist' ),
			'permission_callback' => array( $this, 'rest_permission_check' ),
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
	 * REST: Get bookings.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_bookings( \WP_REST_Request $request ): \WP_REST_Response {
		$bookings = $this->get_user_bookings( get_current_user_id() );
		return new \WP_REST_Response( $bookings, 200 );
	}

	/**
	 * REST: Get wishlist.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_wishlist( \WP_REST_Request $request ): \WP_REST_Response {
		$wishlist = $this->get_user_wishlist( get_current_user_id() );
		return new \WP_REST_Response( $wishlist, 200 );
	}

	// =========================================================================
	// Asset Loading
	// =========================================================================

	/**
	 * Enqueue dashboard assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $post;

		// Only load on pages with dashboard shortcode.
		if ( ! $post || ! has_shortcode( $post->post_content, 'ytrip_dashboard' ) ) {
			return;
		}

		wp_enqueue_style(
			'ytrip-user-dashboard',
			YTRIP_URL . 'assets/css/user-dashboard.css',
			array(),
			YTRIP_VERSION
		);

		wp_enqueue_script(
			'ytrip-user-dashboard',
			YTRIP_URL . 'assets/js/user-dashboard.js',
			array( 'jquery' ),
			YTRIP_VERSION,
			true
		);

		wp_localize_script( 'ytrip-user-dashboard', 'ytripDashboard', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ytrip_dashboard_nonce' ),
			'strings' => array(
				'loading'     => __( 'Loading...', 'ytrip' ),
				'error'       => __( 'An error occurred.', 'ytrip' ),
				'confirm'     => __( 'Are you sure?', 'ytrip' ),
				'removeWish'  => __( 'Remove from wishlist?', 'ytrip' ),
			),
		) );
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Get SVG icon.
	 *
	 * @param string $name Icon name.
	 * @param int    $size Size in pixels.
	 * @return string SVG HTML.
	 */
	private function get_icon( string $name, int $size = 20 ) {
		$icons = array(
			'calendar'  => '<path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>',
			'map-pin'   => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
			'heart'     => '<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>',
			'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
			'ticket'    => '<path d="M2 9a3 3 0 003 3v6a2 2 0 002 2h10a2 2 0 002-2v-6a3 3 0 000-6V6a2 2 0 00-2-2H7a2 2 0 00-2 2v3a3 3 0 00-3 0z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
			'user'      => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'download'  => '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
			'users'     => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
			'hash'      => '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
			'image'     => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
			'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
			'x'         => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
		);

		if ( ! isset( $icons[ $name ] ) ) {
			return '';
		}

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
			$size,
			$size,
			$icons[ $name ]
		);
	}
}

// Initialize.
YTrip_User_Dashboard::instance();
