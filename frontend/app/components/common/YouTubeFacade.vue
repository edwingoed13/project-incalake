<template>
  <div class="absolute inset-0 w-full h-full" :class="containerClass">
    <!-- Before click: a static thumbnail + play button. Zero JS from YouTube
         on the page, no embedded player downloaded. Saves 1-2s of FID/INP
         on slow connections — the YouTube player ships ~600KB of JS just
         to render. -->
    <button
      v-if="!loaded"
      type="button"
      class="group relative w-full h-full block bg-black overflow-hidden"
      :aria-label="`Reproducir video: ${title}`"
      @click="loaded = true"
    >
      <img
        :src="thumbnailUrl"
        :alt="title"
        class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 transition-opacity"
        loading="lazy"
        decoding="async"
        width="480"
        height="360"
      />
      <span class="absolute inset-0 flex items-center justify-center">
        <span class="flex items-center justify-center size-16 md:size-20 rounded-full bg-red-600 text-white shadow-2xl transition-transform group-hover:scale-110 group-active:scale-95">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 md:w-10 md:h-10 ml-1" aria-hidden="true">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </span>
      </span>
    </button>

    <iframe
      v-else
      class="absolute inset-0 w-full h-full"
      :src="embedUrl"
      :title="`Video: ${title}`"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen
      referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  videoId: string
  title?: string
  /** Optional Tailwind class override for the wrapper (e.g. rounded corners) */
  containerClass?: string
  /** Optional iframe URL params (e.g. for shorts: ?loop=1&playlist=ID) */
  extraParams?: Record<string, string>
}>()

const loaded = ref(false)

// `hqdefault.jpg` is 480x360 and exists for every public video. Falls back
// to a black background when the thumbnail itself fails (private video,
// removed video). Cheaper than maxresdefault because mosaic cells / shorts
// don't need 1080p.
const thumbnailUrl = computed(() => `https://i.ytimg.com/vi/${props.videoId}/hqdefault.jpg`)

const embedUrl = computed(() => {
  const params = new URLSearchParams({
    autoplay: '1',
    rel: '0',
    modestbranding: '1',
    ...(props.extraParams || {}),
  })
  return `https://www.youtube.com/embed/${props.videoId}?${params.toString()}`
})
</script>
