<template>
  <div class="flex flex-col gap-6">
    <!-- Section: Basic Information -->
    <WizardSection title="Datos del tour" icon="i-lucide-info">
      <template v-if="isEditMode" #actions>
        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-primary/10 text-primary text-xs font-bold">
          <span class="text-base leading-none">{{ currentLangFlag }}</span>
          <span>{{ currentLangName }}</span>
          <span class="text-primary/60 font-mono">·</span>
          <span class="font-mono">{{ store.basicInfo.code }}</span>
        </div>
      </template>

      <!-- Translation pills + shared-fields hint (edit mode) -->
      <div v-if="isEditMode && tourTranslationCodes.length > 1" class="flex items-center gap-1.5 mb-4">
        <UBadge
          v-for="lang in tourTranslationCodes"
          :key="lang"
          :color="lang === store.currentLanguage ? 'primary' : 'neutral'"
          :variant="lang === store.currentLanguage ? 'solid' : 'subtle'"
          size="sm"
          class="uppercase"
        >
          {{ lang }}
        </UBadge>
        <UPopover :ui="{ content: 'w-72 max-w-[90vw]' }">
          <UButton
            icon="i-lucide-info"
            color="warning"
            variant="ghost"
            size="xs"
            square
            class="ml-1"
            aria-label="Información de campos compartidos"
          />
          <template #content>
            <div class="p-3 space-y-1.5">
              <p class="text-xs font-bold text-warning flex items-center gap-1.5">
                <UIcon name="i-lucide-info" class="size-3.5" />
                Campos compartidos
              </p>
              <p class="text-xs text-default leading-snug">
                Estos campos aplican a <strong>todos los idiomas</strong> del tour. Si cambias uno aquí, el cambio se refleja en cada traducción.
              </p>
            </div>
          </template>
        </UPopover>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
        <!-- Idioma Principal (solo al crear un tour nuevo) -->
        <template v-if="!isEditMode">
          <UFormField label="Idioma principal del tour" hint="Selecciona primero para generar el código" class="md:col-span-2" required>
            <USelect
              v-model="selectedLanguageId"
              :items="languageItems"
              placeholder="Selecciona idioma"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Código del tour" hint="Generado automáticamente" required>
            <UInput
              v-model="store.basicInfo.code"
              placeholder="ES000"
              readonly
              icon="i-lucide-hash"
              class="w-full font-mono"
            >
              <template #trailing>
                <UIcon v-if="store.basicInfo.code" name="i-lucide-check-circle" class="size-4 text-success" />
              </template>
            </UInput>
          </UFormField>
        </template>

        <!-- Ciudad de Salida -->
        <UFormField label="Ciudad de salida" :hint="googleFailed ? 'Busca y selecciona' : 'Busca con Google'" required>
          <!-- Google Places search; if Maps can't load/authenticate we swap to
               the DB catalog select, which always works. Either way the pick
               is resolved to a cities.id via /admin/cities/resolve. -->
          <UInput
            v-if="!googleFailed"
            :ref="setCityInputRef"
            v-model="cityQuery"
            placeholder="Ej: Puno, Cusco, Arequipa..."
            :icon="resolvingCity ? 'i-lucide-loader-circle' : 'i-lucide-map-pin'"
            :ui="{ leadingIcon: resolvingCity ? 'animate-spin' : '' }"
            class="w-full"
          />
          <USelectMenu
            v-else
            v-model="selectedCity"
            :items="cityResults"
            by="id"
            label-key="name"
            description-key="country_code"
            :filter-fields="['name']"
            :loading="isLoadingCities"
            icon="i-lucide-map-pin"
            :search-input="{ placeholder: 'Ej: Puno, Cusco...', icon: 'i-lucide-search' }"
            placeholder="Selecciona la ciudad"
            class="w-full"
          />
          <template #help>
            <span v-if="cityResolveError" class="text-error font-semibold">
              <UIcon name="i-lucide-circle-alert" class="size-3 inline align-middle" />
              {{ cityResolveError }}
            </span>
            <span v-else-if="store.basicInfo.nearestCity" class="text-success font-semibold">
              <UIcon name="i-lucide-check-circle" class="size-3 inline align-middle" />
              {{ store.basicInfo.nearestCity }}
            </span>
          </template>
        </UFormField>

        <!-- Tipo de Servicio -->
        <UFormField label="Tipo de servicio" required>
          <USelect
            v-model="store.basicInfo.serviceType"
            :items="serviceTypeItems"
            class="w-full"
          />
        </UFormField>

        <!-- Dificultad -->
        <UFormField label="Dificultad" required>
          <USelect
            v-model="store.basicInfo.difficulty"
            :items="difficultyItems"
            class="w-full"
          />
        </UFormField>

        <!-- Capacidad Máxima -->
        <UFormField label="Capacidad máxima" hint="Personas por reserva" required>
          <UInputNumber
            v-model="store.basicInfo.capacityMax"
            :min="1"
            :max="999"
            class="w-full"
          />
        </UFormField>
      </div>
    </WizardSection>

    <!-- Section: Schedules & Duration -->
    <WizardSection title="Horarios y duración" icon="i-lucide-calendar-clock">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
        <!-- Horarios de Salida (múltiples, con duración) -->
        <div class="md:col-span-2 space-y-3">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-sm font-semibold">
                Horarios de salida y duración <span class="text-primary">*</span>
              </p>
              <p class="text-[11px] text-muted mt-0.5">Combina D + H + Min (ej. 2D 8H, o 0D 2H 30M)</p>
            </div>
            <UButton
              icon="i-lucide-plus-circle"
              color="primary"
              variant="link"
              size="sm"
              @click="addDepartureTime"
            >
              Agregar horario
            </UButton>
          </div>

          <div class="space-y-2">
            <!-- Mobile (<sm): time gets its own full-width row, then
                 days/hours/minutes + delete sit in a 4-col row below.
                 sm+: the original single 5-col row. The rigid 5-col grid
                 squeezed a time picker + 3 steppers + a button into ~360px
                 on phones. -->
            <div
              v-for="(item, idx) in store.basicInfo.startTimes"
              :key="idx"
              class="grid grid-cols-[repeat(3,minmax(0,1fr))_auto] sm:grid-cols-[1.6fr_repeat(3,minmax(0,1fr))_auto] gap-2 items-end"
            >
              <UFormField :label="idx === 0 ? 'Hora de salida' : undefined" class="col-span-full sm:col-span-1" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
                <UInput
                  v-model="item.time"
                  type="time"
                  icon="i-lucide-clock"
                  class="w-full"
                  @input="syncPrimaryStartTime"
                />
              </UFormField>

              <UFormField :label="idx === 0 ? 'Días' : undefined" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted text-center block' }">
                <UInputNumber
                  v-model="item.days"
                  :min="0"
                  :max="30"
                  class="w-full"
                  @update:model-value="syncPrimaryDuration"
                />
              </UFormField>

              <UFormField :label="idx === 0 ? 'Horas' : undefined" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted text-center block' }">
                <UInputNumber
                  v-model="item.hours"
                  :min="0"
                  :max="23"
                  class="w-full"
                  @update:model-value="syncPrimaryDuration"
                />
              </UFormField>

              <UFormField :label="idx === 0 ? 'Minutos' : undefined" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted text-center block' }">
                <UInputNumber
                  v-model="item.minutes"
                  :min="0"
                  :max="59"
                  :step="5"
                  class="w-full"
                  @update:model-value="syncPrimaryDuration"
                />
              </UFormField>

              <div class="flex items-center justify-end">
                <UButton
                  v-if="store.basicInfo.startTimes.length > 1"
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  size="sm"
                  :title="`Eliminar horario ${idx + 1}`"
                  @click="removeDepartureTime(idx)"
                />
                <div v-else class="size-8" />
              </div>
            </div>
          </div>

          <p class="text-[10px] text-muted">El primer horario se usa como principal.</p>
        </div>

        <!-- Zona Horaria -->
        <UFormField label="Zona horaria" required>
          <USelect
            v-model="store.basicInfo.timezone"
            :items="timezoneItems"
            class="w-full"
          />
        </UFormField>

        <!-- Active Toggle -->
        <UFormField label="Estado">
          <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-elevated">
            <USwitch v-model="isTourActive" color="primary" />
            <span class="text-sm font-semibold">Tour activo</span>
          </div>
        </UFormField>
      </div>
    </WizardSection>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useTourWizardStore } from '~/stores/tourWizard'
