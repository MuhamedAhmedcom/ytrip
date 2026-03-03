<?php
/**
 * Plugin loader
 */

if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('CSF_Plugin_Loader')) {
    class CSF_Plugin_Loader {
        private static $instance = null;
        private $plugin_path;
        
        private function __construct() {
            $this->plugin_path = plugin_dir_path(dirname(__FILE__));
            $this->init();
        }
        
        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function init() {
            // Load the framework
            require_once $this->plugin_path . 'classes/setup.class.php';
            
            // Register autoloader
            spl_autoload_register(array($this, 'autoload'));
            
            // Initialize after theme setup
            add_action('after_setup_theme', array($this, 'setup'), 5);
            
            // Initialize admin
            if (is_admin()) {
                add_action('admin_init', array($this, 'admin_init'));
            }
        }
        
        public function autoload($class) {
            // Check if class is in our namespace
            if (empty($class) || (strpos($class, 'MuhamedAhmed\\') !== 0 && strpos($class, 'CSF_') !== 0)) {
                return;
            }
            
            // Convert class name to file path
            if (strpos($class, 'MuhamedAhmed\\') === 0) {
                $class_name = str_replace('MuhamedAhmed\\', '', $class);
                $path = $this->plugin_path . 'classes/' . strtolower($class_name) . '.class.php';
            } else {
                $path = $this->plugin_path . 'classes/' . strtolower($class) . '.class.php';
            }
            
            if (file_exists($path)) {
                require_once $path;
            }
        }
        
        public function setup() {
            // Load core classes
            require_once $this->plugin_path . 'classes/abstract.class.php';
            require_once $this->plugin_path . 'classes/setup.class.php';
            
            // Initialize the framework
            if (class_exists('CSF')) {
                CSF::init();
            }
            
            // Load welcome page
            if (is_admin()) {
                require_once $this->plugin_path . 'views/welcome.php';
            }
        }
        
        public function admin_init() {
            // Load admin dependencies
            foreach (glob($this->plugin_path . 'options/*.php') as $file) {
                require_once $file;
            }
        }
    }
}

// Initialize the plugin
CSF_Plugin_Loader::instance();