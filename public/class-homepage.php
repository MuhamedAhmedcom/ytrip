<?php
/**
 * YTrip Homepage Controller
 *
 * Renders homepage sections based on CodeStar options and provides shortcode support.
 *
 * @package YTrip
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * YTrip Homepage Class
 */
class YTrip_Homepage {

	/**
	 * Singleton instance.
	 *
	 * @var YTrip_Homepage|null
	 */
	private static $instance = null;

	/**
	 * Homepage options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Section template mapping.
	 *
	 * @var array
	 */
	private $section_templates = array(
		'hero_slider'    => 'homepage/hero-slider.php',
		'search_form'    => 'homepage/search-form.php',
		'featured_tours' => 'homepage/featured-tours.php',
		'destinations'   => 'homepage/destinations.php',
		'categories'     => 'homepage/categories.php',
		'testimonials'   => 'homepage/testimonials.php',
		'stats'          => 'homepage/stats.php',
		'blog'           => 'homepage/blog.php',
		'video_banner'   => 'homepage/video-banner.php',
		'promo_banner'   => 'homepage/promo-banner.php',
		'partners'       => 'homepage/partners.php',
		'instagram_feed' => 'homepage/instagram-feed.php',
	);

	/**
	 * Get singleton instance.
	 *
	 * @return YTrip_Homepage
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'ytrip_homepage', array() );
		$this->init_hooks();
	}

	/**
	 * Import settings from JSON config file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function import_from_json() {
		$json_file = YTRIP_PATH . 'premium-homepage-config.json';

		if ( ! file_exists( $json_file ) ) {
			return false;
		}

		$json_content = file_get_contents( $json_file );
		$config = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		// Save to WordPress options.
		update_option( 'ytrip_homepage', $config );

		// Reload options.
		$this->options = $config;

		return true;
	}


	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register shortcode.
		add_shortcode( 'ytrip_homepage', array( $this, 'shortcode_homepage' ) );
		add_shortcode( 'ytrip_section', array( $this, 'shortcode_section' ) );

		// Auto-render on front page if enabled.
		add_action( 'ytrip_before_main_content', array( $this, 'maybe_render_homepage' ) );

		// Provide action hooks for theme integration.
		add_action( 'wp', array( $this, 'setup_homepage_content' ) );
	}

	/**
	 * Setup homepage content for front page.
	 */
	public function setup_homepage_content() {
		// Check if we're on the front page and should auto-render.
		if ( is_front_page() && ! is_home() ) {
			// Add filter to page content.
			add_filter( 'the_content', array( $this, 'filter_front_page_content' ), 20 );
		}
	}

	/**
	 * Filter front page content to include homepage sections.
	 *
	 * @param string $content Page content.
	 * @return string Modified content.
	 */
	public function filter_front_page_content( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Check if the content already has the shortcode.
		if ( has_shortcode( $content, 'ytrip_homepage' ) ) {
			return $content;
		}

		// Append homepage sections after content.
		ob_start();
		$this->render_all_sections();
		$homepage_content = ob_get_clean();

		return $content . $homepage_content;
	}

	/**
	 * Maybe render homepage sections.
	 */
	public function maybe_render_homepage() {
		if ( is_front_page() ) {
			$this->render_all_sections();
		}
	}

	/**
	 * Shortcode handler for full homepage.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function shortcode_homepage( $atts ) {
		$atts = shortcode_atts(
			array(
				'sections' => '', // Comma-separated list of sections to include, empty = all enabled.
			),
			$atts,
			'ytrip_homepage'
		);

		ob_start();

		if ( ! empty( $atts['sections'] ) ) {
			$sections = array_map( 'trim', explode( ',', $atts['sections'] ) );
			foreach ( $sections as $section ) {
				$this->render_section( $section );
			}
		} else {
			$this->render_all_sections();
		}

		return ob_get_clean();
	}

	/**
	 * Shortcode handler for single section.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function shortcode_section( $atts ) {
		$atts = shortcode_atts(
			array(
				'name' => '',
			),
			$atts,
			'ytrip_section'
		);

		if ( empty( $atts['name'] ) ) {
			return '';
		}

		ob_start();
		$this->render_section( $atts['name'] );
		return ob_get_clean();
	}

	/**
	 * Render all enabled sections in order.
	 */
	public function render_all_sections() {
		$sections = $this->get_enabled_sections();

		if ( empty( $sections ) ) {
			// Default sections if none configured.
			$sections = array( 'hero_slider', 'search_form', 'featured_tours', 'destinations' );
		}

		$layout  = isset( $this->options['homepage_layout'] ) ? sanitize_html_class( $this->options['homepage_layout'] ) : 'modern';
		$width   = isset( $this->options['homepage_width'] ) ? sanitize_html_class( $this->options['homepage_width'] ) : 'wide';
		$classes = array(
			'ytrip-homepage',
			'ytrip-homepage--layout-' . $layout,
			'ytrip-homepage--width-' . $width,
		);
		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		foreach ( $sections as $section_key => $section_label ) {
			// Handle both array formats (sorter returns ['key' => 'label']).
			$key = is_numeric( $section_key ) ? $section_label : $section_key;
			$this->render_section( $key );
		}

		echo '</div>';
	}

