<?php
/**
 * Skeleton Card Template
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$card_style = function_exists( 'ytrip_get_card_style' ) ? ytrip_get_card_style() : 'style_3';
?>

<article class="ytrip-card ytrip-skeleton-card ytrip-card--<?php echo esc_attr( str_replace('style_', 'style-', $card_style) ); ?>">
    <div class="ytrip-skeleton-image"></div>
    <div class="ytrip-skeleton-content">
        <div class="ytrip-skeleton-text ytrip-skeleton-tag"></div>
        <div class="ytrip-skeleton-text ytrip-skeleton-title"></div>
        <div class="ytrip-skeleton-text ytrip-skeleton-meta"></div>
        <div class="ytrip-skeleton-footer">
            <div class="ytrip-skeleton-text ytrip-skeleton-price"></div>
            <div class="ytrip-skeleton-btn"></div>
        </div>
    </div>
</article>
