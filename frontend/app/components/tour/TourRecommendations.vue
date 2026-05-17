<template>
  <!-- Only renders when the tour actually has recommendations / what-to-bring
       content from admin Step 3. No generic hardcoded fallback. -->
  <section
    v-if="sanitizedRecommendations || sanitizedWhatToBring"
    class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8"
  >
    <h2 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-slate-100 mb-4 md:mb-6 flex items-center gap-2">
      <LightBulbIcon class="size-6 md:size-7 text-primary" aria-hidden="true" />
      {{ t('important_info') }}
    </h2>

    <div class="prose md:prose-lg max-w-2xl text-slate-600 dark:text-slate-400">
      <div v-if="sanitizedRecommendations" v-html="sanitizedRecommendations" class="mb-6"></div>
      <div v-if="sanitizedWhatToBring" v-html="sanitizedWhatToBring"></div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { LightBulbIcon } from '@heroicons/vue/24/outline'
const { t } = useI18n()

interface Props {
  tour: any
}

const props = defineProps<Props>()

const sanitizedRecommendations = computed(() => sanitizeHtml(props.tour.recommendations || ''))
const sanitizedWhatToBring = computed(() => sanitizeHtml(props.tour.what_to_bring || ''))
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
  @apply text-2xl md:text-3xl mb-4 mt-6;
}

.prose :deep(h2) {
  @apply text-xl md:text-2xl mb-3 mt-5;
}

.prose :deep(h3) {
  @apply text-lg md:text-xl mb-2 mt-4;
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
</style>
