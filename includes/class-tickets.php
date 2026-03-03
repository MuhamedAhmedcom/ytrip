<?php
/**
 * YTrip QR Code Tickets
 * 
 * Generates QR-coded tickets for bookings and handles check-in verification.
 * Uses chillerlan/php-qrcode or Endroid QR Code if available.
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Tickets {

    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_CANCELLED = 'cancelled';

    public function __construct() {
        add_action( 'woocommerce_order_status_processing', array( $this, 'generate_ticket' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'generate_ticket' ) );
        
        add_action( 'rest_api_init', array( $this, 'register_checkin_route' ) );
        
        add_action( 'woocommerce_email_after_order_table', array( $this, 'add_ticket_to_email' ), 10, 2 );
        
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_ticket_view' ) );
        
        if ( file_exists( YTRIP_PATH . 'includes/lib/phpqrcode/class-qrcode.php' ) ) {
            require_once YTRIP_PATH . 'includes/lib/phpqrcode/class-qrcode.php';
        }
    }

    /**
     * Generate a unique ticket code for an order
     * 
     * @param int $order_id WooCommerce order ID.
     * @return string Ticket code.
     */
    public function generate_ticket( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        // Check if ticket already exists
        $existing_code = $order->get_meta( '_ytrip_ticket_code' );
        if ( $existing_code ) {
            return $existing_code;
        }

        // Check if this is a YTrip booking
        $tour_id = $order->get_meta( '_ytrip_tour_id' );
        if ( ! $tour_id ) {
            return false;
        }

        // Generate unique ticket code
        $ticket_code = $this->generate_unique_code( $order_id );

        // Save ticket data
        $order->add_meta_data( '_ytrip_ticket_code', $ticket_code );
        $order->add_meta_data( '_ytrip_ticket_status', self::STATUS_CONFIRMED );
        $order->add_meta_data( '_ytrip_ticket_generated', current_time( 'mysql' ) );
        $order->save();

        // Generate QR code image if library available
        $this->generate_qr_image( $order_id, $ticket_code );

        return $ticket_code;
    }

    /**
     * Generate unique alphanumeric code
     */
    private function generate_unique_code( $order_id ) {
        $prefix = strtoupper( substr( md5( get_bloginfo( 'name' ) ), 0, 3 ) );
        $unique = strtoupper( substr( md5( $order_id . wp_generate_password( 8, false ) ), 0, 8 ) );
        return $prefix . '-' . $unique;
    }

    /**
     * Generate QR code image
     */
    private function generate_qr_image( $order_id, $ticket_code ) {
        $verification_url = $this->get_verification_url( $ticket_code );
        
        // Try local QR library first (no external dependencies)
        if ( class_exists( 'YTrip_QRCode' ) ) {
            return $this->generate_with_local_library( $order_id, $verification_url );
        }
        
        // Try chillerlan library
        if ( class_exists( 'chillerlan\\QRCode\\QRCode' ) ) {
            return $this->generate_with_chillerlan( $order_id, $verification_url );
        }
        
        // Try Endroid library
        if ( class_exists( 'Endroid\\QrCode\\QrCode' ) ) {
            return $this->generate_with_endroid( $order_id, $verification_url );
        }

        return false;
    }

    private function generate_with_local_library( $order_id, $data ) {
        try {
            $qr = ytrip_qrcode();
            $qr->set_size( 300 )->set_margin( 10 );
            $qr->generate( $data );

            $upload_dir = wp_upload_dir();
            $filename = 'ytrip-ticket-' . $order_id . '.png';
            $filepath = $upload_dir['basedir'] . '/ytrip-tickets/' . $filename;

            wp_mkdir_p( dirname( $filepath ) );

            $png_data = $qr->to_png();
            if ( ! $png_data ) {
                return false;
            }

            file_put_contents( $filepath, $png_data );

            $url = $upload_dir['baseurl'] . '/ytrip-tickets/' . $filename;
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $order->add_meta_data( '_ytrip_ticket_qr_url', $url );
                $order->save();
            }

            return $url;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Generate QR using chillerlan library
     */
    private function generate_with_chillerlan( $order_id, $data ) {
        $qr = new \chillerlan\QRCode\QRCode();
        $image = $qr->render( $data );

        $upload_dir = wp_upload_dir();
        $filename = 'ytrip-ticket-' . $order_id . '.png';
        $filepath = $upload_dir['basedir'] . '/ytrip-tickets/' . $filename;

        // Ensure directory exists
        wp_mkdir_p( dirname( $filepath ) );

        // Save image
        $image_data = str_replace( 'data:image/png;base64,', '', $image );
        file_put_contents( $filepath, base64_decode( $image_data ) );

        // Save URL in order meta
        $url = $upload_dir['baseurl'] . '/ytrip-tickets/' . $filename;
        $order = wc_get_order( $order_id );
        $order->add_meta_data( '_ytrip_ticket_qr_url', $url );
        $order->save();

        return $url;
    }

    /**
     * Generate QR using Endroid library
     */
    private function generate_with_endroid( $order_id, $data ) {
        $qrCode = new \Endroid\QrCode\QrCode( $data );
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write( $qrCode );

        $upload_dir = wp_upload_dir();
        $filename = 'ytrip-ticket-' . $order_id . '.png';
        $filepath = $upload_dir['basedir'] . '/ytrip-tickets/' . $filename;

        wp_mkdir_p( dirname( $filepath ) );
        $result->saveToFile( $filepath );

        $url = $upload_dir['baseurl'] . '/ytrip-tickets/' . $filename;
        $order = wc_get_order( $order_id );
        $order->add_meta_data( '_ytrip_ticket_qr_url', $url );
        $order->save();

        return $url;
    }

    /**
     * Generate QR using Google Charts API (fallback)
     */
    private function generate_with_google_api( $order_id, $data ) {
        return false;
    }

    /**
     * Get verification URL for a ticket
     */
    public function get_verification_url( $ticket_code ) {
        return rest_url( 'ytrip/v1/tickets/verify/' . $ticket_code );
    }

    /**
     * Get ticket view URL
     */
    public function get_ticket_url( $ticket_code ) {
        return home_url( '/ytrip-ticket/' . $ticket_code . '/' );
    }

    /**
     * Register REST endpoint for check-in
     */
    public function register_checkin_route() {
        // Verify ticket
        register_rest_route( 'ytrip/v1', '/tickets/verify/(?P<code>[A-Z0-9-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'verify_ticket' ),
            'permission_callback' => '__return_true',
        ) );

        // Check-in ticket
        register_rest_route( 'ytrip/v1', '/tickets/checkin/(?P<code>[A-Z0-9-]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'checkin_ticket' ),
            'permission_callback' => array( $this, 'can_checkin' ),
        ) );
    }

    /**
     * Verify ticket via REST API
     */
    public function verify_ticket( WP_REST_Request $request ) {
        $code = sanitize_text_field( $request->get_param( 'code' ) );
        $order = $this->get_order_by_ticket( $code );

        if ( ! $order ) {
            return new WP_Error( 'invalid_ticket', __( 'Invalid ticket code', 'ytrip' ), array( 'status' => 404 ) );
        }

        $tour_id = $order->get_meta( '_ytrip_tour_id' );
        $tour = get_post( $tour_id );
        $status = $order->get_meta( '_ytrip_ticket_status' );
        $booking_date = $order->get_meta( '_ytrip_booking_date' );
        $persons = $order->get_meta( '_ytrip_persons' );

        return new WP_REST_Response( array(
            'valid'        => true,
            'ticket_code'  => $code,
            'status'       => $status,
            'checked_in'   => $status === self::STATUS_CHECKED_IN,
            'order_id'     => $order->get_id(),
            'tour'         => array(
                'id'    => $tour_id,
                'title' => $tour ? $tour->post_title : __( 'Unknown Tour', 'ytrip' ),
            ),
            'booking_date' => $booking_date,
            'persons'      => $persons,
            'customer'     => array(
                'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
        ), 200 );
    }

    /**
     * Check-in a ticket
     */
    public function checkin_ticket( WP_REST_Request $request ) {
        $code = sanitize_text_field( $request->get_param( 'code' ) );
        $order = $this->get_order_by_ticket( $code );

        if ( ! $order ) {
            return new WP_Error( 'invalid_ticket', __( 'Invalid ticket code', 'ytrip' ), array( 'status' => 404 ) );
        }

        $current_status = $order->get_meta( '_ytrip_ticket_status' );

        if ( $current_status === self::STATUS_CHECKED_IN ) {
            $checkin_time = $order->get_meta( '_ytrip_checkin_time' );
            return new WP_Error( 'already_checked_in', sprintf( __( 'Already checked in at %s', 'ytrip' ), $checkin_time ), array( 'status' => 400 ) );
        }

        if ( $current_status === self::STATUS_CANCELLED ) {
            return new WP_Error( 'ticket_cancelled', __( 'This ticket has been cancelled', 'ytrip' ), array( 'status' => 400 ) );
        }

        // Perform check-in
        $order->update_meta_data( '_ytrip_ticket_status', self::STATUS_CHECKED_IN );
        $order->update_meta_data( '_ytrip_checkin_time', current_time( 'mysql' ) );
        $order->update_meta_data( '_ytrip_checkin_by', get_current_user_id() );
        $order->save();

        // Add order note
        $order->add_order_note( sprintf( __( 'Guest checked in via QR ticket at %s', 'ytrip' ), current_time( 'mysql' ) ) );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => __( 'Check-in successful', 'ytrip' ),
            'ticket_code'  => $code,
            'checkin_time' => current_time( 'mysql' ),
        ), 200 );
    }

    /**
     * Check if user can perform check-in
     */
    public function can_checkin( WP_REST_Request $request ) {
        // Allow admins and editors
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }

        // Allow with API key (for mobile app)
        $api_key = $request->get_header( 'X-YTrip-API-Key' );
        $stored_key = get_option( 'ytrip_checkin_api_key' );
        if ( $api_key && $stored_key && hash_equals( $stored_key, $api_key ) ) {
            return true;
        }

        return new WP_Error( 'rest_forbidden', __( 'Check-in permission required', 'ytrip' ), array( 'status' => 403 ) );
    }

    /**
     * Get order by ticket code
     */
    private function get_order_by_ticket( $code ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return null;
        }

        $orders = wc_get_orders( array(
            'limit'      => 1,
            'meta_key'   => '_ytrip_ticket_code',
            'meta_value' => $code,
        ) );

        return ! empty( $orders ) ? $orders[0] : null;
    }

    /**
     * Add rewrite rules for ticket view
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'ytrip-ticket/([A-Z0-9-]+)/?$',
            'index.php?ytrip_ticket=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'ytrip_ticket';
        return $vars;
    }

    /**
     * Handle ticket view request
     */
    public function handle_ticket_view() {
        $ticket_code = get_query_var( 'ytrip_ticket' );
        
        if ( ! $ticket_code ) {
            return;
        }

        $ticket_code = sanitize_text_field( strtoupper( $ticket_code ) );
        $order = $this->get_order_by_ticket( $ticket_code );

        if ( ! $order ) {
            status_header( 404 );
            echo $this->render_error_page( __( 'Invalid ticket', 'ytrip' ) );
            exit;
        }

        echo $this->render_ticket_page( $order );
        exit;
    }

    /**
     * Render ticket page
     */
    private function render_ticket_page( $order ) {
        $ticket_code  = $order->get_meta( '_ytrip_ticket_code' );
        $status       = $order->get_meta( '_ytrip_ticket_status' );
        $qr_url       = $order->get_meta( '_ytrip_ticket_qr_url' );
        $tour_id      = $order->get_meta( '_ytrip_tour_id' );
        $booking_date = $order->get_meta( '_ytrip_booking_date' );
        $persons      = $order->get_meta( '_ytrip_persons' );
        $tour         = get_post( $tour_id );
        $settings     = get_option( 'ytrip_settings' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e( 'Your Ticket', 'ytrip' ); ?> - <?php echo esc_html( $ticket_code ); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 20px;
                }
                .ticket {
                    background: white;
                    border-radius: 20px;
                    max-width: 400px;
                    width: 100%;
                    overflow: hidden;
                    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
                }
                .ticket-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 25px;
                    text-align: center;
                }
                .ticket-header h1 { font-size: 20px; margin-bottom: 5px; }
                .ticket-header .date { opacity: 0.9; }
                .ticket-body { padding: 25px; }
                .qr-code {
                    text-align: center;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 15px;
                    margin-bottom: 20px;
                }
                .qr-code img { max-width: 200px; }
                .ticket-code {
                    text-align: center;
                    font-size: 24px;
                    font-weight: bold;
                    letter-spacing: 3px;
                    color: #333;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }
                .ticket-info { border-top: 2px dashed #e0e0e0; padding-top: 20px; }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .info-row:last-child { border-bottom: none; }
                .info-label { color: #666; font-size: 14px; }
                .info-value { font-weight: 600; color: #333; }
                .status {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                .status-confirmed { background: #d4edda; color: #155724; }
                .status-checked_in { background: #cce5ff; color: #004085; }
                .status-cancelled { background: #f8d7da; color: #721c24; }
                .print-btn {
                    display: block;
                    width: 100%;
                    padding: 15px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    border-radius: 10px;
                    margin-top: 20px;
                }
                @media print {
                    body { background: white; }
                    .print-btn { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="ticket-header">
                    <h1><?php echo esc_html( $tour ? $tour->post_title : __( 'Tour Booking', 'ytrip' ) ); ?></h1>
                    <div class="date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking_date ) ) ); ?></div>
                </div>
                <div class="ticket-body">
                    <div class="qr-code">
                        <?php if ( $qr_url ) : ?>
                            <img src="<?php echo esc_url( $qr_url ); ?>" alt="QR Code">
                        <?php endif; ?>
                    </div>
                    <div class="ticket-code"><?php echo esc_html( $ticket_code ); ?></div>
                    <div class="ticket-info">
                        <div class="info-row">
                            <span class="info-label"><?php esc_html_e( 'Status', 'ytrip' ); ?></span>
                            <span class="status status-<?php echo esc_attr( $status ); ?>">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><?php esc_html_e( 'Guest', 'ytrip' ); ?></span>
                            <span class="info-value"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><?php esc_html_e( 'Persons', 'ytrip' ); ?></span>
                            <span class="info-value"><?php echo esc_html( is_array( $persons ) ? array_sum( $persons ) : 1 ); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><?php esc_html_e( 'Order', 'ytrip' ); ?></span>
                            <span class="info-value">#<?php echo esc_html( $order->get_id() ); ?></span>
                        </div>
                    </div>
                    <button class="print-btn" onclick="window.print()">
                        <?php esc_html_e( 'Print Ticket', 'ytrip' ); ?>
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Render error page
     */
    private function render_error_page( $message ) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php esc_html_e( 'Ticket Error', 'ytrip' ); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background: #f8f9fa;
                }
                .error {
                    text-align: center;
                    padding: 40px;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .error h1 { color: #dc3545; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="error">
                <h1>⚠️ <?php esc_html_e( 'Error', 'ytrip' ); ?></h1>
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Add ticket to WooCommerce email
     */
    public function add_ticket_to_email( $order, $sent_to_admin ) {
        if ( $sent_to_admin ) {
            return;
        }

        $ticket_code = $order->get_meta( '_ytrip_ticket_code' );
        $qr_url      = $order->get_meta( '_ytrip_ticket_qr_url' );

        if ( ! $ticket_code ) {
            return;
        }

        $ticket_url = $this->get_ticket_url( $ticket_code );
        ?>
        <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0 0 15px; color: #333;"><?php esc_html_e( 'Your E-Ticket', 'ytrip' ); ?></h3>
            <?php if ( $qr_url ) : ?>
                <img src="<?php echo esc_url( $qr_url ); ?>" alt="QR Code" style="max-width: 150px; margin-bottom: 15px;">
            <?php endif; ?>
            <p style="font-size: 20px; font-weight: bold; letter-spacing: 2px; margin: 10px 0;">
                <?php echo esc_html( $ticket_code ); ?>
            </p>
            <a href="<?php echo esc_url( $ticket_url ); ?>" 
               style="display: inline-block; padding: 10px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;">
                <?php esc_html_e( 'View Full Ticket', 'ytrip' ); ?>
            </a>
        </div>
        <?php
    }
}

// Initialize
new YTrip_Tickets();
