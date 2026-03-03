<?php
/**
 * Search Form Section
 * Renders fields based on admin options (search_form_fields) and style (search_style).
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$homepage_options = get_option( 'ytrip_homepage', array() );
$search_style    = isset( $homepage_options['search_style'] ) ? sanitize_html_class( $homepage_options['search_style'] ) : 'style_1';
$fields_config   = isset( $homepage_options['search_form_fields'] ) && is_array( $homepage_options['search_form_fields'] ) ? $homepage_options['search_form_fields'] : array( 'destination', 'date_range', 'guests' );
$show_destination = in_array( 'destination', $fields_config, true );
$show_date_range  = in_array( 'date_range', $fields_config, true );
$show_guests      = in_array( 'guests', $fields_config, true );

$destinations_flat = get_terms( array( 'taxonomy' => 'ytrip_destination', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
$destinations_parents = get_terms( array( 'taxonomy' => 'ytrip_destination', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name', 'order' => 'ASC' ) );
$is_pill = ( $search_style === 'pill' );
$placeholder_location = __( 'Where are you going?', 'ytrip' );
$current_destination_slug = isset( $_GET['destination'] ) ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : '';
$current_destination_name = '';
if ( $current_destination_slug ) {
    $term = get_term_by( 'slug', $current_destination_slug, 'ytrip_destination' );
    if ( $term && ! is_wp_error( $term ) ) {
        $current_destination_name = $term->name;
    }
}
?>
<section class="ytrip-search ytrip-search--<?php echo esc_attr( $search_style ); ?>" id="search">
    <form class="ytrip-search__form ytrip-search__form--<?php echo esc_attr( $search_style ); ?>" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'ytrip_tour' ) ); ?>">
        <?php if ( $show_destination ) : ?>
        <div class="ytrip-search__field ytrip-search__field--destination">
            <?php if ( $is_pill ) : ?>
            <div class="ytrip-search__field-head">
                <span class="ytrip-search__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </span>
                <label class="ytrip-search__label" for="ytrip-location-trigger"><?php esc_html_e( 'Location', 'ytrip' ); ?></label>
            </div>
            <?php else : ?>
            <label class="ytrip-search__label" for="ytrip-location-trigger"><?php esc_html_e( 'Location', 'ytrip' ); ?></label>
            <?php endif; ?>
            <div class="ytrip-location-dropdown" id="ytrip-location-dropdown">
                <input type="hidden" name="destination" id="ytrip-search-destination" value="<?php echo esc_attr( $current_destination_slug ); ?>">
                <button type="button" class="ytrip-location-dropdown__trigger" id="ytrip-location-trigger" aria-haspopup="listbox" aria-expanded="false" aria-label="<?php echo esc_attr( $placeholder_location ); ?>">
                    <span class="ytrip-location-dropdown__value" data-placeholder="<?php echo esc_attr( $placeholder_location ); ?>"><?php echo $current_destination_name ? esc_html( $current_destination_name ) : esc_html( $placeholder_location ); ?></span>
                    <svg class="ytrip-location-dropdown__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="ytrip-location-dropdown__panel" id="ytrip-location-panel" role="listbox" aria-hidden="true">
                    <div class="ytrip-location-dropdown__list">
                        <?php
                        if ( $destinations_flat && ! is_wp_error( $destinations_flat ) ) {
                            if ( $destinations_parents && ! is_wp_error( $destinations_parents ) && count( $destinations_parents ) > 0 ) {
                                foreach ( $destinations_parents as $parent ) {
                                    $children = get_terms( array( 'taxonomy' => 'ytrip_destination', 'hide_empty' => false, 'parent' => $parent->term_id, 'orderby' => 'name', 'order' => 'ASC' ) );
                                    $has_children = $children && ! is_wp_error( $children ) && count( $children ) > 0;
                                    if ( $has_children ) {
                                        ?><div class="ytrip-location-dropdown__group"><?php echo esc_html( $parent->name ); ?></div>
                                        <button type="button" class="ytrip-location-dropdown__item" role="option" data-slug="<?php echo esc_attr( $parent->slug ); ?>" data-name="<?php echo esc_attr( $parent->name ); ?>">
                                            <svg class="ytrip-location-dropdown__item-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <span><?php echo esc_html( $parent->name ); ?></span>
                                        </button><?php
                                        foreach ( $children as $child ) {
                                            ?><button type="button" class="ytrip-location-dropdown__item" role="option" data-slug="<?php echo esc_attr( $child->slug ); ?>" data-name="<?php echo esc_attr( $child->name ); ?>">
                                                <svg class="ytrip-location-dropdown__item-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                <span><?php echo esc_html( $child->name ); ?></span>
                                            </button><?php
                                        }
                                    } else {
                                        ?><button type="button" class="ytrip-location-dropdown__item" role="option" data-slug="<?php echo esc_attr( $parent->slug ); ?>" data-name="<?php echo esc_attr( $parent->name ); ?>">
                                            <svg class="ytrip-location-dropdown__item-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <span><?php echo esc_html( $parent->name ); ?></span>
                                        </button><?php
                                    }
                                }
                            } else {
                                ?><div class="ytrip-location-dropdown__group"><?php esc_html_e( 'Destinations', 'ytrip' ); ?></div><?php
                                foreach ( $destinations_flat as $dest ) {
                                    ?><button type="button" class="ytrip-location-dropdown__item" role="option" data-slug="<?php echo esc_attr( $dest->slug ); ?>" data-name="<?php echo esc_attr( $dest->name ); ?>">
                                        <svg class="ytrip-location-dropdown__item-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <span><?php echo esc_html( $dest->name ); ?></span>
                                    </button><?php
                                }
                            }
                        } else {
                            ?><div class="ytrip-location-dropdown__empty"><?php esc_html_e( 'No destinations yet.', 'ytrip' ); ?></div><?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $show_date_range ) : ?>
        <div class="ytrip-search__field ytrip-search__field--date-range">
            <?php if ( $is_pill ) : ?>
            <div class="ytrip-search__field-head">
                <span class="ytrip-search__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </span>
                <span class="ytrip-search__label"><?php esc_html_e( 'Date', 'ytrip' ); ?> – <?php esc_html_e( 'Check out', 'ytrip' ); ?></span>
            </div>
            <?php else : ?>
            <label class="ytrip-search__label" for="ytrip-hp-range-display"><?php esc_html_e( 'Date range', 'ytrip' ); ?></label>
            <?php endif; ?>
            <?php
            $args = array(
                'display_id'       => 'ytrip-hp-range-display',
                'from_display_id'  => 'ytrip-hp-date-from-display',
                'to_display_id'    => 'ytrip-hp-date-to-display',
                'from_name'        => 'date_from',
                'to_name'          => 'date_to',
                'from_id'          => 'ytrip-hp-date-from',
                'to_id'            => 'ytrip-hp-date-to',
                'container_id'     => 'ytrip-hp-range-calendar',
                'placeholder'      => __( 'Add date', 'ytrip' ),
                'placeholder_from' => __( 'Check-in', 'ytrip' ),
                'placeholder_to'   => __( 'Check-out', 'ytrip' ),
                'show_hint'        => ! $is_pill,
                'wrapper_class'    => $is_pill ? 'ytrip-search__calendar-inline' : '',
                'two_fields'       => true,
            );
            include YTRIP_PATH . 'templates/parts/calendar-range.php';
            ?>
        </div>
        <?php endif; ?>

        <?php if ( ! $show_date_range ) : ?>
        <div class="ytrip-search__field ytrip-search__field--date">
            <?php if ( $is_pill ) : ?>
            <div class="ytrip-search__field-head">
                <span class="ytrip-search__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </span>
                <label class="ytrip-search__label" for="ytrip-hp-date-display"><?php esc_html_e( 'Date', 'ytrip' ); ?></label>
            </div>
            <?php else : ?>
            <label class="ytrip-search__label" for="ytrip-hp-date-display"><?php esc_html_e( 'Date', 'ytrip' ); ?></label>
            <?php endif; ?>
            <?php
            $args = array(
                'display_id'    => 'ytrip-hp-date-display',
                'hidden_name'   => 'tour_date',
                'hidden_id'     => 'ytrip-hp-tour-date',
                'container_id'  => 'ytrip-hp-calendar',
                'placeholder'   => __( 'Add date', 'ytrip' ),
                'wrapper_class' => $is_pill ? 'ytrip-search__calendar-inline' : '',
            );
            include YTRIP_PATH . 'templates/parts/calendar-single.php';
            ?>
        </div>
        <?php endif; ?>

        <?php if ( $show_guests ) : ?>
        <div class="ytrip-search__field ytrip-search__field--guests">
            <label class="ytrip-search__label" for="ytrip-search-guests"><?php esc_html_e( 'Guests', 'ytrip' ); ?></label>
            <select name="guests" id="ytrip-search-guests" class="ytrip-search__input">
                <option value=""><?php esc_html_e( 'Select guests', 'ytrip' ); ?></option>
                <option value="1">1 <?php esc_html_e( 'Guest', 'ytrip' ); ?></option>
                <option value="2">2 <?php esc_html_e( 'Guests', 'ytrip' ); ?></option>
                <option value="3">3 <?php esc_html_e( 'Guests', 'ytrip' ); ?></option>
                <option value="4">4 <?php esc_html_e( 'Guests', 'ytrip' ); ?></option>
                <option value="5">5+ <?php esc_html_e( 'Guests', 'ytrip' ); ?></option>
            </select>
        </div>
        <?php endif; ?>

        <div class="ytrip-search__field ytrip-search__submit">
            <button type="submit" class="ytrip-btn ytrip-btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <?php esc_html_e( 'Search', 'ytrip' ); ?>
            </button>
        </div>
    </form>
</section>
