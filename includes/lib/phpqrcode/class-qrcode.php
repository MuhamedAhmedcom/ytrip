<?php
/**
 * Simple QR Code Generator
 * 
 * A lightweight, self-contained QR code generator that outputs SVG format.
 * No external dependencies required.
 * 
 * @package YTrip
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_QRCode {

    private static $instance = null;
    
    private $size = 300;
    private $margin = 10;
    private $dark_color = '#000000';
    private $light_color = '#ffffff';
    private $error_correction = 'M';

    private $qr_code = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
    }

    public function set_size( $size ) {
        $this->size = absint( $size );
        return $this;
    }

    public function set_margin( $margin ) {
        $this->margin = absint( $margin );
        return $this;
    }

    public function set_colors( $dark, $light ) {
        $this->dark_color = sanitize_hex_color( $dark );
        $this->light_color = sanitize_hex_color( $light );
        return $this;
    }

    public function set_error_correction( $level ) {
        $levels = array( 'L', 'M', 'Q', 'H' );
        if ( in_array( strtoupper( $level ), $levels ) ) {
            $this->error_correction = strtoupper( $level );
        }
        return $this;
    }

    public function generate( $data ) {
        $matrix = $this->create_qr_matrix( $data );
        if ( ! $matrix ) {
            return false;
        }
        $this->qr_code = $matrix;
        return $this;
    }

    public function to_svg() {
        if ( ! $this->qr_code ) {
            return false;
        }

        $matrix = $this->qr_code;
        $module_count = count( $matrix );
        
        $size = $this->size - ( 2 * $this->margin );
        $module_size = $size / $module_count;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"';
        $svg .= ' viewBox="0 0 ' . $this->size . ' ' . $this->size . '"';
        $svg .= ' width="' . $this->size . '" height="' . $this->size . '">' . "\n";
        $svg .= "\t" . '<rect width="100%" height="100%" fill="' . esc_attr( $this->light_color ) . '"/>' . "\n";
        $svg .= "\t" . '<g fill="' . esc_attr( $this->dark_color ) . '">' . "\n";

        for ( $row = 0; $row < $module_count; $row++ ) {
            for ( $col = 0; $col < $module_count; $col++ ) {
                if ( $matrix[ $row ][ $col ] ) {
                    $x = $this->margin + ( $col * $module_size );
                    $y = $this->margin + ( $row * $module_size );
                    $svg .= "\t\t" . sprintf(
                        '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f"/>',
                        $x, $y, $module_size, $module_size
                    ) . "\n";
                }
            }
        }

        $svg .= "\t" . '</g>' . "\n";
        $svg .= '</svg>';

        return $svg;
    }

    public function to_png() {
        if ( ! $this->qr_code ) {
            return false;
        }

        $matrix = $this->qr_code;
        $module_count = count( $matrix );
        
        $size = $this->size - ( 2 * $this->margin );
        $module_size = $size / $module_count;

        $image = imagecreatetruecolor( $this->size, $this->size );
        
        $dark = $this->hex_to_rgb( $this->dark_color );
        $light = $this->hex_to_rgb( $this->light_color );
        
        $dark_color = imagecolorallocate( $image, $dark['r'], $dark['g'], $dark['b'] );
        $light_color = imagecolorallocate( $image, $light['r'], $light['g'], $light['b'] );
        
        imagefill( $image, 0, 0, $light_color );

        for ( $row = 0; $row < $module_count; $row++ ) {
            for ( $col = 0; $col < $module_count; $col++ ) {
                if ( $matrix[ $row ][ $col ] ) {
                    $x = (int) ( $this->margin + ( $col * $module_size ) );
                    $y = (int) ( $this->margin + ( $row * $module_size ) );
                    $w = (int) ceil( $module_size );
                    $h = (int) ceil( $module_size );
                    imagefilledrectangle( $image, $x, $y, $x + $w, $y + $h, $dark_color );
                }
            }
        }

        ob_start();
        imagepng( $image );
        $png_data = ob_get_clean();
        imagedestroy( $image );

        return $png_data;
    }

    public function to_base64() {
        $png = $this->to_png();
        if ( ! $png ) {
            return false;
        }
        return 'data:image/png;base64,' . base64_encode( $png );
    }

    public function save_to_file( $filepath, $format = 'png' ) {
        $data = ( $format === 'svg' ) ? $this->to_svg() : $this->to_png();
        if ( ! $data ) {
            return false;
        }
        
        if ( ! wp_mkdir_p( dirname( $filepath ) ) ) {
            return false;
        }
        
        return file_put_contents( $filepath, $data ) !== false;
    }

    private function create_qr_matrix( $data ) {
        $data = (string) $data;
        $length = strlen( $data );
        
        $version = $this->get_min_version( $length );
        $module_count = $version * 4 + 17;
        
        $matrix = array();
        for ( $i = 0; $i < $module_count; $i++ ) {
            $matrix[ $i ] = array_fill( 0, $module_count, false );
        }

        $this->add_finder_patterns( $matrix, $module_count );
        $this->add_alignment_pattern( $matrix, $module_count, $version );
        $this->add_timing_patterns( $matrix, $module_count );
        $this->add_dark_module( $matrix, $module_count, $version );
        
        $codewords = $this->encode_data( $data, $version );
        $this->place_data_bits( $matrix, $codewords, $module_count );
        $this->apply_mask( $matrix, $module_count );

        return $matrix;
    }

    private function get_min_version( $length ) {
        $capacities = array(
            1 => 17, 2 => 32, 3 => 53, 4 => 78, 5 => 106,
            6 => 134, 7 => 154, 8 => 192, 9 => 230, 10 => 271
        );

        for ( $v = 1; $v <= 10; $v++ ) {
            if ( $length <= $capacities[ $v ] ) {
                return $v;
            }
        }
        return 10;
    }

    private function add_finder_patterns( &$matrix, $module_count ) {
        $pattern = array(
            array( true, true, true, true, true, true, true ),
            array( true, false, false, false, false, false, true ),
            array( true, false, true, true, true, false, true ),
            array( true, false, true, true, true, false, true ),
            array( true, false, true, true, true, false, true ),
            array( true, false, false, false, false, false, true ),
            array( true, true, true, true, true, true, true )
        );

        $positions = array(
            array( 0, 0 ),
            array( $module_count - 7, 0 ),
            array( 0, $module_count - 7 )
        );

        foreach ( $positions as $pos ) {
            for ( $r = 0; $r < 7; $r++ ) {
                for ( $c = 0; $c < 7; $c++ ) {
                    $matrix[ $pos[0] + $r ][ $pos[1] + $c ] = $pattern[ $r ][ $c ];
                }
            }
        }

        for ( $i = 0; $i < 8; $i++ ) {
            if ( $matrix[7][$i] === false ) $matrix[7][$i] = null;
            if ( $matrix[$i][7] === false ) $matrix[$i][7] = null;
            if ( $matrix[$module_count - 8][$i] === false ) $matrix[$module_count - 8][$i] = null;
            if ( $matrix[$i][$module_count - 8] === false ) $matrix[$i][$module_count - 8] = null;
        }
    }

    private function add_alignment_pattern( &$matrix, $module_count, $version ) {
        if ( $version < 2 ) {
            return;
        }

        $pattern = array(
            array( true, true, true, true, true ),
            array( true, false, false, false, true ),
            array( true, false, true, false, true ),
            array( true, false, false, false, true ),
            array( true, true, true, true, true )
        );

        $positions = $this->get_alignment_positions( $version );
        
        foreach ( $positions as $row ) {
            foreach ( $positions as $col ) {
                if ( $matrix[ $row ][ $col ] !== false ) {
                    continue;
                }
                for ( $r = -2; $r <= 2; $r++ ) {
                    for ( $c = -2; $c <= 2; $c++ ) {
                        $matrix[ $row + $r ][ $col + $c ] = $pattern[ $r + 2 ][ $c + 2 ];
                    }
                }
            }
        }
    }

    private function get_alignment_positions( $version ) {
        if ( $version === 1 ) {
            return array();
        }

        $positions = array( 6 );
        $interval = (int) ( ( $version * 4 + 10 ) / ( count( $positions ) ) );
        
        while ( count( $positions ) <= $version / 7 + 1 ) {
            $positions[] = end( $positions ) + $interval;
        }
        
        $positions[ count( $positions ) - 1 ] = $version * 4 + 10;
        
        return $positions;
    }

    private function add_timing_patterns( &$matrix, $module_count ) {
        for ( $i = 8; $i < $module_count - 8; $i++ ) {
            if ( $matrix[6][$i] === false ) {
                $matrix[6][$i] = ( $i % 2 === 0 );
            }
            if ( $matrix[$i][6] === false ) {
                $matrix[$i][6] = ( $i % 2 === 0 );
            }
        }
    }

    private function add_dark_module( &$matrix, $module_count, $version ) {
        $matrix[ $module_count - 8 ][8] = true;
    }

    private function encode_data( $data, $version ) {
        $mode_indicator = '0100';
        $char_count_bits = $this->get_char_count_bits( $version );
        
        $char_count = sprintf( '%0' . $char_count_bits . 'b', strlen( $data ) );
        
        $data_bits = $mode_indicator . $char_count;
        
        for ( $i = 0; $i < strlen( $data ); $i++ ) {
            $byte = sprintf( '%08b', ord( $data[$i] ) );
            $data_bits .= $byte;
        }
        
        $total_codewords = $this->get_total_codewords( $version );
        $data_codewords = $this->get_data_codewords( $version );
        $ec_codewords = $total_codewords - $data_codewords;
        
        while ( strlen( $data_bits ) < $data_codewords * 8 ) {
            $data_bits .= '0';
        }
        
        $pad_patterns = array( '11101100', '00010001' );
        $pad_index = 0;
        while ( strlen( $data_bits ) < $data_codewords * 8 ) {
            $data_bits .= $pad_patterns[ $pad_index % 2 ];
            $pad_index++;
        }

        $codewords = array();
        for ( $i = 0; $i < strlen( $data_bits ); $i += 8 ) {
            $codewords[] = substr( $data_bits, $i, 8 );
        }

        return $codewords;
    }

    private function get_char_count_bits( $version ) {
        if ( $version <= 9 ) return 8;
        if ( $version <= 26 ) return 16;
        return 16;
    }

    private function get_total_codewords( $version ) {
        $codewords = array(
            1 => 26, 2 => 44, 3 => 70, 4 => 100, 5 => 134,
            6 => 172, 7 => 196, 8 => 242, 9 => 292, 10 => 346
        );
        return $codewords[ $version ] ?? 26;
    }

    private function get_data_codewords( $version ) {
        $ec_levels = array(
            'L' => array( 1 => 19, 2 => 34, 3 => 55, 4 => 80, 5 => 108, 6 => 136, 7 => 156, 8 => 194, 9 => 232, 10 => 274 ),
            'M' => array( 1 => 16, 2 => 28, 3 => 44, 4 => 64, 5 => 86, 6 => 108, 7 => 124, 8 => 154, 9 => 182, 10 => 216 ),
            'Q' => array( 1 => 13, 2 => 22, 3 => 34, 4 => 48, 5 => 62, 6 => 76, 7 => 88, 8 => 110, 9 => 132, 10 => 154 ),
            'H' => array( 1 => 9, 2 => 16, 3 => 26, 4 => 36, 5 => 46, 6 => 60, 7 => 66, 8 => 86, 9 => 100, 10 => 122 )
        );
        return $ec_levels[ $this->error_correction ][ $version ] ?? 16;
    }

    private function place_data_bits( &$matrix, $codewords, $module_count ) {
        $bit_index = 0;
        $up = true;
        
        for ( $col = $module_count - 1; $col > 0; $col -= 2 ) {
            if ( $col === 6 ) $col = 5;
            
            for ( $row = 0; $row < $module_count; $row++ ) {
                $current_row = $up ? $module_count - 1 - $row : $row;
                
                for ( $c = 0; $c < 2; $c++ ) {
                    $current_col = $col - $c;
                    
                    if ( $matrix[ $current_row ][ $current_col ] === false ) {
                        $codeword_index = (int) ( $bit_index / 8 );
                        $bit_in_codeword = 7 - ( $bit_index % 8 );
                        
                        if ( isset( $codewords[ $codeword_index ] ) ) {
                            $bit = $codewords[ $codeword_index ][ $bit_in_codeword ] === '1';
                            $matrix[ $current_row ][ $current_col ] = $bit;
                        }
                        
                        $bit_index++;
                    }
                }
            }
            $up = ! $up;
        }
    }

    private function apply_mask( &$matrix, $module_count ) {
        for ( $row = 0; $row < $module_count; $row++ ) {
            for ( $col = 0; $col < $module_count; $col++ ) {
                if ( $matrix[ $row ][ $col ] !== null && $matrix[ $row ][ $col ] !== false ) {
                    if ( ( $row + $col ) % 2 === 0 ) {
                        $matrix[ $row ][ $col ] = ! $matrix[ $row ][ $col ];
                    }
                }
            }
        }
    }

    private function hex_to_rgb( $hex ) {
        $hex = ltrim( $hex, '#' );
        return array(
            'r' => hexdec( substr( $hex, 0, 2 ) ),
            'g' => hexdec( substr( $hex, 2, 2 ) ),
            'b' => hexdec( substr( $hex, 4, 2 ) )
        );
    }
}

function ytrip_qrcode() {
    return YTrip_QRCode::instance();
}
