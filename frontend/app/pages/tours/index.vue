<template>
  <div class="bg-white font-display text-slate-900 min-h-screen pt-14 md:pt-20">

    <!-- Compact Hero -->
    <section class="bg-gradient-to-r from-primary to-sky-600 text-white px-4 sm:px-6 lg:px-10 py-4 md:py-8">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-xl md:text-3xl font-black mb-0.5">
          {{ selectedCitySlug ? `Tours ${formatCityName(selectedCitySlug)}` : t('hero_title') }}
        </h1>
        <p class="text-white/70 text-xs md:text-sm">{{ pending && !tours.length ? '…' : t('tours_found', { count: filteredTours.length }) }}</p>
      </div>
    </section>

    <!-- MOBILE: Sticky filter bar with text pills -->
    <div class="md:hidden sticky top-[56px] z-30 bg-white border-b border-slate-200 shadow-sm">
      <div class="px-3 py-2">
        <!-- Search -->
        <div class="relative mb-2">
          <Icon name="material-symbols:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
          <input v-model="searchQuery" type="text" :placeholder="t('search_placeholder')"
            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-xs" />
        </div>
        <!-- Filter pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-hide">
          <button @click="mobileSheet = 'city'"
            :class="selectedCitySlug ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200'"
            class="flex items-center gap-1 px-3.5 py-2 border rounded-full text-xs font-semibold whitespace-nowrap shrink-0">
            {{ selectedCitySlug ? formatCityName(selectedCitySlug) : t('destination') }}
            <Icon name="material-symbols:expand-more" class="text-[10px]" />
          </button>
          <button @click="mobileSheet = 'duration'"
            :class="selectedDuration ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200'"
            class="flex items-center gap-1 px-3.5 py-2 border rounded-full text-xs font-semibold whitespace-nowrap shrink-0">
            {{ selectedDuration ? durationLabels[selectedDuration] : t('duration') }}
            <Icon name="material-symbols:expand-more" class="text-[10px]" />
          </button>
          <button @click="mobileSheet = 'price'"
            :class="selectedPrice ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200'"
            class="flex items-center gap-1 px-3.5 py-2 border rounded-full text-xs font-semibold whitespace-nowrap shrink-0">
            {{ selectedPrice ? priceLabels[selectedPrice] : t('price') }}
            <Icon name="material-symbols:expand-more" class="text-[10px]" />
          </button>
          <button @click="mobileSheet = 'place'"
            :class="selectedPlace ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200'"
            class="flex items-center gap-1 px-3.5 py-2 border rounded-full text-xs font-semibold whitespace-nowrap shrink-0">
            <Icon name="material-symbols:location-on" class="text-xs" />
            {{ selectedPlace || t('places') }}
            <Icon name="material-symbols:expand-more" class="text-[10px]" />
          </button>
          <button v-if="hasActiveFilters" @click="clearFilters" class="text-red-500 text-[11px] font-bold whitespace-nowrap shrink-0 px-2 py-2">{{ t('clear_all') }}</button>
        </div>
      </div>
    </div>

    <!-- DESKTOP: Sticky filter bar with full pills -->
    <div class="hidden md:block sticky top-[68px] z-30 bg-white border-b border-slate-200 shadow-sm overflow-visible">
      <div class="max-w-7xl mx-auto px-6 lg:px-10 py-2.5 overflow-visible">
        <div class="flex items-center gap-2">
          <div class="relative shrink-0">
            <Icon name="material-symbols:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base" />
            <input v-model="searchQuery" type="text" :placeholder="t('search_placeholder')"
              class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-xs font-medium focus:ring-2 focus:ring-primary/30 w-48 focus:w-64 transition-all" />
          </div>
          <div class="w-px h-6 bg-slate-200 shrink-0"></div>
          <!-- Desktop dropdowns -->
          <div v-for="filter in desktopFilters" :key="filter.key" class="relative shrink-0">
            <button @click="openFilter = openFilter === filter.key ? '' : filter.key"
              :class="filter.isActive ? 'bg-primary text-white border-primary' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
              class="flex items-center gap-1.5 px-3.5 py-2 border rounded-full text-xs font-semibold transition-all">
              <Icon :name="msIcon(filter.icon)" class="text-sm" />
              {{ filter.label }}
              <Icon name="material-symbols:expand-more" class="text-xs" />
            </button>
            <div v-if="openFilter === filter.key" class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-[40] py-1" :class="filter.key === 'sort' ? 'right-0 left-auto w-48' : 'w-48'">
              <button v-for="opt in filter.options" :key="opt.value" @click="filter.select(opt.value); openFilter = ''"
                class="w-full text-left px-4 py-2 text-xs font-semibold hover:bg-primary/5 transition-colors"
                :class="filter.current === opt.value ? 'text-primary bg-primary/5' : 'text-slate-600'">
                {{ opt.label }}
              </button>
            </div>
          </div>
          <div class="flex-1"></div>
          <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs font-semibold text-red-500 flex items-center gap-1">
            <Icon name="material-symbols:close" class="text-sm" /> {{ t('clear_all') }}
          </button>
          <span v-if="tours.length" class="text-xs font-bold text-slate-400 mr-2">{{ t('tours_found', { count: filteredTours.length }) }}</span>
          <div class="flex items-center border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
            <button
              @click="viewMode = 'grid'"
              class="p-1.5 transition-colors"
              :class="viewMode === 'grid' ? 'bg-primary text-white' : 'bg-white text-slate-400 hover:text-slate-600'"
            >
              <Icon name="material-symbols:grid-view-outline" class="text-base" />
            </button>
            <button
              @click="viewMode = 'list'"
              class="p-1.5 transition-colors"
              :class="viewMode === 'list' ? 'bg-primary text-white' : 'bg-white text-slate-400 hover:text-slate-600'"
            >
              <Icon name="material-symbols:view-list-outline" class="text-base" />
            </button>
          </div>
        </div>
      </div>
      <div v-if="openFilter" class="fixed inset-0" @click="openFilter = ''"></div>
    </div>

    <!-- Mobile Bottom Sheets -->
    <Teleport to="body">
      <Transition name="sheet">
        <div v-if="mobileSheet" class="md:hidden fixed inset-0 z-50">
          <div class="absolute inset-0 bg-black/50" @click="mobileSheet = ''"></div>
          <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[60vh] overflow-y-auto shadow-2xl">
            <div class="flex justify-center pt-3 pb-1"><div class="w-10 h-1 bg-slate-300 rounded-full"></div></div>
            <div class="p-5">
              <h3 class="text-base font-bold text-slate-800 mb-4">
                {{ mobileSheet === 'city' ? t('destination') : mobileSheet === 'duration' ? t('duration') : mobileSheet === 'price' ? t('price') : t('places') }}
              </h3>
              <div class="space-y-1">
                <template v-if="mobileSheet === 'city'">
                  <button @click="selectedCitySlug = ''; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="!selectedCitySlug ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('all_destinations') }}</button>
                  <button v-for="city in cities" :key="city.slug" @click="selectedCitySlug = city.slug; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedCitySlug === city.slug ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">
                    {{ city.name }} <span class="text-slate-400">({{ city.count }})</span>
                  </button>
                </template>
                <template v-else-if="mobileSheet === 'duration'">
                  <button @click="selectedDuration = ''; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="!selectedDuration ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('all') }}</button>
                  <button @click="selectedDuration = 'short'; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedDuration === 'short' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('duration_short') }}</button>
                  <button @click="selectedDuration = 'full'; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedDuration === 'full' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('full_day') }}</button>
                  <button @click="selectedDuration = 'two'; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedDuration === 'two' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('duration_two_day') }}</button>
                  <button @click="selectedDuration = 'multi'; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedDuration === 'multi' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('multi_day') }}</button>
                </template>
                <template v-else-if="mobileSheet === 'price'">
                  <button @click="selectedPrice = ''; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="!selectedPrice ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('all_prices') }}</button>
                  <button v-for="(label, key) in priceLabels" :key="key" @click="selectedPrice = key; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedPrice === key ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ label }}</button>
                </template>
                <template v-else-if="mobileSheet === 'place'">
                  <button @click="selectedPlace = ''; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="!selectedPlace ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">{{ t('all_places') }}</button>
                  <button v-for="place in placeOptions" :key="place.name" @click="selectedPlace = place.name; mobileSheet = ''" class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium" :class="selectedPlace === place.name ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-slate-50'">
                    {{ place.name }} <span class="text-slate-400">({{ place.count }})</span>
                  </button>
                </template>
              </div>
              <p class="text-center text-xs text-slate-400 font-semibold mt-4">{{ t('tours_found', { count: filteredTours.length }) }}</p>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Tour List -->
    <div class="max-w-7xl mx-auto px-3 md:px-6 lg:px-10 py-3 md:py-6">

      <!-- Active filters badges -->
      <div v-if="selectedCitySlug || selectedTagSlug || selectedDuration || selectedPrice || selectedPlace" class="flex flex-wrap items-center gap-1.5 mb-3">
        <span v-if="selectedCitySlug" class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-semibold rounded-full">
          {{ formatCityName(selectedCitySlug) }}
          <button @click="selectedCitySlug = ''" class="text-[10px] hover:text-red-500 inline-flex"><Icon name="material-symbols:close" /></button>
        </span>
        <span v-if="selectedTagSlug" class="inline-flex items-center gap-1 px-2 py-0.5 bg-violet-500/10 text-violet-600 text-[10px] font-semibold rounded-full">
          <Icon name="material-symbols:label-outline" class="text-[10px]" />
          {{ activeTagInfo?.name || selectedTagSlug }}
          <button @click="selectedTagSlug = ''" class="text-[10px] hover:text-red-500 inline-flex"><Icon name="material-symbols:close" /></button>
        </span>
        <span v-if="selectedDuration" class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-semibold rounded-full">
          {{ durationLabels[selectedDuration] }}
          <button @click="selectedDuration = ''" class="text-[10px] hover:text-red-500 inline-flex"><Icon name="material-symbols:close" /></button>
        </span>
        <span v-if="selectedPrice" class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-semibold rounded-full">
          {{ priceLabels[selectedPrice] }}
          <button @click="selectedPrice = ''" class="text-[10px] hover:text-red-500 inline-flex"><Icon name="material-symbols:close" /></button>
        </span>
        <span v-if="selectedPlace" class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-semibold rounded-full">
          <Icon name="material-symbols:location-on-outline" class="text-[10px]" />
          {{ selectedPlace }}
          <button @click="selectedPlace = ''" class="text-[10px] hover:text-red-500 inline-flex"><Icon name="material-symbols:close" /></button>
        </span>
      </div>

      <!-- Loading: skeleton SOLO para las tarjetas (el título y los filtros de arriba ya están visibles) -->
      <div v-if="pending && !tours.length">
        <div class="md:hidden space-y-3">
          <div v-for="i in 6" :key="'sm-'+i" class="flex gap-3 bg-white rounded-xl border border-slate-100 p-2.5">
            <div class="w-24 h-24 rounded-lg bg-slate-200 animate-pulse shrink-0"></div>
            <div class="flex-1 py-1 space-y-2">
              <div class="h-3 bg-slate-200 rounded animate-pulse w-5/6"></div>
              <div class="h-3 bg-slate-200 rounded animate-pulse w-2/3"></div>
              <div class="h-5 bg-slate-200 rounded animate-pulse w-1/3 mt-4"></div>
            </div>
          </div>
        </div>
        <div class="hidden md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <div v-for="i in 8" :key="'lg-'+i" class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
            <div class="aspect-[4/3] bg-slate-200 animate-pulse"></div>
            <div class="p-4 space-y-2.5">
              <div class="h-3 bg-slate-200 rounded animate-pulse w-1/2"></div>
              <div class="h-4 bg-slate-200 rounded animate-pulse w-5/6"></div>
              <div class="h-5 bg-slate-200 rounded animate-pulse w-1/3 mt-3"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error (la API falló y no había nada cacheado) -->
      <div v-else-if="error && !tours.length" class="text-center py-16">
        <Icon name="material-symbols:wifi-off" class="text-5xl text-slate-300 mb-3" />
        <h3 class="text-base font-bold text-slate-800 mb-2">{{ t('error_loading') }}</h3>
        <button @click="refresh()" class="px-6 py-2.5 bg-primary text-white rounded-xl font-bold text-sm">{{ t('retry') }}</button>
      </div>

      <!-- Contenido real -->
      <template v-else>
      <!-- Empty -->
      <div v-if="filteredTours.length === 0" class="text-center py-16">
        <Icon name="material-symbols:search-off" class="text-5xl text-slate-300 mb-3" />
        <h3 class="text-base font-bold text-slate-800 mb-2">{{ t('no_tours_found') }}</h3>
        <button @click="clearFilters" class="px-5 py-2 bg-primary text-white font-bold rounded-xl text-sm">{{ t('clear_filters') }}</button>
      </div>

      <!-- MOBILE: GetYourGuide-style horizontal cards -->
      <div v-else class="md:hidden space-y-3.5">
        <div
          v-for="tour in mobileVisibleTours"
          :key="'m-'+tour.id"
          class="relative bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
        >
          <NuxtLink :to="getTourLink(tour)" @mouseenter="prefetchTour(tour)" @focus="prefetchTour(tour)" class="flex gap-3 p-2.5 active:bg-slate-50 transition-colors">
            <!-- Image -->
            <div class="relative w-[42%] max-w-[150px] aspect-square rounded-xl overflow-hidden shrink-0 bg-slate-100">
              <NuxtImg v-skeleton :src="getImageUrl(tour.featured_image || tour.thumbnail)" :alt="tour.title" class="w-full h-full object-cover" loading="lazy" format="webp" width="150" height="150" />
              <div v-if="hasActiveOffer(tour)" class="absolute top-1.5 left-1.5 px-1.5 py-0.5 bg-green-500 text-white text-[9px] font-black rounded-md shadow">
                {{ getOfferLabel(tour) }}
              </div>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0 flex flex-col py-0.5 pr-8">
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 inline-flex items-center gap-0.5">
                <Icon name="material-symbols:location-on-outline" class="text-[12px]" />{{ cityLabel(tour) }}
              </p>
              <h3 class="text-[13px] font-bold text-slate-800 line-clamp-2 leading-snug mb-1.5">{{ tour.title }}</h3>
              <!-- Attributes -->
              <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[10px] text-slate-500 mb-auto">
                <span v-if="formatDuration(tour)" class="inline-flex items-center gap-0.5">
                  <Icon name="material-symbols:schedule-outline" class="text-[12px]" />{{ formatDuration(tour) }}
                </span>
                <!-- Data-driven: only shows when the tour actually offers it
                     (set `free_cancellation` on the tour from the admin later). -->
                <span v-if="tour.free_cancellation" class="inline-flex items-center gap-0.5 text-trust font-semibold">
                  <Icon name="material-symbols:verified-user-outline" class="text-[12px]" />{{ t('free_cancellation') }}
                </span>
              </div>
              <!-- Price -->
              <div class="flex items-end justify-between mt-2">
                <div class="leading-tight">
                  <span class="text-[9px] text-slate-400 block">{{ t('from') }}</span>
                  <span class="flex items-baseline gap-1">
                    <span v-if="showDiscountedPrice(tour)" class="text-[10px] line-through text-slate-400">
                      {{ currencyStore.formatConverted(tour.min_price || 0, false) }}
                    </span>
                    <span class="text-base font-black" :class="showDiscountedPrice(tour) ? 'text-trust' : 'text-slate-900'">
                      {{ currencyStore.formatConverted(showDiscountedPrice(tour) ? tour.offer.discounted_min_price : (tour.min_price || 0), false) }}
                    </span>
                  </span>
                  <span class="text-[9px] text-slate-400">{{ t('per_person') }}</span>
                </div>
                <span class="text-[11px] font-bold text-primary inline-flex items-center gap-0.5 shrink-0">
                  {{ t('view') }}<Icon name="material-symbols:arrow-forward" class="text-sm" />
                </span>
              </div>
            </div>
          </NuxtLink>
          <!-- Wishlist heart -->
          <button
            @click.stop.prevent="toggleWishlist(tour)"
            class="absolute top-3 right-3 size-8 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm active:scale-90 transition-transform"
            :class="wishlistStore.has(tour.id) ? 'text-red-500' : 'text-slate-400'"
            :aria-label="wishlistStore.has(tour.id) ? 'Quitar de guardados' : 'Guardar'"
            :aria-pressed="wishlistStore.has(tour.id)"
          >
            <Icon :name="wishlistStore.has(tour.id) ? 'material-symbols:favorite' : 'material-symbols:favorite-outline'" class="text-lg" />
          </button>
        </div>
      </div>

      <!-- DESKTOP: Grid cards -->
      <div v-if="filteredTours.length > 0 && viewMode === 'grid'" class="hidden md:grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <div v-for="tour in paginatedTours.data" :key="'d-'+tour.id" class="relative">
        <NuxtLink
          :to="getTourLink(tour)"
          @mouseenter="prefetchTour(tour)"
          @focus="prefetchTour(tour)"
          class="group block bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
        >
          <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            <NuxtImg v-skeleton :src="getImageUrl(tour.featured_image || tour.thumbnail)" :alt="tour.title"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy"
              format="webp" width="400" height="300" sizes="50vw lg:33vw xl:25vw" />
            <div v-if="formatDuration(tour)" class="absolute bottom-3 left-3 flex items-center gap-1 bg-white/90 backdrop-blur-md text-slate-700 px-2.5 py-1 rounded-full shadow text-[11px] font-bold">
              <Icon name="material-symbols:schedule-outline" class="text-sm" />{{ formatDuration(tour) }}
            </div>
            <div v-if="hasActiveOffer(tour)" class="absolute top-3 left-3 px-2 py-1 bg-green-500 text-white text-[10px] font-bold rounded-full shadow flex items-center gap-0.5">
              <Icon name="material-symbols:sell-outline" class="text-xs" />{{ getOfferLabel(tour) }}
            </div>
          </div>
          <div class="p-4">
            <div class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
              <Icon name="material-symbols:location-on-outline" class="text-xs" />{{ cityLabel(tour) }}
            </div>
            <h3 class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">{{ tour.title }}</h3>
            <div class="flex items-end justify-between pt-3 border-t border-slate-100">
              <div>
                <span class="text-[10px] text-slate-400 font-medium block">{{ t('from') }}</span>
                <span class="flex items-baseline gap-1.5">
                  <span v-if="showDiscountedPrice(tour)" class="text-xs line-through text-slate-400">
                    {{ currencyStore.formatConverted(tour.min_price || 0, false) }}
                  </span>
                  <span class="text-lg font-black" :class="showDiscountedPrice(tour) ? 'text-trust' : 'text-primary'">
                    {{ currencyStore.formatConverted(showDiscountedPrice(tour) ? tour.offer.discounted_min_price : (tour.min_price || 0), false) }}
                  </span>
                </span>
              </div>
              <span class="text-xs font-bold text-primary opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                {{ t('view') }} <Icon name="material-symbols:arrow-forward" class="text-sm" />
              </span>
            </div>
          </div>
        </NuxtLink>
        <!-- Wishlist heart (outside the NuxtLink so it doesn't navigate) -->
        <button
          @click.stop.prevent="toggleWishlist(tour)"
          class="absolute top-3 right-3 size-9 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm active:scale-90 transition-transform z-10"
          :class="wishlistStore.has(tour.id) ? 'text-red-500' : 'text-slate-400 hover:text-red-500'"
          :aria-label="wishlistStore.has(tour.id) ? 'Quitar de guardados' : 'Guardar'"
          :aria-pressed="wishlistStore.has(tour.id)"
        >
          <Icon :name="wishlistStore.has(tour.id) ? 'material-symbols:favorite' : 'material-symbols:favorite-outline'" class="text-lg" />
        </button>
        </div>
      </div>

      <!-- DESKTOP: List view -->
      <div v-if="filteredTours.length > 0 && viewMode === 'list'" class="hidden md:block space-y-4">
        <div v-for="tour in paginatedTours.data" :key="'l-'+tour.id" class="relative">
        <NuxtLink
          :to="getTourLink(tour)"
          @mouseenter="prefetchTour(tour)"
          @focus="prefetchTour(tour)"
          class="group flex gap-5 bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-lg transition-all duration-300 p-3"
        >
          <div class="relative w-64 h-44 rounded-xl overflow-hidden shrink-0 bg-slate-100">
            <NuxtImg v-skeleton :src="getImageUrl(tour.featured_image || tour.thumbnail)" :alt="tour.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"
              format="webp" width="256" height="176" sizes="256px" />
            <div v-if="hasActiveOffer(tour)" class="absolute top-2 left-2 px-2 py-0.5 bg-green-500 text-white text-[10px] font-bold rounded-full shadow flex items-center gap-0.5">
              <Icon name="material-symbols:sell-outline" class="text-xs" />{{ getOfferLabel(tour) }}
            </div>
          </div>
          <div class="flex-1 flex flex-col justify-between py-1 min-w-0">
            <div>
              <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
                <Icon name="material-symbols:location-on-outline" class="text-xs" />{{ cityLabel(tour) }}
              </div>
              <h3 class="text-base font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">{{ tour.title }}</h3>
              <p v-if="tour.short_description" class="text-xs text-slate-500 line-clamp-2 mb-3">{{ tour.short_description }}</p>
              <div class="flex items-center gap-3 text-xs text-slate-500">
                <span v-if="formatDuration(tour)" class="flex items-center gap-1">
                  <Icon name="material-symbols:schedule-outline" class="text-sm" />{{ formatDuration(tour) }}
                </span>
                <span class="flex items-center gap-1">
                  <Icon name="material-symbols:location-on-outline" class="text-sm" />{{ cityLabel(tour) }}
                </span>
              </div>
            </div>
            <div class="flex items-end justify-between mt-2">
              <div>
                <span class="text-[10px] text-slate-400 block">{{ t('from') }}</span>
                <span class="flex items-baseline gap-2">
                  <span v-if="showDiscountedPrice(tour)" class="text-sm line-through text-slate-400">
                    {{ currencyStore.formatConverted(tour.min_price || 0, false) }}
                  </span>
                  <span class="text-xl font-black" :class="showDiscountedPrice(tour) ? 'text-trust' : 'text-primary'">
                    {{ currencyStore.formatConverted(showDiscountedPrice(tour) ? tour.offer.discounted_min_price : (tour.min_price || 0), false) }}
                  </span>
                </span>
              </div>
              <span class="text-xs font-bold text-primary flex items-center gap-1 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                {{ t('view') }} <Icon name="material-symbols:arrow-forward" class="text-sm" />
              </span>
            </div>
          </div>
        </NuxtLink>
        <!-- Wishlist heart (outside the NuxtLink so it doesn't navigate) -->
        <button
          @click.stop.prevent="toggleWishlist(tour)"
          class="absolute top-5 right-5 size-9 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm active:scale-90 transition-transform z-10"
          :class="wishlistStore.has(tour.id) ? 'text-red-500' : 'text-slate-400 hover:text-red-500'"
          :aria-label="wishlistStore.has(tour.id) ? 'Quitar de guardados' : 'Guardar'"
          :aria-pressed="wishlistStore.has(tour.id)"
        >
          <Icon :name="wishlistStore.has(tour.id) ? 'material-symbols:favorite' : 'material-symbols:favorite-outline'" class="text-lg" />
        </button>
        </div>
      </div>

      <!-- MOBILE: load more (fluid, no number tapping) -->
      <div v-if="filteredTours.length > 0" class="md:hidden mt-6 mb-2 text-center">
        <p class="text-xs text-slate-400 font-semibold mb-3">
          {{ Math.min(mobileShown, filteredTours.length) }} / {{ filteredTours.length }}
        </p>
        <button
          v-if="mobileVisibleTours.length < filteredTours.length"
          @click="loadMore"
          class="w-full min-h-[48px] bg-white border-2 border-primary text-primary font-bold rounded-xl text-sm active:scale-[0.98] transition-all inline-flex items-center justify-center gap-2"
        >
          {{ t('load_more') }}
          <Icon name="material-symbols:expand-more" class="text-lg" />
        </button>
      </div>

      <!-- DESKTOP: numbered pagination -->
      <div v-if="paginatedTours.lastPage > 1" class="hidden md:flex items-center justify-center gap-2 mt-8 mb-4">
        <button @click="handlePageChange(currentPage - 1)" :disabled="currentPage <= 1" :aria-label="t('previous')"
          class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-30">
          <Icon name="material-symbols:chevron-left" class="text-base" />
        </button>
        <template v-for="(page, idx) in visiblePages" :key="idx">
          <span v-if="page === '…'" class="w-10 h-10 flex items-center justify-center text-slate-400 font-bold select-none">…</span>
          <button v-else @click="handlePageChange(Number(page))"
            :class="page === currentPage ? 'bg-primary text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all">
            {{ page }}
          </button>
        </template>
        <button @click="handlePageChange(currentPage + 1)" :disabled="currentPage >= paginatedTours.lastPage" :aria-label="t('next')"
          class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-30">
          <Icon name="material-symbols:chevron-right" class="text-base" />
        </button>
      </div>
      </template>
    </div>
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
const wishlistStore = useWishlistStore()

const langCode = computed(() => locale.value.toUpperCase())

// Reuse cached fetch results when navigating BACK to /tours (instant, no
// refetch). Still refetches when language/tag (watched deps) change, since the
// cache key encodes them and watch passes cause='watch'.
function getCachedData(key: string, nuxtApp: any, ctx: any) {
  if (ctx?.cause === 'watch' || ctx?.cause === 'refresh:manual') return undefined
  return nuxtApp.payload.data[key] ?? nuxtApp.static.data[key]
}

useHead({ title: computed(() => `${t('tours')} - Incalake Tours`) })

// View mode toggle
const viewMode = ref<'grid' | 'list'>('grid')

// Filter state
const route = useRoute()
const openFilter = ref('')
const mobileSheet = ref('')
const searchQuery = ref((route.query.search as string) || '')
const selectedCitySlug = ref((route.query.city as string) || '')
const selectedTagSlug = ref((route.query.tag as string) || '')
const selectedDuration = ref('')
const selectedPrice = ref('')
const currentPage = ref(1)
const perPage = 12
// Mobile uses a "load more" pattern (more fluid than numbered pages).
const mobileShown = ref(perPage)

// Active tag info (for the chip displayed at the top of the listing)
const activeTagInfo = ref<{ slug: string; name: string } | null>(null)
watch(selectedTagSlug, async (slug) => {
  if (!slug) { activeTagInfo.value = null; return }
  try {
    const response: any = await $fetch(`${apiBase()}/tags`, { params: { search: slug } })
    const match = (response?.data || []).find((t: any) => t.slug === slug)
    activeTagInfo.value = match ? { slug: match.slug, name: match.name } : { slug, name: slug }
  } catch {
    activeTagInfo.value = { slug, name: slug }
  }
}, { immediate: true })

const apiBase = () => useRuntimeConfig().public.apiBase

// Duration buckets aligned with the actual catalog:
//   short  – up to 4 hours (Uros morning, city tour)
//   full   – more than 4 hours, same day (full-day classics)
//   two    – overnight (e.g. 2D1N Uros-Taquile-Amantani)
//   multi  – 3 days or more (south route, multi-day packages)
const durationLabels = computed<Record<string, string>>(() => ({
  short: t('duration_short'),
  full: t('full_day'),
  two: t('duration_two_day'),
  multi: t('multi_day'),
}))
// Price tiers reflect Incalake's real distribution: most tours are <$40
// short experiences and $40-100 full-day classics; multi-day / premium
// pickup tours move into the $100-250 and $250+ buckets.
const priceLabels = computed<Record<string, string>>(() => ({
  budget: t('price_budget'),
  mid: t('price_mid'),
  premium: t('price_premium'),
  top: t('price_top'),
}))
const selectedPlace = ref('')

// Desktop filter configs (computed)
const desktopFilters = computed(() => [
  {
    key: 'city', icon: 'location_on',
    label: selectedCitySlug.value ? formatCityName(selectedCitySlug.value) : t('destination'),
    isActive: !!selectedCitySlug.value,
    current: selectedCitySlug.value,
    options: [{ value: '', label: t('all_destinations') }, ...cities.value.map(c => ({ value: c.slug, label: `${c.name} (${c.count})` }))],
    select: (v: string) => { selectedCitySlug.value = v },
  },
  {
    key: 'duration', icon: 'schedule',
    label: selectedDuration.value ? durationLabels.value[selectedDuration.value] : t('duration'),
    isActive: !!selectedDuration.value,
    current: selectedDuration.value,
    options: [
      { value: '', label: t('all') },
      { value: 'short', label: t('duration_short') },
      { value: 'full', label: t('full_day') },
      { value: 'two', label: t('duration_two_day') },
      { value: 'multi', label: t('multi_day') },
    ],
    select: (v: string) => { selectedDuration.value = v },
  },
  {
    key: 'price', icon: 'payments',
    label: selectedPrice.value ? priceLabels.value[selectedPrice.value] : t('price'),
    isActive: !!selectedPrice.value,
    current: selectedPrice.value,
    options: [{ value: '', label: t('all_prices') }, ...Object.entries(priceLabels.value).map(([k, l]) => ({ value: k, label: l }))],
    select: (v: string) => { selectedPrice.value = v },
  },
  {
    key: 'place', icon: 'location_on',
    label: selectedPlace.value || t('places'),
    isActive: !!selectedPlace.value,
    current: selectedPlace.value,
    options: [{ value: '', label: t('all_places') }, ...placeOptions.value.map(p => ({ value: p.name, label: `${p.name} (${p.count})` }))],
    select: (v: string) => { selectedPlace.value = v },
  },
])

// Cities — single cheap call with server-side `withCount`. No more pulling all
// 500 tours just to count badges. useAsyncData transfers the SSR payload to the
// client (no re-fetch on hydrate) and caches by key, so navigating between
// filters on the same language is instant.
const featuredSlugs = ['puno','cusco','arequipa','la-paz','uyuni','copacabana']

const { data: citiesData } = await useAsyncData(
  () => `cities-with-counts-${langCode.value}`,
  () => api(`/cities?with_tour_counts=1&language=${langCode.value}`) as Promise<any>,
  { watch: [langCode], lazy: true, getCachedData }
)

const cities = computed<any[]>(() => {
  const list = (citiesData.value as any)?.data || []
  return list
    .filter((c: any) => featuredSlugs.includes(c.slug))
    // Count from the loaded tours (not the backend withCount) so the badge
    // matches what selecting the city actually shows.
    .map((c: any) => ({ ...c, count: cityCounts.value[c.slug] ?? 0 }))
    .filter((c: any) => c.count > 0)
})

// Tours listing — City filtering is done CLIENT-SIDE (the slug is in the light
// payload) so the per-city badge counts always match the visible results, AND
// switching cities is instant (no refetch). Tag stays server-side because the
// light payload doesn't carry tags. Keyed by language + tag only.
const { data: toursData, pending, error, refresh } = await useAsyncData(
  () => `tours-${langCode.value}-${selectedTagSlug.value || 'all'}`,
  () => {
    // light=1 returns a slim card payload (no 6 translations / full gallery /
    // categories per tour) and avoids the per-tour N+1 in the API.
    let url = `/tours?per_page=500&active=1&light=1&language=${langCode.value}`
    if (selectedTagSlug.value) url += `&tag=${encodeURIComponent(selectedTagSlug.value)}`
    return api(url) as Promise<any>
  },
  // lazy: don't block route navigation — show skeletons instantly, stream data in
  { watch: [langCode, selectedTagSlug], lazy: true, getCachedData }
)

const tours = computed<any[]>(() => {
  const d = (toursData.value as any)?.data
  return Array.isArray(d?.data) ? d.data : (Array.isArray(d) ? d : [])
})

// Per-city counts derived from the actually-listable tours, so the dropdown
// badge always equals the number of results when that city is selected.
// (The backend's withCount badge included non-listable tours -> mismatch.)
const cityCounts = computed<Record<string, number>>(() => {
  const counts: Record<string, number> = {}
  for (const t of tours.value) {
    const slug = t.city?.slug
    if (slug) counts[slug] = (counts[slug] || 0) + 1
  }
  return counts
})

// Place options: aggregate `places` from every tour, count occurrences,
// then sort by frequency. Keeps only places that appear in at least one
// tour, so the dropdown reflects what's actually filterable.
const placeOptions = computed<{ name: string; count: number }[]>(() => {
  const counts: Record<string, number> = {}
  for (const t of tours.value) {
    const places: any[] = Array.isArray(t.places) ? t.places : []
    const seenInTour = new Set<string>()
    for (const p of places) {
      const name = (p?.name || '').trim()
      if (!name || seenInTour.has(name)) continue
      seenInTour.add(name)
      counts[name] = (counts[name] || 0) + 1
    }
  }
  return Object.entries(counts)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name))
})

