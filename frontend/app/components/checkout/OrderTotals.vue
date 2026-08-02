<script setup lang="ts">
// Order-summary money block shared by the cart sidebar and the Culqi/PayPal
// payment pages: subtotal, transaction fees (info popover with optional
// per-item breakdown and inline uniform %), total, optional advance-balance
// row and the foreign-currency USD notice. Terms/buttons stay in the pages.
const props = withDefaults(defineProps<{
  itemsLabel: string
  subtotal: number
  tax: number
  total: number
  totalLabel?: string
  /** Uniform rate shown inline next to the fees label, e.g. 5 → "(5.00%)". */
  taxPercent?: number | null
  /** Per-item rows inside the popover (cart with mixed rates). */
  taxBreakdown?: Array<{ label: string; percent: number }>
  /** Payment pages: show "≈ $X USD" under the notice and the USD suffix. */
  usdApprox?: number | null
  balanceLabel?: string
  balance?: number | null
}>(), { taxPercent: null, usdApprox: null, balance: null })

const { t } = useI18n()
const currencyStore = useCurrencyStore()
</script>

<template>
  <div>
    <div class="space-y-2 mb-4 pb-4 border-b border-slate-100">
      <div class="flex justify-between text-xs">
        <span class="text-slate-500">{{ itemsLabel }}</span>
        <span class="font-semibold">{{ currencyStore.formatConverted(subtotal) }}</span>
      </div>
      <div v-if="tax > 0" class="flex justify-between text-xs">
        <span class="text-slate-500 flex items-center gap-1 flex-wrap">
          {{ t('transaction_fees') }}
          <AppPopover :label="t('transaction_fees')" :width="taxBreakdown?.length ? 'w-72' : undefined">
            <p class="leading-snug" :class="taxBreakdown?.length ? 'mb-1.5' : ''">{{ t('transaction_fees_info') }}</p>
            <div v-if="taxBreakdown?.length" class="pt-1.5 mt-1.5 border-t border-white/15">
              <p class="text-[9px] font-bold uppercase tracking-wider text-white/60 mb-1">{{ t('transaction_fees') }}</p>
              <div v-for="(row, i) in taxBreakdown" :key="i" class="flex justify-between py-0.5 gap-2">
                <span class="flex-1 break-words">{{ row.label }}</span>
                <span class="shrink-0 font-semibold">{{ row.percent }}%</span>
              </div>
            </div>
          </AppPopover>
          <span v-if="taxPercent !== null" class="font-bold text-slate-700 tabular-nums">({{ taxPercent.toFixed(2) }}%)</span>
        </span>
        <span class="font-semibold">{{ currencyStore.formatConverted(tax) }}</span>
      </div>
    </div>

    <div class="flex justify-between items-center mb-3">
      <span class="font-black">{{ totalLabel || t('total') }}</span>
      <span class="text-2xl font-black text-primary">
        {{ currencyStore.formatConverted(total) }}
        <span v-if="usdApprox !== null && !currencyStore.isForeignCurrency" class="text-sm font-semibold text-slate-400">USD</span>
      </span>
    </div>

    <div v-if="balance !== null && balance > 0.009" class="flex justify-between items-center -mt-2 mb-3 text-xs text-slate-500">
      <span>{{ balanceLabel }}</span>
      <span class="font-semibold">{{ currencyStore.formatConverted(balance) }}</span>
    </div>

    <div v-if="currencyStore.isForeignCurrency" class="mb-4 flex items-start gap-1.5 p-2 bg-amber-50 border border-amber-200 rounded-lg">
      <Icon name="material-symbols:info-outline" class="text-amber-600 text-sm mt-0.5" />
      <div class="flex-1">
        <p class="text-[11px] text-amber-800 leading-tight font-semibold">{{ t('payment_usd_notice') }}</p>
        <p v-if="usdApprox !== null" class="text-[10px] text-amber-700 mt-0.5">≈ ${{ usdApprox.toFixed(2) }} USD</p>
      </div>
    </div>
  </div>
</template>
