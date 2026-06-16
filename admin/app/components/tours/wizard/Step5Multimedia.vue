<template>
  <div class="flex flex-col gap-6">
    <!-- Video Section (per language) -->
    <WizardSection
      collapsible
      title="Video destacado"
      icon="i-lucide-play-circle"
      :open="isSectionExpanded('video')"
      @update:open="toggleSection('video')"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <UBadge
            v-if="currentLangSeo?.youtubeUrl"
            color="success"
            variant="subtle"
            size="xs"
            icon="i-lucide-circle-check"
          >
            {{ store.currentLanguage.toUpperCase() }}
          </UBadge>
          <div class="flex gap-1">
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
        </div>
      </template>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
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
            class="absolute top-3 right-3 opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 transition-opacity"
            @click="updateVideoUrl('')"
           />
        </div>
      </div>
    </WizardSection>

    <!-- Gallery Layout Detection Section -->
    <WizardSection
      collapsible
      title="Detección de layout de galería"
      icon="i-lucide-layout-grid"
      :open="isSectionExpanded('layout')"
      @update:open="toggleSection('layout')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs" class="capitalize">
          {{ store.multimedia.galleryLayout }}
        </UBadge>
      </template>

      <div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
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
        color="primary"
        variant="subtle"
        icon="i-lucide-wand-sparkles"
        title="Detección automática"
        description="El layout se ajusta automáticamente según si el video es YouTube Shorts (vertical) u horizontal."
        class="mt-4"
      />
      </div>
    </WizardSection>

    <!-- Image Gallery Section -->
    <WizardSection
      collapsible
      title="Galería de imágenes"
      icon="i-lucide-images"
      :open="isSectionExpanded('gallery')"
      @update:open="toggleSection('gallery')"
    >
      <template #actions>
        <UBadge color="neutral" variant="subtle" size="sm">
          {{ store.multimedia.images.length }} / 20 imágenes
        </UBadge>
      </template>

      <div>
      <!-- Drag & Drop Upload Area -->
      <div
        :class="[
          'border-2 border-dashed rounded-xl p-6 flex flex-col items-center justify-center text-center transition-all cursor-pointer relative overflow-hidden',
          isDragging
            ? 'border-primary bg-primary/5 scale-[0.99]'
            : 'border-default hover:border-primary/40 hover:bg-elevated/40 bg-elevated/20',
        ]"
        @dragover.prevent="!isUploading && (isDragging = true)"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <input
          type="file"
          multiple
          accept="image/*"
          :disabled="isUploading"
          class="absolute inset-0 opacity-0 cursor-pointer disabled:cursor-default"
          @change="handleFileChange"
        />

        <!-- Uploading state -->
        <template v-if="isUploading">
          <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-3">
            <UIcon name="i-lucide-loader-circle" class="size-6 animate-spin" />
          </div>
          <p class="text-sm font-bold mb-1">Subiendo {{ uploadDone }} de {{ uploadTotal }} {{ uploadTotal === 1 ? 'imagen' : 'imágenes' }}…</p>
          <p class="text-[11px] text-muted mb-3">No cierres ni cambies de paso hasta que termine.</p>
          <div class="w-48 h-1.5 rounded-full bg-elevated overflow-hidden">
            <div
              class="h-full bg-primary rounded-full transition-all duration-300"
              :style="{ width: uploadTotal ? `${Math.round((uploadDone / uploadTotal) * 100)}%` : '0%' }"
            />
          </div>
        </template>

        <!-- Idle state -->
        <template v-else>
          <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-3">
            <UIcon name="i-lucide-cloud-upload" class="size-6" />
          </div>
          <p class="text-sm font-bold mb-1">Arrastra y suelta fotos aquí</p>
          <p class="text-[11px] text-muted mb-4">JPEG, PNG o WebP · Puedes seleccionar varias a la vez · Recomendado 1920×1080px</p>
          <UButton icon="i-lucide-folder-open" color="primary" size="sm">
            Seleccionar archivos
          </UButton>
        </template>
      </div>

      <!-- Image Grid -->
      <div v-if="store.multimedia.images.length > 0" class="space-y-2 mt-6">
        <UAlert
          v-if="galleryIncompleteCount > 0"
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          :title="`${galleryIncompleteCount} ${galleryIncompleteCount === 1 ? 'imagen sin completar' : 'imágenes sin completar'}`"
          description="Las imágenes marcadas en ámbar necesitan texto alternativo (ALT), título o descripción. Haz clic en una imagen para editarla. Completarlas mejora la accesibilidad y el SEO."
        />
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <p class="text-[11px] text-muted flex items-center gap-1.5">
            <UIcon name="i-lucide-move" class="size-3.5" />
            Arrastra para reordenar · La <span class="font-bold text-primary">primera</span> posición es la principal
          </p>
          <div v-if="selectedImageIds.size > 0" class="flex items-center gap-2">
            <UBadge color="primary" variant="subtle" size="sm" icon="i-lucide-check-square">
              {{ selectedImageIds.size }} seleccionada{{ selectedImageIds.size === 1 ? '' : 's' }}
            </UBadge>
            <UButton
              v-if="selectedImageIds.size < store.multimedia.images.length"
              icon="i-lucide-check-check"
              color="neutral"
              variant="ghost"
              size="xs"
              @click="selectAllImages"
            >
              Todas
            </UButton>
            <UButton
              icon="i-lucide-x"
              color="neutral"
              variant="ghost"
              size="xs"
              @click="clearImageSelection"
            >
              Cancelar
            </UButton>
            <UButton
              icon="i-lucide-trash-2"
              color="error"
              size="xs"
              @click="deleteSelectedImages"
            >
              Eliminar {{ selectedImageIds.size }}
            </UButton>
          </div>
        </div>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="(image, index) in store.multimedia.images"
          :key="image.id"
          draggable="true"
          :class="[
            'group relative aspect-square rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg cursor-grab active:cursor-grabbing',
            image.isPrimary ? 'border-primary ring-2 ring-primary/20' : 'border-default',
            isImageSelected(image.id) && 'ring-4 ring-primary/60',
            dragFromIndex === index && 'opacity-40 scale-95',
            dragOverIndex === index && dragFromIndex !== index && 'ring-4 ring-primary/40 scale-105',
          ]"
          @dragstart="onDragStart(index, $event)"
          @dragover="onDragOver(index, $event)"
          @dragleave="onDragLeave(index)"
          @drop="onDrop(index, $event)"
          @dragend="onDragEnd"
        >
          <img :src="getImageUrl(image.url)" :alt="image.altText" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 pointer-events-none" />

          <!-- Selection checkbox (top-left) -->
          <button
            type="button"
            :class="[
              'absolute top-2 left-2 z-20 size-6 rounded-md border-2 flex items-center justify-center transition-all',
              isImageSelected(image.id)
                ? 'bg-primary border-primary text-white'
                : 'bg-black/40 backdrop-blur-md border-white/40 text-transparent opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100',
            ]"
            :title="isImageSelected(image.id) ? 'Deseleccionar' : 'Seleccionar'"
            @click.stop.prevent="toggleImageSelection(image.id)"
            @mousedown.stop
          >
            <UIcon name="i-lucide-check" class="size-4" />
          </button>

          <!-- Top-right: position + primary indicator -->
          <div class="absolute top-2 right-2 z-10 flex items-center gap-1">
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
            <span class="size-6 bg-black/40 backdrop-blur-md rounded-md flex items-center justify-center text-[10px] font-bold text-white">
              #{{ index + 1 }}
            </span>
          </div>

          <!-- Completeness indicator (bottom-left, hidden on hover).
               pointer-events-none so it never blocks the hover-overlay buttons. -->
          <div class="absolute bottom-2 left-2 z-10 transition-opacity group-hover:opacity-0 pointer-events-none">
            <UBadge
              v-if="getMissingFields(image).length"
              :color="getMissingFields(image).includes('ALT') ? 'warning' : 'neutral'"
              variant="solid"
              size="xs"
              icon="i-lucide-triangle-alert"
              class="shadow-md backdrop-blur-md"
              :title="`Faltan datos: ${getMissingFields(image).join(', ')}`"
            >
              Faltan {{ getMissingFields(image).join(' · ') }}
            </UBadge>
            <span
              v-else
              class="size-5 rounded-full bg-success/90 text-white flex items-center justify-center shadow-md backdrop-blur-md"
              title="Datos completos (ALT, título y descripción)"
            >
              <UIcon name="i-lucide-check" class="size-3.5" />
            </span>
          </div>

          <!-- Action overlay. Always visible on touch (can-hover gate), hover-
               reveal on mouse. Move buttons give tablets a reorder path since
               native drag-and-drop doesn't fire on touch. -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 transition-opacity flex flex-col justify-between p-3 pointer-events-none">
            <!-- Reorder (top row) -->
            <div class="flex items-center gap-1.5 pointer-events-auto">
              <UButton
                size="xs" color="neutral" variant="subtle" icon="i-lucide-arrow-left"
                class="backdrop-blur-md" title="Mover antes"
                :disabled="index === 0"
                @click.stop="moveImage(index, -1)"
              />
              <UButton
                size="xs" color="neutral" variant="subtle" icon="i-lucide-arrow-right"
                class="backdrop-blur-md" title="Mover después"
                :disabled="index === store.multimedia.images.length - 1"
                @click.stop="moveImage(index, 1)"
              />
            </div>
            <!-- Edit / delete (bottom row) -->
            <div class="flex items-center justify-between w-full gap-2 pointer-events-auto">
              <UButton
                size="xs"
                color="neutral"
                variant="subtle"
                icon="i-lucide-pencil"
                class="backdrop-blur-md"
                @click.stop="openEditModal(index)"
              >
                Editar
              </UButton>
              <UButton
                size="xs"
                color="error"
                variant="solid"
                icon="i-lucide-trash-2"
                title="Eliminar imagen"
                @click.stop="removeImage(index)"
              />
            </div>
          </div>
        </div>

        <!-- Skeleton placeholders for images still uploading -->
        <div
          v-for="n in uploadingCount"
          :key="'skel-' + n"
          class="aspect-square rounded-xl border-2 border-dashed border-primary/30 bg-elevated animate-pulse flex flex-col items-center justify-center gap-2 text-muted"
        >
          <UIcon name="i-lucide-loader-circle" class="size-6 animate-spin text-primary" />
          <span class="text-[10px] font-bold uppercase tracking-widest">Subiendo…</span>
        </div>
      </div>
      </div>
      </div>
    </WizardSection>

    <!-- Image Editor Modal -->
    <UModal
      :open="editingIndex !== null"
      :ui="{ content: 'max-w-5xl' }"
      @update:open="(v) => !v && (editingIndex = null)"
    >
      <template #content>
        <div v-if="editingIndex !== null && store.multimedia.images[editingIndex]" class="flex flex-col max-h-[90vh] bg-default rounded-lg overflow-hidden">
          <!-- Header -->
          <div class="px-5 sm:px-6 py-4 border-b border-default flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon name="i-lucide-image-plus" class="size-5 text-primary" />
              </div>
              <div>
                <h3 class="text-lg font-bold">Recortar y describir imagen</h3>
                <p class="text-xs text-muted mt-0.5">
                  Imagen #{{ editingIndex + 1 }} · Datos en <span class="font-bold text-primary">{{ store.currentLanguage.toUpperCase() }}</span>
                </p>
              </div>
            </div>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="editingIndex = null" />
          </div>

          <!-- Body -->
          <div class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6 items-start">
              <!-- Preview / crop column -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <span class="size-5 rounded-full bg-primary text-white text-[11px] font-black flex items-center justify-center shrink-0">1</span>
                  <p class="text-sm font-bold">Recorta la imagen</p>
                </div>

                <!-- Aspect ratio presets + reset -->
                <div class="flex items-center justify-between gap-2 flex-wrap">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-widest text-muted mr-0.5">Proporción</span>
                    <UButton
                      v-for="preset in cropPresets"
                      :key="preset.label"
                      size="xs"
                      :color="cropAspect === preset.value ? 'primary' : 'neutral'"
                      :variant="cropAspect === preset.value ? 'solid' : 'subtle'"
                      class="font-bold"
                      @click="cropAspect = preset.value"
                    >
                      {{ preset.label }}
                    </UButton>
                  </div>
                  <UButton
                    icon="i-lucide-rotate-ccw"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Volver al encuadre original"
                    @click="resetCrop"
                  >
                    Restablecer
                  </UButton>
                </div>

                <!-- Interactive cropper -->
                <div class="relative rounded-xl overflow-hidden border border-default bg-slate-900 h-[280px] sm:h-[320px] lg:h-[360px]">
                  <ClientOnly>
                    <Cropper
                      ref="cropperRef"
                      :key="currentEditImage?.id"
                      class="h-full w-full"
                      :src="getImageUrl(currentEditImage?.originalUrl || currentEditImage?.url || '')"
                      :stencil-props="stencilProps"
                      :default-size="defaultCropSize"
                      :default-position="defaultCropPosition"
                      cross-origin="anonymous"
                      image-restriction="fit-area"
                      :canvas="{ maxWidth: 1920, maxHeight: 1920 }"
                      @change="onCropChange"
                    />
                    <template #fallback>
                      <div class="h-full w-full flex items-center justify-center text-white/60">
                        <UIcon name="i-lucide-loader-circle" class="size-6 animate-spin" />
                      </div>
                    </template>
                  </ClientOnly>
                  <UBadge
                    v-if="store.multimedia.images[editingIndex]?.isPrimary"
                    color="primary"
                    variant="solid"
                    size="sm"
                    icon="i-lucide-star"
                    class="absolute top-3 left-3 shadow-md z-10 pointer-events-none"
                  >
                    Principal
                  </UBadge>
                </div>
                <p class="text-[10px] text-muted flex items-center gap-1.5">
                  <UIcon name="i-lucide-move" class="size-3 shrink-0" />
                  Arrastra las esquinas del marco para encuadrar. Si no lo tocas, la imagen se guarda igual.
                </p>

                <UCard :ui="{ body: 'p-3' }">
                  <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                      <span class="text-[10px] font-black uppercase tracking-widest text-muted">Tamaño actual</span>
                      <UBadge color="neutral" variant="subtle" size="xs" class="font-mono">
                        {{ ((store.multimedia.images[editingIndex]?.size || 0) / 1024).toFixed(1) }} KB
                      </UBadge>
                    </div>
                    <div class="flex items-start gap-1.5 pt-2 border-t border-default text-[11px] text-muted">
                      <UIcon name="i-lucide-sparkles" class="size-3.5 text-primary shrink-0 mt-0.5" />
                      <span>Al guardar: recorte aplicado, optimizada a máx <span class="font-bold">1920px</span> y convertida a <span class="font-bold">WebP</span> para carga rápida.</span>
                    </div>
                  </div>
                </UCard>
              </div>

              <!-- Form column -->
              <div class="space-y-4">
                <div class="flex items-center gap-2">
                  <span class="size-5 rounded-full bg-primary text-white text-[11px] font-black flex items-center justify-center shrink-0">2</span>
                  <p class="text-sm font-bold">Describe la imagen</p>
                </div>

                <UFormField
                  label="Texto alternativo (ALT)"
                  required
                  :hint="`${editForm.altText.length}/125`"
                  :help="editForm.altText.trim() ? 'Describe la imagen para lectores de pantalla y motores de búsqueda.' : undefined"
                  :error="editForm.altText.trim() ? undefined : 'Obligatorio: lo leen Google y los lectores de pantalla.'"
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
                  help="Opcional. Aparece al pasar el cursor sobre la imagen."
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
                  help="Opcional. Se muestra en el visor ampliado de la galería."
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
          <div class="px-5 sm:px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2 shrink-0">
            <UButton color="neutral" variant="ghost" :disabled="cropProcessing" @click="editingIndex = null">Cancelar</UButton>
            <UButton
              color="primary"
              :icon="cropProcessing ? undefined : 'i-lucide-save'"
              :loading="cropProcessing"
              @click="saveChanges"
            >
              {{ cropProcessing ? 'Optimizando…' : 'Guardar cambios' }}
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
import WizardSection from './WizardSection.vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { ref, computed } from 'vue'

