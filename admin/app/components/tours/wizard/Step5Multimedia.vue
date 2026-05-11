<template>
  <div class="flex flex-col gap-6 pb-20">
    <!-- Video Section (per language) -->
    <UCard :ui="{ header: 'p-0', body: isSectionExpanded('video') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
      <template #header>
        <button
          type="button"
          class="w-full p-4 flex items-center justify-between gap-3 flex-wrap hover:bg-elevated/40 transition-colors text-left"
          @click="toggleSection('video')"
        >
          <h3 class="text-base font-bold flex items-center gap-2">
            <UIcon
              name="i-lucide-chevron-down"
              class="size-4 text-muted transition-transform"
              :class="{ 'rotate-180': isSectionExpanded('video') }"
            />
            <UIcon name="i-lucide-play-circle" class="size-5 text-primary" />
            Video destacado
            <UBadge
              v-if="currentLangSeo?.youtubeUrl"
              color="success"
              variant="subtle"
              size="xs"
              icon="i-lucide-circle-check"
            >
              {{ store.currentLanguage.toUpperCase() }}
            </UBadge>
          </h3>
          <div class="flex gap-1" @click.stop>
            <UButton
              v-for="lang in videoLanguages"
              :key="lang"
              size="xs"
              :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
              :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
              class="uppercase font-black tracking-wider"
              :trailing-icon="store.contentSEO?.[lang]?.youtubeUrl ? 'i-lucide-circle-check-big' : undefined"
              @click="store.currentLanguage = lang"
            >
              {{ lang }}
            </UButton>
          </div>
        </button>
      </template>

      <div v-show="isSectionExpanded('video')" class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
        <!-- Input Card -->
        <div class="space-y-4">
          <UFormField
            :label="`URL del video — ${store.currentLanguage.toUpperCase()} (${currentLangLabel})`"
            hint="Soportados: YouTube, Vimeo. Cada idioma tiene su propio video."
          >
            <UInput
              :model-value="currentLangSeo?.youtubeUrl || ''"
              icon="i-lucide-link"
              placeholder="https://www.youtube.com/watch?v=..."
              class="w-full"
              @update:model-value="(v: string | number) => updateVideoUrl(String(v))"
            />
          </UFormField>

          <!-- Saved URL preview chip -->
          <div v-if="currentLangSeo?.youtubeUrl" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-elevated border border-default">
            <UIcon name="i-lucide-link" class="size-4 text-muted shrink-0" />
            <a :href="currentLangSeo.youtubeUrl" target="_blank" rel="noopener noreferrer"
               :title="currentLangSeo.youtubeUrl"
               class="flex-1 text-[11px] font-mono text-muted truncate hover:text-primary transition-colors">
              {{ currentLangSeo.youtubeUrl }}
            </a>
            <UButton
              size="xs"
              color="neutral"
              variant="ghost"
              :icon="urlCopied ? 'i-lucide-check' : 'i-lucide-copy'"
              :title="urlCopied ? 'Copiado' : 'Copiar URL'"
              @click="copyVideoUrl"
            />
            <UButton
              size="xs"
              color="neutral"
              variant="ghost"
              icon="i-lucide-external-link"
              :to="currentLangSeo.youtubeUrl"
              target="_blank"
              rel="noopener noreferrer"
              title="Abrir en nueva pestaña"
            />
          </div>
        </div>

        <!-- Preview Card -->
        <div class="relative group aspect-video rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 shadow-sm transition-all duration-500 hover:shadow-2xl">
           <img
            v-if="youtubeId"
            :src="`https://img.youtube.com/vi/${youtubeId}/maxresdefault.jpg`"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 brightness-75 group-hover:brightness-50"
            alt="Video Thumbnail"
           />
           <div v-else class="w-full h-full flex flex-col items-center justify-center text-muted gap-2">
              <UIcon name="i-lucide-youtube" class="size-10 opacity-50" />
              <p class="text-xs font-bold uppercase tracking-widest opacity-60 text-center">
                Vista previa del video ({{ store.currentLanguage.toUpperCase() }})<br/>
                <span class="text-[9px] font-medium normal-case tracking-normal">Ingresa una URL para ver aquí</span>
              </p>
           </div>

           <!-- Preview Overlay -->
           <div v-if="youtubeId" class="absolute inset-0 flex flex-col items-center justify-center gap-3 transition-all duration-500">
              <div class="size-14 rounded-full bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform shadow-2xl">
                 <UIcon name="i-lucide-play" class="size-7 fill-current" />
              </div>
              <UBadge color="neutral" variant="solid" size="xs" class="bg-slate-900/80 backdrop-blur-xl border border-white/10 uppercase tracking-widest">
                {{ store.currentLanguage.toUpperCase() }}
              </UBadge>
           </div>

           <!-- Delete Button -->
           <UButton
            v-if="youtubeId"
            icon="i-lucide-trash-2"
            color="error"
            size="sm"
            class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity"
            @click="updateVideoUrl('')"
           />
        </div>
      </div>
    </UCard>

    <!-- Gallery Layout Detection Section -->
    <UCard :ui="{ header: 'p-0', body: isSectionExpanded('layout') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
      <template #header>
        <button
          type="button"
          class="w-full p-4 flex items-center justify-between gap-3 flex-wrap hover:bg-elevated/40 transition-colors text-left"
          @click="toggleSection('layout')"
        >
          <h3 class="text-base font-bold flex items-center gap-2">
            <UIcon
              name="i-lucide-chevron-down"
              class="size-4 text-muted transition-transform"
              :class="{ 'rotate-180': isSectionExpanded('layout') }"
            />
            <UIcon name="i-lucide-layout-grid" class="size-5 text-primary" />
            Detección de layout de galería
            <UBadge color="primary" variant="subtle" size="xs" class="capitalize">
              {{ store.multimedia.galleryLayout }}
            </UBadge>
          </h3>
        </button>
      </template>

      <div v-show="isSectionExpanded('layout')" class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Layout Options -->
        <button 
          v-for="layout in layouts" 
          :key="layout.id"
          @click="store.multimedia.galleryLayout = layout.id"
          class="flex flex-col items-center gap-4 p-4 rounded-2xl border-2 transition-all group"
          :class="[
            store.multimedia.galleryLayout === layout.id
              ? 'border-primary bg-primary/5 shadow-xl shadow-primary/5'
              : 'border-slate-100 dark:border-slate-800/50 bg-white dark:bg-slate-950/20 hover:border-slate-200 dark:hover:border-slate-700'
          ]"
        >
          <!-- Visual Representation -->
          <div class="w-full aspect-video rounded-2xl p-2 flex gap-1 items-stretch" :class="store.multimedia.galleryLayout === layout.id ? 'bg-primary/20' : 'bg-slate-50 dark:bg-slate-900'">
             <!-- Layout Previews based on type -->
             <template v-if="layout.id === 'featured'">
                <div class="flex-1 bg-primary rounded-lg"></div>
                <div class="w-1/3 flex flex-col gap-1">
                   <div class="flex-1 border-2 border-primary/40 rounded-lg"></div>
                   <div class="flex-1 border-2 border-primary/40 rounded-lg"></div>
                </div>
             </template>

             <template v-if="layout.id === 'grid'">
                <div class="grid grid-cols-2 grid-rows-2 gap-1 w-full">
                   <div class="dark:bg-slate-800 bg-slate-200 rounded-md" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'grid'}"></div>
                   <div class="dark:bg-slate-800 bg-slate-200 rounded-md" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'grid'}"></div>
                   <div class="dark:bg-slate-800 bg-slate-200 rounded-md" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'grid'}"></div>
                   <div class="dark:bg-slate-800 bg-slate-200 rounded-md" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'grid'}"></div>
                </div>
             </template>

             <template v-if="layout.id === 'slider'">
                <div class="flex flex-col gap-1 w-full">
                   <div class="flex-1 dark:bg-slate-800 bg-slate-200 rounded-lg" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'slider'}"></div>
                   <div class="h-1/3 flex gap-1">
                      <div class="flex-1 dark:bg-slate-700 bg-slate-300 rounded-md" :class="{'bg-primary/60 dark:bg-primary/60': store.multimedia.galleryLayout === 'slider'}"></div>
                      <div class="flex-1 dark:bg-slate-700 bg-slate-300 rounded-md" :class="{'bg-primary/60 dark:bg-primary/60': store.multimedia.galleryLayout === 'slider'}"></div>
                      <div class="flex-1 dark:bg-slate-700 bg-slate-300 rounded-md" :class="{'bg-primary/60 dark:bg-primary/60': store.multimedia.galleryLayout === 'slider'}"></div>
                   </div>
                </div>
             </template>

             <template v-if="layout.id === 'mosaic_vertical'">
                <div class="flex gap-1 w-full h-full">
                   <div class="flex-1 dark:bg-slate-800 bg-slate-200 rounded-lg" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'mosaic_vertical'}"></div>
                   <div class="flex-1 dark:bg-slate-800 bg-slate-200 rounded-lg shadow-inner" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'mosaic_vertical'}"></div>
                   <div class="flex-1 dark:bg-slate-800 bg-slate-200 rounded-lg" :class="{'bg-primary/40 dark:bg-primary/40': store.multimedia.galleryLayout === 'mosaic_vertical'}"></div>
                </div>
             </template>
          </div>

          <span class="text-[11px] font-black text-center transition-colors uppercase tracking-widest" :class="store.multimedia.galleryLayout === layout.id ? 'text-primary' : 'text-slate-500'">
            {{ layout.name }}
          </span>
        </button>
      </div>

      <UAlert
        v-show="isSectionExpanded('layout')"
        color="primary"
        variant="subtle"
        icon="i-lucide-wand-sparkles"
        title="Detección automática"
        description="El layout se ajusta automáticamente según si el video es YouTube Shorts (vertical) u horizontal."
        class="mt-4"
      />
    </UCard>

    <!-- Image Gallery Section -->
    <UCard :ui="{ header: 'p-0', body: isSectionExpanded('gallery') ? 'p-4 sm:p-4' : 'p-0 sm:p-0' }">
      <template #header>
        <button
          type="button"
          class="w-full p-4 flex items-center justify-between gap-3 flex-wrap hover:bg-elevated/40 transition-colors text-left"
          @click="toggleSection('gallery')"
        >
          <h3 class="text-base font-bold flex items-center gap-2">
            <UIcon
              name="i-lucide-chevron-down"
              class="size-4 text-muted transition-transform"
              :class="{ 'rotate-180': isSectionExpanded('gallery') }"
            />
            <UIcon name="i-lucide-images" class="size-5 text-primary" />
            Galería de imágenes
          </h3>
          <UBadge color="neutral" variant="subtle" size="sm">
            {{ store.multimedia.images.length }} / 20 imágenes
          </UBadge>
        </button>
      </template>

      <div v-show="isSectionExpanded('gallery')">
      <!-- Drag & Drop Upload Area -->
      <div
        :class="[
          'border-2 border-dashed rounded-xl p-6 flex flex-col items-center justify-center text-center transition-all cursor-pointer relative overflow-hidden',
          isDragging
            ? 'border-primary bg-primary/5 scale-[0.99]'
            : 'border-default hover:border-primary/40 hover:bg-elevated/40 bg-elevated/20',
        ]"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <input type="file" multiple accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleFileChange" />
        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-3">
          <UIcon name="i-lucide-cloud-upload" class="size-6" />
        </div>
        <p class="text-sm font-bold mb-1">Arrastra y suelta fotos aquí</p>
        <p class="text-[11px] text-muted mb-4">JPEG, PNG o WebP · Recomendado 1920×1080px</p>
        <UButton icon="i-lucide-folder-open" color="primary" size="sm">
          Seleccionar archivos
        </UButton>
      </div>

      <!-- Image Grid -->
      <div v-if="store.multimedia.images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        <div
          v-for="(image, index) in store.multimedia.images"
          :key="image.id"
          :class="[
            'group relative aspect-square rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg',
            image.isPrimary ? 'border-primary ring-2 ring-primary/20' : 'border-default',
          ]"
        >
          <img :src="getImageUrl(image.url)" :alt="image.altText" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />

          <!-- Top badges -->
          <div class="absolute top-2 left-2 right-2 flex items-start justify-between gap-2 z-10">
            <UBadge
              v-if="image.isPrimary"
              color="primary"
              variant="solid"
              size="xs"
              icon="i-lucide-star"
              class="shadow-md"
            >
              Principal
            </UBadge>
            <span v-else class="size-6 bg-black/40 backdrop-blur-md rounded-md flex items-center justify-center text-[10px] font-bold text-white">
              #{{ index + 1 }}
            </span>
            <span v-if="image.isPrimary" class="size-6 bg-black/40 backdrop-blur-md rounded-md flex items-center justify-center text-[10px] font-bold text-white">
              #{{ index + 1 }}
            </span>
          </div>

          <!-- Hover overlay with actions -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
            <div class="flex items-center justify-between w-full gap-2">
              <div class="flex gap-1.5 flex-wrap">
                <UButton
                  size="xs"
                  :color="image.isPrimary ? 'primary' : 'neutral'"
                  :variant="image.isPrimary ? 'solid' : 'subtle'"
                  :icon="image.isPrimary ? 'i-lucide-star' : 'i-lucide-star-off'"
                  class="backdrop-blur-md"
                  @click="setPrimary(index)"
                >
                  {{ image.isPrimary ? 'Principal' : 'Marcar' }}
                </UButton>
                <UButton
                  size="xs"
                  color="neutral"
                  variant="subtle"
                  icon="i-lucide-pencil"
                  class="backdrop-blur-md"
                  @click="openEditModal(index)"
                >
                  Editar
                </UButton>
              </div>
              <UButton
                size="xs"
                color="error"
                variant="solid"
                icon="i-lucide-trash-2"
                title="Eliminar imagen"
                @click="removeImage(index)"
              />
            </div>
          </div>
        </div>
      </div>
      </div>
    </UCard>

    <!-- Image Editor Modal -->
    <UModal
      :open="editingIndex !== null"
      :ui="{ content: 'max-w-4xl' }"
      @update:open="(v) => !v && (editingIndex = null)"
    >
      <template #content>
        <div v-if="editingIndex !== null && store.multimedia.images[editingIndex]" class="flex flex-col max-h-[90vh] bg-default rounded-lg overflow-hidden">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon name="i-lucide-image-plus" class="size-5 text-primary" />
              </div>
              <div>
                <h3 class="text-lg font-bold">Editar imagen</h3>
                <p class="text-xs text-muted mt-0.5">
                  Imagen #{{ editingIndex + 1 }} · Idioma: <span class="font-bold text-primary">{{ store.currentLanguage.toUpperCase() }}</span>
                </p>
              </div>
            </div>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="editingIndex = null" />
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[2fr_3fr] gap-5">
              <!-- Preview column -->
              <div class="space-y-3">
                <div class="aspect-video rounded-xl overflow-hidden border border-default relative">
                  <img :src="getImageUrl(store.multimedia.images[editingIndex]?.url || '')" class="w-full h-full object-cover" />
                  <UBadge
                    v-if="store.multimedia.images[editingIndex]?.isPrimary"
                    color="primary"
                    variant="solid"
                    size="sm"
                    icon="i-lucide-star"
                    class="absolute top-3 left-3 shadow-md"
                  >
                    Principal
                  </UBadge>
                </div>

                <UCard :ui="{ body: 'p-4' }">
                  <div class="space-y-2 text-xs">
                    <div>
                      <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1">Archivo</p>
                      <p class="font-mono font-bold truncate">
                        {{ store.multimedia.images[editingIndex]?.filename || `tour-image-${(editingIndex || 0) + 1}.webp` }}
                      </p>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-default">
                      <span class="text-[10px] font-black uppercase tracking-widest text-muted">Tamaño</span>
                      <UBadge color="primary" variant="subtle" size="xs" class="font-mono">
                        {{ ((store.multimedia.images[editingIndex]?.size || 0) / 1024).toFixed(1) }} KB
                      </UBadge>
                    </div>
                  </div>
                </UCard>
              </div>

              <!-- Form column -->
              <div class="space-y-4">
                <UFormField
                  label="Texto alternativo (ALT)"
                  required
                  :hint="`${editForm.altText.length}/125`"
                  help="Describe la imagen para lectores de pantalla y motores de búsqueda."
                >
                  <UInput
                    v-model="editForm.altText"
                    maxlength="125"
                    placeholder="Ej: Tour Lago Titicaca Puno con vista panorámica"
                    class="w-full"
                  />
                </UFormField>

                <UFormField
                  label="Título de la imagen"
                  :hint="`${editForm.titleText.length}/100`"
                >
                  <UInput
                    v-model="editForm.titleText"
                    maxlength="100"
                    placeholder="Ej: Vista del Lago Titicaca desde Puno"
                    class="w-full"
                  />
                </UFormField>

                <UFormField
                  label="Descripción detallada"
                  :hint="`${editForm.description.length}/250`"
                >
                  <UTextarea
                    v-model="editForm.description"
                    :rows="4"
                    maxlength="250"
                    placeholder="Describe con más detalle lo que se ve en la imagen para el visor de la galería..."
                    class="w-full"
                  />
                </UFormField>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2 shrink-0">
            <UButton color="neutral" variant="ghost" @click="editingIndex = null">Cancelar</UButton>
            <UButton color="primary" icon="i-lucide-save" @click="saveChanges">
              Guardar cambios
            </UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useAuthStore } from '~/stores/auth'
