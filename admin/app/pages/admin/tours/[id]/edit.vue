<template>
  <div class="min-h-screen bg-slate-50 dark:bg-background-dark font-display flex flex-col">
    <AdminTopbar title="Tour Wizard" />
    
    <div class="flex flex-1">
      <WizardSidebar />
      
      <!-- Main Content Area -->
      <main class="flex-1 p-8 lg:p-12 overflow-y-auto custom-scrollbar h-[calc(100vh-64px)]">
        <div class="max-w-6xl mx-auto">
          <div class="mb-10">
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
            <Step8Availability v-else-if="store.currentStep === 8" />
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
import { computed, onMounted } from 'vue'

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
import Step8Availability from '~/components/tours/wizard/Step8Availability.vue'

definePageMeta({
  layout: false // Disable the standard admin layout to use the custom Wizard shell
})

const store = useTourWizardStore()
const route = useRoute()

const stepLabels = [
  { id: 1, category: 'Foundation Step', title: 'Basic Information', description: 'Start by providing the essential details of your tour. These fields will be used for initial indexing and customer search filters.' },
  { id: 2, category: 'Content Creation', title: 'Description & SEO', description: 'Craft compelling titles and descriptions to attract travelers and optimize for search engines.' },
  { id: 3, category: 'Itinerary Planning', title: 'Detailed Content', description: 'Outline the daily itinerary, what is included, and specific recommendations for travelers.' },
  { id: 4, category: 'Commercial Rules', title: 'Pricing & Tiers', description: 'Configure price categories by age, nationality, and group size to maximize revenue.' },
  { id: 5, category: 'Visual Assets', title: 'Media Assets', description: 'Upload high-quality photos and videos that showcase the best of your tour experience.' },
  { id: 6, category: 'Workflow Setup', title: 'Booking Options', description: 'Define custom fields and requirements that customers must provide during the booking process.' },
  { id: 7, category: 'Classification', title: 'Categories', description: 'Assign your tour to relevant categories to help users find it through filters.' },
  { id: 8, category: 'Verification', title: 'Final Review', description: 'A complete overview of your tour listing. Check everything carefully before publishing.' },
]

const currentStepLabel = computed(() => {
  return stepLabels.find(s => s.id === store.currentStep) || stepLabels[0]
})

onMounted(async () => {
  if (route.params.id && route.params.id !== 'new') {
    store.setTourId(route.params.id as string)
    await store.fetchTourData(route.params.id as string)
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
