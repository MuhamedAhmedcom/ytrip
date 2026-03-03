<?php
/**
 * YTrip API Authentication
 *
 * Provides secure API key authentication for mobile apps and external integrations.
 * Supports multiple API keys with different permissions and rate limiting.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Authentication Class
 */
class YTrip_API_Auth {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'ytrip_api_keys';

	/**
	 * Rate limit window (seconds).
	 *
	 * @var int
	 */
	private $rate_limit_window = 3600;

	/**
	 * Rate limit requests per window.
	 *
	 * @var int
	 */
	private $rate_limit_requests = 1000;

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
		// Add API key authentication filter.
		add_filter( 'rest_authentication_errors', array( $this, 'authenticate_api_key' ), 99 );

		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_api_keys_page' ), 35 );

		// REST API for managing keys.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ytrip_create_api_key', array( $this, 'ajax_create_key' ) );
		add_action( 'wp_ajax_ytrip_revoke_api_key', array( $this, 'ajax_revoke_key' ) );
	}

	/**
	 * Authenticate API key from request.
	 *
	 * @param mixed $result Current authentication result.
	 * @return mixed|\WP_Error
	 */
	public function authenticate_api_key( $result ) {
		// Don't override existing authentication.
		if ( null !== $result ) {
			return $result;
		}

		// Only check YTrip API endpoints.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( strpos( $request_uri, '/wp-json/ytrip/' ) === false ) {
			return $result;
		}

		// Check for API key in header or query param.
		$api_key = $this->get_api_key_from_request();

		if ( ! $api_key ) {
			// Allow public endpoints without key.
			if ( $this->is_public_endpoint( $request_uri ) ) {
				return $result;
			}
			return $result; // Let WordPress default auth handle it.
		}

		// Validate API key.
		$key_data = $this->validate_api_key( $api_key );

		if ( ! $key_data ) {
			return new \WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'ytrip' ),
				array( 'status' => 401 )
			);
		}

		// Check rate limiting.
		if ( ! $this->check_rate_limit( $api_key ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'API rate limit exceeded. Please try again later.', 'ytrip' ),
				array( 'status' => 429 )
			);
		}

		// Check permissions.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( ! $this->check_permission( $key_data, $method, $request_uri ) ) {
			return new \WP_Error(
				'insufficient_permissions',
				__( 'API key does not have permission for this action.', 'ytrip' ),
				array( 'status' => 403 )
			);
		}

		// Log usage.
		$this->log_usage( $api_key, $request_uri, $method );

		// Set current user if key is linked to a user.
		if ( ! empty( $key_data['user_id'] ) ) {
			wp_set_current_user( $key_data['user_id'] );
		}

		return true;
	}

	/**
	 * Get API key from request.
	 *
	 * @return string|null
	 */
	private function get_api_key_from_request() {
		// Check Authorization header: Bearer <key>.
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) 
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) 
			: '';

		if ( preg_match( '/Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
			return $matches[1];
		}

		// Check X-API-Key header.
		$api_key_header = isset( $_SERVER['HTTP_X_API_KEY'] ) 
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_API_KEY'] ) ) 
			: '';

		if ( ! empty( $api_key_header ) ) {
			return $api_key_header;
		}

		// Check query parameter (less secure, for testing).
		if ( isset( $_GET['api_key'] ) ) {
			return sanitize_text_field( wp_unslash( $_GET['api_key'] ) );
		}

		return null;
	}

	/**
	 * Check if endpoint is public.
	 *
	 * @param string $uri Request URI.
	 * @return bool
	 */
	private function is_public_endpoint( string $uri ) {
		$public_patterns = array(
			'/ytrip/v1/tours$',
			'/ytrip/v1/tours/\d+$',
			'/ytrip/v1/tours/\d+/availability',
			'/ytrip/v1/destinations',
			'/ytrip/v1/categories',
			'/ytrip/v1/map/locations',
		);

		foreach ( $public_patterns as $pattern ) {
			if ( preg_match( '#' . $pattern . '#', $uri ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate API key.
	 *
	 * @param string $api_key API key.
	 * @return array|null Key data or null.
	 */
	public function validate_api_key( string $api_key ) {
		$keys = get_option( $this->option_name, array() );
		$key_hash = hash( 'sha256', $api_key );

		foreach ( $keys as $key_data ) {
			if ( $key_data['key_hash'] === $key_hash && $key_data['active'] ) {
				// Check expiration.
				if ( ! empty( $key_data['expires_at'] ) && strtotime( $key_data['expires_at'] ) < time() ) {
					return null;
				}
				return $key_data;
			}
		}

		return null;
	}

	/**
	 * Check rate limit.
	 *
	 * @param string $api_key API key.
	 * @return bool Within limit.
	 */
	private function check_rate_limit( string $api_key ) {
		$key_hash = hash( 'sha256', $api_key );
		$transient_key = 'ytrip_rate_' . substr( $key_hash, 0, 12 );

		$usage = get_transient( $transient_key );

		if ( false === $usage ) {
			set_transient( $transient_key, 1, $this->rate_limit_window );
			return true;
		}

		if ( $usage >= $this->rate_limit_requests ) {
			return false;
		}

		set_transient( $transient_key, $usage + 1, $this->rate_limit_window );
		return true;
	}

	/**
	 * Check permission for action.
	 *
	 * @param array  $key_data Key data.
	 * @param string $method HTTP method.
	 * @param string $uri Request URI.
	 * @return bool Has permission.
	 */
	private function check_permission( array $key_data, string $method, string $uri ) {
		$permissions = $key_data['permissions'] ?? array( 'read' );

		// Read-only key.
		if ( in_array( 'read', $permissions, true ) && in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
			return true;
		}

		// Write permission.
		if ( in_array( 'write', $permissions, true ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return true;
		}

		// Delete permission.
		if ( in_array( 'delete', $permissions, true ) && $method === 'DELETE' ) {
			return true;
		}

		// Full access.
		if ( in_array( 'full', $permissions, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Log API usage.
	 *
	 * @param string $api_key API key.
	 * @param string $uri Request URI.
	 * @param string $method HTTP method.
	 * @return void
	 */
	private function log_usage( string $api_key, string $uri, string $method ) {
		$key_hash = hash( 'sha256', $api_key );
		$keys = get_option( $this->option_name, array() );

		foreach ( $keys as $index => $key_data ) {
			if ( $key_data['key_hash'] === $key_hash ) {
				$keys[ $index ]['last_used'] = current_time( 'mysql' );
				$keys[ $index ]['usage_count'] = ( $keys[ $index ]['usage_count'] ?? 0 ) + 1;
				update_option( $this->option_name, $keys );
				break;
			}
		}
	}

	/**
	 * Generate new API key.
	 *
	 * @param array $args Key configuration.
	 * @return array Key data with plain key (shown only once).
	 */
	public function generate_api_key( array $args = array() ) {
		$plain_key = 'ytrip_' . wp_generate_password( 32, false );
		$key_hash = hash( 'sha256', $plain_key );

		$key_data = array(
			'key_hash'    => $key_hash,
			'key_prefix'  => substr( $plain_key, 0, 12 ) . '...',
			'name'        => sanitize_text_field( $args['name'] ?? __( 'API Key', 'ytrip' ) ),
			'permissions' => $args['permissions'] ?? array( 'read' ),
			'user_id'     => absint( $args['user_id'] ?? 0 ),
			'created_at'  => current_time( 'mysql' ),
			'expires_at'  => ! empty( $args['expires_at'] ) ? sanitize_text_field( $args['expires_at'] ) : '',
			'last_used'   => null,
			'usage_count' => 0,
			'active'      => true,
		);

		$keys = get_option( $this->option_name, array() );
		$keys[] = $key_data;
		update_option( $this->option_name, $keys );

		return array(
			'key'  => $plain_key,
			'data' => $key_data,
		);
	}

	/**
	 * Revoke API key.
	 *
	 * @param int $index Key index.
	 * @return bool Success.
	 */
	public function revoke_api_key( int $index ) {
		$keys = get_option( $this->option_name, array() );

		if ( isset( $keys[ $index ] ) ) {
			$keys[ $index ]['active'] = false;
			update_option( $this->option_name, $keys );
			return true;
		}

		return false;
	}

	/**
	 * Get all API keys.
	 *
	 * @return array
	 */
	public function get_api_keys() {
		return get_option( $this->option_name, array() );
	}

	// =========================================================================
	// Admin Page
	// =========================================================================

	/**
	 * Add API keys admin page.
	 *
	 * @return void
	 */
	public function add_api_keys_page() {
		add_submenu_page(
			'ytrip',
			__( 'API Keys', 'ytrip' ),
			__( 'API Keys', 'ytrip' ),
			'manage_options',
			'ytrip-api-keys',
			array( $this, 'render_api_keys_page' )
		);
	}

	/**
	 * Render API keys admin page.
	 *
	 * @return void
	 */
	public function render_api_keys_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$keys = $this->get_api_keys();
		$new_key = null;

		// Check for new key in session.
		if ( isset( $_GET['new_key'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ytrip_new_key' ) ) {
			$new_key = sanitize_text_field( wp_unslash( $_GET['new_key'] ) );
		}
		?>
		<div class="wrap ytrip-api-keys-wrap">
			<h1><?php esc_html_e( 'API Keys', 'ytrip' ); ?></h1>
			<p><?php esc_html_e( 'Manage API keys for mobile apps and external integrations.', 'ytrip' ); ?></p>

			<?php if ( $new_key ) : ?>
			<div class="notice notice-success">
				<p><strong><?php esc_html_e( 'API Key Created!', 'ytrip' ); ?></strong></p>
				<p><?php esc_html_e( 'Copy this key now. It will not be shown again:', 'ytrip' ); ?></p>
				<p><code style="font-size: 16px; padding: 10px; display: block; background: #f0f0f0;"><?php echo esc_html( $new_key ); ?></code></p>
			</div>
			<?php endif; ?>

			<div class="ytrip-api-docs">
				<h3><?php esc_html_e( 'API Authentication', 'ytrip' ); ?></h3>
				<p><?php esc_html_e( 'Include your API key in requests using one of these methods:', 'ytrip' ); ?></p>
				<ul>
					<li><code>Authorization: Bearer ytrip_xxxxx</code></li>
					<li><code>X-API-Key: ytrip_xxxxx</code></li>
				</ul>
				<p><strong><?php esc_html_e( 'Base URL:', 'ytrip' ); ?></strong> <code><?php echo esc_url( rest_url( 'ytrip/v1/' ) ); ?></code></p>
			</div>

			<h2><?php esc_html_e( 'Your API Keys', 'ytrip' ); ?></h2>
			
			<?php if ( empty( $keys ) ) : ?>
				<p class="description"><?php esc_html_e( 'No API keys yet. Create one below.', 'ytrip' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Key', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Permissions', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Last Used', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Usage', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Status', 'ytrip' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'ytrip' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $keys as $index => $key ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $key['name'] ); ?></strong></td>
							<td><code><?php echo esc_html( $key['key_prefix'] ); ?></code></td>
							<td><?php echo esc_html( implode( ', ', $key['permissions'] ) ); ?></td>
							<td><?php echo $key['last_used'] ? esc_html( $key['last_used'] ) : '—'; ?></td>
							<td><?php echo esc_html( number_format_i18n( $key['usage_count'] ?? 0 ) ); ?></td>
							<td>
								<?php if ( $key['active'] ) : ?>
									<span style="color: #46b450;">●</span> <?php esc_html_e( 'Active', 'ytrip' ); ?>
								<?php else : ?>
									<span style="color: #dc3232;">●</span> <?php esc_html_e( 'Revoked', 'ytrip' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $key['active'] ) : ?>
								<button type="button" class="button button-small ytrip-revoke-key" data-index="<?php echo esc_attr( $index ); ?>">
									<?php esc_html_e( 'Revoke', 'ytrip' ); ?>
								</button>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Create New API Key', 'ytrip' ); ?></h3>
			<form id="ytrip-create-key-form" method="post">
				<?php wp_nonce_field( 'ytrip_create_api_key', '_wpnonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="key-name"><?php esc_html_e( 'Name', 'ytrip' ); ?></label></th>
						<td>
							<input type="text" id="key-name" name="name" class="regular-text" required 
							       placeholder="<?php esc_attr_e( 'e.g., Mobile App', 'ytrip' ); ?>">
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Permissions', 'ytrip' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="permission" value="read" checked>
									<?php esc_html_e( 'Read Only - Can view tours, availability, etc.', 'ytrip' ); ?>
								</label><br>
								<label>
									<input type="radio" name="permission" value="read_write">
									<?php esc_html_e( 'Read/Write - Can create bookings, reviews', 'ytrip' ); ?>
								</label><br>
								<label>
									<input type="radio" name="permission" value="full">
									<?php esc_html_e( 'Full Access - All operations (admin only)', 'ytrip' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Generate API Key', 'ytrip' ), 'primary', 'create_key' ); ?>
			</form>
		</div>

		<style>
			.ytrip-api-keys-wrap { max-width: 1000px; }
			.ytrip-api-docs { background: #fff; padding: 15px 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; }
			.ytrip-api-docs code { background: #f0f0f0; padding: 2px 6px; }
		</style>

		<script>
		jQuery(function($) {
			$('.ytrip-revoke-key').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Revoke this API key? This cannot be undone.', 'ytrip' ) ); ?>')) return;
				
				$.post(ajaxurl, {
					action: 'ytrip_revoke_api_key',
					index: $(this).data('index'),
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'ytrip_revoke_key' ) ); ?>'
				}, function() {
					location.reload();
				});
			});
		});
		</script>
		<?php
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX: Create API key.
	 *
	 * @return void
	 */
	public function ajax_create_key() {
		check_ajax_referer( 'ytrip_create_api_key' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$permission = sanitize_text_field( wp_unslash( $_POST['permission'] ?? 'read' ) );

		$permissions = array( 'read' );
		if ( $permission === 'read_write' ) {
			$permissions = array( 'read', 'write' );
		} elseif ( $permission === 'full' ) {
			$permissions = array( 'full' );
		}

		$result = $this->generate_api_key( array(
			'name'        => $name,
			'permissions' => $permissions,
			'user_id'     => get_current_user_id(),
		) );

		$redirect_url = add_query_arg( array(
			'page'     => 'ytrip-api-keys',
			'new_key'  => $result['key'],
			'_wpnonce' => wp_create_nonce( 'ytrip_new_key' ),
		), admin_url( 'admin.php' ) );

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * AJAX: Revoke API key.
	 *
	 * @return void
	 */
	public function ajax_revoke_key() {
		check_ajax_referer( 'ytrip_revoke_key' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$index = absint( $_POST['index'] ?? 0 );
		$this->revoke_api_key( $index );

		wp_send_json_success();
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
		// Get current user info (for mobile apps).
		register_rest_route( 'ytrip/v1', '/auth/me', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_current_user' ),
			'permission_callback' => 'is_user_logged_in',
		) );

		// Rate limit status.
		register_rest_route( 'ytrip/v1', '/auth/status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_status' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * REST: Get current authenticated user.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_current_user(): \WP_REST_Response {
		$user = wp_get_current_user();

		return new \WP_REST_Response( array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'avatar'       => get_avatar_url( $user->ID ),
			'capabilities' => array_keys( array_filter( $user->allcaps ) ),
		) );
	}

	/**
	 * REST: Get API status.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_status(): \WP_REST_Response {
		return new \WP_REST_Response( array(
			'status'     => 'ok',
			'version'    => '1.2.0',
			'rate_limit' => array(
				'requests' => $this->rate_limit_requests,
				'window'   => $this->rate_limit_window,
			),
		) );
	}
}

// Initialize.
YTrip_API_Auth::instance();
