<?php
/**
 * YTrip B2B Agent Portal
 * 
 * Provides:
 * - Agent registration and approval workflow
 * - Agent dashboard for booking management
 * - Commission calculation and tracking
 * - White-label ticket support
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Agent_Portal {

    /**
     * Agent status constants
     */
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Agent role name
     */
    const AGENT_ROLE = 'ytrip_agent';

    /**
     * Constructor
     */
    public function __construct() {
        // Add agent role on activation
        add_action( 'init', array( $this, 'register_agent_role' ) );
        
        // Dashboard shortcode
        add_shortcode( 'ytrip_agent_dashboard', array( $this, 'render_dashboard' ) );
        add_shortcode( 'ytrip_agent_register', array( $this, 'render_registration' ) );
        
        // Agent commission on order
        add_action( 'woocommerce_order_status_processing', array( $this, 'calculate_commission' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'calculate_commission' ) );
        
        // REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_ytrip_agent_register', array( $this, 'ajax_register' ) );
        add_action( 'wp_ajax_nopriv_ytrip_agent_register', array( $this, 'ajax_register' ) );
        add_action( 'wp_ajax_ytrip_agent_book', array( $this, 'ajax_book_tour' ) );
    }

    /**
     * Register agent role
     */
    public function register_agent_role() {
        if ( ! get_role( self::AGENT_ROLE ) ) {
            add_role( self::AGENT_ROLE, __( 'Travel Agent', 'ytrip' ), array(
                'read'         => true,
                'edit_posts'   => false,
                'delete_posts' => false,
            ) );
        }
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Get agent profile
        register_rest_route( 'ytrip/v1', '/agent/profile', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_profile' ),
            'permission_callback' => array( $this, 'is_agent' ),
        ) );

        // Get agent bookings
        register_rest_route( 'ytrip/v1', '/agent/bookings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_bookings' ),
            'permission_callback' => array( $this, 'is_agent' ),
        ) );

        // Get commission history
        register_rest_route( 'ytrip/v1', '/agent/commissions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_commissions' ),
            'permission_callback' => array( $this, 'is_agent' ),
        ) );

        // Create booking for client
        register_rest_route( 'ytrip/v1', '/agent/book', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_booking' ),
            'permission_callback' => array( $this, 'is_agent' ),
        ) );
    }

    /**
     * Check if current user is an agent
     */
    public function is_agent() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array( self::AGENT_ROLE, (array) $user->roles, true ) || current_user_can( 'manage_options' );
    }

    /**
     * Get agent data from user
     */
    public static function get_agent_data( $user_id ) {
        return array(
            'company_name'     => get_user_meta( $user_id, '_ytrip_agent_company', true ),
            'commission_rate'  => floatval( get_user_meta( $user_id, '_ytrip_agent_commission_rate', true ) ?: 10 ),
            'status'           => get_user_meta( $user_id, '_ytrip_agent_status', true ) ?: self::STATUS_PENDING,
            'total_bookings'   => self::count_agent_bookings( $user_id ),
            'total_commission' => self::get_total_commission( $user_id ),
            'pending_payout'   => self::get_pending_payout( $user_id ),
            'approved_date'    => get_user_meta( $user_id, '_ytrip_agent_approved_date', true ),
        );
    }

    /**
     * Count agent bookings
     */
    private static function count_agent_bookings( $user_id ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return 0;
        }

        $orders = wc_get_orders( array(
            'limit'      => -1,
            'meta_key'   => '_ytrip_agent_id',
            'meta_value' => $user_id,
            'return'     => 'ids',
        ) );

        return count( $orders );
    }

    /**
     * Get total earned commission
     */
    private static function get_total_commission( $user_id ) {
        global $wpdb;

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
             WHERE pm.meta_key = '_ytrip_agent_commission'
             AND pm2.meta_key = '_ytrip_agent_id'
             AND pm2.meta_value = %d",
            $user_id
        ) );

        return floatval( $total );
    }

    /**
     * Get pending payout (not yet paid)
     */
    private static function get_pending_payout( $user_id ) {
        global $wpdb;

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(pm.meta_value) FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
             LEFT JOIN {$wpdb->postmeta} pm3 ON pm.post_id = pm3.post_id AND pm3.meta_key = '_ytrip_commission_paid'
             WHERE pm.meta_key = '_ytrip_agent_commission'
             AND pm2.meta_key = '_ytrip_agent_id'
             AND pm2.meta_value = %d
             AND (pm3.meta_value IS NULL OR pm3.meta_value = '0')",
            $user_id
        ) );

        return floatval( $total );
    }

    /**
     * Calculate commission on order completion
     */
    public function calculate_commission( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Check if already calculated
        if ( $order->get_meta( '_ytrip_agent_commission' ) ) {
            return;
        }

        // Check if this is an agent booking
        $agent_id = $order->get_meta( '_ytrip_agent_id' );
        if ( ! $agent_id ) {
            return;
        }

        // Check agent status
        $status = get_user_meta( $agent_id, '_ytrip_agent_status', true );
        if ( $status !== self::STATUS_APPROVED ) {
            return;
        }

        // Get commission rate
        $rate = floatval( get_user_meta( $agent_id, '_ytrip_agent_commission_rate', true ) ?: 10 );
        $total = $order->get_total();
        $commission = ( $total * $rate ) / 100;

        // Save commission
        $order->add_meta_data( '_ytrip_agent_commission', $commission );
        $order->add_meta_data( '_ytrip_commission_rate_used', $rate );
        $order->add_meta_data( '_ytrip_commission_paid', 0 );
        $order->save();

        // Add order note
        $order->add_order_note( sprintf(
            __( 'Agent commission calculated: %s (%.1f%% of %s)', 'ytrip' ),
            wc_price( $commission ),
            $rate,
            wc_price( $total )
        ) );
    }

    /**
     * REST: Get agent profile
     */
    public function get_profile( WP_REST_Request $request ) {
        $user = wp_get_current_user();
        $data = self::get_agent_data( $user->ID );

        return new WP_REST_Response( array_merge( array(
            'id'    => $user->ID,
            'name'  => $user->display_name,
            'email' => $user->user_email,
        ), $data ), 200 );
    }

    /**
     * REST: Get agent bookings
     */
    public function get_bookings( WP_REST_Request $request ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return new WP_Error( 'wc_not_active', __( 'WooCommerce required', 'ytrip' ), array( 'status' => 500 ) );
        }

        $user = wp_get_current_user();
        $page = $request->get_param( 'page' ) ?: 1;
        $per_page = $request->get_param( 'per_page' ) ?: 10;

        $orders = wc_get_orders( array(
            'limit'    => $per_page,
            'paged'    => $page,
            'meta_key' => '_ytrip_agent_id',
            'meta_value' => $user->ID,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ) );

        $bookings = array();
        foreach ( $orders as $order ) {
            $tour_id = $order->get_meta( '_ytrip_tour_id' );
            $tour = get_post( $tour_id );

            $bookings[] = array(
                'order_id'     => $order->get_id(),
                'status'       => $order->get_status(),
                'date_created' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
                'total'        => $order->get_total(),
                'commission'   => $order->get_meta( '_ytrip_agent_commission' ),
                'tour'         => array(
                    'id'    => $tour_id,
                    'title' => $tour ? $tour->post_title : __( 'Unknown', 'ytrip' ),
                ),
                'booking_date' => $order->get_meta( '_ytrip_booking_date' ),
                'customer'     => array(
                    'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                ),
            );
        }

        return new WP_REST_Response( array(
            'bookings' => $bookings,
            'page'     => $page,
            'per_page' => $per_page,
        ), 200 );
    }

    /**
     * REST: Get commission history
     */
    public function get_commissions( WP_REST_Request $request ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return new WP_Error( 'wc_not_active', __( 'WooCommerce required', 'ytrip' ), array( 'status' => 500 ) );
        }

        $user = wp_get_current_user();

        $orders = wc_get_orders( array(
            'limit'      => -1,
            'meta_key'   => '_ytrip_agent_id',
            'meta_value' => $user->ID,
            'status'     => array( 'processing', 'completed' ),
        ) );

        $commissions = array();
        $total_earned = 0;
        $total_pending = 0;
        $total_paid = 0;

        foreach ( $orders as $order ) {
            $commission = floatval( $order->get_meta( '_ytrip_agent_commission' ) );
            $paid = $order->get_meta( '_ytrip_commission_paid' ) == 1;

            if ( $commission > 0 ) {
                $commissions[] = array(
                    'order_id'   => $order->get_id(),
                    'amount'     => $commission,
                    'rate'       => $order->get_meta( '_ytrip_commission_rate_used' ),
                    'paid'       => $paid,
                    'date'       => $order->get_date_created()->format( 'Y-m-d' ),
                );

                $total_earned += $commission;
                if ( $paid ) {
                    $total_paid += $commission;
                } else {
                    $total_pending += $commission;
                }
            }
        }

        return new WP_REST_Response( array(
            'history'       => $commissions,
            'total_earned'  => $total_earned,
            'total_pending' => $total_pending,
            'total_paid'    => $total_paid,
            'currency'      => get_woocommerce_currency_symbol(),
        ), 200 );
    }

    /**
     * REST: Create booking for client
     */
    public function create_booking( WP_REST_Request $request ) {
        $user = wp_get_current_user();
        
        // Check agent status
        $status = get_user_meta( $user->ID, '_ytrip_agent_status', true );
        if ( $status !== self::STATUS_APPROVED ) {
            return new WP_Error( 'agent_not_approved', __( 'Your agent account is not approved', 'ytrip' ), array( 'status' => 403 ) );
        }

        $tour_id  = absint( $request->get_param( 'tour_id' ) );
        $date     = sanitize_text_field( $request->get_param( 'date' ) );
        $persons  = (array) $request->get_param( 'persons' );
        $customer = $request->get_param( 'customer' );

        // Validate tour
        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return new WP_Error( 'invalid_tour', __( 'Invalid tour', 'ytrip' ), array( 'status' => 400 ) );
        }

        // Calculate price
        $pricing = YTrip_Pricing_Engine::calculate( $tour_id, $date, $persons );

        // Get linked product
        $product_id = get_post_meta( $tour_id, '_ytrip_linked_product_id', true );
        if ( ! $product_id ) {
            return new WP_Error( 'no_product', __( 'Tour not available for booking', 'ytrip' ), array( 'status' => 400 ) );
        }

        try {
            $order = wc_create_order();

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

            // YTrip booking meta
            $order->add_meta_data( '_ytrip_tour_id', $tour_id );
            $order->add_meta_data( '_ytrip_booking_date', $date );
            $order->add_meta_data( '_ytrip_persons', $persons );
            $order->add_meta_data( '_ytrip_pricing_breakdown', $pricing );

            // Agent meta
            $order->add_meta_data( '_ytrip_agent_id', $user->ID );
            $order->add_meta_data( '_ytrip_agent_company', get_user_meta( $user->ID, '_ytrip_agent_company', true ) );
            $order->add_meta_data( '_ytrip_booked_via_agent', true );

            $order->calculate_totals();
            $order->save();

            return new WP_REST_Response( array(
                'success'   => true,
                'order_id'  => $order->get_id(),
                'total'     => $pricing['total'],
                'status'    => $order->get_status(),
            ), 201 );

        } catch ( Exception $e ) {
            return new WP_Error( 'booking_failed', $e->getMessage(), array( 'status' => 500 ) );
        }
    }

    /**
     * AJAX: Register new agent
     */
    public function ajax_register() {
        check_ajax_referer( 'ytrip_nonce', 'nonce' );

        $email        = sanitize_email( $_POST['email'] ?? '' );
        $password     = $_POST['password'] ?? '';
        $company_name = sanitize_text_field( $_POST['company_name'] ?? '' );
        $phone        = sanitize_text_field( $_POST['phone'] ?? '' );
        $first_name   = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name    = sanitize_text_field( $_POST['last_name'] ?? '' );

        // Validate
        if ( ! $email || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            wp_send_json_error( array( 'message' => __( 'Valid email required', 'ytrip' ) ) );
        }

        if ( strlen( $password ) < 8 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters', 'ytrip' ) ) );
        }

        if ( email_exists( $email ) || username_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Email already registered', 'ytrip' ) ) );
        }

        // Create user
        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        // Update user details
        wp_update_user( array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ) );

        // Set agent role
        $user = new WP_User( $user_id );
        $user->set_role( self::AGENT_ROLE );

        // Save agent meta
        update_user_meta( $user_id, '_ytrip_agent_company', $company_name );
        update_user_meta( $user_id, '_ytrip_agent_phone', $phone );
        update_user_meta( $user_id, '_ytrip_agent_status', self::STATUS_PENDING );
        update_user_meta( $user_id, '_ytrip_agent_registered', current_time( 'mysql' ) );

        // Get default commission rate from settings
        $settings = get_option( 'ytrip_settings' );
        $default_rate = $settings['default_agent_commission'] ?? 10;
        update_user_meta( $user_id, '_ytrip_agent_commission_rate', $default_rate );

        // Notify admin
        $this->notify_admin_new_agent( $user_id );

        wp_send_json_success( array(
            'message' => __( 'Registration successful! Your account is pending approval.', 'ytrip' ),
            'user_id' => $user_id,
        ) );
    }

    /**
     * Notify admin of new agent registration
     */
    private function notify_admin_new_agent( $user_id ) {
        $user = get_user_by( 'ID', $user_id );
        $company = get_user_meta( $user_id, '_ytrip_agent_company', true );
        $admin_email = get_option( 'admin_email' );

        $subject = sprintf( __( '[%s] New Agent Registration: %s', 'ytrip' ), get_bloginfo( 'name' ), $company );
        $message = sprintf(
            __( "A new travel agent has registered and requires approval.\n\nCompany: %s\nName: %s\nEmail: %s\n\nApprove in WordPress admin.", 'ytrip' ),
            $company,
            $user->display_name,
            $user->user_email
        );

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Render agent dashboard shortcode
     */
    public function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to access your agent dashboard.', 'ytrip' ) . '</p>';
        }

        if ( ! $this->is_agent() ) {
            return '<p>' . __( 'This dashboard is for approved travel agents only.', 'ytrip' ) . '</p>';
        }

        $user = wp_get_current_user();
        $data = self::get_agent_data( $user->ID );

        if ( $data['status'] === self::STATUS_PENDING ) {
            return '<div class="ytrip-notice ytrip-notice--warning">' . __( 'Your agent account is pending approval. You will receive an email once approved.', 'ytrip' ) . '</div>';
        }

        if ( $data['status'] === self::STATUS_SUSPENDED ) {
            return '<div class="ytrip-notice ytrip-notice--error">' . __( 'Your agent account has been suspended. Please contact support.', 'ytrip' ) . '</div>';
        }

        ob_start();
        ?>
        <div class="ytrip-agent-dashboard" id="ytrip-agent-dashboard">
            <div class="ytrip-agent-header">
                <h2><?php printf( __( 'Welcome, %s', 'ytrip' ), esc_html( $data['company_name'] ?: $user->display_name ) ); ?></h2>
            </div>

            <div class="ytrip-agent-stats">
                <div class="ytrip-stat-card">
                    <span class="ytrip-stat-value"><?php echo esc_html( $data['total_bookings'] ); ?></span>
                    <span class="ytrip-stat-label"><?php esc_html_e( 'Total Bookings', 'ytrip' ); ?></span>
                </div>
                <div class="ytrip-stat-card">
                    <span class="ytrip-stat-value"><?php echo wc_price( $data['total_commission'] ); ?></span>
                    <span class="ytrip-stat-label"><?php esc_html_e( 'Total Earned', 'ytrip' ); ?></span>
                </div>
                <div class="ytrip-stat-card">
                    <span class="ytrip-stat-value"><?php echo wc_price( $data['pending_payout'] ); ?></span>
                    <span class="ytrip-stat-label"><?php esc_html_e( 'Pending Payout', 'ytrip' ); ?></span>
                </div>
                <div class="ytrip-stat-card">
                    <span class="ytrip-stat-value"><?php echo esc_html( $data['commission_rate'] ); ?>%</span>
                    <span class="ytrip-stat-label"><?php esc_html_e( 'Commission Rate', 'ytrip' ); ?></span>
                </div>
            </div>

            <div class="ytrip-agent-actions">
                <button class="ytrip-btn ytrip-btn--primary" id="ytrip-new-booking-btn">
                    <?php esc_html_e( 'Create New Booking', 'ytrip' ); ?>
                </button>
            </div>

            <div class="ytrip-agent-bookings">
                <h3><?php esc_html_e( 'Recent Bookings', 'ytrip' ); ?></h3>
                <div id="ytrip-bookings-list">
                    <p><?php esc_html_e( 'Loading...', 'ytrip' ); ?></p>
                </div>
            </div>
        </div>

        <style>
            .ytrip-agent-dashboard { max-width: 1200px; margin: 0 auto; }
            .ytrip-agent-header { margin-bottom: 30px; }
            .ytrip-agent-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .ytrip-stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; }
            .ytrip-stat-value { display: block; font-size: 32px; font-weight: bold; margin-bottom: 5px; }
            .ytrip-stat-label { opacity: 0.9; font-size: 14px; }
            .ytrip-agent-actions { margin-bottom: 30px; }
            .ytrip-btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
            .ytrip-btn--primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .ytrip-notice { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
            .ytrip-notice--warning { background: #fff3cd; color: #856404; }
            .ytrip-notice--error { background: #f8d7da; color: #721c24; }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load bookings via REST API
            fetch('<?php echo esc_url( rest_url( 'ytrip/v1/agent/bookings' ) ); ?>', {
                headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
            })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('ytrip-bookings-list');
                if (data.bookings && data.bookings.length) {
                    let html = '<table style="width:100%;border-collapse:collapse;"><thead><tr><th>Order</th><th>Tour</th><th>Date</th><th>Customer</th><th>Commission</th><th>Status</th></tr></thead><tbody>';
                    data.bookings.forEach(b => {
                        html += `<tr>
                            <td>#${b.order_id}</td>
                            <td>${b.tour.title}</td>
                            <td>${b.booking_date}</td>
                            <td>${b.customer.name}</td>
                            <td>${b.commission || '-'}</td>
                            <td>${b.status}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p><?php esc_html_e( 'No bookings yet.', 'ytrip' ); ?></p>';
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render registration form shortcode
     */
    public function render_registration( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>' . __( 'You are already logged in.', 'ytrip' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="ytrip-agent-register">
            <h2><?php esc_html_e( 'Become a Travel Agent Partner', 'ytrip' ); ?></h2>
            <form id="ytrip-agent-register-form">
                <?php wp_nonce_field( 'ytrip_nonce', 'nonce' ); ?>
                
                <div class="ytrip-form-row">
                    <label><?php esc_html_e( 'Company Name', 'ytrip' ); ?> *</label>
                    <input type="text" name="company_name" required>
                </div>
                
                <div class="ytrip-form-row ytrip-form-row--half">
                    <div>
                        <label><?php esc_html_e( 'First Name', 'ytrip' ); ?> *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div>
                        <label><?php esc_html_e( 'Last Name', 'ytrip' ); ?> *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                
                <div class="ytrip-form-row">
                    <label><?php esc_html_e( 'Email', 'ytrip' ); ?> *</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="ytrip-form-row">
                    <label><?php esc_html_e( 'Phone', 'ytrip' ); ?></label>
                    <input type="tel" name="phone">
                </div>
                
                <div class="ytrip-form-row">
                    <label><?php esc_html_e( 'Password', 'ytrip' ); ?> * (min 8 characters)</label>
                    <input type="password" name="password" minlength="8" required>
                </div>
                
                <button type="submit" class="ytrip-btn ytrip-btn--primary">
                    <?php esc_html_e( 'Register as Agent', 'ytrip' ); ?>
                </button>
                
                <div id="ytrip-register-message" style="margin-top:15px;"></div>
            </form>
        </div>

        <style>
            .ytrip-agent-register { max-width: 500px; margin: 0 auto; padding: 30px; background: #f8f9fa; border-radius: 15px; }
            .ytrip-form-row { margin-bottom: 20px; }
            .ytrip-form-row label { display: block; margin-bottom: 5px; font-weight: 600; }
            .ytrip-form-row input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
            .ytrip-form-row--half { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        </style>

        <script>
        document.getElementById('ytrip-agent-register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'ytrip_agent_register');
            
            fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                const msgEl = document.getElementById('ytrip-register-message');
                if (data.success) {
                    msgEl.innerHTML = '<div style="background:#d4edda;color:#155724;padding:15px;border-radius:8px;">' + data.data.message + '</div>';
                    this.reset();
                } else {
                    msgEl.innerHTML = '<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;">' + data.data.message + '</div>';
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Admin: Approve agent
     */
    public static function approve_agent( $user_id ) {
        update_user_meta( $user_id, '_ytrip_agent_status', self::STATUS_APPROVED );
        update_user_meta( $user_id, '_ytrip_agent_approved_date', current_time( 'mysql' ) );

        // Notify agent
        $user = get_user_by( 'ID', $user_id );
        wp_mail(
            $user->user_email,
            sprintf( __( '[%s] Your Agent Account is Approved!', 'ytrip' ), get_bloginfo( 'name' ) ),
            sprintf( __( "Congratulations! Your travel agent account has been approved.\n\nYou can now log in and start creating bookings for your clients.\n\nLogin: %s", 'ytrip' ), wp_login_url() )
        );
    }

    /**
     * Admin: Suspend agent
     */
    public static function suspend_agent( $user_id ) {
        update_user_meta( $user_id, '_ytrip_agent_status', self::STATUS_SUSPENDED );
    }
}

// Initialize
new YTrip_Agent_Portal();
