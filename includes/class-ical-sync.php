<?php
/**
 * YTrip iCal Synchronization
 * 
 * Handles:
 * - iCal feed export for tour availability
 * - Import external calendars to block dates
 * - Scheduled cron sync
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_iCal_Sync {

    /**
     * iCal date format
     */
    const ICAL_DATE_FORMAT = 'Ymd\THis\Z';

    /**
     * Constructor
     */
    public function __construct() {
        // Register rewrite for iCal feed
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_ical_request' ) );
        
        // Cron for syncing external feeds
        add_action( 'ytrip_sync_ical_feeds', array( $this, 'cron_sync_all_feeds' ) );
        
        // Schedule cron if not scheduled
        if ( ! wp_next_scheduled( 'ytrip_sync_ical_feeds' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'ytrip_sync_ical_feeds' );
        }
    }

    /**
     * Add rewrite rules for iCal feed URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'ytrip-ical/([0-9]+)/?$',
            'index.php?ytrip_ical_export=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'ytrip_ical_export';
        return $vars;
    }

    /**
     * Handle iCal feed request
     */
    public function handle_ical_request() {
        $tour_id = get_query_var( 'ytrip_ical_export' );
        
        if ( ! $tour_id ) {
            return;
        }

        $tour_id = absint( $tour_id );
        $post = get_post( $tour_id );

        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            status_header( 404 );
            exit( 'Tour not found' );
        }

        // Generate iCal content
        $ical = $this->generate_ical( $tour_id );

        // Send headers
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="tour-' . $tour_id . '.ics"' );
        header( 'Cache-Control: no-cache, must-revalidate' );

        echo $ical;
        exit;
    }

    /**
     * Get the public iCal feed URL for a tour
     * 
     * @param int $tour_id Tour post ID.
     * @return string Feed URL.
     */
    public static function get_feed_url( $tour_id ) {
        return home_url( '/ytrip-ical/' . $tour_id . '/' );
    }

    /**
     * Generate iCal content for a tour
     * 
     * Exports:
     * - Tour as an event template
     * - Blocked dates as VEVENT items
     * - Existing bookings as busy periods
     * 
     * @param int $tour_id Tour post ID.
     * @return string iCal formatted string.
     */
    public function generate_ical( $tour_id ) {
        $post = get_post( $tour_id );
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        
        $site_name = get_bloginfo( 'name' );
        $site_url  = home_url();
        
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//YTrip//" . sanitize_title( $site_name ) . "//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:" . $this->escape_ical( $post->post_title ) . "\r\n";
        $ical .= "X-WR-CALDESC:" . $this->escape_ical( wp_trim_words( $post->post_content, 30 ) ) . "\r\n";

        // Add blocked dates as events
        $blocked_dates = ! empty( $meta['blocked_dates'] ) ? $meta['blocked_dates'] : array();
        foreach ( $blocked_dates as $blocked ) {
            if ( empty( $blocked['date'] ) ) continue;
            
            $date = $blocked['date'];
            $ical .= $this->create_vevent(
                $tour_id . '-blocked-' . $date,
                $date,
                __( 'BLOCKED: ', 'ytrip' ) . $post->post_title,
                __( 'This date is not available for booking.', 'ytrip' ),
                true // All day
            );
        }

        // Add confirmed bookings (from WooCommerce orders)
        $bookings = $this->get_tour_bookings( $tour_id );
        foreach ( $bookings as $booking ) {
            $ical .= $this->create_vevent(
                $tour_id . '-booking-' . $booking['order_id'],
                $booking['date'],
                __( 'BOOKED: ', 'ytrip' ) . $post->post_title . ' (#' . $booking['order_id'] . ')',
                sprintf( __( 'Order #%d - %d persons', 'ytrip' ), $booking['order_id'], $booking['persons'] ),
                true
            );
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Create a VEVENT entry
     */
    private function create_vevent( $uid, $date, $summary, $description = '', $all_day = false ) {
        $now = gmdate( self::ICAL_DATE_FORMAT );
        
        if ( $all_day ) {
            $dtstart = date( 'Ymd', strtotime( $date ) );
            $dtend   = date( 'Ymd', strtotime( $date . ' +1 day' ) );
            $date_format = ';VALUE=DATE:';
        } else {
            $dtstart = gmdate( self::ICAL_DATE_FORMAT, strtotime( $date ) );
            $dtend   = gmdate( self::ICAL_DATE_FORMAT, strtotime( $date . ' +1 hour' ) );
            $date_format = ':';
        }

        $vevent  = "BEGIN:VEVENT\r\n";
        $vevent .= "UID:" . $uid . "@" . parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
        $vevent .= "DTSTAMP:" . $now . "\r\n";
        $vevent .= "DTSTART" . $date_format . $dtstart . "\r\n";
        $vevent .= "DTEND" . $date_format . $dtend . "\r\n";
        $vevent .= "SUMMARY:" . $this->escape_ical( $summary ) . "\r\n";
        
        if ( $description ) {
            $vevent .= "DESCRIPTION:" . $this->escape_ical( $description ) . "\r\n";
        }
        
        $vevent .= "STATUS:CONFIRMED\r\n";
        $vevent .= "END:VEVENT\r\n";

        return $vevent;
    }

    /**
     * Escape string for iCal format
     */
    private function escape_ical( $string ) {
        $string = str_replace( array( "\\", ",", ";", "\n", "\r" ), array( "\\\\", "\\,", "\\;", "\\n", "" ), $string );
        return $string;
    }

    /**
     * Get bookings for a tour from WooCommerce orders
     */
    private function get_tour_bookings( $tour_id ) {
        $bookings = array();

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return $bookings;
        }

        $orders = wc_get_orders( array(
            'limit'      => -1,
            'status'     => array( 'processing', 'completed', 'on-hold' ),
            'meta_key'   => '_ytrip_tour_id',
            'meta_value' => $tour_id,
        ) );

        foreach ( $orders as $order ) {
            $date = $order->get_meta( '_ytrip_booking_date' );
            $persons = $order->get_meta( '_ytrip_persons' );
            $total_persons = is_array( $persons ) ? array_sum( $persons ) : 1;

            if ( $date ) {
                $bookings[] = array(
                    'order_id' => $order->get_id(),
                    'date'     => $date,
                    'persons'  => $total_persons,
                );
            }
        }

        return $bookings;
    }

    /**
     * Import external iCal feed and block dates
     * 
     * @param int    $tour_id  Tour post ID.
     * @param string $feed_url External iCal URL.
     * @return array Result with count of imported dates.
     */
    public static function import_feed( $tour_id, $feed_url ) {
        $response = wp_remote_get( $feed_url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'text/calendar',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $dates = self::parse_ical_dates( $body );

        if ( empty( $dates ) ) {
            return array(
                'success' => true,
                'imported' => 0,
                'message' => __( 'No dates found in feed', 'ytrip' ),
            );
        }

        // Get existing blocked dates
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        $blocked_dates = ! empty( $meta['blocked_dates'] ) ? $meta['blocked_dates'] : array();
        $existing_dates = array_column( $blocked_dates, 'date' );

        $imported = 0;
        foreach ( $dates as $date ) {
            if ( ! in_array( $date, $existing_dates, true ) ) {
                $blocked_dates[] = array( 'date' => $date );
                $imported++;
            }
        }

        // Save updated meta
        $meta['blocked_dates'] = $blocked_dates;
        update_post_meta( $tour_id, 'ytrip_tour_details', $meta );

        // Log last sync time
        update_post_meta( $tour_id, '_ytrip_last_ical_sync', current_time( 'mysql' ) );

        return array(
            'success'  => true,
            'imported' => $imported,
            'total'    => count( $dates ),
        );
    }

    /**
     * Parse iCal content and extract dates
     */
    private static function parse_ical_dates( $ical_content ) {
        $dates = array();
        
        // Simple regex to find DTSTART entries
        preg_match_all( '/DTSTART[^:]*:(\d{8})/m', $ical_content, $matches );
        
        if ( ! empty( $matches[1] ) ) {
            foreach ( $matches[1] as $date_str ) {
                $year  = substr( $date_str, 0, 4 );
                $month = substr( $date_str, 4, 2 );
                $day   = substr( $date_str, 6, 2 );
                $dates[] = "$year-$month-$day";
            }
        }

        // Also check for datetime format
        preg_match_all( '/DTSTART[^:]*:(\d{8})T/m', $ical_content, $matches2 );
        
        if ( ! empty( $matches2[1] ) ) {
            foreach ( $matches2[1] as $date_str ) {
                $year  = substr( $date_str, 0, 4 );
                $month = substr( $date_str, 4, 2 );
                $day   = substr( $date_str, 6, 2 );
                $formatted = "$year-$month-$day";
                if ( ! in_array( $formatted, $dates, true ) ) {
                    $dates[] = $formatted;
                }
            }
        }

        return array_unique( $dates );
    }

    /**
     * Cron job: Sync all tours with external iCal feeds
     */
    public function cron_sync_all_feeds() {
        // Get all tours with external iCal URLs
        $tours = get_posts( array(
            'post_type'      => 'ytrip_tour',
            'posts_per_page' => -1,
            'meta_key'       => '_ytrip_external_ical_url',
            'meta_compare'   => '!=',
            'meta_value'     => '',
        ) );

        foreach ( $tours as $tour ) {
            $feed_url = get_post_meta( $tour->ID, '_ytrip_external_ical_url', true );
            if ( $feed_url ) {
                self::import_feed( $tour->ID, $feed_url );
            }
        }
    }

    /**
     * AJAX handler to manually sync a tour's external feed
     */
    public static function ajax_sync_feed() {
        check_ajax_referer( 'ytrip_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'ytrip' ) ) );
        }

        $tour_id  = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
        $feed_url = isset( $_POST['feed_url'] ) ? esc_url_raw( $_POST['feed_url'] ) : '';

        if ( ! $tour_id || ! $feed_url ) {
            wp_send_json_error( array( 'message' => __( 'Missing parameters', 'ytrip' ) ) );
        }

        // Store the feed URL
        update_post_meta( $tour_id, '_ytrip_external_ical_url', $feed_url );

        // Import
        $result = self::import_feed( $tour_id, $feed_url );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }
}

// Initialize
new YTrip_iCal_Sync();

// Register AJAX handlers
add_action( 'wp_ajax_ytrip_sync_ical', array( 'YTrip_iCal_Sync', 'ajax_sync_feed' ) );