const store = useTourWizardStore()
const { confirm } = useConfirm()
const isDragging = ref(false)

// Upload progress — drives the drop-area spinner + per-tile skeletons.
const uploadingCount = ref(0) // uploads currently in flight
const uploadTotal = ref(0)    // total queued in the active batch(es)
const uploadDone = ref(0)     // finished (ok or failed) in the active batch(es)
const isUploading = computed(() => uploadingCount.value > 0)

const editingIndex = ref<number | null>(null)
const editForm = ref({
  altText: '',
  titleText: '',
  description: ''
})

// === Image cropper (edit modal) ===
const cropperRef = ref<any>(null)
const cropProcessing = ref(false)
const currentEditImage = computed<any>(() =>
  editingIndex.value !== null ? store.multimedia.images[editingIndex.value] : null,
)
// Restore the saved crop box (in original-image pixels) when reopening; default
// to the full image (= no crop) for images that were never cropped.
const defaultCropSize = ({ imageSize }: any) => {
  const cd = currentEditImage.value?.cropData
  if (cd?.coordinates) return { width: cd.coordinates.width, height: cd.coordinates.height }
  return { width: imageSize.width, height: imageSize.height }
}
const defaultCropPosition = () => {
  const cd = currentEditImage.value?.cropData
  if (cd?.coordinates) return { left: cd.coordinates.left, top: cd.coordinates.top }
  return { left: 0, top: 0 }
}
// Aspect ratio: null = free. Presets cover the gallery layouts (hero, grid, mosaic).
const cropAspect = ref<number | null>(null)
const cropPresets = [
  { label: 'Libre', value: null },
  { label: '16:9', value: 16 / 9 },
  { label: '4:3', value: 4 / 3 },
  { label: '1:1', value: 1 },
] as const
const stencilProps = computed(() => (cropAspect.value ? { aspectRatio: cropAspect.value } : {}))
// Track the stencil position so we only re-encode when the user actually crops.
const initialCropCoords = ref<any>(null)
const currentCropCoords = ref<any>(null)
const onCropChange = ({ coordinates }: any) => {
  // Snapshot so the "initial" reference can't be mutated to match the current one.
  const snap = coordinates ? { ...coordinates } : null
  currentCropCoords.value = snap
  if (!initialCropCoords.value) initialCropCoords.value = snap
}
const cropChanged = () => {
  const a = initialCropCoords.value
  const b = currentCropCoords.value
  if (!a || !b) return false
  const key = (c: any) => `${Math.round(c.left)},${Math.round(c.top)},${Math.round(c.width)},${Math.round(c.height)}`
  return key(a) !== key(b)
}
const resetCrop = () => {
  cropAspect.value = null
  cropperRef.value?.reset?.()
}

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

