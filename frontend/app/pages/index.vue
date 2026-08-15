<template>
  <div class="bg-white font-display text-slate-900 min-h-screen">
    <!-- Premium Hero Section -->
    <section class="relative w-full h-[430px] sm:h-[540px] md:h-[620px] flex flex-col items-center justify-center p-4 sm:p-12">
      <div class="absolute inset-0 z-0 overflow-hidden">
        <!--
          Hero is the LCP candidate. The previous (sizes='100vw' +
          densities='1x 2x') combo made @nuxt/image emit a broken srcset
          ("&w=320 1w, &w=320 2w") on Vercel — both 1x and 2x descriptors
          pointed at a 320-wide variant, which then got upscaled to the
          1920px viewport and looked visibly blurry. Stripping back to
          width-only restores a single high-quality variant for everyone;
          mobile downloads a larger asset than strictly needed (~150 KB
          more on 3G) but the hero is the LCP candidate and sharpness
          matters more than that delta for the visual first impression.
        -->
        <NuxtImg
          :src="heroImage"
          class="absolute w-full h-full object-cover"
          alt="Lake Titicaca"
          width="1920"
          height="1080"
          format="webp"
          quality="82"
          loading="eager"
          fetchpriority="high"
        />

        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/60"></div>
      </div>

      <div class="relative z-10 text-center max-w-4xl px-4 mb-6 md:mb-8">
        <!-- The page's ONE content h1 (the navbar/footer logos are spans now) -->
        <h1 class="text-white text-3xl sm:text-5xl md:text-6xl font-black leading-[1.1] tracking-tighter mb-3 md:mb-4 drop-shadow-2xl">
          {{ c('hero', 'title', 'home_hero_title') }}
        </h1>
        <!-- Was text-white/85 + line-clamp-2: over the bright water in the photo
             it read as grey, and on a 390px screen the clamp chopped the
             sentence mid-word. Full white with a shadow, and a third line on
             small screens so the sentence finishes. -->
        <p class="text-white text-sm sm:text-lg md:text-xl font-medium max-w-2xl mx-auto line-clamp-3 sm:line-clamp-2 drop-shadow-lg">
          {{ c('hero', 'subtitle', 'home_hero_subtitle') }}
        </p>
      </div>

      <!-- Search Bar - simplified on mobile -->
      <div class="relative w-full max-w-3xl px-4 sm:px-6" style="z-index: 60;">
        <div class="bg-white shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] rounded-xl sm:rounded-2xl p-1.5 sm:p-2 flex items-center gap-1.5 sm:gap-2">
          <div class="flex-1 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3">
            <Icon name="material-symbols:search" class="text-primary text-lg sm:text-xl" />
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
            class="btn-primary shrink-0 px-4 sm:px-8"
            :aria-label="c('search_btn', '', 'home_search_btn')"
          >
            <Icon name="material-symbols:search" class="text-lg" />
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
            <p class="px-5 pt-3 pb-1 text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('home_destinations_label') }}</p>
            <NuxtLink
              v-for="city in featuredCities"
              :key="city.id"
              :to="localePath(`/tours?city=${city.slug}`)"
              @click="showDropdown = false"
              class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/5 transition-colors"
            >
              <div class="size-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <Icon name="material-symbols:location-on-outline" class="text-base" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-800">{{ city.name }}</p>
                <p class="text-[10px] text-slate-500 font-medium">{{ city.country_code === 'PE' ? 'Peru' : 'Bolivia' }}</p>
              </div>
            </NuxtLink>
          </template>

          <!-- Search results -->
          <template v-else>
            <!-- Loading -->
            <div v-if="searching" class="px-5 py-4 flex items-center gap-3">
              <Icon name="material-symbols:progress-activity" class="text-slate-400 animate-spin text-lg" />
              <span class="text-sm text-slate-400">{{ t('search_searching') }}</span>
            </div>

            <!-- Results -->
            <template v-else-if="searchResults.length > 0">
              <p class="px-5 pt-3 pb-1 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                {{ searchResults.length }}+ {{ t('search_results_suffix') }}
              </p>
              <NuxtLink
                v-for="result in searchResults"
                :key="result.id"
                :to="getTourLink(result)"
                @mouseenter="prefetchTour(result)"
                @focus="prefetchTour(result)"
                @click="showDropdown = false; searchQuery = ''"
                class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/5 transition-colors"
              >
                <img
                  v-if="result.featured_image"
                  :src="getImageUrl(result.featured_image)"
                  :alt="result.title || ''"
                  width="40" height="40" loading="lazy" decoding="async"
                  class="w-10 h-10 rounded-lg object-cover shrink-0"
                />
                <div v-else class="size-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                  <Icon name="material-symbols:tour-outline" class="text-slate-300 text-sm" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate">{{ result.title }}</p>
                  <p class="text-[10px] text-slate-500 font-medium">
                    {{ result.city?.name || 'Puno' }}
                    <span v-if="result.min_price"> · {{ currencyStore.formatConverted(result.min_price || 0) }}</span>
                  </p>
                </div>
              </NuxtLink>

              <!-- View all -->
              <button
                @click="goToTours"
                class="w-full px-5 py-3 text-sm font-bold text-primary hover:bg-primary/5 transition-colors border-t border-slate-100 flex items-center justify-center gap-1"
              >
                {{ t('search_view_all_for') }} "{{ searchQuery }}"
                <Icon name="material-symbols:arrow-forward" class="text-sm" />
              </button>
            </template>

            <!-- No results -->
            <div v-else class="px-5 py-6 text-center">
              <Icon name="material-symbols:search-off" class="text-slate-300 text-3xl mb-1" />
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
      <!-- Mobile: the chips WRAP instead of scrolling. The hidden-scrollbar
           row (even with an edge fade) still read as a chopped-off chip, and
           four short chips fit comfortably in two centered lines. -->
      <div class="md:hidden relative">
        <div class="px-4">
          <div class="flex flex-wrap items-center justify-center gap-2">
            <div v-for="(signal, idx) in trustSignals" :key="idx"
              class="flex items-center gap-1.5 px-3 py-2 bg-slate-50 rounded-full shrink-0"
            >
              <Icon :name="msIcon(signal.icon)" class="text-sm"
                :class="[idx === 0 ? 'text-orange-500' : idx === 1 ? 'text-green-500' : 'text-blue-500']"
              />
              <span class="text-[11px] font-bold text-slate-700 whitespace-nowrap">{{ signal.title }}</span>
            </div>
            <!-- Real Google rating — this used to be a hardcoded 4.9 sitting a
                 few scrolls above the genuine Google section saying 4.8. -->
            <div v-if="googleRating" class="flex items-center gap-1 px-3 py-2 bg-yellow-50 rounded-full shrink-0">
              <Icon name="material-symbols:star" class="text-yellow-500 text-sm" />
              <span class="text-[11px] font-black text-slate-700">{{ Number(googleRating).toFixed(1) }}/5</span>
            </div>
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
                <Icon :name="msIcon(signal.icon)" class="text-2xl font-bold" />
              </div>
              <div>
                <h5 class="text-sm font-bold text-slate-900">{{ signal.title }}</h5>
                <p class="text-xs text-slate-500 font-medium">{{ signal.description }}</p>
              </div>
            </div>
            <div v-if="googleRating" class="flex items-center gap-1.5">
              <CommonStarRating :value="Number(googleRating)" :total="5" />
              <span class="text-[10px] font-black text-slate-500 ml-1">{{ Number(googleRating).toFixed(1) }}/5</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Explore by Destination (GetYourGuide style) -->
    <section class="py-8 md:py-16 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-5 md:mb-10">
          <h3 class="section-title">{{ c('destinations', 'title', 'home_destinations_title') }}</h3>
        </div>

        <div v-if="cities.length" class="grid grid-cols-3 md:grid-cols-6 gap-3 sm:gap-5">
          <NuxtLink
            v-for="city in featuredCities"
            :key="city.id"
            :to="localePath(`/tours?city=${city.slug}`)"
            class="group flex flex-col items-center gap-3"
          >
            <div class="w-full aspect-square rounded-2xl overflow-hidden shadow-md group-hover:shadow-xl group-hover:-translate-y-1 transition-all duration-300 bg-slate-100">
              <NuxtImg
                v-if="getCityImage(city)"
                v-skeleton
                :src="getCityImage(city)"
                :alt="city.name"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                loading="lazy" format="webp" width="240" height="240"
                sizes="33vw md:16vw"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <Icon name="material-symbols:image-outline" class="text-slate-300 text-3xl" />
              </div>
            </div>
            <h5 class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors">{{ city.name }}</h5>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Featured Tours -->
    <section class="py-8 md:py-12 px-4 md:px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between gap-3 mb-6 md:mb-8">
          <div class="min-w-0">
            <p class="section-label mb-1.5">{{ c('featured', 'label', 'home_featured_label') }}</p>
            <h3 class="section-title">{{ c('featured', 'title', 'home_featured_title') }}</h3>
          </div>
          <NuxtLink :to="localePath('/tours')" class="group flex items-center gap-1.5 bg-white px-4 sm:px-5 min-h-[44px] rounded-xl shadow-sm border border-slate-100 hover:border-primary/50 transition-all font-bold text-xs sm:text-sm shrink-0">
            {{ c('view_all', '', 'home_view_all') }}
            <Icon name="material-symbols:arrow-forward" class="group-hover:translate-x-1 transition-transform text-lg" />
          </NuxtLink>
        </div>

        <div v-if="toursPending" class="flex items-center justify-center py-20">
          <div class="spinner size-10"></div>
        </div>

        <div v-else class="flex md:grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0">
          <!-- md shows 3 columns, so the 4th card would sit orphaned on its
               own row — it hides in that window and returns at lg (4 cols). -->
          <TourCard
            v-for="(tour, idx) in recommendedTours"
            :key="tour.id"
            :tour="tour"
            show-difficulty
            class="shrink-0 w-[78%] sm:w-[45%] md:w-auto snap-start"
            :class="idx === 3 ? 'md:max-lg:hidden' : ''"
          />
        </div>
      </div>
    </section>

    <!-- Our Offers (only if 4+ tours have active offers) -->
    <section v-if="toursWithOffers.length >= 4" class="py-8 md:py-12 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between gap-3 mb-6 md:mb-8">
          <div class="min-w-0">
            <p class="text-green-600 font-black uppercase tracking-[0.2em] text-[11px] mb-1.5">
              <Icon name="material-symbols:sell-outline" class="text-xs align-middle" />
              {{ t('home_offers_label') }}
            </p>
            <h3 class="section-title">{{ t('home_offers_title') }}</h3>
          </div>
          <NuxtLink :to="localePath('/tours')" class="group flex items-center gap-1.5 bg-white px-4 sm:px-5 min-h-[44px] rounded-xl shadow-sm border border-slate-100 hover:border-green-500/50 transition-all font-bold text-xs sm:text-sm text-green-700 shrink-0">
            {{ c('view_all', '', 'home_view_all') }}
            <Icon name="material-symbols:arrow-forward" class="group-hover:translate-x-1 transition-transform text-lg" />
          </NuxtLink>
        </div>

        <div class="flex md:grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0">
          <TourCard
            v-for="tour in toursWithOffers.slice(0, 4)"
            :key="tour.id"
            :tour="tour"
            accent="green"
            :offer-label="getOfferLabel(tour)"
            class="shrink-0 w-[78%] sm:w-[45%] md:w-auto snap-start"
          />
        </div>
      </div>
    </section>

    <!-- Testimonials Slider -->
    <section v-if="featuredReviews.length > 0" class="py-8 md:py-12 px-4 md:px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <CommonSectionHeader
          :label="c('testimonials', 'label', 'home_testimonials_label')"
          :title="c('testimonials', 'title', 'home_testimonials_title')"
          show-arrows
          @scroll="scrollReviews"
        />

        <!-- Native scroll-snap carousel: swipe on mobile, arrows on desktop -->
        <div ref="reviewsScroll" class="flex gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2">
            <div
              v-for="review in featuredReviews"
              :key="review.id"
              class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 hover:shadow-lg transition-shadow shrink-0 snap-start w-[85%] sm:w-[60%] md:w-[calc(33.333%-16px)]"
            >
              <CommonStarRating :value="review.rating" class="mb-3" />
              <p v-if="review.title" class="text-sm font-bold text-slate-800 mb-2 line-clamp-1">{{ review.title }}</p>
              <p class="text-xs text-slate-500 leading-relaxed line-clamp-4 mb-4">{{ review.comment }}</p>
              <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                  <p class="text-xs font-bold text-slate-700">{{ review.name }}</p>
                  <p class="text-[10px] text-slate-500">{{ review.review_date }}</p>
                </div>
                <NuxtLink
                  v-if="review.tour_id && review.tour?.translations?.length"
                  :to="localePath(`/${review.tour.city?.slug || 'puno'}/${review.tour.translations[0].slug}`)"
                  class="text-[10px] text-primary font-semibold truncate max-w-[140px] hover:underline flex items-center gap-0.5"
                >
                  {{ review.opinion || review.tour.translations[0].h1_title }}
                  <Icon name="material-symbols:arrow-forward" class="text-[10px]" />
                </NuxtLink>
              </div>
            </div>
        </div>
      </div>
    </section>

    <!-- Google Reviews — slider (best-rated first, swipe / arrows for more),
         lazy fetch from our cached backend endpoint. Hidden until non-empty. -->
    <section v-if="googleReviews.length" class="py-8 md:py-12 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between gap-4 mb-6 md:mb-10">
          <div class="flex items-center gap-3 min-w-0">
            <!-- Google "G" mark (inline SVG — no icon-set dependency) -->
            <svg viewBox="0 0 48 48" class="size-7 shrink-0" aria-hidden="true">
              <path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>
              <path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>
              <path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34A21.99 21.99 0 0 0 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z"/>
              <path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>
            </svg>
            <div class="min-w-0">
              <h3 class="section-title">{{ t('google_reviews_title') }}</h3>
              <p class="flex items-center gap-x-2 gap-y-0.5 text-sm text-slate-500 flex-wrap">
                <span v-if="googleRating" class="inline-flex items-center gap-1.5">
                  <span class="font-black text-slate-800 tabular-nums">{{ googleRating }}</span>
                  <CommonStarRating :value="googleRating" :total="5" />
                  <span>· {{ googleTotal }} {{ t('reviews_count_label') }}</span>
                </span>
                <a v-if="googlePlaceUrl" :href="googlePlaceUrl" target="_blank" rel="noopener noreferrer" class="font-bold text-primary hover:underline inline-flex items-center gap-0.5">
                  {{ t('view_on_google') }} <Icon name="material-symbols:open-in-new" class="text-sm" />
                </a>
              </p>
            </div>
          </div>
          <!-- Slider arrows (desktop) — only when there's more than one screenful -->
          <div v-if="googleReviews.length > 3" class="hidden sm:flex gap-2 shrink-0">
            <button @click="scrollGoogle(-1)" :aria-label="t('previous')" class="size-11 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all">
              <Icon name="material-symbols:chevron-left" class="text-lg" />
            </button>
            <button @click="scrollGoogle(1)" :aria-label="t('next')" class="size-11 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all">
              <Icon name="material-symbols:chevron-right" class="text-lg" />
            </button>
          </div>
        </div>

        <!-- Native scroll-snap carousel: 3 visible on desktop, swipe for more -->
        <div ref="googleScroll" class="flex gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2">
          <div
            v-for="(r, i) in googleReviews"
            :key="i"
            class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 hover:shadow-lg transition-shadow shrink-0 snap-start w-[85%] sm:w-[60%] md:w-[calc(33.333%-16px)]"
          >
            <div class="flex items-center gap-3 mb-3">
              <img v-if="r.profile_photo_url" :src="r.profile_photo_url" :alt="r.author_name" class="size-9 rounded-full shrink-0" loading="lazy" referrerpolicy="no-referrer" />
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ r.author_name }}</p>
                <CommonStarRating :value="r.rating" size="xs" />
              </div>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed line-clamp-5">{{ r.text }}</p>
            <p class="text-[10px] text-slate-400 mt-3">{{ r.relative_time }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Video Testimonials — lazy YouTube facade (the iframe loads only on
         click), placed below the text reviews so the homepage stays fast. -->
    <section v-if="videoTestimonials.length" class="py-8 md:py-12 px-4 md:px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <div class="mb-6 md:mb-10">
          <p class="section-label mb-2">{{ c('video_testimonials', 'label', 'home_video_label') }}</p>
          <h3 class="section-title">{{ c('video_testimonials', 'title', 'home_video_title') }}</h3>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5">
          <CommonLiteYouTube
            v-for="v in videoTestimonials"
            :key="v.id"
            :video-id="v.id"
            :title="v.title"
          />
        </div>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-8 md:py-12 px-4 md:px-6">
      <div class="max-w-7xl mx-auto">
        <h3 class="section-title text-center mb-6 md:mb-10">{{ c('why_title', '', 'home_why_title') }}</h3>
        <div class="flex md:grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-12 overflow-x-auto md:overflow-visible snap-x snap-mandatory md:snap-none scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0">
          <div v-for="(item, idx) in whyUsItems" :key="idx" class="flex flex-col items-center text-center shrink-0 w-[80%] sm:w-[55%] md:w-auto snap-start bg-slate-50 md:bg-transparent rounded-2xl md:rounded-none p-6 md:p-0">
            <!-- Was bg-slate-900: the only black element on a blue-and-white
                 page; primary keeps the section on brand. -->
            <div class="size-16 rounded-2xl bg-primary text-white flex items-center justify-center mb-6 shadow-xl shadow-primary/20">
              <Icon :name="msIcon(item.icon)" class="text-3xl" />
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
import { msIcon } from '~/utils/icons'
const { api } = useApi()
const { prefetchTour } = useTourPrefetch()
const config = useRuntimeConfig()
const { t, locale } = useI18n()
const localePath = useLocalePath()
const currencyStore = useCurrencyStore()

// Reuse cached fetch results when navigating BACK to this page (instant, no
// refetch). Still refetches when a watched dep (language) changes or on manual
// refresh — so content stays correct.
function getCachedData(key: string, nuxtApp: any, ctx: any) {
  if (ctx?.cause === 'watch' || ctx?.cause === 'refresh:manual') return undefined
  return nuxtApp.payload.data[key] ?? nuxtApp.static.data[key]
}

const searchQuery = ref('')
const searchResults = ref<any[]>([])
const showDropdown = ref(false)
const searching = ref(false)
const searchInputRef = ref<HTMLInputElement | null>(null)
let searchTimer: any = null
const langCode = computed(() => locale.value.toUpperCase())

// ── Non-blocking data fetching (fixes IPC connection closed on Windows) ──

// Page content from API (dynamic, admin-editable)
const { data: pageContentData } = useAsyncData(
  `home-content-${langCode.value}`,
  () => api(`/pages/home?language=${langCode.value}`).catch(() => null),
  { lazy: true, default: () => null, watch: [langCode], getCachedData }
)
const pageContent = computed(() => (pageContentData.value as any)?.data?.content || null)

// Reactive helper: get content from API or fall back to i18n
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
const { data: citiesResponse } = useAsyncData(
  'cities',
  () => api('/cities').catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }), getCachedData }
)
const cities = computed(() => (citiesResponse.value as any)?.data || [])

