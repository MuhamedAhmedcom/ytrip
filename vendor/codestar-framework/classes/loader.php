<?php
/**
 * Plugin Loader
 * 
 * This class is responsible for loading and initializing all plugin components.
 * 
 * @package ProWPSite
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ProWPSite_Loader {
    /**
     * Plugin components
     *
     * @var array
     */
    private $components = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->include_files();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        // CSF Framework constants
        if (!defined('CSF_PATH')) {
            define('CSF_PATH', PROWP_PATH);
        }
        if (!defined('CSF_URL')) {
            define('CSF_URL', PROWP_URL);
        }
    }

    /**
     * Include required files
     */
    private function include_files() {
        // Include helpers
        require_once PROWP_PATH . 'functions/helpers.php';
        require_once PROWP_PATH . 'functions/actions.php';
        require_once PROWP_PATH . 'functions/sanitize.php';
        require_once PROWP_PATH . 'functions/validate.php';
        require_once PROWP_PATH . 'functions/customize.php';

        // Include abstract class first
        require_once PROWP_PATH . 'classes/abstract.class.php';

        // Include core classes
        require_once PROWP_PATH . 'classes/setup.class.php';
        require_once PROWP_PATH . 'classes/fields.class.php';
        require_once PROWP_PATH . 'classes/admin-options.class.php';
        require_once PROWP_PATH . 'classes/customize-options.class.php';
        require_once PROWP_PATH . 'classes/metabox-options.class.php';
        require_once PROWP_PATH . 'classes/nav-menu-options.class.php';
        require_once PROWP_PATH . 'classes/profile-options.class.php';
        require_once PROWP_PATH . 'classes/shortcode-options.class.php';
        require_once PROWP_PATH . 'classes/taxonomy-options.class.php';
        require_once PROWP_PATH . 'classes/widget-options.class.php';
        require_once PROWP_PATH . 'classes/comment-options.class.php';

        // Include CPT classes
        require_once PROWP_PATH . 'classes/cpt.class.php';
        require_once PROWP_PATH . 'classes/meta.class.php';
        require_once PROWP_PATH . 'classes/meta-shortcodes.class.php';
    }

    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize CSF Framework
        if (class_exists('CSF')) {
            $this->components['setup'] = new CSF_Setup();
        }

        // Initialize CPT Manager
        if (class_exists('ProWPSite_CPT')) {
            $this->components['cpt'] = new ProWPSite_CPT();
        }
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Initialize components
        $this->init_components();

        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('prowpsite-panel', false, dirname(PROWP_BASENAME) . '/languages');
    }
}