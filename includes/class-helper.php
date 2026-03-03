<?php
/**
 * YTrip Helper Functions
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Helper {

    /**
     * Get term meta key for taxonomy (CSF serialize key).
     *
     * @param string $taxonomy Taxonomy slug.
     * @return string Meta key.
     */
    private static function get_term_meta_key( $taxonomy ) {
        if ( $taxonomy === 'ytrip_category' ) {
            return 'ytrip_category_meta';
        }
        return 'ytrip_destination_meta';
    }

    /**
     * Get Term Image URL (custom image for homepage/cards).
     *
     * @param int    $term_id   Term ID.
     * @param string $taxonomy Taxonomy slug (ytrip_destination or ytrip_category).
     * @param string $size     Image size (for attachment URL; media field stores id/url).
     * @return string Image URL.
     */
    public static function get_term_image( $term_id, $taxonomy = 'ytrip_destination', $size = 'large' ) {
        $meta_key = self::get_term_meta_key( $taxonomy );
        $meta     = get_term_meta( $term_id, $meta_key, true );

        if ( ! empty( $meta['image']['url'] ) ) {
            return $meta['image']['url'];
        }
        if ( ! empty( $meta['image']['id'] ) && is_numeric( $meta['image']['id'] ) ) {
            $url = wp_get_attachment_image_url( (int) $meta['image']['id'], $size );
            if ( $url ) {
                return $url;
            }
        }

        if ( $taxonomy === 'ytrip_destination' ) {
            $legacy_id = get_term_meta( $term_id, 'ytrip_destination_image', true );
            if ( $legacy_id && is_numeric( $legacy_id ) ) {
                $url = wp_get_attachment_image_url( (int) $legacy_id, $size );
                if ( $url ) {
                    return $url;
                }
            }
        }

        $options = get_option( 'ytrip_settings', array() );
        if ( ! empty( $options['default_term_image']['url'] ) ) {
            return $options['default_term_image']['url'];
        }

        return YTRIP_URL . 'assets/images/placeholder.png';
    }

    /**
     * Get Term Background URL (archive page header).
     *
     * @param int         $term_id   Term ID.
     * @param string|null $taxonomy  Taxonomy slug; if null, detected from term.
     * @return string URL or empty string.
     */
    public static function get_term_background( $term_id, $taxonomy = null ) {
        if ( $taxonomy === null ) {
            $term = get_term( $term_id );
            $taxonomy = ( $term && ! is_wp_error( $term ) ) ? $term->taxonomy : 'ytrip_destination';
        }

        $meta_key = self::get_term_meta_key( $taxonomy );
        $meta     = get_term_meta( $term_id, $meta_key, true );

        if ( ! empty( $meta['banner']['url'] ) ) {
            return $meta['banner']['url'];
        }
        if ( ! empty( $meta['banner']['id'] ) && is_numeric( $meta['banner']['id'] ) ) {
            $url = wp_get_attachment_image_url( (int) $meta['banner']['id'], 'full' );
            if ( $url ) {
                return $url;
            }
        }
        if ( ! empty( $meta['image']['url'] ) ) {
            return $meta['image']['url'];
        }
        if ( ! empty( $meta['image']['id'] ) && is_numeric( $meta['image']['id'] ) ) {
            $url = wp_get_attachment_image_url( (int) $meta['image']['id'], 'full' );
            if ( $url ) {
                return $url;
            }
        }

        $options = get_option( 'ytrip_settings', array() );
        if ( ! empty( $options['default_term_background']['url'] ) ) {
            return $options['default_term_background']['url'];
        }

        return '';
    }

    /**
     * Get Term Color (archive accent color).
     *
     * @param int         $term_id  Term ID.
     * @param string|null $taxonomy Taxonomy slug; if null, detected from term.
     * @return string Hex color or empty string.
     */
    public static function get_term_color( $term_id, $taxonomy = null ) {
        if ( $taxonomy === null ) {
            $term = get_term( $term_id );
            $taxonomy = ( $term && ! is_wp_error( $term ) ) ? $term->taxonomy : 'ytrip_destination';
        }

        $meta_key = self::get_term_meta_key( $taxonomy );
        $meta     = get_term_meta( $term_id, $meta_key, true );

        if ( ! empty( $meta['color'] ) && is_string( $meta['color'] ) ) {
            return sanitize_hex_color( $meta['color'] ) ?: '';
        }
        return '';
    }

    /**
     * Get Term Icon (e.g. for homepage destination cards).
     * CSF icon field usually returns array with 'type' and 'icon' (e.g. 'fa fa-star').
     *
     * @param int         $term_id  Term ID.
     * @param string|null $taxonomy Taxonomy slug; if null, detected from term.
     * @return string Icon class or empty string.
     */
    public static function get_term_icon( $term_id, $taxonomy = null ) {
        if ( $taxonomy === null ) {
            $term = get_term( $term_id );
            $taxonomy = ( $term && ! is_wp_error( $term ) ) ? $term->taxonomy : 'ytrip_destination';
        }

        $meta_key = self::get_term_meta_key( $taxonomy );
        $meta     = get_term_meta( $term_id, $meta_key, true );

        if ( ! empty( $meta['icon'] ) ) {
            if ( is_string( $meta['icon'] ) ) {
                return sanitize_html_class( $meta['icon'] );
            }
            if ( is_array( $meta['icon'] ) && ! empty( $meta['icon']['icon'] ) ) {
                return esc_attr( $meta['icon']['icon'] );
            }
        }
        return '';
    }
    /**
     * Get Currency Symbol
     */
    public static function get_currency_symbol() {
        if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
            return get_woocommerce_currency_symbol();
        }
        
        $options = get_option( 'ytrip_settings' );
        $currency = $options['currency'] ?? 'USD';
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => '$',
            'CAD' => '$',
        ];
        
        return $symbols[$currency] ?? '$';
    }

    /**
     * Format a numeric price for display (e.g. 26.00 €). Used when no WooCommerce product or for fallback.
     *
     * @param float|string $amount   Price amount.
     * @param string|null  $symbol  Currency symbol (default from settings).
     * @return string Formatted price like "26.00 €" or empty string if invalid.
     */
    public static function format_price_display( $amount, $symbol = null ) {
        $num = is_numeric( $amount ) ? floatval( $amount ) : null;
        if ( $num === null || $num < 0 ) {
            return '';
        }
        if ( $symbol === null && method_exists( __CLASS__, 'get_currency_symbol' ) ) {
            $symbol = self::get_currency_symbol();
        }
        $symbol = $symbol !== null && $symbol !== '' ? $symbol : '€';
        return number_format_i18n( $num, 2 ) . ' ' . $symbol;
    }

    /**
     * Format duration from tour meta (handles fieldset array or string).
     *
     * @param array $meta ytrip_tour_details meta.
     * @return string Human-readable duration or empty string.
     */
    public static function format_duration_from_meta( $meta ) {
        if ( ! is_array( $meta ) ) {
            return '';
        }
        $arr = isset( $meta['tour_duration'] ) && is_array( $meta['tour_duration'] ) ? $meta['tour_duration'] : ( isset( $meta['duration'] ) && is_array( $meta['duration'] ) ? $meta['duration'] : array() );
        $days  = isset( $arr['days'] ) ? max( 0, (int) $arr['days'] ) : 0;
        $nights = isset( $arr['nights'] ) ? max( 0, (int) $arr['nights'] ) : 0;
        $hours  = isset( $arr['hours'] ) ? max( 0, (int) $arr['hours'] ) : 0;
        if ( $days > 0 || $nights > 0 ) {
            $parts = array();
            if ( $days > 0 ) {
                $parts[] = sprintf( _n( '%d Day', '%d Days', $days, 'ytrip' ), $days );
            }
            if ( $nights > 0 ) {
                $parts[] = sprintf( _n( '%d Night', '%d Nights', $nights, 'ytrip' ), $nights );
            }
            return implode( ' / ', $parts );
        }
        if ( $hours > 0 ) {
            return sprintf( _n( '%d Hour', '%d Hours', $hours, 'ytrip' ), $hours );
        }
        if ( ! empty( $meta['duration'] ) && is_string( $meta['duration'] ) ) {
            return $meta['duration'];
        }
        return '';
    }

    /**
     * Format group size from tour meta (handles fieldset array or string).
     *
     * @param array $meta ytrip_tour_details meta.
     * @return string Human-readable group size or empty string.
     */
    public static function format_group_size_from_meta( $meta ) {
        if ( ! is_array( $meta ) ) {
            return '';
        }
        $gs = isset( $meta['group_size'] ) ? $meta['group_size'] : null;
        if ( is_array( $gs ) && ( isset( $gs['min'] ) || isset( $gs['max'] ) ) ) {
            $min = isset( $gs['min'] ) ? max( 0, (int) $gs['min'] ) : 1;
            $max = isset( $gs['max'] ) ? max( 0, (int) $gs['max'] ) : 50;
            if ( $min === $max ) {
                return (string) $min;
            }
            return $min . ' – ' . $max;
        }
        if ( is_string( $gs ) && $gs !== '' ) {
            return $gs;
        }
        return '';
    }

    /**
     * Get Dashicons class for a single-tour content tab (when "Show Icons in Content Tabs" is on).
     *
     * @param string $tab_slug Tab data-tab value (overview, itinerary, included, faq, location, etc.).
     * @return string Dashicons class, e.g. 'dashicons dashicons-visibility'.
     */
    public static function get_single_tour_tab_icon( $tab_slug ) {
        $slug = is_string( $tab_slug ) ? $tab_slug : '';
        $map  = array(
            'overview'      => 'dashicons-visibility',
            'itinerary'     => 'dashicons-list-view',
            'included'      => 'dashicons-yes-alt',
            'faq'           => 'dashicons-editor-help',
            'location'      => 'dashicons-location-alt',
            'know-before'   => 'dashicons-info',
            'what-to-bring' => 'dashicons-portfolio',
            'cancellation'  => 'dashicons-no-alt',
            'route'         => 'dashicons-chart-line',
        );
        $icon = isset( $map[ $slug ] ) ? $map[ $slug ] : 'dashicons-admin-generic';
        return 'dashicons ' . $icon;
    }
}
