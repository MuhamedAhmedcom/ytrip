/**
 * YTrip Admin Live Preview JavaScript
 * 
 * Provides real-time CSS variable updates in the admin panel
 * for instant design preview without page reload.
 * 
 * @package YTrip
 * @since 2.2.0
 */

(function ($) {
    'use strict';

    const YTripPreview = {
        /**
         * CSS variable mapping from option IDs to CSS variable names
         */
        tokenMap: {
            // Cards
            'design_tokens_cards_tour_bg': '--ytrip-cards-tour-bg',
            'design_tokens_cards_tour_bg_hover': '--ytrip-cards-tour-bg-hover',
            'design_tokens_cards_tour_border_color': '--ytrip-cards-tour-border-color',
            'design_tokens_cards_tour_border_width': '--ytrip-cards-tour-border-width',
            'design_tokens_cards_tour_border_radius': '--ytrip-cards-tour-border-radius',
            'design_tokens_cards_tour_padding': '--ytrip-cards-tour-padding',
            'design_tokens_cards_tour_shadow': '--ytrip-cards-tour-shadow',
            'design_tokens_cards_tour_shadow_hover': '--ytrip-cards-tour-shadow-hover',

            // Buttons
            'design_tokens_buttons_primary_bg': '--ytrip-buttons-primary-bg',
            'design_tokens_buttons_primary_bg_hover': '--ytrip-buttons-primary-bg-hover',
            'design_tokens_buttons_primary_text_color': '--ytrip-buttons-primary-text-color',
            'design_tokens_buttons_primary_border_radius': '--ytrip-buttons-primary-border-radius',
            'design_tokens_buttons_primary_padding': '--ytrip-buttons-primary-padding',
            'design_tokens_buttons_primary_shadow': '--ytrip-buttons-primary-shadow',

            // Badge
            'design_tokens_cards_badge_bg': '--ytrip-cards-badge-bg',
            'design_tokens_cards_badge_text_color': '--ytrip-cards-badge-text-color',

            // Price
            'design_tokens_cards_price_color': '--ytrip-cards-price-color',
            'design_tokens_cards_price_font_size': '--ytrip-cards-price-font-size',

            // Meta
            'design_tokens_cards_meta_color': '--ytrip-cards-meta-color',
        },

        /**
         * Initialize the preview system
         */
        init: function () {
            this.bindEvents();
            this.injectStyleElement();
        },

        /**
         * Create style element for dynamic CSS variable injection
         */
        injectStyleElement: function () {
            if ($('#ytrip-live-preview-styles').length === 0) {
                $('head').append('<style id="ytrip-live-preview-styles"></style>');
            }
        },

        /**
         * Bind change events to all relevant option fields
         */
        bindEvents: function () {
            const self = this;

            // Watch for color picker changes
            $(document).on('change keyup', '.csf-field-color input, .csf-field-text input', function () {
                const $field = $(this).closest('.csf-field');
                const fieldId = $field.find('[data-depend-id]').data('depend-id');

                if (fieldId && self.tokenMap[fieldId]) {
                    self.updateCSSVariable(self.tokenMap[fieldId], $(this).val());
                }
            });

            // Watch for slider changes
            $(document).on('csf-slider-change', '.csf-field-slider', function (e, value) {
                const $field = $(this).closest('.csf-field');
                const fieldId = $field.find('[data-depend-id]').data('depend-id');

                if (fieldId && self.tokenMap[fieldId]) {
                    const unit = $field.find('.csf-slider-unit').text() || '';
                    self.updateCSSVariable(self.tokenMap[fieldId], value + unit);
                }
            });

            // Watch for select changes
            $(document).on('change', '.csf-field-select select', function () {
                const $field = $(this).closest('.csf-field');
                const fieldId = $field.find('[data-depend-id]').data('depend-id');

                if (fieldId && self.tokenMap[fieldId]) {
                    self.updateCSSVariable(self.tokenMap[fieldId], $(this).val());
                }
            });
        },

        /**
         * Update a CSS custom property
         * @param {string} variable - CSS variable name
         * @param {string} value - New value
         */
        updateCSSVariable: function (variable, value) {
            if (!value) return;

            // Update the inline style element
            const $style = $('#ytrip-live-preview-styles');
            let css = $style.html();

            // Check if variable already exists in styles
            const regex = new RegExp(variable.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ':[^;]+;', 'g');

            if (regex.test(css)) {
                css = css.replace(regex, variable + ': ' + value + ';');
            } else {
                // Add new variable
                if (css.indexOf(':root {') === -1) {
                    css = ':root {\n' + variable + ': ' + value + ';\n}';
                } else {
                    css = css.replace(':root {', ':root {\n' + variable + ': ' + value + ';');
                }
            }

            $style.html(css);

            // Also update the preview card directly if it exists
            const $previewCard = $('#ytrip-preview-card');
            if ($previewCard.length) {
                document.documentElement.style.setProperty(variable, value);
            }
        },

        /**
         * Get current CSS variable value
         * @param {string} variable - CSS variable name
         * @returns {string} Current value
         */
        getCSSVariable: function (variable) {
            return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        // Only initialize on YTrip settings pages
        if ($('.csf-options').length && $('body').hasClass('toplevel_page_ytrip-settings')) {
            YTripPreview.init();
        }
    });

    // Expose to global scope for debugging
    window.YTripPreview = YTripPreview;

})(jQuery);
