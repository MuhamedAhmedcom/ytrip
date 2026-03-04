<?php
/**
 * Plugin Name: YTrip - Travel Booking Manager
 * Plugin URI: https://ytrip.com
 * Description: Professional travel/tourism booking system with WooCommerce integration, optimized for 100/100 performance and security scores.
 * Version: 2.1.4
 * Author: YTrip
 * Author URI: https://ytrip.com
 * Text Domain: ytrip
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// =========================================================================
// Constants
// =========================================================================

define('YTRIP_VERSION', '2.1.4');
define('YTRIP_FILE', __FILE__);
define('YTRIP_PATH', plugin_dir_path(__FILE__));
define('YTRIP_URL', plugin_dir_url(__FILE__));
define('YTRIP_BASENAME', plugin_basename(__FILE__));
define('YTRIP_CACHE_GROUP', 'ytrip_v2');
define('YTRIP_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// =========================================================================
// Autoloader
// =========================================================================

spl_autoload_register(function (string $class): void {
    if (strpos($class, 'YTrip') !== 0) {
        return;
    }

    $file = str_replace(['YTrip_', '_'], ['', '-'], $class);
    $path = YTRIP_PATH . 'includes/class-' . strtolower($file) . '.php';

    if (file_exists($path)) {
        require_once $path;
        return;
    }

    $admin_path = YTRIP_PATH . 'admin/class-' . strtolower($file) . '.php';
    if (file_exists($admin_path) && is_admin()) {
        require_once $admin_path;
    }
});

// =========================================================================
// Core Plugin Class
// =========================================================================

final class YTrip {
        
        private static $instance = null;
    private $initialized = false;

        public static function instance() {
        if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

    private function __construct() {
        $this->load_dependencies();
            $this->init_hooks();
    }

    private function load_dependencies() {
        // Admin: ensure do_settings_sections() and submit_button() exist before Codestar/settings run.
        if (is_admin()) {
            $template = ABSPATH . 'wp-admin/includes/template.php';
            if (file_exists($template) && !function_exists('do_settings_sections')) {
                require_once $template;
            }
        }

        // Codestar Framework
        if (!class_exists('CSF') && file_exists(YTRIP_PATH . 'vendor/codestar-framework/classes/setup.class.php')) {
            require_once YTRIP_PATH . 'vendor/codestar-framework/classes/setup.class.php';
        }

        // Core classes and includes
        $core = [
            'class-web-vitals-audit.php',
            'class-performance-engine.php',
            'class-security-engine.php',
            'class-dark-mode.php',
            'dark-mode-toggle.php',
            'class-template-loader.php',
            'class-post-types.php',
            'class-taxonomies.php',
            'class-tour-display.php',
            'class-related-tours.php',
            'class-rating-display.php',
            'class-homepage-builder.php',
            'class-demo-importer.php',
            'class-theme-compatibility.php',
            'class-brand-system.php',
            'class-ajax.php',
            'class-booking-form.php',
            'class-shortcodes.php',
            'class-dynamic-css.php',
            'class-font-downloader.php',
            'class-archive-filters.php',
            'class-ytrip-widgets.php',
            'helper-functions.php',
            'footer-functions.php',
            'shortcodes/review-form-shortcode.php',
		'review-form-shortcode.php',
        ];

        foreach ($core as $file) {
            $path = YTRIP_PATH . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Admin
        if (is_admin()) {
            $admin = ['codestar-config.php', 'class-admin-config.php', 'class-admin.php', 'class-admin-bookings.php', 'homepage-builder.php'];
            foreach ($admin as $file) {
                $path = YTRIP_PATH . 'admin/' . $file;
                if (file_exists($path)) {
                    require_once $path;
                }
            }
        }

        // Public
        $public = ['class-frontend.php', 'class-homepage.php'];
        foreach ($public as $file) {
            $path = YTRIP_PATH . 'public/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // WooCommerce
        if (class_exists('WooCommerce')) {
            $path = YTRIP_PATH . 'includes/class-woocommerce-integration.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_hooks() {
        // FIX: Load textdomain on 'init' for WordPress 6.7+
        add_action('init', [$this, 'load_textdomain'], 0);

        // Initialize Brand System
        if (class_exists('YTrip_Brand_System')) {
            YTrip_Brand_System::instance();
        }

        add_action('plugins_loaded', [$this, 'on_plugins_loaded'], 5);
        add_action('init', [$this, 'on_init'], 99); // Priority 99 to flush AFTER post types are registered
        add_action('save_post_ytrip_tour', [$this, 'sync_tour_price_meta'], 15, 2);
        add_action('save_post_ytrip_tour', [$this, 'sync_tour_capacity_meta'], 18, 2);
        add_action('save_post_ytrip_tour', [$this, 'migrate_hero_gallery_meta'], 20, 2);
        add_filter('csf_ytrip_tour_details_save', [$this, 'csf_migrate_hero_gallery_on_save'], 10, 3);

        // Template loader
        add_filter('template_include', [$this, 'template_loader'], 99);

        register_activation_hook(YTRIP_FILE, [$this, 'activate']);
        register_deactivation_hook(YTRIP_FILE, [$this, 'deactivate']);

        add_filter('plugin_action_links_' . YTRIP_BASENAME, [$this, 'action_links']);

        // Register body_class for transparent header.
        if (function_exists('ytrip_add_transparent_header_body_class')) {
            add_filter('body_class', 'ytrip_add_transparent_header_body_class', 999);
        }
        add_filter('template_include', [$this, 'register_transparent_header_body_class'], 1);

        if (YTRIP_DEBUG) {
            add_action('wp_footer', [$this, 'debug_transparent_header'], 999);
        }

        // Purge cache AND flush rewrites when settings are saved.
        add_action('csf_ytrip_settings_saved', [$this, 'purge_cache_on_settings_save'], 10, 2);
        add_action('csf_ytrip_settings_saved', [$this, 'flush_rewrites_on_settings_save'], 10, 2);
    }

    /**
     * Register body_class filter for ytrip-transparent-header on template_include (priority 1).
     * Ensures we run on the same request that outputs the body, after main query is set.
     *
     * @param string $template Template path.
     * @return string Unchanged template path.
     */
    public function register_transparent_header_body_class($template) {
        if (function_exists('ytrip_add_transparent_header_body_class')) {
            add_filter('body_class', 'ytrip_add_transparent_header_body_class', 999);
        }
        return $template;
    }

    /**
     * Debug: output HTML comment so we can confirm in View Source whether body_class ran and option state.
     * Remove or disable in production.
     */
    public function debug_transparent_header() {
        $is_tour = is_singular( 'ytrip_tour' ) || is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' );
        if ( ! $is_tour ) {
            return;
        }
        $opts      = get_option( 'ytrip_settings', array() );
        $raw_val   = isset( $opts['transparent_header'] ) ? var_export( $opts['transparent_header'], true ) : 'not_set';
        $option_on = function_exists( 'ytrip_has_transparent_header_enabled' ) && ytrip_has_transparent_header_enabled();
        $classes   = get_body_class();
        $has_class = in_array( 'ytrip-transparent-header', $classes, true );
        $added     = ( ! empty( $GLOBALS['ytrip_transparent_header_filter_added'] ) ) ? 'yes' : 'no';
        echo '<!-- ytrip-transparent-header-debug: is_tour_page=1 option_raw=' . esc_attr( $raw_val ) . ' option_on=' . ( $option_on ? '1' : '0' ) . ' filter_added_class=' . $added . ' get_body_class_has_it=' . ( $has_class ? '1' : '0' ) . ' -->';
    }

    /**
     * Purge page cache when settings are saved so body class and layout changes apply immediately.
     */
    public function purge_cache_on_settings_save($data, $instance) {
        do_action('litespeed_purge_all', 'YTrip settings saved');
        if (function_exists('run_litespeed_purge_all')) {
            run_litespeed_purge_all();
        } elseif (class_exists('LiteSpeed\Purge')) {
            \LiteSpeed\Purge::purge_all();
        }
        wp_cache_flush();
    }

    /**
     * Schedule a rewrite rules flush when settings are saved so slug changes take effect immediately.
     * Uses a transient so flush happens on the next request (init hook) rather than during save.
     */
    public function flush_rewrites_on_settings_save($data, $instance) {
        set_transient('ytrip_flush_rewrite_rules', true, 60);
    }

    /**
     * Load textdomain - MUST be on 'init' for WordPress 6.7+
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ytrip',
            false,
            dirname(YTRIP_BASENAME) . '/languages'
        );
    }

    /**
     * Template loader.
     */
    public function template_loader(string $template) {
        $settings = get_option('ytrip_settings', []);

        // IMPORTANT: Always use internal registered names for conditionals, NOT the URL slug settings.
        // URL slugs change the frontend URL only; taxonomy/CPT names are fixed.
        $tour_cpt  = 'ytrip_tour';
        $tax_dest  = 'ytrip_destination';
        $tax_cat   = 'ytrip_category';

        // Front page: use YTrip template when "Replace content" is enabled so sections always show
        if ((is_front_page() || is_home()) && $this->should_use_ytrip_front_page_template()) {
            $file = 'front-page.php';
            $theme_template = locate_template(['ytrip/' . $file, $file]);
            if ($theme_template) {
                return $theme_template;
            }
            $plugin_template = YTRIP_PATH . 'templates/' . $file;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Single tour: respect single_tour_layout (layout_1 … layout_5) or fallback to default template
        if (is_singular($tour_cpt)) {
            $layout = $settings['single_tour_layout'] ?? $settings['single_layout'] ?? 'layout_1';
            if (in_array($layout, ['standard', 'wide', 'fullwidth'], true)) {
                $layout = 'default_page';
            }
            $layout_files = [
                'layout_1' => 'layout-1-classic.php',
                'layout_2' => 'layout-2-modern.php',
                'layout_3' => 'layout-3-split.php',
                'layout_4' => 'layout-4-booking.php',
                'layout_5' => 'layout-5-magazine.php',
            ];
            if ($layout !== 'default_page' && isset($layout_files[$layout])) {
                $layout_file = $layout_files[$layout];
                $theme_template = locate_template(['ytrip/single/' . $layout_file, 'ytrip/' . $layout_file]);
                if ($theme_template) {
                    return $theme_template;
                }
                $plugin_layout = YTRIP_PATH . 'templates/single/' . $layout_file;
                if (file_exists($plugin_layout)) {
                    return $plugin_layout;
                }
            }
            $file = 'single-ytrip_tour.php';
            $theme_template = locate_template(['ytrip/' . $file, $file]);
            if ($theme_template) {
                return $theme_template;
            }
            return YTRIP_PATH . 'templates/' . $file;
        }

        // Archive
        if (is_post_type_archive($tour_cpt)) {
            $file = 'archive-ytrip_tour.php';
            $theme_template = locate_template(['ytrip/' . $file, $file]);
            if ($theme_template) {
                return $theme_template;
            }
            return YTRIP_PATH . 'templates/' . $file;
        }

        // Taxonomy (destination & category use same archive template as tour archive)
        if (is_tax($tax_dest) || is_tax($tax_cat)) {
            $file = 'archive-ytrip_tour.php';
            $theme_template = locate_template(['ytrip/' . $file, $file]);
            if ($theme_template) {
                return $theme_template;
            }
            return YTRIP_PATH . 'templates/' . $file;
        }

        return $template;
    }

    /**
     * Whether to use the YTrip front-page template (so sections render instead of theme content).
     */
    private function should_use_ytrip_front_page_template(): bool {
        $opts = get_option('ytrip_homepage', []);
        if (!is_array($opts)) {
            return false;
        }
        if (empty($opts['replace_content']) && $opts['replace_content'] !== '1' && $opts['replace_content'] !== 1) {
            return false;
        }
        $config = isset($opts['homepage_sections']) ? $opts['homepage_sections'] : [];
        $enabled = isset($config['enabled']) && is_array($config['enabled']) ? array_keys($config['enabled']) : [];
        if (!empty($enabled)) {
            return true;
        }
        $enable_keys = ['hero_enable', 'search_enable', 'featured_enable', 'destinations_enable', 'categories_enable', 'testimonials_enable', 'stats_enable', 'blog_enable'];
        foreach ($enable_keys as $key) {
            if (!empty($opts[$key])) {
                return true;
            }
        }
        return false;
    }

    public function on_plugins_loaded() {
        $this->initialized = true;
        do_action('ytrip_initialized');
    }

    public function on_init() {
        if (get_transient('ytrip_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_transient('ytrip_flush_rewrite_rules');
        }
        $this->maybe_migrate_bookings_table();
    }

    /**
     * Add infants column to ytrip_bookings if missing (one-time migration).
     */
    private function maybe_migrate_bookings_table(): void {
        $done = get_option('ytrip_bookings_infants_migrated', false);
        if ($done) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'ytrip_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->esc_like($table_name) . "'") !== $table_name) {
            return;
        }
        $col = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table_name}` LIKE %s", 'infants'));
        if (!empty($col)) {
            update_option('ytrip_bookings_infants_migrated', true);
            return;
        }
        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN infants int(3) DEFAULT 0 AFTER children");
        update_option('ytrip_bookings_infants_migrated', true);
    }

    /**
     * Sync _ytrip_price from tour details (pricing) so cards work with or without WooCommerce.
     */
    public function sync_tour_price_meta(int $post_id, \WP_Post $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $meta = get_post_meta($post_id, 'ytrip_tour_details', true);
        if (!is_array($meta)) {
            return;
        }
        $price = null;
        if (!empty($meta['pricing']) && is_array($meta['pricing'])) {
            $p = $meta['pricing'];
            if (isset($p['sale_price']) && $p['sale_price'] !== '' && is_numeric($p['sale_price'])) {
                $price = (float) $p['sale_price'];
            }
            if ($price === null && isset($p['regular_price']) && $p['regular_price'] !== '' && is_numeric($p['regular_price'])) {
                $price = (float) $p['regular_price'];
            }
        }
        if ($price !== null) {
            update_post_meta($post_id, '_ytrip_price', $price);
        }
    }

    /**
     * Sync _ytrip_max_capacity from group_size.max for archive/guest filter.
     */
    public function sync_tour_capacity_meta(int $post_id, \WP_Post $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $meta = get_post_meta($post_id, 'ytrip_tour_details', true);
        if (!is_array($meta) || empty($meta['group_size']) || !is_array($meta['group_size'])) {
            return;
        }
        $max = isset($meta['group_size']['max']) ? absint($meta['group_size']['max']) : 0;
        update_post_meta($post_id, '_ytrip_max_capacity', $max);
    }

    /**
     * Migrate legacy hero/gallery meta to single hero_gallery_mode (single_image | slider | carousel).
     */
    public function migrate_hero_gallery_meta(int $post_id, \WP_Post $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $meta = get_post_meta($post_id, 'ytrip_tour_details', true);
        if (!is_array($meta)) {
            return;
        }
        if (isset($meta['hero_gallery_mode']) && $meta['hero_gallery_mode'] !== '') {
            return;
        }
        $legacy_hero = isset($meta['single_hero_type']) ? sanitize_key($meta['single_hero_type']) : '';
        $legacy_gallery = isset($meta['gallery_display_mode']) ? sanitize_key($meta['gallery_display_mode']) : '';
        $new_mode = 'single_image';
        if ($legacy_hero === 'slider_carousel' && $legacy_gallery === 'carousel') {
            $new_mode = 'carousel';
        } elseif ($legacy_hero === 'slider_carousel') {
            $new_mode = 'slider';
        }
        $meta['hero_gallery_mode'] = $new_mode;
        update_post_meta($post_id, 'ytrip_tour_details', $meta);
    }

    /**
     * When Codestar saves tour details, set hero_gallery_mode from legacy keys if not in form.
     */
    public function csf_migrate_hero_gallery_on_save(array $data, int $post_id, $instance) {
        if (!is_array($data)) {
            return $data;
        }
        if (isset($data['hero_gallery_mode']) && $data['hero_gallery_mode'] !== '') {
            return $data;
        }
        $legacy_hero = isset($data['single_hero_type']) ? sanitize_key($data['single_hero_type']) : '';
        $legacy_gallery = isset($data['gallery_display_mode']) ? sanitize_key($data['gallery_display_mode']) : '';
        if ($legacy_hero === 'slider_carousel' && $legacy_gallery === 'carousel') {
            $data['hero_gallery_mode'] = 'carousel';
        } elseif ($legacy_hero === 'slider_carousel') {
            $data['hero_gallery_mode'] = 'slider';
        } else {
            $data['hero_gallery_mode'] = 'single_image';
        }
        return $data;
    }

    public function activate() {
        update_option('ytrip_version', YTRIP_VERSION);
        $this->create_tables();
        $this->create_pages();
        $this->set_defaults();
        
        // Import homepage config from JSON
        if (class_exists('YTrip_Homepage')) {
            $homepage = YTrip_Homepage::instance();
            $homepage->import_from_json();
        }
        
        flush_rewrite_rules();

        if (!wp_next_scheduled('ytrip_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ytrip_daily_cleanup');
        }

        set_transient('ytrip_activated', true, 30);
        set_transient('ytrip_flush_rewrite_rules', true, 30);

        // Build standalone minified CSS (single-tour.min.css, archive-filters.min.css) for PageSpeed.
        if (class_exists('YTrip_Asset_Optimizer')) {
            YTrip_Asset_Optimizer::instance()->build_standalone_min_css();
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('ytrip_daily_cleanup');
        flush_rewrite_rules();
    }

    private function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ytrip_bookings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tour_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0,
            order_id bigint(20) unsigned DEFAULT 0,
            booking_date date NOT NULL,
            booking_time time DEFAULT NULL,
            adults int(3) DEFAULT 1,
            children int(3) DEFAULT 0,
            infants int(3) DEFAULT 0,
            total_price decimal(10,2) DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'pending',
            customer_name varchar(100) DEFAULT '',
            customer_email varchar(100) DEFAULT '',
            customer_phone varchar(30) DEFAULT '',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tour (tour_id),
            KEY idx_status (status),
            KEY idx_date (booking_date),
            KEY idx_user (user_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Ensure infants column exists for existing installs (migration).
        $table_name = $wpdb->prefix . 'ytrip_bookings';
        $col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table_name}` LIKE %s", 'infants' ) );
        if ( empty( $col ) ) {
            $wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN infants int(3) DEFAULT 0 AFTER children" );
        }
    }

    private function create_pages() {
        $pages = [
            'tours' => [
                'title' => __('Tours', 'ytrip'),
                'content' => '[ytrip_tours]',
            ],
        ];

        foreach ($pages as $slug => $data) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_type' => 'page',
                    'post_title' => $data['title'],
                    'post_content' => $data['content'],
                    'post_name' => $slug,
                    'post_status' => 'publish',
                ]);
            }
        }
    }

    private function set_defaults() {
        $defaults = [
            'slug_tour' => 'tour',
            'slug_destination' => 'destination',
            'slug_category' => 'tour-category',
            'color_preset' => 'ocean_adventure',
            'currency' => 'USD',
            'currency_position' => 'before',
            'dark_mode' => true,
        ];

        add_option('ytrip_settings', $defaults);
    }

    public function action_links(array $links) {
        $home = home_url('/');
        $psi_url = 'https://pagespeed.web.dev/analysis?url=' . rawurlencode($home);
        return array_merge([
            '<a href="' . esc_url($psi_url) . '" target="_blank" rel="noopener">' . __('PageSpeed Check', 'ytrip') . '</a>',
            '<a href="' . admin_url('admin.php?page=ytrip-settings') . '">' . __('Settings', 'ytrip') . '</a>',
        ], $links);
    }

    public static function version() {
        return YTRIP_VERSION;
    }

    public static function path() {
        return YTRIP_PATH;
    }

    public static function url() {
        return YTRIP_URL;
    }

    public function is_initialized() {
        return $this->initialized;
    }
}

// =========================================================================
// Initialize
// =========================================================================

    function YTrip() {
        return YTrip::instance();
    }

add_action('plugins_loaded', 'YTrip', 5);

// =========================================================================
// Welcome Redirect
// =========================================================================

add_action('admin_init', function (): void {
    if (!get_transient('ytrip_activated')) {
        return;
    }
    delete_transient('ytrip_activated');
    // Avoid redirect loops: do not redirect if already on YTrip page
    if (isset($_GET['page']) && $_GET['page'] === 'ytrip') {
        return;
    }
    // Multisite: only redirect on main site to prevent ERR_TOO_MANY_REDIRECTS on subsites
    if (is_multisite() && !is_main_site()) {
        return;
    }
    if (isset($_GET['activate-multi'])) {
        return;
    }
    wp_safe_redirect(admin_url('admin.php?page=ytrip&welcome=1'));
    exit;
});
