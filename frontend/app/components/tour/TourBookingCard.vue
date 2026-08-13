<script setup lang="ts">
// Single booking widget shared by the mobile inline panel (`variant="inline"`)
// and the desktop sticky sidebar (`variant="sidebar"`). Previously this markup
// was duplicated ~300 lines across the tour page; now there's one source.
//
// Pricing is computed in the parent (quantity tiers, offers, group discount)
// and passed in as props; the four inputs (adults/children/date/time) are
// two-way via defineModel. The card only emits `book` / `add-to-cart`.
//
// NOTE: booking labels were hardcoded Spanish in the original page (not i18n),
// so they stay hardcoded here to avoid a regression; only the genuinely
// localized keys (booking_max_pax / booking_cancel_hint) go through t().
const props = defineProps<{
  tour: any
  variant?: 'inline' | 'sidebar'
  adultPrice: number
  childPrice: number
  basePrice: number
  subtotal: number
  total: number
  groupDiscount: number
  hasChildPricing: boolean
  adultAgeLabel?: string
  childAgeLabel?: string
  maxPax: number
  totalPax: number
  minDate: string
  availableTimes: any[]
  activeOffer: any
  tzInfo: any
  error: string
  cartFeedback: 'added' | 'duplicate' | null
}>()

defineEmits<{ book: []; addToCart: []; inquire: [] }>()

const adults = defineModel<number>('adults', { required: true })
const children = defineModel<number>('children', { required: true })
const selectedDate = defineModel<string>('selectedDate', { required: true })
const selectedTime = defineModel<string>('selectedTime', { required: true })

const { t } = useI18n()
const currencyStore = useCurrencyStore()
const fmt = (v: number) => currencyStore.formatConverted(v || 0)

const isInline = computed(() => props.variant !== 'sidebar')
const atMax = computed(() => props.totalPax >= props.maxPax)
// Tours flagged require_availability replace instant booking with an inquiry.
const requiresInquiry = computed(() => !!props.tour?.require_availability)
// Partial payment: the tour takes a deposit now (advance %) and the rest on the
// tour day. Only meaningful when the advance is a real fraction (0 < p < 100).
const partialPct = computed(() => {
  const p = Number(props.tour?.advance_payment_percentage)
  return Number.isFinite(p) && p > 0 && p < 100 ? p : null
})
const offerLabel = computed(() => {
  const o = props.activeOffer
  if (!o) return ''
  return o.discountType === 'percentage' ? `${o.discount}% OFF` : `$${o.discount} OFF`
})

// Transaction fee. The cart and checkout have always added it, but the tour
// page stopped at the subtotal — so a $158 tour turned into $181.70 one screen
// later, which is exactly the surprise that makes people abandon a cart. Shown
// here at the point of decision instead.
const feePercent = computed(() => {
  const p = Number(props.tour?.tax_percentage)
  return Number.isFinite(p) && p > 0 ? p : 0
})
const feeAmount = computed(() => props.total * feePercent.value / 100)
const totalWithFee = computed(() => props.total + feeAmount.value)
</script>

