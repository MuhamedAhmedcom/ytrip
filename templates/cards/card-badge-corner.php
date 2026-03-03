<?php
/**
 * Card Style 8: Badge Corner
 * Conversion-focused with prominent badges
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$meta = is_array( $meta ) ? $meta : array();
$options = get_option( 'ytrip_settings' );
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( (int) $product_id ) : null;
$price_html = '';
if ( $product && is_callable( array( $product, 'get_price_html' ) ) ) {
	$price_html = $product->get_price_html();
}
if ( $price_html === '' ) {
	$raw_price = get_post_meta( $tour_id, '_ytrip_price', true );
	if ( $raw_price !== '' && is_numeric( $raw_price ) ) {
		$price_html = class_exists( 'YTrip_Helper' ) && method_exists( 'YTrip_Helper', 'format_price_display' ) ? YTrip_Helper::format_price_display( $raw_price ) : number_format_i18n( floatval( $raw_price ), 2 ) . ' €';
	}
}
?>

<article class="ytrip-card ytrip-card--badge-corner" data-hover="<?php echo esc_attr( $options['card_hover_effect'] ?? 'lift' ); ?>">
    <a href="<?php the_permalink(); ?>" class="ytrip-card__link">
        <div class="ytrip-card__image">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large' ); ?>
            <?php endif; ?>
            
            <!-- Top Left Badge -->
            <?php if ( ! empty( $meta['featured'] ) ) : ?>
            <span class="ytrip-card__badge ytrip-card__badge--top-left ytrip-card__badge--featured">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?php esc_html_e( 'Featured', 'ytrip' ); ?>
            </span>
            <?php endif; ?>
            
            <!-- Bottom Left Badge -->
            <?php if ( $product && $product->is_on_sale() ) : ?>
            <span class="ytrip-card__badge ytrip-card__badge--bottom-left ytrip-card__badge--sale">
                <?php esc_html_e( 'Sale', 'ytrip' ); ?>
            </span>
            <?php endif; ?>
            
            <!-- Best Seller Badge -->
            <?php if ( ! empty( $meta['bestseller'] ) ) : ?>
            <span class="ytrip-card__badge ytrip-card__badge--top-right ytrip-card__badge--bestseller">
                💎 <?php esc_html_e( 'Bestseller', 'ytrip' ); ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="ytrip-card__body">
            <h3 class="ytrip-card__title"><?php the_title(); ?></h3>
            
            <?php if ( $product && $product->get_review_count() > 0 ) : ?>
            <div class="ytrip-card__rating">
                <span class="ytrip-stars"><?php echo str_repeat( '★', round( $product->get_average_rating() ) ); ?></span>
                <span class="ytrip-card__rating-text">
                    <?php echo esc_html( $product->get_average_rating() ); ?>
                    (<?php echo esc_html( $product->get_review_count() ); ?>)
                </span>
            </div>
            <?php endif; ?>
            
            <div class="ytrip-card__trust">
                <span class="ytrip-trust-icon" title="<?php esc_attr_e( 'Verified', 'ytrip' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <span class="ytrip-trust-icon" title="<?php esc_attr_e( 'Trusted', 'ytrip' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6L9 17l-5-5"/></svg>
                </span>
            </div>
            
            <div class="ytrip-card__footer">
                <div class="ytrip-card__price-block">
                    <span class="ytrip-card__price"><?php echo $price_html ? wp_kses_post( $price_html ) : '—'; ?></span>
                </div>
                
                <button class="ytrip-card__book-btn"><?php esc_html_e( 'Book Now', 'ytrip' ); ?></button>
            </div>
        </div>
    </a>
    
    <?php if ( ! isset( $options['card_show_wishlist'] ) || $options['card_show_wishlist'] ) : ?>
    <button type="button" class="ytrip-card__wishlist ytrip-wishlist-btn" data-tour-id="<?php echo esc_attr( $tour_id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'ytrip' ); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </button>
    <?php endif; ?>
</article>
