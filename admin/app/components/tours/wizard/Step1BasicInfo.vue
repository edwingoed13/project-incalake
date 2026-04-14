<template>
  <div class="flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <!-- Section: Basic Information -->
    <div class="glass-card p-8 rounded-3xl shadow-sm space-y-8 border border-white/10">
      <h3 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
        <span class="material-symbols-outlined text-primary">info</span>
        Información Básica del Tour
      </h3>

      <!-- Editing language banner (edit mode) -->
      <div v-if="isEditMode" class="mb-6 flex items-center gap-4 px-5 py-4 bg-primary/5 border border-primary/20 rounded-2xl">
        <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-lg">
          {{ currentLangFlag }}
        </div>
        <div class="flex-1">
          <p class="text-sm font-black text-slate-900 dark:text-white">
            Editando traducción: <span class="text-primary">{{ currentLangName }} ({{ store.currentLanguage.toUpperCase() }})</span>
          </p>
          <p class="text-[10px] text-slate-500 font-medium">Código del tour: <span class="font-mono font-black">{{ store.basicInfo.code }}</span></p>
        </div>
        <!-- Show other available translations -->
        <div class="flex gap-1">
          <span
            v-for="lang in tourTranslationCodes"
            :key="lang"
            :class="lang === store.currentLanguage
              ? 'bg-primary text-white'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
            class="px-2 py-1 text-[9px] font-black rounded-md uppercase"
          >
            {{ lang }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
        <!-- Idioma Principal (solo al crear un tour nuevo) -->
        <template v-if="!isEditMode">
          <div class="col-span-2 space-y-2">
            <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
              Idioma Principal del Tour <span class="text-primary">*</span>
              <span class="text-[10px] lowercase font-medium ml-1 opacity-60">(selecciona primero para generar el código)</span>
            </label>
            <div class="relative">
              <select
                v-model="selectedLanguageId"
                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold"
              >
                <option v-for="lang in availableLanguages" :key="lang.id" :value="lang.id">
                  {{ lang.country }} ({{ lang.code.toUpperCase() }})
                </option>
              </select>
              <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
            </div>
          </div>

          <!-- Código del Tour (solo crear) -->
          <div class="space-y-2">
            <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
              Código del Tour <span class="text-primary">*</span>
              <span class="text-[10px] lowercase font-medium ml-1 opacity-60">(generado automáticamente)</span>
            </label>
            <div class="relative group">
              <input
                v-model="store.basicInfo.code"
                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-mono font-bold tracking-wider"
                placeholder="ES000"
                readonly
                type="text"
              />
              <div class="mt-1.5 flex items-center gap-1.5 px-1">
                <span class="material-symbols-outlined text-[14px] text-green-500">check_circle</span>
                <span class="text-[10px] font-bold text-green-600 uppercase">Código generado: {{ store.basicInfo.code || '...' }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Shared fields info (edit mode) -->
        <div v-if="isEditMode" class="col-span-2 flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-xl">
          <span class="material-symbols-outlined text-amber-500 text-base">info</span>
          <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400">Los siguientes campos son compartidos entre todos los idiomas de este tour.</p>
        </div>

        <!-- Ciudad de Salida -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
            Ciudad de salida <span class="text-primary">*</span>
            <span class="text-[10px] lowercase font-medium ml-1 opacity-60">(escribe para buscar)</span>
          </label>
          <div class="relative group">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">search</span>
            <input
              ref="cityInputRef"
              v-model="citySearchQuery"
              @input="onCitySearch"
              class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold"
              placeholder="Ej: Puno, Cusco..."
              type="text"
            />
            
            <!-- City Search Results -->
            <div v-if="cityResults.length > 0" class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                <ul class="py-2">
                    <li 
                        v-for="city in cityResults" 
                        :key="city.id"
                        @click="selectCity(city)"
                        class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer flex items-center gap-3 transition-colors text-slate-700 dark:text-slate-300"
                    >
                        <span class="material-symbols-outlined text-slate-400">location_on</span>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">{{ city.name }}</div>
                            <div class="text-[10px] text-slate-500 uppercase font-medium">{{ city.country_code }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-if="store.basicInfo.nearestCity" class="mt-1.5 flex items-center gap-1.5 px-1">
              <span class="material-symbols-outlined text-[14px] text-primary">check_circle</span>
              <span class="text-[10px] font-bold text-primary uppercase">Ciudad seleccionada: {{ store.basicInfo.nearestCity }}</span>
            </div>
          </div>
        </div>

        <!-- Tipo de Servicio -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Tipo de servicio <span class="text-primary">*</span></label>
          <div class="relative">
            <select 
              v-model="store.basicInfo.serviceType"
              class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold"
            >
              <option value="tour">Tour</option>
              <option value="package">Paquete</option>
              <option value="experience">Experiencia</option>
              <option value="transport">Transporte</option>
            </select>
            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
          </div>
        </div>

        <!-- Dificultad -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Dificultad <span class="text-primary">*</span></label>
          <div class="relative">
            <select 
              v-model="store.basicInfo.difficulty"
              class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold"
            >
              <option value="easy">Fácil</option>
              <option value="moderate">Moderado</option>
              <option value="hard">Difícil</option>
            </select>
            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
          </div>
        </div>

        <!-- Capacidad Máxima -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
            Capacidad máxima <span class="text-primary">*</span>
            <span class="text-[10px] lowercase font-medium ml-1 opacity-60">(por defecto: 99 personas)</span>
          </label>
          <input 
            v-model.number="store.basicInfo.capacityMax"
            class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold" 
            type="number"
            min="1"
          />
        </div>
      </div>
    </div>

    <!-- Section: Schedules & Duration -->
    <div class="glass-card p-8 rounded-3xl shadow-sm space-y-8 border border-white/10">
      <h3 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
        <span class="material-symbols-outlined text-primary">calendar_today</span>
        Horarios y Duración
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
        <!-- Horarios de Salida (múltiples, con duración) -->
        <div class="space-y-2 md:col-span-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Horarios de Salida y Duración <span class="text-primary">*</span></label>
            <button
              type="button"
              @click="addDepartureTime"
              class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:text-primary/80 transition-colors"
            >
              <span class="material-symbols-outlined text-sm">add_circle</span>
              Agregar horario
            </button>
          </div>
          <div class="space-y-2">
            <div v-for="(item, idx) in store.basicInfo.startTimes" :key="idx" class="grid grid-cols-[1fr_100px_110px_auto] gap-2 items-center">
              <!-- Hora -->
              <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">schedule</span>
                <input
                  v-model="item.time"
                  @input="syncPrimaryStartTime"
                  class="w-full pl-10 pr-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold"
                  type="time"
                />
              </div>
              <!-- Duración -->
              <input
                v-model.number="item.duration"
                @input="syncPrimaryDuration"
                min="0"
                step="0.5"
                class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold text-center"
                type="number"
                placeholder="Duración"
              />
              <!-- Unidad -->
              <div class="relative">
                <select
                  v-model="item.durationUnit"
                  @change="syncPrimaryDuration"
                  class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold text-sm"
                >
                  <option value="hours">Horas</option>
                  <option value="days">Días</option>
                </select>
                <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-sm">expand_more</span>
              </div>
              <!-- Eliminar -->
              <button
                v-if="store.basicInfo.startTimes.length > 1"
                type="button"
                @click="removeDepartureTime(idx)"
                class="p-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                :title="`Eliminar horario ${idx + 1}`"
              >
                <span class="material-symbols-outlined">delete</span>
              </button>
              <div v-else class="w-[42px]"></div>
            </div>
          </div>
          <p class="text-[10px] text-slate-400 mt-1">El primer horario se usa como principal. Cada horario tiene su propia duración.</p>
        </div>

        <!-- Duración Aproximada (legacy, sincronizado con el primer horario) -->
        <div class="space-y-2 hidden">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Duración Aproximada</label>
          <div class="flex gap-3">
            <input
              v-model.number="store.basicInfo.duration"
              class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold"
              type="number"
            />
            <div class="relative flex-1">
              <select
                v-model="store.basicInfo.durationUnit"
                class="w-full h-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold"
              >
                <option value="hours">Horas</option>
                <option value="days">Días</option>
              </select>
              <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
            </div>
          </div>
        </div>

        <!-- Zona Horaria -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Zona Horaria <span class="text-primary">*</span></label>
          <div class="relative">
            <select 
              v-model="store.basicInfo.timezone"
              class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold"
            >
              <option value="America/Lima">Hora Peruana (GMT-5)</option>
              <option value="America/La_Paz">Hora Boliviana (GMT-4)</option>
            </select>
            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
          </div>
        </div>

        <!-- Active Toggle -->
        <div class="flex items-center pt-8">
          <label class="relative inline-flex items-center cursor-pointer group">
            <input type="checkbox" v-model="isTourActive" class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300 group-hover:text-primary transition-colors">Tour activo</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useTourWizardStore } from '~/stores/tourWizard'
import { useGooglePlaces } from '~/composables/useGooglePlaces'

const store = useTourWizardStore()
const config = useRuntimeConfig()
const defaultApiUrl = config.public.apiUrl
const { initCityAutocomplete, cleanup } = useGooglePlaces()

// Ensure startTimes array exists (migration from single startTime)
if (!Array.isArray(store.basicInfo.startTimes) || store.basicInfo.startTimes.length === 0) {
  store.basicInfo.startTimes = [{
    time: store.basicInfo.startTime || '08:00',
    duration: store.basicInfo.duration || 1,
    durationUnit: store.basicInfo.durationUnit || 'hours',
  }]
} else {
  // Migrate old string[] format to object[] format
  store.basicInfo.startTimes = store.basicInfo.startTimes.map((t: any) => {
    if (typeof t === 'string') {
      return {
        time: t,
        duration: store.basicInfo.duration || 1,
        durationUnit: store.basicInfo.durationUnit || 'hours',
      }
    }
    return {
      time: t.time || '08:00',
      duration: Number(t.duration) || 1,
      durationUnit: t.durationUnit || 'hours',
    }
  })
}

const addDepartureTime = () => {
  // Copy duration from last entry so user doesn't have to re-enter
  const last = store.basicInfo.startTimes[store.basicInfo.startTimes.length - 1]
  store.basicInfo.startTimes.push({
    time: '08:00',
    duration: last?.duration || store.basicInfo.duration || 1,
    durationUnit: last?.durationUnit || store.basicInfo.durationUnit || 'hours',
  })
}

const removeDepartureTime = (idx: number) => {
  if (store.basicInfo.startTimes.length <= 1) return
  store.basicInfo.startTimes.splice(idx, 1)
  syncPrimaryStartTime()
  syncPrimaryDuration()
}

const syncPrimaryStartTime = () => {
  store.basicInfo.startTime = store.basicInfo.startTimes[0]?.time || '08:00'
}

const syncPrimaryDuration = () => {
  // Keep top-level duration fields in sync with first schedule for backward compat
  const first = store.basicInfo.startTimes[0]
  if (first) {
    store.basicInfo.duration = first.duration || 1
    store.basicInfo.durationUnit = first.durationUnit || 'hours'
  }
}

// Edit mode detection
const isEditMode = computed(() => !!store.tourId && store.tourId !== 'new')

// Language display helpers
const langFlags: Record<string, string> = {
  es: '🇪🇸', en: '🇬🇧', pt: '🇵🇹', fr: '🇫🇷', de: '🇩🇪', it: '🇮🇹'
}
const langNames: Record<string, string> = {
  es: 'Español', en: 'English', pt: 'Português', fr: 'Français', de: 'Deutsch', it: 'Italiano'
}
const currentLangFlag = computed(() => langFlags[store.currentLanguage] || '🌐')
const currentLangName = computed(() => langNames[store.currentLanguage] || store.currentLanguage)

// Get list of translation language codes for this tour
const tourTranslationCodes = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

// Data state
const availableLanguages = ref<any[]>([])
const selectedLanguageId = ref<number | null>(null)
const selectedLanguageCode = ref('es')
const isTourActive = ref(true)

// City search state
const citySearchQuery = ref('')
const cityResults = ref<any[]>([])
const isSearchingCities = ref(false)
const cityInputRef = ref<HTMLInputElement | null>(null)

// Fetch languages on mount
const fetchLanguages = async () => {
  try {
    const response: any = await $fetch(`${defaultApiUrl}/languages?all=true`)
    if (response.success) {
      availableLanguages.value = response.data
      store.availableLanguages = response.data // Save to store for other steps
      
      // If store already has a language (edit mode), use it. Otherwise, select 'es' or first.
      // Preserve ?lang= from URL — never override it
      const route = useRoute()
      const langFromUrl = (route.query.lang as string)?.toLowerCase()

      if (store.basicInfo.languageId) {
        selectedLanguageId.value = store.basicInfo.languageId
        const currentLang = availableLanguages.value.find(l => l.id === store.basicInfo.languageId)
        if (currentLang) {
          selectedLanguageCode.value = currentLang.code
        }
      }

      // Set currentLanguage: URL param takes priority, then existing store value for new tours
      if (langFromUrl) {
        store.currentLanguage = langFromUrl
      } else if (!isEditMode.value) {
        // Only auto-set language for new tours
        const esLang = availableLanguages.value.find(l => l.code.toLowerCase() === 'es')
        if (esLang) {
          selectedLanguageId.value = esLang.id
          selectedLanguageCode.value = esLang.code
          store.currentLanguage = 'es'
        } else if (availableLanguages.value.length > 0) {
          selectedLanguageId.value = availableLanguages.value[0].id
          selectedLanguageCode.value = availableLanguages.value[0].code
          store.currentLanguage = availableLanguages.value[0].code.toLowerCase()
        }
      }
    }
  } catch (error) {
    console.error('Error fetching languages:', error)
  }
}

// Generate tour code via API
const generateTourCode = async (langId: number) => {
  if (!langId) return
  
  try {
    const response: any = await $fetch(`${defaultApiUrl}/admin/tours/generate-code?language_id=${langId}`)
    if (response.success) {
      store.basicInfo.code = response.data.code
    }
  } catch (error) {
    console.error('Error generating tour code:', error)
  }
}

// Initialize Google Places Autocomplete
const initializeGooglePlaces = async () => {
  await nextTick()

  if (cityInputRef.value) {
    try {
      await initCityAutocomplete(cityInputRef.value, (placeData: any) => {
        // Store the city information
        store.basicInfo.nearestCity = placeData.cityName || placeData.formatted_address
        store.basicInfo.cityCoordinates = {
          lat: placeData.lat,
          lng: placeData.lng
        }

        // Update the input value
        citySearchQuery.value = placeData.cityName ?
          (placeData.countryName ? `${placeData.cityName}, ${placeData.countryName}` : placeData.cityName) :
          placeData.formatted_address

        // Clear any old results
        cityResults.value = []
      })
    } catch (error) {
      console.error('Error initializing Google Places:', error)
      // Fallback to manual search if Google Places fails
    }
  }
}

// Fallback manual city search (in case Google Places fails)
let searchTimeout: any = null
const onCitySearch = () => {
  // Don't search if Google Places is handling it
  if (cityInputRef.value?.hasAttribute('data-autocomplete-initialized')) {
    return
  }

  if (searchTimeout) clearTimeout(searchTimeout)

  if (citySearchQuery.value.length < 2) {
    cityResults.value = []
    return
  }

  isSearchingCities.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const response: any = await $fetch(`${defaultApiUrl}/cities?search=${citySearchQuery.value}&active=true`)
      if (response.success) {
        cityResults.value = response.data
      }
    } catch (error) {
      console.error('Error searching cities:', error)
    } finally {
      isSearchingCities.value = false
    }
  }, 300)
}

