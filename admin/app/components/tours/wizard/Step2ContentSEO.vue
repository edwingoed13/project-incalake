<template>
  <div class="flex flex-col gap-8">
    <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
      <!-- Content title for current language -->
      <div class="px-8 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg font-bold">translate</span>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Editando contenido en</p>
            <div class="flex items-center gap-2 mt-1">
              <button 
                v-for="lang in ['es', 'en', 'fr', 'de']" 
                :key="lang"
                @click="store.currentLanguage = lang"
                class="px-2 py-0.5 rounded text-[10px] font-black uppercase transition-all"
                :class="store.currentLanguage === lang ? 'bg-primary text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500 hover:bg-slate-300 dark:hover:bg-slate-700'"
              >
                {{ lang }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Content per Language -->
      <div v-if="currentLangData" class="p-8 space-y-10">
        <!-- Section: Tour Content -->
        <section class="space-y-6">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">description</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Tour Content</h3>
          </div>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Tour Title (Public)</label>
              <input 
                v-model="currentLangData.title"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white" 
                placeholder="e.g. Magical Paris Sunset Tour" 
                type="text"
              />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Descripción Corta</label>
              <textarea 
                v-model="currentLangData.shortDescription"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white min-h-[80px] resize-none" 
                placeholder="Resumen breve para listados..."
              ></textarea>
            </div>
          </div>
        </section>

        <!-- Section: SEO Settings -->
        <section class="space-y-6 pt-10 border-t border-slate-100 dark:border-slate-800/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">search</span>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">SEO Settings</h3>
            </div>
            <div class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Google Preview</div>
          </div>

          <div class="space-y-6">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Meta Title</label>
              <input 
                v-model="currentLangData.metaTitle"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white" 
                placeholder="Magical Paris Sunset Tour | Incalake" 
                type="text"
              />
              <div class="flex justify-between mt-1">
                <p class="text-[11px] text-slate-400">Recommended length: 50-60 chars</p>
                <p class="text-[11px]" :class="currentLangData.metaTitle.length > 60 ? 'text-red-500 font-bold' : 'text-slate-400'">{{ (currentLangData.metaTitle || '').length }}/60</p>
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Meta Description</label>
              <textarea 
                v-model="currentLangData.metaDescription"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white min-h-[100px] resize-none" 
                placeholder="Hook users with a brief summary for search results..."
              ></textarea>
              <p class="text-[11px] text-slate-400 mt-1">Recommended length: 150-160 chars</p>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-slate-700 dark:text-slate-300">URL Slug</label>
              <div class="flex group">
                <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-slate-500 text-xs font-medium">
                  incalake.com/{{ store.currentLanguage }}/{{ citySlugDisplay }}/
                </span>
                <input
                  v-model="currentLangData.slug"
                  class="flex-1 px-4 py-3 rounded-r-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white"
                  type="text"
                  @input="sanitizeSlug"
                />
              </div>
              <div v-if="fullMultilangUrl" class="mt-2 p-3 bg-primary/5 border border-primary/20 rounded-lg">
                <p class="text-[10px] font-black uppercase tracking-widest text-primary mb-1.5">Vista Previa URL Multilang</p>
                <a :href="fullMultilangUrl" target="_blank" class="text-sm font-mono text-slate-700 dark:text-slate-300 hover:text-primary transition-colors break-all">
                  {{ fullMultilangUrl }}
                </a>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, onMounted, watch } from 'vue'

const store = useTourWizardStore()
const config = useRuntimeConfig()
const defaultApiUrl = config.public.apiUrl

const cityData = ref<any>(null)

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
  if (!currentLangData.value?.slug || !citySlugDisplay.value) return ''

  const frontendUrl = 'http://localhost:3001' // You can change this to production URL later
  return `${frontendUrl}/${store.currentLanguage}/${citySlugDisplay.value}/${currentLangData.value.slug}`
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
