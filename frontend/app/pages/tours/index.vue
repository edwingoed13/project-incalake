<template>
  <!-- Loading -->
  <div v-if="pending" class="min-h-screen flex items-center justify-center bg-white pt-20">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
      <p class="mt-4 text-slate-500 text-sm">{{ t('loading_tours') }}</p>
    </div>
  </div>

  <!-- Error -->
  <div v-else-if="error && !tours?.length" class="min-h-screen flex items-center justify-center bg-white pt-20">
    <div class="text-center px-4">
      <span class="material-symbols-outlined text-5xl text-slate-300 mb-4">wifi_off</span>
      <h2 class="text-xl font-bold text-slate-800 mb-2">{{ t('error_loading') }}</h2>
      <button @click="refresh()" class="px-6 py-2.5 bg-primary text-white rounded-xl font-bold text-sm">{{ t('retry') }}</button>
    </div>
  </div>

  <!-- Main -->
  <div v-else class="bg-white font-display text-slate-900 min-h-screen pt-20">

    <!-- Compact Hero -->
    <section class="bg-gradient-to-r from-primary to-sky-600 text-white px-4 sm:px-6 lg:px-10 py-8">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl md:text-3xl font-black mb-1">
          {{ selectedCitySlug ? `Tours ${formatCityName(selectedCitySlug)}` : t('hero_title') }}
        </h1>
        <p class="text-white/70 text-sm">{{ filteredTours.length }} {{ filteredTours.length === 1 ? 'tour' : 'tours' }} {{ t('tours_found', { count: '' }).replace(/ $/, '') }}</p>
      </div>
    </section>

    <!-- Sticky Filter Bar (Viator style) -->
    <div class="sticky top-[68px] z-30 bg-white border-b border-slate-200 shadow-sm overflow-visible">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-2.5 overflow-visible">
        <div class="flex items-center gap-2 flex-wrap md:flex-nowrap">

          <!-- Search pill -->
          <div class="relative shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('search_placeholder')"
              class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-xs font-medium focus:ring-2 focus:ring-primary/30 focus:border-primary w-48 focus:w-64 transition-all"
            />
          </div>

          <!-- Divider -->
          <div class="w-px h-6 bg-slate-200 shrink-0"></div>

          <!-- Destination pill -->
          <div class="relative shrink-0">
            <button
              @click="openFilter = openFilter === 'city' ? '' : 'city'"
              :class="selectedCitySlug ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
              class="flex items-center gap-1.5 px-3.5 py-2 border rounded-full text-xs font-semibold transition-all"
            >
              <span class="material-symbols-outlined text-sm">location_on</span>
              {{ selectedCitySlug ? formatCityName(selectedCitySlug) : 'Destination' }}
              <span class="material-symbols-outlined text-xs">expand_more</span>
            </button>
            <!-- Dropdown -->
            <div v-if="openFilter === 'city'" class="absolute top-full left-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl z-[40] py-1">
              <button @click="selectedCitySlug = ''; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5 transition-colors" :class="!selectedCitySlug ? 'text-primary' : 'text-slate-600'">
                All Destinations
              </button>
              <button
                v-for="city in cities"
                :key="city.slug"
                @click="selectedCitySlug = city.slug; openFilter = ''"
                class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5 transition-colors"
                :class="selectedCitySlug === city.slug ? 'text-primary bg-primary/5' : 'text-slate-600'"
              >
                {{ city.name }} <span class="text-slate-400">({{ city.count }})</span>
              </button>
            </div>
          </div>

          <!-- Duration pill -->
          <div class="relative shrink-0">
            <button
              @click="openFilter = openFilter === 'duration' ? '' : 'duration'"
              :class="selectedDuration ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
              class="flex items-center gap-1.5 px-3.5 py-2 border rounded-full text-xs font-semibold transition-all"
            >
              <span class="material-symbols-outlined text-sm">schedule</span>
              {{ selectedDuration ? durationLabels[selectedDuration] : t('duration') }}
              <span class="material-symbols-outlined text-xs">expand_more</span>
            </button>
            <div v-if="openFilter === 'duration'" class="absolute top-full left-0 mt-2 w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-[40] py-1">
              <button @click="selectedDuration = ''; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="!selectedDuration ? 'text-primary' : 'text-slate-600'">All</button>
              <button @click="selectedDuration = 'half'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedDuration === 'half' ? 'text-primary bg-primary/5' : 'text-slate-600'">Half Day (1-4h)</button>
              <button @click="selectedDuration = 'full'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedDuration === 'full' ? 'text-primary bg-primary/5' : 'text-slate-600'">Full Day (5-12h)</button>
              <button @click="selectedDuration = 'multi'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedDuration === 'multi' ? 'text-primary bg-primary/5' : 'text-slate-600'">Multi-Day</button>
            </div>
          </div>

          <!-- Price pill -->
          <div class="relative shrink-0">
            <button
              @click="openFilter = openFilter === 'price' ? '' : 'price'"
              :class="selectedPrice ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
              class="flex items-center gap-1.5 px-3.5 py-2 border rounded-full text-xs font-semibold transition-all"
            >
              <span class="material-symbols-outlined text-sm">payments</span>
              {{ selectedPrice ? priceLabels[selectedPrice] : 'Price' }}
              <span class="material-symbols-outlined text-xs">expand_more</span>
            </button>
            <div v-if="openFilter === 'price'" class="absolute top-full left-0 mt-2 w-40 bg-white border border-slate-200 rounded-xl shadow-xl z-[40] py-1">
              <button @click="selectedPrice = ''; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="!selectedPrice ? 'text-primary' : 'text-slate-600'">All Prices</button>
              <button @click="selectedPrice = 'budget'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedPrice === 'budget' ? 'text-primary bg-primary/5' : 'text-slate-600'">Under $50</button>
              <button @click="selectedPrice = 'mid'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedPrice === 'mid' ? 'text-primary bg-primary/5' : 'text-slate-600'">$50 - $99</button>
              <button @click="selectedPrice = 'premium'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedPrice === 'premium' ? 'text-primary bg-primary/5' : 'text-slate-600'">$100 - $199</button>
              <button @click="selectedPrice = 'luxury'; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="selectedPrice === 'luxury' ? 'text-primary bg-primary/5' : 'text-slate-600'">$200+</button>
            </div>
          </div>

          <!-- Sort pill -->
          <div class="relative shrink-0">
            <button
              @click="openFilter = openFilter === 'sort' ? '' : 'sort'"
              class="flex items-center gap-1.5 px-3.5 py-2 bg-white text-slate-700 border border-slate-200 hover:border-slate-300 rounded-full text-xs font-semibold transition-all"
            >
              <span class="material-symbols-outlined text-sm">sort</span>
              {{ sortLabels[sortBy] || 'Sort' }}
              <span class="material-symbols-outlined text-xs">expand_more</span>
            </button>
            <div v-if="openFilter === 'sort'" class="absolute top-full right-0 mt-2 w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-[40] py-1">
              <button v-for="(label, key) in sortLabels" :key="key" @click="sortBy = key; openFilter = ''" class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5" :class="sortBy === key ? 'text-primary bg-primary/5' : 'text-slate-600'">{{ label }}</button>
            </div>
          </div>

          <!-- Spacer -->
          <div class="flex-1"></div>

          <!-- Clear all -->
          <button
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="shrink-0 text-xs font-semibold text-red-500 hover:text-red-600 flex items-center gap-1 transition-colors"
          >
            <span class="material-symbols-outlined text-sm">close</span>
            Clear
          </button>

          <!-- Results count -->
          <span class="shrink-0 text-xs font-bold text-slate-400 hidden md:block">{{ filteredTours.length }} results</span>
        </div>
      </div>
      <!-- Backdrop to close dropdowns (inside sticky so z-index works) -->
      <div v-if="openFilter" class="fixed inset-0" @click="openFilter = ''"></div>
    </div>


    <!-- Tour Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-6">

      <!-- Active filters badges -->
      <div v-if="selectedCitySlug || selectedDuration || selectedPrice" class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-[10px] font-bold text-slate-400 uppercase">Active:</span>
        <span v-if="selectedCitySlug" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
          {{ formatCityName(selectedCitySlug) }}
          <button @click="selectedCitySlug = ''" class="material-symbols-outlined text-xs hover:text-red-500">close</button>
        </span>
        <span v-if="selectedDuration" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
          {{ durationLabels[selectedDuration] }}
          <button @click="selectedDuration = ''" class="material-symbols-outlined text-xs hover:text-red-500">close</button>
        </span>
        <span v-if="selectedPrice" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
          {{ priceLabels[selectedPrice] }}
          <button @click="selectedPrice = ''" class="material-symbols-outlined text-xs hover:text-red-500">close</button>
        </span>
      </div>

      <!-- Empty -->
      <div v-if="filteredTours.length === 0" class="text-center py-20">
        <span class="material-symbols-outlined text-5xl text-slate-300 mb-4">search_off</span>
        <h3 class="text-lg font-bold text-slate-800 mb-2">{{ t('no_tours_found') }}</h3>
        <p class="text-sm text-slate-500 mb-6">{{ t('no_tours_hint') }}</p>
        <button @click="clearFilters" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl text-sm">{{ t('clear_filters') }}</button>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <NuxtLink
          v-for="tour in paginatedTours.data"
          :key="tour.id"
          :to="getTourLink(tour)"
          class="group bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
        >
          <div class="relative aspect-[4/3] overflow-hidden">
            <img
              :src="getImageUrl(tour.featured_image || tour.thumbnail)"
              :alt="tour.title"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
              loading="lazy"
            />
            <div v-if="tour.duration_days || tour.duration_hours" class="absolute bottom-3 left-3 flex items-center gap-1 bg-white/90 backdrop-blur-md text-slate-700 px-2.5 py-1 rounded-full shadow text-[11px] font-bold">
              <span class="material-symbols-outlined text-sm">schedule</span>
              {{ formatDuration(tour) }}
            </div>
            <!-- Offer badge -->
            <div v-if="hasActiveOffer(tour)" class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-[10px] font-bold rounded-full shadow flex items-center gap-0.5">
              <span class="material-symbols-outlined text-xs">local_offer</span>
              {{ getOfferLabel(tour) }}
            </div>
          </div>
          <div class="p-4">
            <div class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
              <span class="material-symbols-outlined text-xs">location_on</span>
              {{ tour.city?.name || 'Puno' }}
            </div>
            <h3 class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">{{ tour.title }}</h3>
            <div class="flex items-end justify-between pt-3 border-t border-slate-100">
              <div>
                <span class="text-[10px] text-slate-400 font-medium block">{{ t('from') }}</span>
                <span class="text-lg font-black text-primary">${{ (tour.min_price || 0).toFixed(0) }}</span>
                <span class="text-[10px] text-slate-400 ml-0.5">{{ t('per_person') }}</span>
              </div>
              <span class="text-xs font-bold text-primary opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                {{ t('view') }}
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
              </span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Pagination -->
      <div v-if="paginatedTours.lastPage > 1" class="flex items-center justify-center gap-2 mt-10 mb-4">
        <button
          @click="handlePageChange(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors disabled:opacity-30"
        >
          <span class="material-symbols-outlined text-lg">chevron_left</span>
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="handlePageChange(page)"
          :class="page === currentPage ? 'bg-primary text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all"
        >
          {{ page }}
        </button>
        <button
          @click="handlePageChange(currentPage + 1)"
          :disabled="currentPage >= paginatedTours.lastPage"
          class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors disabled:opacity-30"
        >
          <span class="material-symbols-outlined text-lg">chevron_right</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { api } = useApi()
