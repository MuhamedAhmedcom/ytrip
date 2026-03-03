<?php
/**
 * YTrip Related Tours
 * 
 * Handles related tours by category, destination, and manual selection.
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Related_Tours
 */
class YTrip_Related_Tours {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Settings.
     *
     * @var array
     */
    private $settings = [];

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
        $this->settings = get_option('ytrip_settings', []);
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        // Add related tours section to single tour template
        add_action('ytrip_after_single_tour', [$this, 'render_related_section'], 20);
    }

    /**
     * Get related tours.
     *
     * @param int   $tour_id Current tour ID.
     * @param array $args    Query arguments.
     * @return array
     */
    public function get_related(int $tour_id, array $args = []) {
        $defaults = [
            'count' => (int) ( $this->settings['single_related_count'] ?? $this->settings['related_count'] ?? 4 ),
            'by_category' => (bool) ($this->settings['related_by_category'] ?? true),
            'by_destination' => (bool) ($this->settings['related_by_destination'] ?? true),
            'priority' => $this->settings['related_priority'] ?? 'destination',
            'fallback' => $this->settings['related_fallback'] ?? 'featured',
        ];

        $args = wp_parse_args($args, $defaults);

        // Check if manual override is set
        $tour_meta = get_post_meta($tour_id, 'ytrip_tour_details', true);
        if (!empty($tour_meta['related_override'])) {
            return $this->get_manual_related($tour_id, $args);
        }

        // Get related by priority
        $related = [];

        if ($args['priority'] === 'destination' && $args['by_destination']) {
            $related = $this->get_related_by_destination($tour_id, $args);
            if (count($related) < $args['count'] && $args['by_category']) {
                $related = array_merge($related, $this->get_related_by_category($tour_id, $args));
                $related = array_unique($related, SORT_REGULAR);
            }
        } elseif ($args['priority'] === 'category' && $args['by_category']) {
            $related = $this->get_related_by_category($tour_id, $args);
            if (count($related) < $args['count'] && $args['by_destination']) {
                $related = array_merge($related, $this->get_related_by_destination($tour_id, $args));
                $related = array_unique($related, SORT_REGULAR);
            }
        } else {
            // Random mix
            $dest_related = $args['by_destination'] ? $this->get_related_by_destination($tour_id, $args) : [];
            $cat_related = $args['by_category'] ? $this->get_related_by_category($tour_id, $args) : [];
            $related = array_merge($dest_related, $cat_related);
            $related = array_unique($related, SORT_REGULAR);
            shuffle($related);
        }

        // Limit to count
        $related = array_slice($related, 0, $args['count']);

        // Fallback if not enough
        if (count($related) < $args['count']) {
            $related = $this->get_fallback_related($tour_id, $args, $related);
        }

        return $related;
    }

    /**
     * Get manually selected related tours.
     *
     * @param int   $tour_id Tour ID.
     * @param array $args    Arguments.
     * @return array
     */
    private function get_manual_related(int $tour_id, array $args) {
        $tour_meta = get_post_meta($tour_id, 'ytrip_tour_details', true);
        $related_ids = $tour_meta['related_tour_ids'] ?? $tour_meta['related_tours'] ?? '';

        if (empty($related_ids)) {
            return [];
        }

        if (is_string($related_ids)) {
            $related_ids = explode(',', $related_ids);
        }

        $related_ids = array_map('intval', (array) $related_ids);
        $related_ids = array_filter($related_ids);
        $related_ids = array_diff($related_ids, [$tour_id]); // Exclude current tour

        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';

        $query = new WP_Query([
            'post_type' => $tour_slug,
            'post__in' => $related_ids,
            'posts_per_page' => $args['count'],
            'post_status' => 'publish',
            'orderby' => 'post__in',
        ]);

        return $query->posts;
    }

    /**
     * Get related tours by destination.
     *
     * @param int   $tour_id Tour ID.
     * @param array $args    Arguments.
     * @return array
     */
    private function get_related_by_destination(int $tour_id, array $args) {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';

        // Get tour destinations
        $destinations = get_the_terms($tour_id, $destination_slug);

        if (!$destinations || is_wp_error($destinations)) {
            return [];
        }

        $destination_ids = wp_list_pluck($destinations, 'term_id');

        $query_args = [
            'post_type' => $tour_slug,
            'posts_per_page' => $args['count'],
            'post__not_in' => [$tour_id],
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => $destination_slug,
                    'field' => 'term_id',
                    'terms' => $destination_ids,
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Prioritize featured
        $meta_query = [
            'relation' => 'OR',
            [
                'key' => 'ytrip_tour_details',
                'value' => '"featured";s:4:"true"',
                'compare' => 'LIKE',
            ],
            [
                'key' => 'ytrip_tour_details',
                'compare' => 'NOT EXISTS',
            ],
        ];

        $query_args['meta_query'] = $meta_query;

        $query = new WP_Query($query_args);

        return $query->posts;
    }

    /**
     * Get related tours by category.
     *
     * @param int   $tour_id Tour ID.
     * @param array $args    Arguments.
     * @return array
     */
    private function get_related_by_category(int $tour_id, array $args) {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        // Get tour categories
        $categories = get_the_terms($tour_id, $category_slug);

        if (!$categories || is_wp_error($categories)) {
            return [];
        }

        $category_ids = wp_list_pluck($categories, 'term_id');

        $query_args = [
            'post_type' => $tour_slug,
            'posts_per_page' => $args['count'],
            'post__not_in' => [$tour_id],
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => $category_slug,
                    'field' => 'term_id',
                    'terms' => $category_ids,
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new WP_Query($query_args);

        return $query->posts;
    }

    /**
     * Get fallback related tours.
     *
     * @param int   $tour_id Tour ID.
     * @param array $args    Arguments.
     * @param array $exclude Posts to exclude.
     * @return array
     */
    private function get_fallback_related(int $tour_id, array $args, array $exclude = []) {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';

        $exclude_ids = wp_list_pluck($exclude, 'ID');
        $exclude_ids[] = $tour_id;

        $query_args = [
            'post_type' => $tour_slug,
            'posts_per_page' => $args['count'] - count($exclude),
            'post__not_in' => $exclude_ids,
            'post_status' => 'publish',
        ];

        // Fallback method
        switch ($args['fallback']) {
            case 'featured':
                $query_args['meta_query'] = [
                    [
                        'key' => 'ytrip_tour_details',
                        'value' => '"featured";s:4:"true"',
                        'compare' => 'LIKE',
                    ],
                ];
                break;

            case 'popular':
                // Order by comment count
                $query_args['orderby'] = 'comment_count';
                $query_args['order'] = 'DESC';
                break;

            case 'recent':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }

        $query = new WP_Query($query_args);
        $fallback_posts = $query->posts;

        // If featured/popular returned no posts, retry with recent so section is not empty
        $need = $args['count'] - count($exclude);
        if ( count( $fallback_posts ) < $need && $args['fallback'] !== 'recent' ) {
            $query_args['orderby']   = 'date';
            $query_args['order']     = 'DESC';
            $query_args['meta_query'] = [];
            $query_args['posts_per_page'] = $need;
            $retry = new WP_Query( $query_args );
            $fallback_posts = $retry->posts;
        }

        return array_merge( $exclude, $fallback_posts );
    }

    /**
     * Render related tours section.
     *
     * @param int $tour_id Tour ID.
     * @return void
     */
    public function render_related_section(int $tour_id = 0) {
        if (!$tour_id) {
            $tour_id = get_the_ID();
        }

        if (!$tour_id) {
            return;
        }

        // Check if related tours are enabled (default true when not set)
        $setting = $this->settings['single_show_related'] ?? $this->settings['related_enable'] ?? true;
        $enabled = filter_var( $setting, FILTER_VALIDATE_BOOLEAN );
        if ( ! $enabled ) {
            return;
        }

        // Get related tours
        $related = $this->get_related($tour_id);

        if (empty($related)) {
            return;
        }

        // Get settings
        $title = $this->settings['related_title'] ?? __('You May Also Like', 'ytrip');
        $layout = $this->settings['related_layout'] ?? 'carousel';

        // Render
        $this->render_section($related, [
            'title' => $title,
            'layout' => $layout,
        ]);
    }

    /**
     * Render related tours section.
     *
     * @param array $tours Tours array.
     * @param array $args  Display arguments.
     * @return void
     */
    private function render_section(array $tours, array $args = []) {
        $args = wp_parse_args($args, [
            'title' => __('You May Also Like', 'ytrip'),
            'layout' => 'carousel',
            'class' => '',
        ]);
        ?>
        <section class="ytrip-section ytrip-related-tours <?php echo esc_attr($args['class']); ?>" aria-labelledby="ytrip-related-tours-heading">
            <div class="ytrip-container">
                <header class="ytrip-related-tours__header">
                    <p class="ytrip-related-tours__eyebrow"><?php esc_html_e('Discover more', 'ytrip'); ?></p>
                    <h2 id="ytrip-related-tours-heading" class="ytrip-related-tours__title"><?php echo esc_html($args['title']); ?></h2>
                </header>

                <div class="ytrip-related-tours__content">
                <?php
                $related_card_elements = array( 'wishlist', 'badge', 'location', 'category', 'duration', 'group_size', 'rating', 'price' );
                $layout = ( $args['layout'] === 'carousel' ) ? 'carousel' : 'grid';
                $related_ids = array_filter( array_map( function ( $p ) {
                    return isset( $p->ID ) ? (int) $p->ID : 0;
                }, $tours ) );
                echo ytrip_render_tours( [
                    'layout'          => $layout,
                    'columns'         => 4,
                    'posts'           => $tours,
                    'ids'             => $related_ids,
                    'show_pagination' => false,
                    'card_elements'   => $related_card_elements,
                ] );
                ?>
                </div>
            </div>
        </section>
        <?php
    }
}

/**
 * Helper function to get related tours.
 *
 * @param int   $tour_id Tour ID.
 * @param array $args    Arguments.
 * @return array
 */
function ytrip_get_related_tours(int $tour_id, array $args = []) {
    return YTrip_Related_Tours::instance()->get_related($tour_id, $args);
}

/**
 * Helper function to render related tours.
 *
 * @param int $tour_id Tour ID.
 * @return void
 */
function ytrip_render_related_tours(int $tour_id = 0) {
    YTrip_Related_Tours::instance()->render_related_section($tour_id);
}

// Initialize.
YTrip_Related_Tours::instance();
