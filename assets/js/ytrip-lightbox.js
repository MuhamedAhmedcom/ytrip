/**
 * YTrip Lightbox — zero-dependency, pure JS lightbox gallery.
 *
 * Features:
 * - Full-screen overlay with backdrop blur
 * - Prev/Next slide navigation (buttons + keyboard)
 * - Touch swipe support (mobile)
 * - Zoom toggle (fit ↔ actual-size)
 * - Close via button, ESC key, or click outside image
 * - Image counter ("2 / 5")
 * - Smooth CSS-driven transitions
 *
 * Usage:
 *   Add class "ytrip-lightbox-gallery" to the gallery wrapper.
 *   Each child <a> or <img> with data-lightbox-src="..." is an entry.
 *
 * @package YTrip
 * @since   2.2.0
 */

(function () {
  'use strict';

  /* ================================================================
   * Constants
   * ================================================================ */
  var TRANSITION_MS = 300;
  var SWIPE_THRESHOLD = 50;

  /* ================================================================
   * State
   * ================================================================ */
  var images = [];
  var currentIndex = 0;
  var isOpen = false;
  var isZoomed = false;

  // Touch helpers
  var touchStartX = 0;
  var touchStartY = 0;
  var touchDeltaX = 0;

  /* ================================================================
   * DOM refs (lazily created)
   * ================================================================ */
  var overlay, imgEl, prevBtn, nextBtn, closeBtn, counter, zoomBtn, spinner;

  /* ================================================================
   * Build lightbox DOM (once)
   * ================================================================ */
  function buildLightbox() {
    if (overlay) return;

    overlay = document.createElement('div');
    overlay.className = 'ytrip-lb';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Image lightbox');

    overlay.innerHTML = [
      '<div class="ytrip-lb__backdrop"></div>',
      '<div class="ytrip-lb__spinner"><div class="ytrip-lb__spin-ring"></div></div>',
      '<button class="ytrip-lb__close" aria-label="Close">&times;</button>',
      '<button class="ytrip-lb__prev" aria-label="Previous image">',
      '  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
      '</button>',
      '<button class="ytrip-lb__next" aria-label="Next image">',
      '  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
      '</button>',
      '<div class="ytrip-lb__bottom">',
      '  <span class="ytrip-lb__counter"></span>',
      '  <button class="ytrip-lb__zoom" aria-label="Toggle zoom">',
      '    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
      '  </button>',
      '</div>',
      '<div class="ytrip-lb__img-wrap">',
      '  <img class="ytrip-lb__img" src="" alt="" draggable="false" />',
      '</div>'
    ].join('\n');

    document.body.appendChild(overlay);

    // Cache refs
    imgEl    = overlay.querySelector('.ytrip-lb__img');
    prevBtn  = overlay.querySelector('.ytrip-lb__prev');
    nextBtn  = overlay.querySelector('.ytrip-lb__next');
    closeBtn = overlay.querySelector('.ytrip-lb__close');
    counter  = overlay.querySelector('.ytrip-lb__counter');
    zoomBtn  = overlay.querySelector('.ytrip-lb__zoom');
    spinner  = overlay.querySelector('.ytrip-lb__spinner');

    // Event listeners
    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', function (e) { e.stopPropagation(); prev(); });
    nextBtn.addEventListener('click', function (e) { e.stopPropagation(); next(); });
    zoomBtn.addEventListener('click', function (e) { e.stopPropagation(); toggleZoom(); });

    overlay.querySelector('.ytrip-lb__backdrop').addEventListener('click', close);
    overlay.querySelector('.ytrip-lb__img-wrap').addEventListener('click', function (e) {
      if (e.target === this) close();
    });

    // Keyboard
    document.addEventListener('keydown', onKeyDown);

    // Touch events on image wrapper
    var wrap = overlay.querySelector('.ytrip-lb__img-wrap');
    wrap.addEventListener('touchstart', onTouchStart, { passive: true });
    wrap.addEventListener('touchmove', onTouchMove, { passive: false });
    wrap.addEventListener('touchend', onTouchEnd, { passive: true });

    // Image load
    imgEl.addEventListener('load', function () {
      spinner.classList.remove('ytrip-lb__spinner--visible');
      imgEl.classList.add('ytrip-lb__img--loaded');
    });
  }

  /* ================================================================
   * Open / Close
   * ================================================================ */
  function open(galleryImages, startIndex) {
    buildLightbox();
    images = galleryImages;
    currentIndex = startIndex || 0;
    isOpen = true;
    isZoomed = false;

    document.body.style.overflow = 'hidden';
    overlay.classList.add('ytrip-lb--open');
    showImage(currentIndex);
    updateNav();
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    isZoomed = false;
    overlay.classList.remove('ytrip-lb--open');
    overlay.classList.remove('ytrip-lb--zoomed');
    document.body.style.overflow = '';
    setTimeout(function () {
      imgEl.src = '';
      imgEl.classList.remove('ytrip-lb__img--loaded');
    }, TRANSITION_MS);
  }

  /* ================================================================
   * Navigate
   * ================================================================ */
  function showImage(idx) {
    if (isZoomed) {
      isZoomed = false;
      overlay.classList.remove('ytrip-lb--zoomed');
    }

    imgEl.classList.remove('ytrip-lb__img--loaded');
    spinner.classList.add('ytrip-lb__spinner--visible');

    var entry = images[idx];
    imgEl.src = entry.src;
    imgEl.alt = entry.alt || '';
    counter.textContent = (idx + 1) + ' / ' + images.length;
  }

  function next() {
    if (images.length < 2) return;
    currentIndex = (currentIndex + 1) % images.length;
    showImage(currentIndex);
    updateNav();
  }

  function prev() {
    if (images.length < 2) return;
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    showImage(currentIndex);
    updateNav();
  }

  function updateNav() {
    var multi = images.length > 1;
    prevBtn.style.display = multi ? '' : 'none';
    nextBtn.style.display = multi ? '' : 'none';
  }

  /* ================================================================
   * Zoom
   * ================================================================ */
  function toggleZoom() {
    isZoomed = !isZoomed;
    overlay.classList.toggle('ytrip-lb--zoomed', isZoomed);
  }

  /* ================================================================
   * Keyboard
   * ================================================================ */
  function onKeyDown(e) {
    if (!isOpen) return;
    switch (e.key) {
      case 'Escape':
        close();
        break;
      case 'ArrowLeft':
        prev();
        break;
      case 'ArrowRight':
        next();
        break;
    }
  }

  /* ================================================================
   * Touch / Swipe
   * ================================================================ */
  function onTouchStart(e) {
    if (isZoomed) return;
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    touchDeltaX = 0;
  }

  function onTouchMove(e) {
    if (isZoomed) return;
    touchDeltaX = e.touches[0].clientX - touchStartX;
    var deltaY = Math.abs(e.touches[0].clientY - touchStartY);
    // Prevent vertical scroll when swiping horizontally
    if (Math.abs(touchDeltaX) > deltaY) {
      e.preventDefault();
    }
  }

  function onTouchEnd() {
    if (isZoomed) return;
    if (touchDeltaX > SWIPE_THRESHOLD) {
      prev();
    } else if (touchDeltaX < -SWIPE_THRESHOLD) {
      next();
    }
    touchDeltaX = 0;
  }

  /* ================================================================
   * Auto-init: scan DOM for galleries
   * ================================================================ */
  function init() {
    var galleries = document.querySelectorAll('.ytrip-lightbox-gallery');
    if (!galleries.length) return;

    galleries.forEach(function (gallery) {
      var items = gallery.querySelectorAll('[data-lightbox-src]');
      if (!items.length) return;

      // Collect image data
      var galleryImages = [];
      items.forEach(function (item, i) {
        galleryImages.push({
          src: item.getAttribute('data-lightbox-src'),
          alt: item.getAttribute('data-lightbox-alt') || item.getAttribute('alt') || ''
        });

        item.style.cursor = 'pointer';
        item.addEventListener('click', function (e) {
          e.preventDefault();
          open(galleryImages, i);
        });
      });
    });
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose API for programmatic use
  window.YTripLightbox = {
    open: open,
    close: close
  };

})();