const config = useRuntimeConfig()
const { t, locale } = useI18n()
const switchLocalePath = useSwitchLocalePath()
const localePath = useLocalePath()

const langCode = computed(() => locale.value.toUpperCase())

useHead({
  title: computed(() => `${t('tours')} - Incalake Tours`),
})

// Filter state
const route = useRoute()
const openFilter = ref('')
const searchQuery = ref((route.query.search as string) || '')
const selectedCitySlug = ref((route.query.city as string) || '')
const selectedDuration = ref('')
const selectedPrice = ref('')
const sortBy = ref('featured')
const currentPage = ref(1)
const perPage = 12

const durationLabels: Record<string, string> = { half: 'Half Day (1-4h)', full: 'Full Day (5-12h)', multi: 'Multi-Day' }
const priceLabels: Record<string, string> = { budget: 'Under $50', mid: '$50-$99', premium: '$100-$199', luxury: '$200+' }
const sortLabels: Record<string, string> = { featured: 'Recommended', price_asc: 'Price: Low → High', price_desc: 'Price: High → Low' }

// Cities for destination filter
const featuredSlugs = ['puno','cusco','arequipa','la-paz','uyuni','copacabana']
const cities = ref<any[]>([])
const allToursForCount = ref<any[]>([])

async function fetchCitiesWithCounts() {
  try {
    // Fetch cities
    const cityRes = await api('/cities')
    const allCities = ((cityRes as any)?.data || []).filter((c: any) => featuredSlugs.includes(c.slug))

    // Fetch ALL tours (no city filter) to count per city
    const toursRes = await api(`/tours?per_page=500&active=1&language=${langCode.value}`)
    const data = (toursRes as any)?.data
    allToursForCount.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])

    // Count tours per city
    cities.value = allCities.map((c: any) => {
      const count = allToursForCount.value.filter((t: any) => {
        const cId = t.city?.id || t.city_id
        return cId === c.id
      }).length
      return { ...c, count }
    }).filter((c: any) => c.count > 0)
  } catch (e) { cities.value = [] }
}
await fetchCitiesWithCounts()

