<template>
  <div v-if="tour" class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-8">

      <!-- Title & Basic Info -->
      <div class="flex flex-col lg:flex-row justify-between gap-6 mb-8">
        <div class="flex-1">
          <h1 class="text-2xl md:text-3xl font-black mb-3 leading-tight">{{ tour.title }}</h1>
          <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
            <!-- Rating -->
            <div class="flex items-center gap-1">
              <span class="material-symbols-outlined text-yellow-500 fill-1 text-base">star</span>
              <span>{{ tourReviews.length > 0 ? avgRating : '—' }}</span>
              <span class="text-slate-500 underline cursor-pointer hover:text-slate-700">({{ tourReviews.length }} {{ t('reviews') }})</span>
            </div>
            <span class="text-slate-300">•</span>
            <!-- Location -->
            <div class="flex items-center gap-1">
              <span class="material-symbols-outlined text-slate-500 text-base">location_on</span>
              <span>{{ tour.city?.name || 'Puno' }}, Peru</span>
            </div>
            <span class="text-slate-300">•</span>
            <!-- Duration -->
            <div class="flex items-center gap-1">
              <span class="material-symbols-outlined text-slate-500 text-base">schedule</span>
              <span>{{ formatDuration(tour) }}</span>
            </div>
          </div>
        </div>
        <div class="flex gap-2 items-start">
          <button
            class="flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 dark:hover:bg-slate-700 transition-colors"
            aria-label="Share tour"
          >
            <span class="material-symbols-outlined text-lg">share</span>
            <span class="hidden sm:inline">Share</span>
          </button>
          <button
            class="flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 dark:hover:bg-slate-700 transition-colors"
            aria-label="Save to favorites"
          >
            <span class="material-symbols-outlined text-lg">favorite</span>
            <span class="hidden sm:inline">Save</span>
          </button>
        </div>
      </div>

      <!-- Two Column Layout: Left Content | Right Booking Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-8">
        <!-- Left Column: Multimedia + Content -->
        <div class="space-y-10">
          <!-- Multimedia Gallery -->
          <TourMediaGallery :tour="tour" />

          <!-- Content Sections -->
          <!-- Tour Description -->
          <TourDescription v-if="tour.long_description || tour.description" :tour="tour" />

          <!-- Tour Itinerary -->
          <TourItinerary v-if="tour.itinerary" :tour="tour" />

          <!-- What's Included / Not Included -->
          <TourIncludes v-if="tour.what_includes || tour.what_not_includes" :tour="tour" />

          <!-- Important Information / Recommendations -->
          <TourRecommendations :tour="tour" />

          <!-- Cancellation Policies -->
          <TourPolicies v-if="tour.cancellation_policy || tour.policies" :tour="tour" />

          <!-- Location Map -->
          <!-- Location Map -->
          <TourLocation :tour="tour" />

          <hr class="border-slate-200 dark:border-slate-800" />

          <!-- Reviews Section -->
          <section>
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold">Customer Reviews</h3>
              <div v-if="tourReviews.length > 0" class="flex items-center gap-2">
                <span class="font-bold text-2xl">{{ avgRating }}</span>
                <div class="flex">
                  <span v-for="i in 5" :key="i" class="material-symbols-outlined text-lg" :class="i <= Math.round(avgRating) ? 'text-yellow-500' : 'text-slate-300'" style="font-variation-settings: 'FILL' 1">star</span>
                </div>
                <span class="text-sm text-slate-500">({{ tourReviews.length }})</span>
              </div>
            </div>

            <div v-if="tourReviews.length > 0" class="space-y-6">
              <div
                v-for="review in tourReviews.slice(0, showAllReviews ? tourReviews.length : 3)"
                :key="review.id"
                class="border-b border-slate-100 dark:border-slate-800 pb-6"
              >
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary text-sm">
                    {{ getInitials(review.name) }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="text-sm font-bold">{{ review.name }}</h4>
                      <span class="text-[10px] text-slate-400">{{ review.review_date }}</span>
                    </div>
                    <div class="flex items-center gap-0.5 mt-0.5">
                      <span v-for="i in review.rating" :key="i" class="material-symbols-outlined text-yellow-400 text-xs" style="font-variation-settings: 'FILL' 1">star</span>
                    </div>
                  </div>
                </div>
                <p v-if="review.title" class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">{{ review.title }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ review.comment }}</p>
              </div>

              <button
                v-if="tourReviews.length > 3"
                @click="showAllReviews = !showAllReviews"
                class="font-bold text-primary hover:underline text-sm flex items-center gap-1"
              >
                {{ showAllReviews ? 'Show less' : `View all ${tourReviews.length} reviews` }}
                <span class="material-symbols-outlined text-sm">{{ showAllReviews ? 'expand_less' : 'expand_more' }}</span>
              </button>
            </div>

            <div v-else class="py-8 text-center text-slate-400">
              <span class="material-symbols-outlined text-3xl mb-2">rate_review</span>
              <p class="text-sm font-medium">No reviews yet for this tour.</p>
            </div>
          </section>
        </div>

        <!-- Right Column: Booking Widget - Sticky -->
        <div class="hidden lg:block">
          <div class="sticky top-24">
            <!-- Booking Widget Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-lg">
              <!-- Price Header -->
              <div class="mb-5">
                <div class="flex items-baseline gap-2">
                  <span class="text-3xl font-black text-primary">{{ currencyStore.formatConverted(basePrice || 0) }}</span>
                  <span class="text-sm text-slate-500">{{ currency }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1">per person</p>
              </div>

              <!-- Date Selector (Calendar) -->
              <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Tour Date</label>
                <TourCalendar
                  v-model="selectedDate"
                  :min-date="minDate"
                  :offers="tour?.offers_data || []"
                  :blocks="tour?.blocks_data || []"
                  :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
                />
                <!-- Active offer indicator -->
                <div v-if="activeOffer" class="mt-2 flex items-center gap-2 px-3 py-2 rounded-xl" :style="{ backgroundColor: (activeOffer.color || '#f59e0b') + '12' }">
                  <span class="material-symbols-outlined text-base" :style="{ color: activeOffer.color || '#f59e0b' }">sell</span>
                  <span class="text-xs font-bold" :style="{ color: activeOffer.color || '#f59e0b' }">
                    {{ activeOffer.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : `$${activeOffer.discount} OFF` }}
                  </span>
                </div>
              </div>

              <!-- Time Selector -->
              <div class="mb-4">
                <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Departure Time</label>
                  <span v-if="tzInfo" class="inline-flex items-center gap-1 text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full" :title="`${tzInfo.name} (${tzInfo.gmt})`">
                    <span class="material-symbols-outlined text-xs">public</span>
                    <span class="hidden sm:inline">{{ tzInfo.name }} ·</span>
                    <span class="sm:hidden">{{ tzInfo.code }} ·</span>
                    {{ tzInfo.gmt }}
                  </span>
                </div>
                <div class="relative">
                  <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">schedule</span>
                  <select
                    v-model="selectedTime"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary appearance-none"
                  >
                    <option value="">Select time</option>
                    <option v-for="time in availableTimes" :key="time.value" :value="time.value">
                      {{ time.label }}
                    </option>
                  </select>
                </div>
                <div v-if="tour.duration_hours || tour.duration_days" class="mt-1.5 flex items-center gap-1 text-xs text-primary font-semibold px-1">
                  <span class="material-symbols-outlined text-xs">timelapse</span>
                  Duration: {{ formatDuration(tour) }}
                </div>
              </div>

              <!-- Travelers Selector -->
              <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Travelers</label>
                <div class="flex items-center justify-between border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 bg-slate-50 dark:bg-slate-800">
                  <button
                    @click="decrementAdults"
                    type="button"
                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-slate-700 rounded-full hover:bg-slate-100 dark:hover:bg-slate-600 transition border border-slate-200 dark:border-slate-600"
                  >
                    <span class="material-symbols-outlined text-lg">remove</span>
                  </button>
                  <span class="font-bold text-sm">{{ adults }} {{ adults === 1 ? 'Adult' : 'Adults' }}</span>
                  <button
                    @click="incrementAdults"
                    type="button"
                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-slate-700 rounded-full hover:bg-slate-100 dark:hover:bg-slate-600 transition border border-slate-200 dark:border-slate-600"
                  >
                    <span class="material-symbols-outlined text-lg">add</span>
                  </button>
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="space-y-2 mb-5 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ currencyStore.formatConverted(basePrice || 0) }} x {{ adults }} {{ adults === 1 ? 'adult' : 'adults' }}</span>
                  <span class="font-semibold">{{ currencyStore.formatConverted(subtotal || 0) }}</span>
                </div>
                <div v-if="groupDiscount > 0" class="flex justify-between text-sm">
                  <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">local_offer</span>
                    {{ activeOffer?.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : 'Discount' }}
                  </span>
                  <span class="font-semibold text-green-600">-{{ currencyStore.formatConverted(groupDiscount || 0) }}</span>
                </div>
                <div class="flex justify-between text-base font-black border-t border-slate-200 dark:border-slate-700 pt-2">
                  <span>Total</span>
                  <span class="text-primary">{{ currencyStore.formatConverted(total || 0) }} {{ currencyStore.selectedCurrency }}</span>
                </div>
              </div>

              <!-- CTA Buttons -->
              <button
                @click="handleBooking"
                class="w-full bg-primary hover:bg-primary/90 text-white font-black py-4 rounded-xl transition-all shadow-lg shadow-primary/20 mb-3 flex items-center justify-center gap-2"
              >
                <span class="material-symbols-outlined">check_circle</span>
                Book Now
              </button>


            </div>

          </div>
        </div>
      </div>

      <!-- Related Tours (Full Width) -->
      <section class="mt-20" v-if="relatedTours.length > 0">
        <h2 class="text-2xl font-black mb-8">You might also like</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <NuxtLink
            v-for="relatedTour in relatedTours.slice(0, 4)"
            :key="relatedTour.id"
            :to="`/${locale}/${relatedTour.city?.slug || 'puno'}/${relatedTour.slug}`"
            class="group cursor-pointer"
          >
            <div class="aspect-[4/3] rounded-xl overflow-hidden mb-3 relative">
              <img
                :src="getImageUrl(relatedTour.featured_image)"
                :alt="relatedTour.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <button class="absolute top-3 right-3 p-1.5 rounded-full bg-white/20 backdrop-blur-md text-white">
                <span class="material-symbols-outlined text-xl">favorite</span>
              </button>
            </div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ relatedTour.city?.name || 'Puno' }}</p>
            <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">{{ relatedTour.title }}</h4>
            <div class="flex items-center gap-1 mt-1">
              <span class="material-symbols-outlined text-yellow-500 fill-1 text-xs">star</span>
              <span class="text-sm font-bold">{{ relatedTour.rating || '4.5' }}</span>
              <span class="text-xs text-slate-500">({{ relatedTour.reviews_count || 0 }})</span>
            </div>
            <p class="mt-2 font-black text-slate-900 dark:text-white">From ${{ relatedTour.min_price || 0 }}</p>
          </NuxtLink>
        </div>
      </section>
    </main>

    <!-- Mobile Fixed Bottom CTA -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 p-4 shadow-2xl z-40">
      <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col">
          <span class="text-xs text-slate-500">From</span>
          <span class="text-2xl font-black text-primary">${{ (basePrice || 0).toFixed(0) }}</span>
          <span class="text-xs text-slate-500">per person</span>
        </div>
        <button @click="mobileBookingOpen = true" class="flex-1 bg-primary hover:bg-primary/90 text-white font-black py-4 px-6 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
          <span class="material-symbols-outlined">calendar_today</span>
          Book Now
        </button>
      </div>
    </div>

    <!-- Mobile Booking Drawer -->
    <Teleport to="body">
      <Transition name="drawer">
        <div v-if="mobileBookingOpen" class="lg:hidden fixed inset-0 z-50">
          <div class="absolute inset-0 bg-black/50" @click="mobileBookingOpen = false"></div>
          <div class="absolute bottom-0 left-0 right-0 bg-white dark:bg-slate-900 rounded-t-3xl max-h-[85vh] overflow-y-auto shadow-2xl">
            <!-- Handle bar -->
            <div class="flex justify-center pt-3 pb-1">
              <div class="w-10 h-1 bg-slate-300 rounded-full"></div>
            </div>

            <div class="p-5 space-y-4">
              <!-- Price -->
              <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-primary">{{ currencyStore.formatConverted(basePrice || 0, false) }}</span>
                <span class="text-sm text-slate-500">{{ currency }} / person</span>
              </div>

              <!-- Offer banner -->
              <div v-if="activeOffer" class="flex items-center gap-2 px-3 py-2 rounded-xl" :style="{ backgroundColor: (activeOffer.color || '#f59e0b') + '12' }">
                <span class="material-symbols-outlined text-base" :style="{ color: activeOffer.color || '#f59e0b' }">sell</span>
                <span class="text-xs font-bold" :style="{ color: activeOffer.color || '#f59e0b' }">
                  {{ activeOffer.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : `$${activeOffer.discount} OFF` }}
                </span>
              </div>

              <!-- Calendar -->
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Select Date</label>
                <TourCalendar
                  v-model="selectedDate"
                  :min-date="minDate"
                  :offers="tour?.offers_data || []"
                  :blocks="tour?.blocks_data || []"
                  :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
                />
              </div>

              <!-- Time -->
              <div>
                <div class="flex items-baseline justify-between gap-1 mb-2 flex-wrap">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Departure Time</label>
                  <span v-if="tzInfo" class="inline-flex items-center gap-1 text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full" :title="`${tzInfo.name} (${tzInfo.gmt})`">
                    <span class="material-symbols-outlined text-xs">public</span>
                    <span class="hidden sm:inline">{{ tzInfo.name }} ·</span>
                    <span class="sm:hidden">{{ tzInfo.code }} ·</span>
                    {{ tzInfo.gmt }}
                  </span>
                </div>
                <select
                  v-model="selectedTime"
                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm"
                >
                  <option value="">Select time</option>
                  <option v-for="time in availableTimes" :key="time.value" :value="time.value">{{ time.label }}</option>
                </select>
              </div>

              <!-- Travelers -->
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Travelers</label>
                <div class="flex items-center justify-between border border-slate-200 rounded-xl px-4 py-3 bg-slate-50">
                  <button @click="decrementAdults" type="button" class="w-8 h-8 flex items-center justify-center bg-white rounded-full border border-slate-200">
                    <span class="material-symbols-outlined text-lg">remove</span>
                  </button>
                  <span class="font-bold text-sm">{{ adults }} {{ adults === 1 ? 'Adult' : 'Adults' }}</span>
                  <button @click="incrementAdults" type="button" class="w-8 h-8 flex items-center justify-center bg-white rounded-full border border-slate-200">
                    <span class="material-symbols-outlined text-lg">add</span>
                  </button>
                </div>
              </div>

              <!-- Total -->
              <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                <span class="font-bold text-slate-800">Total</span>
                <span class="text-xl font-black text-primary">{{ currencyStore.formatConverted(total || 0) }} {{ currencyStore.selectedCurrency }}</span>
              </div>

              <!-- Validation error -->
              <div v-if="mobileError" class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-xl">
                <span class="material-symbols-outlined text-red-500 text-base">error</span>
                <span class="text-xs font-semibold text-red-700">{{ mobileError }}</span>
              </div>

              <!-- Book button -->
              <button
                @click="mobileHandleBooking"
                class="w-full bg-primary text-white font-black py-4 rounded-xl shadow-lg flex items-center justify-center gap-2"
              >
                <span class="material-symbols-outlined">check_circle</span>
                Book Now
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>

  <!-- Loading State -->
  <div v-else-if="pending" class="min-h-screen flex items-center justify-center bg-background-light dark:bg-background-dark">
    <div class="text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      <p class="mt-4 text-slate-600">Loading tour...</p>
    </div>
  </div>

  <!-- Error State -->
  <div v-else class="min-h-screen flex items-center justify-center bg-background-light dark:bg-background-dark">
    <div class="text-center">
      <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">search_off</span>
      <p class="text-red-600 text-lg mb-4">Tour not found</p>
      <NuxtLink to="/tours" class="text-primary hover:underline font-bold">
        View all tours
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
// Stores and utils like useCartStore and getImageUrl are auto-imported in Nuxt 4
const route = useRoute()
const { api } = useApi()
const config = useRuntimeConfig()
const cartStore = useCartStore()
const { t, locale } = useI18n()
const currencyStore = useCurrencyStore()

