<?php
/**
 * YTrip Design Tokens
 * 
 * Manages component-level design tokens for consistent theming.
 * Generates CSS custom properties for all UI components.
 *
 * @package YTrip
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class YTrip_Design_Tokens
 *
 * Handles design token management, CSS variable generation,
 * and responsive breakpoint handling.
 */
class YTrip_Design_Tokens {

    /**
     * Token categories
     */
    const CATEGORY_CARDS   = 'cards';
    const CATEGORY_BUTTONS = 'buttons';
    const CATEGORY_FORMS   = 'forms';
    const CATEGORY_MODALS  = 'modals';
    const CATEGORY_HEADERS = 'headers';
    const CATEGORY_FOOTERS = 'footers';

    /**
     * Breakpoint definitions
     */
    const BREAKPOINT_MOBILE  = 'mobile';
    const BREAKPOINT_TABLET  = 'tablet';
    const BREAKPOINT_DESKTOP = 'desktop';

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Breakpoint values in pixels
     *
     * @var array
     */
    private $breakpoints = array(
        'mobile'  => 767,
        'tablet'  => 1024,
        'desktop' => 1025,
    );

    /**
     * Default token values
     *
     * @var array
     */
    private $defaults;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options  = get_option( 'ytrip_settings', array() );
        $this->defaults = $this->get_default_tokens();

