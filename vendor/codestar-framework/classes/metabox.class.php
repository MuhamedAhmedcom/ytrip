<?php
namespace MuhamedAhmed;

if (!defined('ABSPATH')) exit;

/**
 * Dynamic Metabox Manager - FINAL VERSION
 * Fixes: Group field foreach error, options dependency, complete fields
 */
class MetaboxManager {
    private static $instance = null;
    private $panel_option = 'muhamed_ahmed_panel';
    private $registered_metaboxes = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Register metaboxes EARLY (removed unnecessary hooks as per issue #3)
        add_action('init', [$this, 'register_panel_metaboxes'], 5);
    }
    
    public function register_panel_metaboxes() {
        if (!class_exists('CSF')) {
            return;
        }
        
        $options = get_option($this->panel_option, []);
        
        if (empty($options['custom_metaboxes'])) {
            return;
        }
        
        foreach ($options['custom_metaboxes'] as $metabox) {
            $this->register_metabox($metabox);
        }
    }
    
    private function register_metabox($metabox) {
        if (empty($metabox['metabox_id']) || empty($metabox['metabox_post_types'])) {
            return;
        }
        
        $metabox_id = sanitize_key($metabox['metabox_id']);
        
        if (in_array($metabox_id, $this->registered_metaboxes)) {
            return;
        }
        
        $metabox_prefix = '_' . $metabox_id;
        
        $post_types = is_array($metabox['metabox_post_types']) 
            ? $metabox['metabox_post_types'] 
            : [$metabox['metabox_post_types']];
        
        $valid_post_types = [];
        foreach ($post_types as $pt) {
            if (post_type_exists($pt)) {
                $valid_post_types[] = $pt;
            }
        }
        
        if (empty($valid_post_types)) {
            return;
        }
        
        $fields = [];
        if (!empty($metabox['metabox_fields'])) {
            $fields = $this->build_fields($metabox['metabox_fields']);
        }
        
        if (empty($fields)) {
            $fields = [
                [
                    'type' => 'content',
                    'content' => '<p style="color:#999;">No fields configured. Please add fields in the Meta Boxes panel.</p>',
                ]
            ];
        }
        
        \CSF::createMetabox($metabox_prefix, [
            'title' => !empty($metabox['metabox_title']) ? $metabox['metabox_title'] : ucfirst($metabox_id),
            'post_type' => $valid_post_types,
            'priority' => $metabox['metabox_priority'] ?? 'high',
            'context' => $metabox['metabox_context'] ?? 'normal',
            'data_type' => 'unserialize',
        ]);
        
        \CSF::createSection($metabox_prefix, [
            'fields' => $fields,
        ]);
        
        $this->registered_metaboxes[] = $metabox_id;
    }
    
