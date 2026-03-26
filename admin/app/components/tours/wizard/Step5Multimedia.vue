<template>
  <div class="flex flex-col gap-12 pb-20">
    <!-- Video Section -->
    <section class="space-y-6">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">play_circle</span>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Video Highlights</h3>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        <!-- Input Card -->
        <div class="glass-card p-8 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-6 shadow-sm">
          <div>
             <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-4">YouTube Video URL</h4>
             <div class="flex gap-3">
               <div class="flex-1 relative group">
                  <input 
                    v-model="store.multimedia.youtubeUrl"
                    type="text" 
                    placeholder="https://www.youtube.com/watch?v="
                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-4 px-12 focus:ring-2 focus:ring-primary focus:border-transparent outline-none text-slate-700 dark:text-slate-200 transition-all font-medium text-sm"
                  />
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">link</span>
               </div>
               <button class="px-8 py-4 bg-primary text-white rounded-2xl font-black text-sm hover:shadow-xl hover:shadow-primary/30 hover:scale-[1.02] active:scale-95 transition-all">
                 Add
               </button>
             </div>
             <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-4">Supported platforms: YouTube, Vimeo. Max 1 video per tour.</p>
          </div>
        </div>

        <!-- Preview Card -->
        <div class="relative group aspect-video rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 shadow-sm transition-all duration-500 hover:shadow-2xl">
           <img 
            v-if="youtubeId" 
            :src="`https://img.youtube.com/vi/${youtubeId}/maxresdefault.jpg`" 
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 brightness-75 group-hover:brightness-50"
            alt="Video Thumbnail"
           />
           <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400">
              <span class="material-symbols-outlined text-4xl mb-2 opacity-50">smart_display</span>
              <p class="text-xs font-bold uppercase tracking-widest opacity-50 text-center">Video Preview<br/><span class="text-[9px] font-medium">Enter a URL to see it here</span></p>
           </div>

           <!-- Preview Overlay -->
           <div v-if="youtubeId" class="absolute inset-0 flex flex-col items-center justify-center gap-4 transition-all duration-500">
              <div class="size-16 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform shadow-2xl">
                 <span class="material-symbols-outlined text-3xl filled">play_arrow</span>
              </div>
              <div class="px-6 py-2 bg-slate-900/80 backdrop-blur-xl rounded-full border border-white/10 flex items-center gap-2">
                 <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">Preview</span>
              </div>
           </div>

           <!-- Delete Button -->
           <button 
            v-if="youtubeId"
            @click="store.multimedia.youtubeUrl = ''"
            class="absolute top-4 right-4 size-10 bg-rose-500 text-white rounded-xl flex items-center justify-center shadow-lg hover:bg-rose-600 transition-all opacity-0 group-hover:opacity-100"
           >
              <span class="material-symbols-outlined text-sm">delete</span>
           </button>
        </div>
      </div>
    </section>

    <!-- Gallery Layout Detection Section -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-8 bg-white dark:bg-slate-950/50 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
          <span class="material-symbols-outlined filled">grid_view</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Gallery Layout Detection</h3>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Layout Options -->
        <button 
          v-for="layout in layouts" 
          :key="layout.id"
          @click="store.multimedia.galleryLayout = layout.id"
          class="flex flex-col items-center gap-4 p-4 rounded-3xl border-2 transition-all group"
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

      <div class="p-5 bg-primary/5 rounded-2xl border border-primary/10 flex items-center gap-4 animate-pulse">
         <span class="material-symbols-outlined text-primary text-xl">auto_fix_high</span>
         <p class="text-xs text-slate-600 dark:text-slate-300 font-medium">
            AI Detection: The layout will be auto-adjusted based on the presence of <strong>YouTube Shorts</strong> or <strong>Horizontal Cinema</strong> video.
         </p>
      </div>
    </section>

    <!-- Image Gallery Section -->
    <section class="space-y-6 pt-10 border-t border-slate-100 dark:border-slate-800/50">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">photo_library</span>
          <h3 class="text-xl font-bold text-slate-900 dark:text-white">Image Gallery</h3>
        </div>
        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
          {{ store.multimedia.images.length }} / 20 images
        </span>
      </div>

      <!-- Drag & Drop Upload Area -->
      <div 
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        class="border-2 border-dashed border-slate-300 dark:border-slate-800 rounded-[2.5rem] p-16 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900/40 hover:bg-slate-100 dark:hover:bg-slate-900 transition-all cursor-pointer group relative overflow-hidden"
        :class="isDragging ? 'border-primary bg-primary/5 scale-[0.98]' : ''"
      >
        <input type="file" multiple accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleFileChange" />
        <div class="size-20 rounded-3xl bg-primary/10 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-primary/5">
          <span class="material-symbols-outlined text-5xl">cloud_upload</span>
        </div>
        <p class="text-2xl font-black text-slate-900 dark:text-white mb-2">Drag and drop photos here</p>
        <p class="text-xs text-slate-500 mb-8 uppercase tracking-[0.2em] font-bold">Support JPEG, PNG, or WebP • Recommended 1920x1080px</p>
        <button class="bg-slate-900 dark:bg-primary text-white px-10 py-4 rounded-2xl font-black text-sm hover:shadow-2xl hover:shadow-primary/30 transition-all group-hover:scale-105 active:scale-95">
          Browse Files
        </button>
      </div>

      <!-- Image Reordering Grid -->
      <div v-if="store.multimedia.images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-12 animate-in fade-in slide-in-from-bottom-5">
        <div 
          v-for="(image, index) in store.multimedia.images" 
          :key="image.id"
          class="group relative aspect-square rounded-3xl overflow-hidden border-2 transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
          :class="image.isPrimary ? 'border-primary shadow-2xl shadow-primary/20' : 'border-slate-200 dark:border-slate-800 bg-background-light dark:bg-background-dark'"
        >
          <img :src="getImageUrl(image.url)" :alt="image.altText" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
          
          <!-- Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col justify-end p-5">
            <div class="flex justify-between items-center">
              <div class="flex gap-2">
                <button @click="setPrimary(index)" class="px-3 py-1.5 rounded-xl bg-white/20 backdrop-blur-xl text-white text-[9px] font-black uppercase tracking-widest hover:bg-primary transition-colors">
                  {{ image.isPrimary ? 'Primary' : 'Set Primary' }}
                </button>
                <button @click="openEditModal(index)" class="px-3 py-1.5 rounded-xl bg-white/20 backdrop-blur-xl text-white text-[9px] font-black uppercase tracking-widest hover:bg-primary transition-colors">
                  Edit Info
                </button>
              </div>
              <button @click="removeImage(index)" class="size-8 rounded-lg bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 shadow-xl">
                <span class="material-symbols-outlined text-sm">delete</span>
              </button>
            </div>
          </div>

          <!-- Primary Badge -->
          <div v-if="image.isPrimary" class="absolute top-4 left-4 px-3 py-1 bg-primary text-white text-[8px] font-black uppercase tracking-widest rounded-full shadow-lg ring-4 ring-white/10">
            Primary Photo
          </div>
          <div class="absolute top-4 right-4 size-6 bg-black/40 backdrop-blur-md rounded-lg flex items-center justify-center text-[10px] font-bold text-white/70">
            #{{ index + 1 }}
          </div>
        </div>
      </div>
      <!-- Image Editor Modal -->
      <div v-if="editingIndex !== null && store.multimedia.images[editingIndex]" class="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-8">
        <!-- Backdrop -->
        <div @click="editingIndex = null" class="absolute inset-0 bg-slate-950/60 backdrop-blur-md animate-in fade-in duration-300"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-5xl glass-card p-8 rounded-[2.5rem] border border-white/20 bg-white/90 dark:bg-slate-900/90 shadow-2xl animate-in fade-in zoom-in-95 duration-300 overflow-hidden max-h-[90vh] flex flex-col">
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined filled">edit_note</span>
              </div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Editar Información de Imagen</h3>
            </div>
            <button @click="editingIndex = null" class="size-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
            <div class="flex flex-col lg:flex-row gap-10">
              <!-- Image Preview -->
              <div class="w-full lg:w-2/5 space-y-4">
                <div class="aspect-video rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 relative group shadow-2xl ring-1 ring-black/5">
                   <img :src="getImageUrl(store.multimedia.images[editingIndex]?.url || '')" class="w-full h-full object-cover" />
                  
                  <!-- Badges on Preview -->
                  <div class="absolute top-4 left-4 flex gap-2">
                    <div class="px-3 py-1.5 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-black rounded-xl">
                      #{{ editingIndex + 1 }}
                    </div>
                     <div v-if="store.multimedia.images[editingIndex]?.isPrimary" class="px-3 py-1.5 bg-primary text-white text-[10px] font-black rounded-xl flex items-center gap-1.5 shadow-lg">
                      <span class="material-symbols-outlined text-[12px] filled">star</span>
                      Principal
                    </div>
                  </div>
                </div>

                <!-- Image Info Card -->
                <div class="p-5 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                  <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nombre del archivo</span>
                     <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">{{ store.multimedia.images[editingIndex]?.filename || 'tour-image-' + ((editingIndex || 0) + 1) + '.webp' }}</p>
                  </div>
                  <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tamaño</span>
                    <p class="text-[10px] font-black text-primary">{{ ((store.multimedia.images[editingIndex]?.size || 0) / 1024).toFixed(1) }} KB</p>
                  </div>
                </div>
              </div>

              <!-- Edit Fields -->
              <div class="flex-1 space-y-8">
                <div class="grid grid-cols-1 gap-8">
                  <div class="space-y-3">
                    <label class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest px-1">Texto Alternativo (ALT) <span class="text-rose-500">*</span></label>
                    <div class="relative group">
                      <input 
                        v-model="editForm.altText"
                        type="text" 
                        maxlength="125"
                        placeholder="Ej: Tour Lago Titicaca Puno con vista panorámica"
                        class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl py-4 px-5 focus:ring-2 focus:ring-primary outline-none transition-all text-sm font-medium pr-16"
                      />
                      <span class="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">
                        {{ editForm.altText.length }}/125
                      </span>
                    </div>
                    <p class="text-[10px] text-slate-400 font-medium italic px-1 flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs">info</span>
                      Describe la imagen para personas con discapacidad visual y motores de búsqueda.
                    </p>
                  </div>

                  <div class="space-y-3">
                    <label class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest px-1">Título de la Imagen</label>
                    <div class="relative group">
                      <input 
                        v-model="editForm.titleText"
                        type="text" 
                        maxlength="100"
                        placeholder="Ej: Vista del Lago Titicaca desde Puno"
                        class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl py-4 px-5 focus:ring-2 focus:ring-primary outline-none transition-all text-sm font-medium pr-16"
                      />
                      <span class="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">
                        {{ editForm.titleText.length }}/100
                      </span>
                    </div>
                  </div>
                </div>

                <div class="space-y-3">
                  <label class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest px-1">Descripción Detallada</label>
                  <div class="relative group">
                    <textarea 
                      v-model="editForm.description"
                      rows="4"
                      maxlength="250"
                      placeholder="Describe con más detalle lo que se ve en la imagen para el visor de la galería..."
                      class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl py-4 px-5 focus:ring-2 focus:ring-primary outline-none transition-all text-sm font-medium resize-none"
                    ></textarea>
                    <span class="absolute right-5 bottom-4 text-[10px] font-black text-slate-400">
                      {{ editForm.description.length }}/250
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-4 pt-8 mt-8 border-t border-slate-100 dark:border-slate-800">
             <button @click="editingIndex = null" class="px-8 py-3.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl font-black text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                Cancelar
             </button>
             <button @click="saveChanges" class="px-12 py-3.5 bg-primary text-white rounded-2xl font-black text-sm shadow-xl shadow-primary/30 hover:shadow-primary/40 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                Guardar Cambios
             </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useAuthStore } from '~/stores/auth'
import { ref, computed } from 'vue'

const store = useTourWizardStore()
const isDragging = ref(false)
const editingIndex = ref<number | null>(null)
const editForm = ref({
  altText: '',
  titleText: '',
  description: ''
})

const openEditModal = (index: number) => {
  const image = store.multimedia.images[index]
  if (image) {
    editForm.value = {
      altText: image.altText || '',
      titleText: image.titleText || '',
      description: image.description || ''
    }
    editingIndex.value = index
  }
}

const saveChanges = () => {
  if (editingIndex.value !== null) {
    const image = store.multimedia.images[editingIndex.value]
    if (image) {
      image.altText = editForm.value.altText
      image.titleText = editForm.value.titleText
      image.description = editForm.value.description

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
  const url = store.multimedia.youtubeUrl
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
        alert('Error al subir la imagen: ' + file.name)
      }
    }
    reader.readAsDataURL(file)
  }
}

const removeImage = (index: number) => {
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