const selectCity = (city: any) => {
  store.basicInfo.nearestCity = city.name
  store.basicInfo.cityId = city.id
  citySearchQuery.value = city.name
  cityResults.value = []
}

// Watch for language changes to auto-generate the tour code (only for new tours)
watch(selectedLanguageId, (newLangId) => {
  if (newLangId && !isEditMode.value) {
    const languageChanged = store.basicInfo.languageId !== newLangId

    store.basicInfo.languageId = newLangId
    const lang = availableLanguages.value.find(l => l.id === newLangId)

    if (lang) {
        selectedLanguageCode.value = lang.code
        store.currentLanguage = lang.code.toLowerCase()

        if (languageChanged) {
           generateTourCode(newLangId)
        }
    }
  }
})

// Initialize city query if already exists in store
onMounted(async () => {
  await fetchLanguages()

  if (store.basicInfo.nearestCity) {
    citySearchQuery.value = store.basicInfo.nearestCity
  }

  // Initialize Google Places Autocomplete
  await initializeGooglePlaces()

  if (!store.basicInfo.code && selectedLanguageId.value) {
    generateTourCode(selectedLanguageId.value)
  }
})

// Cleanup on unmount
onUnmounted(() => {
  if (cityInputRef.value) {
    cleanup(cityInputRef.value)
  }
})
</script>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
