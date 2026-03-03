<?php
/**
 * Card Style 3: Modern Shadow
 * Floating card with deep shadow effect
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$options = get_option( 'ytrip_settings' );
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
$dest = get_the_terms( $tour_id, 'ytrip_destination' );
$duration_str = class_exists( 'YTrip_Helper' ) ? YTrip_Helper::format_duration_from_meta( $meta ) : ( is_array( $meta['duration'] ?? null ) ? '' : ( (string) ( $meta['duration'] ?? '' ) ) );
?>

<article class="ytrip-card ytrip-card--modern-shadow" data-hover="<?php echo esc_attr( $options['card_hover_effect'] ?? 'lift' ); ?>">
    <a href="<?php the_permalink(); ?>" class="ytrip-card__link">
        <div class="ytrip-card__image-wrap">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
            <?php endif; ?>
        </div>
        
        <div class="ytrip-card__floating-content">
            <?php if ( $dest && ! is_wp_error( $dest ) ) : ?>
            <span class="ytrip-card__tag"><?php echo esc_html( $dest[0]->name ); ?></span>
            <?php endif; ?>
            
            <h3 class="ytrip-card__title"><?php the_title(); ?></h3>
            
            <div class="ytrip-card__info">
                <?php if ( $duration_str !== '' ) : ?>
                <span><?php echo esc_html( $duration_str ); ?></span>
                <?php endif; ?>
                
                <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                <span>★ <?php echo esc_html( $product->get_average_rating() ); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="ytrip-card__action">
                <span class="ytrip-card__price"><?php echo $price_html ? wp_kses_post( $price_html ) : '—'; ?></span>
                <span class="ytrip-card__cta">
                    <?php esc_html_e( 'Book Now', 'ytrip' ); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </span>
            </div>
        </div>
    </a>
    
    <?php if ( ! isset( $options['card_show_wishlist'] ) || $options['card_show_wishlist'] ) : ?>
    <button type="button" class="ytrip-card__wishlist ytrip-wishlist-btn" data-tour-id="<?php echo esc_attr( $tour_id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'ytrip' ); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </button>
    <?php endif; ?>
</article>
