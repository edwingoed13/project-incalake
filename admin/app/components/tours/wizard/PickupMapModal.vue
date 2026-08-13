<template>
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm" @click.self="$emit('close')">
      <Transition name="modal">
        <div class="bg-white dark:bg-slate-900 w-full max-w-6xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          <!-- Header -->
          <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined filled">{{ type === 'meeting_point' ? 'location_on' : 'explore_nearby' }}</span>
              </div>
              <h3 class="text-xl font-bold dark:text-white">
                {{ type === 'meeting_point' ? 'Configurar Punto de Encuentro' : 'Configurar Radio de Recojo' }}
              </h3>
            </div>
            <button @click="$emit('close')" class="size-10 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex items-center justify-center dark:text-white">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-3">
            <!-- Sidebar Controls -->
            <div class="p-8 border-r border-slate-100 dark:border-slate-800 space-y-8 overflow-y-auto custom-scrollbar">
              <!-- Search -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Buscar ubicación</label>
                <div class="relative">
                  <input 
                    id="mapSearchInput"
                    type="text" 
                    placeholder="Ej: Plaza de Armas Puno"
                    class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl py-4 px-12 text-sm focus:ring-2 focus:ring-primary dark:text-white font-medium shadow-inner"
                  />
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                </div>
              </div>

              <!-- Area mode + controls (Only for hotel_pickup) -->
              <div v-if="type === 'hotel_pickup'" class="space-y-6 animate-in slide-in-from-top-2">
                <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Forma del área</label>
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      type="button"
                      class="rounded-xl py-3 px-3 text-[11px] font-black uppercase tracking-wide transition-colors border"
                      :class="localAreaType === 'radius'
                        ? 'bg-primary text-white border-primary'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-transparent hover:border-primary/40'"
                      @click="localAreaType = 'radius'"
                    >
                      Radio
                    </button>
                    <button
                      type="button"
                      class="rounded-xl py-3 px-3 text-[11px] font-black uppercase tracking-wide transition-colors border"
                      :class="localAreaType === 'polygon'
                        ? 'bg-primary text-white border-primary'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-transparent hover:border-primary/40'"
                      @click="localAreaType = 'polygon'"
                    >
                      Zona dibujada
                    </button>
                  </div>
                </div>

                <!-- Radio -->
                <div v-if="localAreaType === 'radius'" class="space-y-4">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Radio de recojo (km)</label>
                  <div class="flex items-center gap-4">
                    <input
                      v-model.number="localRadius"
                      type="number"
                      step="0.1"
                      min="0.1"
                      max="20"
                      class="flex-1 bg-slate-100 dark:bg-slate-800 border-none rounded-xl py-3 px-4 text-sm focus:ring-2 focus:ring-primary dark:text-white font-bold"
                      @change="updateCircleRadius"
                    />
                    <span class="text-xs font-bold text-slate-500">km</span>
                  </div>
                  <p class="text-[10px] text-slate-400 font-medium">= {{ (localRadius * 1000).toFixed(0) }} metros</p>
                </div>

                <!-- Zona dibujada -->
                <div v-else class="space-y-3">
                  <div v-if="drawingUnavailable" class="rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-3">
                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                      No se pudo cargar la herramienta de dibujo
                    </p>
                    <p class="text-[10px] text-amber-800/90 dark:text-amber-200/80 mt-1 leading-relaxed">
                      Recarga la página con Ctrl+Shift+R y vuelve a abrir este mapa. Google Maps quedó
                      cargado en esta pestaña sin el módulo de dibujo.
                    </p>
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                    Pulsa el icono de polígono en la barra superior del mapa. Después haz clic en cada
                    esquina de la zona y cierra la figura haciendo clic otra vez sobre el primer punto.
                    Luego puedes arrastrar los vértices para ajustarla. Puedes dibujar más de una zona.
                  </p>
                  <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div>
                      <span class="block text-[8px] uppercase text-slate-400 mb-1">Zonas dibujadas</span>
                      <span class="text-[11px] font-black dark:text-white">
                        {{ localArea.length }} ({{ localArea.reduce((n, r) => n + (r?.length || 0), 0) }} puntos)
                      </span>
                    </div>
                    <button
                      type="button"
                      class="text-[10px] font-black uppercase tracking-wide text-red-500 hover:text-red-600 disabled:opacity-40"
                      :disabled="!localArea.length"
                      @click="clearPolygons"
                    >
                      Borrar
                    </button>
                  </div>
                  <p v-if="!localArea.length" class="text-[10px] font-bold text-amber-600 dark:text-amber-400">
                    Sin zona dibujada no se podrá recoger en ningún hotel.
                  </p>
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                  {{ type === 'meeting_point' ? 'Descripción del punto' : 'Descripción del área' }}
                </label>
                <textarea 
                  v-model="localDescription"
                  rows="3"
                  class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-2xl p-4 text-xs focus:ring-2 focus:ring-primary dark:text-white font-medium"
                  placeholder="Ej: Plaza de Armas de Puno, frente a la Catedral"
                ></textarea>
              </div>

              <!-- Coords -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Coordenadas</label>
                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="block text-[8px] uppercase text-slate-400 mb-1">Latitud</span>
                    <span class="text-[10px] font-black dark:text-white">{{ localCoords.lat.toFixed(6) }}</span>
                  </div>
                  <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="block text-[8px] uppercase text-slate-400 mb-1">Longitud</span>
                    <span class="text-[10px] font-black dark:text-white">{{ localCoords.lng.toFixed(6) }}</span>
                  </div>
                </div>
              </div>

              <!-- Instructions -->
              <div class="p-4 bg-amber-500/5 rounded-2xl border border-amber-500/10 space-y-2">
                <div class="flex items-center gap-2 text-amber-500 mb-2">
                  <span class="material-symbols-outlined text-sm">info</span>
                  <span class="text-[10px] font-black uppercase tracking-widest">Instrucciones</span>
                </div>
                <ul class="text-[10px] text-slate-500 dark:text-slate-400 space-y-1 font-medium italic">
                  <li>• Haz clic en el mapa para marcar el centro.</li>
                  <li>• Arrastra el marcador para ajustar la posición.</li>
                  <li v-if="type === 'hotel_pickup'">• El círculo muestra el área de recojo.</li>
                </ul>
              </div>
            </div>

            <!-- Map Area -->
            <div class="lg:col-span-2 relative">
              <div id="pickupMapCanvas" class="w-full h-full min-h-[400px]"></div>
              
              <!-- Zoom Control Placeholder or custom indicator -->
              <div class="absolute bottom-6 left-6 z-10">
                 <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur rounded-xl border border-slate-200 dark:border-slate-800 p-2 shadow-2xl flex items-center gap-2">
                    <img src="https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_92x30dp.png" class="h-4 opacity-50" />
                    <div class="h-3 w-px bg-slate-200 dark:bg-slate-700"></div>
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">Constructor de mapa interactivo</span>
                 </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 bg-slate-50/50 dark:bg-slate-900/50">
            <button 
              @click="$emit('close')" 
              class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
            >
              Cancelar
            </button>
            <button 
              @click="handleSave"
              class="px-10 py-3 bg-primary text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all"
            >
              Guardar Configuración
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'

