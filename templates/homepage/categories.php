<?php
/**
 * Tour Categories Section — Ultra-modern UI with multiple display styles
 *
 * Uses Homepage Builder: categories_title, categories_count, categories_style.
 * Styles: grid | chips | featured | minimal
 * Uses term meta: custom icon, image, color when set.
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options   = get_option( 'ytrip_homepage', array() );
$title     = isset( $options['categories_title'] ) ? $options['categories_title'] : esc_html__( 'Tour Categories', 'ytrip' );
$subtitle  = isset( $options['categories_subtitle'] ) && (string) $options['categories_subtitle'] !== '' ? $options['categories_subtitle'] : esc_html__( 'Choose your travel style and find your perfect trip.', 'ytrip' );
$cat_count = isset( $options['categories_count'] ) ? max( 1, min( 12, (int) $options['categories_count'] ) ) : 6;
$style     = isset( $options['categories_style'] ) ? sanitize_key( $options['categories_style'] ) : 'grid';
if ( ! in_array( $style, array( 'grid', 'chips', 'featured', 'minimal' ), true ) ) {
	$style = 'grid';
}

$categories = get_terms( array(
	'taxonomy'   => 'ytrip_category',
	'hide_empty' => false,
	'number'     => $cat_count,
) );

// Fallback SVG icons when term has no custom icon
$default_icons = array(
	'adventure'     => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 22h20L12 2z"/></svg>',
	'beach'         => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="3"/><path d="M12 8v13M5 21h14"/></svg>',
	'cultural'      => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'desert-safari' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
	'diving'        => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V8M12 8C12 8 6 12 6 16M12 8C12 8 18 12 18 16M6 16c0 2 2 4 6 6M18 16c0 2-2 4-6 6"/></svg>',
	'family'        => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'historical'    => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
	'nature'        => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V8M12 8C12 8 6 12 6 16M12 8C12 8 18 12 18 16"/></svg>',
	'default'       => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
);

$section_class = 'ytrip-section ytrip-section--categories ytrip-categories ytrip-categories--' . $style;
?>

<section class="<?php echo esc_attr( $section_class ); ?>">
	<div class="ytrip-container">
		<header class="ytrip-section__header ytrip-section-header">
			<span class="ytrip-section-header__eyebrow"><?php esc_html_e( 'Travel style', 'ytrip' ); ?></span>
			<h2 class="ytrip-section__title ytrip-section-header__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="ytrip-section__subtitle ytrip-section-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
            <style>
                @media (max-width: 768px) {
                    .ytrip-categories-grid {
                        display: flex;
                        flex-wrap: nowrap;
                        overflow-x: auto;
                        -webkit-overflow-scrolling: touch;
                        scroll-snap-type: x mandatory;
                        padding-bottom: 16px;
                        gap: 16px;
                        /* Hide scrollbar for a cleaner look */
                        scrollbar-width: none; 
                        scroll-behavior: smooth;
                    }
                    .ytrip-categories-grid::-webkit-scrollbar { display: none; }
                    .ytrip-categories-grid .ytrip-category-card {
                        flex: 0 0 calc(80% - 16px);
                        scroll-snap-align: start;
                        min-width: 200px;
                    }
                }
            </style>
			<div class="ytrip-categories-grid ytrip-categories-grid--<?php echo esc_attr( $style ); ?> ytrip-mobile-carousel">
				<?php foreach ( $categories as $index => $category ) :
					$slug        = sanitize_key( $category->slug );
					$term_color  = YTrip_Helper::get_term_color( $category->term_id, 'ytrip_category' );
					$term_icon   = YTrip_Helper::get_term_icon( $category->term_id, 'ytrip_category' );
					$term_image  = YTrip_Helper::get_term_image( $category->term_id, 'ytrip_category', 'medium' );
					$icon_svg    = isset( $default_icons[ $slug ] ) ? $default_icons[ $slug ] : $default_icons['default'];
					$is_first    = ( $index === 0 );
					$card_attr   = $term_color ? ' style="--ytrip-term-color: ' . esc_attr( $term_color ) . ';"' : '';
				?>
					<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="ytrip-category-card<?php echo $is_first ? ' ytrip-category-card--featured' : ''; ?>"<?php echo $card_attr; ?>>
						<?php if ( $style === 'featured' && $is_first && $term_image ) : ?>
							<div class="ytrip-category-card__bg">
								<img src="<?php echo esc_url( $term_image ); ?>" alt="" role="presentation" loading="lazy">
							</div>
							<div class="ytrip-category-card__overlay"></div>
						<?php endif; ?>
						<div class="ytrip-category-card__icon-wrap">
							<?php if ( $term_icon ) : ?>
								<span class="ytrip-category-card__icon ytrip-category-card__icon--font" aria-hidden="true"><i class="<?php echo esc_attr( $term_icon ); ?>"></i></span>
							<?php else : ?>
								<span class="ytrip-category-card__icon ytrip-category-card__icon--svg" aria-hidden="true"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
						<h3 class="ytrip-category-card__name"><?php echo esc_html( $category->name ); ?></h3>
						<span class="ytrip-category-card__count">
							<?php
							printf(
								esc_html( _n( '%d Tour', '%d Tours', $category->count, 'ytrip' ) ),
								(int) $category->count
							);
							?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="ytrip-section__empty"><?php esc_html_e( 'No categories found.', 'ytrip' ); ?></p>
		<?php endif; ?>
	</div>
</section>
