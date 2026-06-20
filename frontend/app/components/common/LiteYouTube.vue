<script setup lang="ts">
// Lazy "facade" for YouTube embeds: shows only the poster thumbnail + a play
// button on first paint, and mounts the real (heavy) iframe ONLY after the user
// clicks. This keeps ~0.5–1 MB of YouTube player JS out of the initial load —
// the pattern YouTube itself uses and web.dev recommends.
const props = defineProps<{
  videoId: string
  title?: string
}>()

const activated = ref(false)

// hqdefault always exists (maxresdefault is missing for some uploads). i.ytimg
// is the canonical thumbnail host.
const poster = computed(() => `https://i.ytimg.com/vi/${props.videoId}/hqdefault.jpg`)
// youtube-nocookie + autoplay once the user opted in by clicking.
const embedSrc = computed(
  () => `https://www.youtube-nocookie.com/embed/${props.videoId}?autoplay=1&rel=0`,
)
</script>

<template>
  <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-900 shadow-md">
    <!-- Facade: poster + play button, no iframe yet -->
    <button
      v-if="!activated"
      type="button"
      @click="activated = true"
      class="group absolute inset-0 h-full w-full cursor-pointer"
      :aria-label="title ? `Reproducir: ${title}` : 'Reproducir video'"
    >
      <img
        :src="poster"
        :alt="title || 'Video testimonio'"
        loading="lazy"
        decoding="async"
        width="480"
        height="270"
        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
      />
      <!-- Darkening + play button -->
      <span class="absolute inset-0 bg-black/20 transition-colors group-hover:bg-black/30"></span>
      <span
        class="absolute left-1/2 top-1/2 flex size-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-red-600 shadow-xl transition-transform group-hover:scale-110 group-active:scale-95"
      >
        <Icon name="material-symbols:play-arrow" class="text-3xl text-white" />
      </span>
      <!-- Optional caption -->
      <span
        v-if="title"
        class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-left text-sm font-semibold text-white line-clamp-2"
      >
        {{ title }}
      </span>
    </button>

    <!-- Real player — mounted only after click -->
    <iframe
      v-else
      :src="embedSrc"
      :title="title || 'YouTube video'"
      class="absolute inset-0 h-full w-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      referrerpolicy="strict-origin-when-cross-origin"
      allowfullscreen
    ></iframe>
  </div>
</template>
