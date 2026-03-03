<?php
/**
 * Archive Filters Class
 * Handles filtering, sorting, and view modes for tour archives
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class YTrip_Archive_Filters {

    private $options;

    /** Transient TTL for date availability cache (seconds). */
    const AVAIL_CACHE_TTL = 600;

    /** Transient TTL for filter dropdown data (seconds). */
    const FILTER_DATA_CACHE_TTL = 43200;

    public function __construct() {
        $this->options = get_option( 'ytrip_settings' );
        
        add_action( 'pre_get_posts', array( $this, 'modify_archive_query' ), 999 );
        add_filter( 'posts_clauses', array( $this, 'orderby_meta_include_missing' ), 10, 2 );
        add_action( 'wp_ajax_ytrip_filter_tours', array( $this, 'ajax_filter_tours' ) );
        add_action( 'wp_ajax_nopriv_ytrip_filter_tours', array( $this, 'ajax_filter_tours' ) );
        add_action( 'save_post_ytrip_tour', array( __CLASS__, 'invalidate_caches' ), 20 );
        add_action( 'edited_ytrip_destination', array( __CLASS__, 'invalidate_filter_data_cache' ) );
        add_action( 'edited_ytrip_category', array( __CLASS__, 'invalidate_filter_data_cache' ) );
    }

    /**
     * Invalidate date-availability and filter-data caches (e.g. after tour save).
     */
    public static function invalidate_caches() {
        self::invalidate_availability_caches();
        self::invalidate_filter_data_cache();
    }

    /**
     * Delete all transients used for date/range availability.
     */
    public static function invalidate_availability_caches() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ytrip_avail_%' OR option_name LIKE '_transient_timeout_ytrip_avail_%'" );
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'ytrip_avail' );
        }
    }

    /**
     * Delete filter dropdown data transient.
     */
    public static function invalidate_filter_data_cache() {
        delete_transient( 'ytrip_filter_data' );
    }

    /**
     * When ordering by price/rating/views, order by meta value using the meta_query join (mt1)
     * so posts without the meta key are still included (LEFT JOIN). Only runs when ytrip_meta_order is set.
     *
     * @param array    $clauses Query clauses.
     * @param WP_Query $query   Query object.
     * @return array
     */
    public function orderby_meta_include_missing( $clauses, $query ) {
        $meta_order = $query->get( 'ytrip_meta_order' );
        if ( ! is_array( $meta_order ) || empty( $meta_order['order'] ) ) {
            return $clauses;
        }
        $order = strtoupper( $meta_order['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        $clauses['orderby'] = "CAST(COALESCE(mt1.meta_value, 0) AS UNSIGNED) " . $order;
        return $clauses;
    }

    /**
     * Modify main archive query based on URL params
     */
    public function modify_archive_query( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( ! is_post_type_archive( 'ytrip_tour' ) && ! is_tax( 'ytrip_destination' ) && ! is_tax( 'ytrip_category' ) ) {
            return;
        }

        // Posts per page from settings
        $per_page = $this->options['archive_per_page'] ?? 12;
        $query->set( 'posts_per_page', $per_page );

        // Sorting
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

        switch ( $orderby ) {
            case 'price_low':
                $query->set( 'ytrip_meta_order', array( 'order' => 'ASC' ) );
                break;
            case 'price_high':
                $query->set( 'ytrip_meta_order', array( 'order' => 'DESC' ) );
                break;
            case 'rating':
                $query->set( 'ytrip_meta_order', array( 'order' => 'DESC' ) );
                break;
            case 'popularity':
                $query->set( 'ytrip_meta_order', array( 'order' => 'DESC' ) );
                break;
            case 'title':
                $query->set( 'orderby', 'title' );
                $query->set( 'order', $order );
                break;
            default:
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'DESC' );
        }

        // Meta query for filters
        $meta_query = array();

        // Price range filter (use floatval for decimal prices; single BETWEEN when both set)
        $min_price = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : null;
        $max_price = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : null;
        if ( $min_price !== null || $max_price !== null ) {
            if ( $min_price !== null && $max_price !== null && $min_price <= $max_price ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_price',
                    'value'   => array( $min_price, $max_price ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DECIMAL',
                );
            } else {
                if ( $min_price !== null ) {
                    $meta_query[] = array(
                        'key'     => '_ytrip_price',
                        'value'   => $min_price,
                        'compare' => '>=',
                        'type'    => 'DECIMAL',
                    );
                }
                if ( $max_price !== null ) {
                    $meta_query[] = array(
                        'key'     => '_ytrip_price',
                        'value'   => $max_price,
                        'compare' => '<=',
                        'type'    => 'DECIMAL',
                    );
                }
            }
        }

        // Duration filter (whitelist: 1-3, 4-7, 8-14, 15+)
        if ( ! empty( $_GET['duration'] ) ) {
            $duration = sanitize_text_field( wp_unslash( $_GET['duration'] ) );
            if ( $duration === '15%2B' ) {
                $duration = '15+';
            }
            $duration_ranges = array(
                '1-3'  => array( 1, 3 ),
                '4-7'  => array( 4, 7 ),
                '8-14' => array( 8, 14 ),
            );
            if ( isset( $duration_ranges[ $duration ] ) ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_duration_days',
                    'value'   => $duration_ranges[ $duration ],
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC',
                );
            } elseif ( $duration === '15+' ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_duration_days',
                    'value'   => 15,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        // Rating filter (3–5 only)
        if ( isset( $_GET['rating'] ) && $_GET['rating'] !== '' ) {
            $rating = absint( $_GET['rating'] );
            if ( $rating >= 3 && $rating <= 5 ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_rating',
                    'value'   => $rating,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        // Guests filter
        if ( ! empty( $_GET['guests'] ) ) {
            $guests = absint( $_GET['guests'] );
            $meta_query[] = array(
                'key'     => '_ytrip_max_capacity',
                'value'   => $guests,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        // Tour date filter (strict)
        if ( ! empty( $_GET['tour_date'] ) ) {
            $tour_date = sanitize_text_field( wp_unslash( $_GET['tour_date'] ) );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $tour_date ) ) {
                $available_tour_ids = $this->get_tours_available_on_date( $tour_date );
                $query->set( 'post__in', ! empty( $available_tour_ids ) ? $available_tour_ids : array( 0 ) );
            }
        }

        // Date range filter (strict); require date_from <= date_to
        if ( ! empty( $_GET['date_from'] ) && ! empty( $_GET['date_to'] ) ) {
            $date_from = sanitize_text_field( wp_unslash( $_GET['date_from'] ) );
            $date_to   = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) && strtotime( $date_from ) <= strtotime( $date_to ) ) {
                $available_tour_ids = $this->get_tours_available_in_range( $date_from, $date_to );
                $ids_for_range      = ! empty( $available_tour_ids ) ? $available_tour_ids : array( 0 );
                $existing_in        = $query->get( 'post__in' );
                if ( ! empty( $existing_in ) ) {
                    $intersect = array_intersect( $existing_in, $ids_for_range );
                    $query->set( 'post__in', empty( $intersect ) ? array( 0 ) : $intersect );
                } else {
                    $query->set( 'post__in', $ids_for_range );
                }
            }
        }

        // When ordering by meta (price/rating/views), include posts that lack the meta key (LEFT JOIN behavior)
        if ( in_array( $orderby, array( 'price_low', 'price_high', 'rating', 'popularity' ), true ) ) {
            $meta_key_for_order = ( $orderby === 'rating' ) ? '_ytrip_rating' : ( ( $orderby === 'popularity' ) ? '_ytrip_views' : '_ytrip_price' );
            $include_all_meta   = array(
                'relation' => 'OR',
                array( 'key' => $meta_key_for_order, 'compare' => 'NOT EXISTS' ),
                array( 'key' => $meta_key_for_order, 'compare' => 'EXISTS' ),
            );
            if ( ! empty( $meta_query ) ) {
                $meta_query = array_merge( array( 'relation' => 'AND', $include_all_meta ), $meta_query );
            } else {
                $meta_query = array( 'relation' => 'AND', $include_all_meta );
            }
        }

        if ( ! empty( $meta_query ) ) {
            if ( ! isset( $meta_query['relation'] ) ) {
                $meta_query['relation'] = 'AND';
            }
            $query->set( 'meta_query', $meta_query );
        }

        // Taxonomy filters
        $tax_query = array();

        if ( ! empty( $_GET['destination'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'ytrip_destination',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['destination'] ),
            );
        }

        if ( ! empty( $_GET['category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'ytrip_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['category'] ),
            );
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * AJAX handler for filtering tours.
     * Uses $_REQUEST so both GET and POST work (e.g. POST from frontend, GET for direct link/debug).
     */
    public function ajax_filter_tours() {
        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ytrip_filter_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security verification failed. Please refresh the page.', 'ytrip' ),
                'code'    => 'security_verification_failed',
            ), 403 );
            return;
        }

        $req = function ( $key, $default = '' ) {
            if ( ! isset( $_REQUEST[ $key ] ) ) {
                return $default;
            }
            return is_string( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : $default;
        };

        $need_total = filter_var( $req( 'need_total', '1' ), FILTER_VALIDATE_BOOLEAN );
        $args = array(
            'post_type'      => 'ytrip_tour',
            'post_status'    => 'publish',
            'posts_per_page' => (int) ( $this->options['archive_per_page'] ?? 12 ),
            'paged'          => max( 1, (int) $req( 'page', 1 ) ),
            'no_found_rows'  => ! $need_total,
        );

        $meta_query = array();
        $tax_query  = array();

        // Price range (DECIMAL for correct comparison with stored prices)
        $ajax_min = $req( 'min_price' ) !== '' ? floatval( $req( 'min_price' ) ) : null;
        $ajax_max = $req( 'max_price' ) !== '' ? floatval( $req( 'max_price' ) ) : null;
        if ( $ajax_min !== null && $ajax_max !== null && $ajax_min <= $ajax_max ) {
            $meta_query[] = array(
                'key'     => '_ytrip_price',
                'value'   => array( $ajax_min, $ajax_max ),
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL',
            );
        } else {
            if ( $ajax_min !== null ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_price',
                    'value'   => $ajax_min,
                    'compare' => '>=',
                    'type'    => 'DECIMAL',
                );
            }
            if ( $ajax_max !== null ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_price',
                    'value'   => $ajax_max,
                    'compare' => '<=',
                    'type'    => 'DECIMAL',
                );
            }
        }

        // Duration (accept "15+" or URL-encoded "15%2B")
        $dur_raw = $req( 'duration' );
        if ( $dur_raw !== '' ) {
            $duration_ranges = array(
                '1-3'  => array( 1, 3 ),
                '4-7'  => array( 4, 7 ),
                '8-14' => array( 8, 14 ),
            );
            $dur = $dur_raw;
            if ( isset( $duration_ranges[ $dur ] ) ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_duration_days',
                    'value'   => $duration_ranges[ $dur ],
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC',
                );
            } elseif ( $dur === '15+' || $dur === '15%2B' ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_duration_days',
                    'value'   => 15,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        // Rating (3–5 only for correct results)
        $rating_val = $req( 'rating' );
        if ( $rating_val !== '' ) {
            $rating_int = absint( $rating_val );
            if ( $rating_int >= 3 && $rating_int <= 5 ) {
                $meta_query[] = array(
                    'key'     => '_ytrip_rating',
                    'value'   => $rating_int,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        // Guests filter
        $guests_val = $req( 'guests' );
        if ( $guests_val !== '' ) {
            $meta_query[] = array(
                'key'     => '_ytrip_max_capacity',
                'value'   => absint( $guests_val ),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        // Destination
        $destination_slug = $req( 'destination' );
        if ( $destination_slug !== '' ) {
            $tax_query[] = array(
                'taxonomy' => 'ytrip_destination',
                'field'    => 'slug',
                'terms'    => $destination_slug,
            );
        }

        // Category
        $category_slug = $req( 'category' );
        if ( $category_slug !== '' ) {
            $tax_query[] = array(
                'taxonomy' => 'ytrip_category',
                'field'    => 'slug',
                'terms'    => $category_slug,
            );
        }

        // Tour date filter (strict): when user selects a date, show only tours available that day; else 0.
        $tour_date = $req( 'tour_date' );
        if ( $tour_date !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $tour_date ) ) {
            $available_tour_ids = $this->get_tours_available_on_date( $tour_date );
            $args['post__in']   = ! empty( $available_tour_ids ) ? $available_tour_ids : array( 0 );
        }

        // Date range filter (strict): only when both dates set and date_from <= date_to.
        $date_from = $req( 'date_from' );
        $date_to   = $req( 'date_to' );
        if ( $date_from !== '' && $date_to !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) && strtotime( $date_from ) <= strtotime( $date_to ) ) {
            $available_tour_ids = $this->get_tours_available_in_range( $date_from, $date_to );
            $ids_for_range      = ! empty( $available_tour_ids ) ? $available_tour_ids : array( 0 );
            if ( ! empty( $args['post__in'] ) ) {
                $args['post__in'] = array_intersect( $args['post__in'], $ids_for_range );
                if ( empty( $args['post__in'] ) ) {
                    $args['post__in'] = array( 0 );
                }
            } else {
                $args['post__in'] = $ids_for_range;
            }
        }

        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Sorting (for price/rating/views we use ytrip_meta_order + meta_query OR clause so tours without meta are included)
        $orderby = $req( 'orderby', 'date' );
        switch ( $orderby ) {
            case 'price_low':
                $args['ytrip_meta_order'] = array( 'order' => 'ASC' );
                break;
            case 'price_high':
                $args['ytrip_meta_order'] = array( 'order' => 'DESC' );
                break;
            case 'rating':
                $args['ytrip_meta_order'] = array( 'order' => 'DESC' );
                break;
            case 'popularity':
                $args['ytrip_meta_order'] = array( 'order' => 'DESC' );
                break;
            default:
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
        }

        // Include tours without the order meta key (OR EXISTS/NOT EXISTS); orderby applied via posts_clauses (mt1)
        if ( in_array( $orderby, array( 'price_low', 'price_high', 'rating', 'popularity' ), true ) ) {
            $meta_key_order = ( $orderby === 'rating' ) ? '_ytrip_rating' : ( ( $orderby === 'popularity' ) ? '_ytrip_views' : '_ytrip_price' );
            $include_anyway = array(
                'relation' => 'OR',
                array( 'key' => $meta_key_order, 'compare' => 'NOT EXISTS' ),
                array( 'key' => $meta_key_order, 'compare' => 'EXISTS' ),
            );
            if ( ! empty( $meta_query ) ) {
                $meta_query = array_merge( array( 'relation' => 'AND', $include_anyway ), $meta_query );
            } else {
                $meta_query = array( 'relation' => 'AND', $include_anyway );
            }
            $args['meta_query'] = $meta_query;
        } elseif ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );

        ob_start();

        if ( $query->have_posts() ) {
            $view_mode   = $req( 'view', 'grid' );
            $card_style  = function_exists( 'ytrip_get_card_style' ) ? ytrip_get_card_style() : ( $this->options['tour_card_style'] ?? 'style_1' );
            $card_file   = $view_mode === 'list' ? 'card-list-view.php' : $this->get_card_file( $card_style );

            while ( $query->have_posts() ) {
                $query->the_post();
                include YTRIP_PATH . 'templates/cards/' . $card_file;
            }
        } else {
            echo '<p class="ytrip-no-results">' . esc_html__( 'No tours found matching your criteria.', 'ytrip' ) . '</p>';
        }

        $html = ob_get_clean();

        $response = array(
            'html'  => $html,
            'max_pages' => $query->max_num_pages,
        );
        if ( $need_total ) {
            $response['found_posts'] = $query->found_posts;
        }
        wp_send_json_success( $response );

        wp_reset_postdata();
        wp_die();
    }

    /**
     * Get card template filename from style
     */
    private function get_card_file( $style ) {
        $map = array(
            'style_1'  => 'card-overlay-gradient.php',
            'style_2'  => 'card-classic-white.php',
            'style_3'  => 'card-modern-shadow.php',
            'style_4'  => 'card-minimal-border.php',
            'style_5'  => 'card-glassmorphism.php',
            'style_6'  => 'card-hover-zoom.php',
            'style_7'  => 'card-split-content.php',
            'style_8'  => 'card-badge-corner.php',
            'style_9'  => 'card-horizontal.php',
            'style_10' => 'card-compact-grid.php',
        );
        
        return isset( $map[$style] ) ? $map[$style] : 'card-classic-white.php';
    }

    /**
     * Get filter data for templates.
     * When on a destination/category taxonomy archive, includes current term slug
     * so the filter dropdowns can pre-select the active term.
     * Terms are cached in a transient; request-dependent slugs are computed per request.
     */
    public static function get_filter_data() {
        $cached = get_transient( 'ytrip_filter_data' );
        if ( false !== $cached && is_array( $cached ) && isset( $cached['destinations'], $cached['categories'], $cached['durations'], $cached['sort_options'] ) ) {
            $destinations = $cached['destinations'];
            $categories   = $cached['categories'];
            $durations    = $cached['durations'];
            $sort_options = $cached['sort_options'];
        } else {
            $destinations = get_terms( array(
                'taxonomy'   => 'ytrip_destination',
                'hide_empty' => false,
            ) );
            $categories = get_terms( array(
                'taxonomy'   => 'ytrip_category',
                'hide_empty' => false,
            ) );
            $durations = array(
                '1-3'  => __( '1-3 Days', 'ytrip' ),
                '4-7'  => __( '4-7 Days', 'ytrip' ),
                '8-14' => __( '8-14 Days', 'ytrip' ),
                '15+'  => __( '15+ Days', 'ytrip' ),
            );
            $sort_options = array(
                'date'       => __( 'Latest', 'ytrip' ),
                'price_low'  => __( 'Price: Low to High', 'ytrip' ),
                'price_high' => __( 'Price: High to Low', 'ytrip' ),
                'rating'     => __( 'Top Rated', 'ytrip' ),
                'popularity' => __( 'Most Popular', 'ytrip' ),
            );
            set_transient( 'ytrip_filter_data', array(
                'destinations' => $destinations,
                'categories'   => $categories,
                'durations'    => $durations,
                'sort_options' => $sort_options,
            ), self::FILTER_DATA_CACHE_TTL );
        }

        $current_destination_slug = '';
        $current_category_slug   = '';
        if ( is_tax( 'ytrip_destination' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) && isset( $term->slug ) ) {
                $current_destination_slug = sanitize_text_field( $term->slug );
            }
        }
        if ( is_tax( 'ytrip_category' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) && isset( $term->slug ) ) {
                $current_category_slug = sanitize_text_field( $term->slug );
            }
        }

        return array(
            'destinations'             => $destinations,
            'categories'               => $categories,
            'current_destination_slug' => $current_destination_slug,
            'current_category_slug'   => $current_category_slug,
            'durations'                => $durations,
            'sort_options'             => $sort_options,
        );
    }

    /**
     * Get tours that have availability on a specific date.
     * Results are cached in a transient; cache is invalidated on tour save.
     *
     * @param string $date Date in Y-m-d format.
     * @return array Tour IDs.
     */
    private function get_tours_available_on_date( string $date ) {
        $cache_key = 'ytrip_avail_date_' . $date;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $tours = get_posts( array(
            'post_type'      => 'ytrip_tour',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ) );
        if ( empty( $tours ) ) {
            set_transient( $cache_key, array(), self::AVAIL_CACHE_TTL );
            return array();
        }

        update_meta_cache( 'post', $tours );

        $tour_ids = array();
        foreach ( $tours as $tour_id ) {
            if ( $this->is_tour_available_on_date( $tour_id, $date ) ) {
                $tour_ids[] = $tour_id;
            }
        }
        set_transient( $cache_key, $tour_ids, self::AVAIL_CACHE_TTL );
        return $tour_ids;
    }

    /**
     * Get tours available within a date range.
     * Results are cached in a transient; cache is invalidated on tour save.
     *
     * @param string $date_from Start date (Y-m-d).
     * @param string $date_to   End date (Y-m-d).
     * @return array Tour IDs.
     */
    private function get_tours_available_in_range( string $date_from, string $date_to ) {
        $cache_key = 'ytrip_avail_range_' . $date_from . '_' . $date_to;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $tours = get_posts( array(
            'post_type'      => 'ytrip_tour',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ) );
        if ( empty( $tours ) ) {
            set_transient( $cache_key, array(), self::AVAIL_CACHE_TTL );
            return array();
        }

        update_meta_cache( 'post', $tours );

        $tour_ids = array();
        foreach ( $tours as $tour_id ) {
            if ( $this->is_tour_available_in_range( $tour_id, $date_from, $date_to ) ) {
                $tour_ids[] = $tour_id;
            }
        }
        set_transient( $cache_key, $tour_ids, self::AVAIL_CACHE_TTL );
        return $tour_ids;
    }

    /**
     * Check if a tour is available on a specific date
     *
     * @param int    $tour_id Tour ID.
     * @param string $date    Date (Y-m-d).
     * @return bool Available.
     */
    private function is_tour_available_on_date( int $tour_id, string $date ) {
        $date_ts = strtotime( $date );
        if ( $date_ts === false || $date_ts < strtotime( 'today' ) ) {
            return false;
        }

        // If tour has no availability data at all, treat as available any future date (so filter stays accurate)
        $availability = get_post_meta( $tour_id, '_ytrip_availability', true );
        $schedule     = get_post_meta( $tour_id, '_ytrip_schedule', true );
        $fixed        = get_post_meta( $tour_id, '_ytrip_fixed_departures', true );
        $has_any      = ( ! empty( $availability ) && is_array( $availability ) )
            || ( ! empty( $schedule ) && is_array( $schedule ) )
            || ( ! empty( $fixed ) && is_array( $fixed ) );
        if ( ! $has_any ) {
            return true;
        }

        // Check availability calendar
        if ( ! empty( $availability ) && is_array( $availability ) ) {
            // Check specific dates
            if ( isset( $availability['dates'] ) && in_array( $date, $availability['dates'], true ) ) {
                return true;
            }
            
            // Check if date falls within available range
            if ( ! empty( $availability['start_date'] ) && ! empty( $availability['end_date'] ) ) {
                $start = strtotime( $availability['start_date'] );
                $end   = strtotime( $availability['end_date'] );
                if ( $start !== false && $end !== false && $date_ts >= $start && $date_ts <= $end ) {
                    // Check blackout dates
                    if ( ! empty( $availability['blackout_dates'] ) && in_array( $date, $availability['blackout_dates'], true ) ) {
                        return false;
                    }
                    return true;
                }
            }
        }

        // Check recurring schedule (day of week)
        if ( ! empty( $schedule ) && is_array( $schedule ) ) {
            $day_of_week = strtolower( gmdate( 'l', $date_ts ) );
            if ( in_array( $day_of_week, $schedule, true ) ) {
                return true;
            }
        }

        // Fixed departures
        if ( ! empty( $fixed ) && is_array( $fixed ) ) {
            foreach ( $fixed as $departure ) {
                if ( isset( $departure['date'] ) && $departure['date'] === $date ) {
                    // Check if spots available
                    $spots = isset( $departure['spots'] ) ? (int) $departure['spots'] : 0;
                    $booked = isset( $departure['booked'] ) ? (int) $departure['booked'] : 0;
                    if ( $spots === 0 || $spots > $booked ) {
                        return true;
                    }
                }
            }
        }

        // Default: assume available if tour accepts daily bookings
        $booking_type = get_post_meta( $tour_id, '_ytrip_booking_type', true );
        if ( $booking_type === 'daily' || empty( $booking_type ) ) {
            return strtotime( $date ) >= strtotime( 'today' );
        }

        return false;
    }

    /**
     * Check if tour has any availability in date range
     *
     * @param int    $tour_id   Tour ID.
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return bool Has availability.
     */
    private function is_tour_available_in_range( int $tour_id, string $date_from, string $date_to ) {
        $current = strtotime( $date_from );
        $end = strtotime( $date_to );

        while ( $current <= $end ) {
            if ( $this->is_tour_available_on_date( $tour_id, gmdate( 'Y-m-d', $current ) ) ) {
                return true;
            }
            $current = strtotime( '+1 day', $current );
        }

        return false;
    }
}

new YTrip_Archive_Filters();
