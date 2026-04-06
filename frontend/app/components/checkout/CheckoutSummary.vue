<script setup lang="ts">
const { t } = useI18n()
const cartStore = useCartStore()
const localePath = useLocalePath()

const formatDate = (d: string) => {
  if (!d) return ''
  const [y, m, day] = d.split('-').map(Number)
  return new Date(y, m - 1, day).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

const formatTime = (t: string) => {
  if (!t) return ''
  const [h, m] = t.split(':')
  const hour = parseInt(h)
  return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const guideTypeLabels: Record<string, string> = {
  live_guide: 'Live Guide', audio_guide: 'Audio Guide',
  informative_brochures: 'Brochures', no_guide: 'Self-guided',
}
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
      <div v-for="item in cartStore.items" :key="item.id" class="space-y-1.5">
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
            {{ item.adults }} adult{{ item.adults !== 1 ? 's' : '' }}
          </div>
          <div v-if="item.guideType && item.guideType !== 'none'" class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-xs">record_voice_over</span>
            {{ guideTypeLabels[item.guideType] || item.guideType }}{{ item.guideLanguages?.length ? ` [ ${item.guideLanguages.join(', ')} ]` : '' }}
          </div>
        </div>

        <div class="flex justify-between items-center pt-1">
          <span v-if="item.hasOffer && item.originalPrice" class="text-[10px] line-through text-slate-400">${{ (item.originalPrice * item.adults).toFixed(2) }}</span>
          <span v-else></span>
          <span class="text-sm font-black text-slate-800">${{ item.total.toFixed(2) }}</span>
        </div>
      </div>
    </div>

    <!-- Totals -->
    <div class="space-y-1.5 mb-4 pb-4 border-b border-slate-100">
      <div class="flex justify-between text-xs">
        <span class="text-slate-500">{{ t('subtotal') }}</span>
        <span class="font-semibold">${{ cartStore.subtotal.toFixed(2) }}</span>
      </div>
      <div v-if="cartStore.totalTax > 0" class="flex justify-between text-xs">
        <span class="text-slate-500">{{ t('transaction_fees') }}</span>
        <span class="font-semibold">${{ cartStore.totalTax.toFixed(2) }}</span>
      </div>
    </div>

    <div class="flex justify-between items-center">
      <span class="font-black">{{ t('total') }}</span>
      <span class="text-xl font-black text-primary">${{ cartStore.totalAmount.toFixed(2) }}</span>
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
