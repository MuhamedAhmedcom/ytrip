<?php
/**
 * Archive Filters - Top Bar
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$filter_data = YTrip_Archive_Filters::get_filter_data();
?>

<?php
$has_date_range = ! empty( $_GET['date_from'] ) && ! empty( $_GET['date_to'] );
$show_single = ! $has_date_range;
$show_range = $has_date_range;
?>
<form class="ytrip-filters-topbar" id="ytrip-filters-topbar" method="get">
    <div class="ytrip-filters-topbar__row">
        <!-- Travel Date (same as sidebar so date filtering works when filter position is topbar) -->
        <div class="ytrip-filter-item ytrip-filter-item--travel-date">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </span>
                <?php esc_html_e( 'Travel Date', 'ytrip' ); ?>
            </label>
            <div class="ytrip-date-filter ytrip-date-filter--topbar">
                <div class="ytrip-date-filter__tabs">
                    <button type="button" class="ytrip-date-tab <?php echo $show_single ? 'active' : ''; ?>" data-mode="single"><?php esc_html_e( 'Specific Date', 'ytrip' ); ?></button>
                    <button type="button" class="ytrip-date-tab <?php echo $show_range ? 'active' : ''; ?>" data-mode="range"><?php esc_html_e( 'Date Range', 'ytrip' ); ?></button>
                </div>
                <div class="ytrip-date-filter__single" id="ytrip-date-single"<?php echo $show_single ? '' : ' style="display:none;"'; ?>>
                    <div class="ytrip-date-input-wrap">
                        <input type="date" name="tour_date" id="tour_date" class="ytrip-input ytrip-date-input" value="<?php echo isset( $_GET['tour_date'] ) ? esc_attr( sanitize_text_field( $_GET['tour_date'] ) ) : ''; ?>" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
                        <span class="ytrip-date-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </span>
                    </div>
                </div>
                <div class="ytrip-date-filter__range" id="ytrip-date-range"<?php echo $show_range ? '' : ' style="display:none;"'; ?>>
                    <div class="ytrip-date-range-inputs">
                        <div class="ytrip-date-range-field">
                            <label for="date_from" class="ytrip-date-range-label"><?php esc_html_e( 'From', 'ytrip' ); ?></label>
                            <div class="ytrip-date-input-wrap">
                                <input type="date" name="date_from" id="date_from" class="ytrip-input ytrip-date-input" value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( sanitize_text_field( $_GET['date_from'] ) ) : ''; ?>" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" placeholder="<?php esc_attr_e( 'mm/dd/yyyy', 'ytrip' ); ?>" aria-label="<?php esc_attr_e( 'Start date', 'ytrip' ); ?>">
                                <span class="ytrip-date-input-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </span>
                            </div>
                        </div>
                        <span class="ytrip-date-range-sep" aria-hidden="true"><?php esc_html_e( 'to', 'ytrip' ); ?></span>
                        <div class="ytrip-date-range-field">
                            <label for="date_to" class="ytrip-date-range-label"><?php esc_html_e( 'To', 'ytrip' ); ?></label>
                            <div class="ytrip-date-input-wrap">
                                <input type="date" name="date_to" id="date_to" class="ytrip-input ytrip-date-input" value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( sanitize_text_field( $_GET['date_to'] ) ) : ''; ?>" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" placeholder="<?php esc_attr_e( 'mm/dd/yyyy', 'ytrip' ); ?>" aria-label="<?php esc_attr_e( 'End date', 'ytrip' ); ?>">
                                <span class="ytrip-date-input-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ytrip-date-filter__quick">
                    <button type="button" class="ytrip-quick-date" data-days="7"><?php esc_html_e( 'Next 7 days', 'ytrip' ); ?></button>
                    <button type="button" class="ytrip-quick-date" data-days="30"><?php esc_html_e( 'Next 30 days', 'ytrip' ); ?></button>
                    <button type="button" class="ytrip-quick-date" data-days="90"><?php esc_html_e( 'Next 3 months', 'ytrip' ); ?></button>
                </div>
            </div>
        </div>

        <div class="ytrip-filter-item">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon ytrip-filter-item__icon--destination" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </span>
                <?php esc_html_e( 'Destination', 'ytrip' ); ?>
            </label>
            <?php
            $current_dest = isset( $_GET['destination'] ) ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : ( $filter_data['current_destination_slug'] ?? '' );
            ?>
            <select name="destination" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'All', 'ytrip' ); ?></option>
                <?php foreach ( $filter_data['destinations'] as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_dest, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ytrip-filter-item">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon ytrip-filter-item__icon--category" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                </span>
                <?php esc_html_e( 'Category', 'ytrip' ); ?>
            </label>
            <?php
            $current_cat = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : ( $filter_data['current_category_slug'] ?? '' );
            ?>
            <select name="category" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'All', 'ytrip' ); ?></option>
                <?php foreach ( $filter_data['categories'] as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_cat, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ytrip-filter-item">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon ytrip-filter-item__icon--duration" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </span>
                <?php esc_html_e( 'Duration', 'ytrip' ); ?>
            </label>
            <select name="duration" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'Any', 'ytrip' ); ?></option>
                <?php foreach ( $filter_data['durations'] as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $_GET['duration'] ) ? $_GET['duration'] : '', $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ytrip-filter-item ytrip-filter-item--price">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon ytrip-filter-item__icon--price" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path></svg>
                </span>
                <?php esc_html_e( 'Price', 'ytrip' ); ?>
            </label>
            <div class="ytrip-filter-price-inputs">
                <input type="number" name="min_price" placeholder="<?php esc_attr_e( 'Min', 'ytrip' ); ?>" value="<?php echo isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : ''; ?>">
                <span>-</span>
                <input type="number" name="max_price" placeholder="<?php esc_attr_e( 'Max', 'ytrip' ); ?>" value="<?php echo isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : ''; ?>">
            </div>
        </div>

        <div class="ytrip-filter-item">
            <label class="ytrip-filter-item__label--with-icon">
                <span class="ytrip-filter-item__icon ytrip-filter-item__icon--rating" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </span>
                <?php esc_html_e( 'Rating', 'ytrip' ); ?>
            </label>
            <select name="rating" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'Any', 'ytrip' ); ?></option>
                <?php for ( $i = 5; $i >= 3; $i-- ) : ?>
                <option value="<?php echo $i; ?>" <?php selected( isset( $_GET['rating'] ) ? absint( $_GET['rating'] ) : 0, $i ); ?>>
                    <?php echo $i; ?>+ <?php esc_html_e( 'Stars', 'ytrip' ); ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="ytrip-filter-item ytrip-filter-item--actions">
            <button type="submit" class="ytrip-btn ytrip-btn-primary">
                <?php esc_html_e( 'Apply', 'ytrip' ); ?>
            </button>
            <button type="button" class="ytrip-btn ytrip-btn-outline ytrip-clear-filters">
                <?php esc_html_e( 'Clear', 'ytrip' ); ?>
            </button>
        </div>

    </div>
</form>
