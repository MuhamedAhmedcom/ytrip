/**
 * YTrip - Main JavaScript
 * Premium travel booking interactions
 */
(function ($) {
    'use strict';

    const YTrip = {
        init: function () {
            this.bindEvents();
            this.initTabs();
            this.initFAQ();
            this.initSearch();
            this.initAnimations();
        },

        bindEvents: function () {
            $(document).on('click', '.ytrip-tour-card__wishlist', this.toggleWishlist);
            $(document).on('submit', '.ytrip-search__form', this.handleSearch);
        },

        initTabs: function () {
            $('.ytrip-tour-tabs__btn').on('click', function () {
                const target = $(this).data('tab');
                const $container = $(this).closest('.ytrip-tour-tabs');

                $container.find('.ytrip-tour-tabs__btn').removeClass('active');
                $(this).addClass('active');

                $container.find('.ytrip-tour-tabs__content').removeClass('active');
                $container.find('[data-content="' + target + '"]').addClass('active');
            });
        },

        initFAQ: function () {
            $('.ytrip-faq__question').on('click', function () {
                const $item = $(this).closest('.ytrip-faq__item');
                $item.toggleClass('active');
            });
        },

        initSearch: function () {
            if ($.fn.select2) {
                $('.ytrip-search__select').select2({
                    minimumResultsForSearch: Infinity,
                    dropdownCssClass: 'ytrip-select2-dropdown'
                });
            }

            if ($.fn.datepicker) {
                $('.ytrip-search__date').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });
            }
        },

        initAnimations: function () {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('ytrip-animated');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });

                document.querySelectorAll('.ytrip-animate').forEach(el => {
                    observer.observe(el);
                });
            }
        },

        toggleWishlist: function (e) {
            e.preventDefault();
            const $btn = $(this);
            const tourId = $btn.data('tour-id');

            $btn.toggleClass('active');

            $.ajax({
                url: ytrip_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ytrip_toggle_wishlist',
                    tour_id: tourId,
                    nonce: ytrip_params.nonce
                },
                success: function (res) {
                    if (res.success) {
                        $btn.find('.ytrip-icon').toggleClass('filled');
                    }
                }
            });
        },

        handleSearch: function (e) {
            // Allow form to submit normally or handle via AJAX
        },

        initInquiryForm: function () {
            $('#ytrip-inquiry-form').on('submit', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const $msg = $form.find('.ytrip-form-message');

                $btn.prop('disabled', true).addClass('ytrip-loading');
                $msg.hide().removeClass('success error');

                $.ajax({
                    url: ytrip_params.ajax_url,
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (res) {
                        $btn.prop('disabled', false).removeClass('ytrip-loading');
                        if (res.success) {
                            $form[0].reset();
                            $msg.addClass('success').html(res.data.message).fadeIn();
                        } else {
                            $msg.addClass('error').html(res.data.message).fadeIn();
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false).removeClass('ytrip-loading');
                        $msg.addClass('error').text('Server error. Please try again.').fadeIn();
                    }
                });
            });
        }
    };

    $(document).ready(function () {
        YTrip.init();
        YTrip.initInquiryForm();
    });

})(jQuery);
