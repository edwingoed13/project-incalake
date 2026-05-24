<template>
  <div class="flex flex-col gap-6">
    <!-- Language selector -->
    <UCard :ui="{ body: 'p-3 sm:p-3' }">
      <div class="flex items-center gap-3">
        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center">
          <UIcon name="i-lucide-languages" class="size-4 text-primary" />
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Editando contenido detallado en</p>
          <div class="flex items-center gap-1 mt-1">
            <UButton
              v-for="lang in tourLanguages"
              :key="lang"
              size="xs"
              :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
              :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
              class="uppercase font-black tracking-wider"
              @click="store.currentLanguage = lang"
            >
              {{ lang }}
            </UButton>
          </div>
        </div>
      </div>
    </UCard>

    <!-- Sections (collapsibles) -->
    <template v-if="currentLangData">

      <!-- Public title + short description: the title is the #1 content field,
           so it leads the Contenido step (moved here from the SEO step). -->
      <UCard :ui="{ body: 'p-4 sm:p-4 space-y-3' }">
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-type" class="size-5 text-primary" />
          <h3 class="text-base font-bold">Título y resumen</h3>
        </div>
        <UFormField label="Título público" required>
          <UInput
            v-model="currentLangData.title"
            placeholder="Ej. Tour mágico al atardecer en Cusco"
            class="w-full"
          />
        </UFormField>
        <UFormField label="Descripción corta" hint="Resumen para listados de búsqueda">
          <UTextarea
            v-model="currentLangData.shortDescription"
            :rows="3"
            placeholder="Resumen breve para listados..."
            class="w-full"
          />
        </UFormField>
      </UCard>

      <!-- Section: Long Description -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('description') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('description')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('description') }" />
            <UIcon name="i-lucide-file-text" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Descripción detallada del tour</h3>
            <UBadge v-if="hasContent(currentLangData.detailedDescription)" color="success" variant="subtle" size="xs" icon="i-lucide-check">Completo</UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('description')">
          <TiptapEditor
            v-model="currentLangData.detailedDescription"
            placeholder="Escribe una descripción larga y atractiva de la experiencia..."
          />
        </div>
      </UCard>

      <!-- Section: Itinerary Text (Tiptap) -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('itinerary') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('itinerary')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('itinerary') }" />
            <UIcon name="i-lucide-route" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Itinerario del tour</h3>
            <UBadge v-if="hasContent(currentLangData.itineraryText)" color="success" variant="subtle" size="xs" icon="i-lucide-check">Completo</UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('itinerary')">
          <TiptapEditor
            v-model="currentLangData.itineraryText"
            placeholder="Describe el itinerario con listas, títulos y texto en negrita..."
          />
        </div>
      </UCard>

      <!-- Section: Daily Schedule & Map -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('map') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('map')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('map') }" />
            <UIcon name="i-lucide-map" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Construye la ruta del tour</h3>
            <UBadge v-if="(currentLangData.mapPoints?.length || 0) > 0" color="success" variant="subtle" size="xs" icon="i-lucide-map-pin">
              {{ currentLangData.mapPoints?.length }} puntos
            </UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('map')">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Left: Map Preview -->
            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Puntos del tour en el mapa</h4>
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
                  <h5 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Agregar nuevo punto</h5>
                </div>

                <div class="space-y-2">
                  <input
                    ref="mapSearchInput"
                    v-model="newPoint.name"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                    placeholder="Buscar ubicación (Google Places)..."
                    @input="onMapSearchInput"
                  />

                  <textarea
                    v-model="newPoint.description"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary resize-none"
                    placeholder="Descripción (opcional)"
                    rows="2"
                  ></textarea>

                  <div class="grid grid-cols-2 gap-2">
                    <select
                      v-model="newPoint.type"
                      class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                    >
                      <option value="">Seleccionar tipo...</option>
                      <option value="punto_reunion">Punto de encuentro</option>
                      <option value="punto_parada">Punto de parada</option>
                      <option value="lugar_turistico">Atractivo turístico</option>
                      <option value="restaurant">Restaurante</option>
                      <option value="hotel">Hotel</option>
                      <option value="aeropuerto">Aeropuerto</option>
                      <option value="estacion_tren">Estación de tren</option>
                      <option value="puerto">Puerto</option>
                      <option value="otro">Otro</option>
                    </select>

                    <input
                      v-model="newPoint.coordinates"
                      class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary font-mono text-xs"
                      placeholder="Lat,Lng (auto-llenado)"
                      readonly
                    />
                  </div>

                  <UButton
                    block
                    icon="i-lucide-plus"
                    color="primary"
                    size="md"
                    :disabled="!newPoint.name || !newPoint.type || !newPoint.coordinates"
                    @click="addMapPoint"
                  >
                    Agregar punto
                  </UButton>
                </div>
              </div>

              <!-- Points List -->
              <div class="space-y-2">
                <div class="flex items-center gap-2 mb-2">
                  <span class="material-symbols-outlined text-primary text-sm">route</span>
                  <h5 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Puntos de la ruta ({{ currentLangData.mapPoints?.length || 0 }})</h5>
                </div>

                <div v-if="currentLangData.mapPoints && currentLangData.mapPoints.length > 0" class="space-y-1.5">
                  <div
                    v-for="(point, index) in currentLangData.mapPoints"
                    :key="point.id || index"
                    class="group bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-2 hover:border-primary/40 transition-all"
                  >
                    <div v-if="editingPointIndex !== index" class="flex items-center gap-2.5">
                      <div class="flex items-center justify-center size-6 rounded-full bg-primary/10 text-primary text-[10px] font-black shrink-0">
                        {{ index + 1 }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                          <h6 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ point.name }}</h6>
                          <UBadge color="neutral" variant="subtle" size="xs">{{ getPointTypeLabel(point.type) }}</UBadge>
                        </div>
                        <p v-if="point.description" class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ point.description }}</p>
                        <p class="text-[9px] text-slate-400 font-mono mt-0.5 truncate">{{ point.coordinates }}</p>
                      </div>
                      <div class="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <UButton
                          icon="i-lucide-arrow-up"
                          color="neutral"
                          variant="ghost"
                          size="xs"
                          :disabled="index === 0"
                          title="Mover arriba"
                          @click="movePointUp(index)"
                        />
                        <UButton
                          icon="i-lucide-arrow-down"
                          color="neutral"
                          variant="ghost"
                          size="xs"
                          :disabled="index === currentLangData.mapPoints.length - 1"
                          title="Mover abajo"
                          @click="movePointDown(index)"
                        />
                        <UButton
                          icon="i-lucide-pencil"
                          color="info"
                          variant="ghost"
                          size="xs"
                          title="Editar"
                          @click="editPoint(index)"
                        />
                        <UButton
                          icon="i-lucide-trash-2"
                          color="error"
                          variant="ghost"
                          size="xs"
                          title="Eliminar"
                          @click="removePoint(index)"
                        />
                      </div>
                    </div>

                    <!-- Edit Mode -->
                    <div v-else class="space-y-2">
                      <input
                        v-model="editingPoint.name"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm"
                        placeholder="Nombre"
                      />
                      <textarea
                        v-model="editingPoint.description"
                        class="w-full px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-sm resize-none"
                        placeholder="Descripción"
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
                          Cancelar
                        </button>
                        <button
                          @click="saveEdit"
                          class="px-3 py-1 bg-primary text-white text-xs font-bold rounded hover:bg-primary-600"
                        >
                          Guardar
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="py-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center text-slate-400">
                  <span class="material-symbols-outlined text-3xl mb-2 opacity-30">location_off</span>
                  <p class="text-xs font-medium">Aún no hay puntos en el mapa</p>
                  <p class="text-[10px] opacity-60 mt-1">Agrega puntos para crear la ruta del tour</p>
                </div>
              </div>

            </div>
          </div>
        </div>
      </UCard>

      <!-- Section: Inclusions & Exclusions -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('inclusions') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('inclusions')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('inclusions') }" />
            <UIcon name="i-lucide-list-checks" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Qué incluye / Qué NO incluye</h3>
            <UBadge v-if="hasContent(currentLangData.inclusions) || hasContent(currentLangData.exclusions)" color="success" variant="subtle" size="xs" icon="i-lucide-check">
              {{ [hasContent(currentLangData.inclusions), hasContent(currentLangData.exclusions)].filter(Boolean).length }} / 2
            </UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('inclusions')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-circle-check" class="size-4 text-success" />
              <h4 class="text-sm font-bold">Qué incluye</h4>
            </div>
            <TiptapEditor v-model="currentLangData.inclusions" placeholder="¿Qué incluye el precio? Usa lista con viñetas." />
          </div>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-circle-x" class="size-4 text-error" />
              <h4 class="text-sm font-bold">Qué NO incluye</h4>
            </div>
            <TiptapEditor v-model="currentLangData.exclusions" placeholder="¿Qué NO está incluido? Sé claro para evitar reclamos." />
          </div>
        </div>
      </UCard>

      <!-- Section: Recommendations & What to Bring -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('recommendations') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('recommendations')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('recommendations') }" />
            <UIcon name="i-lucide-lightbulb" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Recomendaciones y qué llevar</h3>
            <UBadge v-if="hasContent(currentLangData.recommendations) || hasContent(currentLangData.thingsToBring)" color="success" variant="subtle" size="xs" icon="i-lucide-check">
              {{ [hasContent(currentLangData.recommendations), hasContent(currentLangData.thingsToBring)].filter(Boolean).length }} / 2
            </UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('recommendations')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-lightbulb" class="size-4 text-primary" />
              <h4 class="text-sm font-bold">Recomendaciones</h4>
            </div>
            <TiptapEditor v-model="currentLangData.recommendations" placeholder="Consejos para viajeros, mejor época para visitar, etc." />
          </div>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-lucide-backpack" class="size-4 text-primary" />
              <h4 class="text-sm font-bold">Qué llevar</h4>
            </div>
            <TiptapEditor v-model="currentLangData.thingsToBring" placeholder="Ropa, equipo, documentos requeridos..." />
          </div>
        </div>
      </UCard>

      <!-- Section: Custom additional sections -->
      <UCard :ui="{ header: 'p-0', body: isSectionExpanded('custom') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
        <template #header>
          <button
            type="button"
            class="w-full p-3 flex items-center gap-2 hover:bg-elevated/40 transition-colors text-left"
            @click="toggleSection('custom')"
          >
            <UIcon name="i-lucide-chevron-down" class="size-4 text-muted transition-transform" :class="{ 'rotate-180': isSectionExpanded('custom') }" />
            <UIcon name="i-lucide-plus-circle" class="size-5 text-primary" />
            <h3 class="text-base font-bold flex-1">Secciones adicionales</h3>
            <UBadge v-if="(currentLangData.customSections?.length || 0) > 0" color="success" variant="subtle" size="xs">
              {{ currentLangData.customSections?.length }}
            </UBadge>
          </button>
        </template>
        <div v-show="isSectionExpanded('custom')" class="space-y-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-muted">
              Agrega bloques con título y contenido para información específica del tour (ej. requisitos especiales, equipos, contactos).
            </p>
            <UButton
              icon="i-lucide-plus"
              color="primary"
              size="sm"
              @click="addCustomSection"
            >
              Agregar sección
            </UButton>
          </div>

          <UAlert
            v-if="!currentLangData.customSections?.length"
            color="neutral"
            variant="subtle"
            icon="i-lucide-info"
            description="Sin secciones adicionales. Click en 'Agregar sección' para crear una."
          />

          <div v-else class="space-y-3">
            <UCard
              v-for="(section, idx) in currentLangData.customSections"
              :key="section.id"
              :ui="{ body: 'p-3 space-y-2' }"
            >
              <div class="flex items-center gap-2">
                <span class="size-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px] font-black shrink-0">{{ idx + 1 }}</span>
                <UInput
                  v-model="section.title"
                  placeholder="Título de la sección (ej. Requisitos especiales)"
                  class="flex-1"
                  size="sm"
                />
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  size="sm"
                  title="Eliminar sección"
                  @click="removeCustomSection(idx)"
                />
              </div>
              <TiptapEditor
                v-model="section.content"
                placeholder="Contenido de la sección (texto, listas, imágenes, tablas)..."
                :key="'cs-' + section.id + '-' + store.currentLanguage"
              />
            </UCard>
          </div>
        </div>
      </UCard>

    </template>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import TiptapEditor from '~/components/v2/TiptapEditorV2.vue'
