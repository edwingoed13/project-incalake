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

      <div class="space-y-1.5">
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
        <div v-if="previewUrl" class="px-2 py-1 text-[9px] font-mono text-slate-400 break-all leading-tight">
          {{ previewUrl }}
        </div>
      </div>

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
        <span class="text-lg font-black" :class="qualityColor.text">{{ qualityScore }}%</span>
      </div>
      <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-700" :class="qualityColor.bg" :style="{ width: qualityScore + '%' }"></div>
      </div>
      <div class="p-3 rounded-xl border space-y-2" :class="qualityColor.banner">
        <p v-if="qualityHint" class="text-[11px] font-medium text-slate-700 dark:text-slate-300 leading-relaxed">
          <span class="font-bold" :class="qualityColor.text">{{ qualityHintLabel }}:</span>
          {{ qualityHint }}
        </p>
        <p v-else class="text-[11px] font-medium text-emerald-700 dark:text-emerald-300 leading-relaxed">
          <span class="font-bold">Excelente:</span> Tu tour está completo y listo para publicar.
        </p>
      </div>
      <details class="text-[11px] text-slate-500">
        <summary class="cursor-pointer font-bold hover:text-primary">Ver desglose</summary>
        <ul class="mt-2 space-y-1">
          <li v-for="b in qualityBreakdown" :key="b.label" class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm" :class="b.score >= b.max ? 'text-emerald-500' : (b.score > 0 ? 'text-amber-500' : 'text-slate-300')">
                {{ b.score >= b.max ? 'check_circle' : (b.score > 0 ? 'pending' : 'radio_button_unchecked') }}
              </span>
              {{ b.label }}
            </span>
            <span class="font-mono">{{ b.score }}/{{ b.max }}</span>
          </li>
        </ul>
      </details>
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

    <div class="space-y-3 border-t border-slate-100 dark:border-slate-800 pt-6">
      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Estado</h4>
      <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl border" :class="autosaveStyle.box">
        <span class="material-symbols-outlined text-lg" :class="['shrink-0', autosaveStyle.icon, autosaveStyle.spin ? 'animate-spin' : '']">{{ autosaveStyle.iconName }}</span>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold leading-tight" :class="autosaveStyle.text">{{ autosaveStyle.title }}</p>
          <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">{{ autosaveStyle.subtitle }}</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const store = useTourWizardStore()
const router = useRouter()
const config = useRuntimeConfig()

// Tick every second so "hace X s" updates without the user moving the mouse.
const now = ref(Date.now())
let nowTimer: ReturnType<typeof setInterval> | null = null
onMounted(() => { nowTimer = setInterval(() => { now.value = Date.now() }, 1000) })
onBeforeUnmount(() => { if (nowTimer) clearInterval(nowTimer) })

const formatRelative = (ts: number) => {
  const seconds = Math.max(0, Math.floor((now.value - ts) / 1000))
  if (seconds < 5) return 'hace un momento'
  if (seconds < 60) return `hace ${seconds}s`
  const m = Math.floor(seconds / 60)
  if (m < 60) return `hace ${m} min`
  const h = Math.floor(m / 60)
  return `hace ${h} h`
}

const autosaveStyle = computed(() => {
  if (store.autosaving || store.loading) {
    return {
      box: 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700',
      icon: 'text-slate-500',
      iconName: 'sync',
      spin: true,
      text: 'text-slate-700 dark:text-slate-300',
      title: 'Guardando…',
      subtitle: 'Sincronizando cambios',
    }
  }
  if (store.autosaveError) {
    return {
      box: 'bg-rose-50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800/40',
      icon: 'text-rose-500',
      iconName: 'error',
      spin: false,
      text: 'text-rose-700 dark:text-rose-300',
      title: 'Error al autoguardar',
      subtitle: store.autosaveError,
    }
  }
  if (store.isDirty) {
    return {
      box: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40',
      icon: 'text-amber-500',
      iconName: 'edit',
      spin: false,
      text: 'text-amber-700 dark:text-amber-300',
      title: 'Cambios sin guardar',
      subtitle: 'Se guardará automáticamente en 2s',
    }
  }
  if (store.lastSavedAt) {
    return {
      box: 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800/40',
      icon: 'text-emerald-500',
      iconName: 'check_circle',
      spin: false,
      text: 'text-emerald-700 dark:text-emerald-300',
      title: 'Guardado',
      subtitle: formatRelative(store.lastSavedAt),
    }
  }
  return {
    box: 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700',
    icon: 'text-slate-400',
    iconName: 'cloud',
    spin: false,
    text: 'text-slate-700 dark:text-slate-300',
    title: 'Sin cambios',
    subtitle: 'Edita para autoguardar',
  }
})

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

// Pick the first language that actually has a slug, preferring the current language
const previewLang = computed(() => {
  const langs = [store.currentLanguage || 'es', 'es', 'en', 'pt', 'fr', 'de', 'it']
  for (const l of langs) {
    if (store.contentSEO?.[l]?.slug) return l
  }
  return ''
})

const previewSlug = computed(() => {
  const l = previewLang.value
  return l ? (store.contentSEO[l].slug || '').trim() : ''
})

// Slugify city name as fallback when the API didn't expose city.slug yet
const slugifyCity = (name: string) => (name || '')
  .toLowerCase()
  .normalize('NFD').replace(/\p{Diacritic}/gu, '')
  .replace(/[^a-z0-9\s-]/g, '')
  .trim()
  .replace(/\s+/g, '-')

const previewCitySlug = computed(() => {
  return store.basicInfo.citySlug || slugifyCity(store.basicInfo.nearestCity || '') || 'puno'
})

