<template>
  <!-- Loading State -->
  <div v-if="pending" class="min-h-screen flex items-center justify-center bg-white">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
      <p class="mt-4 text-slate-600">{{ t('loading_tours') }}</p>
    </div>
  </div>

  <!-- Error State -->
  <div v-else-if="error && !tours?.length" class="min-h-screen flex items-center justify-center bg-white">
    <div class="text-center px-4">
      <span class="material-symbols-outlined text-6xl text-slate-400 mb-4">wifi_off</span>
      <h2 class="text-2xl font-bold text-slate-800 mb-2">{{ t('error_loading') }}</h2>
      <p class="text-slate-600 mb-6">{{ t('error_connection') }}</p>
      <button @click="refresh()" class="px-6 py-3 bg-primary text-white rounded-xl hover:bg-primary-600 transition-colors">
        {{ t('retry') }}
      </button>
    </div>
  </div>

  <!-- Main Content -->
  <div v-else class="bg-white font-display text-slate-900 min-h-screen pt-20">

    <!-- Hero Banner -->
    <section class="relative bg-gradient-to-br from-sky-600 via-primary to-blue-900 text-white overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.3\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>
      <div class="relative w-full px-4 sm:px-6 lg:px-10 py-12 md:py-16">
        <div class="max-w-4xl">
          <p class="text-sky-200 font-semibold tracking-wider text-sm uppercase mb-2">{{ t('hero_subtitle') }}</p>
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-black mb-3 leading-tight">
            {{ t('hero_title') }}
          </h1>
          <p class="text-sky-100 text-base md:text-lg max-w-2xl leading-relaxed">
            {{ t('hero_description') }}
          </p>
        </div>
      </div>
    </section>

    <!-- Sticky Search & Filter Bar -->
    <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
      <div class="w-full px-4 sm:px-6 lg:px-10 py-3">
        <div class="flex items-center gap-3">
          <!-- Search -->
          <div class="relative flex-1 max-w-xl">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('search_placeholder')"
              class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border-0 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:bg-white transition-all"
            />
          </div>

          <!-- Filter Toggle (mobile) -->
          <button
            @click="showFilters = !showFilters"
            class="md:hidden flex items-center gap-1.5 px-4 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors"
          >
            <span class="material-symbols-outlined text-lg">tune</span>
            {{ t('filters') }}
            <span v-if="activeFilterCount > 0" class="ml-1 w-5 h-5 bg-primary text-white text-xs flex items-center justify-center rounded-full font-bold">{{ activeFilterCount }}</span>
          </button>

          <!-- Sort (desktop) -->
          <div class="hidden md:flex items-center gap-2">
            <span class="text-sm text-slate-500 font-medium whitespace-nowrap">{{ t('sort_by') }}</span>
            <select
              v-model="sortBy"
              class="bg-slate-100 border-0 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 pr-10 font-semibold py-2.5"
            >
              <option value="featured">{{ t('sort_popularity') }}</option>
              <option value="price_asc">{{ t('sort_price_asc') }}</option>
              <option value="price_desc">{{ t('sort_price_desc') }}</option>
              <option value="rating">{{ t('sort_rating') }}</option>
            </select>
          </div>

          <!-- Language Switcher -->
          <div class="hidden md:flex items-center gap-1.5">
            <NuxtLink
              v-for="loc in availableLocales"
              :key="loc.code"
              :to="switchLocalePath(loc.code)"
              :class="locale === loc.code
                ? 'bg-primary text-white shadow-md shadow-primary/20'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-all uppercase"
            >
              {{ loc.code }}
            </NuxtLink>
          </div>

          <!-- Results Count -->
          <div class="hidden lg:block text-sm text-slate-500 font-medium whitespace-nowrap">
            {{ t('tours_found', { count: filteredTours.length }) }}
          </div>
        </div>
      </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-10 py-6">
      <div class="flex gap-6">

        <!-- Sidebar Filters (desktop) -->
        <aside class="hidden md:block w-64 shrink-0">
          <div class="sticky top-20 space-y-1">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
              <div class="flex items-center justify-between mb-5">
                <h3 class="font-black text-base">{{ t('filters') }}</h3>
                <button
                  v-if="hasActiveFilters"
                  @click="clearFilters"
                  class="text-primary text-xs font-semibold hover:underline"
                >
                  {{ t('clear_all') }}
                </button>
              </div>

              <!-- Duration -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('duration') }}</p>
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="dur in durations"
                    :key="dur.value"
                    @click="selectedDuration = selectedDuration === dur.value ? '' : dur.value"
                    :class="selectedDuration === dur.value ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                  >
                    {{ dur.label }}
                  </button>
                </div>
              </div>

              <!-- Rating -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('rating') }}</p>
                <div class="space-y-2">
                  <label v-for="r in ratings" :key="r.value" class="flex items-center gap-2.5 cursor-pointer group">
                    <input
                      v-model="minRating"
                      :value="r.value"
                      name="rating"
                      type="radio"
                      class="w-4 h-4 text-primary border-slate-300 focus:ring-primary/20"
                    />
                    <div class="flex items-center gap-1">
                      <span v-for="i in r.stars" :key="i" class="material-symbols-outlined text-yellow-400 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
                      <span class="text-xs text-slate-500 group-hover:text-slate-700">{{ r.label }}</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Type -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('experience') }}</p>
                <div class="space-y-2">
                  <label v-for="tp in experienceOptions" :key="tp.value" class="flex items-center gap-2.5 cursor-pointer group">
                    <input
                      v-model="experienceTypes"
                      :value="tp.value"
                      type="checkbox"
                      class="w-4 h-4 rounded text-primary border-slate-300 focus:ring-primary/20"
                    />
                    <span class="text-xs text-slate-600 group-hover:text-slate-800">{{ tp.label }}</span>
                  </label>
                </div>
              </div>

              <!-- Free Cancellation -->
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">{{ t('free_cancellation') }}</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="freeCancellation" class="sr-only peer" type="checkbox" />
                  <div class="w-9 h-5 bg-slate-200 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
              </div>

              <!-- Language Switcher (sidebar) -->
              <div class="mt-6 pt-4 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('filters') }} - Idioma</p>
                <div class="flex flex-wrap gap-1.5">
                  <NuxtLink
                    v-for="loc in availableLocales"
                    :key="loc.code"
                    :to="switchLocalePath(loc.code)"
                    :class="locale === loc.code
                      ? 'bg-primary text-white shadow-md shadow-primary/20'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all uppercase"
                    :title="loc.name"
                  >
                    {{ loc.code }}
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <!-- Mobile Filters Drawer -->
        <Teleport to="body">
          <Transition name="fade">
            <div v-if="showFilters" class="md:hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click="showFilters = false"></div>
          </Transition>
          <Transition name="slide-up">
            <div v-if="showFilters" class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[80vh] overflow-y-auto p-6">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-black">{{ t('filters') }}</h3>
                <button @click="showFilters = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200">
                  <span class="material-symbols-outlined">close</span>
                </button>
              </div>

              <!-- Sort -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('sort_by') }}</p>
                <select v-model="sortBy" class="w-full bg-slate-100 border-0 rounded-xl text-sm py-2.5 font-semibold">
                  <option value="featured">{{ t('sort_popularity') }}</option>
                  <option value="price_asc">{{ t('sort_price_asc') }}</option>
                  <option value="price_desc">{{ t('sort_price_desc') }}</option>
                  <option value="rating">{{ t('sort_rating') }}</option>
                </select>
              </div>

              <!-- Language Switcher (mobile) -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Idioma</p>
                <div class="flex flex-wrap gap-2">
                  <NuxtLink
                    v-for="loc in availableLocales"
                    :key="loc.code"
                    :to="switchLocalePath(loc.code)"
                    @click="showFilters = false"
                    :class="locale === loc.code
                      ? 'bg-primary text-white'
                      : 'bg-slate-100 text-slate-600'"
                    class="px-4 py-2 text-sm font-bold rounded-xl transition-all uppercase"
                  >
                    {{ loc.code }} - {{ loc.name }}
                  </NuxtLink>
                </div>
              </div>

              <!-- Duration -->
              <div class="mb-6">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ t('duration') }}</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="dur in durations"
                    :key="dur.value"
                    @click="selectedDuration = selectedDuration === dur.value ? '' : dur.value"
                    :class="selectedDuration === dur.value ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600'"
                    class="px-4 py-2 text-sm font-semibold rounded-xl transition-all"
                  >
                    {{ dur.label }}
                  </button>
                </div>
              </div>

              <!-- Free Cancellation -->
              <div class="flex items-center justify-between mb-6">
                <span class="text-sm font-semibold">{{ t('free_cancellation') }}</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="freeCancellation" class="sr-only peer" type="checkbox" />
                  <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-primary after:content-[''] after:absolute after:top-[3px] after:left-[3px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
              </div>

              <!-- Actions -->
              <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button @click="clearFilters(); showFilters = false" class="flex-1 py-3 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                  {{ t('clear_all') }}
                </button>
                <button @click="showFilters = false" class="flex-1 py-3 text-sm font-bold text-white bg-primary rounded-xl hover:brightness-110 transition-all shadow-lg shadow-primary/20">
                  {{ t('show_results', { count: filteredTours.length }) }}
                </button>
              </div>
            </div>
          </Transition>
        </Teleport>

        <!-- Results Section -->
        <div class="flex-1 min-w-0">

          <!-- Loading State -->
          <div v-if="pending" class="flex flex-col items-center justify-center py-20">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-primary mb-4"></div>
            <p class="text-slate-500 font-medium">{{ t('loading') }}</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="text-center py-20">
            <span class="material-symbols-outlined text-6xl text-red-300 mb-4 block">error</span>
            <h3 class="text-xl font-bold text-slate-800 mb-2">{{ t('error_generic') }}</h3>
            <p class="text-slate-500 mb-6">{{ t('error_generic_hint') }}</p>
            <button @click="refresh()" class="bg-primary text-white font-bold py-3 px-8 rounded-xl hover:brightness-110 transition-all">
              {{ t('try_again') }}
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredTours.length === 0" class="text-center py-20">
            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">search_off</span>
            <h3 class="text-xl font-bold text-slate-800 mb-2">{{ t('no_tours_found') }}</h3>
            <p class="text-slate-500 mb-6">{{ t('no_tours_hint') }}</p>
            <button @click="clearFilters" class="bg-primary text-white font-bold py-3 px-8 rounded-xl hover:brightness-110 transition-all">
              {{ t('clear_filters') }}
            </button>
          </div>

          <!-- Tour Cards Grid -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <NuxtLink
              v-for="tour in paginatedTours.data"
              :key="tour.id"
              :to="getTourLink(tour)"
              class="group bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:border-slate-200 hover:-translate-y-1 transition-all duration-300"
            >
              <!-- Image -->
              <div class="relative aspect-[4/3] overflow-hidden">
                <img
                  :src="getImageUrl(tour.featured_image || tour.thumbnail)"
                  :alt="tour.title"
                  class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                  loading="lazy"
                />
                <!-- Gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                <!-- Top Badges -->
                <div class="absolute top-3 left-3 flex gap-1.5">
                  <span
                    v-if="tour.difficulty"
                    class="px-2.5 py-1 text-[10px] font-bold rounded-full shadow backdrop-blur-md"
                    :class="{
                      'bg-green-500/90 text-white': tour.difficulty === 'easy',
                      'bg-yellow-500/90 text-white': tour.difficulty === 'moderate',
                      'bg-orange-500/90 text-white': tour.difficulty === 'difficult',
                      'bg-red-500/90 text-white': tour.difficulty === 'hard',
                    }"
                  >
                    {{ tour.difficulty.charAt(0).toUpperCase() + tour.difficulty.slice(1) }}
                  </span>
                </div>

                <!-- Heart -->
                <button
                  class="absolute top-3 right-3 w-8 h-8 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white hover:scale-110 transition-all shadow"
                  @click.prevent
                >
                  <span class="material-symbols-outlined text-slate-500 text-lg">favorite</span>
                </button>

                <!-- Duration pill bottom-left -->
                <div v-if="tour.duration_days || tour.duration_hours" class="absolute bottom-3 left-3 flex items-center gap-1 bg-white/90 backdrop-blur-md text-slate-700 px-2.5 py-1 rounded-full shadow text-[11px] font-bold">
                  <span class="material-symbols-outlined text-sm">schedule</span>
                  {{ formatDuration(tour) }}
                </div>
              </div>

              <!-- Content -->
              <div class="p-4">
                <!-- Location -->
                <div class="flex items-center gap-1 text-[11px] text-slate-400 font-semibold uppercase tracking-wider mb-1.5">
                  <span class="material-symbols-outlined text-xs">location_on</span>
                  {{ tour.city?.name || 'Puno' }}
                </div>

                <!-- Title -->
                <h3 class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">
                  {{ tour.title }}
                </h3>

                <!-- Rating -->
                <div class="flex items-center gap-1 mb-3">
                  <span class="material-symbols-outlined text-yellow-400 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
                  <span class="text-xs font-bold text-slate-700">{{ tour.rating || '4.5' }}</span>
                  <span class="text-[10px] text-slate-400">({{ tour.reviews_count || 0 }})</span>
                </div>

                <!-- Price -->
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
          <div v-if="paginatedTours.data.length > 0" class="flex items-center justify-center gap-2 mt-10 mb-4">
            <button
              @click="handlePageChange(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            >
              <span class="material-symbols-outlined text-lg">chevron_left</span>
            </button>

            <button
              v-for="page in visiblePages"
              :key="page"
              @click="handlePageChange(page)"
              :class="page === currentPage ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all"
            >
              {{ page }}
            </button>

            <button
              @click="handlePageChange(currentPage + 1)"
              :disabled="currentPage >= paginatedTours.lastPage"
              class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            >
              <span class="material-symbols-outlined text-lg">chevron_right</span>
            </button>
          </div>
        </div>
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

