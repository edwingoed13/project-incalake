<template>
  <div v-if="tour" class="bg-background-light font-display text-slate-900">
    <!-- Compact header rhythm: on a 768px-tall laptop every saved pixel means
         the booking card's CTA gets closer to the fold. -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 md:pt-24 pb-24 lg:pb-8">

      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" class="mb-2">
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
          <li class="text-slate-700 font-medium truncate" aria-current="page">
            {{ tour.title }}
          </li>
        </ol>
      </nav>

      <!-- Title & Basic Info — the badges live INSIDE the meta row now: their
           own row above the title cost ~40px of laptop fold for three small
           chips. -->
      <div class="flex flex-col lg:flex-row justify-between gap-4 lg:gap-6 mb-4 lg:mb-5">
        <div class="flex-1 min-w-0">
          <h1 class="text-[22px] sm:text-[26px] md:text-3xl lg:text-[32px] font-extrabold leading-[1.15] tracking-tight mb-2 text-slate-900">
            {{ tour.title }}
          </h1>
          <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 text-[13px] sm:text-[15px]">
            <span
              v-if="tour.is_best_seller"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-bestseller/10 text-bestseller text-[11px] font-bold uppercase tracking-wide"
            >
              <BookmarkSolidIcon class="size-3" aria-hidden="true" />
              {{ t('badge_best_seller') }}
            </span>
            <span v-if="tour.free_cancellation" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-trust-soft text-trust text-[11px] font-bold">
              <CheckCircleSolidIcon class="size-3" aria-hidden="true" />
              {{ t('free_cancellation') }}
            </span>
            <span
              v-if="tour.capacity && tour.cupos != null && tour.cupos / Math.max(tour.capacity, 1) < 0.3"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-urgency-soft text-urgency text-[11px] font-bold"
            >
              <FireSolidIcon class="size-3" aria-hidden="true" />
              {{ t('badge_few_spots') }}
            </span>
            <!-- Rating -->
            <button
              v-if="tourReviews.length > 0"
              type="button"
              @click="scrollToReviews"
              class="inline-flex items-center gap-1 font-bold text-slate-900 hover:text-primary transition-colors"
            >
              <StarSolidIcon class="size-4 text-rating" aria-hidden="true" />
              <span class="tabular-nums">{{ avgRating }}</span>
              <span class="text-slate-500 underline-offset-2 hover:underline">({{ reviewsTotal }})</span>
            </button>
            <span v-if="tourReviews.length > 0" class="text-slate-300" aria-hidden="true">•</span>
            <span class="inline-flex items-center gap-1 text-slate-600">
              <MapPinIcon class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
              {{ cityLabel(tour) }}, Perú
            </span>
            <span class="text-slate-300" aria-hidden="true">•</span>
            <span class="inline-flex items-center gap-1 text-slate-600">
              <ClockIcon class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
              {{ formatDuration(tour) }}
            </span>
            <!-- Difficulty (with an info popover describing the level) -->
            <template v-if="difficultyLabel">
              <span class="text-slate-300" aria-hidden="true">•</span>
              <span class="inline-flex items-center gap-1 font-semibold" :class="difficultyColor">
                <Icon name="material-symbols:signal-cellular-alt" class="size-4 shrink-0" aria-hidden="true" />
                {{ difficultyLabel }}
                <AppPopover v-if="difficultyDesc" :label="`Dificultad: ${difficultyLabel}`" width="w-64">
                  <p class="text-xs font-bold uppercase tracking-wider text-white/60 mb-1">{{ difficultyLabel }}</p>
                  <p class="leading-snug">{{ difficultyDesc }}</p>
                </AppPopover>
              </span>
            </template>
            <!-- Target audience -->
            <template v-if="audienceLabel">
              <span class="text-slate-300" aria-hidden="true">•</span>
              <span class="inline-flex items-center gap-1 text-slate-600">
                <Icon name="material-symbols:groups-outline" class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
                {{ audienceLabel }}
              </span>
            </template>
            <!-- Guide -->
            <template v-if="guideSummary">
              <span class="text-slate-300" aria-hidden="true">•</span>
              <span class="inline-flex items-center gap-1 text-slate-600">
                <Icon name="material-symbols:record-voice-over-outline" class="size-4 shrink-0 text-primary/70" aria-hidden="true" />
                {{ guideSummary }}
              </span>
            </template>
          </div>
        </div>
        <div class="hidden md:flex gap-2 items-start shrink-0">
          <button
            @click="openShare"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] min-w-[44px] px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-lg font-semibold text-sm transition-colors"
            :aria-label="t('share')"
          >
            <ShareIcon class="size-5" aria-hidden="true" />
            <span class="hidden sm:inline">{{ t('share') }}</span>
          </button>
          <button
            @click="toggleSave($event)"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] min-w-[44px] px-3 py-2 hover:bg-slate-100 rounded-lg font-semibold text-sm transition-all active:scale-95"
            :class="isSaved ? 'text-red-500' : 'text-slate-700'"
            :aria-label="isSaved ? 'Quitar de guardados' : 'Guardar tour'"
            :aria-pressed="isSaved"
          >
            <HeartSolidIcon v-if="isSaved" class="size-5" aria-hidden="true" />
            <HeartIcon v-else class="size-5" aria-hidden="true" />
            <span class="hidden sm:inline">{{ isSaved ? 'Guardado' : 'Guardar' }}</span>
          </button>
        </div>
      </div>

      <!-- In-page section nav (sticky): jump straight to a section instead of
           scrolling the whole page. Hidden when there's little content. -->
      <nav
        v-if="sectionNav.length > 2"
        class="sticky-below-nav -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 mb-4 bg-white/95 backdrop-blur border-b border-slate-200"
      >
        <div class="flex gap-1 overflow-x-auto scrollbar-hide">
          <a
            v-for="s in sectionNav"
            :key="s.id"
            :href="`#${s.id}`"
            @click.prevent="scrollToSection(s.id)"
            class="shrink-0 px-3.5 py-3 text-[13px] font-bold whitespace-nowrap border-b-2 -mb-px transition-colors"
            :class="activeSection === s.id ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-800'"
          >
            {{ s.label }}
          </a>
        </div>
      </nav>

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
                class="size-11 rounded-full bg-black/45 backdrop-blur-sm text-white flex items-center justify-center active:scale-90 transition-transform"
                :aria-label="t('share')"
              >
                <ShareIcon class="size-5" aria-hidden="true" />
              </button>
              <TourWishlistHeartButton :tour="tour" size="sm" overlay="dark" class="shadow-none" />
            </div>
          </div>

          <!-- Variant options FIRST — siblings share one activity (Compartido /
               +Guía / Privado) and picking one changes the price/pax shown below,
               so it must appear BEFORE the booking panel. Swaps content inline
               (no navigation) and syncs the URL via history.replaceState. -->
          <TourOptionsSelector v-if="tour.options?.length" :options="tour.options" :loading="swapping" @select="switchOption" />

          <!-- Inline Mobile Booking Panel — appears after gallery so price/date are
               visible without scrolling to the bottom. Hidden on lg+ where the
               sticky right-column widget takes over. -->
          <section ref="mobileBookingRef" class="lg:hidden">
            <TourBookingCard
              variant="inline"
              v-bind="bookingProps"
              v-model:adults="adults"
              v-model:children="children"
              v-model:selected-date="selectedDate"
              v-model:selected-time="selectedTime"
              @book="handleBooking"
              @add-to-cart="handleAddToCart"
              @inquire="inquiryOpen = true"
            />
          </section>

          <!-- Content Sections -->
          <!-- Tour Description -->
          <div v-if="tour.long_description || tour.description" id="descripcion" class="scroll-mt-32">
            <TourDescription :tour="tour" />
          </div>

          <!-- Tour Itinerary -->
          <div v-if="tour.itinerary" id="itinerario" class="scroll-mt-32">
            <TourItinerary :tour="tour" />
          </div>

          <!-- What's Included / Not Included -->
          <div v-if="tour.what_includes || tour.what_not_includes" id="incluye" class="scroll-mt-32">
            <TourIncludes :tour="tour" />
          </div>

          <!-- Important Information / Recommendations -->
          <TourRecommendations :tour="tour" />

          <!-- Cancellation Policies -->
          <TourPolicies v-if="tour.cancellation_policy || tour.policies" :tour="tour" />

          <!-- Custom additional sections (admin Step 3) -->
          <section v-if="customSections.length" class="space-y-6">
            <div v-for="section in customSections" :key="section.id || section.title" class="space-y-3">
              <h3 class="section-title">{{ section.title }}</h3>
              <div class="prose prose-sm md:prose-base max-w-none" v-html="sanitizeHtml(section.content)"></div>
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
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-violet-50 border border-violet-200 text-violet-700 text-xs font-bold hover:bg-violet-100 transition-colors"
              >
                <TagIcon class="size-3.5" aria-hidden="true" />
                {{ tag.name }}
              </NuxtLink>
            </div>
          </section>

          <!-- Location Map -->
          <div id="ubicacion" class="scroll-mt-32">
            <TourLocation :tour="tour" />
          </div>

          <hr class="border-slate-200" />

          <!-- Reviews Section -->
          <section id="reviews" class="scroll-mt-32">
            <h3 class="section-title mb-3 md:mb-4">{{ t('customer_reviews') }}</h3>

            <!-- Rating summary -->
            <div v-if="tourReviews.length > 0" class="flex items-center gap-3 mb-5 md:mb-6">
              <span class="text-3xl md:text-4xl font-black text-slate-900 tabular-nums leading-none">{{ avgRating }}</span>
              <div class="min-w-0">
                <div class="flex">
                  <StarSolidIcon v-for="i in 5" :key="i" class="size-4 md:size-5" :class="i <= Math.round(avgRating) ? 'text-rating' : 'text-slate-300'" aria-hidden="true" />
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">
                  <span v-if="ratingLabel" class="font-bold text-slate-700">{{ ratingLabel }}</span>
                  <span v-if="ratingLabel"> · </span>{{ reviewsTotal }} {{ reviewsTotal === 1 ? 'opinión' : 'opiniones' }}
                </p>
              </div>
            </div>

            <div v-if="tourReviews.length > 0">
              <!-- Mobile: swipeable review cards · Desktop: stacked list -->
              <div class="flex md:block gap-3 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 md:space-y-6 pb-1 md:pb-0">
                <div
                  v-for="review in tourReviews.slice(0, showAllReviews ? tourReviews.length : 3)"
                  :key="review.id"
                  class="shrink-0 w-[85%] sm:w-[55%] md:w-auto snap-start bg-slate-50 rounded-2xl p-4 md:bg-transparent md:md:rounded-none md:p-0 md:border-b md:border-slate-100 md:md:pb-6"
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
                  <p v-if="review.title" class="text-[15px] font-semibold text-slate-800 mb-1">{{ review.title }}</p>
                  <p class="text-[15px] text-slate-600 leading-relaxed line-clamp-5 md:line-clamp-none">{{ review.comment }}</p>
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

            <div v-else class="py-8 text-center text-slate-400 bg-slate-50 rounded-2xl">
              <ChatBubbleLeftRightIcon class="size-8 mb-2 mx-auto" aria-hidden="true" />
              <p class="text-sm font-medium">{{ t('no_reviews') }}</p>
            </div>
          </section>
        </div>

        <!-- Right Column: Booking Widget - Sticky (OTA-style) -->
        <div class="hidden lg:block">
          <div class="sticky top-24 space-y-3">
            <!-- Booking widget (shared component) -->
            <TourBookingCard
              variant="sidebar"
              v-bind="bookingProps"
              v-model:adults="adults"
              v-model:children="children"
              v-model:selected-date="selectedDate"
              v-model:selected-time="selectedTime"
              @book="handleBooking"
              @add-to-cart="handleAddToCart"
              @inquire="inquiryOpen = true"
            />

            <!-- Trust signals card — OTA pattern: stacks below booking widget -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-2.5">
              <div v-if="tour.free_cancellation" class="flex items-start gap-2.5">
                <CheckCircleSolidIcon class="size-5 text-trust shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ t('free_cancellation') }}</p>
                  <p class="text-xs text-slate-500">{{ t('trust_cancel_hint') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-2.5">
                <ClockIcon class="size-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ t('trust_instant') }}</p>
                  <p class="text-xs text-slate-500">{{ t('trust_instant_hint') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-2.5">
                <ShieldCheckIcon class="size-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ t('trust_best_price') }}</p>
                  <p class="text-xs text-slate-500">{{ t('trust_best_price_hint') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ (derived from tour data — visible answers match the FAQPage JSON-LD) -->
      <section v-if="faqItems.length" class="mt-10 md:mt-14">
        <h2 class="section-title mb-5 md:mb-8">{{ faqL.title }}</h2>
        <div class="space-y-3 max-w-3xl">
          <details
            v-for="(item, i) in faqItems"
            :key="i"
            class="group rounded-xl border border-slate-200 bg-white overflow-hidden"
          >
            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none [&::-webkit-details-marker]:hidden px-4 md:px-5 py-4 font-bold text-slate-900">
              <span>{{ item.q }}</span>
              <ChevronDownIcon class="size-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true" />
            </summary>
            <div class="px-4 md:px-5 pb-4 -mt-1 text-sm leading-relaxed text-slate-600">
              {{ item.a }}
            </div>
          </details>
        </div>
      </section>

      <!-- Related Tours (Full Width) -->
      <section class="mt-10 md:mt-14" v-if="relatedTours.length > 0">
        <h2 class="section-title mb-5 md:mb-8">{{ t('you_might_like') }}</h2>
        <div class="flex md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0">
          <!-- Same card language as the listing and the home: white card,
               image guard, real stars, blue price pinned to the bottom. -->
          <NuxtLink
            v-for="relatedTour in relatedTours.slice(0, 4)"
            :key="relatedTour.id"
            :to="`/${locale}/${relatedTour.city?.slug || 'puno'}/${relatedTour.slug}`"
            class="group flex flex-col shrink-0 w-[75%] sm:w-[48%] md:w-auto snap-start bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
          >
            <div class="aspect-[4/3] shrink-0 overflow-hidden relative bg-slate-100">
              <NuxtImg
                v-if="relatedTour.featured_image"
                v-skeleton
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
              <div v-else class="w-full h-full flex items-center justify-center">
                <Icon name="material-symbols:image-outline" class="text-slate-300 text-5xl" />
              </div>
              <TourWishlistHeartButton :tour="relatedTour" size="sm" class="absolute top-3 right-3" />
            </div>
            <div class="p-4 flex-1 flex flex-col">
              <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mb-1">{{ cityLabel(relatedTour) }}</p>
              <h4 class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors leading-snug">{{ relatedTour.title }}</h4>
              <!-- Rating shown only when there are real reviews (no fabricated 4.5) -->
              <div v-if="relatedTour.reviews_count > 0 && relatedTour.rating" class="flex items-center gap-1 mt-1">
                <Icon name="material-symbols:star" class="text-rating text-sm" />
                <span class="text-xs font-bold text-slate-700">{{ Number(relatedTour.rating).toFixed(1) }}</span>
                <span class="text-xs text-slate-400">({{ relatedTour.reviews_count }})</span>
              </div>
              <div class="mt-auto pt-3 border-t border-slate-100">
                <span class="text-[11px] text-slate-500 font-medium block">{{ t('from') }}</span>
                <span class="text-lg font-black text-primary whitespace-nowrap">{{ currencyStore.formatConverted(relatedTour.min_price || 0) }}</span>
              </div>
            </div>
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
    <div v-show="showStickyBar" class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(0,0,0,0.06)] z-40">
      <div class="flex items-center justify-between gap-3">
        <div class="leading-tight shrink-0">
          <div class="text-2xl font-black text-slate-900 tabular-nums leading-none">
            {{ currencyStore.formatConverted(basePrice || 0) }}
          </div>
          <span class="text-[11px] text-slate-500">por persona</span>
        </div>
        <button
          @click="onMobileBottomCta"
          class="btn-primary flex-1"
        >
          {{ tour.require_availability ? t('cta_inquire') : (selectedDate && selectedTime ? t('cta_book') : t('cta_see_dates')) }}
          <ArrowRightIcon class="size-4" aria-hidden="true" />
        </button>
      </div>
    </div>
    </Transition>

    <ShareModal :open="shareOpen" :url="shareUrl" :title="tour?.title" @close="shareOpen = false" />

    <!-- Availability inquiry (tours flagged require_availability) -->
    <TourAvailabilityInquiryModal
      :open="inquiryOpen"
      :tour="tour"
      :prefill-date="selectedDate"
      :prefill-adults="adults"
      :prefill-children="children"
      @close="inquiryOpen = false"
    />

  </div>

  <!-- Loading State -->
  <div v-else-if="pending" class="min-h-screen flex items-center justify-center bg-background-light">
    <div class="text-center">
      <div class="spinner size-12 inline-block"></div>
      <p class="mt-4 text-slate-600">{{ t('loading_tour') }}</p>
    </div>
  </div>

  <!-- Error State: un fallo transitorio (429/500/red) NO es «no existe».
       Decirle «no encontrado» a un comprador por un hipo de la API pierde la
       venta; aquí se le ofrece reintentar. -->
  <div v-else class="min-h-screen bg-background-light pt-28 pb-16 px-4">
    <div class="max-w-5xl mx-auto">
      <div class="text-center">
        <template v-if="isTransientError">
          <ExclamationTriangleIcon class="size-16 text-amber-400 mb-4 mx-auto" aria-hidden="true" />
          <p class="text-slate-700 text-lg font-bold mb-4">{{ t('tour_load_error') }}</p>
          <button type="button" class="btn-primary" @click="refresh()">
            {{ t('retry') }}
          </button>
        </template>
        <template v-else>
          <MagnifyingGlassIcon class="size-16 text-slate-300 mb-4 mx-auto" aria-hidden="true" />
          <h1 class="text-xl md:text-2xl font-black text-slate-800 mb-1.5">{{ t('tour_not_found') }}</h1>
          <p class="text-sm text-slate-500 mb-5 max-w-md mx-auto">{{ t('tour_not_found_help') }}</p>
          <NuxtLink :to="localePath('/tours')" class="btn-primary">
            {{ t('view_all_tours') }}
          </NuxtLink>
        </template>
      </div>

      <!-- A dead link is where visitors from Google land. Sending them to a
           lone "not found" wastes the visit; these are already fetched. -->
      <section v-if="!isTransientError && relatedTours.length" class="mt-12">
        <h2 class="text-base md:text-lg font-bold text-slate-800 mb-4 text-center">{{ t('tour_not_found_suggestions') }}</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
          <TourCard v-for="rt in relatedTours.slice(0, 4)" :key="rt.id" :tour="rt" />
        </div>
      </section>
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
  ChevronDownIcon,
  ChatBubbleLeftRightIcon,
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  ArrowRightIcon,
  ShieldCheckIcon,
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
const { flyTo } = useFlyTo()
const { t, locale } = useI18n()
const localePath = useLocalePath()

// --- Save / Share (wishlist + share modal) ---
const shareOpen = ref(false)
const inquiryOpen = ref(false)

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

function toggleSave(ev?: MouseEvent) {
  if (!tour.value?.id) return
  const wasAdded = !wishlistStore.has(tour.value.id)
  // Capture the tapped button synchronously (currentTarget is nulled after
  // dispatch / on replayed early clicks) so the first save still animates.
  const src = ev ? ((ev.currentTarget as HTMLElement) || (ev.target as HTMLElement)?.closest('button') as HTMLElement | null) : null
  wishlistStore.toggle(wishlistPayload())
  // Fly a heart up to the header wishlist counter on ADD (not on un-save).
  if (wasAdded && src) flyTo(src, '#nav-wishlist', 'heart')
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

// (Related-tour hearts use the shared <TourWishlistHeartButton> now.)
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
const { data: response, pending, error, refresh } = await useAsyncData(
  `tour-${langCode.value}-${citySlug}-${slug}`,
  () => api(`/tours/${langCode.value.toLowerCase()}/${citySlug}/${slug}`),
  { getCachedData }
)

// Fallo con estado distinto de 404 = la API tuvo un problema, el tour puede
// existir perfectamente. Gobierna tanto el mensaje como el codigo de estado.
const isTransientError = computed(() => {
  if (!error.value) return false
  const s = (error.value as any)?.statusCode ?? (error.value as any)?.status
  return s !== 404
})

// The API 404s for a tour that doesn't exist or isn't published, and the
// template below renders a "no encontrado" block — but the response itself
// stayed HTTP 200. That's a soft 404: Google reads it as a live page and keeps
// it indexed, so an un-published tour went on showing up in search. Send a real
// 404 status instead. Done inline rather than with createError so the existing
// not-found UI (inside the normal layout) is preserved.
//
// ONLY when the API itself said 404. The first version sent 404 whenever data
// was missing — which includes a rate-limited (429) or momentarily-down (5xx)
// API. A transient hiccup during a Googlebot crawl would have deindexed a
// perfectly live tour. Transient failures answer 503 so crawlers retry later.
if (import.meta.server && !response.value?.data) {
  const event = useRequestEvent()
  const apiStatus = (error.value as any)?.statusCode ?? (error.value as any)?.status
  if (event) setResponseStatus(event, apiStatus === 404 ? 404 : 503)
}

// Inline option swap: when the user picks an option on the selector we
// fetch that sibling's full payload and overwrite this ref instead of
// navigating. SSR keeps the per-URL HTML (Google/Perplexity see the
// content matching whatever URL they crawled); the client-side swap is
// purely UX so picking an option doesn't reload the page or scroll-jump.
const swappedTour = ref<any>(null)
const swapping = ref(false)
const tour = computed(() => swappedTour.value || response.value?.data || null)

async function switchOption(opt: { id: number; slug: string; city_slug: string }) {
  if (!opt || opt.id === tour.value?.id || swapping.value) return
  swapping.value = true
  try {
    // Reuse Nuxt's payload cache for siblings already opened in this session.
    const cacheKey = `tour-${langCode.value}-${opt.city_slug}-${opt.slug}`
    const nuxtApp = useNuxtApp()
    const cached: any = nuxtApp.payload?.data?.[cacheKey] ?? nuxtApp.static?.data?.[cacheKey]
    let data: any
    if (cached?.data) {
      data = cached.data
    } else {
      const res: any = await api(`/tours/${langCode.value.toLowerCase()}/${opt.city_slug}/${opt.slug}`)
      data = res?.data
      if (data) {
        try { (nuxtApp.payload as any).data[cacheKey] = res } catch { /* read-only in some contexts */ }
      }
    }
    if (!data) return
    // Re-tag is_current across options so every component (selector, badge,
    // booking widget) reads from the same source of truth.
    if (Array.isArray(data.options)) {
      data.options = data.options.map((o: any) => ({ ...o, is_current: o.id === opt.id }))
    }
    swappedTour.value = data
    if (import.meta.client) {
      try {
        const newUrl = localePath(`/${opt.city_slug}/${opt.slug}`)
        history.replaceState(history.state, '', newUrl)
      } catch { /* HMR / edge cases — non-fatal */ }
    }
  } finally {
    swapping.value = false
  }
}

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

// Fetch related tours (lazy - doesn't block navigation).
// This used to be `/tours?limit=4`: `limit` is not even an API parameter, the
// payload had no rating fields (so the stars markup below never rendered),
// and no city/language filter — "you may also like" was just "the 4 newest
// tours", including imageless ones. light=1 brings the same card fields the
// listing uses; we overfetch 8 and pick the best 4 client-side.
const { data: relatedResponse } = await useAsyncData(
  `related-tours-${langCode.value}-${slug}`,
  () => api(`/tours?light=1&per_page=8&language=${langCode.value}&city_slug=${citySlug}`).catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }), getCachedData }
)

const relatedTours = computed(() => {
  const tours = (relatedResponse.value?.data || []).filter((t: any) => t.slug !== slug)
  // Same merchandising rule as the home showcase: photo + reviews first.
  const score = (t: any) =>
    (t.featured_image ? 2 : 0) + (t.reviews_count > 0 && t.rating ? 1 : 0)
  return [...tours].sort((a, b) => score(b) - score(a))
})

// Reviews for this tour — cached so returning to a tour doesn't refetch them.
const { data: reviewsData } = await useAsyncData(
  `reviews-${citySlug}-${slug}`,
  () => tour.value?.id ? api(`/reviews?tour_id=${tour.value.id}&per_page=60`) : Promise.resolve({ data: [] }),
  { lazy: true, default: () => ({ data: [] }), getCachedData }
)
// Imported reviewer names arrive with scraper artifacts — "Maria del
// Rosar... F" (mid-name ellipsis) or "Roving41150014391" (digit tail). Both
// read as fake reviews; keep the human part.
function cleanReviewerName(raw: string): string {
  return String(raw || '')
    .replace(/\.{3,}|…/g, ' ')
    .replace(/\d{5,}$/, '')
    .replace(/\s{2,}/g, ' ')
    .trim() || t('traveler_word')
}

const tourReviews = computed<any[]>(() =>
  ((reviewsData.value as any)?.data || []).map((r: any) => ({ ...r, name: cleanReviewerName(r.name) }))
)
const showAllReviews = ref(false)

// Server aggregate over ALL published reviews; the client-side average of the
// first fetched page stays as fallback only. A tour with 57 reviews used to
// show "20 opiniones" because that's the page size.
const reviewsTotal = computed(() =>
  Number(tour.value?.reviews_count ?? 0) || tourReviews.value.length
)
const avgRating = computed(() => {
  if (tour.value?.rating != null) return Number(tour.value.rating).toFixed(1)
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

// --- In-page section navigation (sticky anchor bar) -----------------------
const sectionNav = computed(() => {
  const tv = tour.value
  const items: { id: string; label: string }[] = []
  if (tv?.long_description || tv?.description) items.push({ id: 'descripcion', label: 'Descripción' })
  if (tv?.itinerary) items.push({ id: 'itinerario', label: 'Itinerario' })
  if (tv?.what_includes || tv?.what_not_includes) items.push({ id: 'incluye', label: 'Incluye' })
  items.push({ id: 'ubicacion', label: 'Ubicación' })
  if (tourReviews.value.length) items.push({ id: 'reviews', label: 'Opiniones' })
  return items
})
const activeSection = ref('')
function scrollToSection(id: string) {
  if (import.meta.client) document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
// Highlight the section currently in view.
let sectionObserver: IntersectionObserver | null = null
onMounted(() => {
  if (typeof IntersectionObserver === 'undefined') return
  sectionObserver = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting) activeSection.value = (e.target as HTMLElement).id
  }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 })
  nextTick(() => {
    for (const s of sectionNav.value) {
      const el = document.getElementById(s.id)
      if (el) sectionObserver!.observe(el)
    }
  })
})
onBeforeUnmount(() => sectionObserver?.disconnect())

// Booking widget state
const selectedDate = ref('')
const selectedTime = ref('')
const adults = ref(2)
const children = ref(0)
const mobileBookingRef = ref<HTMLElement | null>(null)

// One validation source for both the mobile inline panel and the desktop
// sticky widget — localized, shown inline (no browser alert()).
const { error: bookingError, validate: validateBooking } = useBookingValidation()

// Sticky bottom-bar CTA: if the user already picked date+time we proceed to
// checkout; otherwise we scroll them up to the inline booking panel so they
// can finish configuring without losing scroll context.
function onMobileBottomCta() {
  // Tours requiring availability verification open the inquiry modal directly.
  if (tour.value?.require_availability) {
    inquiryOpen.value = true
    return
  }
  if (selectedDate.value && selectedTime.value) {
    handleBooking()
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

// Clear the booking error as soon as the user fixes the missing field.
watch([selectedDate, selectedTime], () => { if (bookingError.value) bookingError.value = '' })

// A date change can invalidate the chosen time (anticipation window) — force
// re-picking instead of silently submitting an unbookable departure.
watch(selectedDate, () => {
  if (selectedTime.value && tour.value && !isDepartureBookable(tour.value, selectedDate.value, selectedTime.value)) {
    selectedTime.value = ''
  }
})

// Pricing model — a tour defines `price_details[]`, one row per
// (age_stage, quantity-tier). We group active rows by age stage and order the
// stages by `age_stage_id` ASC. The backend treats the FIRST stage (lowest id)
// as the primary/adult stage (see Tour::getMinPriceAttribute), and legacy prod
// data sometimes mislabels the text description ("Niño"/"Adulto") — so we trust
// the id order, NOT the label. Within a stage the per-person price drops as the
// group grows (quantity tiers).
const priceStages = computed(() => {
  const details = (tour.value?.price_details || []).filter((p: any) => p.active)
  const byStage: Record<string, { id: any; desc: string; minAge: number | null; maxAge: number | null; tiers: any[] }> = {}
  for (const p of details) {
    const key = String(p.age_stage_id ?? '0')
    if (!byStage[key]) byStage[key] = {
      id: p.age_stage_id,
      desc: p.age_stage?.description || '',
      minAge: p.age_stage?.min_age ?? null,
      maxAge: p.age_stage?.max_age ?? null,
      tiers: [],
    }
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

// "(16-99)" age-range labels from the admin's age stages; empty when unset.
function ageRangeLabel(stage: { minAge: number | null; maxAge: number | null } | null): string {
  if (!stage) return ''
  const min = stage.minAge, max = stage.maxAge
  if (min == null && max == null) return ''
  if (min != null && max != null) return `(${min}-${max})`
  if (min != null) return `(${min}+)`
  return `(0-${max})`
}
// The same legacy data that mislabels stage descriptions also carries junk
// age ranges (e.g. the adult/primary stage saying "0-3" and the child one
// "18-99"). Pricing is unaffected (it goes by stage order), but don't SHOW
// ranges that contradict the roles: an "adult" band must reach adulthood and
// sit above the child band. Otherwise showing nothing beats showing nonsense.
const ageRangesCoherent = computed(() => {
  const a = adultStage.value, c = childStage.value
  if (!a) return false
  if (a.maxAge != null && a.maxAge < 12) return false
  if (c && a.minAge != null && c.maxAge != null && a.minAge < c.maxAge) return false
  return true
})
const adultAgeLabel = computed(() => ageRangesCoherent.value ? ageRangeLabel(adultStage.value) : '')
const childAgeLabel = computed(() => ageRangesCoherent.value ? ageRangeLabel(childStage.value) : '')

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
// admin Step 6, evaluated against the actual departure TIMES (a day-granular
// cutoff still offered today's already-departed tours). Shared logic with the
// cart editor — see composables/useBookingWindow.ts.
const minDate = computed(() => tour.value ? minBookableDateFor(tour.value) : new Date().toISOString().split('T')[0])

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
  // The badge used to read "HP GMT-5" — an internal code nobody outside the
  // office recognizes. The country name is what a traveller understands.
  if (tz === 'America/Lima') return { code: 'Perú', gmt: 'GMT-5', name: t('peruvian_time') }
  if (tz === 'America/La_Paz') return { code: 'Bolivia', gmt: 'GMT-4', name: t('bolivian_time') }
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
    // Fallback departure slots when the tour has no configured times.
    // NOTE: use `defaultDur` — a stray `dur` here was an undeclared variable
    // that threw a ReferenceError and broke the time selector for such tours.
    times.push(
      { value: '06:00', label: `06:00 AM${defaultDur}` },
      { value: '08:00', label: `08:00 AM${defaultDur}` },
      { value: '09:00', label: `09:00 AM${defaultDur}` },
      { value: '10:00', label: `10:00 AM${defaultDur}` }
    )
  }

  // On the selected date, hide departures inside the anticipation window
  // (e.g. today's 6:45 AM that already left).
  if (!selectedDate.value) return times
  return times.filter(x => isDepartureBookable(tour.value, selectedDate.value, x.value))
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
// Quantity +/- now lives in <TourQuantityStepper> (v-model + :at-max), so the
// old increment/decrement handlers were removed as dead code.

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

// --- Quick-facts chips shown under the title (difficulty / audience / guide) ---
const difficultyLabel = computed(() => {
  const k = String(tour.value?.difficulty || '').toLowerCase()
  const norm: Record<string, string> = { easy: 'easy', moderate: 'moderate', hard: 'hard', difficult: 'hard' }
  return norm[k] ? t(`difficulty_${norm[k]}`) : ''
})
const difficultyColor = computed(() => {
  const k = String(tour.value?.difficulty || '').toLowerCase()
  if (k === 'easy') return 'text-trust'
  if (k === 'moderate') return 'text-bestseller'
  if (k === 'hard' || k === 'difficult') return 'text-urgency'
  return 'text-slate-600'
})
const difficultyDesc = computed(() => {
  const k = String(tour.value?.difficulty || '').toLowerCase()
  const map: Record<string, string> = {
    easy: 'Apto para todo tipo de viajeros. Sin esfuerzo físico significativo, caminatas cortas y ritmo tranquilo.',
    moderate: 'Requiere buena condición física básica. Incluye caminatas de mediana duración y algunos desniveles.',
    hard: 'Para viajeros activos. Caminatas largas, terrenos irregulares y mayor exposición a la altitud. Se requiere buena condición física.',
  }
  return map[k === 'difficult' ? 'hard' : k] || ''
})
const audienceLabel = computed(() => {
  const map: Record<string, string> = { all: 'Todos los públicos', families: 'Familias', adults: 'Adultos', adventure: 'Aventureros', seniors: 'Adultos mayores' }
  return map[String(tour.value?.target_audience || '')] || ''
})
const guideSummary = computed(() => {
  const type = tour.value?.guide_type
  if (!type || type === 'none' || type === 'no_guide') return ''
  const esLangs: Record<number, string> = { 1: 'Español', 2: 'Inglés', 3: 'Francés', 4: 'Alemán', 5: 'Portugués', 6: 'Italiano' }
  const langs = (tour.value?.guide_languages || []).map((id: number) => esLangs[id]).filter(Boolean)
  const typeEs: Record<string, string> = { live_guide: 'Guía en vivo', audio_guide: 'Audioguía', informative_brochures: 'Folletos informativos' }
  const base = typeEs[type] || 'Guía'
  return type === 'live_guide' && langs.length ? `${base}: ${langs.join(', ')}` : base
})

// Add the current configuration (date/time/pax) to the cart and stay on the
// page. cartFeedback drives a transient inline message: 'added' when the line
// was created, 'duplicate' when the same tour+date+time is already in the
// cart (cart store dedupes), or null while idle. Returns false only when
// required fields are missing (the caller bails out).
type CartFeedback = 'added' | 'duplicate' | null
const cartFeedback = ref<CartFeedback>(null)
let cartFeedbackTimer: any = null
function handleAddToCart(): boolean {
  // Inline, localized validation (replaces the old English browser alerts).
  if (!validateBooking(selectedDate.value, selectedTime.value)) return false

  const tourImage = tour.value?.media_gallery && tour.value.media_gallery.length > 0
    ? getImageUrl(tour.value.media_gallery[0].url)
    : ''

  // `total` already accounts for adults, children and any active offer
  // discount (tax is added downstream by the cart store).
  const outcome = cartStore.addItem({
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

  cartFeedback.value = outcome
  if (import.meta.client) {
    clearTimeout(cartFeedbackTimer)
    cartFeedbackTimer = setTimeout(() => { cartFeedback.value = null }, 3000)
  }
  // 'added' and 'duplicate' both count as success for the caller (Reservar
  // flow): in either case the desired tour+date is already in the cart.
  return true
}

// "Reservar ahora": add to cart and go to checkout.
function handleBooking() {
  if (handleAddToCart()) navigateTo('/cart')
}

// One-way props for the shared <TourBookingCard> (the four inputs use v-model).
// Spread with v-bind so the mobile + desktop instances stay identical.
const bookingProps = computed(() => ({
  tour: tour.value,
  adultPrice: adultPrice.value,
  childPrice: childPrice.value,
  basePrice: basePrice.value,
  subtotal: subtotal.value,
  total: total.value,
  groupDiscount: groupDiscount.value,
  hasChildPricing: hasChildPricing.value,
  adultAgeLabel: adultAgeLabel.value,
  childAgeLabel: childAgeLabel.value,
  maxPax: maxPax.value,
  totalPax: totalPax.value,
  minDate: minDate.value,
  availableTimes: availableTimes.value,
  activeOffer: activeOffer.value,
  tzInfo: tzInfo.value,
  error: bookingError.value,
  cartFeedback: cartFeedback.value,
}))


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
// i18n.baseUrl, so we don't set a per-page canonical here (avoids duplicates)
// — EXCEPT on child variant pages, where the canonical must point at the
// parent activity to consolidate ranking signals (and the page is noindex'd).
const siteUrl = 'https://incalake.com'
const canonicalUrl = computed(() =>
  `${siteUrl}/${locale.value}/${tour.value?.city?.slug || citySlug}/${slug}`)
const parentCanonicalUrl = computed(() =>
  tour.value?.parent_canonical ? `${siteUrl}/${locale.value}${tour.value.parent_canonical}` : null)
const isChildVariant = computed(() => !!tour.value?.parent_canonical)

watchEffect(() => {
  if (!tour.value) return
  const url = canonicalUrl.value
  const imageUrl = tour.value.featured_image ? getImageUrl(tour.value.featured_image) : ''
  const cityName = tour.value.city?.name || 'Puno'
  const citySlugVal = tour.value.city?.slug || citySlug
  const localeHome = `${siteUrl}/${locale.value}`

  // SEO driven by the admin's per-tour fields (Step 2), with sensible fallbacks
  // so nothing breaks when they're empty.
  const seoTitle = () => tour.value.meta_title || tour.value.title
  const seoDescription = () => tour.value.meta_description || tour.value.short_description || tour.value.title
  const seoKeywords = () => Array.isArray(tour.value.keywords) && tour.value.keywords.length
    ? tour.value.keywords.join(', ')
    : `${tour.value.title}, tours ${cityName}, lago titicaca, peru`

  useSeoMeta({
    title: seoTitle,
    description: seoDescription,
    keywords: seoKeywords,
    robots: () => isChildVariant.value
      ? 'noindex, follow'
      : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
    author: 'Incalake Tours',
    ogTitle: seoTitle,
    ogDescription: seoDescription,
    ogType: 'website',
    ogUrl: () => url,
    ogSiteName: 'Incalake Tours',
    ogImage: () => imageUrl,
    ogImageWidth: 1200,
    ogImageHeight: 630,
    ogImageAlt: () => tour.value.title,
    ogLocale: () => locale.value,
    twitterCard: 'summary_large_image',
    twitterTitle: seoTitle,
    twitterDescription: seoDescription,
    twitterImage: () => imageUrl,
  })

  useHead({
    // On child variants, override the global canonical (emitted by
    // useLocaleHead) so it points to the parent activity instead of the
    // variant URL — consolidates ranking on the indexable page.
    link: isChildVariant.value && parentCanonicalUrl.value
      ? [{ rel: 'canonical', href: parentCanonicalUrl.value }]
      : [],
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
          // Variant grouping: surface every option of this activity so AI
          // crawlers (Perplexity, ChatGPT, Bard) see all variants from a
          // single page request. Each variant carries its own price + URL.
          ...(Array.isArray(tour.value.options) && tour.value.options.length >= 2 ? {
            hasVariant: tour.value.options.map((o: any) => ({
              '@type': 'Product',
              name: `${tour.value.title} — ${o.option_label || (o.is_parent ? 'Estándar' : 'Variante')}`,
              url: `${siteUrl}/${locale.value}/${o.city_slug}/${o.slug}`,
              offers: o.min_price ? {
                '@type': 'Offer',
                price: o.min_price,
                priceCurrency: tour.value.currency || 'USD',
                availability: 'https://schema.org/InStock',
              } : undefined,
            })),
          } : {}),
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
