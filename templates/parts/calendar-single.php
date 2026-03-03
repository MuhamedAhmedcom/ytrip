<?php
/**
 * Shared Single-Date Calendar Component
 * Same UI as booking form date picker. Use on homepage, booking form, etc.
 * JS: ytrip-calendar.js inits via data-ytrip-calendar="single".
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$args = isset( $args ) && is_array( $args ) ? $args : array();
$args = array_merge( array(
	'display_id'    => 'ytrip-date-display',
	'hidden_name'   => 'tour_date',
	'hidden_id'     => 'ytrip-tour-date',
	'container_id'  => 'ytrip-calendar-container',
	'value'         => '',
	'placeholder'   => __( 'Select date', 'ytrip' ),
	'required'      => false,
	'wrapper_class' => '',
	'label_for'     => '',
), $args );

$display_id    = sanitize_html_class( $args['display_id'] );
$hidden_name   = sanitize_text_field( $args['hidden_name'] );
$hidden_id     = sanitize_html_class( $args['hidden_id'] );
$container_id  = sanitize_html_class( $args['container_id'] );
$value         = is_string( $args['value'] ) ? $args['value'] : '';
$placeholder   = esc_attr( $args['placeholder'] );
$required      = ! empty( $args['required'] );
$wrapper_class = isset( $args['wrapper_class'] ) ? sanitize_html_class( $args['wrapper_class'] ) : '';
$label_for     = isset( $args['label_for'] ) && $args['label_for'] !== '' ? sanitize_html_class( $args['label_for'] ) : $display_id;
?>
<div class="ytrip-calendar-input-wrapper <?php echo esc_attr( $wrapper_class ); ?>" data-ytrip-calendar="single" data-display-id="<?php echo esc_attr( $display_id ); ?>" data-hidden-id="<?php echo esc_attr( $hidden_id ); ?>" data-container-id="<?php echo esc_attr( $container_id ); ?>">
	<input type="text" id="<?php echo esc_attr( $display_id ); ?>" class="ytrip-date-display" placeholder="<?php echo $placeholder; ?>" readonly aria-label="<?php esc_attr_e( 'Selected date', 'ytrip' ); ?>"<?php echo $required ? ' required' : ''; ?>>
	<input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" id="<?php echo esc_attr( $hidden_id ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo $required ? ' required' : ''; ?>>
	<svg class="ytrip-calendar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
		<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
		<line x1="16" y1="2" x2="16" y2="6"></line>
		<line x1="8" y1="2" x2="8" y2="6"></line>
		<line x1="3" y1="10" x2="21" y2="10"></line>
	</svg>
	<div class="ytrip-calendar-dropdown" id="<?php echo esc_attr( $container_id ); ?>" role="dialog" aria-label="<?php esc_attr_e( 'Date calendar', 'ytrip' ); ?>"></div>
</div>
