<template>
  <div class="relative w-full">
    <!-- Search Icon -->
    <div class="absolute inset-y-0 left-0 pl-3 md:pl-4 flex items-center pointer-events-none">
      <span class="material-symbols-outlined text-slate-400 text-xl">search</span>
    </div>

    <!-- Search Input -->
    <input
      v-model="searchInput"
      type="search"
      class="w-full pl-10 md:pl-12 pr-10 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-primary-light dark:text-primary-dark placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary"
      placeholder="Search tours by name, city..."
      aria-label="Search tours"
    />

    <!-- Clear Button -->
    <button
      v-if="searchInput"
      @click="clearSearch"
      class="absolute inset-y-0 right-0 pr-3 md:pr-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
      aria-label="Clear search"
    >
      <span class="material-symbols-outlined text-xl">close</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  search: [value: string]
}>()

const searchInput = ref(props.modelValue)

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  searchInput.value = newValue
})

// Debounce search
let debounceTimer: NodeJS.Timeout | null = null

watch(searchInput, (newValue) => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }

  debounceTimer = setTimeout(() => {
    emit('update:modelValue', newValue)
    emit('search', newValue)
  }, 500)
})

function clearSearch() {
  searchInput.value = ''
  emit('update:modelValue', '')
  emit('search', '')
}
</script>

<style scoped>
/* Remove default search input styles */
input[type='search']::-webkit-search-decoration,
input[type='search']::-webkit-search-cancel-button,
input[type='search']::-webkit-search-results-button,
input[type='search']::-webkit-search-results-decoration {
  display: none;
}
</style>