import { ref, computed } from 'vue'

const store = useTourWizardStore()
const { confirm } = useConfirm()
const isDragging = ref(false)
const editingIndex = ref<number | null>(null)
const editForm = ref({
  altText: '',
  titleText: '',
  description: ''
})

// Collapsible sections — state persisted in localStorage so F5 keeps each open/closed.
const { toggleSection, isSectionExpanded } = useCollapsibles('wizard:step5')

// Per-language video helpers
const langLabels: Record<string, string> = {
  es: 'Español', en: 'English', pt: 'Português', fr: 'Français', de: 'Deutsch', it: 'Italiano'
}

const currentLangSeo = computed(() => store.contentSEO[store.currentLanguage])
const currentLangLabel = computed(() => langLabels[store.currentLanguage] || store.currentLanguage)

// Only show language tabs for languages that have content (title filled)
const videoLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && (seo.title || seo.youtubeUrl)
  })
})

const updateVideoUrl = (url: string) => {
  if (store.contentSEO[store.currentLanguage]) {
    store.contentSEO[store.currentLanguage].youtubeUrl = url
  }
}

const urlCopied = ref(false)
const copyVideoUrl = async () => {
  const url = currentLangSeo.value?.youtubeUrl
  if (!url) return
  try {
    await navigator.clipboard.writeText(url)
    urlCopied.value = true
    setTimeout(() => { urlCopied.value = false }, 1500)
  } catch (err) {
    console.error('Clipboard write failed:', err)
  }
}

