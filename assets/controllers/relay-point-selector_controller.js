import { Controller } from '@hotwired/stimulus';

/**
 * Mondial Relay Point Selector Controller
 *
 * Main Stimulus controller for the relay point selection widget.
 * Handles search, list display, relay point selection, and state management.
 *
 * Targets:
 * - searchForm: Search form element
 * - searchInput: Postal code/city input field
 * - mapContainer: Leaflet map container
 * - listContainer: List of relay points
 * - selectedInfo: Selected relay point information display
 * - loadingIndicator: Loading state indicator
 * - errorMessage: Error message display
 * - submitButton: Form submit button (checkout continue)
 *
 * Values:
 * - searchUrl: API endpoint for searching relay points
 * - selectUrl: API endpoint for selecting a relay point
 * - shipmentId: Current shipment identifier
 * - initialPostalCode: Initial postal code from shipping address
 * - initialCity: Initial city from shipping address
 * - initialCountryCode: Initial country code from shipping address
 */
export default class extends Controller {
    static targets = [
        'searchForm',
        'searchInput',
        'mapContainer',
        'listContainer',
        'selectedInfo',
        'loadingIndicator',
        'errorMessage',
        'submitButton',
        'viewToggle',
        'mapView',
        'listView'
    ];

    static values = {
        searchUrl: String,
        selectUrl: String,
        shipmentId: Number,
        initialPostalCode: String,
        initialCity: String,
        initialCountryCode: String,
        selectedRelayPointId: String
    };

    /**
     * State management
     */
    state = {
        relayPoints: [],
        selectedRelayPoint: null,
        isLoading: false,
        currentView: 'list', // 'list' or 'map'
        lastSearchCriteria: null
    };

    /**
     * Initialize controller
     */
    connect() {
        console.log('Mondial Relay selector connected', {
            shipmentId: this.shipmentIdValue,
            initialPostalCode: this.initialPostalCodeValue
        });

        // Perform initial search with shipping address
        if (this.initialPostalCodeValue && this.initialCountryCodeValue) {
            this.performInitialSearch();
        }

        // Update submit button state
        this.updateSubmitButtonState();

        // Set initial view
        this.updateView(this.state.currentView);
    }

    /**
     * Perform initial search based on shipping address
     */
    async performInitialSearch() {
        const criteria = {
            postalCode: this.initialPostalCodeValue,
            city: this.initialCityValue || '',
            countryCode: this.initialCountryCodeValue,
            radius: 20000, // 20km default
            limit: 20
        };

        await this.searchRelayPoints(criteria);
    }

    /**
     * Handle search form submission
     */
    async search(event) {
        event.preventDefault();

        const formData = new FormData(this.searchFormTarget);
        const criteria = {
            postalCode: formData.get('postalCode') || this.initialPostalCodeValue,
            city: formData.get('city') || this.initialCityValue || '',
            countryCode: this.initialCountryCodeValue,
            radius: parseInt(formData.get('radius') || '20000', 10),
            limit: 20
        };

        await this.searchRelayPoints(criteria);
    }

