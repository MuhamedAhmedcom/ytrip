<?php
/**
 * YTrip Enhanced Brand Color System
 * 
 * Professional brand color management with:
 * - 20 color presets
 * - Dark mode support
 * - Automatic contrast calculation
 * - Color scale generation (50-900)
 * - WCAG accessibility compliance
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Brand_System
 * 
 * Handles all brand color operations.
 */
class YTrip_Brand_System {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Plugin options.
     *
     * @var array
     */
    private $options = [];

    /**
     * Current active preset.
     *
     * @var string
     */
    private $active_preset = 'ocean_adventure';

    /**
     * Dark mode enabled flag.
     *
     * @var bool
     */
    private $dark_mode = false;

    /**
     * Color presets.
     *
     * @var array
     */
    private $presets = [];

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
        $this->options = get_option('ytrip_settings', []);
        $this->presets = $this->get_all_presets();
        $this->active_preset = $this->options['color_preset'] ?? 'ocean_adventure';
        $this->dark_mode = !empty($this->options['dark_mode']);

        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_variables'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_variables'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dark_mode_assets'], 999);
        add_filter('body_class', [$this, 'add_body_classes']);
        add_action('wp_ajax_ytrip_toggle_dark_mode', [$this, 'ajax_toggle_dark_mode']);
        add_action('wp_ajax_nopriv_ytrip_toggle_dark_mode', [$this, 'ajax_toggle_dark_mode']);
    }

    /**
     * Enqueue frontend CSS variables (only on YTrip pages; attaches to ytrip-main).
     */
    public function enqueue_frontend_variables() {
        if ( ! function_exists( 'ytrip_is_plugin_page' ) || ! ytrip_is_plugin_page() ) {
            return;
        }
        if ( ! wp_style_is( 'ytrip-main', 'enqueued' ) ) {
            return;
        }
        $css = $this->generate_css_variables();
        wp_add_inline_style( 'ytrip-main', $css );
    }

    /**
     * Enqueue admin CSS variables.
     */
    public function enqueue_admin_variables() {
        if (!$this->is_ytrip_admin_page()) {
            return;
        }
        $css = $this->generate_css_variables();
        // Hook into admin styles if available, or print in head as fallback if no suitable handle
        // Generally admin styles might not have a global 'ytrip-admin' handle unless registered.
        // For safety in admin, we can keep using head output or find a handle.
        // Let's use wp_add_inline_style if a handle exists, else echo.
        // Looking at admin-config, it uses CSF.
        echo '<style id="ytrip-brand-colors-admin">' . wp_strip_all_tags($css) . '</style>';
    }

    /**
     * Get all color presets (20 presets).
     *
     * @return array
     */
    public function get_all_presets() {
        return [
            // Travel & Adventure
            'ocean_adventure' => [
                'name' => __('Ocean Adventure', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#0077b6',
                    'secondary' => '#00b4d8',
                    'accent' => '#fca311',
                    'success' => '#2ec4b6',
                    'warning' => '#ff9f1c',
                    'error' => '#e63946',
                    'info' => '#4895ef',
                ],
                'description' => 'Deep ocean blues with warm sunset accents',
            ],
            'tropical_paradise' => [
                'name' => __('Tropical Paradise', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#06d6a0',
                    'secondary' => '#118ab2',
                    'accent' => '#ffd166',
                    'success' => '#06d6a0',
                    'warning' => '#ef476f',
                    'error' => '#ef476f',
                    'info' => '#118ab2',
                ],
                'description' => 'Vibrant tropical colors for adventure travel',
            ],
            'desert_dunes' => [
                'name' => __('Desert Dunes', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#c17767',
                    'secondary' => '#8b5a5a',
                    'accent' => '#e8c07d',
                    'success' => '#7d9d9c',
                    'warning' => '#cc580c',
                    'error' => '#c44536',
                    'info' => '#577590',
                ],
                'description' => 'Warm desert tones for safari and desert tours',
            ],
            'mountain_peak' => [
                'name' => __('Mountain Peak', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#2d6a4f',
                    'secondary' => '#40916c',
                    'accent' => '#f9c74f',
                    'success' => '#52b788',
                    'warning' => '#f9844a',
                    'error' => '#d62828',
                    'info' => '#457b9d',
                ],
                'description' => 'Natural greens for hiking and mountain tours',
            ],
            'sunset_cruise' => [
                'name' => __('Sunset Cruise', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#e56b6f',
                    'secondary' => '#f4a261',
                    'accent' => '#2a9d8f',
                    'success' => '#57cc99',
                    'warning' => '#ff6b6b',
                    'error' => '#e63946',
                    'info' => '#4cc9f0',
                ],
                'description' => 'Warm sunset colors for cruise and beach tours',
            ],
            'arctic_expedition' => [
                'name' => __('Arctic Expedition', 'ytrip'),
                'category' => 'travel',
                'colors' => [
                    'primary' => '#48cae4',
                    'secondary' => '#90e0ef',
                    'accent' => '#ff6b6b',
                    'success' => '#06d6a0',
                    'warning' => '#ffd93d',
                    'error' => '#ff6b6b',
                    'info' => '#48cae4',
                ],
                'description' => 'Cool icy tones for polar and winter tours',
            ],

            // Luxury & Premium
            'luxury_gold' => [
                'name' => __('Luxury Gold', 'ytrip'),
                'category' => 'luxury',
                'colors' => [
                    'primary' => '#1a1a2e',
                    'secondary' => '#c9a227',
                    'accent' => '#d4af37',
                    'success' => '#2ecc71',
                    'warning' => '#f39c12',
                    'error' => '#e74c3c',
                    'info' => '#3498db',
                ],
                'description' => 'Elegant dark theme with gold accents',
            ],
            'royal_purple' => [
                'name' => __('Royal Purple', 'ytrip'),
                'category' => 'luxury',
                'colors' => [
                    'primary' => '#6b2d5c',
                    'secondary' => '#8b5cf6',
                    'accent' => '#fbbf24',
                    'success' => '#10b981',
                    'warning' => '#f59e0b',
                    'error' => '#ef4444',
                    'info' => '#3b82f6',
                ],
                'description' => 'Rich purple tones for luxury experiences',
            ],
            'platinum_elegance' => [
                'name' => __('Platinum Elegance', 'ytrip'),
                'category' => 'luxury',
                'colors' => [
                    'primary' => '#374151',
                    'secondary' => '#6b7280',
                    'accent' => '#a78bfa',
                    'success' => '#34d399',
                    'warning' => '#fbbf24',
                    'error' => '#f87171',
                    'info' => '#60a5fa',
                ],
                'description' => 'Sophisticated neutral palette',
            ],

            // Modern & Tech
            'tech_blue' => [
                'name' => __('Tech Blue', 'ytrip'),
                'category' => 'modern',
                'colors' => [
                    'primary' => '#2563eb',
                    'secondary' => '#7c3aed',
                    'accent' => '#f59e0b',
                    'success' => '#10b981',
                    'warning' => '#f59e0b',
                    'error' => '#ef4444',
                    'info' => '#3b82f6',
                ],
                'description' => 'Modern tech-inspired blues',
            ],
            'neon_vibes' => [
                'name' => __('Neon Vibes', 'ytrip'),
                'category' => 'modern',
                'colors' => [
                    'primary' => '#06ffa5',
                    'secondary' => '#ff006e',
                    'accent' => '#8338ec',
                    'success' => '#06ffa5',
                    'warning' => '#ffbe0b',
                    'error' => '#ff006e',
                    'info' => '#3a86ff',
                ],
                'description' => 'Bold neon colors for modern experiences',
            ],
            'minimal_light' => [
                'name' => __('Minimal Light', 'ytrip'),
                'category' => 'modern',
                'colors' => [
                    'primary' => '#111827',
                    'secondary' => '#6b7280',
                    'accent' => '#3b82f6',
                    'success' => '#10b981',
                    'warning' => '#f59e0b',
                    'error' => '#ef4444',
                    'info' => '#3b82f6',
                ],
                'description' => 'Clean minimal design',
            ],
            'dark_mode_pro' => [
                'name' => __('Dark Mode Pro', 'ytrip'),
                'category' => 'modern',
                'colors' => [
                    'primary' => '#818cf8',
                    'secondary' => '#a78bfa',
                    'accent' => '#34d399',
                    'success' => '#34d399',
                    'warning' => '#fbbf24',
                    'error' => '#f87171',
                    'info' => '#60a5fa',
                ],
                'dark_mode_default' => true,
                'description' => 'Optimized dark mode experience',
            ],

            // Cultural & Heritage
            'egyptian_gold' => [
                'name' => __('Egyptian Gold', 'ytrip'),
                'category' => 'cultural',
                'colors' => [
                    'primary' => '#1a365d',
                    'secondary' => '#c9a227',
                    'accent' => '#ed8936',
                    'success' => '#48bb78',
                    'warning' => '#ed8936',
                    'error' => '#e53e3e',
                    'info' => '#4299e1',
                ],
                'description' => 'Rich Egyptian heritage colors',
            ],
            'asian_zen' => [
                'name' => __('Asian Zen', 'ytrip'),
                'category' => 'cultural',
                'colors' => [
                    'primary' => '#4a5568',
                    'secondary' => '#c53030',
                    'accent' => '#d69e2e',
                    'success' => '#38a169',
                    'warning' => '#d69e2e',
                    'error' => '#c53030',
                    'info' => '#3182ce',
                ],
                'description' => 'Peaceful Asian-inspired palette',
            ],
            'safari_sunset' => [
                'name' => __('Safari Sunset', 'ytrip'),
                'category' => 'cultural',
                'colors' => [
                    'primary' => '#744210',
                    'secondary' => '#c05621',
                    'accent' => '#e53e3e',
                    'success' => '#276749',
                    'warning' => '#c05621',
                    'error' => '#c53030',
                    'info' => '#2b6cb0',
                ],
                'description' => 'African savanna inspired tones',
            ],

            // Seasonal
            'spring_bloom' => [
                'name' => __('Spring Bloom', 'ytrip'),
                'category' => 'seasonal',
                'colors' => [
                    'primary' => '#059669',
                    'secondary' => '#f472b6',
                    'accent' => '#fbbf24',
                    'success' => '#10b981',
                    'warning' => '#fbbf24',
                    'error' => '#ef4444',
                    'info' => '#3b82f6',
                ],
                'description' => 'Fresh spring colors',
            ],
            'summer_vibes' => [
                'name' => __('Summer Vibes', 'ytrip'),
                'category' => 'seasonal',
                'colors' => [
                    'primary' => '#0ea5e9',
                    'secondary' => '#f97316',
                    'accent' => '#eab308',
                    'success' => '#22c55e',
                    'warning' => '#f97316',
                    'error' => '#ef4444',
                    'info' => '#0ea5e9',
                ],
                'description' => 'Bright summer energy',
            ],
            'autumn_harvest' => [
                'name' => __('Autumn Harvest', 'ytrip'),
                'category' => 'seasonal',
                'colors' => [
                    'primary' => '#b45309',
                    'secondary' => '#92400e',
                    'accent' => '#dc2626',
                    'success' => '#16a34a',
                    'warning' => '#d97706',
                    'error' => '#dc2626',
                    'info' => '#2563eb',
                ],
                'description' => 'Warm autumn tones',
            ],
            'winter_frost' => [
                'name' => __('Winter Frost', 'ytrip'),
                'category' => 'seasonal',
                'colors' => [
                    'primary' => '#0369a1',
                    'secondary' => '#7dd3fc',
                    'accent' => '#f472b6',
                    'success' => '#34d399',
                    'warning' => '#fbbf24',
                    'error' => '#f87171',
                    'info' => '#38bdf8',
                ],
                'description' => 'Cool winter palette',
            ],
        ];
    }

    /**
     * Get active preset.
     *
     * @return array
     */
    public function get_active_preset() {
        $preset = $this->presets[$this->active_preset] ?? $this->presets['ocean_adventure'];
        
        // Check for custom colors
        if ($this->active_preset === 'custom' && !empty($this->options['custom_colors'])) {
            $preset['colors'] = wp_parse_args(
                $this->options['custom_colors'],
                $preset['colors']
            );
        }

        return $preset;
    }

    /**
     * Get active colors.
     *
     * @return array
     */
    public function get_colors() {
        $preset = $this->get_active_preset();
        return $preset['colors'];
    }

    /**
     * Get a specific color.
     *
     * @param string $name Color name (primary, secondary, accent, etc.)
     * @return string
     */
    public function get_color(string $name) {
        $colors = $this->get_colors();
        return $colors[$name] ?? '#000000';
    }

    /**
     * Generate color scale (50-900) from a hex color.
     *
     * @param string $hex Base hex color.
     * @return array
     */
    public function generate_color_scale(string $hex) {
        $rgb = $this->hex_to_rgb($hex);
        $hsl = $this->rgb_to_hsl($rgb);

        $scale = [];

        // Generate tints and shades
        $lightness_map = [
            50 => 0.98,
            100 => 0.95,
            200 => 0.88,
            300 => 0.75,
            400 => 0.60,
            500 => $hsl['l'], // Base
            600 => 0.40,
            700 => 0.30,
            800 => 0.20,
            900 => 0.12,
        ];

        foreach ($lightness_map as $level => $lightness) {
            $new_hsl = [
                'h' => $hsl['h'],
                's' => $hsl['s'],
                'l' => $lightness,
            ];
            $new_rgb = $this->hsl_to_rgb($new_hsl);
            $scale[$level] = $this->rgb_to_hex($new_rgb);
        }

        return $scale;
    }

    /**
     * Get contrast color (black or white) for a background.
     * Uses WCAG 2.1 relative luminance; returns the color that meets AA contrast (4.5:1).
     *
     * @param string $hex Background hex color.
     * @return string #000000 or #ffffff
     */
    public function get_contrast_color(string $hex) {
        $rgb = $this->hex_to_rgb($hex);
        $bg_luminance = $this->get_relative_luminance($rgb);
        // WCAG 2.1: white (L≈1) on background needs bg_luminance <= ~0.183 for 4.5:1.
        return $bg_luminance <= 0.183 ? '#ffffff' : '#000000';
    }

    /**
     * Check WCAG contrast ratio.
     *
     * @param string $foreground Foreground color hex.
     * @param string $background Background color hex.
     * @return array
     */
    public function check_contrast_ratio(string $foreground, string $background) {
        $fg = $this->hex_to_rgb($foreground);
        $bg = $this->hex_to_rgb($background);

        $fg_luminance = $this->get_relative_luminance($fg);
        $bg_luminance = $this->get_relative_luminance($bg);

        $lighter = max($fg_luminance, $bg_luminance);
        $darker = min($fg_luminance, $bg_luminance);

        $ratio = ($lighter + 0.05) / ($darker + 0.05);

        return [
            'ratio' => round($ratio, 2),
            'aa_normal' => $ratio >= 4.5,
            'aa_large' => $ratio >= 3,
            'aaa_normal' => $ratio >= 7,
            'aaa_large' => $ratio >= 4.5,
        ];
    }

    /**
     * Get relative luminance.
     *
     * @param array $rgb RGB color array.
     * @return float
     */
    private function get_relative_luminance(array $rgb) {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Convert hex to RGB.
     *
     * @param string $hex Hex color.
     * @return array
     */
    private function hex_to_rgb(string $hex) {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert RGB to hex.
     *
     * @param array $rgb RGB color array.
     * @return string
     */
    private function rgb_to_hex(array $rgb) {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, (int) $rgb['r'])),
            max(0, min(255, (int) $rgb['g'])),
            max(0, min(255, (int) $rgb['b']))
        );
    }

    /**
     * Convert RGB to HSL.
     *
     * @param array $rgb RGB color array.
     * @return array
     */
    private function rgb_to_hsl(array $rgb) {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
                default:
                    $h = 0;
            }
        }

        return [
            'h' => $h,
            's' => $s,
            'l' => $l,
        ];
    }

    /**
     * Convert HSL to RGB.
     *
     * @param array $hsl HSL color array.
     * @return array
     */
    private function hsl_to_rgb(array $hsl) {
        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = $hsl['l'];

        if ($s === 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hue_to_rgb($p, $q, $h + 1/3);
            $g = $this->hue_to_rgb($p, $q, $h);
            $b = $this->hue_to_rgb($p, $q, $h - 1/3);
        }

        return [
            'r' => round($r * 255),
            'g' => round($g * 255),
            'b' => round($b * 255),
        ];
    }

    /**
     * Convert hue to RGB.
     *
     * @param float $p P value.
     * @param float $q Q value.
     * @param float $t T value.
     * @return float
     */
    private function hue_to_rgb(float $p, float $q, float $t) {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1/6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1/2) {
            return $q;
        }
        if ($t < 2/3) {
            return $p + ($q - $p) * (2/3 - $t) * 6;
        }
        return $p;
    }

    /**
     * Lighten a color.
     *
     * @param string $hex Hex color.
     * @param int $percent Percentage to lighten.
     * @return string
     */
    public function lighten(string $hex, int $percent) {
        $rgb = $this->hex_to_rgb($hex);
        $hsl = $this->rgb_to_hsl($rgb);

        $hsl['l'] = min(1, $hsl['l'] + ($percent / 100));

        $new_rgb = $this->hsl_to_rgb($hsl);
        return $this->rgb_to_hex($new_rgb);
    }

    /**
     * Darken a color.
     *
     * @param string $hex Hex color.
     * @param int $percent Percentage to darken.
     * @return string
     */
    public function darken(string $hex, int $percent) {
        $rgb = $this->hex_to_rgb($hex);
        $hsl = $this->rgb_to_hsl($rgb);

        $hsl['l'] = max(0, $hsl['l'] - ($percent / 100));

        $new_rgb = $this->hsl_to_rgb($hsl);
        return $this->rgb_to_hex($new_rgb);
    }

    /**
     * Generate CSS variables.
     *
     * @return string
     */
    public function generate_css_variables() {
        $colors = $this->get_colors();
        $css = ':root {';

        // Base colors
        foreach ($colors as $name => $color) {
            $var_name = str_replace('_', '-', $name);
            $css .= "--ytrip-{$var_name}: {$color};";

            // Add contrast color
            $contrast = $this->get_contrast_color($color);
            $css .= "--ytrip-{$var_name}-contrast: {$contrast};";
        }

        // Primary RGB for rgba() usage (shadows, overlays)
        if ( ! empty( $colors['primary'] ) ) {
            $rgb = $this->hex_to_rgb( $colors['primary'] );
            $css .= '--ytrip-primary-rgb: ' . ( (int) $rgb['r'] ) . ',' . ( (int) $rgb['g'] ) . ',' . ( (int) $rgb['b'] ) . ';';
        }

        // Generate color scales
        foreach (['primary', 'secondary', 'accent'] as $color_name) {
            if (!isset($colors[$color_name])) {
                continue;
            }

            $scale = $this->generate_color_scale($colors[$color_name]);
            foreach ($scale as $level => $color) {
                $css .= "--ytrip-{$color_name}-{$level}: {$color};";
            }
        }

        // Surface colors for dark/light mode
        $css .= '--ytrip-surface: #ffffff;';
        $css .= '--ytrip-surface-alt: #f8fafc;';
        $css .= '--ytrip-background: #ffffff;';
        $css .= '--ytrip-text: #1e293b;';
        $css .= '--ytrip-text-muted: #64748b;';
        $css .= '--ytrip-border: #e2e8f0;';

        // Spacing and radius
        $css .= '--ytrip-radius: 12px;';
        $css .= '--ytrip-radius-sm: 8px;';
        $css .= '--ytrip-radius-lg: 16px;';
        $css .= '--ytrip-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);';
        $css .= '--ytrip-shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);';

        $css .= '}';

        // Dark mode variables
        if ($this->dark_mode) {
            $css .= $this->generate_dark_mode_css();
        }

        return $css;
    }

    /**
     * Generate dark mode CSS.
     *
     * @return string
     */
    private function generate_dark_mode_css() {
        $colors = $this->get_colors();

        $css = '.ytrip-dark-mode, [data-theme="dark"], .ytrip-section--dark {';
        $css .= '--ytrip-surface: #0f172a;';
        $css .= '--ytrip-surface-alt: #1e293b;';
        $css .= '--ytrip-background: #0f172a;';
        $css .= '--ytrip-text: #f1f5f9;';
        $css .= '--ytrip-text-muted: #94a3b8;';
        $css .= '--ytrip-border: #334155;';
        $css .= '--ytrip-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);';
        $css .= '}';

        return $css;
    }

    /**
     * Output CSS variables to head.
     *
     * @return void
     */
    public function output_css_variables() {
        $css = $this->generate_css_variables();

        printf(
            '<style id="ytrip-brand-colors">%s</style>',
            wp_strip_all_tags($css)
        );

        // Dark mode toggle script
        if ($this->dark_mode) {
            $this->output_dark_mode_toggle();
        }
    }

    /**
     * Output dark mode toggle script.
     *
     * @return void
     */
    private function output_dark_mode_toggle() {
?>
        <script id="ytrip-dark-mode-init">
            (function() {
                const stored = localStorage.getItem('ytrip-theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = stored || (prefersDark ? 'dark' : 'light');
                
                if (theme === 'dark') {
                    document.documentElement.classList.add('ytrip-dark-mode');
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            })();
        </script>
<?php
    }

    /**
     * Output admin CSS variables.
     *
     * @return void
     */
    public function output_admin_css_variables() {
        if (!$this->is_ytrip_admin_page()) {
            return;
        }

        $this->output_css_variables();
    }

    /**
     * Check if current admin page is YTrip.
     *
     * @return bool
     */
    private function is_ytrip_admin_page() {
        if (!is_admin()) {
            return false;
        }

        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'ytrip') !== false;
    }

    /**
     * Enqueue dark mode assets.
     *
     * @return void
     */
    public function enqueue_dark_mode_assets() {
        if ( ! function_exists( 'ytrip_is_plugin_page' ) || ! ytrip_is_plugin_page() ) {
            return;
        }
        if ( ! $this->dark_mode ) {
            return;
        }

        wp_add_inline_script(
            'jquery',
            '
            (function($) {
                $(document).on("click", ".ytrip-dark-mode-toggle", function(e) {
                    e.preventDefault();
                    
                    var isDark = $("html").hasClass("ytrip-dark-mode");
                    
                    if (isDark) {
                        $("html").removeClass("ytrip-dark-mode").attr("data-theme", "light");
                        localStorage.setItem("ytrip-theme", "light");
                    } else {
                        $("html").addClass("ytrip-dark-mode").attr("data-theme", "dark");
                        localStorage.setItem("ytrip-theme", "dark");
                    }
                });
            })(jQuery);
            '
        );
    }

    /**
     * Add body classes.
     *
     * @param array $classes Existing classes.
     * @return array
     */
    public function add_body_classes(array $classes) {
        $classes[] = 'ytrip-preset-' . sanitize_html_class($this->active_preset);

        if ($this->dark_mode) {
            $classes[] = 'ytrip-dark-mode-enabled';
        }

        return $classes;
    }

    /**
     * AJAX toggle dark mode.
     *
     * @return void
     */
    public function ajax_toggle_dark_mode() {
        check_ajax_referer('ytrip_frontend_nonce', 'nonce');

        $this->dark_mode = !$this->dark_mode;
        update_option('ytrip_dark_mode', $this->dark_mode);

        wp_send_json_success([
            'dark_mode' => $this->dark_mode,
        ]);
    }

    /**
     * Get preset options for dropdown.
     *
     * @return array
     */
    public function get_preset_options() {
        $options = [];

        foreach ($this->presets as $key => $preset) {
            $options[$key] = $preset['name'];
        }

        $options['custom'] = __('Custom Colors', 'ytrip');

        return $options;
    }

    /**
     * Export current settings.
     *
     * @return array
     */
    public function export_settings() {
        return [
            'preset' => $this->active_preset,
            'dark_mode' => $this->dark_mode,
            'colors' => $this->get_colors(),
        ];
    }

    /**
     * Import settings.
     *
     * @param array $settings Settings to import.
     * @return bool
     */
    public function import_settings(array $settings) {
        $options = get_option('ytrip_settings', []);

        if (!empty($settings['preset'])) {
            $options['color_preset'] = sanitize_text_field($settings['preset']);
        }

        if (isset($settings['dark_mode'])) {
            $options['dark_mode'] = (bool) $settings['dark_mode'];
        }

        if (!empty($settings['colors']) && is_array($settings['colors'])) {
            $options['custom_colors'] = array_map('sanitize_hex_color', $settings['colors']);
        }

        return update_option('ytrip_settings', $options);
    }
}

/**
 * Helper function to get brand system instance.
 *
 * @return YTrip_Brand_System
 */
function ytrip_brand(): YTrip_Brand_System {
    return YTrip_Brand_System::instance();
}

/**
 * Helper function to get a color.
 *
 * @param string $name Color name.
 * @return string
 */
function ytrip_get_color(string $name) {
    return YTrip_Brand_System::instance()->get_color($name);
}

// Initialize.
YTrip_Brand_System::instance();
