<?php
/**
 * YTrip PDF Itinerary Generator
 * 
 * Generates professional PDF itineraries for tours and bookings.
 * Uses DOMPDF for HTML-to-PDF conversion (fallback to simple HTML if not available).
 * 
 * @package YTrip
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_PDF_Generator {

    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action( 'wp_ajax_ytrip_download_itinerary', array( $this, 'ajax_download_itinerary' ) );
        add_action( 'wp_ajax_nopriv_ytrip_download_itinerary', array( $this, 'ajax_download_itinerary' ) );
        
        // Add download button to single tour
        add_action( 'ytrip_after_tour_content', array( $this, 'add_download_button' ) );
    }

    /**
     * Generate PDF for a tour
     * 
     * @param int   $tour_id Tour post ID.
     * @param array $options Options (include_pricing, etc.).
     * @return string|false PDF content or false on failure.
     */
    public function generate_tour_pdf( $tour_id, $options = array() ) {
        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return false;
        }

        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        $settings = get_option( 'ytrip_settings' );

        // Build HTML content
        $html = $this->build_itinerary_html( $post, $meta, $settings, $options );

        // Try to use DOMPDF if available
        if ( $this->is_dompdf_available() ) {
            return $this->generate_with_dompdf( $html );
        }

        // Fallback: Return HTML for browser rendering/printing
        return $html;
    }

    /**
     * Build HTML for itinerary
     */
    private function build_itinerary_html( $post, $meta, $settings, $options ) {
        $company_name = $settings['company_name'] ?? get_bloginfo( 'name' );
        $company_logo = $settings['company_logo']['url'] ?? '';
        $company_phone = $settings['company_phone'] ?? '';
        $company_email = $settings['company_email'] ?? get_option( 'admin_email' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html( $post->post_title ); ?> - Itinerary</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'DejaVu Sans', Arial, sans-serif; 
                    font-size: 12px; 
                    line-height: 1.6;
                    color: #333;
                }
                .container { padding: 20px 40px; }
                
                /* Header */
                .header {
                    display: table;
                    width: 100%;
                    border-bottom: 3px solid #0066cc;
                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }
                .header-logo { display: table-cell; width: 150px; vertical-align: middle; }
                .header-logo img { max-width: 150px; max-height: 60px; }
                .header-info { display: table-cell; text-align: right; vertical-align: middle; }
                .header-info p { margin: 2px 0; font-size: 11px; color: #666; }
                
                /* Title Section */
                .tour-title {
                    background: linear-gradient(135deg, #0066cc, #004499);
                    color: white;
                    padding: 20px;
                    margin: -20px -40px 20px;
                    text-align: center;
                }
                .tour-title h1 { font-size: 24px; margin-bottom: 5px; }
                .tour-title .duration { font-size: 14px; opacity: 0.9; }
                
                /* Info Box */
                .info-box {
                    background: #f8f9fa;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 20px;
                }
                .info-box table { width: 100%; }
                .info-box td { padding: 5px 10px; }
                .info-box .label { font-weight: bold; color: #666; width: 30%; }
                
                /* Itinerary */
                .day-section {
                    margin-bottom: 25px;
                    page-break-inside: avoid;
                }
                .day-header {
                    background: #0066cc;
                    color: white;
                    padding: 10px 15px;
                    font-weight: bold;
                    font-size: 14px;
                    border-radius: 5px 5px 0 0;
                }
                .day-content {
                    border: 1px solid #e0e0e0;
                    border-top: none;
                    padding: 15px;
                    border-radius: 0 0 5px 5px;
                }
                
                /* Includes/Excludes */
                .two-column { display: table; width: 100%; margin-bottom: 20px; }
                .column { display: table-cell; width: 50%; padding: 10px; vertical-align: top; }
                .column h3 { font-size: 14px; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid; }
                .column.included h3 { color: #28a745; border-color: #28a745; }
                .column.excluded h3 { color: #dc3545; border-color: #dc3545; }
                .column ul { list-style: none; padding-left: 0; }
                .column li { padding: 3px 0; padding-left: 20px; position: relative; }
                .column.included li:before { content: "✓"; position: absolute; left: 0; color: #28a745; }
                .column.excluded li:before { content: "✗"; position: absolute; left: 0; color: #dc3545; }
                
                /* Footer */
                .footer {
                    position: fixed;
                    bottom: 20px;
                    left: 40px;
                    right: 40px;
                    text-align: center;
                    font-size: 10px;
                    color: #999;
                    border-top: 1px solid #e0e0e0;
                    padding-top: 10px;
                }
                
                /* Page break control */
                .page-break { page-break-before: always; }
            </style>
        </head>
        <body>
            <div class="container">
                <!-- Header -->
                <div class="header">
                    <div class="header-logo">
                        <?php if ( $company_logo ) : ?>
                            <img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>">
                        <?php else : ?>
                            <strong style="font-size: 18px;"><?php echo esc_html( $company_name ); ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="header-info">
                        <?php if ( $company_phone ) : ?>
                            <p>📞 <?php echo esc_html( $company_phone ); ?></p>
                        <?php endif; ?>
                        <p>✉ <?php echo esc_html( $company_email ); ?></p>
                        <p>🌐 <?php echo esc_url( home_url() ); ?></p>
                    </div>
                </div>

                <!-- Tour Title -->
                <div class="tour-title">
                    <h1><?php echo esc_html( $post->post_title ); ?></h1>
                    <?php
                    $duration = $meta['tour_duration'] ?? array();
                    $days = $duration['days'] ?? 1;
                    $nights = $duration['nights'] ?? 0;
                    ?>
                    <div class="duration">
                        <?php printf( _n( '%d Day', '%d Days', $days, 'ytrip' ), $days ); ?>
                        <?php if ( $nights ) : ?>
                            / <?php printf( _n( '%d Night', '%d Nights', $nights, 'ytrip' ), $nights ); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="info-box">
                    <table>
                        <tr>
                            <td class="label"><?php esc_html_e( 'Destination', 'ytrip' ); ?></td>
                            <td><?php echo implode( ', ', wp_get_post_terms( $post->ID, 'ytrip_destination', array( 'fields' => 'names' ) ) ); ?></td>
                            <td class="label"><?php esc_html_e( 'Difficulty', 'ytrip' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $meta['difficulty'] ?? 'moderate' ) ); ?></td>
                        </tr>
                        <tr>
                            <td class="label"><?php esc_html_e( 'Group Size', 'ytrip' ); ?></td>
                            <td>
                                <?php
                                $group = $meta['group_size'] ?? array();
                                echo esc_html( ( $group['min'] ?? 1 ) . ' - ' . ( $group['max'] ?? 50 ) . ' ' . __( 'persons', 'ytrip' ) );
                                ?>
                            </td>
                            <td class="label"><?php esc_html_e( 'Age', 'ytrip' ); ?></td>
                            <td>
                                <?php
                                $age = $meta['age_restriction'] ?? array();
                                echo esc_html( ( $age['min_age'] ?? 0 ) . ' - ' . ( $age['max_age'] ?? 99 ) . ' ' . __( 'years', 'ytrip' ) );
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Description -->
                <?php if ( $post->post_content ) : ?>
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 16px; color: #0066cc; margin-bottom: 10px;"><?php esc_html_e( 'Overview', 'ytrip' ); ?></h2>
                        <p><?php echo wp_strip_all_tags( $post->post_content ); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Itinerary -->
                <?php
                $itinerary = $meta['itinerary'] ?? array();
                if ( ! empty( $itinerary ) ) :
                ?>
                    <h2 style="font-size: 16px; color: #0066cc; margin-bottom: 15px;"><?php esc_html_e( 'Daily Itinerary', 'ytrip' ); ?></h2>
                    <?php foreach ( $itinerary as $day ) : ?>
                        <div class="day-section">
                            <div class="day-header">
                                <?php echo esc_html( sprintf( __( 'Day %d', 'ytrip' ), $day['day_number'] ?? 1 ) ); ?>
                                <?php if ( ! empty( $day['day_title'] ) ) : ?>
                                    - <?php echo esc_html( $day['day_title'] ); ?>
                                <?php endif; ?>
                            </div>
                            <div class="day-content">
                                <?php echo wp_strip_all_tags( $day['day_description'] ?? '' ); ?>
                                
                                <?php if ( ! empty( $day['activities'] ) ) : ?>
                                    <ul style="margin-top: 10px;">
                                        <?php foreach ( $day['activities'] as $activity ) : ?>
                                            <li>
                                                <?php if ( ! empty( $activity['time'] ) ) : ?>
                                                    <strong><?php echo esc_html( $activity['time'] ); ?></strong> -
                                                <?php endif; ?>
                                                <?php echo esc_html( $activity['activity'] ?? '' ); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Includes / Excludes -->
                <div class="two-column">
                    <div class="column included">
                        <h3><?php esc_html_e( "What's Included", 'ytrip' ); ?></h3>
                        <ul>
                            <?php
                            $included = $meta['included'] ?? array();
                            foreach ( $included as $item ) :
                            ?>
                                <li><?php echo esc_html( $item['item'] ?? '' ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="column excluded">
                        <h3><?php esc_html_e( "What's Not Included", 'ytrip' ); ?></h3>
                        <ul>
                            <?php
                            $excluded = $meta['excluded'] ?? array();
                            foreach ( $excluded as $item ) :
                            ?>
                                <li><?php echo esc_html( $item['item'] ?? '' ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <?php echo esc_html( $company_name ); ?> | 
                    <?php esc_html_e( 'Generated on', 'ytrip' ); ?> <?php echo date_i18n( get_option( 'date_format' ) ); ?> |
                    <?php echo esc_url( get_permalink( $post->ID ) ); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if DOMPDF is available
     */
    private function is_dompdf_available() {
        return class_exists( 'Dompdf\\Dompdf' );
    }

    /**
     * Generate PDF using DOMPDF
     */
    private function generate_with_dompdf( $html ) {
        if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
            return false;
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml( $html );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * AJAX handler for downloading itinerary
     */
    public function ajax_download_itinerary() {
        $tour_id = isset( $_GET['tour_id'] ) ? absint( $_GET['tour_id'] ) : 0;

        if ( ! $tour_id ) {
            wp_die( __( 'Invalid tour ID', 'ytrip' ) );
        }

        $post = get_post( $tour_id );
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            wp_die( __( 'Tour not found', 'ytrip' ) );
        }

        $content = $this->generate_tour_pdf( $tour_id );

        if ( $this->is_dompdf_available() ) {
            // Output PDF
            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $post->post_title ) . '-itinerary.pdf"' );
            echo $content;
        } else {
            // Output HTML for printing
            header( 'Content-Type: text/html; charset=utf-8' );
            echo $content;
            echo '<script>window.print();</script>';
        }

        exit;
    }

    /**
     * Add download button to tour page
     */
    public function add_download_button() {
        global $post;
        if ( ! $post || $post->post_type !== 'ytrip_tour' ) {
            return;
        }

        $url = admin_url( 'admin-ajax.php?action=ytrip_download_itinerary&tour_id=' . $post->ID );
        ?>
        <a href="<?php echo esc_url( $url ); ?>" 
           class="ytrip-btn ytrip-btn--secondary ytrip-download-itinerary" 
           target="_blank"
           style="margin-top: 15px; display: inline-flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <?php esc_html_e( 'Download Itinerary (PDF)', 'ytrip' ); ?>
        </a>
        <?php
    }

    /**
     * Generate booking confirmation PDF
     */
    public function generate_booking_pdf( $order_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $tour_id = $order->get_meta( '_ytrip_tour_id' );
        if ( ! $tour_id ) {
            return false;
        }

        $post = get_post( $tour_id );
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        $settings = get_option( 'ytrip_settings' );
        $pricing = $order->get_meta( '_ytrip_pricing_breakdown' );

        // This could be extended to generate a booking-specific PDF
        // For now, delegate to tour PDF with booking info overlay
        return $this->generate_tour_pdf( $tour_id, array(
            'booking' => array(
                'order_id'   => $order->get_id(),
                'date'       => $order->get_meta( '_ytrip_booking_date' ),
                'persons'    => $order->get_meta( '_ytrip_persons' ),
                'customer'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'total'      => $order->get_total(),
            ),
        ) );
    }
}

// Initialize
new YTrip_PDF_Generator();
