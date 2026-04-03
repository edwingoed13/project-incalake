<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Home Page Content</h3>
        <p class="text-slate-500 dark:text-slate-400">Manage the home page content for each language.</p>
      </div>
    </div>

    <!-- Language Tabs -->
    <div class="flex items-center gap-2 mb-6">
      <button
        v-for="lang in languages"
        :key="lang.id"
        @click="selectLanguage(lang)"
        :class="currentLang?.id === lang.id
          ? 'bg-primary text-white shadow-md'
          : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
        class="px-4 py-2 text-sm font-bold rounded-xl transition-all flex items-center gap-2"
      >
        <span>{{ langFlags[lang.code] || '🌐' }}</span>
        {{ lang.country }}
        <span v-if="getStatus(lang.id) === 'published'" class="size-2 rounded-full bg-green-400"></span>
        <span v-else-if="getStatus(lang.id) === 'draft'" class="size-2 rounded-full bg-yellow-400"></span>
      </button>

      <!-- Translate button -->
      <button
        v-if="currentLang && currentLang.code !== 'ES' && hasSpanishContent"
        @click="translateFromSpanish"
        :disabled="translating"
        class="ml-auto px-4 py-2 text-sm font-bold rounded-xl bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 hover:bg-purple-200 transition-all flex items-center gap-2 disabled:opacity-50"
      >
        <span v-if="translating" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
        <span v-else class="material-symbols-outlined text-base">auto_awesome</span>
        {{ translating ? 'Translating...' : 'Translate from ES with AI' }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
    </div>

    <div v-else-if="currentLang" class="space-y-8">
      <!-- Hero Section -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">image</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Hero Section</h4>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Title (H2)</label>
            <input v-model="form.hero.title" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-sm font-semibold" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Subtitle</label>
            <textarea v-model="form.hero.subtitle" rows="2" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-sm resize-none"></textarea>
          </div>
          <!-- Hero Background Image -->
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 block">Background Image</label>
            <div class="flex gap-4 items-start">
              <!-- Preview -->
              <div class="w-48 h-28 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 shrink-0">
                <img v-if="form.hero.image" :src="form.hero.image" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                  <span class="material-symbols-outlined text-3xl">image</span>
                </div>
              </div>
              <!-- Upload + URL -->
              <div class="flex-1 space-y-2">
                <div
                  @dragover.prevent="heroDropActive = true"
                  @dragleave.prevent="heroDropActive = false"
                  @drop.prevent="handleHeroDrop"
                  :class="heroDropActive ? 'border-primary bg-primary/5' : 'border-slate-200 dark:border-slate-800'"
                  class="border-2 border-dashed rounded-xl p-4 text-center cursor-pointer hover:border-primary/50 transition-all relative"
                >
                  <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleHeroFileChange" />
                  <span v-if="heroUploading" class="material-symbols-outlined animate-spin text-primary">progress_activity</span>
                  <template v-else>
                    <span class="material-symbols-outlined text-slate-400 text-xl">cloud_upload</span>
                    <p class="text-[10px] font-bold text-slate-400 mt-1">Drop image or click to upload</p>
                  </template>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-[9px] font-bold text-slate-400 uppercase">or URL:</span>
                  <input v-model="form.hero.image" type="text" placeholder="https://..." class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-xs" />
                </div>
                <button v-if="form.hero.image" @click="form.hero.image = ''" class="text-[10px] font-bold text-red-500 hover:underline">Remove image</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Trust Signals -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">verified</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Trust Signals</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="(signal, idx) in form.trust_signals" :key="idx" class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl space-y-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">{{ signal.icon }}</span>
              <input v-model="signal.icon" type="text" placeholder="icon name" class="w-24 px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
            </div>
            <input v-model="signal.title" type="text" placeholder="Title" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
            <input v-model="signal.description" type="text" placeholder="Description" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" />
          </div>
        </div>
      </section>

      <!-- Destinations Section -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">location_on</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Destinations Section</h4>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Section Label</label>
            <input v-model="form.destinations.label" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Section Title</label>
            <input v-model="form.destinations.title" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
          </div>
        </div>
        <p class="text-[10px] text-slate-400 mt-3 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">info</span>
          Cities are managed from the database. These are only the section labels.
        </p>
      </section>

      <!-- Featured Tours Section -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">star</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Featured Tours Section</h4>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Section Label</label>
            <input v-model="form.featured.label" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Section Title</label>
            <input v-model="form.featured.title" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
          </div>
        </div>
      </section>

      <!-- Why Choose Us -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">help</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Why Choose Us</h4>
        </div>
        <div>
          <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Section Title</label>
          <input v-model="form.why_title" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold mb-4" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="(item, idx) in form.why_us" :key="idx" class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl space-y-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">{{ item.icon }}</span>
              <input v-model="item.icon" type="text" placeholder="icon" class="w-24 px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
            </div>
            <input v-model="item.title" type="text" placeholder="Title" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm font-semibold" />
            <textarea v-model="item.description" rows="2" placeholder="Description" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm resize-none"></textarea>
          </div>
        </div>
      </section>

      <!-- Other texts -->
      <section class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-primary">text_fields</span>
          <h4 class="text-lg font-bold text-slate-900 dark:text-white">Other Texts</h4>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Search Placeholder</label>
            <input v-model="form.search_placeholder" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Search Button</label>
            <input v-model="form.search_btn" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">Trending Label</label>
            <input v-model="form.trending_label" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" />
          </div>
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1 block">View All</label>
            <input v-model="form.view_all" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" />
          </div>
        </div>
      </section>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input v-model="published" type="checkbox" class="w-4 h-4 rounded text-primary border-slate-300 focus:ring-primary/20" />
          <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Published</span>
        </label>
        <button
          @click="save"
          :disabled="saving"
          class="px-8 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 flex items-center gap-2"
        >
          <span v-if="saving" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
          <span v-else class="material-symbols-outlined text-base">save</span>
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: 'admin' })

