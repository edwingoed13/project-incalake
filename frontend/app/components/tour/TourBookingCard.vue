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
// Party size lives behind the header count.
const paxOpen = ref(false)
const paxSummary = computed(() => {
  const parts = [`${adults.value} ${adults.value === 1 ? 'adulto' : 'adultos'}`]
  if (children.value > 0) parts.push(`${children.value} ${children.value === 1 ? 'niño' : 'niños'}`)
  return parts.join(' · ')
})
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
  <!-- No inner scroll. Capping this to the viewport and scrolling the body was
       tried: with the month open there is no height left for it, and the
       calendar — the whole reason the panel exists — showed two rows out of
       six. A nested scrollbar inside a sticky sidebar also reads as broken in
       a way a tall panel does not. So the card is as tall as its content and
       scrolls with the page, and the density work goes into keeping it short. -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-md flex flex-col">
    <!-- Price and party share one row: the price stacks its "por persona"
         caption underneath itself, freeing the right half for the travellers
         field. As two stacked rows the pair spent ~110px of card saying two
         short things; both were half empty. -->
    <div class="px-4 py-3 border-b border-slate-100 shrink-0 flex items-center justify-between gap-3">
      <div class="shrink-0">
        <div class="flex items-baseline gap-1">
          <span
            class="text-[26px] font-black text-slate-900 tabular-nums tracking-tight leading-none"
          >
            {{ fmt(basePrice) }}
          </span>
          <span class="text-[11px] font-semibold text-slate-500">{{ currencyStore.selectedCurrency }}</span>
        </div>
        <span class="block text-[11px] text-slate-500 font-medium mt-1">por persona</span>
      </div>

      <!-- Travellers as a labeled FIELD, not the icon chip this once was: the
           chip showed a bare "2" whose meaning only existed in the aria-label,
           and a party with children had no visible breakdown. Label + words
           ("2 adultos · 1 niño") reads at a glance; the steppers stay behind
           a tap, which is what keeps the card short. -->
      <div class="relative min-w-0">
        <button
          type="button"
          @click="paxOpen = !paxOpen"
          :aria-expanded="paxOpen"
          aria-haspopup="dialog"
          class="w-full flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors text-left"
        >
          <span class="flex items-center gap-2.5 min-w-0">
            <Icon name="material-symbols:group-outline" class="size-5 text-primary shrink-0" />
            <span class="min-w-0">
              <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">{{ t('travelers_lc') }}</span>
              <span class="block text-sm font-bold text-slate-800 truncate mt-0.5">{{ paxSummary }}</span>
            </span>
          </span>
          <Icon
            name="material-symbols:expand-more"
            class="size-5 text-slate-400 transition-transform shrink-0"
            :class="{ 'rotate-180': paxOpen }"
          />
        </button>

        <div
          v-if="paxOpen"
          role="dialog"
          aria-label="Viajeros"
          class="absolute right-0 top-full mt-2 z-50 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white shadow-xl p-3 space-y-2"
        >
          <!-- No per-unit hint on adults: the price above already says it.
               The child row keeps its own — that price is nowhere else. -->
          <TourQuantityStepper
            v-model="adults"
            :label="`Adultos${adultAgeLabel ? ' ' + adultAgeLabel : ''}`"
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
          <p v-if="atMax" class="text-[11px] font-semibold text-slate-500">
            {{ t('booking_max_pax', { n: maxPax }) }}
          </p>
          <button
            type="button"
            @click="paxOpen = false"
            class="w-full rounded-lg bg-slate-100 hover:bg-slate-200 py-2 text-sm font-bold text-slate-700 transition-colors"
          >
            Listo
          </button>
        </div>
        <div v-if="paxOpen" class="fixed inset-0 z-40" @click="paxOpen = false"></div>
      </div>
    </div>

    <div class="p-4 pt-3 space-y-1.5">
      <!-- Date + Time: one labeled row, two fields side by side. As stacked
           blocks with their own labels this pair cost ~180px of a phone's
           booking panel; the short placeholders keep the half-width fields
           readable. -->
      <div>
        <!-- No "Fecha y horario" label: a month grid announces itself, and the
             row cost height the calendar wanted. The timezone qualifies the
             TIME, not the date, so it now travels with the time select. -->
        <!-- The month sits open in the panel. Picking a date is the main job
             of this widget, and a click to reveal the calendar was a step in
             the way of it. -->
        <TourCalendar
          v-model="selectedDate"
          inline
          :min-date="minDate"
          :offers="tour?.offers_data || []"
          :blocks="tour?.blocks_data || []"
          :active-days="tour?.availability_data?.activeDays?.map(Number) || [0,1,2,3,4,5,6]"
          :special-days="tour?.special_days || tour?.availability_data?.specialDays || []"
          :availability-start="tour?.availability_data?.start || ''"
          :availability-end="tour?.availability_data?.end || ''"
        />
        <!-- Times only once there is a date. An empty time select next to an
             empty date asks two questions at once and answers neither. -->
        <div v-if="selectedDate" class="mt-2 flex items-center gap-2">
          <TourTimeSelect
            v-model="selectedTime"
            :options="availableTimes"
            placeholder="Horario"
            class="flex-1"
          />
          <span
            v-if="tzInfo"
            class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 shrink-0"
            :title="`${tzInfo.name} (${tzInfo.gmt})`"
          >
            <Icon name="material-symbols:language" class="size-3" />
            {{ tzInfo.code }} ({{ tzInfo.gmt }})
          </span>
        </div>
        <!-- The free-cancellation promise lives in the trust card right below
             this one; saying it twice cost a row of the calendar. -->
      </div>


    </div>

    <!-- Money and actions, grouped and set apart by the rule above them. -->
    <div class="p-4 pt-3 border-t border-slate-100 space-y-2.5">
      <!-- The money and the button stay pinned together. A total that
           scrolls out of view while the traveller is picking a date asks
           them to commit without seeing what they are committing to. -->
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
        <!-- The transaction fee moved to checkout, where it is charged. -->
        <div class="flex justify-between items-baseline pt-1.5 border-t border-slate-200">
          <span class="text-sm font-bold text-slate-900">Total</span>
          <span class="text-lg font-black text-slate-900 tabular-nums">
            {{ fmt(total) }}
            <span class="text-xs font-semibold text-slate-500 ml-0.5">{{ currencyStore.selectedCurrency }}</span>
          </span>
        </div>
      </div>

      <!-- Partial payment: pay a deposit now, the rest on the tour day -->
      <div v-if="partialPct && !requiresInquiry" class="flex items-start gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
        <Icon name="material-symbols:payments-outline" class="size-4 text-amber-600 shrink-0 mt-0.5" />
        <!-- One line. "Pagas el resto el día del tour" is what a deposit
             means; spelling it out cost a second row of the panel. -->
        <p class="text-xs font-bold text-amber-800 leading-snug">
          Reserva con el {{ partialPct }}% de adelanto
        </p>
      </div>

      <!-- Validation error (localized, inline) — only relevant to the instant
           booking flow, not the availability inquiry. -->
      <div v-if="error && !requiresInquiry" class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg" role="alert">
        <Icon name="material-symbols:error-outline" class="size-4 text-red-500 shrink-0" />
        <span class="text-[11px] font-semibold text-red-700">{{ error }}</span>
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
        <p class="mt-2 flex items-center justify-center gap-1.5 text-[11px] text-slate-500">
          <Icon name="material-symbols:info-outline" class="size-4 text-primary shrink-0" />
          Este tour requiere confirmar disponibilidad
        </p>
      </template>
      <template v-else>
        <!-- One row: the cart is a secondary action and does not need to match
             the width of the primary one. It keeps a real button box and its
             full name in the tooltip and the aria-label, because a bare icon
             is only obvious to people who already know what it does — and the
             cart is how someone books two tours in one go. -->
        <div class="flex items-stretch gap-2">
          <button
            @click="$emit('book')"
            class="btn-primary btn-lg flex-1 hover:shadow-xl hover:shadow-primary/30"
          >
            Reservar ahora
            <Icon name="material-symbols:arrow-forward" class="size-5" />
          </button>
          <button
            @click="$emit('add-to-cart')"
            class="btn-outline-primary shrink-0 !px-0 w-14"
            title="Agregar al carrito"
            aria-label="Agregar al carrito"
          >
            <Icon name="material-symbols:shopping-cart-outline" class="size-5" />
          </button>
        </div>
        <div v-if="cartFeedback === 'added'" class="mt-1.5 flex items-center justify-center gap-1 text-xs font-semibold text-trust">
          <Icon name="material-symbols:check-circle" class="size-4" />
          Agregado al carrito — puedes seguir navegando
        </div>
        <div v-else-if="cartFeedback === 'duplicate'" class="mt-1.5 flex items-center justify-center gap-1.5 text-xs font-semibold text-amber-600">
          <Icon name="material-symbols:error-outline" class="size-4" />
          Ya está en tu carrito con esa fecha y hora
        </div>
      </template>

      <!-- Trust signals, both variants. The sidebar used to carry a second
           bordered card below this one for the same three promises: 74px, its
           own border and a gap, to repeat what the inline variant already said
           in a strip. One strip, one card. Localized, unlike the booking
           labels around it — these three keys already existed and worked. -->
      <div class="flex flex-wrap gap-x-3 gap-y-1 pt-2.5 border-t border-slate-100 text-[11px] font-medium text-slate-600">
        <span v-if="tour?.free_cancellation" class="inline-flex items-center gap-1">
          <Icon name="material-symbols:check-circle" class="size-3.5 text-trust shrink-0" />
          {{ t('free_cancellation') }}
        </span>
        <span class="inline-flex items-center gap-1">
          <Icon name="material-symbols:schedule-outline" class="size-3.5 text-primary shrink-0" />
          {{ t('trust_instant') }}
        </span>
        <span class="inline-flex items-center gap-1">
          <Icon name="material-symbols:verified-user-outline" class="size-3.5 text-primary shrink-0" />
          {{ t('trust_best_price') }}
        </span>
      </div>
    </div>
  </div>
</template>