// Get media_texts for current language and specific image
const getMediaText = (mediaId: any, field: 'alt_text' | 'title_text') => {
  const texts = currentLangSeo.value?.mediaTexts || []
  const entry = texts.find((t: any) => t.media_id === mediaId)
  return entry?.[field] || ''
}

const openEditModal = (index: number) => {
  const image = store.multimedia.images[index]
  if (image) {
    // Load from per-language media_texts if available, fallback to shared
    editForm.value = {
      altText: getMediaText(image.id, 'alt_text') || image.altText || '',
      titleText: getMediaText(image.id, 'title_text') || image.titleText || '',
      description: image.description || ''
    }
    editingIndex.value = index
  }
}

const saveChanges = () => {
  if (editingIndex.value !== null) {
    const image = store.multimedia.images[editingIndex.value]
    if (image) {
      // Save shared fields (description stays shared)
      image.description = editForm.value.description

      // Save alt_text and title_text per language in contentSEO.mediaTexts
      if (store.contentSEO[store.currentLanguage]) {
        const mediaTexts = [...(store.contentSEO[store.currentLanguage].mediaTexts || [])]
        const existingIdx = mediaTexts.findIndex((t: any) => t.media_id === image.id)
        const entry = {
          media_id: image.id,
          alt_text: editForm.value.altText,
          title_text: editForm.value.titleText
        }
        if (existingIdx >= 0) {
          mediaTexts[existingIdx] = entry
        } else {
          mediaTexts.push(entry)
        }
        store.contentSEO[store.currentLanguage].mediaTexts = mediaTexts
      }

      // Also update the shared image fields for backwards compat
      image.altText = editForm.value.altText
      image.titleText = editForm.value.titleText

      // If it's a new upload, also update metadata in tempImages for the backend
      const tempImage = store.tempImages.find(img => img.filename === image.filename)
      if (tempImage) {
        tempImage.alt_text = editForm.value.altText
        tempImage.title_text = editForm.value.titleText
        tempImage.description = editForm.value.description
        tempImage.is_primary = image.isPrimary
        tempImage.order = editingIndex.value + 1
      }
    }
    editingIndex.value = null
  }
}

