<?php
/**
 * YTrip Booking Form Handler
 *
 * Handles booking form submission, validation, spam protection,
 * and WooCommerce integration.
 *
 * @package YTrip
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking Form Handler Class
 */
class YTrip_Booking_Form {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Settings array.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = get_option( 'ytrip_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Process booking form.
		add_action( 'init', array( $this, 'process_booking' ) );

		// Validate reCAPTCHA.
		add_action( 'wp_ajax_ytrip_verify_recaptcha', array( $this, 'verify_recaptcha' ) );
		add_action( 'wp_ajax_nopriv_ytrip_verify_recaptcha', array( $this, 'verify_recaptcha' ) );

		// Enqueue scripts for booking form.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Show booking validation errors / success after redirect.
		add_action( 'wp', array( $this, 'maybe_show_booking_feedback' ) );
		// Fallback: prepend notice to content so it shows even if theme skips wp_footer.
		add_filter( 'the_content', array( $this, 'maybe_prepend_booking_feedback_to_content' ), 5 );
	}

	/**
	 * Check if booking requires login.
	 *
	 * @return bool
	 */
	public function requires_login() {
		return ! empty( $this->settings['require_login'] ) && $this->settings['require_login'] === 'yes';
	}

	/**
	 * Check if reCAPTCHA is enabled.
	 *
	 * @return bool
	 */
	public function is_recaptcha_enabled() {
		return ! empty( $this->settings['recaptcha_site_key'] ) && ! empty( $this->settings['recaptcha_secret_key'] );
	}

	/**
	 * Get reCAPTCHA site key.
	 *
	 * @return string
	 */
	public function get_recaptcha_site_key() {
		return $this->settings['recaptcha_site_key'] ?? '';
	}

