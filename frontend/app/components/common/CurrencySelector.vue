<template>
  <div class="relative" ref="dropdownRef">
    <!-- Trigger Button -->
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      :aria-expanded="isOpen"
      aria-haspopup="true"
    >
      <span class="material-symbols-outlined text-lg">payments</span>
      <span class="font-bold text-sm">{{ currencyStore.currentCurrency.code }}</span>
      <span class="material-symbols-outlined text-lg transition-transform" :class="{ 'rotate-180': isOpen }">
        expand_more
      </span>
    </button>

    <!-- Dropdown Menu -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-2 z-50"
        role="menu"
      >
        <!-- Header -->
        <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700">
          <p class="text-xs font-bold text-secondary-light dark:text-secondary-dark uppercase tracking-wide">
            Select Currency
          </p>
        </div>

        <!-- Currency Options -->
        <div class="max-h-80 overflow-y-auto">
          <button
            v-for="currency in currencyStore.CURRENCIES"
            :key="currency.code"
            @click="selectCurrency(currency.code)"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left"
            :class="{
              'bg-primary/10 dark:bg-primary/20': currency.code === currencyStore.selectedCurrency
            }"
            role="menuitem"
          >
            <div class="flex items-center gap-3">
              <span class="text-2xl font-bold text-primary w-8">{{ currency.symbol }}</span>
              <div>
                <p class="font-bold text-sm text-primary-light dark:text-primary-dark">
                  {{ currency.code }}
                </p>
                <p class="text-xs text-secondary-light dark:text-secondary-dark">
                  {{ currency.name }}
                </p>
              </div>
            </div>
            <span
              v-if="currency.code === currencyStore.selectedCurrency"
              class="material-symbols-outlined text-primary"
            >
              check_circle
            </span>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="currencyStore.loading" class="px-4 py-2 border-t border-slate-200 dark:border-slate-700">
          <p class="text-xs text-secondary-light dark:text-secondary-dark flex items-center gap-2">
            <span class="inline-block animate-spin rounded-full h-3 w-3 border-b-2 border-primary"></span>
            Updating rates...
          </p>
        </div>

        <!-- Error State -->
        <div v-else-if="currencyStore.error" class="px-4 py-2 border-t border-slate-200 dark:border-slate-700">
          <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ currencyStore.error }}
          </p>
        </div>

        <!-- Info -->
        <div v-else class="px-4 py-2 border-t border-slate-200 dark:border-slate-700">
          <p class="text-xs text-secondary-light dark:text-secondary-dark">
            Rates updated from live exchange data
          </p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
// useCurrencyStore is auto-imported

const currencyStore = useCurrencyStore()
const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

// Initialize currency store on mount
onMounted(() => {
  currencyStore.initCurrency()

  // Close dropdown when clicking outside
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

function handleClickOutside(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

function selectCurrency(code: string) {
  currencyStore.setCurrency(code)
  isOpen.value = false
}
</script>

<style scoped>
/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.rotate-180 {
  transform: rotate(180deg);
}
</style>