// Fetch tours
const tours = ref<any[]>([])
const pending = ref(false)
const error = ref<any>(null)

async function fetchTours() {
  pending.value = true
  error.value = null
  try {
    let url = `/tours?per_page=500&active=1&language=${langCode.value}`
    if (selectedCitySlug.value) url += `&city_slug=${selectedCitySlug.value}`
    const res = await api(url)
    const data = (res as any)?.data
    tours.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
  } catch (e: any) {
    error.value = e
    tours.value = []
  } finally {
    pending.value = false
  }
}

await fetchTours()
watch([langCode, selectedCitySlug], () => {
  fetchTours()
  fetchCitiesWithCounts()
})

function refresh() { fetchTours() }

// Client-side filters
const filteredTours = computed(() => {
  let result = [...tours.value]

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(tour =>
      tour.title?.toLowerCase().includes(q) ||
      tour.short_description?.toLowerCase().includes(q) ||
      tour.city?.name?.toLowerCase().includes(q)
    )
  }

  if (selectedDuration.value) {
    result = result.filter(tour => {
      const h = tour.duration_hours || 0
      const d = tour.duration_days || 0
      // Multi-day takes priority
      if (d > 0) return selectedDuration.value === 'multi'
      // Hours only
      if (selectedDuration.value === 'half') return h > 0 && h <= 4
      if (selectedDuration.value === 'full') return h > 4
      if (selectedDuration.value === 'multi') return false
      return true
    })
  }

  if (selectedPrice.value) {
    result = result.filter(tour => {
      const p = tour.min_price || 0
      if (selectedPrice.value === 'budget') return p < 50
      if (selectedPrice.value === 'mid') return p >= 50 && p < 100
      if (selectedPrice.value === 'premium') return p >= 100 && p < 200
      if (selectedPrice.value === 'luxury') return p >= 200
      return true
    })
  }

  if (sortBy.value === 'price_asc') result.sort((a, b) => (a.min_price || 0) - (b.min_price || 0))
  else if (sortBy.value === 'price_desc') result.sort((a, b) => (b.min_price || 0) - (a.min_price || 0))

  return result
})

