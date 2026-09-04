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

const { t, locale } = useI18n()
const currencyStore = useCurrencyStore()
const fmt = (v: number) => currencyStore.formatConverted(v || 0)

const isInline = computed(() => props.variant !== 'sidebar')

// The month collapses once it has done its job. It is by far the tallest thing
// in the card, and leaving it open afterwards is what pushed the time select,
// the total and the button off a laptop screen — the scroll the traveller then
// had to fight while the calendar was still the only thing they could see.
const monthOpen = ref(false)
const showMonth = computed(() => !selectedDate.value || monthOpen.value)
watch(selectedDate, (d) => { if (d) monthOpen.value = false })

const chosenDateLabel = computed(() => {
  const raw = String(selectedDate.value || '').split('T')[0]
  const [y, m, d] = raw.split('-').map(Number)
  if (!y || !m || !d) return ''
  return new Date(y, m - 1, d).toLocaleDateString(locale.value || 'es', {
    weekday: 'short', day: 'numeric', month: 'long',
  })
})
// Party size lives behind the header count.
const paxOpen = ref(false)
const paxSummary = computed(() => {
  const parts = [`${adults.value} ${adults.value === 1 ? 'adulto' : 'adultos'}`]
  if (children.value > 0) parts.push(`${children.value} ${children.value === 1 ? 'niño' : 'niños'}`)
  return parts.join(' · ')
})
const atMax = computed(() => props.totalPax >= props.maxPax)

