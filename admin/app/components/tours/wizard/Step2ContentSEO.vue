<template>
  <div class="flex flex-col gap-5">
    <UCard :ui="{ header: 'p-4 sm:p-4', body: 'p-4 sm:p-4 space-y-6' }">
      <template #header>
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div class="flex items-center gap-3">
            <div class="size-9 rounded-lg bg-primary/10 flex items-center justify-center">
              <UIcon name="i-lucide-languages" class="size-5 text-primary" />
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-muted">Editando contenido en</p>
              <div class="flex items-center gap-1 mt-1">
                <UButton
                  v-for="lang in tourLanguages"
                  :key="lang"
                  size="xs"
                  :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
                  :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
                  class="uppercase font-black tracking-wider"
                  @click="store.currentLanguage = lang"
                >
                  {{ lang }}
                </UButton>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="currentLangData" class="space-y-6">
        <!-- Section: Tour Content -->
        <section class="space-y-3">
          <h3 class="text-base font-bold flex items-center gap-2">
            <UIcon name="i-lucide-file-text" class="size-5 text-primary" />
            Contenido del tour
          </h3>
          <UFormField label="Título público" required>
            <UInput
              v-model="currentLangData.title"
              placeholder="Ej. Tour mágico al atardecer en Cusco"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Descripción corta" hint="Resumen para listados de búsqueda">
            <UTextarea
              v-model="currentLangData.shortDescription"
              :rows="3"
              placeholder="Resumen breve para listados..."
              class="w-full"
            />
          </UFormField>
        </section>

        <USeparator />

        <!-- Section: SEO Settings -->
        <section class="space-y-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-base font-bold flex items-center gap-2">
              <UIcon name="i-lucide-search" class="size-5 text-primary" />
              Configuración SEO
            </h3>
            <UBadge color="neutral" variant="subtle" size="sm" class="uppercase tracking-widest">Google Preview</UBadge>
          </div>

          <UFormField
            label="Meta title"
            :hint="`${(currentLangData.metaTitle || '').length}/60 · recomendado 50-60 chars`"
            :error="(currentLangData.metaTitle || '').length > 60 ? 'Excede los 60 caracteres recomendados' : undefined"
          >
            <UInput
              v-model="currentLangData.metaTitle"
              placeholder="Tour Mágico al Atardecer en Cusco | Incalake"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Meta description" hint="Recomendado 150-160 chars para resultados de búsqueda">
            <UTextarea
              v-model="currentLangData.metaDescription"
              :rows="3"
              placeholder="Resumen breve para resultados de búsqueda..."
              class="w-full"
            />
          </UFormField>

          <UFormField label="URL slug">
            <div class="flex w-full">
              <span class="inline-flex items-center px-3 text-[11px] text-muted font-mono whitespace-nowrap bg-elevated border border-r-0 border-default rounded-l-md">
                incalake.com/{{ store.currentLanguage }}/tours/
              </span>
              <UInput
                v-model="currentLangData.slug"
                placeholder="tour-magico-cusco"
                class="flex-1 min-w-0"
                :ui="{ base: 'rounded-l-none' }"
                @input="sanitizeSlug"
              />
            </div>
          </UFormField>

          <UAlert
            v-if="fullMultilangUrl"
            color="primary"
            variant="subtle"
            icon="i-lucide-link"
            title="Vista previa URL"
          >
            <template #description>
              <a :href="fullMultilangUrl" target="_blank" class="text-xs font-mono break-all hover:underline">
                {{ fullMultilangUrl }}
              </a>
            </template>
          </UAlert>

          <!-- Google SERP snippet preview -->
          <div class="space-y-1.5 pt-1">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted flex items-center gap-1.5">
              <UIcon name="i-lucide-eye" class="size-3.5" />
              Así se vería en Google
            </p>
            <div class="rounded-xl border border-default bg-white dark:bg-slate-900 p-4 max-w-xl">
              <div class="flex gap-4">
                <!-- Text column -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1.5">
                    <div class="size-6 rounded-full bg-elevated border border-default flex items-center justify-center shrink-0">
                      <UIcon name="i-lucide-globe" class="size-3.5 text-muted" />
                    </div>
                    <div class="min-w-0 leading-tight">
                      <p class="text-xs text-default font-medium">Incalake</p>
                      <p class="text-[11px] text-emerald-700 dark:text-emerald-500 truncate">{{ previewBreadcrumb }}</p>
                    </div>
                  </div>
                  <h4 class="text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8] hover:underline cursor-pointer truncate">
                    {{ previewTitle }}
                  </h4>
                  <p class="text-[13px] leading-snug text-[#4d5156] dark:text-slate-400 line-clamp-2 mt-0.5">
                    {{ previewDescription }}
                  </p>

                  <!-- Rich result row: price · availability · rating -->
                  <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 mt-1.5 text-[13px]">
                    <span v-if="previewPrice" class="text-[#4d5156] dark:text-slate-300 font-medium">
                      USD {{ previewPrice.toFixed(2) }}
                    </span>
                    <span v-if="previewPrice" class="text-muted">·</span>
                    <span class="text-emerald-700 dark:text-emerald-500">Disponible</span>
                    <span class="text-muted">·</span>
                    <span class="inline-flex items-center gap-0.5 text-amber-500" title="La valoración aparece cuando el tour recibe reseñas">
                      <UIcon v-for="n in 5" :key="n" name="i-lucide-star" class="size-3 opacity-40" />
                    </span>
                    <span class="text-[11px] text-muted">(con reseñas)</span>
                  </div>
                </div>

                <!-- Thumbnail (Google shows the primary image) -->
                <div
                  v-if="previewImage"
                  class="shrink-0 size-[92px] rounded-lg overflow-hidden border border-default bg-elevated"
                >
                  <img :src="previewImage" alt="" class="w-full h-full object-cover" />
                </div>
              </div>
            </div>
            <p class="text-[10px] text-muted">
              Vista referencial. El precio sale del Step 4 y la imagen del Step 5. Las estrellas reales aparecen cuando el tour acumula reseñas. Google puede recortar el título (~60) y la descripción (~160).
            </p>
          </div>
        </section>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, onMounted, watch } from 'vue'