// Show only 5 main destinations
const featuredSlugs = ['puno', 'cusco', 'arequipa', 'la-paz', 'copacabana', 'uyuni']
const featuredCities = computed(() => {
  return featuredSlugs
    .map(slug => cities.value.find((c: any) => c.slug === slug))
    .filter(Boolean)
})

// Fetch featured tours by current language (lazy, non-blocking)
const { data: toursData, status: toursStatus } = useAsyncData(
  `tours-featured-${langCode.value}`,
  () => api(`/tours?active=1&per_page=8&light=1&language=${langCode.value}`).catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }), watch: [langCode], getCachedData }
)
const tours = computed(() => {
  const data = (toursData.value as any)?.data
  return Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
})

// The showcase used to be raw API order (newest first), which put the one
// imageless tour in the most visible slot on the page. Merchandise instead:
// photo + reviews beat photo alone beat neither; ties keep API order.
const recommendedTours = computed(() => {
  const score = (t: any) =>
    (t.featured_image ? 2 : 0) + (t.reviews_count > 0 && t.rating ? 1 : 0)
  return [...tours.value].sort((a, b) => score(b) - score(a)).slice(0, 4)
})
const toursPending = computed(() => toursStatus.value === 'pending')

// Tours with active offers (lazy, non-blocking)
const { data: allToursData } = useAsyncData(
  `tours-offers-${langCode.value}`,
  () => api(`/tours?active=1&per_page=100&light=1&language=${langCode.value}`).catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }), watch: [langCode], getCachedData }
)
const toursWithOffers = computed(() => {
  const data = (allToursData.value as any)?.data
  const allTours = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
  // Light payload precomputes the active offer server-side (tour.offer:{label}|null).
  return allTours.filter((tour: any) => !!tour.offer)
})

