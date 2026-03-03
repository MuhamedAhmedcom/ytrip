<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class YTrip_Taxonomies {
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
    }
    public function register_taxonomies() {
        register_taxonomy('ytrip_destination', 'ytrip_tour', array(
            'label' => 'Destinations',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
        register_taxonomy('ytrip_category', 'ytrip_tour', array(
            'label' => 'Categories',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
}
new YTrip_Taxonomies();
