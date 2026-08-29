<template>
  <div ref="root" class="select-none" :class="inline ? '' : 'relative'">
    <!-- Trigger button — real button semantics + keyboard + aria-expanded -->
    <button
      v-if="!inline"
      type="button"
      @click="open = !open"
      :aria-expanded="open"
      aria-haspopup="dialog"
      class="w-full flex items-center bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-primary/50 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all text-left"
      :class="placeholder ? 'gap-2 px-3 py-3.5' : 'gap-3 px-4 py-3.5'"
    >
      <Icon name="material-symbols:calendar-today-outline" class="text-slate-400 text-lg shrink-0" />
      <span v-if="modelValue" class="text-sm font-semibold text-slate-800 truncate">{{ formatSelected }}</span>
      <span v-else class="text-sm text-slate-400 truncate">{{ placeholder || t('select_date') }}</span>
      <Icon name="material-symbols:expand-more" :class="{ 'rotate-180': open }" class="text-slate-400 text-sm ml-auto shrink-0" />
    </button>

    <!-- Calendar: bottom-sheet on mobile (doesn't push the page), popover on desktop -->
    <Transition name="cal">
      <div
        v-if="inline || open"
        :role="inline ? undefined : 'dialog'"
        :aria-modal="inline ? undefined : 'true'"
        :aria-label="inline ? undefined : t('select_date')"
        :class="[
          inline
            ? 'bg-white max-w-[300px] mx-auto'
            : 'bg-white border border-slate-200 shadow-2xl overflow-y-auto',
          inline ? '' : 'fixed inset-x-0 bottom-0 z-50 rounded-t-3xl max-h-[88vh]',
          inline ? '' : 'lg:absolute lg:inset-x-auto lg:right-0 lg:rounded-2xl lg:w-[560px] lg:max-w-[calc(100vw-2rem)]',
          // Flip above the field when the space under it is too short. Anchored
          // downwards always, the panel ran off the bottom of the window on a
          // 31-day month and the offer legend went with it.
          inline ? '' : (dropUp ? 'lg:bottom-full lg:mb-2' : 'lg:bottom-auto lg:mt-2'),
        ]"
        :style="panelStyle"
      >
        <!-- Mobile grab handle -->
        <div v-if="!inline" class="lg:hidden flex justify-center pt-3 pb-1">
          <div class="w-10 h-1 bg-slate-300 rounded-full"></div>
        </div>
        <!-- Header: navigation -->
        <div class="flex items-center justify-between border-b border-slate-100" :class="inline ? 'px-2 py-1.5' : 'px-6 py-4'">
          <button @click="prevMonth" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <Icon name="material-symbols:chevron-left" class="text-xl" />
          </button>
          <div class="flex gap-12">
            <h4 class="text-base font-bold text-slate-800">{{ monthName(currentMonth, currentYear) }}</h4>
            <h4 v-if="!inline" class="text-base font-bold text-slate-800 hidden sm:block">{{ monthName(nextMonth, nextYear) }}</h4>
          </div>
          <button @click="nextMonthNav" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <Icon name="material-symbols:chevron-right" class="text-xl" />
          </button>
        </div>

        <!-- Two month grid -->
        <div class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
          <!-- Month 1 -->
          <div class="flex-1" :class="inline ? 'px-2 pb-2 pt-1' : 'p-4'">
            <div class="grid grid-cols-7 gap-0 mb-2">
              <span v-for="d in dayHeaders" :key="d" class="text-xs font-bold text-slate-400 text-center py-1">{{ d }}</span>
            </div>
            <div class="grid grid-cols-7 gap-1">
              <template v-for="(day, i) in calendarDays(currentMonth, currentYear)" :key="'m1-'+i">
                <button
                  v-if="day"
                  @click="selectDate(day.date)"
                  :disabled="day.disabled"
                  :aria-label="fullDateLabel(day)"
                  :aria-pressed="day.isSelected"
                  class="relative w-full font-semibold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-primary/40"
                  :class="[inline ? 'h-9 text-xs' : 'h-11 text-sm', getDayClasses(day)]"
                  :style="getDayStyle(day)"
                >
                  {{ day.day }}
                  <!-- Offer marker: a legible colored dot (was an illegible 6px '%') -->
                  <span v-if="day.hasOffer" class="absolute top-1 right-1 size-1.5 rounded-full" :style="{ backgroundColor: day.offerColor }" aria-hidden="true"></span>
                </button>
                <span v-else :class="inline ? 'h-9' : 'h-11'"></span>
              </template>
            </div>
          </div>

          <!-- Month 2 — dropped inline: the sidebar has room for one month,
               and the arrows still reach the rest. -->
          <div v-if="!inline" class="flex-1 p-4">
            <div class="sm:hidden flex items-center justify-center py-2">
              <h4 class="text-base font-bold text-slate-800">{{ monthName(nextMonth, nextYear) }}</h4>
            </div>
            <div class="grid grid-cols-7 gap-0 mb-2">
              <span v-for="d in dayHeaders" :key="d" class="text-xs font-bold text-slate-400 text-center py-1">{{ d }}</span>
            </div>
            <div class="grid grid-cols-7 gap-1">
              <template v-for="(day, i) in calendarDays(nextMonth, nextYear)" :key="'m2-'+i">
                <button
                  v-if="day"
                  @click="selectDate(day.date)"
                  :disabled="day.disabled"
                  :aria-label="fullDateLabel(day)"
                  :aria-pressed="day.isSelected"
                  class="relative w-full font-semibold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-primary/40"
                  :class="[inline ? 'h-9 text-xs' : 'h-11 text-sm', getDayClasses(day)]"
                  :style="getDayStyle(day)"
                >
                  {{ day.day }}
                  <!-- Offer marker: a legible colored dot (was an illegible 6px '%') -->
                  <span v-if="day.hasOffer" class="absolute top-1 right-1 size-1.5 rounded-full" :style="{ backgroundColor: day.offerColor }" aria-hidden="true"></span>
                </button>
                <span v-else :class="inline ? 'h-9' : 'h-11'"></span>
              </template>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div
          v-if="offers.length"
          class="border-t border-slate-100 flex items-center gap-5 font-semibold text-slate-400"
          :class="inline ? 'px-3 py-1.5 text-[11px]' : 'px-6 py-3 text-xs'"
        >
          <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-amber-500"></span> {{ t('offer_legend') }}</span>
        </div>

        <!-- Mobile: explicit "Done" to close the sheet (selecting a day also closes) -->
        <div v-if="!inline" class="lg:hidden sticky bottom-0 bg-white border-t border-slate-100 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
          <button type="button" @click="open = false"
            class="w-full min-h-[48px] bg-primary hover:bg-primary-dark text-white font-bold rounded-xl active:scale-[0.98] transition-transform">
            {{ t('done') || 'Listo' }}
          </button>
        </div>
      </div>
    </Transition>

    <!-- Click-outside / dim backdrop: real overlay (was z-[-1], which sat behind
         the page and never caught clicks). Dim on mobile, invisible on desktop. -->
    <div v-if="!inline && open" class="fixed inset-0 z-40 bg-black/40 lg:bg-transparent" @click="open = false"></div>
  </div>
