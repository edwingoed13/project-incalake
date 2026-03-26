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
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ store.currentLanguage.toUpperCase() }}</h4>
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
            <!-- Left: Itinerary List -->
            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Timeline Activities</h4>
                <button 
                  @click="addDay"
                  class="flex items-center gap-2 text-primary text-xs font-bold hover:underline"
                >
                  <span class="material-symbols-outlined text-sm">add_circle</span>
                  Add Day
                </button>
              </div>

              <div class="space-y-4 max-h-[700px] overflow-y-auto pr-2 custom-scrollbar">
                <TransitionGroup name="list">
                  <div 
                    v-for="(day, index) in currentLangData.itinerary" 
                    :key="day.id"
                    class="glass-card rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden group transition-all"
                  >
                    <div class="p-4 space-y-4">
                      <div class="flex justify-between items-start">
                        <div class="flex items-center gap-2 flex-1">
                          <span class="text-[10px] font-black bg-primary/10 text-primary px-2 py-0.5 rounded uppercase">Day {{ index + 1 }}</span>
                          <input 
                            v-model="day.title"
                            class="flex-1 text-sm font-bold bg-transparent border-none p-0 focus:ring-0 text-slate-900 dark:text-white" 
                            placeholder="Title of this day..." 
                            type="text"
                          />
                        </div>
                        <button @click="removeDay(index)" class="text-slate-300 hover:text-red-500"><span class="material-symbols-outlined text-sm">delete</span></button>
                      </div>
                      <textarea 
                        v-model="day.description"
                        class="w-full bg-slate-50/50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 rounded-lg p-2 text-xs text-slate-600 dark:text-slate-300 focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none" 
                        placeholder="What happens today?" 
                        rows="2"
                      ></textarea>
                    </div>
                  </div>
                </TransitionGroup>
                
                <div v-if="currentLangData.itinerary.length === 0" class="py-12 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-xl flex flex-col items-center justify-center text-slate-300">
                  <span class="material-symbols-outlined text-4xl mb-2 opacity-20">event_note</span>
                  <p class="text-xs font-medium">No days added.</p>
                </div>
              </div>
            </div>

            <!-- Right: Map Builder -->
            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Tour Map Points</h4>
                <div class="flex items-center gap-2 text-[10px] text-emerald-500 font-bold uppercase tracking-wider animate-pulse">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live Preview
                </div>
              </div>

              <!-- Map Container -->
              <div class="relative rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-slate-100 dark:bg-slate-900 aspect-square shadow-inner group">
                <div id="tourMapCanvas" class="w-full h-full"></div>
                
                <!-- Map Controls Overlay -->
                <div class="absolute top-4 left-4 right-4 flex flex-col gap-2 z-10 pointer-events-none">
                  <div class="pointer-events-auto bg-white/90 dark:bg-slate-900/90 backdrop-blur border border-slate-200 dark:border-slate-800 p-3 rounded-xl shadow-xl space-y-3 max-w-xs">
                    <input 
                      id="pointNameInput"
                      v-model="mapFields.name"
                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg text-xs outline-none focus:ring-1 focus:ring-primary"
                      placeholder="Search point/place name..."
                    />
                    <div class="flex gap-2">
                       <select 
                        v-model="mapFields.type"
                        class="flex-1 px-2 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg text-[10px] font-bold outline-none"
                       >
                         <option value="">Type...</option>
                         <option value="punto_parada">Stop Point</option>
                         <option value="restaurant">Restaurant</option>
                         <option value="lugar_turistico">Sightseeing</option>
                         <option value="aeropuerto">Airport</option>
                         <option value="estacion_tren">Train Station</option>
                         <option value="punto_reunion">Meeting Point</option>
                         <option value="otro">Other</option>
                       </select>
                       <button 
                        @click="addPoint"
                        class="px-3 py-2 bg-primary text-white text-[10px] font-bold rounded-lg hover:shadow-lg hover:shadow-primary/30 transition-all flex items-center gap-1"
                       >
                         <span class="material-symbols-outlined text-[14px]">add</span> Add
                       </button>
                    </div>
                  </div>
                </div>

                <!-- Points List Overlay (Minimal) -->
                <div class="absolute bottom-4 left-4 right-4 z-10 pointer-events-none flex justify-end">
                   <div class="pointer-events-auto bg-black/50 backdrop-blur px-3 py-1.5 rounded-full border border-white/10 text-[10px] font-bold text-white flex items-center gap-2 shadow-2xl">
                     <span class="material-symbols-outlined text-xs">location_on</span>
                     <span>{{ currentLangData.mapPoints.length }} Points registered</span>
                   </div>
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
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import TiptapEditor from '~/components/common/TiptapEditor.vue'

const store = useTourWizardStore()

const currentLangData = computed(() => {
  return store.detailedContent[store.currentLanguage]
})

// Map State
const map = ref<any>(null)
const markers = ref<any[]>([])
const routeLine = ref<any>(null)
const mapFields = ref({
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

  // Autocomplete
  const input = document.getElementById('pointNameInput') as HTMLInputElement
  const autocomplete = new google.maps.places.Autocomplete(input)
  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace()
    if (!place.geometry || !place.geometry.location) return
    
    mapFields.value.name = place.name || ''
    mapFields.value.description = place.formatted_address || ''
    mapFields.value.coordinates = `${place.geometry.location.lat()},${place.geometry.location.lng()}`
    
    map.value.setCenter(place.geometry.location)
    map.value.setZoom(15)
  })

  renderPoints()
}

const addPoint = () => {
  if (!mapFields.value.name || !mapFields.value.type || !currentLangData.value) return
  
  const point = {
    name: mapFields.value.name,
    description: mapFields.value.description,
    coordinates: mapFields.value.coordinates,
    type: mapFields.value.type,
    order: currentLangData.value.mapPoints.length + 1
  }
  
  currentLangData.value.mapPoints.push(point)
  mapFields.value = { name: '', description: '', coordinates: '', type: '' }
  renderPoints()
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

  currentLangData.value.mapPoints.forEach((p, i) => {
    const [lat, lng] = p.coordinates.split(',').map(Number)
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
