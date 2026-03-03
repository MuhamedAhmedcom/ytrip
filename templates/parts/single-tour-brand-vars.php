<?php
/**
 * Single tour brand color variables and wrapper styles
 * Ensures Color Preset and Quick Color Palette from YTrip Settings are reflected.
 * Include once at the start of a single-tour layout; close with </div> before get_footer().
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$primary_color = '#2563eb';
$secondary_color = '';
$accent_color   = '';

if ( function_exists( 'ytrip_get_color' ) ) {
	$primary_color   = ytrip_get_color( 'primary' );
	$secondary_color = ytrip_get_color( 'secondary' );
	$accent_color   = ytrip_get_color( 'accent' );
} else {
	$theme_settings  = get_option( 'ytrip_settings', [] );
	$custom_colors   = $theme_settings['custom_colors'] ?? [];
	$primary_color   = $custom_colors['primary'] ?? $theme_settings['opt_color_primary'] ?? $primary_color;
	$secondary_color = $custom_colors['secondary'] ?? $theme_settings['opt_color_secondary'] ?? '';
	$accent_color   = $custom_colors['accent'] ?? $theme_settings['opt_color_accent'] ?? '';
}

$hex = ltrim( $primary_color, '#' );
if ( strlen( $hex ) === 3 ) {
	$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
}
$primary_rgb     = array( 37, 99, 235 );
if ( strlen( $hex ) === 6 && preg_match( '/^[a-fA-F0-9]{6}$/', $hex ) ) {
	$primary_rgb = array(
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
	);
}
$primary_rgb_str = implode( ',', $primary_rgb );
$primary_hover   = ! empty( $secondary_color ) ? $secondary_color : '#' . sprintf( '%02x%02x%02x', max( 0, $primary_rgb[0] - 28 ), max( 0, $primary_rgb[1] - 28 ), max( 0, $primary_rgb[2] - 28 ) );
$primary_light   = 'rgba(' . $primary_rgb_str . ',0.12)';

// WCAG 2.1 contrast: text on primary background (black or white for ≥4.5:1).
if ( function_exists( 'ytrip_brand' ) && method_exists( ytrip_brand(), 'get_contrast_color' ) ) {
	$primary_contrast = ytrip_brand()->get_contrast_color( $primary_color );
} else {
	$r = $primary_rgb[0] / 255;
	$g = $primary_rgb[1] / 255;
	$b = $primary_rgb[2] / 255;
	$r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
	$g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
	$b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );
	$luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	$primary_contrast = $luminance <= 0.183 ? '#ffffff' : '#000000';
}
?>
<div class="ytrip-single-tour-page">
<style>
.ytrip-single-tour-page {
    --ytrip-primary: <?php echo esc_attr( $primary_color ); ?>;
    --ytrip-primary-rgb: <?php echo esc_attr( $primary_rgb_str ); ?>;
    --ytrip-primary-hover: <?php echo esc_attr( $primary_hover ); ?>;
    --ytrip-primary-light: <?php echo esc_attr( $primary_light ); ?>;
    --ytrip-primary-contrast: <?php echo esc_attr( $primary_contrast ); ?>;
    <?php if ( ! empty( $secondary_color ) ) : ?>
    --ytrip-secondary: <?php echo esc_attr( $secondary_color ); ?>;
    <?php endif; ?>
    <?php if ( ! empty( $accent_color ) ) : ?>
    --ytrip-accent: <?php echo esc_attr( $accent_color ); ?>;
    <?php endif; ?>
    --ytrip-surface: #ffffff;
    --ytrip-surface-alt: #f8fafc;
    --ytrip-text: #1e293b;
    --ytrip-text-muted: #64748b;
    --ytrip-border: #e5e7eb;
}
.ytrip-single-tour-page .ytrip-booking-fab__btn {
    background: var(--ytrip-primary) !important;
    color: var(--ytrip-primary-contrast) !important;
    box-shadow: 0 8px 24px rgba(var(--ytrip-primary-rgb), 0.35);
}
.ytrip-single-tour-page .ytrip-booking-fab__btn:hover {
    background: var(--ytrip-primary-hover) !important;
    box-shadow: 0 12px 32px rgba(var(--ytrip-primary-rgb), 0.4);
}
.ytrip-single-tour-page .ytrip-btn-book,
.ytrip-single-tour-page .ytrip-btn--primary {
    background: var(--ytrip-primary) !important;
    box-shadow: 0 4px 14px rgba(var(--ytrip-primary-rgb), 0.25);
}
.ytrip-single-tour-page .ytrip-btn-book:hover,
.ytrip-single-tour-page .ytrip-btn--primary:hover {
    background: var(--ytrip-primary-hover) !important;
    box-shadow: 0 8px 20px rgba(var(--ytrip-primary-rgb), 0.35);
}
.ytrip-single-tour-page .ytrip-booking-card,
.ytrip-single-tour-page .ytrip-sidebar .ytrip-card {
    border-top: 3px solid var(--ytrip-primary);
    box-shadow: 0 20px 40px -12px rgba(0,0,0,0.12), 0 0 0 1px rgba(var(--ytrip-primary-rgb),0.08);
}
.ytrip-single-tour-page .ytrip-section-title,
.ytrip-single-tour-page .ytrip-tabs__btn.active,
.ytrip-single-tour-page .ytrip-inc-exc__col h3 {
    border-inline-start: 4px solid var(--ytrip-primary);
    padding-inline-start: 16px;
}
.ytrip-single-tour-page .ytrip-form-group input:focus,
.ytrip-single-tour-page .ytrip-form-group select:focus {
    border-color: var(--ytrip-primary);
    box-shadow: 0 0 0 4px rgba(var(--ytrip-primary-rgb), 0.15);
}
.ytrip-single-tour-page .ytrip-number-btn:hover {
    background: var(--ytrip-primary);
    color: var(--ytrip-primary-contrast);
}
.ytrip-single-tour-page .ytrip-btn-outline:hover {
    border-color: var(--ytrip-primary);
    color: var(--ytrip-primary);
}
.ytrip-single-tour-page a:not(.ytrip-hero a):not(.ytrip-breadcrumb a) {
    color: var(--ytrip-primary);
}
.ytrip-single-tour-page a:not(.ytrip-hero a):not(.ytrip-breadcrumb a):hover {
    color: var(--ytrip-primary-hover);
}
</style>
