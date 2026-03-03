/**
 * YTrip Hero Slider - Swiper initialization
 * Homepage: fade effect. Single tour: slider or carousel per admin option.
 *
 * @package YTrip
 */
(function () {
    'use strict';

    function initHeroSlider() {
        var containers = document.querySelectorAll('.ytrip-hero-slider');
        if (!containers.length) return;

        if (typeof Swiper === 'undefined') {
            containers.forEach(function (el) { el.classList.add('ytrip-hero-no-swiper'); });
            return;
        }

        containers.forEach(function (el) {
            if (el.classList.contains('ytrip-hero-initialized')) return;

            var isSingleHero = el.classList.contains('ytrip-single-hero');
            var mode = isSingleHero && el.getAttribute('data-hero-mode') === 'carousel' ? 'carousel' : 'slider';

            var opts = {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false
                },
                pagination: {
                    el: el.querySelector('.swiper-pagination'),
                    clickable: true
                },
                navigation: {
                    nextEl: el.querySelector('.swiper-button-next'),
                    prevEl: el.querySelector('.swiper-button-prev')
                },
                touchEventsTarget: 'container',
                allowTouchMove: true,
                touchRatio: 1,
                touchAngle: 45,
                threshold: 8,
                longSwipesRatio: 0.3,
                longSwipesMs: 300,
                resistanceRatio: 0.85,
                passiveListeners: true,
                on: {
                    init: function () {
                        el.classList.add('ytrip-hero-initialized');
                    }
                }
            };

            if (mode === 'carousel') {
                opts.effect = 'slide';
                opts.slidesPerView = 1;
                opts.speed = 600;
            } else {
                opts.effect = 'fade';
                opts.fadeEffect = { crossFade: true };
            }

            new Swiper(el, opts);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeroSlider);
    } else {
        initHeroSlider();
    }
})();
