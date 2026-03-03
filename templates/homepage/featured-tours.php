<?php
/**
 * Featured Tours Section
 *
 * Uses Homepage Builder: featured_section_title, featured_section_subtitle,
 * featured_selection (auto|manual), featured_tours (post IDs), featured_count.
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options   = get_option( 'ytrip_homepage', array() );
$title     = isset( $options['featured_section_title'] ) ? $options['featured_section_title'] : esc_html__( 'Featured Tours', 'ytrip' );
$subtitle  = isset( $options['featured_section_subtitle'] ) ? $options['featured_section_subtitle'] : esc_html__( 'Discover our most popular travel experiences handpicked for you.', 'ytrip' );
$selection = isset( $options['featured_selection'] ) ? $options['featured_selection'] : 'auto';
$count     = isset( $options['featured_count'] ) ? max( 1, min( 24, (int) $options['featured_count'] ) ) : 6;
$manual_ids = isset( $options['featured_tours'] ) && is_array( $options['featured_tours'] ) ? array_map( 'absint', $options['featured_tours'] ) : array();
$manual_ids = array_filter( $manual_ids );

$query_args = array(
	'post_type'      => 'ytrip_tour',
	'post_status'    => 'publish',
	'posts_per_page' => $selection === 'manual' && ! empty( $manual_ids ) ? count( $manual_ids ) : $count,
	'meta_query'     => array(
		array(
			'key'     => 'ytrip_tour_details',
			'compare' => 'EXISTS',
		),
	),
);

if ( $selection === 'manual' && ! empty( $manual_ids ) ) {
	$query_args['post__in'] = $manual_ids;
	$query_args['orderby']  = 'post__in';
} else {
	$query_args['orderby'] = 'date';
	$query_args['order']   = 'DESC';
}

$tours = new WP_Query( $query_args );
?>

<section class="ytrip-section">
	<div class="ytrip-container">
		<div class="ytrip-section__header">
			<h2 class="ytrip-section__title ytrip-h2"><?php echo esc_html( $title ); ?></h2>
			<p class="ytrip-section__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		</div>
        
        <?php if ( $tours->have_posts() ) : ?>
            <div class="ytrip-tours-grid">
                <?php while ( $tours->have_posts() ) : $tours->the_post(); ?>
                    <?php include YTRIP_PATH . 'templates/parts/tour-card.php'; ?>
                <?php endwhile; ?>
            </div>
            
            <div class="ytrip-text-center" style="margin-top: 3rem;">
                <a href="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>" class="ytrip-btn ytrip-btn-secondary">
                    <?php esc_html_e( 'View All Tours', 'ytrip' ); ?>
                </a>
            </div>
        <?php else : ?>
            <p class="ytrip-text-center"><?php esc_html_e( 'No tours found.', 'ytrip' ); ?></p>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
    </div>
</section>