const slug = route.params.slug as string
const citySlug = route.params.city as string

// Get language code (ES, EN, PT) from i18n locale
const langCode = computed(() => locale.value.toUpperCase())

// Fetch tour data with SSR using multilang API endpoint
const { data: response, pending, error } = await useAsyncData(
  `tour-${langCode.value}-${citySlug}-${slug}`,
  () => api(`/tours/${langCode.value.toLowerCase()}/${citySlug}/${slug}`)
)

const tour = computed(() => response.value?.data || null)

// Fetch related tours (lazy - doesn't block navigation)
const { data: relatedResponse } = await useAsyncData(
  `related-tours-${slug}`,
  () => api('/tours?limit=4'),
  { lazy: true, default: () => ({ data: [] }) }
)

const relatedTours = computed(() => {
  const tours = relatedResponse.value?.data || []
  // Filter out current tour
  return tours.filter((t: any) => t.slug !== slug)
})

// Reviews for this tour
const tourReviews = ref<any[]>([])
const showAllReviews = ref(false)

const avgRating = computed(() => {
  if (tourReviews.value.length === 0) return 0
  const sum = tourReviews.value.reduce((acc: number, r: any) => acc + r.rating, 0)
  return (sum / tourReviews.value.length).toFixed(1)
})

function getInitials(name: string) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

