<?php
/**
 * YTrip Homepage Builder
 *
 * Simplified, bulletproof homepage rendering.
 * PHP 7.2+ compatible. Directly includes template files.
 *
 * @package YTrip
 * @since 2.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YTrip_Homepage_Builder
 */
class YTrip_Homepage_Builder {

	/**
	 * @var YTrip_Homepage_Builder|null
	 */
	private static ?self $instance = null;

	/**
	 * @var array
	 */
	private array $options = [];

	/**
	 * @var array
	 */
	private array $template_map = [
		'hero_slider'    => 'homepage/hero-slider.php',
		'search_form'    => 'homepage/search-form.php',
		'featured_tours' => 'homepage/featured-tours.php',
		'destinations'   => 'homepage/destinations.php',
		'categories'     => 'homepage/categories.php',
		'testimonials'   => 'homepage/testimonials.php',
		'stats'          => 'homepage/stats.php',
		'blog'           => 'homepage/blog.php',
	];

	/**
	 * @var array
	 */
	private array $enable_map = [
		'hero_slider'    => 'hero_enable',
		'search_form'    => 'search_enable',
		'featured_tours' => 'featured_enable',
		'destinations'   => 'destinations_enable',
		'categories'     => 'categories_enable',
		'testimonials'   => 'testimonials_enable',
		'stats'          => 'stats_enable',
		'blog'           => 'blog_enable',
	];

	/**
	 * @return YTrip_Homepage_Builder
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$raw = get_option( 'ytrip_homepage', [] );
		$this->options = is_array( $raw ) ? $raw : ( is_object( $raw ) ? (array) $raw : [] );

		add_shortcode( 'ytrip_homepage', array( $this, 'shortcode_handler' ) );

		// Hook into the_content at maximum priority
		add_filter( 'the_content', array( $this, 'filter_front_page_content' ), 9999 );
	}

	/**
	 * Shortcode: [ytrip_homepage]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_handler( $atts = [] ): string {
		$atts = shortcode_atts( [ 'sections' => '' ], (array) $atts, 'ytrip_homepage' );

		ob_start();

		if ( ! empty( $atts['sections'] ) ) {
			$keys = array_map( 'trim', explode( ',', $atts['sections'] ) );
			foreach ( $keys as $key ) {
				$this->render_section( $key );
			}
		} else {
			$this->render_all_sections();
		}

		return ob_get_clean();
	}

	/**
	 * Filter front page content — replaces or appends homepage sections.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function filter_front_page_content( $content ): string {
		static $is_rendering = false;

		// Must be front page and in the main query loop
		if ( ! $this->is_homepage() ) {
			return $content;
		}

		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Prevent infinite recursion or multiple renders on same page
		if ( $is_rendering ) {
			return $content;
		}

		// Don't double-render if shortcode already present
		if ( has_shortcode( $content, 'ytrip_homepage' ) ) {
			return $content;
		}

		$is_rendering = true;

		// Render all homepage sections
		ob_start();
		$this->render_all_sections();
		$homepage_html = ob_get_clean();

		$is_rendering = false;

		// Replace or append
		$replace = isset( $this->options['replace_content'] ) ? $this->options['replace_content'] : false;

		if ( ! empty( $replace ) ) {
			return $homepage_html;
		}

		return $content . $homepage_html;
	}

	/**
	 * Check if current page is the homepage.
	 * Uses multiple checks for maximum compatibility.
	 *
	 * @return bool
	 */
	private function is_homepage(): bool {
		// Standard WordPress front page check
		if ( is_front_page() ) {
			return true;
		}

		// Fallback: check if this is the blog home page (when no static front page is set)
		if ( is_home() && ! get_option( 'page_on_front' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render all enabled sections.
	 *
	 * @return void
	 */
	public function render_all_sections(): void {
		$ordered = $this->get_ordered_section_keys();

		if ( empty( $ordered ) ) {
			// Fallback: render all sections in default order
			$ordered = array( 'hero_slider', 'search_form', 'featured_tours', 'stats', 'destinations', 'testimonials', 'categories', 'blog' );
		}

		$layout  = isset( $this->options['homepage_layout'] ) ? sanitize_html_class( (string) $this->options['homepage_layout'] ) : 'modern';
		$width   = isset( $this->options['homepage_width'] ) ? sanitize_html_class( (string) $this->options['homepage_width'] ) : 'wide';
		$design  = isset( $this->options['homepage_design'] ) ? sanitize_html_class( (string) $this->options['homepage_design'] ) : 'default';
		$classes = array(
			'ytrip-homepage',
			'ytrip-homepage--layout-' . $layout,
			'ytrip-homepage--width-' . $width,
			'ytrip-homepage--design-' . $design,
		);
		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		foreach ( $ordered as $section_key ) {
			$this->render_section( $section_key );
		}

		echo '</div>';
	}

	/**
	 * Render a single section by key.
	 *
	 * @param string $section_key The section key.
	 * @return void
	 */
	public function render_section( $section_key ): void {
		// Must have a template
		if ( ! isset( $this->template_map[ $section_key ] ) ) {
			return;
		}

		$template_file = $this->template_map[ $section_key ];

		// Check if section is explicitly disabled
		if ( isset( $this->enable_map[ $section_key ] ) ) {
			$enable_key = $this->enable_map[ $section_key ];
			if ( isset( $this->options[ $enable_key ] ) && empty( $this->options[ $enable_key ] ) ) {
				return;
			}
		}

		// Resolve template path
		$template_path = $this->resolve_template( $template_file );

		if ( empty( $template_path ) ) {
			return;
		}

		// Include the template
		include $template_path;
	}

	/**
	 * Get ordered section keys from the sorter config.
	 *
	 * @return array
	 */
	private function get_ordered_section_keys(): array {
		$config = isset( $this->options['homepage_sections'] ) ? $this->options['homepage_sections'] : [];
		if ( is_object( $config ) ) {
			$config = (array) $config;
		}
		if ( ! is_array( $config ) ) {
			return array();
		}

		// CodeStar sorter format: ['enabled' => ['key' => 'label'], 'disabled' => [...]]
		$enabled = isset( $config['enabled'] ) ? $config['enabled'] : array();
		if ( is_object( $enabled ) ) {
			$enabled = (array) $enabled;
		}
		if ( is_array( $enabled ) && ! empty( $enabled ) ) {
			return array_keys( $enabled );
		}

		return array();
	}

	/**
	 * Resolve template path (theme override, then plugin).
	 *
	 * @param string $template_file Relative path.
	 * @return string Absolute path or empty string.
	 */
	private function resolve_template( $template_file ): string {
		// Theme override
		$theme = get_stylesheet_directory() . '/ytrip/' . $template_file;
		if ( file_exists( $theme ) ) {
			return $theme;
		}

		// Plugin template
		if ( defined( 'YTRIP_PATH' ) ) {
			$plugin = YTRIP_PATH . 'templates/' . $template_file;
			if ( file_exists( $plugin ) ) {
				return $plugin;
			}
		}

		return '';
	}

	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_option( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * @return array
	 */
	public function get_options(): array {
		return $this->options;
	}
}

// =========================================================================
// Helper Functions
// =========================================================================

if ( ! function_exists( 'ytrip_homepage' ) ) {
	/**
	 * @return YTrip_Homepage_Builder
	 */
	function ytrip_homepage() {
		return YTrip_Homepage_Builder::instance();
	}
}

// Initialize the builder
YTrip_Homepage_Builder::instance();
