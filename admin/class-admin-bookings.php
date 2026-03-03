<?php
/**
 * YTrip Admin Bookings List
 *
 * Displays standalone bookings from ytrip_bookings table.
 *
 * @package YTrip
 * @since 2.1.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for ytrip_bookings.
 */
class YTrip_Bookings_List_Table extends WP_List_Table {

	/**
	 * @var string
	 */
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ytrip_bookings';
		parent::__construct( array(
			'singular' => 'booking',
			'plural'   => 'bookings',
			'ajax'     => false,
		) );
	}

	/**
	 * Column defaults.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'id'             => __( 'ID', 'ytrip' ),
			'tour'           => __( 'Tour', 'ytrip' ),
			'customer_name'  => __( 'Customer', 'ytrip' ),
			'customer_email' => __( 'Email', 'ytrip' ),
			'customer_phone' => __( 'Phone', 'ytrip' ),
			'booking_date'   => __( 'Date', 'ytrip' ),
			'adults'         => __( 'Adults', 'ytrip' ),
			'children'       => __( 'Children', 'ytrip' ),
			'infants'        => __( 'Infants', 'ytrip' ),
			'status'         => __( 'Status', 'ytrip' ),
			'order_id'       => __( 'Order', 'ytrip' ),
			'created_at'     => __( 'Created', 'ytrip' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns(): array {
		return array(
			'id'             => array( 'id', false ),
			'booking_date'   => array( 'booking_date', true ),
			'customer_name'  => array( 'customer_name', false ),
			'status'         => array( 'status', false ),
			'created_at'     => array( 'created_at', true ),
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items(): void {
		global $wpdb;

		$per_page = 20;
		$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';
		$order    = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$allowed  = array( 'id', 'tour_id', 'booking_date', 'customer_name', 'customer_email', 'status', 'created_at' );
		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'created_at';
		}

		$where = '1=1';
		$tour_filter = isset( $_GET['ytrip_tour_id'] ) ? absint( $_GET['ytrip_tour_id'] ) : 0;
		if ( $tour_filter > 0 ) {
			$where .= $wpdb->prepare( ' AND tour_id = %d', $tour_filter );
		}
		$status_filter = isset( $_GET['ytrip_status'] ) ? sanitize_key( $_GET['ytrip_status'] ) : '';
		if ( $status_filter !== '' ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status_filter );
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table_name}` WHERE {$where}" );
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * $per_page;

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );

		$sql = "SELECT * FROM `{$this->table_name}` WHERE {$where} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";
		$items = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ) );
		$this->items = $items ?: array();
	}

	/**
	 * Default column output.
	 *
	 * @param object $item
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'id':
				return (string) $item->id;
			case 'tour':
				$title = get_the_title( (int) $item->tour_id );
				$url   = get_edit_post_link( $item->tour_id, 'raw' );
				if ( $url ) {
					return '<a href="' . esc_url( $url ) . '">' . esc_html( $title ?: '#' . $item->tour_id ) . '</a>';
				}
				return esc_html( $title ?: '#' . $item->tour_id );
			case 'customer_name':
				return esc_html( $item->customer_name ?: '—' );
			case 'customer_email':
				return $item->customer_email ? '<a href="mailto:' . esc_attr( $item->customer_email ) . '">' . esc_html( $item->customer_email ) . '</a>' : '—';
			case 'customer_phone':
				return esc_html( $item->customer_phone ?: '—' );
			case 'booking_date':
				return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->booking_date ) ) );
			case 'adults':
				return (string) ( $item->adults ?? 0 );
			case 'children':
				return (string) ( $item->children ?? 0 );
			case 'infants':
				return (string) ( isset( $item->infants ) ? $item->infants : 0 );
			case 'status':
				return '<span class="ytrip-status ytrip-status--' . esc_attr( $item->status ) . '">' . esc_html( ucfirst( $item->status ) ) . '</span>';
			case 'order_id':
				if ( ! empty( $item->order_id ) && function_exists( 'wc_get_order' ) ) {
					$order = wc_get_order( (int) $item->order_id );
					if ( $order ) {
						$edit = $order->get_edit_order_url();
						return '<a href="' . esc_url( $edit ) . '">#' . esc_html( $item->order_id ) . '</a>';
					}
				}
				return empty( $item->order_id ) ? '—' : '#' . (int) $item->order_id;
			case 'created_at':
				return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) ) );
			default:
				return '';
		}
	}

	/**
	 * Column id with row actions (view).
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_id( $item ): string {
		$url = add_query_arg( array(
			'page'   => 'ytrip-bookings',
			'view'   => (int) $item->id,
		), admin_url( 'admin.php' ) );
		$out = '<strong><a href="' . esc_url( $url ) . '" class="row-title">#' . (int) $item->id . '</a></strong>';
		$out .= '<div class="row-actions"><span class="view"><a href="' . esc_url( $url ) . '">' . esc_html__( 'View', 'ytrip' ) . '</a></span></div>';
		return $out;
	}
}

/**
 * Admin Bookings page controller.
 */
