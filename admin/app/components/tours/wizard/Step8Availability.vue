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

    <!-- Calendar + controls side by side from xl. It used to take 2xl, which
         left every ordinary laptop stacking them — and a stacked full-height
         preview meant the operator scrolled past the whole calendar before
         reaching the Bloqueos and Ofertas tabs, or never found them. -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 items-start">

    <!-- Live Calendar (LEFT) — 2-month preview -->
    <UCard class="xl:col-span-3 xl:sticky xl:top-4" :ui="{ body: 'p-4 space-y-4' }">
      <!-- Header: title + month navigation -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2">
          <div class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <UIcon name="i-lucide-calendar-days" class="size-5 text-primary" />
          </div>
          <div>
            <h3 class="text-base font-bold leading-tight">Vista previa del calendario</h3>
            <p class="text-[11px] text-muted">Refleja en vivo los bloqueos y ofertas que configuras</p>
          </div>
        </div>
        <div class="flex items-center gap-1">
          <UButton icon="i-lucide-chevron-left" color="neutral" variant="soft" size="sm" title="Mes anterior" @click="shiftMonth(-1)" />
          <UButton color="neutral" variant="soft" size="sm" @click="goToToday">Hoy</UButton>
          <UButton icon="i-lucide-chevron-right" color="neutral" variant="soft" size="sm" title="Mes siguiente" @click="shiftMonth(1)" />
        </div>
      </div>

      <!-- Two months side by side, but only where they genuinely fit. Stacked,
           the pair made the preview taller than the window on a 31-day month
           and pushed the Bloqueos/Ofertas tabs off-screen — so below 2xl the
           second month is dropped rather than stacked; the month arrows still
           reach it. -->
      <div class="grid grid-cols-1 2xl:grid-cols-2 gap-5">
        <div
          v-for="(month, mi) in months"
          :key="month.key"
          class="space-y-1.5"
          :class="mi === 1 ? 'hidden 2xl:block' : ''"
        >
          <p class="text-center text-xs font-black uppercase tracking-wider text-default capitalize">{{ month.label }}</p>
          <div class="grid grid-cols-7 gap-1 text-center">
            <div
              v-for="d in weekdayHeaders"
              :key="d"
              class="text-[10px] font-black uppercase tracking-wider text-muted py-1"
            >
              {{ d }}
            </div>
          </div>
          <div class="grid grid-cols-7 gap-1">
            <div
              v-for="cell in month.cells"
              :key="cell.key"
              :class="[
                'relative aspect-square rounded-lg flex items-center justify-center text-xs font-bold border transition-colors',
                cell.outOfMonth ? 'border-transparent text-muted/30' : 'border-default',
                // Bookable days carry the fill. /10 was invisible on a dark
                // background, so dark mode gets its own weight rather than the
                // same value on both grounds.
                cell.inAvailability && cell.activeWeekday && !cell.blocked && !cell.outOfMonth && !cell.isPast
                  ? 'bg-primary/15 dark:bg-primary/25 border-primary/50 text-primary' : '',
                cell.blocked && !cell.outOfMonth && !cell.isPast ? 'bg-error/10 border-error/40 text-error line-through' : '',
                cell.isToday ? 'ring-2 ring-primary ring-inset font-black' : '',
                // A weekday the tour does not run is as unbookable as a date
                // outside the range, and must not read as available.
                (!cell.inAvailability || !cell.activeWeekday || cell.isPast) && !cell.outOfMonth && (!cell.blocked || cell.isPast) ? 'text-muted' : '',
              ]"
              :title="cell.tooltip"
            >
              <span>{{ cell.day }}</span>
              <span
                v-if="cell.offerColor && !cell.outOfMonth"
                class="absolute bottom-1 left-1/2 -translate-x-1/2 size-1.5 rounded-full"
                :style="{ backgroundColor: cell.offerColor }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 pt-3 border-t border-default text-[11px]">
        <div class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-primary/15 dark:bg-primary/25 border border-primary/50" /><span class="text-muted">Disponible</span></div>
        <div class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-error/10 border border-error/40" /><span class="text-muted">Cerrado</span></div>
        <div class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-success" /><span class="text-muted">Con oferta</span></div>
        <div class="flex items-center gap-1.5"><span class="size-3 rounded-sm ring-2 ring-primary ring-inset" /><span class="text-muted">Hoy</span></div>
      </div>

      <!-- Compact summary -->
      <div
        v-if="store.availability.start && (store.availability.neverExpires || store.availability.end)"
        class="rounded-lg bg-primary/5 border border-primary/20 px-3 py-2 text-[11px] leading-snug"
      >
        Disponible
        <span v-if="store.availability.neverExpires" class="font-bold">desde {{ formatDate(store.availability.start) }} · sin caducidad</span>
        <span v-else class="font-bold">{{ formatDate(store.availability.start) }} → {{ formatDate(store.availability.end) }}</span>
        · <span class="font-bold">{{ store.availability.activeDays.length }} días/semana</span>
        <span v-if="(store.availability.blocks || []).length"> · <span class="text-error font-bold">{{ store.availability.blocks.length }} bloqueos</span></span>
        <span v-if="(store.availability.offers || []).length"> · <span class="text-success font-bold">{{ store.availability.offers.length }} ofertas</span></span>
      </div>
    </UCard>

    <!-- Controls (RIGHT) -->
    <UCard class="xl:col-span-2" :ui="{ body: '!p-0' }">
      <div class="flex border-b border-default">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          :class="[
            'flex-1 min-w-0 py-3 px-2 flex items-center justify-center gap-1.5 text-sm font-bold transition-all border-b-2 whitespace-nowrap',
            activeTab === tab.id
              ? 'text-primary border-primary bg-primary/5'
              : 'text-muted border-transparent hover:text-default',
          ]"
          @click="activeTab = tab.id"
        >
          <UIcon :name="tab.icon" class="size-4 shrink-0" />
          <span class="truncate">{{ tab.label }}</span>
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
                :disabled="store.availability.neverExpires"
              />
              <template v-if="store.availability.neverExpires" #help>
                <span class="text-muted">Sin fecha final: el tour no caduca.</span>
              </template>
            </UFormField>
          </div>

          <!-- Every new tour gets an automatic end date of today + 1 year, and
               nobody revisits it — which is how 133 of 290 tours ended up past
               their date, some since 2019. This is the way out for tours that
               simply run all year. -->
          <UCheckbox
            v-model="store.availability.neverExpires"
            label="Este tour no caduca"
            :ui="{ label: 'font-bold' }"
            @update:model-value="onNeverExpiresChange"
          />
          <p class="text-[11px] text-muted -mt-2">
            Sin fecha final el calendario no se cierra nunca, y el tour queda fuera de los
            avisos de caducidad que se envían a reservas@incalake.com.
          </p>

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

        </div>

        <!-- Blocks Tab -->
        <!-- Feriados y bloqueos vivian en pestanas distintas y se pintaban
             distinto, pero para el viajero son la misma cosa: el dia no se
             puede reservar. Lo unico que de verdad los separa es que un feriado
             se repite cada ano y un bloqueo ocurre una vez, y eso se dice en la
             propia fila. Una sola pestana responde "que dias esta cerrado". -->
        <div v-if="activeTab === 'blocks'" class="space-y-4">
          <UFormField label="Feriados" hint="Se repiten cada año">
            <div class="grid grid-cols-2 gap-2">
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

          <div class="p-4 rounded-lg border-2 border-dashed border-error/30 bg-error/5 space-y-3">
            <p class="text-xs font-black uppercase tracking-widest text-error flex items-center gap-1.5">
              <UIcon name="i-lucide-plus-circle" class="size-4" />
              Nuevo bloqueo
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <UFormField label="Desde">
                <UInput v-model="newBlock.startDate" type="date" :min="todayISO" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
              <UFormField label="Hasta">
                <UInput v-model="newBlock.endDate" type="date" :min="newBlock.startDate || todayISO" icon="i-lucide-calendar" class="w-full" />
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

          <!-- One list for both. They were two, in two tabs, and answering
               "what days is this tour closed?" meant looking in two places. -->
          <div v-if="diasCerrados.length" class="space-y-1.5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Días cerrados ({{ diasCerrados.length }})</p>
            <div
              v-for="dia in diasCerrados"
              :key="dia.clave"
              class="group flex items-center gap-2.5 px-3 py-2 rounded-lg border border-default hover:border-error/40 transition-all"
            >
              <UIcon :name="dia.icono" class="size-4 text-error shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate">{{ dia.titulo }}</p>
                <p class="text-[10px] text-muted truncate">{{ dia.detalle }}</p>
              </div>
              <UButton
                icon="i-lucide-x"
                color="error"
                variant="ghost"
                size="xs"
                class="opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 transition-opacity"
                @click="quitarDiaCerrado(dia)"
              />
            </div>
          </div>
          <UAlert
            v-else
            color="neutral"
            variant="subtle"
            icon="i-lucide-calendar-x"
            title="Sin días cerrados"
            description="Marca un feriado arriba o agrega un rango de fechas en el que el tour no estará disponible."
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
                <UInput v-model="newOffer.startDate" type="date" :min="todayISO" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
              <UFormField label="Hasta">
                <UInput v-model="newOffer.endDate" type="date" :min="newOffer.startDate || todayISO" icon="i-lucide-calendar" class="w-full" />
              </UFormField>
            </div>
            <div class="grid grid-cols-2 gap-2">
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
            </div>
            <UFormField label="Color en el calendario">
              <div class="flex items-center gap-2.5 pt-1">
                <button
                  v-for="color in offerColors"
                  :key="color.value"
                  type="button"
                  class="size-8 rounded-full flex items-center justify-center transition-transform hover:scale-110 outline-2 outline-offset-2 outline-transparent"
                  :style="{ backgroundColor: color.value }"
                  :class="newOffer.color === color.value ? 'outline-gray-400 dark:outline-gray-500 scale-110' : ''"
                  :title="color.label"
                  @click="newOffer.color = color.value"
                >
                  <UIcon v-if="newOffer.color === color.value" name="i-lucide-check" class="size-4 text-white drop-shadow-sm" />
                </button>
              </div>
            </UFormField>
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
                class="opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 transition-opacity"
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
        <!-- On a live tour this only parks a draft, so it must not promise a
             save that reaches travellers. -->
        {{ saving ? 'Guardando...' : (store.isLiveTour() ? 'Guardar en borrador' : 'Guardar disponibilidad') }}
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

