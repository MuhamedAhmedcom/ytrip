<?php
/**
 * Single Tour Layout 4: Booking-Focused
 * Conversion-optimized with multiple CTAs
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$options = get_option( 'ytrip_settings' );
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

get_header();

include YTRIP_PATH . 'templates/parts/single-tour-brand-vars.php';
?>

<article class="ytrip-single ytrip-layout-booking">
    
    <!-- Top Bar - Quick Booking -->
    <div class="ytrip-top-booking-bar">
        <div class="ytrip-container">
            <div class="ytrip-top-booking-bar__inner">
                <div class="ytrip-top-booking-bar__info">
                    <h1 class="ytrip-top-booking-bar__title"><?php the_title(); ?></h1>
                    <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                    <div class="ytrip-top-booking-bar__rating">
                        <span class="ytrip-stars"><?php echo str_repeat( '★', round( $product->get_average_rating() ) ); ?></span>
                        <span><?php echo esc_html( $product->get_average_rating() ); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="ytrip-top-booking-bar__action">
                    <?php if ( $product ) : ?>
                    <span class="ytrip-top-booking-bar__price"><?php echo $product->get_price_html(); ?></span>
                    <?php endif; ?>
                    <a href="#book-now" class="ytrip-btn ytrip-btn-primary"><?php esc_html_e( 'Book Now', 'ytrip' ); ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Slider -->
    <section class="ytrip-booking-slider">
        <div class="ytrip-booking-slider__main">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'full' ); ?>
            <?php endif; ?>
        </div>
        
        <?php if ( ! empty( $meta['tour_gallery'] ) ) : ?>
        <div class="ytrip-booking-slider__thumbs">
            <?php 
            $gallery = explode( ',', $meta['tour_gallery'] );
            foreach ( $gallery as $img_id ) :
            ?>
            <div class="ytrip-booking-slider__thumb">
                <?php echo wp_get_attachment_image( $img_id, 'medium' ); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Price & CTA Section -->
    <section class="ytrip-price-cta-section">
        <div class="ytrip-container">
            <div class="ytrip-price-cta">
                <div class="ytrip-price-cta__left">
                    <?php if ( $product ) : ?>
                    <div class="ytrip-price-cta__pricing">
                        <span class="ytrip-price-cta__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
                        <span class="ytrip-price-cta__amount"><?php echo $product->get_price_html(); ?></span>
                        <span class="ytrip-price-cta__per"><?php esc_html_e( '/ person', 'ytrip' ); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ytrip-price-cta__badges">
                        <span class="ytrip-trust-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <?php esc_html_e( 'Best Price Guarantee', 'ytrip' ); ?>
                        </span>
                        <span class="ytrip-trust-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6L9 17l-5-5"/></svg>
                            <?php esc_html_e( 'Free Cancellation', 'ytrip' ); ?>
                        </span>
                    </div>
                </div>
                
                <div class="ytrip-price-cta__right">
                    <a href="#book-now" class="ytrip-btn ytrip-btn-primary ytrip-btn-lg ytrip-btn-pulse">
                        <?php esc_html_e( 'Check Availability', 'ytrip' ); ?>
                    </a>
                    <span class="ytrip-price-cta__note"><?php esc_html_e( 'Reserve now & pay later', 'ytrip' ); ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 Column Content -->
    <section class="ytrip-three-col-section">
        <div class="ytrip-container">
            <div class="ytrip-three-col">
                
                <!-- Quick Facts -->
                <div class="ytrip-quick-facts">
                    <h3><?php esc_html_e( 'Quick Facts', 'ytrip' ); ?></h3>
                    <ul>
                        <?php if ( ! empty( $meta['duration'] ) ) : ?>
                        <li><strong><?php esc_html_e( 'Duration', 'ytrip' ); ?>:</strong> <?php echo esc_html( $meta['duration'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( ! empty( $meta['group_size'] ) ) : ?>
                        <li><strong><?php esc_html_e( 'Group Size', 'ytrip' ); ?>:</strong> <?php echo esc_html( $meta['group_size'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( ! empty( $meta['languages'] ) ) : ?>
                        <li><strong><?php esc_html_e( 'Languages', 'ytrip' ); ?>:</strong> <?php echo esc_html( $meta['languages'] ); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Description -->
                <div class="ytrip-description-col">
                    <h3><?php esc_html_e( 'About This Tour', 'ytrip' ); ?></h3>
                    <?php the_content(); ?>
                </div>
                

                
            </div>
        </div>
    </section>

    <!-- Itinerary Section -->
    <?php if ( ! empty( $meta['itinerary'] ) ) : ?>
    <section class="ytrip-itinerary-section ytrip-section--gray">
        <div class="ytrip-container">
            <h2 class="ytrip-section-title"><?php esc_html_e( 'Tour Itinerary', 'ytrip' ); ?></h2>
            <div class="ytrip-itinerary-cards">
                <?php foreach ( $meta['itinerary'] as $i => $day ) : ?>
                <div class="ytrip-itinerary-card">
                    <div class="ytrip-itinerary-card__day"><?php printf( esc_html__( 'Day %d', 'ytrip' ), $i + 1 ); ?></div>
                    <h4 class="ytrip-itinerary-card__title"><?php echo esc_html( $day['title'] ); ?></h4>
                    <div class="ytrip-itinerary-card__desc"><?php echo wp_kses_post( $day['description'] ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Included/Excluded -->
    <section class="ytrip-inc-exc-section">
        <div class="ytrip-container">
            <div class="ytrip-inc-exc-grid">
                <div class="ytrip-inc-exc-box ytrip-inc-exc-box--yes">
                    <h3><?php esc_html_e( 'What\'s Included', 'ytrip' ); ?></h3>
                    <?php if ( ! empty( $meta['included'] ) ) : ?>
                    <ul>
                        <?php foreach ( $meta['included'] as $item ) : ?>
                        <li><?php echo esc_html( $item['item'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <div class="ytrip-inc-exc-box ytrip-inc-exc-box--no">
                    <h3><?php esc_html_e( 'Not Included', 'ytrip' ); ?></h3>
                    <?php if ( ! empty( $meta['excluded'] ) ) : ?>
                    <ul>
                        <?php foreach ( $meta['excluded'] as $item ) : ?>
                        <li><?php echo esc_html( $item['item'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Full Booking Section -->
    <section class="ytrip-full-booking-section" id="book-now">
        <div class="ytrip-container ytrip-container--narrow">
            <h2 class="ytrip-section-title"><?php esc_html_e( 'Book Your Adventure', 'ytrip' ); ?></h2>
            <?php include YTRIP_PATH . 'templates/parts/booking-card.php'; ?>
        </div>
    </section>

    <?php if ( is_active_sidebar( 'ytrip-single-tour' ) ) : ?>
    <section class="ytrip-section ytrip-single-tour-widget-area-section">
        <div class="ytrip-container ytrip-container--narrow">
            <div class="ytrip-single-tour-widget-area ytrip-widget-area">
                <?php dynamic_sidebar( 'ytrip-single-tour' ); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if ( ! empty( $meta['faq'] ) ) : ?>
    <section class="ytrip-faq-section ytrip-section--gray">
        <div class="ytrip-container ytrip-container--narrow">
            <h2 class="ytrip-section-title"><?php esc_html_e( 'Questions & Answers', 'ytrip' ); ?></h2>
            <div class="ytrip-faq-list">
                <?php foreach ( $meta['faq'] as $faq ) : ?>
                <div class="ytrip-faq-item">
                    <button class="ytrip-faq-item__q"><?php echo esc_html( $faq['question'] ); ?></button>
                    <div class="ytrip-faq-item__a"><?php echo wp_kses_post( $faq['answer'] ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sticky Bottom Bar -->
    <div class="ytrip-sticky-bottom-bar">
        <div class="ytrip-container">
            <div class="ytrip-sticky-bottom-bar__inner">
                <div class="ytrip-sticky-bottom-bar__price">
                    <?php if ( $product ) : ?>
                    <span class="ytrip-sticky-bottom-bar__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
                    <span class="ytrip-sticky-bottom-bar__amount"><?php echo $product->get_price_html(); ?></span>
                    <?php endif; ?>
                </div>
                <a href="#book-now" class="ytrip-btn ytrip-btn-primary"><?php esc_html_e( 'Book Now', 'ytrip' ); ?></a>
            </div>
        </div>
    </div>
    
</article>

</div><!-- .ytrip-single-tour-page -->

<?php if ( function_exists( 'ytrip_render_related_tours' ) ) : ?>
	<?php ytrip_render_related_tours( $tour_id ); ?>
<?php endif; ?>

<?php get_footer(); ?>
