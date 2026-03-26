<template>
  <div class="flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <!-- Section: Basic Information -->
    <div class="glass-card p-8 rounded-3xl shadow-sm space-y-8 border border-white/10">
      <h3 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
        <span class="material-symbols-outlined text-primary">info</span>
        Información Básica del Tour
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
        <!-- Idioma Principal -->
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

        <!-- Código del Tour -->
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

        <!-- Ciudad de Salida -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
            Ciudad de salida <span class="text-primary">*</span>
            <span class="text-[10px] lowercase font-medium ml-1 opacity-60">(escribe para buscar)</span>
          </label>
          <div class="relative group">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">search</span>
            <input 
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
        <!-- Hora de Salida -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Hora de Salida <span class="text-primary">*</span></label>
          <div class="flex gap-3">
            <div class="relative flex-1 group">
              <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors cursor-pointer">schedule</span>
              <input 
                v-model="store.basicInfo.startTime"
                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white font-bold" 
                type="time"
              />
            </div>
            <div class="relative w-24">
              <select class="w-full h-full px-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white appearance-none font-bold">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-sm">expand_more</span>
            </div>
          </div>
        </div>

        <!-- Duración Aproximada -->
        <div class="space-y-2">
          <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Duración Aproximada <span class="text-primary">*</span></label>
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
import { ref, watch, onMounted } from 'vue'
import { useTourWizardStore } from '~/stores/tourWizard'

const store = useTourWizardStore()
const config = useRuntimeConfig()
const defaultApiUrl = config.public.apiUrl

// Data state
const availableLanguages = ref<any[]>([])
const selectedLanguageId = ref<number | null>(null)
const selectedLanguageCode = ref('es')
const isTourActive = ref(true)

// City search state
const citySearchQuery = ref('')
const cityResults = ref<any[]>([])
const isSearchingCities = ref(false)

// Fetch languages on mount
const fetchLanguages = async () => {
  try {
    const response: any = await $fetch(`${defaultApiUrl}/languages?all=true`)
    if (response.success) {
      availableLanguages.value = response.data
      store.availableLanguages = response.data // Save to store for other steps
      
      // If store already has a language (edit mode), use it. Otherwise, select 'es' or first.
      if (store.basicInfo.languageId) {
        selectedLanguageId.value = store.basicInfo.languageId
        const currentLang = availableLanguages.value.find(l => l.id === store.basicInfo.languageId)
        if (currentLang) {
          selectedLanguageCode.value = currentLang.code
          store.currentLanguage = currentLang.code.toLowerCase()
        }
      } else {
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

// City Search
let searchTimeout: any = null
const onCitySearch = () => {
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

// Watch for language changes to auto-generate the tour code
watch(selectedLanguageId, (newLangId) => {
  if (newLangId) {
    const isNewTour = !store.tourId || store.tourId === 'new'
    const languageChanged = store.basicInfo.languageId !== newLangId

    store.basicInfo.languageId = newLangId
    const lang = availableLanguages.value.find(l => l.id === newLangId)

    if (lang) {
        selectedLanguageCode.value = lang.code
        // Update the global current language in the store for Step 2
        store.currentLanguage = lang.code.toLowerCase()

        // Generate new code when language changes
        if (isNewTour || languageChanged) {
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

  if (!store.basicInfo.code && selectedLanguageId.value) {
    generateTourCode(selectedLanguageId.value)
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
