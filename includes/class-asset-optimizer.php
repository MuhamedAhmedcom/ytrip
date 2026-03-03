<?php
/**
 * YTrip Asset Bundler & Optimizer
 *
 * Combines and minifies CSS/JS files for production.
 * Provides critical CSS extraction and async loading.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Optimizer Class
 */
class YTrip_Asset_Optimizer {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Production mode flag.
	 *
	 * @var bool
	 */
	private $production_mode = false;

	/**
	 * CSS bundles configuration.
	 *
	 * @var array
	 */
	private $css_bundles = array(
		'core'    => array(
			'main.css',
			'cards/card-styles.css',
		),
		'single'  => array(
			'single-tour.css',
			'reviews.css',
			'layouts/single-layout-1.css',
			'layouts/single-layout-2.css',
			'layouts/single-layout-3.css',
			'layouts/single-layout-4.css',
			'layouts/single-layout-5.css',
		),
		'archive' => array(
			'archive-filters.css',
		),
		'dashboard' => array(
			'user-dashboard.css',
		),
	);

	/**
	 * JS bundles configuration.
	 *
	 * @var array
	 */
	private $js_bundles = array(
		'core'    => array(
			'main.js',
			'wishlist.js',
		),
		'single'  => array(
			'single-tour.js',
			'reviews.js',
		),
		'archive' => array(
			'archive-filters.js',
		),
		'dashboard' => array(
			'user-dashboard.js',
		),
		'map'     => array(
			'map-view.js',
		),
		'effects' => array(
			'animations.js',
			'microinteractions.js',
			'parallax.js',
		),
	);