const previewUrl = computed(() => {
  if (!store.tourId || store.tourId === 'new') return ''
  if (!previewSlug.value) return ''
  // Frontend route: /{lang}/{city.slug}/{tour.slug} — the /tours/{slug} variant
  // is not pre-rendered in production and 404s.
  return `${FRONTEND_URL}/${previewLang.value}/${previewCitySlug.value}/${previewSlug.value}`
})

const previewTour = () => {
  if (!store.tourId || store.tourId === 'new') {
    alert('Guarda el tour primero — no hay slug todavía.')
    return
  }
  if (!previewSlug.value) {
    alert('Ningún idioma tiene slug guardado.\nVe a Step 2 (Description & SEO), genera/guarda el slug y vuelve a intentar.')
    return
  }
  console.log('[Preview] Opening:', previewUrl.value)
  window.open(previewUrl.value, '_blank', 'noopener,noreferrer')
}

// Real listing-quality breakdown — see claude-design.md §7.
const qualityBreakdown = computed(() => {
  const lang = store.currentLanguage || 'es'
  const seo = store.contentSEO?.[lang] || {}
  const detailed = store.detailedContent?.[lang] || {}
  const images = store.multimedia?.images || []
  const stages = store.commercialRules?.ageStages || []
  const filledLangs = Object.keys(store.contentSEO || {}).filter(k => (store.contentSEO[k]?.title || '').trim()).length

  // 1) Básicos (20)
  let basics = 0
  if (store.basicInfo.title) basics += 5
  if (store.basicInfo.code) basics += 5
  if (store.basicInfo.cityId || store.basicInfo.nearestCity) basics += 5
  const hasDuration = (store.basicInfo.durationDays || 0) + (store.basicInfo.durationHours || 0) + (store.basicInfo.durationMinutes || 0) > 0
    || Number(store.basicInfo.duration) > 0
  if (hasDuration) basics += 5

  // 2) Descripción + SEO (15)
  let descSeo = 0
  if ((seo.shortDescription || '').trim()) descSeo += 5
  if ((seo.metaTitle || '').trim()) descSeo += 5
  if ((seo.metaDescription || '').trim()) descSeo += 5

  // 3) Fotos (25 max)
  const imgCount = images.length
  let media = 0
  if (imgCount >= 1) media += 5
  if (imgCount >= 5) media += 10
  if (imgCount >= 10) media += 5
  if (images.some((i: any) => i.isPrimary)) media += 5

  // 4) Itinerario (15)
  let itin = 0
  if (((detailed as any).detailedDescription || '').trim()) itin += 8
  const itineraryItems = Array.isArray((detailed as any).itinerary) ? (detailed as any).itinerary.length : 0
  if (((detailed as any).itineraryText || '').trim() || itineraryItems > 0) itin += 7

  // 5) Includes / excludes (10)
  let inc = 0
  if (((detailed as any).inclusions || '').trim()) inc += 5
  if (((detailed as any).exclusions || '').trim()) inc += 5

  // 6) Precios + booking (15)
  let pricing = 0
  const hasActivePrices = stages.some((s: any) =>
    s.active && (s.nationalities || []).some((n: any) => (n.ranges || []).some((r: any) => Number(r.price) > 0))
  )
  if (hasActivePrices) pricing += 10
  if (Number(store.commercialRules?.taxPercentage) > 0) pricing += 5

  // 7) Traducciones (10)
  let trans = 0
  if (filledLangs >= 1) trans += 4
  if (filledLangs >= 3) trans += 3
  if (filledLangs >= 6) trans += 3

  return [
    { key: 'basics', label: 'Información básica', score: basics, max: 20 },
    { key: 'descSeo', label: 'Descripción y SEO', score: descSeo, max: 15 },
    { key: 'media', label: 'Galería de fotos', score: media, max: 25 },
    { key: 'itin', label: 'Itinerario', score: itin, max: 15 },
    { key: 'inc', label: 'Incluye / no incluye', score: inc, max: 10 },
    { key: 'pricing', label: 'Precios y reservas', score: pricing, max: 15 },
    { key: 'trans', label: 'Traducciones', score: trans, max: 10 },
  ]
})

const qualityScore = computed(() => {
  const total = qualityBreakdown.value.reduce((s, b) => s + b.score, 0)
  return Math.min(100, Math.round(total))
})

const qualityColor = computed(() => {
  const s = qualityScore.value
  if (s >= 80) return {
    text: 'text-emerald-500',
    bg: 'bg-emerald-500',
    banner: 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800/40',
  }
  if (s >= 40) return {
    text: 'text-amber-500',
    bg: 'bg-amber-500',
    banner: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40',
  }
  return {
    text: 'text-rose-500',
    bg: 'bg-rose-500',
    banner: 'bg-rose-50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800/40',
  }
})

const qualityHintLabel = computed(() => {
  const s = qualityScore.value
  if (s >= 80) return 'Listo'
  if (s >= 40) return 'Casi listo'
  return 'Necesita más trabajo'
})

// First missing item — surfaces the next thing the editor should fill in.
const qualityHint = computed(() => {
  const breakdown = qualityBreakdown.value
  const incomplete = breakdown.find(b => b.score < b.max)
  if (!incomplete) return ''
  const tips: Record<string, string> = {
    basics: 'Completa título, código, ciudad y duración del tour.',
    descSeo: 'Agrega descripción corta, meta title y meta description en SEO.',
    media: 'Sube al menos 5 fotos y marca una como principal (HERO).',
    itin: 'Llena la descripción detallada y el itinerario.',
    inc: 'Define qué incluye y qué no incluye el tour.',
    pricing: 'Activa al menos un rango de precio y configura el porcentaje de impuestos.',
    trans: 'Agrega más traducciones (mínimo 3 idiomas para llegar a 100%).',
  }
  return tips[incomplete.key] || ''
})
</script>
