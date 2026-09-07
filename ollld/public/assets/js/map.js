/**
 * Sandworth Living map layer.
 *
 * Vanilla JS wrapper around Leaflet (loaded from CDN in layout.php).
 * Reads a JSON pin payload from a data-pins attribute and renders
 * synced markers + result list highlighting, similar to the
 * Zillow search-results map pattern.
 */
(function () {
    'use strict';

    function parsePins(raw) {
        if (!raw) {
            return [];
        }

        try {
            var pins = JSON.parse(raw);
            return Array.isArray(pins) ? pins : [];
        } catch (err) {
            return [];
        }
    }

    function money(value) {
        return value || '';
    }

    function buildPopupHtml(pin) {
        var img = pin.image ? '<img src="' + pin.image + '" alt="" class="map-popup-img">' : '';

        return (
            '<a class="map-popup" href="' + pin.url + '">' +
                img +
                '<span class="map-popup-price">' + money(pin.price) + '</span>' +
                '<span class="map-popup-title">' + pin.title + '</span>' +
                '<span class="map-popup-location">' + pin.location + '</span>' +
            '</a>'
        );
    }

    function highlightCard(container, id) {
        var cards = container.querySelectorAll('[data-listing-id]');
        cards.forEach(function (card) {
            if (String(card.getAttribute('data-listing-id')) === String(id)) {
                card.classList.add('is-map-active');
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                card.classList.remove('is-map-active');
            }
        });
    }

    function initSearchMap(mapEl) {
        var pins = parsePins(mapEl.getAttribute('data-pins'));
        var listPanelSelector = mapEl.getAttribute('data-list-panel');
        var listPanel = listPanelSelector ? document.querySelector(listPanelSelector) : null;
        var zoom = parseInt(mapEl.getAttribute('data-zoom'), 10) || 12;

        if (typeof L === 'undefined') {
            mapEl.innerHTML = '<p class="map-fallback">Map library did not load. Check your network connection.</p>';
            return;
        }

        var center = pins.length ? [pins[0].lat, pins[0].lng] : [6.5244, 3.3792];
        var map = L.map(mapEl, { scrollWheelZoom: false }).setView(center, zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var markers = {};
        var bounds = [];

        pins.forEach(function (pin) {
            var marker = L.marker([pin.lat, pin.lng]).addTo(map);
            marker.bindPopup(buildPopupHtml(pin));

            marker.on('click', function () {
                if (listPanel) {
                    highlightCard(listPanel, pin.id);
                }
            });

            markers[pin.id] = marker;
            bounds.push([pin.lat, pin.lng]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [32, 32] });
        }

        if (listPanel) {
            listPanel.querySelectorAll('[data-listing-id]').forEach(function (card) {
                card.addEventListener('mouseenter', function () {
                    var id = card.getAttribute('data-listing-id');
                    if (markers[id]) {
                        markers[id].setZIndexOffset(1000);
                    }
                });

                card.addEventListener('click', function (event) {
                    var id = card.getAttribute('data-listing-id');
                    var marker = markers[id];

                    if (marker && event.target.closest && !event.target.closest('a')) {
                        map.setView(marker.getLatLng(), Math.max(zoom, 14));
                        marker.openPopup();
                        highlightCard(listPanel, id);
                    }
                });
            });
        }
    }

    function initSingleMap(mapEl) {
        var pins = parsePins(mapEl.getAttribute('data-pins'));

        if (typeof L === 'undefined' || !pins.length) {
            mapEl.innerHTML = '<p class="map-fallback">Map preview unavailable.</p>';
            return;
        }

        var pin = pins[0];
        var map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: false }).setView([pin.lat, pin.lng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([pin.lat, pin.lng]).addTo(map).bindPopup(buildPopupHtml(pin)).openPopup();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.map-canvas[data-mode="search"]').forEach(initSearchMap);
        document.querySelectorAll('.map-canvas[data-mode="single"]').forEach(initSingleMap);
    });
})();
