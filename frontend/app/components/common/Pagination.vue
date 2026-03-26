<template>
  <div class="flex flex-col items-center gap-4">
    <!-- Results Info -->
    <div class="text-sm text-secondary-light dark:text-secondary-dark text-center">
      Showing
      <span class="font-bold">{{ from || 1 }}</span>
      -
      <span class="font-bold">{{ to || total }}</span>
      of
      <span class="font-bold">{{ total }}</span>
      results
    </div>

    <!-- Pagination Buttons -->
    <nav class="flex items-center gap-1 md:gap-2" aria-label="Pagination">
      <!-- Previous Button -->
      <button
        @click="prevPage"
        :disabled="!hasPrev"
        class="px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-primary-light dark:text-primary-dark hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :class="{ 'hover:border-primary': hasPrev }"
        aria-label="Previous page"
      >
        <span class="material-symbols-outlined text-xl">chevron_left</span>
      </button>

      <!-- Page Numbers -->
      <div class="flex items-center gap-1">
        <template v-for="(page, index) in pages" :key="index">
          <!-- Dots -->
          <span
            v-if="page === '...'"
            class="px-2 py-2 text-slate-400 select-none hidden sm:inline-block"
          >
            ⋯
          </span>

          <!-- Page Button -->
          <button
            v-else
            @click="goToPage(page as number)"
            class="min-w-[44px] h-11 px-3 py-2 rounded-lg border transition-colors font-bold"
            :class="
              page === currentPage
                ? 'bg-primary text-white border-primary'
                : 'bg-white dark:bg-slate-900 text-primary-light dark:text-primary-dark border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-primary'
            "
            :aria-label="`Go to page ${page}`"
            :aria-current="page === currentPage ? 'page' : undefined"
          >
            {{ page }}
          </button>
        </template>
      </div>

      <!-- Next Button -->
      <button
        @click="nextPage"
        :disabled="!hasNext"
        class="px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-primary-light dark:text-primary-dark hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :class="{ 'hover:border-primary': hasNext }"
        aria-label="Next page"
      >
        <span class="material-symbols-outlined text-xl">chevron_right</span>
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  currentPage: number
  lastPage: number
  total: number
  perPage: number
  from?: number
  to?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'page-change': [page: number]
}>()

const pages = computed(() => {
  const range: (number | string)[] = []
  const delta = 2 // Number of pages to show on each side of current page

  if (props.lastPage <= 7) {
    // Show all pages if total is small
    for (let i = 1; i <= props.lastPage; i++) {
      range.push(i)
    }
  } else {
    // Show first page
    range.push(1)

    // Show dots if needed
    if (props.currentPage > delta + 2) {
      range.push('...')
    }

    // Show pages around current
    const start = Math.max(2, props.currentPage - delta)
    const end = Math.min(props.lastPage - 1, props.currentPage + delta)

    for (let i = start; i <= end; i++) {
      range.push(i)
    }

    // Show dots if needed
    if (props.currentPage < props.lastPage - delta - 1) {
      range.push('...')
    }

    // Show last page
    range.push(props.lastPage)
  }

  return range
})

const hasPrev = computed(() => props.currentPage > 1)
const hasNext = computed(() => props.currentPage < props.lastPage)

function goToPage(page: number) {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('page-change', page)
  }
}

function prevPage() {
  if (hasPrev.value) {
    goToPage(props.currentPage - 1)
  }
}

function nextPage() {
  if (hasNext.value) {
    goToPage(props.currentPage + 1)
  }
}
</script>
