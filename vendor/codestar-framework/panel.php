<?php
/**
 * Plugin Name: ProWPSite Panel Framework
 * Plugin URI: https://prowpsite.com/
 * Description: Advanced WordPress Control Panel with Custom Post Types and Fields Management
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Muhamed Ahmed
 * Author URI: https://prowpsite.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: prowpsite-panel
 * Domain Path: /languages
 *
 * @package ProWPSite
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('PROWP_VERSION')) {
    define('PROWP_VERSION', '1.0.0');
}

if (!defined('PROWP_FILE')) {
    define('PROWP_FILE', __FILE__);
}

if (!defined('PROWP_PATH')) {
    define('PROWP_PATH', plugin_dir_path(__FILE__));
}

if (!defined('PROWP_URL')) {
    define('PROWP_URL', plugin_dir_url(__FILE__));
}

if (!defined('PROWP_BASENAME')) {
    define('PROWP_BASENAME', plugin_basename(__FILE__));
}



if (!defined('PROWP_PATH')) {
    define('PROWP_PATH', plugin_dir_path(__FILE__));
}

if (!defined('PROWP_URL')) {
    define('PROWP_URL', plugin_dir_url(__FILE__));
}

// Load required files
require_once PROWP_PATH . 'classes/setup.class.php';
require_once PROWP_PATH . 'options/admin-options.php';