        add_action( 'wp_head', array( $this, 'output_css_variables' ), 5 );
        add_action( 'admin_head', array( $this, 'output_admin_css_variables' ), 5 );
    }

    /**
     * Get default token values for all categories
     *
     * @return array Default tokens organized by category
     */
    private function get_default_tokens() {
        return array(
            self::CATEGORY_CARDS => array(
                'tour' => array(
                    'bg'            => '#ffffff',
                    'bg_hover'      => '#fafafa',
                    'border_color'  => '#e5e7eb',
                    'border_width'  => '1px',
                    'border_radius' => '12px',
                    'padding'       => '16px',
                    'shadow'        => '0 4px 6px -1px rgba(0,0,0,0.1)',
                    'shadow_hover'  => '0 10px 15px -3px rgba(0,0,0,0.15)',
                    'transition'    => '0.3s ease',
                ),
                'destination' => array(
                    'bg'            => '#ffffff',
                    'bg_hover'      => '#f9fafb',
                    'border_color'  => 'transparent',
                    'border_width'  => '0',
                    'border_radius' => '16px',
                    'padding'       => '0',
                    'shadow'        => '0 4px 6px rgba(0,0,0,0.1)',
                    'shadow_hover'  => '0 12px 20px rgba(0,0,0,0.15)',
                    'transition'    => '0.3s ease',
                ),
                'badge' => array(
                    'position'      => 'top-left',
                    'style'         => 'pill',
                    'bg'            => '#dc2626',
                    'text_color'    => '#ffffff',
                    'font_size'     => '12px',
                    'padding'       => '4px 12px',
                    'border_radius' => '20px',
                ),
                'price' => array(
                    'format'        => 'from_price',
                    'color'         => '#059669',
                    'font_size'     => '20px',
                    'font_weight'   => '700',
                ),
                'meta' => array(
                    'show_rating'   => true,
                    'show_duration' => true,
                    'show_location' => true,
                    'show_reviews'  => true,
                    'show_capacity' => false,
                    'color'         => '#6b7280',
                    'font_size'     => '14px',
                ),
                'animation' => array(
                    'timing'        => 'ease',
                    'duration'      => '300ms',
                    'hover_scale'   => '1.02',
                    'hover_lift'    => '-4px',
                ),
            ),
            self::CATEGORY_BUTTONS => array(
                'primary' => array(
                    'bg'            => '#2563eb',
                    'bg_hover'      => '#1d4ed8',
                    'text_color'    => '#ffffff',
                    'text_hover'    => '#ffffff',
                    'border_color'  => 'transparent',
                    'border_width'  => '0',
                    'border_radius' => '8px',
                    'padding'       => '12px 24px',
                    'font_size'     => '16px',
                    'font_weight'   => '600',
                    'shadow'        => '0 4px 6px rgba(37,99,235,0.25)',
                    'shadow_hover'  => '0 6px 10px rgba(37,99,235,0.35)',
                    'transition'    => '0.2s ease',
                ),
                'secondary' => array(
                    'bg'            => '#f3f4f6',
                    'bg_hover'      => '#e5e7eb',
                    'text_color'    => '#374151',
                    'text_hover'    => '#1f2937',
                    'border_color'  => '#e5e7eb',
                    'border_width'  => '1px',
                    'border_radius' => '8px',
                    'padding'       => '12px 24px',
                    'font_size'     => '16px',
                    'font_weight'   => '500',
                    'shadow'        => 'none',
                    'shadow_hover'  => '0 2px 4px rgba(0,0,0,0.05)',
                    'transition'    => '0.2s ease',
                ),
                'ghost' => array(
                    'bg'            => 'transparent',
                    'bg_hover'      => 'rgba(37,99,235,0.1)',
                    'text_color'    => '#2563eb',
                    'text_hover'    => '#1d4ed8',
                    'border_color'  => '#2563eb',
                    'border_width'  => '2px',
                    'border_radius' => '8px',
                    'padding'       => '10px 22px',
                    'font_size'     => '16px',
                    'font_weight'   => '600',
                    'shadow'        => 'none',
                    'shadow_hover'  => 'none',
                    'transition'    => '0.2s ease',
                ),
                'sizes' => array(
                    'sm' => array(
                        'padding'     => '8px 16px',
                        'font_size'   => '14px',
                    ),
                    'md' => array(
                        'padding'     => '12px 24px',
                        'font_size'   => '16px',
                    ),
                    'lg' => array(
                        'padding'     => '16px 32px',
                        'font_size'   => '18px',
                    ),
                ),
            ),
            self::CATEGORY_FORMS => array(
                'input' => array(
                    'bg'             => '#ffffff',
                    'bg_focus'       => '#ffffff',
                    'text_color'     => '#1f2937',
                    'placeholder'    => '#9ca3af',
                    'border_color'   => '#d1d5db',
                    'border_focus'   => '#2563eb',
                    'border_width'   => '1px',
                    'border_radius'  => '8px',
                    'padding'        => '12px 16px',
                    'font_size'      => '16px',
                    'shadow_focus'   => '0 0 0 3px rgba(37,99,235,0.15)',
                    'transition'     => '0.2s ease',
                ),
                'select' => array(
                    'bg'             => '#ffffff',
                    'bg_hover'       => '#f9fafb',
                    'arrow_color'    => '#6b7280',
                    'border_color'   => '#d1d5db',
                    'border_radius'  => '8px',
                    'padding'        => '12px 40px 12px 16px',
                ),
                'checkbox' => array(
                    'size'           => '20px',
                    'bg'             => '#ffffff',
                    'bg_checked'     => '#2563eb',
                    'border_color'   => '#d1d5db',
                    'check_color'    => '#ffffff',
                    'border_radius'  => '4px',
                ),
                'radio' => array(
                    'size'           => '20px',
                    'bg'             => '#ffffff',
                    'bg_checked'     => '#2563eb',
                    'border_color'   => '#d1d5db',
                    'dot_color'      => '#ffffff',
                ),
                'label' => array(
                    'color'          => '#374151',
                    'font_size'      => '14px',
                    'font_weight'    => '500',
                    'margin_bottom'  => '6px',
                ),
                'error' => array(
                    'color'          => '#dc2626',
                    'border_color'   => '#dc2626',
                    'bg'             => '#fef2f2',
                ),
            ),
            self::CATEGORY_MODALS => array(
                'overlay' => array(
                    'bg'             => 'rgba(0,0,0,0.5)',
                    'backdrop_blur'  => '4px',
                    'transition'     => '0.3s ease',
                ),
                'container' => array(
                    'bg'             => '#ffffff',
                    'border_radius'  => '16px',
                    'padding'        => '24px',
                    'shadow'         => '0 25px 50px -12px rgba(0,0,0,0.25)',
                    'max_width'      => '560px',
                    'animation'      => 'scale-fade',
                ),
                'header' => array(
                    'padding'        => '0 0 16px 0',
                    'border_bottom'  => '1px solid #e5e7eb',
                    'font_size'      => '20px',
                    'font_weight'    => '600',
                ),
                'close_button' => array(
                    'size'           => '32px',
                    'bg'             => '#f3f4f6',
                    'bg_hover'       => '#e5e7eb',
                    'icon_color'     => '#6b7280',
                    'border_radius'  => '8px',
                ),
            ),
            self::CATEGORY_HEADERS => array(
                'main' => array(
                    'bg'             => '#ffffff',
                    'bg_sticky'      => 'rgba(255,255,255,0.95)',
                    'text_color'     => '#1f2937',
                    'height'         => '80px',
                    'height_sticky'  => '64px',
                    'padding'        => '0 24px',
                    'shadow'         => 'none',
                    'shadow_sticky'  => '0 4px 6px -1px rgba(0,0,0,0.1)',
                    'backdrop_blur'  => '10px',
                    'transition'     => '0.3s ease',
                ),
                'nav' => array(
                    'link_color'     => '#374151',
                    'link_hover'     => '#2563eb',
                    'link_active'    => '#2563eb',
                    'font_size'      => '15px',
                    'font_weight'    => '500',
                    'gap'            => '32px',
                ),
                'mobile_menu' => array(
                    'bg'             => '#ffffff',
                    'overlay_bg'     => 'rgba(0,0,0,0.5)',
                    'width'          => '280px',
                    'item_padding'   => '16px 24px',
                    'border_bottom'  => '1px solid #f3f4f6',
                ),
            ),
            self::CATEGORY_FOOTERS => array(
                'main' => array(
                    'bg'             => '#111827',
                    'text_color'     => '#9ca3af',
                    'heading_color'  => '#ffffff',
                    'link_color'     => '#9ca3af',
                    'link_hover'     => '#ffffff',
                    'padding'        => '64px 24px',
                    'gap'            => '48px',
                ),
                'bottom' => array(
                    'bg'             => '#0a0f1a',
                    'text_color'     => '#6b7280',
                    'padding'        => '24px',
                    'border_top'     => '1px solid #1f2937',
                ),
                'social' => array(
                    'icon_size'      => '20px',
                    'icon_color'     => '#9ca3af',
                    'icon_hover'     => '#ffffff',
                    'gap'            => '16px',
                ),
            ),
        );
    }

    /**
     * Get a specific token value with fallback
     *
     * @param string $category   Token category (e.g., 'cards', 'buttons')
     * @param string $component  Component name (e.g., 'tour', 'primary')
     * @param string $property   Property name (e.g., 'bg', 'border_radius')
     * @param string $breakpoint Optional breakpoint (desktop, tablet, mobile)
     * @return mixed Token value or default
     */
    public function get_token( string $category, string $component, string $property, string $breakpoint = 'desktop' ) {
        $option_key = "design_tokens_{$category}_{$component}_{$property}";
        
        // Check for responsive values if not desktop
        if ( $breakpoint !== 'desktop' ) {
            $responsive_key = "{$option_key}_{$breakpoint}";
            $responsive_value = $this->options[ $responsive_key ] ?? null;
            if ( $responsive_value !== null ) {
                return $responsive_value;
            }
        }

        // Get value from options or fall back to default
        $value = $this->options[ $option_key ] ?? null;
        
        if ( $value !== null ) {
            return $value;
        }

        // Return default value
        return $this->defaults[ $category ][ $component ][ $property ] ?? '';
    }

    /**
     * Get all tokens for a category
     *
     * @param string $category Token category
     * @return array All tokens in the category
     */
    public function get_category_tokens( string $category ) {
        $tokens = $this->defaults[ $category ] ?? array();
        
        // Merge with saved options
        foreach ( $tokens as $component => $properties ) {
            if ( is_array( $properties ) ) {
                foreach ( $properties as $property => $default ) {
                    $option_key = "design_tokens_{$category}_{$component}_{$property}";
                    if ( isset( $this->options[ $option_key ] ) ) {
                        $tokens[ $component ][ $property ] = $this->options[ $option_key ];
                    }
                }
            }
        }
        
        return $tokens;
    }

    /**
     * Generate CSS variables from all tokens
     *
     * @return string CSS custom properties
     */
    public function generate_css_variables() {
        $css = ":root {\n";

        foreach ( $this->defaults as $category => $components ) {
            $css .= "    /* {$category} tokens */\n";
            foreach ( $components as $component => $properties ) {
                if ( is_array( $properties ) ) {
                    foreach ( $properties as $property => $default ) {
                        $value = $this->get_token( $category, $component, $property );
                        $var_name = "--ytrip-{$category}-{$component}-{$property}";
                        $var_name = str_replace( '_', '-', $var_name );
                        
                        // Skip nested arrays (like button sizes)
                        if ( is_array( $value ) ) {
                            continue;
                        }
                        
                        // Handle boolean values
                        if ( is_bool( $value ) ) {
                            $value = $value ? '1' : '0';
                        }
                        
                        $css .= "    {$var_name}: {$value};\n";
                    }
                }
            }
            $css .= "\n";
        }

        // Add button size variants
        if ( isset( $this->defaults[ self::CATEGORY_BUTTONS ]['sizes'] ) ) {
            $css .= "    /* Button size variants */\n";
            foreach ( $this->defaults[ self::CATEGORY_BUTTONS ]['sizes'] as $size => $props ) {
                foreach ( $props as $property => $default ) {
                    $var_name = "--ytrip-buttons-{$size}-{$property}";
                    $var_name = str_replace( '_', '-', $var_name );
                    $css .= "    {$var_name}: {$default};\n";
                }
            }
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Generate responsive CSS with media queries
     *
     * @return string Responsive CSS
     */
    public function get_responsive_css() {
        $css = '';

        // Tablet styles
        $css .= "@media (max-width: {$this->breakpoints['tablet']}px) {\n";
        $css .= "    :root {\n";
        $css .= $this->generate_breakpoint_overrides( 'tablet' );
        $css .= "    }\n";
        $css .= "}\n\n";

        // Mobile styles
        $css .= "@media (max-width: {$this->breakpoints['mobile']}px) {\n";
        $css .= "    :root {\n";
        $css .= $this->generate_breakpoint_overrides( 'mobile' );
        $css .= "    }\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Generate CSS overrides for a specific breakpoint
     *
     * @param string $breakpoint Breakpoint name
     * @return string CSS variable overrides
     */
    private function generate_breakpoint_overrides( string $breakpoint ) {
        $css = '';
        
        // Common responsive overrides
        $responsive_tokens = array(
            'cards' => array(
                'tour' => array( 'padding', 'border_radius' ),
                'destination' => array( 'padding', 'border_radius' ),
            ),
            'buttons' => array(
                'primary' => array( 'padding', 'font_size' ),
                'secondary' => array( 'padding', 'font_size' ),
            ),
            'modals' => array(
                'container' => array( 'padding', 'max_width', 'border_radius' ),
            ),
            'headers' => array(
                'main' => array( 'height', 'padding' ),
                'nav' => array( 'font_size', 'gap' ),
            ),
            'footers' => array(
                'main' => array( 'padding', 'gap' ),
            ),
        );

        foreach ( $responsive_tokens as $category => $components ) {
            foreach ( $components as $component => $properties ) {
                foreach ( $properties as $property ) {
                    $option_key = "design_tokens_{$category}_{$component}_{$property}_{$breakpoint}";
                    if ( isset( $this->options[ $option_key ] ) ) {
                        $var_name = "--ytrip-{$category}-{$component}-{$property}";
                        $var_name = str_replace( '_', '-', $var_name );
                        $value = $this->options[ $option_key ];
                        $css .= "        {$var_name}: {$value};\n";
                    }
                }
            }
        }

        return $css;
    }

    /**
     * Output CSS variables to page head
     *
     * @return void
     */
    public function output_css_variables() {
        $css = $this->generate_css_variables();
        $css .= $this->get_responsive_css();

        echo "<style id=\"ytrip-design-tokens\">\n";
        echo wp_kses( $css, array() );
        echo "</style>\n";
    }

    /**
     * Output CSS variables for admin panel
     *
     * @return void
     */
    public function output_admin_css_variables() {
        if ( ! $this->is_ytrip_admin_page() ) {
            return;
        }

        $css = $this->generate_css_variables();

        echo "<style id=\"ytrip-admin-design-tokens\">\n";
        echo wp_kses( $css, array() );
        echo "</style>\n";
    }

    /**
     * Check if current page is YTrip admin page
     *
     * @return bool
     */
    private function is_ytrip_admin_page() {
        if ( ! is_admin() ) {
            return false;
        }

        $screen = get_current_screen();
        return $screen && strpos( $screen->id, 'ytrip' ) !== false;
    }

    /**
     * Get all breakpoint definitions
     *
     * @return array Breakpoints with values
     */
    public function get_breakpoints() {
        return $this->breakpoints;
    }

    /**
     * Set custom breakpoint values
     *
     * @param string $breakpoint Breakpoint name
     * @param int    $value      Pixel value
     * @return void
     */
    public function set_breakpoint( string $breakpoint, int $value ) {
        if ( array_key_exists( $breakpoint, $this->breakpoints ) ) {
            $this->breakpoints[ $breakpoint ] = $value;
        }
    }

    /**
     * Export tokens as JSON
     *
     * @return string JSON encoded tokens
     */
    public function export_tokens() {
        $export = array();

        foreach ( $this->defaults as $category => $components ) {
            $export[ $category ] = $this->get_category_tokens( $category );
        }

        return wp_json_encode( $export, JSON_PRETTY_PRINT );
    }

    /**
     * Import tokens from JSON
     *
     * @param string $json JSON encoded tokens
     * @return bool Success status
     */
    public function import_tokens( string $json ) {
        $tokens = json_decode( $json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return false;
        }

        $new_options = $this->options;

        foreach ( $tokens as $category => $components ) {
            if ( ! isset( $this->defaults[ $category ] ) ) {
                continue;
            }

            foreach ( $components as $component => $properties ) {
                if ( is_array( $properties ) ) {
                    foreach ( $properties as $property => $value ) {
                        $option_key = "design_tokens_{$category}_{$component}_{$property}";
                        $new_options[ $option_key ] = $this->sanitize_token_value( $value );
                    }
                }
            }
        }

        return update_option( 'ytrip_settings', $new_options );
    }

    /**
     * Sanitize a token value based on its type
     *
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    private function sanitize_token_value( $value ) {
        if ( is_bool( $value ) ) {
            return (bool) $value;
        }

        if ( is_numeric( $value ) ) {
            return is_float( $value + 0 ) ? (float) $value : (int) $value;
        }

        if ( is_string( $value ) ) {
            // Check for color values
            if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
                return sanitize_hex_color( $value );
            }

            // Check for rgba values
            if ( strpos( $value, 'rgba' ) === 0 || strpos( $value, 'rgb' ) === 0 ) {
                return sanitize_text_field( $value );
            }

            return sanitize_text_field( $value );
        }

        return $value;
    }

    /**
     * Get available badge positions
     *
     * @return array Badge position options
     */
    public static function get_badge_positions() {
        return array(
            'top-left'       => __( 'Top Left', 'ytrip' ),
            'top-right'      => __( 'Top Right', 'ytrip' ),
            'bottom-left'    => __( 'Bottom Left', 'ytrip' ),
            'bottom-right'   => __( 'Bottom Right', 'ytrip' ),
            'overlay-center' => __( 'Center Overlay', 'ytrip' ),
        );
    }

    /**
     * Get available badge styles
     *
     * @return array Badge style options
     */
    public static function get_badge_styles() {
        return array(
            'pill'    => __( 'Pill', 'ytrip' ),
            'square'  => __( 'Square', 'ytrip' ),
            'ribbon'  => __( 'Ribbon', 'ytrip' ),
            'bubble'  => __( 'Bubble', 'ytrip' ),
        );
    }

    /**
     * Get available price formats
     *
     * @return array Price format options
     */
    public static function get_price_formats() {
        return array(
            'price_only'    => __( '$99', 'ytrip' ),
            'from_price'    => __( 'From $99', 'ytrip' ),
            'per_person'    => __( '$99/person', 'ytrip' ),
            'starting_at'   => __( 'Starting at $99', 'ytrip' ),
        );
    }

    /**
     * Get available animation timing functions
     *
     * @return array Timing function options
     */
    public static function get_timing_functions() {
        return array(
            'ease'           => __( 'Ease', 'ytrip' ),
            'ease-in'        => __( 'Ease In', 'ytrip' ),
            'ease-out'       => __( 'Ease Out', 'ytrip' ),
            'ease-in-out'    => __( 'Ease In Out', 'ytrip' ),
            'linear'         => __( 'Linear', 'ytrip' ),
            'cubic-bezier'   => __( 'Custom Cubic Bezier', 'ytrip' ),
        );
    }
}

// Initialize
new YTrip_Design_Tokens();
