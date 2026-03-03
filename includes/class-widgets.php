<?php
/**
 * YTrip Widgets
 *
 * @package YTrip
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

/**
 * Widget: Latest Tours
 */
class YTrip_Widget_Latest_Tours extends WP_Widget {

    public function __construct() {
        parent::__construct(
            "ytrip_latest_tours",
            __( "YTrip: Latest Tours", "ytrip" ),
            array(
                "description" => __( "Display latest tours with filtering options", "ytrip" ),
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( "widget_title", $instance["title"] ?? __( "Latest Tours", "ytrip" ) );
        $number = absint( $instance["number"] ?? 5 );
        $orderby = $instance["orderby"] ?? "date";
        $destination = $instance["destination"] ?? "";
        $category = $instance["category"] ?? "";

        $query_args = array(
            "post_type" => "ytrip_tour",
            "posts_per_page" => $number,
            "orderby" => $orderby,
            "order" => $orderby === "price" ? "ASC" : "DESC",
            "meta_query" => array(),
        );

        if ( $orderby === "price" ) {
            $query_args["meta_key"] = "_ytrip_price";
            $query_args["orderby"] = "meta_value_num";
        } elseif ( $orderby === "rand" ) {
            $query_args["orderby"] = "rand";
        }

        if ( $destination ) {
            $query_args["tax_query"][] = array(
                "taxonomy" => "ytrip_destination",
                "field" => "slug",
                "terms" => $destination,
            );
        }

        if ( $category ) {
            $query_args["tax_query"][] = array(
                "taxonomy" => "ytrip_category",
                "field" => "slug",
                "terms" => $category,
            );
        }

        $tours = new WP_Query( $query_args );

        echo $args["before_widget"];
        
        if ( $title ) {
            echo $args["before_title"] . esc_html( $title ) . $args["after_title"];
        }

        if ( $tours->have_posts() ) :
            echo "<div class=\"ytrip-widget-tours\">";
            while ( $tours->have_posts() ) : $tours->the_post();
                $tour_id = get_the_ID();
                $price = get_post_meta( $tour_id, "_ytrip_price", true );
                $destinations = get_the_terms( $tour_id, "ytrip_destination" );
                $destination_name = $destinations && ! is_wp_error( $destinations ) ? $destinations[0]->name : "";
                ?>
                <div class="ytrip-widget-tour">
                    <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>" class="ytrip-widget-tour__image">
                        <?php the_post_thumbnail( "medium" ); ?>
                    </a>
                    <?php endif; ?>
                    <div class="ytrip-widget-tour__content">
                        <h4 class="ytrip-widget-tour__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        <?php if ( $destination_name ) : ?>
                        <span class="ytrip-widget-tour__destination">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <?php echo esc_html( $destination_name ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $price ) : ?>
                        <span class="ytrip-widget-tour__price">
                            <?php echo YTrip_Helper::format_price( $price ); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            endwhile;
            echo "</div>";
            wp_reset_postdata();
        else :
            echo "<p>" . esc_html__( "No tours found.", "ytrip" ) . "</p>";
        endif;

        echo $args["after_widget"];
    }

    public function form( $instance ) {
        $title = $instance["title"] ?? __( "Latest Tours", "ytrip" );
        $number = $instance["number"] ?? 5;
        $orderby = $instance["orderby"] ?? "date";
        $destination = $instance["destination"] ?? "";
        $category = $instance["category"] ?? "";
        
        $destinations = get_terms( array( "taxonomy" => "ytrip_destination", "hide_empty" => false ) );
        $categories = get_terms( array( "taxonomy" => "ytrip_category", "hide_empty" => false ) );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( "title" ); ?>">
                <?php esc_html_e( "Title:", "ytrip" ); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id( "title" ); ?>" 
                   name="<?php echo $this->get_field_name( "title" ); ?>" 
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( "number" ); ?>">
                <?php esc_html_e( "Number of tours:", "ytrip" ); ?>
            </label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( "number" ); ?>" 
                   name="<?php echo $this->get_field_name( "number" ); ?>" 
                   type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( "orderby" ); ?>">
                <?php esc_html_e( "Order by:", "ytrip" ); ?>
            </label>
            <select class="widefat" id="<?php echo $this->get_field_id( "orderby" ); ?>" 
                    name="<?php echo $this->get_field_name( "orderby" ); ?>">
                <option value="date" <?php selected( $orderby, "date" ); ?>><?php esc_html_e( "Date (Newest)", "ytrip" ); ?></option>
                <option value="price" <?php selected( $orderby, "price" ); ?>><?php esc_html_e( "Price (Low to High)", "ytrip" ); ?></option>
                <option value="title" <?php selected( $orderby, "title" ); ?>><?php esc_html_e( "Title (A-Z)", "ytrip" ); ?></option>
                <option value="rand" <?php selected( $orderby, "rand" ); ?>><?php esc_html_e( "Random", "ytrip" ); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( "destination" ); ?>">
                <?php esc_html_e( "Filter by Destination:", "ytrip" ); ?>
            </label>
            <select class="widefat" id="<?php echo $this->get_field_id( "destination" ); ?>" 
                    name="<?php echo $this->get_field_name( "destination" ); ?>">
                <option value=""><?php esc_html_e( "All Destinations", "ytrip" ); ?></option>
                <?php foreach ( $destinations as $dest ) : ?>
                <option value="<?php echo esc_attr( $dest->slug ); ?>" <?php selected( $destination, $dest->slug ); ?>>
                    <?php echo esc_html( $dest->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( "category" ); ?>">
                <?php esc_html_e( "Filter by Category:", "ytrip" ); ?>
            </label>
            <select class="widefat" id="<?php echo $this->get_field_id( "category" ); ?>" 
                    name="<?php echo $this->get_field_name( "category" ); ?>">
                <option value=""><?php esc_html_e( "All Categories", "ytrip" ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
                    <?php echo esc_html( $cat->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance["title"] = sanitize_text_field( $new_instance["title"] ?? "" );
        $instance["number"] = absint( $new_instance["number"] ?? 5 );
        $instance["orderby"] = sanitize_text_field( $new_instance["orderby"] ?? "date" );
        $instance["destination"] = sanitize_text_field( $new_instance["destination"] ?? "" );
        $instance["category"] = sanitize_text_field( $new_instance["category"] ?? "" );
        return $instance;
    }
}

/**
 * Widget: Destinations
 */
class YTrip_Widget_Destinations extends WP_Widget {

    public function __construct() {
        parent::__construct(
            "ytrip_destinations",
            __( "YTrip: Destinations", "ytrip" ),
            array(
                "description" => __( "Display tour destinations", "ytrip" ),
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( "widget_title", $instance["title"] ?? __( "Destinations", "ytrip" ) );
        $number = absint( $instance["number"] ?? 10 );
        $show_count = ! empty( $instance["show_count"] );

        $destinations = get_terms( array(
            "taxonomy" => "ytrip_destination",
            "hide_empty" => true,
            "number" => $number,
            "orderby" => "count",
            "order" => "DESC",
        ) );

        echo $args["before_widget"];
        
        if ( $title ) {
            echo $args["before_title"] . esc_html( $title ) . $args["after_title"];
        }

        if ( ! empty( $destinations ) && ! is_wp_error( $destinations ) ) {
            echo "<ul class=\"ytrip-widget-destinations\">";
            foreach ( $destinations as $dest ) {
                $link = get_term_link( $dest );
                echo "<li>";
                echo "<a href=\"" . esc_url( $link ) . "\">" . esc_html( $dest->name ) . "</a>";
                if ( $show_count ) {
                    echo " <span class=\"ytrip-widget-count\">(" . esc_html( $dest->count ) . ")</span>";
                }
                echo "</li>";
            }
            echo "</ul>";
        }

        echo $args["after_widget"];
    }

    public function form( $instance ) {
        $title = $instance["title"] ?? __( "Destinations", "ytrip" );
        $number = $instance["number"] ?? 10;
        $show_count = $instance["show_count"] ?? false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( "title" ); ?>">
                <?php esc_html_e( "Title:", "ytrip" ); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id( "title" ); ?>" 
                   name="<?php echo $this->get_field_name( "title" ); ?>" 
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( "number" ); ?>">
                <?php esc_html_e( "Number:", "ytrip" ); ?>
            </label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( "number" ); ?>" 
                   name="<?php echo $this->get_field_name( "number" ); ?>" 
                   type="number" value="<?php echo esc_attr( $number ); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> 
                   id="<?php echo $this->get_field_id( "show_count" ); ?>" 
                   name="<?php echo $this->get_field_name( "show_count" ); ?>">
            <label for="<?php echo $this->get_field_id( "show_count" ); ?>">
                <?php esc_html_e( "Show tour count", "ytrip" ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance["title"] = sanitize_text_field( $new_instance["title"] ?? "" );
        $instance["number"] = absint( $new_instance["number"] ?? 10 );
        $instance["show_count"] = ! empty( $new_instance["show_count"] );
        return $instance;
    }
}

/**
 * Widget: Categories
 */
class YTrip_Widget_Categories extends WP_Widget {

    public function __construct() {
        parent::__construct(
            "ytrip_categories",
            __( "YTrip: Categories", "ytrip" ),
            array(
                "description" => __( "Display tour categories", "ytrip" ),
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( "widget_title", $instance["title"] ?? __( "Categories", "ytrip" ) );
        $show_count = ! empty( $instance["show_count"] );

        $categories = get_terms( array(
            "taxonomy" => "ytrip_category",
            "hide_empty" => true,
        ) );

        echo $args["before_widget"];
        
        if ( $title ) {
            echo $args["before_title"] . esc_html( $title ) . $args["after_title"];
        }

        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            echo "<ul class=\"ytrip-widget-categories\">";
            foreach ( $categories as $cat ) {
                $link = get_term_link( $cat );
                echo "<li>";
                echo "<a href=\"" . esc_url( $link ) . "\">" . esc_html( $cat->name ) . "</a>";
                if ( $show_count ) {
                    echo " <span class=\"ytrip-widget-count\">(" . esc_html( $cat->count ) . ")</span>";
                }
                echo "</li>";
            }
            echo "</ul>";
        }

        echo $args["after_widget"];
    }

    public function form( $instance ) {
        $title = $instance["title"] ?? __( "Categories", "ytrip" );
        $show_count = $instance["show_count"] ?? false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( "title" ); ?>">
                <?php esc_html_e( "Title:", "ytrip" ); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id( "title" ); ?>" 
                   name="<?php echo $this->get_field_name( "title" ); ?>" 
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> 
                   id="<?php echo $this->get_field_id( "show_count" ); ?>" 
                   name="<?php echo $this->get_field_name( "show_count" ); ?>">
            <label for="<?php echo $this->get_field_id( "show_count" ); ?>">
                <?php esc_html_e( "Show tour count", "ytrip" ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance["title"] = sanitize_text_field( $new_instance["title"] ?? "" );
        $instance["show_count"] = ! empty( $new_instance["show_count"] );
        return $instance;
    }
}

/**
 * Register Widgets
 */
function ytrip_register_widgets() {
    register_widget( "YTrip_Widget_Latest_Tours" );
    register_widget( "YTrip_Widget_Destinations" );
    register_widget( "YTrip_Widget_Categories" );
}
add_action( "widgets_init", "ytrip_register_widgets" );