const store = useTourWizardStore()
const config = useRuntimeConfig()
const defaultApiUrl = config.public.apiUrl

const cityData = ref<any>(null)

// Only show languages that have translations (title filled)
const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

const currentLangData = computed(() => {
  return store.contentSEO[store.currentLanguage]
})

// Fetch city data to get slug
const fetchCityData = async () => {
  if (!store.basicInfo.cityId) return

  try {
    const response: any = await $fetch(`${defaultApiUrl}/cities/${store.basicInfo.cityId}`)
    if (response.success) {
      cityData.value = response.data
    }
  } catch (error) {
    console.error('Error fetching city data:', error)
  }
}

const citySlugDisplay = computed(() => {
  if (cityData.value?.slug) {
    return cityData.value.slug
  }
  return store.basicInfo.nearestCity ? store.basicInfo.nearestCity.toLowerCase().replace(/ /g, '-') : 'city'
})

const fullMultilangUrl = computed(() => {
  if (!currentLangData.value?.slug) return ''

  const frontendUrl = 'http://localhost:3001' // You can change this to production URL later
  return `${frontendUrl}/${store.currentLanguage}/tours/${currentLangData.value.slug}`
})

// --- Google SERP snippet preview ---
const previewTitle = computed(() => {
  const t = (currentLangData.value?.metaTitle || currentLangData.value?.title || '').trim()
  return t || 'Título del tour | Incalake'
})

const previewDescription = computed(() => {
  const d = (currentLangData.value?.metaDescription || '').trim()
  return d || 'La meta descripción aparecerá aquí. Escribe un resumen atractivo de 150-160 caracteres para mejorar el clic desde Google.'
})

const previewBreadcrumb = computed(() => {
  const slug = currentLangData.value?.slug || 'tour-slug'
  return `incalake.com › ${store.currentLanguage} › tours › ${slug}`
})

// Resolve a stored image path to an absolute URL (mirrors Step5's helper).
const resolveImg = (url: string) => {
  if (!url) return ''
  if (url.startsWith('http') || url.startsWith('data:') || url.startsWith('blob:')) return url
  const base = (config.public.apiUrl as string).replace('/api', '')
  const path = url.startsWith('/') ? url : `/${url}`
  const finalPath = path.startsWith('/storage') ? path : `/storage${path}`
  return `${base}${finalPath}`
}

const previewImage = computed(() => {
  const imgs = store.multimedia?.images || []
  const primary = imgs.find((i: any) => i.isPrimary) || imgs[0]
  return primary?.url ? resolveImg(primary.url) : ''
})

// Lowest active price across all age stages — what Google would surface.
const previewPrice = computed(() => {
  let min = Infinity
  for (const stage of store.commercialRules?.ageStages || []) {
    if (!stage.active) continue
    for (const nat of stage.nationalities || []) {
      for (const r of nat.ranges || []) {
        const p = Number(r.price)
        if (Number.isFinite(p) && p > 0 && p < min) min = p
      }
    }
  }
  return min === Infinity ? null : min
})

const sanitizeSlug = (e: Event) => {
  const input = e.target as HTMLInputElement
  const sanitized = input.value
    .toLowerCase()
    .replace(/ /g, '-')
    .replace(/[^\w-]+/g, '')

  const langData = store.contentSEO[store.currentLanguage]
  if (langData) {
    langData.slug = sanitized
  }
}

onMounted(() => {
  fetchCityData()
})

// Watch for city changes to update URL preview
watch(() => store.basicInfo.cityId, (newCityId) => {
  if (newCityId) {
    fetchCityData()
  }
}, { immediate: false })
</script>