class YTrip_Admin_Bookings {

	/**
	 * @var string
	 */
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ytrip_bookings';
		add_action( 'admin_menu', array( $this, 'add_menu' ), 15 );
	}

	public function add_menu(): void {
		add_submenu_page(
			'ytrip-settings',
			__( 'Bookings', 'ytrip' ),
			__( 'Bookings', 'ytrip' ),
			'manage_options',
			'ytrip-bookings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render list or single view.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'ytrip' ) );
		}

		$view_id = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		if ( $view_id > 0 ) {
			$this->render_single( $view_id );
			return;
		}

		$this->render_list();
	}

	/**
	 * Render list table.
	 */
	private function render_list(): void {
		$list = new YTrip_Bookings_List_Table();
		$list->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Bookings', 'ytrip' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Standalone tour bookings (without WooCommerce checkout). Orders from WooCommerce appear in WooCommerce → Orders.', 'ytrip' ) . '</p>';

		// Simple filters
		global $wpdb;
		$tour_count = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT tour_id) FROM `{$this->table_name}`" );
		if ( $tour_count > 0 && $tour_count <= 100 ) {
			echo '<form method="get" class="ytrip-bookings-filters" style="margin:1em 0;">';
			echo '<input type="hidden" name="page" value="ytrip-bookings">';
			$tour_id = isset( $_GET['ytrip_tour_id'] ) ? absint( $_GET['ytrip_tour_id'] ) : 0;
			$status  = isset( $_GET['ytrip_status'] ) ? sanitize_text_field( wp_unslash( $_GET['ytrip_status'] ) ) : '';
			echo '<select name="ytrip_tour_id"><option value="0">' . esc_html__( 'All tours', 'ytrip' ) . '</option>';
			$tours = $wpdb->get_results( "SELECT DISTINCT tour_id FROM `{$this->table_name}` ORDER BY tour_id DESC" );
			foreach ( $tours as $row ) {
				$title = get_the_title( (int) $row->tour_id );
				echo '<option value="' . (int) $row->tour_id . '" ' . selected( $tour_id, (int) $row->tour_id, false ) . '>' . esc_html( $title ?: '#' . $row->tour_id ) . '</option>';
			}
			echo '</select>';
			echo '<select name="ytrip_status">';
			echo '<option value="">' . esc_html__( 'All statuses', 'ytrip' ) . '</option>';
			foreach ( array( 'pending', 'confirmed', 'cancelled', 'completed' ) as $s ) {
				echo '<option value="' . esc_attr( $s ) . '" ' . selected( $status, $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
			}
			echo '</select>';
			echo ' <button type="submit" class="button">' . esc_html__( 'Filter', 'ytrip' ) . '</button>';
			echo '</form>';
		}

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="ytrip-bookings">';
		if ( ! empty( $_GET['ytrip_tour_id'] ) ) {
			echo '<input type="hidden" name="ytrip_tour_id" value="' . esc_attr( (int) $_GET['ytrip_tour_id'] ) . '">';
		}
		if ( ! empty( $_GET['ytrip_status'] ) ) {
			echo '<input type="hidden" name="ytrip_status" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['ytrip_status'] ) ) ) . '">';
		}
		$list->display();
		echo '</form>';
		echo '</div>';

		echo '<style>.ytrip-status { padding: 2px 8px; border-radius: 4px; }.ytrip-status--pending { background: #fef3c7; }.ytrip-status--confirmed { background: #d1fae5; }.ytrip-status--cancelled { background: #fee2e2; }.ytrip-status--completed { background: #e0e7ff; }</style>';
	}

	/**
	 * Render single booking view.
	 *
	 * @param int $id Booking ID.
	 */
	private function render_single( int $id ): void {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->table_name}` WHERE id = %d", $id ) );
		if ( ! $row ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Booking not found.', 'ytrip' ) . '</p><p><a href="' . esc_url( admin_url( 'admin.php?page=ytrip-bookings' ) ) . '">' . esc_html__( 'Back to list', 'ytrip' ) . '</a></p></div>';
			return;
		}

		$tour_title = get_the_title( (int) $row->tour_id );
		$tour_link  = get_permalink( (int) $row->tour_id );
		$edit_tour  = get_edit_post_link( (int) $row->tour_id, 'raw' );

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Booking', 'ytrip' ) . ' #' . (int) $row->id . '</h1>';
		echo ' <a href="' . esc_url( admin_url( 'admin.php?page=ytrip-bookings' ) ) . '" class="page-title-action">' . esc_html__( 'Back to list', 'ytrip' ) . '</a>';
		echo '<hr class="wp-header-end">';

		echo '<table class="form-table"><tbody>';
		echo '<tr><th scope="row">' . esc_html__( 'Tour', 'ytrip' ) . '</th><td>';
		if ( $edit_tour ) {
			echo '<a href="' . esc_url( $edit_tour ) . '">' . esc_html( $tour_title ?: '#' . $row->tour_id ) . '</a>';
		} else {
			echo esc_html( $tour_title ?: '#' . $row->tour_id );
		}
		if ( $tour_link ) {
			echo ' <a href="' . esc_url( $tour_link ) . '" target="_blank" rel="noopener">' . esc_html__( 'View on site', 'ytrip' ) . '</a>';
		}
		echo '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Customer', 'ytrip' ) . '</th><td>' . esc_html( $row->customer_name ?: '—' ) . '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Email', 'ytrip' ) . '</th><td>' . ( $row->customer_email ? '<a href="mailto:' . esc_attr( $row->customer_email ) . '">' . esc_html( $row->customer_email ) . '</a>' : '—' ) . '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Phone', 'ytrip' ) . '</th><td>' . esc_html( $row->customer_phone ?: '—' ) . '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Booking date', 'ytrip' ) . '</th><td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->booking_date ) ) ) . '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Guests', 'ytrip' ) . '</th><td>' . esc_html__( 'Adults:', 'ytrip' ) . ' ' . (int) ( $row->adults ?? 0 ) . ', ' . esc_html__( 'Children:', 'ytrip' ) . ' ' . (int) ( $row->children ?? 0 ) . ', ' . esc_html__( 'Infants:', 'ytrip' ) . ' ' . (int) ( isset( $row->infants ) ? $row->infants : 0 ) . '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Status', 'ytrip' ) . '</th><td>' . esc_html( ucfirst( $row->status ) ) . '</td></tr>';
		if ( ! empty( $row->order_id ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $row->order_id );
			echo '<tr><th scope="row">' . esc_html__( 'WooCommerce order', 'ytrip' ) . '</th><td>' . ( $order ? '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . (int) $row->order_id . '</a>' : '#' . (int) $row->order_id ) . '</td></tr>';
		}
		echo '<tr><th scope="row">' . esc_html__( 'Created', 'ytrip' ) . '</th><td>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->created_at ) ) ) . '</td></tr>';
		if ( ! empty( $row->notes ) ) {
			echo '<tr><th scope="row">' . esc_html__( 'Notes', 'ytrip' ) . '</th><td>' . wp_kses_post( nl2br( $row->notes ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}
}

new YTrip_Admin_Bookings();