// Fetch reviews when tour is loaded
watch(tour, async (t) => {
  if (t?.id) {
    try {
      const res = await api(`/reviews?tour_id=${t.id}&per_page=20`)
      tourReviews.value = (res as any)?.data || []
    } catch (e) { tourReviews.value = [] }
  }
}, { immediate: true })

// Booking widget state
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)
const mobileBookingOpen = ref(false)
const mobileError = ref('')

function mobileHandleBooking() {
  if (!selectedDate.value) {
    mobileError.value = 'Please select a date'
    return
  }
  if (!selectedTime.value) {
    mobileError.value = 'Please select a time'
    return
  }
  mobileError.value = ''
  mobileBookingOpen.value = false
  handleBooking()
}

// Clear mobile error when user selects
watch(selectedDate, () => { if (mobileError.value) mobileError.value = '' })
watch(selectedTime, () => { if (mobileError.value) mobileError.value = '' })

// Computed - Base price with price_details logic
const basePrice = computed(() => {
  const details = tour.value?.price_details
  if (details && details.length > 0) {
    const activePrices = details
      .filter((p: any) => p.active)
      .sort((a: any, b: any) => (a.min_quantity || 1) - (b.min_quantity || 1))

    if (activePrices.length > 0) {
      const matchingPrice = activePrices.find((p: any) => {
        const min = p.min_quantity || 1
        const max = p.max_quantity || Infinity
        return adults.value >= min && adults.value <= max
      })

      if (matchingPrice) {
        return parseFloat(matchingPrice.price || 0)
      }

      const lastPrice = activePrices[activePrices.length - 1]
      if (adults.value > (lastPrice.max_quantity || 0)) {
        return parseFloat(lastPrice.price || 0)
      }

      return Math.min(...activePrices.map((p: any) => parseFloat(p.price || 0)))
    }
  }

  return tour.value?.min_price || 0
})

