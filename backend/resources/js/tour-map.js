// Tour Map Component - Google Maps Integration
// console.log('🗺️ Loading tour-map.js', new Date());

window.tourMapComponent = function() {
    return {
        map: null,
        markers: [],
        markerBounds: null,
        autocomplete: null,
        tempMarker: null,
        routeLine: null, // Polyline para mostrar la ruta
        points: [],

        // Form fields
        pointName: '',
        pointDescription: '',
        pointCoordinates: '',
        pointType: '',

        init() {
            // Wait for DOM and Google Maps to be ready, then initialize map
            setTimeout(() => {
                this.initializeMap();
            }, 1000);
        },

        initializeMap() {
            if (this.map) return; // Already initialized

            // Check if Google Maps is loaded
            if (typeof google === 'undefined' || !google.maps) {
                console.warn('⚠️ Google Maps not loaded yet, retrying...');
                setTimeout(() => this.initializeMap(), 500);
                return;
            }

            // console.log('🗺️ Initializing Google Maps');

            const mapElement = document.getElementById('tourMapCanvas');
            if (!mapElement) {
                console.error('❌ Map canvas element not found');
                return;
            }

            // Initialize map centered on Peru/Bolivia
            this.map = new google.maps.Map(mapElement, {
                center: { lat: -15.8422, lng: -70.0199 },
                zoom: 6,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            this.markerBounds = new google.maps.LatLngBounds();

            // Initialize autocomplete
            const input = document.getElementById('pointNameInput');
            if (input && google.maps.places) {
                this.autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['establishment', 'geocode']
                });

                this.autocomplete.addListener('place_changed', () => {
                    this.handlePlaceSelect();
                });
            } else {
                console.error('❌ Google Places API not available or input not found');
            }

            // Initialize temporary marker (draggable)
            this.tempMarker = new google.maps.Marker({
                map: this.map,
                position: null,
                draggable: true,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#10b981',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 10
                }
            });

            // Click on map to place marker
            this.map.addListener('click', (event) => {
                this.tempMarker.setPosition(event.latLng);
                this.pointCoordinates = `${event.latLng.lat()},${event.latLng.lng()}`;
            });

            // Drag marker to update coordinates
            this.tempMarker.addListener('dragend', (event) => {
                const position = this.tempMarker.getPosition();
                this.pointCoordinates = `${position.lat()},${position.lng()}`;
            });

            // Load existing points
            this.loadExistingPoints();
        },

        handlePlaceSelect() {
            const place = this.autocomplete.getPlace();

            if (!place.geometry) {
                // console.warn('No geometry for selected place');
                return;
            }

            const location = place.geometry.location;
            this.pointName = place.name;
            this.pointDescription = place.formatted_address || '';
            this.pointCoordinates = `${location.lat()},${location.lng()}`;

            this.tempMarker.setPosition(location);
            this.map.setCenter(location);
            this.map.setZoom(15);
        },

        addPoint() {
            // Validation
            if (!this.pointName.trim()) {
                alert('Por favor ingrese el nombre del lugar');
                return;
            }

            if (!this.pointCoordinates) {
                alert('Por favor seleccione una ubicación en el mapa');
                return;
            }

            if (!this.pointType) {
                alert('Por favor seleccione el tipo de lugar');
                return;
            }

            const point = {
                name: this.pointName,
                description: this.pointDescription,
                coordinates: this.pointCoordinates,
                type: this.pointType,
                order: this.points.length + 1,
            };

            this.points.push(point);
            this.renderMarkers();
            this.clearForm();
            this.emitUpdate();

            // console.log('✅ Point added:', point);
        },

        removePoint(index) {
            if (confirm('¿Eliminar este punto del mapa?')) {
                this.points.splice(index, 1);
                // Reorder remaining points
                this.points.forEach((point, idx) => {
                    point.order = idx + 1;
                });
                this.renderMarkers();
                this.emitUpdate();
            }
        },

        renderMarkers() {
            // Clear existing markers
            this.markers.forEach(marker => marker.setMap(null));
            this.markers = [];
            this.markerBounds = new google.maps.LatLngBounds();

            // Clear existing route line
            if (this.routeLine) {
                this.routeLine.setMap(null);
                this.routeLine = null;
            }

            if (this.points.length === 0) {
                this.map.setCenter({ lat: -15.8422, lng: -70.0199 });
                this.map.setZoom(6);
                return;
            }

            // Prepare route path coordinates
            const routePath = [];

            // Add markers for each point
            this.points.forEach((point, index) => {
                const [lat, lng] = point.coordinates.split(',').map(Number);
                const position = new google.maps.LatLng(lat, lng);

                const marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    label: {
                        text: String(index + 1),
                        color: 'white',
                        fontWeight: 'bold',
                        fontSize: '14px'
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: '#4F46E5',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                        scale: 20
                    },
                    title: point.name
                });

                // Info window
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 8px;">
                            <h4 style="margin: 0 0 4px 0; font-weight: bold;">${point.name}</h4>
                            <p style="margin: 0; color: #666; font-size: 12px;">${this.getTypeLabel(point.type)}</p>
                            ${point.description ? `<p style="margin: 4px 0 0 0; font-size: 13px;">${point.description}</p>` : ''}
                        </div>
                    `
                });

                marker.addListener('click', () => {
                    infoWindow.open(this.map, marker);
                });

                this.markers.push(marker);
                this.markerBounds.extend(position);

                // Add position to route path
                routePath.push(position);
            });

            // Draw route line connecting all points
            if (routePath.length > 1) {
                this.routeLine = new google.maps.Polyline({
                    path: routePath,
                    geodesic: true,
                    strokeColor: '#4F46E5', // Indigo-600
                    strokeOpacity: 0.8,
                    strokeWeight: 3,
                    map: this.map
                });

                // console.log(`✅ Route drawn with ${routePath.length} points`);
            }

            // Fit bounds to show all markers
            this.map.fitBounds(this.markerBounds);
        },

        clearForm() {
            this.pointName = '';
            this.pointDescription = '';
            this.pointCoordinates = '';
            this.pointType = '';
            this.tempMarker.setPosition(null);
        },

        loadExistingPoints() {
            // Get points from Livewire component
            const mapPointsData = window.Livewire.find(
                document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
            )?.get('mapPoints');

            if (mapPointsData && Array.isArray(mapPointsData)) {
                this.points = [...mapPointsData];
                this.renderMarkers();
                // console.log(`✅ Loaded ${this.points.length} existing points`);
            }
        },

        emitUpdate() {
            // Update Livewire property directly using @entangle or wire:model
            // Find the Livewire component and update the mapPoints property
            const livewireComponent = window.Livewire?.find(
                document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
            );

            if (livewireComponent) {
                livewireComponent.set('mapPoints', this.points);
                // console.log('✅ Updated Livewire mapPoints:', this.points.length);
            } else {
                // console.warn('⚠️ Livewire component not found');
            }
        },

        getTypeLabel(type) {
            const labels = {
                'punto_parada': 'Punto de Parada',
                'restaurant': 'Restaurant',
                'lugar_turistico': 'Lugar Turístico',
                'aeropuerto': 'Aeropuerto',
                'estacion_tren': 'Estación de Tren',
                'terminal_terrestre': 'Terminal Terrestre (Bus)',
                'museo': 'Museo',
                'punto_reunion': 'Punto de Reunión',
                'otro': 'Otro',
            };
            return labels[type] || type;
        },
    };
};