    /**
     * Build fields array - COMPLETE VERSION WITH ALL FIXES
     */
    private function build_fields($panel_fields) {
        // FIX #1: Ensure panel_fields is an array
        if (!is_array($panel_fields)) {
            return [];
        }
        
        $fields = [];
        
        foreach ($panel_fields as $field) {
            if (empty($field['field_id'])) {
                continue;
            }
            
            $field_type = !empty($field['field_type']) ? $field['field_type'] : 'text';
            
            $csf_field = [
                'id' => sanitize_key($field['field_id']),
                'type' => $field_type,
            ];
            
            // Add common properties
            if (!empty($field['field_title'])) {
                $csf_field['title'] = $field['field_title'];
            }
            
            if (!empty($field['field_subtitle'])) {
                $csf_field['subtitle'] = $field['field_subtitle'];
            }
            
            if (!empty($field['field_desc'])) {
                $csf_field['desc'] = $field['field_desc'];
            }
            
            if (!empty($field['field_placeholder'])) {
                $csf_field['placeholder'] = $field['field_placeholder'];
            }
            
            if (!empty($field['field_default'])) {
                $csf_field['default'] = $field['field_default'];
            }
            
            // Handle required validation
            if (!empty($field['field_required']) && 
                ($field['field_required'] === '1' || $field['field_required'] === 1 || $field['field_required'] === true)) {
                $csf_field['attributes'] = ['required' => 'required'];
            }
            
            // FIX #2: Handle options for select, radio, checkbox
            if (in_array($field_type, ['select', 'radio', 'checkbox'])) {
                $options = [];
                
                if (!empty($field['field_options']) && is_array($field['field_options'])) {
                    foreach ($field['field_options'] as $option) {
                        if (!empty($option['option_key'])) {
                            $options[$option['option_key']] = !empty($option['option_label']) 
                                ? $option['option_label'] 
                                : $option['option_key'];
                        }
                    }
                }
                
                // Always set options array (even if empty)
                $csf_field['options'] = $options;
                
                // Add dependency if provided
                if (!empty($field['dependency'])) {
                    $csf_field['dependency'] = $field['dependency'];
                }
            }
            
// Handle group and repeater fields - MUST have 'fields' as array
if (in_array($field_type, ['group', 'repeater'])) {
    $csf_field['button_title'] = !empty($field['button_title']) 
        ? $field['button_title'] 
        : __('Add Item', 'muhamed-ahmed');
    
    $csf_field['accordion_title_number'] = true;
    
    // Check if fields exist and is a valid array with items
    if (!empty($field['fields']) && is_array($field['fields'])) {
        // Recursively build sub-fields
        $sub_fields = $this->build_fields($field['fields']);
        
        if (!empty($sub_fields)) {
            // Use the built sub-fields
            $csf_field['fields'] = $sub_fields;
        } else {
            // Recursive build returned empty - use default
            $csf_field['fields'] = [
                [
                    'id' => 'value',
                    'type' => 'text',
                    'title' => __('Value', 'muhamed-ahmed'),
                    'placeholder' => __('Enter value...', 'muhamed-ahmed'),
                ]
            ];
        }
    } else {
        // No fields defined or not an array - use default
        $csf_field['fields'] = [
            [
                'id' => 'value',
                'type' => 'text',
                'title' => __('Value', 'muhamed-ahmed'),
                'placeholder' => __('Enter value...', 'muhamed-ahmed'),
            ]
        ];
    }
}

            
            // Special configurations for specific field types
            switch ($field_type) {
                case 'wp_editor':
                    $csf_field['settings'] = [
                        'tinymce' => true,
                        'quicktags' => true,
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                    ];
                    break;
                    
                case 'media':
                    $csf_field['library'] = 'image';
                    $csf_field['button_title'] = __('Add Media', 'muhamed-ahmed');
                    $csf_field['remove_title'] = __('Remove', 'muhamed-ahmed');
                    break;
                    
                case 'gallery':
                    $csf_field['library'] = 'image';
                    $csf_field['button_title'] = __('Add Images', 'muhamed-ahmed');
                    break;
                    
                case 'select':
                    $csf_field['chosen'] = true;
                    break;
                    
                case 'switcher':
                    $csf_field['text_on'] = __('Yes', 'muhamed-ahmed');
                    $csf_field['text_off'] = __('No', 'muhamed-ahmed');
                    break;
                    
                case 'color':
                    $csf_field['default'] = !empty($field['field_default']) ? $field['field_default'] : '#3498db';
                    break;
                    
                case 'date':
                    $csf_field['settings'] = [
                        'dateFormat' => 'Y-m-d',
                    ];
                    break;
                    
                case 'datetime':
                    $csf_field['settings'] = [
                        'dateFormat' => 'Y-m-d',
                        'timeFormat' => 'H:i',
                    ];
                    break;
                    
                case 'number':
                    if (!empty($field['min'])) $csf_field['min'] = $field['min'];
                    if (!empty($field['max'])) $csf_field['max'] = $field['max'];
                    if (!empty($field['step'])) $csf_field['step'] = $field['step'];
                    break;
            }
            
            $fields[] = $csf_field;
        }
        
        return $fields;
    }
    
    public static function get_meta($post_id, $metabox_id, $field_id, $default = '') {
        $metabox_prefix = '_' . sanitize_key($metabox_id);
        $meta = get_post_meta($post_id, $metabox_prefix, true);
        
        if (is_array($meta) && isset($meta[$field_id])) {
            return $meta[$field_id];
        }
        
        return $default;
    }
    
    public static function get_all_meta($post_id, $metabox_id) {
        $metabox_prefix = '_' . sanitize_key($metabox_id);
        return get_post_meta($post_id, $metabox_prefix, true);
    }
    
    public function get_debug_info() {
        $options = get_option($this->panel_option, []);
        
        return [
            'registered_metaboxes' => $this->registered_metaboxes,
            'panel_metaboxes' => !empty($options['custom_metaboxes']) ? count($options['custom_metaboxes']) : 0,
            'csf_available' => class_exists('CSF'),
            'raw_data' => $options['custom_metaboxes'] ?? [],
        ];
    }
}

MetaboxManager::getInstance();

if (!function_exists('ma_get_meta')) {
    function ma_get_meta($post_id, $metabox_id, $field_id, $default = '') {
        return \MuhamedAhmed\MetaboxManager::get_meta($post_id, $metabox_id, $field_id, $default);
    }
}

if (!function_exists('ma_get_all_meta')) {
    function ma_get_all_meta($post_id, $metabox_id) {
        return \MuhamedAhmed\MetaboxManager::get_all_meta($post_id, $metabox_id);
    }
}

add_action('admin_notices', function() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_metabox'])) return;
    
    $manager = \MuhamedAhmed\MetaboxManager::getInstance();
    $debug = $manager->get_debug_info();
    
    echo '<div class="notice notice-success" style="padding:15px;"><pre style="font-size:11px;">';
    echo "=== METABOX DEBUG INFO ===\n\n";
    echo "CSF Available: " . ($debug['csf_available'] ? "✅ YES" : "❌ NO") . "\n";
    echo "Panel Metaboxes: " . $debug['panel_metaboxes'] . "\n";
    echo "Registered: " . implode(', ', $debug['registered_metaboxes']) . "\n";
    echo "\nAdd ?debug_metabox=1 to see this";
    echo '</pre></div>';
});