	/**
	 * Enqueue booking form scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'ytrip_tour' ) ) {
			return;
		}

		// Enqueue reCAPTCHA if enabled.
		if ( $this->is_recaptcha_enabled() ) {
			wp_enqueue_script(
				'google-recaptcha',
				'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $this->get_recaptcha_site_key() ),
				array(),
				null,
				true
			);
		}

		$booking_script = 'ytrip-booking-form';
		wp_enqueue_script(
			$booking_script,
			YTRIP_URL . 'assets/js/booking-form.js',
			array( 'jquery' ),
			YTRIP_VERSION,
			true
		);
		wp_localize_script( $booking_script, 'ytripBookingConfig', array(
			'requiresLogin' => $this->requires_login(),
			'isLoggedIn'   => is_user_logged_in(),
			'loginUrl'     => wp_login_url( get_permalink() ),
			'recaptchaKey' => $this->get_recaptcha_site_key(),
		) );
		wp_localize_script( $booking_script, 'ytripBookingStrings', array(
			'selectDate'     => __( 'Please select a date.', 'ytrip' ),
			'required'       => __( 'This field is required.', 'ytrip' ),
			'invalidEmail'   => __( 'Please enter a valid email address.', 'ytrip' ),
			'loginToBook'    => __( 'Login to Book', 'ytrip' ),
			'processing'     => __( 'Processing...', 'ytrip' ),
			'confirmTitle'   => __( 'Confirm booking', 'ytrip' ),
			'confirmMessage' => __( 'By confirming, your booking will be submitted. You will receive a confirmation email and we will process your request. Proceed to checkout?', 'ytrip' ),
			'confirmBtn'     => __( 'Confirm & continue', 'ytrip' ),
			'cancelBtn'      => __( 'Cancel', 'ytrip' ),
			'pleaseFix'      => __( 'Please fix the following:', 'ytrip' ),
		) );
		add_action( 'wp_footer', array( $this, 'output_booking_modal_and_notice_assets' ), 5 );
	}

	/**
	 * Output confirmation modal HTML and notice/notification styles (single tour only).
	 */
	public function output_booking_modal_and_notice_assets() {
		if ( ! is_singular( 'ytrip_tour' ) ) {
			return;
		}
		$with_wc = function_exists( 'WC' );
		$confirm_msg = $with_wc
			? __( 'By confirming, your booking will be added to the cart and you will receive a confirmation email after checkout. Continue?', 'ytrip' )
			: __( 'By confirming, your booking request will be sent. You will receive a confirmation email and we will contact you shortly. Continue?', 'ytrip' );
		?>
		<div id="ytrip-booking-confirm-modal" class="ytrip-modal" role="dialog" aria-modal="true" aria-labelledby="ytrip-booking-confirm-title" aria-hidden="true" style="display:none;">
			<div class="ytrip-modal__backdrop" id="ytrip-booking-confirm-backdrop"></div>
			<div class="ytrip-modal__panel">
				<h2 id="ytrip-booking-confirm-title" class="ytrip-modal__title"><?php esc_html_e( 'Confirm booking', 'ytrip' ); ?></h2>
				<p class="ytrip-modal__text"><?php echo esc_html( $confirm_msg ); ?></p>
				<div class="ytrip-modal__actions">
					<button type="button" class="ytrip-btn ytrip-btn--outline ytrip-booking-confirm-cancel"><?php esc_html_e( 'Cancel', 'ytrip' ); ?></button>
					<button type="button" class="ytrip-btn ytrip-btn--primary ytrip-booking-confirm-ok"><?php esc_html_e( 'Confirm & continue', 'ytrip' ); ?></button>
				</div>
			</div>
		</div>
		<style>
			.ytrip-booking-notice { position: relative; padding: 1rem 2.5rem 1rem 1rem; margin: 0 0 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
			.ytrip-booking-notice.ytrip-booking-notice--sticky { position: fixed; top: 0; left: 0; right: 0; z-index: 999999; margin: 0; border-radius: 0; max-height: 50vh; overflow: auto; }
			.ytrip-booking-notice--error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
			.ytrip-booking-notice--success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
			.ytrip-booking-notice ul { margin: .5rem 0 0; padding-left: 1.25rem; }
			.ytrip-booking-notice .ytrip-booking-notice__dismiss { position: absolute; top: .5rem; right: .5rem; background: none; border: none; font-size: 1.25rem; cursor: pointer; opacity: .7; line-height: 1; padding: 0 .25rem; }
			.ytrip-booking-notice .ytrip-booking-notice__dismiss:hover { opacity: 1; }
			.ytrip-modal { position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 1rem; }
			.ytrip-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
			.ytrip-modal__panel { position: relative; background: #fff; border-radius: 12px; padding: 1.5rem; max-width: 420px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,.25); }
			.ytrip-modal__title { margin: 0 0 .75rem; font-size: 1.25rem; }
			.ytrip-modal__text { margin: 0 0 1.25rem; color: #475569; }
			.ytrip-modal__actions { display: flex; gap: .75rem; justify-content: flex-end; }
			.ytrip-field-error { border-color: #dc2626 !important; background-color: #fef2f2 !important; }
			.ytrip-error-message { display: block; color: #dc2626; font-size: 0.8125rem; margin-top: 0.25rem; }
			.ytrip-booking-validation-summary { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; }
		</style>
		<?php
	}

	/**
	 * Get validation JavaScript.
	 *
	 * @return string
	 */
	private function get_validation_script() {
		$recaptcha_key = esc_js( $this->get_recaptcha_site_key() );
		$requires_login = $this->requires_login() ? 'true' : 'false';
		$is_logged_in = is_user_logged_in() ? 'true' : 'false';
		$login_url = esc_js( wp_login_url( get_permalink() ) );

		$s = 'typeof ytripBookingStrings !== "undefined" ? ytripBookingStrings : (typeof ytripAjax !== "undefined" && ytripAjax.strings ? ytripAjax.strings : {})';
		return "
(function($) {
	'use strict';
	var s = " . $s . ";
	var str = function(key, fallback) { return (s[key] !== undefined ? s[key] : fallback) || ''; };

	var ytripBooking = {
		requiresLogin: {$requires_login},
		isLoggedIn: {$is_logged_in},
		loginUrl: '{$login_url}',
		recaptchaKey: '{$recaptcha_key}',
		pendingForm: null,

		init: function() {
			this.form = $('.ytrip-booking-widget__form');
			if (this.form.length) {
				this.bindEvents();
				this.checkLoginRestriction();
			}
			$('.ytrip-booking-form').each(function() {
				ytripBooking.bindClassicForm($(this));
			});
			ytripBooking.bindConfirmModal();
			ytripBooking.bindNoticeDismiss();
		},

		bindClassicForm: function(form) {
			var self = this;
			form.on('submit', function(e) {
				var dateInput = form.find('input[name=\"booking_date\"]');
				var dateVal = dateInput.length ? dateInput.first().val() : '';
				if (!dateVal || dateVal.trim() === '') {
					e.preventDefault();
					var wrap = form.find('.ytrip-form-group').has('input[name=\"booking_date\"]').first();
					var target = wrap.find('input[name=\"booking_date\"]').first();
					if (target.length) {
						self.showError(target, str('selectDate', 'Please select a date.'));
						target[0].focus && target[0].focus();
					}
					return false;
				}
				self.clearFormErrors(form);
				e.preventDefault();
				self.pendingForm = form[0];
				$('#ytrip-booking-confirm-modal').attr('aria-hidden', 'false').show();
				return false;
			});
		},

		bindConfirmModal: function() {
			var modal = $('#ytrip-booking-confirm-modal');
			if (!modal.length) return;
			modal.find('.ytrip-booking-confirm-cancel, .ytrip-modal__backdrop').on('click', function() {
				modal.attr('aria-hidden', 'true').hide();
				ytripBooking.pendingForm = null;
			});
			modal.find('.ytrip-booking-confirm-ok').on('click', function() {
				if (ytripBooking.pendingForm) {
					ytripBooking.pendingForm.submit();
					ytripBooking.pendingForm = null;
				}
				modal.attr('aria-hidden', 'true').hide();
			});
		},

		bindNoticeDismiss: function() {
			$(document).on('click', '.ytrip-booking-notice__dismiss', function() {
				$(this).closest('.ytrip-booking-notice').remove();
			});
			if ($('#ytrip-booking-errors, #ytrip-booking-success').length && window.history && window.history.replaceState) {
				var url = window.location.href.replace(/([?&])ytrip_booking_(errors|success)=[^&]+(&|$)/g, '$1').replace(/[?&]$/, '');
				if (url !== window.location.href) window.history.replaceState({}, '', url);
			}
		},

		clearFormErrors: function(form) {
			form.find('.ytrip-field-error').removeClass('ytrip-field-error');
			form.find('.ytrip-error-message').remove();
		},

		bindEvents: function() {
			var self = this;

			this.form.on('submit', function(e) {
				if (!self.validateForm()) {
					e.preventDefault();
					return false;
				}
				if (self.recaptchaKey) {
					e.preventDefault();
					self.executeRecaptcha();
					return false;
				}
				e.preventDefault();
				self.pendingForm = this;
				$('#ytrip-booking-confirm-modal').attr('aria-hidden', 'false').show();
				return false;
			});

			this.form.find('input[required], select[required]').on('blur', function() {
				self.validateField($(this));
			});
			this.form.find('input[type=\"email\"]').on('blur', function() {
				self.validateEmail($(this));
			});
		},

		checkLoginRestriction: function() {
			if (this.requiresLogin && !this.isLoggedIn) {
				this.form.find('button[type=\"submit\"]')
					.text(str('loginToBook', 'Login to Book'))
					.on('click', function(e) {
						e.preventDefault();
						window.location.href = ytripBooking.loginUrl;
					});
			}
		},

		validateForm: function() {
			var isValid = true;
			var self = this;

			this.form.find('input[required], select[required]').each(function() {
				if (!self.validateField($(this))) isValid = false;
			});
			var emailField = this.form.find('input[type=\"email\"]');
			if (emailField.length && !self.validateEmail(emailField)) isValid = false;
			var honeypot = this.form.find('.ytrip-hp-field input');
			if (honeypot.length && honeypot.val()) return false;
			var dateField = this.form.find('#ytrip-tour-date');
			if (!dateField.val()) {
				this.showError(this.form.find('#ytrip-date-display'), str('selectDate', 'Please select a date.'));
				isValid = false;
			}
			return isValid;
		},

		validateField: function(field) {
			var value = field.val().trim();
			if (field.prop('required') && !value) {
				this.showError(field, str('required', 'This field is required.'));
				return false;
			}
			this.clearError(field);
			return true;
		},

		validateEmail: function(field) {
			var email = field.val().trim();
			if (!email && !field.prop('required')) return true;
			var emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
			if (!emailRegex.test(email)) {
				this.showError(field, str('invalidEmail', 'Please enter a valid email.'));
				return false;
			}
			this.clearError(field);
			return true;
		},

		showError: function(field, message) {
			field.addClass('ytrip-field-error');
			var errorEl = field.siblings('.ytrip-error-message');
			if (!errorEl.length) {
				errorEl = $('<span class=\"ytrip-error-message\"></span>');
				field.after(errorEl);
			}
			errorEl.text(message);
		},

		clearError: function(field) {
			field.removeClass('ytrip-field-error');
			field.siblings('.ytrip-error-message').remove();
		},

		executeRecaptcha: function() {
			var self = this;
			var btn = this.form.find('button[type=\"submit\"]');
			btn.prop('disabled', true).text(str('processing', 'Processing...'));
			grecaptcha.ready(function() {
				grecaptcha.execute(self.recaptchaKey, { action: 'booking' }).then(function(token) {
					self.form.find('input[name=\"recaptcha_token\"]').val(token);
					self.pendingForm = self.form[0];
					$('#ytrip-booking-confirm-modal').attr('aria-hidden', 'false').show();
				});
			});
		}
	};

	$(document).ready(function() {
		ytripBooking.init();
	});
})(jQuery);
";
	}

	/**
	 * Process booking form submission.
	 *
	 * @return void
	 */
	public function process_booking() {
		if ( ! isset( $_POST['ytrip_booking_nonce'] ) ) {
			return;
		}

		// Verify nonce manually (POST request)
		if ( ! YTrip_Security_Engine::verify_nonce( sanitize_text_field( wp_unslash( $_POST['ytrip_booking_nonce'] ) ), 'booking' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ytrip' ) );
		}

		// Check login requirement.
		if ( $this->requires_login() && ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( wp_get_referer() ) );
			exit;
		}

		// Check honeypot.
		if ( ! empty( $_POST['ytrip_website'] ) ) {
			// Spam detected - silently fail.
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		// Verify reCAPTCHA if enabled.
		if ( $this->is_recaptcha_enabled() ) {
			$recaptcha_token = sanitize_text_field( $_POST['recaptcha_token'] ?? '' );
			if ( ! $this->verify_recaptcha_token( $recaptcha_token ) ) {
				wp_die( esc_html__( 'reCAPTCHA verification failed. Please try again.', 'ytrip' ) );
			}
		}

		$tour_id = absint( $_POST['tour_id'] ?? 0 );
		$tour_url = $tour_id ? get_permalink( $tour_id ) : false;
		$redirect_base = ( $tour_url && ! is_wp_error( $tour_url ) ) ? $tour_url : ( wp_get_referer() ?: home_url( '/' ) );

		// Validate required fields (date required; never proceed without it).
		$errors = $this->validate_booking_data( $_POST );
		if ( ! empty( $errors ) ) {
			$err_token = wp_generate_password( 16, false );
			set_transient( 'ytrip_booking_errors_' . $err_token, $errors, 300 );
			wp_safe_redirect( add_query_arg( 'ytrip_booking_errors', $err_token, $redirect_base ) );
			exit;
		}
		$date_value = YTrip_Security_Engine::sanitize( $_POST['tour_date'] ?? $_POST['booking_date'] ?? '', 'text' );
		$raw_email  = YTrip_Security_Engine::sanitize( $_POST['booking_email'] ?? '', 'email' );
		if ( empty( $raw_email ) && is_user_logged_in() ) {
			$user = get_userdata( get_current_user_id() );
			$raw_email = $user && ! empty( $user->user_email ) ? $user->user_email : '';
		}
		$booking_data = array(
			'tour_id'   => $tour_id,
			'tour_date' => $date_value,
			'adults'    => absint( $_POST['adults'] ?? 1 ),
			'children'  => absint( $_POST['children'] ?? 0 ),
			'infants'   => absint( $_POST['infants'] ?? 0 ),
			'email'     => $raw_email,
			'name'      => YTrip_Security_Engine::sanitize( $_POST['booking_name'] ?? '', 'text' ),
			'phone'     => YTrip_Security_Engine::sanitize( $_POST['booking_phone'] ?? '', 'phone' ),
			'notes'     => YTrip_Security_Engine::sanitize( $_POST['booking_notes'] ?? '', 'textarea' ),
		);

		$wc_active = function_exists( 'WC' ) && WC()->session;
		$product_id = absint( $_POST['add-to-cart'] ?? 0 );
		if ( $wc_active && $product_id && WC()->cart ) {
			WC()->session->set( 'ytrip_booking_data', $booking_data );
			$qty = max( 1, $booking_data['adults'] + $booking_data['children'] + $booking_data['infants'] );
			WC()->cart->add_to_cart( $product_id, $qty, 0, array(), array(
				'ytrip_tour_date' => $booking_data['tour_date'],
				'ytrip_adults'    => $booking_data['adults'],
				'ytrip_children'  => $booking_data['children'],
				'ytrip_infants'   => $booking_data['infants'],
			) );
			$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
			wp_safe_redirect( $cart_url );
			exit;
		}

		// Without WooCommerce or form without add-to-cart: save to ytrip_bookings and send emails.
		$this->save_booking_without_wc( $booking_data );
		$success_token = wp_generate_password( 16, false );
		set_transient( 'ytrip_booking_success_' . $success_token, 1, 300 );
		wp_safe_redirect( add_query_arg( 'ytrip_booking_success', $success_token, $redirect_base ) );
		exit;
	}

	/**
	 * Save booking to custom table and send confirmation emails (when WooCommerce is not active).
	 *
	 * @param array $booking_data Sanitized booking data.
	 * @return void
	 */
	private function save_booking_without_wc( array $booking_data ) {
		global $wpdb;
		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'ytrip_bookings';
		$name    = '';
		if ( ! empty( $booking_data['name'] ) ) {
			$name = substr( $booking_data['name'], 0, 100 );
		}
		if ( $name === '' && $user_id ) {
			$user = get_userdata( $user_id );
			$name = $user ? $user->display_name : '';
		}
		if ( $name === '' && ! empty( $booking_data['email'] ) ) {
			$name = $booking_data['email'];
		}
		$insert_data = array(
			'tour_id'        => $booking_data['tour_id'],
			'user_id'        => $user_id,
			'order_id'       => 0,
			'booking_date'   => $booking_data['tour_date'],
			'adults'         => $booking_data['adults'],
			'children'       => $booking_data['children'],
			'infants'        => isset( $booking_data['infants'] ) ? $booking_data['infants'] : 0,
			'total_price'    => 0,
			'status'         => 'pending',
			'customer_name'  => $name,
			'customer_email' => $booking_data['email'],
			'customer_phone' => $booking_data['phone'],
			'notes'          => $booking_data['notes'],
		);
		$wpdb->insert(
			$table,
			$insert_data,
			array( '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s' )
		);
		$this->send_booking_confirmation_emails( $booking_data, $name );
	}

	/**
	 * Send booking confirmation to customer and admin (no-WC flow) with HTML emails.
	 *
	 * @param array  $booking_data Sanitized booking data.
	 * @param string $customer_name Customer name.
	 * @return void
	 */
	private function send_booking_confirmation_emails( array $booking_data, string $customer_name ) {
		$tour_title = get_the_title( $booking_data['tour_id'] );
		$tour_link  = get_permalink( $booking_data['tour_id'] );
		$date_fmt   = date_i18n( get_option( 'date_format' ), strtotime( $booking_data['tour_date'] ) );
		$site_name  = get_bloginfo( 'name' );
		$to         = $booking_data['email'];
		$subject    = sprintf(
			/* translators: 1: site name, 2: tour title */
			__( '[%1$s] Booking confirmation: %2$s', 'ytrip' ),
			$site_name,
			$tour_title
		);
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$message = $this->get_booking_email_html( 'customer', $booking_data, $customer_name, $tour_title, $tour_link, $date_fmt, $site_name );
		wp_mail( $to, $subject, $message, $headers );

		$admin_email = get_option( 'admin_email' );
		if ( $admin_email && $admin_email !== $to ) {
			$admin_subject = sprintf(
				/* translators: 1: site name, 2: tour title */
				__( '[%1$s] New booking request: %2$s', 'ytrip' ),
				$site_name,
				$tour_title
			);
			$admin_message = $this->get_booking_email_html( 'admin', $booking_data, $customer_name, $tour_title, $tour_link, $date_fmt, $site_name );
			wp_mail( $admin_email, $admin_subject, $admin_message, $headers );
		}
	}

	/**
	 * Build ultra-modern HTML email body for booking confirmation.
	 *
	 * @param string $type           'customer' or 'admin'.
	 * @param array  $booking_data   Sanitized booking data.
	 * @param string $customer_name  Customer name.
	 * @param string $tour_title     Tour post title.
	 * @param string $tour_link      Tour permalink.
	 * @param string $date_fmt       Formatted booking date.
	 * @param string $site_name      Blog name.
	 * @return string HTML email body.
	 */
	private function get_booking_email_html( string $type, array $booking_data, string $customer_name, string $tour_title, string $tour_link, string $date_fmt, string $site_name ): string {
		$primary = ( isset( $this->settings['brand_colors']['primary'] ) && $this->settings['brand_colors']['primary'] !== '' )
			? $this->settings['brand_colors']['primary']
			: '#0f4c81';
		$tour_link_esc = esc_url( $tour_link );
		$site_name_esc = esc_html( $site_name );
		$tour_title_esc = esc_html( $tour_title );
		$customer_name_esc = esc_html( $customer_name );
		$date_fmt_esc = esc_html( $date_fmt );
		$adults = (int) ( $booking_data['adults'] ?? 0 );
		$children = (int) ( $booking_data['children'] ?? 0 );
		$infants = (int) ( $booking_data['infants'] ?? 0 );
		$notes_safe = ! empty( $booking_data['notes'] ) ? wp_kses_post( nl2br( $booking_data['notes'], false ) ) : '';

		if ( $type === 'customer' ) {
			$view_tour = esc_html__( 'View tour', 'ytrip' );
			$hello = sprintf( /* translators: %s: customer name */ esc_html__( 'Hello %s,', 'ytrip' ), $customer_name_esc );
			$received = esc_html__( 'Your booking request has been received.', 'ytrip' );
			$tour_label = esc_html__( 'Tour', 'ytrip' );
			$date_label = esc_html__( 'Date', 'ytrip' );
			$guests_label = esc_html__( 'Guests', 'ytrip' );
			$adults_label = esc_html__( 'Adults', 'ytrip' );
			$children_label = esc_html__( 'Children', 'ytrip' );
			$infants_label = esc_html__( 'Infants', 'ytrip' );
			$footer_text = esc_html__( 'We will confirm availability and send payment details shortly.', 'ytrip' );
			$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,sans-serif;background-color:#f1f5f9;font-size:16px;line-height:1.6;">';
			$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;"><tr><td align="center" style="padding:32px 16px;">';
			$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.07);overflow:hidden;">';
			$html .= '<tr><td style="padding:32px 32px 24px;background:linear-gradient(135deg,' . esc_attr( $primary ) . ' 0%,' . esc_attr( $primary ) . 'dd 100%);color:#fff;"><h1 style="margin:0;font-size:22px;font-weight:600;">' . $site_name_esc . '</h1><p style="margin:8px 0 0;opacity:0.95;font-size:14px;">' . esc_html__( 'Booking confirmation', 'ytrip' ) . '</p></td></tr>';
			$html .= '<tr><td style="padding:32px;">';
			$html .= '<p style="margin:0 0 20px;color:#1e293b;">' . $hello . '</p>';
			$html .= '<p style="margin:0 0 24px;color:#475569;">' . $received . '</p>';
			$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:8px;margin-bottom:24px;"><tr><td style="padding:20px;">';
			$html .= '<p style="margin:0 0 8px;color:#64748b;font-size:13px;">' . $tour_label . '</p><p style="margin:0 0 16px;font-weight:600;color:#1e293b;">' . $tour_title_esc . '</p>';
			$html .= '<p style="margin:0 0 8px;color:#64748b;font-size:13px;">' . $date_label . '</p><p style="margin:0 0 16px;font-weight:600;color:#1e293b;">' . $date_fmt_esc . '</p>';
			$html .= '<p style="margin:0 0 8px;color:#64748b;font-size:13px;">' . $guests_label . '</p><p style="margin:0;font-weight:600;color:#1e293b;">' . $adults_label . ': ' . $adults . ' | ' . $children_label . ': ' . $children . ' | ' . $infants_label . ': ' . $infants . '</p>';
			$html .= '</td></tr></table>';
			$html .= '<p style="margin:0 0 24px;color:#475569;">' . $footer_text . '</p>';
			$html .= '<p style="margin:0;"><a href="' . $tour_link_esc . '" style="display:inline-block;padding:14px 28px;background:' . esc_attr( $primary ) . ';color:#fff !important;text-decoration:none;border-radius:8px;font-weight:600;">' . $view_tour . '</a></p>';
			$html .= '</td></tr>';
			$html .= '<tr><td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;"><p style="margin:0;font-size:13px;color:#64748b;">— ' . $site_name_esc . '</p></td></tr>';
			$html .= '</table></td></tr></table></body></html>';
			return $html;
		}

		// Admin email.
		$bookings_url = admin_url( 'admin.php?page=ytrip-bookings' );
		$new_booking = esc_html__( 'New booking request', 'ytrip' );
		$name_label = esc_html__( 'Name', 'ytrip' );
		$email_label = esc_html__( 'Email', 'ytrip' );
		$phone_label = esc_html__( 'Phone', 'ytrip' );
		$tour_label = esc_html__( 'Tour', 'ytrip' );
		$date_label = esc_html__( 'Date', 'ytrip' );
		$guests_label = esc_html__( 'Guests', 'ytrip' );
		$adults_label = esc_html__( 'Adults', 'ytrip' );
		$children_label = esc_html__( 'Children', 'ytrip' );
		$infants_label = esc_html__( 'Infants', 'ytrip' );
		$notes_label = esc_html__( 'Notes', 'ytrip' );
		$view_bookings = esc_html__( 'View all bookings', 'ytrip' );
		$email_esc = esc_html( $booking_data['email'] );
		$phone_esc = esc_html( $booking_data['phone'] ?: '—' );
		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;background-color:#f1f5f9;font-size:16px;line-height:1.6;">';
		$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;"><tr><td align="center" style="padding:32px 16px;">';
		$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.07);overflow:hidden;">';
		$html .= '<tr><td style="padding:24px 32px;background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#fff;"><h1 style="margin:0;font-size:20px;font-weight:600;">' . $new_booking . '</h1><p style="margin:6px 0 0;opacity:0.9;font-size:14px;">' . $site_name_esc . '</p></td></tr>';
		$html .= '<tr><td style="padding:32px;">';
		$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;width:140px;">' . $name_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-weight:500;color:#1e293b;">' . $customer_name_esc . '</td></tr>';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">' . $email_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;"><a href="mailto:' . esc_attr( $booking_data['email'] ) . '" style="color:' . esc_attr( $primary ) . ';">' . $email_esc . '</a></td></tr>';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">' . $phone_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#1e293b;">' . $phone_esc . '</td></tr>';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">' . $tour_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;"><a href="' . $tour_link_esc . '" style="color:' . esc_attr( $primary ) . ';">' . $tour_title_esc . '</a></td></tr>';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">' . $date_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#1e293b;">' . $date_fmt_esc . '</td></tr>';
		$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">' . $guests_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#1e293b;">' . $adults_label . ': ' . $adults . ' | ' . $children_label . ': ' . $children . ' | ' . $infants_label . ': ' . $infants . '</td></tr>';
		if ( $notes_safe !== '' ) {
			$html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;vertical-align:top;">' . $notes_label . '</td><td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#1e293b;">' . $notes_safe . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<p style="margin:24px 0 0;"><a href="' . esc_url( $bookings_url ) . '" style="display:inline-block;padding:12px 24px;background:' . esc_attr( $primary ) . ';color:#fff !important;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">' . $view_bookings . '</a></p>';
		$html .= '</td></tr><tr><td style="padding:16px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;"><p style="margin:0;font-size:12px;color:#64748b;">' . $site_name_esc . ' — ' . esc_html__( 'Bookings', 'ytrip' ) . '</p></td></tr>';
		$html .= '</table></td></tr></table></body></html>';
		return $html;
	}

	/**
	 * Stored feedback for current request (set by maybe_show_booking_feedback).
	 *
	 * @var array|null
	 */
	private $booking_feedback_errors = null;

	/**
	 * @var bool|null
	 */
	private $booking_feedback_success = null;

	/**
	 * If redirect had booking errors or success, store and enqueue rendering.
	 * Notice is output in wp_footer (priority 1) and optionally prepended to the_content.
	 */
	public function maybe_show_booking_feedback() {
		$err_token = isset( $_GET['ytrip_booking_errors'] ) ? sanitize_text_field( wp_unslash( $_GET['ytrip_booking_errors'] ) ) : '';
		$ok_token  = isset( $_GET['ytrip_booking_success'] ) ? sanitize_text_field( wp_unslash( $_GET['ytrip_booking_success'] ) ) : '';
		if ( $err_token !== '' ) {
			$errors = get_transient( 'ytrip_booking_errors_' . $err_token );
			if ( is_array( $errors ) ) {
				delete_transient( 'ytrip_booking_errors_' . $err_token );
				$this->booking_feedback_errors = $errors;
				add_action( 'wp_footer', array( $this, 'render_stored_errors' ), 1 );
				add_action( 'wp_body_open', array( $this, 'render_stored_errors' ), 5 );
				add_action( 'ytrip_before_booking_form', array( $this, 'render_stored_errors' ), 5 );
			}
		}
		if ( $ok_token !== '' ) {
			$ok = get_transient( 'ytrip_booking_success_' . $ok_token );
			if ( $ok ) {
				delete_transient( 'ytrip_booking_success_' . $ok_token );
				$this->booking_feedback_success = true;
				add_action( 'wp_footer', array( $this, 'render_stored_success' ), 1 );
				add_action( 'wp_body_open', array( $this, 'render_stored_success' ), 5 );
				add_action( 'ytrip_before_booking_form', array( $this, 'render_stored_success' ), 5 );
			}
		}
	}

	/**
	 * Render stored errors (used by footer/body_open hooks).
	 */
	public function render_stored_errors() {
		if ( is_array( $this->booking_feedback_errors ) ) {
			$this->render_booking_errors( $this->booking_feedback_errors );
		}
	}

	/**
	 * Render stored success (used by footer/body_open hooks).
	 */
	public function render_stored_success() {
		if ( $this->booking_feedback_success ) {
			$this->render_booking_success();
		}
	}

	/**
	 * Prepend booking notice to main content so it shows even if theme skips wp_footer.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_prepend_booking_feedback_to_content( $content ) {
		if ( ! is_singular( 'ytrip_tour' ) ) {
			return $content;
		}
		$prepend = '';
		if ( is_array( $this->booking_feedback_errors ) && ! empty( $this->booking_feedback_errors ) ) {
			ob_start();
			$this->render_booking_errors( $this->booking_feedback_errors );
			$prepend = ob_get_clean();
		}
		if ( $this->booking_feedback_success ) {
			ob_start();
			$this->render_booking_success();
			$prepend .= ob_get_clean();
		}
		return $prepend ? $prepend . $content : $content;
	}

	/**
	 * Output booking validation errors notice (dismissible). Renders only once per request.
	 *
	 * @param array $errors Key => message.
	 */
	public function render_booking_errors( array $errors ) {
		if ( empty( $errors ) ) {
			return;
		}
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;
		$messages = array_map( 'esc_html', array_values( $errors ) );
		$list     = '<ul><li>' . implode( '</li><li>', $messages ) . '</li></ul>';
		echo '<div class="ytrip-booking-notice ytrip-booking-notice--error ytrip-booking-notice--sticky" role="alert" id="ytrip-booking-errors">';
		echo '<div class="ytrip-booking-notice__inner">';
		echo '<p class="ytrip-booking-notice__title">' . esc_html__( 'Please fix the following:', 'ytrip' ) . '</p>';
		echo wp_kses_post( $list );
		echo '<button type="button" class="ytrip-booking-notice__dismiss" aria-label="' . esc_attr__( 'Dismiss', 'ytrip' ) . '">&times;</button>';
		echo '</div></div>';
	}

	/**
	 * Output booking success notice (dismissible). Renders only once per request.
	 */
	public function render_booking_success() {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;
		$with_wc = function_exists( 'WC' );
		$msg     = $with_wc
			? __( 'Booking added to cart. You will receive a confirmation email after checkout.', 'ytrip' )
			: __( 'Your booking request has been received. We sent a confirmation email and will contact you shortly.', 'ytrip' );
		echo '<div class="ytrip-booking-notice ytrip-booking-notice--success ytrip-booking-notice--sticky" role="status" id="ytrip-booking-success">';
		echo '<div class="ytrip-booking-notice__inner"><p>' . esc_html( $msg ) . '</p>';
		echo '<button type="button" class="ytrip-booking-notice__dismiss" aria-label="' . esc_attr__( 'Dismiss', 'ytrip' ) . '">&times;</button>';
		echo '</div></div>';
	}

	/**
	 * Validate booking data.
	 *
	 * @param array $data Form data.
	 * @return array Errors.
	 */
	private function validate_booking_data( array $data ) {
		$errors = array();
		$date_value = isset( $data['tour_date'] ) && $data['tour_date'] !== '' ? $data['tour_date'] : ( isset( $data['booking_date'] ) ? $data['booking_date'] : '' );

		// Tour ID.
		if ( empty( $data['tour_id'] ) || ! get_post( absint( $data['tour_id'] ) ) ) {
			$errors['tour_id'] = __( 'Invalid tour selected.', 'ytrip' );
		}

		// Date (accept tour_date or booking_date from different form variants).
		if ( $date_value === '' ) {
			$errors['tour_date'] = __( 'Please select a date.', 'ytrip' );
		} else {
			$date = \DateTime::createFromFormat( 'Y-m-d', $date_value );
			if ( ! $date || $date < new \DateTime( 'today' ) ) {
				$errors['tour_date'] = __( 'Please select a valid future date.', 'ytrip' );
			}
		}

		// Adults.
		if ( empty( $data['adults'] ) || absint( $data['adults'] ) < 1 ) {
			$errors['adults'] = __( 'At least 1 adult is required.', 'ytrip' );
		}

		// Email: required for guests; for logged-in allow empty (filled from user in process_booking).
		$email = isset( $data['booking_email'] ) ? trim( (string) $data['booking_email'] ) : '';
		if ( is_user_logged_in() ) {
			if ( $email !== '' && ! is_email( $email ) ) {
				$errors['email'] = __( 'Please enter a valid email address.', 'ytrip' );
			}
		} else {
			if ( $email === '' || ! is_email( $email ) ) {
				$errors['email'] = __( 'Please enter a valid email address.', 'ytrip' );
			}
		}

		// Optional name: max length when provided.
		$name = isset( $data['booking_name'] ) ? trim( (string) $data['booking_name'] ) : '';
		if ( $name !== '' && mb_strlen( $name ) > 100 ) {
			$errors['booking_name'] = __( 'Name must be 100 characters or less.', 'ytrip' );
		}

		// Optional phone: basic length check when provided (e.g. at least 5 digits).
		$phone = isset( $data['booking_phone'] ) ? preg_replace( '/[^\d]/', '', $data['booking_phone'] ) : '';
		if ( $phone !== '' && strlen( $phone ) < 5 ) {
			$errors['phone'] = __( 'Please enter a valid phone number.', 'ytrip' );
		}

		return $errors;
	}

	/**
	 * Verify reCAPTCHA token.
	 *
	 * @param string $token reCAPTCHA token.
	 * @return bool
	 */
	private function verify_recaptcha_token( string $token ) {
		if ( empty( $token ) ) {
			return false;
		}

		$secret = $this->settings['recaptcha_secret_key'] ?? '';
		if ( empty( $secret ) ) {
			return true; // Not configured, skip verification.
		}

		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'body' => array(
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check success and score (v3).
		if ( ! empty( $body['success'] ) && ( $body['score'] ?? 1 ) >= 0.5 ) {
			return true;
		}

		return false;
	}

	/**
	 * AJAX verify reCAPTCHA.
	 *
	 * @return void
	 */
	public function verify_recaptcha() {
		// Handled globally by Security Engine as 'general' (mapped to public nonce if not specific)
		// But verify manually here if needed. Since we don't have a specific 'recaptcha' nonce scope in Security Engine,
		// we rely on global check or add one. For now, assume global check passes as 'public'.
		// check_ajax_referer( 'ytrip_ajax_nonce', 'security' );

		$token = sanitize_text_field( $_POST['token'] ?? '' );
		$valid = $this->verify_recaptcha_token( $token );

		if ( $valid ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed.', 'ytrip' ) ) );
		}
	}

	/**
	 * Render booking form.
	 *
	 * @param int $tour_id Tour ID.
	 * @return void
	 */
	public static function render_form( int $tour_id ) {
		$instance = self::instance();
		$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
		$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		$user = wp_get_current_user();
		?>
		<div class="ytrip-booking-widget">
			<?php if ( $product ) : ?>
			<div class="ytrip-booking-widget__price">
				<span class="ytrip-booking-widget__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
				<span class="ytrip-booking-widget__amount"><?php echo $product->get_price_html(); // phpcs:ignore ?></span>
				<span class="ytrip-booking-widget__per"><?php esc_html_e( 'per person', 'ytrip' ); ?></span>
			</div>
			<?php endif; ?>

			<form class="ytrip-booking-widget__form" method="post" action="">
				<!-- Date Selection (shared calendar component) -->
				<div class="ytrip-form-group ytrip-date-group">
					<label for="ytrip-date-display"><?php esc_html_e( 'Select Date', 'ytrip' ); ?> <span class="required">*</span></label>
					<?php
					$args = array(
						'display_id'   => 'ytrip-date-display',
						'hidden_name'  => 'tour_date',
						'hidden_id'    => 'ytrip-tour-date',
						'container_id' => 'ytrip-calendar-container',
						'placeholder'  => __( 'Select date', 'ytrip' ),
						'required'     => true,
					);
					include YTRIP_PATH . 'templates/parts/calendar-single.php';
					?>
				</div>

				<!-- Guests Selection -->
				<div class="ytrip-form-group ytrip-guest-group">
					<label for="ytrip-guests-display"><?php esc_html_e( 'Guests', 'ytrip' ); ?> <span class="required">*</span></label>
					<div class="ytrip-guest-input-wrapper">
						<input type="text" id="ytrip-guests-display" class="ytrip-guests-display" value="1 <?php esc_attr_e( 'Adult', 'ytrip' ); ?>" readonly aria-label="<?php esc_attr_e( 'Number of guests', 'ytrip' ); ?>">
						<div class="ytrip-guest-dropdown" id="ytrip-guest-container">
							<!-- Adults -->
							<div class="ytrip-guest-row">
								<div class="ytrip-guest-label">
									<span class="ytrip-guest-type"><?php esc_html_e( 'Adults', 'ytrip' ); ?></span>
									<span class="ytrip-guest-age"><?php esc_html_e( 'Age 12+', 'ytrip' ); ?></span>
								</div>
								<div class="ytrip-guest-stepper">
									<button type="button" class="ytrip-qty-btn" data-action="minus" data-target="adults" disabled>−</button>
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
									<button type="button" class="ytrip-qty-btn" data-action="minus" data-target="children" disabled>−</button>
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
									<button type="button" class="ytrip-qty-btn" data-action="minus" data-target="infants" disabled>−</button>
									<span class="ytrip-qty-val" id="val-infants">0</span>
									<button type="button" class="ytrip-qty-btn" data-action="plus" data-target="infants">+</button>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="adults" id="ytrip-field-adults" value="1">
					<input type="hidden" name="children" id="ytrip-field-children" value="0">
					<input type="hidden" name="infants" id="ytrip-field-infants" value="0">
				</div>

				<!-- Contact Info (for guests) -->
				<?php if ( ! is_user_logged_in() || ! $instance->requires_login() ) : ?>
				<div class="ytrip-form-group">
					<label for="ytrip-booking-email"><?php esc_html_e( 'Email', 'ytrip' ); ?> <span class="required">*</span></label>
					<input type="email" 
					       id="ytrip-booking-email" 
					       name="booking_email" 
					       value="<?php echo esc_attr( $user->user_email ?? '' ); ?>"
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
				<?php endif; ?>

				<!-- Honeypot anti-spam (hidden) -->
				<div class="ytrip-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;">
					<label for="ytrip-website"><?php esc_html_e( 'Website', 'ytrip' ); ?></label>
					<input type="text" name="ytrip_website" id="ytrip-website" tabindex="-1" autocomplete="off">
				</div>

				<!-- Hidden Fields -->
				<?php if ( $product ) : ?>
				<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>">
				<?php endif; ?>
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
				<input type="hidden" name="recaptcha_token" value="">
				<?php YTrip_Security_Engine::nonce_field( 'booking', 'ytrip_booking_nonce' ); ?>

				<!-- Submit Button -->
				<button type="submit" class="ytrip-btn ytrip-btn--primary ytrip-btn--block">
					<?php 
					if ( $instance->requires_login() && ! is_user_logged_in() ) {
						esc_html_e( 'Login to Book', 'ytrip' );
					} else {
						esc_html_e( 'Book Now', 'ytrip' );
					}
					?>
				</button>
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

			<?php if ( $instance->is_recaptcha_enabled() ) : ?>
			<p class="ytrip-recaptcha-notice">
				<?php
				printf(
					/* translators: %1$s and %2$s are links to Google policies */
					esc_html__( 'Protected by reCAPTCHA. %1$sPrivacy%2$s & %3$sTerms%4$s', 'ytrip' ),
					'<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">',
					'</a>',
					'<a href="https://policies.google.com/terms" target="_blank" rel="noopener">',
					'</a>'
				);
				?>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}
}

// Initialize.
YTrip_Booking_Form::instance();