// Duration bucket helper: derives the bucket from whichever fields the tour
// has populated. New schema uses `duration_quantity`+`duration_unit`; legacy
// rows fall back to `duration_days`+`duration_hours`. Returns one of
// 'short' | 'full' | 'two' | 'multi' or '' if unknown.
function tourDurationBucket(tour: any): string {
  const qty = Number(tour?.duration_quantity || 0)
  const unit = tour?.duration_unit
  if (qty > 0 && unit) {
    if (unit === 'days') {
      if (qty <= 1) return 'full'
      if (qty === 2) return 'two'
      return 'multi'
    }
    if (unit === 'hours') {
      if (qty <= 4) return 'short'
      return 'full'
    }
    if (unit === 'minutes') return 'short'
  }
  const d = Number(tour?.duration_days || 0)
  const h = Number(tour?.duration_hours || 0)
  if (d >= 3) return 'multi'
  if (d === 2) return 'two'
  if (d === 1) return d * 24 + h > 24 ? 'two' : 'full'
  if (h > 4) return 'full'
  if (h > 0) return 'short'
  return ''
}

// Search scoring: H1 title beats everything else. Place names (Taquile,
// Uros, Sillustani) come next because those are exactly the queries the
// new placeholder advertises. Description and city tie at the bottom.
// A score of 0 drops the tour from results.
function searchScore(tour: any, q: string): number {
  if (!q) return 1
  const needle = q.toLowerCase()
  const title = (tour?.title || '').toLowerCase()
  if (title.startsWith(needle)) return 100
  if (title.includes(needle)) return 50

  const places: any[] = Array.isArray(tour?.places) ? tour.places : []
  let placeHit = 0
  for (const p of places) {
    const n = (p?.name || '').toLowerCase()
    if (n.startsWith(needle)) { placeHit = Math.max(placeHit, 40); break }
    if (n.includes(needle)) placeHit = Math.max(placeHit, 25)
  }
  if (placeHit) return placeHit

  if ((tour?.short_description || '').toLowerCase().includes(needle)) return 10
  if ((tour?.city?.name || '').toLowerCase().includes(needle)) return 5
  return 0
}

