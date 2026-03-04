<?php
/**
 * Template Loader
 * Smart asset loading for YTrip plugin v2.1
 * Only loads CSS/JS on pages that need them for best performance
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class YTrip_Template_Loader {

    private $options;
    private $is_ytrip_page = false;

    public function __construct() {
        $this->options = get_option( 'ytrip_settings' );
        // NOTE: Template routing is handled by YTrip_Plugin::template_loader() at priority 99.
        // This class focuses on smart asset enqueuing only.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 5 );
    }

    /**
     * Check if current page is a YTrip page (uses shared helper for consistency).
     */
    private function is_ytrip_context() {
        return function_exists( 'ytrip_is_plugin_page' ) && ytrip_is_plugin_page();
    }

    /**
     * Template routing removed — handled by YTrip_Plugin::template_loader() in ytrip.php.
     * Kept locate_template() and get_template_part() for theme-override support.
     */

    /**
     * Locate a template file in theme or plugin.
     *
     * @param string $template_name Template name relative to templates folder.
     * @return string|false Template path or false if not found.
     */
    public function locate_template( string $template_name ) {
        // Look in theme/ytrip/ first
        $theme_file = get_stylesheet_directory() . '/ytrip/' . $template_name;
        if ( file_exists( $theme_file ) ) {
            return $theme_file;
        }

        // Look in parent theme/ytrip/ if using child theme
        $parent_file = get_template_directory() . '/ytrip/' . $template_name;
        if ( $theme_file !== $parent_file && file_exists( $parent_file ) ) {
            return $parent_file;
        }

        return false;
    }

    /**
     * Get template part with theme override support.
     *
     * @param string $slug Template slug.
     * @param string $name Optional template name.
     * @return void
     */
    public static function get_template_part( string $slug, string $name = '' ) {
        $template = '';

        // Look for slug-name.php
        if ( $name ) {
            $template_name = "{$slug}-{$name}.php";
        } else {
            $template_name = "{$slug}.php";
        }

        // Check theme first
        $theme_file = get_stylesheet_directory() . '/ytrip/' . $template_name;
        if ( file_exists( $theme_file ) ) {
            $template = $theme_file;
        } elseif ( get_stylesheet_directory() !== get_template_directory() ) {
            // Check parent theme
            $parent_file = get_template_directory() . '/ytrip/' . $template_name;
            if ( file_exists( $parent_file ) ) {
                $template = $parent_file;
            }
        }

        // Fall back to plugin
        if ( ! $template ) {
            $plugin_file = YTRIP_PATH . 'templates/' . $template_name;
            if ( file_exists( $plugin_file ) ) {
                $template = $plugin_file;
            }
        }

        // Allow filtering
        $template = apply_filters( 'ytrip_get_template_part', $template, $slug, $name );

        if ( $template ) {
            load_template( $template, false );
        }
    }

    /**
     * Smart asset enqueuing - only load what's needed
     */
    public function enqueue_assets() {
        // Early exit if not a YTrip page
        $this->is_ytrip_page = $this->is_ytrip_context();
        
        if ( ! $this->is_ytrip_page ) {
            return; // Don't load any YTrip assets on non-YTrip pages
        }

        // === CORE ASSETS (only on YTrip pages) ===
        $this->enqueue_core_assets();

        // === PAGE-SPECIFIC ASSETS ===
        
        // Single Tour Page
        if ( is_singular( 'ytrip_tour' ) ) {
            $this->enqueue_single_tour_assets();
        }
        
        // Archive/Taxonomy Pages
        if ( is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' ) ) {
            $this->enqueue_archive_assets();
        }
        
        // Homepage
        if ( is_front_page() ) {
            $this->enqueue_homepage_assets();
        }

        // === OPTIONAL FEATURE ASSETS (based on settings) ===
        $this->enqueue_optional_features();

        // === LOCALIZE SCRIPT (only if main JS loaded) ===
        $this->localize_scripts();

        // === DYNAMIC CSS VARIABLES ===
        $this->add_dynamic_css();
    }

    /**
     * Core assets - minimal CSS/JS for YTrip functionality
     */
    private function enqueue_core_assets() {
        // Fonts: not loaded by plugin — theme fonts are used (see add_dynamic_css for --ytrip-font-*).

        // Main CSS - core styles
        wp_enqueue_style( 
            'ytrip-main', 
            YTRIP_URL . 'assets/css/main.css', 
            array(), 
            YTRIP_VERSION 
        );

        // Main JS - core functionality
        wp_enqueue_script( 
            'ytrip-main', 
            YTRIP_URL . 'assets/js/main.js', 
            array( 'jquery' ), 
            YTRIP_VERSION, 
            true // Load in footer
        );
    }

    /**
     * Single tour page assets
     */
    private function enqueue_single_tour_assets() {
        $layout = $this->options['single_tour_layout'] ?? 'layout_1';

        if ( ! empty( $this->options['single_tabs_show_icons'] ) ) {
            wp_enqueue_style( 'dashicons' );
        }

        $layout_css_map = array(
            'layout_1' => 'single-layout-1.css',
            'layout_2' => 'single-layout-2.css',
            'layout_3' => 'single-layout-3.css',
            'layout_4' => 'single-layout-4.css',
            'layout_5' => 'single-layout-5.css',
        );

        if ( isset( $layout_css_map[$layout] ) ) {
            wp_enqueue_style( 
                'ytrip-layout-' . $layout, 
                YTRIP_URL . 'assets/css/layouts/' . $layout_css_map[$layout], 
                array( 'ytrip-main' ), 
                YTRIP_VERSION 
            );
        }

        wp_enqueue_style(
            'ytrip-reviews',
            YTRIP_URL . 'assets/css/reviews.css',
            array( 'ytrip-main' ),
            YTRIP_VERSION
        );

        // Single tour JS
        wp_enqueue_script( 
            'ytrip-single-tour', 
            YTRIP_URL . 'assets/js/single-tour.js', 
            array( 'jquery', 'ytrip-main' ), 
            YTRIP_VERSION, 
            true 
        );

        // Reviews JS
        wp_enqueue_script( 
            'ytrip-reviews', 
            YTRIP_URL . 'assets/js/reviews.js', 
            array( 'jquery', 'ytrip-main' ), 
            YTRIP_VERSION, 
            true 
        );

        // Swiper for hero slider
        wp_enqueue_style( 
            'swiper-bundle', 
            YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.css', 
            array(), 
            '11.1.14' 
        );
        wp_enqueue_script( 
            'swiper-bundle', 
            YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.js', 
            array(), 
            '11.1.14', 
            true 
        );
        wp_enqueue_script( 
            'ytrip-hero-slider', 
            YTRIP_URL . 'assets/js/hero-slider.js', 
            array( 'swiper-bundle' ), 
            YTRIP_VERSION, 
            true 
        );
    }

    /**
     * Archive page assets.
     * Prefer single archive-bundle.css when present to reduce requests (1 CSS instead of archive-filters + card-styles).
     */
    private function enqueue_archive_assets() {
        $bundle_css = YTRIP_PATH . 'assets/css/archive-bundle.css';
        if ( file_exists( $bundle_css ) ) {
            wp_enqueue_style(
                'ytrip-archive-bundle',
                YTRIP_URL . 'assets/css/archive-bundle.css',
                array( 'ytrip-main' ),
                (string) filemtime( $bundle_css )
            );
        } else {
            wp_enqueue_style( 'ytrip-archive-filters', YTRIP_URL . 'assets/css/archive-filters.css', array( 'ytrip-main' ), YTRIP_VERSION );
        wp_enqueue_style(
            'ytrip-footer',
            YTRIP_URL . 'assets/css/footer.css',
            array( 'ytrip-main' ),
            YTRIP_VERSION
        );
            wp_enqueue_style( 'ytrip-cards', YTRIP_URL . 'assets/css/cards/card-styles.css', array( 'ytrip-main' ), YTRIP_VERSION );
        wp_enqueue_style(
            'ytrip-footer',
            YTRIP_URL . 'assets/css/footer.css',
            array( 'ytrip-main' ),
            YTRIP_VERSION
        );
        }

        $ajax_enabled = $this->options['archive_enable_ajax'] ?? true;
        if ( $ajax_enabled ) {
            wp_enqueue_script( 'ytrip-archive-filters', YTRIP_URL . 'assets/js/archive-filters.js', array( 'ytrip-main' ), YTRIP_VERSION, true );
        }
    }

    /**
     * Homepage assets
     */
    private function enqueue_homepage_assets() {
        // Card styles (needed for tour cards on homepage)
        wp_enqueue_style( 
            'ytrip-cards', 
            YTRIP_URL . 'assets/css/cards/card-styles.css', 
            array( 'ytrip-main' ), 
            YTRIP_VERSION 
        );
        // Shared calendar (homepage search form uses same component as booking form)
        if ( file_exists( YTRIP_PATH . 'assets/js/ytrip-calendar.js' ) ) {
            wp_enqueue_script( 
                'ytrip-calendar', 
                YTRIP_URL . 'assets/js/ytrip-calendar.js', 
                array( 'ytrip-main' ), 
                YTRIP_VERSION, 
                true 
            );
        }
        // Location dropdown (custom destination select on homepage search)
        if ( file_exists( YTRIP_PATH . 'assets/js/ytrip-search-form.js' ) ) {
            wp_enqueue_script( 
                'ytrip-search-form', 
                YTRIP_URL . 'assets/js/ytrip-search-form.js', 
                array( 'ytrip-main' ), 
                YTRIP_VERSION, 
                true 
            );

        // Swiper for hero slider
        wp_enqueue_style( 
            'swiper-bundle', 
            YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.css', 
            array(), 
            '11.1.14' 
        );
        wp_enqueue_script( 
            'swiper-bundle', 
            YTRIP_URL . 'assets/vendor/swiper/swiper-bundle.min.js', 
            array(), 
            '11.1.14', 
            true 
        );
        wp_enqueue_script( 
            'ytrip-hero-slider', 
            YTRIP_URL . 'assets/js/hero-slider.js', 
            array( 'swiper-bundle' ), 
            YTRIP_VERSION, 
            true 
        );
        }
    }

    /**
     * Optional feature assets - only load if feature is enabled
     */
    private function enqueue_optional_features() {
        // Animations JS (scroll-triggered animations)
        $animations_enabled = $this->options['enable_animations'] ?? false;
        if ( $animations_enabled ) {
            wp_enqueue_script( 
                'ytrip-animations', 
                YTRIP_URL . 'assets/js/animations.js', 
                array(), // No jQuery dependency for better performance
                YTRIP_VERSION, 
                true 
            );
        }

        // Parallax JS (hero parallax effects)
        $parallax_enabled = $this->options['enable_parallax'] ?? false;
        if ( $parallax_enabled ) {
            wp_enqueue_script( 
                'ytrip-parallax', 
                YTRIP_URL . 'assets/js/parallax.js', 
                array(), 
                YTRIP_VERSION, 
                true 
            );
        }

        // Microinteractions JS - only on pages with interactive elements
        $microinteractions_enabled = $this->options['enable_microinteractions'] ?? true;
        if ( $microinteractions_enabled && ( is_singular( 'ytrip_tour' ) || is_front_page() ) ) {
            wp_enqueue_script( 
                'ytrip-microinteractions', 
                YTRIP_URL . 'assets/js/microinteractions.js', 
                array(), 
                YTRIP_VERSION, 
                true 
            );
        }
    }

    /**
     * Localize scripts with data
     */
    private function localize_scripts() {
        $data = array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'ytrip_filter_nonce' ),
            'archive_url' => get_post_type_archive_link( 'ytrip_tour' ),
        );

        // Only add feature flags if features are enabled
        if ( ! empty( $this->options['enable_animations'] ) ) {
            $data['enable_animations'] = '1';
        }
        if ( ! empty( $this->options['enable_parallax'] ) ) {
            $data['enable_parallax'] = '1';
        }
        if ( ! empty( $this->options['card_hover_effect'] ) ) {
            $data['card_hover_effect'] = $this->options['card_hover_effect'];
        }

        // Skeleton Loading
        if ( ! empty( $this->options['enable_skeleton_loading'] ) ) {
            $data['enable_skeleton'] = '1';
            $card_style = function_exists( 'ytrip_get_card_style' ) ? ytrip_get_card_style() : ( $this->options['tour_card_style'] ?? 'style_3' );
            ob_start();
            // Simple skeleton structure matching the CSS
            ?>
            <div class="ytrip-card ytrip-skeleton-card ytrip-card--<?php echo esc_attr( str_replace('style_', 'style-', $card_style) ); ?>">
                <div class="ytrip-skeleton-image"></div>
                <div class="ytrip-skeleton-content">
                    <div class="ytrip-skeleton-text ytrip-skeleton-tag"></div>
                    <div class="ytrip-skeleton-text ytrip-skeleton-title"></div>
                    <div class="ytrip-skeleton-text ytrip-skeleton-meta"></div>
                    <div class="ytrip-skeleton-footer">
                        <div class="ytrip-skeleton-text ytrip-skeleton-price"></div>
                        <div class="ytrip-skeleton-btn"></div>
                    </div>
                </div>
            </div>
            <?php
            $skeleton_html = ob_get_clean();
            $data['skeleton_html'] = $skeleton_html;
        }

        wp_localize_script( 'ytrip-main', 'ytrip_vars', $data );
    }

    /**
     * Generate minimal dynamic CSS based on settings
     */
    private function add_dynamic_css() {
        $primary     = $this->options['opt_color_primary'] ?? '#2563eb';
        $secondary   = $this->options['opt_color_secondary'] ?? '#1e40af';
        $accent      = $this->options['opt_color_accent'] ?? '#f59e0b';
        $radius_btn  = $this->options['opt_border_radius_btn'] ?? '6px';
        $radius_card = $this->options['opt_border_radius_card'] ?? '12px';

        $hex = ltrim( $primary, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $primary_rgb_str = '37,99,235';
        $primary_contrast = '#ffffff';
        if ( strlen( $hex ) === 6 && preg_match( '/^[a-fA-F0-9]{6}$/', $hex ) ) {
            $primary_rgb_str = hexdec( substr( $hex, 0, 2 ) ) . ',' . hexdec( substr( $hex, 2, 2 ) ) . ',' . hexdec( substr( $hex, 4, 2 ) );
            $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
            $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
            $b = hexdec( substr( $hex, 4, 2 ) ) / 255;
            $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
            $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
            $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );
            $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
            $primary_contrast = $luminance <= 0.183 ? '#ffffff' : '#111827';
        }

        // Fonts inherit from theme; no plugin font loading.
        $css = ":root{--ytrip-primary:{$primary};--ytrip-primary-dark:{$secondary};--ytrip-primary-rgb:{$primary_rgb_str};--ytrip-primary-contrast:{$primary_contrast};--ytrip-accent:{$accent};--ytrip-radius-btn:{$radius_btn};--ytrip-radius-card:{$radius_card};--ytrip-font-heading:inherit;--ytrip-font-body:inherit;--ytrip-font:inherit;--ytrip-font-display:inherit}";

        // Smooth scroll (optional)
        if ( ! empty( $this->options['enable_smooth_scroll'] ) ) {
            $css .= "html{scroll-behavior:smooth}";
        }

        wp_add_inline_style( 'ytrip-main', $css );
    }
}

new YTrip_Template_Loader();
