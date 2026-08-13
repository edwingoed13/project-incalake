<template>
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm" @click.self="$emit('close')">
      <Transition name="modal">
        <div class="bg-default w-full max-w-6xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          <!-- Header -->
          <div class="p-6 border-b border-default flex items-center justify-between bg-elevated/40">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <UIcon :name="type === 'meeting_point' ? 'i-lucide-map-pin' : 'i-lucide-target'" class="size-6" />
              </div>
              <h3 class="text-xl font-bold text-highlighted">
                {{ type === 'meeting_point' ? 'Configurar Punto de Encuentro' : 'Configurar Radio de Recojo' }}
              </h3>
            </div>
            <button @click="$emit('close')" class="size-10 rounded-xl hover:bg-elevated transition-colors flex items-center justify-center dark:text-white">
              <UIcon name="i-lucide-x" class="size-5" />
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-3">
            <!-- Sidebar Controls -->
            <div class="p-8 border-r border-default space-y-8 overflow-y-auto custom-scrollbar">
              <!-- Search -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Buscar ubicación</label>
                <div class="relative">
                  <input 
                    id="mapSearchInput"
                    type="text" 
                    placeholder="Ej: Plaza de Armas Puno"
                    class="w-full bg-elevated border-none rounded-xl py-4 px-12 text-sm focus:ring-2 focus:ring-primary dark:text-white font-medium shadow-inner"
                  />
                  <UIcon name="i-lucide-search" class="absolute left-4 top-1/2 -translate-y-1/2 text-muted size-5" />
                </div>
              </div>

              <!-- Area mode + controls (Only for hotel_pickup) -->
              <div v-if="type === 'hotel_pickup'" class="space-y-6 animate-in slide-in-from-top-2">
                <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-widest text-muted">Forma del área</label>
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      type="button"
                      class="rounded-xl py-3 px-3 text-[11px] font-black uppercase tracking-wide transition-colors border"
                      :class="localAreaType === 'radius'
                        ? 'bg-primary text-white border-primary'
                        : 'bg-elevated text-muted border-transparent hover:border-primary/40'"
                      @click="localAreaType = 'radius'"
                    >
                      Radio
                    </button>
                    <button
                      type="button"
                      class="rounded-xl py-3 px-3 text-[11px] font-black uppercase tracking-wide transition-colors border"
                      :class="localAreaType === 'polygon'
                        ? 'bg-primary text-white border-primary'
                        : 'bg-elevated text-muted border-transparent hover:border-primary/40'"
                      @click="localAreaType = 'polygon'"
                    >
                      Zona dibujada
                    </button>
                  </div>
                </div>

                <!-- Radio -->
                <div v-if="localAreaType === 'radius'" class="space-y-4">
                  <label class="text-[10px] font-black uppercase tracking-widest text-muted">Radio de recojo (km)</label>
                  <div class="flex items-center gap-4">
                    <input
                      v-model.number="localRadius"
                      type="number"
                      step="0.1"
                      min="0.1"
                      max="20"
                      class="flex-1 bg-elevated border-none rounded-xl py-3 px-4 text-sm focus:ring-2 focus:ring-primary dark:text-white font-bold"
                      @change="updateCircleRadius"
                    />
                    <span class="text-xs font-bold text-muted">km</span>
                  </div>
                  <p class="text-[10px] text-muted font-medium">= {{ (localRadius * 1000).toFixed(0) }} metros</p>
                </div>

                <!-- Zona dibujada -->
                <div v-else class="space-y-3">
                  <!-- Not drawing yet -->
                  <template v-if="!isDrawing">
                    <button
                      type="button"
                      class="w-full rounded-xl bg-primary text-white py-3 px-4 text-[11px] font-black uppercase tracking-wide hover:opacity-90 transition-opacity"
                      @click="startZone"
                    >
                      + Dibujar una zona
                    </button>
                    <p class="text-[11px] text-muted font-medium leading-relaxed">
                      Pulsa el botón y luego haz clic en el mapa en cada esquina de la zona.
                    </p>
                  </template>

                  <!-- Drawing in progress -->
                  <template v-else>
                    <div class="rounded-xl border-2 border-primary bg-primary/5 p-3 space-y-2">
                      <p class="text-[11px] font-black uppercase tracking-wide text-primary">
                        Dibujando · {{ draftPoints.length }} punto(s)
                      </p>
                      <p class="text-[10px] text-default leading-relaxed">
                        Haz clic en el mapa para añadir cada esquina. Cuando tengas al menos 3,
                        cierra la zona con el botón o haciendo clic en el punto verde.
                      </p>
                      <div class="grid grid-cols-3 gap-1.5 pt-1">
                        <button
                          type="button"
                          class="rounded-lg bg-emerald-600 text-white py-2 text-[10px] font-black uppercase disabled:opacity-40"
                          :disabled="draftPoints.length < 3"
                          @click="finishZone"
                        >
                          Cerrar
                        </button>
                        <button
                          type="button"
                          class="rounded-lg bg-slate-200 dark:bg-slate-700 dark:text-white py-2 text-[10px] font-black uppercase disabled:opacity-40"
                          :disabled="!draftPoints.length"
                          @click="undoPoint"
                        >
                          Deshacer
                        </button>
                        <button
                          type="button"
                          class="rounded-lg bg-slate-200 dark:bg-slate-700 dark:text-white py-2 text-[10px] font-black uppercase"
                          @click="cancelZone"
                        >
                          Cancelar
                        </button>
                      </div>
                    </div>
                  </template>

                  <!-- Saved zones -->
                  <div v-if="localArea.length" class="space-y-1.5">
                    <span class="block text-[10px] uppercase text-muted">Zonas guardadas</span>
                    <div
                      v-for="(ring, i) in localArea"
                      :key="i"
                      class="bg-elevated py-2 px-3 rounded-xl border border-default flex items-center justify-between gap-3"
                    >
                      <span class="text-[11px] font-black dark:text-white">
                        Zona {{ i + 1 }} · {{ ring?.length || 0 }} puntos
                      </span>
                      <button
                        type="button"
                        class="text-[10px] font-black uppercase tracking-wide text-red-500 hover:text-red-600"
                        @click="removeZone(i)"
                      >
                        Quitar
                      </button>
                    </div>
                    <p class="text-[10px] text-muted font-medium">
                      Arrastra los vértices en el mapa para ajustar una zona guardada.
                    </p>
                  </div>

                  <p v-if="!localArea.length && !isDrawing" class="text-[10px] font-bold text-amber-600 dark:text-amber-400">
                    Sin zona dibujada no se podrá recoger en ningún hotel.
                  </p>
                </div>
              </div>

              <!-- Reference photo (meeting point only) -->
              <div v-if="type === 'meeting_point'" class="space-y-3">
                <label class="text-[10px] font-black uppercase tracking-widest text-muted">
                  Imagen de referencia
                </label>
                <p class="text-[10px] text-muted font-medium leading-relaxed">
                  La verá el pasajero en su reserva para reconocer el lugar el día del tour.
                </p>

                <div v-if="localImage" class="relative rounded-2xl overflow-hidden border border-default">
                  <img :src="imagePreviewUrl" alt="Referencia del punto de encuentro" class="w-full h-32 object-cover" />
                  <button
                    type="button"
                    class="absolute top-2 right-2 rounded-lg bg-slate-900/80 text-white px-2.5 py-1 text-[10px] font-black uppercase hover:bg-red-600 transition-colors"
                    @click="removeImage"
                  >
                    Quitar
                  </button>
                </div>

                <label
                  v-else
                  class="flex flex-col items-center justify-center gap-1 h-32 rounded-2xl border-2 border-dashed border-accented cursor-pointer hover:border-primary transition-colors"
                  :class="uploadingImage ? 'opacity-60 pointer-events-none' : ''"
                >
                  <UIcon :name="uploadingImage ? 'i-lucide-hourglass' : 'i-lucide-image-plus'" class="size-6 text-muted" />
                  <span class="text-[10px] font-black uppercase tracking-wide text-muted">
                    {{ uploadingImage ? 'Subiendo...' : 'Subir imagen' }}
                  </span>
                  <input type="file" accept="image/*" class="hidden" @change="onImageSelected" />
                </label>

                <p v-if="imageError" class="text-[10px] font-bold text-red-500">{{ imageError }}</p>
              </div>

              <!-- Description -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-muted">
                  {{ type === 'meeting_point' ? 'Descripción del punto' : 'Descripción del área' }}
                </label>
                <textarea 
                  v-model="localDescription"
                  rows="3"
                  class="w-full bg-elevated border-none rounded-2xl p-4 text-xs focus:ring-2 focus:ring-primary dark:text-white font-medium"
                  placeholder="Ej: Plaza de Armas de Puno, frente a la Catedral"
                ></textarea>
              </div>

              <!-- Coords -->
              <div class="space-y-4">
                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Coordenadas</label>
                <div class="grid grid-cols-2 gap-2">
                  <div class="bg-elevated p-3 rounded-xl border border-default">
                    <span class="block text-[10px] uppercase text-muted mb-1">Latitud</span>
                    <span class="text-[10px] font-black dark:text-white">{{ localCoords.lat.toFixed(6) }}</span>
                  </div>
                  <div class="bg-elevated p-3 rounded-xl border border-default">
                    <span class="block text-[10px] uppercase text-muted mb-1">Longitud</span>
                    <span class="text-[10px] font-black dark:text-white">{{ localCoords.lng.toFixed(6) }}</span>
                  </div>
                </div>
              </div>

              <!-- Instructions -->
              <div class="p-4 bg-amber-500/5 rounded-2xl border border-amber-500/10 space-y-2">
                <div class="flex items-center gap-2 text-amber-500 mb-2">
                  <UIcon name="i-lucide-info" class="size-4" />
                  <span class="text-[10px] font-black uppercase tracking-widest">Instrucciones</span>
                </div>
                <ul class="text-[10px] text-muted space-y-1 font-medium italic">
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
                 <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur rounded-xl border border-default p-2 shadow-2xl flex items-center gap-2">
                    <img src="https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_92x30dp.png" class="h-4 opacity-50" />
                    <div class="h-3 w-px bg-slate-200 dark:bg-slate-700"></div>
                    <span class="text-[10px] font-bold text-muted uppercase tracking-tighter">Constructor de mapa interactivo</span>
                 </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-6 border-t border-default flex items-center justify-end gap-3 bg-elevated/40">
            <button 
              @click="$emit('close')" 
              class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-muted hover:bg-elevated transition-all"
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
import { ref, computed, watch, onMounted, nextTick } from 'vue'

