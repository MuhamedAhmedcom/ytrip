<?php
namespace MuhamedAhmed;

if (!defined('ABSPATH')) exit;

/**
 * Meta Shortcodes Handler
 * 
 * Available Shortcodes:
 * [lawyer_profile]
 * [lawyer_profile post_id="27" layout="modern" color="blue"]
 * [lawyers_grid columns="3"]
 * [meta id="lawyer_bio"]
 */
class MetaShortcodes {
    
    public function __construct() {
        add_shortcode('meta', [$this, 'meta_shortcode']);
        add_shortcode('meta_grid', [$this, 'meta_grid_shortcode']);
        add_shortcode('meta_carousel', [$this, 'meta_carousel_shortcode']);
        add_shortcode('meta_list', [$this, 'meta_list_shortcode']);
        
        // ✅ NEW SHORTCODES
        add_shortcode('lawyer_profile', [$this, 'lawyer_profile_shortcode']);
        add_shortcode('lawyers_grid', [$this, 'lawyers_grid_shortcode']);
        add_shortcode('lawyer_debug', [$this, 'lawyer_debug_shortcode']);

    }
    
    /**
     * Lawyer Profile Shortcode - Works in page builders
     * 
     * Usage:
     * [lawyer_profile]
     * [lawyer_profile post_id="27"]
     * [lawyer_profile layout="modern" color="blue"]
     * [lawyer_profile layout="modern" color="green" show_header="true" show_contact="true"]
     */
/**
 * Lawyer Profile Shortcode - GUARANTEED WORKING VERSION
 */
public function lawyer_profile_shortcode($atts) {
    $atts = shortcode_atts([
        'post_id' => null,
        'layout' => 'modern',
        'color' => 'blue',
        'show_header' => 'true',
        'show_contact' => 'true',
        'show_social' => 'true',
        'show_sections' => 'true',
        'animate' => 'true',
    ], $atts);
    
    // ✅ KEY FIX: Get the correct ID at shortcode level where WP Query is available
    global $wp_query;
    
    // Priority 1: Use attribute if provided
    if (!empty($atts['post_id'])) {
        $post_id = intval($atts['post_id']);
    }
    // Priority 2: Use queried_object_id (correct for Divi)
    elseif (!empty($wp_query->queried_object_id) && get_post_type($wp_query->queried_object_id) === 'lawyer') {
        $post_id = $wp_query->queried_object_id;
    }
    // Priority 3: Use WP Query post
    elseif (!empty($wp_query->post->ID) && get_post_type($wp_query->post->ID) === 'lawyer') {
        $post_id = $wp_query->post->ID;
    }
    // Priority 4: Fallback
    else {
        $post_id = get_queried_object_id();
    }
    
    // Prepare options
    $options = [
        'layout' => sanitize_text_field($atts['layout']),
        'color_scheme' => sanitize_text_field($atts['color']),
        'show_header' => filter_var($atts['show_header'], FILTER_VALIDATE_BOOLEAN),
        'show_contact' => filter_var($atts['show_contact'], FILTER_VALIDATE_BOOLEAN),
        'show_social' => filter_var($atts['show_social'], FILTER_VALIDATE_BOOLEAN),
        'show_sections' => filter_var($atts['show_sections'], FILTER_VALIDATE_BOOLEAN),
        'animate' => filter_var($atts['animate'], FILTER_VALIDATE_BOOLEAN),
    ];
    
    // ✅ Pass the detected ID directly to the method
    return \MuhamedAhmed\Meta::lawyerProfile($post_id, $options);
}


    /**
     * Lawyers Grid Shortcode - Display multiple lawyers
     * 
     * Usage:
     * [lawyers_grid]
     * [lawyers_grid columns="3" limit="12"]
     * [lawyers_grid columns="4" city="الرياض"]
     */
    public function lawyers_grid_shortcode($atts) {
        $atts = shortcode_atts([
            'columns' => '3',
            'limit' => '12',
            'city' => '',
            'show_contact' => 'true',
            'show_excerpt' => 'true',
            'color' => 'blue',
        ], $atts);
        
        $options = [
            'columns' => intval($atts['columns']),
            'posts_per_page' => intval($atts['limit']),
            'show_contact' => filter_var($atts['show_contact'], FILTER_VALIDATE_BOOLEAN),
            'show_excerpt' => filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN),
            'color_scheme' => sanitize_text_field($atts['color']),
        ];
        
        // Add city filter if provided
        if (!empty($atts['city'])) {
            $options['city'] = sanitize_text_field($atts['city']);
        }
        
