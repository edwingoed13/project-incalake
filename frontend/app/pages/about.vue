<template>
  <!-- pt-24 clears the fixed navbar, which was slicing the title in half. -->
  <div class="min-h-screen bg-background-light pt-24 pb-12 md:pt-28 md:pb-16">
    <div class="max-w-5xl mx-auto px-4 md:px-6">
      <div class="text-center mb-8 md:mb-10">
        <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2">{{ t('about_title') }}</h1>
        <p class="text-sm md:text-base text-slate-500 max-w-2xl mx-auto">{{ t('about_subtitle') }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-8 mb-5 md:mb-6">
        <p class="text-base md:text-lg text-slate-700 mb-4 leading-relaxed">
          <i18n-t keypath="about_p1" tag="span">
            <template #brand><strong class="text-slate-900">Incalake Tours</strong></template>
          </i18n-t>
        </p>
        <p class="text-sm md:text-base text-slate-600 mb-4 leading-relaxed">{{ t('about_p2') }}</p>
        <p class="text-sm md:text-base text-slate-600 leading-relaxed">{{ t('about_p3') }}</p>
      </div>

      <!-- Real numbers from the live catalog beat a paragraph claiming scale. -->
      <div class="grid grid-cols-3 gap-3 md:gap-4 mb-5 md:mb-6">
        <div v-for="stat in stats" :key="stat.label" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 md:p-5 text-center">
          <p class="text-2xl md:text-3xl font-black text-primary tabular-nums">{{ stat.value }}</p>
          <p class="text-[11px] md:text-xs font-semibold uppercase tracking-wider text-slate-500 mt-1">{{ stat.label }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mb-5 md:mb-6">
        <div v-for="v in values" :key="v.title" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6 text-center">
          <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-3">
            <Icon :name="v.icon" class="size-6 text-primary" />
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ v.title }}</h3>
          <p class="text-sm text-slate-500 leading-snug">{{ v.text }}</p>
        </div>
      </div>

      <!-- Our own tour photography, not stock: proof beats adjectives. -->
      <section v-if="galleryTours.length" class="mb-5 md:mb-6">
        <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4">{{ t('about_gallery_title') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
          <NuxtLink
            v-for="tr in galleryTours"
            :key="tr.id"
            :to="localePath(`/${tr.city?.slug || 'puno'}/${tr.slug}`)"
            class="group relative aspect-square rounded-2xl overflow-hidden bg-slate-100"
          >
            <NuxtImg
              v-skeleton
              :src="imageUrl(tr.featured_image)"
              :alt="tr.title"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              loading="lazy" format="webp" width="300" height="300" sizes="50vw md:25vw"
            />
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2.5 pt-8">
              <p class="text-[11px] font-bold text-white line-clamp-2 leading-snug">{{ tr.title }}</p>
            </div>
          </NuxtLink>
        </div>
      </section>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6 text-center">
        <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-1.5">{{ t('about_cta_title') }}</h2>
        <p class="text-sm text-slate-500 mb-4">{{ t('about_cta_text') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <NuxtLink :to="localePath('/tours')" class="btn-primary w-full sm:w-auto">
            {{ t('view_all_tours') }}
            <Icon name="material-symbols:arrow-forward" class="size-5" />
          </NuxtLink>
          <NuxtLink :to="localePath('/contact')" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 min-h-[44px] rounded-xl border border-slate-200 font-bold text-sm text-slate-700 hover:border-primary/50 hover:text-primary transition-all">
            <Icon name="material-symbols:chat" class="size-5" />
            {{ t('contact_title') }}
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const { api } = useApi()
const config = useRuntimeConfig()

useHead({
  title: 'Sobre Nosotros | Incalake Tours',
  meta: [
    {
      name: 'description',
      content: 'Conoce a Incalake Tours, tu operador turístico de confianza en Puno y el Lago Titicaca. Más de 10 años de experiencia ofreciendo tours inolvidables.'
    }
  ]
})

const values = computed(() => [
  { icon: 'material-symbols:landscape-outline', title: t('about_value_local_title'), text: t('about_value_local_text') },
  { icon: 'material-symbols:workspace-premium-outline', title: t('about_value_quality_title'), text: t('about_value_quality_text') },
  { icon: 'material-symbols:eco-outline', title: t('about_value_responsible_title'), text: t('about_value_responsible_text') },
])

// Lazy + tolerant: the page must still render if the API is having a bad day.
const { data: toursResponse } = await useAsyncData(
  'about-tours',
  () => api('/tours?limit=8').catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }) }
)

const galleryTours = computed(() =>
  ((toursResponse.value as any)?.data || []).filter((tr: any) => tr.featured_image).slice(0, 4)
)

const { data: citiesResponse } = await useAsyncData(
  'about-cities',
  () => api('/cities').catch(() => ({ data: [] })),
  { lazy: true, default: () => ({ data: [] }) }
)
const cityCount = computed(() => ((citiesResponse.value as any)?.data || []).length)

const stats = computed(() => [
  { value: '10+', label: t('about_stat_years') },
  { value: `${cityCount.value || 6}`, label: t('about_stat_destinations') },
  { value: '4.9', label: t('about_stat_rating') },
])

function imageUrl(path: string) {
  if (!path) return ''
  return String(path).startsWith('http') ? path : `${config.public.storageBase}/${path}`
}
</script>
