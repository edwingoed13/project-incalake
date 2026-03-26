<script setup lang="ts">
interface Props {
  tour: any
}

const props = defineProps<Props>()

const mapContainer = ref<HTMLElement | null>(null)
const isExpanded = ref(false)
let map: google.maps.Map | null = null
let markers: google.maps.Marker[] = []
let routeLine: google.maps.Polyline | null = null

const cityName = computed(() => props.tour?.city?.name || props.tour?.city_name || 'Perú')

const pickupDescription = computed(() => {
  if (props.tour?.pickup_type === 'meeting_point' && props.tour?.meeting_point_description) {
    return props.tour.meeting_point_description
  } else if (props.tour?.pickup_type === 'hotel_pickup' && props.tour?.pickup_location_description) {
    return props.tour.pickup_location_description
  }
  // Default message
  return `Recogida en tu hotel en el centro de ${cityName.value}. Te enviaremos la hora exacta 24 horas antes del tour.`
})

const hasMap = computed(() => {
  return (props.tour?.meeting_point_lat && props.tour?.meeting_point_lng) ||
         (props.tour?.pickup_center_lat && props.tour?.pickup_center_lng) ||
         (props.tour?.map_points && props.tour.map_points.length > 0)
})

const mapLat = computed(() => {
  // Prioridad: meeting_point, pickup_center, primer map_point
  if (props.tour?.meeting_point_lat) return parseFloat(props.tour.meeting_point_lat)
  if (props.tour?.pickup_center_lat) return parseFloat(props.tour.pickup_center_lat)
  if (props.tour?.map_points && props.tour.map_points.length > 0) {
    return parseFloat(props.tour.map_points[0].lat)
  }
  return null
})

const mapLng = computed(() => {
  if (props.tour?.meeting_point_lng) return parseFloat(props.tour.meeting_point_lng)
  if (props.tour?.pickup_center_lng) return parseFloat(props.tour.pickup_center_lng)
  if (props.tour?.map_points && props.tour.map_points.length > 0) {
    return parseFloat(props.tour.map_points[0].lng)
  }
  return null
})

const mapPoints = computed(() => props.tour?.map_points || [])

const displayMapPoint = computed(() => {
  if (mapPoints.value.length > 0) {
    // Buscar punto de reunión primero
    const meetingPoint = mapPoints.value.find((p: any) => p.type === 'punto_reunion')
    if (meetingPoint) return meetingPoint
    // Si no hay, retornar el primero
    return mapPoints.value[0]
  }
  return null
})

function initMap() {
  if (!mapContainer.value || !hasMap.value || !mapLat.value || !mapLng.value) return

  // Check if Google Maps is loaded
  if (typeof google === 'undefined' || !google.maps) {
    console.warn('⚠️ Google Maps not loaded yet, retrying...')
    setTimeout(() => initMap(), 500)
    return
  }

  // Limpiar marcadores y líneas existentes
  markers.forEach(marker => marker.setMap(null))
  markers = []
  if (routeLine) {
    routeLine.setMap(null)
    routeLine = null
  }

  // Crear mapa
  if (!map) {
    map = new google.maps.Map(mapContainer.value, {
      center: { lat: mapLat.value, lng: mapLng.value },
      zoom: 13,
      mapTypeControl: true,
      streetViewControl: false,
      fullscreenControl: true,
    })
  }

  const bounds = new google.maps.LatLngBounds()

  // Agregar marcadores
  if (mapPoints.value.length > 0) {
    const routePath: google.maps.LatLngLiteral[] = []

    mapPoints.value.forEach((point: any, index: number) => {
      const lat = parseFloat(point.lat)
      const lng = parseFloat(point.lng)
      const position = { lat, lng }

      const marker = new google.maps.Marker({
        position: position,
        map: map!,
        label: {
          text: String(index + 1),
          color: 'white',
          fontWeight: 'bold',
          fontSize: '14px'
        },
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          fillColor: '#0077cc',
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 3,
          scale: 16
        },
        title: point.name
      })

      const infoWindow = new google.maps.InfoWindow({
        content: `
          <div style="padding: 8px;">
            <h4 style="margin: 0 0 4px 0; font-weight: bold;">${index + 1}. ${point.name}</h4>
            <p style="margin: 0; color: #666; font-size: 12px;">${point.type_label}</p>
            ${point.description ? `<p style="margin: 4px 0 0 0; font-size: 13px;">${point.description}</p>` : ''}
          </div>
        `
      })

      marker.addListener('click', () => {
        infoWindow.open(map!, marker)
      })

      markers.push(marker)
      bounds.extend(position)
      routePath.push(position)
    })

    // Dibujar línea conectando todos los puntos (si hay más de 1)
    if (routePath.length > 1) {
      routeLine = new google.maps.Polyline({
        path: routePath,
        geodesic: true,
        strokeColor: '#0077cc',
        strokeOpacity: 0.8,
        strokeWeight: 3,
        map: map!
      })
    }

    // Ajustar vista para mostrar todos los puntos
    map!.fitBounds(bounds)
  } else {
    // Solo un punto (meeting point o pickup center)
    const position = { lat: mapLat.value, lng: mapLng.value }

    const marker = new google.maps.Marker({
      position: position,
      map: map!,
      label: {
        text: '1',
        color: 'white',
        fontWeight: 'bold',
        fontSize: '14px'
      },
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#0077cc',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 3,
        scale: 16
      }
    })

    const infoWindow = new google.maps.InfoWindow({
      content: `
        <div style="padding: 8px;">
          <h4 style="margin: 0 0 4px 0; font-weight: bold;">Punto de encuentro</h4>
          <p style="margin: 4px 0 0 0; font-size: 13px;">${pickupDescription.value}</p>
        </div>
      `
    })

    marker.addListener('click', () => {
      infoWindow.open(map!, marker)
    })

    markers.push(marker)
  }

  // Agregar círculo de radio si existe pickup_radius_km
  if (props.tour?.pickup_type === 'hotel_pickup' && props.tour?.pickup_radius_km && props.tour?.pickup_center_lat && props.tour?.pickup_center_lng) {
    new google.maps.Circle({
      strokeColor: '#0077cc',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#60a5fa',
      fillOpacity: 0.2,
      map: map!,
      center: {
        lat: parseFloat(props.tour.pickup_center_lat),
        lng: parseFloat(props.tour.pickup_center_lng)
      },
      radius: props.tour.pickup_radius_km * 1000 // convertir km a metros
    })
  }
}

