<?php
/**
 * YTrip Settings Class
 *
 * Handles plugin settings page with Settings API.
 * Settings sections: General, Maps, Booking, Pricing, Performance
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Class
 */
class YTrip_Settings {

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
	private $option_name = 'ytrip_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		// General
		'currency'           => 'USD',
		'currency_position'  => 'before',
		'date_format'        => 'Y-m-d',
		'time_format'        => 'H:i',

		// Maps
		'map_provider'       => 'openstreetmap',
		'google_maps_api'    => '',
		'default_latitude'   => '25.276987',
		'default_longitude'  => '55.296249',
		'default_zoom'       => '5',

		// Booking
		'min_advance_days'   => '1',
		'max_advance_days'   => '365',
		'require_login'      => 'no',
		'enable_coupons'     => 'yes',

		// Pricing
		'enable_early_bird'  => 'yes',
		'enable_last_minute' => 'yes',
		'max_discount'       => '50',

		// Performance
		'lazy_load_images'   => 'yes',
		'enable_webp'        => 'yes',
		'cache_duration'     => '3600',
		'production_mode'    => 'no',
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings page to YTrip menu.
	 * Removed: duplicate submenu that showed a second "YTrip Settings" UI.
	 * The Settings API form is embedded in the Codestar panel instead (see codestar-config.php).
	 *
	 * @return void
	 */
	public function add_settings_page() {
		// No standalone page - form is embedded in the single Codestar settings panel.
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ytrip_settings_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);

		// General Section.
		add_settings_section(
			'ytrip_general',
			__( 'General Settings', 'ytrip' ),
			array( $this, 'render_section_general' ),
			'ytrip-settings'
		);

		add_settings_field(
			'currency',
			__( 'Currency', 'ytrip' ),
			array( $this, 'render_currency_field' ),
			'ytrip-settings',
			'ytrip_general'
		);

		// Maps Section.
		add_settings_section(
			'ytrip_maps',
			__( 'Map Settings', 'ytrip' ),
			array( $this, 'render_section_maps' ),
			'ytrip-settings'
		);

		add_settings_field(
			'map_provider',
			__( 'Map Provider', 'ytrip' ),
			array( $this, 'render_map_provider_field' ),
			'ytrip-settings',
			'ytrip_maps'
		);

		add_settings_field(
			'google_maps_api',
			__( 'Google Maps API Key', 'ytrip' ),
			array( $this, 'render_google_api_field' ),
			'ytrip-settings',
			'ytrip_maps'
		);

		add_settings_field(
			'default_location',
			__( 'Default Map Location', 'ytrip' ),
			array( $this, 'render_location_field' ),
			'ytrip-settings',
			'ytrip_maps'
		);

		// Booking Section.
		add_settings_section(
			'ytrip_booking',
			__( 'Booking Settings', 'ytrip' ),
			array( $this, 'render_section_booking' ),
			'ytrip-settings'
		);

		add_settings_field(
			'booking_options',
			__( 'Booking Options', 'ytrip' ),
			array( $this, 'render_booking_options_field' ),
			'ytrip-settings',
			'ytrip_booking'
		);

		add_settings_field(
			'recaptcha',
			__( 'Spam Protection', 'ytrip' ),
			array( $this, 'render_recaptcha_field' ),
			'ytrip-settings',
			'ytrip_booking'
		);

		// Performance Section.
		add_settings_section(
			'ytrip_performance',
			__( 'Performance', 'ytrip' ),
			array( $this, 'render_section_performance' ),
			'ytrip-settings'
		);

