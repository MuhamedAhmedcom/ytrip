/**
 * YTrip Google Maps View JavaScript
 *
 * Handles Google Maps initialization, markers, clustering, and interactions
 * Alternative to Leaflet.js map-view.js
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    window.YTripGoogleMap = {
        map: null,
        markers: [],
        markerClusterer: null,
        infoWindow: null,
        activeMarker: null,

        /**
         * Initialize map with locations.
         */
        init: function (locations) {
            const self = this;

            if (!locations || !Array.isArray(locations)) {
                locations = [];
            }

            // Check if map container exists
            const mapContainer = document.getElementById('ytrip-tours-map');
            if (!mapContainer) {
                return;
            }

            // Initialize map
            this.map = new google.maps.Map(mapContainer, {
                center: {
                    lat: parseFloat(ytripMap.defaultLat),
                    lng: parseFloat(ytripMap.defaultLng)
                },
                zoom: parseInt(ytripMap.defaultZoom),
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
                styles: this.getMapStyles()
            });

            // Create single InfoWindow
            this.infoWindow = new google.maps.InfoWindow({
                maxWidth: 320
            });

            // Add markers
            this.addMarkers(locations);

            // Fit bounds to markers
            if (locations.length > 0) {
                this.fitBoundsToMarkers();
            }

            // Bind events
            this.bindEvents();
        },

        /**
         * Get custom map styles.
         */
        getMapStyles: function () {
            return [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ];
        },

        /**
         * Add markers to map.
         */
        addMarkers: function (locations) {
            const self = this;

            // Clear existing markers
            this.clearMarkers();

            locations.forEach(function (location) {
                if (!location.lat || !location.lng) {
                    return;
                }

                const marker = new google.maps.Marker({
                    position: { lat: location.lat, lng: location.lng },
                    map: self.map,
                    icon: self.createMarkerIcon(location.price),
                    title: location.title,
                    tourId: location.id,
                    tourData: location
                });

                // Click event
                marker.addListener('click', function () {
                    self.openInfoWindow(marker, location);
                    self.setActiveMarker(location.id);
                });

                self.markers.push(marker);
            });

            // Initialize marker clusterer
            if (typeof MarkerClusterer !== 'undefined' && this.markers.length > 0) {
                this.markerClusterer = new MarkerClusterer(this.map, this.markers, {
                    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
                    maxZoom: 15,
                    gridSize: 50,
                    styles: [{
                        textColor: '#fff',
                        textSize: 14,
                        url: this.createClusterIcon(),
                        height: 44,
                        width: 44
                    }]
                });
            }
        },

        /**
         * Create custom marker icon.
         */
        createMarkerIcon: function (price) {
            const priceLabel = price ? '$' + Math.round(price) : '•';

            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="40" viewBox="0 0 60 40">
                    <rect x="0" y="0" width="60" height="30" rx="15" fill="#2563eb"/>
                    <polygon points="30,40 24,30 36,30" fill="#2563eb"/>
                    <text x="30" y="20" text-anchor="middle" fill="#fff" font-size="12" font-weight="bold" font-family="Arial">${priceLabel}</text>
                </svg>
            `;

            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(60, 40),
                anchor: new google.maps.Point(30, 40)
            };
        },

        /**
         * Create cluster icon.
         */
        createClusterIcon: function () {
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44">
                    <circle cx="22" cy="22" r="20" fill="#2563eb" stroke="#fff" stroke-width="2"/>
                </svg>
            `;
            return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
        },

        /**
         * Open InfoWindow.
         */
        openInfoWindow: function (marker, location) {
            const content = this.createInfoWindowContent(location);
            this.infoWindow.setContent(content);
            this.infoWindow.open(this.map, marker);
        },

        /**
         * Create InfoWindow content.
         */
        createInfoWindowContent: function (location) {
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
         * Fit bounds to all markers.
         */
        fitBoundsToMarkers: function () {
            if (this.markers.length === 0) return;

            const bounds = new google.maps.LatLngBounds();
            this.markers.forEach(function (marker) {
                bounds.extend(marker.getPosition());
            });
            this.map.fitBounds(bounds);

            // Don't zoom in too far
            google.maps.event.addListenerOnce(this.map, 'bounds_changed', () => {
                if (this.map.getZoom() > 15) {
                    this.map.setZoom(15);
                }
            });
        },

        /**
         * Clear all markers.
         */
        clearMarkers: function () {
            this.markers.forEach(function (marker) {
                marker.setMap(null);
            });
            this.markers = [];

            if (this.markerClusterer) {
                this.markerClusterer.clearMarkers();
            }
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
         * Find and focus marker by tour ID.
         */
        openMarkerByTourId: function (tourId) {
            const self = this;

            this.markers.forEach(function (marker) {
                if (marker.tourId === tourId) {
                    self.map.setCenter(marker.getPosition());
                    self.map.setZoom(14);
                    self.openInfoWindow(marker, marker.tourData);
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

                    // Trigger resize for proper rendering
                    google.maps.event.trigger(self.map, 'resize');
                    self.fitBoundsToMarkers();
                }
            });
        },

        /**
         * Refresh markers with new data.
         */
        refresh: function (locations) {
            this.addMarkers(locations);
            this.updateSidebarList(locations);
            this.fitBoundsToMarkers();
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

})(jQuery);