	/**
	 * Critical CSS for above-fold content.
	 *
	 * @var array
	 */
	private $critical_css = array();

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
		$this->production_mode = $this->is_production_mode();
		$this->init_hooks();
	}

	/**
	 * Check if production mode is enabled.
	 *
	 * @return bool
	 */
	private function is_production_mode() {
		// Check setting.
		$settings = get_option( 'ytrip_settings', array() );
		if ( ! empty( $settings['production_mode'] ) ) {
			return true;
		}

		// Check constant.
		if ( defined( 'YTRIP_PRODUCTION' ) && YTRIP_PRODUCTION ) {
			return true;
		}

		// Check WP environment.
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'production' ) {
			return true;
		}

		return false;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Replace default asset loading in production.
		if ( $this->production_mode ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_optimized_assets' ), 5 );
			add_filter( 'ytrip_load_default_assets', '__return_false' );
		}

		// Inline critical CSS.
		add_action( 'wp_head', array( $this, 'inline_critical_css' ), 1 );

		// Font preload disabled — theme fonts are used, not plugin fonts.

		// Add resource hints.
		add_action( 'wp_head', array( $this, 'add_resource_hints' ), 3 );

		// Defer non-critical CSS.
		add_filter( 'style_loader_tag', array( $this, 'defer_non_critical_css' ), 10, 4 );

		// Add async/defer to scripts.
		add_filter( 'script_loader_tag', array( $this, 'optimize_script_loading' ), 10, 3 );

		// Admin: Build assets.
		add_action( 'wp_ajax_ytrip_build_assets', array( $this, 'ajax_build_assets' ) );
	}

	/**
	 * Enqueue optimized (bundled/minified) assets.
	 *
	 * @return void
	 */
	public function enqueue_optimized_assets() {
		$dist_path = YTRIP_PATH . 'assets/dist/';
		$dist_url = YTRIP_URL . 'assets/dist/';

		// Check if bundles exist.
		if ( ! file_exists( $dist_path . 'ytrip-core.min.css' ) ) {
			return; // Fall back to default loading.
		}

		// Core CSS (always load on YTrip pages).
		if ( $this->is_ytrip_page() ) {
			wp_enqueue_style(
				'ytrip-core',
				$dist_url . 'ytrip-core.min.css',
				array(),
				$this->get_bundle_version( 'ytrip-core.min.css' )
			);

			wp_enqueue_script(
				'ytrip-core',
				$dist_url . 'ytrip-core.min.js',
				array( 'jquery' ),
				$this->get_bundle_version( 'ytrip-core.min.js' ),
				true
			);
		}

		// Single tour page.
		if ( is_singular( 'ytrip_tour' ) ) {
			wp_enqueue_style(
				'ytrip-single',
				$dist_url . 'ytrip-single.min.css',
				array( 'ytrip-core' ),
				$this->get_bundle_version( 'ytrip-single.min.css' )
			);

			wp_enqueue_script(
				'ytrip-single',
				$dist_url . 'ytrip-single.min.js',
				array( 'ytrip-core' ),
				$this->get_bundle_version( 'ytrip-single.min.js' ),
				true
			);
		}

		// Archive pages.
		if ( is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' ) ) {
			wp_enqueue_style(
				'ytrip-archive',
				$dist_url . 'ytrip-archive.min.css',
				array( 'ytrip-core' ),
				$this->get_bundle_version( 'ytrip-archive.min.css' )
			);

			wp_enqueue_script(
				'ytrip-archive',
				$dist_url . 'ytrip-archive.min.js',
				array( 'ytrip-core' ),
				$this->get_bundle_version( 'ytrip-archive.min.js' ),
				true
			);
		}
	}

	/**
	 * Check if current page is a YTrip page (uses shared helper).
	 *
	 * @return bool
	 */
	private function is_ytrip_page() {
		return function_exists( 'ytrip_is_plugin_page' ) && ytrip_is_plugin_page();
	}

	/**
	 * Get bundle file version based on modification time.
	 *
	 * @param string $filename Bundle filename.
	 * @return string Version string.
	 */
	private function get_bundle_version( string $filename ) {
		$filepath = YTRIP_PATH . 'assets/dist/' . $filename;
		if ( file_exists( $filepath ) ) {
			return (string) filemtime( $filepath );
		}
		return YTRIP_VERSION;
	}

	/**
	 * Inline critical CSS for above-fold content.
	 * Outputs full critical file in production; minimal fallback on YTrip pages when not in production.
	 *
	 * @return void
	 */
	public function inline_critical_css() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		$critical_css = $this->production_mode ? $this->get_critical_css() : $this->get_minimal_critical_css();
		if ( empty( $critical_css ) ) {
			return;
		}
		echo '<style id="ytrip-critical-css">' . $critical_css . '</style>' . "\n";
	}

	/**
	 * Get critical CSS (full file in production or minimal fallback).
	 *
	 * @return string
	 */
	private function get_critical_css() {
		$critical_file = YTRIP_PATH . 'assets/dist/critical.min.css';
		if ( file_exists( $critical_file ) ) {
			return file_get_contents( $critical_file );
		}
		return $this->get_minimal_critical_css();
	}

	/**
	 * Minimal critical CSS for above-the-fold (used when not in production or when dist file missing).
	 *
	 * @return string
	 */
	private function get_minimal_critical_css() {
		return ':root{--ytrip-primary:#2563eb;--ytrip-dark:#1e293b}
.ytrip-section{width:100%;clear:both;padding:4rem 0;box-sizing:border-box}
.ytrip-section img{max-width:100%;height:auto;display:block}
.ytrip-tour-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.ytrip-tour-image{aspect-ratio:4/3;background:#f1f5f9}
.ytrip-skeleton{animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}';
	}

	/**
	 * Font preload disabled — theme fonts are used, not plugin fonts.
	 * Method kept for backwards compatibility; no output.
	 *
	 * @return void
	 */
	public function preload_fonts() {
		// No-op: plugin does not load or preload fonts.
	}

	/**
	 * Add resource hints for external resources.
	 *
	 * @return void
	 */
	public function add_resource_hints() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		// DNS prefetch is no longer needed - all fonts are local
	}

	/**
	 * Defer non-critical CSS loading (all modes) to improve LCP and reduce render-blocking.
	 *
	 * @param string $html Link tag HTML.
	 * @param string $handle Style handle.
	 * @param string $href Stylesheet URL.
	 * @param string $media Media type.
	 * @return string Modified HTML.
	 */
	public function defer_non_critical_css( string $html, string $handle, string $href, string $media ) {
		// Only defer YTrip styles.
		if ( strpos( $handle, 'ytrip-' ) !== 0 ) {
			return $html;
		}
		if ( ! $this->is_ytrip_page() ) {
			return $html;
		}

		// Never defer critical styles (layout and above-the-fold). Include LCP-critical for homepage and single tour.
		$critical_handles = array( 'ytrip-core', 'ytrip-main', 'ytrip-archive-bundle', 'ytrip-single-tour', 'ytrip-homepage', 'ytrip-hero-slider', 'swiper-bundle' );
		if ( in_array( $handle, $critical_handles, true ) ) {
			return $html;
		}

		// Defer: fonts-extra, archive-filters, card-styles, reviews, etc.
		// Use media="print" trick for async CSS loading.
		$html = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
		$html = str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $html );
		$noscript = sprintf( '<noscript><link rel="stylesheet" href="%s" media="all"></noscript>', esc_url( $href ) );
		return $html . $noscript;
	}

	/**
	 * Optimize script loading with defer (all modes) so JS does not block parsing.
	 *
	 * @param string $tag Script tag.
	 * @param string $handle Script handle.
	 * @param string $src Script URL.
	 * @return string Modified tag.
	 */
	public function optimize_script_loading( string $tag, string $handle, string $src ) {
		// Only touch YTrip scripts on YTrip pages.
		if ( strpos( $handle, 'ytrip-' ) !== 0 || ! $this->is_ytrip_page() ) {
			return $tag;
		}

		// On archive: do not defer main/archive-filters so they load and are not canceled (fixes LCP and card display).
		$is_archive = is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' );
		if ( $is_archive && in_array( $handle, array( 'ytrip-main', 'ytrip-archive-filters' ), true ) ) {
			return $tag;
		}
		// On single tour: do not defer main/single-tour so hero and layout paint for LCP.
		if ( is_singular( 'ytrip_tour' ) && in_array( $handle, array( 'ytrip-main', 'ytrip-single-tour' ), true ) ) {
			return $tag;
		}
		// On homepage: do not defer main/hero-slider so hero paints for LCP.
		if ( is_front_page() && in_array( $handle, array( 'ytrip-main', 'ytrip-hero-slider' ), true ) ) {
			return $tag;
		}

		// Defer other YTrip scripts so parsing isn't blocked (scripts run in order when deferred).
		$defer_handles = array(
			'ytrip-main',
			'ytrip-archive-filters',
			'ytrip-single',
			'ytrip-archive',
			'ytrip-effects',
			'ytrip-map',
			'ytrip-animations',
			'ytrip-parallax',
			'ytrip-microinteractions',
		);
		if ( in_array( $handle, $defer_handles, true ) ) {
			if ( strpos( $tag, ' defer' ) === false && strpos( $tag, ' async' ) === false ) {
				return str_replace( ' src', ' defer src', $tag );
			}
		}

		// Async for analytics/tracking.
		$async_handles = array( 'ytrip-analytics' );
		if ( in_array( $handle, $async_handles, true ) ) {
			return str_replace( ' src', ' async src', $tag );
		}

		return $tag;
	}

	// =========================================================================
	// Asset Building
	// =========================================================================

	/**
	 * Build all asset bundles.
	 *
	 * @return array Build results.
	 */
	public function build_assets() {
		$results = array(
			'css' => array(),
			'js'  => array(),
		);

		$css_path = YTRIP_PATH . 'assets/css/';
		$js_path = YTRIP_PATH . 'assets/js/';
		$dist_path = YTRIP_PATH . 'assets/dist/';

		// Build CSS bundles.
		foreach ( $this->css_bundles as $name => $files ) {
			$combined = '';
			foreach ( $files as $file ) {
				$filepath = $css_path . $file;
				if ( file_exists( $filepath ) ) {
					$combined .= file_get_contents( $filepath ) . "\n";
				}
			}

			$minified = $this->minify_css( $combined );
			$output_file = $dist_path . 'ytrip-' . $name . '.min.css';
			file_put_contents( $output_file, $minified );

			$results['css'][ $name ] = array(
				'original' => strlen( $combined ),
				'minified' => strlen( $minified ),
				'savings'  => round( ( 1 - strlen( $minified ) / max( strlen( $combined ), 1 ) ) * 100, 1 ) . '%',
			);
		}

		// Build JS bundles.
		foreach ( $this->js_bundles as $name => $files ) {
			$combined = '';
			foreach ( $files as $file ) {
				$filepath = $js_path . $file;
				if ( file_exists( $filepath ) ) {
					$combined .= file_get_contents( $filepath ) . ";\n";
				}
			}

			$minified = $this->minify_js( $combined );
			$output_file = $dist_path . 'ytrip-' . $name . '.min.js';
			file_put_contents( $output_file, $minified );

			$results['js'][ $name ] = array(
				'original' => strlen( $combined ),
				'minified' => strlen( $minified ),
				'savings'  => round( ( 1 - strlen( $minified ) / max( strlen( $combined ), 1 ) ) * 100, 1 ) . '%',
			);
		}

		// Generate critical CSS.
		$this->generate_critical_css();

		return $results;
	}

	/**
	 * Simple CSS minification.
	 *
	 * @param string $css CSS content.
	 * @return string Minified CSS.
	 */
	private function minify_css( string $css ) {
		// Remove comments.
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove whitespace.
		$css = preg_replace( '/\s+/', ' ', $css );

		// Remove spaces around selectors.
		$css = preg_replace( '/\s*([{}:;,>+~])\s*/', '$1', $css );

		// Remove trailing semicolons.
		$css = str_replace( ';}', '}', $css );

		// Remove newlines.
		$css = str_replace( array( "\r\n", "\r", "\n" ), '', $css );

		return trim( $css );
	}

	/**
	 * Simple JS minification.
	 *
	 * @param string $js JS content.
	 * @return string Minified JS.
	 */
	private function minify_js( string $js ) {
		// Remove single-line comments (but not URLs).
		$js = preg_replace( '#(?<!:)//(?![\'"]).+$#m', '', $js );

		// Remove multi-line comments.
		$js = preg_replace( '!/\*.*?\*/!s', '', $js );

		// Remove extra whitespace.
		$js = preg_replace( '/\s+/', ' ', $js );

		// Remove spaces around operators.
		$js = preg_replace( '/\s*([{};,=\(\)\[\]])\s*/', '$1', $js );

		// Remove newlines.
		$js = str_replace( array( "\r\n", "\r", "\n" ), '', $js );

		return trim( $js );
	}

	/**
	 * Generate critical CSS file.
	 *
	 * @return void
	 */
	private function generate_critical_css() {
		$critical = '
/* Critical CSS - Above the fold styles */
:root {
	--ytrip-primary: #2563eb;
	--ytrip-primary-dark: #1d4ed8;
	--ytrip-secondary: #f97316;
	--ytrip-dark: #1e293b;
	--ytrip-gray: #64748b;
	--ytrip-light: #f8fafc;
	--ytrip-radius: 12px;
	--ytrip-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}

/* Fonts: inherit from theme — plugin does not load fonts */

.ytrip-tour-card {
	background: #fff;
	border-radius: var(--ytrip-radius);
	overflow: hidden;
	box-shadow: var(--ytrip-shadow);
}

.ytrip-tour-image {
	aspect-ratio: 4/3;
	background: #f1f5f9;
	overflow: hidden;
}

.ytrip-tour-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.ytrip-section {
	width: 100%;
	clear: both;
	padding: 4rem 0;
	box-sizing: border-box;
}

.ytrip-section img {
	max-width: 100%;
	height: auto;
	display: block;
}

.ytrip-skeleton {
	background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
	background-size: 200% 100%;
	animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}

.ytrip-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 0.75rem 1.5rem;
	border-radius: 8px;
	font-weight: 600;
	transition: all 0.2s;
}

