<?php
/**
 * YTrip Webhook System
 *
 * Provides webhook notifications for booking events, payments, and reviews.
 * Mobile apps and external systems can register to receive real-time updates.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook System Class
 */
class YTrip_Webhooks {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Option name for webhook settings.
	 *
	 * @var string
	 */
	private $option_name = 'ytrip_webhooks';

	/**
	 * Webhook events.
	 *
	 * @var array
	 */
	private $events = array(
		'booking.created'   => 'New booking created',
		'booking.confirmed' => 'Booking confirmed/paid',
		'booking.cancelled' => 'Booking cancelled',
		'booking.completed' => 'Tour completed',
		'review.created'    => 'New review submitted',
		'review.approved'   => 'Review approved',
		'tour.updated'      => 'Tour details updated',
		'availability.low'  => 'Low availability alert',
	);

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
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_webhooks_page' ), 30 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Hook into plugin events.
		add_action( 'ytrip_booking_created', array( $this, 'on_booking_created' ), 10, 2 );
		add_action( 'ytrip_booking_confirmed', array( $this, 'on_booking_confirmed' ), 10, 2 );
		add_action( 'ytrip_booking_cancelled', array( $this, 'on_booking_cancelled' ), 10, 2 );
		add_action( 'ytrip_review_created', array( $this, 'on_review_created' ), 10, 2 );
		add_action( 'ytrip_review_approved', array( $this, 'on_review_approved' ), 10, 2 );
		add_action( 'save_post_ytrip_tour', array( $this, 'on_tour_updated' ), 10, 3 );

