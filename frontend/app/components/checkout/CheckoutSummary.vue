<script setup lang="ts">
const { t, locale } = useI18n()
const cartStore = useCartStore()
const currencyStore = useCurrencyStore()
const localePath = useLocalePath()

const localeMap: Record<string, string> = {
  es: 'es-PE', en: 'en-US', pt: 'pt-BR', fr: 'fr-FR', de: 'de-DE', it: 'it-IT'
}

const formatDate = (d: string) => {
  if (!d) return ''
  const [y, m, day] = d.split('-').map(Number)
  return new Date(y, m - 1, day).toLocaleDateString(localeMap[locale.value] || 'en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

const formatTime = (tr: string) => {
  if (!tr) return ''
  const [h, m] = tr.split(':')
  const hour = parseInt(h)
  return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const guideTypeLabels = computed<Record<string, string>>(() => ({
  live_guide: t('guide_live'), audio_guide: t('guide_audio'),
  informative_brochures: t('guide_brochures'), no_guide: t('guide_self'),
}))

// Sort items by tour date (earliest first)
const sortedItems = computed(() =>
  [...cartStore.items].sort((a, b) => a.selectedDate.localeCompare(b.selectedDate))
)
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-5 sticky top-24 border border-slate-200 dark:border-slate-800">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-black">{{ t('booking_summary') }}</h3>
      <NuxtLink :to="localePath('/cart')" class="text-xs font-semibold text-primary hover:underline flex items-center gap-0.5">
        <span class="material-symbols-outlined text-xs">edit</span> {{ t('edit') }}
      </NuxtLink>
    </div>

    <!-- Tours -->
    <div class="space-y-3 mb-4 pb-4 border-b border-slate-100">
      <div v-for="item in sortedItems" :key="item.id" class="space-y-1.5">
        <h4 class="text-sm font-bold text-slate-800 line-clamp-1">{{ item.tourTitle }}</h4>

        <div v-if="item.hasOffer" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold" :style="{ backgroundColor: (item.offerColor || '#22c55e') + '15', color: item.offerColor || '#22c55e' }">
          <span class="material-symbols-outlined text-[10px]">sell</span>
          {{ item.offerDiscount }}{{ item.offerDiscountType === 'percentage' ? '%' : ' USD' }} OFF
        </div>

        <div class="space-y-0.5 text-[11px] text-slate-500">
          <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-xs">calendar_today</span>
            {{ formatDate(item.selectedDate) }} · {{ formatTime(item.selectedTime) }}{{ item.durationLabel ? ` · ${item.durationLabel}` : '' }}
          </div>
          <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-xs">group</span>
            {{ item.adults }} {{ item.adults === 1 ? t('adult') : t('adults') }}
          </div>
          <div v-if="item.guideType && item.guideType !== 'none'" class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-xs">record_voice_over</span>
            {{ guideTypeLabels[item.guideType as string] || item.guideType }}{{ item.guideLanguages?.length ? ` [ ${item.guideLanguages.join(', ')} ]` : '' }}
          </div>
        </div>

        <div class="flex justify-between items-center pt-1">
          <span v-if="item.hasOffer && item.originalPrice" class="text-[10px] line-through text-slate-400">{{ currencyStore.formatConverted(item.originalPrice * item.adults) }}</span>
          <span v-else></span>
          <span class="text-sm font-black text-slate-800">{{ currencyStore.formatConverted(item.total) }}</span>
        </div>
      </div>
    </div>

    <!-- Totals -->
    <div class="space-y-1.5 mb-4 pb-4 border-b border-slate-100">
      <div class="flex justify-between text-xs">
        <span class="text-slate-500">{{ t('subtotal') }}</span>
        <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.subtotal) }}</span>
      </div>
      <div v-if="cartStore.totalTax > 0" class="flex justify-between text-xs">
        <span class="text-slate-500 flex items-center gap-1">
          {{ t('transaction_fees') }} ({{ cartStore.taxPercentageLabel }})
          <span class="relative group cursor-help">
            <span class="material-symbols-outlined text-slate-400 text-xs">info</span>
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-800 text-white text-[10px] rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
              <div v-for="item in sortedItems" :key="'tax-'+item.id" class="flex justify-between py-0.5">
                <span class="truncate mr-2">{{ item.tourTitle }}</span>
                <span class="shrink-0 font-semibold">{{ item.taxPercentage || 0 }}%</span>
              </div>
              <div class="w-2 h-2 bg-slate-800 rotate-45 absolute -bottom-1 left-1/2 -translate-x-1/2"></div>
            </div>
          </span>
        </span>
        <span class="font-semibold">{{ currencyStore.formatConverted(cartStore.totalTax) }}</span>
      </div>
    </div>

    <div class="flex justify-between items-center">
      <span class="font-black">{{ t('total') }}</span>
      <span class="text-xl font-black text-primary">{{ currencyStore.formatConverted(cartStore.totalAmount) }}</span>
    </div>

    <div v-if="currencyStore.isForeignCurrency" class="mt-3 flex items-start gap-1.5 p-2 bg-amber-50 border border-amber-200 rounded-lg">
      <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
      <span class="text-[10px] text-amber-800 leading-tight">{{ t('payment_usd_notice') }}</span>
    </div>

    <!-- Trust -->
    <div class="mt-4 pt-4 border-t border-slate-100 space-y-1">
      <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
        <span class="material-symbols-outlined text-green-500 text-xs">shield</span> {{ t('secure_payment') }}
      </div>
      <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
        <span class="material-symbols-outlined text-yellow-500 text-xs">bolt</span> {{ t('instant_confirmation') }}
      </div>
    </div>
  </div>
</template>