const props = defineProps<{
  isOpen: boolean
  type: 'meeting_point' | 'hotel_pickup'
  initialData: {
    lat: number | null
    lng: number | null
    radius?: number
    description: string
    areaType?: 'radius' | 'polygon'
    area?: Array<Array<{ lat: number; lng: number }>> | null
  }
}>()

const emit = defineEmits(['close', 'save'])

const localCoords = ref({ lat: -15.8402, lng: -70.0219 }) // Puno default
const localRadius = ref(1)
const localDescription = ref('')

// A circle can't follow streets. 'polygon' lets the operator trace the actual
// blocks they cover; 'radius' stays the default so nothing changes for tours
// already configured.
const localAreaType = ref<'radius' | 'polygon'>('radius')
// A list of rings: a tour can cover two separate neighbourhoods.
const localArea = ref<Array<Array<{ lat: number; lng: number }>>>([])

const map = ref<any>(null)
const marker = ref<any>(null)
const circle = ref<any>(null)
const drawingManager = ref<any>(null)
const polygons = ref<any[]>([])

watch(() => props.isOpen, (val) => {
  if (val) {
    localCoords.value = {
      lat: props.initialData.lat || -15.8402,
      lng: props.initialData.lng || -70.0219
    }
    localRadius.value = props.initialData.radius || 1
    localDescription.value = props.initialData.description || ''
    localAreaType.value = props.initialData.areaType === 'polygon' ? 'polygon' : 'radius'
    localArea.value = Array.isArray(props.initialData.area)
      ? JSON.parse(JSON.stringify(props.initialData.area))
      : []

    nextTick(() => {
      loadGoogleMaps()
    })
  }
})