.ytrip-btn-primary {
	background: var(--ytrip-primary);
	color: #fff;
}
';

		$minified = $this->minify_css( $critical );
		file_put_contents( YTRIP_PATH . 'assets/dist/critical.min.css', $minified );
	}

	/**
	 * Build standalone minified CSS next to source (for PageSpeed minify when dist not used).
	 *
	 * @return array List of built files.
	 */
	public function build_standalone_min_css() {
		$files = array( 'assets/css/single-tour.css', 'assets/css/archive-filters.css' );
		$built = array();
		foreach ( $files as $rel ) {
			$path = YTRIP_PATH . $rel;
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$css = file_get_contents( $path );
			if ( $css === false ) {
				continue;
			}
			$min_path = preg_replace( '/\.css$/', '.min.css', $rel );
			$out_path = YTRIP_PATH . $min_path;
			$minified = $this->minify_css( $css );
			if ( file_put_contents( $out_path, $minified ) !== false ) {
				$built[] = $min_path;
			}
		}
		return $built;
	}

	/**
	 * AJAX: Build assets.
	 *
	 * @return void
	 */
	public function ajax_build_assets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		check_ajax_referer( 'ytrip_build_assets' );

		$results = $this->build_assets();

		wp_send_json_success( array(
			'message' => __( 'Assets built successfully!', 'ytrip' ),
			'results' => $results,
		) );
	}
}

// Initialize.
YTrip_Asset_Optimizer::instance();
