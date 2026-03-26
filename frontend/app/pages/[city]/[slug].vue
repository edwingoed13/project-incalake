<template>
  <div v-if="tour" class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Title & Basic Info -->
      <div class="flex flex-col lg:flex-row justify-between gap-6 mb-8">
        <div class="flex-1">
          <h1 class="text-2xl md:text-3xl font-black mb-3 leading-tight">{{ tour.title }}</h1>
          <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
            <!-- Rating -->
            <div class="flex items-center gap-1">
              <span class="material-symbols-outlined text-yellow-500 fill-1 text-base">star</span>
              <span>{{ tour.rating || '4.5' }}</span>
              <span class="text-slate-500 underline cursor-pointer hover:text-slate-700">({{ tour.reviews_count || 0 }} reviews)</span>
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
              <div class="flex items-center gap-2">
                <span class="font-bold text-2xl">{{ tour.rating || '4.5' }}</span>
                <div class="flex">
                  <span v-for="i in 5" :key="i" class="material-symbols-outlined text-yellow-500 fill-1 text-lg">star</span>
                </div>
              </div>
            </div>
            <div class="space-y-8">
              <!-- Sample reviews - replace with real data when available -->
              <div class="border-b border-slate-100 dark:border-slate-800 pb-8">
                <div class="flex items-center gap-4 mb-3">
                  <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center font-bold text-primary">JD</div>
                  <div>
                    <h4 class="font-bold">John Doe</h4>
                    <p class="text-xs text-slate-500">2 days ago • Verified Booking</p>
                  </div>
                </div>
                <p class="text-slate-600 dark:text-slate-400">Incredible experience! Our guide was very knowledgeable and passionate about sharing the culture and history of Lake Titicaca.</p>
              </div>
            </div>
            <button class="mt-6 font-bold text-primary hover:underline text-sm">View all {{ tour.reviews_count || 0 }} reviews</button>
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
                  <span class="text-3xl font-black text-primary">${{ (basePrice || 0).toFixed(2) }}</span>
                  <span class="text-sm text-slate-500">{{ currency }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1">per person</p>
              </div>

              <!-- Date Selector -->
              <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Tour Date</label>
                <div class="relative">
                  <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">calendar_today</span>
                  <input
                    v-model="selectedDate"
                    :min="minDate"
                    type="date"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                  />
                </div>
              </div>

              <!-- Time Selector -->
              <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Departure Time</label>
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
                  <span class="text-slate-600 dark:text-slate-400">${{ (basePrice || 0).toFixed(2) }} x {{ adults }} {{ adults === 1 ? 'adult' : 'adults' }}</span>
                  <span class="font-semibold">${{ (subtotal || 0).toFixed(2) }}</span>
                </div>
                <div v-if="groupDiscount > 0" class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Group discount</span>
                  <span class="font-semibold text-green-600">-${{ (groupDiscount || 0).toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-base font-black border-t border-slate-200 dark:border-slate-700 pt-2">
                  <span>Total</span>
                  <span class="text-primary">${{ (total || 0).toFixed(2) }} {{ currency }}</span>
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
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 p-4 shadow-2xl z-40 safe-area-inset-bottom">
      <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col">
          <span class="text-xs text-slate-500 dark:text-slate-400">From</span>
          <span class="text-2xl font-black text-primary">${{ tour.min_price || 0 }}</span>
          <span class="text-xs text-slate-500">per person</span>
        </div>
        <button @click="handleBooking" class="flex-1 bg-primary hover:bg-primary/90 text-white font-black py-4 px-6 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
          <span class="material-symbols-outlined">calendar_today</span>
          Book Now
        </button>
      </div>
    </div>
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
const { locale } = useI18n()

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

// Fetch related tours
const { data: relatedResponse } = await useAsyncData(
  `related-tours-${slug}`,
  () => api('/tours?limit=4')
)

const relatedTours = computed(() => {
  const tours = relatedResponse.value?.data || []
  // Filter out current tour
  return tours.filter((t: any) => t.slug !== slug)
})

// Booking widget state
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)

// Computed - Base price with price_details logic
const basePrice = computed(() => {
  if (tour.value?.price_details && tour.value.price_details.length > 0) {
    // Filter adult prices only
    const adultPrices = tour.value.price_details.filter((p: any) => {
      const ageStage = p.age_stage || {}
      // Check if age_stage is for adults (typically min_age >= 12-18)
      return ageStage.min_age >= 12 || ageStage.max_age >= 18
    })

    if (adultPrices.length > 0) {
      // Find the price that matches the current number of adults
      const matchingPrice = adultPrices.find((p: any) => {
        const min = p.min_quantity || 1
        const max = p.max_quantity || Infinity
        return adults.value >= min && adults.value <= max
      })

      if (matchingPrice) {
        return parseFloat(matchingPrice.price || 0)
      }

      // If no exact match, return the lowest price
      const prices = adultPrices.map((p: any) => parseFloat(p.price || 0))
      return Math.min(...prices)
    }
  }

  // Fallback to min_price or default
  return tour.value?.min_price || 0
})

const subtotal = computed(() => basePrice.value * adults.value)

const groupDiscount = computed(() => {
  // TODO: Get group discount from tour.offers_data when implemented
  return 0
})

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
const availableTimes = computed(() => {
  const times = []

  // Add departure time if available
  if (tour.value?.departure_time) {
    const [hours, minutes] = tour.value.departure_time.split(':')
    const hour = parseInt(hours)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 || 12
    times.push({
      value: tour.value.departure_time,
      label: `${hour12}:${minutes} ${ampm}`
    })
  }

  // Add default times if no departure time
  if (times.length === 0) {
    times.push(
      { value: '06:00', label: '06:00 AM' },
      { value: '08:00', label: '08:00 AM' },
      { value: '09:00', label: '09:00 AM' },
      { value: '10:00', label: '10:00 AM' }
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
    advancePaymentPercentage: tour.value?.advance_payment_percentage || 100
  }

  console.log('Adding to cart:', cartItem)
  cartStore.addItem(cartItem)
  console.log('Cart items after add:', cartStore.items.length)

  // Navigate to checkout
  navigateTo('/checkout')
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
  if (tour.duration_days > 0) {
    return `${tour.duration_days} day${tour.duration_days > 1 ? 's' : ''}`
  }
  if (tour.duration_hours > 0) {
    return `${tour.duration_hours} hour${tour.duration_hours > 1 ? 's' : ''}`
  }
  return 'Variable duration'
}

</script>

<style scoped>
@reference "../../assets/css/main.css";

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
