<?php
namespace MuhamedAhmed;

if (!defined('ABSPATH')) exit;

class DynamicRegistrationManager {
    private static $instance = null;
    private $panel_option = 'muhamed_ahmed_panel';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('after_setup_theme', [$this, 'register_panel_types'], 999);
        add_action('wp_loaded', [$this, 'register_panel_types'], 1);
        add_action('init', [$this, 'register_panel_types'], 0);
        add_action('admin_init', [$this, 'register_panel_types'], 1);
        add_action('current_screen', [$this, 'force_registration'], 1);
        add_action('update_option_' . $this->panel_option, [$this, 'on_panel_update'], 10, 2);
        
        // FORCE label updates on every admin page load (aggressive)
        if (is_admin()) {
            add_action('admin_init', [$this, 'force_unregister_and_reregister'], 0);
        }
    }
    
    /**
     * FORCE UNREGISTER AND RE-REGISTER - Clears WordPress taxonomy cache
     */
    public function force_unregister_and_reregister() {
        global $wp_taxonomies;
        
        $options = get_option($this->panel_option, []);
        if (empty($options['custom_taxonomies'])) return;
        
        // UNREGISTER all our taxonomies first
        foreach ($options['custom_taxonomies'] as $tax) {
            if (empty($tax['name'])) continue;
            $tax_name = sanitize_key($tax['name']);
            
            // Remove from global
            if (isset($wp_taxonomies[$tax_name])) {
                unset($wp_taxonomies[$tax_name]);
            }
        }
        
        // Now re-register with fresh labels
        $this->register_panel_types();
    }
    
    public function register_panel_types() {
        $options = get_option($this->panel_option, []);
        if (empty($options)) return;
        
        $this->register_cpts($options);
        $this->register_taxonomies($options);
    }
    
    public function force_registration($screen) {
        $this->register_panel_types();
    }
    
    private function register_cpts($options) {
        if (empty($options['custom_post_types'])) return;
        
        foreach ($options['custom_post_types'] as $cpt) {
            if (empty($cpt['name'])) continue;
            
            $cpt_name = sanitize_key($cpt['name']);
            if (post_type_exists($cpt_name)) continue;
            
            $singular = $cpt['singular'] ?? ucfirst($cpt_name);
            $plural = $cpt['plural'] ?? $singular . 's';
            
            $labels = [
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'add_new' => __('Add New', 'muhamed-ahmed'),
                'add_new_item' => sprintf(__('Add New %s', 'muhamed-ahmed'), $singular),
                'edit_item' => sprintf(__('Edit %s', 'muhamed-ahmed'), $singular),
                'new_item' => sprintf(__('New %s', 'muhamed-ahmed'), $singular),
                'view_item' => sprintf(__('View %s', 'muhamed-ahmed'), $singular),
                'all_items' => sprintf(__('All %s', 'muhamed-ahmed'), $plural),
                'search_items' => sprintf(__('Search %s', 'muhamed-ahmed'), $plural),
                'not_found' => sprintf(__('No %s found', 'muhamed-ahmed'), strtolower($plural)),
                'not_found_in_trash' => sprintf(__('No %s found in Trash', 'muhamed-ahmed'), strtolower($plural)),
            ];
            
            $args = [
                'labels' => $labels,
                'public' => $this->to_bool($cpt['public'] ?? '1'),
                'has_archive' => $this->to_bool($cpt['has_archive'] ?? '1'),
                'show_in_rest' => $this->to_bool($cpt['show_in_rest'] ?? '1'),
                'hierarchical' => $this->to_bool($cpt['hierarchical'] ?? ''),
                'supports' => $cpt['supports'] ?? ['title', 'editor', 'thumbnail'],
                'menu_icon' => $cpt['menu_icon'] ?: 'dashicons-admin-post',
                'menu_position' => (int)($cpt['menu_position'] ?? 20),
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'query_var' => true,
                'rewrite' => [
                    'slug' => $cpt['slug'] ?? $cpt_name,
                    'with_front' => false,
                ],
            ];
            
            register_post_type($cpt_name, $args);
        }
    }
    
    private function register_taxonomies($options) {
        if (empty($options['custom_taxonomies'])) return;
        
        foreach ($options['custom_taxonomies'] as $tax) {
            if (empty($tax['name']) || empty($tax['post_types'])) continue;
            
            $tax_name = sanitize_key($tax['name']);
            
            $valid_post_types = [];
            foreach ($tax['post_types'] as $post_type) {
                if (post_type_exists($post_type)) {
                    $valid_post_types[] = $post_type;
                }
            }
            
            if (empty($valid_post_types)) continue;
            
            // USE EXACT LABELS FROM PANEL - NO MODIFICATION
            $singular = $tax['singular'];
            $plural = $tax['plural'];
            
            $is_hierarchical = $this->to_bool($tax['hierarchical'] ?? '1');
            
            // DIRECT LABEL ASSIGNMENT - No sprintf, no translation
            $labels = [
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'all_items' => $plural,
                'edit_item' => $singular,
                'view_item' => $singular,
                'update_item' => $singular,
                'add_new_item' => $singular,
                'new_item_name' => $singular,
                'search_items' => $plural,
                'popular_items' => $plural,
                'separate_items_with_commas' => $plural,
                'add_or_remove_items' => $plural,
                'choose_from_most_used' => $plural,
                'not_found' => $plural,
                'back_to_items' => $plural,
            ];
            
            if ($is_hierarchical) {
                $labels['parent_item'] = $singular;
                $labels['parent_item_colon'] = $singular;
            }
            
            $args = [
                'labels' => $labels,
                'hierarchical' => $is_hierarchical,
                'show_ui' => true,
                'show_admin_column' => $this->to_bool($tax['show_admin_column'] ?? '1'),
                'show_in_rest' => $this->to_bool($tax['show_in_rest'] ?? '1'),
                'show_in_nav_menus' => true,
                'show_tagcloud' => true,
                'query_var' => true,
                'public' => true,
                'rewrite' => [
                    'slug' => $tax['slug'] ?? $tax_name,
                    'with_front' => false,
                    'hierarchical' => $is_hierarchical,
                ],
            ];
            
            // FORCE REGISTER (even if exists)
            register_taxonomy($tax_name, $valid_post_types, $args);
            
            foreach ($valid_post_types as $post_type) {
                register_taxonomy_for_object_type($tax_name, $post_type);
            }
        }
    }
    
    private function to_bool($val) {
        return ($val === "1" || $val === 1 || $val === true);
    }
    
    public function on_panel_update($old, $new) {
        delete_option('rewrite_rules');
        
        // CLEAR all WordPress caches
        wp_cache_flush();
        
        // Schedule flush
        add_action('shutdown', 'flush_rewrite_rules');
    }
}