const subtotal = computed(() => basePrice.value * adults.value)

// Check if selected date has an active offer
const activeOffer = computed(() => {
  if (!selectedDate.value || !tour.value?.offers_data) return null
  const selected = selectedDate.value
  return tour.value.offers_data.find((offer: any) => {
    return selected >= offer.startDate && selected <= offer.endDate
  }) || null
})

// Check if selected date is blocked
const isDateBlocked = computed(() => {
  if (!selectedDate.value || !tour.value?.blocks_data) return false
  const selected = selectedDate.value
  return tour.value.blocks_data.some((block: any) => {
    return selected >= block.startDate && selected <= block.endDate
  })
})

// Calculate offer discount
const offerDiscount = computed(() => {
  if (!activeOffer.value) return 0
  if (activeOffer.value.discountType === 'percentage') {
    return subtotal.value * (activeOffer.value.discount / 100)
  }
  return activeOffer.value.discount * adults.value
})

const groupDiscount = computed(() => offerDiscount.value)

const total = computed(() => subtotal.value - groupDiscount.value)

const currency = computed(() => tour.value?.currency || 'USD')

// Calculate minimum bookable date based on booking_anticipation_hours
const minDate = computed(() => {
  const anticipationHours = tour.value?.booking_anticipation_hours || 24
  const now = new Date()

  // Add anticipation hours to current time
  const minDateTime = new Date(now.getTime() + (anticipationHours * 60 * 60 * 1000))

  // Format as YYYY-MM-DD for input[type="date"]
  return minDateTime.toISOString().split('T')[0]
})

