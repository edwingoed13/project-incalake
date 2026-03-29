<template>
  <div class="flex flex-col gap-8 pb-20">
    <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
      <div class="px-8 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg font-bold">translate</span>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Editando contenido detallado en</p>
            <div class="flex items-center gap-2 mt-1">
              <button
                v-for="lang in tourLanguages"
                :key="lang"
                @click="store.currentLanguage = lang"
                class="px-2 py-0.5 rounded text-[10px] font-black uppercase transition-all"
                :class="store.currentLanguage === lang ? 'bg-primary text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500 hover:bg-slate-300 dark:hover:bg-slate-700'"
              >
                {{ lang }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Content per Language -->
      <div v-if="currentLangData" class="p-8 space-y-12">
        
        <!-- Section: Long Description -->
        <section class="space-y-4">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">article</span>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Detailed Tour Description</h3>
          </div>
          <TiptapEditor 
            v-model="currentLangData.detailedDescription" 
            placeholder="Write a long, engaging description of the experience..."
          />
        </section>

        <!-- Section: Itinerary Text (Tiptap) -->
        <section class="space-y-4 pt-10 border-t border-slate-100 dark:border-slate-800/50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">route</span>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Tour Itinerary (Rich Text)</h3>
          </div>
          <TiptapEditor 
            v-model="currentLangData.itineraryText" 
            placeholder="Outline the detailed itinerary using lists, headings, and bold text..."
          />
        </section>

        <!-- Section: Daily Schedule & Map -->
        <section class="space-y-6 pt-10 border-t border-slate-100 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">calendar_today</span>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Build your daily schedule</h3>
            </div>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left: Map Preview -->
            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Tour Map Points</h4>
              </div>

              <!-- Map Preview -->
              <div class="relative rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-slate-100 dark:bg-slate-900 h-[500px] shadow-inner">
                <div id="tourMapCanvas" class="w-full h-full"></div>
              </div>
            </div>

            <!-- Right: Add Point Form and Points List -->
            <div class="space-y-6">

              <!-- Add New Point Section -->
              <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center gap-2 mb-2">
                  <span class="material-symbols-outlined text-primary text-sm">add_location</span>
                  <h5 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Add New Point</h5>
                </div>

                <div class="space-y-2">
                  <input
                    ref="mapSearchInput"
                    v-model="newPoint.name"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                    placeholder="Search location (Google Places)..."
                    @input="onMapSearchInput"
                  />

                  <textarea
                    v-model="newPoint.description"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary resize-none"
                    placeholder="Description (optional)"
                    rows="2"
                  ></textarea>

                  <div class="flex gap-2">
                    <select
                      v-model="newPoint.type"
                      class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                    >
                      <option value="">Select type...</option>
                      <option value="punto_reunion">Meeting Point</option>
                      <option value="punto_parada">Stop Point</option>
                      <option value="lugar_turistico">Tourist Attraction</option>
                      <option value="restaurant">Restaurant</option>
                      <option value="hotel">Hotel</option>
                      <option value="aeropuerto">Airport</option>
                      <option value="estacion_tren">Train Station</option>
                      <option value="puerto">Port/Harbor</option>
                      <option value="otro">Other</option>
                    </select>

                    <input
                      v-model="newPoint.coordinates"
                      class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                      placeholder="Lat,Lng (auto-filled)"
                      readonly
                    />

                    <button
                      @click="addMapPoint"
                      :disabled="!newPoint.name || !newPoint.type || !newPoint.coordinates"
                      class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-1"
                    >
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </button>
                  </div>
                </div>
              </div>

              <!-- Points List -->
              <div class="space-y-2">
                <div class="flex items-center gap-2 mb-2">
                  <span class="material-symbols-outlined text-primary text-sm">route</span>
                  <h5 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Route Points ({{ currentLangData.mapPoints?.length || 0 }})</h5>
                </div>

                <div v-if="currentLangData.mapPoints && currentLangData.mapPoints.length > 0" class="space-y-2">
                  <div
                    v-for="(point, index) in currentLangData.mapPoints"
                    :key="point.id || index"
                    class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-3 hover:shadow-md transition-shadow"
                  >
                    <div v-if="editingPointIndex !== index" class="flex items-start justify-between">
                      <div class="flex items-start gap-3 flex-1">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white text-xs font-bold flex-shrink-0">
                          {{ index + 1 }}
                        </div>
                        <div class="flex-1">
                          <h6 class="font-bold text-sm text-slate-900 dark:text-white">{{ point.name }}</h6>
                          <p v-if="point.description" class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ point.description }}</p>
                          <div class="flex items-center gap-3 mt-2 text-[10px] text-slate-500">
                            <span class="flex items-center gap-1">
                              <span class="material-symbols-outlined text-xs">category</span>
                              {{ getPointTypeLabel(point.type) }}
                            </span>
                            <span class="flex items-center gap-1">
                              <span class="material-symbols-outlined text-xs">location_on</span>
                              {{ point.coordinates }}
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="flex items-center gap-1 ml-2">
                        <button
                          @click="movePointUp(index)"
                          :disabled="index === 0"
                          class="p-1 text-slate-400 hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed"
                          title="Move up"
                        >
                          <span class="material-symbols-outlined text-sm">arrow_upward</span>
                        </button>
                        <button
                          @click="movePointDown(index)"
                          :disabled="index === currentLangData.mapPoints.length - 1"
                          class="p-1 text-slate-400 hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed"
                          title="Move down"
                        >
                          <span class="material-symbols-outlined text-sm">arrow_downward</span>
                        </button>
                        <button
                          @click="editPoint(index)"
                          class="p-1 text-slate-400 hover:text-blue-500"
                          title="Edit"
                        >
                          <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        <button
                          @click="removePoint(index)"
                          class="p-1 text-slate-400 hover:text-red-500"
                          title="Delete"
                        >
                          <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                      </div>
                    </div>

                    <!-- Edit Mode -->
                    <div v-else class="space-y-2">
                      <input
                        v-model="editingPoint.name"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm"
                        placeholder="Name"
                      />
                      <textarea
                        v-model="editingPoint.description"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm resize-none"
                        placeholder="Description"
                        rows="2"
                      ></textarea>
                      <div class="flex gap-2">
                        <select
                          v-model="editingPoint.type"
                          class="flex-1 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm"
                        >
                          <option value="punto_reunion">Meeting Point</option>
                          <option value="punto_parada">Stop Point</option>
                          <option value="lugar_turistico">Tourist Attraction</option>
                          <option value="restaurant">Restaurant</option>
                          <option value="hotel">Hotel</option>
                          <option value="aeropuerto">Airport</option>
                          <option value="estacion_tren">Train Station</option>
                          <option value="puerto">Port/Harbor</option>
                          <option value="otro">Other</option>
                        </select>
                        <input
                          v-model="editingPoint.coordinates"
                          class="flex-1 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm"
                          placeholder="Lat,Lng"
                        />
                      </div>
                      <div class="flex justify-end gap-2">
                        <button
                          @click="cancelEdit"
                          class="px-3 py-1 text-xs font-bold text-slate-600 hover:text-slate-800"
                        >
                          Cancel
                        </button>
                        <button
                          @click="saveEdit"
                          class="px-3 py-1 bg-primary text-white text-xs font-bold rounded hover:bg-primary-600"
                        >
                          Save
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="py-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center text-slate-400">
                  <span class="material-symbols-outlined text-3xl mb-2 opacity-30">location_off</span>
                  <p class="text-xs font-medium">No map points added yet</p>
                  <p class="text-[10px] opacity-60 mt-1">Add points to create the tour route</p>
                </div>
              </div>

            </div>
          </div>
        </section>

        <!-- Section: Inclusions & Exclusions -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-10 border-t border-slate-100 dark:border-slate-800/50">
          <div class="space-y-4">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-emerald-500">check_circle</span>
              <h4 class="text-xl font-bold text-slate-900 dark:text-white">What's Included</h4>
            </div>
            <TiptapEditor v-model="currentLangData.inclusions" placeholder="What is included in the price? Use bullet points." />
          </div>
          <div class="space-y-4">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-rose-500">cancel</span>
              <h4 class="text-xl font-bold text-slate-900 dark:text-white">What's Excluded</h4>
            </div>
            <TiptapEditor v-model="currentLangData.exclusions" placeholder="What is NOT included? Be clear to avoid complaints." />
          </div>
        </section>

        <!-- Section: Recommendations & What to Bring -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-10 border-t border-slate-100 dark:border-slate-800/50">
          <div class="space-y-4">
            <div class="flex items-center gap-2 text-primary">
              <span class="material-symbols-outlined">lightbulb</span>
              <h4 class="text-xl font-bold text-slate-900 dark:text-white">Recommendations</h4>
            </div>
            <TiptapEditor v-model="currentLangData.recommendations" placeholder="Tips for traveleres, best time to visit, etc." />
          </div>
          <div class="space-y-4">
            <div class="flex items-center gap-2 text-primary">
              <span class="material-symbols-outlined">backpack</span>
              <h4 class="text-xl font-bold text-slate-900 dark:text-white">What to Bring?</h4>
            </div>
            <TiptapEditor v-model="currentLangData.thingsToBring" placeholder="Clothes, equipment, documents required..." />
          </div>
        </section>

        <!-- Section: Policies -->
        <section class="space-y-8 pt-10 border-t border-slate-100 dark:border-slate-800/50 text-slate-900 dark:text-white">
          <div class="space-y-4">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">gavel</span>
              <h4 class="text-xl font-bold">General Policies</h4>
            </div>
            <TiptapEditor v-model="currentLangData.generalPolicies" placeholder="General rules, age restrictions, health requirements..." />
          </div>
          <div class="space-y-4">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-rose-500">priority_high</span>
              <h4 class="text-xl font-bold">Cancellation Policy *</h4>
            </div>
            <TiptapEditor v-model="currentLangData.cancellationPolicy" placeholder="Define clearly when a refund is possible or not." />
          </div>
        </section>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import TiptapEditor from '~/components/common/TiptapEditor.vue'
