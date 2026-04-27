<template>
  <div v-if="tour" class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-28 lg:pb-8">

      <!-- Title & Basic Info -->
      <div class="mb-6 md:mb-8">
        <!-- Mobile layout -->
        <div class="md:hidden">
          <h1 class="text-xl font-black mb-2 leading-tight">{{ tour.title }}</h1>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs text-slate-600">
              <span class="material-symbols-outlined text-yellow-500 fill-1 text-sm">star</span>
              <span class="font-bold">{{ tour.rating || '4.5' }}</span>
              <span class="text-slate-300">-</span>
              <span class="material-symbols-outlined text-slate-400 text-sm">location_on</span>
              <span>{{ tour.city?.name || 'Puno' }}</span>
              <span class="text-slate-300">-</span>
              <span class="material-symbols-outlined text-slate-400 text-sm">schedule</span>
              <span>{{ formatDurationShort(tour) }}</span>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="shareTour"
                class="p-1.5 rounded-full hover:bg-slate-100 transition-colors"
                aria-label="Share"
              >
                <span class="material-symbols-outlined text-slate-500 text-lg">share</span>
              </button>
              <button
                @click="toggleFavorite"
                class="p-1.5 rounded-full hover:bg-slate-100 transition-colors"
                aria-label="Save"
              >
                <span class="material-symbols-outlined text-lg" :class="isFavorite ? 'text-red-500 fill-1' : 'text-slate-500'">favorite</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Desktop layout -->
        <div class="hidden md:flex md:flex-row justify-between gap-6">
          <div class="flex-1">
            <h1 class="text-3xl font-black mb-3 leading-tight">{{ tour.title }}</h1>
            <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-yellow-500 fill-1 text-base">star</span>
                <span>{{ tour.rating || '4.5' }}</span>
                <span class="text-slate-500 underline cursor-pointer hover:text-slate-700">({{ tour.reviews_count || 0 }} {{ t('reviews') }})</span>
              </div>
              <span class="text-slate-300">•</span>
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-slate-500 text-base">location_on</span>
                <span>{{ tour.city?.name || 'Puno' }}, Peru</span>
              </div>
              <span class="text-slate-300">•</span>
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-slate-500 text-base">schedule</span>
                <span>{{ formatDuration(tour) }}</span>
              </div>
            </div>
          </div>
          <div class="flex gap-2 items-start">
            <button
              @click="shareTour"
              class="flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 dark:hover:bg-slate-700 transition-colors"
              aria-label="Share tour"
            >
              <span class="material-symbols-outlined text-lg">share</span>
              <span>{{ t('share') }}</span>
            </button>
            <button
              @click="toggleFavorite"
              class="flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 dark:hover:bg-slate-700 transition-colors"
              aria-label="Save to favorites"
            >
              <span class="material-symbols-outlined text-lg" :class="isFavorite ? 'text-red-500 fill-1' : ''">favorite</span>
              <span>{{ t('save') }}</span>
            </button>
          </div>
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
              <h3 class="text-xl font-bold">{{ t('customer_reviews') }}</h3>
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
            <button class="mt-6 font-bold text-primary hover:underline text-sm">{{ t('view_all_reviews', { count: tour.reviews_count || 0 }) }}</button>
          </section>
        </div>

        <!-- Right Column: Booking Widget - Sticky -->
        <div class="hidden lg:block">
          <div class="sticky top-24">
            <!-- Booking Widget Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-lg">
              <!-- Active Offer Banner -->
              <div v-if="activeOffer" class="mb-4 p-3 rounded-xl flex items-center gap-3" :style="{ backgroundColor: activeOffer.color + '12' }">
                <span class="material-symbols-outlined text-xl" :style="{ color: activeOffer.color }">sell</span>
                <div class="flex-1">
                  <p class="text-sm font-bold" :style="{ color: activeOffer.color }">
                    {{ activeOffer.discount }}{{ activeOffer.discountType === 'percentage' ? '%' : ' USD' }} OFF
                  </p>
                  <p class="text-[11px] text-slate-500">
                    Hasta {{ formatOfferDate(activeOffer.endDate) }}
                  </p>
                </div>
              </div>

              <!-- Price Header -->
              <div class="mb-5">
                <div class="flex items-baseline gap-2">
                  <span v-if="activeOffer && originalPrice > discountedPrice" class="text-xl text-slate-400 line-through">
                    {{ currencyStore.formatConverted(originalPrice) }}
                  </span>
                  <span class="text-3xl font-black text-primary">{{ currencyStore.formatConverted(discountedPrice || 0) }}</span>
                  <span class="text-sm text-slate-500">{{ currencyStore.selectedCurrency }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1">{{ t('per_person_label') }}</p>
                <p v-if="currencyStore.isForeignCurrency" class="text-[11px] text-amber-600 mt-1 flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">info</span>
                  {{ t('payment_usd_notice') }}
                </p>
              </div>

              <!-- Date Selector (Calendar) -->
              <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('tour_date') }}</label>
                <TourCalendar
                  v-model="selectedDate"
                  :min-date="minDate"
                  :offers="tour?.offers_data || []"
                  :blocks="tour?.blocks_data || []"
                  :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
                  :special-days="tour?.special_days || tour?.availability_data?.specialDays || []"
                  :availability-start="tour?.availability_data?.start || ''"
                  :availability-end="tour?.availability_data?.end || ''"
                />

                <!-- Available Dates Info — only when offers are active -->
                <div v-if="hasOffers && !isDateBlocked && !dateHasOffer && !selectedDate" class="mt-2 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                  <p class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">info</span>
                    {{ t('offers_available') }}
                  </p>
                </div>
              </div>

              <!-- Time Selector -->
              <div class="mb-4">
                <div class="flex items-baseline justify-between gap-2 mb-2">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">{{ t('departure_time') }}</label>
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
                    <option value="">{{ t('select_time') }}</option>
                    <option v-for="time in availableTimes" :key="time.value" :value="time.value">
                      {{ time.label }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Travelers Selector -->
              <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('travelers') }}</label>
                <div class="flex items-center justify-between border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 bg-slate-50 dark:bg-slate-800">
                  <button
                    @click="decrementAdults"
                    type="button"
                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-slate-700 rounded-full hover:bg-slate-100 dark:hover:bg-slate-600 transition border border-slate-200 dark:border-slate-600"
                  >
                    <span class="material-symbols-outlined text-lg">remove</span>
                  </button>
                  <span class="font-bold text-sm">{{ adults }} {{ adults === 1 ? t('adult') : t('adults') }}</span>
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
                  <span class="text-slate-600 dark:text-slate-400">{{ currencyStore.formatConverted(discountedPrice || basePrice || 0) }} x {{ adults }} {{ adults === 1 ? t('adult') : t('adults') }}</span>
                  <span class="font-semibold">{{ currencyStore.formatConverted(total || 0) }}</span>
                </div>
                <div v-if="activeOffer" class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ activeOffer.discountType === 'percentage' ? activeOffer.discount + '% de descuento' : '$' + activeOffer.discount + ' USD descuento' }}</span>
                  <span class="font-semibold text-green-600">-{{ currencyStore.formatConverted((basePrice - discountedPrice) * adults) }}</span>
                </div>
                <div v-if="groupDiscount > 0" class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Group discount</span>
                  <span class="font-semibold text-green-600">-{{ currencyStore.formatConverted(groupDiscount || 0) }}</span>
                </div>
                <div class="flex justify-between text-base font-black border-t border-slate-200 dark:border-slate-700 pt-2">
                  <span>{{ t('total') }}</span>
                  <span class="text-primary">{{ currencyStore.formatConverted(total || 0) }} {{ currencyStore.selectedCurrency }}</span>
                </div>
                <div v-if="currencyStore.isForeignCurrency" class="flex items-start gap-1.5 mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                  <span class="material-symbols-outlined text-amber-500 text-sm mt-0.5">info</span>
                  <span class="text-[11px] text-slate-500 leading-tight">{{ t('payment_usd_notice') }}</span>
                </div>
              </div>

              <!-- CTA Buttons -->
              <button
                @click="handleBooking"
                :disabled="isDateBlocked"
                class="w-full bg-primary hover:bg-primary/90 text-white font-black py-4 rounded-xl transition-all shadow-lg shadow-primary/20 mb-3 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="material-symbols-outlined">{{ isDateBlocked ? 'block' : 'check_circle' }}</span>
                {{ isDateBlocked ? t('date_unavailable') : t('book_now') }}
              </button>


            </div>

          </div>
        </div>
      </div>

      <!-- Related Tours (Full Width) -->
      <section class="mt-20" v-if="relatedTours.length > 0">
        <h2 class="text-2xl font-black mb-8">{{ t('you_might_like') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <NuxtLink
            v-for="relatedTour in relatedTours.slice(0, 4)"
            :key="relatedTour.id"
            :to="`/tours/${relatedTour.slug}`"
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
            <p class="mt-2 font-black text-slate-900 dark:text-white">{{ t('from') }} {{ currencyStore.formatConverted(relatedTour.min_price || 0, false) }}</p>
          </NuxtLink>
        </div>
      </section>
    </main>

    <!-- Mobile Fixed Bottom CTA -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
      <div class="flex items-center justify-between px-4 py-2.5">
        <div>
          <span class="text-[10px] text-slate-400 block leading-none mb-0.5">{{ t('from') }}
            <span v-if="activeOffer && originalPrice > discountedPrice" class="line-through">{{ currencyStore.formatConverted(originalPrice, false) }}</span>
          </span>
          <span class="text-lg font-black text-primary leading-none">{{ currencyStore.formatConverted(discountedPrice || basePrice || 0, false) }}</span>
          <span class="text-[10px] text-slate-400"> /{{ t('per_person_label') }}</span>
        </div>
        <button
          @click="mobileBookingOpen = true"
          class="bg-primary active:bg-primary/80 text-white font-bold py-2.5 px-5 rounded-lg flex items-center gap-1.5 text-xs active:scale-[0.98] transition-transform"
        >
          <span class="material-symbols-outlined text-base">calendar_today</span>
          {{ t('book_now') }}
        </button>
      </div>
    </div>

    <!-- Mobile Booking Drawer -->
    <Teleport to="body">
      <Transition name="drawer">
        <div v-if="mobileBookingOpen" class="lg:hidden fixed inset-0 z-50">
          <div class="absolute inset-0 bg-black/50" @click="mobileBookingOpen = false"></div>
          <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[90vh] shadow-2xl flex flex-col drawer-sheet">
            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-2 flex-shrink-0">
              <div class="w-10 h-1 bg-slate-300 rounded-full"></div>
            </div>

            <!-- Scrollable content -->
            <div class="overflow-y-auto flex-1 px-5 pb-2 space-y-4">
              <!-- Price + Offer -->
              <div>
                <div class="flex items-baseline gap-2">
                  <span v-if="activeOffer && originalPrice > discountedPrice" class="text-base text-slate-400 line-through">{{ currencyStore.formatConverted(originalPrice, false) }}</span>
                  <span class="text-2xl font-black text-primary">{{ currencyStore.formatConverted(discountedPrice || basePrice || 0, false) }}</span>
                  <span class="text-xs text-slate-500">{{ currencyStore.selectedCurrency }} / person</span>
                </div>
                <div v-if="activeOffer" class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 border border-green-200 rounded-lg">
                  <span class="material-symbols-outlined text-green-600 text-sm">local_offer</span>
                  <span class="text-xs font-bold text-green-700">
                    {{ activeOffer.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : `$${activeOffer.discount} OFF` }}
                  </span>
                </div>
              </div>

              <!-- Calendar -->
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('tour_date') }}</label>
                <TourCalendar
                  v-model="selectedDate"
                  :min-date="minDate"
                  :offers="tour?.offers_data || []"
                  :blocks="tour?.blocks_data || []"
                  :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
                  :special-days="tour?.special_days || tour?.availability_data?.specialDays || []"
                  :availability-start="tour?.availability_data?.start || ''"
                  :availability-end="tour?.availability_data?.end || ''"
                />
              </div>

              <!-- Time + Travelers in row -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ t('departure_time') }}</label>
                  <select v-model="selectedTime" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                    <option value="">{{ t('select_time') }}</option>
                    <option v-for="time in availableTimes" :key="time.value" :value="time.value">{{ time.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ t('travelers') }}</label>
                  <div class="flex items-center justify-between border border-slate-200 rounded-xl px-3 py-2 bg-slate-50">
                    <button @click="decrementAdults" type="button" class="w-7 h-7 flex items-center justify-center bg-white rounded-full border border-slate-200">
                      <span class="material-symbols-outlined text-base">remove</span>
                    </button>
                    <span class="font-bold text-sm">{{ adults }}</span>
                    <button @click="incrementAdults" type="button" class="w-7 h-7 flex items-center justify-center bg-white rounded-full border border-slate-200">
                      <span class="material-symbols-outlined text-base">add</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sticky bottom: Total + Book button -->
            <div class="flex-shrink-0 border-t border-slate-100 px-5 pt-3 safe-bottom-drawer">
              <!-- Error -->
              <div v-if="mobileError" class="flex items-center gap-2 px-3 py-2 mb-3 bg-red-50 border border-red-200 rounded-xl">
                <span class="material-symbols-outlined text-red-500 text-sm">error</span>
                <span class="text-xs font-semibold text-red-700">{{ mobileError }}</span>
              </div>

              <div v-if="currencyStore.isForeignCurrency" class="flex items-start gap-1.5 mb-2 px-1">
                <span class="material-symbols-outlined text-amber-500 text-xs mt-0.5">info</span>
                <span class="text-[10px] text-slate-500 leading-tight">{{ t('payment_usd_notice') }}</span>
              </div>

              <div class="flex items-center gap-3 pb-3">
                <div class="flex-shrink-0">
                  <span class="text-[10px] text-slate-400">{{ t('total') }}</span>
                  <div class="text-lg font-black text-primary leading-tight">{{ currencyStore.formatConverted(total || 0) }}</div>
                </div>
                <button
                  @click="mobileHandleBooking"
                  class="flex-1 bg-primary active:bg-primary/80 text-white font-bold py-3 rounded-xl flex items-center justify-center gap-2 text-sm active:scale-[0.98] transition-transform"
                >
                  <span class="material-symbols-outlined text-lg">check_circle</span>
                  {{ t('book_now') }}
                </button>
              </div>
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
      <p class="mt-4 text-slate-600">{{ t('loading_tour') }}</p>
    </div>
  </div>

  <!-- Error State -->
  <div v-else class="min-h-screen flex items-center justify-center bg-background-light dark:bg-background-dark">
    <div class="text-center">
      <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">search_off</span>
      <p class="text-red-600 text-lg mb-4">{{ t('tour_not_found') }}</p>
      <NuxtLink to="/tours" class="text-primary hover:underline font-bold">
        {{ t('view_all_tours') }}
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
const { t } = useI18n()
const currencyStore = useCurrencyStore()