// Which descriptive fields are still empty (ALT/title are per current language,
// description is shared). Drives the per-tile "faltan datos" indicator.
const getMissingFields = (image: any): string[] => {
  const alt = (getMediaText(image.id, 'alt_text') || image.altText || '').trim()
  const title = (getMediaText(image.id, 'title_text') || image.titleText || '').trim()
  const desc = (image.description || '').trim()
  const missing: string[] = []
  if (!alt) missing.push('ALT')
  if (!title) missing.push('Título')
  if (!desc) missing.push('Descripción')
  return missing
}
const galleryIncompleteCount = computed(
  () => store.multimedia.images.filter((img: any) => getMissingFields(img).length > 0).length,
)

const openEditModal = (index: number) => {
  const image = store.multimedia.images[index]
  if (image) {
    // Load from per-language media_texts if available, fallback to shared
    editForm.value = {
      altText: getMediaText(image.id, 'alt_text') || image.altText || '',
      titleText: getMediaText(image.id, 'title_text') || image.titleText || '',
      description: image.description || ''
    }
    // Reset cropper state for the freshly opened image. Restore the saved
    // aspect so the stencil reopens exactly as it was left.
    cropAspect.value = image.cropData?.aspect ?? null
    initialCropCoords.value = null
    currentCropCoords.value = null
    editingIndex.value = index
  }
}