// Available times from tour data
const durationLabel = computed(() => {
  if (!tour.value) return ''
  // Use duration_quantity + duration_unit (from admin wizard) as primary source
  const qty = tour.value.duration_quantity
  const unit = tour.value.duration_unit
  if (qty && unit) {
    if (unit === 'hours') return `${qty}h`
    if (unit === 'days') return `${qty} day${qty > 1 ? 's' : ''}`
    if (unit === 'minutes') return `${qty} min`
  }
  // Fallback to legacy fields
  const d = tour.value.duration_days || 0
  const h = tour.value.duration_hours || 0
  if (d > 0) return `${d} day${d > 1 ? 's' : ''}`
  if (h > 0) return `${h}h`
  return ''
})

const tzInfo = computed(() => {
  const tz = tour.value?.timezone
  if (tz === 'America/Lima') return { code: 'HP', gmt: 'GMT-5', name: t('peruvian_time') }
  if (tz === 'America/La_Paz') return { code: 'HB', gmt: 'GMT-4', name: t('bolivian_time') }
  return null
})

const availableTimes = computed(() => {
  const times = []
  const defaultDur = durationLabel.value ? ` - ${t('duration_label')} ${durationLabel.value}` : ''

  const formatDuration = (duration: any, unit: any) => {
    if (!duration) return defaultDur
    const unitLabel = unit === 'days' ? (duration > 1 ? t('days') : t('day')) : (duration > 1 ? t('hours') : t('hour'))
    return ` - ${t('duration_label')} ${duration} ${unitLabel}`
  }

  const formatTimeStr = (raw: string, durStr: string) => {
    const [hours, minutes] = raw.split(':')
    const hour = parseInt(hours)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 || 12
    return { value: raw, label: `${hour12}:${minutes} ${ampm}${durStr}` }
  }

  const multi = tour.value?.departure_times
  if (Array.isArray(multi) && multi.length > 0) {
    for (const item of multi) {
      if (!item) continue
      if (typeof item === 'string') {
        times.push(formatTimeStr(item, defaultDur))
      } else if (item.time) {
        times.push(formatTimeStr(item.time, formatDuration(item.duration, item.duration_unit)))
      }
    }
  } else if (tour.value?.departure_time) {
    times.push(formatTimeStr(tour.value.departure_time, defaultDur))
  }

  if (times.length === 0) {
    times.push(
      { value: '06:00', label: `06:00 AM${dur}` },
      { value: '08:00', label: `08:00 AM${dur}` },
      { value: '09:00', label: `09:00 AM${dur}` },
      { value: '10:00', label: `10:00 AM${dur}` }
    )
  }

  return times
})

