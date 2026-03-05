<?php
/**
 * Debug script to confirm registered widgets.
 */
define('WP_USE_THEMES', false);
require('./wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wp_widget_factory;

header('Content-Type: text/plain');

echo "Registered Widgets:\n";
echo "===================\n";

foreach ($wp_widget_factory->widgets as $id => $widget) {
    echo "ID: " . $id . " | Name: " . $widget->name . "\n";
}

echo "\nHidden from Legacy Widget Block (Filter Check):\n";
echo "===============================================\n";
$hidden = apply_filters('widget_types_to_hide_from_legacy_widget_block', array());
print_r($hidden);
