<template>
  <div class="relative">
    <!-- Input Field -->
    <div class="relative">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">calendar_today</span>
      <input
        :value="formattedDate"
        @click="toggleCalendar"
        readonly
        placeholder="Selecciona una fecha"
        class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary cursor-pointer"
      />
    </div>

    <!-- Calendar Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div v-if="showCalendar" class="absolute z-50 mt-2 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 min-w-[320px]">
        <!-- Month Navigation -->
        <div class="flex items-center justify-between mb-4">
          <button
            @click="previousMonth"
            class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            <span class="material-symbols-outlined text-lg">chevron_left</span>
          </button>

          <h3 class="text-sm font-bold">
            {{ monthNames[currentMonth] }} {{ currentYear }}
          </h3>

          <button
            @click="nextMonth"
            class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            <span class="material-symbols-outlined text-lg">chevron_right</span>
          </button>
        </div>

        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 gap-1 mb-2">
          <div v-for="day in weekDays" :key="day" class="text-center text-xs font-bold text-slate-500 py-1">
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-1">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            @click="selectDate(day)"
            class="relative aspect-square flex items-center justify-center rounded-lg text-sm font-medium transition-all cursor-pointer"
            :class="getDayClasses(day)"
          >
            <span class="relative z-10">{{ day.day }}</span>

            <!-- Indicators -->
            <div v-if="day.hasOffer" class="absolute top-0 right-0 w-2 h-2 rounded-full" :style="{ backgroundColor: day.offerColor }"></div>
            <div v-if="day.isBlocked" class="absolute inset-0 bg-red-500/10 rounded-lg"></div>
          </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 space-y-2">
          <div v-if="hasBlocks" class="flex items-center gap-2 text-xs">
            <div class="w-4 h-4 bg-red-500/20 rounded"></div>
            <span class="text-slate-600 dark:text-slate-400">Fecha bloqueada</span>
          </div>
          <div v-if="hasOffers" class="flex items-center gap-2 text-xs">
            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            <span class="text-slate-600 dark:text-slate-400">Oferta disponible</span>
          </div>

          <!-- Selected Date Info -->
          <div v-if="selectedDateInfo" class="mt-3 p-2 rounded-lg" :class="selectedDateInfo.isBlocked ? 'bg-red-50 dark:bg-red-900/20' : 'bg-green-50 dark:bg-green-900/20'">
            <p class="text-xs font-semibold" :class="selectedDateInfo.isBlocked ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400'">
              {{ selectedDateInfo.message }}
            </p>
            <p v-if="selectedDateInfo.details" class="text-xs mt-1" :class="selectedDateInfo.isBlocked ? 'text-red-600 dark:text-red-500' : 'text-green-600 dark:text-green-500'">
              {{ selectedDateInfo.details }}
            </p>
          </div>
        </div>

        <!-- Close Button -->
        <button
          @click="showCalendar = false"
          class="mt-4 w-full py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
        >
          Cerrar
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

interface Props {
  modelValue: string
  minDate?: string
  blocks?: Array<{
    startDate: string
    endDate: string
    reason: string
  }>
  offers?: Array<{
    startDate: string
    endDate: string
    discount: number
    discountType: string
    color: string
  }>
  activeDays?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  blocks: () => [],
  offers: () => [],
  activeDays: () => [0, 1, 2, 3, 4, 5, 6]
})

const emit = defineEmits(['update:modelValue', 'dateSelected'])

const showCalendar = ref(false)
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())
const selectedDateInfo = ref<any>(null)

const monthNames = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
]

const weekDays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const hasBlocks = computed(() => props.blocks && props.blocks.length > 0)
const hasOffers = computed(() => props.offers && props.offers.length > 0)