// Numbered steps. The card asks for three things in a fixed order — who is
// travelling, which day, which departure — but nothing said so, and the two
// that matter most looked like decoration next to a calendar that fills the
// panel. The number answers "what do I fill first"; the colour answers "what
// is left", so the next pending step is the one that still reads grey.
const stepClass = (done: boolean) => [
  'inline-flex items-center justify-center size-[18px] rounded-full text-[10px] font-black shrink-0 leading-none',
  done ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500',
]
// Children's second line carries both the age band and its status: which price
// applies once there is one, "Opcional" while there is none. The age comes
// first — it is what decides whether a given child belongs on this row at all.
const childHint = computed(() => {
  const estado = children.value > 0 ? `${fmt(props.childPrice)} c/u` : 'Opcional'
  return props.childAgeLabel ? `${props.childAgeLabel} · ${estado}` : estado
})
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
  <!-- The sidebar card is capped to the viewport. Pinned at top-24 with no
       cap, anything taller than the window put "Reservar ahora" permanently
       below the fold: on a 1366x768 laptop the button ended 126px past the
       bottom edge and no amount of page scrolling reached it, because the
       card is sticky. It fitted on a 1080p desktop, which is why it looked
       fine here and broken on a laptop. -->
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-md flex flex-col"
    :class="isInline ? '' : 'lg:max-h-[calc(100dvh-7rem)]'"
  >
    <!-- Price and action on one row, in the one part of the card that never
         scrolls. Every version of this panel so far has fought the same
         constraint — a sticky card capped to the window, a calendar taller
         than what is left, and a button that had to stay reachable. Putting
         the button up here settles it: it cannot be pushed off by anything
         below, and the height that was being reserved for it downstairs goes
         to the month. -->
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

      <button
        v-if="requiresInquiry"
        @click="$emit('inquire')"
        class="btn-primary shrink-0 hover:shadow-xl hover:shadow-primary/30"
      >
        Consultar
        <Icon name="material-symbols:event-available-outline" class="size-5" />
      </button>
      <button
        v-else
        @click="$emit('book')"
        class="btn-primary shrink-0 hover:shadow-xl hover:shadow-primary/30"
      >
        Reservar
        <Icon name="material-symbols:arrow-forward" class="size-5" />
      </button>
    </div>

    <!-- The error belongs with the button that raises it. Both CTAs produce the
         same one ("elige una fecha"), and this row is always on screen, so it
         is read wherever the traveller clicked from. -->
    <div v-if="error" class="px-4 pt-2.5 shrink-0" role="alert">
      <div class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
        <Icon name="material-symbols:error-outline" class="size-4 text-red-500 shrink-0" />
        <span class="text-[11px] font-semibold text-red-700">{{ error }}</span>
      </div>
    </div>

    <!-- Only the month scrolls when the window is short. Nothing scrolls on a
         tall screen: max-height never binds there, so this is identical to
         before. The time select deliberately does NOT live in here — its
         dropdown is absolutely positioned and would be clipped by the
         scroll container. -->
    <div class="relative px-4 pt-2 pb-2 min-h-0" :class="isInline ? '' : 'lg:overflow-y-auto lg:flex-1 tarjeta-scroll'">
      <div>
        <!-- No "Fecha y horario" label: a month grid announces itself, and the
             row cost height the calendar wanted. The timezone qualifies the
             TIME, not the date, so it now travels with the time select. -->
        <!-- The month is open until it is answered, then it folds to this line.
             Picking a date is the main job of this widget, so it starts open;
             once it is done, the 300px it occupies belong to the questions
             that come after it. -->
        <div v-if="showMonth" class="flex items-center gap-1.5 mb-1.5">
          <span :class="stepClass(!!selectedDate)">1</span>
          <span class="text-[11px] font-bold text-slate-600">Elige la fecha</span>
        </div>
        <button
          v-else
          type="button"
          @click="monthOpen = true"
          class="w-full flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors"
        >
          <span :class="stepClass(true)">1</span>
          <span class="min-w-0 flex-1">
            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">Fecha</span>
            <span class="block text-sm font-bold text-slate-800 truncate mt-0.5 first-letter:uppercase">{{ chosenDateLabel }}</span>
          </span>
          <span class="text-[11px] font-bold text-primary shrink-0">Cambiar</span>
        </button>
        <TourCalendar
          v-if="showMonth"
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
        <!-- The free-cancellation promise lives in the trust card right below
             this one; saying it twice cost a row of the calendar. -->
      </div>
    </div>

    <!-- Money and actions, grouped and set apart by the rule above them.
         shrink-0: this block is the one thing that must survive a short
         window, so it never gives up height to the calendar above it. -->
    <div class="p-4 pt-3 border-t border-slate-100 space-y-2 shrink-0">
      <!-- Travellers, now under the calendar: party size changes the price
           per person (quantity tiers and the group discount), so it is asked
           where the money is, not beside a date nobody has picked yet. It is
           not gated on the date — capacity is the tour's, not the day's.

           And, as before, a labeled FIELD rather than the icon chip it was: the
           chip showed a bare "2" whose meaning only existed in the aria-label,
           and a party with children had no visible breakdown. Label + words
           ("2 adultos · 1 niño") reads at a glance; the steppers stay behind
           a tap, which is what keeps the card short. -->
      <div class="flex items-center gap-2">
        <span :class="stepClass(true)">2</span>
        <div class="relative min-w-0 flex-1">
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
          class="absolute right-0 bottom-full mb-2 z-50 w-[21rem] max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white shadow-xl p-3 space-y-2"
        >
          <!-- The age band goes on its own line under each row. Appended to
               the label it read as part of the word ("Adultos (16-99)") and
               was the first thing to be truncated on a narrow popover, so the
               one piece of information that answers "does my 9-year-old count
               as a child here?" was the piece that disappeared. -->
          <TourQuantityStepper
            v-model="adults"
            label="Adultos"
            :hint="adultAgeLabel || undefined"
            :min="1"
            :at-max="atMax"
          />
          <TourQuantityStepper
            v-if="hasChildPricing"
            v-model="children"
            label="Niños"
            :hint="childHint"
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
      <!-- Times only once there is a date. An empty time select next to an
           empty date asks two questions at once and answers neither. -->
      <div v-if="selectedDate" class="flex items-center gap-2">
        <span :class="stepClass(!!selectedTime)">3</span>
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
      <!-- The amount and the cart describe an order that does not exist yet
           while the month is open, and their 142px are exactly what the
           calendar was missing — that was the scroll. They step aside until
           there is a date, and come back with it. (They cannot simply live
           inside the scrolling area instead: the travellers popover and the
           time dropdown are absolutely positioned and would be clipped by its
           edge, which is why the footer sits outside it in the first place.) -->
      <div v-if="!showMonth" class="rounded-lg bg-slate-50 px-3 py-2.5 space-y-1.5">
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
          <span class="text-sm font-bold text-slate-900">Subtotal</span>
          <span class="text-lg font-black text-slate-900 tabular-nums">
            {{ fmt(total) }}
            <span class="text-xs font-semibold text-slate-500 ml-0.5">{{ currencyStore.selectedCurrency }}</span>
          </span>
        </div>
        <!-- The deposit was a bordered amber card of its own. It is a fact
             about this amount, so it belongs inside the amount, and the border
             it dropped is a row the calendar keeps. -->
        <div v-if="partialPct && !requiresInquiry" class="flex items-center gap-1.5 pt-1.5 border-t border-slate-200 text-[11px] font-bold text-amber-700">
          <Icon name="material-symbols:payments-outline" class="size-3.5 shrink-0" />
          Reserva con el {{ partialPct }}% de adelanto
        </div>
      </div>

      <!-- Reservar now lives in the header, where it cannot be scrolled away
           from. What stays here is the cart: a second tour goes in from this
           button, and it belongs beside the amount it is adding. -->
      <template v-if="requiresInquiry">
        <p class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500">
          <Icon name="material-symbols:info-outline" class="size-4 text-primary shrink-0" />
          Este tour requiere confirmar disponibilidad
        </p>
      </template>
      <template v-else-if="!showMonth">
        <div class="flex items-stretch gap-2">
          <button
            @click="$emit('add-to-cart')"
            class="btn-outline-primary w-full whitespace-nowrap"
            title="Agregar al carrito"
          >
            <Icon name="material-symbols:shopping-cart-outline" class="size-5" />
            Agregar al carrito
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

    </div>
  </div>
</template>

<style scoped>
/* A month of six weeks cannot fit a 768px-tall laptop next to a price, a
   travellers field and a button, and the button is the one thing that must
   never leave the screen — so the calendar is what gives. The default
   scrollbar made that read as damage: a fat grey rail beside a September that
   appeared to end on the 27th. Thin, in the card's own grey, it reads as what
   it is. Firefox gets the standard property; WebKit needs its own.
   Literal hex rather than theme(): that helper is not available inside a
   scoped block here and took the whole page down with a 500. */
.tarjeta-scroll {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent; /* slate-300 */
}
.tarjeta-scroll::-webkit-scrollbar {
  width: 6px;
}
.tarjeta-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.tarjeta-scroll::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 9999px;
}
</style>