// Client-side filters
const filteredTours = computed(() => {
  let result = [...tours.value]
  if (selectedCitySlug.value) {
    result = result.filter(tour => tour.city?.slug === selectedCitySlug.value)
  }
  if (selectedPlace.value) {
    const want = selectedPlace.value
    result = result.filter(tour => {
      const places: any[] = Array.isArray(tour?.places) ? tour.places : []
      return places.some(p => p?.name === want)
    })
  }
  if (selectedDuration.value) {
    result = result.filter(tour => tourDurationBucket(tour) === selectedDuration.value)
  }
  if (selectedPrice.value) {
    result = result.filter(tour => {
      const p = tour.min_price || 0
      if (selectedPrice.value === 'budget') return p > 0 && p < 40
      if (selectedPrice.value === 'mid') return p >= 40 && p < 100
      if (selectedPrice.value === 'premium') return p >= 100 && p < 250
      if (selectedPrice.value === 'top') return p >= 250
      return true
    })
  }
  if (searchQuery.value) {
    const q = searchQuery.value.trim()
    const scored = result
      .map(tour => ({ tour, score: searchScore(tour, q) }))
      .filter(x => x.score > 0)
      .sort((a, b) => b.score - a.score)
    result = scored.map(x => x.tour)
  }
  return result
})