<template>
  <div class="bg-white border border-slate-200 rounded-2xl shadow-md">
    <!-- Price header — dominant, OTA pattern -->
    <div class="px-4 pt-4 pb-3 border-b border-slate-100">
      <div class="flex items-baseline gap-1.5 flex-wrap">
        <span
          class="font-black text-slate-900 tabular-nums tracking-tight leading-none"
          :class="isInline ? 'text-3xl sm:text-4xl' : 'text-[32px]'"
        >
          {{ fmt(basePrice) }}
        </span>
        <span class="text-sm font-semibold text-slate-500">{{ currencyStore.selectedCurrency }}</span>
        <span class="text-[11px] text-slate-500 font-medium">por persona</span>
      </div>
    </div>

    <div class="p-4 space-y-3">
      <!-- Travelers (group first, before date) -->
      <div>
        <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
          <Icon name="material-symbols:group-outline" class="size-4 text-primary" />
          Viajeros
        </label>
        <div class="space-y-2">
          <TourQuantityStepper
            v-model="adults"
            :label="`Adultos${adultAgeLabel ? ' ' + adultAgeLabel : ''}`"
            :hint="`${fmt(adultPrice)} c/u`"
            :min="1"
            :at-max="atMax"
          />
          <TourQuantityStepper
            v-if="hasChildPricing"
            v-model="children"
            :label="`Niños${childAgeLabel ? ' ' + childAgeLabel : ''}`"
            :hint="children > 0 ? `${fmt(childPrice)} c/u` : 'Opcional'"
            :min="0"
            :at-max="atMax"
          />
        </div>
        <!-- Why the + is disabled -->
        <p v-if="atMax" class="mt-1.5 text-[11px] font-semibold text-slate-500">
          {{ t('booking_max_pax', { n: maxPax }) }}
        </p>
      </div>

      <!-- Date -->
      <div>
        <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
          <Icon name="material-symbols:calendar-today-outline" class="size-4 text-primary" />
          Fecha
        </label>
        <TourCalendar
          v-model="selectedDate"
          :min-date="minDate"
          :offers="tour?.offers_data || []"
          :blocks="tour?.blocks_data || []"
          :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
          :special-days="tour?.special_days || tour?.availability_data?.specialDays || []"
          :availability-start="tour?.availability_data?.start || ''"
          :availability-end="tour?.availability_data?.end || ''"
        />
        <!-- (The date-specific offer badge was removed here — the discount % now
             lives on the "Descuento" line of the breakdown, so it isn't redundant.) -->
        <!-- Policy reassurance at the point of decision -->
        <p v-if="tour?.free_cancellation" class="mt-2 flex items-center gap-1.5 text-xs text-trust font-semibold">
          <Icon name="material-symbols:check-circle" class="size-4 shrink-0" />
          {{ t('booking_cancel_hint') }}
        </p>
      </div>

      <!-- Time -->
      <div>
        <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
          <label class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700">
            <Icon name="material-symbols:schedule-outline" class="size-4 text-primary" />
            Horario
          </label>
          <span v-if="tzInfo" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500" :title="`${tzInfo.name} (${tzInfo.gmt})`">
            <Icon name="material-symbols:language" class="size-3" />
            {{ tzInfo.code }} {{ tzInfo.gmt }}
          </span>
        </div>
        <TourTimeSelect v-model="selectedTime" :options="availableTimes" placeholder="Selecciona horario" />
      </div>

      <!-- Total -->
      <div class="rounded-lg bg-slate-50 p-3 space-y-1.5">
        <div class="flex justify-between text-xs text-slate-600">
          <span>{{ fmt(adultPrice) }} × {{ adults }} {{ adults === 1 ? 'adulto' : 'adultos' }}</span>
          <span class="tabular-nums font-medium">{{ fmt(adultPrice * adults) }}</span>
        </div>
        <div v-if="hasChildPricing && children > 0" class="flex justify-between text-xs text-slate-600">
          <span>{{ fmt(childPrice) }} × {{ children }} {{ children === 1 ? 'niño' : 'niños' }}</span>
          <span class="tabular-nums font-medium">{{ fmt(childPrice * children) }}</span>
        </div>
        <div v-if="groupDiscount > 0" class="flex justify-between text-xs">
          <span class="text-trust font-bold inline-flex items-center gap-1">
            <Icon name="material-symbols:sell-outline" class="size-3" />
            Descuento<span v-if="offerLabel" class="font-black">({{ offerLabel }})</span>
          </span>
          <span class="text-trust font-bold tabular-nums">−{{ fmt(groupDiscount) }}</span>
        </div>
        <div v-if="feePercent" class="flex justify-between text-xs text-slate-600">
          <span class="flex items-center gap-1">
            {{ t('transaction_fees') }}
            <AppPopover :label="t('transaction_fees')">
              <p class="leading-snug">{{ t('transaction_fees_info') }}</p>
            </AppPopover>
            <span class="tabular-nums">({{ feePercent.toFixed(2) }}%)</span>
          </span>
          <span class="tabular-nums font-medium">{{ fmt(feeAmount) }}</span>
        </div>
        <div class="flex justify-between items-baseline pt-1.5 border-t border-slate-200">
          <span class="text-sm font-bold">{{ feePercent ? t('total') : 'Subtotal' }}</span>
          <span class="text-xl font-black text-slate-900 tabular-nums">
            {{ fmt(totalWithFee) }}
            <span class="text-xs font-semibold text-slate-500 ml-0.5">{{ currencyStore.selectedCurrency }}</span>
          </span>
        </div>
      </div>

      <!-- Partial payment: pay a deposit now, the rest on the tour day -->
      <div v-if="partialPct && !requiresInquiry" class="flex items-start gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
        <Icon name="material-symbols:payments-outline" class="size-4 text-amber-600 shrink-0 mt-0.5" />
        <p class="text-xs text-amber-800 leading-snug">
          <span class="font-bold">Pago parcial disponible:</span>
          reserva con el <span class="font-bold">{{ partialPct }}%</span> ahora y paga el resto el día del tour en efectivo.
        </p>
      </div>

      <!-- Validation error (localized, inline) — only relevant to the instant
           booking flow, not the availability inquiry. -->
      <div v-if="error && !requiresInquiry" class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg" role="alert">
        <Icon name="material-symbols:error-outline" class="size-4 text-red-500 shrink-0" />
        <span class="text-xs font-semibold text-red-700">{{ error }}</span>
      </div>

      <!-- CTAs — tours that require availability verification can't be booked
           instantly; they capture a lead via the inquiry modal instead. -->
      <template v-if="requiresInquiry">
        <button
          @click="$emit('inquire')"
          class="btn-primary btn-lg w-full hover:shadow-xl hover:shadow-primary/30"
        >
          Consultar disponibilidad
          <Icon name="material-symbols:event-available-outline" class="size-5" />
        </button>
        <p class="mt-2 flex items-center justify-center gap-1.5 text-xs text-slate-500">
          <Icon name="material-symbols:info-outline" class="size-4 text-primary shrink-0" />
          Este tour requiere confirmar disponibilidad
        </p>
      </template>
      <template v-else>
        <button
          @click="$emit('book')"
          class="btn-primary btn-lg w-full hover:shadow-xl hover:shadow-primary/30"
        >
          RESERVAR AHORA
          <Icon name="material-symbols:arrow-forward" class="size-5" />
        </button>
        <button
          @click="$emit('add-to-cart')"
          class="btn-outline-primary w-full mt-2"
        >
          <Icon name="material-symbols:shopping-cart-outline" class="size-5" />
          Agregar al carrito
        </button>
        <div v-if="cartFeedback === 'added'" class="mt-1.5 flex items-center justify-center gap-1 text-xs font-semibold text-trust">
          <Icon name="material-symbols:check-circle" class="size-4" />
          Agregado al carrito — puedes seguir navegando
        </div>
        <div v-else-if="cartFeedback === 'duplicate'" class="mt-1.5 flex items-center justify-center gap-1.5 text-xs font-semibold text-amber-600">
          <Icon name="material-symbols:error-outline" class="size-4" />
          Ya está en tu carrito con esa fecha y hora
        </div>
      </template>

      <!-- Trust signals — inline variant only (the desktop sidebar keeps its own
           separate trust card below the widget). -->
      <div v-if="isInline" class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-100">
        <div v-if="tour?.free_cancellation" class="flex items-center gap-1.5 text-xs">
          <Icon name="material-symbols:check-circle" class="size-4 text-trust shrink-0" />
          <span class="text-slate-600 font-medium">Cancelación gratuita</span>
        </div>
        <div class="flex items-center gap-1.5 text-xs">
          <Icon name="material-symbols:schedule-outline" class="size-4 text-primary shrink-0" />
          <span class="text-slate-600 font-medium">Confirmación instantánea</span>
        </div>
        <div class="flex items-center gap-1.5 text-xs">
          <Icon name="material-symbols:verified-user-outline" class="size-4 text-primary shrink-0" />
          <span class="text-slate-600 font-medium">Mejor precio</span>
        </div>
      </div>
    </div>
  </div>
</template>
