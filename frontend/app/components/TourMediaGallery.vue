<script setup lang="ts">
interface Props {
  tour: any
}

const props = defineProps<Props>()
const config = useRuntimeConfig()

// Extract YouTube video ID
const youtubeVideoId = computed(() => {
  if (!props.tour?.youtube_url) return null

  const url = props.tour.youtube_url

  // Detect YouTube Shorts
  const shortsMatch = url.match(/youtube\.com\/shorts\/([^"&?\/ ]{11})/)
  if (shortsMatch) {
    return { id: shortsMatch[1], isShort: true }
  }

  // Detect regular YouTube videos
  const regularMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/)
  if (regularMatch) {
    return { id: regularMatch[1], isShort: false }
  }

  return null
})

// Generate optimized YouTube embed URL
const getYouTubeEmbedUrl = computed(() => {
  if (!youtubeVideoId.value) return ''

  const baseUrl = 'https://www.youtube-nocookie.com/embed/'
  const params = new URLSearchParams({
    autoplay: '0',
    rel: '0',
    modestbranding: '1',
    playsinline: '1',
    enablejsapi: '1'
  })

  return `${baseUrl}${youtubeVideoId.value.id}?${params.toString()}`
})

// YouTube thumbnail URL
const youtubeThumbnail = computed(() => {
  if (!youtubeVideoId.value) return ''
  return `https://img.youtube.com/vi/${youtubeVideoId.value.id}/maxresdefault.jpg`
})

// Gallery images
const images = computed(() => {
  if (!props.tour?.media_gallery || props.tour.media_gallery.length === 0) {
    return []
  }
  return props.tour.media_gallery.map((media: any) => ({
    url: getImageUrl(media.url || media.path),
    alt: media.alt_text || props.tour.title,
    title: media.title_text || '',
  }))
})

// Auto-detect layout based on content
const galleryLayout = computed(() => {
  // If there's a YouTube Short (vertical video)
  if (youtubeVideoId.value?.isShort) {
    return 'video_image' // Short left + image right on desktop
  }

  // If there's a regular horizontal video
  if (youtubeVideoId.value && !youtubeVideoId.value.isShort) {
    return 'video_horizontal_mosaic'
  }

  // No video: default mosaic
  return 'hero_mosaic'
})

// Display images for each layout
const maxImagesForShort = computed(() => {
  return images.value.length > 10 ? 3 : 4
})

const displayImages = computed(() => {
  if (galleryLayout.value === 'video_image') {
    return images.value.slice(0, maxImagesForShort.value)
  } else if (galleryLayout.value === 'video_horizontal_mosaic') {
    return images.value.slice(0, 4)
  }
  return images.value
})

const remainingImagesCount = computed(() => {
  return Math.max(0, images.value.length - displayImages.value.length)
})

// Lightbox state
const lightboxOpen = ref(false)
const currentImageIndex = ref(0)

// Video modal state
const videoModalOpen = ref(false)

function openVideoModal() {
  videoModalOpen.value = true
  document.body.style.overflow = 'hidden'
}

function closeVideoModal() {
  videoModalOpen.value = false
  document.body.style.overflow = ''
}

function openLightbox(index: number) {
  currentImageIndex.value = index
  lightboxOpen.value = true
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightboxOpen.value = false
  document.body.style.overflow = ''
}

function nextImage() {
  if (currentImageIndex.value < images.value.length - 1) {
    currentImageIndex.value++
  }
}

function prevImage() {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
  }
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}
</script>

<template>
  <div>
    <!-- LAYOUT 1: MOSAICO (1 grande + 4 pequeñas) -->
    <div v-if="galleryLayout === 'hero_mosaic' && images.length > 0" class="grid grid-cols-4 grid-rows-2 gap-2 h-[300px] md:h-[500px] overflow-hidden rounded-xl">
      <!-- Hero Image (primera imagen grande) -->
      <div class="col-span-4 md:col-span-2 md:row-span-2 relative group cursor-pointer overflow-hidden" @click="openLightbox(0)">
        <img
          :src="images[0].url"
          :alt="images[0].alt"
          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
      </div>

      <!-- Mosaic Images (siguientes 4 imágenes pequeñas) -->
      <template v-for="(image, index) in images.slice(1, 5)" :key="index + 1">
        <!-- Última imagen con overlay si hay más de 5 -->
        <div
          v-if="index === 3 && images.length > 5"
          class="col-span-2 md:col-span-1 md:row-span-1 relative cursor-pointer group overflow-hidden"
          @click="openLightbox(4)"
        >
          <img
            :src="image.url"
            :alt="image.alt"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-black/60 group-hover:bg-black/70 flex items-center justify-center transition-colors">
            <span class="text-white text-xl md:text-2xl font-bold">+{{ images.length - 5 }} más</span>
          </div>
        </div>

        <!-- Imágenes normales -->
        <div
          v-else
          class="col-span-2 md:col-span-1 md:row-span-1 relative group cursor-pointer overflow-hidden"
          @click="openLightbox(index + 1)"
        >
          <img
            :src="image.url"
            :alt="image.alt"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
          />
        </div>
      </template>
    </div>

    <!-- LAYOUT 2: VIDEO VERTICAL (SHORT) + 3 IMÁGENES CURADAS -->
    <div v-else-if="galleryLayout === 'video_image' && youtubeVideoId && images.length > 0">
      <!-- Desktop: Video Short left + 3 Curated Images right -->
      <div class="hidden md:grid md:grid-cols-[300px_1fr] gap-2 rounded-xl overflow-hidden h-[500px]">
        <!-- Video Column (Left) -->
        <div class="relative bg-black rounded-l-xl overflow-hidden h-[500px] w-[300px]">
          <iframe
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
            style="width: 300px; height: 533px;"
            :src="getYouTubeEmbedUrl"
            :title="`Video: ${tour.title}`"
            frameborder="0"
            loading="lazy"
            allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"
          ></iframe>
        </div>

        <!-- Curated Images Column (Right) -->
        <div class="flex flex-col gap-2">
          <!-- Primera imagen grande -->
          <div
            v-if="displayImages[0]"
            class="relative cursor-pointer hover:opacity-90 transition-opacity flex-1"
            @click="openLightbox(0)"
          >
            <img
              :src="displayImages[0].url"
              :alt="displayImages[0].alt"
              class="w-full h-full object-cover rounded"
            />
          </div>

          <!-- 2 imágenes pequeñas -->
          <div class="grid grid-cols-2 gap-2 h-[182px]">
            <div
              v-if="displayImages[1]"
              class="relative cursor-pointer hover:opacity-90 transition-opacity"
              @click="openLightbox(1)"
            >
              <img
                :src="displayImages[1].url"
                :alt="displayImages[1].alt"
                class="w-full h-full object-cover rounded"
              />
            </div>

            <!-- Última con overlay "Ver todas" -->
            <div
              v-if="displayImages[2]"
              class="relative cursor-pointer group overflow-hidden rounded"
              @click="openLightbox(2)"
            >
              <img
                :src="displayImages[2].url"
                :alt="displayImages[2].alt"
                class="w-full h-full object-cover"
              />
              <div
                v-if="remainingImagesCount > 0"
                class="absolute inset-0 bg-black/70 group-hover:bg-black/80 flex items-center justify-center transition-colors"
              >
                <div class="flex flex-col items-center">
                  <span class="material-symbols-outlined text-white text-3xl mb-2">photo_library</span>
                  <span class="text-white text-sm font-semibold">Ver todas</span>
                  <span class="text-white text-xs">{{ images.length }} fotos</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile: Video + 2 Imágenes -->
      <div class="md:hidden space-y-2">
        <div class="rounded-xl overflow-hidden bg-black flex justify-center h-[400px]">
          <div
            class="relative cursor-pointer group w-[225px] h-[400px]"
            @click="openVideoModal"
          >
            <img
              :src="youtubeThumbnail"
              :alt="`Video: ${tour.title}`"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors"></div>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center shadow-2xl transform group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-white text-4xl fill-1">play_arrow</span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div v-if="displayImages[0]" class="relative cursor-pointer" @click="openLightbox(0)">
            <img :src="displayImages[0].url" :alt="displayImages[0].alt" class="w-full h-32 object-cover rounded" />
          </div>
          <div v-if="displayImages[1]" class="relative cursor-pointer group" @click="openLightbox(1)">
            <img :src="displayImages[1].url" :alt="displayImages[1].alt" class="w-full h-32 object-cover rounded" />
            <div v-if="remainingImagesCount > 0" class="absolute inset-0 bg-black/70 group-hover:bg-black/80 flex flex-col items-center justify-center transition-colors rounded">
              <span class="material-symbols-outlined text-white text-2xl">photo_library</span>
              <span class="text-white text-xs font-semibold">+{{ remainingImagesCount + 1 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LAYOUT 3: VIDEO HORIZONTAL + IMAGEN DESTACADA -->
    <div v-else-if="galleryLayout === 'video_horizontal_mosaic' && youtubeVideoId && images.length > 0">
      <!-- Desktop: Video (60%) + Featured Image (40%) -->
      <div class="hidden md:grid md:grid-cols-[1.5fr_1fr] gap-2 rounded-xl overflow-hidden">
        <!-- Video Column -->
        <div class="relative bg-black rounded-l-xl overflow-hidden">
          <div style="padding-bottom: 56.25%; position: relative;">
            <iframe
              :src="getYouTubeEmbedUrl"
              :title="`Video: ${tour.title}`"
              class="absolute top-0 left-0 w-full h-full"
              frameborder="0"
              loading="lazy"
              allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          </div>
        </div>

        <!-- Featured Image with Gallery Button -->
        <div class="relative cursor-pointer group" @click="openLightbox(0)">
          <img
            v-if="displayImages[0]"
            :src="displayImages[0].url"
            :alt="displayImages[0].alt"
            class="w-full h-full object-cover rounded-r-xl"
          />
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors rounded-r-xl">
            <div class="absolute bottom-4 right-4">
              <button
                class="flex items-center gap-2 bg-white/90 hover:bg-white backdrop-blur-sm text-slate-900 px-4 py-2 rounded-lg shadow-lg transition-all transform hover:scale-105 font-semibold text-sm"
                @click.stop="openLightbox(0)"
              >
                <span class="material-symbols-outlined text-lg">photo_library</span>
                <span>Ver fotos ({{ images.length }})</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile: Video + 2 Imágenes -->
      <div class="md:hidden space-y-2">
        <div class="rounded-xl overflow-hidden bg-black">
          <div style="padding-bottom: 56.25%; position: relative;">
            <iframe
              :src="getYouTubeEmbedUrl"
              :title="`Video: ${tour.title}`"
              class="absolute top-0 left-0 w-full h-full"
              frameborder="0"
              allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div v-if="displayImages[0]" class="relative cursor-pointer" @click="openLightbox(0)">
            <img :src="displayImages[0].url" :alt="displayImages[0].alt" class="w-full h-32 object-cover rounded" />
          </div>
          <div v-if="displayImages[1]" class="relative cursor-pointer group" @click="openLightbox(1)">
            <img :src="displayImages[1].url" :alt="displayImages[1].alt" class="w-full h-32 object-cover rounded" />
            <div v-if="remainingImagesCount > 0" class="absolute inset-0 bg-black/70 group-hover:bg-black/80 flex flex-col items-center justify-center transition-colors rounded">
              <span class="material-symbols-outlined text-white text-2xl">photo_library</span>
              <span class="text-white text-xs font-semibold">+{{ remainingImagesCount + 1 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Video Modal (Mobile) -->
    <Teleport to="body">
      <div
        v-if="videoModalOpen"
        class="fixed inset-0 z-[100] bg-black md:hidden"
        @click="closeVideoModal"
      >
        <button
          @click.stop="closeVideoModal"
          class="absolute top-4 right-4 z-20 w-12 h-12 bg-black/50 backdrop-blur-sm rounded-full flex items-center justify-center text-white shadow-lg"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>

        <div class="relative w-full h-full flex items-center justify-center" @click.stop>
          <div class="relative h-full" style="aspect-ratio: 9/16;">
            <iframe
              :src="`${getYouTubeEmbedUrl}&autoplay=1`"
              :title="`Video: ${tour.title}`"
              class="w-full h-full"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div
        v-if="lightboxOpen"
        class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center"
        @click="closeLightbox"
      >
        <button
          @click.stop="closeLightbox"
          class="absolute top-4 right-4 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-colors z-10"
        >
          <span class="material-symbols-outlined text-3xl">close</span>
        </button>

        <button
          v-if="currentImageIndex > 0"
          @click.stop="prevImage"
          class="absolute left-4 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-colors z-10"
        >
          <span class="material-symbols-outlined text-3xl">chevron_left</span>
        </button>

        <img
          :src="images[currentImageIndex].url"
          :alt="images[currentImageIndex].alt"
          class="max-w-full max-h-full object-contain px-4"
          @click.stop
        />

        <button
          v-if="currentImageIndex < images.length - 1"
          @click.stop="nextImage"
          class="absolute right-4 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-colors z-10"
        >
          <span class="material-symbols-outlined text-3xl">chevron_right</span>
        </button>

        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm bg-black/50 backdrop-blur-sm px-4 py-2 rounded-full">
          {{ currentImageIndex + 1 }} / {{ images.length }}
        </div>
      </div>
    </Teleport>
  </div>
</template>