// Available locales for the language switcher
const availableLocales = [
  { code: 'es', name: 'Español' },
  { code: 'en', name: 'English' },
  { code: 'pt', name: 'Português' },
  { code: 'fr', name: 'Français' },
  { code: 'de', name: 'Deutsch' },
  { code: 'it', name: 'Italiano' },
]

// Map locale code to API language code (uppercase)
const langCode = computed(() => locale.value.toUpperCase())

// SEO
useHead({
  title: computed(() => `${t('tours')} - Incalake Tours`),
  meta: [
    {
      name: 'description',
      content: computed(() => t('hero_description'))
    }
  ]
})

// UI State
const showFilters = ref(false)

// Options
const durations = [
  { value: 'short', label: '0-4h' },
  { value: 'medium', label: '4-8h' },
  { value: 'long', label: 'Full Day+' },
]

const ratings = computed(() => [
  { value: '4', label: t('and_up'), stars: 4 },
  { value: '3', label: t('and_up'), stars: 3 },
  { value: '', label: t('all'), stars: 0 },
])

const experienceOptions = computed(() => [
  { value: 'culture', label: t('culture_history') },
  { value: 'adventure', label: t('adventure_nature') },
  { value: 'food', label: t('food_gastronomy') },
])

// Filters
const searchQuery = ref('')
const selectedDuration = ref('')
const minRating = ref('')
const experienceTypes = ref<string[]>([])
const freeCancellation = ref(false)
const sortBy = ref('featured')

