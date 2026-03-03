/**
 * YTrip booking form validation and confirmation modal.
 * Blocks submit without date; shows confirmation before submit; works with/without WooCommerce.
 */
(function ($) {
	'use strict';

	var config = window.ytripBookingConfig || {};
	var s = window.ytripBookingStrings || (window.ytripAjax && window.ytripAjax.strings) || {};
	function str(key, fallback) {
		return (s[key] !== undefined && s[key]) ? s[key] : (fallback || '');
	}

	var ytripBooking = {
		requiresLogin: config.requiresLogin === true,
		isLoggedIn: config.isLoggedIn === true,
		loginUrl: config.loginUrl || '',
		recaptchaKey: config.recaptchaKey || '',
		pendingForm: null,

		init: function () {
			// Delegated submit so we catch forms regardless of when they appear in DOM
			$(document).on('submit', '.ytrip-booking-widget__form', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var form = $(this);
				if (ytripBooking.requiresLogin && !ytripBooking.isLoggedIn) {
					window.location.href = ytripBooking.loginUrl;
					return false;
				}
				if (!ytripBooking.validateWidgetForm(form)) {
					ytripBooking.showValidationSummary(form);
					return false;
				}
				if (ytripBooking.recaptchaKey) {
					ytripBooking.pendingForm = form.get(0);
					ytripBooking.executeRecaptcha(form);
					return false;
				}
				ytripBooking.pendingForm = form.get(0);
				ytripBooking.showConfirmModalOrSubmit(form);
				return false;
			});

			$(document).on('submit', '.ytrip-booking-form', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var form = $(this);
				if (ytripBooking.requiresLogin && !ytripBooking.isLoggedIn) {
					window.location.href = ytripBooking.loginUrl;
					return false;
				}
				if (!ytripBooking.validateClassicForm(form)) {
					ytripBooking.showValidationSummary(form);
					return false;
				}
				if (ytripBooking.recaptchaKey) {
					ytripBooking.pendingForm = form.get(0);
					ytripBooking.executeRecaptcha(form);
					return false;
				}
				ytripBooking.pendingForm = form.get(0);
				ytripBooking.showConfirmModalOrSubmit(form);
				return false;
			});

			ytripBooking.bindConfirmModal();
			ytripBooking.bindNoticeDismiss();

			if (ytripBooking.requiresLogin && !ytripBooking.isLoggedIn) {
				$(document).on('click', '.ytrip-booking-widget__form button[type="submit"]', function (e) {
					e.preventDefault();
					window.location.href = ytripBooking.loginUrl;
					return false;
				});
			}
		},

		validateWidgetForm: function (form) {
			ytripBooking.clearFormErrors(form);
			var isValid = true;
			form.find('input[required], select[required]').each(function () {
				if (!ytripBooking.validateField($(this))) isValid = false;
			});
			var emailField = form.find('input[type="email"]');
			if (emailField.length && !ytripBooking.validateEmail(emailField)) isValid = false;
			var honeypot = form.find('.ytrip-hp-field input');
			if (honeypot.length && honeypot.val()) return false;
			var dateField = form.find('#ytrip-tour-date');
			if (!dateField.length || !dateField.val() || String(dateField.val()).trim() === '') {
				ytripBooking.showError(form.find('#ytrip-date-display'), str('selectDate', 'Please select a date.'));
				isValid = false;
			}
			return isValid;
		},

		validateClassicForm: function (form) {
			ytripBooking.clearFormErrors(form);
			var isValid = true;
			form.find('input[required], select[required]').each(function () {
				if (!ytripBooking.validateField($(this))) isValid = false;
			});
			var dateField = form.find('input[name="booking_date"]').first();
			var dateVal = dateField.length ? dateField.val() : '';
			if (!dateVal || String(dateVal).trim() === '') {
				ytripBooking.showError(dateField, str('selectDate', 'Please select a date.'));
				if (dateField.length) dateField[0].focus && dateField[0].focus();
				isValid = false;
			}
			var emailField = form.find('input[name="booking_email"], input[type="email"]').first();
			if (emailField.length && !ytripBooking.validateEmail(emailField)) isValid = false;
			var adultsField = form.find('input[name="adults"]').first();
			if (adultsField.length) {
				var adults = parseInt(adultsField.val(), 10);
				if (isNaN(adults) || adults < 1) {
					ytripBooking.showError(adultsField, str('required', 'At least 1 adult is required.'));
					isValid = false;
				}
			}
			var honeypot = form.find('.ytrip-hp-field input');
			if (honeypot.length && honeypot.val()) return false;
			return isValid;
		},

		showConfirmModalOrSubmit: function (form) {
			var modal = $('#ytrip-booking-confirm-modal');
			if (modal.length) {
				modal.attr('aria-hidden', 'false').css('display', 'flex');
			} else {
				ytripBooking.submitFormWithFeedback(form);
			}
		},

		submitFormWithFeedback: function (form) {
			var formEl = form && form.get ? form.get(0) : form;
			if (!formEl || typeof formEl.submit !== 'function') return;
			var btn = form.find('button[type="submit"]');
			if (btn.length) {
				btn.prop('disabled', true).data('ytrip-original-text', btn.text()).text(str('processing', 'Processing...'));
			}
			form.off('submit');
			formEl.submit();
		},

		showValidationSummary: function (form) {
			form.find('.ytrip-booking-validation-summary').remove();
			var firstError = form.find('.ytrip-error-message').first();
			var msg = str('pleaseFix', 'Please fix the following:');
			var summary = $('<div class="ytrip-booking-validation-summary" role="alert">' + msg + '</div>');
			form.prepend(summary);
			if (firstError.length) {
				firstError[0].scrollIntoView && firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		},

		bindConfirmModal: function () {
			var modal = $('#ytrip-booking-confirm-modal');
			if (!modal.length) return;
			modal.find('.ytrip-booking-confirm-cancel, .ytrip-modal__backdrop').on('click', function () {
				modal.attr('aria-hidden', 'true').css('display', 'none');
				ytripBooking.pendingForm = null;
			});
			modal.find('.ytrip-booking-confirm-ok').on('click', function () {
				if (ytripBooking.pendingForm) {
					var f = ytripBooking.pendingForm;
					ytripBooking.pendingForm = null;
					modal.attr('aria-hidden', 'true').css('display', 'none');
					var $form = $(f).closest('form');
					if ($form.length) ytripBooking.submitFormWithFeedback($form); else if (f && typeof f.submit === 'function') f.submit();
				} else {
					modal.attr('aria-hidden', 'true').css('display', 'none');
				}
			});
		},

		bindNoticeDismiss: function () {
			$(document).on('click', '.ytrip-booking-notice__dismiss', function () {
				$(this).closest('.ytrip-booking-notice').remove();
			});
			if ($('#ytrip-booking-errors, #ytrip-booking-success').length && window.history && window.history.replaceState) {
				var url = window.location.href.replace(/([?&])ytrip_booking_(errors|success)=[^&]+(&|$)/g, '$1').replace(/[?&]$/, '');
				if (url !== window.location.href) window.history.replaceState({}, '', url);
			}
		},

		clearFormErrors: function (form) {
			form.find('.ytrip-field-error').removeClass('ytrip-field-error');
			form.find('.ytrip-error-message').remove();
		},

		clearError: function (field) {
			if (!field || !field.length) return;
			field.removeClass('ytrip-field-error');
			field.siblings('.ytrip-error-message').remove();
		},

		validateField: function (field) {
			var value = field.val().trim();
			if (field.prop('required') && !value) {
				this.showError(field, str('required', 'This field is required.'));
				return false;
			}
			this.clearError(field);
			return true;
		},

		validateEmail: function (field) {
			var email = field.val().trim();
			if (!email && !field.prop('required')) return true;
			var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(email)) {
				this.showError(field, str('invalidEmail', 'Please enter a valid email.'));
				return false;
			}
			this.clearError(field);
			return true;
		},

		showError: function (field, message) {
			if (!field || !field.length) return;
			field.addClass('ytrip-field-error');
			var errorEl = field.siblings('.ytrip-error-message');
			if (!errorEl.length) {
				errorEl = $('<span class="ytrip-error-message"></span>');
				field.after(errorEl);
			}
			errorEl.text(message);
		},

		executeRecaptcha: function (form) {
			var btn = form.find('button[type="submit"]');
			btn.prop('disabled', true).text(str('processing', 'Processing...'));
			if (typeof grecaptcha === 'undefined' || !grecaptcha.ready) {
				btn.prop('disabled', false);
				return;
			}
			grecaptcha.ready(function () {
				grecaptcha.execute(ytripBooking.recaptchaKey, { action: 'booking' }).then(function (token) {
					form.find('input[name="recaptcha_token"]').val(token);
					ytripBooking.pendingForm = form.get(0);
					var m = $('#ytrip-booking-confirm-modal');
					if (m.length) m.attr('aria-hidden', 'false').css('display', 'flex');
				});
			});
		}
	};

	$(function () {
		ytripBooking.init();
	});
})(jQuery);