// Non-destructive crop: derive a cropped WebP (≤1920) from the ORIGINAL and set
// it as the displayed file, while keeping the original + crop box so re-editing
// restores it. Returns false on failure (keeps the modal open).
const applyCropAndUpload = async (image: any): Promise<boolean> => {
  const result = cropperRef.value?.getResult?.()
  const canvas = result?.canvas
  if (!canvas) return false

  const blob: Blob | null = await new Promise(res => canvas.toBlob(res, 'image/webp', 0.82))
  if (!blob) return false

  const auth = useAuthStore()
  const config = useRuntimeConfig()
  const base = (image.filename || 'imagen').replace(/\.[^.]+$/, '')
  const file = new File([blob], `${base}.webp`, { type: 'image/webp' })
  const formData = new FormData()
  formData.append('image', file)

  const response: any = await $fetch(`${config.public.apiUrl}/admin/tours/upload-image`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
    body: formData,
  })
  if (!response?.success) return false

  // The crop box, in ORIGINAL-image pixels — re-applied on re-edit.
  const c = result.coordinates || {}
  const cropData = {
    coordinates: {
      left: Math.round(c.left || 0),
      top: Math.round(c.top || 0),
      width: Math.round(c.width || 0),
      height: Math.round(c.height || 0),
    },
    aspect: cropAspect.value,
  }

  if (typeof image.id === 'number') {
    // Existing DB image: keep the id, hand the backend a fresh derived file to
    // swap in (new_display_path) while it preserves the original. No temp entry.
    image.newDisplayPath = response.path
    image.url = response.url
    image.size = blob.size
    image.cropData = cropData
  } else {
    // New upload (has a temp entry): keep the original temp file, point the
    // display at the cropped derived. Match the temp entry by current filename.
    const temp = store.tempImages.find((t: any) => t.filename === image.filename)
    if (temp) {
      if (!temp.original_path) temp.original_path = temp.path // first crop: remember original
      temp.path = response.path                               // display = cropped
      temp.filename = response.filename
      temp.crop_data = cropData
      temp.alt_text = editForm.value.altText
      temp.title_text = editForm.value.titleText
      temp.description = editForm.value.description
      temp.is_primary = image.isPrimary
      temp.order = (editingIndex.value ?? 0) + 1
    } else {
      store.tempImages.push({
        filename: response.filename,
        path: response.path,
        original_path: response.path,
        crop_data: cropData,
        alt_text: editForm.value.altText,
        title_text: editForm.value.titleText,
        description: editForm.value.description,
        is_primary: image.isPrimary,
        order: (editingIndex.value ?? 0) + 1,
      })
    }
    image.url = response.url
    image.filename = response.filename
    image.size = blob.size
    image.cropData = cropData
    // image.originalUrl stays pointing at the full original.
  }

  store.isDirty = true
  return true
}

