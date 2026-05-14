<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useRoute, useRouter } from 'vue-router'
import { computed, onMounted, onBeforeUnmount, watch, ref } from 'vue'

import WizardStepper from '~/components/v2/WizardStepper.vue'
import WizardInsightsSidebar from '~/components/tours/wizard/WizardInsightsSidebar.vue'
import Step1BasicInfo from '~/components/tours/wizard/Step1BasicInfo.vue'
import Step2ContentSEO from '~/components/tours/wizard/Step2ContentSEO.vue'
import Step3DetailedContent from '~/components/tours/wizard/Step3DetailedContent.vue'
import Step4CommercialRules from '~/components/tours/wizard/Step4CommercialRules.vue'
import Step5Multimedia from '~/components/tours/wizard/Step5Multimedia.vue'
import Step6BookingOptions from '~/components/tours/wizard/Step6BookingOptions.vue'
import Step7Categories from '~/components/tours/wizard/Step7Categories.vue'
import Step8Availability from '~/components/tours/wizard/Step8Availability.vue'
import Step8FinalReview from '~/components/tours/wizard/Step8FinalReview.vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

const store = useTourWizardStore()
const route = useRoute()
const router = useRouter()

// Remember the last focused input + scroll position per tour, so F5 keeps the user editing in place.
const focusKey = computed(() => `wizard:focus:${route.params.id}:${route.query.lang || 'es'}:${store.currentStep}`)
const { restore: restoreFocus } = useFocusMemory(focusKey.value)

// Track whether the initial fetch has finished, so subsequent saves (which also set
// store.loading) don't unmount the step component and reset scroll/dropdowns/focus.
const hasFetched = ref(false)
const isInitialLoading = computed(() => store.loading && !hasFetched.value)

const stepLabels = [
  { id: 1, category: 'Datos generales', title: 'Información básica', description: 'Comienza con los datos esenciales del tour. Se usan para indexación interna y filtros de búsqueda del cliente.' },
  { id: 2, category: 'Contenido', title: 'Descripción y SEO', description: 'Crea títulos y descripciones atractivas para captar viajeros y posicionar en buscadores.' },
  { id: 3, category: 'Itinerario', title: 'Contenido detallado', description: 'Define el itinerario, qué incluye y qué no, y recomendaciones específicas para viajeros.' },
  { id: 4, category: 'Reglas comerciales', title: 'Precios y rangos', description: 'Configura precios por etapa de edad, nacionalidad y cantidad de pasajeros.' },
  { id: 5, category: 'Multimedia', title: 'Galería y video', description: 'Sube fotos de calidad y un video que muestren lo mejor de la experiencia.' },
  { id: 6, category: 'Reservas', title: 'Opciones de reserva', description: 'Define políticas, anticipación, datos requeridos, recojo, guía y otras reglas de la reserva.' },
  { id: 7, category: 'Clasificación', title: 'Categorías y etiquetas', description: 'Asigna categorías y etiquetas para que los viajeros encuentren el tour mediante filtros.' },
  { id: 8, category: 'Operación', title: 'Disponibilidad y calendario', description: 'Define fechas activas, bloqueos por temporada baja, ofertas y restricciones de capacidad.' },
  { id: 9, category: 'Publicar', title: 'Revisión final', description: 'Resumen del tour. Revisa cada paso y publica.' },
]

const currentStepLabel = computed(() => stepLabels.find(s => s.id === store.currentStep) || stepLabels[0])