const slug = route.params.slug as string

// Fetch tour data with SSR
const { data: response, pending, error } = await useAsyncData(
  `tour-${slug}`,
  () => api(`/tours/slug/${slug}`)
)

const tour = computed(() => response.value?.data || null)

// Permanent redirect to canonical /{city}/{slug} URL — keeps SEO clean and
// avoids the 404 we get when this URL is loaded directly via Vercel's ISR.
const localePath = useLocalePath()
if (import.meta.server && tour.value?.city?.slug) {
  await navigateTo(
    localePath(`/${tour.value.city.slug}/${slug}`),
    { redirectCode: 301 }
  )
}

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

// Booking widget state
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)
const mobileBookingOpen = ref(false)
const mobileError = ref('')

function mobileHandleBooking() {
  if (!selectedDate.value) { mobileError.value = t('please_select_date'); return }
  if (!selectedTime.value) { mobileError.value = t('please_select_time'); return }
  mobileError.value = ''
  mobileBookingOpen.value = false
  handleBooking()
}

watch(selectedDate, () => { mobileError.value = '' })
watch(selectedTime, () => { mobileError.value = '' })
const isDateBlocked = ref(false)
const blockedReason = ref('')

// Computed - Base price with price_details logic
const basePrice = computed(() => {
  const details = tour.value?.price_details
  if (details && details.length > 0) {
    // Get active prices sorted by min_quantity
    const activePrices = details
      .filter((p: any) => p.active)
      .sort((a: any, b: any) => (a.min_quantity || 1) - (b.min_quantity || 1))

    if (activePrices.length > 0) {
      // Find the price range that matches the current number of adults
      const matchingPrice = activePrices.find((p: any) => {
        const min = p.min_quantity || 1
        const max = p.max_quantity || Infinity
        return adults.value >= min && adults.value <= max
      })

      if (matchingPrice) {
        return parseFloat(matchingPrice.price || 0)
      }

      // If adults exceed max range, use the last (highest quantity) price
      const lastPrice = activePrices[activePrices.length - 1]
      if (adults.value > (lastPrice.max_quantity || 0)) {
        return parseFloat(lastPrice.price || 0)
      }

      // Fallback: lowest price
      return Math.min(...activePrices.map((p: any) => parseFloat(p.price || 0)))
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

const total = computed(() => (discountedPrice.value || basePrice.value) * adults.value - groupDiscount.value)

const currency = computed(() => tour.value?.currency || 'USD')

// Availability data
const availabilityData = computed(() => tour.value?.availability_data || {})

// Check for active offers based on selected date
const activeOffer = computed(() => {
  if (!availabilityData.value?.offers || availabilityData.value.offers.length === 0) return null

  // Use selected date if available, otherwise use today
  const checkDate = selectedDate.value ? new Date(selectedDate.value) : new Date()

  const activeOffers = availabilityData.value.offers.filter((offer: any) => {
    const start = new Date(offer.startDate)
    const end = new Date(offer.endDate)
    return checkDate >= start && checkDate <= end
  })

  // Return the offer with the highest discount
  return activeOffers.reduce((best: any, current: any) => {
    if (!best) return current
    const bestValue = best.discountType === 'percentage' ? best.discount : (best.discount / basePrice.value * 100)
    const currentValue = current.discountType === 'percentage' ? current.discount : (current.discount / basePrice.value * 100)
    return currentValue > bestValue ? current : best
  }, null)
})

// Calculate prices with discount
const originalPrice = computed(() => basePrice.value)

const discountedPrice = computed(() => {
  if (!activeOffer.value) return basePrice.value

  if (activeOffer.value.discountType === 'percentage') {
    return basePrice.value * (1 - activeOffer.value.discount / 100)
  } else {
    return Math.max(0, basePrice.value - activeOffer.value.discount)
  }
})

// Check if there are blocked dates
const hasBlockedDates = computed(() => {
  return availabilityData.value?.blocks && availabilityData.value.blocks.length > 0
})

// Check if there are offers that are still valid (not expired)
const hasOffers = computed(() => {
  const offers = availabilityData.value?.offers
  if (!Array.isArray(offers) || offers.length === 0) return false

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return offers.some((offer: any) => {
    if (!offer?.endDate) return false
    const end = new Date(offer.endDate)
    end.setHours(23, 59, 59, 999)
    return end >= today
  })
})

// Check if selected date has an offer
const dateHasOffer = computed(() => {
  return activeOffer.value !== null
})

// Calculate max date (1 year from now)
const maxDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() + 1)
  return date.toISOString().split('T')[0]
})

// Format offer date
const formatOfferDate = (dateStr: string) => {
  const [year, month, day] = dateStr.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long' })
}

// Handle date change
const onDateChange = () => {
  checkDateAvailability()
}

// Check if selected date is blocked
const checkDateAvailability = () => {
  if (!selectedDate.value || !availabilityData.value?.blocks) {
    isDateBlocked.value = false
    blockedReason.value = ''
    return
  }

  const selected = new Date(selectedDate.value)

  // Check if day of week is active
  const dayOfWeek = selected.getDay()
  if (availabilityData.value.activeDays && !availabilityData.value.activeDays.includes(dayOfWeek)) {
    isDateBlocked.value = true
    blockedReason.value = 'Este día de la semana no está disponible'
    return
  }

  // Check if date is in blocked ranges
  const blocked = availabilityData.value.blocks.find((block: any) => {
    const start = new Date(block.startDate)
    const end = new Date(block.endDate)
    return selected >= start && selected <= end
  })

  if (blocked) {
    isDateBlocked.value = true
    blockedReason.value = blocked.reason || 'Esta fecha no está disponible'
  } else {
    isDateBlocked.value = false
    blockedReason.value = ''
  }
}

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
  const qty = tour.value.duration_quantity
  const unit = tour.value.duration_unit
  if (qty && unit) {
    if (unit === 'hours') return `${qty}h`
    if (unit === 'days') return `${qty} day${qty > 1 ? 's' : ''}`
    if (unit === 'minutes') return `${qty} min`
  }
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

function handleBooking() {
  console.log('=== handleBooking called from [slug].vue ===')
  
  // Validate required fields
  if (!selectedDate.value) {
    alert(t('please_select_date'))
    return
  }

  if (!selectedTime.value) {
    alert(t('please_select_time'))
    return
  }

  console.log('Validation passed, preparing to add to cart...')

  // Get tour image
  const tourImage = tour.value?.media_gallery && tour.value.media_gallery.length > 0
    ? getImageUrl(tour.value.media_gallery[0].url)
    : ''

  // Calculate total with discount if applicable
  const pricePerPerson = discountedPrice.value || basePrice.value
  const totalPrice = pricePerPerson * adults.value

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
    basePrice: pricePerPerson,
    originalPrice: basePrice.value,
    childPrice: 0,
    total: totalPrice,
    currency: tour.value?.currency || 'USD',
    policies: tour.value?.policies || '',
    cancellationPolicy: tour.value?.cancellation_policy || '',
    taxPercentage: tour.value?.tax_percentage || 0,
    advancePaymentPercentage: tour.value?.advance_payment_percentage || 100,
    // Include offer information if applicable
    hasOffer: activeOffer.value !== null,
    offerDiscount: activeOffer.value?.discount || 0,
    offerDiscountType: activeOffer.value?.discountType || '',
    offerColor: activeOffer.value?.color || '',
    guideType: tour.value?.guide_type || '',
    guideLanguages: getGuideLanguageNames(tour.value?.guide_languages || []),
    durationLabel: durationLabel.value,
  }

  console.log('Adding to cart:', cartItem)
  cartStore.addItem(cartItem)
  console.log('Cart items after add:', cartStore.items.length)

  // Navigate to checkout
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

// Favorite state
const isFavorite = ref(false)

function toggleFavorite() {
  isFavorite.value = !isFavorite.value
}

async function shareTour() {
  const url = window.location.href
  if (navigator.share) {
    try {
      await navigator.share({ title: tour.value?.title, url })
    } catch {}
  } else {
    await navigator.clipboard.writeText(url)
  }
}

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

function formatDurationShort(tour: any) {
  if (tour.duration_quantity && tour.duration_unit) {
    if (tour.duration_unit === 'hours') return `${tour.duration_quantity}H`
    if (tour.duration_unit === 'days') return `${tour.duration_quantity}D`
    if (tour.duration_unit === 'minutes') return `${tour.duration_quantity}min`
  }
  if (tour.duration_days > 0) return `${tour.duration_days}D`
  if (tour.duration_hours > 0) return `${tour.duration_hours}H`
  return ''
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

.safe-bottom-drawer {
  padding-bottom: max(env(safe-area-inset-bottom, 0px), 0.75rem);
}

/* Drawer sheet slide-up animation */
.drawer-sheet {
  transform: translateY(0);
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

.drawer-enter-active { transition: opacity 0.3s ease; }
.drawer-leave-active { transition: opacity 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from .drawer-sheet { transform: translateY(100%); }
.drawer-leave-to .drawer-sheet { transform: translateY(100%); }
</style>
