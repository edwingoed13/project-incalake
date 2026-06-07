<template>
  <section v-if="hasOptions" class="bg-white rounded-2xl border border-slate-200 p-3 sm:p-4 md:p-5 mb-5" :class="loading ? 'opacity-70 pointer-events-none' : ''">
    <header class="mb-2.5 sm:mb-3 flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h2 class="text-sm sm:text-base md:text-lg font-black text-slate-900 flex items-center gap-1.5 sm:gap-2">
          <Icon name="material-symbols:tune" class="text-primary text-lg sm:text-xl" />
          {{ t('options_title') }}
        </h2>
        <p class="hidden sm:block text-xs text-slate-500 mt-0.5">{{ t('options_subtitle') }}</p>
      </div>
      <span v-if="loading" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary shrink-0 mt-1">
        <Icon name="material-symbols:progress-activity" class="text-base animate-spin" />
        {{ t('options_loading') }}
      </span>
    </header>

    <!-- Mobile: always a horizontal snap slider — even 2 options take less
         vertical space and feel more like a native picker (GYG-style swipe).
         Desktop: comfortable grid for ≤3 options, slider when 4+. -->
    <div
      :class="mobileSlider
        ? 'flex sm:hidden gap-2.5 overflow-x-auto snap-x snap-mandatory pb-2 -mx-3 px-3 scrollbar-thin'
        : 'hidden'"
    >
      <button
        v-for="opt in options"
        :key="'m-' + opt.id"
        type="button"
        @click="!opt.is_current && emit('select', opt)"
        :aria-pressed="opt.is_current"
        :disabled="opt.is_current"
        :class="[
          'relative shrink-0 basis-[78%] max-w-[280px] snap-start group flex flex-col gap-2 p-3 rounded-xl border-2 text-left transition-all',
          opt.is_current
            ? 'border-primary bg-primary/5 cursor-default'
            : 'border-slate-200 hover:border-primary cursor-pointer bg-white active:scale-[0.99]'
        ]"
      >
        <span v-if="opt.is_current"
          class="absolute top-1.5 right-1.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-primary text-white text-[9px] font-black rounded-full">
          <Icon name="material-symbols:check" class="text-[10px]" />
          {{ t('options_selected') }}
        </span>
        <span
          class="inline-block self-start px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
          :class="badgeClasses(opt.option_color)"
        >
          {{ opt.option_label || (opt.is_parent ? t('options_default') : t('options_variant')) }}
        </span>
        <p class="text-[11px] text-slate-500 leading-snug line-clamp-2 pr-8">{{ optionDescription(opt) }}</p>
        <div class="mt-auto pt-2 border-t border-slate-100 flex items-baseline justify-between gap-1.5">
          <div class="leading-tight">
            <span class="text-[9px] text-slate-400 block uppercase tracking-wider font-semibold">{{ t('from') }}</span>
            <span class="text-base font-black"
              :class="opt.is_current ? 'text-primary' : 'text-slate-900'">
              {{ opt.min_price ? currencyStore.formatConverted(opt.min_price, false) : '—' }}
            </span>
          </div>
          <span v-if="!opt.is_current" class="text-[10px] font-bold text-primary inline-flex items-center gap-0.5 shrink-0">
            {{ t('options_pick') }}
            <Icon name="material-symbols:arrow-forward" class="text-sm" />
          </span>
        </div>
      </button>
    </div>

    <!-- Desktop / tablet (≥sm): grid for ≤3, slider for 4+ -->
    <div
      :class="[
        'hidden sm:flex',
        useSliderDesktop
          ? 'gap-3 overflow-x-auto snap-x snap-mandatory pb-1 -mx-1 px-1 scrollbar-thin'
          : 'grid sm:grid-cols-2 lg:grid-cols-3 gap-3'
      ]"
    >
      <button
        v-for="opt in options"
        :key="'d-' + opt.id"
        type="button"
        @click="!opt.is_current && emit('select', opt)"
        :aria-pressed="opt.is_current"
        :disabled="opt.is_current"
        :class="[
          'relative group flex flex-col gap-2.5 p-4 rounded-xl border-2 text-left transition-all',
          useSliderDesktop ? 'shrink-0 basis-[200px] snap-start' : '',
          opt.is_current
            ? 'border-primary bg-primary/5 cursor-default'
            : 'border-slate-200 hover:border-primary hover:shadow-md cursor-pointer bg-white active:scale-[0.99]'
        ]"
      >
        <span v-if="opt.is_current"
          class="absolute top-2 right-2 inline-flex items-center gap-1 px-1.5 py-0.5 bg-primary text-white text-[9px] font-black rounded-full">
          <Icon name="material-symbols:check" class="text-[10px]" />
          {{ t('options_selected') }}
        </span>

        <div>
          <span
            class="inline-block px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider"
            :class="badgeClasses(opt.option_color)"
          >
            {{ opt.option_label || (opt.is_parent ? t('options_default') : t('options_variant')) }}
          </span>
        </div>

        <p class="text-[11px] text-slate-500 leading-snug pr-10">{{ optionDescription(opt) }}</p>

        <div class="mt-auto pt-3 border-t border-slate-100 flex items-end justify-between gap-2">
          <div class="leading-tight">
            <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">{{ t('from') }}</span>
            <span class="text-xl font-black"
              :class="opt.is_current ? 'text-primary' : 'text-slate-900'">
              {{ opt.min_price ? currencyStore.formatConverted(opt.min_price, false) : '—' }}
            </span>
          </div>
          <span v-if="!opt.is_current" class="text-[11px] font-bold text-primary inline-flex items-center gap-0.5 group-hover:gap-1.5 transition-all shrink-0">
            {{ t('options_pick') }}
            <Icon name="material-symbols:arrow-forward" class="text-sm" />
          </span>
        </div>
      </button>
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

const props = defineProps<{
  options: TourOption[]
  loading?: boolean
}>()
const emit = defineEmits<{ (e: 'select', opt: TourOption): void }>()

const { t } = useI18n()
const currencyStore = useCurrencyStore()

const hasOptions = computed(() => Array.isArray(props.options) && props.options.length >= 2)
// Mobile always uses the slider — even with 2 options it's compacter.
const mobileSlider = computed(() => hasOptions.value)
const useSliderDesktop = computed(() => (props.options?.length || 0) > 3)

// Static, label-driven descriptions: the variant label already carries the
// product intent (Compartido / +Guía / Privado), so we map it to a short
// sentence that explains the differentiator instead of repeating the full
// activity name (which is already on the page H1). Falls back to the h1
// title shortened when the label is unknown, so unconfigured options
// degrade gracefully.
function optionDescription(opt: TourOption): string {
  const label = (opt.option_label || '').toLowerCase()
  if (label.includes('privado') && !label.includes('guía') && !label.includes('guia')) return t('options_desc_private')
  if (label.includes('guía') || label.includes('guia')) return t('options_desc_guided')
  if (label.includes('compartido') || label.includes('shared')) return t('options_desc_shared')
  return (opt.h1_title || '').replace(/^Tour\s+/i, '')
}

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

<style scoped>
.scrollbar-thin::-webkit-scrollbar { height: 6px; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgb(203 213 225); border-radius: 999px; }
.scrollbar-thin { scrollbar-width: thin; scrollbar-color: rgb(203 213 225) transparent; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