</template>

<script setup lang="ts">
const { t, locale } = useI18n()

// Map the active i18n locale to a proper Intl/BCP-47 locale so month names
// and weekday headers render in the user's language (was hardcoded en-US).
const intlLocale = computed(() => {
  const map: Record<string, string> = {
    es: 'es-ES', en: 'en-US', pt: 'pt-BR',
    fr: 'fr-FR', de: 'de-DE', it: 'it-IT',
  }
  return map[String(locale.value)] || 'es-ES'
})

const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1)

interface Props {
  modelValue: string
  minDate?: string
  maxDate?: string  // Availability period end
  availabilityStart?: string  // Availability period start
  availabilityEnd?: string  // Availability period end
  offers?: Array<{ startDate: string; endDate: string; discount: number; discountType: string; color?: string }>
  blocks?: Array<{ startDate: string; endDate: string }>
  activeDays?: number[]
  specialDays?: string[]  // Holidays in "DD-MM" format (e.g. "25-12")
  /** Trigger text when no date is picked. The default full sentence does not
      fit when the field shares a row with the time select. */
  placeholder?: string
  /** Render the grid straight into the page instead of behind a trigger.
      The booking sidebar shows the month permanently: a traveller picking a
      date is the main thing that panel is for, and hiding it behind a click
      cost a step at exactly the wrong moment. */
  inline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  minDate: '',
  maxDate: '',
  availabilityStart: '',
  availabilityEnd: '',
  offers: () => [],
  blocks: () => [],
  activeDays: () => [0, 1, 2, 3, 4, 5, 6],
  inline: false,
  specialDays: () => [],
  placeholder: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)