const config = useRuntimeConfig()
const API = config.public.apiUrl
const auth = useAuthStore()

const langFlags: Record<string, string> = {
  ES: '🇪🇸', EN: '🇬🇧', PT: '🇵🇹', FR: '🇫🇷', DE: '🇩🇪', IT: '🇮🇹'
}

const languages = ref<any[]>([])
const currentLang = ref<any>(null)
const allContents = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const translating = ref(false)
const published = ref(true)
const heroUploading = ref(false)
const heroDropActive = ref(false)

const defaultForm = () => ({
  hero: { title: '', subtitle: '', image: '' },
  trust_signals: [
    { icon: 'cancel', title: '', description: '' },
    { icon: 'verified_user', title: '', description: '' },
    { icon: 'security', title: '', description: '' },
  ],
  destinations: { label: '', title: '' },
  featured: { label: '', title: '' },
  why_us: [
    { icon: 'public', title: '', description: '' },
    { icon: 'star', title: '', description: '' },
    { icon: 'verified', title: '', description: '' },
  ],
  search_placeholder: '',
  search_btn: '',
  trending_label: '',
  view_all: '',
  why_title: '',
})

const form = ref(defaultForm())

const hasSpanishContent = computed(() => allContents.value.some(c => c.language_code === 'ES'))

function getStatus(langId: number) {
  const c = allContents.value.find(x => x.language_id === langId)
  if (!c) return 'empty'
  return c.published ? 'published' : 'draft'
}

async function fetchLanguages() {
  try {
    const res: any = await $fetch(`${API}/languages?all=true`)
    if (res.success) languages.value = res.data
  } catch (e) { console.error(e) }
}

async function fetchContents() {
  loading.value = true
  try {
    const res: any = await $fetch(`${API}/admin/pages/home`, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    if (res.success) allContents.value = res.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

function selectLanguage(lang: any) {
  currentLang.value = lang
  const existing = allContents.value.find(c => c.language_id === lang.id)
  if (existing) {
    form.value = JSON.parse(JSON.stringify(existing.content))
    published.value = existing.published
  } else {
    form.value = defaultForm()
    published.value = false
  }
}

async function save() {
  if (!currentLang.value) return
  saving.value = true
  try {
    const res: any = await $fetch(`${API}/admin/pages/home`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
      body: {
        language_id: currentLang.value.id,
        content: form.value,
        published: published.value,
      }
    })
    if (res.success) {
      alert('Content saved successfully!')
      await fetchContents()
      selectLanguage(currentLang.value)
    }
  } catch (e: any) {
    alert('Error: ' + (e.data?.message || e.message))
  } finally { saving.value = false }
}

async function translateFromSpanish() {
  if (!currentLang.value) return
  const esLang = languages.value.find(l => l.code === 'ES')
  if (!esLang) return

  translating.value = true
  try {
    const res: any = await $fetch(`${API}/admin/pages/home/translate`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
      body: {
        source_language_id: esLang.id,
        target_language_id: currentLang.value.id,
      }
    })
    if (res.success) {
      alert(res.message || 'Translated! Review before publishing.')
      await fetchContents()
      selectLanguage(currentLang.value)
    }
  } catch (e: any) {
    alert('Error: ' + (e.data?.message || e.message))
  } finally { translating.value = false }
}

async function uploadHeroImage(file: File) {
  if (!file.type.startsWith('image/')) return
  heroUploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res: any = await $fetch(`${API}/admin/pages/upload-image`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${auth.token}` },
      body: formData,
    })
    if (res.success) {
      form.value.hero.image = res.url
    }
  } catch (e: any) {
    alert('Upload error: ' + (e.data?.message || e.message))
  } finally {
    heroUploading.value = false
    heroDropActive.value = false
  }
}

function handleHeroFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) uploadHeroImage(file)
}

function handleHeroDrop(e: DragEvent) {
  const file = e.dataTransfer?.files?.[0]
  if (file) uploadHeroImage(file)
}

onMounted(async () => {
  await fetchLanguages()
  await fetchContents()
  // Default to Spanish
  const esLang = languages.value.find(l => l.code === 'ES')
  if (esLang) selectLanguage(esLang)
})
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.5);
}
</style>
