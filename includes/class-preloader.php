<?php
/**
 * YTrip Preloader
 *
 * Implements prefetching, preloading, and predictive loading
 * to reduce perceived load times.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preloader Class
 */
class YTrip_Preloader {

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
		// Add prefetch hints.
		add_action( 'wp_head', array( $this, 'add_prefetch_hints' ), 5 );

		// Add preload script.
		add_action( 'wp_footer', array( $this, 'add_hover_prefetch_script' ), 100 );

		// REST API for prefetching.
		add_action( 'rest_api_init', array( $this, 'register_prefetch_endpoints' ) );
	}

	/**
	 * Add prefetch hints for likely next pages.
	 *
	 * @return void
	 */
	public function add_prefetch_hints() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}

		// Prefetch common assets.
		$prefetch_assets = array();

		// On archive, prefetch single tour assets.
		if ( is_post_type_archive( 'ytrip_tour' ) || is_tax() ) {
			$prefetch_assets[] = YTRIP_URL . 'assets/dist/ytrip-single.min.css';
			$prefetch_assets[] = YTRIP_URL . 'assets/dist/ytrip-single.min.js';
		}

		// On single, prefetch related tours.
		if ( is_singular( 'ytrip_tour' ) ) {
			global $post;
			$related = $this->get_related_tour_urls( $post->ID, 3 );
			foreach ( $related as $url ) {
				$prefetch_assets[] = $url;
			}
		}

		foreach ( $prefetch_assets as $url ) {
			$type = pathinfo( $url, PATHINFO_EXTENSION );
			$as = $type === 'css' ? 'style' : ( $type === 'js' ? 'script' : 'document' );
			
			printf(
				'<link rel="prefetch" href="%s" as="%s">%s',
				esc_url( $url ),
				esc_attr( $as ),
				"\n"
			);
		}
	}

	/**
	 * Add hover prefetch script.
	 *
	 * @return void
	 */
	public function add_hover_prefetch_script() {
		if ( ! $this->is_ytrip_page() ) {
			return;
		}
		?>
		<script id="ytrip-prefetcher">
		(function() {
			'use strict';

			var prefetchedUrls = new Set();
			var prefetchQueue = [];
			var isIdle = true;

			// Use requestIdleCallback for non-blocking prefetch
			var requestIdle = window.requestIdleCallback || function(cb) { setTimeout(cb, 1); };

			function prefetchUrl(url) {
				if (prefetchedUrls.has(url)) return;
				prefetchedUrls.add(url);

				// Use link prefetch
				var link = document.createElement('link');
				link.rel = 'prefetch';
				link.href = url;
				link.as = 'document';
				document.head.appendChild(link);
			}

			function processPrefetchQueue() {
				if (prefetchQueue.length === 0) {
					isIdle = true;
					return;
				}

				var url = prefetchQueue.shift();
				prefetchUrl(url);

				requestIdle(processPrefetchQueue);
			}

			function queuePrefetch(url) {
				if (prefetchedUrls.has(url) || prefetchQueue.includes(url)) return;
				
				prefetchQueue.push(url);
				
				if (isIdle) {
					isIdle = false;
					requestIdle(processPrefetchQueue);
				}
			}

			// Prefetch on hover with delay
			var hoverTimer = null;
			document.addEventListener('mouseover', function(e) {
				var link = e.target.closest('a[href*="/tour/"], a[href*="ytrip_tour"]');
				if (!link) return;

				var url = link.href;
				if (!url || url.indexOf(window.location.origin) !== 0) return;

				hoverTimer = setTimeout(function() {
					queuePrefetch(url);
				}, 65); // 65ms delay to avoid too eager prefetching
			});

			document.addEventListener('mouseout', function(e) {
				if (hoverTimer) {
					clearTimeout(hoverTimer);
					hoverTimer = null;
				}
			});

			// Prefetch on touchstart for mobile
			document.addEventListener('touchstart', function(e) {
				var link = e.target.closest('a[href*="/tour/"], a[href*="ytrip_tour"]');
				if (!link) return;
				queuePrefetch(link.href);
			}, { passive: true });

			// Prefetch visible links with Intersection Observer
			if ('IntersectionObserver' in window) {
				var observer = new IntersectionObserver(function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting) {
							var link = entry.target;
							// Only prefetch if in viewport for more than 1 second
							setTimeout(function() {
								if (entry.isIntersecting) {
									queuePrefetch(link.href);
								}
							}, 1000);
						}
					});
				}, { rootMargin: '50px' });

				// Observe tour links
				document.querySelectorAll('.ytrip-tour-card a').forEach(function(link) {
					observer.observe(link);
				});
			}

			// Prefetch on scroll (upcoming items)
			var scrollTimer = null;
			window.addEventListener('scroll', function() {
				if (scrollTimer) return;
				
				scrollTimer = setTimeout(function() {
					scrollTimer = null;
					
					// Get items near viewport
					var viewportBottom = window.scrollY + window.innerHeight;
					document.querySelectorAll('.ytrip-tour-card a').forEach(function(link) {
						var rect = link.getBoundingClientRect();
						var itemTop = window.scrollY + rect.top;
						
						// Prefetch items just below viewport
						if (itemTop > viewportBottom && itemTop < viewportBottom + 500) {
							queuePrefetch(link.href);
						}
					});
				}, 100);
			}, { passive: true });
		})();
		</script>
		<?php
	}

	/**
	 * Get related tour URLs for prefetching.
	 *
	 * @param int $tour_id Current tour ID.
	 * @param int $limit   Number of URLs.
	 * @return array URLs.
	 */
	private function get_related_tour_urls( int $tour_id, int $limit = 3 ) {
		$cache_key = 'related_urls_' . $tour_id;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Get destination.
		$destinations = wp_get_post_terms( $tour_id, 'ytrip_destination', array( 'fields' => 'ids' ) );

		$args = array(
			'post_type'      => 'ytrip_tour',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $tour_id ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! is_wp_error( $destinations ) && ! empty( $destinations ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'ytrip_destination',
					'field'    => 'term_id',
					'terms'    => $destinations,
				),
			);
		}

		$related = get_posts( $args );
		$urls = array_map( 'get_permalink', $related );

		set_transient( $cache_key, $urls, HOUR_IN_SECONDS );

		return $urls;
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
	 * Register prefetch REST endpoints.
	 *
	 * @return void
	 */
	public function register_prefetch_endpoints() {
		// Quick tour preview data for hover cards.
		register_rest_route( 'ytrip/v1', '/prefetch/tour/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_tour_preview' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get minimal tour preview data.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_tour_preview( \WP_REST_Request $request ): \WP_REST_Response {
		$tour_id = absint( $request->get_param( 'id' ) );

		$cache_key = 'tour_preview_' . $tour_id;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return new \WP_REST_Response( $cached );
		}

		$post = get_post( $tour_id );

		if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
			return new \WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}

		$data = array(
			'id'        => $tour_id,
			'title'     => $post->post_title,
			'thumbnail' => get_the_post_thumbnail_url( $tour_id, 'ytrip-card' ),
			'price'     => get_post_meta( $tour_id, '_ytrip_price', true ),
			'duration'  => get_post_meta( $tour_id, '_ytrip_duration', true ),
			'rating'    => get_post_meta( $tour_id, '_ytrip_rating_average', true ),
		);

		set_transient( $cache_key, $data, 30 * MINUTE_IN_SECONDS );

		return new \WP_REST_Response( $data );
	}
}

// Initialize.
YTrip_Preloader::instance();
