<template>
  <aside class="w-80 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 hidden xl:flex flex-col gap-8 sticky top-16 h-[calc(100vh-64px)] overflow-y-auto custom-scrollbar">
    <!-- Wizard Actions -->
    <div class="flex flex-col gap-3 pb-6 border-b border-slate-100 dark:border-slate-800">
      <button
        @click="store.nextStep"
        v-if="store.currentStep < store.totalSteps"
        class="w-full py-3 text-xs font-black text-white bg-primary rounded-xl shadow-lg shadow-primary/30 hover:shadow-primary/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest flex items-center justify-center gap-2"
      >
        Next Step
        <span class="material-symbols-outlined text-sm">arrow_forward</span>
      </button>
      <button
        v-else
        @click="publishTour"
        :disabled="store.loading"
        class="w-full py-3 text-xs font-black text-white bg-green-600 rounded-xl shadow-lg shadow-green-600/30 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-50"
      >
        <span v-if="!store.loading" class="material-symbols-outlined text-sm">rocket_launch</span>
        <span v-else class="animate-spin text-sm">sync</span>
        {{ store.basicInfo.status === 'published' ? 'Update Published Tour' : 'Publish Tour Now' }}
      </button>

      <button
        @click="previewTour"
        :disabled="!previewUrl"
        :title="previewUrl || 'Guarda el tour para generar el slug y poder previsualizar'"
        class="w-full flex items-center justify-center gap-2 py-2.5 text-[10px] font-black uppercase tracking-wider text-primary bg-primary/5 hover:bg-primary/10 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all border border-primary/20"
      >
        <span class="material-symbols-outlined text-base">visibility</span>
        Preview Tour
        <span class="material-symbols-outlined text-sm">open_in_new</span>
      </button>

      <div class="grid grid-cols-2 gap-3">
        <button
          @click="store.saveCurrentProgress"
          :disabled="store.loading"
          class="flex items-center justify-center gap-2 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50"
        >
          <span v-if="!store.loading" class="material-symbols-outlined text-base">save</span>
          <span v-else class="animate-spin text-base">sync</span>
          Save
        </button>
        <button
          @click="cancel"
          class="flex items-center justify-center gap-2 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl transition-all"
        >
          <span class="material-symbols-outlined text-base">close</span>
          Cancel
        </button>
      </div>

      <p v-if="store.basicInfo.status" class="text-center text-[10px] font-bold uppercase tracking-widest" :class="{
        'text-emerald-500': store.basicInfo.status === 'published',
        'text-amber-500': store.basicInfo.status === 'draft',
        'text-slate-400': store.basicInfo.status === 'archived',
      }">
        Status: {{ store.basicInfo.status }}
      </p>
    </div>

    <div class="space-y-4">
      <div class="flex justify-between items-end">
        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Listing Quality</h4>
        <span class="text-lg font-black text-primary">{{ qualityScore }}%</span>
      </div>
      <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
        <div class="bg-primary h-full rounded-full transition-all duration-1000" :style="{ width: qualityScore + '%' }"></div>
      </div>
      <div class="p-3 bg-primary/5 dark:bg-primary/10 rounded-xl border border-primary/10">
        <p class="text-[11px] font-medium text-slate-600 dark:text-slate-400 leading-relaxed">
          <span class="text-primary font-bold">Pro Tip:</span> Tours with clear subtitles and precise durations see 40% higher booking rates.
        </p>
      </div>
    </div>

    <div class="space-y-4">
      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Location Context</h4>
      <div class="relative rounded-2xl overflow-hidden aspect-video border border-slate-200 dark:border-slate-800 group bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
         <!-- Simulación de mapa -->
         <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600">map</span>
         <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
         <div class="absolute bottom-3 left-3 flex items-center gap-2">
            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur px-2 py-1 rounded-md shadow-sm">
               <p class="text-[10px] font-bold text-slate-800 dark:text-white">{{ store.basicInfo.nearestCity || 'Sin ubicación' }}</p>
            </div>
         </div>
      </div>
      <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">Your tour is currently pinned to {{ store.basicInfo.nearestCity || 'default location' }}. You can adjust the meeting point later.</p>
    </div>

    <div class="space-y-4 border-t border-slate-100 dark:border-slate-800 pt-6">
      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Reciente</h4>
      <div class="space-y-4">
        <div class="flex gap-3">
          <div class="size-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-lg">check</span>
          </div>
          <div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Wizard Initialized</p>
            <p class="text-[10px] text-slate-400">Hace un momento</p>
          </div>
        </div>
        <div v-if="store.isDirty" class="flex gap-3 animate-pulse">
          <div class="size-8 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-lg">edit</span>
          </div>
          <div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Cambios pendientes</p>
            <p class="text-[10px] text-slate-400">Waiting for save...</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const store = useTourWizardStore()
const router = useRouter()
const config = useRuntimeConfig()

const cancel = () => {
  if (confirm('Se perderán los cambios no guardados. ¿Deseas salir?')) {
    router.push('/admin/tours')
  }
}

const publishTour = async () => {
  store.basicInfo.status = 'published'
  await store.saveCurrentProgress()
  if (!store.isDirty) {
    alert('Tour publicado correctamente.')
  }
}

const FRONTEND_URL = (config.public as any).frontendUrl || 'https://incalake-frontend.vercel.app'

const previewUrl = computed(() => {
  const lang = store.currentLanguage || 'es'
  const seo = store.contentSEO?.[lang]
  const slug = seo?.slug
  if (!slug || !store.tourId || store.tourId === 'new') return ''
  return `${FRONTEND_URL}/${lang}/tours/${slug}`
})

const previewTour = () => {
  if (!previewUrl.value) return
  window.open(previewUrl.value, '_blank', 'noopener,noreferrer')
}

const qualityScore = computed(() => {
  let score = 20
  if (store.basicInfo.title) score += 20
  if (store.basicInfo.subtitle) score += 20
  if (store.basicInfo.code) score += 20
  if (store.basicInfo.nearestCity) score += 20
  return score
})
</script>
