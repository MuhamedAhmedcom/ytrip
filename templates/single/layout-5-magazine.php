<?php
/**
 * Single Tour Layout 5: Magazine Style
 * Editorial/magazine feel with visual storytelling
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

<article class="ytrip-single ytrip-layout-magazine">
    
    <!-- Hero Header -->
    <header class="ytrip-magazine-hero">
        <div class="ytrip-magazine-hero__bg">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'full' ); ?>
            <?php endif; ?>
        </div>
        <div class="ytrip-magazine-hero__overlay"></div>
        
        <div class="ytrip-magazine-hero__content">
            <?php $dest = get_the_terms( $tour_id, 'ytrip_destination' ); ?>
            <?php if ( $dest && ! is_wp_error( $dest ) ) : ?>
            <span class="ytrip-magazine-hero__location">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo esc_html( $dest[0]->name ); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="ytrip-magazine-hero__title"><?php the_title(); ?></h1>
            
            <?php if ( has_excerpt() ) : ?>
            <p class="ytrip-magazine-hero__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <?php endif; ?>
            
            <div class="ytrip-magazine-hero__meta">
                <?php if ( ! empty( $meta['duration'] ) ) : ?>
                <span><?php echo esc_html( $meta['duration'] ); ?></span>
                <?php endif; ?>
                
                <?php if ( $product ) : ?>
                <span class="ytrip-magazine-hero__price"><?php esc_html_e( 'From', 'ytrip' ); ?> <?php echo $product->get_price_html(); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Quick Facts Strip -->
    <section class="ytrip-magazine-facts">
        <div class="ytrip-container">
            <div class="ytrip-magazine-facts__grid">
                <?php if ( ! empty( $meta['duration'] ) ) : ?>
                <div class="ytrip-magazine-facts__item">
                    <div class="ytrip-magazine-facts__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="ytrip-magazine-facts__text">
                        <span class="ytrip-magazine-facts__label"><?php esc_html_e( 'Duration', 'ytrip' ); ?></span>
                        <span class="ytrip-magazine-facts__value"><?php echo esc_html( $meta['duration'] ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ( ! empty( $meta['group_size'] ) ) : ?>
                <div class="ytrip-magazine-facts__item">
                    <div class="ytrip-magazine-facts__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div class="ytrip-magazine-facts__text">
                        <span class="ytrip-magazine-facts__label"><?php esc_html_e( 'Group Size', 'ytrip' ); ?></span>
                        <span class="ytrip-magazine-facts__value"><?php echo esc_html( $meta['group_size'] ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                <div class="ytrip-magazine-facts__item">
                    <div class="ytrip-magazine-facts__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="ytrip-magazine-facts__text">
                        <span class="ytrip-magazine-facts__label"><?php esc_html_e( 'Rating', 'ytrip' ); ?></span>
                        <span class="ytrip-magazine-facts__value"><?php echo esc_html( $product->get_average_rating() ); ?> / 5</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Social Share -->
                <div class="ytrip-magazine-facts__share">
                    <span><?php esc_html_e( 'Share', 'ytrip' ); ?></span>
                    <a href="https://facebook.com/sharer/sharer.php?u=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content with Sidebar Elements -->
    <section class="ytrip-magazine-content">
        <div class="ytrip-container">
            <div class="ytrip-magazine-layout">
                
                <!-- Main Column -->
                <div class="ytrip-magazine-main">
                    
                    <!-- Lead Text -->
                    <div class="ytrip-magazine-lead">
                        <?php the_content(); ?>
                    </div>

                    <!-- Highlights -->
                    <?php if ( ! empty( $meta['highlights'] ) ) : ?>
                    <div class="ytrip-magazine-highlights">
                        <h2><?php esc_html_e( 'Highlights', 'ytrip' ); ?></h2>
                        <div class="ytrip-magazine-highlights__list">
                            <?php foreach ( $meta['highlights'] as $h ) : ?>
                            <div class="ytrip-magazine-highlights__item">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span><?php echo esc_html( $h['highlight'] ); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                
                <!-- Side Elements (scroll target for CTA bar) -->
                <aside class="ytrip-magazine-side" id="ytrip-booking-widget">
                    <?php include YTRIP_PATH . 'templates/parts/booking-card.php'; ?>
                    <?php if ( is_active_sidebar( 'ytrip-single-tour' ) ) : ?>
                    <div class="ytrip-single-tour-widget-area ytrip-widget-area">
                        <?php dynamic_sidebar( 'ytrip-single-tour' ); ?>
                    </div>
                    <?php endif; ?>
                </aside>
                
            </div>
        </div>
    </section>

    <!-- Photo Grid -->
    <?php if ( ! empty( $meta['tour_gallery'] ) ) : ?>
    <section class="ytrip-magazine-gallery">
        <div class="ytrip-container">
            <h2 class="ytrip-magazine-section-title"><?php esc_html_e( 'Photo Gallery', 'ytrip' ); ?></h2>
            <div class="ytrip-magazine-gallery__grid">
                <?php 
                $gallery = explode( ',', $meta['tour_gallery'] );
                foreach ( $gallery as $i => $img_id ) :
                    $size = ( $i === 0 || $i === 3 ) ? 'large' : 'medium';
                ?>
                <div class="ytrip-magazine-gallery__item <?php echo $i === 0 ? 'ytrip-magazine-gallery__item--large' : ''; ?>">
                    <?php echo wp_get_attachment_image( $img_id, $size ); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Timeline Itinerary -->
    <?php if ( ! empty( $meta['itinerary'] ) ) : ?>
    <section class="ytrip-magazine-itinerary">
        <div class="ytrip-container ytrip-container--narrow">
            <h2 class="ytrip-magazine-section-title"><?php esc_html_e( 'Your Journey', 'ytrip' ); ?></h2>
            <div class="ytrip-magazine-timeline">
                <?php foreach ( $meta['itinerary'] as $i => $day ) : ?>
                <div class="ytrip-magazine-timeline__item">
                    <div class="ytrip-magazine-timeline__marker">
                        <span class="ytrip-magazine-timeline__day"><?php printf( esc_html__( 'Day %d', 'ytrip' ), $i + 1 ); ?></span>
                    </div>
                    <div class="ytrip-magazine-timeline__content">
                        <h4><?php echo esc_html( $day['title'] ); ?></h4>
                        <div><?php echo wp_kses_post( $day['description'] ); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Included/Excluded -->
    <section class="ytrip-magazine-inc-exc">
        <div class="ytrip-container ytrip-container--narrow">
            <div class="ytrip-magazine-inc-exc__grid">
                <div class="ytrip-magazine-inc-exc__box ytrip-magazine-inc-exc__box--yes">
                    <h3><?php esc_html_e( 'Included', 'ytrip' ); ?></h3>
                    <?php if ( ! empty( $meta['included'] ) ) : ?>
                    <ul>
                        <?php foreach ( $meta['included'] as $item ) : ?>
                        <li><?php echo esc_html( $item['item'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <div class="ytrip-magazine-inc-exc__box ytrip-magazine-inc-exc__box--no">
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

    <!-- Reviews Masonry -->
    <?php if ( $product && $product->get_review_count() > 0 ) : ?>
    <section class="ytrip-magazine-reviews">
        <div class="ytrip-container">
            <h2 class="ytrip-magazine-section-title"><?php esc_html_e( 'What Travelers Say', 'ytrip' ); ?></h2>
            <div class="ytrip-magazine-reviews__grid">
                <?php comments_template(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if ( ! empty( $meta['faq'] ) ) : ?>
    <section class="ytrip-magazine-faq">
        <div class="ytrip-container ytrip-container--narrow">
            <h2 class="ytrip-magazine-section-title"><?php esc_html_e( 'FAQ', 'ytrip' ); ?></h2>
            <div class="ytrip-magazine-faq__list">
                <?php foreach ( $meta['faq'] as $faq ) : ?>
                <details class="ytrip-magazine-faq__item">
                    <summary><?php echo esc_html( $faq['question'] ); ?></summary>
                    <div><?php echo wp_kses_post( $faq['answer'] ); ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sticky CTA bar when booking widget is below the fold -->
    <div class="ytrip-booking-cta-bar" id="ytrip-booking-cta-bar" aria-hidden="true">
        <div class="ytrip-booking-cta-bar__price">
            <span class="ytrip-booking-cta-bar__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
            <span class="ytrip-booking-cta-bar__amount" id="ytrip-cta-bar-amount"><?php echo $product ? $product->get_price_html() : ''; ?></span>
            <span class="ytrip-booking-cta-bar__per"><?php esc_html_e( 'per person', 'ytrip' ); ?></span>
            <span class="ytrip-booking-cta-bar__guests" id="ytrip-cta-bar-guests"></span>
        </div>
        <button type="button" class="ytrip-booking-cta-bar__btn ytrip-btn ytrip-btn--primary" id="ytrip-cta-bar-btn"><?php esc_html_e( 'Book Now', 'ytrip' ); ?></button>
    </div>
    
</article>

</div><!-- .ytrip-single-tour-page -->

<?php if ( function_exists( 'ytrip_render_related_tours' ) ) : ?>
	<?php ytrip_render_related_tours( $tour_id ); ?>
<?php endif; ?>

<?php get_footer(); ?>

