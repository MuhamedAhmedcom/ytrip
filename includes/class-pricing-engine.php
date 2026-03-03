<?php
/**
 * YTrip Dynamic Pricing Engine
 * 
 * Handles all pricing calculations including:
 * - Seasonal pricing (date-based modifiers)
 * - Person type pricing (Adult, Child, Infant)
 * - Group discounts (quantity-based)
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Pricing_Engine {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'ytrip_tour_price', array( $this, 'filter_tour_price' ), 10, 3 );
    }

    /**
     * Calculate the final price for a tour booking
     * 
     * @param int    $tour_id     Tour post ID.
     * @param string $date        Booking date (Y-m-d format).
     * @param array  $persons     Array of person counts: ['adult' => 2, 'child' => 1, 'infant' => 0].
     * @return array {
     *     @type float  $base_price      Original base price.
     *     @type float  $seasonal_price  Price after seasonal adjustment.
     *     @type float  $subtotal        Total before group discount.
     *     @type float  $discount        Group discount amount.
     *     @type float  $total           Final price to charge.
     *     @type array  $breakdown       Detailed breakdown per person type.
     * }
     */
    public static function calculate( $tour_id, $date = '', $persons = array() ) {
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        
        // Default persons if empty
        $persons = wp_parse_args( $persons, array(
            'adult'  => 1,
            'child'  => 0,
            'infant' => 0,
        ) );

        // Get base price
        $base_price = self::get_base_price( $tour_id, $meta );
        
        // Apply seasonal adjustment
        $seasonal_price = self::apply_seasonal_pricing( $base_price, $tour_id, $date, $meta );
        
        // Calculate per-person-type prices
        $person_types = self::get_person_types( $tour_id, $meta );
        $breakdown = self::calculate_person_breakdown( $seasonal_price, $persons, $person_types );
        
        // Sum subtotal
        $subtotal = array_sum( array_column( $breakdown, 'total' ) );
        
        // Apply group discount
        $total_persons = array_sum( $persons );
        $group_discount = self::calculate_group_discount( $subtotal, $total_persons, $tour_id, $meta );
        
        $total = $subtotal - $group_discount;

        return array(
            'base_price'      => $base_price,
            'seasonal_price'  => $seasonal_price,
            'subtotal'        => $subtotal,
            'discount'        => $group_discount,
            'total'           => max( 0, $total ),
            'breakdown'       => $breakdown,
            'currency'        => get_woocommerce_currency_symbol(),
        );
    }

    /**
     * Get base price from meta or linked WooCommerce product
     */
    public static function get_base_price( $tour_id, $meta = null ) {
        if ( is_null( $meta ) ) {
            $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        }

        // Check if tour has explicit price
        if ( ! empty( $meta['price_settings']['tour_price'] ) ) {
            return floatval( $meta['price_settings']['tour_price'] );
        }

        // Fallback to linked WooCommerce product
        $product_id = get_post_meta( $tour_id, '_ytrip_linked_product_id', true );
        if ( $product_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                return floatval( $product->get_regular_price() );
            }
        }

        return 0.00;
    }

    /**
     * Apply seasonal pricing adjustments
     */
    public static function apply_seasonal_pricing( $base_price, $tour_id, $date, $meta = null ) {
        if ( empty( $date ) ) {
            return $base_price;
        }

        if ( is_null( $meta ) ) {
            $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        }

        $seasonal_rules = ! empty( $meta['seasonal_pricing'] ) ? $meta['seasonal_pricing'] : array();
        
        if ( empty( $seasonal_rules ) ) {
            return $base_price;
        }

        $booking_date = strtotime( $date );
        $adjusted_price = $base_price;

        foreach ( $seasonal_rules as $rule ) {
            if ( empty( $rule['start_date'] ) || empty( $rule['end_date'] ) ) {
                continue;
            }

            $start = strtotime( $rule['start_date'] );
            $end   = strtotime( $rule['end_date'] );

            if ( $booking_date >= $start && $booking_date <= $end ) {
                $modifier_type  = ! empty( $rule['modifier_type'] ) ? $rule['modifier_type'] : 'percentage';
                $modifier_value = floatval( $rule['modifier_value'] ?? 0 );

                if ( $modifier_type === 'percentage' ) {
                    // Positive = increase, Negative = decrease
                    $adjusted_price = $base_price * ( 1 + ( $modifier_value / 100 ) );
                } else {
                    // Fixed amount adjustment
                    $adjusted_price = $base_price + $modifier_value;
                }

                // First matching rule wins (priority by order)
                break;
            }
        }

        return max( 0, $adjusted_price );
    }

    /**
     * Get person types configuration
     */
    public static function get_person_types( $tour_id, $meta = null ) {
        if ( is_null( $meta ) ) {
            $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        }

        $custom_types = ! empty( $meta['person_types'] ) ? $meta['person_types'] : array();

        // Default types if none configured
        if ( empty( $custom_types ) ) {
            return array(
                array(
                    'type_key'       => 'adult',
                    'type_label'     => __( 'Adult', 'ytrip' ),
                    'modifier_type'  => 'percentage',
                    'modifier_value' => 0, // 100% of base price
                    'min_age'        => 12,
                    'max_age'        => 99,
                ),
                array(
                    'type_key'       => 'child',
                    'type_label'     => __( 'Child', 'ytrip' ),
                    'modifier_type'  => 'percentage',
                    'modifier_value' => -50, // 50% discount
                    'min_age'        => 3,
                    'max_age'        => 11,
                ),
                array(
                    'type_key'       => 'infant',
                    'type_label'     => __( 'Infant', 'ytrip' ),
                    'modifier_type'  => 'percentage',
                    'modifier_value' => -100, // Free
                    'min_age'        => 0,
                    'max_age'        => 2,
                ),
            );
        }

        return $custom_types;
    }

    /**
     * Calculate price breakdown per person type
     */
    public static function calculate_person_breakdown( $base_price, $persons, $person_types ) {
        $breakdown = array();

        foreach ( $person_types as $type ) {
            $type_key = sanitize_key( $type['type_key'] ?? $type['type_label'] ?? 'unknown' );
            $count    = isset( $persons[ $type_key ] ) ? intval( $persons[ $type_key ] ) : 0;

            if ( $count <= 0 ) {
                continue;
            }

            $modifier_type  = $type['modifier_type'] ?? 'percentage';
            $modifier_value = floatval( $type['modifier_value'] ?? 0 );

            if ( $modifier_type === 'percentage' ) {
                $unit_price = $base_price * ( 1 + ( $modifier_value / 100 ) );
            } else {
                $unit_price = $base_price + $modifier_value;
            }

            $unit_price = max( 0, $unit_price );

            $breakdown[ $type_key ] = array(
                'label'      => $type['type_label'] ?? ucfirst( $type_key ),
                'unit_price' => $unit_price,
                'quantity'   => $count,
                'total'      => $unit_price * $count,
            );
        }

        return $breakdown;
    }

    /**
     * Calculate group discount
     */
    public static function calculate_group_discount( $subtotal, $total_persons, $tour_id, $meta = null ) {
        if ( is_null( $meta ) ) {
            $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        }

        $group_rules = ! empty( $meta['group_discounts'] ) ? $meta['group_discounts'] : array();

        if ( empty( $group_rules ) ) {
            return 0;
        }

        // Sort by min_persons descending to get highest applicable discount
        usort( $group_rules, function( $a, $b ) {
            return ( $b['min_persons'] ?? 0 ) - ( $a['min_persons'] ?? 0 );
        } );

        foreach ( $group_rules as $rule ) {
            $min_persons = intval( $rule['min_persons'] ?? 0 );
            
            if ( $total_persons >= $min_persons ) {
                $discount_type  = $rule['discount_type'] ?? 'percentage';
                $discount_value = floatval( $rule['discount_value'] ?? 0 );

                if ( $discount_type === 'percentage' ) {
                    return $subtotal * ( $discount_value / 100 );
                } else {
                    return min( $discount_value, $subtotal ); // Can't discount more than subtotal
                }
            }
        }

        return 0;
    }

    /**
     * Filter hook for tour price (can be used by other components)
     */
    public function filter_tour_price( $price, $tour_id, $context = 'display' ) {
        // If context is 'calculate', perform full calculation
        if ( $context === 'calculate' && isset( $_POST['ytrip_date'], $_POST['ytrip_persons'] ) ) {
            $date    = sanitize_text_field( wp_unslash( $_POST['ytrip_date'] ) );
            $persons = array_map( 'intval', (array) $_POST['ytrip_persons'] );
            
            $result = self::calculate( $tour_id, $date, $persons );
            return $result['total'];
        }

        return $price;
    }

    /**
     * AJAX handler for price calculation
     */
    public static function ajax_calculate_price() {
        check_ajax_referer( 'ytrip_nonce', 'nonce' );

        $tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
        $date    = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
        $persons = isset( $_POST['persons'] ) ? array_map( 'intval', (array) $_POST['persons'] ) : array();

        if ( ! $tour_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid tour ID', 'ytrip' ) ) );
        }

        $result = self::calculate( $tour_id, $date, $persons );

        wp_send_json_success( $result );
    }

    /**
     * Get availability calendar data for a tour
     */
    public static function get_availability_calendar( $tour_id, $year, $month ) {
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        $base_price = self::get_base_price( $tour_id, $meta );

        $calendar = array();
        $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );

        for ( $day = 1; $day <= $days_in_month; $day++ ) {
            $date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
            $price = self::apply_seasonal_pricing( $base_price, $tour_id, $date, $meta );

            // Check if date is blocked
            $blocked_dates = ! empty( $meta['blocked_dates'] ) ? $meta['blocked_dates'] : array();
            $is_blocked = in_array( $date, $blocked_dates, true );

            $calendar[ $date ] = array(
                'price'     => $price,
                'available' => ! $is_blocked,
                'is_peak'   => $price > $base_price,
            );
        }

        return $calendar;
    }
}

// Initialize
YTrip_Pricing_Engine::instance();

// Register AJAX handlers
add_action( 'wp_ajax_ytrip_calculate_price', array( 'YTrip_Pricing_Engine', 'ajax_calculate_price' ) );
add_action( 'wp_ajax_nopriv_ytrip_calculate_price', array( 'YTrip_Pricing_Engine', 'ajax_calculate_price' ) );
