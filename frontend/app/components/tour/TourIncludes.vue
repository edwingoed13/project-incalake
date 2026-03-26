<template>
  <section class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-6 md:p-8">
    <h2 class="text-2xl md:text-3xl font-black text-primary-light dark:text-primary-dark mb-6 flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">checklist</span>
      What's Included & Not Included
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
            <span class="material-symbols-outlined text-green-500 text-xl">check_circle</span>
            <span class="font-bold text-primary-light dark:text-primary-dark">What's Included</span>
          </div>
          <span
            class="material-symbols-outlined text-slate-400 transition-transform"
            :class="{ 'rotate-180': includedOpen }"
          >
            expand_more
          </span>
        </button>
        <div
          v-show="includedOpen"
          class="p-4 bg-white dark:bg-slate-900"
        >
          <ul class="space-y-2 text-secondary-light dark:text-secondary-dark">
            <li v-for="(item, index) in includesList" :key="index" class="flex items-start gap-2">
              <span class="material-symbols-outlined text-green-500 text-base mt-0.5 flex-shrink-0">done</span>
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
            <span class="material-symbols-outlined text-red-500 text-xl">cancel</span>
            <span class="font-bold text-primary-light dark:text-primary-dark">What's NOT Included</span>
          </div>
          <span
            class="material-symbols-outlined text-slate-400 transition-transform"
            :class="{ 'rotate-180': notIncludedOpen }"
          >
            expand_more
          </span>
        </button>
        <div
          v-show="notIncludedOpen"
          class="p-4 bg-white dark:bg-slate-900"
        >
          <ul class="space-y-2 text-secondary-light dark:text-secondary-dark">
            <li v-for="(item, index) in excludesList" :key="index" class="flex items-start gap-2">
              <span class="material-symbols-outlined text-red-500 text-base mt-0.5 flex-shrink-0">close</span>
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