	/**
	 * Get enabled sections from CodeStar options.
	 *
	 * @return array Array of enabled section keys.
	 */
	public function get_enabled_sections() {
		$sections_config = isset( $this->options['homepage_sections'] ) ? $this->options['homepage_sections'] : array();

		// CodeStar sorter returns ['enabled' => [...], 'disabled' => [...]]
		if ( isset( $sections_config['enabled'] ) && is_array( $sections_config['enabled'] ) ) {
			return $sections_config['enabled'];
		}

		// Fallback for simple array.
		if ( is_array( $sections_config ) && ! isset( $sections_config['enabled'] ) ) {
			return $sections_config;
		}

		return array();
	}

	/**
	 * Render a single section.
	 *
	 * @param string $section_key Section key.
	 */
	public function render_section( $section_key ) {
		// Check if section is enabled in options.
		$enable_key = $this->get_section_enable_key( $section_key );
		if ( $enable_key && isset( $this->options[ $enable_key ] ) && empty( $this->options[ $enable_key ] ) ) {
			return; // Section is disabled.
		}

		// Get template file.
		$template = $this->get_section_template( $section_key );

		if ( ! $template ) {
			return;
		}

		// Allow theme override.
		$theme_template = get_stylesheet_directory() . '/ytrip/' . $template;
		if ( file_exists( $theme_template ) ) {
			$template_path = $theme_template;
		} elseif ( file_exists( YTRIP_PATH . 'templates/' . $template ) ) {
			$template_path = YTRIP_PATH . 'templates/' . $template;
		} else {
			return;
		}

		// Hook before section.
		do_action( 'ytrip_before_section_' . $section_key, $this->options );

		// Include template.
		include $template_path;

		// Hook after section.
		do_action( 'ytrip_after_section_' . $section_key, $this->options );
	}

	/**
	 * Get section template file path.
	 *
	 * @param string $section_key Section key.
	 * @return string|false Template path or false if not found.
	 */
	private function get_section_template( $section_key ) {
		if ( isset( $this->section_templates[ $section_key ] ) ) {
			return $this->section_templates[ $section_key ];
		}

		// Allow custom sections via filter.
		return apply_filters( 'ytrip_section_template', false, $section_key );
	}

	/**
	 * Get the enable option key for a section.
	 *
	 * @param string $section_key Section key.
	 * @return string|false Option key or false.
	 */
	private function get_section_enable_key( $section_key ) {
		$map = array(
			'hero_slider'    => 'hero_enable',
			'search_form'    => 'search_enable',
			'featured_tours' => 'featured_enable',
			'destinations'   => 'destinations_enable',
			'categories'     => 'categories_enable',
			'testimonials'   => 'testimonials_enable',
			'stats'          => 'stats_enable',
			'blog'           => 'blog_enable',
			'video_banner'   => 'video_enable',
			'promo_banner'   => 'promo_enable',
			'partners'       => 'partners_enable',
			'instagram_feed' => 'instagram_enable',
		);

		return isset( $map[ $section_key ] ) ? $map[ $section_key ] : false;
	}

	/**
	 * Get homepage options.
	 *
	 * @return array Options array.
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get specific option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	public function get_option( $key, $default = '' ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}
}

/**
 * Get YTrip Homepage instance.
 *
 * @return YTrip_Homepage
 */
if ( ! function_exists( 'ytrip_homepage' ) ) {
	function ytrip_homepage() {
		return YTrip_Homepage::instance();
	}
}

// Initialize.
ytrip_homepage();