const formattedDate = computed(() => {
  if (!props.modelValue) return ''
  const date = new Date(props.modelValue)
  return date.toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const calendarDays = computed(() => {
  const days = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  const startPadding = firstDay.getDay()

  // Add padding days from previous month
  for (let i = startPadding - 1; i >= 0; i--) {
    const date = new Date(currentYear.value, currentMonth.value, -i)
    days.push({
      day: date.getDate(),
      date: date.toISOString().split('T')[0],
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isBlocked: false,
      hasOffer: false,
      offerColor: '',
      blockedReason: '',
      offerInfo: null
    })
  }

  // Add days of current month
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const minDateObj = props.minDate ? new Date(props.minDate) : today

  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(currentYear.value, currentMonth.value, day)
    const dateStr = date.toISOString().split('T')[0]

    days.push({
      day,
      date: dateStr,
      isCurrentMonth: true,
      isToday: isToday(date),
      isSelected: dateStr === props.modelValue,
      isBlocked: isDateBlocked(date),
      isPast: date < today,
      isBeforeMinDate: date < minDateObj,
      hasOffer: hasDateOffer(date),
      offerColor: getOfferColor(date),
      blockedReason: getBlockedReason(date),
      offerInfo: getOfferInfo(date),
      dayOfWeek: date.getDay()
    })
  }

  // Add padding days from next month
  const endPadding = 6 - lastDay.getDay()
  for (let i = 1; i <= endPadding; i++) {
    const date = new Date(currentYear.value, currentMonth.value + 1, i)
    days.push({
      day: i,
      date: date.toISOString().split('T')[0],
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isBlocked: false,
      hasOffer: false,
      offerColor: '',
      blockedReason: '',
      offerInfo: null
    })
  }

  return days
})

const isToday = (date: Date) => {
  const today = new Date()
  return date.toDateString() === today.toDateString()
}

const isDateBlocked = (date: Date) => {
  if (!props.blocks) return false

  // Check if day of week is not active
  const dayOfWeek = date.getDay()
  if (props.activeDays && !props.activeDays.includes(dayOfWeek)) {
    return true
  }

  // Check if date is in blocked ranges
  return props.blocks.some(block => {
    const start = new Date(block.startDate)
    const end = new Date(block.endDate)
    return date >= start && date <= end
  })
}

const getBlockedReason = (date: Date) => {
  if (!props.blocks) return ''

  // Check if day of week is not active
  const dayOfWeek = date.getDay()
  if (props.activeDays && !props.activeDays.includes(dayOfWeek)) {
    return 'Día no disponible'
  }

  const block = props.blocks.find(b => {
    const start = new Date(b.startDate)
    const end = new Date(b.endDate)
    return date >= start && date <= end
  })

  return block?.reason || ''
}

const hasDateOffer = (date: Date) => {
  if (!props.offers) return false

  return props.offers.some(offer => {
    const start = new Date(offer.startDate)
    const end = new Date(offer.endDate)
    return date >= start && date <= end
  })
}

const getOfferColor = (date: Date) => {
  if (!props.offers) return ''

  const offer = props.offers.find(o => {
    const start = new Date(o.startDate)
    const end = new Date(o.endDate)
    return date >= start && date <= end
  })

  return offer?.color || ''
}

const getOfferInfo = (date: Date) => {
  if (!props.offers) return null

  return props.offers.find(o => {
    const start = new Date(o.startDate)
    const end = new Date(o.endDate)
    return date >= start && date <= end
  })
}

const getDayClasses = (day: any) => {
  const classes = []

  if (!day.isCurrentMonth) {
    classes.push('text-slate-300 dark:text-slate-600 cursor-not-allowed')
  } else if (day.isPast || day.isBeforeMinDate) {
    classes.push('text-slate-300 dark:text-slate-600 cursor-not-allowed bg-slate-50 dark:bg-slate-900/50')
  } else if (day.isBlocked) {
    classes.push('bg-red-50 dark:bg-red-900/20 text-red-400 cursor-not-allowed line-through')
  } else if (day.isSelected) {
    classes.push('bg-primary text-white cursor-pointer')
  } else if (day.isToday) {
    classes.push('bg-primary/10 text-primary font-bold cursor-pointer')
  } else if (day.hasOffer) {
    classes.push('bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 cursor-pointer')
  } else {
    classes.push('hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer')
  }

  return classes.join(' ')
}

const toggleCalendar = () => {
  showCalendar.value = !showCalendar.value
}

const selectDate = (day: any) => {
  // Don't allow selection of non-current month days
  if (!day.isCurrentMonth) return

  // Don't allow selection of past dates
  if (day.isPast) return

  // Don't allow selection of dates before minDate
  if (day.isBeforeMinDate) return

  // Don't allow selection of blocked dates
  if (day.isBlocked) return

  emit('update:modelValue', day.date)
  emit('dateSelected', day)

  // Update selected date info
  if (day.hasOffer && day.offerInfo) {
    selectedDateInfo.value = {
      isBlocked: false,
      message: '¡Oferta disponible!',
      details: `${day.offerInfo.discount}${day.offerInfo.discountType === 'percentage' ? '%' : ' USD'} de descuento`
    }
  } else {
    selectedDateInfo.value = null
  }

  showCalendar.value = false
}

const previousMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

// Close calendar on click outside
const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.relative')) {
    showCalendar.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>