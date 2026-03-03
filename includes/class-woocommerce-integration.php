<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_WooCommerce_Integration {
    
    public function __construct() {
        // Convert tour to product
        add_action('save_post_ytrip_tour', array($this, 'sync_tour_to_product'), 20, 3);
        
        // Add tour-specific product data
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_tour_product_fields'));
        
        // Modify checkout for tour bookings (deprecated in favor of cart item data)
        // add_filter('woocommerce_checkout_fields', array($this, 'add_tour_booking_fields'));
        
        // Capture booking data (Date, Guests) into Cart Item
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_tour_booking_data_to_cart' ), 10, 3 );
        
        // Display booking data in Cart/Checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_tour_data_in_cart' ), 10, 2 );

        // Add tour details to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_tour_data_to_order'), 10, 3);

        // Persist session ytrip_booking_data to order meta when order is created
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_ytrip_booking_data_to_order' ), 10, 2 );

        // Optional: create ytrip_bookings row when order is paid so admin sees all bookings in one list
        add_action( 'woocommerce_payment_complete', array( $this, 'maybe_create_ytrip_booking_on_order_paid' ), 10, 1 );
        
        // Enable reviews for tours
        add_filter('woocommerce_product_review_list_args', array($this, 'enable_tour_reviews'));

        // Hide "Tour Products" from catalog
        add_action( 'pre_get_posts', array( $this, 'hide_tour_products_from_catalog' ) );
    }
    
    /**
     * Sync Tour CPT to WooCommerce Product
     */
    public function sync_tour_to_product( $post_id, $post, $update ) {
        // 1. Checks
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( 'ytrip_tour' !== $post->post_type ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        
        // Check if WooCommerce is active
        if ( ! class_exists( 'WC_Product_Simple' ) ) return;

        // 2. GetData
        $meta = get_post_meta( $post_id, 'ytrip_tour_details', true );
        $booking_method = isset( $meta['booking_method'] ) ? $meta['booking_method'] : 'woocommerce';
        
        // Structure based on 'fieldset' type in tour-details.php
        $price      = isset( $meta['price_settings']['tour_price'] ) ? $meta['price_settings']['tour_price'] : '';
        $sale_price = isset( $meta['price_settings']['tour_sale_price'] ) ? $meta['price_settings']['tour_sale_price'] : '';
        $stock      = isset( $meta['booking_settings']['tour_stock'] ) ? $meta['booking_settings']['tour_stock'] : 20;
        
        // Clean prices
        $price = wc_format_decimal( $price );
        $sale_price = wc_format_decimal( $sale_price );

        // 3. Find Linked Product
        $product_id = get_post_meta( $post_id, '_ytrip_linked_product_id', true );
        
        $product = null;
        if ( $product_id ) {
            $product = wc_get_product( $product_id );
        }

        // 4. Logic Branching
        // If Inquiry Mode: Set product to draft (if exists) and return
        if ( 'inquiry' === $booking_method ) {
            if ( $product ) {
                $product->set_status( 'draft' );
                $product->set_stock_quantity( 0 );
                $product->save();
            }
            return;
        }

        // 5. Create or Update Product (WooCommerce Mode)
        if ( ! $product ) {
            $product = new WC_Product_Simple();
            $product->set_slug( $post->post_name . '-booking' );
        }
        
        $product->set_name( $post->post_title . ' (Booking)' );
        $product->set_status( $post->post_status ); // Sync status (publish/draft)
        
        // Visibility - Hidden from catalog
        $product->set_catalog_visibility( 'hidden' );
        $product->set_virtual( true ); // No shipping
        
        // Pricing
        $product->set_regular_price( $price );
        if ( ! empty( $sale_price ) ) {
            $product->set_sale_price( $sale_price );
        } else {
            $product->set_sale_price( '' );
        }
        
        // Stock
        $product->set_manage_stock( true );
        $product->set_stock_quantity( $stock );
        
        // Image
        if ( has_post_thumbnail( $post_id ) ) {
            $product->set_image_id( get_post_thumbnail_id( $post_id ) );
        }
        
        // Save
        $product_id = $product->save();
        
        // 5. Update Links
        update_post_meta( $post_id, '_ytrip_linked_product_id', $product_id );
        update_post_meta( $product_id, '_ytrip_linked_tour_id', $post_id );
        
        // Assign to 'Tours' category (optional: create if not exists)
        $term = wp_insert_term( 'Tours', 'product_cat' );
        if ( ! is_wp_error( $term ) ) {
             $term_id = isset( $term['term_id'] ) ? $term['term_id'] : $term;
             wp_set_object_terms( $product_id, $term_id, 'product_cat' );
        } elseif ( isset( $term->error_data['term_exists'] ) ) {
            wp_set_object_terms( $product_id, $term->error_data['term_exists'], 'product_cat' );
        }
    }
    
    /**
     * Hide Tour Products from main shop loop loop just in case visibility setting fails
     */
    public function hide_tour_products_from_catalog( $query ) {
        if ( ! is_admin() && $query->is_main_query() && ( $query->is_shop() || $query->is_product_category() ) ) {
            $tax_query = (array) $query->get( 'tax_query' );
            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'exclude-from-catalog',
                'operator' => 'IN',
            );
            //$query->set( 'tax_query', $tax_query ); // This is handled by WC visibility usually
        }
    }
    
    public function add_tour_product_fields() {
         echo '<div class="options_group">';

        woocommerce_wp_text_input( array(
            'id' => '_ytrip_linked_tour_id',
            'label' => __( 'Linked Tour ID', 'ytrip' ),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __( 'Enter the ID of the Tour CPT linked to this product.', 'ytrip' ),
            'custom_attributes' => array( 'readonly' => 'readonly' )
        ) );

        echo '</div>';
    }
    
    /**
     * Store custom booking data in cart session (date, guests, contact from form).
     */
    public function add_tour_booking_data_to_cart( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['tour_date'] ) ) {
            $cart_item_data['ytrip_tour_date'] = sanitize_text_field( wp_unslash( $_POST['tour_date'] ) );
        }
        if ( isset( $_POST['booking_date'] ) && empty( $cart_item_data['ytrip_tour_date'] ) ) {
            $cart_item_data['ytrip_tour_date'] = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) );
        }
        if ( isset( $_POST['adults'] ) ) {
            $cart_item_data['ytrip_adults'] = absint( $_POST['adults'] );
        }
        if ( isset( $_POST['children'] ) ) {
            $cart_item_data['ytrip_children'] = absint( $_POST['children'] );
        }
        if ( isset( $_POST['infants'] ) ) {
            $cart_item_data['ytrip_infants'] = absint( $_POST['infants'] );
        }
        if ( isset( $_POST['booking_email'] ) ) {
            $cart_item_data['ytrip_booking_email'] = sanitize_email( wp_unslash( $_POST['booking_email'] ) );
        }
        if ( isset( $_POST['booking_phone'] ) ) {
            $cart_item_data['ytrip_booking_phone'] = sanitize_text_field( wp_unslash( $_POST['booking_phone'] ) );
        }
        if ( isset( $_POST['booking_notes'] ) ) {
            $cart_item_data['ytrip_booking_notes'] = sanitize_textarea_field( wp_unslash( $_POST['booking_notes'] ) );
        }
        if ( isset( $_POST['booking_name'] ) ) {
            $cart_item_data['ytrip_booking_name'] = sanitize_text_field( wp_unslash( $_POST['booking_name'] ) );
        }
        return $cart_item_data;
    }

    /**
     * Display booking data in Cart and Checkout
     */
    public function display_tour_data_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['ytrip_tour_date'] ) ) {
            $item_data[] = array(
                'name'  => __( 'Travel Date', 'ytrip' ),
                'value' => $cart_item['ytrip_tour_date'],
            );
        }
        if ( isset( $cart_item['ytrip_adults'] ) ) {
            $item_data[] = array(
                'name'  => __( 'Adults', 'ytrip' ),
                'value' => $cart_item['ytrip_adults'],
            );
        }
        if ( isset( $cart_item['ytrip_children'] ) && $cart_item['ytrip_children'] > 0 ) {
            $item_data[] = array(
                'name'  => __( 'Children', 'ytrip' ),
                'value' => $cart_item['ytrip_children'],
            );
        }
        if ( isset( $cart_item['ytrip_infants'] ) && $cart_item['ytrip_infants'] > 0 ) {
            $item_data[] = array(
                'name'  => __( 'Infants', 'ytrip' ),
                'value' => $cart_item['ytrip_infants'],
            );
        }
        return $item_data;
    }

    /**
     * Save booking data to Order Line Item (date, guests, contact)
     */
    public function add_tour_data_to_order( $item, $cart_item_key, $values ) {
        if ( isset( $values['ytrip_tour_date'] ) ) {
            $item->add_meta_data( __( 'Travel Date', 'ytrip' ), $values['ytrip_tour_date'] );
        }
        if ( isset( $values['ytrip_adults'] ) ) {
            $item->add_meta_data( __( 'Adults', 'ytrip' ), $values['ytrip_adults'] );
        }
        if ( isset( $values['ytrip_children'] ) ) {
            $item->add_meta_data( __( 'Children', 'ytrip' ), $values['ytrip_children'] );
        }
        if ( isset( $values['ytrip_infants'] ) ) {
            $item->add_meta_data( __( 'Infants', 'ytrip' ), $values['ytrip_infants'] );
        }
        if ( ! empty( $values['ytrip_booking_email'] ) ) {
            $item->add_meta_data( __( 'Booking email', 'ytrip' ), $values['ytrip_booking_email'] );
        }
        if ( ! empty( $values['ytrip_booking_phone'] ) ) {
            $item->add_meta_data( __( 'Booking phone', 'ytrip' ), $values['ytrip_booking_phone'] );
        }
        if ( ! empty( $values['ytrip_booking_notes'] ) ) {
            $item->add_meta_data( __( 'Booking notes', 'ytrip' ), $values['ytrip_booking_notes'] );
        }
        if ( ! empty( $values['ytrip_booking_name'] ) ) {
            $item->add_meta_data( __( 'Booking name', 'ytrip' ), $values['ytrip_booking_name'] );
        }
    }

    /**
     * Persist session ytrip_booking_data to order meta for reference (backup when cart item data is present).
     */
    public function save_ytrip_booking_data_to_order( $order, $data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }
        $booking_data = WC()->session->get( 'ytrip_booking_data' );
        if ( ! is_array( $booking_data ) ) {
            return;
        }
        if ( ! empty( $booking_data['email'] ) ) {
            $order->update_meta_data( '_ytrip_booking_email', $booking_data['email'] );
        }
        if ( ! empty( $booking_data['phone'] ) ) {
            $order->update_meta_data( '_ytrip_booking_phone', $booking_data['phone'] );
        }
        if ( isset( $booking_data['notes'] ) && $booking_data['notes'] !== '' ) {
            $order->update_meta_data( '_ytrip_booking_notes', $booking_data['notes'] );
        }
        if ( ! empty( $booking_data['name'] ) ) {
            $order->update_meta_data( '_ytrip_booking_name', $booking_data['name'] );
        }
        $order->save();
    }

    /**
     * On order payment complete, create ytrip_bookings row for each tour item so admin Bookings list shows WC orders too.
     */
    public function maybe_create_ytrip_booking_on_order_paid( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ytrip_bookings';
        $user_id = $order->get_customer_id();
        $order_email = $order->get_billing_email();
        $order_phone = $order->get_billing_phone();
        $order_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_name = trim( $order_name ) ?: $order_email;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $tour_id = (int) get_post_meta( $product_id, '_ytrip_linked_tour_id', true );
            if ( $tour_id < 1 ) {
                continue;
            }
            $tour_date = $item->get_meta( __( 'Travel Date', 'ytrip' ) );
            if ( $tour_date === '' ) {
                $tour_date = $item->get_meta( 'Travel Date' );
            }
            if ( $tour_date === '' ) {
                continue;
            }
            $adults = (int) $item->get_meta( __( 'Adults', 'ytrip' ) );
            if ( $adults === 0 ) {
                $adults = (int) $item->get_meta( 'Adults' );
            }
            if ( $adults === 0 ) {
                $adults = 1;
            }
            $children = (int) $item->get_meta( __( 'Children', 'ytrip' ) ) ?: (int) $item->get_meta( 'Children' );
            $infants = (int) $item->get_meta( __( 'Infants', 'ytrip' ) ) ?: (int) $item->get_meta( 'Infants' );
            $booking_email = $item->get_meta( __( 'Booking email', 'ytrip' ) ) ?: $item->get_meta( 'Booking email' ) ?: $order_email;
            $booking_phone = $item->get_meta( __( 'Booking phone', 'ytrip' ) ) ?: $item->get_meta( 'Booking phone' ) ?: $order_phone;
            $booking_notes = $item->get_meta( __( 'Booking notes', 'ytrip' ) ) ?: $item->get_meta( 'Booking notes' );
            $booking_name = $item->get_meta( __( 'Booking name', 'ytrip' ) ) ?: $item->get_meta( 'Booking name' ) ?: $order_name;

            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM `{$table}` WHERE order_id = %d AND tour_id = %d LIMIT 1",
                $order_id,
                $tour_id
            ) );
            if ( $existing ) {
                continue;
            }
            $wpdb->insert(
                $table,
                array(
                    'tour_id'        => $tour_id,
                    'user_id'        => $user_id,
                    'order_id'       => $order_id,
                    'booking_date'   => $tour_date,
                    'adults'         => $adults,
                    'children'       => $children,
                    'infants'        => $infants,
                    'total_price'    => $item->get_total(),
                    'status'         => 'confirmed',
                    'customer_name'  => $booking_name,
                    'customer_email' => $booking_email,
                    'customer_phone' => $booking_phone,
                    'notes'          => $booking_notes,
                ),
                array( '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s' )
            );
        }
    }

    public function enable_tour_reviews( $args ) {
        return $args;
    }
}

new YTrip_WooCommerce_Integration();