const paginatedTours = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const data = filteredTours.value.slice(start, start + perPage)
  return { data, total: filteredTours.value.length, lastPage: Math.ceil(filteredTours.value.length / perPage) }
})

// Pagination with first/last + ellipsis: "1 2 3 … 15", "1 … 6 7 8 … 15", etc.
const visiblePages = computed<(number | string)[]>(() => {
  const l = paginatedTours.value.lastPage, c = currentPage.value
  if (l <= 1) return [1]
  const out: (number | string)[] = []
  const start = Math.max(1, c - 2), end = Math.min(l, c + 2)
  if (start > 1) { out.push(1); if (start > 2) out.push('…') }
  for (let i = start; i <= end; i++) out.push(i)
  if (end < l) { if (end < l - 1) out.push('…'); out.push(l) }
  return out
})

// Mobile "load more": show the first N, grow by perPage.
const mobileVisibleTours = computed(() => filteredTours.value.slice(0, mobileShown.value))
function loadMore() { mobileShown.value += perPage }

const hasActiveFilters = computed(() => searchQuery.value || selectedCitySlug.value || selectedTagSlug.value || selectedDuration.value || selectedPrice.value || selectedPlace.value)

watch([searchQuery, selectedDuration, selectedPrice, selectedPlace, selectedCitySlug], () => { currentPage.value = 1; mobileShown.value = perPage })

