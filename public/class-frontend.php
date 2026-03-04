<?php
/**
 * YTrip Frontend Controller
 *
 * Handles frontend asset loading and initialization.
 *
 * @package YTrip
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * YTrip Frontend Class
 */
class YTrip_Frontend {

	/**
	 * Singleton instance.
	 *
	 * @var YTrip_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Get singleton instance.
	 *
	 * @return YTrip_Frontend
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
		$this->options = get_option( 'ytrip_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'wp_head', array( $this, 'output_custom_css' ), 100 );
		add_action( 'wp_footer', array( $this, 'output_custom_js' ), 100 );
		add_filter( 'body_class', array( $this, 'add_body_classes' ), 20 );
	}

	/**
	 * Enqueue frontend styles (page-specific only; base comes from Template_Loader as ytrip-main).
	 * When dist bundles exist, the asset optimizer (priority 5) loads them; skip here to avoid duplicate CSS.
	 * Ensures base ytrip-main is always present on YTrip pages so layout and content never break.
	 */
	public function enqueue_styles() {
		// Only load on YTrip pages.
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		// Use optimized bundles when dist exists (avoids duplicate CSS and reduces unused CSS per page).
		if ( file_exists( YTRIP_PATH . 'assets/dist/ytrip-core.min.css' ) ) {
			return;
		}

		// Ensure base assets exist so page always has core layout (fixes broken/empty content when Template_Loader order differs).
		$this->ensure_base_assets_enqueued();

		$base_handle = 'ytrip-main';

		// Homepage stylesheet — load on front page OR blog home.
		if ( ( is_front_page() || is_home() ) && file_exists( YTRIP_PATH . 'assets/css/homepage.css' ) ) {
			wp_enqueue_style(
				'ytrip-homepage',
				YTRIP_URL . 'assets/css/homepage.css',
				array( $base_handle ),
				YTRIP_VERSION
			);
			$homepage_opts = get_option( 'ytrip_homepage', array() );
			if ( ! empty( $homepage_opts['hero_enable'] ) && ! empty( $homepage_opts['hero_slides'] ) ) {
				wp_enqueue_style(
					'swiper-bundle',
					YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
					array(),
					'11.2.10'
				);
			}
			if ( ! empty( $homepage_opts['homepage_design'] ) && $homepage_opts['homepage_design'] === 'travel_concept' && file_exists( YTRIP_PATH . 'assets/css/design-travel-concept.css' ) ) {
				wp_enqueue_style(
					'ytrip-design-travel-concept',
					YTRIP_URL . 'assets/css/design-travel-concept.css',
					array( $base_handle, 'ytrip-homepage' ),
					YTRIP_VERSION
				);
			}
		}

		// Archive: prefer single bundle to reduce requests; fallback to separate files.
		if ( $this->is_tour_archive() ) {
			$bundle_path = YTRIP_PATH . 'assets/css/archive-bundle.css';
			if ( file_exists( $bundle_path ) ) {
				wp_enqueue_style( 'ytrip-archive-bundle', YTRIP_URL . 'assets/css/archive-bundle.css', array( $base_handle ), (string) filemtime( $bundle_path ) );
			} else {
				$archive_css = $this->prefer_min_css( 'assets/css/archive-filters.css' );
				if ( $archive_css ) {
					wp_enqueue_style( 'ytrip-archive-filters', YTRIP_URL . $archive_css, array( $base_handle ), YTRIP_VERSION );
				}
				$card_css = $this->prefer_min_css( 'assets/css/cards/card-styles.css' );
				if ( $card_css ) {
					wp_enqueue_style( 'ytrip-cards', YTRIP_URL . $card_css, array( $base_handle ), YTRIP_VERSION );
				}
			}
		}

		// RTL support.
		if ( is_rtl() && file_exists( YTRIP_PATH . 'assets/css/frontend-rtl.css' ) ) {
			wp_enqueue_style(
				'ytrip-frontend-rtl',
				YTRIP_URL . 'assets/css/frontend-rtl.css',
				array( $base_handle ),
				YTRIP_VERSION
			);
		}

        // Single tour stylesheet (prefer minified when available for PageSpeed).
        $single_css = $this->prefer_min_css( 'assets/css/single-tour.css' );
        if ( is_singular( 'ytrip_tour' ) && $single_css ) {
            wp_enqueue_style(
                'ytrip-single-tour',
                YTRIP_URL . $single_css,
                array( $base_handle ),
                YTRIP_VERSION
            );
            // Related section: force dynamic height and no clipping (applies even when min CSS is used).
            wp_add_inline_style( 'ytrip-single-tour', $this->get_related_tours_inline_css() );
            if ( ! empty( $this->options['single_tabs_show_icons'] ) ) {
                wp_enqueue_style( 'dashicons' );
            }
            // Homepage hero CSS on single tour for full-width hero and overlay (height capped in single-tour.css).
            if ( file_exists( YTRIP_PATH . 'assets/css/homepage.css' ) ) {
                wp_enqueue_style(
                    'ytrip-homepage',
                    YTRIP_URL . 'assets/css/homepage.css',
                    array( $base_handle ),
                    YTRIP_VERSION
                );
            }
            // Swiper CSS only when single tour hero is slider/carousel (avoids unused CSS, CLS from late styles).
            $tour_id   = get_queried_object_id();
            $meta      = get_post_meta( $tour_id, 'ytrip_tour_details', true );
            $meta      = is_array( $meta ) ? $meta : array();
            $hero_mode = isset( $meta['hero_gallery_mode'] ) ? sanitize_key( $meta['hero_gallery_mode'] ) : 'single_image';
            $gallery   = isset( $meta['tour_gallery'] ) ? array_filter( array_map( 'absint', explode( ',', $meta['tour_gallery'] ) ) ) : array();
            if ( empty( $gallery ) && has_post_thumbnail( $tour_id ) ) {
                $gallery = array( get_post_thumbnail_id( $tour_id ) );
            }
            $hero_is_slider = ( $hero_mode === 'slider' || $hero_mode === 'carousel' ) && count( $gallery ) > 1;
            if ( $hero_is_slider ) {
                wp_enqueue_style(
                    'swiper-bundle',
                    YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
                    array(),
                    '11.2.10'
                );
            }
        }

		// Reviews stylesheet.
		if ( is_singular( 'ytrip_tour' ) && file_exists( YTRIP_PATH . 'assets/css/reviews.css' ) ) {
			wp_enqueue_style(
				'ytrip-reviews',
				YTRIP_URL . 'assets/css/reviews.css',
				array( $base_handle ),
				YTRIP_VERSION
			);
		}
	}

