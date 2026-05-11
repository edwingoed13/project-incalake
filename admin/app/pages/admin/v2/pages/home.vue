<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Language {
  id: number
  code: string
  country: string
}

interface HomeContent {
  hero: { title: string; subtitle: string; image: string }
  trust_signals: { icon: string; title: string; description: string }[]
  destinations: { label: string; title: string }
  featured: { label: string; title: string }
  why_us: { icon: string; title: string; description: string }[]
  why_title: string
  search_placeholder: string
  search_btn: string
  trending_label: string
  view_all: string
}

const config = useRuntimeConfig()
const auth = useAuthStore()
const toast = useToast()
const { confirm } = useConfirm()

const langFlags: Record<string, string> = {
  ES: '🇪🇸', EN: '🇬🇧', PT: '🇵🇹', FR: '🇫🇷', DE: '🇩🇪', IT: '🇮🇹',
}

const languages = ref<Language[]>([])
const currentLang = ref<Language | null>(null)
const allContents = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const translating = ref(false)
const published = ref(true)
const heroUploading = ref(false)
const heroDropActive = ref(false)

const defaultForm = (): HomeContent => ({
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

const form = ref<HomeContent>(defaultForm())

const headers = () => ({
  Authorization: `Bearer ${auth.token || localStorage.getItem('auth_token') || ''}`,
  Accept: 'application/json',
})

const hasSpanishContent = computed(() => allContents.value.some(c => c.language_code === 'ES'))

const getStatus = (langId: number): 'published' | 'draft' | 'empty' => {
  const c = allContents.value.find(x => x.language_id === langId)
  if (!c) return 'empty'
  return c.published ? 'published' : 'draft'
}

const statusColor = (status: string) => {
  if (status === 'published') return 'bg-success'
  if (status === 'draft') return 'bg-warning'
  return 'bg-elevated'
}

const fetchLanguages = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/languages?all=true`)
    if (res.success) languages.value = res.data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar los idiomas.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const fetchContents = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/home`, { headers: headers() })
    if (res.success) allContents.value = res.data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cargar el contenido.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const selectLanguage = (lang: Language) => {
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

const save = async () => {
  if (!currentLang.value) return
  saving.value = true
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/home`, {
      method: 'PUT',
      headers: headers(),
      body: {
        language_id: currentLang.value.id,
        content: form.value,
        published: published.value,
      },
    })
    if (res.success) {
      toast.add({
        title: 'Contenido guardado',
        description: `Idioma: ${currentLang.value.country}`,
        icon: 'i-lucide-circle-check',
        color: 'success',
      })
      await fetchContents()
      if (currentLang.value) selectLanguage(currentLang.value)
    }
  } catch (e: any) {
    toast.add({ title: 'Error al guardar', description: e.data?.message || e.message, color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    saving.value = false
  }
}

const translateFromSpanish = async () => {
  if (!currentLang.value) return
  const esLang = languages.value.find(l => l.code === 'ES')
  if (!esLang) return

  const ok = await confirm({
    title: 'Traducir desde español',
    description: `La IA generará el contenido en ${currentLang.value.country} a partir del español. Revisa antes de publicar. ¿Continuar?`,
    confirmLabel: 'Traducir con IA',
    confirmColor: 'primary',
    confirmIcon: 'i-lucide-sparkles',
    icon: 'i-lucide-sparkles',
    iconColor: 'primary',
  })
  if (!ok) return

  translating.value = true
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/home/translate`, {
      method: 'POST',
      headers: headers(),
      body: {
        source_language_id: esLang.id,
        target_language_id: currentLang.value.id,
      },
    })
    if (res.success) {
      toast.add({
        title: 'Traducción completada',
        description: res.message || 'Revisa el contenido antes de publicar.',
        icon: 'i-lucide-sparkles',
        color: 'success',
      })
      await fetchContents()
      if (currentLang.value) selectLanguage(currentLang.value)
    }
  } catch (e: any) {
    toast.add({ title: 'Error al traducir', description: e.data?.message || e.message, color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    translating.value = false
  }
}

const uploadHeroImage = async (file: File) => {
  if (!file.type.startsWith('image/')) {
    toast.add({ title: 'Archivo inválido', description: 'Selecciona una imagen.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  heroUploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/upload-image`, {
      method: 'POST',
      headers: { Authorization: headers().Authorization },
      body: formData,
    })
    if (res.success) {
      form.value.hero.image = res.url
      toast.add({ title: 'Imagen subida', icon: 'i-lucide-circle-check', color: 'success' })
    }
  } catch (e: any) {
    toast.add({ title: 'Error al subir', description: e.data?.message || e.message, color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    heroUploading.value = false
    heroDropActive.value = false
  }
}

const handleHeroFileChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) uploadHeroImage(file)
}

const handleHeroDrop = (e: DragEvent) => {
  heroDropActive.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) uploadHeroImage(file)
}

onMounted(async () => {
  await fetchLanguages()
  await fetchContents()
  const esLang = languages.value.find(l => l.code === 'ES')
  if (esLang) selectLanguage(esLang)
})
</script>

<template>
  <UDashboardPanel id="home-page-v2">
    <template #header>
      <UDashboardNavbar title="Home Page">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="currentLang && currentLang.code !== 'ES' && hasSpanishContent"
            icon="i-lucide-sparkles"
            color="secondary"
            variant="subtle"
            :loading="translating"
            @click="translateFromSpanish"
          >
            {{ translating ? 'Traduciendo...' : 'Traducir desde ES con IA' }}
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-6 pb-32">
        <div>
          <h2 class="text-2xl font-bold">Contenido de la página principal</h2>
          <p class="text-sm text-muted mt-1">Gestiona los textos e imágenes que ven los clientes en cada idioma</p>
        </div>

        <!-- Language tabs -->
        <div class="flex items-center gap-2 flex-wrap">
          <UButton
            v-for="lang in languages"
            :key="lang.id"
            :color="currentLang?.id === lang.id ? 'primary' : 'neutral'"
            :variant="currentLang?.id === lang.id ? 'solid' : 'subtle'"
            size="md"
            class="font-bold"
            @click="selectLanguage(lang)"
          >
            <span class="text-base">{{ langFlags[lang.code] || '🌐' }}</span>
            <span>{{ lang.country }}</span>
            <span :class="['size-2 rounded-full ml-1', statusColor(getStatus(lang.id))]" :title="getStatus(lang.id)" />
          </UButton>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="py-20 flex flex-col items-center gap-3">
          <UIcon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin" />
          <p class="text-sm text-muted">Cargando contenido...</p>
        </div>

        <!-- Editor -->
        <div v-else-if="currentLang" class="space-y-5">
          <!-- Hero Section -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-primary/10 flex items-center justify-center">
                  <UIcon name="i-lucide-image" class="size-5 text-primary" />
                </div>
                <h3 class="text-base font-bold">Hero (cabecera)</h3>
              </div>
            </template>

            <div class="space-y-4">
              <UFormField label="Título (H2)">
                <UInput v-model="form.hero.title" placeholder="Título principal" class="w-full font-semibold" />
              </UFormField>
              <UFormField label="Subtítulo">
                <UTextarea v-model="form.hero.subtitle" :rows="2" placeholder="Subtítulo descriptivo" class="w-full" />
              </UFormField>
              <UFormField label="Imagen de fondo">
                <div class="flex gap-4 items-start flex-wrap">
                  <div class="w-48 h-28 rounded-xl overflow-hidden border border-default bg-elevated shrink-0">
                    <img v-if="form.hero.image" :src="form.hero.image" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-muted">
                      <UIcon name="i-lucide-image-off" class="size-8" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-[200px] space-y-2">
                    <div
                      :class="[
                        'border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-all relative',
                        heroDropActive ? 'border-primary bg-primary/5' : 'border-default hover:border-primary/50',
                      ]"
                      @dragover.prevent="heroDropActive = true"
                      @dragleave.prevent="heroDropActive = false"
                      @drop.prevent="handleHeroDrop"
                    >
                      <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleHeroFileChange" />
                      <UIcon
                        v-if="heroUploading"
                        name="i-lucide-loader-circle"
                        class="size-6 text-primary animate-spin mx-auto"
                      />
                      <template v-else>
                        <UIcon name="i-lucide-cloud-upload" class="size-6 text-muted mx-auto" />
                        <p class="text-[10px] font-bold text-muted mt-1">Arrastra una imagen o click para subir</p>
                      </template>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-[9px] font-bold text-muted uppercase shrink-0">O URL:</span>
                      <UInput v-model="form.hero.image" placeholder="https://..." size="sm" class="flex-1" />
                    </div>
                    <UButton
                      v-if="form.hero.image"
                      icon="i-lucide-x"
                      color="error"
                      variant="link"
                      size="xs"
                      @click="form.hero.image = ''"
                    >
                      Quitar imagen
                    </UButton>
                  </div>
                </div>
              </UFormField>
            </div>
          </UCard>

          <!-- Trust Signals -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-success/10 flex items-center justify-center">
                  <UIcon name="i-lucide-shield-check" class="size-5 text-success" />
                </div>
                <h3 class="text-base font-bold">Indicadores de confianza</h3>
              </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <UCard
                v-for="(signal, idx) in form.trust_signals"
                :key="idx"
                :ui="{ body: 'p-3 space-y-2' }"
                class="bg-elevated/40"
              >
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">{{ signal.icon }}</span>
                  <UInput v-model="signal.icon" placeholder="icon name" size="xs" class="w-28 font-mono" />
                </div>
                <UInput v-model="signal.title" placeholder="Título" class="w-full font-semibold" size="sm" />
                <UInput v-model="signal.description" placeholder="Descripción" class="w-full" size="sm" />
              </UCard>
            </div>
          </UCard>

          <!-- Destinations -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-info/10 flex items-center justify-center">
                  <UIcon name="i-lucide-map-pin" class="size-5 text-info" />
                </div>
                <h3 class="text-base font-bold">Sección de destinos</h3>
              </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <UFormField label="Etiqueta">
                <UInput v-model="form.destinations.label" class="w-full font-semibold" />
              </UFormField>
              <UFormField label="Título">
                <UInput v-model="form.destinations.title" class="w-full font-semibold" />
              </UFormField>
            </div>
            <UAlert
              color="neutral"
              variant="subtle"
              icon="i-lucide-info"
              description="Las ciudades se gestionan desde la base de datos. Aquí solo defines los textos de la sección."
              class="mt-3"
            />
          </UCard>

          <!-- Featured Tours -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-warning/10 flex items-center justify-center">
                  <UIcon name="i-lucide-star" class="size-5 text-warning" />
                </div>
                <h3 class="text-base font-bold">Tours destacados</h3>
              </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <UFormField label="Etiqueta">
                <UInput v-model="form.featured.label" class="w-full font-semibold" />
              </UFormField>
              <UFormField label="Título">
                <UInput v-model="form.featured.title" class="w-full font-semibold" />
              </UFormField>
            </div>
          </UCard>

          <!-- Why choose us -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-secondary/10 flex items-center justify-center">
                  <UIcon name="i-lucide-circle-help" class="size-5 text-secondary" />
                </div>
                <h3 class="text-base font-bold">¿Por qué elegirnos?</h3>
              </div>
            </template>
            <UFormField label="Título de sección" class="mb-4">
              <UInput v-model="form.why_title" class="w-full font-semibold" />
            </UFormField>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <UCard
                v-for="(item, idx) in form.why_us"
                :key="idx"
                :ui="{ body: 'p-3 space-y-2' }"
                class="bg-elevated/40"
              >
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">{{ item.icon }}</span>
                  <UInput v-model="item.icon" placeholder="icon" size="xs" class="w-28 font-mono" />
                </div>
                <UInput v-model="item.title" placeholder="Título" class="w-full font-semibold" size="sm" />
                <UTextarea v-model="item.description" :rows="2" placeholder="Descripción" class="w-full" size="sm" />
              </UCard>
            </div>
          </UCard>

          <!-- Other texts -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <div class="size-9 rounded-lg bg-neutral/10 flex items-center justify-center">
                  <UIcon name="i-lucide-type" class="size-5 text-muted" />
                </div>
                <h3 class="text-base font-bold">Otros textos</h3>
              </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
              <UFormField label="Placeholder buscador">
                <UInput v-model="form.search_placeholder" class="w-full" size="sm" />
              </UFormField>
              <UFormField label="Botón buscar">
                <UInput v-model="form.search_btn" class="w-full" size="sm" />
              </UFormField>
              <UFormField label="Etiqueta tendencias">
                <UInput v-model="form.trending_label" class="w-full" size="sm" />
              </UFormField>
              <UFormField label="Ver todos">
                <UInput v-model="form.view_all" class="w-full" size="sm" />
              </UFormField>
            </div>
          </UCard>
        </div>
      </div>

      <!-- Sticky action bar -->
      <div
        v-if="currentLang && !loading"
        class="sticky bottom-0 left-0 right-0 bg-default/95 backdrop-blur-sm border-t border-default p-4 flex items-center justify-between gap-3 flex-wrap"
      >
        <div class="flex items-center gap-3">
          <USwitch v-model="published" label="Publicada" />
          <UBadge
            :color="published ? 'success' : 'warning'"
            variant="subtle"
            size="sm"
            :icon="published ? 'i-lucide-eye' : 'i-lucide-file-text'"
          >
            {{ published ? 'Visible al público' : 'Solo borrador' }}
          </UBadge>
          <span class="text-xs text-muted hidden md:inline">
            Editando: <span class="font-bold">{{ currentLang.country }}</span>
          </span>
        </div>
        <UButton
          icon="i-lucide-save"
          color="primary"
          size="lg"
          :loading="saving"
          @click="save"
        >
          {{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </UButton>
      </div>
    </template>
  </UDashboardPanel>
</template>
