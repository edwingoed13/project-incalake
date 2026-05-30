<template>
  <div v-if="tour" class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 md:pt-28 pb-24 lg:pb-8">

      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" class="mb-3 lg:mb-4">
        <ol class="flex items-center gap-1 text-xs text-slate-500 overflow-x-auto whitespace-nowrap">
          <li>
            <NuxtLink :to="localePath('/')" class="hover:text-primary transition-colors">
              {{ t('home') || 'Home' }}
            </NuxtLink>
          </li>
          <li class="text-slate-300" aria-hidden="true">/</li>
          <li>
            <NuxtLink :to="localePath(`/tours?city=${tour.city?.slug || route.params.city}`)" class="hover:text-primary transition-colors">
              {{ cityLabel(tour) }}
            </NuxtLink>
          </li>
          <li class="text-slate-300" aria-hidden="true">/</li>
          <li class="text-slate-700 dark:text-slate-200 font-medium truncate" aria-current="page">
            {{ tour.title }}
          </li>
        </ol>
      </nav>

      <!-- Trust badges row (OTA-style — visible immediately above title) -->
      <div class="flex flex-wrap items-center gap-2 mb-3">
        <span
          v-if="(tourReviews.length || 0) >= 20"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-bestseller/10 text-bestseller text-xs font-bold uppercase tracking-wide"
        >
          <BookmarkSolidIcon class="size-3.5" aria-hidden="true" />
          Más vendido
        </span>
        <span v-if="tour.free_cancellation" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-trust-soft text-trust text-xs font-bold">
          <CheckCircleSolidIcon class="size-3.5" aria-hidden="true" />
          Cancelación gratuita
        </span>
        <span
          v-if="tour.capacity && tour.cupos != null && tour.cupos / Math.max(tour.capacity, 1) < 0.3"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-urgency-soft text-urgency text-xs font-bold"
        >
          <FireSolidIcon class="size-3.5" aria-hidden="true" />
          Pocos cupos
        </span>
      </div>

      <!-- Title & Basic Info — OTA-style: title huge & bold, meta dense -->
      <div class="flex flex-col lg:flex-row justify-between gap-4 lg:gap-6 mb-6 lg:mb-8">
        <div class="flex-1 min-w-0">
          <h1 class="text-[22px] sm:text-[26px] md:text-3xl lg:text-4xl font-extrabold leading-[1.15] tracking-tight mb-3 text-slate-900 dark:text-white">
            {{ tour.title }}
          </h1>
          <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px] sm:text-[15px]">
            <!-- Rating -->
            <button
              v-if="tourReviews.length > 0"
              type="button"
              @click="scrollToReviews"
              class="inline-flex items-center gap-1 font-bold text-slate-900 dark:text-white hover:text-primary transition-colors"
            >
              <StarSolidIcon class="size-4 text-rating" aria-hidden="true" />
              <span class="tabular-nums">{{ avgRating }}</span>
              <span class="text-slate-500 underline-offset-2 hover:underline">({{ tourReviews.length }})</span>
            </button>
            <span v-if="tourReviews.length > 0" class="text-slate-300" aria-hidden="true">•</span>
            <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
              <MapPinIcon class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
              {{ cityLabel(tour) }}, Perú
            </span>
            <span class="text-slate-300" aria-hidden="true">•</span>
            <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
              <ClockIcon class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
              {{ formatDuration(tour) }}
            </span>
          </div>
        </div>
        <div class="hidden md:flex gap-2 items-start shrink-0">
          <button
            @click="openShare"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] min-w-[44px] px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg font-semibold text-sm transition-colors"
            aria-label="Compartir tour"
          >
            <ShareIcon class="size-5" aria-hidden="true" />
            <span class="hidden sm:inline">Compartir</span>
          </button>
          <button
            @click="toggleSave"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] min-w-[44px] px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg font-semibold text-sm transition-all active:scale-95"
            :class="isSaved ? 'text-red-500' : 'text-slate-700 dark:text-slate-200'"
            :aria-label="isSaved ? 'Quitar de guardados' : 'Guardar tour'"
            :aria-pressed="isSaved"
          >
            <HeartSolidIcon v-if="isSaved" class="size-5" aria-hidden="true" />
            <HeartIcon v-else class="size-5" aria-hidden="true" />
            <span class="hidden sm:inline">{{ isSaved ? 'Guardado' : 'Guardar' }}</span>
          </button>
        </div>
      </div>

      <!-- Two Column Layout: Left Content | Right Booking Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-8">
        <!-- Left Column: Multimedia + Content -->
        <div class="space-y-6 lg:space-y-10">
          <!-- Multimedia Gallery -->
          <div class="relative">
            <TourMediaGallery :tour="tour" />
            <!-- Mobile: share + save overlaid on the gallery image to save space -->
            <div class="md:hidden absolute top-3 right-3 z-20 flex gap-2">
              <button
                @click="openShare"
                type="button"
                class="size-9 rounded-full bg-black/45 backdrop-blur-sm text-white flex items-center justify-center active:scale-90 transition-transform"
                aria-label="Compartir tour"
              >
                <ShareIcon class="size-5" aria-hidden="true" />
              </button>
              <button
                @click="toggleSave"
                type="button"
                class="size-9 rounded-full bg-black/45 backdrop-blur-sm flex items-center justify-center active:scale-90 transition-transform"
                :class="isSaved ? 'text-red-500' : 'text-white'"
                :aria-label="isSaved ? 'Quitar de guardados' : 'Guardar tour'"
                :aria-pressed="isSaved"
              >
                <HeartSolidIcon v-if="isSaved" class="size-5" aria-hidden="true" />
                <HeartIcon v-else class="size-5" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Inline Mobile Booking Panel — appears after gallery so price/date are
               visible without scrolling to the bottom. Hidden on lg+ where the
               sticky right-column widget takes over. -->
          <section ref="mobileBookingRef" class="lg:hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-md">
            <!-- Price header (OTA dominant) -->
            <div class="px-4 sm:px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
              <div class="flex items-end justify-between gap-3 flex-wrap">
                <div>
                  <div class="flex items-baseline gap-2">
                    <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums tracking-tight">
                      {{ currencyStore.formatConverted(basePrice || 0) }}
                    </span>
                    <span class="text-xs font-semibold text-slate-500">{{ currencyStore.selectedCurrency }} / persona</span>
                  </div>
                </div>
                <span v-if="activeOffer" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-trust-soft text-trust text-xs font-bold">
                  <TagIcon class="size-3.5" aria-hidden="true" />
                  {{ activeOffer.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : `$${activeOffer.discount} OFF` }}
                </span>
              </div>
            </div>

            <div class="p-4 space-y-3">
              <!-- Calendar -->
              <div>
                <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                  <CalendarDaysIcon class="size-4 text-primary" aria-hidden="true" />
                  Fecha
                </label>
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

              <!-- Time -->
              <div>
                <div class="flex items-baseline justify-between gap-1 mb-2 flex-wrap">
                  <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    <ClockIcon class="size-4 text-primary" aria-hidden="true" />
                    Horario
                  </label>
                  <span v-if="tzInfo" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500" :title="`${tzInfo.name} (${tzInfo.gmt})`">
                    <GlobeAltIcon class="size-3" aria-hidden="true" />
                    {{ tzInfo.code }} {{ tzInfo.gmt }}
                  </span>
                </div>
                <TourTimeSelect v-model="selectedTime" :options="availableTimes" placeholder="Selecciona horario" />
              </div>

              <!-- Travelers -->
              <div>
                <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                  <UsersIcon class="size-4 text-primary" aria-hidden="true" />
                  Viajeros
                </label>
                <div class="space-y-2">
                  <!-- Adults -->
                  <div class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2 bg-white">
                    <div class="leading-tight">
                      <span class="font-bold text-sm text-slate-800">Adultos</span>
                      <span class="block text-[11px] text-slate-500">{{ currencyStore.formatConverted(adultPrice || 0) }} c/u</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <button @click="decrementAdults" type="button" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-full hover:bg-slate-200 disabled:opacity-40" :disabled="adults <= 1" aria-label="Quitar adulto">
                        <MinusIcon class="size-4" aria-hidden="true" />
                      </button>
                      <span class="w-6 text-center font-bold text-sm tabular-nums">{{ adults }}</span>
                      <button @click="incrementAdults" type="button" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-full hover:bg-slate-200 disabled:opacity-40" :disabled="totalPax >= maxPax" aria-label="Agregar adulto">
                        <PlusIcon class="size-4" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                  <!-- Children (only when the tour has child pricing) -->
                  <div v-if="hasChildPricing" class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2 bg-white">
                    <div class="leading-tight">
                      <span class="font-bold text-sm text-slate-800">Niños</span>
                      <span class="block text-[11px] text-slate-500">{{ children > 0 ? `${currencyStore.formatConverted(childPrice || 0)} c/u` : 'Opcional' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <button @click="decrementChildren" type="button" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-full hover:bg-slate-200 disabled:opacity-40" :disabled="children <= 0" aria-label="Quitar niño">
                        <MinusIcon class="size-4" aria-hidden="true" />
                      </button>
                      <span class="w-6 text-center font-bold text-sm tabular-nums">{{ children }}</span>
                      <button @click="incrementChildren" type="button" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-full hover:bg-slate-200 disabled:opacity-40" :disabled="totalPax >= maxPax" aria-label="Agregar niño">
                        <PlusIcon class="size-4" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Price breakdown + total -->
              <div class="pt-3 border-t border-slate-100 space-y-1.5">
                <div class="flex justify-between text-xs text-slate-600">
                  <span>{{ currencyStore.formatConverted(adultPrice || 0) }} × {{ adults }} {{ adults === 1 ? 'adulto' : 'adultos' }}</span>
                  <span class="tabular-nums font-medium">{{ currencyStore.formatConverted(adultPrice * adults || 0) }}</span>
                </div>
                <div v-if="hasChildPricing && children > 0" class="flex justify-between text-xs text-slate-600">
                  <span>{{ currencyStore.formatConverted(childPrice || 0) }} × {{ children }} {{ children === 1 ? 'niño' : 'niños' }}</span>
                  <span class="tabular-nums font-medium">{{ currencyStore.formatConverted(childPrice * children || 0) }}</span>
                </div>
                <div v-if="groupDiscount > 0" class="flex justify-between text-xs">
                  <span class="text-trust font-bold inline-flex items-center gap-1"><TagIcon class="size-3" aria-hidden="true" />Descuento</span>
                  <span class="text-trust font-bold tabular-nums">−{{ currencyStore.formatConverted(groupDiscount || 0) }}</span>
                </div>
                <div class="flex justify-between items-baseline pt-1.5 border-t border-slate-100">
                  <span class="text-sm font-bold text-slate-800">Total</span>
                  <span class="text-xl font-black text-slate-900 dark:text-white tabular-nums">
                    {{ currencyStore.formatConverted(total || 0) }}
                    <span class="text-xs font-semibold text-slate-500 ml-0.5">{{ currencyStore.selectedCurrency }}</span>
                  </span>
                </div>
              </div>

              <!-- Validation error -->
              <div v-if="mobileError" class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                <ExclamationCircleIcon class="size-4 text-red-500" aria-hidden="true" />
                <span class="text-xs font-semibold text-red-700">{{ mobileError }}</span>
              </div>

              <!-- CTA — OTA-style big and prominent -->
              <button
                @click="mobileHandleBooking"
                class="w-full min-h-[56px] bg-primary hover:bg-primary-dark text-white font-extrabold py-4 rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-[0.98] inline-flex items-center justify-center gap-2 tracking-wide"
              >
                RESERVAR AHORA
                <ArrowRightIcon class="size-5" aria-hidden="true" />
              </button>
              <button
                @click="handleAddToCart"
                class="w-full mt-2 min-h-[48px] bg-white dark:bg-slate-800 border-2 border-primary text-primary font-bold py-3 rounded-xl transition-all active:scale-[0.98] inline-flex items-center justify-center gap-2"
              >
                <ShoppingCartIcon class="size-5" aria-hidden="true" />
                Agregar al carrito
              </button>
              <div v-if="addedToCart" class="mt-1.5 flex items-center justify-center gap-1 text-xs font-semibold text-trust">
                <CheckCircleSolidIcon class="size-4" aria-hidden="true" />
                Agregado al carrito — puedes seguir navegando
              </div>

              <!-- Trust signals (compact 3-row stack) -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                <div v-if="tour.free_cancellation" class="flex items-center gap-1.5 text-xs">
                  <CheckCircleSolidIcon class="size-4 text-trust shrink-0" aria-hidden="true" />
                  <span class="text-slate-600 font-medium">Cancelación gratuita</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs">
                  <ClockIcon class="size-4 text-primary shrink-0" aria-hidden="true" />
                  <span class="text-slate-600 font-medium">Confirmación instantánea</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs">
                  <ShieldCheckIcon class="size-4 text-primary shrink-0" aria-hidden="true" />
                  <span class="text-slate-600 font-medium">Mejor precio</span>
                </div>
              </div>
            </div>
          </section>

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

          <!-- Custom additional sections (admin Step 3) -->
          <section v-if="customSections.length" class="space-y-6">
            <div v-for="section in customSections" :key="section.id || section.title" class="space-y-3">
              <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white">{{ section.title }}</h3>
              <div class="prose prose-sm md:prose-base max-w-none dark:prose-invert" v-html="sanitizeHtml(section.content)"></div>
            </div>
          </section>

          <!-- Tags chips -->
          <section v-if="tour.tags && tour.tags.length" class="space-y-3">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">{{ t('tags') || 'Etiquetas' }}</h3>
            <div class="flex flex-wrap gap-2">
              <NuxtLink
                v-for="tag in tour.tags"
                :key="tag.id"
                :to="localePath(`/tours?tag=${tag.slug}`)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/40 text-violet-700 dark:text-violet-300 text-xs font-bold hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors"
              >
                <TagIcon class="size-3.5" aria-hidden="true" />
                {{ tag.name }}
              </NuxtLink>
            </div>
          </section>

          <!-- Location Map -->
          <TourLocation :tour="tour" />

          <hr class="border-slate-200 dark:border-slate-800" />

          <!-- Reviews Section -->
          <section id="reviews" class="scroll-mt-24">
            <h3 class="text-xl font-bold mb-3 md:mb-4">{{ t('customer_reviews') }}</h3>

            <!-- Rating summary -->
            <div v-if="tourReviews.length > 0" class="flex items-center gap-3 mb-5 md:mb-6">
              <span class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tabular-nums leading-none">{{ avgRating }}</span>
              <div class="min-w-0">
                <div class="flex">
                  <StarSolidIcon v-for="i in 5" :key="i" class="size-4 md:size-5" :class="i <= Math.round(avgRating) ? 'text-rating' : 'text-slate-300 dark:text-slate-600'" aria-hidden="true" />
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">
                  <span v-if="ratingLabel" class="font-bold text-slate-700 dark:text-slate-300">{{ ratingLabel }}</span>
                  <span v-if="ratingLabel"> · </span>{{ tourReviews.length }} {{ tourReviews.length === 1 ? 'opinión' : 'opiniones' }}
                </p>
              </div>
            </div>

            <div v-if="tourReviews.length > 0">
              <!-- Mobile: swipeable review cards · Desktop: stacked list -->
              <div class="flex md:block gap-3 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 md:space-y-6 pb-1 md:pb-0">
                <div
                  v-for="review in tourReviews.slice(0, showAllReviews ? tourReviews.length : 3)"
                  :key="review.id"
                  class="shrink-0 w-[85%] sm:w-[55%] md:w-auto snap-start bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-4 md:bg-transparent md:dark:bg-transparent md:rounded-none md:p-0 md:border-b md:border-slate-100 md:dark:border-slate-800 md:pb-6"
                >
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary text-sm shrink-0">
                      {{ getInitials(review.name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="text-[15px] font-bold truncate">{{ review.name }}</h4>
                        <span class="text-xs text-slate-400 shrink-0">{{ review.review_date }}</span>
                      </div>
                      <div class="flex items-center gap-0.5 mt-0.5">
                        <StarSolidIcon v-for="i in review.rating" :key="i" class="size-3 text-rating" aria-hidden="true" />
                      </div>
                    </div>
                  </div>
                  <p v-if="review.title" class="text-[15px] font-semibold text-slate-800 dark:text-slate-200 mb-1">{{ review.title }}</p>
                  <p class="text-[15px] text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-5 md:line-clamp-none">{{ review.comment }}</p>
                </div>
              </div>

              <button
                v-if="tourReviews.length > 3"
                @click="showAllReviews = !showAllReviews"
                class="mt-4 md:mt-6 font-bold text-primary hover:underline text-sm inline-flex items-center gap-1"
              >
                {{ showAllReviews ? t('show_less') : t('view_all_reviews', { count: tourReviews.length }) }}
                <ChevronDownIcon class="size-4 transition-transform" :class="{ 'rotate-180': showAllReviews }" aria-hidden="true" />
              </button>
            </div>

            <div v-else class="py-8 text-center text-slate-400 bg-slate-50 dark:bg-slate-800/40 rounded-2xl">
              <ChatBubbleLeftRightIcon class="size-8 mb-2 mx-auto" aria-hidden="true" />
              <p class="text-sm font-medium">{{ t('no_reviews') }}</p>
            </div>
          </section>
        </div>

        <!-- Right Column: Booking Widget - Sticky (OTA-style) -->
        <div class="hidden lg:block">
          <div class="sticky top-24 space-y-3">
            <!-- Booking Widget Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-md">
              <!-- Price Header — dominant, OTA pattern -->
              <div class="px-4 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-baseline gap-1.5 flex-wrap">
                  <span class="text-[32px] leading-none font-black text-slate-900 dark:text-white tabular-nums tracking-tight">
                    {{ currencyStore.formatConverted(basePrice || 0) }}
                  </span>
                  <span class="text-sm font-semibold text-slate-500">{{ currencyStore.selectedCurrency }}</span>
                  <span class="text-[11px] text-slate-500 font-medium">por persona</span>
                </div>
              </div>

              <div class="p-4 space-y-3">
                <!-- Date Selector -->
                <div>
                  <label class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    <span class="inline-flex items-center gap-1.5">
                      <CalendarDaysIcon class="size-4 text-primary" aria-hidden="true" />
                      Fecha
                    </span>
                  </label>
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
                  <div v-if="activeOffer" class="mt-2 flex items-center gap-1.5 px-2.5 py-1.5 rounded-md bg-trust-soft text-trust">
                    <TagIcon class="size-3.5" aria-hidden="true" />
                    <span class="text-xs font-bold">
                      {{ activeOffer.discountType === 'percentage' ? `${activeOffer.discount}% OFF` : `$${activeOffer.discount} OFF` }}
                    </span>
                  </div>
                </div>

                <!-- Time Selector -->
                <div>
                  <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                    <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                      <ClockIcon class="size-4 text-primary" aria-hidden="true" />
                      Horario
                    </label>
                    <span v-if="tzInfo" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500" :title="`${tzInfo.name} (${tzInfo.gmt})`">
                      <GlobeAltIcon class="size-3" aria-hidden="true" />
                      {{ tzInfo.code }} {{ tzInfo.gmt }}
                    </span>
                  </div>
                  <TourTimeSelect v-model="selectedTime" :options="availableTimes" placeholder="Selecciona horario" />
                </div>

                <!-- Travelers -->
                <div>
                  <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    <UsersIcon class="size-4 text-primary" aria-hidden="true" />
                    Viajeros
                  </label>
                  <div class="space-y-2">
                    <!-- Adults -->
                    <div class="flex items-center justify-between border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800">
                      <div class="leading-tight">
                        <span class="font-bold text-sm text-slate-800 dark:text-slate-100">Adultos</span>
                        <span class="block text-[11px] text-slate-500">{{ currencyStore.formatConverted(adultPrice || 0) }} c/u</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <button
                          @click="decrementAdults"
                          type="button"
                          class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 transition disabled:opacity-40"
                          :disabled="adults <= 1"
                          aria-label="Quitar adulto"
                        >
                          <MinusIcon class="size-4" aria-hidden="true" />
                        </button>
                        <span class="w-6 text-center font-bold text-sm tabular-nums">{{ adults }}</span>
                        <button
                          @click="incrementAdults"
                          type="button"
                          class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 transition disabled:opacity-40"
                          :disabled="totalPax >= maxPax"
                          aria-label="Agregar adulto"
                        >
                          <PlusIcon class="size-4" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                    <!-- Children (only when the tour has child pricing) -->
                    <div v-if="hasChildPricing" class="flex items-center justify-between border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800">
                      <div class="leading-tight">
                        <span class="font-bold text-sm text-slate-800 dark:text-slate-100">Niños</span>
                        <span class="block text-[11px] text-slate-500">{{ children > 0 ? `${currencyStore.formatConverted(childPrice || 0)} c/u` : 'Opcional' }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <button
                          @click="decrementChildren"
                          type="button"
                          class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 transition disabled:opacity-40"
                          :disabled="children <= 0"
                          aria-label="Quitar niño"
                        >
                          <MinusIcon class="size-4" aria-hidden="true" />
                        </button>
                        <span class="w-6 text-center font-bold text-sm tabular-nums">{{ children }}</span>
                        <button
                          @click="incrementChildren"
                          type="button"
                          class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 transition disabled:opacity-40"
                          :disabled="totalPax >= maxPax"
                          aria-label="Agregar niño"
                        >
                          <PlusIcon class="size-4" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Compact total -->
                <div v-if="adults" class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3 space-y-1.5">
                  <div class="flex justify-between text-xs text-slate-600 dark:text-slate-400">
                    <span>{{ currencyStore.formatConverted(adultPrice || 0) }} × {{ adults }} {{ adults === 1 ? 'adulto' : 'adultos' }}</span>
                    <span class="tabular-nums font-medium">{{ currencyStore.formatConverted(adultPrice * adults || 0) }}</span>
                  </div>
                  <div v-if="hasChildPricing && children > 0" class="flex justify-between text-xs text-slate-600 dark:text-slate-400">
                    <span>{{ currencyStore.formatConverted(childPrice || 0) }} × {{ children }} {{ children === 1 ? 'niño' : 'niños' }}</span>
                    <span class="tabular-nums font-medium">{{ currencyStore.formatConverted(childPrice * children || 0) }}</span>
                  </div>
                  <div v-if="groupDiscount > 0" class="flex justify-between text-xs">
                    <span class="text-trust font-bold inline-flex items-center gap-1">
                      <TagIcon class="size-3" aria-hidden="true" />
                      Descuento
                    </span>
                    <span class="text-trust font-bold tabular-nums">−{{ currencyStore.formatConverted(groupDiscount || 0) }}</span>
                  </div>
                  <div class="flex justify-between items-baseline pt-1.5 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-sm font-bold">Total</span>
                    <span class="text-xl font-black text-slate-900 dark:text-white tabular-nums">
                      {{ currencyStore.formatConverted(total || 0) }}
                      <span class="text-xs font-semibold text-slate-500 ml-0.5">{{ currencyStore.selectedCurrency }}</span>
                    </span>
                  </div>
                </div>

                <!-- CTA — bigger, bolder, shadow -->
                <button
                  @click="handleBooking"
                  class="w-full min-h-[56px] bg-primary hover:bg-primary-dark text-white font-extrabold text-base py-4 rounded-xl shadow-lg shadow-primary/20 transition-all hover:shadow-xl hover:shadow-primary/30 active:scale-[0.98] inline-flex items-center justify-center gap-2 tracking-wide"
                >
                  RESERVAR AHORA
                  <ArrowRightIcon class="size-5" aria-hidden="true" />
                </button>
                <button
                  @click="handleAddToCart"
                  class="w-full mt-2 min-h-[48px] bg-white dark:bg-slate-800 border-2 border-primary text-primary font-bold py-3 rounded-xl transition-all active:scale-[0.98] inline-flex items-center justify-center gap-2"
                >
                  <ShoppingCartIcon class="size-5" aria-hidden="true" />
                  Agregar al carrito
                </button>
                <div v-if="addedToCart" class="mt-1.5 flex items-center justify-center gap-1 text-xs font-semibold text-trust">
                  <CheckCircleSolidIcon class="size-4" aria-hidden="true" />
                  Agregado al carrito — puedes seguir navegando
                </div>
              </div>
            </div>

            <!-- Trust signals card — OTA pattern: stacks below booking widget -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 space-y-2.5">
              <div v-if="tour.free_cancellation" class="flex items-start gap-2.5">
                <CheckCircleSolidIcon class="size-5 text-trust shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">Cancelación gratuita</p>
                  <p class="text-xs text-slate-500">Hasta 24h antes del tour</p>
                </div>
              </div>
              <div class="flex items-start gap-2.5">
                <ClockIcon class="size-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">Confirmación inmediata</p>
                  <p class="text-xs text-slate-500">Recibe tu reserva al instante</p>
                </div>
              </div>
              <div class="flex items-start gap-2.5">
                <ShieldCheckIcon class="size-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">Mejor precio garantizado</p>
                  <p class="text-xs text-slate-500">Operador oficial autorizado</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ (derived from tour data — visible answers match the FAQPage JSON-LD) -->
      <section v-if="faqItems.length" class="mt-16 md:mt-20">
        <h2 class="text-xl md:text-2xl font-black mb-5 md:mb-8">{{ faqL.title }}</h2>
        <div class="space-y-3 max-w-3xl">
          <details
            v-for="(item, i) in faqItems"
            :key="i"
            class="group rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden"
          >
            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none [&::-webkit-details-marker]:hidden px-4 md:px-5 py-4 font-bold text-slate-900 dark:text-white">
              <span>{{ item.q }}</span>
              <ChevronDownIcon class="size-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true" />
            </summary>
            <div class="px-4 md:px-5 pb-4 -mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
              {{ item.a }}
            </div>
          </details>
        </div>
      </section>

      <!-- Related Tours (Full Width) -->
      <section class="mt-16 md:mt-20" v-if="relatedTours.length > 0">
        <h2 class="text-xl md:text-2xl font-black mb-5 md:mb-8">{{ t('you_might_like') }}</h2>
        <div class="flex md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0">
          <NuxtLink
            v-for="relatedTour in relatedTours.slice(0, 4)"
            :key="relatedTour.id"
            :to="`/${locale}/${relatedTour.city?.slug || 'puno'}/${relatedTour.slug}`"
            class="group cursor-pointer shrink-0 w-[75%] sm:w-[48%] md:w-auto snap-start"
          >
            <div class="aspect-[4/3] rounded-xl overflow-hidden mb-3 relative bg-slate-100">
              <NuxtImg
                :src="getImageUrl(relatedTour.featured_image)"
                :alt="relatedTour.title"
                format="webp"
                width="400"
                height="300"
                densities="x1"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <button
                @click.stop.prevent="toggleSaveRelated(relatedTour)"
                type="button"
                class="absolute top-3 right-3 p-1.5 rounded-full bg-white/20 backdrop-blur-md transition-all active:scale-90"
                :class="wishlistStore.has(relatedTour.id) ? 'text-red-500 bg-white/80' : 'text-white'"
                :aria-label="wishlistStore.has(relatedTour.id) ? 'Quitar de guardados' : 'Guardar tour'"
                :aria-pressed="wishlistStore.has(relatedTour.id)"
              >
                <HeartSolidIcon v-if="wishlistStore.has(relatedTour.id)" class="size-5" aria-hidden="true" />
                <HeartIcon v-else class="size-5" aria-hidden="true" />
              </button>
            </div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ cityLabel(relatedTour) }}</p>
            <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">{{ relatedTour.title }}</h4>
            <!-- Rating shown only when there are real reviews (no fabricated 4.5) -->
            <div v-if="relatedTour.reviews_count > 0 && relatedTour.rating" class="flex items-center gap-1 mt-1">
              <StarSolidIcon class="size-3 text-yellow-500" aria-hidden="true" />
              <span class="text-sm font-bold">{{ Number(relatedTour.rating).toFixed(1) }}</span>
              <span class="text-xs text-slate-500">({{ relatedTour.reviews_count }})</span>
            </div>
            <p class="mt-2 font-black text-slate-900 dark:text-white">{{ t('from') }} {{ currencyStore.formatConverted(relatedTour.min_price || 0) }}</p>
          </NuxtLink>
        </div>
      </section>
    </main>

    <!-- Mobile Fixed Bottom CTA (OTA-style sticky bar) — only once the inline
         booking panel has scrolled out of view, so the price isn't duplicated
         at the top of the page. -->
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-y-full"
      enter-to-class="translate-y-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-y-0"
      leave-to-class="translate-y-full"
    >
    <div v-show="showStickyBar" class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(0,0,0,0.06)] z-40">
      <div class="flex items-center justify-between gap-3">
        <div class="leading-tight shrink-0">
          <div class="text-2xl font-black text-slate-900 dark:text-white tabular-nums leading-none">
            {{ currencyStore.formatConverted(basePrice || 0) }}
          </div>
          <span class="text-[11px] text-slate-500">por persona</span>
        </div>
        <button
          @click="onMobileBottomCta"
          class="flex-1 min-h-[52px] bg-primary hover:bg-primary-dark text-white font-extrabold py-3 px-5 rounded-xl shadow-md shadow-primary/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2 tracking-wide text-sm"
        >
          {{ selectedDate && selectedTime ? 'RESERVAR' : 'VER FECHAS' }}
          <ArrowRightIcon class="size-4" aria-hidden="true" />
        </button>
      </div>
    </div>
    </Transition>

    <ShareModal :open="shareOpen" :url="shareUrl" :title="tour?.title" @close="shareOpen = false" />

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
      <MagnifyingGlassIcon class="size-16 text-slate-300 mb-4 mx-auto" aria-hidden="true" />
      <p class="text-red-600 text-lg mb-4">{{ t('tour_not_found') }}</p>
      <NuxtLink :to="localePath('/tours')" class="text-primary hover:underline font-bold">
        {{ t('view_all_tours') }}
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  MapPinIcon,
  ClockIcon,
  ShareIcon,
  HeartIcon,
  TagIcon,
  GlobeAltIcon,
  MinusIcon,
  PlusIcon,
  CalendarDaysIcon,
  ChevronDownIcon,
  ChatBubbleLeftRightIcon,
  ExclamationCircleIcon,
  MagnifyingGlassIcon,
  UsersIcon,
  ArrowRightIcon,
  ShieldCheckIcon,
  ShoppingCartIcon,
} from '@heroicons/vue/24/outline'
import {
  StarIcon as StarSolidIcon,
  CheckCircleIcon as CheckCircleSolidIcon,
  BookmarkIcon as BookmarkSolidIcon,
  FireIcon as FireSolidIcon,
  HeartIcon as HeartSolidIcon,
} from '@heroicons/vue/24/solid'
import ShareModal from '~/components/tour/ShareModal.vue'

// Stores and utils like useCartStore and getImageUrl are auto-imported in Nuxt 4
const route = useRoute()
const { api } = useApi()
const config = useRuntimeConfig()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const { t, locale } = useI18n()
const localePath = useLocalePath()

// --- Save / Share (wishlist + share modal) ---
const shareOpen = ref(false)

const shareUrl = computed(() => {
  if (import.meta.client) return window.location.href
  // SSR fallback so the modal can render with a sensible URL pre-hydration.
  const base = (config.public.appUrl as string) || 'https://incalake.com'
  return `${base.replace(/\/$/, '')}${route.fullPath}`
})

function wishlistPayload() {
  const t = tour.value as any
  // The tour API exposes `featured_image`; the gallery item is the second
  // best source. `featured_image_path` was a guess that doesn't exist, which
  // is why saved cards were rendering the placeholder icon.
  const image = t?.featured_image
    || (Array.isArray(t?.media_gallery) && t.media_gallery[0]?.url)
    || ''
  return {
    id: Number(t?.id),
    slug: t?.slug,
    city_slug: t?.city?.slug || (route.params.city as string | undefined),
    title: t?.title,
    image,
    min_price: t?.min_price,
    currency: t?.currency || 'USD',
  }
}

function toggleSave() {
  if (!tour.value?.id) return
  wishlistStore.toggle(wishlistPayload())
}

const isSaved = computed(() => wishlistStore.has((tour.value as any)?.id))

async function openShare() {
  const data = { title: (tour.value as any)?.title || 'Incalake', text: (tour.value as any)?.title || '', url: shareUrl.value }
  // Prefer the native share sheet on devices that support it (mainly mobile).
  if (import.meta.client && typeof navigator !== 'undefined' && (navigator as any).share) {
    try { await (navigator as any).share(data); return } catch { /* user cancelled -> fall through to modal */ }
  }
  shareOpen.value = true
}

function toggleSaveRelated(relatedTour: any) {
  if (!relatedTour?.id) return
  wishlistStore.toggle({
    id: Number(relatedTour.id),
    slug: relatedTour.slug,
    city_slug: relatedTour.city?.slug,
    title: relatedTour.title,
    image: relatedTour.featured_image || (Array.isArray(relatedTour.media_gallery) && relatedTour.media_gallery[0]?.url) || '',
    min_price: relatedTour.min_price,
    currency: relatedTour.currency || 'USD',
  })
}
const currencyStore = useCurrencyStore()

const slug = route.params.slug as string
const citySlug = route.params.city as string

// Get language code (ES, EN, PT) from i18n locale
const langCode = computed(() => locale.value.toUpperCase())

// Reuse cached fetch results when navigating BACK to an already-opened tour
// (instant, no refetch). Each tour is cached under its own key, so A → B → A
// serves A from cache. Still refetches on manual refresh / watched-dep change.
function getCachedData(key: string, nuxtApp: any, ctx: any) {
  if (ctx?.cause === 'watch' || ctx?.cause === 'refresh:manual') return undefined
  return nuxtApp.payload.data[key] ?? nuxtApp.static.data[key]
}

// Fetch tour data with SSR using multilang API endpoint
const { data: response, pending, error } = await useAsyncData(
  `tour-${langCode.value}-${citySlug}-${slug}`,
  () => api(`/tours/${langCode.value.toLowerCase()}/${citySlug}/${slug}`),
  { getCachedData }
)

const tour = computed(() => response.value?.data || null)

// Custom additional sections — pulled from the active language's translation
const customSections = computed(() => {
  const t = tour.value
  if (!t) return []
  const lang = (locale.value || 'es').toUpperCase()
  const trans = (t.translations || []).find((x: any) => x.language?.code?.toUpperCase() === lang)
    || (t.translations || []).find((x: any) => x.language?.code?.toUpperCase() === 'ES')
    || (t.translations || [])[0]
  const sections = trans?.custom_sections || []
  return Array.isArray(sections) ? sections.filter((s: any) => (s.title || '').trim() || (s.content || '').trim()) : []
})

// Fetch related tours (lazy - doesn't block navigation)
const { data: relatedResponse } = await useAsyncData(
  `related-tours-${slug}`,
  () => api('/tours?limit=4'),
  { lazy: true, default: () => ({ data: [] }), getCachedData }
)

const relatedTours = computed(() => {
  const tours = relatedResponse.value?.data || []
  // Filter out current tour
  return tours.filter((t: any) => t.slug !== slug)
})

// Reviews for this tour — cached so returning to a tour doesn't refetch them.
const { data: reviewsData } = await useAsyncData(
  `reviews-${citySlug}-${slug}`,
  () => tour.value?.id ? api(`/reviews?tour_id=${tour.value.id}&per_page=20`) : Promise.resolve({ data: [] }),
  { lazy: true, default: () => ({ data: [] }), getCachedData }
)
const tourReviews = computed<any[]>(() => (reviewsData.value as any)?.data || [])
const showAllReviews = ref(false)

const avgRating = computed(() => {
  if (tourReviews.value.length === 0) return 0
  const sum = tourReviews.value.reduce((acc: number, r: any) => acc + r.rating, 0)
  return (sum / tourReviews.value.length).toFixed(1)
})

// Qualitative label for the rating summary (OTA pattern).
const ratingLabel = computed(() => {
  const r = parseFloat(String(avgRating.value)) || 0
  if (r >= 4.5) return 'Excelente'
  if (r >= 4) return 'Muy bueno'
  if (r >= 3.5) return 'Bueno'
  return r > 0 ? 'Aceptable' : ''
})

function getInitials(name: string) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

// Clicking the rating in the header jumps to the reviews section (OTA pattern).
function scrollToReviews() {
  if (import.meta.client) {
    document.getElementById('reviews')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// Booking widget state
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)
const children = ref(0)
const mobileError = ref('')
const mobileBookingRef = ref<HTMLElement | null>(null)

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
  handleBooking()
}

// Sticky bottom-bar CTA: if the user already picked date+time we proceed to
// checkout; otherwise we scroll them up to the inline booking panel so they
// can finish configuring without losing scroll context.
function onMobileBottomCta() {
  if (selectedDate.value && selectedTime.value) {
    mobileHandleBooking()
    return
  }
  if (mobileBookingRef.value) {
    mobileBookingRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// Sticky bottom bar should only appear once the inline mobile booking
// panel (which already shows the price) has scrolled out of view —
// otherwise the price is duplicated at the top of the page.
const showStickyBar = ref(false)
let stickyObserver: IntersectionObserver | null = null

watch(mobileBookingRef, (el) => {
  stickyObserver?.disconnect()
  if (!el || typeof IntersectionObserver === 'undefined') return
  stickyObserver = new IntersectionObserver(
    ([entry]) => { showStickyBar.value = !entry.isIntersecting },
    { threshold: 0 }
  )
  stickyObserver.observe(el)
}, { immediate: true })

onBeforeUnmount(() => stickyObserver?.disconnect())

// Clear mobile error when user selects
watch(selectedDate, () => { if (mobileError.value) mobileError.value = '' })
watch(selectedTime, () => { if (mobileError.value) mobileError.value = '' })

// Pricing model — a tour defines `price_details[]`, one row per
// (age_stage, quantity-tier). We group active rows by age stage and order the
// stages by `age_stage_id` ASC. The backend treats the FIRST stage (lowest id)
// as the primary/adult stage (see Tour::getMinPriceAttribute), and legacy prod
// data sometimes mislabels the text description ("Niño"/"Adulto") — so we trust
// the id order, NOT the label. Within a stage the per-person price drops as the
// group grows (quantity tiers).
const priceStages = computed(() => {
  const details = (tour.value?.price_details || []).filter((p: any) => p.active)
  const byStage: Record<string, { id: any; desc: string; tiers: any[] }> = {}
  for (const p of details) {
    const key = String(p.age_stage_id ?? '0')
    if (!byStage[key]) byStage[key] = { id: p.age_stage_id, desc: p.age_stage?.description || '', tiers: [] }
    byStage[key].tiers.push(p)
  }
  const stages = Object.values(byStage)
  for (const s of stages) s.tiers.sort((a: any, b: any) => (a.min_quantity || 1) - (b.min_quantity || 1))
  // Lowest age_stage_id first = primary/adult stage (matches backend min_price).
  stages.sort((a, b) => (Number(a.id) || 0) - (Number(b.id) || 0))
  return stages
})

// Adult = the primary (lowest-id) stage; child = the next stage, if any.
const adultStage = computed(() => priceStages.value[0] || null)
const childStage = computed(() => priceStages.value[1] || null)
const hasChildPricing = computed(() => !!childStage.value)

// Per-person price for a stage at a given quantity, honoring quantity tiers
// (beyond the last tier we keep the cheapest/last per-person rate).
function tierPriceFor(stage: { tiers: any[] } | null, qty: number): number {
  if (!stage || !stage.tiers.length) return 0
  const tiers = stage.tiers
  const match = tiers.find((p: any) => {
    const min = p.min_quantity || 1
    const max = p.max_quantity || Infinity
    return qty >= min && qty <= max
  })
  if (match) return parseFloat(match.price || 0)
  const last = tiers[tiers.length - 1]
  if (qty > (last.max_quantity || 0)) return parseFloat(last.price || 0)
  return Math.min(...tiers.map((p: any) => parseFloat(p.price || 0)))
}

const adultPrice = computed(() => {
  if (!adultStage.value) return tour.value?.min_price || 0
  return tierPriceFor(adultStage.value, adults.value)
})

const childPrice = computed(() =>
  childStage.value ? tierPriceFor(childStage.value, children.value) : 0)

// `basePrice` is the headline "Desde" / per-adult price (template compat).
const basePrice = computed(() => adultPrice.value)

const subtotal = computed(() =>
  adultPrice.value * adults.value + childPrice.value * children.value)

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
  return activeOffer.value.discount * (adults.value + children.value)
})

const groupDiscount = computed(() => offerDiscount.value)

const total = computed(() => subtotal.value - groupDiscount.value)

const currency = computed(() => tour.value?.currency || 'USD')

// Minimum bookable date from the booking-anticipation rule configured in
// admin Step 6. The wizard writes `booking_anticipation_quantity` +
// `booking_anticipation_unit` (Step 6 normalizes to minutes); the legacy
// `booking_anticipation_hours` column is NOT updated by the new wizard, so we
// only fall back to it when quantity/unit are absent (old tours).
const anticipationMs = computed(() => {
  const qty = Number(tour.value?.booking_anticipation_quantity)
  const unit = tour.value?.booking_anticipation_unit

  if (Number.isFinite(qty) && qty > 0 && unit) {
    const perUnitMs =
      unit === 'minutes' ? 60_000 :
      unit === 'hours'   ? 3_600_000 :
      unit === 'days'    ? 86_400_000 :
      unit === 'weeks'   ? 604_800_000 :
      3_600_000 // default treat unknown unit as hours
    return qty * perUnitMs
  }

  // Legacy fallback
  const hours = Number(tour.value?.booking_anticipation_hours)
  if (Number.isFinite(hours) && hours > 0) return hours * 3_600_000

  return 24 * 3_600_000 // sensible default: 24h
})

const minDate = computed(() => {
  const minDateTime = new Date(Date.now() + anticipationMs.value)
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

// --- FAQ (authored in the admin, per language) ---------------------------
// The visible accordion and the FAQPage JSON-LD render from the SAME faqItems
// source so they stay identical (Google requires the schema answer to match
// the on-page text). Only admin-authored FAQs are shown — no auto-generation —
// so a tour with no FAQs renders no section and no schema.
const FAQ_TITLES: Record<string, string> = {
  es: 'Preguntas frecuentes', en: 'Frequently asked questions',
  pt: 'Perguntas frequentes', fr: 'Questions fréquentes',
  de: 'Häufige Fragen', it: 'Domande frequenti',
}
const faqL = computed(() => ({ title: FAQ_TITLES[locale.value] || FAQ_TITLES.en }))

const faqItems = computed<Array<{ q: string; a: string }>>(() =>
  (tour.value?.faqs || [])
    .map((f: any) => ({ q: String(f?.question || '').trim(), a: String(f?.answer || '').trim() }))
    .filter((f: { q: string; a: string }) => f.q && f.a)
)

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
// Max bookable participants — prefer explicit capacity/cupos, else the largest
// quantity tier admin configured, else a sane default. Adults + children share
// this cap (so e.g. 21 adults / 0 children is allowed when admin permits 21).
const maxPax = computed(() => {
  const cap = Number(tour.value?.capacity) || Number(tour.value?.cupos) || 0
  if (cap > 0) return cap
  const maxes = (tour.value?.price_details || [])
    .filter((p: any) => p.active)
    .map((p: any) => Number(p.max_quantity))
    .filter((n: number) => Number.isFinite(n) && n > 0)
  return maxes.length ? Math.max(...maxes) : 20
})

const totalPax = computed(() => adults.value + children.value)

function incrementAdults() {
  if (totalPax.value < maxPax.value) adults.value++
}
function decrementAdults() {
  if (adults.value > 1) adults.value--
}
function incrementChildren() {
  if (totalPax.value < maxPax.value) children.value++
}
function decrementChildren() {
  if (children.value > 0) children.value--
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

// Add the current configuration (date/time/pax) to the cart and stay on the
// page. Returns false on missing required fields so the caller can bail out.
const addedToCart = ref(false)
let addedToCartTimer: any = null
function handleAddToCart(): boolean {
  if (!selectedDate.value) { alert('Please select a tour date'); return false }
  if (!selectedTime.value) { alert('Please select a departure time'); return false }

  const tourImage = tour.value?.media_gallery && tour.value.media_gallery.length > 0
    ? getImageUrl(tour.value.media_gallery[0].url)
    : ''

  // `total` already accounts for adults, children and any active offer
  // discount (tax is added downstream by the cart store).
  cartStore.addItem({
    tourId: tour.value?.id,
    tourTitle: tour.value?.title,
    tourSlug: slug,
    tourImage,
    selectedDate: selectedDate.value,
    selectedTime: selectedTime.value,
    timezone: tour.value?.timezone || 'America/Lima',
    adults: adults.value,
    children: children.value,
    basePrice: adultPrice.value,
    childPrice: childPrice.value,
    total: total.value,
    currency: tour.value?.currency || 'USD',
    policies: tour.value?.policies || '',
    cancellationPolicy: tour.value?.cancellation_policy || '',
    taxPercentage: tour.value?.tax_percentage || 0,
    advancePaymentPercentage: tour.value?.advance_payment_percentage || 100,
    guideType: tour.value?.guide_type || '',
    guideLanguages: getGuideLanguageNames(tour.value?.guide_languages || []),
    durationLabel: durationLabel.value,
  })

  addedToCart.value = true
  if (import.meta.client) {
    clearTimeout(addedToCartTimer)
    addedToCartTimer = setTimeout(() => { addedToCart.value = false }, 2500)
  }
  return true
}

// "Reservar ahora": add to cart and go to checkout.
function handleBooking() {
  if (handleAddToCart()) navigateTo('/cart')
}


// Per-locale slugs for correct hreflang/alternate links — tour slugs differ
// per language, so the default path-swap would produce wrong URLs.
const setI18nParams = useSetI18nParams()
watchEffect(() => {
  const trans = (tour.value as any)?.translations
  if (!Array.isArray(trans)) return
  const params: Record<string, { city: string; slug: string }> = {}
  for (const tr of trans) {
    const code = tr.language?.code?.toLowerCase()
    if (code && tr.slug) params[code] = { city: citySlug, slug: tr.slug }
  }
  if (Object.keys(params).length) setI18nParams(params)
})

// Dynamic SEO + Schema.org — locale & city aware, on incalake.com.
// Canonical + hreflang are emitted globally by useLocaleHead (app.vue) using
// i18n.baseUrl, so we don't set a per-page canonical here (avoids duplicates).
const siteUrl = 'https://incalake.com'
const canonicalUrl = computed(() =>
  `${siteUrl}/${locale.value}/${tour.value?.city?.slug || citySlug}/${slug}`)

watchEffect(() => {
  if (!tour.value) return
  const url = canonicalUrl.value
  const imageUrl = tour.value.featured_image ? getImageUrl(tour.value.featured_image) : ''
  const cityName = tour.value.city?.name || 'Puno'
  const citySlugVal = tour.value.city?.slug || citySlug
  const localeHome = `${siteUrl}/${locale.value}`

  useSeoMeta({
    title: () => tour.value.title,
    description: () => tour.value.short_description || tour.value.title,
    keywords: () => `${tour.value.title}, tours ${cityName}, lago titicaca, peru`,
    robots: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
    author: 'Incalake Tours',
    ogTitle: () => tour.value.title,
    ogDescription: () => tour.value.short_description || tour.value.title,
    ogType: 'website',
    ogUrl: () => url,
    ogSiteName: 'Incalake Tours',
    ogImage: () => imageUrl,
    ogImageWidth: 1200,
    ogImageHeight: 630,
    ogImageAlt: () => tour.value.title,
    ogLocale: () => locale.value,
    twitterCard: 'summary_large_image',
    twitterTitle: () => tour.value.title,
    twitterDescription: () => tour.value.short_description || tour.value.title,
    twitterImage: () => imageUrl,
  })

  useHead({
    script: [
      {
        type: 'application/ld+json',
        children: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'Product',
          name: tour.value.title,
          description: tour.value.short_description || tour.value.title,
          image: imageUrl,
          brand: { '@type': 'Brand', name: 'Incalake Tours' },
          offers: {
            '@type': 'Offer',
            price: tour.value.min_price || 0,
            priceCurrency: tour.value.currency || 'USD',
            availability: 'https://schema.org/InStock',
            url,
            // Deterministic (year-based) so SSR and client hydration match.
            priceValidUntil: `${new Date().getFullYear() + 1}-12-31`,
            seller: { '@type': 'Organization', name: 'Incalake Tours' },
          },
          // Only emit aggregateRating from REAL fetched reviews — never a
          // fabricated rating (Google penalizes fake review rich snippets).
          ...(tourReviews.value.length > 0 ? {
            aggregateRating: {
              '@type': 'AggregateRating',
              ratingValue: String(avgRating.value),
              reviewCount: tourReviews.value.length,
              bestRating: '5',
              worstRating: '1',
            },
          } : {}),
        }),
      },
      {
        type: 'application/ld+json',
        children: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'TouristTrip',
          name: tour.value.title,
          description: tour.value.short_description || tour.value.title,
          touristType: 'Tourist',
          offers: { '@type': 'Offer', price: tour.value.min_price || 0, priceCurrency: tour.value.currency || 'USD', url },
          provider: { '@type': 'TravelAgency', name: 'Incalake Tours', url: siteUrl },
        }),
      },
      {
        type: 'application/ld+json',
        children: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'BreadcrumbList',
          itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Inicio', item: localeHome },
            { '@type': 'ListItem', position: 2, name: 'Tours', item: `${localeHome}/tours` },
            { '@type': 'ListItem', position: 3, name: cityName, item: `${localeHome}/tours?city=${citySlugVal}` },
            { '@type': 'ListItem', position: 4, name: tour.value.title, item: url },
          ],
        }),
      },
      // FAQPage — biggest lever for AI-search citation (ChatGPT/Perplexity/AI
      // Overviews). Answers MUST match the visible FAQ section: both render from
      // `faqItems`, so they can't drift.
      ...(faqItems.value.length ? [{
        type: 'application/ld+json',
        children: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'FAQPage',
          mainEntity: faqItems.value.map(f => ({
            '@type': 'Question',
            name: f.q,
            acceptedAnswer: { '@type': 'Answer', text: f.a },
          })),
        }),
      }] : []),
    ],
  })
})

// Helper functions
function formatCityName(slug: string): string {
  if (!slug) return ''
  return slug
    .split('-')
    .map(w => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
}

// Clean city label from the slug (city.name is inconsistent in the DB, e.g.
// "Vinicunca Peru - Rainbow Mountain, Cusco, Perú"). Falls back to first name
// segment, then the route's city param.
function cityLabel(t: any): string {
  const slug = t?.city?.slug
  if (slug) return formatCityName(slug)
  const name = t?.city?.name
  if (name) return name.split(',')[0].trim()
  return formatCityName(citySlug)
}

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