// Pagination
const currentPage = ref(1)
const perPage = 12

// Fetch tours filtered by current language
const { data: response, pending, error, refresh } = await useAsyncData(
  `tours-${locale.value}`,
  () => api(`/tours?per_page=100&active=1&language=${langCode.value}`),
  { watch: [locale] }
)

const tours = computed(() => {
  if (response.value && response.value.data) {
    if (Array.isArray(response.value.data.data)) {
      return response.value.data.data
    }
    if (Array.isArray(response.value.data)) {
      return response.value.data
    }
  }
  return []
})

// Computed filtered and sorted tours
const filteredTours = computed(() => {
  let result = [...tours.value]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(tour =>
      tour.title?.toLowerCase().includes(query) ||
      tour.short_description?.toLowerCase().includes(query) ||
      tour.city?.name?.toLowerCase().includes(query)
    )
  }

  if (selectedDuration.value) {
    result = result.filter(tour => {
      const hours = tour.duration_hours || 0
      if (selectedDuration.value === 'short') return hours <= 4
      if (selectedDuration.value === 'medium') return hours > 4 && hours <= 8
      if (selectedDuration.value === 'long') return hours > 8
      return true
    })
  }

  if (minRating.value) {
    const rating = parseFloat(minRating.value)
    result = result.filter(tour => (tour.rating || 0) >= rating)
  }

  if (freeCancellation.value) {
    result = result.filter(tour => tour.free_cancellation)
  }

  if (sortBy.value === 'price_asc') {
    result.sort((a, b) => (a.min_price || 0) - (b.min_price || 0))
  } else if (sortBy.value === 'price_desc') {
    result.sort((a, b) => (b.min_price || 0) - (a.min_price || 0))
  } else if (sortBy.value === 'rating') {
    result.sort((a, b) => (b.rating || 0) - (a.rating || 0))
  }

  return result
})

