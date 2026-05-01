<template>
  <div class="flex flex-col gap-6 pb-20 max-w-4xl mx-auto">
    <!-- Hero summary -->
    <section class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden">
      <div class="aspect-[16/8] bg-slate-100 dark:bg-slate-800 relative">
        <img v-if="heroImage" :src="heroImage" :alt="store.basicInfo.title" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
          <span class="material-symbols-outlined text-5xl">image</span>
        </div>
        <div class="absolute top-3 left-3 flex gap-2">
          <span class="px-2.5 py-1 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur text-[10px] font-black uppercase tracking-widest" :class="statusColor.text">
            {{ statusColor.label }}
          </span>
          <span v-if="store.basicInfo.code" class="px-2.5 py-1 rounded-full bg-slate-900/80 text-white text-[10px] font-mono">
            {{ store.basicInfo.code }}
          </span>
        </div>
      </div>
      <div class="p-6 space-y-3">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ store.basicInfo.title || 'Tour sin título' }}</h2>
        <p v-if="currentSeo?.shortDescription" class="text-sm text-slate-500 dark:text-slate-400">{{ currentSeo.shortDescription }}</p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">location_on</span> {{ store.basicInfo.nearestCity || '—' }}</span>
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">schedule</span> {{ durationLabel }}</span>
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">groups</span> {{ store.basicInfo.capacityMax || 0 }} pax máx</span>
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">attach_money</span> desde ${{ minPrice }}</span>
        </div>
      </div>
    </section>

    <!-- Stats grid -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <div v-for="stat in stats" :key="stat.label" class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 text-center">
        <div class="text-xl font-black" :class="stat.value > 0 ? 'text-emerald-500' : 'text-slate-300'">{{ stat.value }}</div>
        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-1">{{ stat.label }}</div>
      </div>
    </section>

    <!-- Per-step checklist -->
    <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50">
      <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Resumen por paso</h3>
        <span class="text-xs font-bold" :class="completedSteps === totalChecks ? 'text-emerald-500' : 'text-amber-500'">{{ completedSteps }} / {{ totalChecks }} listos</span>
      </div>
      <ul class="divide-y divide-slate-100 dark:divide-slate-800">
        <li v-for="check in checklist" :key="check.step" class="px-5 py-3 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <span class="material-symbols-outlined text-lg shrink-0" :class="check.ok ? 'text-emerald-500' : 'text-amber-500'">{{ check.ok ? 'check_circle' : 'pending' }}</span>
            <div class="min-w-0">
              <p class="text-sm font-bold dark:text-white">{{ check.label }}</p>
              <p class="text-[11px] text-slate-500 truncate">{{ check.detail }}</p>
            </div>
          </div>
          <button type="button" @click="store.currentStep = check.step" class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline shrink-0">Editar</button>
        </li>
      </ul>
    </section>

    <!-- Calendar link -->
    <section class="rounded-2xl border-2 border-violet-200 dark:border-violet-800/40 bg-violet-50/40 dark:bg-violet-900/10 p-5 flex items-center gap-4">
      <span class="size-12 rounded-2xl bg-violet-500 text-white flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined">calendar_month</span>
      </span>
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-black text-violet-700 dark:text-violet-300">Calendario y disponibilidad</h4>
        <p class="text-xs text-violet-600/80 dark:text-violet-400/80">Fechas, días activos, bloqueos y ofertas se gestionan en una sección aparte.</p>
      </div>
      <button
        type="button"
        @click="goToAvailability"
        :disabled="!hasTour"
        :title="hasTour ? '' : 'Guarda el tour para gestionar el calendario'"
        class="px-4 py-2 bg-violet-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-violet-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2 shrink-0"
      >
        <span class="material-symbols-outlined text-sm">event</span>
        Gestionar
      </button>
    </section>

    <!-- Publish callout -->
    <section
      class="rounded-2xl border-2 p-6 flex flex-col sm:flex-row items-center gap-4"
      :class="canPublish ? 'border-emerald-300 dark:border-emerald-700/50 bg-emerald-50/40 dark:bg-emerald-900/10' : 'border-amber-300 dark:border-amber-700/50 bg-amber-50/40 dark:bg-amber-900/10'"
    >
      <span class="size-14 rounded-full flex items-center justify-center shrink-0" :class="canPublish ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white'">
        <span class="material-symbols-outlined text-2xl">{{ canPublish ? 'rocket_launch' : 'warning' }}</span>
      </span>
      <div class="flex-1 min-w-0 text-center sm:text-left">
        <h4 class="text-base font-black" :class="canPublish ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">
          {{ canPublish ? '¡Todo listo para publicar!' : 'Aún faltan datos clave' }}
        </h4>
        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
          <template v-if="canPublish">Revisa el preview y presiona "Publish Tour" en el sidebar.</template>
          <template v-else>{{ missingDetail }}</template>
        </p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useRouter } from 'vue-router'
import { computed } from 'vue'

const store = useTourWizardStore()
const router = useRouter()

