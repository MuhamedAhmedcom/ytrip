<?php
/**
 * Card Style 4: Minimal Border
 * Clean minimalist design with thin border
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

<article class="ytrip-card ytrip-card--minimal-border" data-hover="<?php echo esc_attr( $options['card_hover_effect'] ?? 'lift' ); ?>">
    <a href="<?php the_permalink(); ?>" class="ytrip-card__link">
        <div class="ytrip-card__image">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large' ); ?>
            <?php endif; ?>
        </div>
        
        <div class="ytrip-card__body">
            <h3 class="ytrip-card__title"><?php the_title(); ?></h3>
            
            <?php if ( has_excerpt() ) : ?>
            <p class="ytrip-card__excerpt"><?php echo wp_trim_words( get_the_excerpt(), 15 ); ?></p>
            <?php endif; ?>
            
            <div class="ytrip-card__footer">
                <span class="ytrip-card__price"><?php echo $price_html ? wp_kses_post( $price_html ) : '—'; ?></span>
                <span class="ytrip-card__arrow">→</span>
            </div>
        </div>
    </a>
</article>
