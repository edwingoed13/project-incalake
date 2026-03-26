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

              <!-- Radius Controls (Only for hotel_pickup) -->
              <div v-if="type === 'hotel_pickup'" class="space-y-6 animate-in slide-in-from-top-2">
                <div class="space-y-4">
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
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">Interactive Map Builder</span>
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
  }
}>()

const emit = defineEmits(['close', 'save'])

const localCoords = ref({ lat: -15.8402, lng: -70.0219 }) // Puno default
const localRadius = ref(1)
const localDescription = ref('')

const map = ref<any>(null)
const marker = ref<any>(null)
const circle = ref<any>(null)

watch(() => props.isOpen, (val) => {
  if (val) {
    localCoords.value = {
      lat: props.initialData.lat || -15.8402,
      lng: props.initialData.lng || -70.0219
    }
    localRadius.value = props.initialData.radius || 1
    localDescription.value = props.initialData.description || ''
    
    nextTick(() => {
      loadGoogleMaps()
    })
  }
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

  const script = document.createElement('script')
  script.id = 'google-maps-script'
  script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyCC2CAVXwufsdT5TX3UPk7hZ3HHw3NZl_c&libraries=places`
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

const handleSave = () => {
  emit('save', {
    lat: localCoords.value.lat,
    lng: localCoords.value.lng,
    radius: localRadius.value,
    description: localDescription.value
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
