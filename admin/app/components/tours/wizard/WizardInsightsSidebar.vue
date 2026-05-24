<template>
  <aside class="w-72 border-l border-default bg-default p-4 hidden xl:flex flex-col gap-4 sticky top-16 h-[calc(100vh-64px)] overflow-y-auto">
    <!-- Preview is the only action unique to this panel. Navigation lives in
         the bottom bar; publish / back / save-status live in the top navbar. -->
    <UButton
      icon="i-lucide-eye"
      trailing-icon="i-lucide-external-link"
      color="primary"
      variant="soft"
      size="md"
      block
      :disabled="!previewUrl"
      :title="previewUrl || 'Guarda el tour para generar el slug'"
      @click="previewTour"
    >
      Vista previa
    </UButton>

    <!-- Listing Quality -->
    <UCard :ui="{ body: 'p-3 space-y-3' }">
      <div class="flex items-center justify-between">
        <p class="text-xs font-bold flex items-center gap-1.5">
          <UIcon name="i-lucide-gauge" class="size-4 text-primary" />
          Calidad del listado
        </p>
        <span class="text-base font-black tabular-nums" :class="qualityColor.text">{{ qualityScore }}%</span>
      </div>

      <div class="w-full bg-elevated h-2 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="qualityColor.bg"
          :style="{ width: qualityScore + '%' }"
        />
      </div>

      <UAlert
        :color="qualityScore >= 80 ? 'success' : qualityScore >= 40 ? 'warning' : 'error'"
        variant="subtle"
        :icon="qualityScore >= 80 ? 'i-lucide-circle-check' : qualityScore >= 40 ? 'i-lucide-info' : 'i-lucide-triangle-alert'"
        :title="qualityHintLabel"
        :description="qualityHint || 'Tu tour está completo y listo para publicar.'"
        :ui="{ title: 'text-[11px]', description: 'text-[10px] leading-relaxed' }"
      />

      <details class="text-[11px] text-muted">
        <summary class="cursor-pointer font-bold hover:text-primary flex items-center gap-1 select-none">
          <UIcon name="i-lucide-chevron-right" class="size-3 transition-transform [details[open]_&]:rotate-90" />
          Ver desglose ({{ qualityBreakdown.length }} criterios)
        </summary>
        <ul class="mt-2 space-y-1 pl-4">
          <li
            v-for="b in qualityBreakdown"
            :key="b.label"
            class="flex items-center justify-between gap-2 py-0.5"
            :title="b.tip"
          >
            <span class="flex items-center gap-1.5 min-w-0">
              <UIcon
                :name="b.score >= b.max ? 'i-lucide-circle-check' : b.score > 0 ? 'i-lucide-circle-dashed' : 'i-lucide-circle'"
                class="size-3.5 shrink-0"
                :class="b.score >= b.max ? 'text-success' : b.score > 0 ? 'text-warning' : 'text-muted/50'"
              />
              <span class="truncate">{{ b.label }}</span>
            </span>
            <span class="font-mono text-[10px] shrink-0" :class="b.score >= b.max ? 'text-success font-bold' : ''">
              {{ b.score }}/{{ b.max }}
            </span>
          </li>
        </ul>
      </details>
    </UCard>

    <!-- Location -->
    <UCard :ui="{ body: 'p-3 space-y-2' }">
      <p class="text-xs font-bold flex items-center gap-1.5">
        <UIcon name="i-lucide-map-pin" class="size-4 text-primary" />
        Ubicación
      </p>
      <div class="relative rounded-lg overflow-hidden aspect-video border border-default bg-elevated flex items-center justify-center">
        <UIcon name="i-lucide-map" class="size-10 text-muted/40" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
        <div class="absolute bottom-2 left-2">
          <UBadge color="neutral" variant="solid" size="xs" icon="i-lucide-map-pin" class="bg-black/70 text-white backdrop-blur">
            {{ store.basicInfo.nearestCity || 'Sin ubicación' }}
          </UBadge>
        </div>
      </div>
      <p class="text-[10px] text-muted leading-relaxed">
        Tour anclado a <span class="font-bold text-default">{{ store.basicInfo.nearestCity || 'ubicación por defecto' }}</span>.
        Ajusta el punto de encuentro en el paso 3.
      </p>
    </UCard>

  </aside>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const store = useTourWizardStore()