const getImageUrl = (url: string) => {
  if (!url) return ''
  if (url.startsWith('http') || url.startsWith('data:')) return url
  const config = useRuntimeConfig()
  const baseUrl = config.public.apiUrl.replace('/api', '')
  
  // Ensure the path starts with /storage if it's a relative storage path
  const path = url.startsWith('/') ? url : `/${url}`
  const finalPath = path.startsWith('/storage') ? path : `/storage${path}`
  
  return `${baseUrl}${finalPath}`
}

const layouts = [
  { id: 'featured', name: 'Featured Hero' },
  { id: 'grid', name: 'Uniform Grid' },
  { id: 'slider', name: 'Cinematic Slider' },
  { id: 'mosaic_vertical', name: 'Mosaic Vertical' },
] as const

const youtubeId = computed(() => {
  const url = currentLangSeo.value?.youtubeUrl || ''
  if (!url) return null

  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/
  const match = url.match(regExp)
  if (match && match[2] && match[2].length === 11) return match[2]

  const shortExp = /youtube.com\/shorts\/([^\/\?]+)/
  const shortMatch = url.match(shortExp)
  if (shortMatch) return shortMatch[1]

  return null
})

const handleFileChange = (e: Event) => {
  const files = (e.target as HTMLInputElement).files
  if (files) addFiles(Array.from(files))
}