import { useGooglePlaces } from '~/composables/useGooglePlaces'

const store = useTourWizardStore()
const { initCityAutocomplete } = useGooglePlaces()

const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

const currentLangData = computed(() => {
  return store.detailedContent[store.currentLanguage]
})

// Map State
const map = ref<any>(null)
const markers = ref<any[]>([])
const routeLine = ref<any>(null)
const mapSearchInput = ref<HTMLInputElement | null>(null)

// New point form
const newPoint = ref({
  name: '',
  description: '',
  coordinates: '',
  type: ''
})

// Edit mode
const editingPointIndex = ref<number | null>(null)
const editingPoint = ref<any>({
  name: '',
  description: '',
  coordinates: '',
  type: ''
})

const addDay = () => {
  if (!currentLangData.value) return
  currentLangData.value.itinerary.push({
    id: crypto.randomUUID(),
    dayNumber: currentLangData.value.itinerary.length + 1,
    title: '',
    location: '',
    description: '',
    image: ''
  })
}

const removeDay = (index: number) => {
  if (!currentLangData.value) return
  currentLangData.value.itinerary.splice(index, 1)
  currentLangData.value.itinerary.forEach((d, i) => d.dayNumber = i + 1)
}

// Map Logic Integration
const initMap = async () => {
  if (typeof (window as any).google === 'undefined') {
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyCC2CAVXwufsdT5TX3UPk7hZ3HHw3NZl_c&libraries=places`
    script.onload = () => setupMap()
    document.head.appendChild(script)
  } else {
    setupMap()
  }
}

const setupMap = () => {
  const canvas = document.getElementById('tourMapCanvas')
  if (!canvas) return

  const google = (window as any).google
  map.value = new google.maps.Map(canvas, {
    center: { lat: -15.8422, lng: -70.0199 },
    zoom: 8,
    styles: [],
    disableDefaultUI: true,
    zoomControl: true,
  })

  // Setup autocomplete for the search input
  nextTick(() => {
    if (mapSearchInput.value) {
      initCityAutocomplete(mapSearchInput.value, (placeData: any) => {
        newPoint.value.name = placeData.cityName || placeData.formatted_address || ''
        newPoint.value.description = placeData.formatted_address || ''
        newPoint.value.coordinates = `${placeData.lat},${placeData.lng}`

        // Center map on selected location
        if (map.value && placeData.lat && placeData.lng) {
          map.value.setCenter({ lat: placeData.lat, lng: placeData.lng })
          map.value.setZoom(12)
        }
      })
    }
  })

  renderPoints()
}

// Map Point Management Functions
const addMapPoint = () => {
  if (!newPoint.value.name || !newPoint.value.type || !newPoint.value.coordinates || !currentLangData.value) return

  // Initialize mapPoints array if it doesn't exist
  if (!currentLangData.value.mapPoints) {
    currentLangData.value.mapPoints = []
  }

  const point = {
    id: crypto.randomUUID(),
    name: newPoint.value.name,
    description: newPoint.value.description,
    coordinates: newPoint.value.coordinates,
    type: newPoint.value.type,
    order: currentLangData.value.mapPoints.length + 1
  }

  currentLangData.value.mapPoints.push(point)

  // Reset form
  newPoint.value = { name: '', description: '', coordinates: '', type: '' }

  // Update map
  renderPoints()
}

const removePoint = (index: number) => {
  if (!currentLangData.value?.mapPoints) return
  currentLangData.value.mapPoints.splice(index, 1)
  // Update order
  currentLangData.value.mapPoints.forEach((p, i) => {
    p.order = i + 1
  })
  renderPoints()
}

const movePointUp = (index: number) => {
  if (!currentLangData.value?.mapPoints || index === 0) return
  const points = currentLangData.value.mapPoints
  ;[points[index - 1], points[index]] = [points[index], points[index - 1]]
  // Update order
  points.forEach((p, i) => {
    p.order = i + 1
  })
  renderPoints()
}

const movePointDown = (index: number) => {
  if (!currentLangData.value?.mapPoints || index >= currentLangData.value.mapPoints.length - 1) return
  const points = currentLangData.value.mapPoints
  ;[points[index], points[index + 1]] = [points[index + 1], points[index]]
  // Update order
  points.forEach((p, i) => {
    p.order = i + 1
  })
  renderPoints()
}

const editPoint = (index: number) => {
  if (!currentLangData.value?.mapPoints) return
  const point = currentLangData.value.mapPoints[index]
  editingPointIndex.value = index
  editingPoint.value = { ...point }
}

const saveEdit = () => {
  if (!currentLangData.value?.mapPoints || editingPointIndex.value === null) return
  currentLangData.value.mapPoints[editingPointIndex.value] = { ...editingPoint.value }
  editingPointIndex.value = null
  editingPoint.value = { name: '', description: '', coordinates: '', type: '' }
  renderPoints()
}

const cancelEdit = () => {
  editingPointIndex.value = null
  editingPoint.value = { name: '', description: '', coordinates: '', type: '' }
}

const getPointTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    'punto_reunion': 'Meeting Point',
    'punto_parada': 'Stop Point',
    'lugar_turistico': 'Tourist Attraction',
    'restaurant': 'Restaurant',
    'hotel': 'Hotel',
    'aeropuerto': 'Airport',
    'estacion_tren': 'Train Station',
    'puerto': 'Port/Harbor',
    'otro': 'Other'
  }
  return labels[type] || type
}

// Handle search input with Google Places
const onMapSearchInput = () => {
  // This is handled by the Google Places autocomplete
}

const renderPoints = () => {
  if (!map.value || !currentLangData.value) return
  const google = (window as any).google

  // Clear existing
  markers.value.forEach(m => m.setMap(null))
  markers.value = []
  if (routeLine.value) routeLine.value.setMap(null)

  const bounds = new google.maps.LatLngBounds()
  const path: any[] = []

  currentLangData.value.mapPoints?.forEach((p, i) => {
    if (!p.coordinates) return
    const [lat, lng] = p.coordinates.split(',').map(Number)
    if (isNaN(lat) || isNaN(lng)) return
    const pos = { lat, lng }
    
    const marker = new google.maps.Marker({
      position: pos,
      map: map.value,
      label: {
        text: String(i + 1),
        color: 'white',
        fontWeight: 'bold'
      },
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#330df2',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
        scale: 12
      }
    })
    
    markers.value.push(marker)
    bounds.extend(pos)
    path.push(pos)
  })

  if (path.length > 1) {
    routeLine.value = new google.maps.Polyline({
      path: path,
      geodesic: true,
      strokeColor: '#330df2',
      strokeOpacity: 1.0,
      strokeWeight: 2,
      map: map.value
    })
  }

  if (currentLangData.value.mapPoints.length > 0) {
    map.value.fitBounds(bounds)
  }
}

watch(() => store.currentLanguage, () => {
  setTimeout(() => renderPoints(), 100)
})

onMounted(() => {
  initMap()
})
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-10px);
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
