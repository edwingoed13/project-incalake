<template>
  <div class="flex flex-col gap-6">
    <!-- Require Availability toggle -->
    <UCard :ui="{ body: 'p-4' }">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <div class="size-9 rounded-lg bg-warning/10 flex items-center justify-center">
            <UIcon name="i-lucide-shield-check" class="size-5 text-warning" />
          </div>
          <div>
            <p class="text-sm font-bold">Requerir verificación de disponibilidad</p>
            <p class="text-[11px] text-muted">El cliente debe consultar si hay cupos antes de pagar.</p>
          </div>
        </div>
        <USwitch v-model="store.availability.requireAvailability" color="primary" />
      </div>
    </UCard>

    <!-- 2-column layout: forms (left) + live calendar (right) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
    <!-- Tabs -->
    <UCard class="lg:col-span-2" :ui="{ body: '!p-0' }">
      <div class="flex border-b border-default">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          :class="[
            'flex-1 py-3 px-4 flex items-center justify-center gap-2 text-sm font-bold transition-all border-b-2',
            activeTab === tab.id
              ? 'text-primary border-primary bg-primary/5'
              : 'text-muted border-transparent hover:text-default',
          ]"
          @click="activeTab = tab.id"
        >
          <UIcon :name="tab.icon" class="size-4" />
          <span class="hidden sm:inline">{{ tab.label }}</span>
        </button>
      </div>

      <div class="p-4 sm:p-5">
        <!-- Availability Tab -->
        <div v-if="activeTab === 'availability'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <UFormField label="Fecha desde">
              <UInput
                v-model="store.availability.start"
                type="date"
                :min="todayISO"
                icon="i-lucide-calendar"
                class="w-full"
              />
              <template v-if="store.availability.start && store.availability.start < todayISO" #help>
                <span class="text-warning flex items-center gap-1">
                  <UIcon name="i-lucide-triangle-alert" class="size-3" />
                  Fecha anterior a hoy. Se ignorará en el frontend.
                </span>
              </template>
            </UFormField>
            <UFormField label="Fecha hasta">
              <UInput
                v-model="store.availability.end"
                type="date"
                :min="store.availability.start || todayISO"
                icon="i-lucide-calendar"
                class="w-full"
              />
            </UFormField>
          </div>

          <!-- Active Days — compact pills -->
          <UFormField label="Días de la semana disponibles">
            <div class="flex flex-wrap gap-1">
              <button
                v-for="day in weekDays"
                :key="day.value"
                type="button"
                :class="[
                  'px-2.5 py-1 rounded-md border text-[11px] font-bold uppercase tracking-wider transition-colors',
                  store.availability.activeDays.includes(day.value)
                    ? 'border-primary bg-primary text-white'
                    : 'border-default text-muted hover:border-muted hover:text-default',
                ]"
                @click="toggleDay(day.value)"
              >
                {{ day.label }}
              </button>
              <button
                type="button"
                class="ml-auto px-2 py-1 rounded-md text-[10px] font-bold text-muted hover:text-primary transition-colors"
                :title="store.availability.activeDays.length === 7 ? 'Desmarcar todos' : 'Marcar todos los días'"
                @click="toggleAllDays"
              >
                {{ store.availability.activeDays.length === 7 ? 'Ninguno' : 'Todos' }}
              </button>
            </div>
          </UFormField>

          <!-- Holidays -->
          <UFormField label="Bloquear feriados nacionales">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
              <button
                v-for="holiday in holidays"
                :key="holiday.value"
                type="button"
                :class="[
                  'px-3 py-2 rounded-lg border-2 transition-all flex items-center gap-2 text-left',
                  store.availability.specialDays.includes(holiday.value)
                    ? 'border-error bg-error/5'
                    : 'border-default hover:border-muted',
                ]"
                @click="toggleSpecialDay(holiday.value)"
              >
                <UIcon
                  :name="holiday.icon"
                  class="size-4 shrink-0"
                  :class="store.availability.specialDays.includes(holiday.value) ? 'text-error' : 'text-muted'"
                />
                <div class="min-w-0">
                  <p class="text-xs font-bold truncate" :class="store.availability.specialDays.includes(holiday.value) ? 'text-error' : ''">
                    {{ holiday.label }}
                  </p>
                  <p class="text-[10px] text-muted truncate">{{ holiday.date }}</p>
                </div>
              </button>
            </div>
          </UFormField>

        </div>

        <!-- Blocks Tab -->
        <div v-if="activeTab === 'blocks'" class="space-y-4">
          <div class="p-4 rounded-lg border-2 border-dashed border-error/30 bg-error/5 space-y-3">
            <p class="text-xs font-black uppercase tracking-widest text-error flex items-center gap-1.5">
              <UIcon name="i-lucide-plus-circle" class="size-4" />
              Nuevo bloqueo
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <UFormField label="Desde">
                <UInput v-model="newBlock.startDate" type="date" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
              <UFormField label="Hasta">
                <UInput v-model="newBlock.endDate" type="date" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
            </div>
            <UFormField label="Motivo" hint="Opcional">
              <UTextarea
                v-model="newBlock.reason"
                :rows="2"
                placeholder="Mantenimiento, vacaciones, evento privado... (opcional)"
                class="w-full"
              />
            </UFormField>
            <UButton
              icon="i-lucide-plus"
              color="error"
              size="sm"
              :disabled="!newBlock.startDate || !newBlock.endDate"
              @click="addBlock"
            >
              Agregar bloqueo
            </UButton>
          </div>

          <!-- Blocks list -->
          <div v-if="store.availability.blocks && store.availability.blocks.length > 0" class="space-y-1.5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Bloqueos configurados ({{ store.availability.blocks.length }})</p>
            <div
              v-for="(block, index) in store.availability.blocks"
              :key="block.id || index"
              class="group flex items-center gap-2.5 px-3 py-2 rounded-lg border border-default hover:border-error/40 transition-all"
            >
              <UIcon name="i-lucide-ban" class="size-4 text-error shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate">{{ block.reason }}</p>
                <p class="text-[10px] text-muted">{{ formatDate(block.startDate) }} → {{ formatDate(block.endDate) }}</p>
              </div>
              <UButton
                icon="i-lucide-x"
                color="error"
                variant="ghost"
                size="xs"
                class="opacity-0 group-hover:opacity-100 transition-opacity"
                @click="removeBlock(index)"
              />
            </div>
          </div>
          <UAlert
            v-else
            color="neutral"
            variant="subtle"
            icon="i-lucide-calendar-x"
            title="Sin bloqueos"
            description="Agrega rangos de fechas en las que el tour no estará disponible."
          />
        </div>

        <!-- Offers Tab -->
        <div v-if="activeTab === 'offers'" class="space-y-4">
          <div class="p-4 rounded-lg border-2 border-dashed border-success/30 bg-success/5 space-y-3">
            <p class="text-xs font-black uppercase tracking-widest text-success flex items-center gap-1.5">
              <UIcon name="i-lucide-plus-circle" class="size-4" />
              Nueva oferta
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <UFormField label="Desde">
                <UInput v-model="newOffer.startDate" type="date" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
              <UFormField label="Hasta">
                <UInput v-model="newOffer.endDate" type="date" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <UFormField label="Descuento">
                <UInputNumber v-model="newOffer.discount" :min="1" class="w-full" />
              </UFormField>
              <UFormField label="Tipo">
                <USelectMenu
                  v-model="newOffer.discountType"
                  :items="discountTypeOptions"
                  value-key="value"
                  class="w-full"
                />
              </UFormField>
              <UFormField label="Color">
                <div class="flex gap-1.5">
                  <button
                    v-for="color in offerColors"
                    :key="color.value"
                    type="button"
                    class="size-9 rounded-lg border-2 transition-all flex items-center justify-center"
                    :style="{ backgroundColor: color.value }"
                    :class="newOffer.color === color.value ? 'ring-2 ring-offset-1 ring-default scale-105' : 'hover:scale-105'"
                    :title="color.label"
                    @click="newOffer.color = color.value"
                  >
                    <UIcon v-if="newOffer.color === color.value" name="i-lucide-check" class="size-3.5 text-white drop-shadow" />
                  </button>
                </div>
              </UFormField>
            </div>
            <UButton
              icon="i-lucide-plus"
              color="success"
              size="sm"
              :disabled="!newOffer.startDate || !newOffer.endDate || !newOffer.discount"
              @click="addOffer"
            >
              Agregar oferta
            </UButton>
          </div>

          <!-- Offers list -->
          <div v-if="store.availability.offers && store.availability.offers.length > 0" class="space-y-1.5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Ofertas configuradas ({{ store.availability.offers.length }})</p>
            <div
              v-for="(offer, index) in store.availability.offers"
              :key="offer.id || index"
              class="group flex items-center gap-2.5 px-3 py-2 rounded-lg border-l-4 border border-default hover:bg-elevated/40 transition-all"
              :style="{ borderLeftColor: offer.color }"
            >
              <UIcon name="i-lucide-percent" class="size-4 shrink-0" :style="{ color: offer.color }" />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold">
                  {{ offer.discount }}{{ offer.discountType === 'percentage' ? '%' : ' USD' }} de descuento
                </p>
                <p class="text-[10px] text-muted">{{ formatDate(offer.startDate) }} → {{ formatDate(offer.endDate) }}</p>
              </div>
              <UButton
                icon="i-lucide-x"
                color="error"
                variant="ghost"
                size="xs"
                class="opacity-0 group-hover:opacity-100 transition-opacity"
                @click="removeOffer(index)"
              />
            </div>
          </div>
          <UAlert
            v-else
            color="neutral"
            variant="subtle"
            icon="i-lucide-tag"
            title="Sin ofertas"
            description="Crea descuentos por rango de fechas. Puedes asignar un color para identificarlos en el calendario."
          />
        </div>
      </div>
    </UCard>

    <!-- Live Calendar (right column) -->
    <UCard class="lg:col-span-1 lg:sticky lg:top-4" :ui="{ body: 'p-3 space-y-3' }">
      <!-- Header with month navigation -->
      <div class="flex items-center justify-between gap-2">
        <UButton icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" @click="shiftMonth(-1)" />
        <button
          type="button"
          class="text-xs font-black uppercase tracking-wider hover:text-primary transition-colors"
          :title="'Volver al mes actual'"
          @click="goToToday"
        >
          {{ visibleMonthLabel }}
        </button>
        <UButton icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" @click="shiftMonth(1)" />
      </div>

      <!-- Week-day headers -->
      <div class="grid grid-cols-7 gap-0.5 text-center">
        <div
          v-for="d in weekdayHeaders"
          :key="d"
          class="text-[9px] font-black uppercase tracking-wider text-muted py-1"
        >
          {{ d }}
        </div>
      </div>

      <!-- Day grid -->
      <div class="grid grid-cols-7 gap-0.5">
        <div
          v-for="cell in calendarCells"
          :key="cell.key"
          :class="[
            'relative aspect-square rounded-md flex items-center justify-center text-[11px] font-bold border transition-colors',
            cell.outOfMonth ? 'border-transparent text-muted/40' : 'border-default',
            cell.inAvailability && cell.activeWeekday && !cell.blocked && !cell.outOfMonth ? 'bg-primary/10 border-primary/30 text-primary' : '',
            cell.blocked && !cell.outOfMonth ? 'bg-error/10 border-error/40 text-error line-through' : '',
            cell.isHoliday && !cell.outOfMonth ? 'ring-2 ring-error/40 ring-inset' : '',
            cell.isToday ? 'ring-2 ring-primary ring-inset font-black' : '',
            !cell.inAvailability && !cell.outOfMonth && !cell.blocked ? 'text-muted' : '',
          ]"
          :title="cell.tooltip"
        >
          <span>{{ cell.day }}</span>
          <!-- Offer dot -->
          <span
            v-if="cell.offerColor && !cell.outOfMonth"
            class="absolute bottom-0.5 left-1/2 -translate-x-1/2 size-1.5 rounded-full"
            :style="{ backgroundColor: cell.offerColor }"
          />
        </div>
      </div>

      <!-- Legend -->
      <div class="space-y-1 pt-2 border-t border-default text-[10px]">
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-sm bg-primary/20 border border-primary/40" />
          <span class="text-muted">Disponible</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-sm bg-error/10 border border-error/40" />
          <span class="text-muted">Bloqueado</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-full bg-success" />
          <span class="text-muted">Con oferta</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-sm ring-2 ring-primary ring-inset" />
          <span class="text-muted">Hoy</span>
        </div>
      </div>

      <!-- Compact summary -->
      <div
        v-if="store.availability.start && store.availability.end"
        class="rounded-lg bg-primary/5 border border-primary/20 px-2.5 py-2 text-[10px] leading-snug"
      >
        Disponible <span class="font-bold">{{ formatDate(store.availability.start) }} → {{ formatDate(store.availability.end) }}</span>
        · <span class="font-bold">{{ store.availability.activeDays.length }} días/semana</span>
        <span v-if="(store.availability.blocks || []).length"> · <span class="text-error font-bold">{{ store.availability.blocks.length }} bloqueos</span></span>
        <span v-if="(store.availability.offers || []).length"> · <span class="text-success font-bold">{{ store.availability.offers.length }} ofertas</span></span>
      </div>
    </UCard>
    </div>

    <!-- Save button -->
    <div class="flex items-center justify-end gap-3">
      <UBadge :color="hasAnyAvailability ? 'success' : 'warning'" variant="subtle" size="sm" :icon="hasAnyAvailability ? 'i-lucide-circle-check' : 'i-lucide-circle-dashed'">
        {{ hasAnyAvailability ? 'Configurado' : 'Sin configurar' }}
      </UBadge>
      <UButton
        icon="i-lucide-save"
        color="primary"
        :loading="saving"
        @click="saveAvailability"
      >
        {{ saving ? 'Guardando...' : 'Guardar disponibilidad' }}
      </UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { ref, reactive, computed } from 'vue'

