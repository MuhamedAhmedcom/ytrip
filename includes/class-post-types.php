<?php
/**
 * YTrip Post Types
 *
 * Registers the tour CPT. Tour Details metabox works in both Classic Editor
 * and Gutenberg (shown in Document sidebar in block editor).
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Post_Types {

    const TOUR_POST_TYPE = 'ytrip_tour';

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_metabox_assets_for_block_editor' ), 9 );
        add_filter( 'csf_enqueue_assets', array( $this, 'force_csf_enqueue_for_tour_edit' ), 10, 1 );
    }

    /**
     * Register custom post types.
     */
    public function register_post_types() {
        register_post_type( self::TOUR_POST_TYPE, array(
            'labels'       => array(
                'name'          => __( 'Tours', 'ytrip' ),
                'singular_name' => __( 'Tour', 'ytrip' ),
            ),
            'public'       => true,
            'has_archive'  => true,
            'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
            'show_in_rest' => true,
        ) );
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
