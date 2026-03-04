<?php
/**
 * Archive Tours Template with Filters
 * 
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$options = get_option( 'ytrip_settings' );
$filter_data = YTrip_Archive_Filters::get_filter_data();

// View settings
$default_view = $options['archive_default_view'] ?? $options['archive_layout'] ?? 'grid';
$default_cols = $options['archive_default_columns'] ?? $options['archive_columns'] ?? 3;
$show_filters = $options['archive_show_filters'] ?? true;
$filter_position = $options['archive_filter_position'] ?? 'sidebar';
$archive_template = isset( $options['archive_template'] ) ? sanitize_html_class( $options['archive_template'] ) : 'default';
// Fullwidth template: no sidebar, use topbar for filters
if ( $archive_template === 'fullwidth' ) {
	$filter_position = 'topbar';
}
$pagination_style = $options['archive_pagination_style'] ?? $options['archive_pagination'] ?? 'numbered';
if ( $pagination_style === 'numbers' ) {
	$pagination_style = 'numbered';
}
$dark_mode_enabled = ! empty( $options['dark_mode'] );
$archive_filter_style = isset( $options['archive_filter_style'] ) ? sanitize_html_class( $options['archive_filter_style'] ) : 'modern';

// Current values from URL
$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : $default_view;
$current_cols = isset( $_GET['cols'] ) ? absint( $_GET['cols'] ) : $default_cols;
$current_sort = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';

get_header();
?>

<div class="ytrip-wrapper ytrip-archive ytrip-archive--template-<?php echo esc_attr( $archive_template ); ?> ytrip-archive--filter-<?php echo esc_attr( $archive_filter_style ); ?> <?php echo $dark_mode_enabled ? 'ytrip-dark-mode-available' : ''; ?>">
    
    <?php if ( $archive_template === 'minimal' ) : ?>
        <?php include YTRIP_PATH . 'templates/parts/archive-header-minimal.php'; ?>
    <?php else : ?>
    <!-- Archive Header (custom background & color from term meta for destination/category) -->
    <?php
    $header_bg    = '';
    $header_class = 'ytrip-archive-header';
    $term_color   = '';
    $term_tax     = '';

    if ( is_tax() ) {
        $term_id   = get_queried_object_id();
        $term_obj  = get_queried_object();
        $term_tax  = ( $term_obj && isset( $term_obj->taxonomy ) ) ? $term_obj->taxonomy : 'ytrip_destination';
        $bg_url    = YTrip_Helper::get_term_background( $term_id, $term_tax );
        $term_color = YTrip_Helper::get_term_color( $term_id, $term_tax );

        if ( $bg_url ) {
            $header_bg    = 'style="background-image: url(' . esc_url( $bg_url ) . '); background-size: cover; background-position: center;"';
            $header_class .= ' ytrip-has-bg';
        }
    } else {
        if ( ! empty( $options['default_term_background']['url'] ) ) {
            $header_bg    = 'style="background-image: url(' . esc_url( $options['default_term_background']['url'] ) . '); background-size: cover; background-position: center;"';
            $header_class .= ' ytrip-has-bg';
        }
    }
    $header_attr = $header_bg;
    if ( $term_color !== '' ) {
        $header_attr .= ' data-term-color="' . esc_attr( $term_color ) . '"';
        $header_class .= ' ytrip-archive-header--has-color';
    }
    // When no background image, use term color as solid archive header background.
    if ( $header_bg === '' && $term_color !== '' ) {
        $header_attr .= ' style="background-color: ' . esc_attr( $term_color ) . ';"';
    }
    ?>
    <header class="<?php echo esc_attr( $header_class ); ?>" <?php echo $header_attr; ?>>
        <div class="ytrip-overlay"<?php echo $term_color ? ' style="background-color: ' . esc_attr( $term_color ) . '33;"' : ''; ?>></div>
        <div class="ytrip-container">
            <?php if ( is_post_type_archive( 'ytrip_tour' ) ) : ?>
                <h1 class="ytrip-archive-header__title"><?php esc_html_e( 'All Tours', 'ytrip' ); ?></h1>
                <p class="ytrip-archive-header__desc"><?php esc_html_e( 'Explore our collection of unforgettable travel experiences.', 'ytrip' ); ?></p>
            <?php elseif ( is_tax( 'ytrip_destination' ) ) : ?>
                <h1 class="ytrip-archive-header__title"><?php single_term_title(); ?></h1>
                <?php if ( term_description() ) : ?>
                    <p class="ytrip-archive-header__desc"><?php echo wp_kses_post( term_description() ); ?></p>
                <?php endif; ?>
            <?php elseif ( is_tax( 'ytrip_category' ) ) : ?>
                <h1 class="ytrip-archive-header__title"><?php single_term_title(); ?></h1>
                <?php if ( term_description() ) : ?>
                    <p class="ytrip-archive-header__desc"><?php echo wp_kses_post( term_description() ); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>

    <div class="ytrip-container">
        <div class="ytrip-archive-layout ytrip-archive-layout--<?php echo esc_attr( $filter_position ); ?>">
            
            <?php if ( $show_filters && $filter_position === 'sidebar' ) : ?>
            <!-- Sidebar Filters + Custom Widgets -->
            <aside class="ytrip-archive-sidebar ytrip-archive-sidebar--<?php echo esc_attr( $archive_filter_style ); ?>">
                <?php include YTRIP_PATH . 'templates/parts/archive-filters-sidebar.php'; ?>
                <?php if ( is_active_sidebar( 'ytrip-archive' ) ) : ?>
                <div class="ytrip-archive-widget-area ytrip-widget-area">
                    <?php dynamic_sidebar( 'ytrip-archive' ); ?>
                </div>
                <?php endif; ?>
            </aside>
            <?php endif; ?>

            <main class="ytrip-archive-main">
                
                <!-- Toolbar -->
                <div class="ytrip-archive-toolbar">
                    <div class="ytrip-archive-toolbar__left">
                        <span class="ytrip-archive-toolbar__count" id="ytrip-archive-count">
                            <?php
                            global $wp_query;
                            printf(
                                '<span class="count-number">%s</span> <span class="count-label">%s</span>',
                                esc_html( (string) $wp_query->found_posts ),
                                esc_html( _n( 'Tour', 'Tours', $wp_query->found_posts, 'ytrip' ) )
                            );
                            ?>
                        </span>
                        
                        <?php if ( $show_filters && $filter_position === 'topbar' ) : ?>
                        <button class="ytrip-modern-filter-btn" type="button">
                            <span class="ytrip-icon-filter">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                            </span>
                            <?php esc_html_e( 'Filter', 'ytrip' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ytrip-archive-toolbar__right">
                        <!-- Sort Dropdown -->
                        <div class="ytrip-modern-sort">
                            <span class="ytrip-sort-label"><?php esc_html_e( 'Sort by:', 'ytrip' ); ?></span>
                            <div class="ytrip-select-wrapper">
                                <select id="ytrip-sort" class="ytrip-sort-select">
                                    <?php foreach ( $filter_data['sort_options'] as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <svg class="ytrip-select-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </div>
                        </div>
                        
                        <div class="ytrip-toolbar-divider"></div>

                        <!-- Display: View + Columns (with label so icons are clear) -->
                        <div class="ytrip-toolbar-display-group" role="group" aria-label="<?php esc_attr_e( 'Display', 'ytrip' ); ?>">
                            <span class="ytrip-toolbar-display-label"><?php esc_html_e( 'Display:', 'ytrip' ); ?></span>
                            <div class="ytrip-view-toggle" aria-label="<?php esc_attr_e( 'View Mode', 'ytrip' ); ?>">
                                <button type="button" class="ytrip-view-toggle__btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>" data-view="grid" aria-label="<?php esc_attr_e( 'Grid View', 'ytrip' ); ?>" title="<?php esc_attr_e( 'Grid View', 'ytrip' ); ?>">
                                    <svg class="ytrip-icon-view-grid" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </button>
                                <button type="button" class="ytrip-view-toggle__btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" data-view="list" aria-label="<?php esc_attr_e( 'List View', 'ytrip' ); ?>" title="<?php esc_attr_e( 'List View', 'ytrip' ); ?>">
                                    <svg class="ytrip-icon-view-list" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                </button>
                            </div>
                            <div class="ytrip-columns-selector" data-current="<?php echo esc_attr( $current_cols ); ?>" aria-label="<?php esc_attr_e( 'Columns', 'ytrip' ); ?>">
                            <?php
                            $col_icons = [
                                2 => '<svg class="ytrip-icon-cols" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="4" y="4" width="6" height="16" rx="1"/><rect x="14" y="4" width="6" height="16" rx="1"/></svg>',
                                3 => '<svg class="ytrip-icon-cols" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="2" y="4" width="5" height="16" rx="1"/><rect x="9.5" y="4" width="5" height="16" rx="1"/><rect x="17" y="4" width="5" height="16" rx="1"/></svg>',
                                4 => '<svg class="ytrip-icon-cols" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="2" y="4" width="3.5" height="16" rx="0.5"/><rect x="7.5" y="4" width="3.5" height="16" rx="0.5"/><rect x="13" y="4" width="3.5" height="16" rx="0.5"/><rect x="18.5" y="4" width="3.5" height="16" rx="0.5"/></svg>',
                            ];
                            
                            for ( $i = 2; $i <= 4; $i++ ) : 
                            ?>
                            <button type="button" class="ytrip-columns-selector__btn <?php echo $current_cols == $i ? 'active' : ''; ?>" data-cols="<?php echo $i; ?>" aria-label="<?php echo sprintf( __( '%d Columns', 'ytrip' ), $i ); ?>" title="<?php echo sprintf( __( '%d Columns', 'ytrip' ), $i ); ?>">
                                <?php echo $col_icons[$i]; ?>
                            </button>
                            <?php endfor; ?>
                            </div>
                        </div>

                        <?php if ( $dark_mode_enabled ) : ?>
                        <div class="ytrip-toolbar-divider"></div>
                        <?php echo ytrip_dark_mode_toggle( array( 'class' => 'ytrip-toolbar-dark-toggle' ) ); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ( $show_filters && $filter_position === 'topbar' ) : ?>
                <!-- Collapsible Filter Bar -->
                <div class="ytrip-archive-filter-bar ytrip-archive-filter-bar--<?php echo esc_attr( $archive_filter_style ); ?>" id="ytrip-filter-bar">
                    <?php include YTRIP_PATH . 'templates/parts/archive-filters-topbar.php'; ?>
                </div>
                <?php endif; ?>

                <!-- Active Filters -->
                <div class="ytrip-active-filters" id="ytrip-active-filters"></div>

                <!-- Tours Grid/List -->
                <div class="ytrip-tours-container ytrip-view-<?php echo esc_attr( $current_view ); ?> ytrip-cols-<?php echo esc_attr( $current_cols ); ?>" id="ytrip-tours-container">
                    <?php if ( have_posts() ) : ?>
                        <?php while ( have_posts() ) : the_post(); ?>
                            <?php 
                            if ( $current_view === 'list' ) {
                                include YTRIP_PATH . 'templates/cards/card-list-view.php';
                            } else {
                                include YTRIP_PATH . 'templates/parts/tour-card.php';
                            }
                            ?>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="ytrip-no-results"><?php esc_html_e( 'No tours found. Please check back later.', 'ytrip' ); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Loading Indicator -->
                <div class="ytrip-loading" id="ytrip-loading" style="display: none;">
                    <div class="ytrip-spinner"></div>
                    <span><?php esc_html_e( 'Loading...', 'ytrip' ); ?></span>
                </div>

                <!-- Pagination (only when there are results and more than one page) -->
                <?php 
                global $wp_query;
                $max_pages = $wp_query->max_num_pages;
                $current_page = max( 1, get_query_var( 'paged' ) );
                $has_results = (int) $wp_query->found_posts > 0;
                $show_pagination = $has_results && $max_pages > 1;
                ?>
                <?php if ( $show_pagination ) : ?>
                <div class="ytrip-pagination-wrapper" data-style="<?php echo esc_attr( $pagination_style ); ?>" data-max-pages="<?php echo esc_attr( $max_pages ); ?>" data-current-page="<?php echo esc_attr( $current_page ); ?>">
                    
                    <?php if ( $pagination_style === 'numbered' ) : ?>
                    <!-- Numbered Pagination -->
                    <nav class="ytrip-pagination ytrip-pagination--numbered" id="ytrip-pagination">
                        <?php
                        the_posts_pagination( array(
                            'prev_text' => '&larr; ' . esc_html__( 'Previous', 'ytrip' ),
                            'next_text' => esc_html__( 'Next', 'ytrip' ) . ' &rarr;',
                        ) );
                        ?>
                    </nav>
                    
                    <?php elseif ( $pagination_style === 'loadmore' && $current_page < $max_pages ) : ?>
                    <!-- Load More Button -->
                    <div class="ytrip-pagination ytrip-pagination--loadmore" id="ytrip-loadmore-wrap">
                        <button type="button" class="ytrip-btn ytrip-btn-primary ytrip-btn-lg" id="ytrip-loadmore-btn">
                            <span class="ytrip-loadmore-text"><?php esc_html_e( 'Load More Tours', 'ytrip' ); ?></span>
                            <span class="ytrip-loadmore-spinner" style="display:none;">
                                <span class="ytrip-spinner-sm"></span>
                            </span>
                        </button>
                    </div>
                    
                    <?php elseif ( $pagination_style === 'infinite' ) : ?>
                    <!-- Infinite Scroll Trigger -->
                    <div class="ytrip-pagination ytrip-pagination--infinite" id="ytrip-infinite-trigger">
                        <div class="ytrip-infinite-loading" style="display:none;">
                            <div class="ytrip-spinner"></div>
                            <span><?php esc_html_e( 'Loading more...', 'ytrip' ); ?></span>
                        </div>
                        <?php if ( $current_page >= $max_pages ) : ?>
                        <p class="ytrip-all-loaded"><?php esc_html_e( 'All tours loaded', 'ytrip' ); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
            </main>

            <?php if ( ( ! $show_filters || $filter_position !== 'sidebar' ) && is_active_sidebar( 'ytrip-archive' ) ) : ?>
            <!-- Archive widgets when filters are in topbar or hidden -->
            <div class="ytrip-archive-widget-area ytrip-archive-widget-area--bottom ytrip-widget-area">
                <?php dynamic_sidebar( 'ytrip-archive' ); ?>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>
