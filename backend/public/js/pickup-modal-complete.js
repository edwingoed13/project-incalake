// pickup-modal-complete.js - Sistema de configuración de pickup con Google Maps
// Función de callback para Google Maps
window.initGooglePlaces = function() {
    console.log('✅ Google Maps API cargada correctamente');
    // Inicializar el mapa si el modal ya está abierto
    if (document.getElementById('pickupModal') && !document.getElementById('pickupModal').classList.contains('hidden')) {
        window.initPickupMap();
    }
};


// Variables globales para el mapa
let map;
let marker;
let circle;
let currentPickupType = '';

window.initPickupMap = function() {
    const defaultLocation = { lat: -15.8402, lng: -70.0219 };
    const mapElement = document.getElementById("pickupMap");

    if (!mapElement) {
        return;
    }

    map = new google.maps.Map(mapElement, {
        center: defaultLocation,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
    });

    map.addListener("click", (e) => {
        window.placeMarker(e.latLng);
    });

};

window.openPickupModal = function(type) {
    console.log('📍 Abriendo modal para tipo:', type);
    const modal = document.getElementById('pickupModal');
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    currentPickupType = type || 'meeting_point';

    if (currentPickupType === 'meeting_point') {
        document.getElementById('meetingPointControls').style.display = 'block';
        document.getElementById('hotelPickupControls').style.display = 'none';
        document.getElementById('modalTitle').textContent = 'Configurar Punto de Encuentro';
    } else {
        document.getElementById('meetingPointControls').style.display = 'none';
        document.getElementById('hotelPickupControls').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Configurar Radio de Recojo';
    }

    // Siempre limpiar y reinicializar el mapa para evitar problemas de visibilidad
    setTimeout(() => {
        // Limpiar marcadores y círculos anteriores
        if (marker) {
            marker.setMap(null);
            marker = null;
        }
        if (circle) {
            circle.setMap(null);
            circle = null;
        }

        // Siempre reinicializar el mapa cuando se abre el modal
        if (map) {
            // Destruir el mapa anterior
            map = null;
        }

        // Crear nuevo mapa
        window.initPickupMap();

        // Cargar configuración después de que el mapa esté listo
        setTimeout(() => {
            window.loadExistingConfiguration();
        }, 200);
    }, 150);
};

window.closePickupModal = function() {
    const modal = document.getElementById('pickupModal');
    if (modal) modal.classList.add('hidden');
};

window.placeMarker = function(location) {
    if (marker) {
        marker.setMap(null);
    }

    marker = new google.maps.Marker({
        position: location,
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP,
    });

    marker.addListener('drag', () => {
        const pos = marker.getPosition();
        window.updateCoordinates(pos.lat(), pos.lng());
        if (currentPickupType === 'hotel_pickup') {
            window.updateRadiusCircle(pos);
        }
    });

    // Handle both plain objects {lat: x, lng: y} and Google Maps LatLng objects
    const lat = typeof location.lat === 'function' ? location.lat() : location.lat;
    const lng = typeof location.lng === 'function' ? location.lng() : location.lng;
    window.updateCoordinates(lat, lng);

    if (currentPickupType === 'hotel_pickup') {
        window.updateRadiusCircle(location);
    }
}

window.updateCoordinates = function(lat, lng) {
    if (currentPickupType === 'meeting_point') {
        const latEl = document.getElementById('meetingLat');
        const lngEl = document.getElementById('meetingLng');
        if (latEl) latEl.value = lat.toFixed(6);
        if (lngEl) lngEl.value = lng.toFixed(6);
    } else {
        const latEl = document.getElementById('centerLat');
        const lngEl = document.getElementById('centerLng');
        if (latEl) latEl.value = lat.toFixed(6);
        if (lngEl) lngEl.value = lng.toFixed(6);
    }
}

window.updateRadius = function(value) {
    const radiusKm = parseFloat(value);
    const kmEl = document.getElementById('pickupRadiusKm');
    const mEl = document.getElementById('radiusInMeters');

    if (kmEl) kmEl.value = radiusKm;
    if (mEl) mEl.textContent = (radiusKm * 1000).toFixed(0);

    if (marker) {
        window.updateRadiusCircle(marker.getPosition());
    }
};

window.updateRadiusCircle = function(center) {
    const radiusKmEl = document.getElementById('pickupRadiusKm');
    const radiusKm = radiusKmEl ? parseFloat(radiusKmEl.value) : 1;

    if (circle) {
        circle.setMap(null);
    }

    circle = new google.maps.Circle({
        map: map,
        center: center,
        radius: radiusKm * 1000,
        fillColor: '#4F46E5',
        fillOpacity: 0.15,
        strokeColor: '#4F46E5',
        strokeOpacity: 0.8,
        strokeWeight: 2,
    });

    map.fitBounds(circle.getBounds());
}

