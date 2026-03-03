<?php
/**
 * Single Tour Layout 2: Modern Hero
 * Hero-centric design with embedded booking widget
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id    = get_the_ID();
$meta       = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$meta       = is_array( $meta ) ? $meta : array();
$options    = get_option( 'ytrip_settings', array() );
$options    = is_array( $options ) ? $options : array();
$show_tab_icons = ! empty( $options['single_tabs_show_icons'] );
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );

// Gallery / hero images (same as layout-1 for slider when 2+ images)
$gallery_ids = ! empty( $meta['tour_gallery'] ) ? array_filter( array_map( 'absint', explode( ',', $meta['tour_gallery'] ) ) ) : array();
if ( empty( $gallery_ids ) && has_post_thumbnail( $tour_id ) ) {
	$gallery_ids = array( get_post_thumbnail_id( $tour_id ) );
}
$settings       = get_option( 'ytrip_settings', array() );
$hero_mode      = isset( $meta['hero_gallery_mode'] ) ? sanitize_key( $meta['hero_gallery_mode'] ) : '';
$legacy_hero    = isset( $meta['single_hero_type'] ) ? sanitize_key( $meta['single_hero_type'] ) : 'single_image';
$legacy_gallery = isset( $meta['gallery_display_mode'] ) ? sanitize_key( $meta['gallery_display_mode'] ) : '';
if ( $hero_mode === '' ) {
	if ( $legacy_hero === 'slider_carousel' && $legacy_gallery === 'carousel' ) {
		$hero_mode = 'carousel';
	} elseif ( $legacy_hero === 'slider_carousel' ) {
		$hero_mode = 'slider';
	} else {
		$hero_mode = 'single_image';
	}
}
$hero_images       = $gallery_ids;
$hero_count        = count( $hero_images );
if ( $hero_count > 1 && $hero_mode === 'single_image' ) {
	$hero_mode = isset( $settings['single_hero_gallery_mode'] ) ? sanitize_key( $settings['single_hero_gallery_mode'] ) : 'slider';
}
$hero_is_slider    = ( $hero_mode === 'slider' || $hero_mode === 'carousel' ) && $hero_count > 1;
$hero_gallery_mode = $hero_is_slider ? $hero_mode : 'slider';
$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

// Get taxonomy data
$destinations = get_the_terms( $tour_id, 'ytrip_destination' );
$destination_name = ( $destinations && ! is_wp_error( $destinations ) ) ? $destinations[0]->name : '';
$categories = get_the_terms( $tour_id, 'ytrip_category' );
$category_name = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';

get_header();

include YTRIP_PATH . 'templates/parts/single-tour-brand-vars.php';
?>

<!-- Critical Inline Styles -->
<style>
    /* Force Full Width on Astra */
    .ast-container, .site-content .ast-container {
        max-width: 100% !important;
        padding: 0 !important;
        display: block !important;
    }
    #primary, #secondary {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        float: none !important;
        display: block !important;
    }
    #secondary, .widget-area { display: none !important; }
    .ytrip-tour-premium { width: 100% !important; max-width: 100% !important; }
    
    /* Layout 2 Specifics */
    .ytrip-layout-2 .ytrip-hero {
        height: 600px;
        position: relative;
        margin-bottom: 40px;
    }
    .ytrip-layout-2 .ytrip-hero.ytrip-hero-slider {
        min-height: 600px;
        overflow: hidden;
    }
    .ytrip-layout-2 .ytrip-hero__overlay {
        background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 100%);
        z-index: 1;
    }
    .ytrip-layout-2 .ytrip-hero__content {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 40px 0;
        z-index: 2;
        color: white;
    }
    .ytrip-layout-2 .ytrip-tour-title {
        color: white;
        font-size: 3rem;
        margin-bottom: 1rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .ytrip-layout-2 .ytrip-meta-item {
        color: rgba(255,255,255,0.9);
    }
    .ytrip-layout-2 .ytrip-hero__floating-booking {
        position: absolute;
        right: 40px;
        bottom: -100px;
        width: 380px;
        z-index: 10;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    @media (max-width: 1024px) {
        .ytrip-layout-2 .ytrip-hero__floating-booking {
            position: relative;
            right: auto;
            bottom: auto;
            width: 100%;
            margin-top: 20px;
            z-index: 1;
        }
        .ytrip-layout-2 .ytrip-hero { height: auto; min-height: 400px; }
    }
</style>

<article class="ytrip-tour ytrip-tour--premium ytrip-layout-2" id="ytrip-tour-<?php echo esc_attr( $tour_id ); ?>">

    <!-- Hero Section: slider/carousel when 2+ images, else single image -->
    <?php if ( $hero_is_slider ) : ?>
    <section class="ytrip-hero ytrip-single-hero ytrip-single-hero--fullwidth ytrip-hero-slider swiper ytrip-layout-2-hero" data-hero-mode="<?php echo esc_attr( $hero_gallery_mode ); ?>">
        <div class="ytrip-hero__bg ytrip-hero-bg swiper-wrapper">
            <?php foreach ( $hero_images as $i => $img_id ) : ?>
                <div class="swiper-slide ytrip-hero-slide-bg">
                    <?php echo ytrip_hero_image_with_skeleton( (int) $img_id, $i === 0 ); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-pagination ytrip-single-hero-pagination"></div>
        <div class="swiper-button-next ytrip-single-hero-next"></div>
        <div class="swiper-button-prev ytrip-single-hero-prev"></div>
        <div class="ytrip-hero__overlay ytrip-hero-overlay"></div>
        <div class="ytrip-hero__content">
            <div class="ytrip-container">
                <div class="ytrip-hero__info" style="max-width: 700px;">
                    <nav class="ytrip-breadcrumb" style="color:rgba(255,255,255,0.8); margin-bottom:1rem;">
                        <a href="<?php echo esc_url( home_url() ); ?>" style="color:inherit;"><?php esc_html_e( 'Home', 'ytrip' ); ?></a> / 
                        <span><?php the_title(); ?></span>
                    </nav>
                    <h1 class="ytrip-tour-title"><?php the_title(); ?></h1>
                    <div class="ytrip-tour-meta" style="color:white;">
                        <?php if ( $destination_name ) : ?>
                        <span class="ytrip-meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ytrip-warning);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html( $destination_name ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                        <span class="ytrip-meta-item">
                            <span style="color:#f59e0b;">★</span>
                            <strong><?php echo esc_html( number_format( $product->get_average_rating(), 1 ) ); ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ytrip-hero__floating-booking" id="ytrip-booking-widget">
                    <?php include YTRIP_PATH . 'templates/parts/booking-card.php'; ?>
                </div>
            </div>
        </div>
    </section>
    <?php else : ?>
    <section class="ytrip-hero">
        <div class="ytrip-hero__main" style="height: 100%; border-radius: 0;">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php
                $hero_img = get_the_post_thumbnail(
                    get_the_ID(),
                    'full',
                    array(
                        'class'         => 'ytrip-hero__img',
                        'loading'       => 'eager',
                        'decoding'      => 'async',
                        'fetchpriority' => 'high',
                    )
                );
                echo ytrip_wrap_image_with_skeleton( $hero_img, true );
                ?>
            <?php else : ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#2563eb,#1d4ed8);"></div>
            <?php endif; ?>
            <div class="ytrip-hero__overlay"></div>
        </div>
        <div class="ytrip-hero__content">
            <div class="ytrip-container">
                <div class="ytrip-hero__info" style="max-width: 700px;">
                    <nav class="ytrip-breadcrumb" style="color:rgba(255,255,255,0.8); margin-bottom:1rem;">
                        <a href="<?php echo esc_url( home_url() ); ?>" style="color:inherit;"><?php esc_html_e( 'Home', 'ytrip' ); ?></a> / 
                        <span><?php the_title(); ?></span>
                    </nav>
                    <h1 class="ytrip-tour-title"><?php the_title(); ?></h1>
                    <div class="ytrip-tour-meta" style="color:white;">
                        <?php if ( $destination_name ) : ?>
                        <span class="ytrip-meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ytrip-warning);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html( $destination_name ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                        <span class="ytrip-meta-item">
                            <span style="color:#f59e0b;">★</span>
                            <strong><?php echo esc_html( number_format( $product->get_average_rating(), 1 ) ); ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ytrip-hero__floating-booking" id="ytrip-booking-widget">
                    <?php include YTRIP_PATH . 'templates/parts/booking-card.php'; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Quick Info Section (Offset to clear booking widget) -->
    <section class="ytrip-quick-info" style="padding-top: 60px;">
        <div class="ytrip-container">
            <div class="ytrip-quick-info__grid">
                <!-- Using same logic as Layout 1 for cards -->
                 <?php if ( ! empty( $meta['duration'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                    <div class="ytrip-quick-card__content"><span class="ytrip-quick-card__label">Duration</span><span class="ytrip-quick-card__value"><?php echo esc_html( $meta['duration'] ); ?></span></div>
                </div>
                <?php endif; ?>
                <!-- Group Size -->
                <?php if ( ! empty( $meta['group_size'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="ytrip-quick-card__content"><span class="ytrip-quick-card__label">Group Size</span><span class="ytrip-quick-card__value"><?php echo esc_html( is_array( $meta['group_size'] ) ? implode( ' - ', $meta['group_size'] ) : $meta['group_size'] ); ?></span></div>
                </div>
                <?php endif; ?>
                <!-- Languages -->
                <?php if ( ! empty( $meta['languages'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg></div>
                    <div class="ytrip-quick-card__content"><span class="ytrip-quick-card__label">Languages</span><span class="ytrip-quick-card__value"><?php echo esc_html( is_array( $meta['languages'] ) ? implode( ', ', $meta['languages'] ) : $meta['languages'] ); ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Main Content (One Column) -->
    <section class="ytrip-main" style="padding-top: 40px;">
        <div class="ytrip-container">
            <div class="ytrip-content" style="max-width: 800px;">
                <!-- Reusing Tabs Logic from Layout 1 -->
                <nav class="ytrip-tabs" role="tablist">
                    <button class="ytrip-tabs__btn active" data-tab="overview">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'overview' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'Overview', 'ytrip' ); ?></span>
                    </button>
                    <button class="ytrip-tabs__btn" data-tab="itinerary">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'itinerary' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'Itinerary', 'ytrip' ); ?></span>
                    </button>
                    <button class="ytrip-tabs__btn" data-tab="included">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'included' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'Included', 'ytrip' ); ?></span>
                    </button>
                    <button class="ytrip-tabs__btn" data-tab="faq">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'faq' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'FAQ', 'ytrip' ); ?></span>
                    </button>
                </nav>
                
                <div class="ytrip-panels">
                    <!-- Overview -->
                    <div class="ytrip-panel active" id="panel-overview">
                        <div class="ytrip-panel__content"><?php the_content(); ?></div>
                        <?php if ( ! empty( $meta['highlights'] ) && is_array( $meta['highlights'] ) ) : ?>
                        <div class="ytrip-highlights">
                            <h3>Tour Highlights</h3>
                            <ul class="ytrip-highlights__list">
                                <?php foreach ( $meta['highlights'] as $highlight ) : 
                                    $text = is_array( $highlight ) ? ( $highlight['highlight'] ?? '' ) : $highlight;
                                    if(empty($text)) continue; ?>
                                <li><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php echo esc_html( $text ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Itinerary (Simplified for brevity) -->
                     <?php if ( ! empty( $meta['itinerary'] ) && is_array( $meta['itinerary'] ) ) : ?>
                    <div class="ytrip-panel" id="panel-itinerary">
                        <div class="ytrip-itinerary">
                            <?php foreach ( $meta['itinerary'] as $i => $day ) : 
                                $title = is_array($day) ? ($day['title']??'') : ''; $desc = is_array($day)?($day['description']??'') : ''; ?>
                            <div class="ytrip-itinerary__day">
                                <div class="ytrip-itinerary__marker"><span class="ytrip-itinerary__number"><?php echo ($i+1); ?></span></div>
                                <div class="ytrip-itinerary__content"><h4 class="ytrip-itinerary__title">Day <?php echo ($i+1); ?>: <?php echo esc_html($title); ?></h4><div class="ytrip-itinerary__desc"><?php echo wp_kses_post($desc); ?></div></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Included/Excluded (Simplified) -->
                    <div class="ytrip-panel" id="panel-included">
                         <div class="ytrip-inc-exc">
                            <div class="ytrip-inc-exc__col ytrip-inc-exc__col--yes">
                                <h3>What's Included</h3>
                                <?php if(!empty($meta['included']) && is_array($meta['included'])): ?>
                                <ul><?php foreach($meta['included'] as $x): $t = is_array($x)?($x['item']??''):$x; if($t){ echo "<li>".esc_html($t)."</li>"; } endforeach; ?></ul>
                                <?php endif; ?>
                            </div>
                            <div class="ytrip-inc-exc__col ytrip-inc-exc__col--no">
                                <h3>Not Included</h3>
                                <?php if(!empty($meta['excluded']) && is_array($meta['excluded'])): ?>
                                <ul><?php foreach($meta['excluded'] as $x): $t = is_array($x)?($x['item']??''):$x; if($t){ echo "<li>".esc_html($t)."</li>"; } endforeach; ?></ul>
                                <?php endif; ?>
                            </div>
                         </div>
                    </div>
                    
                    <!-- FAQ (Simplified) -->
                     <?php if ( ! empty( $meta['faq'] ) && is_array( $meta['faq'] ) ) : ?>
                    <div class="ytrip-panel" id="panel-faq">
                        <div class="ytrip-faq">
                            <?php foreach($meta['faq'] as $f): $q = is_array($f)?($f['question']??''):''; $a = is_array($f)?($f['answer']??''):''; if($q): ?>
                            <details class="ytrip-faq__item"><summary class="ytrip-faq__question"><?php echo esc_html($q); ?></summary><div class="ytrip-faq__answer"><?php echo wp_kses_post($a); ?></div></details>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar column removed for layout 2, booking is floating -->
        </div>
    </section>

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

<script>
// Tab Navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ytrip-tabs__btn');
    const panels = document.querySelectorAll('.ytrip-panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            tabs.forEach(t => { t.classList.remove('active'); });
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('panel-' + target)?.classList.add('active');
        });
    });
});
</script>

<?php if ( is_active_sidebar( 'ytrip-single-tour' ) ) : ?>
<section class="ytrip-section ytrip-single-tour-widget-area-section">
    <div class="ytrip-container">
        <div class="ytrip-single-tour-widget-area ytrip-widget-area">
            <?php dynamic_sidebar( 'ytrip-single-tour' ); ?>
        </div>
    </div>
</section>
<?php endif; ?>

</div><!-- .ytrip-single-tour-page -->

<?php if ( function_exists( 'ytrip_render_related_tours' ) ) : ?>
	<?php ytrip_render_related_tours( $tour_id ); ?>
<?php endif; ?>

<?php get_footer(); ?>
