<template>
  <section v-if="hasOptions" class="bg-white rounded-2xl border border-slate-200 p-4 md:p-5 mb-5">
    <header class="mb-3">
      <h2 class="text-base md:text-lg font-black text-slate-900 flex items-center gap-2">
        <Icon name="material-symbols:tune" class="text-primary text-xl" />
        {{ t('options_title') }}
      </h2>
      <p class="text-xs text-slate-500 mt-0.5">{{ t('options_subtitle') }}</p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <component
        :is="opt.is_current ? 'div' : NuxtLinkComp"
        v-for="opt in options"
        :key="opt.id"
        :to="opt.is_current ? undefined : localePath(`/${opt.city_slug}/${opt.slug}`)"
        class="relative group flex flex-col gap-2 p-3.5 rounded-xl border-2 transition-all"
        :class="opt.is_current
          ? 'border-primary bg-primary/5 cursor-default'
          : 'border-slate-200 hover:border-primary hover:shadow-md cursor-pointer bg-white'"
      >
        <!-- Active badge -->
        <span v-if="opt.is_current"
          class="absolute top-2 right-2 inline-flex items-center gap-1 px-1.5 py-0.5 bg-primary text-white text-[9px] font-black rounded-full">
          <Icon name="material-symbols:check" class="text-[10px]" />
          {{ t('options_selected') }}
        </span>

        <!-- Option label badge -->
        <div class="flex items-center gap-1.5">
          <span
            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
            :class="badgeClasses(opt.option_color)"
          >
            {{ opt.option_label || (opt.is_parent ? t('options_default') : t('options_variant')) }}
          </span>
        </div>

        <!-- Title -->
        <h3 class="text-sm font-bold text-slate-800 line-clamp-2 leading-snug pr-12">{{ opt.h1_title }}</h3>

        <!-- Price -->
        <div class="mt-auto pt-2 border-t border-slate-100 flex items-end justify-between">
          <div>
            <span class="text-[10px] text-slate-400 block">{{ t('from') }}</span>
            <span class="text-lg font-black"
              :class="opt.is_current ? 'text-primary' : 'text-slate-900'">
              {{ opt.min_price ? currencyStore.formatConverted(opt.min_price, false) : '—' }}
            </span>
          </div>
          <span v-if="!opt.is_current" class="text-[11px] font-bold text-primary inline-flex items-center gap-0.5 group-hover:gap-1.5 transition-all">
            {{ t('options_view') }}
            <Icon name="material-symbols:arrow-forward" class="text-sm" />
          </span>
        </div>
      </component>
    </div>
  </section>
</template>

<script setup lang="ts">
type TourOption = {
  id: number
  is_current: boolean
  slug: string
  city_slug: string
  h1_title: string
  option_label?: string | null
  option_color?: string | null
  is_parent: boolean
  min_price: number | null
}

const props = defineProps<{ options: TourOption[] }>()
const { t } = useI18n()
const localePath = useLocalePath()
const currencyStore = useCurrencyStore()

// Resolve NuxtLink as a component reference, not a string. Passing the
// literal "NuxtLink" string to <component :is="..."> renders an unknown
// element with no router behavior, so the cards looked clickable but did
// nothing.
const NuxtLinkComp = resolveComponent('NuxtLink')

const hasOptions = computed(() => Array.isArray(props.options) && props.options.length >= 2)

// Map admin-friendly color tokens to Tailwind classes for the option badge.
// Falls back to slate when the token isn't recognized so a typo doesn't
// break the layout.
function badgeClasses(color?: string | null): string {
  switch ((color || '').toLowerCase()) {
    case 'blue': return 'bg-blue-100 text-blue-700'
    case 'violet': return 'bg-violet-100 text-violet-700'
    case 'amber': return 'bg-amber-100 text-amber-700'
    case 'rose': return 'bg-rose-100 text-rose-700'
    case 'emerald': return 'bg-emerald-100 text-emerald-700'
    case 'sky': return 'bg-sky-100 text-sky-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}
</script>
