<?php
/**
 * YTrip helper functions
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta keys used for archive filter and sort queries.
 * For best performance on large catalogs, ensure wp_postmeta has indexes on (meta_key, meta_value)
 * for these keys if your host allows (e.g. via plugin or DB console).
 *
 * @return string[] List of meta key names.
 */
function ytrip_get_filter_meta_keys() {
    return array(
        '_ytrip_price',
        '_ytrip_duration_days',
        '_ytrip_rating',
        '_ytrip_views',
        '_ytrip_max_capacity',
    );
}

/**
 * Get the effective tour card template style (style_1 … style_10) from settings.
 * Prefers tour_card_style; falls back to mapping legacy card_style (standard, modern, minimal, overlay).
 *
 * @return string One of style_1 … style_10.
 */
function ytrip_get_card_style() {
	$options = get_option( 'ytrip_settings', array() );
	$tour_card = isset( $options['tour_card_style'] ) ? $options['tour_card_style'] : '';
	if ( $tour_card !== '' && preg_match( '/^style_(?:[1-9]|10)$/', $tour_card ) ) {
		return $tour_card;
	}
	$legacy = isset( $options['card_style'] ) ? $options['card_style'] : '';
	$map = array(
		'standard' => 'style_2',
		'modern'   => 'style_3',
		'minimal'  => 'style_4',
		'overlay'  => 'style_1',
	);
	return isset( $map[ $legacy ] ) ? $map[ $legacy ] : 'style_1';
}

/**
 * Whether the current request is a YTrip plugin page (single tour, archive, taxonomies, front/home, or any page with a ytrip shortcode).
 * Use this for conditional asset loading and Critical CSS so plugin CSS/JS do not load globally.
 *
 * @return bool
 */
function ytrip_is_plugin_page() {
    if ( is_singular( 'ytrip_tour' ) ) {
        return true;
    }
    if ( is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' ) ) {
        return true;
    }
    if ( is_front_page() || is_home() ) {
        return true;
    }
    global $post;
    if ( $post && ! empty( $post->post_content ) ) {
        $shortcodes = array( 'ytrip_homepage', 'ytrip_section', 'ytrip_dashboard', 'ytrip_tours_map', 'ytrip_reviews', 'ytrip_tours', 'ytrip_search', 'ytrip_agent_dashboard', 'ytrip_agent_register' );
        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }
        if ( has_shortcode( $post->post_content, 'ytrip_' ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Whether transparent header option is enabled (Codestar switcher may save 1, '1', true, etc.).
 *
 * @return bool
 */
function ytrip_has_transparent_header_enabled() {
    // Bypass object cache so body class reflects current setting (avoids stale cache after save).
    wp_cache_delete( 'ytrip_settings', 'options' );
    $opts = get_option( 'ytrip_settings', array() );
    if ( ! is_array( $opts ) ) {
        return false;
    }
    $on = ! empty( $opts['transparent_header'] );
    return (bool) apply_filters( 'ytrip_has_transparent_header', $on );
}

/**
 * Add ytrip-transparent-header to body class when the option is on and we're on tour archive or single tour.
 * Hooked directly so the class is applied even if Frontend loads late.
 *
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function ytrip_add_transparent_header_body_class( $classes ) {
    if ( ! is_array( $classes ) ) {
        return $classes;
    }
    $is_tour_page = is_singular( 'ytrip_tour' ) || is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' );
    if ( ! $is_tour_page || ! ytrip_has_transparent_header_enabled() ) {
        return $classes;
    }
    $classes[] = 'ytrip-transparent-header';
    $GLOBALS['ytrip_transparent_header_filter_added'] = true;
    return $classes;
}

/**
 * Output hero image with wrapper and skeleton placeholder for LCP/CLS.
 * Reserves space (aspect-ratio), shows skeleton until load, optional fetchpriority for first image.
 *
 * @param int  $attachment_id Attachment ID.
 * @param bool $is_first      True for the first/above-the-fold hero image (LCP).
 * @return string HTML for the wrapped image.
 */
function ytrip_hero_image_with_skeleton( $attachment_id, $is_first = false ) {
    $attachment_id = (int) $attachment_id;
    if ( ! $attachment_id ) {
        return '';
    }
    $attrs = array(
        'class'    => 'ytrip-hero-image',
        'loading'  => $is_first ? 'eager' : 'lazy',
        'decoding' => 'async',
        'sizes'    => '(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1920px',
    );
    if ( $is_first ) {
        $attrs['fetchpriority'] = 'high';
    }
    // Use 'large' so WordPress outputs srcset/sizes (improve image delivery, smaller payload on mobile).
    $img = wp_get_attachment_image( $attachment_id, 'large', false, $attrs );
    if ( empty( $img ) ) {
        return '';
    }
    return '<div class="ytrip-img-wrap ytrip-img-wrap--hero">'
        . '<span class="ytrip-img-skeleton" aria-hidden="true"></span>'
        . $img
        . '</div>';
}

/**
 * Wrap existing image HTML with skeleton placeholder (e.g. for the_post_thumbnail).
 *
 * @param string $img_html Image HTML.
 * @param bool   $is_hero  Add hero wrapper class for aspect-ratio.
 * @return string Wrapped HTML.
 */
function ytrip_wrap_image_with_skeleton( $img_html, $is_hero = true ) {
    if ( trim( (string) $img_html ) === '' ) {
        return '';
    }
    $class = 'ytrip-img-wrap';
    if ( $is_hero ) {
        $class .= ' ytrip-img-wrap--hero';
    }
    return '<div class="' . esc_attr( $class ) . '">'
        . '<span class="ytrip-img-skeleton" aria-hidden="true"></span>'
        . $img_html
        . '</div>';
}
/**
 * Get gallery attachment IDs from tour meta, handling both CSV string and array formats.
 *
 * @param array $meta Tour details meta array.
 * @return int[]
 */
function ytrip_get_gallery_ids( $meta ) {
	if ( empty( $meta['tour_gallery'] ) ) {
		return array();
	}
	$raw = $meta['tour_gallery'];
	if ( is_string( $raw ) ) {
		$ids = explode( ',', $raw );
	} else {
		$ids = (array) $raw;
	}
	return array_filter( array_map( 'absint', $ids ) );
}

/**
 * Get the effective thumbnail ID for a tour.
 * Falls back to the first gallery image if no featured image is set.
 *
 * @param int   $tour_id Tour ID.
 * @param array $meta    Optional. Pre-fetched meta.
 * @return int Attachment ID.
 */
function ytrip_get_effective_thumbnail_id( $tour_id, $meta = null ) {
	$thumb_id = has_post_thumbnail( $tour_id ) ? (int) get_post_thumbnail_id( $tour_id ) : 0;
	if ( $thumb_id ) {
		return $thumb_id;
	}

	if ( null === $meta ) {
		$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
	}
	$gallery = ytrip_get_gallery_ids( is_array( $meta ) ? $meta : array() );

	if ( ! empty( $gallery ) ) {
		$fallback_id = $gallery[0];
		// Optional: Persist to DB so WP core functions see it.
		// We do this here once to ensure consistency.
		set_post_thumbnail( $tour_id, $fallback_id );
		return $fallback_id;
	}

	return 0;
}
