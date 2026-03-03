<?php
/**
 * YTrip Universal Theme Compatibility System
 * 
 * Provides seamless integration with ANY WordPress theme.
 * Handles full-width sections, CSS isolation, and theme-specific fixes.
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Theme_Compatibility
 * 
 * Universal rendering system that works with any WordPress theme.
 */
class YTrip_Theme_Compatibility {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Current theme slug.
     *
     * @var string
     */
    private $theme_slug = '';

    /**
     * Theme-specific configurations.
     *
     * @var array
     */
    private $theme_configs = [];

    /**
     * Container closing stack.
     *
     * @var array
     */
    private $container_stack = [];

    /**
     * Section counter for unique IDs.
     *
     * @var int
     */
    private $section_counter = 0;

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->theme_slug = $this->get_theme_slug();
        $this->load_theme_configs();
        $this->init_hooks();
    }

    /**
     * Get current theme slug.
     *
     * @return string
     */
    private function get_theme_slug() {
        $theme = wp_get_theme();
        return sanitize_key($theme->get_template());
    }

    /**
     * Load theme-specific configurations.
     *
     * @return void
     */
    private function load_theme_configs() {
        // Default configurations for popular themes
        $this->theme_configs = [
            'astra' => [
                'container_class' => 'ast-container',
                'content_class' => 'ast-plain-container',
                'break_selectors' => ['#primary', '.ast-plain-container', '.entry-content'],
                'full_width_class' => 'alignfull',
                'css_scope' => '.ytrip-section',
            ],
            'generatepress' => [
                'container_class' => 'grid-container',
                'content_class' => 'site-content',
                'break_selectors' => ['#primary', '.inside-article', '.entry-content'],
                'full_width_class' => 'full-width',
                'css_scope' => '.ytrip-section',
            ],
            'oceanwp' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#content-wrap', '.entry-content'],
                'full_width_class' => 'owp-full-width',
                'css_scope' => '.ytrip-section',
            ],
            'divi' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#main-content', '.et_pb_row', '.entry-content'],
                'full_width_class' => 'et_pb_fullwidth_section',
                'css_scope' => '.ytrip-section',
            ],
            'elementor-hello' => [
                'container_class' => 'elementor-container',
                'content_class' => 'elementor-entry-content',
                'break_selectors' => ['.elementor-section'],
                'full_width_class' => 'elementor-section-full_width',
                'css_scope' => '.ytrip-section',
            ],
            'flatsome' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#content', '.page-wrapper', '.entry-content'],
                'full_width_class' => 'full-width',
                'css_scope' => '.ytrip-section',
            ],
            'avada' => [
                'container_class' => 'fusion-container',
                'content_class' => 'post-content',
                'break_selectors' => ['#content', '.post-content'],
                'full_width_class' => 'fusion-full-width',
                'css_scope' => '.ytrip-section',
            ],
            'enfold' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#main', '.entry-content'],
                'full_width_class' => 'avia-fullwidth',
                'css_scope' => '.ytrip-section',
            ],
            'betheme' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#Content', '.entry-content'],
                'full_width_class' => 'full-width',
                'css_scope' => '.ytrip-section',
            ],
            'salient' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['.post-content', '.entry-content'],
                'full_width_class' => 'full-width-section',
                'css_scope' => '.ytrip-section',
            ],
            'default' => [
                'container_class' => 'container',
                'content_class' => 'entry-content',
                'break_selectors' => ['#primary', '#content', '.entry-content'],
                'full_width_class' => 'alignfull',
                'css_scope' => '.ytrip-section',
            ],
        ];
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        // Output buffer for theme container manipulation
        add_action('ytrip_before_section', [$this, 'maybe_close_theme_containers'], 5);
        add_action('ytrip_after_section', [$this, 'maybe_reopen_theme_containers'], 15);

        // Add body classes
        add_filter('body_class', [$this, 'add_body_classes']);

        // CSS isolation
        add_action('wp_head', [$this, 'output_isolation_styles'], 1);

        // Theme-specific fixes
        add_action('wp_enqueue_scripts', [$this, 'enqueue_theme_fixes'], 999);

        // Section wrapper
        add_filter('ytrip_section_wrapper', [$this, 'get_section_wrapper'], 10, 2);
    }

    /**
     * Get theme configuration.
     *
     * @return array
     */
    public function get_config() {
        return $this->theme_configs[$this->theme_slug] ?? $this->theme_configs['default'];
    }

    /**
     * Close theme containers for full-width section.
     *
     * @param array $args Section arguments.
     * @return void
     */
    public function maybe_close_theme_containers(array $args = []) {
        if (empty($args['full_width']) && empty($args['break_container'])) {
            return;
        }

        $config = $this->get_config();
        $closers = [];

        foreach ($config['break_selectors'] as $selector) {
            // Store what we're closing to reopen later
            $closers[] = $selector;
        }

        $this->container_stack[] = $closers;

        // Output closing tags based on theme
        $this->output_container_closers();
    }

    /**
     * Reopen theme containers after full-width section.
     *
     * @param array $args Section arguments.
     * @return void
     */
    public function maybe_reopen_theme_containers(array $args = []) {
        if (empty($args['full_width']) && empty($args['break_container'])) {
            return;
        }

        $this->output_container_openers();
        array_pop($this->container_stack);
    }

    /**
     * Output container closing tags.
     *
     * @return void
     */
    private function output_container_closers() {
        // Universal closing approach - close all common container patterns
        echo '</div></div></div></div></article></main>';
    }

    /**
     * Output container opening tags.
     *
     * @return void
     */
    private function output_container_openers() {
        // Reopen with theme-specific classes
        $config = $this->get_config();
        $container_class = esc_attr($config['container_class']);

        printf(
            '<main class="%s"><article class="%s"><div class="%s"><div class="%s">',
            esc_attr($config['content_class']),
            'entry',
            $container_class,
            'entry-content'
        );
    }

    /**
     * Render a section with proper wrapper.
     *
     * @param string $section_key Section identifier.
     * @param array $args Section arguments.
     * @return void
     */
    public function render_section(string $section_key, array $args = []) {
        $this->section_counter++;

        $defaults = [
            'full_width' => false,
            'break_container' => false,
            'class' => '',
            'id' => '',
            'style' => '',
            'background' => '',
            'padding' => '',
            'bg_color' => '',
            'bg_image' => '',
            'bg_overlay' => false,
            'parallax' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        // Generate unique section ID
        $section_id = $args['id'] ?: 'ytrip-section-' . $this->section_counter;

        // Build classes
        $classes = [
            'ytrip-section',
            'ytrip-section--' . sanitize_html_class($section_key),
        ];

        if ($args['full_width'] || $args['break_container']) {
            $classes[] = 'ytrip-section--full-width';
            $classes[] = $this->get_config()['full_width_class'];
        }

        if ($args['bg_overlay']) {
            $classes[] = 'ytrip-section--has-overlay';
        }

        if ($args['parallax']) {
            $classes[] = 'ytrip-section--parallax';
        }

        if (!empty($args['class'])) {
            $classes[] = esc_attr($args['class']);
        }

        // Build inline styles
        $styles = $this->build_section_styles($args);

        // Fire action before section
        do_action('ytrip_before_section', array_merge($args, ['section_key' => $section_key]));

        // Close theme containers if needed
        if ($args['full_width'] || $args['break_container']) {
            $this->output_container_closers();
        }

        // Section opening tag
        printf(
            '<section id="%s" class="%s" style="%s" data-section="%s">',
            esc_attr($section_id),
            esc_attr(implode(' ', $classes)),
            esc_attr($styles),
            esc_attr($section_key)
        );

        // Background overlay
        if ($args['bg_overlay']) {
            echo '<div class="ytrip-section__overlay"></div>';
        }

        // Section inner container
        $container_class = ($args['full_width'] || $args['break_container']) 
            ? 'ytrip-container' 
            : 'ytrip-section__content';

        printf('<div class="%s">', esc_attr($container_class));

        // Section content action
        do_action('ytrip_section_content_' . $section_key, $args);

        // Close section
        echo '</div></section>';

        // Reopen theme containers if needed
        if ($args['full_width'] || $args['break_container']) {
            $this->output_container_openers();
        }

        // Fire action after section
        do_action('ytrip_after_section', array_merge($args, ['section_key' => $section_key]));
    }

    /**
     * Build section inline styles.
     *
     * @param array $args Section arguments.
     * @return string
     */
    private function build_section_styles(array $args) {
        $styles = [];

        if (!empty($args['bg_color'])) {
            $styles[] = sprintf('background-color: %s;', esc_attr($args['bg_color']));
        }

        if (!empty($args['bg_image'])) {
            $styles[] = sprintf('background-image: url(%s);', esc_url($args['bg_image']));
            $styles[] = 'background-size: cover;';
            $styles[] = 'background-position: center;';
        }

        if (!empty($args['padding'])) {
            $styles[] = sprintf('padding: %s;', esc_attr($args['padding']));
        }

        if (!empty($args['style'])) {
            $styles[] = esc_attr($args['style']);
        }

        return implode(' ', $styles);
    }

    /**
     * Get section wrapper HTML.
     *
     * @param string $content Section content.
     * @param array $args Section arguments.
     * @return string
     */
    public function get_section_wrapper(string $content, array $args = []) {
        $this->section_counter++;

        $defaults = [
            'section_key' => 'custom',
            'full_width' => false,
            'class' => '',
            'id' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $section_id = $args['id'] ?: 'ytrip-section-' . $this->section_counter;

        $classes = [
            'ytrip-section',
            'ytrip-section--' . sanitize_html_class($args['section_key']),
        ];

        if ($args['full_width']) {
            $classes[] = 'ytrip-section--full-width';
        }

        if (!empty($args['class'])) {
            $classes[] = esc_attr($args['class']);
        }

        return sprintf(
            '<section id="%s" class="%s"><div class="ytrip-container">%s</div></section>',
            esc_attr($section_id),
            esc_attr(implode(' ', $classes)),
            $content
        );
    }

    /**
     * Add body classes for theme compatibility.
     *
     * @param array $classes Existing body classes.
     * @return array
     */
    public function add_body_classes(array $classes) {
        $classes[] = 'ytrip-active';
        $classes[] = 'ytrip-theme-' . $this->theme_slug;

        if ($this->is_ytrip_page()) {
            $classes[] = 'ytrip-page';
        }

        return $classes;
    }

    /**
     * Check if current page is a YTrip page (uses shared helper).
     *
     * @return bool
     */
    private function is_ytrip_page() {
        return function_exists( 'ytrip_is_plugin_page' ) && ytrip_is_plugin_page();
    }

    /**
     * Output CSS isolation styles.
     *
     * @return void
     */
    public function output_isolation_styles() {
        if (!$this->is_ytrip_page()) {
            return;
        }
?>
        <style id="ytrip-theme-compatibility">
            /* Universal Section Styles */
            .ytrip-section {
                position: relative;
                width: 100%;
                clear: both;
                box-sizing: border-box;
            }

            .ytrip-section::before,
            .ytrip-section::after {
                content: "";
                display: table;
                clear: both;
            }

            /* Full-Width Breaking */
            .ytrip-section--full-width {
                width: 100vw !important;
                max-width: none !important;
                margin-left: calc(50% - 50vw) !important;
                margin-right: calc(50% - 50vw) !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                left: auto !important;
                right: auto !important;
            }

            /* RTL Support */
            [dir="rtl"] .ytrip-section--full-width {
                margin-left: calc(50% - 50vw) !important;
                margin-right: calc(50% - 50vw) !important;
            }

            /* Container */
            .ytrip-container {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 1.5rem;
                box-sizing: border-box;
            }

            .ytrip-section--full-width .ytrip-container {
                max-width: 1280px;
            }

            /* Background Overlay */
            .ytrip-section--has-overlay {
                position: relative;
            }

            .ytrip-section__overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1;
            }

            .ytrip-section--has-overlay > *:not(.ytrip-section__overlay) {
                position: relative;
                z-index: 2;
            }

            /* CSS Isolation - Prevent theme style bleeding */
            .ytrip-section,
            .ytrip-section * {
                box-sizing: border-box;
            }

            .ytrip-section p {
                margin: 0 0 1rem 0;
            }

            .ytrip-section img {
                max-width: 100%;
                height: auto;
            }

            /* Theme-specific overrides */
            <?php echo $this->get_theme_specific_css(); ?>
        </style>
<?php
    }

    /**
     * Get theme-specific CSS fixes.
     *
     * @return string
     */
    private function get_theme_specific_css() {
        $css = '';

        switch ($this->theme_slug) {
            case 'astra':
                $css .= '.ytrip-section { padding: 0; }';
                $css .= '.ast-plain-container .ytrip-section { margin: 0; }';
                break;

            case 'divi':
                $css .= '.ytrip-section { padding: 0; }';
                $css .= '.et_pb_section .ytrip-section { padding: 0; }';
                break;

            case 'elementor-hello':
            case 'hello-elementor':
                $css .= '.ytrip-section { margin: 0; }';
                break;

            case 'flatsome':
                $css .= '.ytrip-section { padding: 0; }';
                $css .= '#content .ytrip-section { margin: 0; }';
                break;

            case 'avada':
                $css .= '.ytrip-section { padding: 0; }';
                $css .= '.fusion-builder-row .ytrip-section { margin: 0; }';
                break;

            default:
                break;
        }

        return $css;
    }

    /**
     * Enqueue theme-specific CSS fixes.
     *
     * @return void
     */
    public function enqueue_theme_fixes() {
        if (!$this->is_ytrip_page()) {
            return;
        }

        $css_file = YTRIP_PATH . 'assets/css/themes/' . $this->theme_slug . '.css';

        if (file_exists($css_file)) {
            wp_enqueue_style(
                'ytrip-theme-' . $this->theme_slug,
                YTRIP_URL . 'assets/css/themes/' . $this->theme_slug . '.css',
                array( 'ytrip-main' ),
                YTRIP_VERSION
            );
        }
    }

    /**
     * Get detected theme slug.
     *
     * @return string
     */
    public function get_theme_slug_public() {
        return $this->theme_slug;
    }

    /**
     * Get all supported themes.
     *
     * @return array
     */
    public function get_supported_themes() {
        return array_keys($this->theme_configs);
    }

    /**
     * Check if current theme is supported.
     *
     * @return bool
     */
    public function is_theme_supported() {
        return isset($this->theme_configs[$this->theme_slug]);
    }
}

/**
 * Helper function to render a section.
 *
 * @param string $section_key Section identifier.
 * @param array $args Section arguments.
 * @return void
 */
function ytrip_render_compat_section(string $section_key, array $args = []) {
    YTrip_Theme_Compatibility::instance()->render_section($section_key, $args);
}

/**
 * Helper function to get section wrapper.
 *
 * @param string $content Section content.
 * @param array $args Section arguments.
 * @return string
 */
function ytrip_get_section_wrapper(string $content, array $args = []) {
    return YTrip_Theme_Compatibility::instance()->get_section_wrapper($content, $args);
}

// Initialize.
YTrip_Theme_Compatibility::instance();
