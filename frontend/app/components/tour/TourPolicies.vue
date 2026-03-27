<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-6 md:p-8">
    <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">shield</span>
      Cancellation Policies
    </h2>
    <div class="prose prose-lg max-w-none text-slate-600 dark:text-slate-400">
      <div v-html="sanitizedPolicies"></div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
// sanitizeHtml is auto-imported

interface Props {
  tour: any
}

const props = defineProps<Props>()

const sanitizedPolicies = computed(() => sanitizeHtml(props.tour.cancellation_policy || props.tour.policies || ''))
</script>

<style scoped>
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

.prose :deep(table) {
  @apply w-full border-collapse my-4;
}

.prose :deep(thead) {
  @apply bg-slate-50 dark:bg-slate-800;
}

.prose :deep(th) {
  @apply px-4 py-3 text-left text-sm font-bold text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700;
}

.prose :deep(td) {
  @apply px-4 py-3 text-sm text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700;
}

.prose :deep(tbody tr:nth-child(even)) {
  @apply bg-slate-50 dark:bg-slate-800/50;
}
</style>
