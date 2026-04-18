<template>
  <header
    class="fixed top-0 z-[100] w-full transition-all duration-500 border-b"
    :class="[
      isScrolled
        ? 'bg-white/95 backdrop-blur-xl border-slate-200 py-2 shadow-lg'
        : 'bg-white/80 backdrop-blur-md border-slate-100 py-3'
    ]"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <!-- Logo -->
      <NuxtLink :to="localePath('/')" class="group flex items-center gap-2.5 active:scale-95 transition-transform">
        <div class="size-9 bg-primary group-hover:rotate-[15deg] transition-transform rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/30">
          <span class="material-symbols-outlined font-bold text-xl">explore</span>
        </div>
        <div class="flex flex-col">
          <h1 class="text-lg font-black tracking-tighter uppercase italic leading-none text-slate-900">Incalake</h1>
          <span class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-400">Tours & Experiences</span>
        </div>
      </NuxtLink>

      <!-- Desktop Navigation -->
      <nav class="hidden md:flex items-center gap-8">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.key"
          :to="localePath(link.path)"
          class="text-[11px] font-black uppercase tracking-widest transition-all hover:text-primary relative group text-slate-600"
        >
          {{ t(link.key) }}
          <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all group-hover:w-full"></span>
        </NuxtLink>
      </nav>

      <!-- Right Actions -->
      <div class="flex items-center gap-2">
        <!-- Language Selector -->
        <div class="relative">
          <button
            @click="langOpen = !langOpen"
            class="flex items-center gap-1.5 px-2 py-1.5 text-[10px] font-black uppercase border border-slate-200 rounded-lg hover:border-primary/40 transition-colors text-slate-600"
          >
            <img :src="flagSrc(locale, 20)" :alt="locale" class="w-5 h-auto rounded-sm shadow-sm" loading="lazy" />
            <span class="hidden sm:inline">{{ langShortLabels[locale] || locale.toUpperCase() }}</span>
            <span class="material-symbols-outlined text-xs transition-transform" :class="{ 'rotate-180': langOpen }">expand_more</span>
          </button>
          <div v-if="langOpen" class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-50 py-1 animate-in fade-in slide-in-from-top-2 duration-200">
            <div class="px-3 py-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 mb-1">Language</div>
            <NuxtLink
              v-for="loc in locales"
              :key="loc.code"
              :to="switchLocalePath(loc.code)"
              @click="langOpen = false"
              class="w-full flex items-center justify-between px-3 py-2 text-sm hover:bg-primary/5 hover:text-primary transition-colors"
              :class="{ 'bg-primary/5 text-primary font-semibold': locale === loc.code }"
            >
              <span class="flex items-center gap-2.5">
                <img :src="flagSrc(loc.code, 24)" :alt="loc.code" class="w-6 h-auto rounded-sm shadow-sm" loading="lazy" />
                <span class="text-xs font-semibold">{{ loc.name }}</span>
              </span>
              <span v-if="locale === loc.code" class="material-symbols-outlined text-sm text-primary">check</span>
            </NuxtLink>
          </div>
          <div v-if="langOpen" class="fixed inset-0 z-40" @click="langOpen = false" />
        </div>

        <!-- Currency Selector (desktop) -->
        <div class="relative hidden sm:block">
          <button
            @click="currOpen = !currOpen"
            class="flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-black border border-slate-200 rounded-lg hover:border-primary/40 transition-colors text-slate-600"
          >
            <span class="text-xs font-semibold">{{ currencyStore.currentCurrency.symbol }}</span>
            <span>{{ currencyStore.selectedCurrency }}</span>
            <span class="material-symbols-outlined text-xs transition-transform" :class="{ 'rotate-180': currOpen }">expand_more</span>
          </button>
          <div v-if="currOpen" class="absolute right-0 mt-2 w-52 bg-white border border-slate-200 rounded-xl shadow-xl z-50 py-1 animate-in fade-in slide-in-from-top-2 duration-200">
            <div class="px-3 py-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 mb-1">Currency</div>
            <button
              v-for="currency in CURRENCIES"
              :key="currency.code"
              @click="selectCurrency(currency.code)"
              class="w-full flex items-center justify-between px-3 py-2 text-sm hover:bg-primary/5 hover:text-primary transition-colors"
              :class="{ 'bg-primary/5 text-primary font-semibold': currencyStore.selectedCurrency === currency.code }"
            >
              <span class="text-xs">{{ currency.name }}</span>
              <span class="text-[10px] font-mono text-slate-400">{{ currency.symbol }}</span>
            </button>
          </div>
          <div v-if="currOpen" class="fixed inset-0 z-40" @click="currOpen = false" />
        </div>

        <!-- Cart Icon -->
        <NuxtLink :to="localePath('/cart')" class="relative p-2 text-slate-600 hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-xl">shopping_cart</span>
          <span
            v-if="cartStore.itemCount > 0"
            class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[9px] font-black rounded-full min-w-[18px] h-[18px] flex items-center justify-center shadow-sm"
          >
            {{ cartStore.itemCount }}
          </span>
        </NuxtLink>

        <!-- Mobile hamburger -->
        <button
          @click="mobileOpen = !mobileOpen"
          class="md:hidden p-2 text-slate-600 hover:text-primary transition-colors"
          aria-label="Menu"
        >
          <span v-if="!mobileOpen" class="material-symbols-outlined text-xl">menu</span>
          <span v-else class="material-symbols-outlined text-xl">close</span>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <Transition name="slide-down">
      <div v-if="mobileOpen" class="md:hidden bg-white border-t border-slate-100 shadow-lg">
        <div class="px-4 py-4 space-y-1">
          <NuxtLink
            v-for="link in navLinks"
            :key="link.key"
            :to="localePath(link.path)"
            @click="mobileOpen = false"
            class="block px-4 py-3 text-sm font-bold text-slate-700 hover:bg-primary/5 hover:text-primary rounded-xl transition-colors"
          >
            {{ t(link.key) }}
          </NuxtLink>

          <!-- Mobile Language -->
          <div class="pt-3 mt-3 border-t border-slate-100">
            <p class="px-4 text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Language</p>
            <div class="grid grid-cols-3 gap-1.5 px-4">
              <NuxtLink
                v-for="loc in locales"
                :key="loc.code"
                :to="switchLocalePath(loc.code)"
                @click="mobileOpen = false"
                class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg border transition-all"
                :class="locale === loc.code
                  ? 'border-primary bg-primary/5 text-primary font-bold'
                  : 'border-slate-200 text-slate-600 hover:border-primary/40'"
              >
                <img :src="flagSrc(loc.code, 20)" :alt="loc.code" class="w-5 h-auto rounded-sm shadow-sm" loading="lazy" />
                <span class="text-[10px] font-bold">{{ langShortLabels[loc.code] || loc.code.toUpperCase() }}</span>
              </NuxtLink>
            </div>
          </div>

          <!-- Mobile Currency -->
          <div class="pt-3 mt-3 border-t border-slate-100">
            <p class="px-4 text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Currency</p>
            <div class="flex flex-wrap gap-1.5 px-4">
              <button
                v-for="currency in CURRENCIES"
                :key="currency.code"
                @click="selectCurrency(currency.code)"
                :class="currencyStore.selectedCurrency === currency.code
                  ? 'bg-primary text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all"
              >
                {{ currency.symbol }} {{ currency.code }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { countryFlagUrl } from '~/utils/countries'
import { useCartStore } from '~/stores/cart'
import { useCurrencyStore, CURRENCIES } from '~/stores/currency'

const { t, locale, locales } = useI18n()
const localePath = useLocalePath()
const switchLocalePath = useSwitchLocalePath()
const cartStore = useCartStore()
const currencyStore = useCurrencyStore()

const isScrolled = ref(false)
const langOpen = ref(false)
const currOpen = ref(false)
const mobileOpen = ref(false)

// Locale → ISO country code for flagcdn images (emojis don't render on Windows)
const langCountryCode: Record<string, string> = {
  es: 'es', en: 'gb', pt: 'pt', fr: 'fr', de: 'de', it: 'it'
}

const langShortLabels: Record<string, string> = {
  es: 'ESP', en: 'ENG', pt: 'POR', fr: 'FRA', de: 'DEU', it: 'ITA'
}

const flagSrc = (locCode: string, size = 24) => {
  const cc = langCountryCode[locCode] || locCode
  return countryFlagUrl(cc, size)
}

const navLinks = [
  { key: 'tours', path: '/tours' },
  { key: 'about', path: '/about' },
  { key: 'contact', path: '/contact' },
]

function selectCurrency(code: string) {
  currencyStore.setCurrency(code)
  currOpen.value = false
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll)
  currencyStore.initCurrency()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
