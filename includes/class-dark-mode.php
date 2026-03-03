<?php
/**
 * YTrip Dark Mode Handler
 * 
 * Properly implements dark mode with CSS variables
 *
 * @package YTrip
 * @since 2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class YTrip_Dark_Mode {

    /**
     * Singleton instance.
     */
    private static $instance = null;

    /**
     * Get instance.
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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_ytrip_toggle_theme', [$this, 'ajax_toggle_theme']);
        add_action('wp_ajax_nopriv_ytrip_toggle_theme', [$this, 'ajax_toggle_theme']);
        add_action('wp_head', [$this, 'output_css_variables'], 1);
        add_action('wp_body_open', [$this, 'output_dark_mode_script'], 1);
    }

    /**
     * Enqueue assets (only on YTrip pages).
     */
    public function enqueue_assets() {
        if ( ! function_exists( 'ytrip_is_plugin_page' ) || ! ytrip_is_plugin_page() ) {
            return;
        }
        wp_register_script(
            'ytrip-dark-mode',
            '',
            array(),
            YTRIP_VERSION,
            true
        );
        $script = $this->get_dark_mode_script();
        wp_add_inline_script( 'ytrip-dark-mode', $script );
        wp_enqueue_script( 'ytrip-dark-mode' );
    }

    /**
     * Output CSS variables in head (only on YTrip pages).
     */
    public function output_css_variables() {
        if ( ! function_exists( 'ytrip_is_plugin_page' ) || ! ytrip_is_plugin_page() ) {
            return;
        }
        $settings = get_option( 'ytrip_settings', array() );
        $dark_mode_enabled = $settings['dark_mode'] ?? false;
        if ( ! $dark_mode_enabled ) {
            return;
        }

        $custom_colors = $settings['custom_colors'] ?? [];
        $dark_colors = $settings['dark_colors'] ?? [];

        $primary = $custom_colors['primary'] ?? '#2563eb';
        $secondary = $custom_colors['secondary'] ?? '#7c3aed';
        $accent = $custom_colors['accent'] ?? '#f59e0b';

        // Light mode colors
        $light_bg = $custom_colors['background'] ?? '#ffffff';
        $light_surface = $custom_colors['surface'] ?? '#ffffff';
        $light_text = $custom_colors['text_primary'] ?? '#1e293b';

        // Dark mode colors
        $dark_bg = $dark_colors['dark_background'] ?? '#0f172a';
        $dark_surface = $dark_colors['dark_surface'] ?? '#1e293b';
        $dark_text = $dark_colors['dark_text_primary'] ?? '#f1f5f9';
        ?>
        <style id="ytrip-color-variables">
            :root {
                /* Brand */
                --ytrip-primary: <?php echo esc_attr($primary); ?>;
                --ytrip-primary-rgb: <?php echo esc_attr(implode(',', $this->hex_to_rgb($primary))); ?>;
                --ytrip-secondary: <?php echo esc_attr($secondary); ?>;
                --ytrip-accent: <?php echo esc_attr($accent); ?>;
                
                /* Semantic */
                --ytrip-success: #10b981;
                --ytrip-warning: #f59e0b;
                --ytrip-error: #ef4444;
                --ytrip-info: #3b82f6;
                
                /* Light Mode (Default) */
                --ytrip-bg: <?php echo esc_attr($light_bg); ?>;
                --ytrip-surface: <?php echo esc_attr($light_surface); ?>;
                --ytrip-surface-alt: #f8fafc;
                --ytrip-border: #e5e7eb;
                --ytrip-text: <?php echo esc_attr($light_text); ?>;
                --ytrip-text-muted: #64748b;
                --ytrip-text-light: #94a3b8;
            }

            /* Dark Mode */
            .ytrip-dark-mode,
            [data-theme="dark"] {
                --ytrip-bg: <?php echo esc_attr($dark_bg); ?>;
                --ytrip-surface: <?php echo esc_attr($dark_surface); ?>;
                --ytrip-surface-alt: #334155;
                --ytrip-border: #475569;
                --ytrip-text: <?php echo esc_attr($dark_text); ?>;
                --ytrip-text-muted: #94a3b8;
                --ytrip-text-light: #64748b;
            }

            /* Body and global wrappers – site-wide dark mode */
            .ytrip-dark-mode body,
            [data-theme="dark"] body,
            body.ytrip-dark-mode {
                background-color: var(--ytrip-bg);
                color: var(--ytrip-text);
            }
            .ytrip-dark-mode .site,
            .ytrip-dark-mode #page,
            .ytrip-dark-mode .site-content,
            .ytrip-dark-mode #content,
            .ytrip-dark-mode main,
            .ytrip-dark-mode .content-area,
            .ytrip-dark-mode .wp-site-blocks,
            .ytrip-dark-mode .entry-content,
            .ytrip-dark-mode .page-content,
            .ytrip-dark-mode .entry,
            .ytrip-dark-mode .post,
            [data-theme="dark"] .site,
            [data-theme="dark"] #page,
            [data-theme="dark"] .site-content,
            [data-theme="dark"] #content,
            [data-theme="dark"] main,
            [data-theme="dark"] .content-area,
            [data-theme="dark"] .wp-site-blocks,
            [data-theme="dark"] .entry-content,
            [data-theme="dark"] .page-content,
            [data-theme="dark"] .entry,
            [data-theme="dark"] .post {
                background-color: var(--ytrip-bg);
                color: var(--ytrip-text);
            }

            /* Dark mode specific overrides */
            .ytrip-dark-mode .ytrip-section,
            [data-theme="dark"] .ytrip-section {
                background-color: var(--ytrip-bg);
                color: var(--ytrip-text);
            }

            .ytrip-dark-mode .ytrip-tour-card,
            [data-theme="dark"] .ytrip-tour-card {
                background: var(--ytrip-surface);
                border-color: var(--ytrip-border);
            }

            .ytrip-dark-mode .ytrip-sidebar-card,
            [data-theme="dark"] .ytrip-sidebar-card {
                background: var(--ytrip-surface);
                border-color: var(--ytrip-border);
            }

            .ytrip-dark-mode .ytrip-faq-question,
            [data-theme="dark"] .ytrip-faq-question {
                color: var(--ytrip-text);
            }

            .ytrip-dark-mode .ytrip-day-content,
            [data-theme="dark"] .ytrip-day-content {
                background: var(--ytrip-surface);
                border-color: var(--ytrip-border);
            }

            .ytrip-dark-mode .ytrip-tour-info-item,
            [data-theme="dark"] .ytrip-tour-info-item {
                background: var(--ytrip-surface-alt);
                border-color: var(--ytrip-border);
            }
            .ytrip-dark-mode .ytrip-tour-info-value,
            [data-theme="dark"] .ytrip-tour-info-value {
                color: var(--ytrip-text);
            }

            /* Dark mode input styling */
            .ytrip-dark-mode .ytrip-form-group input,
            .ytrip-dark-mode .ytrip-form-group select,
            .ytrip-dark-mode .ytrip-number-input,
            [data-theme="dark"] .ytrip-form-group input,
            [data-theme="dark"] .ytrip-form-group select,
            [data-theme="dark"] .ytrip-number-input {
                background: var(--ytrip-surface-alt);
                border-color: var(--ytrip-border);
                color: var(--ytrip-text);
            }

            .ytrip-dark-mode .ytrip-number-btn,
            [data-theme="dark"] .ytrip-number-btn {
                background: var(--ytrip-surface-alt);
                color: var(--ytrip-text);
            }
        </style>
        <?php
    }

    /**
     * Output dark mode detection script (only on YTrip pages).
     */
    public function output_dark_mode_script() {
        if ( ! function_exists( 'ytrip_is_plugin_page' ) || ! ytrip_is_plugin_page() ) {
            return;
        }
        $settings = get_option( 'ytrip_settings', array() );
        $dark_mode_enabled = $settings['dark_mode'] ?? false;
        if ( ! $dark_mode_enabled ) {
            return;
        }
        ?>
        <script id="ytrip-dark-mode-init">
            (function() {
                // Check saved preference first
                var savedTheme = localStorage.getItem('ytrip_theme');
                
                function applyTheme(isDark) {
                    document.documentElement.classList.toggle('ytrip-dark-mode', isDark);
                    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
                    document.body.classList.toggle('ytrip-dark-mode', isDark);
                }
                if (savedTheme) {
                    applyTheme(savedTheme === 'dark');
                } else {
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    applyTheme(prefersDark);
                }
            })();
        </script>
        <?php
    }

    /**
     * Get dark mode script.
     */
    private function get_dark_mode_script() {
        return <<<'JS'
(function() {
    'use strict';

    // Dark Mode Toggle
    window.ytripToggleDarkMode = function() {
        var html = document.documentElement;
        var isDark = html.classList.contains('ytrip-dark-mode');
        var newTheme = isDark ? 'light' : 'dark';

        html.classList.toggle('ytrip-dark-mode', !isDark);
        html.setAttribute('data-theme', newTheme);
        document.body.classList.toggle('ytrip-dark-mode', !isDark);
        localStorage.setItem('ytrip_theme', newTheme);

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent('ytripThemeChanged', {
            detail: { theme: newTheme }
        }));

        return newTheme;
    };

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('ytrip_theme')) {
            var isDark = e.matches;
            document.documentElement.classList.toggle('ytrip-dark-mode', isDark);
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            document.body.classList.toggle('ytrip-dark-mode', isDark);
        }
    });

    // Dark mode toggle button handler
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.ytrip-dark-mode-toggle');
        if (btn) {
            e.preventDefault();
            ytripToggleDarkMode();
            
            // Update button icon if present
            var icon = btn.querySelector('svg');
            if (icon) {
                btn.classList.toggle('is-dark', document.documentElement.classList.contains('ytrip-dark-mode'));
            }
        }
    });

})();
JS;
    }

    /**
     * AJAX toggle theme.
     */
    public function ajax_toggle_theme() {
        check_ajax_referer('ytrip_public_nonce', 'nonce');

        $current = isset($_COOKIE['ytrip_theme']) ? sanitize_text_field(wp_unslash($_COOKIE['ytrip_theme'])) : 'light';
        $new = $current === 'dark' ? 'light' : 'dark';

        // Set cookie
        setcookie('ytrip_theme', $new, time() + (365 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

        wp_send_json_success([
            'theme' => $new,
        ]);
    }

    /**
     * Convert hex to RGB.
     */
    private function hex_to_rgb(string $hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [$r, $g, $b];
    }
}

// Initialize
YTrip_Dark_Mode::instance();