const store = useTourWizardStore()
const toast = useToast()

const todayISO = new Date().toISOString().split('T')[0]
const activeTab = ref<'availability' | 'blocks' | 'offers'>('availability')
const saving = ref(false)

const tabs = [
  { id: 'availability', label: 'Disponibilidad', icon: 'i-lucide-calendar-check' },
  { id: 'blocks', label: 'Bloqueos', icon: 'i-lucide-calendar-x' },
  { id: 'offers', label: 'Ofertas', icon: 'i-lucide-tag' },
] as const

const weekDays = [
  { label: 'Lun', value: 1 },
  { label: 'Mar', value: 2 },
  { label: 'Mie', value: 3 },
  { label: 'Jue', value: 4 },
  { label: 'Vie', value: 5 },
  { label: 'Sab', value: 6 },
  { label: 'Dom', value: 0 },
]

const holidays = [
  { label: 'Navidad', value: '25-12', date: '25 Dic', icon: 'i-lucide-gift' },
  { label: 'Fin de Año', value: '31-12', date: '31 Dic', icon: 'i-lucide-sparkles' },
  { label: 'Año Nuevo', value: '01-01', date: '01 Ene', icon: 'i-lucide-party-popper' },
  { label: 'Fiestas Patrias', value: '28-07', date: '28 Jul', icon: 'i-lucide-flag' },
]

