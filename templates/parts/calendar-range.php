<?php
/**
 * Shared Date-Range Calendar Component
 * Same UI as archive filter date range. Use on homepage search form.
 * JS: ytrip-calendar.js inits via data-ytrip-calendar="range".
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$args = isset( $args ) && is_array( $args ) ? $args : array();
$args = array_merge( array(
	'display_id'       => 'ytrip-date-range-display',
	'from_display_id'  => '',
	'to_display_id'    => '',
	'from_name'        => 'date_from',
	'to_name'          => 'date_to',
	'from_id'          => 'date_from',
	'to_id'            => 'date_to',
	'container_id'     => 'ytrip-date-range-calendar',
	'value_from'       => '',
	'value_to'         => '',
	'placeholder'      => __( 'Select start date, then end date', 'ytrip' ),
	'placeholder_from' => __( 'Check-in', 'ytrip' ),
	'placeholder_to'   => __( 'Check-out', 'ytrip' ),
	'wrapper_class'    => '',
	'show_hint'        => true,
	'two_fields'        => false,
), $args );
$display_id       = sanitize_html_class( $args['display_id'] );
$from_display_id  = ! empty( $args['from_display_id'] ) ? sanitize_html_class( $args['from_display_id'] ) : '';
$to_display_id    = ! empty( $args['to_display_id'] ) ? sanitize_html_class( $args['to_display_id'] ) : '';
$from_name        = sanitize_text_field( $args['from_name'] );
$to_name          = sanitize_text_field( $args['to_name'] );
$from_id          = sanitize_html_class( $args['from_id'] );
$to_id            = sanitize_html_class( $args['to_id'] );
$container_id     = sanitize_html_class( $args['container_id'] );
$value_from       = is_string( $args['value_from'] ) ? $args['value_from'] : '';
$value_to         = is_string( $args['value_to'] ) ? $args['value_to'] : '';
$placeholder      = esc_attr( $args['placeholder'] );
$placeholder_from = esc_attr( $args['placeholder_from'] );
$placeholder_to   = esc_attr( $args['placeholder_to'] );
$wrapper_class    = sanitize_html_class( $args['wrapper_class'] );
$show_hint        = (bool) $args['show_hint'];
$two_fields       = (bool) $args['two_fields'];
$data_attrs       = ' data-display-id="' . esc_attr( $display_id ) . '" data-from-id="' . esc_attr( $from_id ) . '" data-to-id="' . esc_attr( $to_id ) . '" data-container-id="' . esc_attr( $container_id ) . '"';
if ( $two_fields && $from_display_id && $to_display_id ) {
	$data_attrs .= ' data-from-display-id="' . esc_attr( $from_display_id ) . '" data-to-display-id="' . esc_attr( $to_display_id ) . '"';
}
?>
<div class="ytrip-date-range-calendar-wrapper <?php echo esc_attr( $wrapper_class ); ?> <?php echo $two_fields ? 'ytrip-date-range--two-fields' : ''; ?>" data-ytrip-calendar="range"<?php echo $data_attrs; ?>>
	<?php if ( $two_fields && $from_display_id && $to_display_id ) : ?>
	<div class="ytrip-date-range-input-row ytrip-date-range-two-fields">
		<div class="ytrip-date-range-field-from">
			<label for="<?php echo esc_attr( $from_display_id ); ?>" class="ytrip-sr-only"><?php esc_attr_e( 'Check-in', 'ytrip' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $from_display_id ); ?>" class="ytrip-input ytrip-date-range-display ytrip-date-range-display-from" placeholder="<?php echo $placeholder_from; ?>" readonly autocomplete="off" aria-haspopup="dialog" aria-expanded="false" aria-label="<?php esc_attr_e( 'Check-in date', 'ytrip' ); ?>">
		</div>
		<div class="ytrip-date-range-field-to">
			<label for="<?php echo esc_attr( $to_display_id ); ?>" class="ytrip-sr-only"><?php esc_attr_e( 'Check-out', 'ytrip' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $to_display_id ); ?>" class="ytrip-input ytrip-date-range-display ytrip-date-range-display-to" placeholder="<?php echo $placeholder_to; ?>" readonly autocomplete="off" aria-haspopup="dialog" aria-expanded="false" aria-label="<?php esc_attr_e( 'Check-out date', 'ytrip' ); ?>">
		</div>
		<span class="ytrip-date-range-calendar-icon" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
		</span>
	</div>
	<?php else : ?>
	<div class="ytrip-date-range-input-row">
		<label for="<?php echo esc_attr( $display_id ); ?>" class="ytrip-sr-only"><?php esc_attr_e( 'Date range', 'ytrip' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $display_id ); ?>" class="ytrip-input ytrip-date-range-display" placeholder="<?php echo $placeholder; ?>" readonly autocomplete="off" aria-haspopup="dialog" aria-expanded="false" aria-label="<?php esc_attr_e( 'Select date range', 'ytrip' ); ?>">
		<span class="ytrip-date-range-calendar-icon" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
		</span>
	</div>
	<?php endif; ?>
	<input type="hidden" name="<?php echo esc_attr( $from_name ); ?>" id="<?php echo esc_attr( $from_id ); ?>" value="<?php echo esc_attr( $value_from ); ?>">
	<input type="hidden" name="<?php echo esc_attr( $to_name ); ?>" id="<?php echo esc_attr( $to_id ); ?>" value="<?php echo esc_attr( $value_to ); ?>">
	<?php if ( $show_hint ) : ?>
	<p class="ytrip-date-range-hint"><?php esc_html_e( 'Click the field above, then pick start date and end date in the calendar.', 'ytrip' ); ?></p>
	<?php endif; ?>
	<div class="ytrip-date-range-calendar ytrip-calendar-dropdown" id="<?php echo esc_attr( $container_id ); ?>" role="dialog" aria-label="<?php esc_attr_e( 'Date range calendar', 'ytrip' ); ?>"></div>
</div>
