<template>
  <div v-if="tour" class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Title & Basic Info -->
      <div class="flex flex-col lg:flex-row justify-between gap-6 mb-8">
        <div class="flex-1">
          <h1 class="text-2xl md:text-3xl font-black mb-3 leading-tight">{{ tour.h1_title || tour.title }}</h1>
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
              <span>{{ tour.city?.name || cityName }}, Peru</span>
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

      <!-- Main Content -->
      <div v-if="tour.long_description" v-html="tour.long_description" class="prose max-w-none mb-8"></div>

    </main>
  </div>
  <div v-else class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <h2 class="text-2xl font-bold mb-2">Cargando tour...</h2>
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useRuntimeConfig } from '#app'

const route = useRoute()
const config = useRuntimeConfig()
const tour = ref(null)

// Extract city name from slug
const cityName = route.params.city ?
  route.params.city.charAt(0).toUpperCase() + route.params.city.slice(1) :
  'Puno'

const formatDuration = (tour) => {
  if (!tour) return 'N/A'
  return tour.duration || '3 horas'
}

const fetchTour = async () => {
  try {
    const locale = route.params.locale || 'es'
    const city = route.params.city
    const slug = route.params.slug

    const response = await fetch(`${config.public.apiBase}/tours/${locale}/${city}/${slug}`)

    if (!response.ok) {
      console.error('Error fetching tour:', response.status)
      return
    }

    const data = await response.json()
    tour.value = data.data
  } catch (error) {
    console.error('Error fetching tour:', error)
  }
}

onMounted(() => {
  fetchTour()
})

// Meta tags for SEO
useHead({
  title: () => tour.value?.meta_title || tour.value?.h1_title || 'Tour',
  meta: [
    {
      name: 'description',
      content: () => tour.value?.meta_description || ''
    },
    {
      property: 'og:title',
      content: () => tour.value?.og_title || tour.value?.h1_title || ''
    },
    {
      property: 'og:description',
      content: () => tour.value?.og_description || tour.value?.meta_description || ''
    }
  ]
})
</script>