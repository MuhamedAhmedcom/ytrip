/**
 * YTrip Animations
 * Handles scroll-triggered animations using Intersection Observer
 */

(function ($) {
    'use strict';

    class YTripAnimations {
        constructor() {
            this.init();
        }

        init() {
            this.setupObserver();
            this.addAnimationClasses();
        }

        setupObserver() {
            const options = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            this.observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('ytrip-animated');
                        this.observer.unobserve(entry.target);
                    }
                });
            }, options);
        }

        addAnimationClasses() {
            // Select elements to animate
            const elements = document.querySelectorAll(
                '.ytrip-card, .ytrip-section__title, .ytrip-highlights__item, .ytrip-gallery-grid__item, .ytrip-itinerary-modern__day'
            );

            elements.forEach((el, index) => {
                el.classList.add('ytrip-animate-fade-up');

                // Add stagger delay
                if (index % 3 === 1) el.style.transitionDelay = '0.1s';
                if (index % 3 === 2) el.style.transitionDelay = '0.2s';

                this.observer.observe(el);
            });
        }
    }

    // Initialize on load
    $(document).ready(function () {
        if (typeof ytrip_vars !== 'undefined' && ytrip_vars.enable_animations === '1') {
            new YTripAnimations();
        }
    });

})(jQuery);
