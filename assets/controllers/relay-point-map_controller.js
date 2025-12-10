import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';

/**
 * Mondial Relay Point Map Controller
 *
 * Stimulus controller for Leaflet map integration.
 * Displays relay points on an interactive map with markers and popups.
 *
 * Targets:
 * - container: Map container element
 *
 * Values:
 * - latitude: Initial map center latitude
 * - longitude: Initial map center longitude
 * - zoom: Initial map zoom level (default: 12)
 */
export default class extends Controller {
    static targets = ['container'];

    static values = {
        latitude: { type: Number, default: 48.8566 }, // Paris default
        longitude: { type: Number, default: 2.3522 },
        zoom: { type: Number, default: 12 }
    };

    /**
     * Map state
     */
    map = null;
    markers = [];
    markerCluster = null;
    relayPoints = [];
    selectedRelayPointId = null;

    /**
     * Leaflet map icons
     */
    defaultIcon = null;
    selectedIcon = null;

    /**
     * Initialize controller and create map
     */
    connect() {
        console.log('Mondial Relay map connected');

        // Initialize Leaflet icons
        this.initializeIcons();

        // Create map
        this.initializeMap();

        // Listen for relay points updates from selector controller
        this.element.addEventListener(
            'relay-point-selector:relay-points-updated',
            this.handleRelayPointsUpdated.bind(this)
        );

        this.element.addEventListener(
            'relay-point-selector:relay-point-selected',
            this.handleRelayPointSelected.bind(this)
        );

        this.element.addEventListener(
            'relay-point-selector:map-view-activated',
            this.handleMapViewActivated.bind(this)
        );
    }

    /**
     * Cleanup when controller disconnects
     */
    disconnect() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }

    /**
     * Initialize Leaflet map
     */
    initializeMap() {
        if (!this.hasContainerTarget) {
            console.error('Map container not found');
            return;
        }

        // Create map instance
        this.map = L.map(this.containerTarget, {
            center: [this.latitudeValue, this.longitudeValue],
            zoom: this.zoomValue,
            scrollWheelZoom: true,
            dragging: true,
            touchZoom: true
        });

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
            minZoom: 3
        }).addTo(this.map);

        // Add user location marker if available
        this.addUserLocationMarker();

        console.log('Map initialized', {
            center: [this.latitudeValue, this.longitudeValue],
            zoom: this.zoomValue
        });
    }

    /**
     * Initialize Leaflet marker icons
     */
    initializeIcons() {
        // Default relay point icon (blue)
        this.defaultIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Selected relay point icon (green)
        this.selectedIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }

    /**
     * Add user location marker (shipping address)
     */
    addUserLocationMarker() {
        // Red marker for user's location
        const userIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        L.marker([this.latitudeValue, this.longitudeValue], {
            icon: userIcon,
            title: 'Votre adresse de livraison'
        }).addTo(this.map)
          .bindPopup('<strong>Votre adresse de livraison</strong>')
          .openPopup();
    }

    /**
     * Handle relay points updated event
     */
    handleRelayPointsUpdated(event) {
        const { relayPoints, searchCriteria } = event.detail;

        console.log('Relay points updated', {
            count: relayPoints.length,
            criteria: searchCriteria
        });

        this.relayPoints = relayPoints;
        this.updateMarkers();

        // Update map center if we have relay points
        if (relayPoints.length > 0) {
            this.fitBoundsToRelayPoints();
        }
    }

    /**
     * Handle relay point selected event
     */
    handleRelayPointSelected(event) {
        const { relayPoint } = event.detail;

        console.log('Relay point selected', relayPoint);

        this.selectedRelayPointId = relayPoint.relayPointId;
        this.updateMarkers();

        // Pan to selected marker
        this.panToRelayPoint(relayPoint);
    }

    /**
     * Handle map view activated event
     */
    handleMapViewActivated() {
        // Force map to recalculate size when view is shown
        if (this.map) {
            setTimeout(() => {
                this.map.invalidateSize();
            }, 100);
        }
    }

    /**
     * Update map markers for relay points
     */
    updateMarkers() {
        // Clear existing markers
        this.clearMarkers();

        // Add markers for each relay point
        this.relayPoints.forEach(relayPoint => {
            this.addRelayPointMarker(relayPoint);
        });
    }

    /**
     * Add marker for a relay point
     */
    addRelayPointMarker(relayPoint) {
        const isSelected = relayPoint.relayPointId === this.selectedRelayPointId;
        const icon = isSelected ? this.selectedIcon : this.defaultIcon;

        const marker = L.marker(
            [relayPoint.coordinates.latitude, relayPoint.coordinates.longitude],
            {
                icon: icon,
                title: relayPoint.name,
                relayPointId: relayPoint.relayPointId
            }
        );

        // Create popup content
        const popupContent = this.createPopupContent(relayPoint, isSelected);
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'mr-map-popup'
        });

        // Add click handler to marker
        marker.on('click', () => {
            this.handleMarkerClick(relayPoint);
        });

        // Add to map
        marker.addTo(this.map);

        // Store marker reference
        this.markers.push(marker);

        // Open popup if selected
        if (isSelected) {
            marker.openPopup();
        }
    }

    /**
     * Create popup HTML content for relay point
     */
    createPopupContent(relayPoint, isSelected) {
        const distance = relayPoint.distanceKm
            ? `<div class="mr-popup-distance">${relayPoint.distanceKm} km</div>`
            : '';

        const selectButton = isSelected
            ? '<button type="button" class="btn btn-success" disabled>Sélectionné</button>'
            : `<button type="button"
                       class="btn btn-primary mr-popup-select"
                       data-relay-point-id="${relayPoint.relayPointId}">
                   Sélectionner
               </button>`;

        return `
            <div class="mr-popup-content">
                <h4 class="mr-popup-title">${this.escapeHtml(relayPoint.name)}</h4>
                ${distance}
                <div class="mr-popup-address">
                    <p>${this.escapeHtml(relayPoint.address.street)}</p>
                    <p>${this.escapeHtml(relayPoint.address.postalCode)} ${this.escapeHtml(relayPoint.address.city)}</p>
                </div>
                <div class="mr-popup-actions">
                    ${selectButton}
                </div>
            </div>
        `;
    }

    /**
     * Handle marker click
     */
    handleMarkerClick(relayPoint) {
        // Dispatch event to notify selector controller
        this.dispatch('marker-clicked', {
            detail: { relayPoint }
        });
    }

    /**
     * Clear all relay point markers
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            marker.remove();
        });
        this.markers = [];
    }

    /**
     * Fit map bounds to show all relay points
     */
    fitBoundsToRelayPoints() {
        if (this.relayPoints.length === 0) {
            return;
        }

        const bounds = L.latLngBounds(
            this.relayPoints.map(rp => [
                rp.coordinates.latitude,
                rp.coordinates.longitude
            ])
        );

        // Include user location in bounds
        bounds.extend([this.latitudeValue, this.longitudeValue]);

        this.map.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 15
        });
    }

    /**
     * Pan map to relay point location
     */
    panToRelayPoint(relayPoint) {
        this.map.setView(
            [relayPoint.coordinates.latitude, relayPoint.coordinates.longitude],
            14,
            { animate: true }
        );
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Dispatch custom event
     */
    dispatch(name, detail = {}) {
        this.element.dispatchEvent(new CustomEvent(`relay-point-map:${name}`, {
            bubbles: true,
            ...detail
        }));
    }
}
