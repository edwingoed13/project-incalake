<template>
  <div class="min-h-screen bg-slate-50 dark:bg-background-dark font-display flex flex-col">
    <AdminTopbar title="Tour Wizard" />
    
    <div class="flex flex-1">
      <WizardSidebar />
      
      <!-- Main Content Area -->
      <main class="flex-1 p-8 lg:p-12 overflow-y-auto custom-scrollbar h-[calc(100vh-64px)]">
        <div class="max-w-6xl mx-auto">
          <div class="mb-10">
            <NuxtLink to="/admin/tours" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-primary transition-colors mb-4">
              <span class="material-symbols-outlined text-base">arrow_back</span>
              Back to Tours
            </NuxtLink>
            <span class="inline-block px-3 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-widest mb-3">
              {{ currentStepLabel?.category }}
            </span>
            <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight mb-4">
              {{ currentStepLabel?.title }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-lg leading-relaxed">
              {{ currentStepLabel?.description }}
            </p>
          </div>

          <!-- Loading State -->
          <div v-if="store.loading" class="glass-card p-20 rounded-3xl flex flex-col items-center justify-center animate-pulse border border-white/10">
             <div class="w-16 h-16 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-6"></div>
             <p class="text-xl font-bold text-slate-400">Cargando datos del tour...</p>
             <p class="text-sm text-slate-500 mt-2">Estamos conectando con el servidor para traerte la información.</p>
          </div>

          <!-- Step Components -->
          <Transition v-else name="fade" mode="out-in">
            <Step1BasicInfo v-if="store.currentStep === 1" />
            <Step2ContentSEO v-else-if="store.currentStep === 2" />
            <Step3DetailedContent v-else-if="store.currentStep === 3" />
            <Step4CommercialRules v-else-if="store.currentStep === 4" />
            <Step5Multimedia v-else-if="store.currentStep === 5" />
            <Step6BookingOptions v-else-if="store.currentStep === 6" />
            <Step7Categories v-else-if="store.currentStep === 7" />
            <Step8FinalReview v-else-if="store.currentStep === 8" />
            <div v-else class="glass-card p-12 rounded-3xl flex flex-col items-center justify-center text-slate-400 border-dashed">
               <span class="material-symbols-outlined text-6xl mb-4 opacity-20">construction</span>
               <p class="text-xl font-bold">Paso {{ store.currentStep }} en construcción</p>
               <p class="text-sm">Estamos portando el diseño de Stitch para este módulo.</p>
               <button @click="store.prevStep" class="mt-6 text-primary font-bold hover:underline">Regresar al paso anterior</button>
            </div>
          </Transition>
        </div>
      </main>

      <WizardInsightsSidebar />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useRoute } from 'vue-router'
import { computed, onMounted, onBeforeUnmount, watch } from 'vue'

// Import components directly to avoid auto-import issues during development if needed
import AdminTopbar from '~/components/admin/AdminTopbar.vue'
import WizardSidebar from '~/components/tours/wizard/WizardSidebar.vue'
import WizardInsightsSidebar from '~/components/tours/wizard/WizardInsightsSidebar.vue'
import Step1BasicInfo from '~/components/tours/wizard/Step1BasicInfo.vue'
import Step2ContentSEO from '~/components/tours/wizard/Step2ContentSEO.vue'
import Step3DetailedContent from '~/components/tours/wizard/Step3DetailedContent.vue'
import Step4CommercialRules from '~/components/tours/wizard/Step4CommercialRules.vue'
import Step5Multimedia from '~/components/tours/wizard/Step5Multimedia.vue'
import Step6BookingOptions from '~/components/tours/wizard/Step6BookingOptions.vue'
import Step7Categories from '~/components/tours/wizard/Step7Categories.vue'
import Step8FinalReview from '~/components/tours/wizard/Step8FinalReview.vue'

definePageMeta({
  layout: false // Disable the standard admin layout to use the custom Wizard shell
})

const store = useTourWizardStore()
const route = useRoute()

const stepLabels = [
  { id: 1, category: 'Datos generales', title: 'Información básica', description: 'Comienza con los datos esenciales del tour. Se usan para indexación interna y filtros de búsqueda del cliente.' },
  { id: 2, category: 'Contenido', title: 'Descripción y SEO', description: 'Crea títulos y descripciones atractivas para captar viajeros y posicionar en buscadores.' },
  { id: 3, category: 'Itinerario', title: 'Contenido detallado', description: 'Define el itinerario, qué incluye y qué no, y recomendaciones específicas para viajeros.' },
  { id: 4, category: 'Reglas comerciales', title: 'Precios y rangos', description: 'Configura precios por etapa de edad, nacionalidad y cantidad de pasajeros.' },
  { id: 5, category: 'Multimedia', title: 'Galería y video', description: 'Sube fotos de calidad y un video que muestren lo mejor de la experiencia.' },
  { id: 6, category: 'Reservas', title: 'Opciones de reserva', description: 'Define políticas, anticipación, datos requeridos, recojo, guía y otras reglas de la reserva.' },
  { id: 7, category: 'Clasificación', title: 'Categorías y etiquetas', description: 'Asigna categorías y etiquetas para que los viajeros encuentren el tour mediante filtros.' },
  { id: 8, category: 'Publicar', title: 'Revisión final', description: 'Resumen del tour. Revisa cada paso y publica. La disponibilidad (calendario, bloqueos, ofertas) se gestiona aparte.' },
]

const currentStepLabel = computed(() => {
  return stepLabels.find(s => s.id === store.currentStep) || stepLabels[0]
})

onMounted(async () => {
  // Set the language FIRST, before fetching data, so Step1 respects it
  const langParam = (route.query.lang as string)?.toLowerCase()
  if (langParam) {
    store.currentLanguage = langParam
  }

  if (route.params.id && route.params.id !== 'new') {
    store.setTourId(route.params.id as string)
    await store.fetchTourData(route.params.id as string)

    // Re-apply after fetch (fetchTourData may override currentLanguage)
    if (langParam) {
      store.currentLanguage = langParam
    }
  }
})

// Autosave — debounce 2s after the last change. Skips while a save is already
// running (the store guards against concurrent saves) and on brand-new tours.
let autosaveTimer: ReturnType<typeof setTimeout> | null = null
watch(() => store.isDirty, (dirty) => {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  if (!dirty) return
  if (!store.tourId || store.tourId === 'new') return
  autosaveTimer = setTimeout(() => {
    store.autosave()
    autosaveTimer = null
  }, 2000)
})

onBeforeUnmount(() => {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