// Switching mode swaps which shape the map is showing and editing.
watch(localAreaType, () => {
  if (!map.value) return
  applyAreaMode()
})

const loadGoogleMaps = () => {
  if ((window as any).google && (window as any).google.maps) {
    initMap()
    return
  }
  
  if (document.getElementById('google-maps-script')) {
    let interval = setInterval(() => {
      if ((window as any).google && (window as any).google.maps) {
        clearInterval(interval)
        initMap()
      }
    }, 100)
    return
  }

  // Key comes from runtime config so it can be rotated or restricted per
  // environment; the literal stays as a fallback because it is what shipped and
  // removing it outright would black out the map wherever the env var isn't set.
  const config = useRuntimeConfig()
  const apiKey = (config.public as any).googleMapsApiKey || 'AIzaSyCC2CAVXwufsdT5TX3UPk7hZ3HHw3NZl_c'

  const script = document.createElement('script')
  script.id = 'google-maps-script'
  // `drawing` is what provides DrawingManager; without it polygon mode can't load.
  script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places,drawing,geometry`
  script.async = true
  script.defer = true
  script.onload = () => {
    initMap()
  }
  document.head.appendChild(script)
}

const initMap = async () => {
  const google = (window as any).google
  if (!google) return

  const canvas = document.getElementById('pickupMapCanvas')
  if (!canvas) return

  map.value = new google.maps.Map(canvas, {
    center: localCoords.value,
    zoom: 14,
    disableDefaultUI: false,
    zoomControl: true,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
    styles: [] // Could use a premium dark theme here if needed
  })

  // Marker
  marker.value = new google.maps.Marker({
    position: localCoords.value,
    map: map.value,
    draggable: true,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      fillColor: '#330df2',
      fillOpacity: 1,
      strokeColor: '#ffffff',
      strokeWeight: 2,
      scale: 10
    }
  })

  // Circle (if hotel pickup)
  if (props.type === 'hotel_pickup') {
    circle.value = new google.maps.Circle({
      strokeColor: '#330df2',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#330df2',
      fillOpacity: 0.15,
      map: map.value,
      center: localCoords.value,
      radius: localRadius.value * 1000 // Convert km to meters
    })

    restorePolygons()
    applyAreaMode()
  }

  // Click on map to move marker
  map.value.addListener('click', (e: any) => {
    updatePosition(e.latLng)
  })

  // Drag marker
  marker.value.addListener('dragend', (e: any) => {
    updatePosition(e.latLng)
  })

  // Autocomplete search
  const input = document.getElementById('mapSearchInput') as HTMLInputElement
  if (input) {
    const autocomplete = new google.maps.places.Autocomplete(input)
    autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace()
      if (!place.geometry || !place.geometry.location) return
      
      updatePosition(place.geometry.location)
      map.value.setCenter(place.geometry.location)
      map.value.setZoom(15)
      
      if (place.name) {
        localDescription.value = place.name + (place.formatted_address ? ' - ' + place.formatted_address : '')
      }
    })
  }
}

const updatePosition = (latLng: any) => {
  localCoords.value = {
    lat: latLng.lat(),
    lng: latLng.lng()
  }
  marker.value.setPosition(latLng)
  if (circle.value) {
    circle.value.setCenter(latLng)
  }
}

const updateCircleRadius = () => {
  if (circle.value) {
    circle.value.setRadius(localRadius.value * 1000)
    // Optional: fit bounds to circle
    map.value.fitBounds(circle.value.getBounds())
  }
}

// --- Drawn area -------------------------------------------------------------

/** Read the live shapes back into localArea. Called after every edit. */
const syncPolygons = () => {
  localArea.value = polygons.value.map((poly: any) =>
    poly.getPath().getArray().map((p: any) => ({ lat: p.lat(), lng: p.lng() }))
  )
}

/** Wire a shape so dragging a vertex updates what will be saved. */
const trackPolygon = (poly: any) => {
  const path = poly.getPath()
  // set_at fires on vertex drag, insert_at/remove_at on add/delete.
  ;['set_at', 'insert_at', 'remove_at'].forEach((ev) => path.addListener(ev, syncPolygons))
  poly.addListener('dragend', syncPolygons)
  polygons.value.push(poly)
}

const polygonStyle = {
  strokeColor: '#330df2',
  strokeOpacity: 0.9,
  strokeWeight: 2,
  fillColor: '#330df2',
  fillOpacity: 0.15,
  editable: true,
  draggable: true,
}

/** Rebuild saved rings as editable shapes when the modal reopens. */
const restorePolygons = () => {
  const google = (window as any).google
  polygons.value.forEach((p: any) => p.setMap(null))
  polygons.value = []

  localArea.value.forEach((ring) => {
    if (!Array.isArray(ring) || ring.length < 3) return
    const poly = new google.maps.Polygon({ ...polygonStyle, paths: ring, map: null })
    trackPolygon(poly)
  })
}

/**
 * google.maps.drawing only exists if the script that booted Maps asked for it,
 * and Maps honours only the first loader that wins the race. Every admin loader
 * now requests the same list, but a tab holding the old cached script would
 * still come up without it — so try to pull it in after the fact.
 */
const drawingUnavailable = ref(false)

const ensureDrawingLibrary = async (): Promise<boolean> => {
  const google = (window as any).google
  if (google?.maps?.drawing) return true

  if (typeof google?.maps?.importLibrary === 'function') {
    try {
      await google.maps.importLibrary('drawing')
      return !!google.maps.drawing
    } catch { /* fall through to the visible warning */ }
  }

  return false
}

/**
 * Show only the shape that matches the selected mode, and let the drawing tool
 * run only in polygon mode — leaving it armed in radius mode would let someone
 * draw an area the tour will never use.
 */
const applyAreaMode = async () => {
  const google = (window as any).google
  if (!google || props.type !== 'hotel_pickup') return

  const drawing = localAreaType.value === 'polygon'

  if (drawing && !(await ensureDrawingLibrary())) {
    // Better a stated reason than a map that just shows a dot.
    drawingUnavailable.value = true
    return
  }
  drawingUnavailable.value = false

  if (circle.value) circle.value.setMap(drawing ? null : map.value)
  if (marker.value) marker.value.setMap(drawing ? null : map.value)
  polygons.value.forEach((p: any) => p.setMap(drawing ? map.value : null))

  if (!drawingManager.value) {
    drawingManager.value = new google.maps.drawing.DrawingManager({
      drawingMode: null,
      drawingControl: true,
      drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: [google.maps.drawing.OverlayType.POLYGON],
      },
      polygonOptions: polygonStyle,
    })
    drawingManager.value.addListener('polygoncomplete', (poly: any) => {
      trackPolygon(poly)
      syncPolygons()
      // Back to panning: staying in draw mode makes every later click start a
      // new shape, which is never what someone wants after closing one.
      drawingManager.value.setDrawingMode(null)
    })
  }

  drawingManager.value.setMap(drawing ? map.value : null)
  if (!drawing) drawingManager.value.setDrawingMode(null)
}

const clearPolygons = () => {
  polygons.value.forEach((p: any) => p.setMap(null))
  polygons.value = []
  localArea.value = []
}

const handleSave = () => {
  emit('save', {
    lat: localCoords.value.lat,
    lng: localCoords.value.lng,
    radius: localRadius.value,
    description: localDescription.value,
    areaType: localAreaType.value,
    // Only rings that actually enclose something; the backend rejects the rest
    // anyway and an unusable shape reads as "nowhere covered".
    area: localArea.value.filter((r) => Array.isArray(r) && r.length >= 3),
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.modal-enter-active {
  animation: modal-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-leave-active {
  animation: modal-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) reverse;
}

@keyframes modal-in {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1e293b;
}

.material-symbols-outlined.filled {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
