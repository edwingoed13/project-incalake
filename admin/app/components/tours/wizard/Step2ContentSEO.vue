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
            @click="store.currentLanguage = lang"
          >
            {{ lang }}
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
            hint="La principal (★) define el foco del tour; las demás son secundarias. Útiles para el contenido y futuras integraciones."
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
              <div v-if="currentLangData.keywords?.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="(kw, i) in currentLangData.keywords"
                  :key="i"
                  class="inline-flex items-center gap-1.5 pl-2 pr-1.5 py-1 rounded-full text-xs border"
                  :class="kw.is_primary ? 'bg-primary/10 border-primary/40 text-primary font-semibold' : 'bg-elevated border-default text-default'"
                >
                  <button
                    type="button"
                    :title="kw.is_primary ? 'Palabra clave principal' : 'Marcar como principal'"
                    @click="setPrimaryKeyword(i)"
                  >
                    <UIcon :name="kw.is_primary ? 'i-lucide-star' : 'i-lucide-star-off'" class="size-3.5" :class="kw.is_primary ? '' : 'opacity-50'" />
                  </button>
                  {{ kw.keyword }}
                  <button type="button" class="hover:text-red-500 opacity-60 hover:opacity-100" title="Quitar" @click="removeKeyword(i)">
                    <UIcon name="i-lucide-x" class="size-3.5" />
                  </button>
                </span>
              </div>
              <p v-else class="text-[11px] text-muted">Sin palabras clave aún. Recomendado: 3-6 (una principal).</p>
            </div>
          </UFormField>

          <UFormField
            label="Preguntas frecuentes (FAQ)"
            hint="Mejoran el SEO (resultados enriquecidos) y ayudan a que el tour aparezca en respuestas de IA (ChatGPT/Perplexity/AI Overviews). Se editan por idioma."
          >
            <div class="space-y-3">
              <div
                v-for="(faq, i) in (currentLangData.faqs || [])"
                :key="i"
                class="rounded-lg border border-default p-3 space-y-2 bg-elevated/40"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-[11px] font-black uppercase tracking-wider text-muted">Pregunta {{ i + 1 }}</span>
                  <div class="flex items-center gap-1">
                    <UButton icon="i-lucide-arrow-up" size="xs" color="neutral" variant="ghost" :disabled="i === 0" @click="moveFaq(i, -1)" />
                    <UButton icon="i-lucide-arrow-down" size="xs" color="neutral" variant="ghost" :disabled="i === (currentLangData.faqs.length - 1)" @click="moveFaq(i, 1)" />
                    <UButton icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" @click="removeFaq(i)" />
                  </div>
                </div>
                <UInput v-model="faq.question" placeholder="¿Pregunta? ej. ¿Cuánto dura el tour?" class="w-full" />
                <UTextarea v-model="faq.answer" :rows="2" placeholder="Respuesta clara y directa (ideal para snippets e IA)." class="w-full" />
              </div>
              <UButton icon="i-lucide-plus" color="neutral" variant="subtle" size="sm" @click="addFaq">
                Agregar pregunta
              </UButton>
              <p v-if="!(currentLangData.faqs?.length)" class="text-[11px] text-muted">Sin FAQ aún. Recomendado: 4-6 (duración, ubicación, qué llevar, cancelación, precio…).</p>
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
  list.push({ keyword: word, is_primary: list.length === 0 }) // first one is primary by default
  newKeyword.value = ''
}

function removeKeyword(i: number) {
  const list = ensureKeywords()
  if (!list) return
  const wasPrimary = list[i]?.is_primary
  list.splice(i, 1)
  if (wasPrimary && list.length && !list.some(k => k.is_primary)) list[0].is_primary = true
}

function setPrimaryKeyword(i: number) {
  const list = ensureKeywords()
  if (!list) return
  list.forEach((k, idx) => { k.is_primary = idx === i })
}

// --- FAQs (per language) ---
function ensureFaqs(): Array<{ question: string; answer: string }> | undefined {
  const d = store.contentSEO[store.currentLanguage]
  if (!d) return undefined
  if (!Array.isArray(d.faqs)) d.faqs = []
  return d.faqs
}

function addFaq() {
  const list = ensureFaqs()
  if (!list) return
  list.push({ question: '', answer: '' })
}

function removeFaq(i: number) {
  const list = ensureFaqs()
  if (!list) return
  list.splice(i, 1)
}

function moveFaq(i: number, dir: number) {
  const list = ensureFaqs()
  if (!list) return
  const j = i + dir
  if (j < 0 || j >= list.length) return
  const [item] = list.splice(i, 1)
  list.splice(j, 0, item)
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