import { useGooglePlaces } from '~/composables/useGooglePlaces'

const store = useTourWizardStore()
const { initCityAutocomplete, initPlaceAutocomplete } = useGooglePlaces()

// Collapsible sections — state persisted in localStorage so F5 keeps each open/closed.
const { toggleSection, isSectionExpanded } = useCollapsibles('wizard:step3')
const hasContent = (html?: string) => !!(html && html.replace(/<[^>]*>/g, '').trim().length > 0)

const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

const currentLangData = computed(() => {
  const data = store.detailedContent[store.currentLanguage]
  // Tours saved before custom_sections existed don't have the array — backfill
  if (data && !Array.isArray(data.customSections)) {
    data.customSections = []
  }
  return data
})

const addCustomSection = () => {
  if (!currentLangData.value) return
  if (!Array.isArray(currentLangData.value.customSections)) {
    currentLangData.value.customSections = []
  }
  currentLangData.value.customSections.push({
    id: `cs-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    title: '',
    content: '',
  })
}

const removeCustomSection = (idx: number) => {
  if (!currentLangData.value?.customSections) return
  if (!confirm('¿Eliminar esta sección? Esta acción no se puede deshacer hasta guardar.')) return
  currentLangData.value.customSections.splice(idx, 1)
}

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

  // Setup autocomplete for the search input — generic places (POIs, landmarks, hotels, etc.)
  nextTick(() => {
    if (mapSearchInput.value) {
      initPlaceAutocomplete(mapSearchInput.value, (placeData: any) => {
        newPoint.value.name = placeData.name || placeData.formatted_address || ''
        newPoint.value.description = placeData.formatted_address || ''
        newPoint.value.coordinates = `${placeData.lat},${placeData.lng}`

        // Center map on selected location
        if (map.value && placeData.lat && placeData.lng) {
          map.value.setCenter({ lat: placeData.lat, lng: placeData.lng })
          map.value.setZoom(14)
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
