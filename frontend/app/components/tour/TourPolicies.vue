<template>
  <section class="bg-white rounded-2xl shadow-sm p-4 sm:p-6 md:p-8">
    <button type="button" @click="open = !open" :aria-expanded="open" class="w-full flex items-center justify-between gap-2 text-left">
      <h2 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
        <ShieldCheckIcon class="size-6 md:size-7 text-primary" aria-hidden="true" />
        Políticas de cancelación
      </h2>
      <Icon name="material-symbols:expand-more" class="size-6 text-slate-400 transition-transform shrink-0" :class="{ '-rotate-180': open }" aria-hidden="true" />
    </button>
    <div v-show="open" class="prose md:prose-lg max-w-2xl text-slate-600 mt-5 md:mt-6">
      <div v-html="sanitizedPolicies"></div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
// sanitizeHtml is auto-imported

// Collapsed by default — reference content, expand on demand.
const open = ref(false)

interface Props {
  tour: any
}

const props = defineProps<Props>()

const sanitizedPolicies = computed(() => sanitizeHtml(props.tour.cancellation_policy || props.tour.policies || ''))
</script>

<style scoped>
@reference "~/assets/css/main.css";

.prose {
  @apply text-slate-600 leading-relaxed;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3) {
  @apply font-black text-slate-800
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
  @apply text-slate-600
}

.prose :deep(strong) {
  @apply font-bold text-slate-800
}

.prose :deep(a) {
  @apply text-primary hover:text-primary-dark underline;
}

.prose :deep(blockquote) {
  @apply border-l-4 border-primary pl-4 italic text-slate-600 my-4 bg-slate-50 py-2;
}

.prose :deep(table) {
  @apply w-full border-collapse my-4;
}

.prose :deep(thead) {
  @apply bg-slate-50
}

.prose :deep(th) {
  @apply px-4 py-3 text-left text-sm font-bold text-slate-800 border border-slate-200
}

.prose :deep(td) {
  @apply px-4 py-3 text-sm text-slate-600 border border-slate-200
}

.prose :deep(tbody tr:nth-child(even)) {
  @apply bg-slate-50
}
</style>
