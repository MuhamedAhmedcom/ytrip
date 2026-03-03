<?php
/**
 * Testimonials Section
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options      = get_option( 'ytrip_homepage' );
$title        = isset( $options['testimonials_title'] ) ? $options['testimonials_title'] : esc_html__( 'What Our Travelers Say', 'ytrip' );
$testimonials = isset( $options['testimonials'] ) ? $options['testimonials'] : array();

// Default testimonials if none configured.
if ( empty( $testimonials ) ) {
	$testimonials = array(
		array(
			'name'    => 'Sarah Johnson',
			'role'    => 'Adventure Traveler',
			'content' => 'An absolutely incredible experience! The tour was well-organized, the guides were knowledgeable, and every moment was magical.',
			'rating'  => 5,
		),
		array(
			'name'    => 'Michael Chen',
			'role'    => 'Family Vacation',
			'content' => 'Perfect for our family trip. The kids loved every activity and we created memories that will last a lifetime.',
			'rating'  => 5,
		),
		array(
			'name'    => 'Emma Williams',
			'role'    => 'Solo Traveler',
			'content' => 'As a solo traveler, I felt safe and welcomed throughout the entire journey. Highly recommend!',
			'rating'  => 5,
		),
	);
}
?>

<section class="ytrip-section ytrip-section--gradient">
	<div class="ytrip-container">
		<div class="ytrip-section__header ytrip-text-center">
			<h2 class="ytrip-section__title ytrip-h2"><?php echo esc_html( $title ); ?></h2>
			<p class="ytrip-section__subtitle"><?php esc_html_e( 'Real stories from real travelers around the world.', 'ytrip' ); ?></p>
		</div>

		<div class="ytrip-testimonials-grid">
			<?php foreach ( $testimonials as $testimonial ) : ?>
				<div class="ytrip-testimonial-card">
					<div class="ytrip-testimonial-card__rating">
						<?php
						$rating = isset( $testimonial['rating'] ) ? intval( $testimonial['rating'] ) : 5;
						for ( $i = 0; $i < 5; $i++ ) :
							if ( $i < $rating ) :
							?>
								<svg width="20" height="20" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
							<?php else : ?>
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
							<?php
							endif;
						endfor;
						?>
					</div>

					<blockquote class="ytrip-testimonial-card__content">
						<?php echo esc_html( isset( $testimonial['content'] ) ? $testimonial['content'] : '' ); ?>
					</blockquote>

					<div class="ytrip-testimonial-card__author">
						<div class="ytrip-testimonial-card__avatar">
							<?php
							if ( ! empty( $testimonial['image']['url'] ) ) :
							?>
								<img src="<?php echo esc_url( $testimonial['image']['url'] ); ?>" alt="<?php echo esc_attr( $testimonial['name'] ?? '' ); ?>">
							<?php else :
								$initials = '';
								if ( ! empty( $testimonial['name'] ) ) {
									$parts    = explode( ' ', $testimonial['name'] );
									$initials = strtoupper( substr( $parts[0], 0, 1 ) );
									if ( count( $parts ) > 1 ) {
										$initials .= strtoupper( substr( end( $parts ), 0, 1 ) );
									}
								}
							?>
								<span><?php echo esc_html( $initials ); ?></span>
							<?php endif; ?>
						</div>
						<div class="ytrip-testimonial-card__info">
							<span class="ytrip-testimonial-card__name"><?php echo esc_html( $testimonial['name'] ?? '' ); ?></span>
							<span class="ytrip-testimonial-card__role"><?php echo esc_html( $testimonial['role'] ?? '' ); ?></span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
