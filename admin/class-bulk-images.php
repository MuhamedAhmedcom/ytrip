<?php
/**
 * YTrip Bulk Image Assignment Tool
 *
 * Admin-only page to quickly set featured images and gallery for tours
 * that have none. Accessible at: Admin → YTrip → Assign Tour Images
 *
 * @package YTrip
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the admin submenu.
 */
function ytrip_bulk_images_menu() {
    add_submenu_page(
        'ytrip-settings',
        __( 'Assign Tour Images', 'ytrip' ),
        __( '📷 Assign Images', 'ytrip' ),
        'manage_options',
        'ytrip-bulk-images',
        'ytrip_bulk_images_page'
    );
}
add_action( 'admin_menu', 'ytrip_bulk_images_menu' );

/**
 * Enqueue media uploader on our page.
 */
function ytrip_bulk_images_enqueue( $hook ) {
    if ( strpos( $hook, 'ytrip-bulk-images' ) === false ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style( 'ytrip-bulk-images-css', false );
    wp_add_inline_style( 'dashicons', '
        .ytrip-bi-wrap { max-width: 1100px; margin: 20px auto; font-family: -apple-system, sans-serif; }
        .ytrip-bi-tour { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 16px; padding: 16px 20px; display:flex; align-items:center; gap: 20px; }
        .ytrip-bi-tour--has-images { border-left: 4px solid #22c55e; }
        .ytrip-bi-tour--no-images { border-left: 4px solid #f59e0b; }
        .ytrip-bi-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; background: #e5e7eb; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
        .ytrip-bi-thumb img { width:80px; height:60px; object-fit:cover; border-radius:4px; }
        .ytrip-bi-info { flex: 1; }
        .ytrip-bi-title { font-weight: 600; font-size: 15px; margin: 0 0 4px; }
        .ytrip-bi-meta { color: #6b7280; font-size: 13px; }
        .ytrip-bi-meta .no-img { color: #f59e0b; font-weight:600; }
        .ytrip-bi-meta .has-img { color: #22c55e; font-weight:600; }
        .ytrip-bi-actions { display:flex; gap:8px; flex-shrink:0; }
        .ytrip-bi-btn { padding: 6px 14px; border-radius: 5px; border: 1px solid #d1d5db; background: #f9fafb; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:6px; }
        .ytrip-bi-btn:hover { background: #e5e7eb; }
        .ytrip-bi-btn--primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .ytrip-bi-btn--primary:hover { background: #1d4ed8; }
        .ytrip-bi-btn--danger { background: #ef4444; color:#fff; border-color:#ef4444; }
        .ytrip-bi-btn--danger:hover { background:#dc2626; }
        .ytrip-bi-gallery-strip { display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; }
        .ytrip-bi-gallery-strip img { width:36px; height:36px; object-fit:cover; border-radius:3px; }
        .ytrip-bi-progress { display:none; position:fixed; top:0; left:0; right:0; background:#2563eb; height:3px; z-index:9999; transition:width 0.3s; }
    ' );
}
add_action( 'admin_enqueue_scripts', 'ytrip_bulk_images_enqueue' );

/**
 * Handle AJAX: save featured image + gallery for a tour.
 */
function ytrip_bulk_images_save() {
    check_ajax_referer( 'ytrip_bulk_images', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $tour_id    = absint( $_POST['tour_id'] ?? 0 );
    $thumb_id   = absint( $_POST['thumb_id'] ?? 0 );
    $gallery_ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( $_POST['gallery_ids'] ?? '' ) ) ) );

    if ( ! $tour_id || get_post_type( $tour_id ) !== 'ytrip_tour' ) {
        wp_send_json_error( 'Invalid tour ID' );
    }

    // Set featured image.
    if ( $thumb_id ) {
        set_post_thumbnail( $tour_id, $thumb_id );
    } else {
        delete_post_thumbnail( $tour_id );
    }

    // Save gallery into the ytrip_tour_details meta.
    $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
    if ( ! is_array( $meta ) ) {
        $meta = array();
    }
    $meta['tour_gallery'] = implode( ',', $gallery_ids );

    // If no featured image but gallery has items, auto-set first as thumbnail.
    if ( ! $thumb_id && ! empty( $gallery_ids ) ) {
        set_post_thumbnail( $tour_id, reset( $gallery_ids ) );
    }

    update_post_meta( $tour_id, 'ytrip_tour_details', $meta );

    wp_send_json_success( array(
        'thumb_id'    => get_post_thumbnail_id( $tour_id ),
        'thumb_url'   => get_the_post_thumbnail_url( $tour_id, 'thumbnail' ),
        'gallery_ids' => $gallery_ids,
    ) );
}
add_action( 'wp_ajax_ytrip_bulk_images_save', 'ytrip_bulk_images_save' );

/**
 * Render the admin page.
 */
function ytrip_bulk_images_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $tours = get_posts( array(
        'post_type'      => 'ytrip_tour',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    $nonce = wp_create_nonce( 'ytrip_bulk_images' );
    $ajax_url = admin_url( 'admin-ajax.php' );

    $no_image_count = 0;
    foreach ( $tours as $tour ) {
        $meta = get_post_meta( $tour->ID, 'ytrip_tour_details', true );
        if ( ! has_post_thumbnail( $tour->ID ) && ( ! isset( $meta['tour_gallery'] ) || empty( $meta['tour_gallery'] ) ) ) {
            $no_image_count++;
        }
    }

    ?>
    <div class="wrap ytrip-bi-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            📷 <?php esc_html_e( 'YTrip — Assign Tour Images', 'ytrip' ); ?>
            <?php if ( $no_image_count ) : ?>
                <span style="background:#f59e0b;color:#fff;padding:3px 10px;border-radius:12px;font-size:13px;"><?php echo $no_image_count; ?> tours without images</span>
            <?php endif; ?>
        </h1>
        <p style="color:#6b7280;margin-top:0;"><?php esc_html_e( 'Click on a tour to set its featured image and gallery. Changes are saved instantly via AJAX.', 'ytrip' ); ?></p>
        <div class="ytrip-bi-progress" id="ytrip-bi-progress"></div>

        <?php foreach ( $tours as $tour ) :
            $tour_id     = $tour->ID;
            $meta        = get_post_meta( $tour_id, 'ytrip_tour_details', true );
            if ( ! is_array( $meta ) ) $meta = array();
            $thumb_id    = get_post_thumbnail_id( $tour_id );
            $thumb_url   = $thumb_id ? get_the_post_thumbnail_url( $tour_id, 'thumbnail' ) : '';
            $gallery_raw = isset( $meta['tour_gallery'] ) ? $meta['tour_gallery'] : '';
            $gallery_ids = array_filter( array_map( 'absint', $gallery_raw ? explode( ',', $gallery_raw ) : array() ) );
            $has_images  = $thumb_id || ! empty( $gallery_ids );
        ?>
        <div class="ytrip-bi-tour <?php echo $has_images ? 'ytrip-bi-tour--has-images' : 'ytrip-bi-tour--no-images'; ?>"
             id="ytrip-bi-tour-<?php echo $tour_id; ?>"
             data-tour-id="<?php echo $tour_id; ?>"
             data-thumb-id="<?php echo (int) $thumb_id; ?>"
             data-gallery-ids="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">

            <div class="ytrip-bi-thumb" id="ytrip-bi-thumb-<?php echo $tour_id; ?>">
                <?php if ( $thumb_url ) : ?>
                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="" />
                <?php else : ?>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <?php endif; ?>
            </div>

            <div class="ytrip-bi-info">
                <div class="ytrip-bi-title">
                    <a href="<?php echo esc_url( get_edit_post_link( $tour_id ) ); ?>" target="_blank"><?php echo esc_html( $tour->post_title ); ?></a>
                    <span style="color:#9ca3af;font-size:12px;font-weight:400;"> #<?php echo $tour_id; ?></span>
                </div>
                <div class="ytrip-bi-meta">
                    <?php if ( $thumb_id ) : ?>
                        <span class="has-img">✓ Featured image set</span>
                    <?php else : ?>
                        <span class="no-img">✗ No featured image</span>
                    <?php endif; ?>
                    &nbsp;·&nbsp;
                    <?php if ( ! empty( $gallery_ids ) ) : ?>
                        <span class="has-img">✓ <?php echo count( $gallery_ids ); ?> gallery images</span>
                    <?php else : ?>
                        <span class="no-img">✗ No gallery</span>
                    <?php endif; ?>
                </div>
                <?php if ( ! empty( $gallery_ids ) ) : ?>
                <div class="ytrip-bi-gallery-strip" id="ytrip-bi-gallery-<?php echo $tour_id; ?>">
                    <?php foreach ( array_slice( $gallery_ids, 0, 8 ) as $gid ) :
                        $gurl = wp_get_attachment_image_url( $gid, 'thumbnail' );
                        if ( $gurl ) : ?>
                            <img src="<?php echo esc_url( $gurl ); ?>" alt="" />
                        <?php endif;
                    endforeach; ?>
                    <?php if ( count( $gallery_ids ) > 8 ) echo '<span style="font-size:12px;color:#9ca3af;line-height:36px;">+' . ( count($gallery_ids) - 8 ) . '</span>'; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="ytrip-bi-actions">
                <button type="button" class="ytrip-bi-btn ytrip-bi-btn--primary ytrip-bi-set-thumb"
                        data-tour-id="<?php echo $tour_id; ?>">
                    🖼 <?php esc_html_e( 'Set Image', 'ytrip' ); ?>
                </button>
                <button type="button" class="ytrip-bi-btn ytrip-bi-set-gallery"
                        data-tour-id="<?php echo $tour_id; ?>">
                    📸 <?php esc_html_e( 'Set Gallery', 'ytrip' ); ?>
                </button>
                <?php if ( $thumb_id || ! empty( $gallery_ids ) ) : ?>
                <button type="button" class="ytrip-bi-btn ytrip-bi-btn--danger ytrip-bi-clear"
                        data-tour-id="<?php echo $tour_id; ?>">
                    ✕
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function($) {
        var AJAX_URL  = <?php echo json_encode( $ajax_url ); ?>;
        var NONCE     = <?php echo json_encode( $nonce ); ?>;
        var progress  = document.getElementById('ytrip-bi-progress');

        function showProgress() {
            progress.style.display = 'block';
            progress.style.width   = '30%';
            setTimeout(function() { progress.style.width = '70%'; }, 200);
        }
        function hideProgress() {
            progress.style.width = '100%';
            setTimeout(function() { progress.style.display = 'none'; progress.style.width = '0'; }, 400);
        }

        function saveTour(tourId, thumbId, galleryIds) {
            showProgress();
            return fetch(AJAX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'ytrip_bulk_images_save',
                    nonce: NONCE,
                    tour_id: tourId,
                    thumb_id: thumbId || 0,
                    gallery_ids: Array.isArray(galleryIds) ? galleryIds.join(',') : galleryIds
                })
            })
            .then(r => r.json())
            .then(function(data) {
                hideProgress();
                if (data.success) {
                    refreshTourRow(tourId, data.data);
                }
            });
        }

        function refreshTourRow(tourId, data) {
            var row    = document.getElementById('ytrip-bi-tour-' + tourId);
            var thumb  = document.getElementById('ytrip-bi-thumb-' + tourId);
            var gallDiv = document.getElementById('ytrip-bi-gallery-' + tourId);

            row.dataset.thumbId    = data.thumb_id || '';
            row.dataset.galleryIds = data.gallery_ids.join(',');

            // Update thumb preview
            if (data.thumb_url) {
                thumb.innerHTML = '<img src="' + data.thumb_url + '" alt="" />';
            }

            // Update status classes
            var hasImages = data.thumb_id || data.gallery_ids.length > 0;
            row.classList.toggle('ytrip-bi-tour--has-images', hasImages);
            row.classList.toggle('ytrip-bi-tour--no-images', !hasImages);
        }

        // Set Featured Image
        document.querySelectorAll('.ytrip-bi-set-thumb').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tourId = this.dataset.tourId;
                var row    = document.getElementById('ytrip-bi-tour-' + tourId);
                var frame  = wp.media({
                    title: 'Select Featured Image',
                    button: { text: 'Set as Featured Image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var galleryIds = row.dataset.galleryIds ? row.dataset.galleryIds.split(',').filter(Boolean) : [];
                    saveTour(tourId, attachment.id, galleryIds);
                });
                frame.open();
            });
        });

        // Set Gallery
        document.querySelectorAll('.ytrip-bi-set-gallery').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tourId = this.dataset.tourId;
                var row    = document.getElementById('ytrip-bi-tour-' + tourId);
                var frame  = wp.media({
                    title: 'Select Gallery Images',
                    button: { text: 'Add to Gallery' },
                    multiple: 'add'
                });
                frame.on('select', function() {
                    var ids      = frame.state().get('selection').toArray().map(function(a) { return a.id; });
                    var thumbId  = row.dataset.thumbId || 0;
                    // Merge with existing
                    var existing = row.dataset.galleryIds ? row.dataset.galleryIds.split(',').filter(Boolean).map(Number) : [];
                    var merged   = existing.concat(ids.filter(function(id) { return !existing.includes(id); }));
                    // Auto-set thumb from first gallery if not already set
                    if (!thumbId && merged.length) thumbId = merged[0];
                    saveTour(tourId, thumbId, merged);
                });
                frame.open();
            });
        });

        // Clear images
        document.querySelectorAll('.ytrip-bi-clear').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tourId = this.dataset.tourId;
                if (!confirm('Remove all images from this tour?')) return;
                saveTour(tourId, 0, []);
            });
        });

    })(jQuery);
    </script>
    <?php
}
