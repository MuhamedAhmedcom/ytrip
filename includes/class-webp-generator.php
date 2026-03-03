<?php
/**
 * WebP Image Generator
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class YTrip_WebP_Generator {

    public function __construct() {
        $options = get_option('ytrip_settings');
        if ( ! empty( $options['enable_webp'] ) ) {
            add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_webp' ), 10, 2 );
        }
    }

    public function generate_webp( $metadata, $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        
        if ( ! file_exists( $file ) ) return $metadata;

        // Convert original
        $this->convert_to_webp( $file );

        // Convert sizes
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size => $data ) {
                $size_file = path_join( dirname( $file ), $data['file'] );
                $this->convert_to_webp( $size_file );
            }
        }

        return $metadata;
    }

    private function convert_to_webp( $file ) {
        // Check if supported
        if ( ! function_exists( 'imagewebp' ) ) return false;

        $mime = wp_check_filetype( $file )['type'];
        if ( ! in_array( $mime, ['image/jpeg', 'image/png'] ) ) return false;

        $webp_file = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
        
        // Skip if exists
        if ( file_exists( $webp_file ) ) return true;

        $editor = wp_get_image_editor( $file );
        if ( ! is_wp_error( $editor ) ) {
            $editor->set_quality( 85 );
            $editor->save( $webp_file, 'image/webp' );
        }
    }
}

new YTrip_WebP_Generator();
