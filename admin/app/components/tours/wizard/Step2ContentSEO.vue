<template>
  <div class="flex flex-col gap-6">
    <WizardSection title="SEO y buscadores" icon="i-lucide-search">
      <template #actions>
        <div class="flex items-center gap-1">
          <UButton
            v-for="lang in tourLanguages"
            :key="lang"
            size="xs"
            :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
            :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
            class="uppercase font-black tracking-wider"
            :title="store.draftChangedLanguages.includes(lang.toUpperCase())
              ? `${lang.toUpperCase()} tiene cambios sin publicar`
              : undefined"
            @click="store.currentLanguage = lang"
          >
            {{ lang }}
            <!-- A dot on the languages that differ from the live site, so the
                 switcher itself says where the unpublished work is instead of
                 making the operator click through all six. -->
            <span
              v-if="store.draftChangedLanguages.includes(lang.toUpperCase())"
              class="size-1.5 rounded-full shrink-0"
              :class="store.currentLanguage === lang ? 'bg-white' : 'bg-info-500'"
              aria-label="con cambios sin publicar"
            />
          </UButton>
        </div>
      </template>

      <div v-if="currentLangData" class="space-y-6">
        <!-- Section: SEO Settings -->
        <section class="space-y-3">

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

          <UFormField
            label="Palabras clave (SEO)"
            hint="Los términos por los que quieres que te encuentren. Al menos uno debería aparecer en el meta título."
          >
            <div class="space-y-2">
              <div class="flex gap-2">
                <UInput
                  v-model="newKeyword"
                  placeholder="ej. tour islas uros puno"
                  class="flex-1"
                  @keydown.enter.prevent="addKeyword"
                />
                <UButton
                  icon="i-lucide-plus"
                  color="neutral"
                  variant="subtle"
                  :disabled="!newKeyword.trim()"
                  @click="addKeyword"
                >
                  Agregar
                </UButton>
              </div>
              <!-- Plain tags. There used to be a star on each one picking a
                   "primary" keyword: it set a flag nothing anywhere read, and
                   the unpicked ones wore a crossed-out star that reads as
                   blocked rather than secondary. Ranking your own keywords is
                   not a decision this tool should be asking for — they all go
                   to the same place. -->
              <div v-if="currentLangData.keywords?.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="(kw, i) in currentLangData.keywords"
                  :key="i"
                  class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full text-xs border bg-elevated border-default text-default"
                >
                  {{ kw.keyword }}
                  <button type="button" class="hover:text-red-500 opacity-60 hover:opacity-100" title="Quitar" @click="removeKeyword(i)">
                    <UIcon name="i-lucide-x" class="size-3.5" />
                  </button>
                </span>
              </div>

              <!-- The check that made the star worth having, without the star:
                   it asks whether ANY keyword made it into the two lines Google
                   shows, which needs no ranking to answer. -->
              <div v-if="currentLangData.keywords?.length" class="space-y-1 pt-1">
                <p class="text-[11px] flex items-center gap-1.5" :class="keywordInTitle ? 'text-success' : 'text-warning'">
                  <UIcon :name="keywordInTitle ? 'i-lucide-circle-check' : 'i-lucide-circle-alert'" class="size-3.5 shrink-0" />
                  <span v-if="keywordInTitle">«{{ keywordInTitle }}» aparece en el meta título</span>
                  <span v-else>Ninguna palabra clave aparece en el meta título</span>
                </p>
                <p class="text-[11px] flex items-center gap-1.5" :class="keywordInDescription ? 'text-success' : 'text-warning'">
                  <UIcon :name="keywordInDescription ? 'i-lucide-circle-check' : 'i-lucide-circle-alert'" class="size-3.5 shrink-0" />
                  <span v-if="keywordInDescription">«{{ keywordInDescription }}» aparece en la meta descripción</span>
                  <span v-else>Ninguna aparece en la meta descripción</span>
                </p>
              </div>
              <p v-else class="text-[11px] text-muted">Sin palabras clave aún. Recomendado: 3-6.</p>
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
    </WizardSection>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, onMounted, watch } from 'vue'
import WizardSection from './WizardSection.vue'

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

// Whether ANY keyword made it into the two lines a searcher sees, and which
// one. Accent- and case-insensitive: "Uyuni" and "uyuni" are the same word to a
// person, and the operator should not be told otherwise.
const norm = (v: string) =>
  (v || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')

const keywordsFor = (): string[] => {
  const list = store.contentSEO[store.currentLanguage]?.keywords
  return Array.isArray(list) ? list.map((k: any) => k?.keyword).filter(Boolean) : []
}

// Returns the matching keyword (truthy) or '', so the template can name it.
const firstMatchIn = (field: 'metaTitle' | 'metaDescription') => {
  const haystack = norm((store.contentSEO[store.currentLanguage] as any)?.[field] || '')
  if (!haystack) return ''
  return keywordsFor().find(k => haystack.includes(norm(k))) || ''
}

const keywordInTitle = computed(() => firstMatchIn('metaTitle'))
const keywordInDescription = computed(() => firstMatchIn('metaDescription'))

// --- SEO keywords (per language) ---
const newKeyword = ref('')

function ensureKeywords(): Array<{ keyword: string; is_primary: boolean }> | undefined {
  const d = store.contentSEO[store.currentLanguage]
  if (!d) return undefined
  if (!Array.isArray(d.keywords)) d.keywords = []
  return d.keywords
}

function addKeyword() {
  const word = newKeyword.value.trim()
  if (!word) return
  const list = ensureKeywords()
  if (!list) return
  if (list.some(k => k.keyword.toLowerCase() === word.toLowerCase())) { newKeyword.value = ''; return }
  // is_primary stays on the row because the column still exists, but nothing
  // reads it and the UI no longer asks anyone to set it.
  list.push({ keyword: word, is_primary: list.length === 0 })
  newKeyword.value = ''
}

function removeKeyword(i: number) {
  const list = ensureKeywords()
  if (!list) return
  list.splice(i, 1)
  list.forEach((k, idx) => { k.is_primary = idx === 0 })
}

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
