<script setup lang="ts">
import { countries, countryFlag } from '~/utils/countries'

interface Props {
  modelValue: string
  error?: string
  label?: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select country...',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)
const search = ref('')
const listRef = ref<HTMLElement | null>(null)
const inputRef = ref<HTMLInputElement | null>(null)

const selectedCountry = computed(() =>
  countries.find(c => c.code === props.modelValue)
)

const filteredCountries = computed(() => {
  if (!search.value) return countries
  const q = search.value.toLowerCase()
  return countries.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.code.toLowerCase().includes(q) ||
    c.dial.includes(q)
  )
})

function select(code: string) {
  emit('update:modelValue', code)
  open.value = false
  search.value = ''
}

function toggle() {
  open.value = !open.value
  if (open.value) {
    search.value = ''
    nextTick(() => inputRef.value?.focus())
  }
}

// Close on click outside
function onClickOutside(e: MouseEvent) {
  const target = e.target as HTMLElement
  if (!target.closest('.country-selector')) {
    open.value = false
    search.value = ''
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

// Scroll to selected on open
watch(open, (val) => {
  if (val && listRef.value) {
    nextTick(() => {
      const active = listRef.value?.querySelector('.active-country')
      active?.scrollIntoView({ block: 'center' })
    })
  }
})
</script>

<template>
  <div class="country-selector relative">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      class="w-full flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg transition-colors text-left"
      :class="[
        error ? 'border-red-500' : open ? 'border-primary ring-2 ring-primary/20' : 'border-slate-300 dark:border-slate-700',
      ]"
    >
      <span v-if="selectedCountry" class="text-lg leading-none">{{ countryFlag(selectedCountry.code) }}</span>
      <span v-if="selectedCountry" class="flex-1 text-sm font-medium text-slate-800 dark:text-white">
        {{ selectedCountry.name }}
        <span class="text-slate-400 ml-1">({{ selectedCountry.dial }})</span>
      </span>
      <span v-else class="flex-1 text-sm text-slate-400">{{ placeholder }}</span>
      <span class="material-symbols-outlined text-slate-400 text-lg transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div
        v-if="open"
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden"
      >
        <!-- Search -->
        <div class="p-2 border-b border-slate-100 dark:border-slate-800">
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
            <input
              ref="inputRef"
              v-model="search"
              type="text"
              class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
              placeholder="Search country..."
              @keydown.esc="open = false"
            />
          </div>
        </div>

        <!-- List -->
        <div ref="listRef" class="max-h-60 overflow-y-auto">
          <button
            v-for="country in filteredCountries"
            :key="country.code"
            type="button"
            @click="select(country.code)"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-primary/5 transition-colors"
            :class="[
              country.code === modelValue ? 'bg-primary/10 active-country' : '',
            ]"
          >
            <span class="text-lg leading-none w-6 text-center">{{ countryFlag(country.code) }}</span>
            <span class="flex-1 text-sm" :class="country.code === modelValue ? 'font-bold text-primary' : 'text-slate-700 dark:text-slate-300'">
              {{ country.name }}
            </span>
            <span class="text-xs text-slate-400 font-mono">{{ country.dial }}</span>
            <span v-if="country.code === modelValue" class="material-symbols-outlined text-primary text-sm">check</span>
          </button>

          <div v-if="filteredCountries.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
            No countries found
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
