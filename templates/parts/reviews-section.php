<?php
/**
 * Reviews Section for Single Tour
 *
 * @package YTrip
 */

if (!defined("ABSPATH")) exit;

$tour_id = get_the_ID();

if (class_exists("YTrip_Rating_Display")) :
    echo YTrip_Rating_Display::instance()->render_reviews_section($tour_id);
endif;
