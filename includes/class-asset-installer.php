<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class YTrip_Asset_Installer
 * 
 * Handles generation of necessary placeholder images if they don't exist.
 * This ensures the plugin looks good even if image assets weren't uploaded correctly.
 */
class YTrip_Asset_Installer {

    /**
     * Install assets.
     */
    public static function install() {
        if ( ! extension_loaded('gd') ) {
            return;
        }

        self::generate_admin_placeholders();
        self::generate_preset_previews();
    }

    /**
     * Generate admin layout/card placeholders.
     */
    private static function generate_admin_placeholders() {
        $base_dir = YTRIP_PATH . 'assets/admin/img/';
        if ( ! file_exists( $base_dir ) ) {
            wp_mkdir_p( $base_dir );
        }

        $images = [
            'layout-grid' => 'Grid',
            'layout-list' => 'List',
            'layout-carousel' => 'Carousel',
            'layout-map' => 'Map',
            'card-classic' => 'Classic',
            'card-modern' => 'Modern',
            'card-overlay' => 'Overlay',
            'card-glass' => 'Glass',
            'card-minimal' => 'Minimal',
            'single-1' => 'Single 1',
            'single-2' => 'Single 2',
            'single-3' => 'Single 3',
            'single-4' => 'Single 4',
            'single-5' => 'Single 5',
        ];

        foreach ( $images as $filename => $text ) {
            $path = $base_dir . $filename . '.png';
            if ( ! file_exists( $path ) ) {
                self::create_placeholder_image( $path, $text, 100, 75, [240, 248, 255] ); // AliceBlue
            }
        }
    }

    /**
     * Generate color preset preview images.
     */
    private static function generate_preset_previews() {
        $base_dir = YTRIP_PATH . 'assets/admin/img/presets/';
        if ( ! file_exists( $base_dir ) ) {
            wp_mkdir_p( $base_dir );
        }

        $presets = [
            'ocean'     => [0, 119, 182],      // #0077b6
            'tropical'  => [6, 214, 160],      // #06d6a0
            'desert'    => [193, 119, 103],    // #c17767
            'mountain'  => [45, 106, 79],      // #2d6a4f
            'sunset'    => [229, 107, 111],    // #e56b6f
            'arctic'    => [72, 202, 228],     // #48cae4
            'luxury'    => [26, 26, 46],       // #1a1a2e
            'royal'     => [107, 45, 92],      // #6b2d5c
            'tech'      => [37, 99, 235],      // #2563eb
            'dark'      => [15, 23, 42],       // #0f172a
            'egyptian'  => [26, 54, 93],       // #1a365d
            'asian'     => [74, 85, 104],      // #4a5568
            'spring'    => [5, 150, 105],      // #059669
            'summer'    => [14, 165, 233],     // #0ea5e9
            'autumn'    => [180, 83, 9],       // #b45309
            'winter'    => [3, 105, 161],      // #0369a1
            'custom'    => [170, 170, 170],    // #aaaaaa
        ];

        foreach ( $presets as $name => $rgb ) {
            $path = $base_dir . $name . '.png';
            if ( ! file_exists( $path ) ) {
                self::create_placeholder_image( $path, '', 80, 80, $rgb, true );
            }
        }
    }

    /**
     * Create a single placeholder image using GD.
     * 
     * @param string $path File path
     * @param string $text Text to write
     * @param int $width Width
     * @param int $height Height
     * @param array $bg_rgb Background RGB array
     * @param bool $border Draw border?
     */
    private static function create_placeholder_image( string $path, string $text, int $width, int $height, array $bg_rgb, bool $border = false ) {
        $im = @imagecreatetruecolor( $width, $height );
        if ( ! $im ) {
            return;
        }

        $bg = imagecolorallocate( $im, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
        imagefilledrectangle( $im, 0, 0, $width, $height, $bg );

        if ( $border ) {
            $black = imagecolorallocate( $im, 0, 0, 0 );
            imagerectangle( $im, 0, 0, $width - 1, $height - 1, $black );
        }

        if ( ! empty( $text ) ) {
            $text_color = imagecolorallocate( $im, 0, 0, 0 );
            // Centering text roughly (GD fonts are limited without TTF)
            $font = 2; // System font
            $font_w = imagefontwidth( $font );
            $font_h = imagefontheight( $font );
            $text_w = strlen( $text ) * $font_w;
            $x = (int) ( ( $width - $text_w ) / 2 );
            $y = (int) ( ( $height - $font_h ) / 2 );
            imagestring( $im, $font, $x, $y, $text, $text_color );
        }

        imagepng( $im, $path );
        imagedestroy( $im );
    }
}
