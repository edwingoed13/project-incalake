<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <AdminTopbar />

    <header class="sticky top-16 z-30 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
      <div class="max-w-6xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            @click="goBack"
            class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            :title="`Volver a editar ${store.basicInfo.title || 'tour'}`"
          >
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <div class="min-w-0">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Disponibilidad</p>
            <h1 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ store.basicInfo.title || 'Tour' }}</h1>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="text-[10px] font-bold uppercase tracking-widest" :class="autosaveStyle.text">
            <span class="material-symbols-outlined text-base align-middle" :class="autosaveStyle.spin ? 'animate-spin' : ''">{{ autosaveStyle.icon }}</span>
            {{ autosaveStyle.title }}
          </div>
          <button
            type="button"
            @click="store.saveCurrentProgress()"
            :disabled="store.loading"
            class="px-4 py-2 bg-primary text-white rounded-xl text-xs font-black uppercase tracking-widest hover:shadow-lg hover:shadow-primary/30 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50"
          >
            <span v-if="!store.loading" class="material-symbols-outlined text-sm">save</span>
            <span v-else class="material-symbols-outlined animate-spin text-sm">sync</span>
            Guardar
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8">
      <Step8Availability v-if="loaded" />
      <div v-else class="p-12 text-center text-slate-400">
        <span class="material-symbols-outlined animate-spin text-2xl">sync</span>
        <p class="text-sm mt-2">Cargando tour...</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useRoute, useRouter } from 'vue-router'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import AdminTopbar from '~/components/admin/AdminTopbar.vue'
import Step8Availability from '~/components/tours/wizard/Step8Availability.vue'

definePageMeta({ layout: false })

const store = useTourWizardStore()
const route = useRoute()
const router = useRouter()

const loaded = ref(false)

onMounted(async () => {
  const id = route.params.id as string
  const langParam = (route.query.lang as string)?.toLowerCase()
  if (langParam) store.currentLanguage = langParam
  if (id && id !== 'new') {
    store.setTourId(id)
    await store.fetchTourData(id)
    if (langParam) store.currentLanguage = langParam
  }
  loaded.value = true
})

const goBack = () => {
  const lang = store.currentLanguage || 'es'
  router.push({ path: `/admin/tours/${store.tourId}/edit`, query: { lang } })
}

// Reuse the same autosave watcher as the wizard page
let autosaveTimer: ReturnType<typeof setTimeout> | null = null
watch(() => store.isDirty, (dirty) => {
  if (autosaveTimer) clearTimeout(autosaveTimer)
  if (!dirty) return
  if (!store.tourId || store.tourId === 'new') return
  autosaveTimer = setTimeout(() => {
    store.autosave()
    autosaveTimer = null
  }, 2000)
})
onBeforeUnmount(() => { if (autosaveTimer) clearTimeout(autosaveTimer) })

const autosaveStyle = computed(() => {
  if (store.autosaving || store.loading) return { text: 'text-slate-500', icon: 'sync', spin: true, title: 'Guardando…' }
  if (store.autosaveError) return { text: 'text-rose-500', icon: 'error', spin: false, title: 'Error' }
  if (store.isDirty) return { text: 'text-amber-500', icon: 'edit', spin: false, title: 'Sin guardar' }
  if (store.lastSavedAt) return { text: 'text-emerald-500', icon: 'check_circle', spin: false, title: 'Guardado' }
  return { text: 'text-slate-400', icon: 'cloud', spin: false, title: 'Listo' }
})
</script>
