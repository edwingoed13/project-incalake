<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8">
    <h2 class="heading-section flex items-center gap-sm text-slate-800 dark:text-slate-100">
      <MapIcon class="size-6 md:size-7 text-primary" aria-hidden="true" />
      {{ t('detailed_itinerary') }}
    </h2>
    <div class="prose md:prose-lg max-w-none text-prose">
      <div v-html="sanitizedItinerary"></div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { sanitizeHtml } from '@/utils/sanitize'
import { MapIcon } from '@heroicons/vue/24/outline'
const { t } = useI18n()

interface Props {
  tour: any
}

const props = defineProps<Props>()

const sanitizedItinerary = computed(() => sanitizeHtml(props.tour.itinerary || ''))
</script>

<style scoped>
@reference "../../assets/css/main.css";

.prose {
  @apply text-slate-600 dark:text-slate-400 leading-relaxed;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3) {
  @apply font-black text-slate-800 dark:text-slate-100;
}

.prose :deep(h1) {
  @apply text-h2 mb-md mt-lg;
}

.prose :deep(h2) {
  @apply text-h3 mb-md mt-md;
}

.prose :deep(h3) {
  @apply text-body font-bold mb-sm mt-md;
}

.prose :deep(p) {
  @apply mb-4 leading-relaxed;
}

.prose :deep(ul),
.prose :deep(ol) {
  @apply ml-6 mb-4 space-y-2;
}

.prose :deep(ul) {
  @apply list-disc;
}

.prose :deep(ol) {
  @apply list-decimal;
}

.prose :deep(li) {
  @apply text-slate-600 dark:text-slate-400;
}

.prose :deep(strong) {
  @apply font-bold text-slate-800 dark:text-slate-100;
}

.prose :deep(a) {
  @apply text-primary hover:text-primary-dark underline;
}

.prose :deep(blockquote) {
  @apply border-l-4 border-primary pl-4 italic text-slate-600 dark:text-slate-400 my-4 bg-slate-50 dark:bg-slate-800 py-2;
}

/* Timeline styles for itinerary */
.prose :deep(.timeline-item) {
  @apply relative pl-12 pb-8;
}

.prose :deep(.timeline-item::before) {
  content: '';
  @apply absolute left-4 top-8 bottom-0 w-0.5 bg-slate-200 dark:bg-slate-700;
}

.prose :deep(.timeline-item:last-child::before) {
  @apply hidden;
}
</style>
