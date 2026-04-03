<template>
  <div class="select-none relative">
    <!-- Trigger button -->
    <div
      @click="open = !open"
      class="w-full flex items-center gap-3 px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-primary/50 hover:shadow-sm transition-all"
    >
      <span class="material-symbols-outlined text-slate-400 text-lg">calendar_today</span>
      <span v-if="modelValue" class="text-sm font-semibold text-slate-800 dark:text-white">{{ formatSelected }}</span>
      <span v-else class="text-sm text-slate-400">Select date</span>
      <span class="material-symbols-outlined text-slate-400 text-sm ml-auto" :class="{ 'rotate-180': open }">expand_more</span>
    </div>

    <!-- Calendar dropdown -->
    <Transition name="cal">
      <div v-if="open" class="mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden lg:absolute lg:z-50 lg:left-0 lg:w-[560px]">
        <!-- Header: navigation -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
          <button @click="prevMonth" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-xl">chevron_left</span>
          </button>
          <div class="flex gap-12">
            <h4 class="text-base font-bold text-slate-800 dark:text-white capitalize">{{ monthName(currentMonth, currentYear) }}</h4>
            <h4 class="text-base font-bold text-slate-800 dark:text-white capitalize hidden sm:block">{{ monthName(nextMonth, nextYear) }}</h4>
          </div>
          <button @click="nextMonthNav" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-xl">chevron_right</span>
          </button>
        </div>

        <!-- Two month grid -->
        <div class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800">
          <!-- Month 1 -->
          <div class="flex-1 p-4">
            <div class="grid grid-cols-7 gap-0 mb-2">
              <span v-for="d in dayHeaders" :key="d" class="text-xs font-bold text-slate-400 text-center py-1">{{ d }}</span>
            </div>
            <div class="grid grid-cols-7 gap-1">
              <template v-for="(day, i) in calendarDays(currentMonth, currentYear)" :key="'m1-'+i">
                <button
                  v-if="day"
                  @click="selectDate(day.date)"
                  :disabled="day.disabled"
                  class="relative h-10 text-sm font-semibold rounded-xl transition-all"
                  :class="getDayClasses(day)"
                >
                  {{ day.day }}
                  <span v-if="day.hasOffer" class="absolute top-0 right-0.5 text-green-500 font-black leading-none" style="font-size: 6px;">%</span>
                </button>
                <span v-else class="h-10"></span>
              </template>
            </div>
          </div>

          <!-- Month 2 -->
          <div class="flex-1 p-4">
            <div class="sm:hidden flex items-center justify-center py-2">
              <h4 class="text-base font-bold text-slate-800 dark:text-white capitalize">{{ monthName(nextMonth, nextYear) }}</h4>
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
                  class="relative h-10 text-sm font-semibold rounded-xl transition-all"
                  :class="getDayClasses(day)"
                >
                  {{ day.day }}
                  <span v-if="day.hasOffer" class="absolute top-0 right-0.5 text-green-500 font-black leading-none" style="font-size: 6px;">%</span>
                </button>
                <span v-else class="h-10"></span>
              </template>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-5 text-xs font-semibold text-slate-400">
          <span class="flex items-center gap-1"><span class="text-green-500 text-[8px] font-black">%</span> Offer</span>
          <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span> Unavailable</span>
        </div>
      </div>
    </Transition>

    <!-- Close backdrop -->
    <div v-if="open" class="fixed inset-0 z-[-1]" @click="open = false"></div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string
  minDate?: string
  offers?: Array<{ startDate: string; endDate: string; discount: number; discountType: string }>
  blocks?: Array<{ startDate: string; endDate: string }>
  activeDays?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  minDate: '',
  offers: () => [],
  blocks: () => [],
  activeDays: () => [0, 1, 2, 3, 4, 5, 6],
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)

const today = new Date()
const currentMonth = ref(today.getMonth())
const currentYear = ref(today.getFullYear())

const nextMonth = computed(() => currentMonth.value === 11 ? 0 : currentMonth.value + 1)
const nextYear = computed(() => currentMonth.value === 11 ? currentYear.value + 1 : currentYear.value)

const dayHeaders = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const formatSelected = computed(() => {
  if (!props.modelValue) return ''
  const [y, m, d] = props.modelValue.split('-').map(Number)
  const date = new Date(y, m - 1, d)
  return date.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
})

function monthName(month: number, year: number) {
  return new Date(year, month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
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
    const isBlocked = props.blocks.some(b => dateStr >= b.startDate && dateStr <= b.endDate)
    const isActiveDay = props.activeDays.includes(dayOfWeek)
    const hasOffer = props.offers.some(o => dateStr >= o.startDate && dateStr <= o.endDate)

    days.push({
      day: d,
      date: dateStr,
      disabled: isPast || isBlocked || !isActiveDay,
      isToday: dateStr === todayStr,
      isSelected: dateStr === props.modelValue,
      hasOffer: hasOffer && !isPast,
      isBlocked,
      isPast,
    })
  }

  return days
}

function getDayClasses(day: CalDay): string {
  if (day.isSelected) return 'bg-primary text-white shadow-md'
  if (day.disabled) return 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
  if (day.hasOffer) return 'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400'
  if (day.isToday) return 'bg-primary/10 text-primary font-bold hover:bg-primary/20'
  return 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
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