const discountTypeOptions = [
  { value: 'percentage', label: 'Porcentaje (%)' },
  { value: 'amount', label: 'Monto fijo (USD)' },
]

const offerColors = [
  { label: 'Azul', value: '#286090' },
  { label: 'Verde', value: '#449d44' },
  { label: 'Celeste', value: '#31b0d5' },
  { label: 'Naranja', value: '#f0ad4e' },
  { label: 'Rojo', value: '#d9534f' },
]

const newBlock = reactive({ startDate: '', endDate: '', reason: '' })
const newOffer = reactive({
  startDate: '',
  endDate: '',
  discount: null as number | null,
  discountType: 'percentage',
  color: '#449d44',
})

const hasAnyAvailability = computed(() =>
  !!(store.availability?.start && store.availability?.end && (store.availability?.activeDays || []).length > 0),
)

const addBlock = () => {
  if (!newBlock.startDate || !newBlock.endDate) return
  if (!store.availability.blocks) store.availability.blocks = []
  store.availability.blocks.push({
    id: crypto.randomUUID(),
    startDate: newBlock.startDate,
    endDate: newBlock.endDate,
    reason: newBlock.reason.trim() || 'Bloqueado',
  })
  newBlock.startDate = ''
  newBlock.endDate = ''
  newBlock.reason = ''
}