    /**
     * Search relay points via API
     */
    async searchRelayPoints(criteria) {
        this.setLoading(true);
        this.clearError();

        try {
            const queryParams = new URLSearchParams(criteria);
            const url = `${this.searchUrlValue}?${queryParams.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error?.message || 'Erreur de recherche');
            }

            this.state.relayPoints = result.data.relayPoints;
            this.state.lastSearchCriteria = criteria;

            this.renderRelayPoints();

            // Notify map controller
            this.dispatchRelayPointsUpdated();

        } catch (error) {
            console.error('Search failed:', error);
            this.showError('Impossible de rechercher les points relais. Veuillez réessayer.');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Select a relay point
     */
    async selectRelayPoint(event) {
        const relayPointId = event.currentTarget.dataset.relayPointId;
        const relayPoint = this.state.relayPoints.find(
            rp => rp.relayPointId === relayPointId
        );

        if (!relayPoint) {
            console.error('Relay point not found:', relayPointId);
            return;
        }

        this.setLoading(true);
        this.clearError();

        try {
            // Save selection via API
            const url = this.selectUrlValue.replace('__ID__', this.shipmentIdValue);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    relayPointId: relayPoint.relayPointId,
                    name: relayPoint.name,
                    street: relayPoint.address.street,
                    postalCode: relayPoint.address.postalCode,
                    city: relayPoint.address.city,
                    countryCode: relayPoint.address.countryCode,
                    latitude: relayPoint.coordinates.latitude,
                    longitude: relayPoint.coordinates.longitude,
                    openingHours: relayPoint.openingHours || {}
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error?.message || 'Erreur de sélection');
            }

            // Update state
            this.state.selectedRelayPoint = relayPoint;
            this.selectedRelayPointIdValue = relayPoint.relayPointId;

            // Update UI
            this.renderSelectedInfo();
            this.updateSubmitButtonState();
            this.highlightSelectedInList();

            // Dispatch event for other controllers
            this.dispatchRelayPointSelected(relayPoint);

            // Show success message
            this.showSuccess('Point relais sélectionné avec succès');

        } catch (error) {
            console.error('Selection failed:', error);
            this.showError('Impossible de sélectionner ce point relais. Veuillez réessayer.');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Toggle between map and list views
     */
    toggleView(event) {
        const view = event.currentTarget.dataset.view;
        this.updateView(view);
    }

    /**
     * Update view display
     */
    updateView(view) {
        this.state.currentView = view;

        if (this.hasMapViewTarget && this.hasListViewTarget) {
            if (view === 'map') {
                this.mapViewTarget.classList.remove('hidden');
                this.listViewTarget.classList.add('hidden');
            } else {
                this.mapViewTarget.classList.add('hidden');
                this.listViewTarget.classList.remove('hidden');
            }
        }

        // Update toggle button states
        if (this.hasViewToggleTarget) {
            this.viewToggleTargets.forEach(button => {
                if (button.dataset.view === view) {
                    button.classList.add('active');
                    button.setAttribute('aria-pressed', 'true');
                } else {
                    button.classList.remove('active');
                    button.setAttribute('aria-pressed', 'false');
                }
            });
        }

        // Notify map controller to refresh
        if (view === 'map') {
            this.dispatchMapViewActivated();
        }
    }

    /**
     * Render relay points list
     */
    renderRelayPoints() {
        if (!this.hasListContainerTarget) {
            return;
        }

        if (this.state.relayPoints.length === 0) {
            this.listContainerTarget.innerHTML = `
                <div class="mr-no-results">
                    <p>Aucun point relais trouvé dans cette zone.</p>
                    <p>Essayez d'élargir votre recherche ou vérifiez votre code postal.</p>
                </div>
            `;
            return;
        }

        // Render list items
        const html = this.state.relayPoints.map(relayPoint =>
            this.renderRelayPointItem(relayPoint)
        ).join('');

        this.listContainerTarget.innerHTML = html;

        // Highlight selected if exists
        this.highlightSelectedInList();
    }

    /**
     * Render a single relay point list item
     */
    renderRelayPointItem(relayPoint) {
        const isSelected = this.state.selectedRelayPoint?.relayPointId === relayPoint.relayPointId;
        const distance = relayPoint.distanceKm
            ? `${relayPoint.distanceKm} km`
            : '';

        return `
            <div class="mr-relay-point-item ${isSelected ? 'selected' : ''}"
                 data-relay-point-id="${relayPoint.relayPointId}">
                <div class="mr-relay-point-header">
                    <h4 class="mr-relay-point-name">${this.escapeHtml(relayPoint.name)}</h4>
                    ${distance ? `<span class="mr-relay-point-distance">${distance}</span>` : ''}
                </div>
                <div class="mr-relay-point-address">
                    <p>${this.escapeHtml(relayPoint.address.street)}</p>
                    <p>${this.escapeHtml(relayPoint.address.postalCode)} ${this.escapeHtml(relayPoint.address.city)}</p>
                </div>
                <div class="mr-relay-point-actions">
                    <button type="button"
                            class="btn btn-primary mr-select-button"
                            data-action="click->relay-point-selector#selectRelayPoint"
                            data-relay-point-id="${relayPoint.relayPointId}"
                            ${isSelected ? 'disabled' : ''}>
                        ${isSelected ? 'Sélectionné' : 'Sélectionner'}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Render selected relay point information
     */
    renderSelectedInfo() {
        if (!this.hasSelectedInfoTarget || !this.state.selectedRelayPoint) {
            return;
        }

        const rp = this.state.selectedRelayPoint;

        this.selectedInfoTarget.innerHTML = `
            <div class="mr-selected-relay-point">
                <h4>Point relais sélectionné</h4>
                <div class="mr-selected-details">
                    <p class="mr-selected-name">${this.escapeHtml(rp.name)}</p>
                    <p class="mr-selected-address">
                        ${this.escapeHtml(rp.address.street)}<br>
                        ${this.escapeHtml(rp.address.postalCode)} ${this.escapeHtml(rp.address.city)}
                    </p>
                </div>
                <button type="button"
                        class="btn btn-link mr-change-button"
                        data-action="click->relay-point-selector#showWidget">
                    Changer de point relais
                </button>
            </div>
        `;

        this.selectedInfoTarget.classList.remove('hidden');
    }

    /**
     * Highlight selected relay point in list
     */
    highlightSelectedInList() {
        if (!this.hasListContainerTarget || !this.state.selectedRelayPoint) {
            return;
        }

        const items = this.listContainerTarget.querySelectorAll('.mr-relay-point-item');
        items.forEach(item => {
            const id = item.dataset.relayPointId;
            if (id === this.state.selectedRelayPoint.relayPointId) {
                item.classList.add('selected');
                item.querySelector('.mr-select-button')?.setAttribute('disabled', 'disabled');
            } else {
                item.classList.remove('selected');
                item.querySelector('.mr-select-button')?.removeAttribute('disabled');
            }
        });
    }

    /**
     * Update submit button state based on selection
     */
    updateSubmitButtonState() {
        if (!this.hasSubmitButtonTarget) {
            return;
        }

        const hasSelection = this.state.selectedRelayPoint !== null;
        this.submitButtonTarget.disabled = !hasSelection;

        if (!hasSelection) {
            this.submitButtonTarget.title = 'Veuillez sélectionner un point relais';
        } else {
            this.submitButtonTarget.title = '';
        }
    }

    /**
     * Set loading state
     */
    setLoading(isLoading) {
        this.state.isLoading = isLoading;

        if (this.hasLoadingIndicatorTarget) {
            this.loadingIndicatorTarget.classList.toggle('hidden', !isLoading);
        }

        // Disable search form during loading
        if (this.hasSearchFormTarget) {
            const inputs = this.searchFormTarget.querySelectorAll('input, button');
            inputs.forEach(input => {
                input.disabled = isLoading;
            });
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (!this.hasErrorMessageTarget) {
            return;
        }

        this.errorMessageTarget.textContent = message;
        this.errorMessageTarget.classList.remove('hidden');
        this.errorMessageTarget.setAttribute('role', 'alert');
    }

    /**
     * Clear error message
     */
    clearError() {
        if (!this.hasErrorMessageTarget) {
            return;
        }

        this.errorMessageTarget.textContent = '';
        this.errorMessageTarget.classList.add('hidden');
        this.errorMessageTarget.removeAttribute('role');
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Could use a toast notification system here
        console.log('Success:', message);
    }

    /**
     * Dispatch custom event when relay points are updated
     */
    dispatchRelayPointsUpdated() {
        this.dispatch('relay-points-updated', {
            detail: {
                relayPoints: this.state.relayPoints,
                searchCriteria: this.state.lastSearchCriteria
            }
        });
    }

    /**
     * Dispatch custom event when relay point is selected
     */
    dispatchRelayPointSelected(relayPoint) {
        this.dispatch('relay-point-selected', {
            detail: { relayPoint }
        });
    }

    /**
     * Dispatch event when map view is activated
     */
    dispatchMapViewActivated() {
        this.dispatch('map-view-activated');
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