import WizardSection from './WizardSection.vue'

const store = useTourWizardStore()

// Items para los USelect (Nuxt UI espera { label, value })
const serviceTypeItems = [
  { label: 'Tour', value: 'tour' },
  { label: 'Paquete', value: 'package' },
  { label: 'Experiencia', value: 'experience' },
  { label: 'Transporte', value: 'transport' },
]

const difficultyItems = [
  { label: 'Fácil', value: 'easy' },
  { label: 'Moderado', value: 'moderate' },
  { label: 'Difícil', value: 'hard' },
]

const timezoneItems = [
  { label: 'Hora Peruana (GMT-5)', value: 'America/Lima' },
  { label: 'Hora Boliviana (GMT-4)', value: 'America/La_Paz' },
]
const config = useRuntimeConfig()
const defaultApiUrl = config.public.apiUrl

// Convert any legacy {duration, durationUnit} into {days, hours, minutes}
const splitLegacyDuration = (qty: number, unit: string) => {
  if (unit === 'days') return { days: Math.floor(qty), hours: 0, minutes: 0 }
  if (unit === 'minutes') return { days: 0, hours: Math.floor(qty / 60), minutes: qty % 60 }
  // hours (allow fractional like 2.5 -> 2h 30m)
  const h = Math.floor(qty)
  return { days: 0, hours: h, minutes: Math.round((qty - h) * 60) }
}

