/**
 * YTrip Microinteractions
 * Handles button ripples, card tilts, and other interactive effects
 */

(function ($) {
    'use strict';

    // Ripple Effect (capped size so large buttons don't get a huge ripple)
    var RIPPLE_MAX_DIAMETER = 120;

    function createRipple(event) {
        const button = event.currentTarget;
        var rect = button.getBoundingClientRect();
        var diameter = Math.max(button.clientWidth, button.clientHeight);
        if (diameter > RIPPLE_MAX_DIAMETER) {
            diameter = RIPPLE_MAX_DIAMETER;
        }
        var radius = diameter / 2;
        var left = event.clientX - rect.left - radius;
        var top = event.clientY - rect.top - radius;

        var ripple = button.getElementsByClassName('ytrip-ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        var circle = document.createElement('span');
        circle.classList.add('ytrip-ripple');
        circle.style.width = diameter + 'px';
        circle.style.height = diameter + 'px';
        circle.style.left = left + 'px';
        circle.style.top = top + 'px';

        button.appendChild(circle);
    }

    // 3D Tilt Effect
    function handleTilt(e) {
        const el = e.currentTarget;
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -5;
        const rotateY = ((x - centerX) / centerX) * 5;

        el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
    }

    function resetTilt(e) {
        const el = e.currentTarget;
        el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
    }

    $(document).ready(function () {
        // Init Ripple
        $('.ytrip-btn, .ytrip-card__book-btn').on('click', createRipple);

        // Init Tilt for cards with 'tilt' data-hover attribute
        $('[data-hover="tilt"]').on('mousemove', handleTilt).on('mouseleave', resetTilt);
    });

})(jQuery);
