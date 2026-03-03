<?php
/**
 * Destinations Section — Ultra-modern UI with multiple display styles
 *
 * Uses Homepage Builder: destinations_title, destinations_count, destinations_style, destinations_layout.
 * Layout: default (grid styles) | carousel (circular cards with arrows).
 * Styles (when default): bento | grid | strip | minimal
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options    = get_option( 'ytrip_homepage', array() );
$title      = isset( $options['destinations_title'] ) ? $options['destinations_title'] : esc_html__( 'Popular Destinations', 'ytrip' );
$subtitle   = isset( $options['destinations_subtitle'] ) && (string) $options['destinations_subtitle'] !== '' ? $options['destinations_subtitle'] : esc_html__( 'Explore breathtaking locations around the world.', 'ytrip' );
$dest_count = isset( $options['destinations_count'] ) ? max( 1, min( 12, (int) $options['destinations_count'] ) ) : 6;
$layout     = isset( $options['destinations_layout'] ) ? sanitize_key( $options['destinations_layout'] ) : 'default';
$layout     = in_array( $layout, array( 'default', 'carousel' ), true ) ? $layout : 'default';
$style      = isset( $options['destinations_style'] ) ? sanitize_key( $options['destinations_style'] ) : 'bento';
if ( ! in_array( $style, array( 'bento', 'grid', 'strip', 'minimal' ), true ) ) {
	$style = 'bento';
}

$destinations = get_terms( array(
	'taxonomy'   => 'ytrip_destination',
	'hide_empty' => false,
	'number'     => $dest_count,
) );

$section_class = 'ytrip-section ytrip-section--destinations ytrip-destinations ytrip-destinations--' . ( $layout === 'carousel' ? 'carousel' : $style );

$carousel_autoplay  = $layout === 'carousel' && ! empty( $options['destinations_carousel_autoplay'] );
$carousel_delay     = $layout === 'carousel' && isset( $options['destinations_carousel_delay'] ) ? max( 2, min( 60, (int) $options['destinations_carousel_delay'] ) ) : 5;
$carousel_desktop   = $layout === 'carousel' && isset( $options['destinations_carousel_items_desktop'] ) ? max( 1, min( 12, (int) $options['destinations_carousel_items_desktop'] ) ) : 6;
$carousel_tablet    = $layout === 'carousel' && isset( $options['destinations_carousel_items_tablet'] ) ? max( 1, min( 8, (int) $options['destinations_carousel_items_tablet'] ) ) : 4;
$carousel_mobile    = $layout === 'carousel' && isset( $options['destinations_carousel_items_mobile'] ) ? max( 1, min( 4, (int) $options['destinations_carousel_items_mobile'] ) ) : 2;
?>

<section class="<?php echo esc_attr( $section_class ); ?>">
	<div class="ytrip-container">
		<header class="ytrip-section__header ytrip-section-header">
			<span class="ytrip-section-header__eyebrow"><?php esc_html_e( 'Where to go', 'ytrip' ); ?></span>
			<h2 class="ytrip-section__title ytrip-section-header__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="ytrip-section__subtitle ytrip-section-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( $destinations && ! is_wp_error( $destinations ) ) : ?>

			<?php if ( $layout === 'carousel' ) : ?>
				<div class="ytrip-destinations-carousel" data-ytrip-destinations-carousel
					data-autoplay="<?php echo $carousel_autoplay ? '1' : '0'; ?>"
					data-delay="<?php echo esc_attr( (string) $carousel_delay ); ?>"
					data-items-desktop="<?php echo esc_attr( (string) $carousel_desktop ); ?>"
					data-items-tablet="<?php echo esc_attr( (string) $carousel_tablet ); ?>"
					data-items-mobile="<?php echo esc_attr( (string) $carousel_mobile ); ?>">
					<button type="button" class="ytrip-destinations-carousel__nav ytrip-destinations-carousel__nav--prev" aria-label="<?php esc_attr_e( 'Previous destinations', 'ytrip' ); ?>">
						<span aria-hidden="true">&larr;</span>
					</button>
					<div class="ytrip-destinations-carousel__track-wrap">
						<div class="ytrip-destinations-carousel__track">
							<?php foreach ( $destinations as $dest ) :
								$image_url = YTrip_Helper::get_term_image( $dest->term_id, 'ytrip_destination', 'large' );
							?>
								<a href="<?php echo esc_url( get_term_link( $dest ) ); ?>" class="ytrip-destination-carousel-item">
									<span class="ytrip-destination-carousel-item__image-wrap">
										<?php if ( $image_url ) : ?>
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $dest->name ); ?>" class="ytrip-destination-carousel-item__image" loading="lazy">
										<?php else : ?>
											<span class="ytrip-destination-carousel-item__image ytrip-destination-carousel-item__image--placeholder"></span>
										<?php endif; ?>
									</span>
									<span class="ytrip-destination-carousel-item__name"><?php echo esc_html( $dest->name ); ?></span>
									<span class="ytrip-destination-carousel-item__count">
										<?php
										printf(
											esc_html( _n( '%d Tour', '%d Tours', $dest->count, 'ytrip' ) ),
											(int) $dest->count
										);
										?>
									</span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
					<button type="button" class="ytrip-destinations-carousel__nav ytrip-destinations-carousel__nav--next" aria-label="<?php esc_attr_e( 'Next destinations', 'ytrip' ); ?>">
						<span aria-hidden="true">&rarr;</span>
					</button>
				</div>
			<?php else : ?>
				<div class="ytrip-destinations-grid ytrip-destinations-grid--<?php echo esc_attr( $style ); ?>">
					<?php foreach ( $destinations as $index => $dest ) :
						$image_url  = YTrip_Helper::get_term_image( $dest->term_id, 'ytrip_destination', 'large' );
						$icon_class = YTrip_Helper::get_term_icon( $dest->term_id, 'ytrip_destination' );
						$term_color = YTrip_Helper::get_term_color( $dest->term_id, 'ytrip_destination' );
						$card_attr  = '';
						if ( $term_color ) {
							$card_attr = ' data-term-color="' . esc_attr( $term_color ) . '"';
						}
						$is_first = ( $index === 0 );
					?>
						<a href="<?php echo esc_url( get_term_link( $dest ) ); ?>" class="ytrip-destination-card<?php echo $is_first ? ' ytrip-destination-card--hero' : ''; ?>"<?php echo $card_attr; ?>>
							<?php if ( $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $dest->name ); ?>" class="ytrip-destination-card__image" loading="lazy">
							<?php else : ?>
								<div class="ytrip-destination-card__image ytrip-destination-card__image--placeholder"></div>
							<?php endif; ?>
							<div class="ytrip-destination-card__overlay"<?php echo $term_color ? ' style="--ytrip-term-color: ' . esc_attr( $term_color ) . ';"' : ''; ?>></div>
							<div class="ytrip-destination-card__content ytrip-destination-card__content--glass">
								<?php if ( $icon_class ) : ?>
									<span class="ytrip-destination-card__icon" aria-hidden="true"><i class="<?php echo esc_attr( $icon_class ); ?>"></i></span>
								<?php endif; ?>
								<h3 class="ytrip-destination-card__name"><?php echo esc_html( $dest->name ); ?></h3>
								<span class="ytrip-destination-card__count">
									<?php
									printf(
										esc_html( _n( '%d Tour', '%d Tours', $dest->count, 'ytrip' ) ),
										(int) $dest->count
									);
									?>
								</span>
								<span class="ytrip-destination-card__cta"><?php esc_html_e( 'Explore', 'ytrip' ); ?> &rarr;</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		<?php endif; ?>
	</div>
</section>
