// pickup-modal.js - Modal de configuración de pickup con Google Maps
let map;
let marker;
let circle;
let currentPickupType = '';

window.initPickupMap = function() {
    console.log('🗺️ Inicializando mapa...');
    const defaultLocation = { lat: -15.8402, lng: -70.0219 }; // Puno, Perú

    const mapElement = document.getElementById("pickupMap");
    if (!mapElement) {
        console.error('❌ Elemento pickupMap no encontrado');
        return;
    }

    map = new google.maps.Map(mapElement, {
        center: defaultLocation,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
    });

    // Listener para hacer clic en el mapa
    map.addListener("click", (e) => {
        placeMarker(e.latLng);
    });

    console.log('✅ Mapa inicializado correctamente');
};

window.openPickupModal = function() {
    console.log('📍 Abriendo modal de pickup...');
    const modal = document.getElementById('pickupModal');
    if (!modal) {
        console.error('❌ Modal no encontrado');
        return;
    }

    modal.classList.remove('hidden');

    // Obtener el tipo de recojo actual desde Livewire
    currentPickupType = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).get('pickup_type');

    // Configurar controles según el tipo
    if (currentPickupType === 'meeting_point') {
        document.getElementById('meetingPointControls').style.display = 'block';
        document.getElementById('hotelPickupControls').style.display = 'none';
        document.getElementById('modalTitle').textContent = 'Configurar Punto de Encuentro';
        document.getElementById('modalInstructions').innerHTML =
            '1. Usa el buscador o haz clic en el mapa para marcar el punto de encuentro<br>' +
            '2. Arrastra el marcador para ajustar la posición<br>' +
            '3. Guarda la configuración';
    } else {
        document.getElementById('meetingPointControls').style.display = 'none';
        document.getElementById('hotelPickupControls').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Configurar Radio de Recojo';
        document.getElementById('modalInstructions').innerHTML =
            '1. Usa el buscador o haz clic en el mapa para marcar el centro del área de recojo<br>' +
            '2. Ajusta el radio en kilómetros<br>' +
            '3. Arrastra el marcador para ajustar la posición<br>' +
            '4. Guarda la configuración';
    }

    // Inicializar el mapa después de mostrar el modal
    setTimeout(() => {
        if (!map) {
            window.initPickupMap();
        } else {
            google.maps.event.trigger(map, 'resize');
        }
        loadExistingConfiguration();
    }, 100);
};

window.closePickupModal = function() {
    const modal = document.getElementById('pickupModal');
    modal.classList.add('hidden');
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
        updateCoordinates(pos.lat(), pos.lng());
        if (currentPickupType === 'hotel_pickup') {
            updateRadiusCircle(pos);
        }
    });

    updateCoordinates(location.lat(), location.lng());

    if (currentPickupType === 'hotel_pickup') {
        updateRadiusCircle(location);
    }
}

window.updateCoordinates = function(lat, lng) {
    if (currentPickupType === 'meeting_point') {
        document.getElementById('meetingLat').value = lat.toFixed(6);
        document.getElementById('meetingLng').value = lng.toFixed(6);
    } else {
        document.getElementById('centerLat').value = lat.toFixed(6);
        document.getElementById('centerLng').value = lng.toFixed(6);
    }
}

window.updateRadius = function(value) {
    const radiusKm = parseFloat(value);
    document.getElementById('pickupRadiusKm').value = radiusKm;
    document.getElementById('radiusInMeters').value = (radiusKm * 1000).toFixed(0);

    if (marker) {
        updateRadiusCircle(marker.getPosition());
    }
};

window.updateRadiusCircle = function(center) {
    const radiusKm = parseFloat(document.getElementById('pickupRadiusKm').value) || 1;

    if (circle) {
        circle.setMap(null);
    }

    circle = new google.maps.Circle({
        map: map,
        center: center,
        radius: radiusKm * 1000, // Convertir km a metros
        fillColor: '#4F46E5',
        fillOpacity: 0.15,
        strokeColor: '#4F46E5',
        strokeOpacity: 0.8,
        strokeWeight: 2,
    });

    map.fitBounds(circle.getBounds());
}

window.searchLocation = function() {
    const address = document.getElementById('searchLocation').value;
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
            placeMarker(location);
        } else {
            alert('No se pudo encontrar la ubicación: ' + status);
        }
    });
};

window.loadExistingConfiguration = function() {
    const livewireComponent = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));

    if (currentPickupType === 'meeting_point') {
        const lat = livewireComponent.get('meeting_point_lat');
        const lng = livewireComponent.get('meeting_point_lng');
        const desc = livewireComponent.get('meeting_point_description');

        if (lat && lng) {
            const location = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(location);
            placeMarker(location);
        }

        if (desc) {
            document.getElementById('meetingPointDesc').value = desc;
        }
    } else {
        const lat = livewireComponent.get('pickup_center_lat');
        const lng = livewireComponent.get('pickup_center_lng');
        const radius = livewireComponent.get('pickup_radius_km');
        const desc = livewireComponent.get('pickup_location_description');

        if (lat && lng) {
            const location = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(location);
            placeMarker(location);
        }

        if (radius) {
            document.getElementById('pickupRadiusKm').value = radius;
            document.getElementById('radiusInMeters').value = (radius * 1000).toFixed(0);
        }

        if (desc) {
            document.getElementById('pickupCenterDesc').value = desc;
        }
    }
}

window.savePickupConfig = function() {
    if (!marker) {
        alert('Por favor marca una ubicación en el mapa');
        return;
    }

    const position = marker.getPosition();
    const livewireComponent = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));

    if (currentPickupType === 'meeting_point') {
        livewireComponent.set('meeting_point_lat', position.lat());
        livewireComponent.set('meeting_point_lng', position.lng());
        livewireComponent.set('meeting_point_description', document.getElementById('meetingPointDesc').value);
    } else {
        livewireComponent.set('pickup_center_lat', position.lat());
        livewireComponent.set('pickup_center_lng', position.lng());
        livewireComponent.set('pickup_radius_km', parseFloat(document.getElementById('pickupRadiusKm').value));
        livewireComponent.set('pickup_location_description', document.getElementById('pickupCenterDesc').value);
    }

    window.closePickupModal();
    alert('✅ Configuración guardada correctamente');
};

// Inicializar cuando Livewire esté listo
document.addEventListener('livewire:load', function () {
    console.log('✅ Pickup Modal JS cargado - Livewire listo');
});

// Reinicializar después de actualizaciones de Livewire
document.addEventListener('livewire:update', function () {
    console.log('🔄 Livewire actualizado - Modal listo');
});

console.log('✅ pickup-modal.js cargado correctamente');

// Función para attach event listener al botón
function attachPickupButtonHandler() {
    const btn = document.getElementById('openPickupModalBtn');
    if (btn) {
        // Quitar listeners anteriores si existen
        btn.replaceWith(btn.cloneNode(true));
        const newBtn = document.getElementById('openPickupModalBtn');
        
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.openPickupModal();
        });
        
        console.log('✅ Event listener agregado al botón de pickup modal');
    } else {
        console.log('⚠️ Botón #openPickupModalBtn no encontrado aún');
    }
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachPickupButtonHandler);
} else {
    attachPickupButtonHandler();
}

// Re-attach después de actualizaciones de Livewire
document.addEventListener('livewire:update', function() {
    setTimeout(attachPickupButtonHandler, 100);
});

// Re-attach después de navegación de Livewire  
document.addEventListener('livewire:navigated', function() {
    setTimeout(attachPickupButtonHandler, 100);
});
