<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-4 sm:p-6 md:p-8">
    <h2 class="text-xl md:text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 md:mb-6 flex items-center gap-2">
      <ClipboardDocumentCheckIcon class="size-6 md:size-7 text-primary" aria-hidden="true" />
      {{ t('whats_included') }}
    </h2>

    <div class="space-y-3">
      <!-- Included Accordion -->
      <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
        <button
          @click="toggleIncluded"
          type="button"
          class="w-full flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
        >
          <div class="flex items-center gap-3">
            <CheckCircleIcon class="size-5 text-green-500" aria-hidden="true" />
            <span class="font-bold text-primary-light dark:text-primary-dark">{{ t('included') }}</span>
          </div>
          <ChevronDownIcon
            class="size-5 text-slate-400 transition-transform"
            :class="{ 'rotate-180': includedOpen }"
            aria-hidden="true"
          />
        </button>
        <div
          v-show="includedOpen"
          class="p-4 bg-white dark:bg-slate-900"
        >
          <ul class="space-y-2 text-secondary-light dark:text-secondary-dark">
            <li v-for="(item, index) in includesList" :key="index" class="flex items-start gap-2">
              <CheckIcon class="size-4 text-green-500 mt-0.5 flex-shrink-0" aria-hidden="true" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Not Included Accordion -->
      <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
        <button
          @click="toggleNotIncluded"
          type="button"
          class="w-full flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
        >
          <div class="flex items-center gap-3">
            <XCircleIcon class="size-5 text-red-500" aria-hidden="true" />
            <span class="font-bold text-primary-light dark:text-primary-dark">{{ t('not_included') }}</span>
          </div>
          <ChevronDownIcon
            class="size-5 text-slate-400 transition-transform"
            :class="{ 'rotate-180': notIncludedOpen }"
            aria-hidden="true"
          />
        </button>
        <div
          v-show="notIncludedOpen"
          class="p-4 bg-white dark:bg-slate-900"
        >
          <ul class="space-y-2 text-secondary-light dark:text-secondary-dark">
            <li v-for="(item, index) in excludesList" :key="index" class="flex items-start gap-2">
              <XMarkIcon class="size-4 text-red-500 mt-0.5 flex-shrink-0" aria-hidden="true" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  ClipboardDocumentCheckIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  CheckIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
const { t } = useI18n()

interface Props {
  tour: any
}

const props = defineProps<Props>()

const includedOpen = ref(true)
const notIncludedOpen = ref(false)

function toggleIncluded() {
  includedOpen.value = !includedOpen.value
}

function toggleNotIncluded() {
  notIncludedOpen.value = !notIncludedOpen.value
}

// Parse includes/excludes from HTML lists or text
const includesList = computed(() => {
  if (!props.tour.what_includes) return []

  // Check if we're in browser environment
  if (typeof document === 'undefined') {
    // Server-side: return empty or basic parsing
    return []
  }

  const div = document.createElement('div')
  div.innerHTML = props.tour.what_includes
  const listItems = div.querySelectorAll('li')
  if (listItems.length > 0) {
    return Array.from(listItems).map(li => li.textContent?.trim() || '')
  }
  return props.tour.what_includes.split('\n').filter((item: string) => item.trim())
})

const excludesList = computed(() => {
  if (!props.tour.what_not_includes) return []

  // Check if we're in browser environment
  if (typeof document === 'undefined') {
    // Server-side: return empty or basic parsing
    return []
  }

  const div = document.createElement('div')
  div.innerHTML = props.tour.what_not_includes
  const listItems = div.querySelectorAll('li')
  if (listItems.length > 0) {
    return Array.from(listItems).map(li => li.textContent?.trim() || '')
  }
  return props.tour.what_not_includes.split('\n').filter((item: string) => item.trim())
})
</script>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