const autosaveLabel = computed(() => {
  if (store.autosaving) return 'Guardando...'
  if (store.lastSavedAt) return `Guardado · ${new Date(store.lastSavedAt).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
  if (store.isDirty) return 'Cambios sin guardar'
  return 'Todo guardado'
})
const autosaveColor = computed<'warning' | 'success' | 'info' | 'neutral'>(() => {
  if (store.autosaving) return 'info'
  if (store.isDirty) return 'warning'
  if (store.lastSavedAt) return 'success'
  return 'neutral'
})

const lastStepKey = computed(() => `wizard:lastStep:${route.params.id}`)

onMounted(async () => {
  const langParam = (route.query.lang as string)?.toLowerCase()
  if (langParam) {
    store.currentLanguage = langParam
  }

  // Restore step: ?step=N wins over localStorage (so direct links still work),
  // otherwise fall back to the last step the user was on for this tour.
  const stepParam = parseInt(String(route.query.step || ''), 10)
  if (Number.isFinite(stepParam) && stepParam >= 1 && stepParam <= store.totalSteps) {
    store.currentStep = stepParam
  } else if (route.params.id && route.params.id !== 'new') {
    const remembered = parseInt(String(localStorage.getItem(lastStepKey.value) || ''), 10)
    if (Number.isFinite(remembered) && remembered >= 1 && remembered <= store.totalSteps) {
      store.currentStep = remembered
    }
  }

  if (route.params.id && route.params.id !== 'new') {
    store.setTourId(route.params.id as string)
    await store.fetchTourData(route.params.id as string)

    if (langParam) {
      store.currentLanguage = langParam
    }
  }

  // Mark initial load as done — from now on, store.loading transitions are saves, not fetches,
  // so the step component shouldn't unmount.
  hasFetched.value = true

  // Restore focus + scroll after data + DOM are settled
  await restoreFocus()
})

// Sync current step → URL query param + localStorage. Uses router.replace so it doesn't pollute browser history.
watch(() => store.currentStep, (newStep) => {
  if (route.params.id && route.params.id !== 'new') {
    try { localStorage.setItem(lastStepKey.value, String(newStep)) } catch { /* quota or disabled */ }
  }
  const current = parseInt(String(route.query.step || ''), 10)
  if (current === newStep) return
  router.replace({ query: { ...route.query, step: String(newStep) } })
})

let autosaveTimer: ReturnType<typeof setTimeout> | null = null
let firstSaveInFlight = false

watch(() => store.isDirty, (dirty) => {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  if (!dirty) return

  // New tour: first dirty change triggers a debounced "create draft" save.
  // After it succeeds, the store has a real tourId and we update the URL so
  // F5 / direct link can resume editing.
  const isNew = !store.tourId || store.tourId === 'new'
  if (isNew) {
    if (firstSaveInFlight) return
    autosaveTimer = setTimeout(async () => {
      autosaveTimer = null
      firstSaveInFlight = true
      try {
        await store.saveCurrentProgress({ silent: true })
        const newId = store.tourId
        if (newId && newId !== 'new') {
          await router.replace({
            path: `/admin/v2/tours/${newId}/edit`,
            query: route.query,
          })
        }
      } finally {
        firstSaveInFlight = false
      }
    }, 1500)
    return
  }

  // Existing tour: regular debounced autosave
  autosaveTimer = setTimeout(() => {
    store.autosave()
    autosaveTimer = null
  }, 2000)
})

// Warn before leaving with unsaved changes (only fires on actual close/refresh,
// not on internal Vue route changes).
const onBeforeUnload = (e: BeforeUnloadEvent) => {
  if (store.isDirty || firstSaveInFlight) {
    e.preventDefault()
    // Modern browsers ignore the message but require returnValue to be set.
    e.returnValue = ''
  }
}

// Ctrl+S / Cmd+S manual save
const onKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault()
    if (store.tourId && store.tourId !== 'new') {
      store.saveCurrentProgress()
    }
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', onBeforeUnload)
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  window.removeEventListener('beforeunload', onBeforeUnload)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <UDashboardPanel id="tour-editor-v2">
    <template #header>
      <UDashboardNavbar :title="`Tour ${route.params.id !== 'new' ? '#' + route.params.id : 'nuevo'}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UBadge :color="autosaveColor" variant="subtle" size="md" :icon="store.autosaving ? 'i-lucide-loader-circle' : (store.isDirty ? 'i-lucide-circle-dot' : 'i-lucide-circle-check')">
            <span :class="{ 'animate-spin': store.autosaving }" class="inline-flex">{{ autosaveLabel }}</span>
          </UBadge>
          <UButton
            to="/admin/v2/tours"
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="ghost"
          >
            Volver
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <!-- Horizontal stepper -->
      <WizardStepper />

      <div class="flex h-full">
        <!-- Main content -->
        <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
          <div class="max-w-5xl mx-auto">
            <!-- Step header -->
            <div class="mb-5">
              <UBadge color="primary" variant="subtle" size="sm" class="mb-2">{{ currentStepLabel?.category }}</UBadge>
              <h1 class="text-2xl font-bold tracking-tight">{{ currentStepLabel?.title }}</h1>
              <p class="text-sm text-muted leading-snug mt-1">{{ currentStepLabel?.description }}</p>
            </div>

            <!-- Loading (only during initial fetch — saves use the autosave badge in the navbar) -->
            <div v-if="isInitialLoading" class="flex flex-col items-center justify-center py-20">
              <UIcon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin mb-3" />
              <p class="text-sm font-bold text-muted">Cargando datos del tour...</p>
            </div>

            <!-- Step components -->
            <Transition v-else name="fade" mode="out-in">
              <Step1BasicInfo v-if="store.currentStep === 1" />
              <Step2ContentSEO v-else-if="store.currentStep === 2" />
              <Step3DetailedContent v-else-if="store.currentStep === 3" />
              <Step4CommercialRules v-else-if="store.currentStep === 4" />
              <Step5Multimedia v-else-if="store.currentStep === 5" />
              <Step6BookingOptions v-else-if="store.currentStep === 6" />
              <Step7Categories v-else-if="store.currentStep === 7" />
              <Step8Availability v-else-if="store.currentStep === 8" />
              <Step8FinalReview v-else-if="store.currentStep === 9" />
              <UCard v-else>
                <div class="flex flex-col items-center text-center py-12 gap-3">
                  <UIcon name="i-lucide-hammer" class="size-12 text-muted" />
                  <p class="text-base font-bold">Paso {{ store.currentStep }} en construcción</p>
                  <UButton variant="ghost" size="sm" @click="store.prevStep">Regresar al paso anterior</UButton>
                </div>
              </UCard>
            </Transition>
          </div>
        </main>

        <!-- Insights sidebar -->
        <WizardInsightsSidebar />
      </div>
    </template>
  </UDashboardPanel>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
