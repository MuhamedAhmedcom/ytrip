<?php
/**
 * YTrip Reviews Admin Page
 *
 * @package YTrip
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pending_count  = isset( $counts['pending'] ) ? $counts['pending']->count : 0;
$approved_count = isset( $counts['approved'] ) ? $counts['approved']->count : 0;
$rejected_count = isset( $counts['rejected'] ) ? $counts['rejected']->count : 0;
$total_pages    = ceil( $total / $per_page );
?>
<div class="wrap ytrip-reviews-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Tour Reviews', 'ytrip' ); ?></h1>
	
	<hr class="wp-header-end">

	<!-- Status Filter Tabs -->
	<ul class="subsubsub">
		<li class="all">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews' ) ); ?>" 
			   class="<?php echo empty( $status_filter ) ? 'current' : ''; ?>">
				<?php esc_html_e( 'All', 'ytrip' ); ?>
				<span class="count">(<?php echo esc_html( $total ); ?>)</span>
			</a> |
		</li>
		<li class="pending">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews&status=pending' ) ); ?>"
			   class="<?php echo 'pending' === $status_filter ? 'current' : ''; ?>">
				<?php esc_html_e( 'Pending', 'ytrip' ); ?>
				<span class="count">(<?php echo esc_html( $pending_count ); ?>)</span>
			</a> |
		</li>
		<li class="approved">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews&status=approved' ) ); ?>"
			   class="<?php echo 'approved' === $status_filter ? 'current' : ''; ?>">
				<?php esc_html_e( 'Approved', 'ytrip' ); ?>
				<span class="count">(<?php echo esc_html( $approved_count ); ?>)</span>
			</a> |
		</li>
		<li class="rejected">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews&status=rejected' ) ); ?>"
			   class="<?php echo 'rejected' === $status_filter ? 'current' : ''; ?>">
				<?php esc_html_e( 'Rejected', 'ytrip' ); ?>
				<span class="count">(<?php echo esc_html( $rejected_count ); ?>)</span>
			</a>
		</li>
	</ul>

	<table class="wp-list-table widefat fixed striped reviews">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-author" style="width: 15%;">
					<?php esc_html_e( 'Author', 'ytrip' ); ?>
				</th>
				<th scope="col" class="manage-column column-tour" style="width: 20%;">
					<?php esc_html_e( 'Tour', 'ytrip' ); ?>
				</th>
				<th scope="col" class="manage-column column-rating" style="width: 10%;">
					<?php esc_html_e( 'Rating', 'ytrip' ); ?>
				</th>
				<th scope="col" class="manage-column column-review" style="width: 35%;">
					<?php esc_html_e( 'Review', 'ytrip' ); ?>
				</th>
				<th scope="col" class="manage-column column-status" style="width: 10%;">
					<?php esc_html_e( 'Status', 'ytrip' ); ?>
				</th>
				<th scope="col" class="manage-column column-date" style="width: 10%;">
					<?php esc_html_e( 'Date', 'ytrip' ); ?>
				</th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php if ( empty( $reviews ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No reviews found.', 'ytrip' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $reviews as $review ) : ?>
					<?php
					$user = get_userdata( $review->user_id );
					$user_name = $user ? $user->display_name : __( 'Unknown', 'ytrip' );
					$user_email = $user ? $user->user_email : '';
					$is_verified = ! empty( $review->order_id );
					?>
					<tr id="review-<?php echo esc_attr( $review->id ); ?>" 
					    class="review-status-<?php echo esc_attr( $review->status ); ?>">
						<td class="column-author">
							<?php echo get_avatar( $review->user_id, 32 ); ?>
							<strong><?php echo esc_html( $user_name ); ?></strong>
							<?php if ( $is_verified ) : ?>
								<span class="verified-badge" title="<?php esc_attr_e( 'Verified Purchase', 'ytrip' ); ?>">✓</span>
							<?php endif; ?>
							<br>
							<small><?php echo esc_html( $user_email ); ?></small>
						</td>
						<td class="column-tour">
							<a href="<?php echo esc_url( get_edit_post_link( $review->tour_id ) ); ?>">
								<?php echo esc_html( $review->tour_title ?: __( 'Unknown Tour', 'ytrip' ) ); ?>
							</a>
						</td>
						<td class="column-rating">
							<div class="star-rating">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
									<span class="star <?php echo $i <= $review->overall_rating ? 'filled' : ''; ?>">★</span>
								<?php endfor; ?>
							</div>
							<strong><?php echo esc_html( number_format( $review->overall_rating, 1 ) ); ?></strong>
						</td>
						<td class="column-review">
							<?php if ( $review->review_title ) : ?>
								<strong><?php echo esc_html( $review->review_title ); ?></strong><br>
							<?php endif; ?>
							<?php echo esc_html( wp_trim_words( $review->review_text, 30 ) ); ?>
							
							<!-- Row Actions -->
							<div class="row-actions">
								<?php if ( 'pending' === $review->status || 'rejected' === $review->status ) : ?>
									<span class="approve">
										<a href="#" class="ytrip-moderate-review" 
										   data-id="<?php echo esc_attr( $review->id ); ?>" 
										   data-action="approve">
											<?php esc_html_e( 'Approve', 'ytrip' ); ?>
										</a> |
									</span>
								<?php endif; ?>
								
								<?php if ( 'pending' === $review->status || 'approved' === $review->status ) : ?>
									<span class="reject">
										<a href="#" class="ytrip-moderate-review" 
										   data-id="<?php echo esc_attr( $review->id ); ?>" 
										   data-action="reject">
											<?php esc_html_e( 'Reject', 'ytrip' ); ?>
										</a> |
									</span>
								<?php endif; ?>
								
								<span class="delete">
									<a href="#" class="ytrip-moderate-review" 
									   data-id="<?php echo esc_attr( $review->id ); ?>" 
									   data-action="delete"
									   style="color: #b32d2e;">
										<?php esc_html_e( 'Delete', 'ytrip' ); ?>
									</a>
								</span>
							</div>
						</td>
						<td class="column-status">
							<?php
							$status_labels = array(
								'pending'  => '<span class="status-badge status-pending">' . esc_html__( 'Pending', 'ytrip' ) . '</span>',
								'approved' => '<span class="status-badge status-approved">' . esc_html__( 'Approved', 'ytrip' ) . '</span>',
								'rejected' => '<span class="status-badge status-rejected">' . esc_html__( 'Rejected', 'ytrip' ) . '</span>',
							);
							echo $status_labels[ $review->status ] ?? esc_html( $review->status );
							?>
						</td>
						<td class="column-date">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?>
							<br>
							<small><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $review->created_at ) ) ); ?></small>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: Number of items */
						esc_html( _n( '%s item', '%s items', $total, 'ytrip' ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</span>
				<span class="pagination-links">
					<?php
					$base_url = admin_url( 'edit.php?post_type=ytrip_tour&page=ytrip-reviews' );
					if ( $status_filter ) {
						$base_url .= '&status=' . $status_filter;
					}

					echo paginate_links( array(
						'base'      => $base_url . '%_%',
						'format'    => '&paged=%#%',
						'current'   => $paged,
						'total'     => $total_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					) );
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
.ytrip-reviews-admin .star-rating .star { color: #ddd; font-size: 14px; }
.ytrip-reviews-admin .star-rating .star.filled { color: #f5a623; }
.ytrip-reviews-admin .verified-badge { 
	display: inline-block; 
	background: #28a745; 
	color: #fff; 
	border-radius: 50%; 
	width: 16px; 
	height: 16px; 
	text-align: center; 
	font-size: 10px; 
	line-height: 16px;
	margin-left: 4px;
}
.ytrip-reviews-admin .status-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 600;
}
.ytrip-reviews-admin .status-pending { background: #fff3cd; color: #856404; }
.ytrip-reviews-admin .status-approved { background: #d4edda; color: #155724; }
.ytrip-reviews-admin .status-rejected { background: #f8d7da; color: #721c24; }
.ytrip-reviews-admin .review-status-rejected { opacity: 0.6; }
.ytrip-reviews-admin .column-author img { vertical-align: middle; margin-right: 8px; border-radius: 50%; }
</style>

<script>
jQuery(document).ready(function($) {
	$('.ytrip-moderate-review').on('click', function(e) {
		e.preventDefault();
		
		var $link = $(this);
		var reviewId = $link.data('id');
		var action = $link.data('action');
		var confirmMsg = action === 'delete' 
			? '<?php echo esc_js( __( 'Are you sure you want to delete this review?', 'ytrip' ) ); ?>'
			: '<?php echo esc_js( __( 'Are you sure?', 'ytrip' ) ); ?>';
		
		if (!confirm(confirmMsg)) {
			return;
		}
		
		$link.text('<?php echo esc_js( __( 'Processing...', 'ytrip' ) ); ?>');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ytrip_moderate_review',
				security: '<?php echo esc_js( wp_create_nonce( 'ytrip_admin_nonce' ) ); ?>',
				review_id: reviewId,
				action_type: action
			},
			success: function(response) {
				if (response.success) {
					if (action === 'delete') {
						$('#review-' + reviewId).fadeOut(300, function() {
							$(this).remove();
						});
					} else {
						location.reload();
					}
				} else {
					alert(response.data.message);
					$link.text(action.charAt(0).toUpperCase() + action.slice(1));
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'An error occurred.', 'ytrip' ) ); ?>');
				$link.text(action.charAt(0).toUpperCase() + action.slice(1));
			}
		});
	});
});
</script>
