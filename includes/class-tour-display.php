<?php
/**
 * YTrip Tour Display
 * 
 * Handles tour display with Grid, List, and Carousel layouts.
 * Pure CSS and Vanilla JavaScript for best performance.
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Tour_Display
 */
class YTrip_Tour_Display {

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
    }

    /**
     * Render tours.
     *
     * @param array $args Display arguments.
     * @return string
     */
    public function render(array $args = []) {
        $defaults = [
            'layout' => $this->settings['archive_layout'] ?? 'grid',
            'columns' => $this->settings['archive_columns'] ?? 3,
            'posts_per_page' => $this->settings['archive_cards'] ?? 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'style' => $this->settings['card_style'] ?? 'modern',
            'show_pagination' => true,
            'show_filters' => false,
            'category' => '',
            'destination' => '',
            'ids' => '',
            // Carousel specific
            'autoplay' => $this->settings['carousel_autoplay'] ?? true,
            'speed' => $this->settings['carousel_speed'] ?? 5000,
            'loop' => $this->settings['carousel_loop'] ?? true,
            'navigation' => $this->settings['carousel_navigation'] ?? ['arrows', 'dots'],
            'slides_desktop' => $this->settings['carousel_slides'] ?? 3,
            'slides_tablet' => $this->settings['carousel_slides_tablet'] ?? 2,
            'slides_mobile' => $this->settings['carousel_slides_mobile'] ?? 1,
            'gap' => $this->settings['carousel_gap'] ?? 20,
            'pause_hover' => $this->settings['carousel_pause_hover'] ?? true,
        ];

        $args = wp_parse_args($args, $defaults);

        // When posts are passed directly (e.g. related section), skip query so nothing filters them out
        if ( ! empty( $args['posts'] ) && is_array( $args['posts'] ) ) {
            $tours = [
                'posts' => array_values( $args['posts'] ),
                'found' => count( $args['posts'] ),
                'max_pages' => 1,
            ];
        } else {
            $tours = $this->get_tours( $args );
        }

        if (empty($tours['posts'])) {
            return $this->render_empty();
        }

        // Render based on layout
        switch ($args['layout']) {
            case 'list':
                $output = $this->render_list($tours, $args);
                break;
            case 'carousel':
                $output = $this->render_carousel($tours, $args);
                break;
            case 'grid':
            default:
                $output = $this->render_grid($tours, $args);
                break;
        }

        // Add pagination if needed
        if ($args['show_pagination'] && $args['layout'] !== 'carousel') {
            $output .= $this->render_pagination($tours);
        }

        return $output;
    }

    /**
     * Get tours query.
     *
     * @param array $args Query arguments.
     * @return array
     */
    private function get_tours(array $args) {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        $query_args = [
            'post_type' => $tour_slug,
            'posts_per_page' => $args['posts_per_page'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'post_status' => 'publish',
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        ];

        // Taxonomy queries
        $tax_query = [];

        if (!empty($args['destination'])) {
            $tax_query[] = [
                'taxonomy' => $destination_slug,
                'field' => 'slug',
                'terms' => $args['destination'],
            ];
        }

        if (!empty($args['category'])) {
            $tax_query[] = [
                'taxonomy' => $category_slug,
                'field' => 'slug',
                'terms' => $args['category'],
            ];
        }

        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }

        // Specific IDs (e.g. related tours) – suppress filters so theme/plugins don't remove posts
        if ( ! empty( $args['ids'] ) ) {
            $ids = is_array( $args['ids'] ) ? $args['ids'] : explode( ',', $args['ids'] );
            $query_args['post__in'] = array_map( 'intval', $ids );
            $query_args['orderby'] = 'post__in';
            $query_args['suppress_filters'] = true;
        }

        // Use cached query if available (only when not querying by ids)
        if ( function_exists( 'ytrip_get_tours_optimized' ) && empty( $args['ids'] ) ) {
            return ytrip_get_tours_optimized( $query_args );
        }

        $query = new WP_Query( $query_args );

        return [
            'posts' => $query->posts,
            'found' => (int) $query->found_posts,
            'max_pages' => (int) $query->max_num_pages,
        ];
    }

    /**
     * Render grid layout.
     *
     * @param array $tours Tours data.
     * @param array $args  Display arguments.
     * @return string
     */
    private function render_grid(array $tours, array $args) {
        $columns = (int) $args['columns'];
        $style = sanitize_html_class($args['style']);

        ob_start();
        ?>
        <div class="ytrip-tours ytrip-tours--grid ytrip-tours--cols-<?php echo esc_attr($columns); ?> ytrip-tours--style-<?php echo esc_attr($style); ?>">
            <?php foreach ($tours['posts'] as $tour) : ?>
                <?php $this->render_card($tour, $args); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render list layout.
     *
     * @param array $tours Tours data.
     * @param array $args  Display arguments.
     * @return string
     */
    private function render_list(array $tours, array $args) {
        ob_start();
        ?>
        <div class="ytrip-tours ytrip-tours--list">
            <?php foreach ($tours['posts'] as $tour) : ?>
                <?php $this->render_list_item($tour, $args); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render carousel layout.
     *
     * @param array $tours Tours data.
     * @param array $args  Display arguments.
     * @return string
     */
    private function render_carousel(array $tours, array $args) {
        $carousel_id = 'ytrip-carousel-' . wp_unique_id();

        // Build data attributes for JavaScript
        $carousel_data = [
            'autoplay' => $args['autoplay'] ? 'true' : 'false',
            'speed' => (int) $args['speed'],
            'loop' => $args['loop'] ? 'true' : 'false',
            'slides-desktop' => (int) $args['slides_desktop'],
            'slides-tablet' => (int) $args['slides_tablet'],
            'slides-mobile' => (int) $args['slides_mobile'],
            'gap' => (int) $args['gap'],
            'pause-hover' => $args['pause_hover'] ? 'true' : 'false',
        ];

        ob_start();
        ?>
        <div class="ytrip-tours ytrip-tours--carousel-wrapper">
            <div 
                id="<?php echo esc_attr($carousel_id); ?>" 
                class="ytrip-carousel"
                <?php foreach ($carousel_data as $key => $value) : ?>
                    data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
                <?php endforeach; ?>
            >
                <div class="ytrip-carousel__track">
                    <?php foreach ($tours['posts'] as $tour) : ?>
                        <div class="ytrip-carousel__slide">
                            <?php $this->render_card($tour, $args); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (in_array('arrows', $args['navigation'], true)) : ?>
            <button type="button" class="ytrip-carousel__arrow ytrip-carousel__arrow--prev" data-carousel="<?php echo esc_attr($carousel_id); ?>" aria-label="<?php esc_attr_e('Previous', 'ytrip'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" class="ytrip-carousel__arrow ytrip-carousel__arrow--next" data-carousel="<?php echo esc_attr($carousel_id); ?>" aria-label="<?php esc_attr_e('Next', 'ytrip'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <?php endif; ?>

            <?php if (in_array('dots', $args['navigation'], true)) : ?>
            <div class="ytrip-carousel__dots" data-carousel="<?php echo esc_attr($carousel_id); ?>"></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tour card.
     *
     * @param WP_Post|array|int $tour Tour object or ID.
     * @param array             $args Display arguments.
     * @return void
     */
    private function render_card($tour, array $args = []) {
        $tour_data = null;
        $tour_id = 0;

        if (is_array($tour) && isset($tour['id'])) {
            $tour_data = $tour;
            $tour_id = (int) $tour['id'];
        } elseif (is_numeric($tour)) {
            $tour = get_post($tour);
        }

        if (!$tour_data) {
            if ($tour instanceof WP_Post) {
                $tour_id = $tour->ID;
            } else {
                return;
            }
        }

        // Get meta
        if ($tour_data && isset($tour_data['meta'])) {
            $meta = $tour_data['meta'];
        } else {
            $meta = get_post_meta($tour_id, 'ytrip_tour_details', true);
            $meta = is_array($meta) ? $meta : [];
        }

        // Get card elements setting
        $elements = $this->settings['card_elements'] ?? ['wishlist', 'badge', 'rating', 'duration', 'price'];
        if ( ! empty( $args['card_elements'] ) && is_array( $args['card_elements'] ) ) {
            $elements = $args['card_elements'];
        }
        $style = $args['style'] ?? $this->settings['card_style'] ?? 'modern';
        $show_wishlist = in_array('wishlist', $elements, true);
        $show_badge = in_array('badge', $elements, true);
        $show_rating = in_array('rating', $elements, true);
        $show_duration = in_array('duration', $elements, true);
        $show_group = in_array('group_size', $elements, true);
        $show_location = in_array('location', $elements, true);
        $show_category = in_array('category', $elements, true);
        $show_price = in_array('price', $elements, true);
        $show_excerpt = in_array('excerpt', $elements, true);

        $settings = get_option('ytrip_settings', []);

        // Get destination term (for name + link)
        $destination_term = null;
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $dest_terms = get_the_terms($tour_id, $destination_slug);
        if ($dest_terms && ! is_wp_error($dest_terms)) {
            $destination_term = $dest_terms[0];
        }

        // Get category term (for name + link)
        $category_term = null;
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';
        $cat_terms = get_the_terms($tour_id, $category_slug);
        if ($cat_terms && ! is_wp_error($cat_terms)) {
            $category_term = $cat_terms[0];
        }

        // Get rating
        $rating = $tour_data['average_rating'] ?? $this->get_tour_rating($tour_id);
        $review_count = $tour_data['review_count'] ?? $this->get_tour_review_count($tour_id);

        // Get price
        $price_data = $tour_data['price'] ?? $this->get_tour_price($tour_id);
        
        // Get permalink and title
        $permalink = $tour_data['permalink'] ?? get_permalink($tour_id);
        $title = $tour_data['title'] ?? get_the_title($tour_id);
        $excerpt = $tour_data['excerpt'] ?? get_the_excerpt($tour_id);
        ?>
        <article class="ytrip-tour-card ytrip-tour-card--<?php echo esc_attr($style); ?>">
            <div class="ytrip-tour-card__image">
                <?php
                $has_thumbnail = false;
                if ($tour_data && !empty($tour_data['thumbnail'])) {
                    $has_thumbnail = true;
                    echo '<span class="ytrip-img-skeleton" aria-hidden="true"></span>';
                    echo '<a href="' . esc_url($permalink) . '" class="ytrip-tour-card__image-link" aria-label="' . esc_attr($title) . '">';
                    echo '<img src="' . esc_url($tour_data['thumbnail']) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async" width="400" height="300">';
                    echo '</a>';
                } else {
                    $effective_thumb_id = function_exists( 'ytrip_get_effective_thumbnail_id' ) 
                        ? ytrip_get_effective_thumbnail_id( $tour_id, $meta )
                        : ( has_post_thumbnail($tour_id) ? get_post_thumbnail_id($tour_id) : 0 );

                    if ( $effective_thumb_id ) {
                        $has_thumbnail = true;
                        echo '<span class="ytrip-img-skeleton" aria-hidden="true"></span>';
                        ?>
                        <a href="<?php echo esc_url($permalink); ?>" class="ytrip-tour-card__image-link" aria-label="<?php echo esc_attr($title); ?>">
                            <?php echo wp_get_attachment_image($effective_thumb_id, 'ytrip-card', false, ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        </a>
                        <?php
                    }
                }

                if (!$has_thumbnail) : ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="ytrip-tour-card__image-link" aria-label="<?php echo esc_attr($title); ?>">
                        <div class="ytrip-tour-card__placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($show_badge) : ?>
                    <?php if (!empty($meta['featured'])) : ?>
                        <span class="ytrip-tour-card__badge ytrip-tour-card__badge--featured">
                            <?php esc_html_e('Featured', 'ytrip'); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($price_data['on_sale'])) : ?>
                        <span class="ytrip-tour-card__badge ytrip-tour-card__badge--sale">
                            <?php esc_html_e('Sale', 'ytrip'); ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($show_wishlist) : ?>
                    <button 
                        type="button" 
                        class="ytrip-tour-card__wishlist" 
                        data-tour-id="<?php echo esc_attr((string)$tour_id); ?>"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('ytrip_wishlist_nonce')); ?>"
                        aria-label="<?php esc_attr_e('Add to wishlist', 'ytrip'); ?>"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

            <div class="ytrip-tour-card__content">
                <?php if ( ($show_location && $destination_term) || ($show_category && $category_term) ) : ?>
                    <div class="ytrip-tour-card__terms">
                        <?php if ($show_location && $destination_term) : ?>
                            <?php
                            $dest_link = get_term_link($destination_term);
                            if ( ! is_wp_error($dest_link) ) :
                                ?>
                                <a href="<?php echo esc_url($dest_link); ?>" class="ytrip-tour-card__term ytrip-tour-card__term--destination">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span><?php echo esc_html($destination_term->name); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="ytrip-tour-card__term ytrip-tour-card__term--destination">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span><?php echo esc_html($destination_term->name); ?></span>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($show_location && $destination_term && $show_category && $category_term) : ?>
                            <span class="ytrip-tour-card__terms-sep" aria-hidden="true">·</span>
                        <?php endif; ?>

                        <?php if ($show_category && $category_term) : ?>
                            <?php
                            $cat_link = get_term_link($category_term);
                            if ( ! is_wp_error($cat_link) ) :
                                ?>
                                <a href="<?php echo esc_url($cat_link); ?>" class="ytrip-tour-card__term ytrip-tour-card__term--category">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <span><?php echo esc_html($category_term->name); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="ytrip-tour-card__term ytrip-tour-card__term--category">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <span><?php echo esc_html($category_term->name); ?></span>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h3 class="ytrip-tour-card__title">
                    <a href="<?php echo esc_url($permalink); ?>">
                        <?php echo esc_html($title); ?>
                    </a>
                </h3>

                <?php if ($show_excerpt) : ?>
                    <p class="ytrip-tour-card__excerpt">
                        <?php echo esc_html(wp_trim_words($excerpt, 15)); ?>
                    </p>
                <?php endif; ?>

                <div class="ytrip-tour-card__meta">
                    <?php
                    if ($show_group) {
                        $group_formatted = class_exists('YTrip_Helper') && method_exists('YTrip_Helper', 'format_group_size_from_meta')
                            ? YTrip_Helper::format_group_size_from_meta($meta)
                            : $this->format_group_size($meta);
                        if (is_string($group_formatted)) {
                            $group_formatted = trim($group_formatted);
                        }
                        if ($group_formatted !== '') {
                            if (is_numeric($group_formatted) || preg_match('/^\d+\s*[–-]\s*\d+$/', $group_formatted)) {
                                $group_formatted = sprintf(/* translators: group size range or single number */ __('%s People', 'ytrip'), $group_formatted);
                            }
                            ?>
                        <span class="ytrip-tour-card__meta-item ytrip-tour-card__meta-item--group">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <?php echo esc_html($group_formatted); ?>
                        </span>
                            <?php
                        }
                    }

                    if ($show_duration) {
                        $duration_formatted = class_exists('YTrip_Helper') && method_exists('YTrip_Helper', 'format_duration_from_meta')
                            ? YTrip_Helper::format_duration_from_meta($meta)
                            : $this->format_duration($meta);
                        if (is_string($duration_formatted)) {
                            $duration_formatted = trim($duration_formatted);
                        }
                        if ($duration_formatted !== '') :
                            ?>
                        <span class="ytrip-tour-card__meta-item ytrip-tour-card__meta-item--duration">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?php echo esc_html($duration_formatted); ?>
                        </span>
                            <?php
                        endif;
                    }
                    ?>
                </div>

                <?php if ($show_rating && $rating > 0) : ?>
                    <div class="ytrip-tour-card__rating" role="img" aria-label="<?php printf(esc_attr__('Rating: %s out of 5', 'ytrip'), esc_html((string)$rating)); ?>">
                        <div class="ytrip-tour-card__stars">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <svg 
                                    width="14" 
                                    height="14" 
                                    viewBox="0 0 24 24" 
                                    fill="<?php echo $i <= round($rating) ? 'currentColor' : 'none'; ?>" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <span class="ytrip-tour-card__rating-text">(<?php echo esc_html((string)$review_count); ?>)</span>
                    </div>
                <?php endif; ?>

                <div class="ytrip-tour-card__footer">
                    <?php if ($show_price && ! empty( $price_data['formatted'] )) : ?>
                        <div class="ytrip-tour-card__price">
                            <?php if ( ! empty( $price_data['on_sale'] ) && ! empty( $price_data['regular'] )) : ?>
                                <span class="ytrip-tour-card__price-regular">
                                    <del><?php echo esc_html( $price_data['regular_formatted'] ); ?></del>
                                </span>
                            <?php endif; ?>
                            <span class="ytrip-tour-card__price-current">
                                <?php echo wp_kses_post( $price_data['formatted'] ); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($permalink); ?>" class="ytrip-btn ytrip-btn-sm ytrip-btn-primary">
                        <?php esc_html_e('View Details', 'ytrip'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Render list item.
     *
     * @param WP_Post|array|int $tour Tour object or ID.
     * @param array             $args Display arguments.
     * @return void
     */
    private function render_list_item($tour, array $args = []) {
        $tour_data = null;
        $tour_id = 0;

        if (is_array($tour) && isset($tour['id'])) {
            $tour_data = $tour;
            $tour_id = (int) $tour['id'];
        } elseif (is_numeric($tour)) {
            $tour = get_post($tour);
        }

        if (!$tour_data) {
            if ($tour instanceof WP_Post) {
                $tour_id = $tour->ID;
            } else {
                return;
            }
        }

        if ($tour_data && isset($tour_data['meta'])) {
            $meta = $tour_data['meta'];
        } else {
            $meta = get_post_meta($tour_id, 'ytrip_tour_details', true);
            $meta = is_array($meta) ? $meta : [];
        }

        $destination = '';
        if ($tour_data && !empty($tour_data['destinations'])) {
            $destination = is_array($tour_data['destinations']) ? reset($tour_data['destinations']) : '';
        } else {
            $settings = get_option('ytrip_settings', []);
            $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
            $terms = get_the_terms($tour_id, $destination_slug);
            $destination = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
        }

        $rating = $tour_data['average_rating'] ?? $this->get_tour_rating($tour_id);
        $review_count = $tour_data['review_count'] ?? $this->get_tour_review_count($tour_id);
        $price_data = $tour_data['price'] ?? $this->get_tour_price($tour_id);
        
        $permalink = $tour_data['permalink'] ?? get_permalink($tour_id);
        $title = $tour_data['title'] ?? get_the_title($tour_id);
        $excerpt = $tour_data['excerpt'] ?? get_the_excerpt($tour_id);
        ?>
        <article class="ytrip-tour-list-item">
            <div class="ytrip-tour-list-item__image">
                <?php 
                if ($tour_data && !empty($tour_data['thumbnail'])) {
                    echo '<a href="' . esc_url($permalink) . '">';
                    echo '<img src="' . esc_url($tour_data['thumbnail']) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async">';
                    echo '</a>';
                } elseif (has_post_thumbnail($tour_id)) {
                    ?>
                    <a href="<?php echo esc_url($permalink); ?>">
                        <?php echo get_the_post_thumbnail($tour_id, 'medium', ['loading' => 'lazy']); ?>
                    </a>
                    <?php
                }
                ?>
            </div>

            <div class="ytrip-tour-list-item__content">
                <?php if ($destination) : ?>
                    <div class="ytrip-tour-list-item__location"><?php echo esc_html($destination); ?></div>
                <?php endif; ?>

                <h3 class="ytrip-tour-list-item__title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>

                <p class="ytrip-tour-list-item__excerpt">
                    <?php echo esc_html(wp_trim_words($excerpt, 30)); ?>
                </p>

                <div class="ytrip-tour-list-item__meta">
                    <?php if (!empty($meta['duration'])) : ?>
                        <span class="ytrip-tour-list-item__meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?php echo esc_html($this->format_duration($meta)); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($rating > 0) : ?>
                        <span class="ytrip-tour-list-item__rating">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo esc_html(number_format($rating, 1)); ?> (<?php echo esc_html((string)$review_count); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ytrip-tour-list-item__action">
                <div class="ytrip-tour-list-item__price">
                    <?php echo !empty($price_data['formatted']) ? wp_kses_post($price_data['formatted']) : ''; ?>
                </div>
                <a href="<?php echo esc_url($permalink); ?>" class="ytrip-btn ytrip-btn-primary">
                    <?php esc_html_e('View Tour', 'ytrip'); ?>
                </a>
            </div>
        </article>
        <?php
    }

    /**
     * Render empty state.
     *
     * @return string
     */
    private function render_empty() {
        ob_start();
        ?>
        <div class="ytrip-tours-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
                <line x1="9" y1="9" x2="9.01" y2="9"/>
                <line x1="15" y1="9" x2="15.01" y2="9"/>
            </svg>
            <h3><?php esc_html_e('No Tours Found', 'ytrip'); ?></h3>
            <p><?php esc_html_e('Try adjusting your filters or check back later.', 'ytrip'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render pagination.
     *
     * @param array $tours Tours data.
     * @return string
     */
    private function render_pagination(array $tours) {
        if ($tours['max_pages'] <= 1) {
            return '';
        }

        $pagination_style = $this->settings['archive_pagination'] ?? 'numbers';

        ob_start();

        if ($pagination_style === 'loadmore') :
            ?>
            <div class="ytrip-pagination ytrip-pagination--loadmore">
                <button type="button" class="ytrip-btn ytrip-btn-secondary ytrip-loadmore-btn" data-page="1" data-max="<?php echo esc_attr($tours['max_pages']); ?>">
                    <?php esc_html_e('Load More Tours', 'ytrip'); ?>
                </button>
            </div>
            <?php
        elseif ($pagination_style === 'infinite') :
            ?>
            <div class="ytrip-pagination ytrip-pagination--infinite" data-page="1" data-max="<?php echo esc_attr($tours['max_pages']); ?>">
                <div class="ytrip-infinite-loader">
                    <div class="ytrip-spinner"></div>
                </div>
            </div>
            <?php
        else :
            ?>
            <nav class="ytrip-pagination ytrip-pagination--numbers" role="navigation" aria-label="<?php esc_attr_e('Tour pagination', 'ytrip'); ?>">
                <?php
                echo paginate_links([
                    'total' => $tours['max_pages'],
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                    'next_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                ]);
                ?>
            </nav>
            <?php
        endif;

        return ob_get_clean();
    }

    /**
     * Format duration (supports tour_duration fieldset, duration array, or duration string).
     *
     * @param array $meta Tour meta (ytrip_tour_details).
     * @return string
     */
    private function format_duration(array $meta) {
        if ( ! is_array($meta) ) {
            return '';
        }

        if ( ! empty($meta['duration']) && is_string($meta['duration']) ) {
            return trim($meta['duration']);
        }

        $arr = isset($meta['tour_duration']) && is_array($meta['tour_duration'])
            ? $meta['tour_duration']
            : ( isset($meta['duration']) && is_array($meta['duration']) ? $meta['duration'] : [] );

        $days  = isset($arr['days']) ? max(0, (int) $arr['days']) : 0;
        $nights = isset($arr['nights']) ? max(0, (int) $arr['nights']) : 0;
        $hours = isset($arr['hours']) ? max(0, (int) $arr['hours']) : 0;

        if ($days > 0 || $nights > 0) {
            $parts = [];
            if ($days > 0) {
                $parts[] = sprintf( _n('%d Day', '%d Days', $days, 'ytrip'), $days );
            }
            if ($nights > 0) {
                $parts[] = sprintf( _n('%d Night', '%d Nights', $nights, 'ytrip'), $nights );
            }
            return implode(' / ', $parts);
        }

        if ($hours > 0) {
            return sprintf( _n('%d Hour', '%d Hours', $hours, 'ytrip'), $hours );
        }

        return '';
    }

    /**
     * Format group size.
     *
     * @param array $meta Tour meta.
     * @return string
     */
    private function format_group_size(array $meta) {
        if ( ! isset($meta['group_size']) ) {
            return '';
        }
        $gs = $meta['group_size'];
        if ( is_string($gs) && trim($gs) !== '' ) {
            return trim($gs);
        }
        if ( ! is_array($gs) ) {
            return '';
        }
        $min = (int) ($gs['min'] ?? 1);
        $max = (int) ($gs['max'] ?? 20);
        if ( $min <= 0 && $max <= 0 ) {
            return '';
        }
        if ( $min <= 0 ) {
            $min = 1;
        }
        if ( $max <= 0 ) {
            $max = max($min, 20);
        }
        if ( $min > $max ) {
            $max = $min;
        }
        return sprintf(
            /* translators: 1: min, 2: max */
            __('%1$d-%2$d People', 'ytrip'),
            $min,
            $max
        );
    }

    /**
     * Get tour rating.
     *
     * @param int $tour_id Tour ID.
     * @return float
     */
    private function get_tour_rating(int $tour_id) {
        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    return (float) $product->get_average_rating();
                }
            }
        }
        return 0.0;
    }

    /**
     * Get tour review count.
     *
     * @param int $tour_id Tour ID.
     * @return int
     */
    private function get_tour_review_count(int $tour_id) {
        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    return (int) $product->get_review_count();
                }
            }
        }
        return 0;
    }

    /**
     * Get tour price.
     *
     * @param int $tour_id Tour ID.
     * @return array
     */
    private function get_tour_price(int $tour_id) {
        $price_data = [
            'regular' => 0,
            'sale' => null,
            'formatted' => '',
            'regular_formatted' => '',
            'on_sale' => false,
        ];

        if (function_exists('wc_get_product')) {
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $price_data['regular'] = (float) $product->get_regular_price();
                    $price_data['sale'] = $product->is_on_sale() ? (float) $product->get_sale_price() : null;
                    $price_data['formatted'] = $product->get_price_html();
                    $price_data['regular_formatted'] = wc_price($price_data['regular']);
                    $price_data['on_sale'] = $product->is_on_sale();
                }
            }
        }

        if ($price_data['formatted'] === '' && is_numeric(get_post_meta($tour_id, '_ytrip_price', true))) {
            $raw = get_post_meta($tour_id, '_ytrip_price', true);
            $price_data['regular'] = (float) $raw;
            $price_data['formatted'] = class_exists('YTrip_Helper') && method_exists('YTrip_Helper', 'format_price_display')
                ? YTrip_Helper::format_price_display($raw)
                : number_format_i18n((float) $raw, 2) . ' €';
        }

        return $price_data;
    }
}

/**
 * Helper function to render tours.
 *
 * @param array $args Display arguments.
 * @return string
 */
function ytrip_render_tours(array $args = []) {
    return YTrip_Tour_Display::instance()->render($args);
}

// Initialize.
YTrip_Tour_Display::instance();
