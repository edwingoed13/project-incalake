<template>
  <div class="bg-white font-display text-slate-900 min-h-screen">
    <!-- Premium Hero Section -->
    <section class="relative w-full h-[480px] md:h-[620px] flex flex-col items-center justify-center p-4 sm:p-12">
      <div class="absolute inset-0 z-0 overflow-hidden">
        <img
          :src="heroImage"
          class="absolute w-full h-full object-cover"
          alt="Lake Titicaca"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/60"></div>
      </div>

      <div class="relative z-10 text-center max-w-4xl px-4 mb-6 md:mb-8">
        <h2 class="text-white text-3xl sm:text-5xl md:text-6xl font-black leading-[1.1] tracking-tighter mb-3 md:mb-4 drop-shadow-2xl">
          {{ c('hero', 'title', 'home_hero_title') }}
        </h2>
        <p class="hidden sm:block text-white/80 text-lg md:text-xl font-medium max-w-2xl mx-auto">
          {{ c('hero', 'subtitle', 'home_hero_subtitle') }}
        </p>
      </div>

      <!-- Search Bar - simplified on mobile -->
      <div class="relative w-full max-w-3xl px-4 sm:px-6" style="z-index: 60;">
        <div class="bg-white shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] rounded-xl sm:rounded-2xl p-1.5 sm:p-2 flex items-center gap-1.5 sm:gap-2">
          <div class="flex-1 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3">
            <span class="material-symbols-outlined text-primary text-lg sm:text-xl">search</span>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              class="bg-transparent border-none p-0 focus:ring-0 text-xs sm:text-sm font-semibold text-slate-800 placeholder:text-slate-400 w-full outline-none"
              :placeholder="c('search_placeholder', '', 'home_search_placeholder')"
              type="text"
              @input="onSearchInput"
              @keyup.enter="goToTours"
              @focus="onSearchFocus"
              autocomplete="off"
            />
          </div>
          <button
            @click="goToTours"
            class="bg-primary text-white px-4 sm:px-8 py-2.5 sm:py-3.5 rounded-lg sm:rounded-xl font-bold text-sm hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-primary/30 flex items-center gap-2 shrink-0"
          >
            <span class="material-symbols-outlined text-lg">search</span>
            <span class="hidden sm:inline">{{ c('search_btn', '', 'home_search_btn') }}</span>
          </button>
        </div>

        <!-- Dropdown (over everything) -->
        <div
          v-if="showDropdown"
          class="absolute left-6 right-6 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden max-h-[420px] overflow-y-auto"
        >
          <!-- Suggestions: show destinations when input is empty -->
          <template v-if="searchQuery.length < 2">
            <p class="px-5 pt-3 pb-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('home_destinations_label') }}</p>
            <NuxtLink
              v-for="city in featuredCities"
              :key="city.id"
              :to="localePath(`/tours?city=${city.slug}`)"
              @click="showDropdown = false"
              class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/5 transition-colors"
            >
              <div class="size-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-base">location_on</span>
              </div>
              <div>
                <p class="text-sm font-bold text-slate-800">{{ city.name }}</p>
                <p class="text-[10px] text-slate-400 font-medium">{{ city.country_code === 'PE' ? 'Peru' : 'Bolivia' }}</p>
              </div>
            </NuxtLink>
          </template>

          <!-- Search results -->
          <template v-else>
            <!-- Loading -->
            <div v-if="searching" class="px-5 py-4 flex items-center gap-3">
              <span class="material-symbols-outlined text-slate-400 animate-spin text-lg">progress_activity</span>
              <span class="text-sm text-slate-400">Buscando...</span>
            </div>

            <!-- Results -->
            <template v-else-if="searchResults.length > 0">
              <p class="px-5 pt-3 pb-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                {{ searchResults.length }}+ resultados
              </p>
              <NuxtLink
                v-for="result in searchResults"
                :key="result.id"
                :to="getTourLink(result)"
                @click="showDropdown = false; searchQuery = ''"
                class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/5 transition-colors"
              >
                <img
                  v-if="result.featured_image"
                  :src="getImageUrl(result.featured_image)"
                  class="w-10 h-10 rounded-lg object-cover shrink-0"
                />
                <div v-else class="size-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                  <span class="material-symbols-outlined text-slate-300 text-sm">tour</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate">{{ result.title }}</p>
                  <p class="text-[10px] text-slate-400 font-medium">
                    {{ result.city?.name || 'Puno' }}
                    <span v-if="result.min_price"> · ${{ result.min_price }}</span>
                  </p>
                </div>
              </NuxtLink>

              <!-- View all -->
              <button
                @click="goToTours"
                class="w-full px-5 py-3 text-sm font-bold text-primary hover:bg-primary/5 transition-colors border-t border-slate-100 flex items-center justify-center gap-1"
              >
                Ver todos los resultados para "{{ searchQuery }}"
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
              </button>
            </template>

            <!-- No results -->
            <div v-else class="px-5 py-6 text-center">
              <span class="material-symbols-outlined text-slate-300 text-3xl mb-1">search_off</span>
              <p class="text-sm font-semibold text-slate-500">{{ t('no_tours_found') }}</p>
            </div>
          </template>
        </div>

        <!-- Backdrop to close dropdown -->
        <div v-if="showDropdown" class="fixed inset-0 -z-10" @click="showDropdown = false"></div>
      </div>
    </section>

    <!-- Trust Signals - Mobile: horizontal scroll pills / Desktop: full layout -->
    <section class="py-4 md:py-8 relative z-0">
      <!-- Mobile: compact horizontal scroll -->
      <div class="md:hidden overflow-x-auto scrollbar-hide px-4">
        <div class="flex items-center gap-3 w-max">
          <div v-for="(signal, idx) in trustSignals" :key="idx"
            class="flex items-center gap-1.5 px-3 py-2 bg-slate-50 rounded-full shrink-0"
          >
            <span class="material-symbols-outlined text-sm"
              :class="[idx === 0 ? 'text-orange-500' : idx === 1 ? 'text-green-500' : 'text-blue-500']"
            >{{ signal.icon }}</span>
            <span class="text-[10px] font-bold text-slate-700 whitespace-nowrap">{{ signal.title }}</span>
          </div>
          <div class="flex items-center gap-1 px-3 py-2 bg-yellow-50 rounded-full shrink-0">
            <span class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
            <span class="text-[10px] font-black text-slate-700">4.9/5</span>
          </div>
        </div>
      </div>

      <!-- Desktop: full layout -->
      <div class="hidden md:block px-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex items-center justify-between gap-8">
            <div v-for="(signal, idx) in trustSignals" :key="idx" class="flex items-center gap-3 group">
              <div class="size-10 rounded-xl flex items-center justify-center group-hover:-translate-y-1 transition-transform"
                   :class="[idx === 0 ? 'bg-orange-50 text-orange-600' : idx === 1 ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600']">
                <span class="material-symbols-outlined text-2xl font-bold">{{ signal.icon }}</span>
              </div>
              <div>
                <h5 class="text-sm font-bold text-slate-900">{{ signal.title }}</h5>
                <p class="text-xs text-slate-500 font-medium">{{ signal.description }}</p>
              </div>
            </div>
            <div class="flex items-center gap-1.5">
              <span v-for="i in 5" :key="i" class="material-symbols-outlined text-yellow-500 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
              <span class="text-[10px] font-black text-slate-400 ml-1">4.9/5</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Explore by Destination (GetYourGuide style) -->
    <section class="py-2 md:py-16 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-10">
          <h3 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">{{ c('destinations', 'title', 'home_destinations_title') }}</h3>
        </div>

        <div v-if="cities.length" class="grid grid-cols-3 md:grid-cols-6 gap-5">
          <NuxtLink
            v-for="city in featuredCities"
            :key="city.id"
            :to="localePath(`/tours?city=${city.slug}`)"
            class="group flex flex-col items-center gap-3"
          >
            <div class="w-full aspect-square rounded-2xl overflow-hidden shadow-md group-hover:shadow-xl group-hover:-translate-y-1 transition-all duration-300">
              <img
                :src="getCityImage(city.slug)"
                :alt="city.name"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              />
            </div>
            <h5 class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors">{{ city.name }}</h5>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Featured Tours -->
    <section class="py-8 md:py-12 px-4 md:px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-8">
          <div>
            <p class="text-primary font-black uppercase tracking-[0.2em] text-[10px] mb-2">{{ c('featured', 'label', 'home_featured_label') }}</p>
            <h3 class="text-3xl md:text-4xl font-black tracking-tighter text-slate-900">{{ c('featured', 'title', 'home_featured_title') }}</h3>
          </div>
          <NuxtLink :to="localePath('/tours')" class="group flex items-center gap-2 bg-white px-5 py-2.5 rounded-xl shadow-sm border border-slate-100 hover:border-primary/50 transition-all font-bold text-sm">
            {{ c('view_all', '', 'home_view_all') }}
            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform text-lg">arrow_forward</span>
          </NuxtLink>
        </div>

        <div v-if="toursPending" class="flex items-center justify-center py-20">
          <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <NuxtLink
            v-for="tour in tours.slice(0, 4)"
            :key="tour.id"
            :to="getTourLink(tour)"
            class="group bg-white rounded-2xl overflow-hidden border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
          >
            <div class="relative h-52 overflow-hidden">
              <img
                v-if="tour.featured_image"
                :src="getImageUrl(tour.featured_image)"
                :alt="tour.title"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                loading="lazy"
              />
              <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-slate-300 text-4xl">image</span>
              </div>
              <div v-if="tour.difficulty" class="absolute top-3 left-3">
                <span
                  class="px-2.5 py-1 text-[10px] font-bold rounded-full shadow backdrop-blur-md"
                  :class="{
                    'bg-green-500/90 text-white': tour.difficulty === 'easy',
                    'bg-yellow-500/90 text-white': tour.difficulty === 'moderate',
                    'bg-red-500/90 text-white': tour.difficulty === 'hard',
                  }"
                >
                  {{ tour.difficulty }}
                </span>
              </div>
            </div>
            <div class="p-4">
              <div class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-xs">location_on</span>
                {{ tour.city?.name || 'Puno' }}
              </div>
              <h4 class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">{{ tour.title }}</h4>
              <div class="flex items-end justify-between pt-3 border-t border-slate-100">
                <div>
                  <span class="text-[10px] text-slate-400 font-medium block">{{ t('from') }}</span>
                  <span class="text-lg font-black text-primary">${{ (tour.min_price || 0).toFixed(0) }}</span>
                </div>
                <span class="text-xs font-bold text-primary opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                  {{ t('view') }}
                  <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </span>
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Our Offers (only if 4+ tours have active offers) -->
    <section v-if="toursWithOffers.length >= 4" class="py-8 md:py-12 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-8">
          <div>
            <p class="text-green-600 font-black uppercase tracking-[0.2em] text-[10px] mb-2">
              <span class="material-symbols-outlined text-xs align-middle">local_offer</span>
              Special Deals
            </p>
            <h3 class="text-3xl md:text-4xl font-black tracking-tighter text-slate-900">Our Offers</h3>
          </div>
          <NuxtLink :to="localePath('/tours')" class="group flex items-center gap-2 bg-white px-5 py-2.5 rounded-xl shadow-sm border border-slate-100 hover:border-green-500/50 transition-all font-bold text-sm text-green-700">
            {{ c('view_all', '', 'home_view_all') }}
            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform text-lg">arrow_forward</span>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <NuxtLink
            v-for="tour in toursWithOffers.slice(0, 4)"
            :key="tour.id"
            :to="getTourLink(tour)"
            class="group bg-white rounded-2xl overflow-hidden border border-green-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative"
          >
            <!-- Offer badge -->
            <div class="absolute top-3 right-3 z-10 px-2.5 py-1 bg-green-500 text-white text-[10px] font-black rounded-full shadow-lg flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">local_offer</span>
              {{ getOfferLabel(tour) }}
            </div>
            <div class="relative h-52 overflow-hidden">
              <img
                v-if="tour.featured_image"
                :src="getImageUrl(tour.featured_image)"
                :alt="tour.title"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                loading="lazy"
              />
              <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-slate-300 text-4xl">image</span>
              </div>
            </div>
            <div class="p-4">
              <div class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-xs">location_on</span>
                {{ tour.city?.name || 'Puno' }}
              </div>
              <h4 class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-green-600 transition-colors leading-snug">{{ tour.title }}</h4>
              <div class="flex items-end justify-between pt-3 border-t border-green-100">
                <div>
                  <span class="text-[10px] text-slate-400 font-medium block">{{ t('from') }}</span>
                  <span class="text-lg font-black text-green-600">${{ (tour.min_price || 0).toFixed(0) }}</span>
                </div>
                <span class="text-xs font-bold text-green-600 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                  {{ t('view') }}
                  <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </span>
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Testimonials Slider -->
    <section v-if="featuredReviews.length > 0" class="py-8 md:py-12 px-4 md:px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-10">
          <div>
            <p class="text-primary font-black uppercase tracking-[0.2em] text-[10px] mb-2">Testimonials</p>
            <h3 class="text-2xl md:text-3xl font-black tracking-tighter text-slate-900">What our travelers say</h3>
          </div>
          <div class="flex gap-2">
            <button @click="slideReviews(-1)" class="size-10 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all">
              <span class="material-symbols-outlined text-lg">chevron_left</span>
            </button>
            <button @click="slideReviews(1)" class="size-10 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all">
              <span class="material-symbols-outlined text-lg">chevron_right</span>
            </button>
          </div>
        </div>

        <div class="overflow-hidden">
          <div
            class="flex gap-6 transition-transform duration-500 ease-out"
            :style="{ transform: `translateX(-${reviewSlide * (100 / 3)}%)` }"
          >
            <div
              v-for="review in featuredReviews"
              :key="review.id"
              class="bg-white border border-slate-100 rounded-2xl p-6 hover:shadow-lg transition-shadow shrink-0 w-full md:w-[calc(33.333%-16px)]"
            >
              <div class="flex items-center gap-0.5 mb-3">
                <span v-for="i in review.rating" :key="i" class="material-symbols-outlined text-yellow-400 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
              </div>
              <p v-if="review.title" class="text-sm font-bold text-slate-800 mb-2 line-clamp-1">{{ review.title }}</p>
              <p class="text-xs text-slate-500 leading-relaxed line-clamp-4 mb-4">{{ review.comment }}</p>
              <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                  <p class="text-xs font-bold text-slate-700">{{ review.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ review.review_date }}</p>
                </div>
                <NuxtLink
                  v-if="review.tour_id && review.tour?.translations?.length"
                  :to="localePath(`/${review.tour.city?.slug || 'puno'}/${review.tour.translations[0].slug}`)"
                  class="text-[9px] text-primary font-semibold truncate max-w-[140px] hover:underline flex items-center gap-0.5"
                >
                  {{ review.opinion || review.tour.translations[0].h1_title }}
                  <span class="material-symbols-outlined text-[10px]">arrow_forward</span>
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Dots -->
        <div class="flex items-center justify-center gap-1.5 mt-6">
          <button
            v-for="i in maxSlide + 1"
            :key="i"
            @click="reviewSlide = i - 1"
            class="size-2 rounded-full transition-all"
            :class="reviewSlide === i - 1 ? 'bg-primary w-6' : 'bg-slate-300 hover:bg-slate-400'"
          ></button>
        </div>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-8 md:py-12 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <h3 class="text-3xl md:text-4xl font-black tracking-tighter text-slate-900 text-center mb-10">{{ c('why_title', '', 'home_why_title') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div v-for="(item, idx) in whyUsItems" :key="idx" class="flex flex-col items-center text-center">
            <div class="size-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center mb-6 shadow-xl">
              <span class="material-symbols-outlined text-3xl">{{ item.icon }}</span>
            </div>
            <h4 class="text-lg font-black mb-3">{{ item.title }}</h4>
            <p class="text-slate-500 font-medium text-sm leading-relaxed">{{ item.description }}</p>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
const { api } = useApi()
const config = useRuntimeConfig()
const { t, locale } = useI18n()
const localePath = useLocalePath()

const searchQuery = ref('')
const searchResults = ref<any[]>([])
const showDropdown = ref(false)
const searching = ref(false)
const searchInputRef = ref<HTMLInputElement | null>(null)
let searchTimer: any = null
const langCode = computed(() => locale.value.toUpperCase())

// Fetch page content from API (dynamic, admin-editable)
const pageContent = ref<any>(null)

async function fetchPageContent() {
  try {
    const res = await api(`/pages/home?language=${langCode.value}`)
    pageContent.value = (res as any)?.data?.content || null
  } catch (e) {
    pageContent.value = null
  }
}

await fetchPageContent()
watch(langCode, () => fetchPageContent())

// Reactive helper: get content from API or fall back to i18n
// Returns a getter function that Vue's template will track reactively
function c(section: string, field: string, fallbackKey: string): string {
  const content = pageContent.value
  if (content) {
    let val: any
    if (field) {
      val = content[section]?.[field]
    } else {
      val = content[section]
    }
    if (val && typeof val === 'string') return val
  }
  return t(fallbackKey)
}

// SEO
useHead({
  title: computed(() => `${c('hero', 'title', 'home_hero_title')} - Incalake Tours`),
})

// Fetch cities (lazy - doesn't block home page render)
const { data: citiesResponse } = await useAsyncData(
  'cities',
  () => api('/cities'),
  { lazy: true, default: () => ({ data: [] }) }
)
const cities = computed(() => citiesResponse.value?.data || [])

// Show only 5 main destinations: Puno first, then Cusco, Arequipa, Copacabana, Uyuni
const featuredSlugs = ['puno', 'cusco', 'arequipa', 'la-paz', 'copacabana', 'uyuni']
const featuredCities = computed(() => {
  return featuredSlugs
    .map(slug => cities.value.find((c: any) => c.slug === slug))
    .filter(Boolean)
})

// Fetch featured tours by current language
const tours = ref<any[]>([])
const toursPending = ref(false)

async function fetchTours() {
  toursPending.value = true
  try {
    const res = await api(`/tours?active=1&per_page=8&language=${langCode.value}`)
    const data = (res as any)?.data
    tours.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
  } catch (e) {
    tours.value = []
  } finally {
    toursPending.value = false
  }
}

await fetchTours()
watch(langCode, () => fetchTours())

// Tours with active offers
const toursWithOffers = ref<any[]>([])

async function fetchToursWithOffers() {
  try {
    const res = await api(`/tours?active=1&per_page=100&language=${langCode.value}`)
    const data = (res as any)?.data
    const allTours = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    const today = new Date().toISOString().split('T')[0]
    toursWithOffers.value = allTours.filter((tour: any) => {
      const offers = tour.availability_data?.offers || []
      return offers.some((o: any) => o.endDate >= today)
    })
  } catch (e) { toursWithOffers.value = [] }
}

await fetchToursWithOffers()
watch(langCode, () => fetchToursWithOffers())

function getOfferLabel(tour: any) {
  const today = new Date().toISOString().split('T')[0]
  const offers = tour.availability_data?.offers || []
  const active = offers.find((o: any) => o.endDate >= today)
  if (!active) return ''
  return active.discountType === 'percentage' ? `${active.discount}% OFF` : `$${active.discount} OFF`
}

// Fetch featured reviews
const featuredReviews = ref<any[]>([])
const reviewSlide = ref(0)
const maxSlide = computed(() => Math.max(0, Math.ceil(featuredReviews.value.length / 3) - 1))

function slideReviews(dir: number) {
  reviewSlide.value = Math.max(0, Math.min(maxSlide.value, reviewSlide.value + dir))
}

async function fetchFeaturedReviews() {
  try {
    const res = await api(`/reviews?featured=1&per_page=9&language=${locale.value}`)
    featuredReviews.value = (res as any)?.data || []
    if (featuredReviews.value.length < 3) {
      const res2 = await api(`/reviews?featured=1&per_page=9`)
      featuredReviews.value = (res2 as any)?.data || []
    }
  } catch (e) { featuredReviews.value = [] }
}
await fetchFeaturedReviews()

// Hero image: from API or default
const defaultHeroImage = 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_RYQ7qkkoEaBPmoTKZzaG0YqRCjHegCR7RyERPQkd1TtLTQg9RBjbabWhebnRMrUB20ewsrsBPSVd6DSHmHht2CDGuVapyxM2-QivgVXECSdMWlVIrUHpRWi-kYXNgGWzL5n8LrG0LDy65HR5hOFM_toPA7xM8lnDtR4JFasVk-50uf1v5cmyZfqOvKFkinf3_DBwZiEeJp-2fgM5W72REPm0RxDXSlTGjmg4V1Jfto_VIJ4AUc9TPFiZlRzbS-VIy24MMT2dYVq1'
const heroImage = computed(() => pageContent.value?.hero?.image || defaultHeroImage)

// Dynamic content from API with i18n fallback
const trustSignals = computed(() => {
  if (pageContent.value?.trust_signals?.length) return pageContent.value.trust_signals
  return [
    { icon: 'cancel', title: t('home_trust_cancellation'), description: t('home_trust_cancellation_desc') },
    { icon: 'verified_user', title: t('home_trust_guides'), description: t('home_trust_guides_desc') },
    { icon: 'security', title: t('home_trust_payments'), description: t('home_trust_payments_desc') },
  ]
})

const whyUsItems = computed(() => {
  if (pageContent.value?.why_us?.length) return pageContent.value.why_us
  return [
    { icon: 'public', title: t('home_why_1_title'), description: t('home_why_1_desc') },
    { icon: 'star', title: t('home_why_2_title'), description: t('home_why_2_desc') },
    { icon: 'verified', title: t('home_why_3_title'), description: t('home_why_3_desc') },
  ]
})

// City placeholder images (will be replaced with real images from admin)
const cityImages: Record<string, string> = {
  'puno': 'https://images.unsplash.com/photo-1580619305218-8423a7ef79b4?w=800&q=80',
  'cusco': 'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=800&q=80',
  'arequipa': 'https://images.unsplash.com/photo-1580975556080-e1a7e28e4b36?w=800&q=80',
  'lima': 'https://images.unsplash.com/photo-1531968455001-5c5272a67c71?w=800&q=80',
  'la-paz': 'https://images.unsplash.com/photo-1591543620767-582b2e76369e?w=800&q=80',
  'uyuni': 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=800&q=80',
  'copacabana': 'https://images.unsplash.com/photo-1580619305218-8423a7ef79b4?w=800&q=80',
  'juliaca': 'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=800&q=80',
}

function getCityImage(slug: string) {
  return cityImages[slug] || 'https://images.unsplash.com/photo-1580619305218-8423a7ef79b4?w=800&q=80'
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

function getTourLink(tour: any) {
  const slug = tour.slug || tour.id
  return localePath(`/tours/${slug}`)
}

function onSearchFocus() {
  showDropdown.value = true
}

function onSearchInput() {
  showDropdown.value = true
  clearTimeout(searchTimer)
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    searching.value = false
    return
  }
  searching.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await api(`/tours?search=${encodeURIComponent(searchQuery.value)}&language=${langCode.value}&active=1&per_page=8`)
      const data = (res as any)?.data
      searchResults.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    } catch (e) {
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }, 100)
}

function goToTours() {
  showDropdown.value = false
  if (searchQuery.value) {
    navigateTo(localePath(`/tours?search=${encodeURIComponent(searchQuery.value)}`))
  } else {
    navigateTo(localePath('/tours'))
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.search-overlay-enter-active,
.search-overlay-leave-active {
  transition: opacity 0.2s ease;
}
.search-overlay-enter-from,
.search-overlay-leave-to {
  opacity: 0;
}
</style>
