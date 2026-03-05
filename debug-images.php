<?php
/**
 * YTrip Debug — Dump tour meta to diagnose missing images.
 * Usage: Access via WP-CLI or browser (admin only).
 * Delete after debugging.
 *
 * @package YTrip
 */

// Load WordPress.
$wp_load_paths = array(
	dirname( __FILE__ ) . '/../../../wp-load.php',
	dirname( __FILE__ ) . '/../../../../wp-load.php',
	dirname( __FILE__ ) . '/../wp-load.php',
);
$loaded = false;
foreach ( $wp_load_paths as $path ) {
	if ( file_exists( $path ) ) {
		require_once $path;
		$loaded = true;
		break;
	}
}
if ( ! $loaded ) {
	die( 'Cannot find wp-load.php' );
}

// Security: admin only.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Admin access required.' );
}

header( 'Content-Type: text/plain; charset=utf-8' );

// Get all tours.
$tours = get_posts( array(
	'post_type'      => 'ytrip_tour',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
) );

echo "=== YTrip Tour Image Debug ===\n";
echo "Total published tours: " . count( $tours ) . "\n\n";

foreach ( $tours as $tour ) {
	echo str_repeat( '-', 60 ) . "\n";
	echo "Tour: {$tour->post_title} (ID: {$tour->ID})\n";
	echo "URL: " . get_permalink( $tour->ID ) . "\n";

	// Featured image.
	$thumb_id = get_post_thumbnail_id( $tour->ID );
	echo "Featured Image ID: " . ( $thumb_id ? $thumb_id : 'NONE' ) . "\n";
	if ( $thumb_id ) {
		echo "  → URL: " . wp_get_attachment_url( $thumb_id ) . "\n";
	}

	// Tour meta.
	$meta = get_post_meta( $tour->ID, 'ytrip_tour_details', true );
	if ( ! is_array( $meta ) ) {
		echo "Meta: NOT AN ARRAY (type: " . gettype( $meta ) . ", value: " . var_export( $meta, true ) . ")\n";
	} else {
		// Gallery.
		$gallery_raw = isset( $meta['tour_gallery'] ) ? $meta['tour_gallery'] : 'KEY NOT SET';
		echo "tour_gallery raw: " . var_export( $gallery_raw, true ) . "\n";

		if ( function_exists( 'ytrip_get_gallery_ids' ) ) {
			$ids = ytrip_get_gallery_ids( $meta );
			echo "Parsed gallery IDs: [" . implode( ', ', $ids ) . "] (" . count( $ids ) . " images)\n";
		}

		// Duration.
		$dur = isset( $meta['tour_duration'] ) ? $meta['tour_duration'] : ( isset( $meta['duration'] ) ? $meta['duration'] : 'KEY NOT SET' );
		echo "duration: " . ( is_array( $dur ) ? json_encode( $dur ) : var_export( $dur, true ) ) . "\n";

		// Group size.
		$gs = isset( $meta['group_size'] ) ? $meta['group_size'] : 'KEY NOT SET';
		echo "group_size: " . ( is_array( $gs ) ? json_encode( $gs ) : var_export( $gs, true ) ) . "\n";

		// Hero gallery mode.
		$hgm = isset( $meta['hero_gallery_mode'] ) ? $meta['hero_gallery_mode'] : 'KEY NOT SET';
		echo "hero_gallery_mode: " . var_export( $hgm, true ) . "\n";
	}

	// Effective thumbnail.
	if ( function_exists( 'ytrip_get_effective_thumbnail_id' ) ) {
		$eff = ytrip_get_effective_thumbnail_id( $tour->ID, $meta );
		echo "Effective thumbnail ID: " . ( $eff ? $eff : 'NONE' ) . "\n";
		if ( $eff ) {
			echo "  → URL: " . wp_get_attachment_url( $eff ) . "\n";
		}
	}

	echo "\n";
}

echo "=== END ===\n";