// Where the panel goes on desktop. It used to hang below the field
// unconditionally, so on the tour page — where the field sits low in the
// booking card — a 6-row month pushed the bottom of the panel, legend
// included, past the edge of the window. The traveller could not tell whether
// a date carried an offer without scrolling the page. So: measure the room on
// each side of the field, open towards the roomier one, and never let the
// panel be taller than the room it has.
const root = ref<HTMLElement | null>(null)
const dropUp = ref(false)
const isDesktop = ref(false)
const panelMaxH = ref(0)

// Two months side by side, six week rows: about 400px of panel.
const NATURAL_H = 420
const EDGE_GAP = 16
const MIN_USABLE = 280

function place() {
  if (typeof window === 'undefined' || props.inline) return
  isDesktop.value = window.matchMedia('(min-width: 1024px)').matches
  // Mobile is a bottom sheet — nothing to measure.
  if (!isDesktop.value || !open.value) return
  const trigger = root.value?.querySelector('button')
  if (!trigger) return
  const r = trigger.getBoundingClientRect()
  const below = window.innerHeight - r.bottom - EDGE_GAP
  const above = r.top - EDGE_GAP
  dropUp.value = below < NATURAL_H && above > below
  panelMaxH.value = Math.max(MIN_USABLE, Math.floor(dropUp.value ? above : below))
}

const panelStyle = computed(() =>
  isDesktop.value && panelMaxH.value ? { maxHeight: `${panelMaxH.value}px` } : undefined
)

watch(open, (v) => { if (v) nextTick(place) })

onMounted(() => {
  window.addEventListener('resize', place)
  // Capture phase: the booking card scrolls inside its own container too.
  window.addEventListener('scroll', place, true)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', place)
  window.removeEventListener('scroll', place, true)
})

const today = new Date()
const currentMonth = ref(today.getMonth())
const currentYear = ref(today.getFullYear())

const nextMonth = computed(() => currentMonth.value === 11 ? 0 : currentMonth.value + 1)
const nextYear = computed(() => currentMonth.value === 11 ? currentYear.value + 1 : currentYear.value)

// Weekday short labels, localized, Monday-first (matches the Mon-Sun grid).
const dayHeaders = computed(() => {
  // 2024-01-01 is a Monday — walk 7 days from it.
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(2024, 0, 1 + i)
    return cap(d.toLocaleDateString(intlLocale.value, { weekday: 'short' }).replace('.', ''))
  })
})

const formatSelected = computed(() => {
  if (!props.modelValue) return ''
  const [y, m, d] = props.modelValue.split('-').map(Number)
  const date = new Date(y, m - 1, d)
  // Compact usage (custom placeholder = half-width field): drop the year so
  // "Jue, 20 ago" fits — the full date is visible in the calendar itself.
  const opts: Intl.DateTimeFormatOptions = props.placeholder
    ? { weekday: 'short', day: 'numeric', month: 'short' }
    : { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }
  return cap(date.toLocaleDateString(intlLocale.value, opts))
})

function monthName(month: number, year: number) {
  return cap(new Date(year, month, 1).toLocaleDateString(intlLocale.value, { month: 'long', year: 'numeric' }))
}

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonthNav() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

interface CalDay {
  day: number
  date: string
  disabled: boolean
  isToday: boolean
  isSelected: boolean
  hasOffer: boolean
  offerColor: string
  isBlocked: boolean
  isPast: boolean
}