function clearFilters() {
  searchQuery.value = ''; selectedCitySlug.value = ''; selectedTagSlug.value = ''; selectedDuration.value = ''; selectedPrice.value = ''; selectedPlace.value = ''; currentPage.value = 1; mobileShown.value = perPage
}

function handlePageChange(page: number) {
  if (page < 1 || page > paginatedTours.value.lastPage) return
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function getImageUrl(path: string) {
  if (!path) return ''; if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}

function formatDuration(tour: any) {
  if (tour.duration_quantity && tour.duration_unit) {
    const qty = tour.duration_quantity
    if (tour.duration_unit === 'hours') return `${qty}h`
    if (tour.duration_unit === 'days') return `${qty}D`
    if (tour.duration_unit === 'minutes') return `${qty}min`
  }
  const d = tour.duration_days || 0, h = tour.duration_hours || 0
  if (d > 0 && h > 0) return `${d}D ${h}h`
  if (d > 0) return `${d}D`
  if (h > 0) return `${h}h`
  return ''
}

function formatCityName(slug: string) {
  return slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

// City label for cards: derive from the clean `slug` (e.g. "cusco" -> "Cusco")
// because `city.name` is inconsistent in the DB (e.g. "Vinicunca Peru - Rainbow
// Mountain, Cusco, Perú"). Falls back to the first segment of name, then "Puno".
function cityLabel(tour: any) {
  const slug = tour?.city?.slug
  if (slug) return formatCityName(slug)
  const name = tour?.city?.name
  return name ? name.split(',')[0].trim() : 'Puno'
}

function getTourLink(tour: any) {
  const citySlug = tour.city?.slug || 'puno'
  return localePath(`/${citySlug}/${tour.slug || tour.id}`)
}

function toggleWishlist(tour: any) {
  wishlistStore.toggle({
    id: tour.id,
    title: tour.title,
    slug: tour.slug,
    city_slug: tour.city?.slug || 'puno',
    image: getImageUrl(tour.featured_image || tour.thumbnail),
    min_price: tour.min_price || 0,
    currency: tour.currency || 'USD',
  })
}

// The card now gets a precomputed `offer` ({ label, discount, discount_type,
// start_date, end_date, is_active, is_upcoming, discounted_min_price }) from
// the API instead of the full availability_data (~53% of the listing payload).
function hasActiveOffer(tour: any) {
  return !!tour.offer
}

function getOfferLabel(tour: any) {
  return tour?.offer?.label || ''
}

// Strikethrough + discounted price applies to ANY non-past offer (the backend
// already excludes past ones). Active offers show the live price drop today;
// upcoming offers preview what the price will be when the promo kicks in.
function showDiscountedPrice(tour: any): boolean {
  return !!(tour?.offer && tour?.offer?.discounted_min_price)
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sheet-enter-active, .sheet-leave-active { transition: all 0.3s ease; }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
</style>