window.searchLocation = function() {
    const searchEl = document.getElementById('searchLocation');
    const address = searchEl ? searchEl.value : '';

    if (!address) {
        alert('Por favor ingresa una dirección o lugar');
        return;
    }

    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: address }, (results, status) => {
        if (status === 'OK' && results[0]) {
            const location = results[0].geometry.location;
            map.setCenter(location);
            map.setZoom(15);
            window.placeMarker(location);
        } else {
            alert('No se pudo encontrar la ubicación: ' + status);
        }
    });
};

window.loadExistingConfiguration = function() {
    const livewireEl = document.querySelector('[wire\\:id]');
    if (!livewireEl) return;

    const livewireComponent = window.Livewire.find(livewireEl.getAttribute('wire:id'));

    if (currentPickupType === 'meeting_point') {
        const lat = livewireComponent.get('meeting_point_lat');
        const lng = livewireComponent.get('meeting_point_lng');
        const desc = livewireComponent.get('meeting_point_description');

        if (lat && lng) {
            const location = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(location);
            window.placeMarker(location);
        }

        const descEl = document.getElementById('meetingPointDesc');
        if (desc && descEl) {
            descEl.value = desc;
        }
    } else {
        const lat = livewireComponent.get('pickup_center_lat');
        const lng = livewireComponent.get('pickup_center_lng');
        const radius = livewireComponent.get('pickup_radius_km');
        const desc = livewireComponent.get('pickup_location_description');

        const kmEl = document.getElementById('pickupRadiusKm');
        const mEl = document.getElementById('radiusInMeters');

        // Si hay coordenadas guardadas, cargarlas
        if (lat && lng) {
            const location = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(location);
            window.placeMarker(location);

            if (radius) {
                if (kmEl) kmEl.value = radius;
                if (mEl) mEl.textContent = (radius * 1000).toFixed(0);
            }
        } else {
            // Si no hay coordenadas, colocar marcador predeterminado en Plaza de Armas de Puno
            const defaultLocation = { lat: -15.8402, lng: -70.0219 };
            map.setCenter(defaultLocation);
            window.placeMarker(defaultLocation);

            // Establecer radio predeterminado de 1 km
            if (kmEl) kmEl.value = 1;
            if (mEl) mEl.textContent = '1000';
        }

        const descEl = document.getElementById('pickupCenterDesc');
        if (desc && descEl) {
            descEl.value = desc;
        }
    }
}

window.savePickupConfig = function() {
    if (!marker) {
        alert('Por favor marca una ubicación en el mapa');
        return;
    }

    const position = marker.getPosition();
    const livewireEl = document.querySelector('[wire\\:id]');
    if (!livewireEl) {
        return;
    }
    const livewireComponent = window.Livewire.find(livewireEl.getAttribute('wire:id'));
    if (!livewireComponent) {
        return;
    }

    if (currentPickupType === 'meeting_point') {
        const descEl = document.getElementById('meetingPointDesc');
        const desc = descEl ? descEl.value : '';

        livewireComponent.set('meeting_point_lat', position.lat());
        livewireComponent.set('meeting_point_lng', position.lng());
        livewireComponent.set('meeting_point_description', desc);

    } else {
        const kmEl = document.getElementById('pickupRadiusKm');
        const descEl = document.getElementById('pickupCenterDesc');
        const radius = kmEl ? parseFloat(kmEl.value) : 1;
        const desc = descEl ? descEl.value : '';

        livewireComponent.set('pickup_center_lat', position.lat());
        livewireComponent.set('pickup_center_lng', position.lng());
        livewireComponent.set('pickup_radius_km', radius);
        livewireComponent.set('pickup_location_description', desc);

    }

    window.closePickupModal();
    alert('✅ Configuración guardada correctamente');
};

// Auto-attach event listener cuando el DOM esté listo
function attachPickupButton() {
    const btn = document.getElementById('openPickupModalBtn');
    if (btn) {
        btn.onclick = function(e) {
            e.preventDefault();
            window.openPickupModal();
        };
    } else {
        setTimeout(attachPickupButton, 200);
    }
}

// Ejecutar cuando se cargue el script
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachPickupButton);
} else {
    attachPickupButton();
}

// Re-attach en actualizaciones de Livewire
document.addEventListener('livewire:navigated', attachPickupButton);