onMounted(() => {
  if (hasMap.value) {
    // Pequeño delay para asegurar que el DOM esté listo
    setTimeout(() => {
      initMap()
    }, 100)
  }
})

watch(() => props.tour, () => {
  if (hasMap.value) {
    setTimeout(() => {
      initMap()
    }, 100)
  }
}, { deep: true })
</script>

<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-6 md:p-8">
    <h2 class="text-2xl md:text-3xl font-black text-primary-light dark:text-primary-dark mb-6 flex items-center">
      <span class="material-symbols-outlined text-primary mr-3" style="font-size: 32px;">location_on</span>
      Ubicación
    </h2>

    <!-- Map Container (solo cuando no hay timeline) -->
    <div v-if="hasMap && mapPoints.length <= 1" ref="mapContainer" class="rounded-xl overflow-hidden h-96 mb-4 border border-slate-200 dark:border-slate-700"></div>

    <!-- Placeholder when no map data -->
    <div v-else-if="!hasMap" class="bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden h-96 flex items-center justify-center mb-4">
      <div class="text-center">
        <span class="material-symbols-outlined text-slate-400 dark:text-slate-600 mb-4 block" style="font-size: 96px;">map</span>
        <p class="text-slate-500 dark:text-slate-400 text-lg font-bold">Mapa no disponible</p>
        <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">{{ cityName }}, Perú</p>
      </div>
    </div>

    <!-- Meeting Point Info -->
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
      <div class="flex items-start space-x-3">
        <span class="material-symbols-outlined text-blue-500 mt-0.5 flex-shrink-0" style="font-size: 20px;">location_on</span>
        <div class="text-sm text-slate-700 dark:text-slate-300">
          <strong>Punto de encuentro:</strong> {{ pickupDescription }}
        </div>
      </div>
    </div>

    <!-- Timeline and Map Layout (if multiple points exist) -->
    <div v-if="mapPoints.length > 1" class="mt-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Timeline - Left Side (1/3) -->
        <div class="lg:col-span-1">
          <div class="relative bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h3 class="text-lg font-black text-primary-light dark:text-primary-dark mb-4 flex items-center">
              <span class="material-symbols-outlined text-primary mr-2" style="font-size: 20px;">route</span>
              Itinerario
            </h3>
            <!-- Timeline Items -->
            <div
              :class="[
                'overflow-hidden transition-all duration-300',
                isExpanded ? 'max-h-none' : 'max-h-96'
              ]"
            >
              <div class="space-y-0">
                <div
                  v-for="(point, index) in mapPoints"
                  :key="point.id"
                  class="relative pl-10 pb-6 last:pb-0"
                >
                  <!-- Vertical Line -->
                  <div
                    v-if="index < mapPoints.length - 1"
                    class="absolute left-4 top-8 bottom-0 w-0.5 bg-gradient-to-b from-primary to-blue-300"
                  ></div>

                  <!-- Icon/Number -->
                  <div
                    class="absolute left-0 top-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-md z-10"
                    :class="index === 0 ? 'bg-red-500 text-white' : 'bg-primary text-white'"
                  >
                    {{ index + 1 }}
                  </div>

                  <!-- Content -->
                  <div>
                    <h4 class="font-bold text-primary-light dark:text-primary-dark text-sm">{{ point.name }}</h4>
                    <p v-if="point.description" class="text-xs text-secondary-light dark:text-secondary-dark mt-1 line-clamp-2">
                      {{ point.description }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                      {{ point.type_label }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Expand/Collapse Button -->
            <div
              v-if="mapPoints.length > 4"
              class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 text-center"
            >
              <button
                @click="isExpanded = !isExpanded"
                class="text-sm font-bold text-primary hover:text-primary-dark underline"
              >
                {{ isExpanded ? 'Ver menos' : 'Ver itinerario completo' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Map - Right Side (2/3) -->
        <div class="lg:col-span-2">
          <div class="relative bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h3 class="text-lg font-black text-primary-light dark:text-primary-dark mb-4 flex items-center">
              <span class="material-symbols-outlined text-primary mr-2" style="font-size: 20px;">map</span>
              Mapa
            </h3>
            <div ref="mapContainer" class="rounded-xl overflow-hidden h-[450px] border border-slate-200 dark:border-slate-700"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dropoff Location (if exists) -->
    <div v-if="tour?.dropoff_location_description" class="mt-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
      <div class="flex items-start space-x-3">
        <span class="material-symbols-outlined text-green-500 mt-0.5 flex-shrink-0" style="font-size: 20px;">arrow_forward</span>
        <div class="text-sm text-slate-700 dark:text-slate-300">
          <strong>Punto de retorno:</strong> {{ tour.dropoff_location_description }}
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Google Maps styles */
:deep(.gm-style .gm-style-iw-c) {
  border-radius: 8px;
}

:deep(.gm-style .gm-style-iw-d) {
  overflow: auto !important;
}

/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
