<template>
  <!-- Grid Layout (Default) -->
  <div v-if="layout === 'grid'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
    <NuxtLink :to="tourLink" class="block">
      <!-- Image -->
      <div class="relative h-48 md:h-56 overflow-hidden">
        <img
          :src="imageUrl"
          :alt="tour.title"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          loading="lazy"
        />

        <!-- Badges Overlay -->
        <div class="absolute top-3 right-3 flex flex-col gap-2">
          <span
            v-if="tour.difficulty"
            class="px-3 py-1 text-xs font-bold rounded-full shadow-md"
            :class="difficultyClass"
          >
            {{ difficultyLabel }}
          </span>
          <span v-if="tour.service_type" class="px-3 py-1 bg-primary text-white text-xs font-bold rounded-full shadow-md">
            {{ tour.service_type }}
          </span>
        </div>

        <!-- Favorite Button -->
        <button
          class="absolute top-3 left-3 w-10 h-10 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white dark:hover:bg-slate-800 transition-colors shadow-md"
          @click.prevent="() => {}"
          aria-label="Add to favorites"
        >
          <span class="material-symbols-outlined text-slate-600 dark:text-slate-300 text-xl">favorite</span>
        </button>
      </div>

      <!-- Content -->
      <div class="p-5">
        <!-- Title -->
        <h3 class="text-lg md:text-xl font-black mb-2 group-hover:text-primary transition-colors line-clamp-2 text-primary-light dark:text-primary-dark">
          {{ tour.title }}
        </h3>

        <!-- Short Description -->
        <p class="text-sm text-secondary-light dark:text-secondary-dark mb-4 line-clamp-2">
          {{ tour.short_description }}
        </p>

        <!-- Metadata -->
        <div class="flex flex-wrap gap-3 mb-4 text-sm text-secondary-light dark:text-secondary-dark">
          <!-- Duration -->
          <div class="flex items-center gap-1">
            <span class="material-symbols-outlined text-base">schedule</span>
            <span>{{ duration }}</span>
          </div>

          <!-- City -->
          <div v-if="tour.city" class="flex items-center gap-1">
            <span class="material-symbols-outlined text-base">location_on</span>
            <span>{{ tour.city.name }}</span>
          </div>

          <!-- Capacity -->
          <div v-if="tour.capacity" class="flex items-center gap-1">
            <span class="material-symbols-outlined text-base">groups</span>
            <span>Max {{ tour.capacity }}</span>
          </div>
        </div>

        <!-- Price and CTA -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
          <div>
            <span class="text-xs text-secondary-light dark:text-secondary-dark block">From</span>
            <span class="text-2xl font-black text-primary">
              ${{ (tour.min_price || 0).toFixed(2) }}
            </span>
            <span class="text-xs text-secondary-light dark:text-secondary-dark ml-1">/ person</span>
          </div>
          <button class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
            View Details
          </button>
        </div>
      </div>
    </NuxtLink>
  </div>

  <!-- List Layout -->
  <div v-else class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
    <NuxtLink :to="tourLink" class="flex flex-col md:flex-row">
      <!-- Image -->
      <div class="relative w-full md:w-72 h-48 md:h-full flex-shrink-0 overflow-hidden">
        <img
          :src="imageUrl"
          :alt="tour.title"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          loading="lazy"
        />

        <!-- Badges -->
        <div class="absolute top-3 right-3 flex flex-col gap-2">
          <span
            v-if="tour.difficulty"
            class="px-3 py-1 text-xs font-bold rounded-full shadow-md"
            :class="difficultyClass"
          >
            {{ difficultyLabel }}
          </span>
        </div>
      </div>

      <!-- Content -->
      <div class="p-5 flex-1 flex flex-col">
        <div class="flex-1">
          <!-- Title -->
          <h3 class="text-xl md:text-2xl font-black mb-2 group-hover:text-primary transition-colors text-primary-light dark:text-primary-dark">
            {{ tour.title }}
          </h3>

          <!-- Description -->
          <p class="text-secondary-light dark:text-secondary-dark mb-4 line-clamp-3">
            {{ tour.short_description }}
          </p>

          <!-- Metadata -->
          <div class="flex flex-wrap gap-4 text-sm text-secondary-light dark:text-secondary-dark mb-4">
            <div class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-lg">schedule</span>
              <span>{{ duration }}</span>
            </div>
            <div v-if="tour.city" class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-lg">location_on</span>
              <span>{{ tour.city.name }}</span>
            </div>
            <div v-if="tour.service_type" class="flex items-center gap-1.5">
              <span class="px-2 py-1 bg-primary text-white text-xs font-bold rounded-full">{{ tour.service_type }}</span>
            </div>
          </div>
        </div>

        <!-- Price and CTA -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
          <div>
            <span class="text-xs text-secondary-light dark:text-secondary-dark block">From</span>
            <span class="text-3xl font-black text-primary">
              ${{ (tour.min_price || 0).toFixed(2) }}
            </span>
            <span class="text-sm text-secondary-light dark:text-secondary-dark ml-1">/ person</span>
          </div>
          <button class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-colors w-full sm:w-auto">
            View Details →
          </button>
        </div>
      </div>
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  tour: any
  layout?: 'grid' | 'list'
}

const props = withDefaults(defineProps<Props>(), {
  layout: 'grid'
})

const config = useRuntimeConfig()
const { t, te } = useI18n()

const imageUrl = computed(() => {
  const image = props.tour.featured_image || props.tour.thumbnail
  if (!image) return '/placeholder-tour.jpg'
  if (image.startsWith('http')) return image
  return `${config.public.storageBase}/${image}`
})

const difficultyLabel = computed(() => {
  const raw = String(props.tour.difficulty || '').toLowerCase()
  const key = raw === 'difficult' ? 'hard' : raw
  const i18nKey = `difficulty_${key}`
  return te(i18nKey) ? t(i18nKey) : props.tour.difficulty
})

const difficultyClass = computed(() => {
  const raw = String(props.tour.difficulty || '').toLowerCase()
  const classes: Record<string, string> = {
    easy: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    moderate: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    difficult: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    hard: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return classes[raw] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
})

const duration = computed(() => {
  const days = props.tour.duration_days || 0
  const hours = props.tour.duration_hours || 0

  if (days > 0 && hours > 0) {
    return `${days}d ${hours}h`
  } else if (days > 0) {
    return `${days} ${days === 1 ? 'day' : 'days'}`
  } else if (hours > 0) {
    return `${hours} ${hours === 1 ? 'hour' : 'hours'}`
  }
  return 'Duration not specified'
})

const tourLink = computed(() => {
  return props.tour.slug ? `/tours/${props.tour.slug}` : `/tours/${props.tour.id}`
})
</script>
