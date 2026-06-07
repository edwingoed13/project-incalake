<template>
  <div class="space-y-5">
    <!-- Destination -->
    <section v-if="cities.length">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('destination') }}</h3>
      <div class="space-y-1">
        <button @click="city = ''"
          class="w-full flex items-center justify-between text-left px-2 py-1.5 rounded-lg text-sm transition-colors"
          :class="!city ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 hover:bg-slate-50'">
          <span>{{ t('all_destinations') }}</span>
        </button>
        <button v-for="c in cities" :key="c.slug" @click="city = c.slug"
          class="w-full flex items-center justify-between text-left px-2 py-1.5 rounded-lg text-sm transition-colors"
          :class="city === c.slug ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 hover:bg-slate-50'">
          <span>{{ c.name }}</span>
          <span class="text-xs text-slate-400">{{ c.count }}</span>
        </button>
      </div>
    </section>

    <!-- Duration -->
    <section class="border-t border-slate-100 pt-4">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('duration') }}</h3>
      <div class="space-y-1">
        <button v-for="opt in durationOptions" :key="opt.value" @click="duration = opt.value"
          class="w-full text-left px-2 py-1.5 rounded-lg text-sm transition-colors"
          :class="duration === opt.value ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 hover:bg-slate-50'">
          {{ opt.label }}
        </button>
      </div>
    </section>

    <!-- Price -->
    <section class="border-t border-slate-100 pt-4">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('price') }}</h3>
      <div class="space-y-1">
        <button v-for="opt in priceOptions" :key="opt.value" @click="price = opt.value"
          class="w-full text-left px-2 py-1.5 rounded-lg text-sm transition-colors"
          :class="price === opt.value ? 'bg-primary/10 text-primary font-bold' : 'text-slate-600 hover:bg-slate-50'">
          {{ opt.label }}
        </button>
      </div>
    </section>

    <!-- Places (multi-select with search-within-filter) -->
    <section v-if="placeOptions.length" class="border-t border-slate-100 pt-4">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">{{ t('places') }}</h3>
      <div class="relative mb-2">
        <Icon name="material-symbols:search" class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
        <input v-model="placeQuery" type="text" :placeholder="t('search_place')"
          class="w-full pl-7 pr-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary/30" />
      </div>
      <div class="space-y-0.5 max-h-72 overflow-y-auto -mx-1 px-1">
        <label v-for="p in visiblePlaces" :key="p.name"
          class="flex items-center gap-2 px-1 py-1.5 rounded-lg text-sm cursor-pointer hover:bg-slate-50">
          <input type="checkbox" :checked="places.includes(p.name)" @change="togglePlace(p.name)"
            class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary" />
          <span class="flex-1 text-slate-700">{{ p.name }}</span>
          <span class="text-xs text-slate-400">{{ p.count }}</span>
        </label>
        <p v-if="!visiblePlaces.length" class="text-xs text-slate-400 italic px-1 py-2">{{ t('no_places_match') }}</p>
      </div>
      <button v-if="hasMorePlaces" @click="showAllPlaces = true"
        class="mt-2 text-xs font-bold text-primary hover:underline">
        {{ t('show_more_n', { n: placeOptions.length - placeCollapsedLimit }) }}
      </button>
      <button v-else-if="showAllPlaces && !placeQuery && placeOptions.length > placeCollapsedLimit" @click="showAllPlaces = false"
        class="mt-2 text-xs font-bold text-slate-500 hover:underline">
        {{ t('show_less') }}
      </button>
    </section>
  </div>
</template>

<script setup lang="ts">
const { t } = useI18n()

const props = defineProps<{
  cities: Array<{ slug: string; name: string; count: number }>
  placeOptions: Array<{ name: string; count: number }>
}>()

const city = defineModel<string>('city', { required: true })
const duration = defineModel<string>('duration', { required: true })
const price = defineModel<string>('price', { required: true })
const places = defineModel<string[]>('places', { required: true })

const placeQuery = ref('')
const showAllPlaces = ref(false)
const placeCollapsedLimit = 10

// Filter the place list by the in-filter search box; collapse to the top N
// most-frequent places by default so the panel stays scannable when the
// catalog has 30+ sightseeing points. Searching always expands.
const visiblePlaces = computed(() => {
  const q = placeQuery.value.trim().toLowerCase()
  const list = q ? props.placeOptions.filter(p => p.name.toLowerCase().includes(q)) : props.placeOptions
  if (q || showAllPlaces.value) return list
  return list.slice(0, placeCollapsedLimit)
})
const hasMorePlaces = computed(() => !showAllPlaces.value && !placeQuery.value && props.placeOptions.length > placeCollapsedLimit)

const durationOptions = computed(() => [
  { value: '', label: t('all') },
  { value: 'short', label: t('duration_short') },
  { value: 'full', label: t('full_day') },
  { value: 'two', label: t('duration_two_day') },
  { value: 'multi', label: t('multi_day') },
])

const priceOptions = computed(() => [
  { value: '', label: t('all_prices') },
  { value: 'budget', label: t('price_budget') },
  { value: 'mid', label: t('price_mid') },
  { value: 'premium', label: t('price_premium') },
  { value: 'top', label: t('price_top') },
])

function togglePlace(name: string) {
  const arr = places.value
  const i = arr.indexOf(name)
  places.value = i >= 0 ? [...arr.slice(0, i), ...arr.slice(i + 1)] : [...arr, name]
}
</script>