const handleDrop = (e: DragEvent) => {
  isDragging.value = false
  const files = e.dataTransfer?.files
  if (files) addFiles(Array.from(files))
}

const addFiles = async (files: File[]) => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()
  
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue
    
    // Create preview
    const reader = new FileReader()
    reader.onload = async (e) => {
      const previewUrl = e.target?.result as string
      
      // Upload to server
      const formData = new FormData()
      formData.append('image', file)
      
      try {
        const response: any = await $fetch(`${config.public.apiUrl}/admin/tours/upload-image`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${auth.token}`,
            'Accept': 'application/json'
          },
          body: formData
        })
        
        if (response.success) {
          store.multimedia.images.push({
            id: crypto.randomUUID(),
            url: response.url, // URL for preview
            filename: response.filename,
            size: file.size,
            altText: '',
            titleText: '',
            description: '',
            isPrimary: store.multimedia.images.length === 0,
            order: store.multimedia.images.length
          })
          
          store.tempImages.push({
            filename: response.filename,
            path: response.path
          })
        }
      } catch (error) {
        console.error('Error uploading image:', error)
        toast.add({
          title: 'Error al subir',
          description: file.name,
          icon: 'i-lucide-triangle-alert',
          color: 'error',
        })
      }
    }
    reader.readAsDataURL(file)
  }
}

const removeImage = async (index: number) => {
  const image = store.multimedia.images[index]
  if (!image) return

  const ok = await confirm({
    title: 'Eliminar imagen',
    description: image.isPrimary
      ? `Vas a eliminar la imagen principal (#${index + 1}). Otra imagen será marcada como principal automáticamente.`
      : `Vas a eliminar la imagen #${index + 1} de la galería. Esta acción no se puede deshacer.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return

  const removed = store.multimedia.images.splice(index, 1)[0]
  if (!removed) return

  // Also remove from tempImages if it was a new upload
  const tempIndex = store.tempImages.findIndex(img => img.filename === removed.filename)
  if (tempIndex !== -1) {
    store.tempImages.splice(tempIndex, 1)
  }

  if (removed.isPrimary && store.multimedia.images.length > 0 && store.multimedia.images[0]) {
    store.multimedia.images[0].isPrimary = true
  }
}

const setPrimary = (index: number) => {
  store.multimedia.images.forEach((img, i) => {
    img.isPrimary = i === index
  })
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.4);
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-in {
  animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.material-symbols-outlined.filled {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
