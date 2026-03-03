<?php
/**
 * Muhamed Ahmed Panel - Dynamic Codestar Framework Integration
 * 
 * 100% Generic - Works for ANY content type:
 * - Social Media
 * - Services
 * - Team Members
 * - Features
 * - Products
 * - Steps/Process
 * - FAQs
 * - Portfolio
 * - Testimonials
 * - Any custom group/repeater data
 * 
 * @package MuhamedAhmed
 * @author Muhamed Ahmed
 * @version 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!class_exists('CSF')) return;

$prefix = 'muhamed_ahmed_panel';

// ════════════════════════════════════════════════════════════
// Helper: Get Post Types for Taxonomy Association
// ════════════════════════════════════════════════════════════
function ma_get_post_types_list() {
    $options = [];
    
    $post_types = get_post_types(['public' => true], 'objects');
    foreach ($post_types as $post_type) {
        $options[$post_type->name] = $post_type->label;
    }
    
    $panel_data = get_option('muhamed_ahmed_panel', []);
    if (!empty($panel_data['custom_post_types'])) {
        foreach ($panel_data['custom_post_types'] as $cpt) {
            if (!empty($cpt['name']) && !empty($cpt['plural'])) {
                $options[$cpt['name']] = $cpt['plural'];
            }
        }
    }
    
    return $options;
}

// ════════════════════════════════════════════════════════════
// Create Options Page
// ════════════════════════════════════════════════════════════
CSF::createOptions($prefix, [
    'menu_title' => __('Muhamed Ahmed', 'muhamed-ahmed'),
    'menu_slug' => 'muhamed-ahmed-panel',
    'menu_type' => 'menu',
    'menu_position' => 2,
    'menu_icon' => 'dashicons-admin-generic',
    'framework_title' => __('Muhamed Ahmed - Universal Dynamic Panel', 'muhamed-ahmed'),
    'theme' => 'dark',
    'ajax_save' => true,
    'show_search' => true,
    'show_reset_all' => true,
    'show_footer' => true,
    'footer_text' => __('Developed by Muhamed Ahmed', 'muhamed-ahmed'),
    'footer_after' => __('Version 3.0.0', 'muhamed-ahmed'),
]);

// ════════════════════════════════════════════════════════════
// SECTION 1: Custom Post Types
// ════════════════════════════════════════════════════════════
CSF::createSection($prefix, [
    'id' => 'cpt_section',
    'title' => __('Custom Post Types', 'muhamed-ahmed'),
    'icon' => 'fas fa-cubes',
    'fields' => [
        [
            'type' => 'heading',
            'content' => __('Create Custom Post Types', 'muhamed-ahmed'),
        ],
        [
            'type' => 'content',
            'content' => '<div style="background:#e3f2fd;padding:15px;border-radius:8px;margin-bottom:20px;">' . 
                __('Create any content type: Lawyers, Doctors, Products, Services, etc.', 'muhamed-ahmed') . 
                '</div>',
        ],
        [
            'id' => 'custom_post_types',
            'type' => 'group',
            'title' => __('Post Types', 'muhamed-ahmed'),
            'button_title' => __('Add Post Type', 'muhamed-ahmed'),
            'accordion_title_prefix' => __('Post Type: ', 'muhamed-ahmed'),
            'accordion_title_number' => true,
            'fields' => [
                [
                    'id' => 'name',
                    'type' => 'text',
                    'title' => __('Slug (lowercase)', 'muhamed-ahmed'),
                    'placeholder' => 'lawyer',
                ],
                [
                    'id' => 'singular',
                    'type' => 'text',
                    'title' => __('Singular Name', 'muhamed-ahmed'),
                    'placeholder' => 'Lawyer',
                ],
                [
                    'id' => 'plural',
                    'type' => 'text',
                    'title' => __('Plural Name', 'muhamed-ahmed'),
                    'placeholder' => 'Lawyers',
                ],
                [
                    'id' => 'slug',
                    'type' => 'text',
                    'title' => __('URL Slug', 'muhamed-ahmed'),
                    'placeholder' => 'lawyer',
                ],
                [
                    'id' => 'menu_icon',
                    'type' => 'icon',
                    'title' => __('Menu Icon', 'muhamed-ahmed'),
                    'default' => 'dashicons-admin-post',
                ],
                [
                    'id' => 'menu_position',
                    'type' => 'number',
                    'title' => __('Menu Position', 'muhamed-ahmed'),
                    'default' => 5,
                ],
                [
                    'id' => 'supports',
                    'type' => 'checkbox',
                    'title' => __('Supports', 'muhamed-ahmed'),
                    'options' => [
                        'title' => 'Title',
                        'editor' => 'Editor',
                        'thumbnail' => 'Featured Image',
                        'excerpt' => 'Excerpt',
                        'custom-fields' => 'Custom Fields',
                        'comments' => 'Comments',
                        'revisions' => 'Revisions',
                        'author' => 'Author',
                        'page-attributes' => 'Page Attributes',
                    ],
                    'default' => ['title', 'editor', 'thumbnail'],
                ],
                [
                    'id' => 'hierarchical',
                    'type' => 'switcher',
                    'title' => __('Hierarchical', 'muhamed-ahmed'),
                    'default' => false,
                ],
                [
                    'id' => 'has_archive',
                    'type' => 'switcher',
                    'title' => __('Has Archive', 'muhamed-ahmed'),
                    'default' => true,
                ],
                [
                    'id' => 'public',
                    'type' => 'switcher',
                    'title' => __('Public', 'muhamed-ahmed'),
                    'default' => true,
                ],
                [
                    'id' => 'show_in_rest',
                    'type' => 'switcher',
                    'title' => __('Show in REST API', 'muhamed-ahmed'),
                    'default' => true,
                ],
            ],
        ],
    ],
]);

// ════════════════════════════════════════════════════════════
// SECTION 2: Taxonomies
// ════════════════════════════════════════════════════════════
CSF::createSection($prefix, [
    'id' => 'taxonomy_section',
    'title' => __('Taxonomies', 'muhamed-ahmed'),
    'icon' => 'fas fa-tags',
    'fields' => [
        [
            'type' => 'heading',
            'content' => __('Create Custom Taxonomies', 'muhamed-ahmed'),
        ],
        [
            'id' => 'custom_taxonomies',
            'type' => 'group',
            'title' => __('Taxonomies', 'muhamed-ahmed'),
            'button_title' => __('Add Taxonomy', 'muhamed-ahmed'),
            'accordion_title_prefix' => __('Taxonomy: ', 'muhamed-ahmed'),
            'fields' => [
                [
                    'id' => 'name',
                    'type' => 'text',
                    'title' => __('Slug', 'muhamed-ahmed'),
                    'placeholder' => 'city',
                ],
                [
                    'id' => 'singular',
                    'type' => 'text',
                    'title' => __('Singular Name', 'muhamed-ahmed'),
                ],
                [
                    'id' => 'plural',
                    'type' => 'text',
                    'title' => __('Plural Name', 'muhamed-ahmed'),
                ],
                [
                    'id' => 'slug',
                    'type' => 'text',
                    'title' => __('URL Slug', 'muhamed-ahmed'),
                ],
                [
                    'id' => 'post_types',
                    'type' => 'select',
                    'title' => __('Post Types', 'muhamed-ahmed'),
                    'options' => 'ma_get_post_types_list',
                    'chosen' => true,
                    'multiple' => true,
                ],
                [
                    'id' => 'hierarchical',
                    'type' => 'switcher',
                    'title' => __('Hierarchical', 'muhamed-ahmed'),
                    'default' => true,
                ],
                [
                    'id' => 'show_admin_column',
                    'type' => 'switcher',
                    'title' => __('Show Admin Column', 'muhamed-ahmed'),
                    'default' => true,
                ],
                [
                    'id' => 'show_in_rest',
                    'type' => 'switcher',
                    'title' => __('Show in REST', 'muhamed-ahmed'),
                    'default' => true,
                ],
            ],
        ],
    ],
]);

// ════════════════════════════════════════════════════════════
// SECTION 3: Dynamic Metaboxes (THE MAGIC IS HERE!)
// ════════════════════════════════════════════════════════════
CSF::createSection($prefix, [
    'id' => 'metabox_section',
    'title' => __('Meta Boxes', 'muhamed-ahmed'),
    'icon' => 'fas fa-boxes',
    'fields' => [
        [
            'type' => 'heading',
            'content' => __('Create Dynamic Meta Boxes', 'muhamed-ahmed'),
        ],
        [
            'type' => 'content',
            'content' => '<div style="background:#fff3cd;padding:15px;border-radius:8px;margin-bottom:20px;">' .
                '<strong>💡 Universal System:</strong> Create fields for ANY purpose: Contact Info, Social Media, Services, Features, Team, Products, Steps, FAQs, etc.' .
                '</div>',
        ],
        [
            'id' => 'custom_metaboxes',
            'type' => 'group',
            'title' => __('Metaboxes', 'muhamed-ahmed'),
            'button_title' => __('Add Metabox', 'muhamed-ahmed'),
            'accordion_title_prefix' => __('Metabox: ', 'muhamed-ahmed'),
            'accordion_title_number' => true,
            'fields' => [
                
                // ─────────────────────────────────────
                // Metabox Basic Info
                // ─────────────────────────────────────
                [
                    'id' => 'metabox_id',
                    'type' => 'text',
                    'title' => __('Metabox ID', 'muhamed-ahmed'),
                    'placeholder' => 'contact_info',
                ],
                [
                    'id' => 'metabox_title',
                    'type' => 'text',
                    'title' => __('Metabox Title', 'muhamed-ahmed'),
                    'placeholder' => 'Contact Information',
                ],
                [
                    'id' => 'metabox_post_types',
                    'type' => 'select',
                    'title' => __('Post Types', 'muhamed-ahmed'),
                    'options' => 'ma_get_post_types_list',
                    'chosen' => true,
                    'multiple' => true,
                ],
                [
                    'id' => 'metabox_priority',
                    'type' => 'select',
                    'title' => __('Priority', 'muhamed-ahmed'),
                    'options' => [
                        'high' => 'High',
                        'default' => 'Default',
                        'low' => 'Low',
                    ],
                    'default' => 'high',
                ],
                [
                    'id' => 'metabox_context',
                    'type' => 'select',
                    'title' => __('Context', 'muhamed-ahmed'),
                    'options' => [
                        'normal' => 'Normal',
                        'side' => 'Side',
                        'advanced' => 'Advanced',
                    ],
                    'default' => 'normal',
                ],
                
                // ─────────────────────────────────────
                // DYNAMIC FIELDS (The Heart of the System!)
                // ─────────────────────────────────────
                [
                    'id' => 'metabox_fields',
                    'type' => 'group',
                    'title' => __('Fields', 'muhamed-ahmed'),
                    'button_title' => __('Add Field', 'muhamed-ahmed'),
                    'accordion_title_prefix' => __('Field: ', 'muhamed-ahmed'),
                    'fields' => [
                        
                        // Basic Field Settings
                        [
                            'id' => 'field_id',
                            'type' => 'text',
                            'title' => __('Field ID', 'muhamed-ahmed'),
                            'placeholder' => 'phone',
                        ],
                        [
                            'id' => 'field_type',
                            'type' => 'select',
                            'title' => __('Field Type', 'muhamed-ahmed'),
                            'options' => [
                                'text' => 'Text',
                                'textarea' => 'Textarea',
                                'wp_editor' => 'WP Editor',
                                'number' => 'Number',
                                'email' => 'Email',
                                'url' => 'URL',
                                'color' => 'Color',
                                'upload' => 'Upload',
                                'gallery' => 'Gallery',
                                'select' => 'Select Dropdown',
                                'radio' => 'Radio',
                                'checkbox' => 'Checkbox',
                                'switcher' => 'Switcher',
                                'date' => 'Date',
                                'icon' => 'Icon',
                                'group' => 'Group (Repeatable Items)',
                                'repeater' => 'Repeater',
                            ],
                            'default' => 'text',
                        ],
                        [
                            'id' => 'field_title',
                            'type' => 'text',
                            'title' => __('Field Title', 'muhamed-ahmed'),
                        ],
                        [
                            'id' => 'field_subtitle',
                            'type' => 'text',
                            'title' => __('Field Subtitle', 'muhamed-ahmed'),
                        ],
                        [
                            'id' => 'field_desc',
                            'type' => 'textarea',
                            'title' => __('Description', 'muhamed-ahmed'),
                        ],
                        [
                            'id' => 'field_default',
                            'type' => 'text',
                            'title' => __('Default Value', 'muhamed-ahmed'),
                        ],
                        [
                            'id' => 'field_placeholder',
                            'type' => 'text',
                            'title' => __('Placeholder', 'muhamed-ahmed'),
                        ],
                        [
                            'id' => 'field_required',
                            'type' => 'switcher',
                            'title' => __('Required', 'muhamed-ahmed'),
                        ],
                        
                        // ═══════════════════════════════════════
                        // DYNAMIC OPTIONS FOR ANY PURPOSE
                        // ═══════════════════════════════════════
                        [
                            'type' => 'subheading',
                            'content' => __('🎨 Dynamic Options (For Group/Repeater/Select/Radio/Checkbox)', 'muhamed-ahmed'),
                        ],
                        [
                            'type' => 'content',
                            'content' => '<div style="background:#e8f5e9;padding:12px;border-radius:6px;font-size:13px;margin-bottom:15px;">' .
                                '<strong>Use for:</strong> Social Media, Services, Features, Team, Products, Steps, Links, FAQs, Portfolio, Testimonials, etc.' .
                                '</div>',
                        ],
                        [
                            'id' => 'field_options',
                            'type' => 'group',
                            'title' => __('Options', 'muhamed-ahmed'),
                            'subtitle' => __('Universal options for any content type', 'muhamed-ahmed'),
                            'button_title' => __('Add Option', 'muhamed-ahmed'),
                            'accordion_title_prefix' => __('Item: ', 'muhamed-ahmed'),
                            'fields' => [
                                
                                // ─── Basic Info (Always Visible) ───
                                [
                                    'id' => 'option_key',
                                    'type' => 'text',
                                    'title' => __('Key/Slug', 'muhamed-ahmed'),
                                    'subtitle' => __('Used in code (lowercase)', 'muhamed-ahmed'),
                                    'placeholder' => 'facebook',
                                    'sanitize' => 'sanitize_key',
                                ],
                                [
                                    'id' => 'option_label',
                                    'type' => 'text',
                                    'title' => __('Display Name', 'muhamed-ahmed'),
                                    'subtitle' => __('Shown to users', 'muhamed-ahmed'),
                                    'placeholder' => 'Facebook',
                                ],
                                
                                // ─── Enable Advanced Features ───
                                [
                                    'id' => 'enable_advanced',
                                    'type' => 'switcher',
                                    'title' => __('🎨 Enable Advanced Features', 'muhamed-ahmed'),
                                    'subtitle' => __('Icons, images, URLs, colors, custom data', 'muhamed-ahmed'),
                                    'text_on' => __('Yes', 'muhamed-ahmed'),
                                    'text_off' => __('No', 'muhamed-ahmed'),
                                    'default' => false,
                                ],
                                
                                // ─── Display Type ───
                                [
                                    'id' => 'display_type',
                                    'type' => 'button_set',
                                    'title' => __('Display Type', 'muhamed-ahmed'),
                                    'options' => [
                                        'icon' => __('Icon', 'muhamed-ahmed'),
                                        'image' => __('Image', 'muhamed-ahmed'),
                                        'text' => __('Text', 'muhamed-ahmed'),
                                        'none' => __('None', 'muhamed-ahmed'),
                                    ],
                                    'default' => 'icon',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Icon (Font Awesome/Dashicons) ───
                                [
                                    'id' => 'option_icon',
                                    'type' => 'icon',
                                    'title' => __('Icon', 'muhamed-ahmed'),
                                    'default' => 'fab fa-facebook-f',
                                    'dependency' => [
                                        ['enable_advanced', '==', true],
                                        ['display_type', '==', 'icon'],
                                    ],
                                ],
                                
                                // ─── Custom Image ───
                                [
                                    'id' => 'option_image',
                                    'type' => 'upload',
                                    'title' => __('Custom Image', 'muhamed-ahmed'),
                                    'library' => 'image',
                                    'button_title' => __('Upload', 'muhamed-ahmed'),
                                    'remove_title' => __('Remove', 'muhamed-ahmed'),
                                    'dependency' => [
                                        ['enable_advanced', '==', true],
                                        ['display_type', '==', 'image'],
                                    ],
                                ],
                                
                                // ─── URL/Link ───
                                [
                                    'id' => 'option_url',
                                    'type' => 'text',
                                    'title' => __('URL/Link', 'muhamed-ahmed'),
                                    'subtitle' => __('External link, social profile, etc.', 'muhamed-ahmed'),
                                    'placeholder' => 'https://example.com',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Link Target ───
                                [
                                    'id' => 'option_target',
                                    'type' => 'button_set',
                                    'title' => __('Link Target', 'muhamed-ahmed'),
                                    'options' => [
                                        '_self' => __('Same Window', 'muhamed-ahmed'),
                                        '_blank' => __('New Tab', 'muhamed-ahmed'),
                                    ],
                                    'default' => '_blank',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Colors ───
                                [
                                    'id' => 'option_color',
                                    'type' => 'color',
                                    'title' => __('Color', 'muhamed-ahmed'),
                                    'subtitle' => __('Background or theme color', 'muhamed-ahmed'),
                                    'default' => '#3b5998',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                [
                                    'id' => 'option_hover_color',
                                    'type' => 'color',
                                    'title' => __('Hover Color', 'muhamed-ahmed'),
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Custom CSS Class ───
                                [
                                    'id' => 'option_class',
                                    'type' => 'text',
                                    'title' => __('CSS Class', 'muhamed-ahmed'),
                                    'placeholder' => 'custom-class',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Display Order ───
                                [
                                    'id' => 'option_order',
                                    'type' => 'number',
                                    'title' => __('Order', 'muhamed-ahmed'),
                                    'subtitle' => __('Lower numbers appear first', 'muhamed-ahmed'),
                                    'default' => 0,
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Description/Note ───
                                [
                                    'id' => 'option_description',
                                    'type' => 'textarea',
                                    'title' => __('Description', 'muhamed-ahmed'),
                                    'subtitle' => __('Internal note or tooltip', 'muhamed-ahmed'),
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Custom Data (JSON) ───
                                [
                                    'id' => 'option_custom_data',
                                    'type' => 'textarea',
                                    'title' => __('Custom Data (JSON)', 'muhamed-ahmed'),
                                    'subtitle' => __('Store any additional data', 'muhamed-ahmed'),
                                    'placeholder' => '{"key": "value"}',
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                                // ─── Enable/Disable ───
                                [
                                    'id' => 'option_enabled',
                                    'type' => 'switcher',
                                    'title' => __('Active', 'muhamed-ahmed'),
                                    'text_on' => __('Yes', 'muhamed-ahmed'),
                                    'text_off' => __('No', 'muhamed-ahmed'),
                                    'default' => true,
                                    'dependency' => ['enable_advanced', '==', true],
                                ],
                                
                            ],
                            'dependency' => ['field_type', 'any', 'group,repeater,select,radio,checkbox'],
                        ],
                        
                    ],
                ],
            ],
        ],
    ],
]);

// ════════════════════════════════════════════════════════════
// SECTION 4: Import/Export
// ════════════════════════════════════════════════════════════
CSF::createSection($prefix, [
    'id' => 'backup_section',
    'title' => __('Import/Export', 'muhamed-ahmed'),
    'icon' => 'fas fa-download',
    'fields' => [
        [
            'type' => 'backup',
            'title' => __('Backup & Restore', 'muhamed-ahmed'),
        ],
    ],
]);


/**
 * Register CPTs from panel data
 */
