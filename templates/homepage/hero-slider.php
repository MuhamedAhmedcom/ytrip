<?php
/**
 * Hero Slider Section
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$options = get_option( 'ytrip_homepage' );
$slides = isset( $options['hero_slides'] ) ? $options['hero_slides'] : array();
$homepage_width = isset( $options['homepage_width'] ) ? sanitize_html_class( $options['homepage_width'] ) : 'wide';

// Overlay color and opacity
$overlay_hex = isset( $options['hero_overlay_color'] ) ? sanitize_hex_color( $options['hero_overlay_color'] ) : '#000000';
$overlay_opacity = isset( $options['hero_overlay_opacity'] ) ? min( 100, max( 0, (float) $options['hero_overlay_opacity'] ) ) / 100 : 0.5;
$overlay_rgba = 'rgba(0,0,0,0.5)';
if ( $overlay_hex ) {
    $hex = ltrim( $overlay_hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) === 6 ) {
        $rgb = array_map( 'hexdec', str_split( $hex, 2 ) );
        $overlay_rgba = 'rgba(' . implode( ',', $rgb ) . ',' . $overlay_opacity . ')';
    }
}

// If no slides configured, show a default slide
if ( empty( $slides ) ) {
    $slides = array(
        array(
            'slide_title' => __('Discover Your Next Adventure', 'ytrip'),
            'slide_subtitle' => __('Explore the world with our premium travel experiences', 'ytrip'),
        )
    );
}
?>

<style type="text/css">
.ytrip-hero-slider .ytrip-hero__overlay {
    background: <?php echo esc_attr( $overlay_rgba ); ?> !important;
}
</style>

<?php
// LCP: preload first slide image (use large size when attachment ID present for better delivery).
$first_slide = ! empty( $slides[0] ) ? $slides[0] : null;
$first_slide_url = '';
if ( $first_slide && ! empty( $first_slide['slide_image']['id'] ) ) {
    $first_slide_url = wp_get_attachment_image_url( (int) $first_slide['slide_image']['id'], 'large' );
}
if ( empty( $first_slide_url ) && $first_slide && ! empty( $first_slide['slide_image']['url'] ) ) {
    $first_slide_url = $first_slide['slide_image']['url'];
}
if ( $first_slide_url && ! is_admin() ) :
?>
<link rel="preload" href="<?php echo esc_url( $first_slide_url ); ?>" as="image">
<?php endif; ?>
<section class="ytrip-hero swiper-container ytrip-hero-slider ytrip-hero--width-<?php echo esc_attr( $homepage_width ); ?>" style="--ytrip-hero-aspect: 21/9; min-height: 100vh;">
    <div class="swiper-wrapper">
        <?php foreach ( $slides as $idx => $slide ) : ?>
            <div class="swiper-slide ytrip-hero__slide">
                <div class="ytrip-hero__bg ytrip-hero__bg--ratio">
                    <?php
                    $first = ( $idx === 0 );
                    $alt   = esc_attr( $slide['slide_title'] ?? __( 'Hero slide', 'ytrip' ) );
                    // Codestar media: array( 'id' => x, 'url' => '...' ). Also support scalar id.
                    $slide_img = isset( $slide['slide_image'] ) ? $slide['slide_image'] : array();
                    $attach_id = isset( $slide_img['id'] ) ? (int) $slide_img['id'] : ( is_numeric( $slide_img ) ? (int) $slide_img : 0 );
                    $img_url   = isset( $slide_img['url'] ) ? $slide_img['url'] : '';
                    if ( $attach_id && ! $img_url ) {
                        $img_url = wp_get_attachment_image_url( $attach_id, 'large' );
                    }
                    $has_image = ( $attach_id && $img_url ) || ( ! empty( $img_url ) && is_string( $img_url ) );
                    if ( $has_image && $img_url ) :
                        $img_url = esc_url( $img_url );
                        $img_html = '';
                        if ( $attach_id ) {
                            $img_attrs = array(
                                'alt'      => $alt,
                                'loading'  => $first ? 'eager' : 'lazy',
                                'decoding' => 'async',
                                'sizes'    => '(max-width: 768px) 100vw, (max-width: 1920px) 100vw, 1920px',
                            );
                            if ( $first ) {
                                $img_attrs['fetchpriority'] = 'high';
                            }
                            $meta = wp_get_attachment_metadata( $attach_id );
                            if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
                                $img_attrs['width']  = (int) $meta['width'];
                                $img_attrs['height'] = (int) $meta['height'];
                            } else {
                                $img_attrs['width']  = 1920;
                                $img_attrs['height'] = 823;
                            }
                            $img_html = wp_get_attachment_image( $attach_id, 'large', false, $img_attrs );
                        }
                        if ( $img_html === '' ) {
                            $img_html = sprintf(
                                '<img src="%1$s" alt="%2$s" width="1920" height="823" sizes="(max-width: 768px) 100vw, (max-width: 1920px) 100vw, 1920px" %3$s>',
                                $img_url,
                                $alt,
                                $first ? 'fetchpriority="high" loading="eager" decoding="async"' : 'loading="lazy" decoding="async"'
                            );
                        }
                        echo $img_html;
                    else :
                    ?>
                        <div class="ytrip-hero__placeholder" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 100%; height: 100%;"></div>
                    <?php endif; ?>
                </div>
                <div class="ytrip-hero__overlay"></div>
                
                <div class="ytrip-hero__content">
                    <span class="ytrip-hero__badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php esc_html_e( 'Premium Travel Experience', 'ytrip' ); ?>
                    </span>
                    
                    <h1 class="ytrip-hero__title">
                        <?php echo ! empty( $slide['slide_title'] ) ? wp_kses_post( $slide['slide_title'] ) : esc_html__( 'Explore the World', 'ytrip' ); ?>
                    </h1>
                    
                    <p class="ytrip-hero__subtitle">
                        <?php echo ! empty( $slide['slide_subtitle'] ) ? esc_html( $slide['slide_subtitle'] ) : ''; ?>
                    </p>
                    
                    <?php if ( ! empty( $slide['button_1']['text'] ) ) : ?>
                        <div class="ytrip-hero__cta">
                            <?php
                            $cta_url   = $slide['button_1']['link']['url'] ?? get_post_type_archive_link( 'ytrip_tour' );
                            $cta_text  = $slide['button_1']['text'];
                            $cta_label = $cta_text;
                            if ( count( $slides ) > 1 && ! empty( $slide['slide_title'] ) ) {
                                $cta_label = sprintf( __( '%1$s: %2$s', 'ytrip' ), $cta_text, $slide['slide_title'] );
                            }
                            ?>
                            <a href="<?php echo esc_url( $cta_url ); ?>"
                               class="ytrip-btn ytrip-btn-<?php echo esc_attr( $slide['button_1']['style'] ?? 'primary' ); ?> ytrip-btn-lg"
                               aria-label="<?php echo esc_attr( $cta_label ); ?>">
                                <?php echo esc_html( $cta_text ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Slider Controls (accessible labels for Lighthouse) -->
    <div class="swiper-pagination" role="group" aria-label="<?php esc_attr_e( 'Slide pagination', 'ytrip' ); ?>"></div>
    <button type="button" class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next slide', 'ytrip' ); ?>"></button>
    <button type="button" class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'ytrip' ); ?>"></button>
</section>