const saveChanges = async () => {
  if (editingIndex.value !== null) {
    const image = store.multimedia.images[editingIndex.value]
    if (image) {
      // 1. Apply crop only if the user actually moved/resized the stencil.
      if (cropChanged() && cropperRef.value) {
        cropProcessing.value = true
        try {
          const ok = await applyCropAndUpload(image)
          if (!ok) {
            toast.add({
              title: 'No se pudo recortar la imagen',
              description: 'Vuelve a intentarlo.',
              icon: 'i-lucide-triangle-alert',
              color: 'error',
            })
            cropProcessing.value = false
            return // keep modal open so the user doesn't lose the crop
          }
        } catch (error) {
          console.error('Crop/upload failed:', error)
          toast.add({
            title: 'No se pudo recortar la imagen',
            description: 'Vuelve a intentarlo.',
            icon: 'i-lucide-triangle-alert',
            color: 'error',
          })
          cropProcessing.value = false
          return
        }
        cropProcessing.value = false
      }

      // 2. Save metadata.
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
  const input = e.target as HTMLInputElement
  const files = input.files
  if (files) addFiles(Array.from(files))
  // Reset so picking the same file(s) again re-triggers the change event.
  input.value = ''
}

const handleDrop = (e: DragEvent) => {
  isDragging.value = false
  if (isUploading.value) return
  const files = e.dataTransfer?.files
  if (files) addFiles(Array.from(files))
}

const uploadOne = async (file: File) => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response: any = await $fetch(`${config.public.apiUrl}/admin/tours/upload-image`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json',
      },
      body: formData,
    })

    if (response.success) {
      store.multimedia.images.push({
        id: crypto.randomUUID(),
        url: response.url, // displayed image (== original until cropped)
        originalUrl: response.url, // full image kept for non-destructive crop
        cropData: null,
        filename: response.filename,
        size: file.size,
        altText: '',
        titleText: '',
        description: '',
        isPrimary: store.multimedia.images.length === 0,
        order: store.multimedia.images.length,
      })

      store.tempImages.push({
        filename: response.filename,
        path: response.path,
        original_path: response.path, // same file until a crop derives a new one
        crop_data: null,
      })

      store.isDirty = true
    } else {
      throw new Error(response.message || 'upload failed')
    }
  } catch (error) {
    console.error('Error uploading image:', error)
    toast.add({
      title: 'Error al subir',
      description: file.name,
      icon: 'i-lucide-triangle-alert',
      color: 'error',
    })
  } finally {
    uploadingCount.value--
    uploadDone.value++
    // Whole queue drained → reset the batch counters for the next upload.
    if (uploadingCount.value === 0) {
      uploadTotal.value = 0
      uploadDone.value = 0
    }
  }
}

