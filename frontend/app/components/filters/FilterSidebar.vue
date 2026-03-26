<template>
  <!-- Mobile: Filter Button -->
  <div class="md:hidden">
    <button
      @click="toggleMobileFilters"
      class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
    >
      <span class="material-symbols-outlined text-xl">tune</span>
      Filters
      <span v-if="activeFiltersCount" class="ml-2 px-2 py-0.5 bg-primary text-white text-xs font-bold rounded-full">
        {{ activeFiltersCount }}
      </span>
    </button>
  </div>

  <!-- Mobile: Modal Overlay -->
  <Transition name="fade">
    <div
      v-if="isOpen && isMobile"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"
      @click="isOpen = false"
    ></div>
  </Transition>

  <!-- Filter Sidebar -->
  <Transition name="slide-up">
    <aside
      v-show="!isMobile || isOpen"
      class="fixed md:static inset-x-0 bottom-0 md:inset-auto z-50 bg-white dark:bg-slate-900 md:bg-transparent max-h-[85vh] md:max-h-none overflow-y-auto md:overflow-visible rounded-t-2xl md:rounded-none shadow-2xl md:shadow-none"
    >
      <!-- Mobile Header -->
      <div class="sticky top-0 bg-white dark:bg-slate-900 md:hidden px-4 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-black text-primary-light dark:text-primary-dark">Filters</h3>
        <button @click="isOpen = false" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="p-4 md:p-0 space-y-6">
        <!-- Desktop Title -->
        <div class="hidden md:flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-primary-light dark:text-primary-dark">Filter Tours</h3>
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="text-sm text-primary hover:text-primary-dark font-bold"
          >
            Clear
          </button>
        </div>

        <!-- City Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-bold text-primary-light dark:text-primary-dark">City</label>
          <select
            v-model="localFilters.city_id"
            class="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-primary-light dark:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
            @change="!isMobile && applyFilters()"
          >
            <option :value="null">All cities</option>
            <option v-for="city in cities" :key="city.id" :value="city.id">
              {{ city.name }}
            </option>
          </select>
        </div>

        <!-- Category Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-bold text-primary-light dark:text-primary-dark">Category</label>
          <select
            v-model="localFilters.category_id"
            class="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-primary-light dark:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
            @change="!isMobile && applyFilters()"
          >
            <option :value="null">All categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- Difficulty Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-bold text-primary-light dark:text-primary-dark">Difficulty</label>
          <div class="space-y-2">
            <label
              v-for="diff in difficulties"
              :key="diff.value"
              class="flex items-center gap-2 cursor-pointer py-1"
            >
              <input
                type="radio"
                :value="diff.value"
                v-model="localFilters.difficulty"
                @change="!isMobile && applyFilters()"
                class="w-4 h-4 text-primary focus:ring-primary"
              />
              <span class="text-sm text-secondary-light dark:text-secondary-dark">{{ diff.label }}</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer py-1">
              <input
                type="radio"
                :value="null"
                v-model="localFilters.difficulty"
                @change="!isMobile && applyFilters()"
                class="w-4 h-4 text-primary focus:ring-primary"
              />
              <span class="text-sm text-secondary-light dark:text-secondary-dark">All</span>
            </label>
          </div>
        </div>

        <!-- Price Range Filter -->
        <div class="space-y-3">
          <label class="block text-sm font-bold text-primary-light dark:text-primary-dark">Price Range (USD)</label>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-secondary-light dark:text-secondary-dark mb-1">Min</label>
              <input
                v-model.number="localFilters.min_price"
                type="number"
                min="0"
                step="10"
                class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-primary-light dark:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="$0"
                @change="!isMobile && applyFilters()"
              />
            </div>
            <div>
              <label class="block text-xs text-secondary-light dark:text-secondary-dark mb-1">Max</label>
              <input
                v-model.number="localFilters.max_price"
                type="number"
                min="0"
                step="10"
                class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-primary-light dark:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="$1000"
                @change="!isMobile && applyFilters()"
              />
            </div>
          </div>
        </div>

        <!-- Mobile: Action Buttons -->
        <div v-if="isMobile" class="sticky bottom-0 bg-white dark:bg-slate-900 pt-4 pb-2 border-t border-slate-200 dark:border-slate-700 -mx-4 px-4 space-y-2">
          <button @click="applyFilters" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition-colors">
            Apply Filters
            <span v-if="activeFiltersCount" class="ml-2">
              ({{ activeFiltersCount }})
            </span>
          </button>
          <button @click="resetFilters" class="w-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-primary-light dark:text-primary-dark font-bold py-3 rounded-lg transition-colors">
            Clear Filters
          </button>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Props {
  cities?: Array<{ id: number; name: string }>
  categories?: Array<{ id: number; name: string }>
  modelValue?: any
}

const props = withDefaults(defineProps<Props>(), {
  cities: () => [],
  categories: () => [],
  modelValue: () => ({
    city_id: null,
    category_id: null,
    difficulty: null,
    min_price: null,
    max_price: null
  })
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
  apply: []
}>()

const isOpen = ref(false)
const isMobile = ref(false)

const difficulties = [
  { value: 'easy', label: 'Easy' },
  { value: 'moderate', label: 'Moderate' },
  { value: 'difficult', label: 'Difficult' },
  { value: 'hard', label: 'Hard' }
]

// Local filter state
const localFilters = ref({ ...props.modelValue })

// Watch for changes in modelValue prop
watch(() => props.modelValue, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })

// Check mobile on mount
onMounted(() => {
  isMobile.value = window.innerWidth < 768
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth < 768
  })
})

// Lock body scroll when modal is open on mobile
watch(isOpen, (open) => {
  if (isMobile.value) {
    document.body.style.overflow = open ? 'hidden' : ''
  }
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (localFilters.value.city_id) count++
  if (localFilters.value.category_id) count++
  if (localFilters.value.difficulty) count++
  if (localFilters.value.min_price) count++
  if (localFilters.value.max_price) count++
  return count
})

const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

function applyFilters() {
  emit('update:modelValue', { ...localFilters.value })
  emit('apply')
  if (isMobile.value) {
    isOpen.value = false
  }
}

function resetFilters() {
  localFilters.value = {
    city_id: null,
    category_id: null,
    difficulty: null,
    min_price: null,
    max_price: null
  }
  applyFilters()
}

function toggleMobileFilters() {
  isOpen.value = !isOpen.value
}

defineExpose({
  toggleMobileFilters
})
</script>

<style scoped>
/* Fade transition for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide up transition for mobile modal */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