// Booking widget methods
function incrementAdults() {
  if (adults.value < 20) adults.value++
}

function decrementAdults() {
  if (adults.value > 1) adults.value--
}

const guideLanguageMap: Record<number, string> = { 1: 'Spanish', 2: 'English', 3: 'French', 4: 'German', 5: 'Portuguese', 6: 'Italian' }
function getGuideLanguageNames(ids: number[]): string[] {
  return ids.map(id => guideLanguageMap[id] || `Lang ${id}`)
}

const guideTypeLabels: Record<string, string> = {
  live_guide: 'Live Guide',
  audio_guide: 'Audio Guide',
  informative_brochures: 'Informative Brochures',
  no_guide: 'No Guide',
  none: 'None'
}

function handleBooking() {
  console.log('=== handleBooking called from [slug].vue ===')
  
  // Validate required fields
  if (!selectedDate.value) {
    alert('Please select a tour date')
    return
  }

  if (!selectedTime.value) {
    alert('Please select a departure time')
    return
  }

  console.log('Validation passed, preparing to add to cart...')

  // Get tour image
  const tourImage = tour.value?.media_gallery && tour.value.media_gallery.length > 0
    ? getImageUrl(tour.value.media_gallery[0].url)
    : ''

  // Calculate total
  const totalPrice = basePrice.value * adults.value

  // Add to cart
  const cartItem = {
    tourId: tour.value?.id,
    tourTitle: tour.value?.title,
    tourSlug: slug,
    tourImage,
    selectedDate: selectedDate.value,
    selectedTime: selectedTime.value,
    timezone: tour.value?.timezone || 'America/Lima',
    adults: adults.value,
    children: 0,
    basePrice: basePrice.value,
    childPrice: 0,
    total: totalPrice,
    currency: tour.value?.currency || 'USD',
    policies: tour.value?.policies || '',
    cancellationPolicy: tour.value?.cancellation_policy || '',
    taxPercentage: tour.value?.tax_percentage || 0,
    advancePaymentPercentage: tour.value?.advance_payment_percentage || 100,
    guideType: tour.value?.guide_type || '',
    guideLanguages: getGuideLanguageNames(tour.value?.guide_languages || []),
    durationLabel: durationLabel.value,
  }

  console.log('Adding to cart:', cartItem)
  cartStore.addItem(cartItem)
  console.log('Cart items after add:', cartStore.items.length)

  // Navigate to cart
  navigateTo('/cart')
}