const paginatedTours = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const data = filteredTours.value.slice(start, start + perPage)
  return { data, total: filteredTours.value.length, lastPage: Math.ceil(filteredTours.value.length / perPage) }
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const c = currentPage.value
  const l = paginatedTours.value.lastPage
  for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) pages.push(i)
  return pages
})

const hasActiveFilters = computed(() => searchQuery.value || selectedCitySlug.value || selectedDuration.value || selectedPrice.value || sortBy.value !== 'featured')

const activeFilterCount = computed(() => {
  let c = 0
  if (selectedCitySlug.value) c++
  if (selectedDuration.value) c++
  if (selectedPrice.value) c++
  return c
})

// Reset page when filters change
watch([searchQuery, selectedDuration, selectedPrice, sortBy, selectedCitySlug], () => {
  currentPage.value = 1
})

function clearFilters() {
  searchQuery.value = ''
  selectedCitySlug.value = ''
  selectedDuration.value = ''
  selectedPrice.value = ''
  sortBy.value = 'featured'
  currentPage.value = 1
}

function handlePageChange(page: number) {
  if (page < 1 || page > paginatedTours.value.lastPage) return
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

function formatDuration(tour: any) {
  const d = tour.duration_days || 0
  const h = tour.duration_hours || 0
  if (d > 0 && h > 0) return `${d}d ${h}h`
  if (d > 0) return `${d} day${d > 1 ? 's' : ''}`
  if (h > 0) return `${h}h`
  return ''
}

function formatCityName(slug: string) {
  return slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function getTourLink(tour: any) {
  return localePath(`/tours/${tour.slug || tour.id}`)
}

function hasActiveOffer(tour: any) {
  const today = new Date().toISOString().split('T')[0]
  return (tour.availability_data?.offers || []).some((o: any) => o.endDate >= today)
}

function getOfferLabel(tour: any) {
  const today = new Date().toISOString().split('T')[0]
  const offer = (tour.availability_data?.offers || []).find((o: any) => o.endDate >= today)
  if (!offer) return ''
  return offer.discountType === 'percentage' ? `${offer.discount}% OFF` : `$${offer.discount} OFF`
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