// Remember the date while "no caduca" is on, so unticking it doesn't leave the
// field blank and force the operator to type it again from memory.
const lastEndDate = ref<string>('')

const onNeverExpiresChange = (value: boolean) => {
  if (value) {
    if (store.availability.end) lastEndDate.value = store.availability.end
    store.availability.end = ''
  } else if (!store.availability.end) {
    store.availability.end = lastEndDate.value
      || new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]
  }
  store.isDirty = true
}
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

/**
 * Feriados y bloqueos, en una sola lista.
 *
 * Son dos formas de decir lo mismo — el dia no se puede reservar — guardadas
 * distinto: el feriado es un DD-MM que vuelve cada ano, el bloqueo un rango con
 * fecha de inicio y fin. Esa diferencia si importa y por eso se conserva en los
 * datos; lo que no tenia sentido era obligar a mirar en dos pestanas para saber
 * que dias esta cerrado el tour.
 */
const diasCerrados = computed(() => {
  const feriados = (store.availability.specialDays || []).map((valor: string) => {
    const f = holidays.find(h => h.value === valor)
    return {
      clave: `feriado-${valor}`,
      tipo: 'feriado' as const,
      valor,
      icono: f?.icon || 'i-lucide-calendar-heart',
      titulo: f?.label || valor,
      detalle: `${f?.date || valor} · cada año`,
    }
  })

  const rangos = (store.availability.blocks || []).map((b: any, i: number) => ({
    clave: `bloqueo-${b.id || i}`,
    tipo: 'bloqueo' as const,
    indice: i,
    icono: 'i-lucide-ban',
    titulo: b.reason || 'Bloqueado',
    detalle: `${formatDate(b.startDate)} → ${formatDate(b.endDate)}`,
  }))

  return [...feriados, ...rangos]
})

