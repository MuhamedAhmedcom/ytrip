/**
 * YTrip Parallax
 * Handles parallax effects for hero sections and backgrounds
 */

(function ($) {
    'use strict';

    class YTripParallax {
        constructor() {
            this.elements = document.querySelectorAll('.ytrip-hero-tour__bg img, .ytrip-magazine-hero__bg img');
            if (this.elements.length) {
                this.init();
            }
        }

        init() {
            window.addEventListener('scroll', () => this.onScroll());
            this.onScroll(); // Initial call
        }

        onScroll() {
            const scrolled = window.scrollY;

            this.elements.forEach(el => {
                const speed = 0.4;
                const offset = scrolled * speed;

                // Uses transform for GPU acceleration
                el.style.transform = `translateY(${offset}px) scale(1.1)`;
            });
        }
    }

    // Initialize
    $(document).ready(function () {
        if (typeof ytrip_vars !== 'undefined' && ytrip_vars.enable_parallax === '1') {
            new YTripParallax();
        }
    });

})(jQuery);