const removeBlock = (index: number) => {
  if (store.availability.blocks) store.availability.blocks.splice(index, 1)
}

const addOffer = () => {
  if (!newOffer.startDate || !newOffer.endDate || !newOffer.discount) return
  if (!store.availability.offers) store.availability.offers = []
  store.availability.offers.push({
    id: crypto.randomUUID(),
    startDate: newOffer.startDate,
    endDate: newOffer.endDate,
    discount: newOffer.discount,
    discountType: newOffer.discountType,
    color: newOffer.color,
  })
  newOffer.startDate = ''
  newOffer.endDate = ''
  newOffer.discount = null
  newOffer.discountType = 'percentage'
  newOffer.color = '#449d44'
}

const removeOffer = (index: number) => {
  if (store.availability.offers) store.availability.offers.splice(index, 1)
}

const toggleDay = (day: number) => {
  const idx = store.availability.activeDays.indexOf(day)
  if (idx === -1) store.availability.activeDays.push(day)
  else store.availability.activeDays.splice(idx, 1)
}

const toggleAllDays = () => {
  if (store.availability.activeDays.length === 7) {
    store.availability.activeDays.splice(0, store.availability.activeDays.length)
  } else {
    store.availability.activeDays.splice(0, store.availability.activeDays.length, 0, 1, 2, 3, 4, 5, 6)
  }
}

