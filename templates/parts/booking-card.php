<?php
/**
 * Booking Card Part
 * Reusable ultra-modern booking widget
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
?>

<?php do_action( 'ytrip_before_booking_form' ); ?>
<div class="ytrip-booking-widget">
    <!-- Price Header -->
    <?php if ( $product ) : ?>
    <div class="ytrip-booking-widget__price">
        <span class="ytrip-booking-widget__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
        <span class="ytrip-booking-widget__amount"><?php echo $product->get_price_html(); ?></span>
        <span class="ytrip-booking-widget__per"><?php esc_html_e( 'per person', 'ytrip' ); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Booking Form -->
    <form class="ytrip-booking-widget__form" method="post">
        <div class="ytrip-form-group ytrip-date-group">
            <label for="ytrip-date-display"><?php esc_html_e( 'Select Date', 'ytrip' ); ?></label>
            <?php
            $args = array(
                'display_id'   => 'ytrip-date-display',
                'hidden_name'  => 'tour_date',
                'hidden_id'    => 'ytrip-tour-date',
                'container_id' => 'ytrip-calendar-container',
                'placeholder'  => __( 'Select dates', 'ytrip' ),
                'required'     => true,
            );
            include YTRIP_PATH . 'templates/parts/calendar-single.php';
            ?>
        </div>
        
        <div class="ytrip-form-group ytrip-guest-group">
            <label for="ytrip-guests-display"><?php esc_html_e( 'Guests', 'ytrip' ); ?></label>
            <div class="ytrip-guest-input-wrapper">
                <input type="text" id="ytrip-guests-display" class="ytrip-guests-display" value="1 Adult" readonly aria-label="<?php esc_attr_e( 'Number of guests', 'ytrip' ); ?>">
                <div class="ytrip-guest-dropdown" id="ytrip-guest-container">
                    
                    <!-- Adults -->
                    <div class="ytrip-guest-row">
                        <div class="ytrip-guest-label">
                            <span class="ytrip-guest-type"><?php esc_html_e( 'Adults', 'ytrip' ); ?></span>
                            <span class="ytrip-guest-age"><?php esc_html_e( 'Age 12+', 'ytrip' ); ?></span>
                        </div>
                        <div class="ytrip-guest-stepper">
                            <button type="button" class="ytrip-qty-btn" data-action="minus" data-target="adults" disabled>-</button>
                            <span class="ytrip-qty-val" id="val-adults">1</span>
                            <button type="button" class="ytrip-qty-btn" data-action="plus" data-target="adults">+</button>
                        </div>
                    </div>

                    <!-- Children -->
                    <div class="ytrip-guest-row">
                        <div class="ytrip-guest-label">
                            <span class="ytrip-guest-type"><?php esc_html_e( 'Children', 'ytrip' ); ?></span>
                            <span class="ytrip-guest-age"><?php esc_html_e( 'Age 2-12', 'ytrip' ); ?></span>
                        </div>
                        <div class="ytrip-guest-stepper">
                            <button type="button" class="ytrip-qty-btn" data-action="minus" data-target="children" disabled>-</button>
                            <span class="ytrip-qty-val" id="val-children">0</span>
                            <button type="button" class="ytrip-qty-btn" data-action="plus" data-target="children">+</button>
                        </div>
                    </div>

                    <!-- Infants -->
                    <div class="ytrip-guest-row">
                        <div class="ytrip-guest-label">
                            <span class="ytrip-guest-type"><?php esc_html_e( 'Infants', 'ytrip' ); ?></span>
                            <span class="ytrip-guest-age"><?php esc_html_e( 'Under 2', 'ytrip' ); ?></span>
                        </div>
                        <div class="ytrip-guest-stepper">
                            <button type="button" class="ytrip-qty-btn" data-action="minus" data-target="infants" disabled>-</button>
                            <span class="ytrip-qty-val" id="val-infants">0</span>
                            <button type="button" class="ytrip-qty-btn" data-action="plus" data-target="infants">+</button>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Hidden Inputs for Form Submission -->
            <input type="hidden" name="adults" id="ytrip-field-adults" value="1">
            <input type="hidden" name="children" id="ytrip-field-children" value="0">
            <input type="hidden" name="infants" id="ytrip-field-infants" value="0">
        </div>
        
<?php if ( $product ) : ?>
        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">
        <?php endif; ?>
        <input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
        <?php wp_nonce_field( 'ytrip_booking', 'ytrip_booking_nonce' ); ?>
        
        <?php 
        // Get settings for require login and reCAPTCHA
        $ytrip_settings = get_option( 'ytrip_settings', array() );
        $require_login = ! empty( $ytrip_settings['require_login'] ) && $ytrip_settings['require_login'] === 'yes';
        $current_user = wp_get_current_user();
        ?>
        
        <?php if ( $require_login && ! is_user_logged_in() ) : ?>
        <!-- Login Required Message -->
        <div class="ytrip-login-required">
            <p><?php esc_html_e( 'Please login to make a booking.', 'ytrip' ); ?></p>
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="ytrip-btn ytrip-btn--primary ytrip-btn--block">
                <?php esc_html_e( 'Login to Book', 'ytrip' ); ?>
            </a>
        </div>
        <?php else : ?>
        
        <!-- Contact Information -->
        <div class="ytrip-form-group">
            <label for="ytrip-booking-email"><?php esc_html_e( 'Email', 'ytrip' ); ?> <span class="required">*</span></label>
            <input type="email" 
                   id="ytrip-booking-email" 
                   name="booking_email" 
                   value="<?php echo esc_attr( $current_user->user_email ?? '' ); ?>"
                   placeholder="<?php esc_attr_e( 'your@email.com', 'ytrip' ); ?>"
                   required>
        </div>
        
        <div class="ytrip-form-group">
            <label for="ytrip-booking-phone"><?php esc_html_e( 'Phone', 'ytrip' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'ytrip' ); ?>)</span></label>
            <input type="tel" 
                   id="ytrip-booking-phone" 
                   name="booking_phone" 
                   placeholder="<?php esc_attr_e( '+1 234 567 8900', 'ytrip' ); ?>">
        </div>
        
        <!-- Honeypot Anti-Spam (hidden from users) -->
        <div class="ytrip-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden;">
            <label for="ytrip-website"><?php esc_html_e( 'Website', 'ytrip' ); ?></label>
            <input type="text" name="ytrip_website" id="ytrip-website" tabindex="-1" autocomplete="off">
        </div>
        
        <button type="submit" class="ytrip-btn ytrip-btn--primary ytrip-btn--block">
            <?php esc_html_e( 'Book Now', 'ytrip' ); ?>
        </button>
        <?php endif; ?>
    </form>
    
    <!-- Trust Badges -->
    <div class="ytrip-trust-badges">
        <div class="ytrip-trust-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>
            </svg>
            <span><?php esc_html_e( 'Free Cancellation', 'ytrip' ); ?></span>
        </div>
        <div class="ytrip-trust-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span><?php esc_html_e( 'Secure Payment', 'ytrip' ); ?></span>
        </div>
        <div class="ytrip-trust-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
            <span><?php esc_html_e( 'Instant Confirmation', 'ytrip' ); ?></span>
        </div>
    </div>
    
    <!-- Contact -->
    <div class="ytrip-booking-widget__contact">
        <p><?php esc_html_e( 'Need help?', 'ytrip' ); ?></p>
        <a href="#" class="ytrip-contact-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
            </svg>
            <?php esc_html_e( 'Contact Us', 'ytrip' ); ?>
        </a>
    </div>
</div>
