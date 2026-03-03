/**
 * Homepage destinations carousel — prev/next, autoplay, responsive items, equal sizes
 *
 * @package YTrip
 */
(function () {
	'use strict';

	var BREAKPOINT_TABLET = 768;
	var BREAKPOINT_DESKTOP = 1024;
	var GAP_PX = 32;

	var selector = '[data-ytrip-destinations-carousel]';

	function parseNum(val, fallback) {
		var n = parseInt(val, 10);
		return isNaN(n) ? fallback : n;
	}

	function getVisibleCount(container) {
		var w = typeof window !== 'undefined' ? window.innerWidth : 1024;
		var desktop = parseNum(container.getAttribute('data-items-desktop'), 6);
		var tablet = parseNum(container.getAttribute('data-items-tablet'), 4);
		var mobile = parseNum(container.getAttribute('data-items-mobile'), 2);
		if (w >= BREAKPOINT_DESKTOP) return desktop;
		if (w >= BREAKPOINT_TABLET) return tablet;
		return mobile;
	}

	function initCarousel(container) {
		var track = container.querySelector('.ytrip-destinations-carousel__track');
		var trackWrap = container.querySelector('.ytrip-destinations-carousel__track-wrap');
		var prevBtn = container.querySelector('.ytrip-destinations-carousel__nav--prev');
		var nextBtn = container.querySelector('.ytrip-destinations-carousel__nav--next');
		if (!track || !trackWrap || !prevBtn || !nextBtn) return;

		var items = track.querySelectorAll('.ytrip-destination-carousel-item');
		var total = items.length;
		if (total === 0) return;

		var autoplay = container.getAttribute('data-autoplay') === '1';
		var delaySec = parseNum(container.getAttribute('data-delay'), 5);
		var itemWidth = 0;
		var gap = GAP_PX;
		var visibleCount = getVisibleCount(container);
		var currentIndex = 0;
		var autoplayTimer = null;

		function getVisibleCountNow() {
			return getVisibleCount(container);
		}

		function getMaxIndex() {
			return Math.max(0, total - visibleCount);
		}

		function applyItemSizes() {
			var wrapWidth = trackWrap.getBoundingClientRect().width;
			if (wrapWidth <= 0) return;
			visibleCount = getVisibleCountNow();
			var totalGap = (visibleCount - 1) * gap;
			var w = (wrapWidth - totalGap) / visibleCount;
			itemWidth = w;
			container.style.setProperty('--ytrip-carousel-item-width', w + 'px');
			container.style.setProperty('--ytrip-carousel-image-size', Math.min(w * 0.92, 200) + 'px');
		}

		function applyTransform() {
			var offset = currentIndex * (itemWidth + gap);
			track.style.transform = 'translate3d(-' + offset + 'px, 0, 0)';
			var maxIdx = getMaxIndex();
			prevBtn.style.visibility = currentIndex <= 0 ? 'hidden' : '';
			nextBtn.style.visibility = currentIndex >= maxIdx ? 'hidden' : '';
		}

		function goPrev() {
			if (currentIndex <= 0) return;
			currentIndex -= 1;
			applyTransform();
			resetAutoplay();
		}

		function goNext() {
			var maxIdx = getMaxIndex();
			if (currentIndex >= maxIdx) {
				currentIndex = 0;
			} else {
				currentIndex += 1;
			}
			applyTransform();
			resetAutoplay();
		}

		function startAutoplay() {
			stopAutoplay();
			if (!autoplay || delaySec < 2) return;
			autoplayTimer = setInterval(function () {
				goNext();
			}, delaySec * 1000);
		}

		function stopAutoplay() {
			if (autoplayTimer) {
				clearInterval(autoplayTimer);
				autoplayTimer = null;
			}
		}

		function resetAutoplay() {
			if (autoplay) startAutoplay();
		}

		prevBtn.addEventListener('click', function () { goPrev(); });
		nextBtn.addEventListener('click', function () { goNext(); });

		container.addEventListener('mouseenter', stopAutoplay);
		container.addEventListener('mouseleave', function () { if (autoplay) startAutoplay(); });
		container.addEventListener('focusin', stopAutoplay);
		container.addEventListener('focusout', function () { if (autoplay) startAutoplay(); });

		function tick() {
			applyItemSizes();
			visibleCount = getVisibleCountNow();
			currentIndex = Math.min(currentIndex, getMaxIndex());
			applyTransform();
		}

		tick();
		startAutoplay();

		window.addEventListener('resize', function () {
			tick();
		});
	}

	function run() {
		var containers = document.querySelectorAll(selector);
		containers.forEach(function (el) { initCarousel(el); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();
