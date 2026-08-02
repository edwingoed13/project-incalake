<script setup lang="ts">
// Vertical tour card for the home sliders (featured + offers). The two inline
// versions were identical except for the accent color, the offer badge and
// the difficulty pill — those are props now. Slider sizing classes
// (shrink-0 w-[78%] … snap-start) stay in the parent via class passthrough.
const props = withDefaults(defineProps<{
  tour: any
  accent?: 'primary' | 'green'
  offerLabel?: string
  showDifficulty?: boolean
}>(), { accent: 'primary', showDifficulty: false })

const { t, te } = useI18n()
const localePath = useLocalePath()
const config = useRuntimeConfig()
const currencyStore = useCurrencyStore()
const { prefetchTour } = useTourPrefetch()

const link = computed(() => {
  const slug = props.tour.slug || props.tour.id
  const citySlug = props.tour.city?.slug || 'puno'
  return localePath(`/${citySlug}/${slug}`)
})

const imageUrl = computed(() => {
  const path = props.tour.featured_image
  if (!path) return ''
  return String(path).startsWith('http') ? path : `${config.public.storageBase}/${path}`
})

const green = computed(() => props.accent === 'green')

const difficultyLabel = computed(() => {
  const raw = String(props.tour.difficulty || '').toLowerCase()
  if (!raw) return ''
  const key = `difficulty_${raw === 'difficult' ? 'hard' : raw}`
  return te(key) ? t(key) : props.tour.difficulty
})
</script>

<template>
  <NuxtLink
    :to="link"
    class="group bg-white rounded-2xl overflow-hidden border hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative"
    :class="green ? 'border-green-100' : 'border-slate-100'"
    @mouseenter="prefetchTour(tour)"
    @focus="prefetchTour(tour)"
  >
    <TourOfferBadge v-if="offerLabel" :label="offerLabel" class="absolute top-3 right-3 z-10" />
    <div class="relative h-52 overflow-hidden bg-slate-100">
      <NuxtImg
        v-if="tour.featured_image"
        v-skeleton
        :src="imageUrl"
        :alt="tour.title"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
        loading="lazy" format="webp" width="400" height="208"
        sizes="78vw sm:45vw md:30vw lg:25vw"
      />
      <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center">
        <Icon name="material-symbols:image-outline" class="text-slate-300 text-4xl" />
      </div>
      <div v-if="showDifficulty && tour.difficulty" class="absolute top-3 left-3">
        <span
          class="px-2.5 py-1 text-[10px] font-bold rounded-full shadow backdrop-blur-md"
          :class="{
            'bg-green-500/90 text-white': tour.difficulty === 'easy',
            'bg-yellow-500/90 text-white': tour.difficulty === 'moderate',
            'bg-red-500/90 text-white': tour.difficulty === 'hard' || tour.difficulty === 'difficult',
          }"
        >
          {{ difficultyLabel }}
        </span>
      </div>
    </div>
    <div class="p-4">
      <div class="flex items-center gap-1 text-[11px] text-slate-500 font-semibold uppercase tracking-wider mb-1">
        <Icon name="material-symbols:location-on-outline" class="text-xs" />
        {{ tour.city?.name || 'Puno' }}
      </div>
      <h4
        class="text-sm font-bold text-slate-800 mb-2 line-clamp-2 transition-colors leading-snug"
        :class="green ? 'group-hover:text-green-600' : 'group-hover:text-primary'"
      >{{ tour.title }}</h4>
      <div class="flex items-end justify-between pt-3 border-t" :class="green ? 'border-green-100' : 'border-slate-100'">
        <div>
          <span class="text-[11px] text-slate-500 font-medium block">{{ t('from') }}</span>
          <span class="text-lg font-black" :class="green ? 'text-green-600' : 'text-primary'">
            {{ currencyStore.formatConverted(tour.min_price || 0) }}
          </span>
        </div>
        <span
          class="text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5"
          :class="green ? 'text-green-600' : 'text-primary'"
        >
          {{ t('view') }}
          <Icon name="material-symbols:arrow-forward" class="text-sm" />
        </span>
      </div>
    </div>
  </NuxtLink>
</template>
