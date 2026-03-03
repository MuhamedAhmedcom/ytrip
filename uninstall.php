<?php
/**
 * YTrip Uninstall Script
 * 
 * Cleans up all plugin data when uninstalled.
 * CodeCanyon requirement.
 *
 * @package YTrip
 * @since 2.0.0
 */

// Exit if not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
$options_to_delete = [
    'ytrip_settings',
    'ytrip_homepage',
    'ytrip_version',
    'ytrip_db_version',
    'ytrip_flush_rewrite_rules',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option); // For multisite
}

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_ytrip_%' 
    OR option_name LIKE '_transient_timeout_ytrip_%'"
);

// Delete post meta
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
    WHERE meta_key LIKE 'ytrip_%' 
    OR meta_key LIKE '_ytrip_%'"
);

// Delete term meta
$wpdb->query(
    "DELETE FROM {$wpdb->termmeta} 
    WHERE meta_key LIKE 'ytrip_%'"
);

// Delete user meta
$wpdb->query(
    "DELETE FROM {$wpdb->usermeta} 
    WHERE meta_key LIKE 'ytrip_%'"
);

// Drop custom tables
$tables_to_drop = [
    "{$wpdb->prefix}ytrip_bookings",
    "{$wpdb->prefix}ytrip_reviews",
    "{$wpdb->prefix}ytrip_wishlists",
];

foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete custom posts
$post_types = ['ytrip_tour'];

foreach ($post_types as $post_type) {
    $posts = get_posts([
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ]);

    foreach ($posts as $post_id) {
        // Delete attached media
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post_parent' => $post_id,
            'fields' => 'ids',
        ]);

        foreach ($attachments as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }

        // Delete post
        wp_delete_post($post_id, true);
    }
}

// Delete custom taxonomies terms
$taxonomies = ['ytrip_destination', 'ytrip_category', 'ytrip_tag'];

foreach ($taxonomies as $taxonomy) {
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }
}

// Clear any cached data
wp_cache_flush();

// Clear scheduled cron events
wp_clear_scheduled_hook('ytrip_daily_cleanup');
wp_clear_scheduled_hook('ytrip_clear_expired_transients');
wp_clear_scheduled_hook('ytrip_sync_ical');

// Log uninstall
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('YTrip: Plugin uninstalled and all data cleaned up.');
}
