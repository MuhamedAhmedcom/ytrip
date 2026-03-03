<?php
/**
 * YTrip Dark Mode Toggle Component
 * 
 * Usage: echo ytrip_dark_mode_toggle();
 *
 * @package YTrip
 * @since 2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render dark mode toggle button.
 *
 * @param array $args Arguments.
 * @return string
 */
function ytrip_dark_mode_toggle(array $args = []) {
    $defaults = [
        'style' => 'icon', // icon, switch, button
        'show_label' => false,
        'class' => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $class = 'ytrip-dark-mode-toggle ' . $args['class'];

    ob_start();

    if ($args['style'] === 'switch') :
        ?>
        <button type="button" class="<?php echo esc_attr($class); ?> ytrip-dark-switch" aria-label="<?php esc_attr_e('Toggle dark mode', 'ytrip'); ?>">
            <span class="ytrip-switch-track">
                <span class="ytrip-switch-thumb"></span>
            </span>
            <?php if ($args['show_label']) : ?>
                <span class="ytrip-switch-label"><?php esc_html_e('Dark Mode', 'ytrip'); ?></span>
            <?php endif; ?>
        </button>
        <?php
    elseif ($args['style'] === 'button') :
        ?>
        <button type="button" class="<?php echo esc_attr($class); ?> ytrip-dark-button" aria-label="<?php esc_attr_e('Toggle dark mode', 'ytrip'); ?>">
            <span class="ytrip-btn-icon ytrip-icon-sun">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </span>
            <span class="ytrip-btn-icon ytrip-icon-moon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </span>
            <?php if ($args['show_label']) : ?>
                <span class="ytrip-btn-label"><?php esc_html_e('Toggle Theme', 'ytrip'); ?></span>
            <?php endif; ?>
        </button>
        <?php
    else :
        ?>
        <button type="button" class="<?php echo esc_attr($class); ?> ytrip-dark-icon" aria-label="<?php esc_attr_e('Toggle dark mode', 'ytrip'); ?>">
            <span class="ytrip-icon-sun">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </span>
            <span class="ytrip-icon-moon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </span>
        </button>
        <?php
    endif;

    return ob_get_clean();
}

/**
 * Output dark mode toggle CSS.
 */
add_action('wp_head', function () {
    $settings = get_option('ytrip_settings', []);
    if (empty($settings['dark_mode'])) {
        return;
    }
    ?>
    <style id="ytrip-dark-mode-toggle-css">
        .ytrip-dark-mode-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            background: transparent;
            border: none;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .ytrip-dark-mode-toggle:hover {
            background: rgba(0,0,0,0.05);
        }

        .ytrip-dark-mode .ytrip-dark-mode-toggle:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Icon Style */
        .ytrip-dark-icon .ytrip-icon-sun,
        .ytrip-dark-icon .ytrip-icon-moon {
            display: flex;
            transition: transform 0.3s, opacity 0.3s;
        }

        .ytrip-dark-icon .ytrip-icon-moon {
            display: none;
        }

        .ytrip-dark-mode .ytrip-dark-icon .ytrip-icon-sun {
            display: none;
        }

        .ytrip-dark-mode .ytrip-dark-icon .ytrip-icon-moon {
            display: flex;
        }

        /* Switch Style */
        .ytrip-dark-switch {
            gap: 10px;
        }

        .ytrip-switch-track {
            width: 44px;
            height: 24px;
            background: #e5e7eb;
            border-radius: 12px;
            position: relative;
            transition: background 0.3s;
        }

        .ytrip-dark-mode .ytrip-switch-track {
            background: #475569;
        }

        .ytrip-switch-thumb {
            position: absolute;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ytrip-dark-mode .ytrip-switch-thumb {
            transform: translateX(20px);
        }

        .ytrip-switch-label {
            font-size: 14px;
            color: var(--ytrip-text, #1e293b);
        }

        /* Button Style */
        .ytrip-dark-button {
            padding: 10px 16px;
            background: var(--ytrip-surface, #fff);
            border: 1px solid var(--ytrip-border, #e5e7eb);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--ytrip-text, #1e293b);
            gap: 8px;
            transition: all 0.2s;
        }

        .ytrip-dark-button:hover {
            border-color: var(--ytrip-primary, #2563eb);
            color: var(--ytrip-primary, #2563eb);
        }

        .ytrip-dark-button .ytrip-btn-icon {
            display: flex;
            transition: transform 0.3s, opacity 0.3s;
        }

        .ytrip-dark-button .ytrip-icon-moon {
            display: none;
        }

        .ytrip-dark-mode .ytrip-dark-button .ytrip-icon-sun {
            display: none;
        }

        .ytrip-dark-mode .ytrip-dark-button .ytrip-icon-moon {
            display: flex;
        }
    </style>
    <?php
});
