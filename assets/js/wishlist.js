/**
 * YTrip Wishlist JavaScript
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    /**
     * Initialize wishlist buttons.
     */
    function initWishlistButtons() {
        // Mark existing wishlisted items
        if (ytripWishlist.wishlist && ytripWishlist.wishlist.length) {
            ytripWishlist.wishlist.forEach(function (tourId) {
                $('.ytrip-wishlist-btn[data-tour-id="' + tourId + '"]').addClass('active ytrip-tour-card__wishlist--active');
            });
        }

        // Toggle wishlist
        $(document).on('click', '.ytrip-wishlist-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var tourId = $btn.data('tour-id');
            var isActive = $btn.hasClass('active');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');

            $.ajax({
                url: ytripWishlist.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ytrip_toggle_wishlist',
                    security: ytripWishlist.nonce,
                    tour_id: tourId
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data.added) {
                            $btn.addClass('active ytrip-tour-card__wishlist--active');
                            showToast(ytripWishlist.strings.added, 'success');
                        } else {
                            $btn.removeClass('active ytrip-tour-card__wishlist--active');
                            showToast(ytripWishlist.strings.removed, 'info');
                        }

                        // Update count badge
                        updateWishlistCount(response.data.count);

                        // Update local state
                        var index = ytripWishlist.wishlist.indexOf(tourId);
                        if (response.data.added && index === -1) {
                            ytripWishlist.wishlist.push(tourId);
                        } else if (!response.data.added && index > -1) {
                            ytripWishlist.wishlist.splice(index, 1);
                        }
                    } else {
                        showToast(response.data.message || ytripWishlist.strings.error, 'error');
                    }
                },
                error: function () {
                    showToast(ytripWishlist.strings.error, 'error');
                },
                complete: function () {
                    $btn.removeClass('loading');
                }
            });
        });
    }

    /**
     * Update wishlist count in header.
     */
    function updateWishlistCount(count) {
        var $badge = $('.ytrip-wishlist-count');

        if ($badge.length) {
            $badge.text(count);

            if (count > 0) {
                $badge.addClass('has-items');
            } else {
                $badge.removeClass('has-items');
            }
        }
    }

    /**
     * Show toast notification.
     */
    function showToast(message, type) {
        type = type || 'info';

        // Remove existing toast
        $('.ytrip-toast').remove();

        // Create toast
        var $toast = $('<div class="ytrip-toast ytrip-toast-' + type + '">' +
            '<span class="ytrip-toast-message">' + message + '</span>' +
            '<button class="ytrip-toast-close">&times;</button>' +
            '</div>');

        // Add to body
        $('body').append($toast);

        // Animate in
        setTimeout(function () {
            $toast.addClass('show');
        }, 10);

        // Auto-hide after 3 seconds
        setTimeout(function () {
            $toast.removeClass('show');
            setTimeout(function () {
                $toast.remove();
            }, 300);
        }, 3000);

        // Close button
        $toast.find('.ytrip-toast-close').on('click', function () {
            $toast.removeClass('show');
            setTimeout(function () {
                $toast.remove();
            }, 300);
        });
    }

    /**
     * Add toast styles dynamically.
     */
    function addToastStyles() {
        if ($('#ytrip-toast-styles').length) {
            return;
        }

        var styles = `
			.ytrip-toast {
				position: fixed;
				bottom: 20px;
				left: 50%;
				transform: translateX(-50%) translateY(100%);
				z-index: 99999;
				display: flex;
				align-items: center;
				gap: 12px;
				padding: 12px 20px;
				background: #1e293b;
				color: #fff;
				border-radius: 10px;
				box-shadow: 0 4px 20px rgba(0,0,0,0.2);
				font-size: 14px;
				font-weight: 500;
				opacity: 0;
				transition: all 0.3s ease;
			}
			.ytrip-toast.show {
				transform: translateX(-50%) translateY(0);
				opacity: 1;
			}
			.ytrip-toast-success { background: #059669; }
			.ytrip-toast-error { background: #dc2626; }
			.ytrip-toast-info { background: #1e293b; }
			.ytrip-toast-close {
				background: none;
				border: none;
				color: rgba(255,255,255,0.7);
				font-size: 18px;
				cursor: pointer;
				padding: 0;
				line-height: 1;
			}
			.ytrip-toast-close:hover { color: #fff; }
			
			.ytrip-wishlist-btn {
				position: relative;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 40px;
				height: 40px;
				background: rgba(255,255,255,0.9);
				border: none;
				border-radius: 50%;
				cursor: pointer;
				transition: all 0.2s ease;
			}
			.ytrip-wishlist-btn svg {
				width: 20px;
				height: 20px;
				stroke: #64748b;
				fill: none;
				transition: all 0.2s ease;
			}
			.ytrip-wishlist-btn:hover svg {
				stroke: #ef4444;
			}
			.ytrip-wishlist-btn.active svg {
				stroke: #ef4444;
				fill: #ef4444;
			}
			.ytrip-wishlist-btn.loading {
				pointer-events: none;
				opacity: 0.6;
			}
			.ytrip-guest-wishlist { position: fixed; bottom: 24px; right: 24px; z-index: 99998; }
			.ytrip-guest-wishlist-trigger {
				display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px;
				background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
				box-shadow: 0 4px 14px rgba(0,0,0,0.1); cursor: pointer; font-size: 14px; font-weight: 500;
				color: #333; transition: all 0.2s;
			}
			.ytrip-guest-wishlist-trigger:hover { border-color: #0d9488; color: #0d9488; box-shadow: 0 4px 18px rgba(13,148,136,0.15); }
			.ytrip-guest-wishlist-trigger__icon { color: #ef4444; font-size: 18px; }
			.ytrip-guest-wishlist-count { min-width: 20px; padding: 2px 6px; border-radius: 10px; background: #f1f5f9; font-size: 12px; text-align: center; }
			.ytrip-guest-wishlist-count.has-items { background: #ef4444; color: #fff; }
			.ytrip-guest-wishlist-drawer {
				position: fixed; top: 0; right: -360px; width: 360px; max-width: 100vw; height: 100vh;
				background: #fff; box-shadow: -4px 0 24px rgba(0,0,0,0.12); z-index: 100000;
				display: flex; flex-direction: column; transition: right 0.3s ease; overflow: hidden;
			}
			.ytrip-guest-wishlist.open .ytrip-guest-wishlist-drawer { right: 0; }
			.ytrip-guest-wishlist-drawer__header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #eee; flex-shrink: 0; }
			.ytrip-guest-wishlist-drawer__title { margin: 0; font-size: 1.125rem; font-weight: 600; }
			.ytrip-guest-wishlist-drawer__close { background: none; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: #64748b; padding: 4px; }
			.ytrip-guest-wishlist-drawer__close:hover { color: #333; }
			.ytrip-guest-wishlist-drawer__body { flex: 1; overflow: auto; padding: 1rem; }
			.ytrip-guest-wishlist-drawer__list { display: flex; flex-direction: column; gap: 0.75rem; }
			.ytrip-guest-wishlist-item { display: flex; gap: 12px; padding: 10px; border: 1px solid #eee; border-radius: 10px; align-items: center; }
			.ytrip-guest-wishlist-item__thumb { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
			.ytrip-guest-wishlist-item__content { flex: 1; min-width: 0; }
			.ytrip-guest-wishlist-item__title { font-weight: 600; font-size: 14px; margin: 0 0 4px 0; }
			.ytrip-guest-wishlist-item__title a { color: inherit; text-decoration: none; }
			.ytrip-guest-wishlist-item__title a:hover { text-decoration: underline; }
			.ytrip-guest-wishlist-item__remove { flex-shrink: 0; width: 32px; height: 32px; border: none; background: #fef2f2; color: #ef4444; border-radius: 8px; cursor: pointer; font-size: 16px; line-height: 1; padding: 0; }
			.ytrip-guest-wishlist-item__remove:hover { background: #ef4444; color: #fff; }
			.ytrip-guest-wishlist-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 99999; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
			.ytrip-guest-wishlist.open .ytrip-guest-wishlist-backdrop { opacity: 1; pointer-events: auto; }
			@media (max-width: 480px) {
				.ytrip-guest-wishlist { bottom: 16px; right: 16px; left: 16px; }
				.ytrip-guest-wishlist-trigger { min-height: 48px; padding: 12px 16px; width: 100%; justify-content: center; -webkit-tap-highlight-color: transparent; }
				.ytrip-guest-wishlist-drawer { width: 100%; max-width: 100%; right: -100%; border-radius: 0; }
				.ytrip-guest-wishlist.open .ytrip-guest-wishlist-drawer { right: 0; }
				.ytrip-guest-wishlist-drawer__close { min-width: 44px; min-height: 44px; }
				.ytrip-guest-wishlist-item__remove { min-width: 44px; min-height: 44px; }
			}
			@media (max-width: 768px) {
				.ytrip-guest-wishlist-trigger { min-height: 44px; -webkit-tap-highlight-color: transparent; }
			}
		`;

        $('<style id="ytrip-toast-styles">' + styles + '</style>').appendTo('head');
    }

    /**
     * Guest wishlist drawer: open/close, fetch list, render, remove.
     */
    function initGuestDrawer() {
        if (!ytripWishlist.isGuest || !$('#ytrip-guest-wishlist-drawer').length) {
            return;
        }
        var $wrap = $('#ytrip-guest-wishlist-drawer');
        var $trigger = $wrap.find('.ytrip-guest-wishlist-trigger');
        var $drawer = $wrap.find('.ytrip-guest-wishlist-drawer');
        var $list = $wrap.find('.ytrip-guest-wishlist-drawer__list');
        var $empty = $wrap.find('.ytrip-guest-wishlist-drawer__empty');
        var $countEl = $wrap.find('.ytrip-guest-wishlist-count');

        function openDrawer() {
            $wrap.addClass('open').attr('aria-hidden', 'false');
            loadDrawerList();
        }
        function closeDrawer() {
            $wrap.removeClass('open').attr('aria-hidden', 'true');
        }
        function loadDrawerList() {
            $list.empty();
            $empty.hide();
            $.ajax({
                url: ytripWishlist.restUrl,
                type: 'GET',
                dataType: 'json',
                xhrFields: { withCredentials: true }
            }).done(function (data) {
                var tours = data.tours || [];
                if (tours.length === 0) {
                    $empty.show();
                } else {
                    tours.forEach(function (t) {
                        var thumb = t.thumbnail ? '<img class="ytrip-guest-wishlist-item__thumb" src="' + t.thumbnail + '" alt="">' : '<div class="ytrip-guest-wishlist-item__thumb" style="background:#f1f5f9;"></div>';
                        var row = '<div class="ytrip-guest-wishlist-item" data-tour-id="' + t.id + '">' +
                            thumb +
                            '<div class="ytrip-guest-wishlist-item__content"><h4 class="ytrip-guest-wishlist-item__title"><a href="' + (t.url || '#') + '">' + (t.title || '') + '</a></h4></div>' +
                            '<button type="button" class="ytrip-guest-wishlist-item__remove" aria-label="Remove">&times;</button></div>';
                        $list.append(row);
                    });
                }
            }).fail(function () {
                $empty.text(ytripWishlist.strings.error || 'Error').show();
            });
        }
        function refreshCount(count) {
            if (typeof count === 'undefined') {
                count = ytripWishlist.wishlist ? ytripWishlist.wishlist.length : 0;
            }
            $countEl.text(count);
            if (count > 0) {
                $countEl.addClass('has-items');
            } else {
                $countEl.removeClass('has-items');
            }
        }

        $trigger.on('click', openDrawer);
        $wrap.find('.ytrip-guest-wishlist-drawer__close, .ytrip-guest-wishlist-backdrop').on('click', closeDrawer);
        $(document).on('click', '.ytrip-guest-wishlist-item__remove', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.ytrip-guest-wishlist-item');
            var tourId = $item.data('tour-id');
            if (!tourId) return;
            $(this).prop('disabled', true);
            $.ajax({
                url: ytripWishlist.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ytrip_toggle_wishlist',
                    security: ytripWishlist.nonce,
                    tour_id: tourId
                },
                success: function (response) {
                    if (response.success && !response.data.added) {
                        $item.fadeOut(200, function () { $(this).remove(); });
                        var idx = ytripWishlist.wishlist.indexOf(tourId);
                        if (idx > -1) ytripWishlist.wishlist.splice(idx, 1);
                        updateWishlistCount(response.data.count);
                        refreshCount(response.data.count);
                        showToast(ytripWishlist.strings.removed, 'info');
                    }
                },
                complete: function () {
                    $item.find('.ytrip-guest-wishlist-item__remove').prop('disabled', false);
                }
            });
        });

        window.ytripRefreshGuestWishlistCount = refreshCount;
    }

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        addToastStyles();
        initWishlistButtons();
        initGuestDrawer();
    });

})(jQuery);