class Store {
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function store_post_type($p, $a) { return true; }
    public function store_taxonomy($t, $p, $a) { return true; }
    public function register_stored_types() {
        return DynamicRegistrationManager::getInstance()->register_panel_types();
    }
}

class CPT {
    public function __construct($n, $o = []) {}
}

class PostTypeManager {
    protected $postType;
    public function __construct($p, array $a = []) { $this->postType = $p; }
    public static function create($p, array $a = []) { return new self($p, $a); }
    public function setLabels($l) { return $this; }
    public function generateLabels($s, $p, $t = 'default') { return $this; }
    public function setIcon($i) { return $this; }
    public function setMenuPosition($p) { return $this; }
    public function setSupports($s) { return $this; }
    public function showInRest($e = true) { return $this; }
    public function setRewrite($s) { return $this; }
    public function setHierarchical($h = true) { return $this; }
    public function addTaxonomy($t, $a = [], $h = false) { return $this; }
    public function associateTaxonomy($t) { return $this; }
    public function enableCodestarFramework($p = '') { return $this; }
    public function addCodestarMetabox($f, $t = '') { return $this; }
    public function register() { return $this; }
    public function registerCodestarIntegration() { return $this; }
    protected function validatePostTypeKey($p) {
        if (empty($p) || strlen($p) > 20) throw new \InvalidArgumentException('Invalid post type key.');
    }
    public static function getInstance($p) { return null; }
}

class TaxonomyBuilder {
    protected $taxonomy;
    public function __construct($t, $p, array $a = []) { $this->taxonomy = $t; }
    public static function create($t, $p, array $a = []) { return new self($t, $p, $a); }
    public function generateLabels($s, $p, $h = false, $t = 'default') { return $this; }
    public function setHierarchical($h = true) { return $this; }
    public function setRewrite($s) { return $this; }
    public function showAdminColumn($s = true) { return $this; }
    public function addCodestarFields($f) { return $this; }
    public function register() { return $this; }
    protected function validateTaxonomyKey($t) {
        if (empty($t) || strlen($t) > 32) throw new \InvalidArgumentException('Invalid taxonomy key.');
    }
}

DynamicRegistrationManager::getInstance();

add_action('init', function() { 
    DynamicRegistrationManager::getInstance()->register_panel_types(); 
}, 0);

add_action('init', function() { 
    Store::getInstance()->register_stored_types(); 
}, 1);

add_action('admin_menu', function() {
    DynamicRegistrationManager::getInstance()->register_panel_types();
}, 1);

// ADMIN DEBUG - Shows actual registered labels
add_action('admin_notices', function() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_labels'])) return;
    
    $options = get_option('muhamed_ahmed_panel', []);
    
    echo '<div class="notice notice-info" style="padding:15px;"><pre style="font-size:11px;">';
    echo "=== LABEL DEBUG ===\n\n";
    
    if (!empty($options['custom_taxonomies'])) {
        foreach ($options['custom_taxonomies'] as $tax) {
            $tax_name = $tax['name'];
            echo "Taxonomy: {$tax_name}\n";
            echo "  Panel Singular: '{$tax['singular']}'\n";
            echo "  Panel Plural: '{$tax['plural']}'\n";
            
            if (taxonomy_exists($tax_name)) {
                $tax_obj = get_taxonomy($tax_name);
                echo "  ✅ REGISTERED\n";
                echo "  WordPress name label: '{$tax_obj->labels->name}'\n";
                echo "  WordPress singular_name: '{$tax_obj->labels->singular_name}'\n";
                echo "  WordPress menu_name: '{$tax_obj->labels->menu_name}'\n";
            } else {
                echo "  ❌ NOT REGISTERED\n";
            }
            echo "\n";
        }
    }
    
    echo "Add ?debug_labels=1 to see this";
    echo '</pre></div>';
});
