<?php
/**
 * Single Tour Layout 3: Split Screen
 * 50% Image Gallery (Left) / 50% Content (Right)
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tour_id = get_the_ID();
$meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
$meta = is_array( $meta ) ? $meta : array();
$options = get_option( 'ytrip_settings', array() );
$options = is_array( $options ) ? $options : array();
$show_tab_icons = ! empty( $options['single_tabs_show_icons'] );
$product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
$gallery_ids = ! empty( $meta['tour_gallery'] ) ? explode( ',', $meta['tour_gallery'] ) : array();

get_header();

include YTRIP_PATH . 'templates/parts/single-tour-brand-vars.php';
?>

<!-- Critical Inline Styles -->
<style>
    /* Astra Overrides */
    .ast-container, .site-content .ast-container { max-width: 100% !important; padding: 0 !important; display: block !important; }
    #primary, #secondary { width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; float: none !important; display: block !important; }
    #secondary, .widget-area { display: none !important; }
    .entry-content { margin: 0 !important; }
    
    /* Split Layout Styles */
    .ytrip-split-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 100vh;
    }
    .ytrip-split-left {
        padding: 0;
    }
    .ytrip-split-gallery {
        display: grid;
        grid-template-columns: 1fr;
        gap: 4px;
    }
    .ytrip-split-gallery img {
        width: 100%;
        height: 60vh;
        object-fit: cover;
        display: block;
    }
    .ytrip-split-right {
        padding: 60px 40px;
        background: white;
    }
    .ytrip-layout-3 .ytrip-booking-widget {
        margin-top: 40px;
        position: sticky;
        top: 20px;
        z-index: 10;
        background: white;
        border: 1px solid var(--ytrip-gray-200);
        padding: 24px;
        border-radius: var(--ytrip-radius-lg);
        box-shadow: var(--ytrip-shadow-lg);
    }
    
    @media (max-width: 1024px) {
        .ytrip-split-wrapper { grid-template-columns: 1fr; }
        .ytrip-split-gallery img { height: 40vh; }
        .ytrip-split-right { padding: 24px; }
        .ytrip-split-left { order: -1; }
    }
</style>

<article class="ytrip-tour ytrip-layout-3" id="ytrip-tour-<?php echo esc_attr( $tour_id ); ?>">
    
    <div class="ytrip-split-wrapper">
        <!-- Left: Gallery -->
        <div class="ytrip-split-left">
            <div class="ytrip-split-gallery">
                <?php if ( ! empty( $gallery_ids ) ) : ?>
                    <?php foreach ( $gallery_ids as $img_id ) : ?>
                        <?php echo wp_get_attachment_image( $img_id, 'full' ); ?>
                    <?php endforeach; ?>
                <?php elseif ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'full' ); ?>
                <?php else: ?>
                    <div style="height:100vh;background:var(--ytrip-gray-100);"></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right: Content -->
        <div class="ytrip-split-right">
            <div class="ytrip-content-wrapper" style="max-width: 600px; margin: 0 auto;">
                
                <nav class="ytrip-breadcrumb" style="margin-bottom:20px;">
                     <a href="<?php echo esc_url( home_url() ); ?>">Home</a> / <span><?php the_title(); ?></span>
                </nav>
                
                <h1 class="ytrip-tour-title" style="font-size: 2.5rem; line-height: 1.2; margin-bottom: 16px;"><?php the_title(); ?></h1>
                
                <div class="ytrip-tour-meta" style="margin-bottom: 32px;">
                     <!-- Rating & Location logic similar to Layout 1 -->
                     <?php if ( $product && $product->get_review_count() > 0 ) : ?>
                     <span class="ytrip-meta-item">
                        <span style="color:#f59e0b">★</span> <strong><?php echo $product->get_average_rating(); ?></strong>
                     </span>
                     <?php endif; ?>
                </div>
                
                <!-- Layout 3 Tab Nav -->
                <nav class="ytrip-tabs" role="tablist" style="margin-bottom: 30px; border-bottom: 1px solid var(--ytrip-gray-200);">
                    <button class="ytrip-tabs__btn active" data-tab="overview">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'overview' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'Overview', 'ytrip' ); ?></span>
                    </button>
                    <button class="ytrip-tabs__btn" data-tab="itinerary">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'itinerary' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'Itinerary', 'ytrip' ); ?></span>
                    </button>
                    <button class="ytrip-tabs__btn" data-tab="faq">
                        <?php if ( $show_tab_icons ) : ?><span class="ytrip-tabs__icon <?php echo esc_attr( YTrip_Helper::get_single_tour_tab_icon( 'faq' ) ); ?>" aria-hidden="true"></span><?php endif; ?>
                        <span class="ytrip-tabs__label"><?php esc_html_e( 'FAQ', 'ytrip' ); ?></span>
                    </button>
                </nav>
                
                <!-- Panels -->
                <div class="ytrip-panels">
                    <div class="ytrip-panel active" id="panel-overview">
                        <?php the_content(); ?>
                        
                        <?php if ( ! empty( $meta['highlights'] ) && is_array( $meta['highlights'] ) ) : ?>
                        <div class="ytrip-highlights" style="margin-top: 30px;">
                            <h3>Highlights</h3>
                            <ul>
                                <?php foreach($meta['highlights'] as $h): $t=is_array($h)?($h['highlight']??''):$h; if($t) echo "<li>".esc_html($t)."</li>"; endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Other panels logic (simplified) -->
                    <div class="ytrip-panel" id="panel-itinerary">...</div>
                    <div class="ytrip-panel" id="panel-faq">...</div>
                </div>
                
                <!-- Booking Widget (scroll target for CTA bar) -->
                <div id="ytrip-booking-widget">
                    <?php include YTRIP_PATH . 'templates/parts/booking-card.php'; ?>
                    <?php if ( is_active_sidebar( 'ytrip-single-tour' ) ) : ?>
                    <div class="ytrip-single-tour-widget-area ytrip-widget-area">
                        <?php dynamic_sidebar( 'ytrip-single-tour' ); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>

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
// Tab Logic reusable
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ytrip-tabs__btn');
    const panels = document.querySelectorAll('.ytrip-panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('panel-' + target)?.classList.add('active');
        });
    });
});
</script>

</div><!-- .ytrip-single-tour-page -->

<?php if ( function_exists( 'ytrip_render_related_tours' ) ) : ?>
	<?php ytrip_render_related_tours( $tour_id ); ?>
<?php endif; ?>

<?php get_footer(); ?>
