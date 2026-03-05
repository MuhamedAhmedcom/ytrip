<?php
/**
 * YTrip Codestar Framework Widgets
 *
 * Registers Destinations, Activities, and Trips widgets using CSF.
 * All widget IDs and classnames use ytrip_ prefix for discoverability in Classic and Gutenberg Legacy Widget.
 *
 * @package YTrip
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Destinations widget callback — lists ytrip_destination terms.
 *
 * @param array $args     Widget wrapper args (before_widget, after_widget, before_title, after_title).
 * @param array $instance Saved widget options.
 */
function ytrip_destinations_widget( $args, $instance ) {
    $title       = ! empty( $instance['ytrip_destinations_title'] ) ? $instance['ytrip_destinations_title'] : '';
    $title       = apply_filters( 'widget_title', $title, $instance, 'ytrip_destinations_widget' );
    $count       = isset( $instance['ytrip_destinations_count'] ) ? absint( $instance['ytrip_destinations_count'] ) : 6;
    $count       = max( 1, min( 20, $count ) );
    $style       = ! empty( $instance['ytrip_destinations_style'] ) ? sanitize_key( $instance['ytrip_destinations_style'] ) : 'list';
    $show_count  = ! empty( $instance['ytrip_destinations_show_count'] );
    $orderby     = ! empty( $instance['ytrip_destinations_orderby'] ) ? sanitize_key( $instance['ytrip_destinations_orderby'] ) : 'name';
    $order       = ( $orderby === 'name_desc' || $orderby === 'count_desc' ) ? 'DESC' : 'ASC';
    $orderby_q   = ( $orderby === 'count_asc' || $orderby === 'count_desc' ) ? 'count' : 'name';

    $terms = get_terms( array(
        'taxonomy'   => 'ytrip_destination',
        'hide_empty' => false,
        'number'     => $count,
        'orderby'    => $orderby_q,
        'order'      => $order,
    ) );

    if ( ! $terms || is_wp_error( $terms ) ) {
        return;
    }

    echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    if ( $title ) {
        echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    echo '<ul class="ytrip-widget ytrip-widget-destinations ytrip-widget-destinations--' . esc_attr( $style ) . '">';
    foreach ( $terms as $term ) {
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) {
            continue;
        }
        $icon  = class_exists( 'YTrip_Helper' ) ? YTrip_Helper::get_term_icon( $term->term_id, 'ytrip_destination' ) : '';
        $image = ( $style === 'compact' && class_exists( 'YTrip_Helper' ) ) ? YTrip_Helper::get_term_image( $term->term_id, 'ytrip_destination', 'thumbnail' ) : '';
        echo '<li class="ytrip-widget-destinations__item">';
        echo '<a href="' . esc_url( $link ) . '" class="ytrip-widget-destinations__link">';
        if ( $image ) {
            echo '<span class="ytrip-widget-destinations__thumb"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" /></span>';
        }
        if ( $icon ) {
            echo '<span class="ytrip-widget-destinations__icon" aria-hidden="true"><i class="' . esc_attr( $icon ) . '"></i></span>';
        }
        echo '<span class="ytrip-widget-destinations__name">' . esc_html( $term->name ) . '</span>';
        if ( $show_count ) {
            echo ' <span class="ytrip-widget-destinations__count">(' . absint( $term->count ) . ')</span>';
        }
        echo '</a></li>';
    }
    echo '</ul>';

    echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Activities (categories) widget callback — lists ytrip_category terms.
 *
 * @param array $args     Widget wrapper args.
 * @param array $instance Saved widget options.
 */
function ytrip_activities_widget( $args, $instance ) {
    $title      = ! empty( $instance['ytrip_activities_title'] ) ? $instance['ytrip_activities_title'] : '';
    $title      = apply_filters( 'widget_title', $title, $instance, 'ytrip_activities_widget' );
    $count      = isset( $instance['ytrip_activities_count'] ) ? absint( $instance['ytrip_activities_count'] ) : 6;
    $count      = max( 1, min( 20, $count ) );
    $show_count = ! empty( $instance['ytrip_activities_show_count'] );
    $orderby    = ! empty( $instance['ytrip_activities_orderby'] ) ? sanitize_key( $instance['ytrip_activities_orderby'] ) : 'name';
    $order      = ( $orderby === 'name_desc' || $orderby === 'count_desc' ) ? 'DESC' : 'ASC';
    $orderby_q  = ( $orderby === 'count_asc' || $orderby === 'count_desc' ) ? 'count' : 'name';

    $terms = get_terms( array(
        'taxonomy'   => 'ytrip_category',
        'hide_empty' => false,
        'number'     => $count,
        'orderby'    => $orderby_q,
        'order'      => $order,
    ) );

    if ( ! $terms || is_wp_error( $terms ) ) {
        return;
    }

    echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    if ( $title ) {
        echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    echo '<ul class="ytrip-widget ytrip-widget-activities">';
    foreach ( $terms as $term ) {
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) {
            continue;
        }
        $icon = class_exists( 'YTrip_Helper' ) ? YTrip_Helper::get_term_icon( $term->term_id, 'ytrip_category' ) : '';
        echo '<li class="ytrip-widget-activities__item">';
        echo '<a href="' . esc_url( $link ) . '" class="ytrip-widget-activities__link">';
        if ( $icon ) {
            echo '<span class="ytrip-widget-activities__icon" aria-hidden="true"><i class="' . esc_attr( $icon ) . '"></i></span>';
        }
        echo '<span class="ytrip-widget-activities__name">' . esc_html( $term->name ) . '</span>';
        if ( $show_count ) {
            echo ' <span class="ytrip-widget-activities__count">(' . absint( $term->count ) . ')</span>';
        }
        echo '</a></li>';
    }
    echo '</ul>';

    echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Trips widget callback — lists tours with sort: random, new, cheapest, top_rated, most_popular.
 *
 * @param array $args     Widget wrapper args.
 * @param array $instance Saved widget options.
 */
function ytrip_trips_widget( $args, $instance ) {
    $title        = ! empty( $instance['ytrip_trips_title'] ) ? $instance['ytrip_trips_title'] : '';
    $title        = apply_filters( 'widget_title', $title, $instance, 'ytrip_trips_widget' );
    $sort         = ! empty( $instance['ytrip_trips_sort'] ) ? sanitize_key( $instance['ytrip_trips_sort'] ) : 'new';
    $count        = isset( $instance['ytrip_trips_count'] ) ? absint( $instance['ytrip_trips_count'] ) : 5;
    $count        = max( 1, min( 12, $count ) );
    $show_image   = ! empty( $instance['ytrip_trips_show_image'] );
    $show_price   = ! empty( $instance['ytrip_trips_show_price'] );
    $show_rating  = ! empty( $instance['ytrip_trips_show_rating'] );

    $query_args = array(
        'post_type'      => 'ytrip_tour',
        'posts_per_page' => $count,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    );

    switch ( $sort ) {
        case 'random':
            $query_args['orderby'] = 'rand';
            break;
        case 'new':
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
            break;
        case 'cheapest':
            $query_args['meta_key']    = '_ytrip_price';
            $query_args['orderby']     = 'meta_value_num';
            $query_args['order']       = 'ASC';
            $query_args['meta_query']  = array(
                'relation' => 'OR',
                array( 'key' => '_ytrip_price', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_ytrip_price', 'compare' => 'EXISTS' ),
            );
            break;
        case 'top_rated':
            $query_args['meta_key']   = '_ytrip_rating';
            $query_args['orderby']    = 'meta_value_num';
            $query_args['order']      = 'DESC';
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array( 'key' => '_ytrip_rating', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_ytrip_rating', 'compare' => 'EXISTS' ),
            );
            break;
        case 'most_popular':
            $query_args['meta_key']   = '_ytrip_views';
            $query_args['orderby']    = 'meta_value_num';
            $query_args['order']     = 'DESC';
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array( 'key' => '_ytrip_views', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_ytrip_views', 'compare' => 'EXISTS' ),
            );
            break;
        default:
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
    }

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return;
    }

    echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    if ( $title ) {
        echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    echo '<ul class="ytrip-widget ytrip-widget-trips">';

    while ( $query->have_posts() ) {
        $query->the_post();
        $tour_id = get_the_ID();
        echo '<li class="ytrip-widget-trips__item">';
        ytrip_trips_widget_render_simple_card( $tour_id, $show_image, $show_price, $show_rating );
        echo '</li>';
    }

    wp_reset_postdata();

    echo '</ul>';
    echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Renders a minimal tour card for the Trips widget when not using the full tour-card part.
 *
 * @param int  $tour_id     Post ID of the tour.
 * @param bool $show_image  Whether to show thumbnail.
 * @param bool $show_price  Whether to show price.
 * @param bool $show_rating Whether to show rating.
 */
function ytrip_trips_widget_render_simple_card( $tour_id, $show_image, $show_price, $show_rating ) {
    $meta    = get_post_meta( $tour_id, 'ytrip_tour_details', true );
    $product = null;
    if ( function_exists( 'wc_get_product' ) ) {
        $product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
        $product    = $product_id ? wc_get_product( $product_id ) : null;
    }
    $price_html = '';
    if ( $show_price ) {
        if ( $product && is_callable( array( $product, 'get_price_html' ) ) ) {
            $price_html = $product->get_price_html();
        } else {
            $raw_price = get_post_meta( $tour_id, '_ytrip_price', true );
            if ( $raw_price !== '' && is_numeric( $raw_price ) ) {
                $currency  = ( function_exists( 'YTrip_Helper' ) && method_exists( 'YTrip_Helper', 'get_currency_symbol' ) ) ? YTrip_Helper::get_currency_symbol() : '';
                $price_html = $currency . number_format_i18n( floatval( $raw_price ), 2 );
            }
        }
    }
    $terms      = get_the_terms( $tour_id, 'ytrip_destination' );
    $destination = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
    ?>
    <div class="ytrip-tour-card">
        <?php if ( $show_image ) : ?>
            <div class="ytrip-tour-card__image">
                <a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>" class="ytrip-tour-card__image-link" aria-label="<?php echo esc_attr( get_the_title( $tour_id ) ); ?>">
                    <?php
                    if ( has_post_thumbnail( $tour_id ) ) {
                        echo get_the_post_thumbnail( $tour_id, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        echo '<span class="ytrip-tour-card__placeholder"></span>';
                    }
                    ?>
                </a>
            </div>
        <?php endif; ?>
        <div class="ytrip-tour-card__content">
            <?php if ( $destination ) : ?>
                <div class="ytrip-tour-card__location"><?php echo esc_html( $destination ); ?></div>
            <?php endif; ?>
            <h3 class="ytrip-tour-card__title">
                <a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>"><?php echo esc_html( get_the_title( $tour_id ) ); ?></a>
            </h3>
            <?php if ( $show_rating && $product && $product->get_review_count() > 0 ) : ?>
                <div class="ytrip-tour-card__rating">
                    <?php echo esc_html( number_format( (float) $product->get_average_rating(), 1 ) ); ?> (<?php echo absint( $product->get_review_count() ); ?>)
                </div>
            <?php endif; ?>
            <?php if ( $show_price && $price_html ) : ?>
                <div class="ytrip-tour-card__footer">
                    <span class="ytrip-tour-card__price-value"><?php echo wp_kses_post( $price_html ); ?></span>
                </div>
            <?php endif; ?>
            <a href="<?php echo esc_url( get_permalink( $tour_id ) ); ?>" class="ytrip-btn ytrip-btn-sm ytrip-btn-primary"><?php esc_html_e( 'View Details', 'ytrip' ); ?></a>
        </div>
    </div>
    <?php
}

/**
 * Returns CSF fields for the Destinations widget.
 *
 * @return array
 */
function ytrip_destinations_widget_fields() {
    return array(
        array(
            'id'          => 'ytrip_destinations_title',
            'type'        => 'text',
            'title'       => __( 'Title', 'ytrip' ),
            'default'     => __( 'Destinations', 'ytrip' ),
        ),
        array(
            'id'          => 'ytrip_destinations_count',
            'type'        => 'spinner',
            'title'       => __( 'Number of destinations', 'ytrip' ),
            'default'     => '6',
            'min'         => '1',
            'max'         => '20',
        ),
        array(
            'id'          => 'ytrip_destinations_style',
            'type'        => 'select',
            'title'       => __( 'Display style', 'ytrip' ),
            'options'     => array(
                'list'    => __( 'List (links only)', 'ytrip' ),
                'compact' => __( 'Compact (with thumbnail)', 'ytrip' ),
            ),
            'default'     => 'list',
        ),
        array(
            'id'          => 'ytrip_destinations_show_count',
            'type'        => 'switcher',
            'title'       => __( 'Show tour count', 'ytrip' ),
            'default'     => true,
        ),
        array(
            'id'          => 'ytrip_destinations_orderby',
            'type'        => 'select',
            'title'       => __( 'Order by', 'ytrip' ),
            'options'     => array(
                'name_asc'   => __( 'Name (A–Z)', 'ytrip' ),
                'name_desc'  => __( 'Name (Z–A)', 'ytrip' ),
                'count_asc'  => __( 'Tour count (low to high)', 'ytrip' ),
                'count_desc' => __( 'Tour count (high to low)', 'ytrip' ),
            ),
            'default'     => 'name_asc',
        ),
    );
}

/**
 * Returns CSF fields for the Activities widget.
 *
 * @return array
 */
function ytrip_activities_widget_fields() {
    return array(
        array(
            'id'      => 'ytrip_activities_title',
            'type'    => 'text',
            'title'   => __( 'Title', 'ytrip' ),
            'default' => __( 'Activities', 'ytrip' ),
        ),
        array(
            'id'      => 'ytrip_activities_count',
            'type'    => 'spinner',
            'title'   => __( 'Number of activities', 'ytrip' ),
            'default' => '6',
            'min'     => '1',
            'max'     => '20',
        ),
        array(
            'id'      => 'ytrip_activities_show_count',
            'type'    => 'switcher',
            'title'   => __( 'Show tour count', 'ytrip' ),
            'default' => true,
        ),
        array(
            'id'      => 'ytrip_activities_orderby',
            'type'    => 'select',
            'title'   => __( 'Order by', 'ytrip' ),
            'options' => array(
                'name_asc'   => __( 'Name (A–Z)', 'ytrip' ),
                'name_desc'  => __( 'Name (Z–A)', 'ytrip' ),
                'count_asc'  => __( 'Tour count (low to high)', 'ytrip' ),
                'count_desc' => __( 'Tour count (high to low)', 'ytrip' ),
            ),
            'default' => 'name_asc',
        ),
    );
}

/**
 * Returns CSF fields for the Trips widget.
 *
 * @return array
 */
function ytrip_trips_widget_fields() {
    return array(
        array(
            'id'      => 'ytrip_trips_title',
            'type'    => 'text',
            'title'   => __( 'Title', 'ytrip' ),
            'default' => __( 'Trips', 'ytrip' ),
        ),
        array(
            'id'      => 'ytrip_trips_sort',
            'type'    => 'select',
            'title'   => __( 'Sort by', 'ytrip' ),
            'options' => array(
                'random'      => __( 'Random', 'ytrip' ),
                'new'         => __( 'Newest', 'ytrip' ),
                'cheapest'    => __( 'Cheapest', 'ytrip' ),
                'top_rated'   => __( 'Top rated', 'ytrip' ),
                'most_popular' => __( 'Most popular', 'ytrip' ),
            ),
            'default' => 'new',
        ),
        array(
            'id'      => 'ytrip_trips_count',
            'type'    => 'spinner',
            'title'   => __( 'Number of trips', 'ytrip' ),
            'default' => '5',
            'min'     => '1',
            'max'     => '12',
        ),
        array(
            'id'      => 'ytrip_trips_show_image',
            'type'    => 'switcher',
            'title'   => __( 'Show image', 'ytrip' ),
            'default' => true,
        ),
        array(
            'id'      => 'ytrip_trips_show_price',
            'type'    => 'switcher',
            'title'   => __( 'Show price', 'ytrip' ),
            'default' => true,
        ),
        array(
            'id'      => 'ytrip_trips_show_rating',
            'type'    => 'switcher',
            'title'   => __( 'Show rating', 'ytrip' ),
            'default' => true,
        ),
    );
}

/**
 * Register YTrip widgets with Codestar Framework.
 * Hooks must run after CSF is loaded; file is loaded with core includes after CSF.
 */
function ytrip_register_csf_widgets() {
    if ( ! class_exists( 'CSF' ) ) {
        return;
    }

    CSF::createWidget( 'ytrip_destinations_widget', array(
        'title'       => __( 'YTrip: Destinations', 'ytrip' ),
        'classname'   => 'ytrip_widget_destinations',
        'description' => __( 'List tour destinations with optional count and style.', 'ytrip' ),
        'fields'      => ytrip_destinations_widget_fields(),
    ) );

    CSF::createWidget( 'ytrip_activities_widget', array(
        'title'       => __( 'YTrip: Activities', 'ytrip' ),
        'classname'   => 'ytrip_widget_activities',
        'description' => __( 'List tour categories (activities) with optional count.', 'ytrip' ),
        'fields'      => ytrip_activities_widget_fields(),
    ) );

    CSF::createWidget( 'ytrip_trips_widget', array(
        'title'       => __( 'YTrip: Trips', 'ytrip' ),
        'classname'   => 'ytrip_widget_trips',
        'description' => __( 'List tours by random, new, cheapest, top rated, or most popular.', 'ytrip' ),
        'fields'      => ytrip_trips_widget_fields(),
    ) );
}

ytrip_register_csf_widgets();

/**
 * Register YTrip widget areas (sidebars) for archive and single tour pages.
 */
function ytrip_register_widget_areas() {
    register_sidebar( array(
        'id'            => 'ytrip-archive',
        'name'          => __( 'YTrip: Tour Archive', 'ytrip' ),
        'description'   => __( 'Widgets in this area appear on the tour archive and destination/category taxonomy pages.', 'ytrip' ),
        'before_widget' => '<div id="%1$s" class="ytrip-widget-area__widget widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="ytrip-widget-area__title widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'id'            => 'ytrip-single-tour',
        'name'          => __( 'YTrip: Single Tour', 'ytrip' ),
        'description'   => __( 'Widgets in this area appear on single tour (trip) pages in the sidebar.', 'ytrip' ),
        'before_widget' => '<div id="%1$s" class="ytrip-widget-area__widget widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="ytrip-widget-area__title widget-title">',
        'after_title'   => '</h3>',
    ) );
}

add_action( 'widgets_init', 'ytrip_register_widget_areas' );

/**
 * Allow YTrip CSF widgets to appear inside Gutenberg's "Legacy Widget" block.
 * Without this filter, Gutenberg hides them because it cannot auto-generate
 * a block preview for CSF widgets. We expose them explicitly so editors can
 * insert them from the block inserter → Widgets → Legacy Widget.
 *
 * @param array $widget_types Widget type IDs to hide from Legacy Widget block.
 * @return array
 */
function ytrip_allow_legacy_widget_block( $widget_types ) {
    // Remove our widget IDs from the "hidden" list so they become available.
    $ytrip_widgets = array(
        'ytrip_destinations_widget',
        'ytrip_activities_widget',
        'ytrip_trips_widget',
    );
    return array_diff( $widget_types, $ytrip_widgets );
}
add_filter( 'widget_types_to_hide_from_legacy_widget_block', 'ytrip_allow_legacy_widget_block' );

/**
 * Suppress the Gutenberg block-editor warning for our CSF legacy widgets
 * by registering them as "no preview" widgets. This prevents the
 * "block was affected by errors" notice in the editor.
 */
function ytrip_register_widget_block_editor_scripts() {
    if ( ! function_exists( 'wp_add_inline_script' ) ) {
        return;
    }
    // Only enqueue on the block editor screen.
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! $screen->is_block_editor() ) {
        return;
    }
}
add_action( 'admin_enqueue_scripts', 'ytrip_register_widget_block_editor_scripts' );
