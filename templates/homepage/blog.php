<?php
/**
 * Latest Blog Posts Section
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'ytrip_homepage' );
$title   = isset( $options['blog_title'] ) ? $options['blog_title'] : esc_html__( 'Travel Tips & Stories', 'ytrip' );
$count   = isset( $options['blog_count'] ) ? intval( $options['blog_count'] ) : 3;

$posts = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => $count,
		'post_status'    => 'publish',
	)
);
?>

<section class="ytrip-section">
	<div class="ytrip-container">
		<div class="ytrip-section__header">
			<h2 class="ytrip-section__title ytrip-h2"><?php echo esc_html( $title ); ?></h2>
			<p class="ytrip-section__subtitle"><?php esc_html_e( 'Inspiration and insights from our travel experts.', 'ytrip' ); ?></p>
		</div>

		<?php if ( $posts->have_posts() ) : ?>
			<div class="ytrip-blog-grid">
				<?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
					<article class="ytrip-blog-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="ytrip-blog-card__image">
								<?php the_post_thumbnail( 'medium_large' ); ?>
							</a>
						<?php endif; ?>

						<div class="ytrip-blog-card__content">
							<div class="ytrip-blog-card__meta">
								<span class="ytrip-blog-card__date">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
										<line x1="16" y1="2" x2="16" y2="6"/>
										<line x1="8" y1="2" x2="8" y2="6"/>
										<line x1="3" y1="10" x2="21" y2="10"/>
									</svg>
									<?php echo esc_html( get_the_date() ); ?>
								</span>
								<?php
								$categories = get_the_category();
								if ( $categories ) :
								?>
									<span class="ytrip-blog-card__category">
										<?php echo esc_html( $categories[0]->name ); ?>
									</span>
								<?php endif; ?>
							</div>

							<h3 class="ytrip-blog-card__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<p class="ytrip-blog-card__excerpt">
								<?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?>
							</p>

							<a href="<?php the_permalink(); ?>" class="ytrip-blog-card__link">
								<?php esc_html_e( 'Read More', 'ytrip' ); ?>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<line x1="5" y1="12" x2="19" y2="12"/>
									<polyline points="12 5 19 12 12 19"/>
								</svg>
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="ytrip-text-center" style="margin-top: 3rem;">
				<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="ytrip-btn ytrip-btn-secondary">
					<?php esc_html_e( 'View All Posts', 'ytrip' ); ?>
				</a>
			</div>
		<?php else : ?>
			<p class="ytrip-text-center"><?php esc_html_e( 'No blog posts found.', 'ytrip' ); ?></p>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>
	</div>
</section>
