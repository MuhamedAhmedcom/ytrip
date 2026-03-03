<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * YTrip AJAX Handler
 * 
 * Handles general AJAX requests not covered by specific modules.
 * 
 * @package YTrip
 * @since 1.0.0
 */
class YTrip_AJAX {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Inquiry form
        add_action( 'wp_ajax_ytrip_submit_inquiry', array( $this, 'submit_inquiry' ) );
        add_action( 'wp_ajax_nopriv_ytrip_submit_inquiry', array( $this, 'submit_inquiry' ) );
    }

    /**
     * Handle inquiry submission.
     */
    public function submit_inquiry() {
        // Security check (Globally handled by Security Engine as 'contact', but manual check for safety)
        if ( ! YTrip_Security_Engine::verify_nonce( $_REQUEST['nonce'] ?? '', 'contact' ) ) {
             wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ytrip' ) ) );
        }

        $tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
        $name    = isset( $_POST['name'] ) ? YTrip_Security_Engine::sanitize( $_POST['name'] ) : '';
        $email   = isset( $_POST['email'] ) ? YTrip_Security_Engine::sanitize( $_POST['email'], 'email' ) : '';
        $message = isset( $_POST['message'] ) ? YTrip_Security_Engine::sanitize( $_POST['message'], 'textarea' ) : '';

        if ( ! $tour_id || ! $email || ! $name ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'ytrip' ) ) );
        }

        // Get Recipient
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        $to   = isset( $meta['inquiry_email'] ) && ! empty( $meta['inquiry_email'] ) ? $meta['inquiry_email'] : get_option( 'admin_email' );
        
        // Subject & Body
        $subject = sprintf( __( 'New Inquiry: %s', 'ytrip' ), get_the_title( $tour_id ) );
        $body    = "Name: $name\n";
        $body   .= "Email: $email\n";
        $body   .= "Tour: " . get_the_title( $tour_id ) . " (" . get_permalink( $tour_id ) . ")\n\n";
        $body   .= "Message:\n$message\n";

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';

        if ( wp_mail( $to, $subject, $body, $headers ) ) {
            wp_send_json_success( array( 'message' => __( 'Your inquiry has been sent successfully.', 'ytrip' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send email. Please try again later.', 'ytrip' ) ) );
        }
    }
}

new YTrip_AJAX();
