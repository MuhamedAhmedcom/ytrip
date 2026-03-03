<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function ytrip_generate_dynamic_css() {
    // Deprecated: delegated to YTrip_Brand_System
    if ( function_exists('ytrip_brand') ) {
        return ytrip_brand()->generate_css_variables();
    }
    return '';
}

// Hook removed as YTrip_Brand_System handles this now.
