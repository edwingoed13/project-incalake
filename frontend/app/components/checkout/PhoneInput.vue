<script setup lang="ts">
import { countries, countryFlagUrl, getDialCode } from '~/utils/countries'

interface Props {
  phone: string
  country: string
  phoneError?: string
  countryError?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:phone': [value: string]
  'update:country': [value: string]
}>()

const open = ref(false)
const search = ref('')
const inputRef = ref<HTMLInputElement | null>(null)
const phoneRef = ref<HTMLInputElement | null>(null)
const listRef = ref<HTMLElement | null>(null)

const selectedCountry = computed(() =>
  countries.find(c => c.code === props.country)
)

const dialCode = computed(() => getDialCode(props.country))

const filteredCountries = computed(() => {
  if (!search.value) return countries
  const q = search.value.toLowerCase()
  return countries.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.code.toLowerCase().includes(q) ||
    c.dial.includes(q)
  )
})

function selectCountry(code: string) {
  emit('update:country', code)
  open.value = false
  search.value = ''
  nextTick(() => phoneRef.value?.focus())
}

function toggleDropdown() {
  open.value = !open.value
  if (open.value) {
    search.value = ''
    nextTick(() => inputRef.value?.focus())
  }
}

function onClickOutside(e: MouseEvent) {
  const target = e.target as HTMLElement
  if (!target.closest('.phone-input-group')) {
    open.value = false
    search.value = ''
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

watch(open, (val) => {
  if (val && listRef.value) {
    nextTick(() => {
      const active = listRef.value?.querySelector('.active-item')
      active?.scrollIntoView({ block: 'center' })
    })
  }
})
</script>

<template>
  <div class="phone-input-group relative">
    <!-- Combined input row -->
    <div class="flex">
      <!-- Country prefix button -->
      <button
        type="button"
        @click="toggleDropdown"
        class="inline-flex items-center gap-1.5 px-3 bg-slate-100 dark:bg-slate-700 border border-r-0 rounded-l-lg text-sm font-medium transition-colors hover:bg-slate-200 dark:hover:bg-slate-600 shrink-0"
        :class="[
          phoneError || countryError ? 'border-red-500' : open ? 'border-primary' : 'border-slate-300 dark:border-slate-700',
        ]"
      >
        <img :src="countryFlagUrl(country)" :alt="country" class="w-5 h-4 object-cover rounded-sm" />
        <span class="text-slate-700 dark:text-slate-300">{{ dialCode }}</span>
        <span class="material-symbols-outlined text-slate-400 text-sm transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
      </button>

      <!-- Phone number input -->
      <input
        ref="phoneRef"
        :value="phone"
        @input="emit('update:phone', ($event.target as HTMLInputElement).value)"
        type="tel"
        autocomplete="tel"
        class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-r-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors min-w-0"
        :class="phoneError ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
        placeholder="999 999 999"
      />
    </div>

    <!-- Country dropdown -->
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
        <div ref="listRef" class="max-h-56 overflow-y-auto">
          <button
            v-for="c in filteredCountries"
            :key="c.code"
            type="button"
            @click="selectCountry(c.code)"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-primary/5 transition-colors"
            :class="c.code === country ? 'bg-primary/10 active-item' : ''"
          >
            <img :src="countryFlagUrl(c.code)" :alt="c.code" class="w-5 h-4 object-cover rounded-sm" />
            <span class="flex-1 text-sm" :class="c.code === country ? 'font-bold text-primary' : 'text-slate-700 dark:text-slate-300'">
              {{ c.name }}
            </span>
            <span class="text-xs text-slate-400 font-mono">{{ c.dial }}</span>
            <span v-if="c.code === country" class="material-symbols-outlined text-primary text-sm">check</span>
          </button>

          <div v-if="filteredCountries.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
            No results
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