function getOfferLabel(tour: any) {
  return tour.offer?.label || ''
}

// Latest published reviews (lazy, non-blocking). The API orders by the real
// review date, so the slider stays fresh without curating a "featured" set;
// min_rating keeps low ratings off the homepage.
const { data: reviewsData } = useAsyncData(
  `reviews-latest-${locale.value}`,
  async () => {
    try {
      const res = await api(`/reviews?per_page=15&min_rating=4&language=${locale.value}`)
      const reviews = (res as any)?.data || []
      if (reviews.length < 3) {
        const res2 = await api(`/reviews?per_page=15&min_rating=4`)
        return (res2 as any)?.data || []
      }
      return reviews
    } catch { return [] }
  },
  { lazy: true, default: () => [], getCachedData }
)
// Imported reviews carry scraper artifacts: platform usernames with a long
// digit tail ("Roving41150014391") and mid-name ellipses ("Maria del
// Rosar... F"). Both read as fake reviews — keep the human part; if nothing
// survives, sign it as "Viajero".
function cleanReviewerName(raw: string): string {
  return String(raw || '')
    .replace(/\.{3,}|…/g, ' ')
    .replace(/\d{5,}$/, '')
    .replace(/\s{2,}/g, ' ')
    .trim() || t('traveler_word')
}

