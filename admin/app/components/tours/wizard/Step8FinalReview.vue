<template>
  <div class="flex flex-col gap-3 pb-20">
    <!-- Hero summary -->
    <UCard :ui="{ body: '!p-0' }">
      <div class="aspect-[16/5] bg-elevated relative overflow-hidden">
        <img v-if="heroImage" :src="heroImage" :alt="store.basicInfo.title" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full flex items-center justify-center text-muted">
          <UIcon name="i-lucide-image-off" class="size-10" />
        </div>
        <div class="absolute top-2 left-2 flex gap-1.5">
          <UBadge :color="statusBadge.color" variant="solid" size="sm" :icon="statusBadge.icon">
            {{ statusBadge.label }}
          </UBadge>
          <UBadge v-if="store.basicInfo.code" color="neutral" variant="solid" size="sm" class="font-mono bg-black/70 text-white">
            {{ store.basicInfo.code }}
          </UBadge>
        </div>
      </div>
      <div class="p-4 space-y-2">
        <h2 class="text-lg font-bold leading-tight">{{ store.basicInfo.title || 'Tour sin título' }}</h2>
        <p v-if="currentSeo?.shortDescription" class="text-xs text-muted leading-relaxed line-clamp-2">{{ currentSeo.shortDescription }}</p>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted pt-2 border-t border-default">
          <span class="flex items-center gap-1">
            <UIcon name="i-lucide-map-pin" class="size-3.5" />
            {{ store.basicInfo.nearestCity || '—' }}
          </span>
          <span class="flex items-center gap-1">
            <UIcon name="i-lucide-clock" class="size-3.5" />
            {{ durationLabel }}
          </span>
          <span class="flex items-center gap-1">
            <UIcon name="i-lucide-users" class="size-3.5" />
            {{ store.basicInfo.capacityMax || 0 }} pax
          </span>
          <span class="flex items-center gap-1 text-success font-bold">
            <UIcon name="i-lucide-dollar-sign" class="size-3.5" />
            desde ${{ minPrice }}
          </span>
        </div>
      </div>
    </UCard>

    <!-- Stats grid -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
      <UCard
        v-for="stat in stats"
        :key="stat.label"
        :ui="{ body: 'p-2.5' }"
      >
        <p class="text-center text-xl font-black tabular-nums leading-none" :class="stat.value > 0 ? 'text-success' : 'text-muted'">
          {{ stat.value }}
        </p>
        <p class="text-[9px] font-black uppercase tracking-widest text-muted text-center mt-1">
          {{ stat.label }}
        </p>
      </UCard>
    </div>

    <!-- Per-step checklist -->
    <UCard :ui="{ header: 'p-3 sm:p-3', body: '!p-0' }">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <UIcon name="i-lucide-clipboard-check" class="size-4 text-primary" />
            <h3 class="text-sm font-bold">Resumen por paso</h3>
          </div>
          <UBadge :color="canPublish ? 'success' : 'warning'" variant="subtle" size="sm">
            {{ completedSteps }} / {{ totalChecks }}
          </UBadge>
        </div>
      </template>

      <ul class="divide-y divide-default">
        <li
          v-for="check in checklist"
          :key="check.step"
          class="px-4 py-2 flex items-center justify-between gap-2 hover:bg-elevated/30 transition-colors group"
        >
          <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <UIcon
              :name="check.ok ? 'i-lucide-circle-check' : 'i-lucide-circle-dashed'"
              class="size-4 shrink-0"
              :class="check.ok ? 'text-success' : 'text-warning'"
            />
            <div class="min-w-0 flex-1">
              <p class="text-xs font-bold leading-tight">{{ check.label }}</p>
              <p class="text-[10px] text-muted truncate leading-tight mt-0.5">{{ check.detail }}</p>
            </div>
          </div>
          <UButton
            color="primary"
            variant="ghost"
            size="xs"
            icon="i-lucide-pencil"
            class="opacity-60 group-hover:opacity-100 transition-opacity"
            @click="store.currentStep = check.step"
          />
        </li>
      </ul>
    </UCard>

    <!-- Publish callout -->
    <UAlert
      :color="canPublish ? 'success' : 'warning'"
      variant="subtle"
      :icon="canPublish ? 'i-lucide-rocket' : 'i-lucide-triangle-alert'"
      :title="publishTitle"
      :description="publishDescription"
    />
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed } from 'vue'

const store = useTourWizardStore()

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
  const availability = store.availability || {}
  const hasAvailability = !!(availability.start && availability.end && (availability.activeDays || []).length > 0)

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
    {
      step: 8,
      label: 'Disponibilidad',
      ok: hasAvailability,
      detail: hasAvailability
        ? `Activo del ${availability.start} al ${availability.end} · ${(availability.blocks || []).length} bloqueos · ${(availability.offers || []).length} ofertas`
        : 'Configura el rango de fechas y días activos',
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

const publishTitle = computed(() =>
  canPublish.value ? '¡Todo listo para publicar!' : 'Aún faltan datos clave',
)

const publishDescription = computed(() =>
  canPublish.value
    ? 'Revisa el preview y presiona "Publicar tour" en el sidebar para publicar.'
    : missingDetail.value,
)

const statusBadge = computed(() => {
  const s = store.basicInfo.status || 'draft'
  if (s === 'published') return { label: 'Publicado', color: 'success' as const, icon: 'i-lucide-circle-check' }
  if (s === 'archived') return { label: 'Archivado', color: 'neutral' as const, icon: 'i-lucide-archive' }
  return { label: 'Borrador', color: 'warning' as const, icon: 'i-lucide-file-text' }
})
</script>
