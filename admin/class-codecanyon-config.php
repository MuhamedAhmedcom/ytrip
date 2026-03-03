<?php
/**
 * YTrip Admin Configuration for CodeCanyon
 * 
 * Complete admin panel with all required features.
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
        $this->init();
    }

    /**
     * Initialize admin configuration.
     *
     * @return void
     */
    private function init() {
        if (!class_exists('CSF')) {
            return;
        }

        $this->create_main_panel();
        $this->create_settings_panel();
        $this->create_color_panel();
        $this->create_homepage_panel();
        $this->create_import_export_panel();
    }

    /**
     * Create main admin panel.
     *
     * @return void
     */
    private function create_main_panel() {
        // Main options panel
        CSF::createOptions('ytrip', [
            'menu_title' => __('YTrip', 'ytrip'),
            'menu_slug' => 'ytrip',
            'menu_icon' => 'dashicons-palmtree',
            'menu_position' => 25,
            'framework_title' => __('YTrip - Travel Booking Manager', 'ytrip'),
            'theme' => 'dark',
            'show_search' => true,
            'show_reset_all' => true,
            'show_reset_section' => true,
            'show_footer' => true,
            'show_all_options' => false,
            'sticky_header' => true,
            'footer_credit' => __('Thank you for choosing YTrip!', 'ytrip'),
            'footer_text' => sprintf(
                __('Version %s | <a href="%s" target="_blank">Documentation</a>', 'ytrip'),
                YTRIP_VERSION,
                'https://docs.ytrip.com'
            ),
        ]);

        // Welcome section
        CSF::createSection('ytrip', [
            'title' => __('Welcome', 'ytrip'),
            'icon' => 'fa fa-home',
            'fields' => [
                [
                    'type' => 'content',
                    'content' => $this->get_welcome_content(),
                ],
            ],
        ]);

        // Quick Stats
        CSF::createSection('ytrip', [
            'title' => __('Dashboard', 'ytrip'),
            'icon' => 'fa fa-tachometer',
            'fields' => $this->get_dashboard_fields(),
        ]);
    }

    /**
     * Get welcome content.
     *
     * @return string
     */
    private function get_welcome_content() {
        $tour_count = wp_count_posts('ytrip_tour')->publish;
        $destination_count = wp_count_terms(['taxonomy' => 'ytrip_destination']);
        $category_count = wp_count_terms(['taxonomy' => 'ytrip_category']);

        ob_start();
?>
        <div class="ytrip-welcome">
            <div class="ytrip-welcome-header">
                <h1><?php _e('Welcome to YTrip!', 'ytrip'); ?></h1>
                <p class="about-text"><?php _e('Professional travel booking management for WordPress.', 'ytrip'); ?></p>
            </div>

            <div class="ytrip-welcome-dashboard">
                <div class="ytrip-stat-box">
                    <span class="ytrip-stat-number"><?php echo esc_html($tour_count); ?></span>
                    <span class="ytrip-stat-label"><?php _e('Tours', 'ytrip'); ?></span>
                </div>
                <div class="ytrip-stat-box">
                    <span class="ytrip-stat-number"><?php echo esc_html($destination_count); ?></span>
                    <span class="ytrip-stat-label"><?php _e('Destinations', 'ytrip'); ?></span>
                </div>
                <div class="ytrip-stat-box">
                    <span class="ytrip-stat-number"><?php echo esc_html($category_count); ?></span>
                    <span class="ytrip-stat-label"><?php _e('Categories', 'ytrip'); ?></span>
                </div>
            </div>

            <div class="ytrip-quick-links">
                <h3><?php _e('Quick Links', 'ytrip'); ?></h3>
                <a href="<?php echo admin_url('post-new.php?post_type=ytrip_tour'); ?>" class="button button-primary">
                    <?php _e('Add New Tour', 'ytrip'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ytrip-homepage'); ?>" class="button">
                    <?php _e('Configure Homepage', 'ytrip'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ytrip-colors'); ?>" class="button">
                    <?php _e('Customize Colors', 'ytrip'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ytrip-demo'); ?>" class="button">
                    <?php _e('Import Demo', 'ytrip'); ?>
                </a>
            </div>
        </div>

        <style>
            .ytrip-welcome {
                padding: 20px;
            }

            .ytrip-welcome-header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }

            .ytrip-welcome-dashboard {
                display: flex;
                gap: 20px;
                margin: 30px 0;
            }

            .ytrip-stat-box {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                flex: 1;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .ytrip-stat-number {
                display: block;
                font-size: 36px;
                font-weight: 700;
                color: #2563eb;
            }

            .ytrip-stat-label {
                color: #6b7280;
            }

            .ytrip-quick-links {
                margin-top: 30px;
            }

            .ytrip-quick-links .button {
                margin-right: 10px;
            }
        </style>
<?php
        return ob_get_clean();
    }

    /**
     * Get dashboard fields.
     *
     * @return array
     */
    private function get_dashboard_fields() {
        return [
            [
                'type' => 'callback',
                'function' => [$this, 'render_dashboard_widget'],
            ],
        ];
    }

    /**
     * Render dashboard widget.
     *
     * @return void
     */
    public function render_dashboard_widget() {
        // Performance metrics
        $cache_stats = YTrip_Performance_Optimizer::instance()->get_query_stats();
        $import_status = YTrip_Demo_Importer::instance()->get_import_status();
?>
        <div class="ytrip-dashboard">
            <div class="ytrip-dashboard-row">
                <div class="ytrip-dashboard-col">
                    <h3><?php _e('Cache Performance', 'ytrip'); ?></h3>
                    <p>
                        <?php printf(__('Cache Hits: %d', 'ytrip'), $cache_stats['cache_hits']); ?> |
                        <?php printf(__('Misses: %d', 'ytrip'), $cache_stats['cache_misses']); ?>
                    </p>
                    <button type="button" class="button" id="ytrip-clear-cache-btn">
                        <?php _e('Clear All Cache', 'ytrip'); ?>
                    </button>
                </div>

                <div class="ytrip-dashboard-col">
                    <h3><?php _e('Demo Content', 'ytrip'); ?></h3>
                    <?php if ($import_status['installed']) : ?>
                        <p class="ytrip-status-installed"><?php _e('Demo content is installed.', 'ytrip'); ?></p>
                        <button type="button" class="button" id="ytrip-remove-demo-btn">
                            <?php _e('Remove Demo Content', 'ytrip'); ?>
                        </button>
                    <?php else : ?>
                        <p><?php _e('No demo content installed.', 'ytrip'); ?></p>
                        <button type="button" class="button button-primary" id="ytrip-import-demo-btn">
                            <?php _e('Import Demo Content', 'ytrip'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            jQuery(function($) {
                $('#ytrip-clear-cache-btn').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('<?php echo esc_js(__('Clearing...', 'ytrip')); ?>');

                    $.post(ajaxurl, {
                        action: 'ytrip_clear_cache',
                        nonce: '<?php echo esc_js(wp_create_nonce('ytrip_frontend_nonce')); ?>'
                    }, function(res) {
                        btn.prop('disabled', false).text('<?php echo esc_js(__('Clear All Cache', 'ytrip')); ?>');
                        if (res.success) {
                            alert(res.data.message);
                        }
                    });
                });

                $('#ytrip-import-demo-btn').on('click', function() {
                    if (confirm('<?php echo esc_js(__('This will import demo tours, destinations, and settings. Continue?', 'ytrip')); ?>')) {
                        var btn = $(this);
                        btn.prop('disabled', true).text('<?php echo esc_js(__('Importing...', 'ytrip')); ?>');

                        $.post(ajaxurl, {
                            action: 'ytrip_import_demo',
                            nonce: '<?php echo esc_js(wp_create_nonce('ytrip_admin_nonce')); ?>'
                        }, function(res) {
                            if (res.success) {
                                alert(res.data.message);
                                location.reload();
                            } else {
                                alert(res.data.message || 'Error');
                                btn.prop('disabled', false).text('<?php echo esc_js(__('Import Demo Content', 'ytrip')); ?>');
                            }
                        });
                    }
                });

                $('#ytrip-remove-demo-btn').on('click', function() {
                    if (confirm('<?php echo esc_js(__('This will remove all demo content. Continue?', 'ytrip')); ?>')) {
                        var btn = $(this);
                        btn.prop('disabled', true).text('<?php echo esc_js(__('Removing...', 'ytrip')); ?>');

                        $.post(ajaxurl, {
                            action: 'ytrip_remove_demo',
                            nonce: '<?php echo esc_js(wp_create_nonce('ytrip_admin_nonce')); ?>'
                        }, function(res) {
                            if (res.success) {
                                alert(res.data.message);
                                location.reload();
                            }
                        });
                    }
                });
            });
        </script>
<?php
    }

    /**
     * Create settings panel.
     *
     * @return void
     */
    private function create_settings_panel() {
        // General settings
        CSF::createSection('ytrip', [
            'title' => __('General Settings', 'ytrip'),
            'icon' => 'fa fa-cog',
            'fields' => [
                [
                    'id' => 'general_tab',
                    'type' => 'tabbed',
                    'tabs' => [
                        [
                            'title' => __('Currency', 'ytrip'),
                            'fields' => [
                                [
                                    'id' => 'currency',
                                    'type' => 'select',
                                    'title' => __('Currency', 'ytrip'),
                                    'options' => [
                                        'USD' => 'US Dollar ($)',
                                        'EUR' => 'Euro (€)',
                                        'GBP' => 'British Pound (£)',
                                        'AED' => 'UAE Dirham (د.إ)',
                                        'SAR' => 'Saudi Riyal (﷼)',
                                        'EGP' => 'Egyptian Pound (E£)',
                                    ],
                                    'default' => 'USD',
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
                                ],
                                [
                                    'id' => 'decimal_separator',
                                    'type' => 'text',
                                    'title' => __('Decimal Separator', 'ytrip'),
                                    'default' => '.',
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
                                        'F j, Y' => date('F j, Y'),
                                    ],
                                    'default' => 'Y-m-d',
                                ],
                                [
                                    'id' => 'time_format',
                                    'type' => 'select',
                                    'title' => __('Time Format', 'ytrip'),
                                    'options' => [
                                        'H:i' => '24-hour',
                                        'g:i A' => '12-hour',
                                    ],
                                    'default' => 'H:i',
                                ],
                            ],
                        ],
                        [
                            'title' => __('Booking', 'ytrip'),
                            'fields' => [
                                [
                                    'id' => 'min_advance_days',
                                    'type' => 'number',
                                    'title' => __('Minimum Advance Days', 'ytrip'),
                                    'default' => 1,
                                    'min' => 0,
                                    'max' => 365,
                                ],
                                [
                                    'id' => 'max_advance_days',
                                    'type' => 'number',
                                    'title' => __('Maximum Advance Days', 'ytrip'),
                                    'default' => 365,
                                    'min' => 1,
                                    'max' => 730,
                                ],
                                [
                                    'id' => 'require_login',
                                    'type' => 'switcher',
                                    'title' => __('Require Login to Book', 'ytrip'),
                                    'default' => false,
                                ],
                                [
                                    'id' => 'enable_coupons',
                                    'type' => 'switcher',
                                    'title' => __('Enable Coupons', 'ytrip'),
                                    'default' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create color panel.
     *
     * @return void
     */
    private function create_color_panel() {
        // Color settings panel
        CSF::createOptions('ytrip_colors', [
            'menu_title' => __('Colors & Branding', 'ytrip'),
            'menu_slug' => 'ytrip-colors',
            'menu_type' => 'submenu',
            'menu_parent' => 'ytrip',
            'framework_title' => __('Colors & Branding', 'ytrip'),
            'theme' => 'dark',
        ]);

        // Color presets
        CSF::createSection('ytrip_colors', [
            'title' => __('Color Presets', 'ytrip'),
            'icon' => 'fa fa-palette',
            'fields' => [
                [
                    'id' => 'color_preset',
                    'type' => 'image_select',
                    'title' => __('Choose Color Preset', 'ytrip'),
                    'desc' => __('Select a color scheme for your travel site.', 'ytrip'),
                    'options' => $this->get_color_preset_options(),
                    'default' => 'ocean_adventure',
                ],
                [
                    'id' => 'dark_mode',
                    'type' => 'switcher',
                    'title' => __('Enable Dark Mode Toggle', 'ytrip'),
                    'desc' => __('Allow visitors to switch between light and dark mode.', 'ytrip'),
                    'default' => false,
                ],
            ],
        ]);

        // Custom colors
        CSF::createSection('ytrip_colors', [
            'title' => __('Custom Colors', 'ytrip'),
            'icon' => 'fa fa-paint-brush',
            'fields' => [
                [
                    'type' => 'content',
                    'content' => '<p>' . __('Override preset colors with your custom colors below.', 'ytrip') . '</p>',
                ],
                [
                    'id' => 'custom_colors',
                    'type' => 'fieldset',
                    'title' => __('Custom Color Scheme', 'ytrip'),
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
                        [
                            'id' => 'success',
                            'type' => 'color',
                            'title' => __('Success Color', 'ytrip'),
                            'default' => '#10b981',
                        ],
                        [
                            'id' => 'error',
                            'type' => 'color',
                            'title' => __('Error Color', 'ytrip'),
                            'default' => '#ef4444',
                        ],
                    ],
                ],
            ],
        ]);

        // Typography
        CSF::createSection('ytrip_colors', [
            'title' => __('Typography', 'ytrip'),
            'icon' => 'fa fa-font',
            'fields' => [
                [
                    'id' => 'heading_font',
                    'type' => 'text',
                    'title' => __('Heading Font', 'ytrip'),
                    'default' => 'Outfit',
                ],
                [
                    'id' => 'body_font',
                    'type' => 'text',
                    'title' => __('Body Font', 'ytrip'),
                    'default' => 'Inter',
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
            ],
        ]);
    }

    /**
     * Get color preset options for image select.
     *
     * @return array
     */
    private function get_color_preset_options() {
        $presets = YTrip_Brand_System::instance()->get_all_presets();
        $options = [];

        foreach ($presets as $key => $preset) {
            // Generate a simple color preview URL or use placeholder
            $options[$key] = YTRIP_URL . 'assets/images/presets/' . $key . '.png';
        }

        return $options;
    }

    /**
     * Create homepage panel.
     *
     * @return void
     */
    private function create_homepage_panel() {
        // Homepage builder panel
        CSF::createOptions('ytrip_homepage', [
            'menu_title' => __('Homepage Builder', 'ytrip'),
            'menu_slug' => 'ytrip-homepage',
            'menu_type' => 'submenu',
            'menu_parent' => 'ytrip',
            'framework_title' => __('Homepage Builder', 'ytrip'),
            'theme' => 'dark',
        ]);

        // Sections manager
        CSF::createSection('ytrip_homepage', [
            'title' => __('Sections Manager', 'ytrip'),
            'icon' => 'fa fa-th-large',
            'fields' => [
                [
                    'id' => 'homepage_sections',
                    'type' => 'sorter',
                    'title' => __('Drag to Reorder Sections', 'ytrip'),
                    'desc' => __('Drag sections between Enabled and Disabled.', 'ytrip'),
                    'default' => [
                        'enabled' => [
                            'hero_slider' => __('Hero Slider', 'ytrip'),
                            'search_form' => __('Search & Filter', 'ytrip'),
                            'featured_tours' => __('Featured Tours', 'ytrip'),
                            'destinations' => __('Popular Destinations', 'ytrip'),
                            'categories' => __('Tour Categories', 'ytrip'),
                            'testimonials' => __('Customer Reviews', 'ytrip'),
                            'stats' => __('Statistics Counter', 'ytrip'),
                            'blog' => __('Latest Blog Posts', 'ytrip'),
                        ],
                        'disabled' => [
                            'video_banner' => __('Video Banner', 'ytrip'),
                            'promo_banner' => __('Promotional Banner', 'ytrip'),
                            'newsletter' => __('Newsletter Signup', 'ytrip'),
                            'countdown' => __('Countdown Timer', 'ytrip'),
                        ],
                    ],
                ],
                [
                    'id' => 'auto_render',
                    'type' => 'switcher',
                    'title' => __('Auto-Render on Homepage', 'ytrip'),
                    'desc' => __('Automatically display homepage sections on front page.', 'ytrip'),
                    'default' => true,
                ],
            ],
        ]);

        // General layout
        CSF::createSection('ytrip_homepage', [
            'title' => __('Layout Settings', 'ytrip'),
            'icon' => 'fa fa-columns',
            'fields' => [
                [
                    'id' => 'homepage_layout',
                    'type' => 'image_select',
                    'title' => __('Homepage Layout', 'ytrip'),
                    'options' => [
                        'modern' => YTRIP_URL . 'assets/images/layouts/modern.png',
                        'classic' => YTRIP_URL . 'assets/images/layouts/classic.png',
                        'search' => YTRIP_URL . 'assets/images/layouts/search.png',
                    ],
                    'default' => 'modern',
                ],
                [
                    'id' => 'homepage_width',
                    'type' => 'button_set',
                    'title' => __('Content Width', 'ytrip'),
                    'options' => [
                        'boxed' => __('Boxed (1200px)', 'ytrip'),
                        'wide' => __('Wide (1400px)', 'ytrip'),
                        'full' => __('Full Width', 'ytrip'),
                    ],
                    'default' => 'wide',
                ],
                [
                    'id' => 'section_spacing',
                    'type' => 'slider',
                    'title' => __('Section Spacing', 'ytrip'),
                    'default' => '80',
                    'min' => '40',
                    'max' => '120',
                    'step' => '10',
                    'unit' => 'px',
                ],
            ],
        ]);

        // Hero section
        CSF::createSection('ytrip_homepage', [
            'title' => __('Hero Section', 'ytrip'),
            'icon' => 'fa fa-image',
            'fields' => [
                [
                    'id' => 'hero_enable',
                    'type' => 'switcher',
                    'title' => __('Enable Hero Section', 'ytrip'),
                    'default' => true,
                ],
                [
                    'id' => 'hero_style',
                    'type' => 'button_set',
                    'title' => __('Hero Style', 'ytrip'),
                    'options' => [
                        'slider' => __('Image Slider', 'ytrip'),
                        'video' => __('Video Background', 'ytrip'),
                        'static' => __('Static Image', 'ytrip'),
                    ],
                    'default' => 'slider',
                    'dependency' => ['hero_enable', '==', 'true'],
                ],
                [
                    'id' => 'hero_slides',
                    'type' => 'group',
                    'title' => __('Hero Slides', 'ytrip'),
                    'dependency' => ['hero_style', '==', 'slider'],
                    'fields' => [
                        [
                            'id' => 'image',
                            'type' => 'media',
                            'title' => __('Background Image', 'ytrip'),
                        ],
                        [
                            'id' => 'title',
                            'type' => 'text',
                            'title' => __('Title', 'ytrip'),
                        ],
                        [
                            'id' => 'subtitle',
                            'type' => 'textarea',
                            'title' => __('Subtitle', 'ytrip'),
                        ],
                    ],
                ],
            ],
        ]);

        // Featured Tours
        CSF::createSection('ytrip_homepage', [
            'title' => __('Featured Tours', 'ytrip'),
            'icon' => 'fa fa-star',
            'fields' => [
                [
                    'id' => 'featured_enable',
                    'type' => 'switcher',
                    'title' => __('Enable Featured Tours', 'ytrip'),
                    'default' => true,
                ],
                [
                    'id' => 'featured_section_title',
                    'type' => 'text',
                    'title' => __('Section Title', 'ytrip'),
                    'default' => 'Featured Tours',
                    'dependency' => ['featured_enable', '==', 'true'],
                ],
                [
                    'id' => 'featured_section_subtitle',
                    'type' => 'text',
                    'title' => __('Section Subtitle', 'ytrip'),
                    'default' => 'Discover our most popular travel experiences',
                    'dependency' => ['featured_enable', '==', 'true'],
                ],
                [
                    'id' => 'featured_count',
                    'type' => 'number',
                    'title' => __('Number of Tours', 'ytrip'),
                    'default' => 6,
                    'min' => 1,
                    'max' => 24,
                    'dependency' => ['featured_enable', '==', 'true'],
                ],
                [
                    'id' => 'card_style',
                    'type' => 'image_select',
                    'title' => __('Card Style', 'ytrip'),
                    'options' => [
                        'classic' => YTRIP_URL . 'assets/images/cards/classic.png',
                        'modern' => YTRIP_URL . 'assets/images/cards/modern.png',
                        'overlay' => YTRIP_URL . 'assets/images/cards/overlay.png',
                        'glass' => YTRIP_URL . 'assets/images/cards/glass.png',
                    ],
                    'default' => 'modern',
                    'dependency' => ['featured_enable', '==', 'true'],
                ],
            ],
        ]);
    }

    /**
     * Create import/export panel.
     *
     * @return void
     */
    private function create_import_export_panel() {
        // Demo importer
        CSF::createSection('ytrip', [
            'title' => __('Demo Import', 'ytrip'),
            'icon' => 'fa fa-download',
            'fields' => [
                [
                    'type' => 'content',
                    'content' => $this->get_demo_import_content(),
                ],
            ],
        ]);

        // Backup/Restore
        CSF::createSection('ytrip', [
            'title' => __('Backup & Restore', 'ytrip'),
            'icon' => 'fa fa-database',
            'fields' => [
                [
                    'id' => 'backup',
                    'type' => 'backup',
                    'title' => __('Settings Backup', 'ytrip'),
                ],
            ],
        ]);
    }

    /**
     * Get demo import content.
     *
     * @return string
     */
    private function get_demo_import_content() {
        $import_status = YTrip_Demo_Importer::instance()->get_import_status();
        $tours_count   = ( isset( $import_status['tours'] ) && ! is_wp_error( $import_status['tours'] ) && is_numeric( $import_status['tours'] ) ) ? (int) $import_status['tours'] : 0;
        $dests_count   = ( isset( $import_status['destinations'] ) && ! is_wp_error( $import_status['destinations'] ) && is_numeric( $import_status['destinations'] ) ) ? (int) $import_status['destinations'] : 0;
        $cats_count    = ( isset( $import_status['categories'] ) && ! is_wp_error( $import_status['categories'] ) && is_numeric( $import_status['categories'] ) ) ? (int) $import_status['categories'] : 0;
?>
        <div class="ytrip-demo-importer">
            <h2><?php _e('One-Click Demo Import', 'ytrip'); ?></h2>
            <p><?php _e('Import demo content to quickly set up your travel site with sample tours, destinations, and settings.', 'ytrip'); ?></p>

            <div class="ytrip-demo-preview">
                <h3><?php _e('Demo Content Includes:', 'ytrip'); ?></h3>
                <ul>
                    <li><?php printf( _n( '%d Tour', '%d Tours', $tours_count, 'ytrip' ), $tours_count ); ?></li>
                    <li><?php printf( _n( '%d Destination', '%d Destinations', $dests_count, 'ytrip' ), $dests_count ); ?></li>
                    <li><?php printf( _n( '%d Category', '%d Categories', $cats_count, 'ytrip' ), $cats_count ); ?></li>
                    <li><?php _e('Sample testimonials', 'ytrip'); ?></li>
                    <li><?php _e('Recommended color preset', 'ytrip'); ?></li>
                    <li><?php _e('Configured homepage sections', 'ytrip'); ?></li>
                </ul>
            </div>

            <div class="ytrip-demo-actions">
                <?php if ($import_status['installed']) : ?>
                    <p class="ytrip-status"><?php _e('Demo content is currently installed.', 'ytrip'); ?></p>
                    <button type="button" class="button button-secondary" id="ytrip-remove-demo">
                        <?php _e('Remove Demo Content', 'ytrip'); ?>
                    </button>
                <?php else : ?>
                    <button type="button" class="button button-primary button-hero" id="ytrip-import-demo">
                        <?php _e('Import Demo Content', 'ytrip'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div id="ytrip-import-progress" style="display: none;">
                <div class="ytrip-progress-bar">
                    <div class="ytrip-progress-fill"></div>
                </div>
                <p class="ytrip-progress-text"><?php _e('Importing...', 'ytrip'); ?></p>
            </div>
        </div>

        <style>
            .ytrip-demo-importer {
                max-width: 600px;
                padding: 20px;
            }

            .ytrip-demo-preview {
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }

            .ytrip-demo-preview ul {
                margin: 10px 0 0 20px;
            }

            .ytrip-demo-preview li {
                margin: 5px 0;
            }

            .ytrip-progress-bar {
                width: 100%;
                height: 10px;
                background: #e5e7eb;
                border-radius: 5px;
                overflow: hidden;
                margin: 20px 0;
            }

            .ytrip-progress-fill {
                width: 0%;
                height: 100%;
                background: #2563eb;
                transition: width 0.3s ease;
            }
        </style>
<?php
        return ob_get_clean();
    }
}

// Initialize
YTrip_Admin_Config::instance();
