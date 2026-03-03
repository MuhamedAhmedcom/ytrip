/**
 * Homepage search form: custom location dropdown (open/close, select, accessibility).
 *
 * @package YTrip
 * @since 1.0.0
 */
(function () {
    'use strict';

    function initLocationDropdown() {
        var root = document.getElementById('ytrip-location-dropdown');
        if (!root) return;

        var trigger = root.querySelector('.ytrip-location-dropdown__trigger');
        var valueEl = root.querySelector('.ytrip-location-dropdown__value');
        var hiddenInput = root.querySelector('input[name="destination"]');
        var panel = root.querySelector('.ytrip-location-dropdown__panel');
        var items = root.querySelectorAll('.ytrip-location-dropdown__item');

        var placeholder = valueEl && valueEl.getAttribute('data-placeholder') ? valueEl.getAttribute('data-placeholder') : '';

        function isOpen() {
            return panel && panel.getAttribute('aria-hidden') === 'false';
        }

        function open() {
            if (!panel || !trigger) return;
            panel.setAttribute('aria-hidden', 'false');
            if (trigger.setAttribute) trigger.setAttribute('aria-expanded', 'true');
            panel.classList.add('is-open');
        }

        function close() {
            if (!panel || !trigger) return;
            panel.setAttribute('aria-hidden', 'true');
            if (trigger.setAttribute) trigger.setAttribute('aria-expanded', 'false');
            panel.classList.remove('is-open');
        }

        function setValue(slug, name) {
            if (hiddenInput) hiddenInput.value = slug || '';
            if (valueEl) valueEl.textContent = name || placeholder;
            close();
        }

        if (trigger && panel) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                if (isOpen()) close(); else open();
            });
        }

        if (items && items.length) {
            items.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var slug = btn.getAttribute('data-slug');
                    var name = btn.getAttribute('data-name');
                    setValue(slug, name);
                });
            });
        }

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target) && isOpen()) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                close();
            }
        });

        /* Close location dropdown when calendar (or other search field) is opened */
        document.addEventListener('click', function (e) {
            var target = e.target && e.target.closest ? e.target.closest('[data-ytrip-calendar], .ytrip-date-range-display, .ytrip-date-display') : null;
            if (target && isOpen()) close();
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLocationDropdown);
    } else {
        initLocationDropdown();
    }
})();
