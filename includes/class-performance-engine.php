<?php
/**
 * YTrip Performance Optimizer
 * 
 * Comprehensive performance optimization including:
 * - Query caching and optimization
 * - N+1 query prevention
 * - Database indexing
 * - Object cache integration
 * - Critical CSS generation
 * - Asset preloading
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Performance_Optimizer
 */
class YTrip_Performance_Engine {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Cache group name.
     *
     * @var string
     */
    private string $cache_group = 'ytrip';

    /**
     * Default cache TTL.
     *
     * @var int
     */
    private int $cache_ttl = 3600;

    /**
     * Query statistics.
     *
     * @var array
     */
    private array $query_stats = [
        'queries' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * Enabled features.
     *
     * @var array
     */
    private array $features = [];

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->features = $this->get_enabled_features();
        $this->init_hooks();
    }

    /**
     * Get enabled features.
     *
     * @return array
     */
    private function get_enabled_features() {
        $settings = get_option('ytrip_settings', []);

        $lazy = isset( $settings['lazy_load_images'] ) ? ( $settings['lazy_load_images'] === 'yes' || $settings['lazy_load_images'] === true ) : true;
        if ( isset( $settings['enable_lazy_load'] ) ) {
            $lazy = (bool) $settings['enable_lazy_load'];
        }
        $critical = ! empty( $settings['enable_critical_css'] );
        if ( isset( $settings['critical_css'] ) ) {
            $critical = (bool) $settings['critical_css'];
        }
        return [
            'query_cache' => ! empty( $settings['enable_query_cache'] ),
            'object_cache' => ! empty( $settings['enable_object_cache'] ),
            'lazy_load' => $lazy,
            'webp' => isset( $settings['enable_webp'] ) ? ( $settings['enable_webp'] === 'yes' || (bool) $settings['enable_webp'] ) : true,
            'critical_css' => $critical,
            'db_indexes' => ! empty( $settings['enable_db_indexes'] ),
            'query_optimization' => true,
        ];
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        // Query optimization
        add_filter('posts_pre_query', [$this, 'maybe_cache_posts_query'], 10, 2);
        add_action('save_post_ytrip_tour', [$this, 'clear_tour_cache']);
        add_action('delete_post', [$this, 'clear_tour_cache']);

        // Database indexes
        add_action('init', [$this, 'maybe_create_indexes'], 5);

        // Asset optimization: layout-critical CSS first so elements reserve space from first paint (LCP/CLS).
        add_action('wp_head', [$this, 'output_preload_hints'], 1);
        add_action('wp_head', [$this, 'output_layout_critical_css'], 2);
        add_action('wp_head', [$this, 'output_critical_css'], 3);
        add_filter('style_loader_tag', [$this, 'optimize_style_loading'], 10, 4);
        add_filter('script_loader_tag', [$this, 'optimize_script_loading'], 10, 3);

        // Image optimization
        add_filter('wp_get_attachment_image_attributes', [$this, 'optimize_image_attributes'], 10, 2);
        add_filter('the_content', [$this, 'lazy_load_content_images'], 999);

        // Object cache integration
        add_action('wp_ajax_ytrip_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_nopriv_ytrip_clear_cache', [$this, 'ajax_clear_cache']);

        // Admin bar
        add_action('admin_bar_menu', [$this, 'add_cache_clear_button'], 999);

        // Query monitoring (development only)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('shutdown', [$this, 'log_query_stats']);
        }
    }

    // =========================================================================
    // Query Caching
    // =========================================================================

    /**
     * Maybe cache posts query.
     *
     * @param array|null $posts Posts array.
     * @param WP_Query $query Query object.
     * @return array|null
     */
    public function maybe_cache_posts_query($posts, $query): ?array {
        if (!$this->features['query_cache']) {
            return null;
        }

        // Only cache YTrip queries
        if (!$this->is_ytrip_query($query)) {
            return null;
        }

        $cache_key = $this->generate_query_cache_key($query);
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            $this->query_stats['cache_hits']++;
            return $cached;
        }

        $this->query_stats['cache_misses']++;
        return null;
    }

    /**
     * Check if query is YTrip related.
     *
     * @param WP_Query $query Query object.
     * @return bool
     */
    private function is_ytrip_query($query) {
        $post_type = $query->get('post_type');

        if (is_array($post_type) && in_array('ytrip_tour', $post_type, true)) {
            return true;
        }

        if ($post_type === 'ytrip_tour') {
            return true;
        }

        $tax_query = $query->get('tax_query');
        if (is_array($tax_query)) {
            foreach ($tax_query as $tax) {
                if (isset($tax['taxonomy']) && strpos($tax['taxonomy'], 'ytrip_') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate cache key for query.
     *
     * @param WP_Query $query Query object.
     * @return string
     */
    private function generate_query_cache_key($query) {
        $vars = $query->query_vars;
        ksort($vars);
        return 'query_' . md5(serialize($vars));
    }

    /**
     * Get tours with optimized query (prevents N+1).
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_tours_optimized(array $args = []) {
        $defaults = [
            'post_type' => 'ytrip_tour',
            'posts_per_page' => 12,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'no_found_rows' => false,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ];

        $args = wp_parse_args($args, $defaults);

        $cache_key = 'tours_' . md5(serialize($args));
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        // Get posts
        $query = new WP_Query($args);
        $posts = $query->posts;

        if (empty($posts)) {
            return [
                'posts' => [],
                'found' => 0,
                'max_pages' => 0,
            ];
        }

        // Prime caches to prevent N+1 queries
        $this->prime_post_caches($posts);

        // Prepare tour data
        $tours = [];
        foreach ($posts as $post) {
            $tours[] = $this->prepare_tour_data($post);
        }

        $result = [
            'posts' => $tours,
            'found' => (int) $query->found_posts,
            'max_pages' => (int) $query->max_num_pages,
        ];

        $this->cache_set($cache_key, $result, HOUR_IN_SECONDS);

        return $result;
    }

    /**
     * Prime post caches to prevent N+1 queries.
     *
     * @param array $posts Posts array.
     * @return void
     */
    private function prime_post_caches(array $posts) {
        $post_ids = wp_list_pluck($posts, 'ID');

        // Prime meta cache
        update_meta_cache('post', $post_ids);

        // Prime term cache
        $taxonomies = ['ytrip_destination', 'ytrip_category', 'ytrip_tag'];
        foreach ($taxonomies as $taxonomy) {
            update_object_term_cache($post_ids, $taxonomy);
        }

        // Prime WooCommerce product cache if available
        if (function_exists('wc_get_product')) {
            foreach ($post_ids as $post_id) {
                $product_id = get_post_meta($post_id, '_ytrip_wc_product_id', true);
                if ($product_id) {
                    wc_get_product($product_id);
                }
            }
        }
    }

    /**
     * Prepare tour data for output.
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function prepare_tour_data($post) {
        $tour_id = $post->ID;

        return [
            'id' => $tour_id,
            'title' => get_the_title($tour_id),
            'excerpt' => get_the_excerpt($tour_id),
            'permalink' => get_permalink($tour_id),
            'thumbnail' => get_the_post_thumbnail_url($tour_id, 'ytrip-card'),
            'thumbnail_id' => get_post_thumbnail_id($tour_id),
            'meta' => get_post_meta($tour_id, 'ytrip_tour_details', true) ?: [],
            'destinations' => wp_get_post_terms($tour_id, 'ytrip_destination', ['fields' => 'names']),
            'categories' => wp_get_post_terms($tour_id, 'ytrip_category', ['fields' => 'names']),
            'average_rating' => $this->get_tour_rating($tour_id),
            'review_count' => $this->get_tour_review_count($tour_id),
            'price' => $this->get_tour_price($tour_id),
        ];
    }

    /**
     * Get tour rating.
     *
     * @param int $tour_id Tour ID.
     * @return float
     */
    private function get_tour_rating(int $tour_id) {
        $cache_key = 'rating_' . $tour_id;
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            return (float) $cached;
        }

        $rating = 0;

        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $rating = (float) $product->get_average_rating();
                }
            }
        }

        $this->cache_set($cache_key, $rating, HOUR_IN_SECONDS);

        return $rating;
    }

    /**
     * Get tour review count.
     *
     * @param int $tour_id Tour ID.
     * @return int
     */
    private function get_tour_review_count(int $tour_id) {
        $cache_key = 'review_count_' . $tour_id;
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            return (int) $cached;
        }

        $count = 0;

        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $count = (int) $product->get_review_count();
                }
            }
        }

        $this->cache_set($cache_key, $count, HOUR_IN_SECONDS);

        return $count;
    }

    /**
     * Get tour price.
     *
     * @param int $tour_id Tour ID.
     * @return array
     */
    private function get_tour_price(int $tour_id) {
        $cache_key = 'price_' . $tour_id;
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $price = [
            'regular' => 0,
            'sale' => null,
            'formatted' => '',
        ];

        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $price['regular'] = (float) $product->get_regular_price();
                    $price['sale'] = $product->is_on_sale() ? (float) $product->get_sale_price() : null;
                    $price['formatted'] = $product->get_price_html();
                }
            }
        }

        $this->cache_set($cache_key, $price, HOUR_IN_SECONDS);

        return $price;
    }

    /**
     * Clear tour cache.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public function clear_tour_cache(int $post_id) {
        if (get_post_type($post_id) !== 'ytrip_tour') {
            return;
        }

        // Clear specific caches
        $this->cache_delete('rating_' . $post_id);
        $this->cache_delete('review_count_' . $post_id);
        $this->cache_delete('price_' . $post_id);

        // Clear list caches
        $this->flush_cache_group();
    }

    // =========================================================================
    // Database Optimization
    // =========================================================================

    /**
     * Maybe create database indexes.
     *
     * @return void
     */
    public function maybe_create_indexes() {
        if (!$this->features['db_indexes']) {
            return;
        }

        $installed_version = get_option('ytrip_db_version', '0');

        if (version_compare($installed_version, YTRIP_VERSION, '>=')) {
            return;
        }

        $this->create_database_indexes();

        update_option('ytrip_db_version', YTRIP_VERSION);
    }

    /**
     * Create database indexes.
     *
     * @return void
     */
    private function create_database_indexes(): void {
        global $wpdb;

        // Helper to check if index exists
        $index_exists = function($table, $index) use ($wpdb) {
            $check = $wpdb->get_results($wpdb->prepare("SHOW INDEX FROM $table WHERE Key_name = %s", $index));
            return !empty($check);
        };

        // Post meta indexes
        if (!$index_exists($wpdb->postmeta, 'ytrip_meta_key_idx')) {
            $wpdb->query("CREATE INDEX ytrip_meta_key_idx ON {$wpdb->postmeta} (meta_key(50), post_id)");
        }
        if (!$index_exists($wpdb->postmeta, 'ytrip_meta_value_idx')) {
            $wpdb->query("CREATE INDEX ytrip_meta_value_idx ON {$wpdb->postmeta} (meta_key(50), meta_value(50))");
        }

        // Term taxonomy indexes
        if (!$index_exists($wpdb->term_taxonomy, 'ytrip_term_taxonomy_idx')) {
            $wpdb->query("CREATE INDEX ytrip_term_taxonomy_idx ON {$wpdb->term_taxonomy} (taxonomy(50), count)");
        }

        // Term relationships indexes
        if (!$index_exists($wpdb->term_relationships, 'ytrip_term_rel_idx')) {
            $wpdb->query("CREATE INDEX ytrip_term_rel_idx ON {$wpdb->term_relationships} (term_taxonomy_id, object_id)");
        }

        // Custom tables
        $custom_tables = [
            "{$wpdb->prefix}ytrip_bookings" => ['tour_id', 'user_id', 'status', 'created_at'],
            "{$wpdb->prefix}ytrip_reviews" => ['tour_id', 'user_id', 'rating', 'created_at'],
        ];

        foreach ($custom_tables as $table => $columns) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
                foreach ($columns as $column) {
                    $index_name = "ytrip_{$column}_idx";
                    if (!$index_exists($table, $index_name)) {
                        $wpdb->query("CREATE INDEX {$index_name} ON {$table} ({$column})");
                    }
                }
            }
        }
    }

    // =========================================================================
    // Asset Optimization
    // =========================================================================

    /**
     * Output preload hints.
     *
     * @return void
     */
    public function output_preload_hints() {
        if ( ! $this->is_ytrip_page() ) {
            return;
        }

        // LCP request discovery: preload single-tour hero/featured image when it is the LCP element.
        if ( is_singular( 'ytrip_tour' ) ) {
            $tour_id = get_queried_object_id();
            $meta   = get_post_meta( $tour_id, 'ytrip_tour_details', true );
            $meta   = is_array( $meta ) ? $meta : array();
            $gallery = isset( $meta['tour_gallery'] ) ? array_filter( array_map( 'absint', explode( ',', $meta['tour_gallery'] ) ) ) : array();
            if ( empty( $gallery ) && has_post_thumbnail( $tour_id ) ) {
                $gallery = array( get_post_thumbnail_id( $tour_id ) );
            }
            $hero_id = ! empty( $gallery[0] ) ? (int) $gallery[0] : get_post_thumbnail_id( $tour_id );
            if ( $hero_id ) {
                $src = wp_get_attachment_image_url( $hero_id, 'large' );
                if ( $src ) {
                    printf( '<link rel="preload" href="%s" as="image">', esc_url( $src ) );
                }
            }
        }

        // LCP: preload homepage hero first slide image when hero is enabled.
        if ( is_front_page() ) {
            $homepage_opts = get_option( 'ytrip_homepage', array() );
            if ( ! empty( $homepage_opts['hero_enable'] ) && ! empty( $homepage_opts['hero_slides'] ) && is_array( $homepage_opts['hero_slides'] ) ) {
                $first = reset( $homepage_opts['hero_slides'] );
                $img_id = isset( $first['image'] ) && is_numeric( $first['image'] ) ? (int) $first['image'] : 0;
                if ( $img_id ) {
                    $src = wp_get_attachment_image_url( $img_id, 'large' );
                    if ( $src ) {
                        printf( '<link rel="preload" href="%s" as="image">', esc_url( $src ) );
                    }
                }
            }
        }

        // Font preload disabled — theme fonts are used, not plugin fonts.
    }

    /**
     * Output layout-critical CSS on every YTrip page so layout is reserved from first paint (no option).
     * Reduces CLS and helps LCP request discovery by defining hero/card/container dimensions early.
     *
     * @return void
     */
    public function output_layout_critical_css() {
        if ( ! $this->is_ytrip_page() ) {
            return;
        }
        $css = $this->get_layout_critical_css();
        if ( ! empty( $css ) ) {
            printf( '<style id="ytrip-layout-critical">%s</style>', wp_strip_all_tags( $css ) );
        }
    }

    /**
     * Get layout-only critical CSS: aspect-ratios, min-heights, and skeleton so elements load in place.
     *
     * @return string
     */
    private function get_layout_critical_css() {
        $cache_key = 'ytrip_layout_critical_v8';
        $cached    = $this->cache_get( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }
        $css = $this->generate_layout_critical_css();
        $this->cache_set( $cache_key, $css, DAY_IN_SECONDS );
        return $css;
    }

    /**
     * Generate layout-critical CSS (reserve space for hero, cards, single hero, skeleton).
     *
     * @return string
     */
    private function generate_layout_critical_css() {
        // Homepage hero: 100vh with !important so no deferred/theme CSS can change height (fixes CLS 0.68+ on desktop).
        // Single-tour hero: reserve min-height so LCP area is reserved from first paint (avoids white gap above content).
        // .ytrip-hero__bg--ratio: same as .ytrip-hero__bg so no aspect-ratio override causes shift when full CSS loads.
        // Tap targets 48px and focus-visible for Lighthouse Accessibility.
        $base = ':root{--ytrip-primary:#2563eb;--ytrip-primary-contrast:#fff}.ytrip-container{max-width:1280px;margin:0 auto;padding:0 1.5rem}.ytrip-hero{position:relative;min-height:100vh!important;height:100vh!important;display:flex;align-items:center;justify-content:center;overflow:hidden}.ytrip-hero-slider,.ytrip-hero-slider .swiper-wrapper,.ytrip-hero-slider .swiper-slide,.ytrip-hero__slide{min-height:100vh!important;height:100vh!important}.ytrip-hero__bg,.ytrip-hero__bg--ratio{position:absolute;inset:0;z-index:1}.ytrip-hero__bg img,.ytrip-hero__bg--ratio img{width:100%;height:100%;object-fit:cover}.ytrip-single-hero{min-height:50vh!important;position:relative;display:flex;align-items:center;overflow:hidden}.ytrip-single-hero .ytrip-hero-bg,.ytrip-single-hero .swiper-slide{min-height:50vh!important}.ytrip-single-hero .ytrip-hero__content{position:relative;z-index:2}.ytrip-tour-card__image{position:relative;aspect-ratio:4/3;overflow:hidden;background:#f1f5f9}.ytrip-tour-card__image-link,.ytrip-tour-card__image img{width:100%;height:100%;object-fit:cover;display:block}.ytrip-img-wrap{position:relative;overflow:hidden;aspect-ratio:16/9;background:#f1f5f9}.ytrip-img-wrap--hero{aspect-ratio:21/9;min-height:280px}.ytrip-img-wrap img{width:100%;height:100%;object-fit:cover}.ytrip-img-skeleton{position:absolute;inset:0;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:ytrip-skeleton 1.5s ease-in-out infinite}@keyframes ytrip-skeleton{to{background-position:-200% 0}}.ytrip-hero__grid-item{aspect-ratio:4/3;overflow:hidden}.ytrip-hero__grid-item img{width:100%;height:100%;object-fit:cover}';
        $section = '.ytrip-section{width:100%;clear:both;padding:4rem 0;box-sizing:border-box}.ytrip-section img{max-width:100%;height:auto;display:block}';
        $a11y = '.ytrip-btn,.ytrip-hero__cta .ytrip-btn,.swiper-button-next,.swiper-button-prev{min-height:48px;min-width:48px;box-sizing:border-box}.ytrip-btn:focus-visible,.swiper-button-next:focus-visible,.swiper-button-prev:focus-visible{outline:2px solid currentColor;outline-offset:2px}';
        return $base . $section . $a11y;
    }

    /**
     * Output critical CSS (optional fuller above-the-fold styles when setting enabled).
     *
     * @return void
     */
    public function output_critical_css() {
        if ( ! $this->features['critical_css'] || ! $this->is_ytrip_page() ) {
            return;
        }

        $critical_css = $this->get_critical_css();

        if ( ! empty( $critical_css ) ) {
            printf(
                '<style id="ytrip-critical-css">%s</style>',
                wp_strip_all_tags( $critical_css )
            );
        }
    }

    /**
     * Get critical CSS for current page.
     *
     * @return string
     */
    private function get_critical_css() {
        $cache_key = 'critical_css_' . $this->get_page_type();
        $cached = $this->cache_get($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $critical_file = YTRIP_PATH . 'assets/dist/critical-' . $this->get_page_type() . '.min.css';

        if (file_exists($critical_file)) {
            $css = file_get_contents($critical_file);
        } else {
            $css = $this->generate_minimal_critical_css();
        }

        $this->cache_set($cache_key, $css, DAY_IN_SECONDS);

        return $css;
    }

    /**
     * Generate minimal critical CSS.
     *
     * @return string
     */
    private function generate_minimal_critical_css() {
        return ':root{--ytrip-primary:#2563eb;--ytrip-secondary:#7c3aed;--ytrip-accent:#f59e0b}.ytrip-container{max-width:1280px;margin:0 auto;padding:0 1.5rem}.ytrip-section{width:100%;clear:both;padding:4rem 0;box-sizing:border-box}.ytrip-section img{max-width:100%;height:auto;display:block}.ytrip-tour-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)}.ytrip-tour-card__image{aspect-ratio:4/3;background:#f1f5f9;overflow:hidden}.ytrip-tour-card__image img{width:100%;height:100%;object-fit:cover}.ytrip-btn{display:inline-flex;align-items:center;padding:0.75rem 1.5rem;font-weight:600;border-radius:50px;text-decoration:none}.ytrip-btn-primary{background:linear-gradient(135deg,var(--ytrip-primary),var(--ytrip-secondary));color:#fff}.ytrip-skeleton{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skeleton 1.5s infinite}@keyframes skeleton{0%{background-position:200% 0}100%{background-position:-200% 0}}';
    }

    /**
     * Get current page type.
     *
     * @return string
     */
    private function get_page_type() {
        if (is_singular('ytrip_tour')) {
            return 'single';
        }

        if (is_post_type_archive('ytrip_tour') || is_tax('ytrip_destination') || is_tax('ytrip_category')) {
            return 'archive';
        }

        if (is_front_page()) {
            return 'home';
        }

        return 'default';
    }

    /**
     * Optimize style loading.
     *
     * @param string $html Link tag HTML.
     * @param string $handle Style handle.
     * @param string $href Stylesheet URL.
     * @param string $media Media type.
     * @return string
     */
    public function optimize_style_loading(string $html, string $handle, string $href, string $media) {
        // Never defer critical styles (main layout, core bundle, archive-bundle, single-tour, homepage for LCP). Plugin does not load fonts.
        $critical_handles = array( 'ytrip-critical', 'ytrip-core', 'ytrip-main', 'ytrip-archive-bundle', 'ytrip-single-tour', 'ytrip-homepage', 'ytrip-hero-slider', 'swiper-bundle' );
        if ( in_array( $handle, $critical_handles, true ) ) {
            return $html;
        }

        // Only defer non-critical YTrip styles (archive-filters, card-styles, fonts-extra, etc.).
        if ( strpos( $handle, 'ytrip-' ) !== 0 ) {
            return $html;
        }

        // Async loading with media trick (non-render-blocking)
        $html = str_replace(
            "media='all'",
            "media='print' onload=\"this.media='all'\"",
            $html
        );
        $html = str_replace(
            'media="all"',
            'media="print" onload="this.media=\'all\'"',
            $html
        );

        // Add noscript fallback
        $noscript = sprintf(
            '<noscript><link rel="stylesheet" href="%s" media="all"></noscript>',
            esc_url($href)
        );

        return $html . $noscript;
    }

    /**
     * Optimize script loading.
     *
     * @param string $tag Script tag.
     * @param string $handle Script handle.
     * @param string $src Script URL.
     * @return string
     */
    public function optimize_script_loading(string $tag, string $handle, string $src) {
        // Only optimize YTrip scripts
        if (strpos($handle, 'ytrip-') !== 0) {
            return $tag;
        }

        // On archive: do not defer main/archive-filters so they load and are not canceled (fixes LCP and card display).
        $is_archive = is_post_type_archive( 'ytrip_tour' ) || is_tax( 'ytrip_destination' ) || is_tax( 'ytrip_category' );
        if ( $is_archive && in_array( $handle, array( 'ytrip-main', 'ytrip-archive-filters' ), true ) ) {
            return $tag;
        }
        // On single tour: do not defer main/single-tour so hero and layout paint for LCP.
        if ( is_singular( 'ytrip_tour' ) && in_array( $handle, array( 'ytrip-main', 'ytrip-single-tour' ), true ) ) {
            return $tag;
        }
        // On homepage: do not defer main/hero-slider so hero paints for LCP.
        if ( is_front_page() && in_array( $handle, array( 'ytrip-main', 'ytrip-hero-slider' ), true ) ) {
            return $tag;
        }

        // Defer other YTrip scripts so parsing isn't blocked (run in order when deferred).
        $defer_handles = ['ytrip-main', 'ytrip-archive-filters', 'ytrip-frontend', 'ytrip-single-tour', 'ytrip-single', 'ytrip-archive', 'ytrip-effects', 'ytrip-hero-slider', 'ytrip-homepage', 'ytrip-reviews'];
        if (in_array($handle, $defer_handles, true)) {
            if (strpos($tag, ' defer') === false && strpos($tag, ' async') === false) {
                return str_replace(' src', ' defer src', $tag);
            }
        }

        // Async for analytics
        if ($handle === 'ytrip-analytics') {
            return str_replace(' src', ' async src', $tag);
        }

        return $tag;
    }

    // =========================================================================
    // Image Optimization
    // =========================================================================

    /**
     * Optimize image attributes.
     *
     * @param array $attr Image attributes.
     * @param WP_Post $attachment Attachment object.
     * @return array
     */
    public function optimize_image_attributes(array $attr, $attachment) {
        if ($this->features['lazy_load']) {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }

        return $attr;
    }

    /**
     * Lazy load content images.
     *
     * @param string $content Content.
     * @return string
     */
    public function lazy_load_content_images(string $content) {
        if (!$this->features['lazy_load']) {
            return $content;
        }

        // Add loading="lazy" to images without it
        $content = preg_replace(
            '/<img([^>]+)(?<!loading=["\']lazy["\'])([^>]*?)>/i',
            '<img$1 loading="lazy"$2>',
            $content
        );

        // Add decoding="async"
        $content = preg_replace(
            '/<img([^>]+)(?<!decoding=["\']async["\'])([^>]*?)>/i',
            '<img$1 decoding="async"$2>',
            $content
        );

        return $content;
    }

    // =========================================================================
    // Cache Operations
    // =========================================================================

    /**
     * Get from cache.
     *
     * @param string $key Cache key.
     * @return mixed
     */
    public function cache_get(string $key) {
        if ($this->features['object_cache'] && wp_using_ext_object_cache()) {
            return wp_cache_get($key, $this->cache_group);
        }

        return get_transient($this->cache_group . '_' . $key);
    }

    /**
     * Set to cache.
     *
     * @param string $key Cache key.
     * @param mixed $value Value.
     * @param int $ttl TTL in seconds.
     * @return bool
     */
    public function cache_set(string $key, $value, int $ttl = 0) {
        $ttl = $ttl ?: $this->cache_ttl;

        if ($this->features['object_cache'] && wp_using_ext_object_cache()) {
            return wp_cache_set($key, $value, $this->cache_group, $ttl);
        }

        return set_transient($this->cache_group . '_' . $key, $value, $ttl);
    }

    /**
     * Delete from cache.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function cache_delete(string $key) {
        if ($this->features['object_cache'] && wp_using_ext_object_cache()) {
            return wp_cache_delete($key, $this->cache_group);
        }

        return delete_transient($this->cache_group . '_' . $key);
    }

    /**
     * Flush cache group.
     *
     * @return void
     */
    public function flush_cache_group() {
        global $wpdb;

        // For transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_group . '_%',
                '_transient_timeout_' . $this->cache_group . '_%'
            )
        );

        // For object cache
        if (wp_using_ext_object_cache()) {
            wp_cache_flush_group($this->cache_group);
        }
    }

    /**
     * AJAX clear cache.
     *
     * @return void
     */
    public function ajax_clear_cache() {
        check_ajax_referer('ytrip_frontend_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ytrip')]);
        }

        $this->flush_cache_group();

        // Clear object cache
        wp_cache_flush();

        wp_send_json_success([
            'message' => __('Cache cleared successfully', 'ytrip'),
        ]);
    }

    /**
     * Add cache clear button to admin bar.
     *
     * @param WP_Admin_Bar $admin_bar Admin bar object.
     * @return void
     */
    public function add_cache_clear_button($admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!$this->is_ytrip_page() && !is_admin()) {
            return;
        }

        $admin_bar->add_node([
            'id' => 'ytrip-clear-cache',
            'title' => '<span class="ab-icon dashicons dashicons-update"></span> ' . __('Clear YTrip Cache', 'ytrip'),
            'href' => '#',
            'meta' => [
                'class' => 'ytrip-cache-clear-btn',
            ],
        ]);
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Check if current page is YTrip page.
     *
     * @return bool
     */
    private function is_ytrip_page() {
        if (is_singular('ytrip_tour')) {
            return true;
        }

        if (is_post_type_archive('ytrip_tour') || is_tax('ytrip_destination') || is_tax('ytrip_category')) {
            return true;
        }

        if (is_front_page()) {
            return true;
        }

        global $post;
        if ($post && (
            has_shortcode($post->post_content, 'ytrip_homepage') ||
            has_shortcode($post->post_content, 'ytrip_tours')
        )) {
            return true;
        }

        return false;
    }

    /**
     * Log query stats (development only).
     *
     * @return void
     */
    public function log_query_stats() {
        if (!$this->is_ytrip_page()) {
            return;
        }

        $stats = [
            'cache_hits' => $this->query_stats['cache_hits'],
            'cache_misses' => $this->query_stats['cache_misses'],
            'hit_rate' => $this->query_stats['cache_hits'] > 0
                ? round(($this->query_stats['cache_hits'] / max($this->query_stats['cache_hits'] + $this->query_stats['cache_misses'], 1)) * 100, 2)
                : 0,
        ];

        if ( defined( 'YTRIP_DEBUG' ) && YTRIP_DEBUG ) {
            error_log( 'YTrip Query Stats: ' . print_r( $stats, true ) );
        }
    }

    /**
     * Get query statistics.
     *
     * @return array
     */
    public function get_query_stats() {
        return $this->query_stats;
    }
}

/**
 * Helper function to get optimized tours.
 *
 * @param array $args Query arguments.
 * @return array
 */
function ytrip_get_tours_optimized(array $args = []) {
    return YTrip_Performance_Engine::instance()->get_tours_optimized($args);
}

/**
 * Helper function to get from cache.
 *
 * @param string $key Cache key.
 * @return mixed
 */
function ytrip_cache_get(string $key) {
    return YTrip_Performance_Engine::instance()->cache_get($key);
}

/**
 * Helper function to set to cache.
 *
 * @param string $key Cache key.
 * @param mixed $value Value.
 * @param int $ttl TTL.
 * @return bool
 */
function ytrip_cache_set(string $key, $value, int $ttl = 0) {
    return YTrip_Performance_Engine::instance()->cache_set($key, $value, $ttl);
}

// Initialize.
YTrip_Performance_Engine::instance();
