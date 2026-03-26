<template>
  <aside class="w-72 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 hidden lg:flex flex-col gap-8 sticky top-32 h-[calc(100vh-128px)] overflow-y-auto custom-scrollbar">
    <div>
      <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-6">Progress ({{ store.currentStep }}/{{ store.totalSteps }})</h3>
      <nav class="flex flex-col gap-1">
        <button 
          v-for="step in steps" 
          :key="step.id"
          @click="store.goToStep(step.id)"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group w-full text-left"
          :class="[
            store.currentStep === step.id 
              ? 'bg-primary/10 text-primary font-semibold shadow-sm' 
              : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'
          ]"
        >
          <span class="material-symbols-outlined text-[20px]" :class="store.currentStep === step.id ? 'fill-1' : ''">
            {{ step.icon }}
          </span>
          <span class="text-sm">{{ step.label }}</span>
        </button>
      </nav>
    </div>
    
    <div class="mt-auto p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
      <div class="flex items-center gap-2 mb-3">
        <span class="material-symbols-outlined text-primary text-xl">help</span>
        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">¿Necesitas ayuda?</p>
      </div>
      <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">Nuestros expertos están disponibles 24/7 para ayudarte con tu tour.</p>
      <button class="mt-3 w-full py-2 text-[11px] font-bold text-primary border border-primary/20 bg-white dark:bg-slate-900 rounded-lg hover:bg-primary/5 transition-colors">
        Contact Support
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'

const store = useTourWizardStore()

const steps = [
  { id: 1, label: 'Basic Information', icon: 'info' },
  { id: 2, label: 'Description & SEO', icon: 'description' },
  { id: 3, label: 'Detailed Content', icon: 'map' },
  { id: 4, label: 'Pricing Rules', icon: 'payments' },
  { id: 5, label: 'Media Assets', icon: 'image' },
  { id: 6, label: 'Booking Options', icon: 'settings' },
  { id: 7, label: 'Categories', icon: 'verified' },
  { id: 8, label: 'Final Review', icon: 'check_circle' },
]
</script>
