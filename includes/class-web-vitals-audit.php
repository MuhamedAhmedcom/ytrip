<?php
/**
 * YTrip Web Vitals Audit – Findings Reference
 *
 * Central list of LCP/CLS/INP issues and fixes for Core Web Vitals (PageSpeed/Lighthouse).
 * Used by performance refactors and QA. Not a user-facing guide.
 *
 * @package YTrip
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YTrip_Web_Vitals_Audit
 */
class YTrip_Web_Vitals_Audit {

	/**
	 * Findings grouped by metric (LCP, CLS, INP).
	 * Format: [ 'file' => relative path, 'line' => int, 'issue' => string, 'impact' => string ]
	 *
	 * @var array<string, array<int, array>>
	 */
	public static $findings = array(
		'LCP' => array(
			array(
				'file'   => 'templates/homepage/hero-slider.php',
				'line'   => 52,
				'issue'  => 'Hero slide <img> without width/height, fetchpriority, or preload for first slide.',
				'impact' => 'LCP element (hero image) not prioritized; layout not reserved → slower LCP.',
			),
			array(
				'file'   => 'public/class-frontend.php',
				'line'   => 273,
				'issue'  => 'Swiper + hero-slider.js enqueued on every single tour even when hero is single_image.',
				'impact' => 'Unnecessary ~100KB+ JS on single-image hero pages → blocks main thread, hurts LCP.',
			),
			array(
				'file'   => 'public/class-frontend.php',
				'line'   => 214,
				'issue'  => 'ytrip-frontend script has no defer; depends on jQuery (render/parse cost).',
				'impact' => 'Parser-blocking or main-thread work can delay LCP.',
			),
			array(
				'file'   => 'includes/class-performance-engine.php',
				'line'   => 563,
				'issue'  => 'Critical CSS only output when enable_critical_css option is on; default off.',
				'impact' => 'Above-the-fold content may wait for full CSS → worse LCP when disabled.',
			),
			array(
				'file'   => 'templates/parts/tour-card.php',
				'line'   => 37,
				'issue'  => 'the_post_thumbnail() with no explicit dimensions or aspect-ratio on wrapper.',
				'impact' => 'Card image can shift when loaded (CLS); also affects LCP if card is in viewport.',
			),
		),
		'CLS' => array(
			array(
				'file'   => 'templates/parts/tour-card.php',
				'line'   => 34,
				'issue'  => '.ytrip-tour-card__image has no aspect-ratio or min-height; image loads without reserved space.',
				'impact' => 'Layout shift when thumbnail loads → CLS.',
			),
			array(
				'file'   => 'templates/homepage/hero-slider.php',
				'line'   => 52,
				'issue'  => 'Hero <img> without width/height; .ytrip-hero__bg has no fixed aspect-ratio.',
				'impact' => 'Hero section resizes when image loads → CLS.',
			),
			array(
				'file'   => 'templates/homepage/destinations.php',
				'line'   => 57,
				'issue'  => 'Destination card image has loading="lazy" but no width/height or aspect-ratio on container.',
				'impact' => 'Shift when image loads.',
			),
			array(
				'file'   => 'templates/homepage/categories.php',
				'line'   => 71,
				'issue'  => 'Category image has loading="lazy" but no dimensions/aspect-ratio on wrapper.',
				'impact' => 'Shift when image loads.',
			),
			array(
				'file'   => 'templates/single/layout-1-classic.php',
				'line'   => 164,
				'issue'  => 'wp_get_attachment_image() in hero grid without loading="lazy" or aspect-ratio on .ytrip-hero__grid-item.',
				'impact' => 'Grid items can shift.',
			),
			array(
				'file'   => 'templates/homepage/testimonials.php',
				'line'   => 75,
				'issue'  => 'Testimonial <img> without width/height or reserved space.',
				'impact' => 'CLS when testimonial images load.',
			),
			array(
				'file'   => 'templates/single/layout-1-classic.php',
				'line'   => 90,
				'issue'  => 'Large inline <style> block for theme overrides; can cause reflow if applied late.',
				'impact' => 'Potential reflow/CLS if layout recalculates.',
			),
		),
		'INP' => array(
			array(
				'file'   => 'public/class-frontend.php',
				'line'   => 214,
				'issue'  => 'Main frontend script not deferred; heavy first interaction (tabs, wishlist, slider init).',
				'impact' => 'INP can spike on first click if JS is still parsing/executing.',
			),
			array(
				'file'   => 'includes/class-performance-engine.php',
				'line'   => 451,
				'issue'  => 'optimize_script_loading only defers ytrip-single, ytrip-archive, ytrip-effects; ytrip-frontend not in list.',
				'impact' => 'ytrip-frontend blocks or runs early → worse INP.',
			),
			array(
				'file'   => 'assets/js/hero-slider.js',
				'line'   => 1,
				'issue'  => 'Swiper initialization runs on DOMContentLoaded; can be heavy on first interaction if user taps slider.',
				'impact' => 'INP for first tap on slider controls.',
			),
		),
	);

	/**
	 * Whether a finding has been addressed (for tracking).
	 *
	 * @param string $metric LCP, CLS, or INP.
	 * @param int    $index  Index in self::$findings[ $metric ].
	 * @return bool
	 */
	public static function is_fixed( $metric, $index ) {
		$fixed = get_option( 'ytrip_web_vitals_fixed', array() );
		return ! empty( $fixed[ $metric ][ $index ] );
	}

	/**
	 * Pass targets for all PageSpeed/Lighthouse categories (to pass all tests).
	 *
	 * @return array Category key => minimum score (0–100) and metric targets.
	 */
	public static function get_pass_targets() {
		return array(
			'performance'   => array(
				'score'  => 90,
				'LCP'    => 2.5,
				'FCP'    => 1.8,
				'CLS'    => 0.1,
				'TBT_ms' => 200,
				'SI'     => 3.4,
			),
			'accessibility' => array( 'score' => 90 ),
			'best-practices' => array( 'score' => 90 ),
			'seo'            => array( 'score' => 90 ),
		);
	}
}
