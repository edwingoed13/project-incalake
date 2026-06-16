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
const toast = useToast()
const { confirm } = useConfirm()
const config = useRuntimeConfig()

// Public preview — also exposed in the navbar (below 2xl the insights sidebar
// that normally holds this button is hidden, so testers on tablets/laptops
// couldn't reach it).
const FRONTEND_URL = (config.public as any).frontendUrl || 'https://incalake-frontend.vercel.app'
const slugifyCity = (name: string) => (name || '')
  .toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '')
  .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-')
const previewLang = computed(() => {
  const langs = [store.currentLanguage || 'es', 'es', 'en', 'pt', 'fr', 'de', 'it']
  for (const l of langs) {
    if (store.contentSEO?.[l]?.slug) return l
  }
  return ''
})
const previewUrl = computed(() => {
  if (!store.tourId || store.tourId === 'new' || !previewLang.value) return ''
  const slug = (store.contentSEO[previewLang.value].slug || '').trim()
  if (!slug) return ''
  const city = store.basicInfo.citySlug || slugifyCity(store.basicInfo.nearestCity || '') || 'puno'
  return `${FRONTEND_URL}/${previewLang.value}/${city}/${slug}`
})
const previewTour = () => {
  if (!previewUrl.value) {
    toast.add({ title: 'Vista previa no disponible', description: 'Guarda el tour para generar el enlace.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  window.open(previewUrl.value, '_blank', 'noopener,noreferrer')
}

// Pre-warm the public page's Vercel cache as soon as the preview link is known,
// so opening "Vista previa" is a cache HIT (~0.5s) instead of a cold SSR (~3-4s).
let warmedUrl = ''
const warmPreview = () => {
  if (!import.meta.client) return
  const u = previewUrl.value
  if (!u || warmedUrl === u) return
  warmedUrl = u
  fetch(u, { mode: 'no-cors' }).catch(() => {})
}
watch(previewUrl, (u) => { if (u) warmPreview() }, { immediate: true })

// Publish from the bottom nav (last step). Mirrors the sidebar action so the
// flow works below xl too, where the insights sidebar is hidden.
const publishing = ref(false)
const publishTour = async () => {
  const wasPublished = store.basicInfo.status === 'published'
  const ok = await confirm({
    title: wasPublished ? 'Actualizar publicación' : 'Publicar tour',
    description: wasPublished
      ? '¿Confirmas la actualización? Los cambios serán visibles en el sitio público inmediatamente.'
      : '¿Confirmas publicar este tour? Será visible en el sitio público inmediatamente.',
    confirmLabel: wasPublished ? 'Actualizar' : 'Publicar',
    confirmColor: 'success',
    icon: 'i-lucide-rocket',
    iconColor: 'success',
  })
  if (!ok) return
  publishing.value = true
  try {
    store.basicInfo.status = 'published'
    await store.saveCurrentProgress()
    if (!store.isDirty) {
      toast.add({
        title: wasPublished ? 'Tour actualizado' : 'Tour publicado',
        description: 'Ya es visible en el sitio público.',
        icon: 'i-lucide-rocket',
        color: 'success',
      })
    }
  } finally {
    publishing.value = false
  }
}

// Remember the last focused input + scroll position per tour, so F5 keeps the user editing in place.
const focusKey = computed(() => `wizard:focus:${route.params.id}:${route.query.lang || 'es'}:${store.currentStep}`)
const { restore: restoreFocus } = useFocusMemory(focusKey.value)

// Track whether the initial fetch has finished, so subsequent saves (which also set
// store.loading) don't unmount the step component and reset scroll/dropdowns/focus.
const hasFetched = ref(false)
const isInitialLoading = computed(() => store.loading && !hasFetched.value)

const stepLabels = [
  { id: 1, category: 'Datos generales', title: 'Información básica', description: 'Comienza con los datos esenciales del tour. Se usan para indexación interna y filtros de búsqueda del cliente.' },
  { id: 2, category: 'Contenido', title: 'Contenido del tour', description: 'Redacta el título público, las descripciones y el itinerario que verá el viajero.' },
  { id: 3, category: 'SEO', title: 'SEO y buscadores', description: 'Optimiza cómo aparece el tour en Google: meta título, meta descripción y URL.' },
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
  // For /new, wipe any state inherited from a previously edited tour.
  // Without this, opening "Nuevo tour" after editing tour 306 shows tour 306
  // pre-filled, and the draft autosave would clone it as a new tour. Must run
  // BEFORE the lang/step setup below so those settings aren't overwritten.
  if (route.params.id === 'new') {
    store.resetWizard()
  }

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
    // Defense-in-depth: never create a draft from empty/leaked state. A real
    // new tour starts with a title; if title is blank, this is either initial
    // mount or stale state we shouldn't persist.
    if (!String(store.basicInfo.title || '').trim()) return
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
      <UDashboardNavbar :ui="{ center: 'flex-1 min-w-0', root: 'gap-2' }">
        <template #leading>
          <UDashboardSidebarCollapse />
          <UBadge color="primary" variant="subtle" size="md" class="ml-1 font-mono font-bold shrink-0 hidden sm:inline-flex">
            Tour {{ route.params.id !== 'new' ? '#' + route.params.id : 'nuevo' }}
          </UBadge>
        </template>

        <!-- Steps live in the top bar to save a row of vertical space -->
        <template #default>
          <WizardStepper bare />
        </template>

        <template #right>
          <!-- spin the loader ICON, not the text — animate-spin on the label
               span made the whole "Guardando…" text rotate. -->
          <UBadge
            :color="autosaveColor"
            variant="subtle"
            size="md"
            class="shrink-0"
            :icon="store.autosaving ? 'i-lucide-loader-circle' : (store.isDirty ? 'i-lucide-circle-dot' : 'i-lucide-circle-check')"
            :ui="{ leadingIcon: store.autosaving ? 'animate-spin' : '' }"
          >
            {{ autosaveLabel }}
          </UBadge>
          <!-- Below 2xl the insights sidebar (which holds these) is hidden,
               so keep preview + publish + back reachable here as a fallback. -->
          <UButton
            icon="i-lucide-eye"
            color="neutral"
            variant="ghost"
            class="xl:hidden"
            :disabled="!previewUrl"
            :title="previewUrl || 'Guarda el tour para generar el enlace'"
            aria-label="Vista previa"
            @mouseenter="warmPreview"
            @click="previewTour"
          />
          <UButton
            icon="i-lucide-rocket"
            color="success"
            class="xl:hidden"
            :loading="publishing"
            :disabled="store.loading || store.autosaving"
            @click="publishTour"
          >
            {{ store.basicInfo.status === 'published' ? 'Actualizar' : 'Publicar' }}
          </UButton>
          <UButton
            to="/admin/v2/tours"
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="ghost"
            class="xl:hidden"
          >
            Volver
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex h-full min-h-0">
        <!-- Main content -->
        <main class="flex-1 flex flex-col min-h-0">
          <!-- pb-28 reserves space below the last field so the sticky bottom
               nav bar (~56px) never covers it; scroll-pb-28 makes keyboard /
               programmatic scroll-into-view stop above the bar too. Fixes the
               recurring "dropdown / last input hidden behind the footer". -->
          <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-28 scroll-pb-28">
          <div class="max-w-5xl mx-auto">
            <!-- Step header (the stepper already shows the step/category) -->
            <div class="mb-5">
              <h1 class="text-xl font-bold tracking-tight">{{ currentStepLabel?.title }}</h1>
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
              <Step3DetailedContent v-else-if="store.currentStep === 2" />
              <Step2ContentSEO v-else-if="store.currentStep === 3" />
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
          </div>

          <!-- Bottom navigation (always visible — sidebar is hidden below xl) -->
          <div class="shrink-0 border-t border-default bg-default px-4 lg:px-6 py-3">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
              <UButton
                icon="i-lucide-arrow-left"
                color="neutral"
                variant="ghost"
                :disabled="store.currentStep <= 1"
                @click="store.prevStep"
              >
                Anterior
              </UButton>

              <span class="text-xs text-muted tabular-nums">Paso {{ store.currentStep }} de {{ store.totalSteps }}</span>

              <UButton
                v-if="store.currentStep < store.totalSteps"
                trailing-icon="i-lucide-arrow-right"
                color="primary"
                @click="store.nextStep"
              >
                Siguiente
              </UButton>
              <span v-else class="text-xs text-muted inline-flex items-center gap-1">
                <UIcon name="i-lucide-check" class="size-3.5 text-success" />
                Último paso · revisa y publica
              </span>
            </div>
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
