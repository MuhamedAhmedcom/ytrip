<?php
/**
 * Archive Header - Minimal (compact title bar, no large background)
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<header class="ytrip-archive-header ytrip-archive-header--minimal">
    <div class="ytrip-container">
        <?php if ( is_post_type_archive( 'ytrip_tour' ) ) : ?>
            <h1 class="ytrip-archive-header__title"><?php esc_html_e( 'All Tours', 'ytrip' ); ?></h1>
            <p class="ytrip-archive-header__desc"><?php esc_html_e( 'Explore our collection of unforgettable travel experiences.', 'ytrip' ); ?></p>
        <?php elseif ( is_tax( 'ytrip_destination' ) ) : ?>
            <h1 class="ytrip-archive-header__title"><?php single_term_title(); ?></h1>
            <?php if ( term_description() ) : ?>
                <p class="ytrip-archive-header__desc"><?php echo term_description(); ?></p>
            <?php endif; ?>
        <?php elseif ( is_tax( 'ytrip_category' ) ) : ?>
            <h1 class="ytrip-archive-header__title"><?php single_term_title(); ?></h1>
            <?php if ( term_description() ) : ?>
                <p class="ytrip-archive-header__desc"><?php echo term_description(); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>