const featuredReviews = computed(() =>
  (reviewsData.value || []).map((r: any) => ({ ...r, name: cleanReviewerName(r.name) }))
)

// Google Places reviews (cached 12h server-side). Lazy so it never blocks the
// homepage; the section hides itself when the integration isn't configured.
const { data: googleData } = useAsyncData(
  `google-reviews-${locale.value}`,
  () => api(`/google-reviews?language=${locale.value}`).catch(() => ({ data: [], rating: null, total: 0 })),
  { lazy: true, default: () => ({ data: [], rating: null, total: 0 }), watch: [locale], getCachedData }
)
// Best-rated first so the 3 visible in the slider are the strongest reviews;
// ties fall back to most recent.
const googleReviews = computed(() => {
  const list = ((googleData.value as any)?.data || []) as any[]
  return [...list].sort((a, b) => (b.rating - a.rating) || (b.time - a.time))
})
const googleRating = computed(() => (googleData.value as any)?.rating || null)
const googleTotal = computed(() => (googleData.value as any)?.total || 0)
// Google reviews slider (same native scroll-snap pattern as the text reviews).
const { container: googleScroll, scroll: scrollGoogle } = useSnapScroll()
const googlePlaceUrl = computed(() => (googleData.value as any)?.place_url || null)
// Testimonials: native horizontal scroll-snap (swipe on mobile, arrows desktop).
const { container: reviewsScroll, scroll: scrollReviews } = useSnapScroll()

