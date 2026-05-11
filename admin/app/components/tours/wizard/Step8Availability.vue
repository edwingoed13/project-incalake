<template>
  <div class="flex flex-col gap-4 pb-20">
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

    <!-- Tabs -->
    <UCard :ui="{ body: '!p-0' }">
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

          <!-- Active Days -->
          <UFormField label="Días de la semana disponibles">
            <div class="grid grid-cols-7 gap-1.5">
              <button
                v-for="day in weekDays"
                :key="day.value"
                type="button"
                :class="[
                  'py-2 px-1 rounded-lg border-2 transition-all flex flex-col items-center gap-0.5',
                  store.availability.activeDays.includes(day.value)
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-default text-muted hover:border-muted',
                ]"
                @click="toggleDay(day.value)"
              >
                <span class="text-[11px] font-black uppercase tracking-wider">{{ day.label }}</span>
                <UIcon
                  :name="store.availability.activeDays.includes(day.value) ? 'i-lucide-circle-check' : 'i-lucide-circle'"
                  class="size-3.5"
                />
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

          <UAlert
            v-if="store.availability.start && store.availability.end"
            color="primary"
            variant="subtle"
            icon="i-lucide-info"
            :description="`Disponible del ${formatDate(store.availability.start)} al ${formatDate(store.availability.end)} · ${store.availability.activeDays.length} días/semana${store.availability.specialDays.length ? ` · ${store.availability.specialDays.length} feriados bloqueados` : ''}.`"
          />
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
            <UFormField label="Motivo">
              <UTextarea
                v-model="newBlock.reason"
                :rows="2"
                placeholder="Mantenimiento, vacaciones, evento privado..."
                class="w-full"
              />
            </UFormField>
            <UButton
              icon="i-lucide-plus"
              color="error"
              size="sm"
              :disabled="!newBlock.startDate || !newBlock.endDate || !newBlock.reason"
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
  if (!newBlock.startDate || !newBlock.endDate || !newBlock.reason) return
  if (!store.availability.blocks) store.availability.blocks = []
  store.availability.blocks.push({
    id: crypto.randomUUID(),
    startDate: newBlock.startDate,
    endDate: newBlock.endDate,
    reason: newBlock.reason,
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