const addFiles = async (files: File[]) => {
  const images = files.filter(f => f.type.startsWith('image/'))
  const skipped = files.length - images.length
  if (skipped > 0) {
    toast.add({
      title: skipped === 1 ? 'Se omitió 1 archivo' : `Se omitieron ${skipped} archivos`,
      description: 'Solo se permiten imágenes (JPEG, PNG o WebP).',
      icon: 'i-lucide-triangle-alert',
      color: 'warning',
    })
  }
  if (images.length === 0) return

  // Track the batch so the UI shows "Subiendo X de N…" + skeleton tiles.
  uploadTotal.value += images.length
  uploadingCount.value += images.length

  // Upload in parallel; each call decrements the counters in its finally block.
  await Promise.all(images.map(file => uploadOne(file)))
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

  // Re-sync primary (first in order = primary) + mark dirty
  syncPrimaryByOrder()
}

// Ensure the first image in the array is always marked as primary, the rest aren't.
// This keeps the data model consistent with the new "order-based primary" rule
// (first position = primary). The store only auto-marks isDirty on basicInfo changes,
// so we flag it manually for multimedia mutations.
const syncPrimaryByOrder = () => {
  store.multimedia.images.forEach((img: any, i: number) => {
    img.isPrimary = i === 0
  })
  store.isDirty = true
}