const hasTour = computed(() => !!store.tourId && store.tourId !== 'new')

const currentSeo = computed(() => store.contentSEO?.[store.currentLanguage])
const currentDetail = computed(() => store.detailedContent?.[store.currentLanguage])

const heroImage = computed(() => {
  const imgs = store.multimedia?.images || []
  const primary = imgs.find((i: any) => i.isPrimary)
  return (primary || imgs[0])?.url || ''
})

const durationLabel = computed(() => {
  const d = Number(store.basicInfo.durationDays) || 0
  const h = Number(store.basicInfo.durationHours) || 0
  const m = Number(store.basicInfo.durationMinutes) || 0
  const parts: string[] = []
  if (d > 0) parts.push(`${d}D`)
  if (h > 0) parts.push(`${h}H`)
  if (m > 0) parts.push(`${m}M`)
  return parts.join(' ') || '—'
})

const minPrice = computed(() => {
  const stages = store.commercialRules?.ageStages || []
  const prices: number[] = []
  for (const s of stages) {
    if (!s.active) continue
    for (const n of s.nationalities || []) {
      for (const r of n.ranges || []) {
        if (Number(r.price) > 0) prices.push(Number(r.price))
      }
    }
  }
  return prices.length ? Math.min(...prices).toFixed(2) : '—'
})

const stats = computed(() => {
  const langs = Object.keys(store.contentSEO || {}).filter(k => (store.contentSEO[k]?.title || '').trim())
  return [
    { label: 'Idiomas', value: langs.length },
    { label: 'Fotos', value: (store.multimedia?.images || []).length },
    { label: 'Categorías', value: (store.selectedCategories || []).length },
    { label: 'Etiquetas', value: (store.selectedTags || []).length },
    { label: 'Bloqueos', value: (store.availability?.blocks || []).length },
    { label: 'Ofertas', value: (store.availability?.offers || []).length },
  ]
})

const checklist = computed(() => {
  const seo = currentSeo.value || {}
  const detail = currentDetail.value || {}
  const imgCount = (store.multimedia?.images || []).length
  const hasPrices = (store.commercialRules?.ageStages || []).some((s: any) =>
    s.active && (s.nationalities || []).some((n: any) => (n.ranges || []).some((r: any) => Number(r.price) > 0))
  )
  return [
    {
      step: 1,
      label: 'Información básica',
      ok: !!(store.basicInfo.title && store.basicInfo.code && store.basicInfo.cityId),
      detail: `${store.basicInfo.title || '—'} · ${durationLabel.value} · ${store.basicInfo.nearestCity || 'sin ciudad'}`,
    },
    {
      step: 2,
      label: 'Descripción y SEO',
      ok: !!(seo.shortDescription && seo.metaTitle && seo.metaDescription),
      detail: seo.metaTitle ? `Meta: ${seo.metaTitle}` : 'Falta meta title / description',
    },
    {
      step: 3,
      label: 'Contenido detallado',
      ok: !!(detail.detailedDescription || detail.itineraryText),
      detail: detail.detailedDescription ? 'Itinerario y descripción listos' : 'Falta itinerario / descripción detallada',
    },
    {
      step: 4,
      label: 'Precios',
      ok: hasPrices,
      detail: hasPrices ? `Desde $${minPrice.value}` : 'Activa al menos un rango de precio',
    },
    {
      step: 5,
      label: 'Galería',
      ok: imgCount >= 5,
      detail: imgCount > 0 ? `${imgCount} foto${imgCount === 1 ? '' : 's'} subida${imgCount === 1 ? '' : 's'}` : 'Sube al menos 5 fotos',
    },
    {
      step: 6,
      label: 'Opciones de reserva',
      ok: !!store.bookingOptions?.policyType,
      detail: store.bookingOptions?.policyType ? `Política: ${store.bookingOptions.policyType}` : 'Configura la política de reserva',
    },
    {
      step: 7,
      label: 'Categorías',
      ok: (store.selectedCategories || []).length > 0,
      detail: `${(store.selectedCategories || []).length} categorías · ${(store.selectedTags || []).length} etiquetas`,
    },
  ]
})

const totalChecks = computed(() => checklist.value.length)
const completedSteps = computed(() => checklist.value.filter(c => c.ok).length)
const canPublish = computed(() => completedSteps.value === totalChecks.value)

const missingDetail = computed(() => {
  const missing = checklist.value.find(c => !c.ok)
  return missing ? `Falta: ${missing.label.toLowerCase()} (${missing.detail})` : ''
})

const statusColor = computed(() => {
  const s = store.basicInfo.status || 'draft'
  if (s === 'published') return { label: 'Publicado', text: 'text-emerald-600' }
  if (s === 'archived') return { label: 'Archivado', text: 'text-slate-500' }
  return { label: 'Borrador', text: 'text-amber-600' }
})

const goToAvailability = () => {
  if (!hasTour.value) return
  const lang = store.currentLanguage || 'es'
  router.push({ path: `/admin/tours/${store.tourId}/availability`, query: { lang } })
}
</script>
