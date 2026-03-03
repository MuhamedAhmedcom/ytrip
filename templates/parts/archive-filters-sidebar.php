<?php
/**
 * Archive Filters - Sidebar
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$filter_data = YTrip_Archive_Filters::get_filter_data();
?>

<form class="ytrip-filters-form" id="ytrip-filters-form" method="get">
    
    <!-- Travel Date Filter -->
    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--calendar" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </span>
            <?php esc_html_e( 'Travel Date', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content">
            <div class="ytrip-date-filter">
                <?php
                $has_date_range = ! empty( $_GET['date_from'] ) && ! empty( $_GET['date_to'] );
                $show_single = ! $has_date_range;
                $show_range = $has_date_range;
                ?>
                <div class="ytrip-date-filter__tabs">
                    <button type="button" class="ytrip-date-tab <?php echo $show_single ? 'active' : ''; ?>" data-mode="single">
                        <?php esc_html_e( 'Specific Date', 'ytrip' ); ?>
                    </button>
                    <button type="button" class="ytrip-date-tab <?php echo $show_range ? 'active' : ''; ?>" data-mode="range">
                        <?php esc_html_e( 'Date Range', 'ytrip' ); ?>
                    </button>
                </div>
                
                <div class="ytrip-date-filter__single" id="ytrip-date-single"<?php echo $show_single ? '' : ' style="display:none;"'; ?>>
                    <div class="ytrip-date-single-calendar-wrapper">
                        <div class="ytrip-date-single-input-row">
                            <label for="ytrip-date-single-display" class="ytrip-sr-only"><?php esc_attr_e( 'Select date', 'ytrip' ); ?></label>
                            <input type="text"
                                   id="ytrip-date-single-display"
                                   class="ytrip-input ytrip-date-single-display"
                                   placeholder="<?php esc_attr_e( 'Select date', 'ytrip' ); ?>"
                                   readonly
                                   autocomplete="off"
                                   aria-haspopup="dialog"
                                   aria-expanded="false"
                                   aria-label="<?php esc_attr_e( 'Select date', 'ytrip' ); ?>">
                            <span class="ytrip-date-single-calendar-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </span>
                        </div>
                        <input type="hidden" name="tour_date" id="tour_date" value="<?php echo isset( $_GET['tour_date'] ) ? esc_attr( sanitize_text_field( $_GET['tour_date'] ) ) : ''; ?>">
                        <div class="ytrip-date-single-calendar ytrip-calendar-dropdown" id="ytrip-date-single-calendar" role="dialog" aria-label="<?php esc_attr_e( 'Date calendar', 'ytrip' ); ?>"></div>
                    </div>
                </div>
                
                <div class="ytrip-date-filter__range" id="ytrip-date-range"<?php echo $show_range ? '' : ' style="display:none;"'; ?>>
                    <div class="ytrip-date-range-calendar-wrapper">
                        <div class="ytrip-date-range-input-row">
                            <label for="ytrip-date-range-display" class="ytrip-sr-only"><?php esc_attr_e( 'Date range', 'ytrip' ); ?></label>
                            <input type="text"
                                   id="ytrip-date-range-display"
                                   class="ytrip-input ytrip-date-range-display"
                                   placeholder="<?php esc_attr_e( 'Select start date, then end date', 'ytrip' ); ?>"
                                   readonly
                                   autocomplete="off"
                                   aria-haspopup="dialog"
                                   aria-expanded="false"
                                   aria-label="<?php esc_attr_e( 'Select date range', 'ytrip' ); ?>">
                            <span class="ytrip-date-range-calendar-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </span>
                        </div>
                        <input type="hidden" name="date_from" id="date_from" value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( sanitize_text_field( $_GET['date_from'] ) ) : ''; ?>">
                        <input type="hidden" name="date_to" id="date_to" value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( sanitize_text_field( $_GET['date_to'] ) ) : ''; ?>">
                        <p class="ytrip-date-range-hint"><?php esc_html_e( 'Click the field above, then pick start date and end date in the calendar.', 'ytrip' ); ?></p>
                        <div class="ytrip-date-range-calendar ytrip-calendar-dropdown" id="ytrip-date-range-calendar" role="dialog" aria-label="<?php esc_attr_e( 'Date range calendar', 'ytrip' ); ?>"></div>
                    </div>
                </div>
                
                <div class="ytrip-date-filter__quick-wrap">
                    <span class="ytrip-date-filter__quick-label"><?php esc_html_e( 'Quick select', 'ytrip' ); ?></span>
                    <div class="ytrip-date-filter__quick">
                        <button type="button" class="ytrip-quick-date" data-days="7">
                            <?php esc_html_e( 'Next 7 days', 'ytrip' ); ?>
                        </button>
                        <button type="button" class="ytrip-quick-date" data-days="30">
                            <?php esc_html_e( 'Next 30 days', 'ytrip' ); ?>
                        </button>
                        <button type="button" class="ytrip-quick-date" data-days="90">
                            <?php esc_html_e( 'Next 3 months', 'ytrip' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--destination" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            </span>
            <?php esc_html_e( 'Destination', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content">
            <?php
            $current_dest = isset( $_GET['destination'] ) ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : ( $filter_data['current_destination_slug'] ?? '' );
            ?>
            <select name="destination" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'All Destinations', 'ytrip' ); ?></option>
                <?php foreach ( $filter_data['destinations'] as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_dest, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>


    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--category" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </span>
            <?php esc_html_e( 'Category', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content">
            <?php
            $current_cat = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : ( $filter_data['current_category_slug'] ?? '' );
            ?>
            <select name="category" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'All Categories', 'ytrip' ); ?></option>
                <?php foreach ( $filter_data['categories'] as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_cat, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--price" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path></svg>
            </span>
            <?php esc_html_e( 'Price Range', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content">
            <div class="ytrip-price-range">
                <div class="ytrip-price-input-group">
                    <span class="ytrip-currency-symbol"><?php echo esc_html( YTrip_Helper::get_currency_symbol() ); ?></span>
                    <input type="number" name="min_price" id="min_price" class="ytrip-input" placeholder="<?php esc_attr_e( 'Min', 'ytrip' ); ?>" min="0" step="0.01" value="<?php echo isset( $_GET['min_price'] ) ? esc_attr( (string) floatval( $_GET['min_price'] ) ) : ''; ?>">
                </div>
                <span class="ytrip-price-range__sep">-</span>
                <div class="ytrip-price-input-group">
                    <span class="ytrip-currency-symbol"><?php echo esc_html( YTrip_Helper::get_currency_symbol() ); ?></span>
                    <input type="number" name="max_price" id="max_price" class="ytrip-input" placeholder="<?php esc_attr_e( 'Max', 'ytrip' ); ?>" min="0" step="0.01" value="<?php echo isset( $_GET['max_price'] ) ? esc_attr( (string) floatval( $_GET['max_price'] ) ) : ''; ?>">
                </div>
            </div>
            <div class="ytrip-range-slider" id="ytrip-price-slider" data-min="0" data-max="5000"></div>
        </div>
    </div>

    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--duration" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </span>
            <?php esc_html_e( 'Duration', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content ytrip-filter-checkboxes">
            <?php
            $duration_get = isset( $_GET['duration'] ) ? sanitize_text_field( $_GET['duration'] ) : '';
            ?>
            <label class="ytrip-checkbox">
                <input type="radio" name="duration" value="" <?php checked( $duration_get, '' ); ?>>
                <span class="ytrip-checkbox__label"><?php esc_html_e( 'Any', 'ytrip' ); ?></span>
            </label>
            <?php foreach ( $filter_data['durations'] as $value => $label ) : ?>
            <label class="ytrip-checkbox">
                <input type="radio" name="duration" value="<?php echo esc_attr( $value ); ?>" <?php checked( $duration_get, $value ); ?>>
                <span class="ytrip-checkbox__label"><?php echo esc_html( $label ); ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--guests" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
            </span>
            <?php esc_html_e( 'Number of Guests', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content">
            <?php
            $current_guests = isset( $_GET['guests'] ) && $_GET['guests'] !== '' ? absint( $_GET['guests'] ) : '';
            ?>
            <select name="guests" class="ytrip-filter-select">
                <option value=""><?php esc_html_e( 'Any', 'ytrip' ); ?></option>
                <option value="1" <?php selected( $current_guests, 1 ); ?>><?php echo esc_html( _n( '1 Guest', '1 Guest', 1, 'ytrip' ) ); ?></option>
                <option value="2" <?php selected( $current_guests, 2 ); ?>><?php echo esc_html( sprintf( _n( '%d Guest', '%d Guests', 2, 'ytrip' ), 2 ) ); ?></option>
                <option value="3" <?php selected( $current_guests, 3 ); ?>><?php echo esc_html( sprintf( _n( '%d Guest', '%d Guests', 3, 'ytrip' ), 3 ) ); ?></option>
                <option value="4" <?php selected( $current_guests, 4 ); ?>><?php echo esc_html( sprintf( _n( '%d Guest', '%d Guests', 4, 'ytrip' ), 4 ) ); ?></option>
                <option value="5" <?php selected( $current_guests, 5 ); ?>>5+ <?php esc_html_e( 'Guests', 'ytrip' ); ?></option>
            </select>
        </div>
    </div>

    <div class="ytrip-filter-section">
        <h4 class="ytrip-filter-section__title">
            <span class="ytrip-filter-section__icon ytrip-filter-section__icon--rating" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            </span>
            <?php esc_html_e( 'Rating', 'ytrip' ); ?>
        </h4>
        <div class="ytrip-filter-section__content ytrip-filter-checkboxes">
            <?php
            $rating_get = isset( $_GET['rating'] ) && $_GET['rating'] !== '' ? absint( $_GET['rating'] ) : '';
            ?>
            <label class="ytrip-checkbox">
                <input type="radio" name="rating" value="" <?php checked( $rating_get, '' ); ?>>
                <span class="ytrip-checkbox__label"><?php esc_html_e( 'Any', 'ytrip' ); ?></span>
            </label>
            <?php for ( $i = 5; $i >= 3; $i-- ) : ?>
            <label class="ytrip-checkbox">
                <input type="radio" name="rating" value="<?php echo $i; ?>" <?php checked( $rating_get, $i ); ?>>
                <span class="ytrip-checkbox__label">
                    <span class="ytrip-stars"><?php echo str_repeat( '★', $i ); ?><?php echo str_repeat( '☆', 5 - $i ); ?></span>
                    <?php esc_html_e( '& Up', 'ytrip' ); ?>
                </span>
            </label>
            <?php endfor; ?>
        </div>
    </div>

    <div class="ytrip-filter-actions">
        <button type="submit" class="ytrip-btn ytrip-btn-primary ytrip-btn-block">
            <?php esc_html_e( 'Apply Filters', 'ytrip' ); ?>
        </button>
        <button type="button" class="ytrip-btn ytrip-btn-outline ytrip-btn-block ytrip-clear-filters">
            <?php esc_html_e( 'Clear All', 'ytrip' ); ?>
        </button>
    </div>

</form>