// === Bulk selection for delete ===
const selectedImageIds = ref<Set<string>>(new Set())

const isImageSelected = (id: string) => selectedImageIds.value.has(id)

const toggleImageSelection = (id: string) => {
  const next = new Set(selectedImageIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selectedImageIds.value = next
}

const selectAllImages = () => {
  selectedImageIds.value = new Set(store.multimedia.images.map((i: any) => i.id))
}

const clearImageSelection = () => {
  selectedImageIds.value = new Set()
}

const deleteSelectedImages = async () => {
  if (selectedImageIds.value.size === 0) return
  const count = selectedImageIds.value.size
  const ok = await confirm({
    title: `Eliminar ${count} imagen${count === 1 ? '' : 'es'}`,
    description: `Vas a eliminar ${count} imagen${count === 1 ? '' : 'es'} de la galería. Esta acción no se puede deshacer.`,
    confirmLabel: `Eliminar ${count}`,
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return

  const idsToDelete = new Set(selectedImageIds.value)
  store.multimedia.images = store.multimedia.images.filter((img: any) => {
    if (idsToDelete.has(img.id)) {
      // Also remove from tempImages so backend doesn't try to attach a deleted file
      const tempIdx = store.tempImages.findIndex((t: any) => t.filename === img.filename)
      if (tempIdx !== -1) store.tempImages.splice(tempIdx, 1)
      return false
    }
    return true
  })

  syncPrimaryByOrder()
  clearImageSelection()
}

// Touch-friendly reorder: drag-and-drop (below) doesn't fire on tablets, so
// these step the image one slot earlier/later. Same splice + order-resync as
// onDrop, exposed as buttons in the image overlay.
const moveImage = (index: number, dir: -1 | 1) => {
  const images = store.multimedia.images
  const target = index + dir
  if (target < 0 || target >= images.length) return
  const [moved] = images.splice(index, 1)
  images.splice(target, 0, moved)
  images.forEach((img: any, i: number) => { img.order = i + 1 })
  syncPrimaryByOrder()
  store.isDirty = true
}

// Drag-and-drop reordering of gallery images.
const dragFromIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const onDragStart = (index: number, e: DragEvent) => {
  dragFromIndex.value = index
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(index))
  }
}

const onDragOver = (index: number, e: DragEvent) => {
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
  if (dragFromIndex.value !== null && dragFromIndex.value !== index) {
    dragOverIndex.value = index
  }
}

const onDragLeave = (index: number) => {
  if (dragOverIndex.value === index) dragOverIndex.value = null
}

const onDrop = (targetIndex: number, e: DragEvent) => {
  e.preventDefault()
  const from = dragFromIndex.value
  dragFromIndex.value = null
  dragOverIndex.value = null
  if (from === null || from === targetIndex) return

  const images = store.multimedia.images
  const [moved] = images.splice(from, 1)
  images.splice(targetIndex, 0, moved)

  // Refresh order field (1-based) so the backend keeps the sequence
  images.forEach((img: any, i: number) => { img.order = i + 1 })

  // The first image in the new order becomes the primary
  syncPrimaryByOrder()
}

const onDragEnd = () => {
  dragFromIndex.value = null
  dragOverIndex.value = null
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