add_action('init', function() use ($prefix) {
    $options = get_option($prefix, []);
    
    if (empty($options['custom_post_types'])) {
        return;
    }
    
    foreach ($options['custom_post_types'] as $cpt) {
        if (empty($cpt['name'])) {
            continue;
        }
        
        // Check if CPT class exists
        if (!class_exists('MuhamedAhmed\CPT')) {
            error_log('Muhamed Ahmed Panel: CPT class not found');
            return;
        }
        
        try {
            // Prepare names
            $names = [
                'name' => $cpt['name'],
                'singular' => $cpt['singular'] ?? '',
                'plural' => $cpt['plural'] ?? '',
                'slug' => $cpt['slug'] ?? '',
            ];
            
            // Prepare options
            $cptOptions = [
                'public'       => $cpt['public'] ?? true,
                'has_archive'  => $cpt['has_archive'] ?? true,
                'show_in_rest' => $cpt['show_in_rest'] ?? true,
                'hierarchical' => $cpt['hierarchical'] ?? false,
                'supports'     => $cpt['supports'] ?? ['title', 'editor', 'thumbnail'],
                'menu_icon'    => $cpt['menu_icon'] ?? 'dashicons-admin-post',
                'menu_position' => $cpt['menu_position'] ?? 20,
                'capability_type' => 'post',
                'map_meta_cap'    => true,
                'rest_base'       => $cpt['name'],
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'rewrite' => [
                    'slug' => $cpt['slug'] ?? $cpt['name'],
                    'with_front' => false,
                ],
            ];
            
            // Register CPT
            new \MuhamedAhmed\CPT($names, $cptOptions);
            
        } catch (Exception $e) {
            error_log('Muhamed Ahmed Panel CPT Error: ' . $e->getMessage());
        }
    }
}, 0);