/** El nombre del feriado para el tooltip; si no esta en la lista, su fecha. */
function nombreFeriado(ddmm: string): string {
  return holidays.find(h => h.value === ddmm)?.label || `feriado ${ddmm}`
}

function quitarDiaCerrado(dia: any) {
  if (dia.tipo === 'feriado') toggleSpecialDay(dia.valor)
  else removeBlock(dia.indice)
}

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

// A tour that never expires has no end date on purpose, so requiring one here
// left the step reading "Sin configurar" forever however carefully it was set up.
const hasAnyAvailability = computed(() =>
  !!(
    store.availability?.start
    && (store.availability?.neverExpires || store.availability?.end)
    && (store.availability?.activeDays || []).length > 0
  ),
)

const addBlock = () => {
  if (!newBlock.startDate || !newBlock.endDate) return
  if (newBlock.startDate < todayISO) {
    toast.add({ title: 'Fecha inválida', description: 'El bloqueo no puede empezar antes de hoy.', color: 'warning', icon: 'i-lucide-triangle-alert' })
    return
  }
  if (newBlock.endDate < newBlock.startDate) {
    toast.add({ title: 'Rango inválido', description: 'La fecha "hasta" no puede ser anterior a "desde".', color: 'warning', icon: 'i-lucide-triangle-alert' })
    return
  }
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
  if (newOffer.startDate < todayISO) {
    toast.add({ title: 'Fecha inválida', description: 'La oferta no puede empezar antes de hoy.', color: 'warning', icon: 'i-lucide-triangle-alert' })
    return
  }
  if (newOffer.endDate < newOffer.startDate) {
    toast.add({ title: 'Rango inválido', description: 'La fecha "hasta" no puede ser anterior a "desde".', color: 'warning', icon: 'i-lucide-triangle-alert' })
    return
  }
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

// Build the 42-cell (6×7) grid for a given month, with per-day availability state.
const buildCells = (base: Date) => {
  const first = new Date(base.getFullYear(), base.getMonth(), 1)

  // Find the Monday on or before the 1st (Mon=0 grid).
  const startOffset = (first.getDay() + 6) % 7
  const gridStart = new Date(first)
  gridStart.setDate(first.getDate() - startOffset)

  // 6 rows × 7 cols = 42 cells covers any month.
  const cells: any[] = []
  const availStart = parseISO(store.availability.start)
  // "Este tour no caduca" stores end as '', which parsed to null and made
  // every single day fall outside the range — so the preview painted the whole
  // calendar as unavailable and the "Disponible" key in the legend pointed at
  // a colour that never appeared. No end date means no upper bound, not an
  // empty range.
  const neverExpires = !!store.availability.neverExpires
  const availEnd = neverExpires ? null : parseISO(store.availability.end)
  const activeDays: number[] = store.availability.activeDays || []
  const specialDays: string[] = store.availability.specialDays || []
  const blocks = store.availability.blocks || []
  const offers = store.availability.offers || []

  for (let i = 0; i < 42; i++) {
    const d = new Date(gridStart)
    d.setDate(gridStart.getDate() + i)
    d.setHours(0, 0, 0, 0)

    const outOfMonth = d.getMonth() !== base.getMonth()
    const iso = isoDate(d)
    const dow = d.getDay() // 0=Sun..6=Sat
    // DD-MM, the same shape the holiday buttons store ('25-12'), the public
    // calendar compares and the server validates. This used to be built as
    // MM-DD, so the preview never painted a holiday and operators reasonably
    // concluded the whole feature was broken — while the real blocking worked.
    const mmdd = `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}`

    const inAvailability = !!(availStart && d >= availStart && (neverExpires || (availEnd && d <= availEnd)))
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
    // Yesterday is not bookable no matter what the range says. The public
    // calendar has always disabled past dates, so painting them as available
    // here made the preview disagree with the very thing it previews — and on
    // the 22nd it showed three weeks of August as bookable.
    const isPast = d.getTime() < today.getTime()

    const tooltipParts: string[] = []
    tooltipParts.push(d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' }))
    if (isPast) tooltipParts.push('Fecha pasada')
    else if (blocked) tooltipParts.push(`Cerrado: ${blockReason}`)
    else if (isHoliday) tooltipParts.push(`Cerrado: ${nombreFeriado(mmdd)}`)
    else if (!inAvailability) tooltipParts.push('Fuera del rango')
    else if (!activeWeekday) tooltipParts.push('Día semanal no activo')
    else tooltipParts.push('Disponible')
    if (offerLabel) tooltipParts.push(`Oferta: ${offerLabel}`)

    cells.push({
      key: iso,
      day: d.getDate(),
      outOfMonth,
      inAvailability,
      activeWeekday,
      isPast,
      blocked: blocked || (isHoliday && inAvailability),
      isHoliday,
      offerColor,
      isToday,
      tooltip: tooltipParts.join(' · '),
    })
  }
  return cells
}

// Two consecutive months anchored at the visible month.
const months = computed(() => {
  const a = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth(), 1)
  const b = new Date(a.getFullYear(), a.getMonth() + 1, 1)
  const fmt = (d: Date) => d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
  return [
    { key: isoDate(a), label: fmt(a), cells: buildCells(a) },
    { key: isoDate(b), label: fmt(b), cells: buildCells(b) },
  ]
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
    // saveWork routes to the draft buffer when the tour is already published,
    // so this button can't push availability changes live on its own.
    await store.saveWork()
    toast.add({
      title: store.isLiveTour() ? 'Guardado en borrador' : 'Disponibilidad guardada',
      description: store.isLiveTour()
        ? 'Se aplicará al sitio público cuando publiques los cambios.'
        : 'Los cambios se sincronizaron correctamente.',
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