// === Live Calendar ===
const weekdayHeaders = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do']

const today = new Date()
today.setHours(0, 0, 0, 0)

// Visible month — anchor to today at first; user can navigate freely.
const visibleMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1))

const visibleMonthLabel = computed(() => {
  return visibleMonth.value.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
})

const shiftMonth = (delta: number) => {
  const d = new Date(visibleMonth.value)
  d.setMonth(d.getMonth() + delta)
  visibleMonth.value = d
}

const goToToday = () => {
  visibleMonth.value = new Date(today.getFullYear(), today.getMonth(), 1)
}

const isoDate = (d: Date) => {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const parseISO = (s: string) => {
  if (!s) return null
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y, m - 1, d)
}

const calendarCells = computed(() => {
  const first = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth(), 1)
  const last = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + 1, 0)

  // Find the Monday on or before the 1st (Mon=0 grid).
  const startOffset = (first.getDay() + 6) % 7
  const gridStart = new Date(first)
  gridStart.setDate(first.getDate() - startOffset)

  // 6 rows × 7 cols = 42 cells covers any month.
  const cells: any[] = []
  const availStart = parseISO(store.availability.start)
  const availEnd = parseISO(store.availability.end)
  const activeDays: number[] = store.availability.activeDays || []
  const specialDays: string[] = store.availability.specialDays || []
  const blocks = store.availability.blocks || []
  const offers = store.availability.offers || []

  for (let i = 0; i < 42; i++) {
    const d = new Date(gridStart)
    d.setDate(gridStart.getDate() + i)
    d.setHours(0, 0, 0, 0)

    const outOfMonth = d.getMonth() !== visibleMonth.value.getMonth()
    const iso = isoDate(d)
    const dow = d.getDay() // 0=Sun..6=Sat
    const mmdd = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

    const inAvailability = !!(availStart && availEnd && d >= availStart && d <= availEnd)
    const activeWeekday = activeDays.includes(dow)
    const isHoliday = specialDays.includes(mmdd)

    let blocked = false
    let blockReason = ''
    for (const b of blocks) {
      const bs = parseISO(b.startDate)
      const be = parseISO(b.endDate)
      if (bs && be && d >= bs && d <= be) {
        blocked = true
        blockReason = b.reason || 'Bloqueado'
        break
      }
    }

    let offerColor = ''
    let offerLabel = ''
    for (const o of offers) {
      const os = parseISO(o.startDate)
      const oe = parseISO(o.endDate)
      if (os && oe && d >= os && d <= oe) {
        offerColor = o.color || '#449d44'
        offerLabel = `${o.discount}${o.discountType === 'percentage' ? '%' : ' USD'} off`
        break
      }
    }

    const isToday = d.getTime() === today.getTime()

    const tooltipParts: string[] = []
    tooltipParts.push(d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' }))
    if (blocked) tooltipParts.push(`Bloqueo: ${blockReason}`)
    else if (!inAvailability) tooltipParts.push('Fuera del rango')
    else if (!activeWeekday) tooltipParts.push('Día semanal no activo')
    else tooltipParts.push('Disponible')
    if (isHoliday) tooltipParts.push('Feriado bloqueado')
    if (offerLabel) tooltipParts.push(`Oferta: ${offerLabel}`)

    cells.push({
      key: iso,
      day: d.getDate(),
      outOfMonth,
      inAvailability,
      activeWeekday,
      blocked: blocked || (isHoliday && inAvailability),
      isHoliday,
      offerColor,
      isToday,
      tooltip: tooltipParts.join(' · '),
    })
  }
  return cells
})

const toggleSpecialDay = (value: string) => {
  const idx = store.availability.specialDays.indexOf(value)
  if (idx === -1) store.availability.specialDays.push(value)
  else store.availability.specialDays.splice(idx, 1)
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  const [year, month, day] = dateStr.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

const saveAvailability = async () => {
  saving.value = true
  try {
    await store.saveCurrentProgress()
    toast.add({
      title: 'Disponibilidad guardada',
      description: 'Los cambios se sincronizaron correctamente.',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
  } catch (err: any) {
    toast.add({
      title: 'Error al guardar',
      description: err?.message || 'Intenta de nuevo.',
      icon: 'i-lucide-alert-triangle',
      color: 'error',
    })
  } finally {
    saving.value = false
  }
}
</script>
