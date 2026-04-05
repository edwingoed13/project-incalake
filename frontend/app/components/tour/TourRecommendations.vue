<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-6 md:p-8">
    <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">lightbulb</span>
      {{ t('important_info') }}
    </h2>

    <div v-if="sanitizedRecommendations || sanitizedWhatToBring" class="prose prose-lg max-w-none text-slate-600 dark:text-slate-400">
      <div v-if="sanitizedRecommendations" v-html="sanitizedRecommendations" class="mb-6"></div>
      <div v-if="sanitizedWhatToBring" v-html="sanitizedWhatToBring"></div>
    </div>

    <div v-else class="grid md:grid-cols-2 gap-4">
      <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl mt-1">badge</span>
        <div>
          <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-1">Documentation</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400">Valid passport required with at least 6 months validity</p>
        </div>
      </div>
      <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl mt-1">hiking</span>
        <div>
          <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-1">Difficulty Level</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400">
            {{ tour.difficulty === 'easy' ? 'Easy' : tour.difficulty === 'moderate' ? 'Moderate' : 'Difficult' }}
          </p>
        </div>
      </div>
      <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl mt-1">medical_services</span>
        <div>
          <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-1">Restrictions</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400">Consult your doctor before booking if you have medical conditions</p>
        </div>
      </div>
      <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl mt-1">backpack</span>
        <div>
          <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-1">What to Bring</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400">Sunscreen, insect repellent, hat, water, comfortable clothes</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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
