<?php
/**
 * YTrip Complete Admin Configuration
 * 
 * Full CodeStar Framework integration with all features.
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Admin_Config
 */
class YTrip_Admin_Config {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action('csf_loaded', [$this, 'init']);
    }

    /**
     * Initialize admin configuration.
     *
     * @return void
     */
    public function init() {
        if (!class_exists('CSF')) {
            return;
        }

        $this->create_main_panel();
        $this->create_tour_metabox();
        $this->create_taxonomy_meta();
    }

    /**
     * Create main admin panel.
     * Panel is created once in codestar-config.php (top-level menu). We only add sections here
     * to avoid duplicate menu entries and duplicate left nav.
     *
     * @return void
     */
    private function create_main_panel() {
        // Do not call createOptions again – codestar-config.php already creates the panel.
        // Only add sections so they merge into the single panel.

        // General Settings
        CSF::createSection('ytrip_settings', [
            'title' => __('General', 'ytrip'),
            'icon' => 'fas fa-cog',
            'fields' => $this->get_general_fields(),
        ]);

        // Permalinks/Slugs
        CSF::createSection('ytrip_settings', [
            'title' => __('Permalinks', 'ytrip'),
            'icon' => 'fas fa-link',
            'fields' => $this->get_permalink_fields(),
        ]);

        // Display Options
        CSF::createSection('ytrip_settings', [
            'title' => __('Display', 'ytrip'),
            'icon' => 'fas fa-desktop',
            'fields' => $this->get_display_fields(),
        ]);

        // Colors
        CSF::createSection('ytrip_settings', [
            'title' => __('Colors', 'ytrip'),
            'icon' => 'fas fa-palette',
            'fields' => $this->get_color_fields(),
        ]);

        // Typography
        CSF::createSection('ytrip_settings', [
            'title' => __('Typography', 'ytrip'),
            'icon' => 'fas fa-font',
            'fields' => $this->get_typography_fields(),
        ]);

        // Tours Display
        CSF::createSection('ytrip_settings', [
            'title' => __('Tours', 'ytrip'),
            'icon' => 'fas fa-map-marked-alt',
            'fields' => $this->get_tours_fields(),
        ]);

        // Related Tours
        CSF::createSection('ytrip_settings', [
            'title' => __('Related Tours', 'ytrip'),
            'icon' => 'fas fa-project-diagram',
            'fields' => $this->get_related_tours_fields(),
        ]);

        // Performance
        CSF::createSection('ytrip_settings', [
            'title' => __('Performance', 'ytrip'),
            'icon' => 'fas fa-tachometer-alt',
            'fields' => $this->get_performance_fields(),
        ]);

        // Import/Export
        CSF::createSection('ytrip_settings', [
            'title' => __('Import/Export', 'ytrip'),
            'icon' => 'fas fa-database',
            'fields' => [
                [
                    'id' => 'backup',
                    'type' => 'backup',
                    'title' => __('Backup & Restore', 'ytrip'),
                    'desc' => __('Export your settings or import from a backup file.', 'ytrip'),
                ],
            ],
        ]);
    }

    /**
     * Get general settings fields.
     *
     * @return array
     */
    private function get_general_fields() {
        return [
            [
                'id' => 'general_section',
                'type' => 'tabbed',
                'tabs' => [
                    [
                        'title' => __('Currency', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'currency',
                                'type' => 'select',
                                'title' => __('Currency', 'ytrip'),
                                'options' => $this->get_currency_options(),
                                'default' => 'USD',
                            ],
                            [
                                'id' => 'currency_symbol',
                                'type' => 'text',
                                'title' => __('Currency Symbol', 'ytrip'),
                                'default' => '$',
                                'attributes' => [
                                    'maxlength' => 5,
                                ],
                            ],
                            [
                                'id' => 'currency_position',
                                'type' => 'button_set',
                                'title' => __('Currency Position', 'ytrip'),
                                'options' => [
                                    'before' => __('Before ($100)', 'ytrip'),
                                    'after' => __('After (100$)', 'ytrip'),
                                ],
                                'default' => 'before',
                            ],
                            [
                                'id' => 'thousand_separator',
                                'type' => 'text',
                                'title' => __('Thousand Separator', 'ytrip'),
                                'default' => ',',
                                'attributes' => [
                                    'maxlength' => 1,
                                ],
                            ],
                            [
                                'id' => 'decimal_separator',
                                'type' => 'text',
                                'title' => __('Decimal Separator', 'ytrip'),
                                'default' => '.',
                                'attributes' => [
                                    'maxlength' => 1,
                                ],
                            ],
                            [
                                'id' => 'decimals',
                                'type' => 'spinner',
                                'title' => __('Decimal Places', 'ytrip'),
                                'default' => '2',
                                'min' => '0',
                                'max' => '4',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Date & Time', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'date_format',
                                'type' => 'select',
                                'title' => __('Date Format', 'ytrip'),
                                'options' => [
                                    'Y-m-d' => date('Y-m-d'),
                                    'd/m/Y' => date('d/m/Y'),
                                    'm/d/Y' => date('m/d/Y'),
                                    'd-m-Y' => date('d-m-Y'),
                                    'm-d-Y' => date('m-d-Y'),
                                    'F j, Y' => date('F j, Y'),
                                    'j F Y' => date('j F Y'),
                                    'M j, Y' => date('M j, Y'),
                                ],
                                'default' => 'Y-m-d',
                            ],
                            [
                                'id' => 'time_format',
                                'type' => 'select',
                                'title' => __('Time Format', 'ytrip'),
                                'options' => [
                                    'H:i' => '24-hour (' . date('H:i') . ')',
                                    'g:i A' => '12-hour (' . date('g:i A') . ')',
                                ],
                                'default' => 'H:i',
                            ],
                            [
                                'id' => 'timezone',
                                'type' => 'select',
                                'title' => __('Timezone', 'ytrip'),
                                'options' => $this->get_timezone_options(),
                                'default' => 'UTC',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Booking', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'min_advance_booking',
                                'type' => 'spinner',
                                'title' => __('Minimum Advance Booking (Days)', 'ytrip'),
                                'default' => '1',
                                'min' => '0',
                                'max' => '365',
                            ],
                            [
                                'id' => 'max_advance_booking',
                                'type' => 'spinner',
                                'title' => __('Maximum Advance Booking (Days)', 'ytrip'),
                                'default' => '365',
                                'min' => '1',
                                'max' => '730',
                            ],
                            [
                                'id' => 'require_login',
                                'type' => 'switcher',
                                'title' => __('Require Login to Book', 'ytrip'),
                                'default' => false,
                            ],
                            [
                                'id' => 'enable_guest_booking',
                                'type' => 'switcher',
                                'title' => __('Enable Guest Booking', 'ytrip'),
                                'default' => true,
                                'dependency' => ['require_login', '==', 'false'],
                            ],
                            [
                                'id' => 'booking_confirmation',
                                'type' => 'select',
                                'title' => __('Booking Confirmation', 'ytrip'),
                                'options' => [
                                    'auto' => __('Automatic', 'ytrip'),
                                    'manual' => __('Manual Review', 'ytrip'),
                                    'payment' => __('After Payment', 'ytrip'),
                                ],
                                'default' => 'auto',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get permalink settings fields.
     *
     * @return array
     */
    private function get_permalink_fields() {
        return [
            [
                'type' => 'content',
                'content' => '<div class="csf-warning">' . __('After changing permalinks, visit Settings > Permalinks and click Save Changes to flush rewrite rules.', 'ytrip') . '</div>',
            ],
            [
                'id' => 'slug_tour',
                'type' => 'text',
                'title' => __('Tour Post Type Slug', 'ytrip'),
                'default' => 'tour',
                'desc' => __('URL structure: yoursite.com/{slug}/tour-name/', 'ytrip'),
                'attributes' => [
                    'placeholder' => 'tour',
                ],
            ],
            [
                'id' => 'slug_destination',
                'type' => 'text',
                'title' => __('Destination Taxonomy Slug', 'ytrip'),
                'default' => 'destination',
                'desc' => __('URL structure: yoursite.com/{slug}/destination-name/', 'ytrip'),
                'attributes' => [
                    'placeholder' => 'destination',
                ],
            ],
            [
                'id' => 'slug_category',
                'type' => 'text',
                'title' => __('Category Taxonomy Slug', 'ytrip'),
                'default' => 'tour-category',
                'desc' => __('URL structure: yoursite.com/{slug}/category-name/', 'ytrip'),
                'attributes' => [
                    'placeholder' => 'tour-category',
                ],
            ],
            [
                'id' => 'slug_tag',
                'type' => 'text',
                'title' => __('Tag Taxonomy Slug', 'ytrip'),
                'default' => 'tour-tag',
                'attributes' => [
                    'placeholder' => 'tour-tag',
                ],
            ],
            [
                'id' => 'slug_archive',
                'type' => 'text',
                'title' => __('Archive Page Slug', 'ytrip'),
                'default' => 'tours',
                'desc' => __('URL structure: yoursite.com/{slug}/', 'ytrip'),
                'attributes' => [
                    'placeholder' => 'tours',
                ],
            ],
        ];
    }

    /**
     * Get display settings fields.
     *
     * @return array
     */
    private function get_display_fields() {
        return [
            [
                'id' => 'display_section',
                'type' => 'tabbed',
                'tabs' => [
                    [
                        'title' => __('Archive', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'archive_layout',
                                'type' => 'image_select',
                                'title' => __('Archive Layout', 'ytrip'),
                                'options' => [
                                    'grid' => YTRIP_URL . 'assets/admin/img/layout-grid.png',
                                    'list' => YTRIP_URL . 'assets/admin/img/layout-list.png',
                                    'carousel' => YTRIP_URL . 'assets/admin/img/layout-carousel.png',
                                    'map' => YTRIP_URL . 'assets/admin/img/layout-map.png',
                                ],
                                'default' => 'grid',
                            ],
                            [
                                'id' => 'archive_columns',
                                'type' => 'slider',
                                'title' => __('Grid Columns', 'ytrip'),
                                'default' => '3',
                                'min' => '2',
                                'max' => '5',
                                'step' => '1',
                                'dependency' => ['archive_layout', '==', 'grid'],
                            ],
                            [
                                'id' => 'archive_sidebar',
                                'type' => 'select',
                                'title' => __('Sidebar Position', 'ytrip'),
                                'options' => [
                                    'left' => __('Left', 'ytrip'),
                                    'right' => __('Right', 'ytrip'),
                                    'none' => __('No Sidebar', 'ytrip'),
                                ],
                                'default' => 'right',
                            ],
                            [
                                'id' => 'archive_cards',
                                'type' => 'spinner',
                                'title' => __('Cards Per Page', 'ytrip'),
                                'default' => '12',
                                'min' => '4',
                                'max' => '48',
                            ],
                            [
                                'id'          => 'archive_pagination_style',
                                'type'        => 'select',
                                'title'       => __( 'Pagination Style', 'ytrip' ),
                                'desc'        => __( 'Numeric (AJAX): page numbers load via AJAX and update the URL. Load more: button loads next page. Infinite scroll: next page loads when user scrolls to bottom.', 'ytrip' ),
                                'options'     => [
                                    'numbered' => __( 'Numeric (AJAX)', 'ytrip' ),
                                    'loadmore' => __( 'Load more', 'ytrip' ),
                                    'infinite' => __( 'Infinite scroll', 'ytrip' ),
                                ],
                                'default'     => 'numbered',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Card Style', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'card_style',
                                'type' => 'image_select',
                                'title' => __('Card Style', 'ytrip'),
                                'options' => [
                                    'classic' => YTRIP_URL . 'assets/admin/img/card-classic.png',
                                    'modern' => YTRIP_URL . 'assets/admin/img/card-modern.png',
                                    'overlay' => YTRIP_URL . 'assets/admin/img/card-overlay.png',
                                    'glass' => YTRIP_URL . 'assets/admin/img/card-glass.png',
                                    'minimal' => YTRIP_URL . 'assets/admin/img/card-minimal.png',
                                ],
                                'default' => 'modern',
                            ],
                            [
                                'id' => 'card_border_radius',
                                'type' => 'slider',
                                'title' => __('Border Radius', 'ytrip'),
                                'default' => '12',
                                'min' => '0',
                                'max' => '30',
                                'step' => '2',
                                'unit' => 'px',
                            ],
                            [
                                'id' => 'card_shadow',
                                'type' => 'select',
                                'title' => __('Shadow Style', 'ytrip'),
                                'options' => [
                                    'none' => __('None', 'ytrip'),
                                    'sm' => __('Small', 'ytrip'),
                                    'md' => __('Medium', 'ytrip'),
                                    'lg' => __('Large', 'ytrip'),
                                    'hover' => __('On Hover', 'ytrip'),
                                ],
                                'default' => 'md',
                            ],
                            [
                                'id' => 'card_image_ratio',
                                'type' => 'select',
                                'title' => __('Image Aspect Ratio', 'ytrip'),
                                'options' => [
                                    '4/3' => __('4:3 (Standard)', 'ytrip'),
                                    '16/9' => __('16:9 (Widescreen)', 'ytrip'),
                                    '3/2' => __('3:2 (Classic)', 'ytrip'),
                                    '1/1' => __('1:1 (Square)', 'ytrip'),
                                    '3/4' => __('3:4 (Portrait)', 'ytrip'),
                                ],
                                'default' => '4/3',
                            ],
                            [
                                'id' => 'card_elements',
                                'type' => 'checkbox',
                                'title' => __('Card Elements', 'ytrip'),
                                'options' => [
                                    'wishlist' => __('Wishlist Button', 'ytrip'),
                                    'badge' => __('Featured/Sale Badge', 'ytrip'),
                                    'rating' => __('Rating Stars', 'ytrip'),
                                    'duration' => __('Duration', 'ytrip'),
                                    'group_size' => __('Group Size', 'ytrip'),
                                    'location' => __('Location', 'ytrip'),
                                    'price' => __('Price', 'ytrip'),
                                    'excerpt' => __('Excerpt', 'ytrip'),
                                ],
                                'default' => ['wishlist', 'badge', 'rating', 'duration', 'price'],
                            ],
                        ],
                    ],
                    [
                        'title' => __('Carousel', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'carousel_autoplay',
                                'type' => 'switcher',
                                'title' => __('Auto Play', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'carousel_speed',
                                'type' => 'slider',
                                'title' => __('Auto Play Speed (ms)', 'ytrip'),
                                'default' => '5000',
                                'min' => '1000',
                                'max' => '10000',
                                'step' => '500',
                                'dependency' => ['carousel_autoplay', '==', 'true'],
                            ],
                            [
                                'id' => 'carousel_pause_hover',
                                'type' => 'switcher',
                                'title' => __('Pause on Hover', 'ytrip'),
                                'default' => true,
                                'dependency' => ['carousel_autoplay', '==', 'true'],
                            ],
                            [
                                'id' => 'carousel_loop',
                                'type' => 'switcher',
                                'title' => __('Infinite Loop', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'carousel_navigation',
                                'type' => 'checkbox',
                                'title' => __('Navigation', 'ytrip'),
                                'options' => [
                                    'arrows' => __('Arrows', 'ytrip'),
                                    'dots' => __('Dots/Pagination', 'ytrip'),
                                ],
                                'default' => ['arrows', 'dots'],
                            ],
                            [
                                'id' => 'carousel_slides',
                                'type' => 'slider',
                                'title' => __('Slides Per View (Desktop)', 'ytrip'),
                                'default' => '3',
                                'min' => '1',
                                'max' => '6',
                            ],
                            [
                                'id' => 'carousel_slides_tablet',
                                'type' => 'slider',
                                'title' => __('Slides Per View (Tablet)', 'ytrip'),
                                'default' => '2',
                                'min' => '1',
                                'max' => '4',
                            ],
                            [
                                'id' => 'carousel_slides_mobile',
                                'type' => 'slider',
                                'title' => __('Slides Per View (Mobile)', 'ytrip'),
                                'default' => '1',
                                'min' => '1',
                                'max' => '2',
                            ],
                            [
                                'id' => 'carousel_gap',
                                'type' => 'slider',
                                'title' => __('Gap Between Slides', 'ytrip'),
                                'default' => '20',
                                'min' => '0',
                                'max' => '60',
                                'step' => '5',
                                'unit' => 'px',
                            ],
                        ],
                    ],
                    [
                        'title' => __('List View', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'list_show_image',
                                'type' => 'switcher',
                                'title' => __('Show Image', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'list_image_width',
                                'type' => 'slider',
                                'title' => __('Image Width', 'ytrip'),
                                'default' => '300',
                                'min' => '150',
                                'max' => '500',
                                'step' => '25',
                                'unit' => 'px',
                                'dependency' => ['list_show_image', '==', 'true'],
                            ],
                            [
                                'id' => 'list_elements',
                                'type' => 'checkbox',
                                'title' => __('List Elements', 'ytrip'),
                                'options' => [
                                    'rating' => __('Rating Stars', 'ytrip'),
                                    'duration' => __('Duration', 'ytrip'),
                                    'group_size' => __('Group Size', 'ytrip'),
                                    'location' => __('Location', 'ytrip'),
                                    'price' => __('Price', 'ytrip'),
                                    'excerpt' => __('Excerpt', 'ytrip'),
                                    'highlights' => __('Highlights', 'ytrip'),
                                ],
                                'default' => ['rating', 'duration', 'price', 'excerpt'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get color settings fields using CSF palette.
     *
     * @return array
     */
    private function get_color_fields() {
        return [
            [
                'id' => 'color_preset',
                'type' => 'image_select',
                'title' => __('Color Preset', 'ytrip'),
                'desc' => __('Choose a predefined color scheme or select Custom to create your own.', 'ytrip'),
                'options' => $this->get_color_preset_options(),
                'default' => 'ocean_adventure',
            ],
            [
                'type' => 'content',
                'content' => '<hr><h3>' . __('Custom Colors', 'ytrip') . '</h3><p>' . __('Set custom colors below. These will override the preset colors.', 'ytrip') . '</p>',
            ],
            [
                'id' => 'custom_colors',
                'type' => 'tabbed',
                'title' => __('Custom Color Scheme', 'ytrip'),
                'tabs' => [
                    [
                        'title' => __('Brand', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'primary',
                                'type' => 'color',
                                'title' => __('Primary Color', 'ytrip'),
                                'default' => '#2563eb',
                            ],
                            [
                                'id' => 'secondary',
                                'type' => 'color',
                                'title' => __('Secondary Color', 'ytrip'),
                                'default' => '#7c3aed',
                            ],
                            [
                                'id' => 'accent',
                                'type' => 'color',
                                'title' => __('Accent Color', 'ytrip'),
                                'default' => '#f59e0b',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Semantic', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'success',
                                'type' => 'color',
                                'title' => __('Success Color', 'ytrip'),
                                'default' => '#10b981',
                            ],
                            [
                                'id' => 'warning',
                                'type' => 'color',
                                'title' => __('Warning Color', 'ytrip'),
                                'default' => '#f59e0b',
                            ],
                            [
                                'id' => 'error',
                                'type' => 'color',
                                'title' => __('Error Color', 'ytrip'),
                                'default' => '#ef4444',
                            ],
                            [
                                'id' => 'info',
                                'type' => 'color',
                                'title' => __('Info Color', 'ytrip'),
                                'default' => '#3b82f6',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Surfaces', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'background',
                                'type' => 'color',
                                'title' => __('Background Color', 'ytrip'),
                                'default' => '#ffffff',
                            ],
                            [
                                'id' => 'surface',
                                'type' => 'color',
                                'title' => __('Surface Color', 'ytrip'),
                                'default' => '#ffffff',
                            ],
                            [
                                'id' => 'border',
                                'type' => 'color',
                                'title' => __('Border Color', 'ytrip'),
                                'default' => '#e5e7eb',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Text', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'text_primary',
                                'type' => 'color',
                                'title' => __('Primary Text', 'ytrip'),
                                'default' => '#1e293b',
                            ],
                            [
                                'id' => 'text_secondary',
                                'type' => 'color',
                                'title' => __('Secondary Text', 'ytrip'),
                                'default' => '#64748b',
                            ],
                            [
                                'id' => 'text_muted',
                                'type' => 'color',
                                'title' => __('Muted Text', 'ytrip'),
                                'default' => '#94a3b8',
                            ],
                        ],
                    ],
                ],
                'dependency' => ['color_preset', '==', 'custom'],
            ],
            [
                'id' => 'color_palette',
                'type' => 'palette',
                'title' => __('Quick Color Palette', 'ytrip'),
                'desc' => __('Select a color combination to apply instantly.', 'ytrip'),
                'options' => [
                    'ocean' => [
                        '#0077b6', '#00b4d8', '#fca311', '#90e0ef', '#caf0f8',
                    ],
                    'sunset' => [
                        '#e56b6f', '#f4a261', '#e9c46a', '#2a9d8f', '#264653',
                    ],
                    'forest' => [
                        '#2d6a4f', '#40916c', '#52b788', '#74c69d', '#95d5b2',
                    ],
                    'royal' => [
                        '#6b2d5c', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe',
                    ],
                    'desert' => [
                        '#c17767', '#8b5a5a', '#e8c07d', '#cc580c', '#7d9d9c',
                    ],
                    'midnight' => [
                        '#1a1a2e', '#16213e', '#0f3460', '#e94560', '#533483',
                    ],
                ],
                'default' => 'ocean',
            ],
            [
                'id' => 'dark_mode',
                'type' => 'switcher',
                'title' => __('Enable Dark Mode Toggle', 'ytrip'),
                'desc' => __('Allow visitors to switch between light and dark mode.', 'ytrip'),
                'default' => false,
            ],
            [
                'id' => 'dark_colors',
                'type' => 'tabbed',
                'title' => __('Dark Mode Colors', 'ytrip'),
                'dependency' => ['dark_mode', '==', 'true'],
                'tabs' => [
                    [
                        'title' => __('Backgrounds', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'dark_background',
                                'type' => 'color',
                                'title' => __('Background', 'ytrip'),
                                'default' => '#0f172a',
                            ],
                            [
                                'id' => 'dark_surface',
                                'type' => 'color',
                                'title' => __('Surface', 'ytrip'),
                                'default' => '#1e293b',
                            ],
                            [
                                'id' => 'dark_border',
                                'type' => 'color',
                                'title' => __('Border', 'ytrip'),
                                'default' => '#475569',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Text', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'dark_text_primary',
                                'type' => 'color',
                                'title' => __('Primary Text', 'ytrip'),
                                'default' => '#f1f5f9',
                            ],
                            [
                                'id' => 'dark_text_secondary',
                                'type' => 'color',
                                'title' => __('Secondary Text', 'ytrip'),
                                'default' => '#94a3b8',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get typography settings fields.
     *
     * @return array
     */
    private function get_typography_fields() {
        return [
            [
                'id' => 'heading_font',
                'type' => 'typography',
                'title' => __('Heading Font', 'ytrip'),
                'output' => '.ytrip-section h1, .ytrip-section h2, .ytrip-section h3, .ytrip-section h4, .ytrip-section h5, .ytrip-section h6',
                'default' => [
                    'font-family' => 'Outfit',
                    'font-weight' => '700',
                ],
            ],
            [
                'id' => 'body_font',
                'type' => 'typography',
                'title' => __('Body Font', 'ytrip'),
                'output' => '.ytrip-section, .ytrip-wrapper',
                'default' => [
                    'font-family' => 'Inter',
                    'font-weight' => '400',
                ],
            ],
            [
                'id' => 'base_font_size',
                'type' => 'slider',
                'title' => __('Base Font Size', 'ytrip'),
                'default' => '16',
                'min' => '12',
                'max' => '20',
                'step' => '1',
                'unit' => 'px',
            ],
            [
                'id' => 'line_height',
                'type' => 'slider',
                'title' => __('Line Height', 'ytrip'),
                'default' => '1.6',
                'min' => '1.2',
                'max' => '2.0',
                'step' => '0.1',
            ],
        ];
    }

    /**
     * Get tours settings fields.
     *
     * @return array
     */
    private function get_tours_fields() {
        return [
            [
                'id' => 'single_layout',
                'type' => 'image_select',
                'title' => __('Single Tour Layout', 'ytrip'),
                'options' => [
                    'layout-1' => YTRIP_URL . 'assets/admin/img/single-1.png',
                    'layout-2' => YTRIP_URL . 'assets/admin/img/single-2.png',
                    'layout-3' => YTRIP_URL . 'assets/admin/img/single-3.png',
                    'layout-4' => YTRIP_URL . 'assets/admin/img/single-4.png',
                    'layout-5' => YTRIP_URL . 'assets/admin/img/single-5.png',
                ],
                'default' => 'layout-1',
            ],
            [
                'id' => 'single_elements',
                'type' => 'sorter',
                'title' => __('Single Tour Elements', 'ytrip'),
                'options' => [
                    'enabled' => [
                        'gallery' => __('Gallery/Slider', 'ytrip'),
                        'booking' => __('Booking Form', 'ytrip'),
                        'highlights' => __('Highlights', 'ytrip'),
                        'itinerary' => __('Itinerary', 'ytrip'),
                        'included' => __('Included/Excluded', 'ytrip'),
                        'map' => __('Map', 'ytrip'),
                        'reviews' => __('Reviews', 'ytrip'),
                        'faq' => __('FAQ', 'ytrip'),
                        'related' => __('Related Tours', 'ytrip'),
                    ],
                    'disabled' => [
                        'video' => __('Video', 'ytrip'),
                        'meeting_point' => __('Meeting Point', 'ytrip'),
                        'route' => __('Tour Route', 'ytrip'),
                    ],
                ],
            ],
            [
                'id' => 'show_breadcrumbs',
                'type' => 'switcher',
                'title' => __('Show Breadcrumbs', 'ytrip'),
                'default' => true,
            ],
            [
                'id' => 'show_share_buttons',
                'type' => 'switcher',
                'title' => __('Show Share Buttons', 'ytrip'),
                'default' => true,
            ],
            [
                'id' => 'share_networks',
                'type' => 'checkbox',
                'title' => __('Share Networks', 'ytrip'),
                'options' => [
                    'facebook' => __('Facebook', 'ytrip'),
                    'twitter' => __('Twitter/X', 'ytrip'),
                    'whatsapp' => __('WhatsApp', 'ytrip'),
                    'pinterest' => __('Pinterest', 'ytrip'),
                    'email' => __('Email', 'ytrip'),
                    'copy' => __('Copy Link', 'ytrip'),
                ],
                'default' => ['facebook', 'twitter', 'whatsapp', 'email'],
                'dependency' => ['show_share_buttons', '==', 'true'],
            ],
        ];
    }

    /**
     * Get related tours settings fields.
     *
     * @return array
     */
    private function get_related_tours_fields() {
        return [
            [
                'id' => 'related_enable',
                'type' => 'switcher',
                'title' => __('Enable Related Tours', 'ytrip'),
                'default' => true,
            ],
            [
                'id' => 'related_section',
                'type' => 'tabbed',
                'title' => __('Related Tours Settings', 'ytrip'),
                'dependency' => ['related_enable', '==', 'true'],
                'tabs' => [
                    [
                        'title' => __('General', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'related_title',
                                'type' => 'text',
                                'title' => __('Section Title', 'ytrip'),
                                'default' => 'You May Also Like',
                            ],
                            [
                                'id' => 'related_count',
                                'type' => 'slider',
                                'title' => __('Number of Tours', 'ytrip'),
                                'default' => '4',
                                'min' => '2',
                                'max' => '12',
                            ],
                            [
                                'id' => 'related_layout',
                                'type' => 'image_select',
                                'title' => __('Layout', 'ytrip'),
                                'options' => [
                                    'grid' => YTRIP_URL . 'assets/admin/img/layout-grid.png',
                                    'carousel' => YTRIP_URL . 'assets/admin/img/layout-carousel.png',
                                ],
                                'default' => 'carousel',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Auto Related', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'related_by_category',
                                'type' => 'switcher',
                                'title' => __('Related by Category', 'ytrip'),
                                'desc' => __('Show tours from the same category.', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'related_by_destination',
                                'type' => 'switcher',
                                'title' => __('Related by Destination', 'ytrip'),
                                'desc' => __('Show tours from the same destination.', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'related_priority',
                                'type' => 'select',
                                'title' => __('Priority', 'ytrip'),
                                'desc' => __('Which relation to prioritize when both match.', 'ytrip'),
                                'options' => [
                                    'destination' => __('Destination First', 'ytrip'),
                                    'category' => __('Category First', 'ytrip'),
                                    'random' => __('Random Mix', 'ytrip'),
                                ],
                                'default' => 'destination',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Fallback', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'related_fallback',
                                'type' => 'select',
                                'title' => __('Fallback Method', 'ytrip'),
                                'desc' => __('What to show when no related tours found.', 'ytrip'),
                                'options' => [
                                    'featured' => __('Featured Tours', 'ytrip'),
                                    'recent' => __('Recent Tours', 'ytrip'),
                                    'popular' => __('Popular Tours', 'ytrip'),
                                    'hide' => __('Hide Section', 'ytrip'),
                                ],
                                'default' => 'featured',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get performance settings fields.
     *
     * @return array
     */
    private function get_performance_fields() {
        return [
            [
                'id' => 'performance_section',
                'type' => 'tabbed',
                'tabs' => [
                    [
                        'title' => __('Caching', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'enable_query_cache',
                                'type' => 'switcher',
                                'title' => __('Enable Query Caching', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'cache_duration',
                                'type' => 'select',
                                'title' => __('Cache Duration', 'ytrip'),
                                'options' => [
                                    '1800' => __('30 Minutes', 'ytrip'),
                                    '3600' => __('1 Hour', 'ytrip'),
                                    '7200' => __('2 Hours', 'ytrip'),
                                    '14400' => __('4 Hours', 'ytrip'),
                                    '28800' => __('8 Hours', 'ytrip'),
                                    '86400' => __('24 Hours', 'ytrip'),
                                ],
                                'default' => '3600',
                                'dependency' => ['enable_query_cache', '==', 'true'],
                            ],
                        ],
                    ],
                    [
                        'title' => __('Images', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'lazy_load_images',
                                'type' => 'switcher',
                                'title' => __('Lazy Load Images', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'enable_webp',
                                'type' => 'switcher',
                                'title' => __('Enable WebP', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'image_quality',
                                'type' => 'slider',
                                'title' => __('Image Quality', 'ytrip'),
                                'default' => '85',
                                'min' => '60',
                                'max' => '100',
                                'unit' => '%',
                            ],
                        ],
                    ],
                    [
                        'title' => __('CSS/JS', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'critical_css',
                                'type' => 'switcher',
                                'title' => __('Inline Critical CSS', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'defer_js',
                                'type' => 'switcher',
                                'title' => __('Defer JavaScript', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'minify_css',
                                'type' => 'switcher',
                                'title' => __('Minify CSS', 'ytrip'),
                                'default' => true,
                            ],
                            [
                                'id' => 'minify_js',
                                'type' => 'switcher',
                                'title' => __('Minify JavaScript', 'ytrip'),
                                'default' => true,
                            ],
                        ],
                    ],
                    [
                        'title' => __('Database', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'enable_db_indexes',
                                'type' => 'switcher',
                                'title' => __('Enable Database Indexes', 'ytrip'),
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create tour metabox.
     * Uses the actual registered post type 'ytrip_tour' (not the URL slug) so the metabox displays when editing trips.
     *
     * @return void
     */
    private function create_tour_metabox() {
        CSF::createMetabox('ytrip_tour_details', [
            'title' => __('Tour Details', 'ytrip'),
            'post_type' => ['ytrip_tour'],
            'context' => 'normal',
            'priority' => 'high',
            'theme' => 'modern',
        ]);

        // Basic Info
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Basic Info', 'ytrip'),
            'icon' => 'fas fa-info-circle',
            'fields' => $this->get_tour_basic_fields(),
        ]);

        // Single Page Hero
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Single Page Hero', 'ytrip'),
            'icon' => 'fas fa-image',
            'fields' => $this->get_tour_hero_fields(),
        ]);

        // Pricing
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Pricing', 'ytrip'),
            'icon' => 'fas fa-dollar-sign',
            'fields' => $this->get_tour_pricing_fields(),
        ]);

        // Itinerary
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Itinerary', 'ytrip'),
            'icon' => 'fas fa-route',
            'fields' => $this->get_tour_itinerary_fields(),
        ]);

        // Gallery
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Gallery', 'ytrip'),
            'icon' => 'fas fa-images',
            'fields' => $this->get_tour_gallery_fields(),
        ]);

        // Included/Excluded
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Included/Excluded', 'ytrip'),
            'icon' => 'fas fa-list-check',
            'fields' => $this->get_tour_included_fields(),
        ]);

        // FAQ
        CSF::createSection('ytrip_tour_details', [
            'title' => __('FAQ', 'ytrip'),
            'icon' => 'fas fa-question-circle',
            'fields' => $this->get_tour_faq_fields(),
        ]);

        // Location
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Location', 'ytrip'),
            'icon' => 'fas fa-map-marker-alt',
            'fields' => $this->get_tour_location_fields(),
        ]);

        // Related Tours
        CSF::createSection('ytrip_tour_details', [
            'title' => __('Related Tours', 'ytrip'),
            'icon' => 'fas fa-project-diagram',
            'fields' => $this->get_tour_related_fields(),
        ]);
    }

    /**
     * Get tour basic fields.
     *
     * @return array
     */
    private function get_tour_basic_fields() {
        return [
            [
                'id' => 'featured',
                'type' => 'switcher',
                'title' => __('Featured Tour', 'ytrip'),
                'default' => false,
            ],
            [
                'id' => 'tour_code',
                'type' => 'text',
                'title' => __('Tour Code', 'ytrip'),
                'desc' => __('Unique identifier for this tour (e.g., EGY-001).', 'ytrip'),
            ],
            [
                'id' => 'duration',
                'type' => 'tabbed',
                'title' => __('Duration', 'ytrip'),
                'tabs' => [
                    [
                        'title' => __('Days', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'days',
                                'type' => 'spinner',
                                'title' => __('Days', 'ytrip'),
                                'default' => '1',
                                'min' => '0',
                                'max' => '365',
                            ],
                            [
                                'id' => 'nights',
                                'type' => 'spinner',
                                'title' => __('Nights', 'ytrip'),
                                'default' => '0',
                                'min' => '0',
                                'max' => '365',
                            ],
                        ],
                    ],
                    [
                        'title' => __('Hours', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'hours',
                                'type' => 'spinner',
                                'title' => __('Hours', 'ytrip'),
                                'default' => '0',
                                'min' => '0',
                                'max' => '24',
                            ],
                            [
                                'id' => 'minutes',
                                'type' => 'spinner',
                                'title' => __('Minutes', 'ytrip'),
                                'default' => '0',
                                'min' => '0',
                                'max' => '59',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'group_size',
                'type' => 'tabbed',
                'title' => __('Group Size', 'ytrip'),
                'tabs' => [
                    [
                        'title' => __('Size', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'min',
                                'type' => 'spinner',
                                'title' => __('Minimum', 'ytrip'),
                                'default' => '1',
                                'min' => '1',
                                'max' => '100',
                            ],
                            [
                                'id' => 'max',
                                'type' => 'spinner',
                                'title' => __('Maximum', 'ytrip'),
                                'default' => '20',
                                'min' => '1',
                                'max' => '500',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'difficulty',
                'type' => 'select',
                'title' => __('Difficulty Level', 'ytrip'),
                'options' => [
                    'easy' => __('Easy - Suitable for everyone', 'ytrip'),
                    'moderate' => __('Moderate - Some fitness required', 'ytrip'),
                    'challenging' => __('Challenging - Good fitness required', 'ytrip'),
                    'extreme' => __('Extreme - Very fit individuals only', 'ytrip'),
                ],
                'default' => 'easy',
            ],
            [
                'id' => 'languages',
                'type' => 'checkbox',
                'title' => __('Available Languages', 'ytrip'),
                'options' => [
                    'en' => __('English', 'ytrip'),
                    'ar' => __('Arabic', 'ytrip'),
                    'de' => __('German', 'ytrip'),
                    'fr' => __('French', 'ytrip'),
                    'es' => __('Spanish', 'ytrip'),
                    'it' => __('Italian', 'ytrip'),
                    'ru' => __('Russian', 'ytrip'),
                    'zh' => __('Chinese', 'ytrip'),
                    'ja' => __('Japanese', 'ytrip'),
                ],
                'default' => ['en'],
            ],
            [
                'id' => 'highlights',
                'type' => 'repeater',
                'title' => __('Tour Highlights', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'highlight',
                        'type' => 'text',
                        'title' => __('Highlight', 'ytrip'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tour pricing fields.
     *
     * @return array
     */
    private function get_tour_pricing_fields() {
        return [
            [
                'id' => 'pricing_type',
                'type' => 'button_set',
                'title' => __('Pricing Type', 'ytrip'),
                'options' => [
                    'fixed' => __('Fixed Price', 'ytrip'),
                    'person' => __('Per Person', 'ytrip'),
                    'group' => __('Group Price', 'ytrip'),
                    'variable' => __('Variable Pricing', 'ytrip'),
                    'inquiry' => __('On Inquiry', 'ytrip'),
                ],
                'default' => 'person',
            ],
            [
                'id' => 'regular_price',
                'type' => 'number',
                'title' => __('Regular Price', 'ytrip'),
                'dependency' => ['pricing_type', 'any', 'fixed,person'],
            ],
            [
                'id' => 'sale_price',
                'type' => 'number',
                'title' => __('Sale Price', 'ytrip'),
                'dependency' => ['pricing_type', 'any', 'fixed,person'],
            ],
            [
                'id' => 'sale_dates',
                'type' => 'date',
                'title' => __('Sale End Date', 'ytrip'),
                'dependency' => ['pricing_type', 'any', 'fixed,person'],
            ],
            [
                'id' => 'group_pricing',
                'type' => 'repeater',
                'title' => __('Group Pricing', 'ytrip'),
                'dependency' => ['pricing_type', '==', 'group'],
                'fields' => [
                    [
                        'id' => 'min_people',
                        'type' => 'number',
                        'title' => __('Min People', 'ytrip'),
                    ],
                    [
                        'id' => 'max_people',
                        'type' => 'number',
                        'title' => __('Max People', 'ytrip'),
                    ],
                    [
                        'id' => 'price_per_person',
                        'type' => 'number',
                        'title' => __('Price Per Person', 'ytrip'),
                    ],
                ],
            ],
            [
                'id' => 'deposit',
                'type' => 'tabbed',
                'title' => __('Deposit Options', 'ytrip'),
                'tabs' => [
                    [
                        'title' => __('Deposit', 'ytrip'),
                        'fields' => [
                            [
                                'id' => 'enable',
                                'type' => 'switcher',
                                'title' => __('Enable Deposit', 'ytrip'),
                                'default' => false,
                            ],
                            [
                                'id' => 'type',
                                'type' => 'button_set',
                                'title' => __('Deposit Type', 'ytrip'),
                                'options' => [
                                    'percentage' => __('Percentage', 'ytrip'),
                                    'fixed' => __('Fixed Amount', 'ytrip'),
                                ],
                                'default' => 'percentage',
                            ],
                            [
                                'id' => 'amount',
                                'type' => 'number',
                                'title' => __('Deposit Amount', 'ytrip'),
                                'default' => '20',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tour itinerary fields.
     *
     * @return array
     */
    private function get_tour_itinerary_fields() {
        return [
            [
                'id' => 'itinerary',
                'type' => 'repeater',
                'title' => __('Day by Day Itinerary', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'day_number',
                        'type' => 'number',
                        'title' => __('Day Number', 'ytrip'),
                    ],
                    [
                        'id' => 'day_title',
                        'type' => 'text',
                        'title' => __('Title', 'ytrip'),
                        'placeholder' => __('e.g., Arrival & Welcome', 'ytrip'),
                    ],
                    [
                        'id' => 'day_description',
                        'type' => 'wp_editor',
                        'title' => __('Description', 'ytrip'),
                        'media_buttons' => false,
                        'textarea_rows' => 5,
                    ],
                    [
                        'id' => 'meals',
                        'type' => 'checkbox',
                        'title' => __('Meals Included', 'ytrip'),
                        'options' => [
                            'breakfast' => __('Breakfast', 'ytrip'),
                            'lunch' => __('Lunch', 'ytrip'),
                            'dinner' => __('Dinner', 'ytrip'),
                        ],
                    ],
                    [
                        'id' => 'accommodation',
                        'type' => 'text',
                        'title' => __('Accommodation', 'ytrip'),
                    ],
                    [
                        'id' => 'image',
                        'type' => 'media',
                        'title' => __('Image', 'ytrip'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tour single page hero fields.
     *
     * @return array
     */
    private function get_tour_hero_fields() {
        return [
            [
                'id' => 'single_hero_type',
                'type' => 'button_set',
                'title' => __('Hero Type', 'ytrip'),
                'desc' => __('Single image uses the featured image (or first gallery image). Slider/Carousel uses all images from the Gallery section below.', 'ytrip'),
                'options' => [
                    'single_image' => __('Single Image', 'ytrip'),
                    'slider_carousel' => __('Slider / Carousel', 'ytrip'),
                ],
                'default' => 'single_image',
            ],
        ];
    }

    /**
     * Get tour gallery fields.
     *
     * @return array
     */
    private function get_tour_gallery_fields() {
        return [
            [
                'id' => 'gallery_type',
                'type' => 'button_set',
                'title' => __('Gallery Type', 'ytrip'),
                'options' => [
                    'grid' => __('Grid Gallery', 'ytrip'),
                    'slider' => __('Slider Gallery', 'ytrip'),
                    'masonry' => __('Masonry Gallery', 'ytrip'),
                ],
                'default' => 'slider',
            ],
            [
                'id' => 'gallery',
                'type' => 'gallery',
                'title' => __('Tour Gallery', 'ytrip'),
                'desc' => __('Upload or select images for the gallery.', 'ytrip'),
            ],
            [
                'id' => 'video_url',
                'type' => 'text',
                'title' => __('Video URL', 'ytrip'),
                'desc' => __('YouTube or Vimeo video URL.', 'ytrip'),
            ],
            [
                'id' => 'video_type',
                'type' => 'select',
                'title' => __('Video Display', 'ytrip'),
                'options' => [
                    'embed' => __('Embedded Video', 'ytrip'),
                    'popup' => __('Popup Video', 'ytrip'),
                    'background' => __('Background Video', 'ytrip'),
                ],
                'default' => 'popup',
                'dependency' => ['video_url', '!=', ''],
            ],
            [
                'id' => 'virtual_tour',
                'type' => 'text',
                'title' => __('360° Virtual Tour URL', 'ytrip'),
            ],
        ];
    }

    /**
     * Get tour included/excluded fields.
     *
     * @return array
     */
    private function get_tour_included_fields() {
        return [
            [
                'id' => 'included',
                'type' => 'repeater',
                'title' => __('What\'s Included', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'item',
                        'type' => 'text',
                        'title' => __('Item', 'ytrip'),
                    ],
                ],
            ],
            [
                'id' => 'excluded',
                'type' => 'repeater',
                'title' => __('What\'s Not Included', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'item',
                        'type' => 'text',
                        'title' => __('Item', 'ytrip'),
                    ],
                ],
            ],
            [
                'id' => 'what_to_bring',
                'type' => 'textarea',
                'title' => __('What to Bring', 'ytrip'),
            ],
            [
                'id' => 'know_before',
                'type' => 'wp_editor',
                'title' => __('Know Before You Go', 'ytrip'),
                'media_buttons' => false,
            ],
            [
                'id' => 'cancellation_policy',
                'type' => 'wp_editor',
                'title' => __('Cancellation Policy', 'ytrip'),
                'media_buttons' => false,
            ],
        ];
    }

    /**
     * Get tour FAQ fields.
     *
     * @return array
     */
    private function get_tour_faq_fields() {
        return [
            [
                'id' => 'faq',
                'type' => 'group',
                'title' => __('Frequently Asked Questions', 'ytrip'),
                'button_title' => __('Add New FAQ', 'ytrip'),
                'accordion_title' => __('FAQ Item', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'question',
                        'type' => 'text',
                        'title' => __('Question', 'ytrip'),
                    ],
                    [
                        'id' => 'answer',
                        'type' => 'textarea',
                        'title' => __('Answer', 'ytrip'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tour location fields.
     *
     * @return array
     */
    private function get_tour_location_fields() {
        return [
            [
                'id' => 'meeting_point',
                'type' => 'text',
                'title' => __('Meeting Point', 'ytrip'),
            ],
            [
                'id' => 'meeting_time',
                'type' => 'text',
                'title' => __('Meeting Time', 'ytrip'),
                'placeholder' => __('e.g., 8:00 AM', 'ytrip'),
            ],
            [
                'id' => 'map_lat',
                'type' => 'text',
                'title' => __('Latitude', 'ytrip'),
            ],
            [
                'id' => 'map_lng',
                'type' => 'text',
                'title' => __('Longitude', 'ytrip'),
            ],
            [
                'id' => 'map_zoom',
                'type' => 'slider',
                'title' => __('Map Zoom Level', 'ytrip'),
                'default' => '12',
                'min' => '1',
                'max' => '20',
            ],
            [
                'id' => 'tour_route',
                'type' => 'repeater',
                'title' => __('Tour Route/Stops', 'ytrip'),
                'fields' => [
                    [
                        'id' => 'stop_name',
                        'type' => 'text',
                        'title' => __('Stop Name', 'ytrip'),
                    ],
                    [
                        'id' => 'stop_description',
                        'type' => 'textarea',
                        'title' => __('Description', 'ytrip'),
                    ],
                    [
                        'id' => 'stop_duration',
                        'type' => 'text',
                        'title' => __('Duration', 'ytrip'),
                        'placeholder' => __('e.g., 2 hours', 'ytrip'),
                    ],
                    [
                        'id' => 'stop_lat',
                        'type' => 'text',
                        'title' => __('Latitude', 'ytrip'),
                    ],
                    [
                        'id' => 'stop_lng',
                        'type' => 'text',
                        'title' => __('Longitude', 'ytrip'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tour related fields.
     *
     * @return array
     */
    private function get_tour_related_fields() {
        return [
            [
                'type' => 'content',
                'content' => '<p>' . __('Configure related tours for this specific tour. Auto-related tours (by category/destination) can be enabled in Settings.', 'ytrip') . '</p>',
            ],
            [
                'id' => 'related_override',
                'type' => 'switcher',
                'title' => __('Override Auto-Related', 'ytrip'),
                'desc' => __('Enable this to manually select related tours instead of auto-generating.', 'ytrip'),
                'default' => false,
            ],
            [
                'id' => 'related_tours',
                'type' => 'select',
                'title' => __('Select Related Tours', 'ytrip'),
                'desc' => __('Choose tours to display as related.', 'ytrip'),
                'options' => 'posts',
                'query_args' => [
                    'post_type' => 'ytrip_tour',
                    'posts_per_page' => -1,
                ],
                'multiple' => true,
                'chosen' => true,
                'dependency' => ['related_override', '==', 'true'],
            ],
            [
                'id' => 'related_tour_ids',
                'type' => 'hidden',
                'default' => '',
            ],
        ];
    }

    /**
     * Create taxonomy meta.
     *
     * @return void
     */
    private function create_taxonomy_meta() {
        $settings = get_option('ytrip_settings', []);
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        // Destination meta
        CSF::createTaxonomyOptions('ytrip_destination_meta', [
            'taxonomy' => [$destination_slug],
            'data_type' => 'serialize',
        ]);

        CSF::createSection('ytrip_destination_meta', [
            'fields' => [
                [
                    'id' => 'image',
                    'type' => 'media',
                    'title' => __('Custom Image', 'ytrip'),
                    'desc' => __('Displayed on the homepage destinations section and cards. Recommended: 800×600px.', 'ytrip'),
                ],
                [
                    'id' => 'icon',
                    'type' => 'icon',
                    'title' => __('Custom Icon', 'ytrip'),
                    'desc' => __('Optional icon shown on the homepage and next to the destination name.', 'ytrip'),
                ],
                [
                    'id' => 'banner',
                    'type' => 'media',
                    'title' => __('Archive Background', 'ytrip'),
                    'desc' => __('Custom background image for this destination’s archive page. Recommended: 1920×400px.', 'ytrip'),
                ],
                [
                    'id' => 'color',
                    'type' => 'color',
                    'title' => __('Archive Color', 'ytrip'),
                    'desc' => __('Accent color for the destination archive page (header overlay, links).', 'ytrip'),
                ],
                [
                    'id' => 'featured',
                    'type' => 'switcher',
                    'title' => __('Featured Destination', 'ytrip'),
                    'default' => false,
                ],
                [
                    'id' => 'map_lat',
                    'type' => 'text',
                    'title' => __('Latitude', 'ytrip'),
                ],
                [
                    'id' => 'map_lng',
                    'type' => 'text',
                    'title' => __('Longitude', 'ytrip'),
                ],
            ],
        ]);

        // Category meta
        CSF::createTaxonomyOptions('ytrip_category_meta', [
            'taxonomy' => [$category_slug],
            'data_type' => 'serialize',
        ]);

        CSF::createSection('ytrip_category_meta', [
            'fields' => [
                [
                    'id' => 'image',
                    'type' => 'media',
                    'title' => __('Custom Image', 'ytrip'),
                    'desc' => __('Displayed on the homepage and category cards. Recommended: 800×600px.', 'ytrip'),
                ],
                [
                    'id' => 'icon',
                    'type' => 'icon',
                    'title' => __('Custom Icon', 'ytrip'),
                    'desc' => __('Optional icon shown next to the category name.', 'ytrip'),
                ],
                [
                    'id' => 'banner',
                    'type' => 'media',
                    'title' => __('Archive Background', 'ytrip'),
                    'desc' => __('Custom background image for this category’s archive page. Recommended: 1920×400px.', 'ytrip'),
                ],
                [
                    'id' => 'color',
                    'type' => 'color',
                    'title' => __('Archive Color', 'ytrip'),
                    'desc' => __('Accent color for the category archive page (header overlay, links).', 'ytrip'),
                ],
                [
                    'id' => 'featured',
                    'type' => 'switcher',
                    'title' => __('Featured Category', 'ytrip'),
                    'default' => false,
                ],
            ],
        ]);
    }

    /**
     * Get currency options.
     *
     * @return array
     */
    private function get_currency_options() {
        return [
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
            'AED' => 'UAE Dirham (AED)',
            'SAR' => 'Saudi Riyal (SAR)',
            'EGP' => 'Egyptian Pound (EGP)',
            'AUD' => 'Australian Dollar (AUD)',
            'CAD' => 'Canadian Dollar (CAD)',
            'CHF' => 'Swiss Franc (CHF)',
            'CNY' => 'Chinese Yuan (CNY)',
            'INR' => 'Indian Rupee (INR)',
            'JPY' => 'Japanese Yen (JPY)',
        ];
    }

    /**
     * Get timezone options.
     *
     * @return array
     */
    private function get_timezone_options() {
        $zones = timezone_identifiers_list();
        $options = [];
        foreach ($zones as $zone) {
            $options[$zone] = $zone;
        }
        return $options;
    }

    /**
     * Get color preset options.
     *
     * @return array
     */
    private function get_color_preset_options() {
        return [
            'ocean_adventure' => YTRIP_URL . 'assets/admin/img/presets/ocean.png',
            'tropical_paradise' => YTRIP_URL . 'assets/admin/img/presets/tropical.png',
            'desert_dunes' => YTRIP_URL . 'assets/admin/img/presets/desert.png',
            'mountain_peak' => YTRIP_URL . 'assets/admin/img/presets/mountain.png',
            'sunset_cruise' => YTRIP_URL . 'assets/admin/img/presets/sunset.png',
            'arctic_expedition' => YTRIP_URL . 'assets/admin/img/presets/arctic.png',
            'luxury_gold' => YTRIP_URL . 'assets/admin/img/presets/luxury.png',
            'royal_purple' => YTRIP_URL . 'assets/admin/img/presets/royal.png',
            'tech_blue' => YTRIP_URL . 'assets/admin/img/presets/tech.png',
            'dark_mode_pro' => YTRIP_URL . 'assets/admin/img/presets/dark.png',
            'egyptian_gold' => YTRIP_URL . 'assets/admin/img/presets/egyptian.png',
            'asian_zen' => YTRIP_URL . 'assets/admin/img/presets/asian.png',
            'spring_bloom' => YTRIP_URL . 'assets/admin/img/presets/spring.png',
            'summer_vibes' => YTRIP_URL . 'assets/admin/img/presets/summer.png',
            'autumn_harvest' => YTRIP_URL . 'assets/admin/img/presets/autumn.png',
            'winter_frost' => YTRIP_URL . 'assets/admin/img/presets/winter.png',
            'custom' => YTRIP_URL . 'assets/admin/img/presets/custom.png',
        ];
    }
}

// Initialize.
YTrip_Admin_Config::instance();