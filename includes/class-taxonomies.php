<?php
/**
 * YTrip Taxonomies
 *
 * Registers destination and category taxonomies with dynamic rewrite slugs
 * read from plugin settings so admins can change them without editing PHP.
 *
 * @package YTrip
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YTrip_Taxonomies
 */
class YTrip_Taxonomies {

	/**
	 * Internal taxonomy keys (never change, used for queries and term storage).
	 *
	 * @var string
	 */
	const TAXONOMY_DESTINATION = 'ytrip_destination';
	const TAXONOMY_CATEGORY    = 'ytrip_category';

	/**
	 * Constructor – hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register custom taxonomies.
	 *
	 * Rewrite slugs are read dynamically from ytrip_settings.  Both flat-key
	 * storage ('slug_destination') and fieldset storage ('url_slugs → slug_destination')
	 * are supported to remain backward-compatible with any existing installs.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		$settings = get_option( 'ytrip_settings', array() );

		// Resolve slugs – support CSF fieldset key ('url_slugs') or flat key.
		$slug_destination = $this->resolve_slug( $settings, 'slug_destination', 'destination' );
		$slug_category    = $this->resolve_slug( $settings, 'slug_category', 'tour-category' );

		// ------------------------------------------------------------------ //
		// Destination taxonomy                                                 //
		// ------------------------------------------------------------------ //
		$destination_labels = array(
			'name'                       => _x( 'Destinations', 'taxonomy general name', 'ytrip' ),
			'singular_name'              => _x( 'Destination', 'taxonomy singular name', 'ytrip' ),
			'search_items'               => esc_html__( 'Search Destinations', 'ytrip' ),
			'popular_items'              => esc_html__( 'Popular Destinations', 'ytrip' ),
			'all_items'                  => esc_html__( 'All Destinations', 'ytrip' ),
			'parent_item'                => esc_html__( 'Parent Destination', 'ytrip' ),
			'parent_item_colon'          => esc_html__( 'Parent Destination:', 'ytrip' ),
			'edit_item'                  => esc_html__( 'Edit Destination', 'ytrip' ),
			'view_item'                  => esc_html__( 'View Destination', 'ytrip' ),
			'update_item'                => esc_html__( 'Update Destination', 'ytrip' ),
			'add_new_item'               => esc_html__( 'Add New Destination', 'ytrip' ),
			'new_item_name'              => esc_html__( 'New Destination Name', 'ytrip' ),
			'separate_items_with_commas' => esc_html__( 'Separate destinations with commas', 'ytrip' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove destinations', 'ytrip' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used destinations', 'ytrip' ),
			'not_found'                  => esc_html__( 'No destinations found.', 'ytrip' ),
			'no_terms'                   => esc_html__( 'No destinations', 'ytrip' ),
			'items_list_navigation'      => esc_html__( 'Destinations list navigation', 'ytrip' ),
			'items_list'                 => esc_html__( 'Destinations list', 'ytrip' ),
			'back_to_items'              => esc_html__( '&larr; Go to Destinations', 'ytrip' ),
			'menu_name'                  => esc_html__( 'Destinations', 'ytrip' ),
		);

		register_taxonomy(
			self::TAXONOMY_DESTINATION,
			YTrip_Post_Types::TOUR_POST_TYPE,
			array(
				'labels'            => $destination_labels,
				'description'       => esc_html__( 'Geographic destinations for tours.', 'ytrip' ),
				'public'            => true,
				'publicly_queryable' => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => true,
				'show_in_quick_edit' => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rest_base'         => 'ytrip-destinations',
				'rewrite'           => array(
					'slug'         => $slug_destination,
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);

		// ------------------------------------------------------------------ //
		// Category taxonomy                                                    //
		// ------------------------------------------------------------------ //
		$category_labels = array(
			'name'                       => _x( 'Tour Categories', 'taxonomy general name', 'ytrip' ),
			'singular_name'              => _x( 'Tour Category', 'taxonomy singular name', 'ytrip' ),
			'search_items'               => esc_html__( 'Search Tour Categories', 'ytrip' ),
			'popular_items'              => esc_html__( 'Popular Tour Categories', 'ytrip' ),
			'all_items'                  => esc_html__( 'All Tour Categories', 'ytrip' ),
			'parent_item'                => esc_html__( 'Parent Tour Category', 'ytrip' ),
			'parent_item_colon'          => esc_html__( 'Parent Tour Category:', 'ytrip' ),
			'edit_item'                  => esc_html__( 'Edit Tour Category', 'ytrip' ),
			'view_item'                  => esc_html__( 'View Tour Category', 'ytrip' ),
			'update_item'                => esc_html__( 'Update Tour Category', 'ytrip' ),
			'add_new_item'               => esc_html__( 'Add New Tour Category', 'ytrip' ),
			'new_item_name'              => esc_html__( 'New Tour Category Name', 'ytrip' ),
			'separate_items_with_commas' => esc_html__( 'Separate categories with commas', 'ytrip' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove categories', 'ytrip' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used categories', 'ytrip' ),
			'not_found'                  => esc_html__( 'No tour categories found.', 'ytrip' ),
			'no_terms'                   => esc_html__( 'No tour categories', 'ytrip' ),
			'items_list_navigation'      => esc_html__( 'Tour Categories list navigation', 'ytrip' ),
			'items_list'                 => esc_html__( 'Tour Categories list', 'ytrip' ),
			'back_to_items'              => esc_html__( '&larr; Go to Tour Categories', 'ytrip' ),
			'menu_name'                  => esc_html__( 'Categories', 'ytrip' ),
		);

		register_taxonomy(
			self::TAXONOMY_CATEGORY,
			YTrip_Post_Types::TOUR_POST_TYPE,
			array(
				'labels'             => $category_labels,
				'description'        => esc_html__( 'Type or category of tours.', 'ytrip' ),
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_tagcloud'      => true,
				'show_in_quick_edit' => true,
				'show_admin_column'  => true,
				'query_var'          => true,
				'show_in_rest'       => true,
				'rest_base'          => 'ytrip-categories',
				'rewrite'            => array(
					'slug'         => $slug_category,
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	}

	/**
	 * Resolve a slug setting, checking fieldset ('url_slugs') then flat key,
	 * and falling back to the supplied default.
	 *
	 * @param array  $settings Plugin settings array.
	 * @param string $key      Setting key name (e.g. 'slug_destination').
	 * @param string $fallback Default slug value.
	 * @return string Sanitized slug.
	 */
	private function resolve_slug( array $settings, string $key, string $fallback ): string {
		if ( ! empty( $settings['url_slugs'][ $key ] ) && is_string( $settings['url_slugs'][ $key ] ) ) {
			return sanitize_title( $settings['url_slugs'][ $key ] );
		}
		if ( ! empty( $settings[ $key ] ) && is_string( $settings[ $key ] ) ) {
			return sanitize_title( $settings[ $key ] );
		}
		return $fallback;
	}
}

new YTrip_Taxonomies();