// Dynamic SEO with Schema.org - Optimized for Google AI
watchEffect(() => {
  if (tour.value) {
    const canonicalUrl = `https://voyager.com/tours/${slug}`
    const imageUrl = tour.value.featured_image ? getImageUrl(tour.value.featured_image) : ''

    useHead({
      title: `${tour.value.title} | Voyager Marketplace`,
      meta: [
        // Basic SEO
        { name: 'description', content: tour.value.short_description || tour.value.title },
        { name: 'keywords', content: `${tour.value.title}, tours puno, lake titicaca tours, peru tours, ${tour.value.city?.name || 'puno'} tours` },
        { name: 'robots', content: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' },
        { name: 'author', content: 'Voyager Marketplace' },

        // Open Graph
        { property: 'og:title', content: tour.value.title },
        { property: 'og:description', content: tour.value.short_description || tour.value.title },
        { property: 'og:type', content: 'product' },
        { property: 'og:url', content: canonicalUrl },
        { property: 'og:site_name', content: 'Voyager' },
        { property: 'og:image', content: imageUrl },
        { property: 'og:image:width', content: '1200' },
        { property: 'og:image:height', content: '630' },
        { property: 'og:image:alt', content: tour.value.title },
        { property: 'og:locale', content: 'en_US' },

        // Twitter Card
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: tour.value.title },
        { name: 'twitter:description', content: tour.value.short_description || tour.value.title },
        { name: 'twitter:image', content: imageUrl },
        { name: 'twitter:image:alt', content: tour.value.title },

        // Mobile
        { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
        { name: 'theme-color', content: '#0077cc' },
        { name: 'mobile-web-app-capable', content: 'yes' },
      ],
      link: [
        { rel: 'canonical', href: canonicalUrl }
      ],
      script: [
        // Product Schema
        {
          type: 'application/ld+json',
          children: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: tour.value.title,
            description: tour.value.short_description || tour.value.title,
            image: imageUrl,
            brand: {
              '@type': 'Brand',
              name: 'Voyager Marketplace'
            },
            offers: {
              '@type': 'Offer',
              price: tour.value.min_price || 0,
              priceCurrency: 'USD',
              availability: 'https://schema.org/InStock',
              url: canonicalUrl,
              priceValidUntil: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
              seller: {
                '@type': 'Organization',
                name: 'Voyager Marketplace'
              }
            },
            aggregateRating: {
              '@type': 'AggregateRating',
              ratingValue: tour.value.rating || '4.5',
              reviewCount: tour.value.reviews_count || 0,
              bestRating: '5',
              worstRating: '1'
            }
          })
        },
        // TouristTrip Schema
        {
          type: 'application/ld+json',
          children: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'TouristTrip',
            name: tour.value.title,
            description: tour.value.short_description || tour.value.title,
            touristType: 'Tourist',
            itinerary: {
              '@type': 'ItemList',
              name: 'Tour Itinerary',
              description: tour.value.itinerary || 'Detailed tour itinerary'
            },
            offers: {
              '@type': 'Offer',
              price: tour.value.min_price || 0,
              priceCurrency: 'USD'
            },
            provider: {
              '@type': 'TouristInformationCenter',
              name: 'Voyager Marketplace'
            }
          })
        },
        // BreadcrumbList Schema
        {
          type: 'application/ld+json',
          children: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: [
              {
                '@type': 'ListItem',
                position: 1,
                name: 'Home',
                item: 'https://voyager.com/'
              },
              {
                '@type': 'ListItem',
                position: 2,
                name: 'Tours',
                item: 'https://voyager.com/tours'
              },
              {
                '@type': 'ListItem',
                position: 3,
                name: tour.value.city?.name || 'Puno',
                item: `https://voyager.com/tours?city=${tour.value.city?.slug || 'puno'}`
              },
              {
                '@type': 'ListItem',
                position: 4,
                name: tour.value.title,
                item: canonicalUrl
              }
            ]
          })
        },
        // TouristAttraction Schema
        {
          type: 'application/ld+json',
          children: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'TouristAttraction',
            name: tour.value.title,
            description: tour.value.short_description || tour.value.title,
            image: imageUrl,
            address: {
              '@type': 'PostalAddress',
              addressLocality: tour.value.city?.name || 'Puno',
              addressRegion: 'Puno',
              addressCountry: 'PE'
            },
            touristType: 'Tourist',
            availableLanguage: ['English', 'Spanish'],
            isAccessibleForFree: false
          })
        }
      ]
    })
  }
})

