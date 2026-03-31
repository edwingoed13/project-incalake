<template>
  <div class="bg-white font-display text-slate-900 min-h-screen pt-20">
    <!-- Premium Hero Section -->
    <section class="relative w-full h-[550px] md:h-[600px] overflow-hidden flex flex-col items-center justify-center p-6 sm:p-12">
      <div class="absolute inset-0 z-0">
        <img
          src="https://lh3.googleusercontent.com/aida-public/AB6AXuC_RYQ7qkkoEaBPmoTKZzaG0YqRCjHegCR7RyERPQkd1TtLTQg9RBjbabWhebnRMrUB20ewsrsBPSVd6DSHmHht2CDGuVapyxM2-QivgVXECSdMWlVIrUHpRWi-kYXNgGWzL5n8LrG0LDy65HR5hOFM_toPA7xM8lnDtR4JFasVk-50uf1v5cmyZfqOvKFkinf3_DBwZiEeJp-2fgM5W72REPm0RxDXSlTGjmg4V1Jfto_VIJ4AUc9TPFiZlRzbS-VIy24MMT2dYVq1"
          class="absolute w-full h-full object-cover scale-105"
          alt="Lake Titicaca"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/60"></div>
      </div>

      <div class="relative z-10 text-center max-w-4xl px-4 mb-8">
        <h2 class="text-white text-4xl sm:text-5xl md:text-6xl font-black leading-[1.1] tracking-tighter mb-4 drop-shadow-2xl">
          {{ c('hero', 'title', 'home_hero_title') }}
        </h2>
        <p class="text-white/80 text-lg md:text-xl font-medium max-w-2xl mx-auto">
          {{ c('hero', 'subtitle', 'home_hero_subtitle') }}
        </p>
      </div>

      <!-- Search Bar -->
      <div class="relative z-20 w-full max-w-4xl px-6">
        <div class="bg-white shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] rounded-2xl p-2 flex items-center gap-2">
          <div class="flex-1 flex items-center gap-3 px-4 py-3">
            <span class="material-symbols-outlined text-primary text-xl">search</span>
            <input
              v-model="searchQuery"
              class="bg-transparent border-none p-0 focus:ring-0 text-sm font-semibold text-slate-800 placeholder:text-slate-400 w-full outline-none"
              :placeholder="c('search_placeholder', '', 'home_search_placeholder')"
              type="text"
              @keyup.enter="goToTours"
            />
          </div>
          <button
            @click="goToTours"
            class="bg-primary text-white px-8 py-3.5 rounded-xl font-bold text-sm hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-primary/30 flex items-center gap-2"
          >
            <span class="material-symbols-outlined text-lg">search</span>
            {{ c('search_btn', '', 'home_search_btn') }}
          </button>
        </div>
      </div>
    </section>

    <!-- Trust Signals -->
    <section class="py-8 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8">
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
    </section>

    <!-- Explore by Destination -->
    <section class="py-16 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-10">
          <p class="text-primary font-black uppercase tracking-[0.2em] text-[10px] mb-2">{{ c('destinations', 'label', 'home_destinations_label') }}</p>
          <h3 class="text-3xl md:text-4xl font-black tracking-tighter text-slate-900">{{ c('destinations', 'title', 'home_destinations_title') }}</h3>
        </div>

        <div v-if="cities.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <!-- Featured city: Puno (large) -->
          <NuxtLink
            :to="localePath(`/tours`)"
            class="col-span-2 row-span-2 relative group overflow-hidden rounded-3xl h-80 md:h-full cursor-pointer shadow-lg"
          >
            <img :src="getCityImage(featuredCities[0]?.slug)" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            <div class="absolute bottom-8 left-8">
              <span class="inline-block px-3 py-1 bg-primary rounded-full text-[9px] font-black uppercase tracking-widest text-white mb-3">{{ c('trending_label', '', 'home_trending') }}</span>
              <h4 class="text-white text-4xl md:text-5xl font-black tracking-tighter drop-shadow-lg">{{ featuredCities[0]?.name }}</h4>
              <p class="text-white/60 text-sm font-medium mt-1">{{ featuredCities[0]?.country_code }}</p>
            </div>
          </NuxtLink>

          <!-- 4 secondary cities -->
          <NuxtLink
            v-for="city in featuredCities.slice(1, 5)"
            :key="city.id"
            :to="localePath(`/tours`)"
            class="relative group overflow-hidden rounded-2xl h-48 cursor-pointer shadow-md"
          >
            <img :src="getCityImage(city.slug)" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 grayscale-[30%] group-hover:grayscale-0" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div class="absolute bottom-5 left-5">
              <h5 class="text-white text-xl font-black tracking-tight drop-shadow-md">{{ city.name }}</h5>
              <p class="text-white/50 text-[10px] font-bold uppercase">{{ city.country_code }}</p>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- Featured Tours -->
    <section class="py-20 px-6 bg-slate-50/50">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-end justify-between mb-12">
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
            v-for="tour in tours.slice(0, 8)"
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

    <!-- Why Choose Us -->
    <section class="py-20 px-6">
      <div class="max-w-7xl mx-auto">
        <h3 class="text-3xl md:text-4xl font-black tracking-tighter text-slate-900 text-center mb-16">{{ c('why_title', '', 'home_why_title') }}</h3>
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
const langCode = computed(() => locale.value.toUpperCase())

// Fetch page content from API (dynamic, admin-editable)
const { data: pageResponse } = await useAsyncData(
  `home-content-${locale.value}`,
  () => api(`/pages/home?language=${langCode.value}`),
  { watch: [locale] }
)
const pageContent = computed(() => pageResponse.value?.data?.content || null)

// Helper: get content from API or fall back to i18n
const c = (section: string, field: string, fallbackKey: string) => {
  const content = pageContent.value
  if (content) {
    const val = typeof content[section] === 'object' ? content[section]?.[field] : content[section]
    if (val) return val
  }
  return t(fallbackKey)
}

// SEO
useHead({
  title: computed(() => `${c('hero', 'title', 'home_hero_title')} - Incalake Tours`),
})

// Fetch cities
const { data: citiesResponse } = await useAsyncData(
  'cities',
  () => api('/cities')
)
const cities = computed(() => citiesResponse.value?.data || [])

// Show only 5 main destinations: Puno first, then Cusco, Arequipa, Copacabana, Uyuni
const featuredSlugs = ['puno', 'cusco', 'arequipa', 'copacabana', 'uyuni']
const featuredCities = computed(() => {
  return featuredSlugs
    .map(slug => cities.value.find((c: any) => c.slug === slug))
    .filter(Boolean)
})

// Fetch featured tours by current language
const { data: toursResponse, pending: toursPending } = await useAsyncData(
  `featured-tours-${locale.value}`,
  () => api(`/tours?active=1&per_page=8&language=${langCode.value}`),
  { watch: [locale] }
)
const tours = computed(() => {
  if (toursResponse.value?.data) {
    return Array.isArray(toursResponse.value.data.data)
      ? toursResponse.value.data.data
      : toursResponse.value.data
  }
  return []
})

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

function goToTours() {
  navigateTo(localePath('/tours'))
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
