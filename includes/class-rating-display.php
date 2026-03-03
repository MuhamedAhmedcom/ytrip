<?php
/**
 * YTrip Rating Display
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Rating_Display {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_shortcode( 'ytrip_rating', array( $this, 'shortcode' ) );
    }

    public function enqueue_styles() {
        wp_add_inline_style( 'ytrip-main', $this->get_css() );
    }

    public function get_tour_rating( $tour_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ytrip_ratings';
        
        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(*) as count, AVG(rating) as average FROM {$table} WHERE tour_id = %d AND status = 'approved'",
            $tour_id
        ), ARRAY_A );

        return array(
            'average' => $stats['average'] ? round( (float) $stats['average'], 1 ) : 0,
            'count'   => (int) $stats['count'],
        );
    }

    public function render_stars( $rating, $size = 'md' ) {
        $full_stars = floor( $rating );
        $half_star  = ( $rating - $full_stars ) >= 0.5 ? 1 : 0;
        $empty_stars = 5 - $full_stars - $half_star;

        $html = '<div class="ytrip-stars ytrip-stars--' . esc_attr( $size ) . '">';
        
        for ( $i = 0; $i < $full_stars; $i++ ) {
            $html .= '<span class="ytrip-star ytrip-star--full">★</span>';
        }
        
        if ( $half_star ) {
            $html .= '<span class="ytrip-star ytrip-star--half">★</span>';
        }
        
        for ( $i = 0; $i < $empty_stars; $i++ ) {
            $html .= '<span class="ytrip-star ytrip-star--empty">☆</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    public function render_rating_badge( $tour_id ) {
        $rating = $this->get_tour_rating( $tour_id );
        
        if ( $rating['count'] == 0 ) {
            return '';
        }

        ob_start();
        ?>
        <div class="ytrip-rating-badge">
            <?php echo $this->render_stars( $rating['average'], 'sm' ); ?>
            <span class="ytrip-rating-badge__average"><?php echo esc_html( $rating['average'] ); ?></span>
            <span class="ytrip-rating-badge__count">(<?php echo esc_html( $rating['count'] ); ?>)</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_reviews_section( $tour_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ytrip_ratings';
        
        // Reviews enabled by default
        $rating = $this->get_tour_rating( $tour_id );
        $reviews = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE tour_id = %d AND status = 'approved' ORDER BY helpful_count DESC, created_at DESC LIMIT 10",
            $tour_id
        ) );

        ob_start();
        ?>
        <div class="ytrip-reviews" id="reviews">
            <div class="ytrip-reviews__header">
                <h3 class="ytrip-reviews__title">
                    <?php echo esc_html__( 'Customer Reviews', 'ytrip' ); ?>
                </h3>
                <?php if ( $rating['count'] > 0 ) : ?>
                <div class="ytrip-reviews__summary">
                    <div class="ytrip-reviews__overall">
                        <span class="ytrip-reviews__average"><?php echo esc_html( $rating['average'] ); ?></span>
                        <?php echo $this->render_stars( $rating['average'], 'md' ); ?>
                        <span class="ytrip-reviews__count"><?php printf( _n( '%s review', '%s reviews', $rating['count'], 'ytrip' ), esc_html( $rating['count'] ) ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $reviews ) ) : ?>
            <div class="ytrip-reviews__list">
                <?php foreach ( $reviews as $review ) : ?>
                <div class="ytrip-review">
                    <div class="ytrip-review__header">
                        <div class="ytrip-review__stars">
                            <?php echo $this->render_stars( $review->rating, 'sm' ); ?>
                        </div>
                        <span class="ytrip-review__date">
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?>
                        </span>
                    </div>
                    <?php if ( ! empty( $review->title ) ) : ?>
                    <h4 class="ytrip-review__title"><?php echo esc_html( $review->title ); ?></h4>
                    <?php endif; ?>
                    <div class="ytrip-review__content">
                        <?php echo wpautop( esc_html( $review->content ) ); ?>
                    </div>
                    <?php if ( $review->verified ) : ?>
                    <span class="ytrip-review__verified">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                        <?php echo esc_html__( 'Verified', 'ytrip' ); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <p class="ytrip-reviews__empty">
                <?php echo esc_html__( 'No reviews yet. Be the first to review!', 'ytrip' ); ?>
            </p>
            <?php endif; ?>
            
            <?php echo $this->render_review_form( $tour_id ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_review_form( $tour_id ) {
        ob_start();
        ?>
        <div class="ytrip-review-form" id="write-review">
            <h3 class="ytrip-review-form__title"><?php echo esc_html__( 'Write a Review', 'ytrip' ); ?></h3>
            
            <form class="ytrip-form" method="post" action="">
                <input type="hidden" name="ytrip_review_tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
                
                <div class="ytrip-form__group">
                    <label class="ytrip-form__label"><?php echo esc_html__( 'Your Rating', 'ytrip' ); ?></label>
                    <div class="ytrip-star-rating-input">
                        <input type="radio" name="ytrip_review_rating" value="5" id="star5" required>
                        <label for="star5">★</label>
                        <input type="radio" name="ytrip_review_rating" value="4" id="star4">
                        <label for="star4">★</label>
                        <input type="radio" name="ytrip_review_rating" value="3" id="star3">
                        <label for="star3">★</label>
                        <input type="radio" name="ytrip_review_rating" value="2" id="star2">
                        <label for="star2">★</label>
                        <input type="radio" name="ytrip_review_rating" value="1" id="star1">
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <div class="ytrip-form__group">
                    <label class="ytrip-form__label" for="review_title"><?php echo esc_html__( 'Review Title', 'ytrip' ); ?></label>
                    <input type="text" id="review_title" name="ytrip_review_title" class="ytrip-form__input" placeholder="<?php echo esc_attr__( 'Summarize your experience', 'ytrip' ); ?>" required>
                </div>
                
                <div class="ytrip-form__group">
                    <label class="ytrip-form__label" for="review_content"><?php echo esc_html__( 'Your Review', 'ytrip' ); ?></label>
                    <textarea id="review_content" name="ytrip_review_content" class="ytrip-form__textarea" rows="5" placeholder="<?php echo esc_attr__( 'Tell us about your experience...', 'ytrip' ); ?>" required></textarea>
                </div>
                
                <button type="submit" name="ytrip_submit_review" class="ytrip-btn ytrip-btn--primary">
                    <?php echo esc_html__( 'Submit Review', 'ytrip' ); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'tour_id' => get_the_ID(),
            'type'    => 'badge',
        ), $atts );

        if ( $atts['type'] === 'reviews' ) {
            return $this->render_reviews_section( (int) $atts['tour_id'] );
        }

        return $this->render_rating_badge( (int) $atts['tour_id'] );
    }

    private function get_css() {
        return '
        .ytrip-stars { display: inline-flex; gap: 2px; }
        .ytrip-stars--sm { font-size: 14px; }
        .ytrip-stars--md { font-size: 18px; }
        .ytrip-star--full { color: #f59e0b; }
        .ytrip-star--half { color: #f59e0b; opacity: 0.6; }
        .ytrip-star--empty { color: #d1d5db; }
        
        .ytrip-rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #fef3c7;
            border-radius: 20px;
            font-size: 14px;
        }
        .ytrip-rating-badge__average { font-weight: 700; color: #92400e; }
        .ytrip-rating-badge__count { color: #78716c; }
        
        .ytrip-reviews { margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; }
        .ytrip-reviews__header { margin-bottom: 1.5rem; }
        .ytrip-reviews__title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .ytrip-reviews__overall { display: flex; align-items: center; gap: 10px; }
        .ytrip-reviews__average { font-size: 2rem; font-weight: 800; color: #1f2937; }
        .ytrip-reviews__count { color: #6b7280; }
        
        .ytrip-review { padding: 1.5rem 0; border-bottom: 1px solid #f3f4f6; }
        .ytrip-review:last-child { border-bottom: none; }
        .ytrip-review__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .ytrip-review__date { font-size: 13px; color: #9ca3af; }
        .ytrip-review__title { font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .ytrip-review__content { color: #4b5563; line-height: 1.6; }
        .ytrip-review__verified { display: inline-flex; align-items: center; gap: 4px; color: #059669; font-size: 13px; margin-top: 0.5rem; }
        .ytrip-reviews__empty { padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 8px; }
        
        .ytrip-review-form { background: #f8fafc; padding: 2rem; border-radius: 12px; margin-top: 2rem; }
        .ytrip-review-form__title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; }
        .ytrip-form__group { margin-bottom: 1.25rem; }
        .ytrip-form__label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b; }
        .ytrip-form__input, .ytrip-form__textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        .ytrip-form__input:focus, .ytrip-form__textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .ytrip-star-rating-input { display: flex; flex-direction: row-reverse; gap: 4px; }
        .ytrip-star-rating-input input { display: none; }
        .ytrip-star-rating-input label { font-size: 2rem; color: #d1d5db; cursor: pointer; }
        .ytrip-star-rating-input label:hover, .ytrip-star-rating-input label:hover ~ label, .ytrip-star-rating-input input:checked ~ label { color: #f59e0b; }
        
        .ytrip-btn--primary { background: #2563eb; color: #fff; padding: 0.875rem 2rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .ytrip-btn--primary:hover { background: #1d4ed8; }
        ';
    }
}

YTrip_Rating_Display::instance();