// Helper functions
function formatDuration(tour: any) {
  if (tour.duration_quantity && tour.duration_unit) {
    const qty = tour.duration_quantity
    if (tour.duration_unit === 'hours') return `${qty}H`
    if (tour.duration_unit === 'days') return `${qty}D`
    if (tour.duration_unit === 'minutes') return `${qty}min`
  }
  if (tour.duration_days > 0) return `${tour.duration_days}D`
  if (tour.duration_hours > 0) return `${tour.duration_hours}H`
  return ''
}

</script>

<style scoped>
@reference "../../assets/css/main.css";

.drawer-enter-active, .drawer-leave-active { transition: all 0.3s ease; }
.drawer-enter-from .absolute.bottom-0, .drawer-leave-to .absolute.bottom-0 { transform: translateY(100%); }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }

/* Style for includes/not includes lists from HTML content */
:deep(.tour-includes-list ul),
:deep(.tour-not-includes-list ul) {
  @apply space-y-3;
}

:deep(.tour-includes-list li) {
  @apply flex items-center gap-3;
}

:deep(.tour-includes-list li::before) {
  content: '';
  @apply hidden;
}

:deep(.tour-not-includes-list li) {
  @apply flex items-center gap-3;
}

:deep(.tour-not-includes-list li::before) {
  content: '';
  @apply hidden;
}

/* Style for itinerary from HTML content */
:deep(.tour-itinerary > *) {
  @apply relative pl-10 pb-8;
}

:deep(.tour-itinerary > *::before) {
  content: '';
  @apply absolute left-0 top-1.5 w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white z-10;
}
</style>