		// WooCommerce integration.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ) );
	}

	/**
	 * Add webhooks settings page.
	 *
	 * @return void
	 */
	public function add_webhooks_page() {
		add_submenu_page(
			'ytrip',
			__( 'Webhooks', 'ytrip' ),
			__( 'Webhooks', 'ytrip' ),
			'manage_options',
			'ytrip-webhooks',
			array( $this, 'render_webhooks_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ytrip_webhooks_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_webhooks' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize webhook settings.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized webhooks.
	 */
	public function sanitize_webhooks( array $input ) {
		$sanitized = array();

		if ( isset( $input['endpoints'] ) && is_array( $input['endpoints'] ) ) {
			foreach ( $input['endpoints'] as $endpoint ) {
				if ( empty( $endpoint['url'] ) ) {
					continue;
				}

				$sanitized['endpoints'][] = array(
					'name'   => sanitize_text_field( $endpoint['name'] ?? '' ),
					'url'    => esc_url_raw( $endpoint['url'] ),
					'secret' => sanitize_text_field( $endpoint['secret'] ?? '' ),
					'events' => array_map( 'sanitize_text_field', $endpoint['events'] ?? array() ),
					'active' => ! empty( $endpoint['active'] ),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Get all registered webhooks.
	 *
	 * @return array
	 */
	public function get_webhooks() {
		$webhooks = get_option( $this->option_name, array() );
		return $webhooks['endpoints'] ?? array();
	}

	/**
	 * Dispatch webhook event.
	 *
	 * @param string $event Event name.
	 * @param array  $payload Event data.
	 * @return void
	 */
	public function dispatch( string $event, array $payload ) {
		$webhooks = $this->get_webhooks();

		foreach ( $webhooks as $webhook ) {
			if ( ! $webhook['active'] ) {
				continue;
			}

			if ( ! in_array( $event, $webhook['events'], true ) && ! in_array( '*', $webhook['events'], true ) ) {
				continue;
			}

			$this->send_webhook( $webhook, $event, $payload );
		}
	}

	/**
	 * Send webhook request.
	 *
	 * @param array  $webhook Webhook config.
	 * @param string $event Event name.
	 * @param array  $payload Event data.
	 * @return bool Success.
	 */
	private function send_webhook( array $webhook, string $event, array $payload ) {
		$body = array(
			'event'     => $event,
			'timestamp' => gmdate( 'c' ),
			'data'      => $payload,
		);

		$json_body = wp_json_encode( $body );

		// Generate signature.
		$signature = hash_hmac( 'sha256', $json_body, $webhook['secret'] );

		$response = wp_remote_post( $webhook['url'], array(
			'timeout'   => 15,
			'headers'   => array(
				'Content-Type'       => 'application/json',
				'X-YTrip-Event'      => $event,
				'X-YTrip-Signature'  => $signature,
				'X-YTrip-Timestamp'  => (string) time(),
				'User-Agent'         => 'YTrip-Webhook/1.2.0',
			),
			'body'      => $json_body,
			'sslverify' => true,
		) );

		$success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;

		// Log webhook delivery.
		$this->log_delivery( $webhook['name'], $event, $success, $response );

		return $success;
	}

	/**
	 * Log webhook delivery.
	 *
	 * @param string $name Webhook name.
	 * @param string $event Event.
	 * @param bool   $success Was successful.
	 * @param mixed  $response Response.
	 * @return void
	 */
	private function log_delivery( string $name, string $event, bool $success, $response ) {
		$logs = get_option( 'ytrip_webhook_logs', array() );

		$logs[] = array(
			'webhook'   => $name,
			'event'     => $event,
			'success'   => $success,
			'code'      => is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response ),
			'timestamp' => current_time( 'mysql' ),
		);

		// Keep only last 100 logs.
		$logs = array_slice( $logs, -100 );

		update_option( 'ytrip_webhook_logs', $logs );
	}

	// =========================================================================
	// Event Handlers
	// =========================================================================

	/**
	 * Handle booking created.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data Booking data.
	 * @return void
	 */
	public function on_booking_created( int $booking_id, array $data ) {
		$this->dispatch( 'booking.created', array(
			'booking_id'   => $booking_id,
			'tour_id'      => $data['tour_id'] ?? 0,
			'tour_title'   => get_the_title( $data['tour_id'] ?? 0 ),
			'customer'     => array(
				'name'  => $data['customer_name'] ?? '',
				'email' => $data['customer_email'] ?? '',
			),
			'date'         => $data['tour_date'] ?? '',
			'guests'       => $data['guests'] ?? 0,
			'total'        => $data['total'] ?? 0,
			'currency'     => get_woocommerce_currency(),
		) );
	}

	/**
	 * Handle booking confirmed.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data Booking data.
	 * @return void
	 */
	public function on_booking_confirmed( int $booking_id, array $data ) {
		$this->dispatch( 'booking.confirmed', array(
			'booking_id' => $booking_id,
			'order_id'   => $data['order_id'] ?? 0,
			'tour_id'    => $data['tour_id'] ?? 0,
			'tour_title' => get_the_title( $data['tour_id'] ?? 0 ),
			'date'       => $data['tour_date'] ?? '',
			'guests'     => $data['guests'] ?? 0,
			'total'      => $data['total'] ?? 0,
		) );
	}

	/**
	 * Handle booking cancelled.
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $data Booking data.
	 * @return void
	 */
	public function on_booking_cancelled( int $booking_id, array $data ) {
		$this->dispatch( 'booking.cancelled', array(
			'booking_id' => $booking_id,
			'tour_id'    => $data['tour_id'] ?? 0,
			'tour_title' => get_the_title( $data['tour_id'] ?? 0 ),
			'reason'     => $data['reason'] ?? '',
		) );
	}

	/**
	 * Handle review created.
	 *
	 * @param int   $review_id Review ID.
	 * @param array $data Review data.
	 * @return void
	 */
	public function on_review_created( int $review_id, array $data ) {
		$this->dispatch( 'review.created', array(
			'review_id'  => $review_id,
			'tour_id'    => $data['tour_id'] ?? 0,
			'tour_title' => get_the_title( $data['tour_id'] ?? 0 ),
			'rating'     => $data['rating'] ?? 0,
			'title'      => $data['title'] ?? '',
			'author'     => $data['author'] ?? '',
			'status'     => $data['status'] ?? 'pending',
		) );
	}

	/**
	 * Handle review approved.
	 *
	 * @param int   $review_id Review ID.
	 * @param array $data Review data.
	 * @return void
	 */
	public function on_review_approved( int $review_id, array $data ) {
		$this->dispatch( 'review.approved', array(
			'review_id'  => $review_id,
			'tour_id'    => $data['tour_id'] ?? 0,
			'rating'     => $data['rating'] ?? 0,
		) );
	}

	/**
	 * Handle tour updated.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param bool     $update Is update.
	 * @return void
	 */
	public function on_tour_updated( int $post_id, \WP_Post $post, bool $update ) {
		if ( ! $update || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->dispatch( 'tour.updated', array(
			'tour_id'    => $post_id,
			'tour_title' => $post->post_title,
			'url'        => get_permalink( $post_id ),
			'price'      => get_post_meta( $post_id, '_ytrip_price', true ),
			'status'     => $post->post_status,
		) );
	}

	/**
	 * Handle WooCommerce order completed.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_order_completed( int $order_id ) {
		$tour_id = get_post_meta( $order_id, '_ytrip_tour_id', true );
		if ( ! $tour_id ) {
			return;
		}

		$this->dispatch( 'booking.confirmed', array(
			'order_id'   => $order_id,
			'tour_id'    => $tour_id,
			'tour_title' => get_the_title( $tour_id ),
		) );
	}

	/**
	 * Handle WooCommerce order cancelled.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_order_cancelled( int $order_id ) {
		$tour_id = get_post_meta( $order_id, '_ytrip_tour_id', true );
		if ( ! $tour_id ) {
			return;
		}

		$this->dispatch( 'booking.cancelled', array(
			'order_id'   => $order_id,
			'tour_id'    => $tour_id,
			'tour_title' => get_the_title( $tour_id ),
			'reason'     => 'Order cancelled',
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
	public function register_rest_routes() {
		// Get webhooks (admin only).
		register_rest_route( 'ytrip/v1', '/webhooks', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_webhooks' ),
			'permission_callback' => array( $this, 'admin_permission_check' ),
		) );

		// Add webhook.
		register_rest_route( 'ytrip/v1', '/webhooks', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_add_webhook' ),
			'permission_callback' => array( $this, 'admin_permission_check' ),
		) );

		// Delete webhook.
		register_rest_route( 'ytrip/v1', '/webhooks/(?P<index>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'rest_delete_webhook' ),
			'permission_callback' => array( $this, 'admin_permission_check' ),
		) );

		// Test webhook.
		register_rest_route( 'ytrip/v1', '/webhooks/test', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_test_webhook' ),
			'permission_callback' => array( $this, 'admin_permission_check' ),
		) );

		// Get webhook logs.
		register_rest_route( 'ytrip/v1', '/webhooks/logs', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_logs' ),
			'permission_callback' => array( $this, 'admin_permission_check' ),
		) );
	}

	/**
	 * Admin permission check.
	 *
	 * @return bool
	 */
	public function admin_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * REST: Get webhooks.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_webhooks(): \WP_REST_Response {
		return new \WP_REST_Response( array(
			'webhooks' => $this->get_webhooks(),
			'events'   => $this->events,
		) );
	}

	/**
	 * REST: Add webhook.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_add_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$webhooks = get_option( $this->option_name, array( 'endpoints' => array() ) );

		$webhooks['endpoints'][] = array(
			'name'   => sanitize_text_field( $request->get_param( 'name' ) ),
			'url'    => esc_url_raw( $request->get_param( 'url' ) ),
			'secret' => wp_generate_password( 32, false ),
			'events' => array_map( 'sanitize_text_field', $request->get_param( 'events' ) ?? array( '*' ) ),
			'active' => true,
		);

		update_option( $this->option_name, $webhooks );

		return new \WP_REST_Response( array(
			'success'  => true,
			'webhooks' => $webhooks['endpoints'],
		) );
	}

	/**
	 * REST: Delete webhook.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_delete_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$index = absint( $request->get_param( 'index' ) );
		$webhooks = get_option( $this->option_name, array( 'endpoints' => array() ) );

		if ( isset( $webhooks['endpoints'][ $index ] ) ) {
			array_splice( $webhooks['endpoints'], $index, 1 );
			update_option( $this->option_name, $webhooks );
		}

		return new \WP_REST_Response( array(
			'success'  => true,
			'webhooks' => $webhooks['endpoints'],
		) );
	}

	/**
	 * REST: Test webhook.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_test_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$url = esc_url_raw( $request->get_param( 'url' ) );
		$secret = sanitize_text_field( $request->get_param( 'secret' ) ?? '' );

		$webhook = array(
			'name'   => 'Test',
			'url'    => $url,
			'secret' => $secret,
		);

		$success = $this->send_webhook( $webhook, 'test.ping', array(
			'message' => 'This is a test webhook from YTrip.',
			'site'    => get_bloginfo( 'name' ),
		) );

		return new \WP_REST_Response( array(
			'success' => $success,
			'message' => $success 
				? __( 'Webhook delivered successfully!', 'ytrip' ) 
				: __( 'Failed to deliver webhook.', 'ytrip' ),
		) );
	}

	/**
	 * REST: Get logs.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_logs(): \WP_REST_Response {
		$logs = get_option( 'ytrip_webhook_logs', array() );
		return new \WP_REST_Response( array_reverse( $logs ) );
	}

	// =========================================================================
	// Admin Page
	// =========================================================================

	/**
	 * Render webhooks admin page.
	 *
	 * @return void
	 */
	public function render_webhooks_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$webhooks = $this->get_webhooks();
		$logs = array_reverse( get_option( 'ytrip_webhook_logs', array() ) );
		?>
		<div class="wrap ytrip-webhooks-wrap">
			<h1><?php esc_html_e( 'Webhooks', 'ytrip' ); ?></h1>
			<p><?php esc_html_e( 'Webhooks allow external applications to receive real-time notifications when events occur.', 'ytrip' ); ?></p>

			<div class="ytrip-webhooks-grid">
				<div class="ytrip-webhooks-main">
					<h2><?php esc_html_e( 'Registered Webhooks', 'ytrip' ); ?></h2>
					
					<?php if ( empty( $webhooks ) ) : ?>
						<p class="description"><?php esc_html_e( 'No webhooks registered yet.', 'ytrip' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'ytrip' ); ?></th>
									<th><?php esc_html_e( 'URL', 'ytrip' ); ?></th>
									<th><?php esc_html_e( 'Events', 'ytrip' ); ?></th>
									<th><?php esc_html_e( 'Status', 'ytrip' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'ytrip' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $webhooks as $index => $webhook ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $webhook['name'] ); ?></strong></td>
									<td><code><?php echo esc_url( $webhook['url'] ); ?></code></td>
									<td><?php echo esc_html( implode( ', ', $webhook['events'] ) ); ?></td>
									<td>
										<?php if ( $webhook['active'] ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
										<?php else : ?>
											<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
										<?php endif; ?>
									</td>
									<td>
										<button type="button" class="button button-small ytrip-delete-webhook" data-index="<?php echo esc_attr( $index ); ?>">
											<?php esc_html_e( 'Delete', 'ytrip' ); ?>
										</button>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<h3><?php esc_html_e( 'Add New Webhook', 'ytrip' ); ?></h3>
					<form id="ytrip-add-webhook-form" class="ytrip-webhook-form">
						<table class="form-table">
							<tr>
								<th><label for="webhook-name"><?php esc_html_e( 'Name', 'ytrip' ); ?></label></th>
								<td><input type="text" id="webhook-name" class="regular-text" required></td>
							</tr>
							<tr>
								<th><label for="webhook-url"><?php esc_html_e( 'URL', 'ytrip' ); ?></label></th>
								<td><input type="url" id="webhook-url" class="regular-text" placeholder="https://" required></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Events', 'ytrip' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="events[]" value="*" checked>
											<?php esc_html_e( 'All events', 'ytrip' ); ?>
										</label><br>
										<?php foreach ( $this->events as $key => $label ) : ?>
										<label>
											<input type="checkbox" name="events[]" value="<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $label ); ?>
										</label><br>
										<?php endforeach; ?>
									</fieldset>
								</td>
							</tr>
						</table>
						<?php submit_button( __( 'Add Webhook', 'ytrip' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="ytrip-webhooks-sidebar">
					<h3><?php esc_html_e( 'Recent Deliveries', 'ytrip' ); ?></h3>
					<?php if ( empty( $logs ) ) : ?>
						<p class="description"><?php esc_html_e( 'No webhook deliveries yet.', 'ytrip' ); ?></p>
					<?php else : ?>
						<ul class="ytrip-webhook-logs">
							<?php foreach ( array_slice( $logs, 0, 20 ) as $log ) : ?>
							<li class="<?php echo $log['success'] ? 'success' : 'failed'; ?>">
								<strong><?php echo esc_html( $log['event'] ); ?></strong>
								<span class="log-status"><?php echo $log['success'] ? '✓' : '✗'; ?></span>
								<span class="log-time"><?php echo esc_html( $log['timestamp'] ); ?></span>
							</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<style>
			.ytrip-webhooks-grid { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }
			.ytrip-webhooks-main { background: #fff; padding: 20px; border: 1px solid #ccd0d4; }
			.ytrip-webhooks-sidebar { background: #fff; padding: 20px; border: 1px solid #ccd0d4; }
			.ytrip-webhook-logs { list-style: none; margin: 0; padding: 0; max-height: 400px; overflow-y: auto; }
			.ytrip-webhook-logs li { padding: 8px; border-bottom: 1px solid #eee; font-size: 12px; }
			.ytrip-webhook-logs .success .log-status { color: #46b450; }
			.ytrip-webhook-logs .failed .log-status { color: #dc3232; }
			.ytrip-webhook-logs .log-time { color: #666; display: block; }
			@media (max-width: 782px) { .ytrip-webhooks-grid { grid-template-columns: 1fr; } }
		</style>

		<script>
		jQuery(function($) {
			$('#ytrip-add-webhook-form').on('submit', function(e) {
				e.preventDefault();
				
				var events = [];
				$('input[name="events[]"]:checked').each(function() {
					events.push($(this).val());
				});
				
				$.post(ajaxurl, {
					action: 'ytrip_add_webhook',
					name: $('#webhook-name').val(),
					url: $('#webhook-url').val(),
					events: events,
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'ytrip_webhook_nonce' ) ); ?>'
				}, function(response) {
					if (response.success) {
						location.reload();
					}
				});
			});
			
			$('.ytrip-delete-webhook').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Delete this webhook?', 'ytrip' ) ); ?>')) return;
				
				$.post(ajaxurl, {
					action: 'ytrip_delete_webhook',
					index: $(this).data('index'),
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'ytrip_webhook_nonce' ) ); ?>'
				}, function() {
					location.reload();
				});
			});
		});
		</script>
		<?php
	}
}

// Initialize.
YTrip_Webhooks::instance();
