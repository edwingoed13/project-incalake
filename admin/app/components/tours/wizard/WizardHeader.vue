<template>
  <header class="sticky top-16 z-50 w-full border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-6 py-4 flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-4">
      <div class="bg-primary/10 p-2 rounded-xl text-primary flex items-center justify-center">
        <span class="material-symbols-outlined text-2xl">public</span>
      </div>
      <div>
        <h2 class="text-xs font-black tracking-widest text-slate-800 dark:text-white uppercase leading-none mb-1">Tour Wizard</h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tracking-tight">Drafting: <span class="text-slate-700 dark:text-slate-200 font-bold">{{ store.basicInfo.title || 'Untitled Tour' }}</span></p>
      </div>
    </div>
    
    <div class="flex items-center gap-6">
      <button 
        @click="cancel"
        class="text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"
      >
        Cancel
      </button>
      
      <button 
        @click="store.saveCurrentProgress"
        :disabled="store.loading"
        class="flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50"
      >
        <span v-if="!store.loading" class="material-symbols-outlined text-lg">save</span>
        <span v-else class="animate-spin text-lg">sync</span>
        {{ store.loading ? 'Saving...' : 'Save Draft' }}
      </button>

      <button 
        @click="store.nextStep"
        v-if="store.currentStep < store.totalSteps"
        class="px-8 py-2.5 text-xs font-black text-white bg-primary rounded-xl shadow-lg shadow-primary/30 hover:shadow-primary/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest"
      >
        Next Step
      </button>
      <button 
        v-else
        class="px-8 py-2.5 text-xs font-black text-white bg-green-600 rounded-xl shadow-lg shadow-green-600/30 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest"
      >
        Publish Tour Now
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useRouter } from 'vue-router'

const store = useTourWizardStore()
const router = useRouter()

const cancel = () => {
  if (confirm('Se perderán los cambios no guardados. ¿Deseas salir?')) {
    router.push('/admin/tours')
  }
}
</script>