/**
 * Register Taxonomies from panel data
 */
add_action('init', function() use ($prefix) {
    $options = get_option($prefix, []);
    
    if (empty($options['custom_taxonomies']) || empty($options['custom_post_types'])) {
        return;
    }
    
    // Get registered CPT names
    $registered_cpts = [];
    foreach ($options['custom_post_types'] as $cpt) {
        if (!empty($cpt['name'])) {
            $registered_cpts[] = $cpt['name'];
        }
    }
    
    foreach ($options['custom_taxonomies'] as $tax) {
        if (empty($tax['name']) || empty($tax['post_types'])) {
            continue;
        }
        
        try {
            // Prepare names
            $names = [
                'name' => $tax['name'],
                'singular' => $tax['singular'] ?? '',
                'plural' => $tax['plural'] ?? '',
                'slug' => $tax['slug'] ?? '',
            ];
            
            // Prepare options
            $taxOptions = [
                'hierarchical' => $tax['hierarchical'] ?? true,
                'show_admin_column' => $tax['show_admin_column'] ?? true,
                'show_in_rest' => $tax['show_in_rest'] ?? true,
                'rest_base' => $tax['name'],
                'rest_controller_class' => 'WP_REST_Terms_Controller',
                'rewrite' => [
                    'slug' => $tax['slug'] ?? $tax['name'],
                    'with_front' => false,
                ],
            ];
            
            // Register for each selected post type
            foreach ($tax['post_types'] as $post_type) {
                // Only register if it's one of our custom post types
                if (in_array($post_type, $registered_cpts)) {
                    register_taxonomy($tax['name'], $post_type, $taxOptions);
                }
            }
            
        } catch (Exception $e) {
            error_log('Muhamed Ahmed Panel Taxonomy Error: ' . $e->getMessage());
        }
    }
}, 0);