const ensureBreakdown = (t: any) => {
  if (t && (t.days != null || t.hours != null || t.minutes != null)) {
    return {
      days: Number(t.days) || 0,
      hours: Number(t.hours) || 0,
      minutes: Number(t.minutes) || 0,
    }
  }
  return splitLegacyDuration(Number(t?.duration) || 0, t?.durationUnit || 'hours')
}

// Ensure startTimes array exists (migration from single startTime)
if (!Array.isArray(store.basicInfo.startTimes) || store.basicInfo.startTimes.length === 0) {
  const fallback = splitLegacyDuration(store.basicInfo.duration || 0, store.basicInfo.durationUnit || 'hours')
  store.basicInfo.startTimes = [{
    time: store.basicInfo.startTime || '08:00',
    duration: store.basicInfo.duration || 1,
    durationUnit: store.basicInfo.durationUnit || 'hours',
    days: store.basicInfo.durationDays ?? fallback.days,
    hours: store.basicInfo.durationHours ?? fallback.hours,
    minutes: store.basicInfo.durationMinutes ?? fallback.minutes,
  }]
} else {
  // Migrate old string[] format to object[] format and ensure D/H/M present
  store.basicInfo.startTimes = store.basicInfo.startTimes.map((t: any) => {
    if (typeof t === 'string') {
      const parts = splitLegacyDuration(store.basicInfo.duration || 0, store.basicInfo.durationUnit || 'hours')
      return {
        time: t,
        duration: store.basicInfo.duration || 1,
        durationUnit: store.basicInfo.durationUnit || 'hours',
        ...parts,
      }
    }
    const parts = ensureBreakdown(t)
    return {
      time: t.time || '08:00',
      duration: Number(t.duration) || 1,
      durationUnit: t.durationUnit || 'hours',
      ...parts,
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
    days: last?.days ?? 0,
    hours: last?.hours ?? 1,
    minutes: last?.minutes ?? 0,
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
    const days = Number(first.days) || 0
    const hours = Number(first.hours) || 0
    const minutes = Number(first.minutes) || 0
    store.basicInfo.durationDays = days
    store.basicInfo.durationHours = hours
    store.basicInfo.durationMinutes = minutes
    // Pick the most-significant unit for the legacy fields
    if (days > 0) {
      store.basicInfo.duration = days
      store.basicInfo.durationUnit = 'days'
    } else if (hours > 0) {
      store.basicInfo.duration = hours
      store.basicInfo.durationUnit = 'hours'
    } else {
      store.basicInfo.duration = minutes
      store.basicInfo.durationUnit = 'minutes'
    }
    return
  }
  // Below this point the original function continues using `first` which is now null —
  // skip the legacy lines below by exiting early.
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

// Items derivados para el USelect de idioma
const languageItems = computed(() =>
  availableLanguages.value.map((lang: any) => ({
    label: `${lang.country} (${lang.code.toUpperCase()})`,
    value: lang.id,
  }))
)

// City state. Primary UX: Google Places autocomplete over any city; the pick
// is resolved to a cities.id via /admin/cities/resolve (find-or-create),
// because public /[city]/[slug] URLs hang off the catalog. If Maps fails to
// load or authenticate (restricted key, offline), we fall back to a
// USelectMenu over the DB catalog, which always works.
const cityResults = ref<any[]>([])
const isLoadingCities = ref(false)
const cityQuery = ref('')
const googleFailed = ref(false)
const resolvingCity = ref(false)
const cityResolveError = ref('')

const { initCityAutocomplete, cleanup } = useGooglePlaces()

const cityInputRef = ref<HTMLInputElement | null>(null)
// Resolve the native <input> from inside UInput's Vue instance (Nuxt UI v4
// wraps it in a div), then attach the Places autocomplete to it.
const setCityInputRef = (el: any) => {
  if (!el) {
    cityInputRef.value = null
    return
  }
  const native = el.inputRef?.value || el.$el?.querySelector?.('input') || (el.tagName === 'INPUT' ? el : null)
  if (native && native !== cityInputRef.value) {
    cityInputRef.value = native
    attachCityAutocomplete()
  }
}

async function attachCityAutocomplete() {
  const input = cityInputRef.value
  if (!input) return
  try {
    const instance = await initCityAutocomplete(input, onPlacePicked)
    if (!instance && !input.hasAttribute('data-autocomplete-initialized')) {
      googleFailed.value = true
    }
  } catch {
    googleFailed.value = true
  }
}

async function onPlacePicked(placeData: any) {
  const name = placeData.cityName || placeData.formatted_address
  if (!name) return
  cityQuery.value = placeData.countryName ? `${name}, ${placeData.countryName}` : name
  resolvingCity.value = true
  cityResolveError.value = ''
  try {
    const res: any = await $fetch(`${defaultApiUrl}/admin/cities/resolve`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}` },
      body: {
        name,
        country_code: placeData.countryCode || null,
        latitude: placeData.lat ?? null,
        longitude: placeData.lng ?? null,
      },
    })
    const city = res?.data
    if (city?.id) {
      store.basicInfo.cityId = city.id
      store.basicInfo.nearestCity = city.name
      if (city.latitude && city.longitude) {
        store.basicInfo.cityCoordinates = { lat: parseFloat(city.latitude), lng: parseFloat(city.longitude) }
      }
      if (!cityResults.value.some((c: any) => c.id === city.id)) cityResults.value.push(city)
    }
  } catch (error) {
    console.error('Error resolving city:', error)
    cityResolveError.value = 'No se pudo registrar la ciudad. Intenta de nuevo o elige de la lista.'
    googleFailed.value = false
  } finally {
    resolvingCity.value = false
  }
}

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
    const response: any = await $fetch(`${defaultApiUrl}/admin/tours/generate-code?language_id=${langId}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}` },
    })
    if (response.success) {
      store.basicInfo.code = response.data.code
    }
  } catch (error) {
    console.error('Error generating tour code:', error)
  }
}

// Departure city comes from our own /cities table. This field must resolve to
// a DB city (cityId drives the public /[city]/[slug] URLs), so Google Places
// is the wrong source here — and with a referer-restricted key it also
// silently returned no predictions while blocking the old fallback.
const fetchCities = async () => {
  isLoadingCities.value = true
  try {
    const response: any = await $fetch(`${defaultApiUrl}/cities?active=true&per_page=100`)
    if (response.success) {
      cityResults.value = response.data
    }
  } catch (error) {
    console.error('Error fetching cities:', error)
  } finally {
    isLoadingCities.value = false
  }
}

// Bridge between the store (id + name) and USelectMenu's object model.
const selectedCity = computed({
  get: () => {
    const byId = cityResults.value.find(c => c.id === store.basicInfo.cityId)
    if (byId) return byId
    return store.basicInfo.nearestCity
      ? { id: store.basicInfo.cityId ?? 0, name: store.basicInfo.nearestCity, country_code: '' }
      : undefined
  },
  set: (city: any) => {
    if (!city) return
    store.basicInfo.nearestCity = city.name
    store.basicInfo.cityId = city.id
    if (city.latitude && city.longitude) {
      store.basicInfo.cityCoordinates = {
        lat: parseFloat(city.latitude),
        lng: parseFloat(city.longitude),
      }
    }
  },
})

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

  fetchCities()
  cityQuery.value = store.basicInfo.nearestCity || ''

  // Google Maps reports invalid/restricted keys through this global hook —
  // the script "loads" fine, so it's the only reliable failure signal.
  ;(window as any).gm_authFailure = () => { googleFailed.value = true }

  if (!store.basicInfo.code && selectedLanguageId.value) {
    generateTourCode(selectedLanguageId.value)
  }
})

// Edit mode loads the tour after mount — reflect the stored city in the input.
watch(() => store.basicInfo.nearestCity, (name) => {
  if (name && !cityQuery.value) cityQuery.value = name
})

onUnmounted(() => {
  if (cityInputRef.value) cleanup(cityInputRef.value)
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