		add_settings_field(
			'performance_options',
			__( 'Performance Options', 'ytrip' ),
			array( $this, 'render_performance_field' ),
			'ytrip-settings',
			'ytrip_performance'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ) {
		$sanitized = array();

		// General.
		$sanitized['currency'] = sanitize_text_field( $input['currency'] ?? 'USD' );
		$sanitized['currency_position'] = in_array( $input['currency_position'] ?? '', array( 'before', 'after' ), true )
			? $input['currency_position'] : 'before';
		$sanitized['date_format'] = sanitize_text_field( $input['date_format'] ?? 'Y-m-d' );
		$sanitized['time_format'] = sanitize_text_field( $input['time_format'] ?? 'H:i' );

		// Maps.
		$sanitized['map_provider'] = in_array( $input['map_provider'] ?? '', array( 'openstreetmap', 'google' ), true )
			? $input['map_provider'] : 'openstreetmap';
		$sanitized['google_maps_api'] = sanitize_text_field( $input['google_maps_api'] ?? '' );
		$sanitized['default_latitude'] = floatval( $input['default_latitude'] ?? 25.276987 );
		$sanitized['default_longitude'] = floatval( $input['default_longitude'] ?? 55.296249 );
		$sanitized['default_zoom'] = absint( $input['default_zoom'] ?? 5 );

		// Booking.
		$sanitized['min_advance_days'] = absint( $input['min_advance_days'] ?? 1 );
		$sanitized['max_advance_days'] = absint( $input['max_advance_days'] ?? 365 );
		$sanitized['require_login'] = ( $input['require_login'] ?? '' ) === 'yes' ? 'yes' : 'no';
		$sanitized['enable_coupons'] = ( $input['enable_coupons'] ?? 'yes' ) === 'yes' ? 'yes' : 'no';
		$sanitized['recaptcha_site_key'] = sanitize_text_field( $input['recaptcha_site_key'] ?? '' );
		$sanitized['recaptcha_secret_key'] = sanitize_text_field( $input['recaptcha_secret_key'] ?? '' );

		// Performance.
		$sanitized['lazy_load_images'] = ( $input['lazy_load_images'] ?? 'yes' ) === 'yes' ? 'yes' : 'no';
		$sanitized['enable_webp'] = ( $input['enable_webp'] ?? 'yes' ) === 'yes' ? 'yes' : 'no';
		$sanitized['cache_duration'] = absint( $input['cache_duration'] ?? 3600 );
		$sanitized['production_mode'] = ( $input['production_mode'] ?? '' ) === 'yes' ? 'yes' : 'no';

		return $sanitized;
	}

	/**
	 * Get setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default = null ) {
		$settings = get_option( $this->option_name, $this->defaults );
		return $settings[ $key ] ?? $default ?? ( $this->defaults[ $key ] ?? null );
	}

	/**
	 * Get all settings.
	 *
	 * @return array All settings.
	 */
	public function get_all() {
		return wp_parse_args( get_option( $this->option_name, array() ), $this->defaults );
	}

	// =========================================================================
	// Render Methods
	// =========================================================================

	/**
	 * Render settings page (standalone; used when embedded form is not used).
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap ytrip-settings-wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo $this->get_embedded_settings_form_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Return the Settings API form HTML for embedding in the Codestar panel.
	 * Used so there is only one settings entry (Codestar) with General/Maps/Booking/Performance inside it.
	 *
	 * @return string
	 */
	public function get_embedded_settings_form_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		// Ensure WordPress admin template is loaded (do_settings_sections, submit_button).
		if ( ! function_exists( 'do_settings_sections' ) && is_admin() ) {
			$template = ABSPATH . 'wp-admin/includes/template.php';
			if ( file_exists( $template ) ) {
				require_once $template;
			}
		}
		if ( ! function_exists( 'do_settings_sections' ) || ! function_exists( 'submit_button' ) ) {
			return '<div class="ytrip-settings-embedded"><p>' . esc_html__( 'Settings form unavailable. Please refresh the page.', 'ytrip' ) . '</p></div>';
		}
		ob_start();
		?>
		<div class="ytrip-settings-embedded">
			<nav class="nav-tab-wrapper ytrip-settings-tabs">
				<a href="#ytrip_general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General', 'ytrip' ); ?></a>
				<a href="#ytrip_maps" class="nav-tab"><?php esc_html_e( 'Maps', 'ytrip' ); ?></a>
				<a href="#ytrip_booking" class="nav-tab"><?php esc_html_e( 'Booking', 'ytrip' ); ?></a>
				<a href="#ytrip_performance" class="nav-tab"><?php esc_html_e( 'Performance', 'ytrip' ); ?></a>
			</nav>

			<form method="post" action="options.php" class="ytrip-settings-form">
				<?php
				settings_fields( 'ytrip_settings_group' );
				do_settings_sections( 'ytrip-settings' );
				submit_button( __( 'Save Settings', 'ytrip' ) );
				?>
			</form>
		</div>

