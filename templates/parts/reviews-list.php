<?php
/**
 * YTrip Reviews List Template
 *
 * Displays reviews for a tour with ratings summary and review form.
 *
 * @package YTrip
 * @since 1.2.0
 *
 * Variables available:
 * @var array  $rating       Rating summary data
 * @var array  $reviews_data Reviews with pagination
 * @var bool   $can_review   Whether current user can review
 * @var int    $tour_id      Tour ID
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reviews = $reviews_data['reviews'];
$total_reviews = $reviews_data['total'];
?>

<div class="ytrip-reviews" id="ytrip-reviews" data-tour-id="<?php echo esc_attr( $tour_id ); ?>">
	
	<!-- Reviews Summary -->
	<div class="ytrip-reviews-summary">
		<div class="ytrip-reviews-summary-left">
			<div class="ytrip-reviews-average">
				<span class="ytrip-reviews-score"><?php echo esc_html( $rating['average'] ?: '0.0' ); ?></span>
				<div class="ytrip-reviews-stars">
					<?php echo wp_kses_post( ytrip_render_stars( $rating['average'] ) ); ?>
				</div>
				<span class="ytrip-reviews-count">
					<?php
					printf(
						/* translators: %s: Number of reviews */
						esc_html( _n( '%s review', '%s reviews', $rating['count'], 'ytrip' ) ),
						esc_html( number_format_i18n( $rating['count'] ) )
					);
					?>
				</span>
			</div>
		</div>
		
		<div class="ytrip-reviews-summary-right">
			<!-- Rating Distribution -->
			<div class="ytrip-reviews-distribution">
				<?php for ( $stars = 5; $stars >= 1; $stars-- ) : ?>
					<?php
					$count = $rating['distribution'][ $stars ] ?? 0;
					$percentage = $rating['count'] > 0 ? ( $count / $rating['count'] ) * 100 : 0;
					?>
					<div class="ytrip-reviews-bar-row">
						<span class="ytrip-reviews-bar-label"><?php echo esc_html( $stars ); ?> ★</span>
						<div class="ytrip-reviews-bar">
							<div class="ytrip-reviews-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
						</div>
						<span class="ytrip-reviews-bar-count"><?php echo esc_html( $count ); ?></span>
					</div>
				<?php endfor; ?>
			</div>
		</div>
	</div>

	<!-- Category Ratings -->
	<?php if ( $rating['service_average'] || $rating['value_average'] || $rating['guide_average'] ) : ?>
		<div class="ytrip-reviews-categories">
			<?php if ( $rating['service_average'] ) : ?>
				<div class="ytrip-reviews-category">
					<span class="ytrip-reviews-category-label"><?php esc_html_e( 'Service', 'ytrip' ); ?></span>
					<div class="ytrip-reviews-category-bar">
						<div class="ytrip-reviews-category-fill" style="width: <?php echo esc_attr( ( $rating['service_average'] / 5 ) * 100 ); ?>%;"></div>
					</div>
					<span class="ytrip-reviews-category-value"><?php echo esc_html( $rating['service_average'] ); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if ( $rating['value_average'] ) : ?>
				<div class="ytrip-reviews-category">
					<span class="ytrip-reviews-category-label"><?php esc_html_e( 'Value', 'ytrip' ); ?></span>
					<div class="ytrip-reviews-category-bar">
						<div class="ytrip-reviews-category-fill" style="width: <?php echo esc_attr( ( $rating['value_average'] / 5 ) * 100 ); ?>%;"></div>
					</div>
					<span class="ytrip-reviews-category-value"><?php echo esc_html( $rating['value_average'] ); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if ( $rating['guide_average'] ) : ?>
				<div class="ytrip-reviews-category">
					<span class="ytrip-reviews-category-label"><?php esc_html_e( 'Guide', 'ytrip' ); ?></span>
					<div class="ytrip-reviews-category-bar">
						<div class="ytrip-reviews-category-fill" style="width: <?php echo esc_attr( ( $rating['guide_average'] / 5 ) * 100 ); ?>%;"></div>
					</div>
					<span class="ytrip-reviews-category-value"><?php echo esc_html( $rating['guide_average'] ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Write Review Button -->
	<div class="ytrip-reviews-actions">
		<?php if ( is_user_logged_in() ) : ?>
			<?php if ( true === $can_review ) : ?>
				<button type="button" class="ytrip-btn ytrip-btn-primary" id="ytrip-write-review-btn">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
					<?php esc_html_e( 'Write a Review', 'ytrip' ); ?>
				</button>
			<?php else : ?>
				<p class="ytrip-reviews-notice">
					<?php echo esc_html( is_wp_error( $can_review ) ? $can_review->get_error_message() : __( 'You cannot review this tour.', 'ytrip' ) ); ?>
				</p>
			<?php endif; ?>
		<?php else : ?>
			<p class="ytrip-reviews-notice">
				<a href="<?php echo esc_url( wp_login_url( get_permalink() . '#ytrip-reviews' ) ); ?>">
					<?php esc_html_e( 'Log in to write a review', 'ytrip' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>

	<!-- Review Form (Hidden by default) -->
	<?php if ( true === $can_review ) : ?>
		<div class="ytrip-review-form-wrapper" id="ytrip-review-form-wrapper" style="display: none;">
			<?php include YTRIP_PATH . 'templates/parts/review-form.php'; ?>
		</div>
	<?php endif; ?>

	<!-- Reviews List -->
	<div class="ytrip-reviews-list" id="ytrip-reviews-list">
		<?php if ( empty( $reviews ) ) : ?>
			<div class="ytrip-reviews-empty">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
				<p><?php esc_html_e( 'No reviews yet. Be the first to share your experience!', 'ytrip' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $reviews as $review ) : ?>
				<div class="ytrip-review-item" id="review-<?php echo esc_attr( $review->id ); ?>">
					<div class="ytrip-review-header">
						<div class="ytrip-review-author">
							<img src="<?php echo esc_url( $review->user_avatar ); ?>" 
							     alt="<?php echo esc_attr( $review->user_name ); ?>" 
							     class="ytrip-review-avatar">
							<div class="ytrip-review-author-info">
								<span class="ytrip-review-author-name">
									<?php echo esc_html( $review->user_name ); ?>
									<?php if ( $review->is_verified ) : ?>
										<span class="ytrip-review-verified" title="<?php esc_attr_e( 'Verified Booking', 'ytrip' ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22l-1.5-1.5L12 19l1.5 1.5zm0-19l-1.5-1.5L12 0l1.5 1.5zM22 12l1.5 1.5L22 15l-1.5-1.5zM2 12L.5 10.5 2 9l1.5 1.5zm17.07-4.93l1.07-1.07 1.41 1.41-1.07 1.07zM4.93 17.07l-1.07 1.07-1.41-1.41 1.07-1.07zm0-10.14l1.41-1.41 1.07 1.07-1.41 1.41zm12.14 12.14l1.41-1.41 1.07 1.07-1.41 1.41zM12 6a6 6 0 100 12 6 6 0 000-12zm0 10a4 4 0 110-8 4 4 0 010 8z"/><path d="M11 14.17l-1.59-1.59L8 14l3 3 5-5-1.41-1.42z"/></svg>
											<?php esc_html_e( 'Verified', 'ytrip' ); ?>
										</span>
									<?php endif; ?>
								</span>
								<span class="ytrip-review-date">
									<?php echo esc_html( human_time_diff( strtotime( $review->created_at ), current_time( 'timestamp' ) ) ); ?>
									<?php esc_html_e( 'ago', 'ytrip' ); ?>
								</span>
							</div>
						</div>
						<div class="ytrip-review-rating">
							<?php echo wp_kses_post( ytrip_render_stars( $review->overall_rating ) ); ?>
						</div>
					</div>
					
					<?php if ( $review->review_title ) : ?>
						<h4 class="ytrip-review-title"><?php echo esc_html( $review->review_title ); ?></h4>
					<?php endif; ?>
					
					<?php if ( $review->review_text ) : ?>
						<div class="ytrip-review-content">
							<?php echo wp_kses_post( nl2br( esc_html( $review->review_text ) ) ); ?>
						</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $review->photos ) ) : ?>
						<div class="ytrip-review-photos">
							<?php foreach ( $review->photos as $photo_id ) : ?>
								<?php $photo_url = wp_get_attachment_image_url( $photo_id, 'thumbnail' ); ?>
								<?php if ( $photo_url ) : ?>
									<a href="<?php echo esc_url( wp_get_attachment_url( $photo_id ) ); ?>" 
									   class="ytrip-review-photo" 
									   data-lightbox="review-<?php echo esc_attr( $review->id ); ?>">
										<img src="<?php echo esc_url( $photo_url ); ?>" alt="">
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<div class="ytrip-review-footer">
						<div class="ytrip-review-helpful">
							<span><?php esc_html_e( 'Was this review helpful?', 'ytrip' ); ?></span>
							<button type="button" class="ytrip-helpful-btn" data-review-id="<?php echo esc_attr( $review->id ); ?>" data-vote="yes">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
								<?php esc_html_e( 'Yes', 'ytrip' ); ?>
								<span class="ytrip-helpful-count">(<?php echo esc_html( $review->helpful_yes ); ?>)</span>
							</button>
							<button type="button" class="ytrip-helpful-btn" data-review-id="<?php echo esc_attr( $review->id ); ?>" data-vote="no">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>
								<?php esc_html_e( 'No', 'ytrip' ); ?>
								<span class="ytrip-helpful-count">(<?php echo esc_html( $review->helpful_no ); ?>)</span>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Load More -->
	<?php if ( $reviews_data['pages'] > 1 ) : ?>
		<div class="ytrip-reviews-pagination">
			<button type="button" class="ytrip-btn ytrip-btn-outline" id="ytrip-load-more-reviews" data-page="1" data-pages="<?php echo esc_attr( $reviews_data['pages'] ); ?>">
				<?php esc_html_e( 'Load More Reviews', 'ytrip' ); ?>
			</button>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Helper function to render star rating.
 */
if ( ! function_exists( 'ytrip_render_stars' ) ) {
	function ytrip_render_stars( $rating ) {
		$rating = floatval( $rating );
		$full_stars = floor( $rating );
		$half_star = ( $rating - $full_stars ) >= 0.5;
		$empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );

		$html = '<span class="ytrip-stars">';
		
		for ( $i = 0; $i < $full_stars; $i++ ) {
			$html .= '<span class="ytrip-star ytrip-star-full">★</span>';
		}
		
		if ( $half_star ) {
			$html .= '<span class="ytrip-star ytrip-star-half">★</span>';
		}
		
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			$html .= '<span class="ytrip-star ytrip-star-empty">★</span>';
		}
		
		$html .= '</span>';
		
		return $html;
	}
}
?>
