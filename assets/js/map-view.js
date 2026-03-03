/**
 * YTrip Map View JavaScript
 *
 * Handles Leaflet.js map initialization, markers, clustering, and interactions
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    window.YTripMap = {
        map: null,
        markers: null,
        markersData: [],
        activeMarker: null,

        /**
         * Initialize map with locations.
         */
        init: function (locations) {
            const self = this;

            if (!locations || !Array.isArray(locations)) {
                locations = [];
            }

            this.markersData = locations;

            // Check if map container exists
            const mapContainer = document.getElementById('ytrip-tours-map');
            if (!mapContainer) {
                return;
            }

            // Initialize map
            this.map = L.map('ytrip-tours-map', {
                scrollWheelZoom: true,
                zoomControl: true
            });

            // Add tile layer
            L.tileLayer(ytripMap.tileUrl, {
                attribution: ytripMap.attribution,
                maxZoom: 19
            }).addTo(this.map);

            // Initialize marker cluster group
            this.markers = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                iconCreateFunction: function (cluster) {
                    const count = cluster.getChildCount();
                    let size = 'small';

                    if (count >= 10 && count < 50) {
                        size = 'medium';
                    } else if (count >= 50) {
                        size = 'large';
                    }

                    return L.divIcon({
                        html: '<div class="ytrip-cluster-marker ytrip-cluster-' + size + '">' + count + '</div>',
                        className: 'ytrip-cluster-icon',
                        iconSize: [40, 40]
                    });
                }
            });

            // Add markers
            this.addMarkers(locations);

            // Fit bounds to markers
            if (locations.length > 0) {
                const bounds = this.markers.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds, { padding: [50, 50] });
                }
            } else {
                this.map.setView([ytripMap.defaultLat, ytripMap.defaultLng], ytripMap.defaultZoom);
            }

            // Bind events
            this.bindEvents();
        },

        /**
         * Add markers to map.
         */
        addMarkers: function (locations) {
            const self = this;

            locations.forEach(function (location) {
                if (!location.lat || !location.lng) {
                    return;
                }

                const marker = L.marker([location.lat, location.lng], {
                    icon: self.createMarkerIcon(location.price)
                });

                // Bind popup
                marker.bindPopup(self.createPopup(location), {
                    maxWidth: 300,
                    className: 'ytrip-map-popup-wrapper'
                });

                // Store tour ID on marker
                marker.tourId = location.id;

                // Events
                marker.on('click', function () {
                    self.setActiveMarker(location.id);
                });

                marker.on('popupopen', function () {
                    self.setActiveMarker(location.id);
                });

                self.markers.addLayer(marker);
            });

            this.map.addLayer(this.markers);
        },

        /**
         * Create custom marker icon.
         */
        createMarkerIcon: function (price) {
            const priceLabel = price ? '$' + Math.round(price) : '';

            return L.divIcon({
                html: `
                    <div class="ytrip-marker">
                        <div class="ytrip-marker-pin">
                            <span class="ytrip-marker-price">${priceLabel}</span>
                        </div>
                        <div class="ytrip-marker-shadow"></div>
                    </div>
                `,
                className: 'ytrip-marker-icon',
                iconSize: [50, 60],
                iconAnchor: [25, 60],
                popupAnchor: [0, -60]
            });
        },

        /**
         * Create popup content.
         */
        createPopup: function (location) {
            let html = '<div class="ytrip-map-popup">';

            if (location.thumbnail) {
                html += `
                    <div class="ytrip-map-popup__image">
                        <img src="${location.thumbnail}" alt="${location.title}">
                    </div>
                `;
            }

            html += `
                <h4 class="ytrip-map-popup__title">${location.title}</h4>
                <div class="ytrip-map-popup__meta">
            `;

            if (location.destination) {
                html += `<span>📍 ${location.destination}</span>`;
            }

            if (location.duration) {
                html += `<span>⏱️ ${location.duration}</span>`;
            }

            if (location.rating > 0) {
                html += `<span>⭐ ${location.rating.toFixed(1)}</span>`;
            }

            html += '</div>';

            if (location.price_html) {
                html += `
                    <div class="ytrip-map-popup__price">
                        ${location.price_html}
                        <small>${ytripMap.strings.perPerson}</small>
                    </div>
                `;
            }

            html += `
                <a href="${location.url}" class="ytrip-map-popup__btn">
                    ${ytripMap.strings.viewDetails}
                </a>
            </div>`;

            return html;
        },

        /**
         * Set active marker and highlight sidebar item.
         */
        setActiveMarker: function (tourId) {
            // Remove previous active class
            $('.ytrip-map-tour-item').removeClass('active');

            // Add active class to current
            $(`.ytrip-map-tour-item[data-tour-id="${tourId}"]`).addClass('active');

            // Scroll sidebar to active item
            const $item = $(`.ytrip-map-tour-item[data-tour-id="${tourId}"]`);
            if ($item.length) {
                const $list = $('#ytrip-map-tour-list');
                $list.animate({
                    scrollTop: $item.offset().top - $list.offset().top + $list.scrollTop() - 20
                }, 300);
            }

            this.activeMarker = tourId;
        },

        /**
         * Find and open marker by tour ID.
         */
        openMarkerByTourId: function (tourId) {
            const self = this;

            this.markers.eachLayer(function (marker) {
                if (marker.tourId === tourId) {
                    // Zoom to marker
                    self.map.setView(marker.getLatLng(), 14, { animate: true });

                    // Open popup
                    setTimeout(function () {
                        marker.openPopup();
                    }, 300);
                }
            });
        },

        /**
         * Bind events.
         */
        bindEvents: function () {
            const self = this;

            // Sidebar item click
            $(document).on('click', '.ytrip-map-tour-item', function () {
                const tourId = parseInt($(this).data('tour-id'));
                self.openMarkerByTourId(tourId);
            });

            // Sidebar close button
            $('.ytrip-map-sidebar__close').on('click', function () {
                $('.ytrip-map-sidebar').toggleClass('hidden');
            });

            // Map/Grid toggle
            $('#ytrip-map-toggle').on('click', function () {
                const $this = $(this);
                const $gridContainer = $('#ytrip-tours-container');
                const $mapContainer = $('.ytrip-map-view-container');

                if ($this.hasClass('active')) {
                    // Switch to grid view
                    $this.removeClass('active');
                    $mapContainer.hide();
                    $gridContainer.show();
                } else {
                    // Switch to map view
                    $this.addClass('active');
                    $gridContainer.hide();
                    $mapContainer.show();

                    // Invalidate map size (important for proper rendering)
                    if (self.map) {
                        setTimeout(function () {
                            self.map.invalidateSize();
                        }, 100);
                    }
                }
            });
        },

        /**
         * Refresh markers with new data.
         */
        refresh: function (locations) {
            if (!this.map || !this.markers) {
                return;
            }

            // Clear existing markers
            this.markers.clearLayers();

            // Add new markers
            this.addMarkers(locations);

            // Update sidebar list
            this.updateSidebarList(locations);

            // Fit bounds
            if (locations.length > 0) {
                const bounds = this.markers.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds, { padding: [50, 50] });
                }
            }
        },

        /**
         * Update sidebar tour list.
         */
        updateSidebarList: function (locations) {
            const $list = $('#ytrip-map-tour-list');
            if (!$list.length) {
                return;
            }

            let html = '';

            if (locations.length === 0) {
                html = `<p class="ytrip-no-results">${ytripMap.strings.noTours}</p>`;
            } else {
                locations.forEach(function (location) {
                    html += `
                        <div class="ytrip-map-tour-item" data-tour-id="${location.id}">
                            <div class="ytrip-map-tour-item__thumb">
                                ${location.thumbnail ? `<img src="${location.thumbnail}" alt="${location.title}">` : ''}
                            </div>
                            <div class="ytrip-map-tour-item__info">
                                <h4 class="ytrip-map-tour-item__title">${location.title}</h4>
                                <span class="ytrip-map-tour-item__price">${location.price_html}</span>
                            </div>
                        </div>
                    `;
                });
            }

            $list.html(html);

            // Update count
            $('.ytrip-map-sidebar__title').text(
                locations.length + ' Tour' + (locations.length !== 1 ? 's' : '')
            );
        }
    };

    // Add custom marker CSS
    const markerStyles = `
        <style>
            .ytrip-marker-icon {
                background: transparent !important;
                border: none !important;
            }
            .ytrip-marker {
                position: relative;
                width: 50px;
                height: 60px;
            }
            .ytrip-marker-pin {
                position: absolute;
                bottom: 12px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--ytrip-primary, #2563eb);
                color: #fff;
                padding: 4px 8px;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 700;
                white-space: nowrap;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            .ytrip-marker-pin::after {
                content: '';
                position: absolute;
                bottom: -6px;
                left: 50%;
                transform: translateX(-50%);
                border-left: 6px solid transparent;
                border-right: 6px solid transparent;
                border-top: 6px solid var(--ytrip-primary, #2563eb);
            }
            .ytrip-marker-shadow {
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 10px;
                height: 5px;
                background: rgba(0,0,0,0.2);
                border-radius: 50%;
            }
            .ytrip-cluster-icon {
                background: transparent !important;
            }
            .ytrip-cluster-marker {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: var(--ytrip-primary, #2563eb);
                color: #fff;
                font-weight: 700;
                font-size: 14px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            .ytrip-cluster-medium {
                width: 48px;
                height: 48px;
                font-size: 15px;
            }
            .ytrip-cluster-large {
                width: 56px;
                height: 56px;
                font-size: 16px;
            }
            .ytrip-map-sidebar.hidden {
                transform: translateX(100%);
            }
            [dir="rtl"] .ytrip-map-sidebar.hidden {
                transform: translateX(-100%);
            }
        </style>
    `;
    $('head').append(markerStyles);

})(jQuery);
