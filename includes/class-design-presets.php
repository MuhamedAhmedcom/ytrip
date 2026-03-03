<?php
/**
 * YTrip Design Presets
 * 
 * Pre-configured design themes that can be applied with one click.
 * Supports import/export for custom preset sharing.
 *
 * @package YTrip
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class YTrip_Design_Presets
 *
 * Manages design presets and theme configurations.
 */
class YTrip_Design_Presets {

    /**
     * Available preset names
     */
    const PRESET_MODERN_MINIMAL   = 'modern_minimal';
    const PRESET_BOLD_TRAVEL      = 'bold_travel';
    const PRESET_LUXURY_GOLD      = 'luxury_gold';
    const PRESET_CLEAN_CORPORATE  = 'clean_corporate';

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Preset configurations
     *
     * @var array
     */
    private $presets;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option( 'ytrip_settings', array() );
        $this->presets = $this->define_presets();

        add_action( 'wp_ajax_ytrip_apply_preset', array( $this, 'ajax_apply_preset' ) );
        add_action( 'wp_ajax_ytrip_export_preset', array( $this, 'ajax_export_preset' ) );
        add_action( 'wp_ajax_ytrip_import_preset', array( $this, 'ajax_import_preset' ) );
    }

    /**
     * Define all built-in presets
     *
     * @return array Preset configurations
     */
    private function define_presets() {
        return array(
            self::PRESET_MODERN_MINIMAL => array(
                'name'        => __( 'Modern Minimal', 'ytrip' ),
                'description' => __( 'Clean, whitespace-heavy design perfect for corporate sites', 'ytrip' ),
                'thumbnail'   => YTRIP_URL . 'assets/images/presets/modern-minimal.jpg',
                'colors'      => array(
                    'primary'    => '#2563eb',
                    'secondary'  => '#64748b',
                    'accent'     => '#0ea5e9',
                    'background' => '#ffffff',
                    'surface'    => '#f8fafc',
                    'text'       => '#1e293b',
                ),
                'tokens'      => array(
                    'cards' => array(
                        'tour' => array(
                            'bg'            => '#ffffff',
                            'border_color'  => '#e2e8f0',
                            'border_width'  => '1px',
                            'border_radius' => '12px',
                            'shadow'        => '0 1px 3px rgba(0,0,0,0.05)',
                            'shadow_hover'  => '0 10px 20px rgba(0,0,0,0.08)',
                        ),
                        'badge' => array(
                            'bg'            => '#2563eb',
                            'style'         => 'pill',
                            'border_radius' => '20px',
                        ),
                    ),
                    'buttons' => array(
                        'primary' => array(
                            'bg'            => '#2563eb',
                            'bg_hover'      => '#1d4ed8',
                            'border_radius' => '8px',
                            'shadow'        => 'none',
                        ),
                        'secondary' => array(
                            'bg'            => '#f1f5f9',
                            'text_color'    => '#475569',
                            'border_radius' => '8px',
                        ),
                    ),
                    'modals' => array(
                        'container' => array(
                            'border_radius' => '16px',
                            'shadow'        => '0 20px 40px rgba(0,0,0,0.15)',
                        ),
                    ),
                ),
                'typography'  => array(
                    'body_font'    => 'Inter',
                    'heading_font' => 'Inter',
                ),
            ),

            self::PRESET_BOLD_TRAVEL => array(
                'name'        => __( 'Bold Travel', 'ytrip' ),
                'description' => __( 'Vibrant, high-contrast design for adventure brands', 'ytrip' ),
                'thumbnail'   => YTRIP_URL . 'assets/images/presets/bold-travel.jpg',
                'colors'      => array(
                    'primary'    => '#dc2626',
                    'secondary'  => '#ea580c',
                    'accent'     => '#fbbf24',
                    'background' => '#fafafa',
                    'surface'    => '#ffffff',
                    'text'       => '#171717',
                ),
                'tokens'      => array(
                    'cards' => array(
                        'tour' => array(
                            'bg'            => '#ffffff',
                            'border_color'  => 'transparent',
                            'border_width'  => '0',
                            'border_radius' => '16px',
                            'shadow'        => '0 4px 12px rgba(0,0,0,0.1)',
                            'shadow_hover'  => '0 16px 32px rgba(0,0,0,0.15)',
                        ),
                        'badge' => array(
                            'bg'            => '#dc2626',
                            'style'         => 'ribbon',
                            'border_radius' => '0',
                        ),
                        'animation' => array(
                            'duration'    => '400ms',
                            'hover_scale' => '1.03',
                            'hover_lift'  => '-8px',
                        ),
                    ),
                    'buttons' => array(
                        'primary' => array(
                            'bg'            => '#dc2626',
                            'bg_hover'      => '#b91c1c',
                            'border_radius' => '12px',
                            'shadow'        => '0 4px 14px rgba(220,38,38,0.35)',
                        ),
                        'secondary' => array(
                            'bg'            => '#fef2f2',
                            'text_color'    => '#dc2626',
                            'border_radius' => '12px',
                        ),
                    ),
                ),
                'typography'  => array(
                    'body_font'    => 'Outfit',
                    'heading_font' => 'Poppins',
                ),
            ),

            self::PRESET_LUXURY_GOLD => array(
                'name'        => __( 'Luxury Gold', 'ytrip' ),
                'description' => __( 'Elegant dark theme for premium travel experiences', 'ytrip' ),
                'thumbnail'   => YTRIP_URL . 'assets/images/presets/luxury-gold.jpg',
                'colors'      => array(
                    'primary'    => '#d4a574',
                    'secondary'  => '#a78355',
                    'accent'     => '#fcd34d',
                    'background' => '#1a1a1a',
                    'surface'    => '#262626',
                    'text'       => '#fafafa',
                ),
                'tokens'      => array(
                    'cards' => array(
                        'tour' => array(
                            'bg'            => '#262626',
                            'bg_hover'      => '#303030',
                            'border_color'  => '#404040',
                            'border_width'  => '1px',
                            'border_radius' => '8px',
                            'shadow'        => '0 4px 20px rgba(0,0,0,0.3)',
                            'shadow_hover'  => '0 8px 30px rgba(212,165,116,0.2)',
                        ),
                        'badge' => array(
                            'bg'            => '#d4a574',
                            'text_color'    => '#1a1a1a',
                            'style'         => 'square',
                            'border_radius' => '4px',
                        ),
                        'price' => array(
                            'color' => '#d4a574',
                        ),
                    ),
                    'buttons' => array(
                        'primary' => array(
                            'bg'            => '#d4a574',
                            'bg_hover'      => '#c49464',
                            'text_color'    => '#1a1a1a',
                            'border_radius' => '4px',
                            'shadow'        => '0 4px 14px rgba(212,165,116,0.3)',
                        ),
                        'ghost' => array(
                            'border_color'  => '#d4a574',
                            'text_color'    => '#d4a574',
                            'text_hover'    => '#1a1a1a',
                            'bg_hover'      => '#d4a574',
                        ),
                    ),
                    'headers' => array(
                        'main' => array(
                            'bg'         => '#1a1a1a',
                            'text_color' => '#fafafa',
                        ),
                        'nav' => array(
                            'link_color'  => '#a3a3a3',
                            'link_hover'  => '#d4a574',
                            'link_active' => '#d4a574',
                        ),
                    ),
                    'footers' => array(
                        'main' => array(
                            'bg'            => '#0a0a0a',
                            'heading_color' => '#d4a574',
                        ),
                    ),
                ),
                'typography'  => array(
                    'body_font'    => 'Cormorant Garamond',
                    'heading_font' => 'Playfair Display',
                ),
            ),

            self::PRESET_CLEAN_CORPORATE => array(
                'name'        => __( 'Clean Corporate', 'ytrip' ),
                'description' => __( 'Professional design for business travel agencies', 'ytrip' ),
                'thumbnail'   => YTRIP_URL . 'assets/images/presets/clean-corporate.jpg',
                'colors'      => array(
                    'primary'    => '#0ea5e9',
                    'secondary'  => '#6366f1',
                    'accent'     => '#14b8a6',
                    'background' => '#ffffff',
                    'surface'    => '#f1f5f9',
                    'text'       => '#334155',
                ),
                'tokens'      => array(
                    'cards' => array(
                        'tour' => array(
                            'bg'            => '#ffffff',
                            'border_color'  => '#e2e8f0',
                            'border_width'  => '1px',
                            'border_radius' => '8px',
                            'shadow'        => '0 1px 2px rgba(0,0,0,0.04)',
                            'shadow_hover'  => '0 8px 16px rgba(0,0,0,0.08)',
                        ),
                        'badge' => array(
                            'bg'            => '#0ea5e9',
                            'style'         => 'pill',
                        ),
                    ),
                    'buttons' => array(
                        'primary' => array(
                            'bg'            => '#0ea5e9',
                            'bg_hover'      => '#0284c7',
                            'border_radius' => '6px',
                            'font_weight'   => '500',
                        ),
                        'secondary' => array(
                            'bg'            => '#ffffff',
                            'text_color'    => '#0ea5e9',
                            'border_color'  => '#0ea5e9',
                            'border_width'  => '1px',
                        ),
                    ),
                    'forms' => array(
                        'input' => array(
                            'border_radius' => '6px',
                            'border_color'  => '#cbd5e1',
                            'border_focus'  => '#0ea5e9',
                        ),
                    ),
                ),
                'typography'  => array(
                    'body_font'    => 'Roboto',
                    'heading_font' => 'Roboto',
                ),
            ),
        );
    }

    /**
     * Get all available presets
     *
     * @return array Preset configurations
     */
    public function get_presets() {
        return $this->presets;
    }

    /**
     * Get a specific preset
     *
     * @param string $preset_id Preset identifier
     * @return array|null Preset configuration or null
     */
    public function get_preset( string $preset_id ) {
        return $this->presets[ $preset_id ] ?? null;
    }

    /**
     * Apply a preset to the current settings
     *
     * @param string $preset_id Preset identifier
     * @return bool Success status
     */
    public function apply_preset( string $preset_id ) {
        $preset = $this->get_preset( $preset_id );

        if ( ! $preset ) {
            return false;
        }

        $new_options = $this->options;

        // Apply colors
        if ( isset( $preset['colors'] ) ) {
            foreach ( $preset['colors'] as $key => $value ) {
                $new_options[ "custom_colors_{$key}" ] = $value;
            }
            $new_options['color_preset'] = 'custom';
        }

        // Apply design tokens
        if ( isset( $preset['tokens'] ) ) {
            foreach ( $preset['tokens'] as $category => $components ) {
                foreach ( $components as $component => $properties ) {
                    foreach ( $properties as $property => $value ) {
                        $option_key = "design_tokens_{$category}_{$component}_{$property}";
                        $new_options[ $option_key ] = $value;
                    }
                }
            }
        }

        // Apply typography
        if ( isset( $preset['typography'] ) ) {
            if ( isset( $preset['typography']['body_font'] ) ) {
                $new_options['body_typography']['font-family'] = $preset['typography']['body_font'];
            }
            if ( isset( $preset['typography']['heading_font'] ) ) {
                $new_options['heading_typography']['font-family'] = $preset['typography']['heading_font'];
            }
        }

        // Store active preset ID
        $new_options['active_preset'] = $preset_id;

        return update_option( 'ytrip_settings', $new_options );
    }

    /**
     * Export current settings as a preset JSON
     *
     * @param string $name   Custom preset name
     * @param string $desc   Custom preset description
     * @return string JSON encoded preset
     */
    public function export_current_settings( string $name = '', string $desc = '' ) {
        $export = array(
            'name'        => $name ?: __( 'Custom Preset', 'ytrip' ),
            'description' => $desc ?: __( 'Exported custom preset', 'ytrip' ),
            'version'     => YTRIP_VERSION,
            'exported_at' => current_time( 'mysql' ),
            'colors'      => array(),
            'tokens'      => array(),
            'typography'  => array(),
        );

        // Export colors
        $color_keys = array( 'primary', 'secondary', 'accent', 'background', 'surface', 'text' );
        foreach ( $color_keys as $key ) {
            $option_key = "custom_colors_{$key}";
            if ( isset( $this->options[ $option_key ] ) ) {
                $export['colors'][ $key ] = $this->options[ $option_key ];
            }
        }

        // Export design tokens
        $categories = array( 'cards', 'buttons', 'forms', 'modals', 'headers', 'footers' );
        foreach ( $categories as $category ) {
            $export['tokens'][ $category ] = array();
            foreach ( $this->options as $key => $value ) {
                if ( strpos( $key, "design_tokens_{$category}_" ) === 0 ) {
                    // Parse the key to extract component and property
                    $parts = explode( '_', str_replace( "design_tokens_{$category}_", '', $key ) );
                    if ( count( $parts ) >= 2 ) {
                        $component = $parts[0];
                        $property  = implode( '_', array_slice( $parts, 1 ) );
                        
                        if ( ! isset( $export['tokens'][ $category ][ $component ] ) ) {
                            $export['tokens'][ $category ][ $component ] = array();
                        }
                        $export['tokens'][ $category ][ $component ][ $property ] = $value;
                    }
                }
            }
        }

        // Export typography
        if ( isset( $this->options['body_typography']['font-family'] ) ) {
            $export['typography']['body_font'] = $this->options['body_typography']['font-family'];
        }
        if ( isset( $this->options['heading_typography']['font-family'] ) ) {
            $export['typography']['heading_font'] = $this->options['heading_typography']['font-family'];
        }

        return wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Import a preset from JSON
     *
     * @param string $json JSON encoded preset
     * @return bool|WP_Error Success status or error
     */
    public function import_preset( string $json ) {
        $preset = json_decode( $json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error( 'invalid_json', __( 'Invalid JSON format', 'ytrip' ) );
        }

        // Validate required fields
        if ( ! isset( $preset['colors'] ) && ! isset( $preset['tokens'] ) ) {
            return new \WP_Error( 'invalid_preset', __( 'Preset must contain colors or tokens', 'ytrip' ) );
        }

        $new_options = $this->options;

        // Import colors
        if ( isset( $preset['colors'] ) && is_array( $preset['colors'] ) ) {
            foreach ( $preset['colors'] as $key => $value ) {
                if ( $this->is_valid_color( $value ) ) {
                    $new_options[ "custom_colors_{$key}" ] = sanitize_hex_color( $value );
                }
            }
            $new_options['color_preset'] = 'custom';
        }

        // Import design tokens
        if ( isset( $preset['tokens'] ) && is_array( $preset['tokens'] ) ) {
            foreach ( $preset['tokens'] as $category => $components ) {
                if ( is_array( $components ) ) {
                    foreach ( $components as $component => $properties ) {
                        if ( is_array( $properties ) ) {
                            foreach ( $properties as $property => $value ) {
                                $option_key = "design_tokens_{$category}_{$component}_{$property}";
                                $new_options[ $option_key ] = $this->sanitize_token_value( $value );
                            }
                        }
                    }
                }
            }
        }

        // Import typography
        if ( isset( $preset['typography'] ) && is_array( $preset['typography'] ) ) {
            if ( isset( $preset['typography']['body_font'] ) ) {
                $new_options['body_typography']['font-family'] = sanitize_text_field( $preset['typography']['body_font'] );
            }
            if ( isset( $preset['typography']['heading_font'] ) ) {
                $new_options['heading_typography']['font-family'] = sanitize_text_field( $preset['typography']['heading_font'] );
            }
        }

        $new_options['active_preset'] = 'custom';

        if ( update_option( 'ytrip_settings', $new_options ) ) {
            return true;
        }

        return new \WP_Error( 'save_failed', __( 'Failed to save preset', 'ytrip' ) );
    }

    /**
     * Check if a color value is valid
     *
     * @param string $color Color to validate
     * @return bool Is valid color
     */
    private function is_valid_color( string $color ) {
        // Hex color
        if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $color ) ) {
            return true;
        }
        // RGB/RGBA
        if ( preg_match( '/^rgba?\([\d\s,.%]+\)$/', $color ) ) {
            return true;
        }
        return false;
    }

    /**
     * Sanitize a token value
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
            return sanitize_text_field( $value );
        }
        return $value;
    }

    /**
     * AJAX handler: Apply preset
     *
     * @return void
     */
    public function ajax_apply_preset() {
        check_ajax_referer( 'ytrip_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'ytrip' ) );
        }

        $preset_id = isset( $_POST['preset_id'] ) ? sanitize_key( $_POST['preset_id'] ) : '';

        if ( empty( $preset_id ) ) {
            wp_send_json_error( __( 'Invalid preset ID', 'ytrip' ) );
        }

        if ( $this->apply_preset( $preset_id ) ) {
            wp_send_json_success( array(
                'message' => __( 'Preset applied successfully!', 'ytrip' ),
                'preset'  => $this->get_preset( $preset_id ),
            ) );
        }

        wp_send_json_error( __( 'Failed to apply preset', 'ytrip' ) );
    }

    /**
     * AJAX handler: Export preset
     *
     * @return void
     */
    public function ajax_export_preset() {
        check_ajax_referer( 'ytrip_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'ytrip' ) );
        }

        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $desc = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';

        $json = $this->export_current_settings( $name, $desc );

        wp_send_json_success( array(
            'json' => $json,
            'filename' => 'ytrip-preset-' . sanitize_file_name( $name ?: 'custom' ) . '.json',
        ) );
    }

    /**
     * AJAX handler: Import preset
     *
     * @return void
     */
    public function ajax_import_preset() {
        check_ajax_referer( 'ytrip_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'ytrip' ) );
        }

        $json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';

        if ( empty( $json ) ) {
            wp_send_json_error( __( 'No preset data provided', 'ytrip' ) );
        }

        $result = $this->import_preset( $json );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( __( 'Preset imported successfully!', 'ytrip' ) );
    }

    /**
     * Get current active preset ID
     *
     * @return string Active preset ID or 'custom'
     */
    public function get_active_preset() {
        return $this->options['active_preset'] ?? 'custom';
    }

    /**
     * Check if a preset is active
     *
     * @param string $preset_id Preset to check
     * @return bool Is active
     */
    public function is_preset_active( string $preset_id ) {
        return $this->get_active_preset() === $preset_id;
    }
}

// Initialize
new YTrip_Design_Presets();

/**
 * Helper function to get preset instance
 *
 * @return YTrip_Design_Presets
 */
function ytrip_get_design_presets(): YTrip_Design_Presets {
    static $instance = null;
    if ( $instance === null ) {
        $instance = new YTrip_Design_Presets();
    }
    return $instance;
}