// Video testimonials (YouTube), rendered via the lazy <CommonLiteYouTube>
// facade so their iframes never touch the initial page load. Accepts full URLs
// or bare IDs. The section hides itself when the list is empty.
function ytId(urlOrId: string): string {
  const m = urlOrId.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([\w-]{11})/)
  return m ? m[1] : urlOrId
}
const videoTestimonials = computed(() =>
  ([
    { url: 'https://www.youtube.com/watch?v=sUVaxTj9UI4', title: '' },
    { url: 'https://www.youtube.com/watch?v=MuAQ4OaNhpQ', title: '' },
    { url: 'https://www.youtube.com/watch?v=UuWqDJY5lXc', title: '' },
    { url: 'https://www.youtube.com/watch?v=pOZDbv39fU0', title: '' },
  ]).map(v => ({ id: ytId(v.url), title: v.title })),
)

// Hero image: from API or default
// Stable fallback = the real hero on our own CDN (NOT a fragile Google design-tool
// URL). It's the same image pageContent.hero.image normally returns, so even if
// the page content fails to load there's no broken placeholder and no jarring swap.
const defaultHeroImage = 'https://api.incalake.com/storage/pages/79770cd8-69a2-4da3-9e42-50fda4ca5ceb.webp'
const heroImage = computed(() => pageContent.value?.hero?.image || defaultHeroImage)

// Dynamic content from API with i18n fallback
const trustSignals = computed(() => {
  if (pageContent.value?.trust_signals?.length) return pageContent.value.trust_signals
  // "Cancelación Gratuita 24h" was dropped by request: the real standard
  // policy is 20% up to 48h before / no refund inside 48h, so the chip
  // promised something the checkout would contradict. Local-operator cred
  // is true and differentiating, so it takes the slot.
  return [
    { icon: 'location_on', title: t('home_trust_local'), description: t('home_trust_local_desc') },
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

// Each destination tile shows the photo of a real tour in that city, served by
// /api/cities. It replaced a hardcoded Unsplash map where Puno and Copacabana
// shared a photo and Cusco and Juliaca both showed Machu Picchu.
function getCityImage(city: any) {
  return city?.image ? getImageUrl(city.image) : ''
}

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

function getTourLink(tour: any) {
  const slug = tour.slug || tour.id
  const citySlug = tour.city?.slug || 'puno'
  return localePath(`/${citySlug}/${slug}`)
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
