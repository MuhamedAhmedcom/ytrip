<?php
/**
 * YTrip Image Optimizer
 *
 * Provides advanced image optimization including WebP conversion,
 * responsive srcset, lazy loading with blur placeholders.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Optimizer Class
 */
class YTrip_Image_Optimizer {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	private $settings = array();

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
		$this->settings = get_option( 'ytrip_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Add custom image sizes.
		add_action( 'after_setup_theme', array( $this, 'add_image_sizes' ) );

		// Optimize image attributes.
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'optimize_image_attributes' ), 10, 3 );

		// Add srcset to post thumbnails.
		add_filter( 'post_thumbnail_html', array( $this, 'add_responsive_images' ), 10, 5 );

		// Lazy load with blur placeholder.
		if ( ! empty( $this->settings['enable_blur_placeholder'] ) ) {
			add_filter( 'ytrip_tour_thumbnail', array( $this, 'add_blur_placeholder' ), 10, 2 );
		}

		// WebP support.
		if ( ! empty( $this->settings['enable_webp'] ) ) {
			add_filter( 'image_editor_output_format', array( $this, 'enable_webp_format' ) );
		}

		// Connection-aware loading.
		add_action( 'wp_head', array( $this, 'add_connection_detection' ) );
	}

	/**
	 * Add custom image sizes for tours.
	 *
	 * @return void
	 */
	public function add_image_sizes() {
		// Card thumbnail (optimized 4:3 ratio).
		add_image_size( 'ytrip-card', 400, 300, true );
		add_image_size( 'ytrip-card-2x', 800, 600, true );

		// Hero image.
		add_image_size( 'ytrip-hero', 1200, 600, true );

		// Gallery thumbnails.
		add_image_size( 'ytrip-gallery-thumb', 150, 150, true );

		// Blur placeholder (tiny).
		add_image_size( 'ytrip-blur', 20, 15, true );
	}

	/**
	 * Optimize image attributes.
	 *
	 * @param array        $attr       Attributes.
	 * @param \WP_Post     $attachment Attachment post.
	 * @param string|array $size       Image size.
	 * @return array Modified attributes.
	 */
	public function optimize_image_attributes( array $attr, \WP_Post $attachment, $size ) {
		// Add native lazy loading.
		if ( ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}

		// Add decoding hint.
		$attr['decoding'] = 'async';

		// Add fetchpriority for hero images.
		if ( is_string( $size ) && strpos( $size, 'hero' ) !== false ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading'] = 'eager'; // Don't lazy load hero images.
		}

		return $attr;
	}

	/**
	 * Add responsive images with srcset.
	 *
	 * @param string       $html              Thumbnail HTML.
	 * @param int          $post_id           Post ID.
	 * @param int          $post_thumbnail_id Thumbnail ID.
	 * @param string|array $size              Size.
	 * @param string       $attr              Attributes.
	 * @return string Modified HTML.
	 */
	public function add_responsive_images( string $html, int $post_id, int $post_thumbnail_id, $size, $attr ) {
		// Only modify YTrip tour images.
		if ( get_post_type( $post_id ) !== 'ytrip_tour' ) {
			return $html;
		}

		// Ensure srcset is present.
		if ( strpos( $html, 'srcset' ) === false ) {
			$srcset = wp_get_attachment_image_srcset( $post_thumbnail_id, $size );
			$sizes = wp_get_attachment_image_sizes( $post_thumbnail_id, $size );

			if ( $srcset ) {
				$html = str_replace( '<img', '<img srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"', $html );
			}
		}

		return $html;
	}

	/**
	 * Add blur placeholder for progressive loading.
	 *
	 * @param string $html     Image HTML.
	 * @param int    $image_id Image ID.
	 * @return string Modified HTML.
	 */
	public function add_blur_placeholder( string $html, int $image_id ) {
		// Get blur version.
		$blur_url = wp_get_attachment_image_url( $image_id, 'ytrip-blur' );

		if ( ! $blur_url ) {
			return $html;
		}

		// Add blur as inline background.
		$blur_style = sprintf(
			'background-image:url(%s);background-size:cover;filter:blur(10px);',
			esc_url( $blur_url )
		);

		// Wrap image with placeholder container.
		$wrapper = sprintf(
			'<div class="ytrip-image-placeholder" style="%s">%s</div>',
			esc_attr( $blur_style ),
			$html
		);

		return $wrapper;
	}

	/**
	 * Enable WebP format for uploaded images.
	 *
	 * @param array $formats Output formats.
	 * @return array Modified formats.
	 */
	public function enable_webp_format( array $formats ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png'] = 'image/webp';

		return $formats;
	}

	/**
	 * Add connection detection script.
	 *
	 * @return void
	 */
	public function add_connection_detection() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}
		?>
		<script>
		(function() {
			// Detect slow connections
			var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
			if (connection) {
				var effectiveType = connection.effectiveType;
				if (effectiveType === 'slow-2g' || effectiveType === '2g') {
					document.documentElement.classList.add('ytrip-slow-connection');
				}
				// Save data mode
				if (connection.saveData) {
					document.documentElement.classList.add('ytrip-save-data');
				}
			}
		})();
		</script>
		<style>
		/* Reduce image quality on slow connections */
		.ytrip-slow-connection .ytrip-tour-image img,
		.ytrip-save-data .ytrip-tour-image img {
			image-rendering: -webkit-optimize-contrast;
		}
		.ytrip-slow-connection .ytrip-animation,
		.ytrip-save-data .ytrip-animation {
			animation: none !important;
			transition: none !important;
		}
		</style>
		<?php
	}

	/**
	 * Check if current page is a YTrip page.
	 *
	 * @return bool
	 */
	private function is_ytrip_page() {
		return is_singular( 'ytrip_tour' )
			|| is_post_type_archive( 'ytrip_tour' )
			|| is_tax( 'ytrip_destination' )
			|| is_tax( 'ytrip_category' );
	}

	/**
	 * Get optimized tour image HTML.
	 *
	 * @param int    $tour_id Tour ID.
	 * @param string $size    Image size.
	 * @param array  $attr    Additional attributes.
	 * @return string Image HTML.
	 */
	public function get_tour_image( int $tour_id, string $size = 'ytrip-card', array $attr = array() ) {
		$thumbnail_id = get_post_thumbnail_id( $tour_id );

		if ( ! $thumbnail_id ) {
			return $this->get_placeholder_image( $size );
		}

		$default_attr = array(
			'loading'  => 'lazy',
			'decoding' => 'async',
			'class'    => 'ytrip-tour-img',
		);

		$attr = array_merge( $default_attr, $attr );

		// Get image with srcset.
		$html = wp_get_attachment_image( $thumbnail_id, $size, false, $attr );

		// Apply blur placeholder filter.
		return apply_filters( 'ytrip_tour_thumbnail', $html, $thumbnail_id );
	}

	/**
	 * Get placeholder image.
	 *
	 * @param string $size Image size.
	 * @return string Placeholder HTML.
	 */
	private function get_placeholder_image( string $size ) {
		$sizes = array(
			'ytrip-card'   => array( 400, 300 ),
			'ytrip-card-2x' => array( 800, 600 ),
			'ytrip-hero'   => array( 1200, 600 ),
		);

		$dimensions = $sizes[ $size ] ?? array( 400, 300 );

		return sprintf(
			'<div class="ytrip-placeholder-image" style="width:%dpx;height:%dpx;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);">
				<svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
					<circle cx="8.5" cy="8.5" r="1.5"/>
					<polyline points="21 15 16 10 5 21"/>
				</svg>
			</div>',
			$dimensions[0],
			$dimensions[1]
		);
	}

	/**
	 * Generate blur hash placeholder (LQIP).
	 *
	 * @param int $image_id Image attachment ID.
	 * @return string Base64 encoded blur placeholder.
	 */
	public function generate_blur_hash( int $image_id ) {
		$blur_url = wp_get_attachment_image_url( $image_id, 'ytrip-blur' );

		if ( ! $blur_url ) {
			return '';
		}

		$blur_path = str_replace( site_url(), ABSPATH, $blur_url );

		if ( ! file_exists( $blur_path ) ) {
			return '';
		}

		$image_data = file_get_contents( $blur_path );

		if ( ! $image_data ) {
			return '';
		}

		return 'data:image/jpeg;base64,' . base64_encode( $image_data );
	}
}

// Initialize.
YTrip_Image_Optimizer::instance();

/**
 * Helper function to get optimized tour image.
 *
 * @param int    $tour_id Tour ID.
 * @param string $size    Image size.
 * @param array  $attr    Additional attributes.
 * @return string Image HTML.
 */
function ytrip_get_tour_image( int $tour_id, string $size = 'ytrip-card', array $attr = array() ) {
	return YTrip_Image_Optimizer::instance()->get_tour_image( $tour_id, $size, $attr );
}