		<style>
			.ytrip-settings-embedded { max-width: 900px; }
			.ytrip-settings-tabs { margin-bottom: 20px; }
			.ytrip-settings-form .form-table th { width: 200px; padding: 15px 10px 15px 0; }
			.ytrip-settings-form .form-table td { padding: 15px 10px; }
			.ytrip-field-group { display: flex; flex-wrap: wrap; gap: 15px; }
			.ytrip-field-group label { display: flex; align-items: center; gap: 5px; }
			.ytrip-field-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
			.ytrip-field-row input[type="text"], .ytrip-field-row input[type="number"] { width: 150px; }
			.ytrip-settings-form .description { color: #666; font-style: italic; margin-top: 5px; }
			.ytrip-api-key-field { width: 400px; }
			.ytrip-conditional[data-show="google"] { display: none; }
			.ytrip-conditional.visible { display: block !important; }
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render general section description.
	 *
	 * @return void
	 */
	public function render_section_general() {
		echo '<p>' . esc_html__( 'Configure general plugin settings.', 'ytrip' ) . '</p>';
	}

	/**
	 * Render maps section description.
	 *
	 * @return void
	 */
	public function render_section_maps() {
		echo '<p>' . esc_html__( 'Configure map display settings. Choose between OpenStreetMap (free) or Google Maps.', 'ytrip' ) . '</p>';
	}

	/**
	 * Render booking section description.
	 *
	 * @return void
	 */
	public function render_section_booking() {
		echo '<p>' . esc_html__( 'Configure booking behavior and restrictions.', 'ytrip' ) . '</p>';
	}

	/**
	 * Render performance section description.
	 *
	 * @return void
	 */
	public function render_section_performance() {
		echo '<p>' . esc_html__( 'Optimize plugin performance.', 'ytrip' ) . '</p>';
	}

	/**
	 * Render currency field.
	 *
	 * @return void
	 */
	public function render_currency_field() {
		$settings = $this->get_all();
		$currencies = array(
			'USD' => 'US Dollar ($)',
			'EUR' => 'Euro (€)',
			'GBP' => 'British Pound (£)',
			'AED' => 'UAE Dirham (د.إ)',
			'SAR' => 'Saudi Riyal (﷼)',
			'EGP' => 'Egyptian Pound (E£)',
		);
		?>
		<div class="ytrip-field-row">
			<select name="<?php echo esc_attr( $this->option_name ); ?>[currency]">
				<?php foreach ( $currencies as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['currency'], $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<select name="<?php echo esc_attr( $this->option_name ); ?>[currency_position]">
				<option value="before" <?php selected( $settings['currency_position'], 'before' ); ?>>
					<?php esc_html_e( 'Before amount ($100)', 'ytrip' ); ?>
				</option>
				<option value="after" <?php selected( $settings['currency_position'], 'after' ); ?>>
					<?php esc_html_e( 'After amount (100$)', 'ytrip' ); ?>
				</option>
			</select>
		</div>
		<?php
	}

	/**
	 * Render map provider field.
	 *
	 * @return void
	 */
	public function render_map_provider_field() {
		$settings = $this->get_all();
		?>
		<div class="ytrip-field-group">
			<label>
				<input type="radio" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[map_provider]" 
				       value="openstreetmap" 
				       <?php checked( $settings['map_provider'], 'openstreetmap' ); ?>
				       class="ytrip-map-provider-toggle">
				<?php esc_html_e( 'OpenStreetMap (Free)', 'ytrip' ); ?>
			</label>
			<label>
				<input type="radio" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[map_provider]" 
				       value="google" 
				       <?php checked( $settings['map_provider'], 'google' ); ?>
				       class="ytrip-map-provider-toggle">
				<?php esc_html_e( 'Google Maps (Requires API Key)', 'ytrip' ); ?>
			</label>
		</div>
		<p class="description"><?php esc_html_e( 'OpenStreetMap is free with no usage limits. Google Maps offers more features but requires billing.', 'ytrip' ); ?></p>

		<script>
		jQuery(function($) {
			$('.ytrip-map-provider-toggle').on('change', function() {
				const provider = $(this).val();
				$('.ytrip-conditional[data-show="google"]').toggleClass('visible', provider === 'google');
			}).filter(':checked').trigger('change');
		});
		</script>
		<?php
	}

	/**
	 * Render Google API field.
	 *
	 * @return void
	 */
	public function render_google_api_field() {
		$settings = $this->get_all();
		?>
		<div class="ytrip-conditional" data-show="google">
			<input type="text" 
			       name="<?php echo esc_attr( $this->option_name ); ?>[google_maps_api]" 
			       value="<?php echo esc_attr( $settings['google_maps_api'] ); ?>"
			       class="regular-text ytrip-api-key-field"
			       placeholder="<?php esc_attr_e( 'Enter your Google Maps API key', 'ytrip' ); ?>">
			<p class="description">
				<?php 
				printf( 
					/* translators: %s: Google Cloud Console URL */
					esc_html__( 'Get your API key from the %s', 'ytrip' ),
					'<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render location field.
	 *
	 * @return void
	 */
	public function render_location_field() {
		$settings = $this->get_all();
		?>
		<div class="ytrip-field-row">
			<label>
				<?php esc_html_e( 'Latitude', 'ytrip' ); ?>
				<input type="text" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[default_latitude]" 
				       value="<?php echo esc_attr( $settings['default_latitude'] ); ?>"
				       placeholder="25.276987">
			</label>
			<label>
				<?php esc_html_e( 'Longitude', 'ytrip' ); ?>
				<input type="text" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[default_longitude]" 
				       value="<?php echo esc_attr( $settings['default_longitude'] ); ?>"
				       placeholder="55.296249">
			</label>
			<label>
				<?php esc_html_e( 'Zoom', 'ytrip' ); ?>
				<input type="number" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[default_zoom]" 
				       value="<?php echo esc_attr( $settings['default_zoom'] ); ?>"
				       min="1" max="20"
				       style="width: 60px;">
			</label>
		</div>
		<p class="description"><?php esc_html_e( 'Default center point when no tours have coordinates.', 'ytrip' ); ?></p>
		<?php
	}

	/**
	 * Render booking options field.
	 *
	 * @return void
	 */
	public function render_booking_options_field() {
		$settings = $this->get_all();
		?>
		<div class="ytrip-field-row">
			<label>
				<?php esc_html_e( 'Min advance booking', 'ytrip' ); ?>
				<input type="number" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[min_advance_days]" 
				       value="<?php echo esc_attr( $settings['min_advance_days'] ); ?>"
				       min="0" max="365">
				<?php esc_html_e( 'days', 'ytrip' ); ?>
			</label>
		</div>
		<div class="ytrip-field-row">
			<label>
				<?php esc_html_e( 'Max advance booking', 'ytrip' ); ?>
				<input type="number" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[max_advance_days]" 
				       value="<?php echo esc_attr( $settings['max_advance_days'] ); ?>"
				       min="1" max="730">
				<?php esc_html_e( 'days', 'ytrip' ); ?>
			</label>
		</div>
		<div class="ytrip-field-group" style="margin-top: 10px;">
			<label>
				<input type="checkbox" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[require_login]" 
				       value="yes"
				       <?php checked( $settings['require_login'], 'yes' ); ?>>
				<?php esc_html_e( 'Require login to book', 'ytrip' ); ?>
			</label>
			<label>
				<input type="checkbox" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[enable_coupons]" 
				       value="yes"
				       <?php checked( $settings['enable_coupons'], 'yes' ); ?>>
				<?php esc_html_e( 'Enable coupon codes', 'ytrip' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Render reCAPTCHA field.
	 *
	 * @return void
	 */
	public function render_recaptcha_field() {
		$settings = $this->get_all();
		?>
		<p class="description" style="margin-bottom: 15px;">
			<?php
			printf(
				/* translators: %s is a link to Google reCAPTCHA */
				esc_html__( 'Get your keys from %s (use reCAPTCHA v3)', 'ytrip' ),
				'<a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>'
			);
			?>
		</p>
		<div class="ytrip-field-row">
			<label style="display: flex; flex-direction: column; gap: 4px;">
				<?php esc_html_e( 'Site Key', 'ytrip' ); ?>
				<input type="text" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[recaptcha_site_key]" 
				       value="<?php echo esc_attr( $settings['recaptcha_site_key'] ?? '' ); ?>"
				       class="regular-text"
				       placeholder="6Lc...">
			</label>
		</div>
		<div class="ytrip-field-row" style="margin-top: 10px;">
			<label style="display: flex; flex-direction: column; gap: 4px;">
				<?php esc_html_e( 'Secret Key', 'ytrip' ); ?>
				<input type="password" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[recaptcha_secret_key]" 
				       value="<?php echo esc_attr( $settings['recaptcha_secret_key'] ?? '' ); ?>"
				       class="regular-text"
				       placeholder="6Lc...">
			</label>
		</div>
		<p class="description" style="margin-top: 10px;">
			<?php esc_html_e( 'Leave empty to use honeypot-only spam protection.', 'ytrip' ); ?>
		</p>
		<?php
	}

	/**
	 * Render performance field.
	 *
	 * @return void
	 */
	public function render_performance_field() {
		$settings = $this->get_all();
		$bundles_exist = file_exists( YTRIP_PATH . 'assets/dist/ytrip-core.min.css' );
		?>
		<div class="ytrip-field-group">
			<label style="font-weight: 600; color: #1e40af;">
				<input type="checkbox" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[production_mode]" 
				       value="yes"
				       <?php checked( $settings['production_mode'] ?? '', 'yes' ); ?>
				       <?php disabled( ! $bundles_exist ); ?>>
				<?php esc_html_e( 'Production Mode (Minified Assets)', 'ytrip' ); ?>
			</label>
		</div>
		<?php if ( ! $bundles_exist ) : ?>
		<p class="description" style="color: #dc2626;">
			<?php esc_html_e( 'Build assets first to enable production mode.', 'ytrip' ); ?>
		</p>
		<?php endif; ?>

		<div style="margin: 15px 0; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
			<button type="button" class="button button-primary" id="ytrip-build-assets">
				<?php esc_html_e( 'Build Optimized Assets', 'ytrip' ); ?>
			</button>
			<span id="ytrip-build-status" style="margin-left: 10px;"></span>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'Combines and minifies CSS/JS files for faster loading.', 'ytrip' ); ?>
			</p>
		</div>

		<h4 style="margin-top: 20px;"><?php esc_html_e( 'Image Optimization', 'ytrip' ); ?></h4>
		<div class="ytrip-field-group">
			<label>
				<input type="checkbox" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[lazy_load_images]" 
				       value="yes"
				       <?php checked( $settings['lazy_load_images'], 'yes' ); ?>>
				<?php esc_html_e( 'Lazy load images', 'ytrip' ); ?>
			</label>
			<label>
				<input type="checkbox" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[enable_webp]" 
				       value="yes"
				       <?php checked( $settings['enable_webp'], 'yes' ); ?>>
				<?php esc_html_e( 'Enable WebP conversion', 'ytrip' ); ?>
			</label>
		</div>

		<h4 style="margin-top: 20px;"><?php esc_html_e( 'Caching', 'ytrip' ); ?></h4>
		<div class="ytrip-field-row">
			<label>
				<?php esc_html_e( 'Cache duration', 'ytrip' ); ?>
				<input type="number" 
				       name="<?php echo esc_attr( $this->option_name ); ?>[cache_duration]" 
				       value="<?php echo esc_attr( $settings['cache_duration'] ); ?>"
				       min="0" max="86400"
				       style="width: 100px;">
				<?php esc_html_e( 'seconds', 'ytrip' ); ?>
			</label>
			<button type="button" class="button" id="ytrip-clear-cache">
				<?php esc_html_e( 'Clear Cache', 'ytrip' ); ?>
			</button>
		</div>

		<script>
		jQuery(function($) {
			$('#ytrip-build-assets').on('click', function() {
				var btn = $(this);
				var status = $('#ytrip-build-status');
				
				btn.prop('disabled', true).text('<?php echo esc_js( __( 'Building...', 'ytrip' ) ); ?>');
				status.html('<span style="color: #666;">⏳ Processing...</span>');
				
				$.post(ajaxurl, {
					action: 'ytrip_build_assets',
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'ytrip_build_assets' ) ); ?>'
				}, function(response) {
					btn.prop('disabled', false).text('<?php echo esc_js( __( 'Build Optimized Assets', 'ytrip' ) ); ?>');
					
					if (response.success) {
						status.html('<span style="color: #16a34a;">✓ ' + response.data.message + '</span>');
						location.reload();
					} else {
						status.html('<span style="color: #dc2626;">✗ Error building assets</span>');
					}
				});
			});

			$('#ytrip-clear-cache').on('click', function() {
				$.post(ajaxurl, { action: 'ytrip_clear_cache' }, function(response) {
					if (response.success) {
						alert('<?php echo esc_js( __( 'Cache cleared!', 'ytrip' ) ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ) {
		$settings_screens = array( 'toplevel_page_ytrip-settings', 'ytrip_page_ytrip-settings' );
		if ( ! in_array( $hook, $settings_screens, true ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
	}
}

// Helper function to get settings.
function ytrip_get_setting( string $key, $default = null ) {
	return YTrip_Settings::instance()->get( $key, $default );
}

// Initialize.
YTrip_Settings::instance();