	/**
	 * Ensure base CSS and JS (ytrip-main) are enqueued when dist is not used.
	 * Prevents broken layout and empty content on archive/homepage/single when load order varies.
	 */
	private function ensure_base_assets_enqueued() {
		if ( wp_style_is( 'ytrip-main', 'enqueued' ) && wp_script_is( 'ytrip-main', 'enqueued' ) ) {
			return;
		}
		// Plugin does not load fonts; theme fonts are used.
		if ( ! wp_style_is( 'ytrip-main', 'enqueued' ) && file_exists( YTRIP_PATH . 'assets/css/main.css' ) ) {
			wp_enqueue_style(
				'ytrip-main',
				YTRIP_URL . 'assets/css/main.css',
				array(),
				YTRIP_VERSION
			);
		}
		if ( ! wp_script_is( 'ytrip-main', 'enqueued' ) && file_exists( YTRIP_PATH . 'assets/js/main.js' ) ) {
			wp_enqueue_script(
				'ytrip-main',
				YTRIP_URL . 'assets/js/main.js',
				array( 'jquery' ),
				YTRIP_VERSION,
				true
			);
		}
	}

	/**
	 * Inline CSS for related tours section: dynamic height and no clipping.
	 * Ensures full card content is visible even when single-tour.min.css is loaded.
	 *
	 * @return string CSS string.
	 */
	private function get_related_tours_inline_css() {
		return '/* Related section: no negative margin so first card is not clipped */
.ytrip-related-tours .ytrip-tours--carousel-wrapper{margin:0;padding:0 3.5rem;}
/* Related section: uniform card width and height */
.ytrip-related-tours .ytrip-tour-card{overflow:hidden;height:100%;min-height:500px;}
.ytrip-related-tours .ytrip-tours--carousel-wrapper .ytrip-carousel{min-height:500px;overflow-x:hidden;overflow-y:hidden;}
.ytrip-related-tours .ytrip-tours--carousel-wrapper .ytrip-carousel__track{align-items:stretch;min-height:500px;}
.ytrip-related-tours .ytrip-carousel__slide{width:300px;min-width:300px;max-width:300px;flex-shrink:0;}
.ytrip-related-tours .ytrip-carousel__slide .ytrip-tour-card{min-height:500px;}
.ytrip-related-tours .ytrip-tour-card__content{overflow:hidden;}
.ytrip-related-tours .ytrip-tour-card__title{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden;}
/* Related carousel dots: always visible; click scrolls carousel */
.ytrip-related-tours .ytrip-carousel__dots{display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1.75rem;overflow:visible;min-height:28px;padding:6px 0;}
.ytrip-related-tours .ytrip-carousel__dots .ytrip-carousel__dot{width:8px;height:8px;min-width:24px;min-height:24px;border-radius:50%;background:var(--ytrip-gray-400,#94a3b8);border:none;padding:0;cursor:pointer;transition:background .2s,transform .2s,box-shadow .2s;flex-shrink:0;overflow:visible;visibility:visible!important;opacity:1!important;pointer-events:auto;display:inline-flex;align-items:center;justify-content:center;}
.ytrip-related-tours .ytrip-carousel__dots .ytrip-carousel__dot:hover{background:var(--ytrip-primary,#2563eb)!important;transform:scale(1.2);visibility:visible!important;opacity:1!important;}
.ytrip-related-tours .ytrip-carousel__dots .ytrip-carousel__dot.ytrip-carousel__dot--active{background:var(--ytrip-primary,#2563eb)!important;transform:scale(1.25);box-shadow:0 0 0 2px var(--ytrip-primary-light,#eff6ff);}
.ytrip-related-tours .ytrip-carousel__dots .ytrip-carousel__dot.ytrip-carousel__dot--active:hover{background:var(--ytrip-primary-hover,#1d4ed8)!important;transform:scale(1.25);box-shadow:0 0 0 2px var(--ytrip-primary-light,#eff6ff);}
/* Mobile: center related tours carousel so orphan slide is centered not left-aligned */
@media(max-width:767px){
  .ytrip-related-tours .ytrip-tours--carousel-wrapper{padding:0 1rem;}
  .ytrip-related-tours .ytrip-carousel__track{justify-content:center;}
  .ytrip-related-tours .ytrip-carousel__slide{width:85vw;min-width:260px;max-width:340px;}
  .ytrip-related-tours .ytrip-carousel__slide .ytrip-tour-card{min-height:420px;}
  .ytrip-related-tours .ytrip-tour-card{min-height:420px;}
}
/* Review section layout */
.ytrip-review-section{padding:3rem 0;}
.ytrip-review-section__header{text-align:center;margin-bottom:2rem;}
.ytrip-review-section__eyebrow{font-size:.875rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--ytrip-primary,#2563eb);margin:0 0 .5rem;}
.ytrip-review-section__title{font-size:1.75rem;font-weight:700;margin:0;color:var(--ytrip-text,#1e293b);}
.ytrip-review-section__body{max-width:700px;margin:0 auto;}';
	}

	/**
	 * Prefer minified CSS path when available (PageSpeed minify recommendation).
	 *
	 * @param string $relative_path Path relative to plugin root, e.g. 'assets/css/single-tour.css'.
	 * @return string Relative path to use (min or source), or empty if neither exists.
	 */
	private function prefer_min_css( $relative_path ) {
		$base = YTRIP_PATH . $relative_path;
		if ( ! file_exists( $base ) ) {
			return '';
		}
		$min_path = preg_replace( '/\.css$/', '.min.css', $relative_path );
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			if ( file_exists( YTRIP_PATH . $min_path ) ) {
				return $min_path;
			}
		}
		return $relative_path;
	}

	/**
	 * Enqueue frontend scripts (page-specific only; base comes from Template_Loader as ytrip-main).
	 * Ensures base ytrip-main script is enqueued before localizing so archive/homepage/single always work.
	 */
	public function enqueue_scripts() {
		// Only load on YTrip pages.
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		// When dist does not exist, ensure base script is enqueued so localize has a valid handle.
		if ( ! file_exists( YTRIP_PATH . 'assets/dist/ytrip-core.min.css' ) ) {
			$this->ensure_base_assets_enqueued();
		}

		$localize = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'restUrl'   => rest_url( 'ytrip/v1/' ),
			'nonce'     => wp_create_nonce( 'ytrip_frontend_nonce' ),
			'isRtl'     => is_rtl(),
			'strings'   => array(
				'loading'   => esc_html__( 'Loading...', 'ytrip' ),
				'error'     => esc_html__( 'An error occurred.', 'ytrip' ),
				'noResults' => esc_html__( 'No tours found.', 'ytrip' ),
			),
		);
		if ( is_singular( 'ytrip_tour' ) || is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' ) ) {
			$localize['wishlistNonce'] = wp_create_nonce( 'ytrip_wishlist_nonce' );
		}
		wp_localize_script( 'ytrip-main', 'ytripFrontend', $localize );

		// Homepage scripts.
		if ( is_front_page() ) {
			$homepage_opts = get_option( 'ytrip_homepage', array() );
			$hero_enabled  = ! empty( $homepage_opts['hero_enable'] ) && ! empty( $homepage_opts['hero_slides'] );
			if ( $hero_enabled ) {
				wp_enqueue_script(
					'swiper-bundle',
					YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
					array(),
					'11.2.10',
					true
				);
				if ( file_exists( YTRIP_PATH . 'assets/js/hero-slider.js' ) ) {
					wp_enqueue_script(
						'ytrip-hero-slider',
						YTRIP_URL . 'assets/js/hero-slider.js',
						array( 'swiper-bundle' ),
						YTRIP_VERSION,
						true
					);
				}
			}
			$destinations_carousel = ! empty( $homepage_opts['destinations_enable'] )
				&& isset( $homepage_opts['destinations_layout'] )
				&& $homepage_opts['destinations_layout'] === 'carousel';
			if ( $destinations_carousel && file_exists( YTRIP_PATH . 'assets/js/destinations-carousel.js' ) ) {
				wp_enqueue_script(
					'ytrip-destinations-carousel',
					YTRIP_URL . 'assets/js/destinations-carousel.js',
					array( 'ytrip-main' ),
					YTRIP_VERSION,
					true
				);
			}
			if ( file_exists( YTRIP_PATH . 'assets/js/homepage.js' ) ) {
				wp_enqueue_script(
					'ytrip-homepage',
					YTRIP_URL . 'assets/js/homepage.js',
					array( 'ytrip-main' ),
					YTRIP_VERSION,
					true
				);
			}
		}

		// Single tour: Swiper + hero slider only when hero is actually slider/carousel (LCP/INP: avoid ~100KB JS on single-image hero).
		if ( is_singular( 'ytrip_tour' ) ) {
			$tour_id   = get_queried_object_id();
			$meta      = get_post_meta( $tour_id, 'ytrip_tour_details', true );
			$meta      = is_array( $meta ) ? $meta : array();
			$hero_mode = isset( $meta['hero_gallery_mode'] ) ? sanitize_key( $meta['hero_gallery_mode'] ) : 'single_image';
			$gallery   = isset( $meta['tour_gallery'] ) ? array_filter( array_map( 'absint', explode( ',', $meta['tour_gallery'] ) ) ) : array();
			if ( empty( $gallery ) && has_post_thumbnail( $tour_id ) ) {
				$gallery = array( get_post_thumbnail_id( $tour_id ) );
			}
			$hero_is_slider = ( $hero_mode === 'slider' || $hero_mode === 'carousel' ) && count( $gallery ) > 1;
			if ( $hero_is_slider ) {
				wp_enqueue_script(
					'swiper-bundle',
					YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
					array(),
					'11.2.10',
					true
				);
				if ( file_exists( YTRIP_PATH . 'assets/js/hero-slider.js' ) ) {
					wp_enqueue_script(
						'ytrip-hero-slider',
						YTRIP_URL . 'assets/js/hero-slider.js',
						array( 'swiper-bundle' ),
						YTRIP_VERSION,
						true
					);
				}
			}
		}

		// Carousel + wishlist (needed for related section arrows and card interactions on single tour).
		if ( is_singular( 'ytrip_tour' ) && file_exists( YTRIP_PATH . 'assets/js/ytrip-frontend.js' ) ) {
			wp_enqueue_script(
				'ytrip-frontend',
				YTRIP_URL . 'assets/js/ytrip-frontend.js',
				array(),
				YTRIP_VERSION,
				true
			);
		}

		// Shared calendar (booking form uses same component as homepage search).
		if ( is_singular( 'ytrip_tour' ) && file_exists( YTRIP_PATH . 'assets/js/ytrip-calendar.js' ) ) {
			wp_enqueue_script(
				'ytrip-calendar',
				YTRIP_URL . 'assets/js/ytrip-calendar.js',
				array( 'ytrip-main' ),
				YTRIP_VERSION,
				true
			);
		}

		// Single tour script.
		if ( is_singular( 'ytrip_tour' ) && file_exists( YTRIP_PATH . 'assets/js/single-tour.js' ) ) {
			wp_enqueue_script(
				'ytrip-single-tour',
				YTRIP_URL . 'assets/js/single-tour.js',
				array( 'ytrip-main', 'ytrip-calendar' ),
				YTRIP_VERSION,
				true
			);

			// Localize for AJAX.
			wp_localize_script(
				'ytrip-single-tour',
				'ytripAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'ytrip_inquiry_nonce' ),
				)
			);
		}

		// Reviews script.
		if ( is_singular( 'ytrip_tour' ) && file_exists( YTRIP_PATH . 'assets/js/reviews.js' ) ) {
			wp_enqueue_script(
				'ytrip-reviews',
				YTRIP_URL . 'assets/js/reviews.js',
				array( 'jquery' ),
				YTRIP_VERSION,
				true
			);

			// Localize reviews script.
			wp_localize_script(
				'ytrip-reviews',
				'ytripReviews',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'resturl' => rest_url(),
					'nonce'   => wp_create_nonce( 'ytrip_review_nonce' ),
					'strings' => array(
						'loading'      => esc_html__( 'Loading...', 'ytrip' ),
						'error'        => esc_html__( 'An error occurred.', 'ytrip' ),
						'selectRating' => esc_html__( 'Please select a rating.', 'ytrip' ),
						'maxPhotos'    => esc_html__( 'Maximum 5 photos allowed.', 'ytrip' ),
						'loadMore'     => esc_html__( 'Load More Reviews', 'ytrip' ),
						'wasHelpful'   => esc_html__( 'Was this review helpful?', 'ytrip' ),
						'terrible'     => esc_html__( 'Terrible', 'ytrip' ),
						'poor'         => esc_html__( 'Poor', 'ytrip' ),
						'average'      => esc_html__( 'Average', 'ytrip' ),
						'good'         => esc_html__( 'Good', 'ytrip' ),
						'excellent'    => esc_html__( 'Excellent', 'ytrip' ),
					),
				)
			);
		}
	}

	/**
	 * Output custom CSS from options (General, Tablet, Mobile).
	 */
	public function output_custom_css() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		$output = '';

		// General CSS (applies to all screens).
		$css_general = isset( $this->options['custom_css'] ) ? trim( $this->options['custom_css'] ) : '';
		if ( $css_general !== '' ) {
			$output .= wp_strip_all_tags( $css_general );
		}

		// Tablet CSS (auto-wrapped in ≤1024px media query).
		$css_tablet = isset( $this->options['custom_css_tablet'] ) ? trim( $this->options['custom_css_tablet'] ) : '';
		if ( $css_tablet !== '' ) {
			$output .= '@media(max-width:1024px){' . wp_strip_all_tags( $css_tablet ) . '}';
		}

		// Mobile CSS (auto-wrapped in ≤768px media query).
		$css_mobile = isset( $this->options['custom_css_mobile'] ) ? trim( $this->options['custom_css_mobile'] ) : '';
		if ( $css_mobile !== '' ) {
			$output .= '@media(max-width:768px){' . wp_strip_all_tags( $css_mobile ) . '}';
		}

		if ( $output !== '' ) {
			echo '<style id="ytrip-custom-css">' . $output . '</style>';
		}
	}

	/**
	 * Output custom JavaScript from options in the footer.
	 */
	public function output_custom_js() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		$custom_js = isset( $this->options['custom_js'] ) ? trim( $this->options['custom_js'] ) : '';
		if ( $custom_js !== '' ) {
			echo '<script id="ytrip-custom-js">' . $custom_js . '</script>';
		}
	}

	/**
	 * Add body classes for YTrip pages.
	 *
	 * @param array $classes Body classes.
	 * @return array Modified body classes.
	 */
	public function add_body_classes( $classes ) {
		if ( is_singular( 'ytrip_tour' ) ) {
			$classes[] = 'ytrip-single-tour';
			$layout    = isset( $this->options['single_tour_layout'] ) ? $this->options['single_tour_layout'] : 'layout_1';
			$classes[] = 'ytrip-layout-' . sanitize_html_class( $layout );
		}

		if ( $this->is_tour_archive() ) {
			$classes[] = 'ytrip-archive';
			$archive_template = isset( $this->options['archive_template'] ) ? sanitize_html_class( $this->options['archive_template'] ) : 'default';
			$classes[] = 'ytrip-archive-template-' . $archive_template;
		}

		// ytrip-transparent-header is added via ytrip_add_transparent_header_body_class (helper-functions.php, hooked in ytrip.php).

		if ( is_front_page() ) {
			$classes[] = 'ytrip-homepage';
			$homepage_opts = get_option( 'ytrip_homepage', array() );
			if ( ! empty( $homepage_opts['homepage_width'] ) ) {
				$classes[] = 'ytrip-homepage-width-' . sanitize_html_class( $homepage_opts['homepage_width'] );
			}
			if ( ! empty( $homepage_opts['homepage_layout'] ) ) {
				$classes[] = 'ytrip-homepage-layout-' . sanitize_html_class( $homepage_opts['homepage_layout'] );
			}
		}

		if ( is_rtl() ) {
			$classes[] = 'ytrip-rtl';
		}

		return $classes;
	}

	/**
	 * Check if current page is a YTrip page (uses shared helper for conditional loading).
	 *
	 * @return bool True if YTrip page.
	 */
	public function is_ytrip_page() {
		return function_exists( 'ytrip_is_plugin_page' ) && ytrip_is_plugin_page();
	}

	/**
	 * Check if current page is tour archive.
	 *
	 * @return bool True if tour archive.
	 */
	private function is_tour_archive() {
		return is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' );
	}

	/**
	 * Get plugin options.
	 *
	 * @return array Options array.
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get specific option.
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
 * Get YTrip Frontend instance.
 *
 * @return YTrip_Frontend
 */
function ytrip_frontend() {
	return YTrip_Frontend::instance();
}

// Initialize.
ytrip_frontend();
