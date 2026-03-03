/**
 * YTrip User Dashboard JavaScript
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    /**
     * Initialize wishlist functionality.
     */
    function initWishlist() {
        // Remove from wishlist
        $(document).on('click', '.ytrip-wishlist-remove', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $card = $btn.closest('.ytrip-wishlist-card');
            var tourId = $btn.data('tour-id');

            if (!confirm(ytripDashboard.strings.removeWish)) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: ytripDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ytrip_remove_from_wishlist',
                    security: ytripDashboard.nonce,
                    tour_id: tourId
                },
                success: function (response) {
                    if (response.success) {
                        $card.fadeOut(300, function () {
                            $(this).remove();

                            // Show empty state if no more items
                            if ($('.ytrip-wishlist-card').length === 0) {
                                $('.ytrip-wishlist-grid').html(
                                    '<div class="ytrip-empty-state">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>' +
                                    '<p>Your wishlist is empty.</p>' +
                                    '</div>'
                                );
                            }
                        });
                    } else {
                        alert(response.data.message || ytripDashboard.strings.error);
                    }
                },
                error: function () {
                    alert(ytripDashboard.strings.error);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize profile form.
     */
    function initProfileForm() {
        var $form = $('#ytrip-profile-form');
        var $messages = $('#ytrip-profile-messages');

        $form.on('submit', function (e) {
            e.preventDefault();

            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();

            // Validate passwords match
            var newPass = $form.find('#ytrip-new-password').val();
            var confirmPass = $form.find('#ytrip-confirm-password').val();

            if (newPass && newPass !== confirmPass) {
                showMessage($messages, 'error', 'Passwords do not match.');
                return;
            }

            $submitBtn.prop('disabled', true).text(ytripDashboard.strings.loading);

            $.ajax({
                url: ytripDashboard.ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=ytrip_update_profile',
                success: function (response) {
                    if (response.success) {
                        showMessage($messages, 'success', response.data.message);

                        // Clear password fields
                        $form.find('input[type="password"]').val('');
                    } else {
                        showMessage($messages, 'error', response.data.message);
                    }
                },
                error: function () {
                    showMessage($messages, 'error', ytripDashboard.strings.error);
                },
                complete: function () {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Initialize booking actions.
     */
    function initBookingActions() {
        // Cancel booking
        $(document).on('click', '.ytrip-cancel-booking', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var orderId = $btn.data('order-id');

            if (!confirm(ytripDashboard.strings.confirm)) {
                return;
            }

            $btn.prop('disabled', true).text(ytripDashboard.strings.loading);

            $.ajax({
                url: ytripDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ytrip_cancel_booking',
                    security: ytripDashboard.nonce,
                    order_id: orderId
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || ytripDashboard.strings.error);
                        $btn.prop('disabled', false).text('Cancel');
                    }
                },
                error: function () {
                    alert(ytripDashboard.strings.error);
                    $btn.prop('disabled', false).text('Cancel');
                }
            });
        });
    }

    /**
     * Show message.
     */
    function showMessage($container, type, message) {
        var className = type === 'success' ? 'ytrip-message-success' : 'ytrip-message-error';
        $container.html('<div class="ytrip-message ' + className + '">' + message + '</div>');

        // Scroll to message
        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 300);

        // Auto-hide after 5 seconds
        setTimeout(function () {
            $container.find('.ytrip-message').fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        if ($('.ytrip-dashboard').length) {
            initWishlist();
            initProfileForm();
            initBookingActions();
        }
    });

})(jQuery);
