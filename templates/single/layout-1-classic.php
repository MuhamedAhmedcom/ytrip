<?php
/**
 * Single Tour Layout 1: Ultra-Modern Premium
 * Inspired by Viator, GetYourGuide, and top travel sites
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
$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

// Standardize terms and metadata strings
$destinations     = get_the_terms( $tour_id, 'ytrip_destination' );
$destination_name = ( ! empty( $destinations ) && ! is_wp_error( $destinations ) ) ? $destinations[0]->name : '';
$categories       = get_the_terms( $tour_id, 'ytrip_category' );
$category_name    = ( ! empty( $categories ) && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';

$duration_str = ytrip_get_meta_value_as_string( $meta, 'tour_duration' );
$group_str    = ytrip_get_meta_value_as_string( $meta, 'group_size' );
$location_str = ytrip_get_meta_value_as_string( $meta, 'tour_location' );

// Gallery / Hero images logic
$gallery_ids = ytrip_get_gallery_ids( $meta );
$thumb_id    = ytrip_get_effective_thumbnail_id( $tour_id, $meta );

// Build hero images array: thumbnail first, then remaining gallery (no duplicates).
$hero_images = array();
if ( $thumb_id ) {
	$hero_images[] = $thumb_id;
}
foreach ( $gallery_ids as $gid ) {
	if ( (int) $gid !== (int) $thumb_id ) {
		$hero_images[] = $gid;
	}
}

// ── HERO SLIDER: automatic Swiper when 2+ images ──
$hero_count      = count( $hero_images );
$hero_is_slider  = function_exists( 'ytrip_single_tour_needs_swiper' ) ? ytrip_single_tour_needs_swiper( $tour_id ) : ( $hero_count > 1 );
$hero_gallery_mode = $hero_is_slider ? ( isset( $options['single_hero_gallery_mode'] ) ? sanitize_key( $options['single_hero_gallery_mode'] ) : 'slider' ) : 'single_image';

get_header();

include YTRIP_PATH . 'templates/parts/single-tour-brand-vars.php';
?>

<!-- Critical Inline Styles for Layout Stability -->
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
    /* Hide Sidebars */
    #secondary, .widget-area {
        display: none !important;
    }
    /* Ensure Article is Full Width */
    .ytrip-tour-premium, article.ytrip-tour {
        width: 100% !important;
        max-width: 100% !important;
    }
</style>