/**
 * Arabic slug transliteration
 */
add_filter('sanitize_title', function($title, $raw_title = '', $context = 'save') {
    if (!preg_match('/[\x{0600}-\x{06FF}]/u', $raw_title)) {
        return $title;
    }
    
    $transliteration = [
        'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'aa',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j',
        'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'th',
        'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
        'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z',
        'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q',
        'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a',
        'ة' => 'h', 'ئ' => 'e', 'ء' => 'a',
    ];
    
    $slug = str_replace(array_keys($transliteration), array_values($transliteration), $raw_title);
    $slug = preg_replace('/[^\p{Latin}\s\-0-9]/u', '', $slug);
    $slug = strtolower(trim(preg_replace('/\s+/', '-', $slug), '-'));
    
    return $slug ?: 'post-' . uniqid();
}, 10, 3);

/**
 * Term slug transliteration
 */
add_filter('pre_insert_term', function($term, $taxonomy) {
    if (!isset($term['name']) || !preg_match('/[\x{0600}-\x{06FF}]/u', $term['name'])) {
        return $term;
    }
    
    if (empty($term['slug'])) {
        $term['slug'] = sanitize_title($term['name']);
    }
    
    return $term;
}, 10, 2);

/**
 * Flush rewrite rules on save
 */
add_action('csf_' . $prefix . '_saved', function() {
    flush_rewrite_rules();
    delete_transient('rewrite_rules');
});