// Paginated tours
const paginatedTours = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const end = start + perPage
  const data = filteredTours.value.slice(start, end)

  return {
    data,
    total: filteredTours.value.length,
    lastPage: Math.ceil(filteredTours.value.length / perPage),
    from: start + 1,
    to: Math.min(end, filteredTours.value.length)
  }
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const last = paginatedTours.value.lastPage
  const current = currentPage.value

  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  return pages
})

const hasActiveFilters = computed(() =>
  searchQuery.value ||
  selectedDuration.value ||
  minRating.value ||
  experienceTypes.value.length > 0 ||
  freeCancellation.value ||
  sortBy.value !== 'featured'
)

const activeFilterCount = computed(() => {
  let count = 0
  if (selectedDuration.value) count++
  if (minRating.value) count++
  if (experienceTypes.value.length > 0) count++
  if (freeCancellation.value) count++
  return count
})

function clearFilters() {
  searchQuery.value = ''
  selectedDuration.value = ''
  minRating.value = ''
  experienceTypes.value = []
  freeCancellation.value = false
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
  const days = tour.duration_days || 0
  const hours = tour.duration_hours || 0

  if (days > 0 && hours > 0) return `${days}d ${hours}h`
  if (days > 0) return `${days} day${days > 1 ? 's' : ''}`
  if (hours > 0) return `${hours}h`
  return 'Flexible'
}

function getTourLink(tour: any) {
  const slug = tour.slug || tour.id
  return localePath(`/tours/${slug}`)
}
</script>

<style scoped>
@reference "../../assets/css/main.css";

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