const props = defineProps<{
  isOpen: boolean
  type: 'meeting_point' | 'hotel_pickup'
  initialData: {
    lat: number | null
    lng: number | null
    radius?: number
    description: string
    image?: string | null
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
// Reference photo for a meeting point. Stored as the storage-relative path so
// it survives a domain change; the preview builds an absolute URL for display.
const localImage = ref<string | null>(null)
const uploadingImage = ref(false)
const imageError = ref<string | null>(null)

const imagePreviewUrl = computed(() => {
  const p = localImage.value
  if (!p) return ''
  if (p.startsWith('http')) return p
  const base = ((useRuntimeConfig().public as any).apiUrl || '').replace(/\/api\/?$/, '')
  return `${base}/storage/${p.replace(/^\/+/, '')}`
})

const onImageSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  imageError.value = null
  if (!file.type.startsWith('image/')) {
    imageError.value = 'El archivo debe ser una imagen.'
    return
  }
  // Matches the limit the upload endpoint enforces.
  if (file.size > 5 * 1024 * 1024) {
    imageError.value = 'La imagen no puede superar 5 MB.'
    return
  }

  uploadingImage.value = true
  try {
    const config = useRuntimeConfig()
    const auth = useAuthStore()
    const formData = new FormData()
    formData.append('image', file)

    const res: any = await $fetch(`${(config.public as any).apiUrl}/admin/tours/upload-image`, {
      method: 'POST',
      body: formData,
      headers: { Authorization: `Bearer ${auth.token}` },
    })

    if (res?.success && res.path) {
      localImage.value = res.path
    } else {
      imageError.value = res?.message || 'No se pudo subir la imagen.'
    }
  } catch (e: any) {
    imageError.value = e?.data?.message || e?.message || 'Error al subir la imagen.'
  } finally {
    uploadingImage.value = false
    input.value = ''
  }
}

const removeImage = () => {
  localImage.value = null
  imageError.value = null
}

const localAreaType = ref<'radius' | 'polygon'>('radius')
// A list of rings: a tour can cover two separate neighbourhoods.
const localArea = ref<Array<Array<{ lat: number; lng: number }>>>([])

const map = ref<any>(null)
const marker = ref<any>(null)
const circle = ref<any>(null)
const polygons = ref<any[]>([])

watch(() => props.isOpen, (val) => {
  if (val) {
    localCoords.value = {
      lat: props.initialData.lat || -15.8402,
      lng: props.initialData.lng || -70.0219
    }
    localRadius.value = props.initialData.radius || 1
    localDescription.value = props.initialData.description || ''
    localImage.value = props.initialData.image || null
    imageError.value = null
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

  // Click on map: adds a vertex while drawing a zone, otherwise moves the
  // centre marker as before.
  map.value.addListener('click', (e: any) => {
    if (isDrawing.value) {
      draftPoints.value.push({ lat: e.latLng.lat(), lng: e.latLng.lng() })
      renderDraft()
      return
    }
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

// --- Drawing, without Google's DrawingManager -------------------------------
//
// DrawingManager lives in the optional `drawing` library, and Maps honours only
// the library list of whichever loader boots it first. With three loaders in
// this app that race was unwinnable: the toolbar silently never appeared and
// the map came up showing nothing but a dot. Clicking to place vertices needs
// only Polygon/Polyline/Marker, all core, so it cannot break that way — and a
// labelled button in the panel is easier to find than an icon on the map.

const isDrawing = ref(false)
const draftPoints = ref<Array<{ lat: number; lng: number }>>([])
let draftLine: any = null
let draftMarkers: any[] = []

const clearDraftOverlays = () => {
  if (draftLine) { draftLine.setMap(null); draftLine = null }
  draftMarkers.forEach((m) => m.setMap(null))
  draftMarkers = []
}

/** Redraw the in-progress outline after every click. */
const renderDraft = () => {
  const google = (window as any).google
  clearDraftOverlays()
  if (!draftPoints.value.length) return

  draftLine = new google.maps.Polyline({
    map: map.value,
    path: draftPoints.value,
    strokeColor: '#330df2',
    strokeOpacity: 0.9,
    strokeWeight: 2,
  })

  draftPoints.value.forEach((p, i) => {
    draftMarkers.push(new google.maps.Marker({
      map: map.value,
      position: p,
      // The first vertex is highlighted: clicking it closes the shape.
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: i === 0 ? 7 : 5,
        fillColor: i === 0 ? '#16a34a' : '#330df2',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
      },
      clickable: i === 0,
      title: i === 0 ? 'Clic aquí para cerrar la zona' : undefined,
      zIndex: 10,
    }))
  })

  if (draftMarkers[0]) {
    draftMarkers[0].addListener('click', () => finishZone())
  }
}

const startZone = () => {
  localAreaType.value = 'polygon'
  isDrawing.value = true
  draftPoints.value = []
  renderDraft()
}

const cancelZone = () => {
  isDrawing.value = false
  draftPoints.value = []
  clearDraftOverlays()
}

const undoPoint = () => {
  draftPoints.value.pop()
  renderDraft()
}

const finishZone = () => {
  if (draftPoints.value.length < 3) return
  const google = (window as any).google
  const poly = new google.maps.Polygon({
    ...polygonStyle,
    paths: [...draftPoints.value],
    map: map.value,
  })
  trackPolygon(poly)
  syncPolygons()
  cancelZone()
}

const removeZone = (index: number) => {
  const poly = polygons.value[index]
  if (poly) poly.setMap(null)
  polygons.value.splice(index, 1)
  syncPolygons()
}

/** Show only the shape that matches the selected mode. */
const applyAreaMode = () => {
  if (props.type !== 'hotel_pickup') return
  const drawing = localAreaType.value === 'polygon'

  if (circle.value) circle.value.setMap(drawing ? null : map.value)
  if (marker.value) marker.value.setMap(drawing ? null : map.value)
  polygons.value.forEach((p: any) => p.setMap(drawing ? map.value : null))

  if (!drawing) cancelZone()
}

const clearPolygons = () => {
  polygons.value.forEach((p: any) => p.setMap(null))
  polygons.value = []
  localArea.value = []
  cancelZone()
}

const handleSave = () => {
  emit('save', {
    lat: localCoords.value.lat,
    lng: localCoords.value.lng,
    radius: localRadius.value,
    description: localDescription.value,
    image: localImage.value,
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

</style>