function calendarDays(month: number, year: number): (CalDay | null)[] {
  const firstDay = new Date(year, month, 1)
  // Monday = 0, Sunday = 6
  let startDay = firstDay.getDay() - 1
  if (startDay < 0) startDay = 6

  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const days: (CalDay | null)[] = []

  // Empty cells before first day
  for (let i = 0; i < startDay; i++) days.push(null)

  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const dayOfWeek = new Date(year, month, d).getDay() // 0=Sun, 6=Sat

    const isPast = dateStr < todayStr || (props.minDate && dateStr < props.minDate)
    // Check if date is outside the tour's availability range (start/end)
    const isBeforeRange = props.availabilityStart && dateStr < props.availabilityStart
    const isAfterRange = props.availabilityEnd && dateStr > props.availabilityEnd
    const rangeBlocked = props.blocks.some(b => dateStr >= b.startDate && dateStr <= b.endDate)
    // Check if this date is a blocked holiday (DD-MM format)
    const ddmm = `${String(d).padStart(2, '0')}-${String(month + 1).padStart(2, '0')}`
    const isHoliday = props.specialDays.includes(ddmm)
    const isBlocked = rangeBlocked || isHoliday || isBeforeRange || isAfterRange
    const isActiveDay = props.activeDays.includes(dayOfWeek)
    const isDisabled = isPast || isBlocked || !isActiveDay
    const matchingOffer = props.offers.find(o => dateStr >= o.startDate && dateStr <= o.endDate)

    days.push({
      day: d,
      date: dateStr,
      disabled: isDisabled,
      isToday: dateStr === todayStr,
      isSelected: dateStr === props.modelValue,
      // Only badge offers on actually selectable days: a "%" on top of a
      // blocked / past / inactive day reads as "available with discount" and
      // confuses the user.
      hasOffer: !!matchingOffer && !isDisabled,
      offerColor: matchingOffer?.color || '#f59e0b',
      isBlocked,
      isPast,
    })
  }

  return wrapToFiveRows(days, startDay)
}

/**
 * Fold a sixth week back into the empty cells that open the first one.
 *
 * A month like August 2026 starts on a Saturday and runs to 31 days, so the
 * 31st lands alone on a sixth row and costs the panel a whole week of height
 * to show one date. Printed calendars have always solved this by putting that
 * day in the gap at the top instead, and the move is weekday-safe rather than
 * cosmetic: cell 35 falls in the Monday column, exactly where the leading gap
 * begins, so each moved day keeps the column it belongs in — the 31st is a
 * Monday and lands under "Lun". Since a month is at most 31 days there are
 * never more overflow days than gap cells to take them.
 */
function wrapToFiveRows(days: (CalDay | null)[], startDay: number): (CalDay | null)[] {
  if (days.length <= 35 || startDay === 0) return days

  const overflow = days.slice(35)
  const grid = days.slice(0, 35)
  overflow.forEach((day, i) => {
    if (day && i < startDay) grid[i] = day
  })

  return grid
}

function getDayClasses(day: CalDay): string {
  if (day.isSelected) return 'bg-primary text-white shadow-md'
  if (day.disabled) return 'text-slate-300 cursor-not-allowed'
  if (day.hasOffer) return 'offer-day'
  if (day.isToday) return 'bg-primary/10 text-primary font-bold hover:bg-primary/20'
  return 'text-slate-700 hover:bg-slate-100'
}

function getDayStyle(day: CalDay): Record<string, string> {
  if (day.hasOffer && !day.isSelected && !day.disabled) {
    return {
      backgroundColor: day.offerColor + '18',
      color: day.offerColor,
    }
  }
  return {}
}

// Full, localized date for the day button's aria-label (screen readers hear
// "Sábado, 12 de julio" instead of just "12").
function fullDateLabel(day: CalDay): string {
  const [y, m, d] = day.date.split('-').map(Number)
  return cap(new Date(y, m - 1, d).toLocaleDateString(intlLocale.value, { weekday: 'long', day: 'numeric', month: 'long' }))
}

function selectDate(date: string) {
  emit('update:modelValue', date)
  open.value = false
}
</script>

<style scoped>
.cal-enter-active, .cal-leave-active { transition: all 0.2s ease; }
.cal-enter-from, .cal-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
