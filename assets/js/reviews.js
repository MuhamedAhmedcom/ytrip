/**
 * YTrip Reviews JavaScript
 *
 * Handles:
 * - Star rating interaction
 * - Review form submission
 * - Helpful voting
 * - Load more reviews
 * - Photo upload
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    // Rating labels
    const ratingLabels = {
        1: ytripReviews.strings.terrible || 'Terrible',
        2: ytripReviews.strings.poor || 'Poor',
        3: ytripReviews.strings.average || 'Average',
        4: ytripReviews.strings.good || 'Good',
        5: ytripReviews.strings.excellent || 'Excellent'
    };

    /**
     * Initialize star rating inputs.
     */
    function initStarRatings() {
        $('.ytrip-star-rating-input').each(function () {
            const $container = $(this);
            const $stars = $container.find('.ytrip-star-input');
            const $input = $container.find('input[type="hidden"]');
            const $label = $container.find('.ytrip-rating-label');

            // Hover effect
            $stars.on('mouseenter', function () {
                const value = $(this).data('value');
                $stars.each(function () {
                    $(this).toggleClass('hovered', $(this).data('value') <= value);
                });
                if ($label.length && !$container.hasClass('ytrip-star-rating-small')) {
                    $label.text(ratingLabels[value] || '');
                }
            });

            $container.on('mouseleave', function () {
                $stars.removeClass('hovered');
                const currentValue = parseInt($input.val()) || 0;
                if ($label.length) {
                    $label.text(currentValue > 0 ? ratingLabels[currentValue] : '');
                }
            });

            // Click to select
            $stars.on('click', function () {
                const value = $(this).data('value');
                $input.val(value).trigger('change');
                $container.attr('data-rating', value);

                $stars.each(function () {
                    $(this).toggleClass('selected', $(this).data('value') <= value);
                });

                if ($label.length && !$container.hasClass('ytrip-star-rating-small')) {
                    $label.text(ratingLabels[value] || '');
                }
            });
        });
    }

    /**
     * Initialize review form.
     */
    function initReviewForm() {
        const $wrapper = $('#ytrip-review-form-wrapper');
        const $form = $('#ytrip-review-form');
        const $writeBtn = $('#ytrip-write-review-btn');
        const $closeBtn = $('#ytrip-review-form-close');
        const $cancelBtn = $('#ytrip-review-cancel');
        const $submitBtn = $('#ytrip-review-submit');
        const $messages = $('#ytrip-review-messages');
        const $textarea = $('#ytrip-review-text');
        const $charCount = $('#ytrip-review-char-count');

        // Show form
        $writeBtn.on('click', function () {
            $wrapper.slideDown(300);
            $('html, body').animate({
                scrollTop: $wrapper.offset().top - 100
            }, 300);
        });

        // Hide form
        $closeBtn.add($cancelBtn).on('click', function () {
            $wrapper.slideUp(300);
        });

        // Character count
        $textarea.on('input', function () {
            $charCount.text($(this).val().length);
        });

        // Form submission
        $form.on('submit', function (e) {
            e.preventDefault();

            // Validate overall rating
            const overallRating = $form.find('input[name="overall_rating"]').val();
            if (!overallRating || parseFloat(overallRating) < 1) {
                showMessage($messages, 'error', ytripReviews.strings.selectRating || 'Please select an overall rating.');
                return;
            }

            // Disable button
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.ytrip-btn-text').hide();
            $submitBtn.find('.ytrip-btn-loading').show();

            // Prepare data
            const formData = {
                action: 'ytrip_submit_review',
                security: $form.find('#ytrip_review_security').val(),
                tour_id: $form.find('input[name="tour_id"]').val(),
                overall_rating: overallRating,
                service_rating: $form.find('input[name="service_rating"]').val() || '',
                value_rating: $form.find('input[name="value_rating"]').val() || '',
                guide_rating: $form.find('input[name="guide_rating"]').val() || '',
                review_title: $form.find('#ytrip-review-title').val(),
                review_text: $textarea.val(),
                photos: getUploadedPhotos()
            };

            $.ajax({
                url: ytripReviews.ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        showMessage($messages, 'success', response.data.message);
                        $form[0].reset();
                        $('.ytrip-star-input').removeClass('selected');
                        $('.ytrip-star-rating-input').attr('data-rating', '0');
                        $('.ytrip-rating-label').text('');
                        $('#ytrip-photo-preview').empty();

                        // Hide form after delay
                        setTimeout(function () {
                            $wrapper.slideUp(300);
                        }, 2000);
                    } else {
                        showMessage($messages, 'error', response.data.message);
                    }
                },
                error: function () {
                    showMessage($messages, 'error', ytripReviews.strings.error || 'An error occurred. Please try again.');
                },
                complete: function () {
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('.ytrip-btn-text').show();
                    $submitBtn.find('.ytrip-btn-loading').hide();
                }
            });
        });
    }

    /**
     * Initialize photo upload.
     */
    function initPhotoUpload() {
        const $input = $('#ytrip-photo-input');
        const $preview = $('#ytrip-photo-preview');
        const maxPhotos = 5;
        let uploadedPhotos = [];

        $input.on('change', function (e) {
            const files = e.target.files;

            if (uploadedPhotos.length + files.length > maxPhotos) {
                alert(ytripReviews.strings.maxPhotos || 'Maximum 5 photos allowed.');
                return;
            }

            Array.from(files).forEach(function (file) {
                if (!file.type.match('image.*')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    // For demo purposes, we're just showing preview
                    // In production, you'd upload to WordPress media library
                    const $item = $('<div class="ytrip-photo-preview-item">' +
                        '<img src="' + e.target.result + '">' +
                        '<button type="button" class="ytrip-photo-remove">&times;</button>' +
                        '</div>');

                    $preview.append($item);
                    uploadedPhotos.push(file);
                };
                reader.readAsDataURL(file);
            });

            // Clear input for next selection
            $input.val('');
        });

        // Remove photo
        $preview.on('click', '.ytrip-photo-remove', function () {
            const index = $(this).parent().index();
            $(this).parent().remove();
            uploadedPhotos.splice(index, 1);
        });

        // Store reference for form submission
        window.ytripUploadedPhotos = uploadedPhotos;
    }

    /**
     * Get uploaded photo IDs.
     */
    function getUploadedPhotos() {
        // In a full implementation, photos would be uploaded first
        // and we'd return an array of attachment IDs
        return [];
    }

    /**
     * Initialize helpful voting.
     */
    function initHelpfulVoting() {
        $(document).on('click', '.ytrip-helpful-btn', function (e) {
            e.preventDefault();

            const $btn = $(this);
            const reviewId = $btn.data('review-id');
            const vote = $btn.data('vote');

            if ($btn.hasClass('voted')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: ytripReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ytrip_helpful_vote',
                    security: ytripReviews.nonce,
                    review_id: reviewId,
                    vote: vote
                },
                success: function (response) {
                    if (response.success) {
                        // Update count
                        const $count = $btn.find('.ytrip-helpful-count');
                        const currentCount = parseInt($count.text().replace(/[()]/g, '')) || 0;
                        $count.text('(' + (currentCount + 1) + ')');

                        // Mark as voted
                        $btn.addClass('voted');
                        $btn.siblings('.ytrip-helpful-btn').prop('disabled', true);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(ytripReviews.strings.error || 'An error occurred.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize load more reviews.
     */
    function initLoadMore() {
        const $btn = $('#ytrip-load-more-reviews');
        const $list = $('#ytrip-reviews-list');
        const tourId = $('.ytrip-reviews').data('tour-id');

        $btn.on('click', function () {
            const currentPage = parseInt($btn.data('page'));
            const totalPages = parseInt($btn.data('pages'));
            const nextPage = currentPage + 1;

            if (nextPage > totalPages) {
                $btn.hide();
                return;
            }

            $btn.prop('disabled', true).text(ytripReviews.strings.loading || 'Loading...');

            $.ajax({
                url: ytripReviews.resturl + 'ytrip/v1/tours/' + tourId + '/reviews',
                type: 'GET',
                data: {
                    page: nextPage,
                    per_page: 10
                },
                success: function (response) {
                    if (response.reviews && response.reviews.length) {
                        response.reviews.forEach(function (review) {
                            const html = buildReviewHTML(review);
                            $list.append(html);
                        });

                        $btn.data('page', nextPage);

                        if (nextPage >= totalPages) {
                            $btn.hide();
                        }
                    }
                },
                error: function () {
                    alert(ytripReviews.strings.error || 'Failed to load reviews.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(ytripReviews.strings.loadMore || 'Load More Reviews');
                }
            });
        });
    }

    /**
     * Build review HTML from data.
     */
    function buildReviewHTML(review) {
        const stars = buildStarsHTML(review.overall_rating);
        const verified = review.is_verified ?
            '<span class="ytrip-review-verified"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Verified</span>' : '';

        return `
			<div class="ytrip-review-item" id="review-${review.id}">
				<div class="ytrip-review-header">
					<div class="ytrip-review-author">
						<img src="${review.user_avatar}" alt="${review.user_name}" class="ytrip-review-avatar">
						<div class="ytrip-review-author-info">
							<span class="ytrip-review-author-name">${review.user_name} ${verified}</span>
							<span class="ytrip-review-date">${review.created_at}</span>
						</div>
					</div>
					<div class="ytrip-review-rating">${stars}</div>
				</div>
				${review.review_title ? `<h4 class="ytrip-review-title">${review.review_title}</h4>` : ''}
				${review.review_text ? `<div class="ytrip-review-content">${review.review_text}</div>` : ''}
				<div class="ytrip-review-footer">
					<div class="ytrip-review-helpful">
						<span>${ytripReviews.strings.wasHelpful || 'Was this review helpful?'}</span>
						<button type="button" class="ytrip-helpful-btn" data-review-id="${review.id}" data-vote="yes">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
							Yes <span class="ytrip-helpful-count">(${review.helpful_yes})</span>
						</button>
						<button type="button" class="ytrip-helpful-btn" data-review-id="${review.id}" data-vote="no">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>
							No <span class="ytrip-helpful-count">(${review.helpful_no})</span>
						</button>
					</div>
				</div>
			</div>
		`;
    }

    /**
     * Build stars HTML.
     */
    function buildStarsHTML(rating) {
        let html = '<span class="ytrip-stars">';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                html += '<span class="ytrip-star ytrip-star-full">★</span>';
            } else if (i - 0.5 <= rating) {
                html += '<span class="ytrip-star ytrip-star-half">★</span>';
            } else {
                html += '<span class="ytrip-star ytrip-star-empty">★</span>';
            }
        }
        html += '</span>';
        return html;
    }

    /**
     * Show message.
     */
    function showMessage($container, type, message) {
        const className = type === 'success' ? 'ytrip-message-success' : 'ytrip-message-error';
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
        if ($('.ytrip-reviews').length) {
            initStarRatings();
            initReviewForm();
            initPhotoUpload();
            initHelpfulVoting();
            initLoadMore();
        }
    });

})(jQuery);