/**
 * AJAX handler to refresh post types dropdown
 */
add_action('wp_ajax_ma_refresh_post_types', function() {
    check_ajax_referer('ma_nonce', 'nonce');
    wp_send_json_success(ma_get_post_types_list());
});

/**
 * Enqueue admin scripts
 */
add_action('admin_enqueue_scripts', function($hook) use ($prefix) {
    if ($hook !== 'toplevel_page_muhamed-ahmed-panel') {
        return;
    }
    
    
    wp_localize_script('ma-panel-refresh', 'maPanelData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ma_nonce'),
    ]);
});







function my_custom_prefix_admin_footer_js() {
  ?>
  <style>
      .hideIt {
          opacity: 0;
          height: 1px;
      }
  </style>
  <script type="text/javascript">
    /**
 * Muhamed Ahmed Panel - Real-time Refresh
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Listen for save event
        $(document).on('csf.save.success', function() {
            setTimeout(refreshPostTypesDropdown, 500);
        });
    });
    
    function refreshPostTypesDropdown() {
        $.ajax({
            url: maPanelData.ajax_url,
            type: 'POST',
            data: {
                action: 'ma_refresh_post_types',
                nonce: maPanelData.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateSelectFields(response.data);
                }
            }
        });
    }
    
    function updateSelectFields(options) {
        $('select[data-depend-id="post_types"]').each(function() {
            var $select = $(this);
            var currentValues = $select.val() || [];
            
            // Destroy Chosen if active
            if ($select.next().hasClass('chosen-container')) {
                $select.chosen('destroy');
            }
            
            // Clear and rebuild
            $select.empty();
            
            $.each(options, function(key, label) {
                var $option = $('<option></option>')
                    .val(key)
                    .text(label);
                    
                if (currentValues.indexOf(key) !== -1) {
                    $option.prop('selected', true);
                }
                
                $select.append($option);
            });
            
            // Re-init Chosen
            $select.chosen({width: '100%'});
        });
    }
    
})(jQuery);

  </script>
  <?php
}
add_action('admin_footer', 'my_custom_prefix_admin_footer_js');



// AJAX handler to fetch tags based on the selected term of type from CSF options
add_action('wp_ajax_fetch_tags_by_term', 'fetch_tags_by_term');
add_action('wp_ajax_nopriv_fetch_tags_by_term', 'fetch_tags_by_term');
function fetch_tags_by_term() {
  $term_id = isset($_POST['term_id']) ? (is_array($_POST['term_id']) ? end($_POST['term_id']) : $_POST['term_id']) : 0;
  $term_id = intval($term_id); // Convert term_id to integer

  $prefix = 'places_options'; // Your CSF options prefix

  // Retrieve the saved options from CSF
  $options = get_option($prefix);

  // Assuming the tags are stored within a group field
  $tags = isset($options['opt-group-1']) ? $options['opt-group-1'] : array();

  // Initialize an empty array to store tag names
  $tag_names = array();


  // Loop through $tags to find matching term_id_select
  foreach ($tags as $k => $tag) {


  if ($tag['term_id_select'] && $tag['term_id_select'] ==   $term_id ) {

  
          // If term_id_select matches, add term_tags to $tag_names array
          if (isset($tag['term_tags'])) {
              // Split term_tags into an array of individual tags
              $individual_tags = explode(', ', $tag['term_tags']);
              // Merge individual tags into the $tag_names array
              $tag_names = $individual_tags; //array_merge($tag_names, $individual_tags);
          } else {
            $tag_names = [__('No tags are set!', TRNS)];
          }
      }
  }

  // Return the tag names as a JSON response
  wp_send_json_success($tag_names);

  // Terminate immediately and return a proper response
  wp_die();
}
