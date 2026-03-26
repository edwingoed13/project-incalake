<script setup lang="ts">
interface Props {
  images: Array<{ url: string; alt: string; title?: string }>
  currentIndex: number
  isOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  next: []
  prev: []
}>()

// Keyboard navigation
onMounted(() => {
  const handleKeydown = (e: KeyboardEvent) => {
    if (!props.isOpen) return

    if (e.key === 'Escape') emit('close')
    if (e.key === 'ArrowRight') emit('next')
    if (e.key === 'ArrowLeft') emit('prev')
  }

  window.addEventListener('keydown', handleKeydown)

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
  })
})

// Prevent body scroll when open
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

const currentImage = computed(() => props.images[props.currentIndex])
const hasNext = computed(() => props.currentIndex < props.images.length - 1)
const hasPrev = computed(() => props.currentIndex > 0)
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 bg-black/95 flex flex-col"
        @click="emit('close')"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 md:p-6">
          <div class="text-white">
            <span class="text-sm md:text-base">{{ currentIndex + 1 }} / {{ images.length }}</span>
          </div>
          <button
            class="text-white hover:text-gray-300 transition-colors"
            @click.stop="emit('close')"
          >
            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Image Container -->
        <div class="flex-1 flex items-center justify-center p-4 relative" @click.stop>
          <!-- Previous Button -->
          <button
            v-if="hasPrev"
            class="absolute left-2 md:left-4 p-2 md:p-3 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors z-10"
            @click.stop="emit('prev')"
          >
            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>

          <!-- Image -->
          <div class="max-w-7xl max-h-full flex items-center justify-center">
            <NuxtImg
              :src="currentImage.url"
              :alt="currentImage.alt"
              format="webp"
              quality="90"
              sizes="90vw"
              class="max-w-full max-h-[80vh] object-contain"
            />
          </div>

          <!-- Next Button -->
          <button
            v-if="hasNext"
            class="absolute right-2 md:right-4 p-2 md:p-3 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors z-10"
            @click.stop="emit('next')"
          >
            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>

        <!-- Caption -->
        <div v-if="currentImage.title" class="p-4 md:p-6 text-center" @click.stop>
          <p class="text-white text-sm md:text-base">{{ currentImage.title }}</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