        return Meta::lawyersArchive($options);
    }
    
    /**
     * Basic meta shortcode
     * [meta id="lawyer_bio" field="lawyer_bio" post_id="27"]
     */
    public function meta_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => '',
            'field' => '',
            'post_id' => null,
            'default' => '',
        ], $atts);
        
        if (empty($atts['id'])) {
            return '<!-- Meta shortcode: metabox ID required -->';
        }
        
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : get_the_ID();
        
        if ($atts['field']) {
            $value = Meta::getField($post_id, $atts['id'], $atts['field'], $atts['default']);
            return !empty($value) ? wp_kses_post($value) : $atts['default'];
        }
        
        // Get all metabox data
        $data = Meta::getMetabox($post_id, $atts['id']);
        
        if (empty($data)) {
            return $atts['default'];
        }
        
        $output = '<div class="meta-output">';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $output .= '<div class="meta-field">';
                $output .= '<strong>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ';
                $output .= wp_kses_post($value);
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Grid layout shortcode
     * [meta_grid id="contact_info" columns="3"]
     */
    public function meta_grid_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => '',
            'post_id' => null,
            'columns' => '3',
            'gap' => '20px',
        ], $atts);
        
        if (empty($atts['id'])) {
            return '';
        }
        
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : get_the_ID();
        $data = Meta::getMetabox($post_id, $atts['id']);
        
        if (empty($data)) {
            return '';
        }
        
        $output = '<div class="meta-grid" style="display:grid;grid-template-columns:repeat(' . esc_attr($atts['columns']) . ',1fr);gap:' . esc_attr($atts['gap']) . ';">';
        
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $output .= '<div class="meta-grid-item" style="padding:15px;background:#f9f9f9;border-radius:5px;">';
                $output .= '<h4 style="margin:0 0 10px 0;">' . esc_html(ucfirst(str_replace('_', ' ', $key))) . '</h4>';
                $output .= '<div>' . wp_kses_post($value) . '</div>';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Carousel shortcode
     * [meta_carousel id="social_media" field="social_accounts"]
     */
    public function meta_carousel_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => '',
            'field' => '',
            'post_id' => null,
        ], $atts);
        
        if (empty($atts['id']) || empty($atts['field'])) {
            return '';
        }
        
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : get_the_ID();
        $data = Meta::getField($post_id, $atts['id'], $atts['field']);
        
        if (empty($data) || !is_array($data)) {
            return '';
        }
        
        wp_enqueue_script('jquery');
        
        $carousel_id = 'carousel-' . uniqid();
        
        $output = '<div class="meta-carousel" id="' . $carousel_id . '" style="position:relative;overflow:hidden;max-width:600px;margin:0 auto;">';
        $output .= '<div class="carousel-inner" style="display:flex;transition:transform 0.3s ease;">';
        
        foreach ($data as $item) {
            $output .= '<div class="carousel-item" style="min-width:100%;padding:20px;text-align:center;">';
            foreach ($item as $key => $value) {
                if (!empty($value)) {
                    $output .= '<div style="margin:10px 0;">';
                    $output .= '<strong>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ';
                    $output .= esc_html($value);
                    $output .= '</div>';
                }
            }
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '<button class="carousel-prev" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:#667eea;color:white;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;">❮</button>';
        $output .= '<button class="carousel-next" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:#667eea;color:white;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;">❯</button>';
        $output .= '</div>';
        
        $output .= '<script>
        jQuery(document).ready(function($) {
            var carousel = $("#' . $carousel_id . '");
            var inner = carousel.find(".carousel-inner");
            var items = carousel.find(".carousel-item");
            var currentIndex = 0;
            
            carousel.find(".carousel-next").click(function() {
                currentIndex = (currentIndex + 1) % items.length;
                inner.css("transform", "translateX(-" + (currentIndex * 100) + "%)");
            });
            
            carousel.find(".carousel-prev").click(function() {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                inner.css("transform", "translateX(-" + (currentIndex * 100) + "%)");
            });
        });
        </script>';
        
        return $output;
    }
    /**
 * DEBUG SHORTCODE - Test if system works
 * [lawyer_debug]
 */
public function lawyer_debug_shortcode($atts) {
    global $post;
    
    $output = '<div style="background:#f0f0f0;padding:20px;margin:20px 0;border:2px solid #0073aa;font-family:monospace;direction:ltr;text-align:left;">';
    $output .= '<h3>🔍 LAWYER PROFILE DEBUG</h3>';
    
    // Check post
    $output .= '<strong>Current Post ID:</strong> ' . ($post ? $post->ID : 'NO POST') . '<br>';
    $output .= '<strong>Post Type:</strong> ' . ($post ? $post->post_type : 'NO POST') . '<br>';
    $output .= '<strong>Post Title:</strong> ' . ($post ? $post->post_title : 'NO POST') . '<br><br>';
    
    // Check if Meta class exists
    $output .= '<strong>Meta Class Exists:</strong> ' . (class_exists('\MuhamedAhmed\Meta') ? '✅ YES' : '❌ NO') . '<br>';
    
    // Check if method exists
    if (class_exists('\MuhamedAhmed\Meta')) {
        $output .= '<strong>lawyerProfile Method Exists:</strong> ' . (method_exists('\MuhamedAhmed\Meta', 'lawyerProfile') ? '✅ YES' : '❌ NO') . '<br><br>';
        
        // Get all meta
        if ($post) {
            $all_meta = \MuhamedAhmed\Meta::all($post->ID);
            $output .= '<strong>Meta Data Found:</strong> ' . (empty($all_meta) ? '❌ EMPTY' : '✅ YES') . '<br>';
            $output .= '<strong>Meta Data Count:</strong> ' . count($all_meta) . '<br><br>';
            
            if (!empty($all_meta)) {
                $output .= '<strong>Available Metaboxes:</strong><br>';
                $output .= '<pre style="background:white;padding:10px;overflow:auto;">';
                $output .= print_r(array_keys($all_meta), true);
                $output .= '</pre>';
            }
        }
    }
    
    $output .= '</div>';
    
    return $output;
}

    
    /**
     * List all metaboxes
     * [meta_list]
     */
    public function meta_list_shortcode($atts) {
        return Meta::listAll();
    }
}

// Initialize
new MetaShortcodes();
