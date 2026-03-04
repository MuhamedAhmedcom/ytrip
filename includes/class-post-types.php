<?php
/**
 * YTrip Post Types
 *
 * Registers the tour CPT with a dynamic rewrite slug read from plugin settings.
 * Tour Details metabox works in both Classic Editor and Gutenberg (Document sidebar).
 *
 * @package YTrip
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class YTrip_Post_Types
 */
class YTrip_Post_Types {

	/**
	 * Internal post-type key (never changes, used for queries).
	 *
	 * @var string
	 */
	const TOUR_POST_TYPE = 'ytrip_tour';

	/**
	 * Constructor – hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_metabox_assets_for_block_editor' ), 9 );
		add_filter( 'csf_enqueue_assets', array( $this, 'force_csf_enqueue_for_tour_edit' ), 10, 1 );
	}

	/**
	 * Register custom post types.
	 *
	 * The rewrite slug is read dynamically from ytrip_settings so admins can
	 * change it from the Settings panel without editing PHP.
	 *
	 * @return void
	 */
	public function register_post_types() {
		$settings  = get_option( 'ytrip_settings', array() );

		// Support both flat key ('slug_tour') and fieldset key ('url_slugs' → 'slug_tour').
		$slug_tour = '';
		if ( ! empty( $settings['url_slugs']['slug_tour'] ) ) {
			$slug_tour = sanitize_title( $settings['url_slugs']['slug_tour'] );
		} elseif ( ! empty( $settings['slug_tour'] ) ) {
			$slug_tour = sanitize_title( $settings['slug_tour'] );
		}
		if ( $slug_tour === '' ) {
			$slug_tour = 'tours';
		}

		$labels = array(
			'name'                  => _x( 'Tours', 'Post type general name', 'ytrip' ),
			'singular_name'         => _x( 'Tour', 'Post type singular name', 'ytrip' ),
			'menu_name'             => esc_html__( 'Tours', 'ytrip' ),
			'name_admin_bar'        => esc_html__( 'Tour', 'ytrip' ),
			'add_new'               => esc_html__( 'Add New', 'ytrip' ),
			'add_new_item'          => esc_html__( 'Add New Tour', 'ytrip' ),
			'new_item'              => esc_html__( 'New Tour', 'ytrip' ),
			'edit_item'             => esc_html__( 'Edit Tour', 'ytrip' ),
			'view_item'             => esc_html__( 'View Tour', 'ytrip' ),
			'all_items'             => esc_html__( 'All Tours', 'ytrip' ),
			'search_items'          => esc_html__( 'Search Tours', 'ytrip' ),
			'parent_item_colon'     => esc_html__( 'Parent Tours:', 'ytrip' ),
			'not_found'             => esc_html__( 'No tours found.', 'ytrip' ),
			'not_found_in_trash'    => esc_html__( 'No tours found in Trash.', 'ytrip' ),
			'featured_image'        => esc_html__( 'Tour Cover Image', 'ytrip' ),
			'set_featured_image'    => esc_html__( 'Set cover image', 'ytrip' ),
			'remove_featured_image' => esc_html__( 'Remove cover image', 'ytrip' ),
			'use_featured_image'    => esc_html__( 'Use as cover image', 'ytrip' ),
			'archives'              => esc_html__( 'Tour archives', 'ytrip' ),
			'insert_into_item'      => esc_html__( 'Insert into tour', 'ytrip' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this tour', 'ytrip' ),
			'items_list'            => esc_html__( 'Tours list', 'ytrip' ),
			'items_list_navigation' => esc_html__( 'Tours list navigation', 'ytrip' ),
			'filter_items_list'     => esc_html__( 'Filter tours list', 'ytrip' ),
		);

		register_post_type(
			self::TOUR_POST_TYPE,
			array(
				'labels'              => $labels,
				'description'         => esc_html__( 'Travel tours and holiday packages.', 'ytrip' ),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'query_var'           => true,
				'capability_type'     => 'post',
				'has_archive'         => true,
				'hierarchical'        => false,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-airplane',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'author', 'revisions' ),
				'show_in_rest'        => true,
				'rest_base'           => 'ytrip-tours',
				'rewrite'             => array(
					'slug'       => $slug_tour,
					'with_front' => false,
					'feeds'      => true,
					'pages'      => true,
				),
			)
		);
	}

	/**
	 * Force CodeStar Framework to enqueue assets when editing a tour (Classic or Gutenberg).
	 * Ensures Tour Details metabox has styles/scripts in both editors.
	 *
	 * @param bool $enqueue Whether CSF should enqueue.
	 * @return bool
	 */
	public function force_csf_enqueue_for_tour_edit( $enqueue ) {
		if ( ! is_admin() ) {
			return $enqueue;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! empty( $screen->post_type ) && $screen->post_type === self::TOUR_POST_TYPE ) {
			return true;
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( $post_id && get_post_type( $post_id ) === self::TOUR_POST_TYPE ) {
			return true;
		}
		return $enqueue;
	}

	/**
	 * Enqueue CSF assets in block editor so Tour Details metabox in sidebar renders correctly.
	 *
	 * @return void
	 */
	public function enqueue_metabox_assets_for_block_editor() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id || get_post_type( $post_id ) !== self::TOUR_POST_TYPE ) {
			return;
		}
		if ( class_exists( 'CSF' ) && method_exists( 'CSF', 'add_admin_enqueue_scripts' ) ) {
			CSF::add_admin_enqueue_scripts();
		}
	}
}

new YTrip_Post_Types();
