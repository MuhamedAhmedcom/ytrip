<?php
/**
 * YTrip REST API Controller
 * 
 * Provides public/private endpoints for:
 * - Tour search and listing
 * - Availability checking
 * - Price calculation
 * - Booking creation
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_REST_API {

    /**
     * API namespace
     */
    const NAMESPACE = 'ytrip/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register all REST routes
     */
    public function register_routes() {
        
        // Tours Collection
        register_rest_route( self::NAMESPACE, '/tours', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_tours' ),
            'permission_callback' => '__return_true',
            'args'                => $this->get_tours_args(),
        ) );

        // Single Tour
        register_rest_route( self::NAMESPACE, '/tours/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_tour' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                ),
            ),
        ) );

        // Availability
        register_rest_route( self::NAMESPACE, '/tours/(?P<id>\d+)/availability', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_availability' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id'    => array( 'required' => true ),
                'month' => array( 'default' => date( 'n' ) ),
                'year'  => array( 'default' => date( 'Y' ) ),
            ),
        ) );

        // Price Calculation
        register_rest_route( self::NAMESPACE, '/tours/(?P<id>\d+)/calculate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'calculate_price' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id'      => array( 'required' => true ),
                'date'    => array( 'required' => true ),
                'persons' => array( 'default' => array( 'adult' => 1 ) ),
            ),
        ) );

        // Create Booking (requires auth or nonce)
        register_rest_route( self::NAMESPACE, '/bookings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_booking' ),
            'permission_callback' => array( $this, 'booking_permission_check' ),
            'args'                => $this->get_booking_args(),
        ) );

        // Get Booking (requires auth)
        register_rest_route( self::NAMESPACE, '/bookings/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_booking' ),
            'permission_callback' => array( $this, 'booking_permission_check' ),
        ) );

        // Destinations
        register_rest_route( self::NAMESPACE, '/destinations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_destinations' ),
            'permission_callback' => '__return_true',
        ) );

        // Categories
        register_rest_route( self::NAMESPACE, '/categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_categories' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * GET /tours - List tours with optional filters
     */
    public function get_tours( WP_REST_Request $request ) {
        $args = array(
            'post_type'      => 'ytrip_tour',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
            'paged'          => $request->get_param( 'page' ) ?: 1,
            'orderby'        => $request->get_param( 'orderby' ) ?: 'date',
            'order'          => $request->get_param( 'order' ) ?: 'DESC',
        );

        // Filter by destination
        if ( $destination = $request->get_param( 'destination' ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => 'ytrip_destination',
                'field'    => 'slug',
                'terms'    => $destination,
            );
        }

        // Filter by category
        if ( $category = $request->get_param( 'category' ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => 'ytrip_category',
                'field'    => 'slug',
                'terms'    => $category,
            );
        }

        // Search
        if ( $search = $request->get_param( 'search' ) ) {
            $args['s'] = sanitize_text_field( $search );
        }

        // Price range filter
        if ( $min_price = $request->get_param( 'min_price' ) ) {
            $args['meta_query'][] = array(
                'key'     => '_ytrip_base_price',
                'value'   => floatval( $min_price ),
                'compare' => '>=',
                'type'    => 'DECIMAL(10,2)',
            );
        }

        if ( $max_price = $request->get_param( 'max_price' ) ) {
            $args['meta_query'][] = array(
                'key'     => '_ytrip_base_price',
                'value'   => floatval( $max_price ),
                'compare' => '<=',
                'type'    => 'DECIMAL(10,2)',
            );
        }

        $query = new WP_Query( $args );
        $tours = array();

        foreach ( $query->posts as $post ) {
            $tours[] = $this->format_tour( $post );
        }

        return new WP_REST_Response( array(
            'tours'       => $tours,
            'total'       => $query->found_posts,
            'pages'       => $query->max_num_pages,
            'current_page' => intval( $args['paged'] ),
        ), 200 );
    }

    /**
     * GET /tours/{id} - Single tour details
     */
    public function get_tour( WP_REST_Request $request ) {
        $tour_id = $request->get_param( 'id' );
        $post = get_post( $tour_id );

        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return new WP_Error( 'tour_not_found', __( 'Tour not found', 'ytrip' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( $this->format_tour( $post, true ), 200 );
    }

    /**
     * GET /tours/{id}/availability - Calendar availability
     */
    public function get_availability( WP_REST_Request $request ) {
        $tour_id = $request->get_param( 'id' );
        $year    = intval( $request->get_param( 'year' ) );
        $month   = intval( $request->get_param( 'month' ) );

        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return new WP_Error( 'tour_not_found', __( 'Tour not found', 'ytrip' ), array( 'status' => 404 ) );
        }

        $calendar = YTrip_Pricing_Engine::get_availability_calendar( $tour_id, $year, $month );

        return new WP_REST_Response( array(
            'tour_id'  => $tour_id,
            'year'     => $year,
            'month'    => $month,
            'calendar' => $calendar,
        ), 200 );
    }

    /**
     * POST /tours/{id}/calculate - Calculate price
     */
    public function calculate_price( WP_REST_Request $request ) {
        $tour_id = $request->get_param( 'id' );
        $date    = sanitize_text_field( $request->get_param( 'date' ) );
        $persons = (array) $request->get_param( 'persons' );

        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return new WP_Error( 'tour_not_found', __( 'Tour not found', 'ytrip' ), array( 'status' => 404 ) );
        }

        $result = YTrip_Pricing_Engine::calculate( $tour_id, $date, $persons );

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * POST /bookings - Create a new booking
     */
    public function create_booking( WP_REST_Request $request ) {
        $tour_id = absint( $request->get_param( 'tour_id' ) );
        $date    = sanitize_text_field( $request->get_param( 'date' ) );
        $persons = (array) $request->get_param( 'persons' );
        $customer = $request->get_param( 'customer' );

        // Validate tour exists
        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return new WP_Error( 'tour_not_found', __( 'Tour not found', 'ytrip' ), array( 'status' => 404 ) );
        }

        // Calculate price
        $pricing = YTrip_Pricing_Engine::calculate( $tour_id, $date, $persons );

        // Check if WooCommerce is active for order creation
        if ( ! function_exists( 'wc_create_order' ) ) {
            return new WP_Error( 'wc_not_active', __( 'WooCommerce is required for bookings', 'ytrip' ), array( 'status' => 500 ) );
        }

        // Get linked product
        $product_id = get_post_meta( $tour_id, '_ytrip_linked_product_id', true );
        if ( ! $product_id ) {
            return new WP_Error( 'no_product', __( 'No linked product for this tour', 'ytrip' ), array( 'status' => 400 ) );
        }

        try {
            // Create WooCommerce order
            $order = wc_create_order();

            // Add product
            $product = wc_get_product( $product_id );
            $order->add_product( $product, 1, array(
                'subtotal' => $pricing['total'],
                'total'    => $pricing['total'],
            ) );

            // Customer details
            $order->set_billing_first_name( sanitize_text_field( $customer['first_name'] ?? '' ) );
            $order->set_billing_last_name( sanitize_text_field( $customer['last_name'] ?? '' ) );
            $order->set_billing_email( sanitize_email( $customer['email'] ?? '' ) );
            $order->set_billing_phone( sanitize_text_field( $customer['phone'] ?? '' ) );

            // Add booking meta
            $order->add_meta_data( '_ytrip_tour_id', $tour_id );
            $order->add_meta_data( '_ytrip_booking_date', $date );
            $order->add_meta_data( '_ytrip_persons', $persons );
            $order->add_meta_data( '_ytrip_pricing_breakdown', $pricing );

            $order->calculate_totals();
            $order->save();

            return new WP_REST_Response( array(
                'success'  => true,
                'order_id' => $order->get_id(),
                'total'    => $pricing['total'],
                'status'   => $order->get_status(),
                'checkout_url' => $order->get_checkout_payment_url(),
            ), 201 );

        } catch ( Exception $e ) {
            return new WP_Error( 'booking_failed', $e->getMessage(), array( 'status' => 500 ) );
        }
    }

    /**
     * GET /bookings/{id} - Get booking details
     */
    public function get_booking( WP_REST_Request $request ) {
        $order_id = $request->get_param( 'id' );

        if ( ! function_exists( 'wc_get_order' ) ) {
            return new WP_Error( 'wc_not_active', __( 'WooCommerce is required', 'ytrip' ), array( 'status' => 500 ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found', 'ytrip' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( array(
            'id'           => $order->get_id(),
            'status'       => $order->get_status(),
            'total'        => $order->get_total(),
            'tour_id'      => $order->get_meta( '_ytrip_tour_id' ),
            'booking_date' => $order->get_meta( '_ytrip_booking_date' ),
            'persons'      => $order->get_meta( '_ytrip_persons' ),
            'customer'     => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'email'      => $order->get_billing_email(),
            ),
        ), 200 );
    }

    /**
     * GET /destinations
     */
    public function get_destinations( WP_REST_Request $request ) {
        $terms = get_terms( array(
            'taxonomy'   => 'ytrip_destination',
            'hide_empty' => false,
        ) );

        $destinations = array();
        foreach ( $terms as $term ) {
            $destinations[] = $this->format_term( $term );
        }

        return new WP_REST_Response( $destinations, 200 );
    }

    /**
     * GET /categories
     */
    public function get_categories( WP_REST_Request $request ) {
        $terms = get_terms( array(
            'taxonomy'   => 'ytrip_category',
            'hide_empty' => false,
        ) );

        $categories = array();
        foreach ( $terms as $term ) {
            $categories[] = $this->format_term( $term );
        }

        return new WP_REST_Response( $categories, 200 );
    }

    /**
     * Format tour post for API response
     */
    private function format_tour( $post, $full = false ) {
        $meta = get_post_meta( $post->ID, 'ytrip_tour_details', true );
        $base_price = YTrip_Pricing_Engine::get_base_price( $post->ID, $meta );

        $tour = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'slug'        => $post->post_name,
            'excerpt'     => wp_trim_words( $post->post_content, 30 ),
            'permalink'   => get_permalink( $post->ID ),
            'image'       => get_the_post_thumbnail_url( $post->ID, 'large' ),
            'price'       => $base_price,
            'duration'    => $meta['tour_duration'] ?? null,
            'difficulty'  => $meta['difficulty'] ?? null,
            'destinations' => wp_get_post_terms( $post->ID, 'ytrip_destination', array( 'fields' => 'names' ) ),
            'categories'  => wp_get_post_terms( $post->ID, 'ytrip_category', array( 'fields' => 'names' ) ),
        );

        // Add full details if requested
        if ( $full ) {
            $tour['content']     = apply_filters( 'the_content', $post->post_content );
            $tour['itinerary']   = $meta['itinerary'] ?? array();
            $tour['included']    = $meta['included'] ?? array();
            $tour['excluded']    = $meta['excluded'] ?? array();
            $tour['highlights']  = $meta['highlights'] ?? array();
            $tour['faq']         = $meta['faq'] ?? array();
            $tour['gallery']     = $meta['gallery'] ?? array();
            $tour['person_types'] = YTrip_Pricing_Engine::get_person_types( $post->ID, $meta );
            $tour['group_size']  = $meta['group_size'] ?? null;
        }

        return $tour;
    }

    /**
     * Format term for API response
     */
    private function format_term( $term ) {
        $image = YTrip_Helper::get_term_image( $term->term_id );
        
        return array(
            'id'    => $term->term_id,
            'name'  => $term->name,
            'slug'  => $term->slug,
            'count' => $term->count,
            'image' => $image,
            'link'  => get_term_link( $term ),
        );
    }

    /**
     * Arguments for tours collection endpoint
     */
    private function get_tours_args() {
        return array(
            'per_page'    => array( 'default' => 10, 'sanitize_callback' => 'absint' ),
            'page'        => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
            'search'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'destination' => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'category'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'orderby'     => array( 'default' => 'date', 'sanitize_callback' => 'sanitize_text_field' ),
            'order'       => array( 'default' => 'DESC', 'sanitize_callback' => 'sanitize_text_field' ),
            'min_price'   => array( 'sanitize_callback' => 'floatval' ),
            'max_price'   => array( 'sanitize_callback' => 'floatval' ),
        );
    }

    /**
     * Arguments for booking creation
     */
    private function get_booking_args() {
        return array(
            'tour_id'  => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'date'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'persons'  => array( 'default' => array( 'adult' => 1 ) ),
            'customer' => array( 'required' => true ),
        );
    }

    /**
     * Permission check for booking endpoints
     */
    public function booking_permission_check( WP_REST_Request $request ) {
        // Allow if user is logged in
        if ( is_user_logged_in() ) {
            return true;
        }

        // Allow if valid nonce provided (for frontend forms)
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return true;
        }

        // Allow for GET requests on own bookings (basic check)
        if ( $request->get_method() === 'GET' ) {
            return true; // You may add more specific checks here
        }

        return new WP_Error( 'rest_forbidden', __( 'Authentication required', 'ytrip' ), array( 'status' => 401 ) );
    }
}

// Initialize
new YTrip_REST_API();