<article class="ytrip-tour ytrip-tour--premium" id="ytrip-tour-<?php echo esc_attr( $tour_id ); ?>">

    <!-- =============================================
         HERO SECTION - Slider when 2+ images, else single image + grid
         ============================================= -->
    <?php if ( $hero_is_slider ) : ?>
    <section class="ytrip-hero ytrip-single-hero ytrip-single-hero--fullwidth ytrip-hero-slider swiper" data-hero-mode="<?php echo esc_attr( $hero_gallery_mode ); ?>">
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
    </section>
    <?php else : ?>
    <section class="ytrip-hero">
        <div class="ytrip-hero__gallery">
            <!-- Main Image -->
            <div class="ytrip-hero__main">
                <?php if ( ! empty( $hero_images ) ) : ?>
                    <?php
                    $main_img_id = $hero_images[0];
                    $hero_img = wp_get_attachment_image(
                        $main_img_id,
                        'large',
                        false,
                        array(
                            'class'          => 'ytrip-hero__img',
                            'loading'        => 'eager',
                            'decoding'       => 'async',
                            'fetchpriority'  => 'high',
                            'sizes'          => '(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1920px',
                        )
                    );
                    echo ytrip_wrap_image_with_skeleton( $hero_img, true );
                    ?>
                <?php endif; ?>
                <div class="ytrip-hero__overlay"></div>
            </div>
            
            <!-- Gallery Grid (Right Side) -->
            <?php if ( count( $gallery_ids ) >= 2 ) : ?>
            <div class="ytrip-hero__grid">
                <?php foreach ( array_slice( $gallery_ids, 0, 4 ) as $i => $img_id ) : ?>
                <div class="ytrip-hero__grid-item">
                    <?php
                    $grid_attrs = array( 'loading' => $i === 0 ? 'eager' : 'lazy', 'decoding' => 'async' );
                    echo wp_get_attachment_image( (int) $img_id, 'medium_large', false, $grid_attrs );
                    ?>
                </div>
                <?php endforeach; ?>
                
                <?php if ( count( $gallery_ids ) > 4 ) : ?>
                <button class="ytrip-hero__more" data-gallery="<?php echo esc_attr( $meta['tour_gallery'] ); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                    </svg>
                    <span><?php printf( esc_html__( 'View all %d photos', 'ytrip' ), count( $gallery_ids ) + 1 ); ?></span>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- =============================================
         BREADCRUMB & TITLE SECTION
         ============================================= -->
    <section class="ytrip-title-section">
        <div class="ytrip-container">
            <!-- Breadcrumb -->
            <nav class="ytrip-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ytrip' ); ?>">
                <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'ytrip' ); ?></a>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>"><?php esc_html_e( 'Tours', 'ytrip' ); ?></a>
                <?php if ( $destination_name ) : ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                <span><?php echo esc_html( $destination_name ); ?></span>
                <?php endif; ?>
            </nav>
            
            <!-- Title -->
            <h1 class="ytrip-tour-title"><?php the_title(); ?></h1>
            
            <!-- Meta Info Row -->
            <div class="ytrip-tour-meta">
                <?php if ( $destination_name ) : ?>
                <span class="ytrip-meta-item ytrip-meta-item--location">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php echo esc_html( $destination_name ); ?>
                </span>
                <?php endif; ?>
                
                <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                <span class="ytrip-meta-item ytrip-meta-item--rating">
                    <span class="ytrip-stars">
                        <?php 
                        $rating = $product->get_average_rating();
                        for ( $i = 1; $i <= 5; $i++ ) {
                            echo $i <= round( $rating ) ? '★' : '☆';
                        }
                        ?>
                    </span>
                    <strong><?php echo esc_html( number_format( $rating, 1 ) ); ?></strong>
                    <span class="ytrip-meta-item--reviews">
                        (<?php echo esc_html( $product->get_review_count() ); ?> <?php esc_html_e( 'reviews', 'ytrip' ); ?>)
                    </span>
                </span>
                <?php endif; ?>
                
                <?php if ( $category_name ) : ?>
                <span class="ytrip-meta-item ytrip-meta-item--category">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    <?php echo esc_html( $category_name ); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- =============================================
         QUICK INFO CARDS
         ============================================= -->
    <section class="ytrip-quick-info">
        <div class="ytrip-container">
            <div class="ytrip-quick-info__grid">
                <?php if ( $duration_str ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__content">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Duration', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( $duration_str ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $group_str ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__text">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Group Size', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( $group_str ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php 
                $languages = ytrip_get_meta_value_as_string( $meta, 'languages' );
                if ( $languages ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__content">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Languages', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( $languages ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $meta['difficulty'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__content">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Difficulty', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( is_array( $meta['difficulty'] ) ? $meta['difficulty'][0] : $meta['difficulty'] ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $meta['tour_type'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__content">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Tour Type', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( ucfirst( $meta['tour_type'] ) ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $meta['tour_code'] ) ) : ?>
                <div class="ytrip-quick-card">
                    <div class="ytrip-quick-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/>
                        </svg>
                    </div>
                    <div class="ytrip-quick-card__content">
                        <span class="ytrip-quick-card__label"><?php esc_html_e( 'Tour Code', 'ytrip' ); ?></span>
                        <span class="ytrip-quick-card__value"><?php echo esc_html( $meta['tour_code'] ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- =============================================
         MAIN CONTENT + SIDEBAR
         ============================================= -->
    <section class="ytrip-main">
        <div class="ytrip-container">
            <!-- Tab Navigation (above grid, full width) -->
            <nav class="ytrip-tabs" role="tablist">
                <button class="ytrip-tabs__btn active" data-tab="overview" role="tab" aria-selected="true">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'overview' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Overview', 'ytrip' ); ?></span>
                </button>
                <?php if ( ! empty( $meta['itinerary'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="itinerary" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'itinerary' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Itinerary', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <button class="ytrip-tabs__btn" data-tab="included" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'included' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Included', 'ytrip' ); ?></span>
                </button>
                <?php if ( ! empty( $meta['faq'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="faq" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'faq' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'FAQ', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <?php if ( ! empty( $meta['meeting_point'] ) || ! empty( $meta['meeting_time'] ) || ! empty( $meta['start_times'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="location" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'location' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Location & Times', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <?php if ( ! empty( $meta['know_before_you_go'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="know-before" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'know-before' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Know Before You Go', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <?php if ( ! empty( $meta['things_to_bring'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="what-to-bring" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'what-to-bring' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'What to Bring', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <?php if ( ! empty( $meta['cancellation_policy'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="cancellation" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'cancellation' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Cancellation', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
                <?php if ( ! empty( $meta['tour_route'] ) && is_array( $meta['tour_route'] ) ) : ?>
                <button class="ytrip-tabs__btn" data-tab="route" role="tab" aria-selected="false">
                    <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'route' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                    <span class="ytrip-tabs__label"><?php esc_html_e( 'Tour Route', 'ytrip' ); ?></span>
                </button>
                <?php endif; ?>
            </nav>

            <div class="ytrip-main__grid">
                <!-- Content Column -->
                <div class="ytrip-content">
                    <!-- Tab Panels -->
                    <div class="ytrip-panels">
                        
                        <!-- Overview Panel -->
                        <div class="ytrip-panel active" id="panel-overview" role="tabpanel">
                            <?php if ( ! empty( $meta['short_description'] ) ) : ?>
                            <p class="ytrip-short-desc"><?php echo esc_html( $meta['short_description'] ); ?></p>
                            <?php endif; ?>
                            <div class="ytrip-panel__content">
                                <?php the_content(); ?>
                            </div>
                            <?php if ( ! empty( $meta['tour_video'] ) ) : ?>
                            <div class="ytrip-tour-video">
                                <h3 class="ytrip-section-title"><?php esc_html_e( 'Watch the experience', 'ytrip' ); ?></h3>
                                <div class="ytrip-video-wrap">
                                    <?php
                                    $video_url = esc_url( $meta['tour_video'] );
                                    if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]+)#', $video_url, $m ) ) {
                                        echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr( $m[1] ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
                                    } elseif ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $video_url, $m ) ) {
                                        echo '<iframe src="https://player.vimeo.com/video/' . esc_attr( $m[1] ) . '" width="560" height="315" frameborder="0" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>';
                                    } else {
                                        echo '<a href="' . $video_url . '" target="_blank" rel="noopener" class="ytrip-video-link">' . esc_html__( 'Watch video', 'ytrip' ) . '</a>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $meta['highlights'] ) && is_array( $meta['highlights'] ) ) : ?>
                            <div class="ytrip-highlights">
                                <h3 class="ytrip-section-title"><?php esc_html_e( 'Tour Highlights', 'ytrip' ); ?></h3>
                                <ul class="ytrip-highlights__list">
                                    <?php foreach ( $meta['highlights'] as $highlight ) :
                                        $text = is_array( $highlight ) ? ( $highlight['highlight'] ?? '' ) : $highlight;
                                        if ( empty( $text ) ) { continue; }
                                    ?>
                                    <li>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        <?php echo esc_html( $text ); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Itinerary Panel -->
                        <?php if ( ! empty( $meta['itinerary'] ) && is_array( $meta['itinerary'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-itinerary" role="tabpanel">
                            <div class="ytrip-itinerary">
                                <?php foreach ( $meta['itinerary'] as $i => $day ) :
                                    $day_num  = is_array( $day ) && isset( $day['day_number'] ) ? (int) $day['day_number'] : ( $i + 1 );
                                    $title    = is_array( $day ) ? ( $day['day_title'] ?? $day['title'] ?? '' ) : '';
                                    $desc     = is_array( $day ) ? ( $day['day_description'] ?? $day['description'] ?? '' ) : '';
                                    $day_img  = is_array( $day ) && ! empty( $day['day_image'] ) ? $day['day_image'] : '';
                                    $meals    = is_array( $day ) && ! empty( $day['meals'] ) ? $day['meals'] : array();
                                    $accommodation = is_array( $day ) && ! empty( $day['accommodation'] ) ? $day['accommodation'] : '';
                                    $activities    = is_array( $day ) && ! empty( $day['activities'] ) && is_array( $day['activities'] ) ? $day['activities'] : array();
                                ?>
                                <div class="ytrip-itinerary__day">
                                    <div class="ytrip-itinerary__marker">
                                        <span class="ytrip-itinerary__number"><?php echo esc_html( $day_num ); ?></span>
                                    </div>
                                    <div class="ytrip-itinerary__content">
                                        <?php if ( $day_img && is_numeric( $day_img ) ) : ?>
                                        <div class="ytrip-itinerary__image"><?php echo wp_get_attachment_image( (int) $day_img, 'medium_large' ); ?></div>
                                        <?php endif; ?>
                                        <h4 class="ytrip-itinerary__title">
                                            <?php printf( esc_html__( 'Day %d', 'ytrip' ), $day_num ); ?>
                                            <?php if ( $title ) : ?>
                                            <span><?php echo esc_html( $title ); ?></span>
                                            <?php endif; ?>
                                        </h4>
                                        <?php if ( $desc ) : ?>
                                        <div class="ytrip-itinerary__desc"><?php echo wp_kses_post( $desc ); ?></div>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $meals ) && is_array( $meals ) ) : ?>
                                        <p class="ytrip-itinerary__meals">
                                            <span class="ytrip-itinerary__meals-label"><?php esc_html_e( 'Meals:', 'ytrip' ); ?></span>
                                            <?php echo esc_html( implode( ', ', array_map( 'ucfirst', $meals ) ) ); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if ( $accommodation !== '' ) : ?>
                                        <p class="ytrip-itinerary__accommodation">
                                            <span class="ytrip-itinerary__accommodation-label"><?php esc_html_e( 'Accommodation:', 'ytrip' ); ?></span>
                                            <?php echo esc_html( $accommodation ); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $activities ) ) : ?>
                                        <ul class="ytrip-itinerary__activities">
                                            <?php foreach ( $activities as $act ) :
                                                $time = is_array( $act ) ? ( $act['time'] ?? '' ) : '';
                                                $activity = is_array( $act ) ? ( $act['activity'] ?? '' ) : $act;
                                                if ( $activity === '' ) { continue; }
                                            ?>
                                            <li><?php if ( $time ) { echo '<span class="ytrip-itinerary__activity-time">' . esc_html( $time ) . '</span> '; } ?><?php echo esc_html( $activity ); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Included/Excluded Panel -->
                        <div class="ytrip-panel" id="panel-included" role="tabpanel">
                            <div class="ytrip-inc-exc">
                                <div class="ytrip-inc-exc__col ytrip-inc-exc__col--yes">
                                    <h3 class="ytrip-section-title">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>
                                        </svg>
                                        <?php esc_html_e( "What's Included", 'ytrip' ); ?>
                                    </h3>
                                    <?php if ( ! empty( $meta['included'] ) && is_array( $meta['included'] ) ) : ?>
                                    <ul>
                                        <?php foreach ( $meta['included'] as $item ) : 
                                            $text = is_array( $item ) ? ( $item['item'] ?? '' ) : $item;
                                            if ( empty( $text ) ) continue;
                                        ?>
                                        <li><?php echo esc_html( $text ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else : ?>
                                    <p class="ytrip-empty"><?php esc_html_e( 'No inclusions specified.', 'ytrip' ); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ytrip-inc-exc__col ytrip-inc-exc__col--no">
                                    <h3 class="ytrip-section-title">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                                        </svg>
                                        <?php esc_html_e( 'Not Included', 'ytrip' ); ?>
                                    </h3>
                                    <?php if ( ! empty( $meta['excluded'] ) && is_array( $meta['excluded'] ) ) : ?>
                                    <ul>
                                        <?php foreach ( $meta['excluded'] as $item ) : 
                                            $text = is_array( $item ) ? ( $item['item'] ?? '' ) : $item;
                                            if ( empty( $text ) ) continue;
                                        ?>
                                        <li><?php echo esc_html( $text ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else : ?>
                                    <p class="ytrip-empty"><?php esc_html_e( 'No exclusions specified.', 'ytrip' ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ Panel -->
                        <?php if ( ! empty( $meta['faq'] ) && is_array( $meta['faq'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-faq" role="tabpanel">
                            <div class="ytrip-faq">
                                <?php foreach ( $meta['faq'] as $faq ) : 
                                    $question = is_array( $faq ) ? ( $faq['question'] ?? '' ) : '';
                                    $answer = is_array( $faq ) ? ( $faq['answer'] ?? '' ) : '';
                                    if ( empty( $question ) ) continue;
                                ?>
                                <details class="ytrip-faq__item">
                                    <summary class="ytrip-faq__question" aria-expanded="false">
                                        <?php echo esc_html( $question ); ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </summary>
                                    <div class="ytrip-faq__answer">
                                        <?php echo wp_kses_post( $answer ); ?>
                                    </div>
                                </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Location & Times Panel -->
                        <?php if ( ! empty( $meta['meeting_point'] ) || ! empty( $meta['meeting_time'] ) || ! empty( $meta['start_times'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-location" role="tabpanel">
                            <div class="ytrip-info-cards">
                                <?php if ( ! empty( $meta['meeting_point'] ) ) : ?>
                                <div class="ytrip-info-card">
                                    <h4 class="ytrip-info-card__title">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <?php esc_html_e( 'Meeting Point', 'ytrip' ); ?>
                                    </h4>
                                    <p><?php echo esc_html( $meta['meeting_point'] ); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $meta['meeting_time'] ) ) : ?>
                                <div class="ytrip-info-card">
                                    <h4 class="ytrip-info-card__title">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                        <?php esc_html_e( 'Meeting Time', 'ytrip' ); ?>
                                    </h4>
                                    <p><?php echo esc_html( $meta['meeting_time'] ); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $meta['end_point'] ) ) : ?>
                                <div class="ytrip-info-card">
                                    <h4 class="ytrip-info-card__title">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        <?php esc_html_e( 'End Point', 'ytrip' ); ?>
                                    </h4>
                                    <p><?php echo esc_html( $meta['end_point'] ); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $meta['start_times'] ) && is_array( $meta['start_times'] ) ) : ?>
                                <div class="ytrip-info-card">
                                    <h4 class="ytrip-info-card__title">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                        <?php esc_html_e( 'Start Times', 'ytrip' ); ?>
                                    </h4>
                                    <ul class="ytrip-start-times">
                                        <?php foreach ( $meta['start_times'] as $slot ) :
                                            $time = is_array( $slot ) ? ( $slot['time'] ?? '' ) : $slot;
                                            if ( $time === '' ) { continue; }
                                        ?>
                                        <li><?php echo esc_html( $time ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Know Before You Go Panel -->
                        <?php if ( ! empty( $meta['know_before_you_go'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-know-before" role="tabpanel">
                            <div class="ytrip-section-block">
                                <h3 class="ytrip-section-title"><?php esc_html_e( 'Know Before You Go', 'ytrip' ); ?></h3>
                                <div class="ytrip-section-content"><?php echo wp_kses_post( $meta['know_before_you_go'] ); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- What to Bring Panel -->
                        <?php if ( ! empty( $meta['things_to_bring'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-what-to-bring" role="tabpanel">
                            <div class="ytrip-section-block">
                                <h3 class="ytrip-section-title"><?php esc_html_e( 'What to Bring', 'ytrip' ); ?></h3>
                                <div class="ytrip-section-content"><?php echo wp_kses_post( nl2br( esc_html( $meta['things_to_bring'] ) ) ); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Cancellation Panel -->
                        <?php if ( ! empty( $meta['cancellation_policy'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-cancellation" role="tabpanel">
                            <div class="ytrip-section-block">
                                <h3 class="ytrip-section-title"><?php esc_html_e( 'Cancellation Policy', 'ytrip' ); ?></h3>
                                <div class="ytrip-section-content"><?php echo wp_kses_post( $meta['cancellation_policy'] ); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tour Route Panel -->
                        <?php if ( ! empty( $meta['tour_route'] ) && is_array( $meta['tour_route'] ) ) : ?>
                        <div class="ytrip-panel" id="panel-route" role="tabpanel">
                            <h3 class="ytrip-section-title"><?php esc_html_e( 'Tour Route & Stops', 'ytrip' ); ?></h3>
                            <div class="ytrip-route-list">
                                <?php foreach ( $meta['tour_route'] as $idx => $stop ) :
                                    $name = is_array( $stop ) ? ( $stop['stop_name'] ?? '' ) : '';
                                    $stop_desc = is_array( $stop ) ? ( $stop['stop_description'] ?? '' ) : '';
                                    $stop_dur  = is_array( $stop ) ? ( $stop['stop_duration'] ?? '' ) : '';
                                    if ( $name === '' && $stop_desc === '' ) { continue; }
                                ?>
                                <div class="ytrip-route-stop">
                                    <span class="ytrip-route-stop__num"><?php echo esc_html( $idx + 1 ); ?></span>
                                    <div class="ytrip-route-stop__body">
                                        <?php if ( $name ) : ?><h4 class="ytrip-route-stop__title"><?php echo esc_html( $name ); ?></h4><?php endif; ?>
                                        <?php if ( $stop_desc ) : ?><p><?php echo esc_html( $stop_desc ); ?></p><?php endif; ?>
                                        <?php if ( $stop_dur ) : ?><span class="ytrip-route-stop__duration"><?php echo esc_html( $stop_dur ); ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <!-- Sidebar - Booking Widget (scroll target for CTA bar) -->
                <aside class="ytrip-sidebar" id="ytrip-booking-widget">
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

    <!-- Photo Gallery (Lightbox) -->
    <?php if ( ! empty( $gallery_ids ) && count( $gallery_ids ) > 0 ) : ?>
    <section class="ytrip-content-gallery" style="padding: 60px 0;">
        <div class="ytrip-container">
            <h2 class="ytrip-section-title" style="text-align:center; margin-bottom:32px; font-size:1.75rem;"><?php esc_html_e( 'Photo Gallery', 'ytrip' ); ?></h2>
            <div class="ytrip-gallery-grid ytrip-lightbox-gallery">
                <?php 
                foreach ( $gallery_ids as $i => $img_id ) :
                    $full_url = wp_get_attachment_image_url( $img_id, 'full' );
                    $alt_text = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
                    if ( ! $full_url ) continue;
                ?>
                <div class="ytrip-gallery-grid__item"
                     data-lightbox-src="<?php echo esc_url( $full_url ); ?>"
                     data-lightbox-alt="<?php echo esc_attr( $alt_text ); ?>">
                    <?php echo wp_get_attachment_image( $img_id, 'medium_large', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sticky CTA bar: shown on tablet/mobile when booking widget is below the fold -->
    <div class="ytrip-booking-cta-bar" id="ytrip-booking-cta-bar" aria-hidden="true">
        <div class="ytrip-booking-cta-bar__price">
            <span class="ytrip-booking-cta-bar__from"><?php esc_html_e( 'From', 'ytrip' ); ?></span>
            <span class="ytrip-booking-cta-bar__amount" id="ytrip-cta-bar-amount"><?php echo $product ? $product->get_price_html() : ''; ?></span>
            <span class="ytrip-booking-cta-bar__per"><?php esc_html_e( 'per person', 'ytrip' ); ?></span>
            <span class="ytrip-booking-cta-bar__guests" id="ytrip-cta-bar-guests"></span>
        </div>
        <button type="button" class="ytrip-booking-cta-bar__btn ytrip-btn ytrip-btn--primary" id="ytrip-cta-bar-btn">
            <?php esc_html_e( 'Book Now', 'ytrip' ); ?>
        </button>
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

            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            document.getElementById('panel-' + target)?.classList.add('active');

            // Keep active tab visible when tab bar is scrollable
            this.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        });
    });
});
</script>

</div><!-- .ytrip-single-tour-page -->

<?php
/**
 * Review Form Section – displayed before Related Tours.
 * Visibility controlled by 'single_show_reviews' in YTrip Settings → Single Tour.
 */
$_ytrip_show_reviews = isset( $options['single_show_reviews'] ) ? filter_var( $options['single_show_reviews'], FILTER_VALIDATE_BOOLEAN ) : true;
if ( $_ytrip_show_reviews ) :
	$_ytrip_review_template = YTRIP_PATH . 'templates/parts/review-form.php';
	if ( file_exists( $_ytrip_review_template ) ) :
?>
<section class="ytrip-section ytrip-review-section" aria-labelledby="ytrip-review-section-heading">
	<div class="ytrip-container">
		<header class="ytrip-review-section__header">
			<p class="ytrip-review-section__eyebrow"><?php esc_html_e( 'Share your feedback', 'ytrip' ); ?></p>
			<h2 id="ytrip-review-section-heading" class="ytrip-review-section__title"><?php esc_html_e( 'Write a Review', 'ytrip' ); ?></h2>
		</header>
		<div class="ytrip-review-section__body">
			<?php include $_ytrip_review_template; ?>
		</div>
	</div>
</section>
<?php
	endif;
endif;
?>

<?php if ( function_exists( 'ytrip_render_related_tours' ) ) : ?>
	<?php ytrip_render_related_tours( $tour_id ); ?>
<?php endif; ?>

<?php get_footer(); ?>