const router = useRouter()
const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()

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
      box: 'border-info/30 bg-info/5',
      icon: 'i-lucide-loader-circle',
      iconColor: 'text-info',
      spin: true,
      text: 'text-info',
      title: 'Guardando…',
      subtitle: 'Sincronizando cambios',
    }
  }
  if (store.autosaveError) {
    return {
      box: 'border-error/30 bg-error/5',
      icon: 'i-lucide-circle-x',
      iconColor: 'text-error',
      spin: false,
      text: 'text-error',
      title: 'Error al autoguardar',
      subtitle: store.autosaveError,
    }
  }
  if (store.isDirty) {
    return {
      box: 'border-warning/30 bg-warning/5',
      icon: 'i-lucide-pencil',
      iconColor: 'text-warning',
      spin: false,
      text: 'text-warning',
      title: 'Cambios sin guardar',
      subtitle: 'Se guardará automáticamente en 2s',
    }
  }
  if (store.lastSavedAt) {
    return {
      box: 'border-success/30 bg-success/5',
      icon: 'i-lucide-circle-check',
      iconColor: 'text-success',
      spin: false,
      text: 'text-success',
      title: 'Guardado',
      subtitle: formatRelative(store.lastSavedAt),
    }
  }
  return {
    box: 'border-default bg-elevated/40',
    icon: 'i-lucide-cloud',
    iconColor: 'text-muted',
    spin: false,
    text: 'text-default',
    title: 'Sin cambios',
    subtitle: 'Edita para autoguardar',
  }
})

const cancel = async () => {
  if (store.isDirty) {
    const ok = await confirm({
      title: 'Salir sin guardar',
      description: 'Tienes cambios sin guardar. Si sales ahora, se perderán.',
      confirmLabel: 'Salir igual',
      cancelLabel: 'Volver al editor',
      confirmColor: 'error',
      icon: 'i-lucide-triangle-alert',
      iconColor: 'warning',
    })
    if (!ok) return
  }
  router.push('/admin/v2/tours')
}

const publishing = ref(false)

const publishTour = async () => {
  const wasPublished = store.basicInfo.status === 'published'
  const ok = await confirm({
    title: wasPublished ? 'Actualizar publicación' : 'Publicar tour',
    description: wasPublished
      ? '¿Confirmas la actualización? Los cambios serán visibles en el sitio público inmediatamente.'
      : '¿Confirmas publicar este tour? Será visible en el sitio público inmediatamente.',
    confirmLabel: wasPublished ? 'Actualizar' : 'Publicar',
    cancelLabel: 'Cancelar',
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
    toast.add({
      title: 'Guarda el tour primero',
      description: 'No se puede previsualizar hasta que el tour exista en la BD.',
      icon: 'i-lucide-info',
      color: 'warning',
    })
    return
  }
  if (!previewSlug.value) {
    toast.add({
      title: 'Falta el slug',
      description: 'Ningún idioma tiene slug guardado. Ve al paso 2 (SEO), genera el slug y guarda.',
      icon: 'i-lucide-triangle-alert',
      color: 'warning',
    })
    return
  }
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
    {
      key: 'basics', label: 'Información básica', score: basics, max: 20,
      tip: 'Título (5) + Código (5) + Ciudad (5) + Duración (5)',
    },
    {
      key: 'descSeo', label: 'Descripción y SEO', score: descSeo, max: 15,
      tip: 'Descripción corta (5) + Meta title (5) + Meta description (5) — del idioma actual',
    },
    {
      key: 'media', label: 'Galería de fotos', score: media, max: 25,
      tip: '1+ foto (5) + 5+ fotos (10) + 10+ fotos (5) + Imagen principal marcada (5)',
    },
    {
      key: 'itin', label: 'Itinerario', score: itin, max: 15,
      tip: 'Descripción detallada (8) + Itinerario o lista de paradas (7)',
    },
    {
      key: 'inc', label: 'Incluye / no incluye', score: inc, max: 10,
      tip: 'Qué incluye (5) + Qué NO incluye (5)',
    },
    {
      key: 'pricing', label: 'Precios y reservas', score: pricing, max: 15,
      tip: 'Al menos un rango con precio > 0 (10) + Porcentaje de impuestos configurado (5)',
    },
    {
      key: 'trans', label: 'Traducciones', score: trans, max: 10,
      tip: '1 idioma con título (4) + 3 idiomas (3) + 6 idiomas (3)',
    },
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
