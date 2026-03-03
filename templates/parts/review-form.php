<?php
/**
 * YTrip Review Form Template
 *
 * Review submission form with star ratings.
 *
 * @package YTrip
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form id="ytrip-review-form" class="ytrip-review-form" method="post">
	<?php wp_nonce_field( 'ytrip_review_nonce', 'ytrip_review_security' ); ?>
	<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
	
	<div class="ytrip-review-form-header">
		<h3><?php esc_html_e( 'Write Your Review', 'ytrip' ); ?></h3>
		<button type="button" class="ytrip-review-form-close" id="ytrip-review-form-close">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
		</button>
	</div>

	<!-- Overall Rating (Required) -->
	<div class="ytrip-form-group ytrip-form-group-rating">
		<label><?php esc_html_e( 'Overall Rating', 'ytrip' ); ?> <span class="required">*</span></label>
		<div class="ytrip-star-rating-input" data-rating="0" data-name="overall_rating">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<span class="ytrip-star-input" data-value="<?php echo esc_attr( $i ); ?>">★</span>
			<?php endfor; ?>
			<input type="hidden" name="overall_rating" value="" required>
			<span class="ytrip-rating-label"></span>
		</div>
	</div>

	<!-- Category Ratings (Optional) -->
	<div class="ytrip-form-group ytrip-category-ratings">
		<label><?php esc_html_e( 'Rate by Category', 'ytrip' ); ?> <small><?php esc_html_e( '(Optional)', 'ytrip' ); ?></small></label>
		<div class="ytrip-category-ratings-grid">
			<!-- Service Rating -->
			<div class="ytrip-category-rating">
				<span class="ytrip-category-label"><?php esc_html_e( 'Service', 'ytrip' ); ?></span>
				<div class="ytrip-star-rating-input ytrip-star-rating-small" data-rating="0" data-name="service_rating">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="ytrip-star-input" data-value="<?php echo esc_attr( $i ); ?>">★</span>
					<?php endfor; ?>
					<input type="hidden" name="service_rating" value="">
				</div>
			</div>
			
			<!-- Value Rating -->
			<div class="ytrip-category-rating">
				<span class="ytrip-category-label"><?php esc_html_e( 'Value for Money', 'ytrip' ); ?></span>
				<div class="ytrip-star-rating-input ytrip-star-rating-small" data-rating="0" data-name="value_rating">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="ytrip-star-input" data-value="<?php echo esc_attr( $i ); ?>">★</span>
					<?php endfor; ?>
					<input type="hidden" name="value_rating" value="">
				</div>
			</div>
			
			<!-- Guide Rating -->
			<div class="ytrip-category-rating">
				<span class="ytrip-category-label"><?php esc_html_e( 'Tour Guide', 'ytrip' ); ?></span>
				<div class="ytrip-star-rating-input ytrip-star-rating-small" data-rating="0" data-name="guide_rating">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="ytrip-star-input" data-value="<?php echo esc_attr( $i ); ?>">★</span>
					<?php endfor; ?>
					<input type="hidden" name="guide_rating" value="">
				</div>
			</div>
		</div>
	</div>

	<!-- Review Title -->
	<div class="ytrip-form-group">
		<label for="ytrip-review-title"><?php esc_html_e( 'Review Title', 'ytrip' ); ?></label>
		<input type="text" 
		       id="ytrip-review-title" 
		       name="review_title" 
		       class="ytrip-input"
		       placeholder="<?php esc_attr_e( 'Summarize your experience', 'ytrip' ); ?>"
		       maxlength="100">
	</div>

	<!-- Review Text -->
	<div class="ytrip-form-group">
		<label for="ytrip-review-text"><?php esc_html_e( 'Your Review', 'ytrip' ); ?></label>
		<textarea id="ytrip-review-text" 
		          name="review_text" 
		          class="ytrip-textarea"
		          rows="5"
		          placeholder="<?php esc_attr_e( 'Share details of your experience. What did you like or dislike? Would you recommend this tour?', 'ytrip' ); ?>"
		          maxlength="2000"></textarea>
		<div class="ytrip-char-count">
			<span id="ytrip-review-char-count">0</span>/2000
		</div>
	</div>

	<!-- Photo Upload -->
	<div class="ytrip-form-group">
		<label><?php esc_html_e( 'Add Photos', 'ytrip' ); ?> <small><?php esc_html_e( '(Optional, max 5)', 'ytrip' ); ?></small></label>
		<div class="ytrip-photo-upload">
			<div class="ytrip-photo-preview" id="ytrip-photo-preview"></div>
			<label for="ytrip-photo-input" class="ytrip-photo-add">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
				<span><?php esc_html_e( 'Add Photo', 'ytrip' ); ?></span>
			</label>
			<input type="file" 
			       id="ytrip-photo-input" 
			       accept="image/jpeg,image/png,image/webp" 
			       multiple 
			       style="display: none;">
			<input type="hidden" name="photos" id="ytrip-photos-input" value="">
		</div>
	</div>

	<!-- Submit -->
	<div class="ytrip-form-group ytrip-form-actions">
		<button type="button" class="ytrip-btn ytrip-btn-outline" id="ytrip-review-cancel">
			<?php esc_html_e( 'Cancel', 'ytrip' ); ?>
		</button>
		<button type="submit" class="ytrip-btn ytrip-btn-primary" id="ytrip-review-submit">
			<span class="ytrip-btn-text"><?php esc_html_e( 'Submit Review', 'ytrip' ); ?></span>
			<span class="ytrip-btn-loading" style="display: none;">
				<svg class="ytrip-spinner" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
				<?php esc_html_e( 'Submitting...', 'ytrip' ); ?>
			</span>
		</button>
	</div>

	<!-- Messages -->
	<div class="ytrip-review-messages" id="ytrip-review-messages"></div>
</form>
