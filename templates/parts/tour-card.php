<?php
/**
 * Tour Card Part
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$meta = is_array( $meta ) ? $meta : array();
$product = null;
if ( function_exists( 'wc_get_product' ) ) {
	$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
	$product = $product_id ? wc_get_product( (int) $product_id ) : null;
}
$price_html = '';
if ( $product && is_callable( array( $product, 'get_price_html' ) ) ) {
	$price_html = $product->get_price_html();
}
if ( $price_html === '' ) {
	$raw_price = get_post_meta( $tour_id, '_ytrip_price', true );
	if ( $raw_price !== '' && is_numeric( $raw_price ) ) {
		$price_html = class_exists( 'YTrip_Helper' ) && method_exists( 'YTrip_Helper', 'format_price_display' )
			? YTrip_Helper::format_price_display( $raw_price )
			: number_format_i18n( floatval( $raw_price ), 2 ) . ' €';
	}
}
$terms = get_the_terms( $tour_id, 'ytrip_destination' );
$destination = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
$duration_str  = class_exists( 'YTrip_Helper' ) && method_exists( 'YTrip_Helper', 'format_duration_from_meta' ) ? YTrip_Helper::format_duration_from_meta( $meta ) : ( is_array( $meta['duration'] ?? null ) ? '' : ( (string) ( $meta['duration'] ?? '' ) ) );
$group_size_str = class_exists( 'YTrip_Helper' ) && method_exists( 'YTrip_Helper', 'format_group_size_from_meta' ) ? YTrip_Helper::format_group_size_from_meta( $meta ) : ( is_array( $meta['group_size'] ?? null ) ? ( ( (int) ( $meta['group_size']['min'] ?? 0 ) ) . ' – ' . ( (int) ( $meta['group_size']['max'] ?? 0 ) ) ) : ( (string) ( $meta['group_size'] ?? '' ) ) );
?>

<div class="ytrip-tour-card">
    <div class="ytrip-tour-card__image">
        <?php if ( has_post_thumbnail() ) : ?>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="ytrip-tour-card__image-link" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
                <?php the_post_thumbnail( 'medium_large' ); ?>
            </a>
        <?php else : ?>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="ytrip-tour-card__image-link ytrip-tour-card__placeholder" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
                <?php 
                if ( function_exists( 'wc_placeholder_img' ) && is_callable( 'wc_placeholder_img' ) ) {
                    echo wc_placeholder_img( 'medium_large' );
                } else {
                    echo '<div class="ytrip-placeholder-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>';
                }
                ?>
            </a>
        <?php endif; ?>
        
        <?php if ( ! empty( $meta['featured'] ) ) : ?>
            <span class="ytrip-tour-card__badge"><?php esc_html_e( 'Featured', 'ytrip' ); ?></span>
        <?php endif; ?>
        
        <button type="button" class="ytrip-tour-card__wishlist ytrip-wishlist-btn" data-tour-id="<?php echo esc_attr( $tour_id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'ytrip' ); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
    </div>
    
    <div class="ytrip-tour-card__content">
        <?php if ( $destination ) : ?>
            <div class="ytrip-tour-card__location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo esc_html( $destination ); ?>
            </div>
        <?php endif; ?>
        
        <h3 class="ytrip-tour-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <div class="ytrip-tour-card__meta">
            <?php if ( $duration_str !== '' ) : ?>
                <span class="ytrip-tour-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <?php echo esc_html( $duration_str ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( $group_size_str !== '' ) : ?>
                <span class="ytrip-tour-card__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <?php echo esc_html( $group_size_str ); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ( $product && $product->get_review_count() > 0 ) : ?>
            <div class="ytrip-tour-card__rating">
                <div class="ytrip-tour-card__stars">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i <= round( $product->get_average_rating() ) ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?php endfor; ?>
                </div>
                <span class="ytrip-tour-card__rating-text">(<?php echo esc_html( $product->get_review_count() ); ?>)</span>
            </div>
        <?php endif; ?>
        
        <div class="ytrip-tour-card__footer">
            <div class="ytrip-tour-card__price">
                <span class="ytrip-tour-card__price-value">
                    <?php echo $price_html ? wp_kses_post( $price_html ) : '—'; ?>
                </span>
            </div>
            <a href="<?php the_permalink(); ?>" class="ytrip-btn ytrip-btn-sm ytrip-btn-primary">
                <?php esc_html_e( 'View Details', 'ytrip' ); ?>
            </a>
        </div>
    </div>
</div>
