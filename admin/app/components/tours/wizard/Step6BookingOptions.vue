<template>
  <div class="flex flex-col gap-6">
    <!-- Opciones de reserva · secciones colapsables -->
    <!-- Language selector -->
    <UCard :ui="{ body: 'p-3 sm:p-3' }">
      <div class="flex items-center gap-3">
        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center">
          <UIcon name="i-lucide-languages" class="size-4 text-primary" />
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Editando opciones de reserva en</p>
          <div class="flex items-center gap-1 mt-1">
            <UButton
              v-for="lang in tourLanguages"
              :key="lang"
              size="xs"
              :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
              :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
              class="uppercase font-black tracking-wider"
              @click="store.currentLanguage = lang"
            >
              {{ lang }}
            </UButton>
          </div>
        </div>
      </div>
    </UCard>

    <!-- 1. Políticas y Cancelaciones -->
    <WizardSection
      collapsible
      title="Políticas y cancelaciones"
      icon="i-lucide-shield-check"
      :open="isSectionExpanded('policies')"
      @update:open="toggleSection('policies')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs" class="capitalize">{{ store.bookingOptions.policyType || 'standard' }}</UBadge>
      </template>

      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <button
            v-for="type in policyTypes"
            :key="type.id"
            type="button"
            :class="[
              'p-4 rounded-xl border-2 text-left transition-all flex items-center gap-3',
              store.bookingOptions.policyType === type.id
                ? 'border-primary bg-primary/5 shadow-md shadow-primary/10'
                : 'border-default hover:border-muted',
            ]"
            @click="store.bookingOptions.policyType = type.id"
          >
            <div :class="['size-5 rounded-full border-2 flex items-center justify-center shrink-0', store.bookingOptions.policyType === type.id ? 'border-primary bg-primary' : 'border-default']">
              <div v-if="store.bookingOptions.policyType === type.id" class="size-2 bg-white rounded-full" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold" :class="store.bookingOptions.policyType === type.id ? 'text-primary' : ''">{{ type.name }}</span>
              <span class="text-[11px] text-muted">{{ type.description }}</span>
            </div>
          </button>
        </div>

        <UFormField
          :label="store.bookingOptions.policyType === 'standard' ? 'Políticas pre-establecidas (editables)' : 'Descripción personalizada'"
        >
          <div class="rounded-lg overflow-hidden">
            <TiptapEditor
              v-if="store.bookingOptions.policyType === 'standard'"
              :modelValue="currentBookingTexts.policyDescription || ''"
              placeholder="Escribe las políticas estándar aquí..."
              :key="'policy-std-' + store.currentLanguage"
              @update:modelValue="(v: string) => { const seo = store.contentSEO[store.currentLanguage]; if (seo?.bookingTexts) seo.bookingTexts.policyDescription = v; store.bookingOptions.policyDescription = v }"
            />
            <TiptapEditor
              v-else
              :modelValue="currentBookingTexts.policyDescriptionCustom || ''"
              placeholder="Escribe las políticas personalizadas para esta actividad..."
              :key="'policy-custom-' + store.currentLanguage"
              @update:modelValue="(v: string) => { const seo = store.contentSEO[store.currentLanguage]; if (seo?.bookingTexts) seo.bookingTexts.policyDescriptionCustom = v; store.bookingOptions.policyDescriptionCustom = v }"
            />
          </div>
        </UFormField>

        <UAlert
          v-if="store.bookingOptions.policyType === 'standard'"
          color="info"
          variant="subtle"
          icon="i-lucide-info"
          description="Estas son las políticas estándar de Inca Lake. Puedes modificarlas si esta actividad lo requiere."
        />
      </div>
    </WizardSection>

    <!-- 2. Tiempo de Anticipación -->
    <WizardSection
      collapsible
      title="Tiempo de anticipación"
      icon="i-lucide-clock"
      :open="isSectionExpanded('anticipation')"
      @update:open="toggleSection('anticipation')"
    >
      <template #actions>
        <UBadge color="warning" variant="subtle" size="xs">{{ anticipationSummary }}</UBadge>
      </template>

      <div class="space-y-4">
        <div class="grid grid-cols-3 gap-3">
          <UFormField label="Días" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationDays" :min="0" :max="30" class="w-full" />
          </UFormField>
          <UFormField label="Horas" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationHours" :min="0" :max="23" class="w-full" />
          </UFormField>
          <UFormField label="Minutos" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationMinutes" :min="0" :max="59" :step="1" class="w-full" />
          </UFormField>
        </div>

        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-lightbulb"
          :title="`Anticipación: ${anticipationSummary}`"
          description="Combina días, horas y minutos. Ejemplo: 2 horas 30 minutos significa que los clientes deben reservar al menos 2h 30m antes del inicio del tour."
        />
      </div>
    </WizardSection>

    <!-- 3 & 4. Datos Requeridos -->
    <WizardSection
      collapsible
      title="Datos requeridos del cliente"
      icon="i-lucide-user-plus"
      :open="isSectionExpanded('data')"
      @update:open="toggleSection('data')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs">
          {{ store.bookingOptions.dataRequirementType === 'all' ? 'Todos' : 'Solo líder' }} · {{ (store.bookingOptions.personalInfoRequired?.length || 0) + (store.bookingOptions.operationalInfoRequired?.length || 0) }} campos
        </UBadge>
      </template>

      <div class="space-y-4">
      <div class="flex bg-elevated rounded-lg p-1 border border-default w-fit">
        <button
          type="button"
          :class="[
            'px-4 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-all',
            store.bookingOptions.dataRequirementType === 'leader' ? 'bg-default text-primary shadow-sm' : 'text-muted',
          ]"
          @click="store.bookingOptions.dataRequirementType = 'leader'"
        >
          Solo líder
        </button>
        <button
          type="button"
          :class="[
            'px-4 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-all',
            store.bookingOptions.dataRequirementType === 'all' ? 'bg-default text-primary shadow-sm' : 'text-muted',
          ]"
          @click="store.bookingOptions.dataRequirementType = 'all'"
        >
          Todos los pasajeros
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Personal Info -->
        <div class="space-y-2">
          <div class="flex items-center justify-between pb-1.5 border-b border-default">
            <p class="text-[11px] font-black uppercase tracking-widest text-muted">Información personal</p>
            <span class="text-[9px] text-muted italic">datos básicos</span>
          </div>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="(label, key) in personalFields"
              :key="key"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.personalInfoRequired, key)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.personalInfoRequired, key, v)"
              />
              <span class="text-xs font-medium select-none">{{ label }}</span>
            </label>
          </div>
        </div>

        <!-- Operational Info -->
        <div class="space-y-2">
          <div class="flex items-center justify-between pb-1.5 border-b border-default">
            <p class="text-[11px] font-black uppercase tracking-widest text-muted">Información operacional</p>
            <span class="text-[9px] text-muted italic">datos específicos</span>
          </div>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="(label, key) in operationalFields"
              :key="key"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.operationalInfoRequired, key)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.operationalInfoRequired, key, v)"
              />
              <span class="text-xs font-medium select-none">{{ label }}</span>
            </label>
          </div>
        </div>
      </div>
      </div>
    </WizardSection>

    <!-- 5. Opciones de Recojo -->
    <WizardSection
      collapsible
      title="Opciones de recojo"
      icon="i-lucide-map-pin"
      :open="isSectionExpanded('pickup')"
      @update:open="toggleSection('pickup')"
    >
      <template #actions>
        <UBadge
          :color="store.bookingOptions.enableMeetingPoint || store.bookingOptions.enableHotelPickup ? 'success' : 'error'"
          variant="subtle"
          size="xs"
        >
          {{ [store.bookingOptions.enableMeetingPoint && 'Encuentro', store.bookingOptions.enableHotelPickup && 'Hotel'].filter(Boolean).join(' + ') || 'Sin configurar' }}
        </UBadge>
      </template>

      <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <!-- Meeting Points (multi) -->
        <div
          :class="[
            'rounded-lg border-2 transition-all',
            store.bookingOptions.enableMeetingPoint ? 'border-primary bg-primary/5' : 'border-default',
          ]"
        >
          <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
            <UCheckbox v-model="store.bookingOptions.enableMeetingPoint" color="primary" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold">
                Puntos de encuentro
                <UBadge
                  v-if="store.bookingOptions.meetingPoints.length > 0"
                  color="primary"
                  variant="subtle"
                  size="xs"
                  class="ml-1"
                >
                  {{ store.bookingOptions.meetingPoints.length }}
                </UBadge>
              </p>
              <p class="text-[11px] text-muted">El cliente puede elegir entre uno o varios lugares de encuentro</p>
            </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableMeetingPoint" class="px-3 pb-3 pt-2 border-t border-default space-y-2">
              <!-- Empty state -->
              <div
                v-if="store.bookingOptions.meetingPoints.length === 0"
                class="rounded-lg border-2 border-dashed border-default p-4 text-center"
              >
                <UIcon name="i-lucide-map-pin-off" class="size-6 text-muted mx-auto mb-1.5" />
                <p class="text-xs text-muted mb-2">Aún no hay puntos de encuentro</p>
                <UButton icon="i-lucide-plus" color="primary" size="xs" @click="addMeetingPoint">
                  Agregar primer punto
                </UButton>
              </div>

              <!-- Points list -->
              <div
                v-for="(point, idx) in store.bookingOptions.meetingPoints"
                :key="point.id"
                class="rounded-lg border border-default bg-default p-2.5 space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <UBadge color="primary" variant="solid" size="xs" class="font-mono shrink-0">#{{ idx + 1 }}</UBadge>
                    <p v-if="point.lat != null && point.lng != null" class="text-[10px] font-mono text-muted truncate">
                      {{ point.lat.toFixed(5) }}, {{ point.lng.toFixed(5) }}
                    </p>
                    <p v-else class="text-[10px] italic text-muted">Sin coordenadas</p>
                  </div>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <UButton
                      icon="i-lucide-arrow-up"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :disabled="idx === 0"
                      title="Subir"
                      @click="moveMeetingPoint(idx, -1)"
                    />
                    <UButton
                      icon="i-lucide-arrow-down"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :disabled="idx === store.bookingOptions.meetingPoints.length - 1"
                      title="Bajar"
                      @click="moveMeetingPoint(idx, 1)"
                    />
                    <UButton
                      icon="i-lucide-trash-2"
                      color="error"
                      variant="ghost"
                      size="xs"
                      title="Eliminar este punto"
                      @click="removeMeetingPoint(idx)"
                    />
                  </div>
                </div>

                <UTextarea
                  :model-value="point.descriptions[store.currentLanguage] || ''"
                  :placeholder="`Descripción en ${store.currentLanguage.toUpperCase()} (ej: Plaza de Armas de Puno)...`"
                  :rows="2"
                  class="w-full"
                  @update:model-value="(v: string) => updatePointDescription(idx, v)"
                />

                <div class="flex items-center gap-2">
                  <UButton
                    icon="i-lucide-map-pin"
                    color="neutral"
                    variant="solid"
                    size="xs"
                    class="flex-1"
                    @click="openMeetingPointModal(idx)"
                  >
                    {{ point.lat != null ? 'Editar en el mapa' : 'Marcar en el mapa' }}
                  </UButton>
                  <UIcon
                    v-if="point.lat != null && point.lng != null"
                    name="i-lucide-circle-check"
                    class="size-4 text-success"
                    :title="`Lat ${point.lat.toFixed(5)}, Lng ${point.lng.toFixed(5)}`"
                  />
                </div>
              </div>

              <!-- Add another -->
              <UButton
                v-if="store.bookingOptions.meetingPoints.length > 0"
                icon="i-lucide-plus"
                color="primary"
                variant="soft"
                size="sm"
                block
                @click="addMeetingPoint"
              >
                Agregar otro punto de encuentro
              </UButton>
            </div>
          </Transition>
        </div>

        <!-- Hotel Pickup -->
        <div
          :class="[
            'rounded-lg border-2 transition-all',
            store.bookingOptions.enableHotelPickup ? 'border-primary bg-primary/5' : 'border-default',
          ]"
        >
          <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
            <UCheckbox v-model="store.bookingOptions.enableHotelPickup" color="primary" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold">Recojo en hotel</p>
              <p class="text-[11px] text-muted">Recojo en hoteles dentro de un radio</p>
            </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableHotelPickup" class="px-3 pb-3 pt-2 border-t border-default space-y-2">
              <UTextarea
                v-model="currentBookingTexts.pickupLocationDescription"
                placeholder="Ej: Hoteles del centro y alrededores..."
                :rows="2"
                class="w-full"
              />
              <div class="grid grid-cols-[1fr_2fr] gap-2 items-end">
                <UFormField label="Radio (km)" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
                  <UInputNumber v-model="store.bookingOptions.pickupRadiusKm" :min="1" :max="100" class="w-full" />
                </UFormField>
                <UButton
                  icon="i-lucide-target"
                  color="neutral"
                  size="sm"
                  block
                  @click="openPickupModal('hotel_pickup')"
                >
                  Configurar radio
                </UButton>
              </div>
              <UAlert
                v-if="store.bookingOptions.pickupCenterLat && store.bookingOptions.pickupCenterLng"
                color="success"
                variant="subtle"
                icon="i-lucide-circle-check"
                :description="`Radio de ${store.bookingOptions.pickupRadiusKm}km configurado`"
              />
              <UTextarea
                v-model="currentBookingTexts.dropoffLocationDescription"
                placeholder="Punto de retorno (opcional)..."
                :rows="2"
                class="w-full"
              />
            </div>
          </Transition>
        </div>
      </div>

      <UAlert
        v-if="!store.bookingOptions.enableMeetingPoint && !store.bookingOptions.enableHotelPickup"
        color="error"
        variant="subtle"
        icon="i-lucide-triangle-alert"
        title="Alerta de seguridad"
        description="Debes habilitar al menos una opción de recojo para que el tour sea reservable."
      />
      </div>
    </WizardSection>

    <!-- 6. Asociar Guías -->
    <WizardSection
      collapsible
      title="Configuración de guía"
      icon="i-lucide-megaphone"
      :open="isSectionExpanded('guide')"
      @update:open="toggleSection('guide')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs">
          {{ guideTypes.find(g => g.id === store.bookingOptions.guideType)?.name || 'Sin definir' }}
          <template v-if="store.bookingOptions.guideType === 'live_guide' && store.bookingOptions.guideLanguages?.length">
            · {{ store.bookingOptions.guideLanguages.length }} idiomas
          </template>
        </UBadge>
      </template>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="space-y-2">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Tipo de acompañante</p>
          <div class="space-y-1.5">
            <label
              v-for="guide in guideTypes"
              :key="guide.id"
              :class="[
                'flex items-center gap-2.5 px-3 py-2 rounded-lg border-2 transition-all cursor-pointer',
                store.bookingOptions.guideType === guide.id
                  ? 'border-primary bg-primary/5'
                  : 'border-default hover:border-muted',
              ]"
            >
              <input type="radio" v-model="store.bookingOptions.guideType" :value="guide.id" class="hidden" />
              <div :class="['size-4 rounded-full border-2 flex items-center justify-center shrink-0', store.bookingOptions.guideType === guide.id ? 'border-primary' : 'border-default']">
                <div v-if="store.bookingOptions.guideType === guide.id" class="size-2 bg-primary rounded-full" />
              </div>
              <span class="text-sm font-medium" :class="store.bookingOptions.guideType === guide.id ? 'text-primary' : ''">{{ guide.name }}</span>
            </label>
          </div>
        </div>

        <div v-if="store.bookingOptions.guideType === 'live_guide'" class="space-y-2">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Idiomas disponibles</p>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="lang in guideLanguages"
              :key="lang.id"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.guideLanguages, lang.id)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.guideLanguages, lang.id, v)"
              />
              <span class="text-xs font-medium select-none">{{ lang.name }}</span>
            </label>
          </div>
        </div>
      </div>
    </WizardSection>

    <!-- Map Modal -->
    <PickupMapModal 
      :is-open="isMapModalOpen"
      :type="pickupModalType"
      :initial-data="pickupModalData"
      @close="isMapModalOpen = false"
      @save="handlePickupSave"
    />
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import TiptapEditor from '~/components/v2/TiptapEditorV2.vue'
import PickupMapModal from '~/components/tours/wizard/PickupMapModal.vue'
import WizardSection from './WizardSection.vue'
import { ref, computed } from 'vue'

const store = useTourWizardStore()

// Collapsible sections — state persisted in localStorage so F5 keeps each open/closed.
const { toggleSection, isSectionExpanded } = useCollapsibles('wizard:step6')

// Helper to toggle a value in/out of an array (UCheckbox multi-select pattern)
const toggleInArray = (arr: any[], value: any, checked: boolean) => {
  const idx = arr.indexOf(value)
  if (checked && idx === -1) arr.push(value)
  if (!checked && idx !== -1) arr.splice(idx, 1)
}

const isInArray = (arr: any[] | undefined, value: any) => Array.isArray(arr) && arr.includes(value)

// === Tiempo de anticipación: D + H + M combinables ===
// Internamente convertimos a "minutes" totales y guardamos en quantity/unit del store.
const anticipationTotalMinutes = computed(() => {
  const q = store.bookingOptions.bookingAnticipationQuantity || 0
  const u = store.bookingOptions.bookingAnticipationUnit
  if (u === 'minutes') return q
  if (u === 'hours') return q * 60
  if (u === 'days') return q * 24 * 60
  return q * 60
})

const anticipationDays = computed({
  get: () => Math.floor(anticipationTotalMinutes.value / (24 * 60)),
  set: (v) => updateAnticipation(v, anticipationHours.value, anticipationMinutes.value),
})

const anticipationHours = computed({
  get: () => Math.floor((anticipationTotalMinutes.value % (24 * 60)) / 60),
  set: (v) => updateAnticipation(anticipationDays.value, v, anticipationMinutes.value),
})

const anticipationMinutes = computed({
  get: () => anticipationTotalMinutes.value % 60,
  set: (v) => updateAnticipation(anticipationDays.value, anticipationHours.value, v),
})

const updateAnticipation = (days: number, hours: number, minutes: number) => {
  const total = (Number(days) || 0) * 24 * 60 + (Number(hours) || 0) * 60 + (Number(minutes) || 0)
  store.bookingOptions.bookingAnticipationQuantity = total
  store.bookingOptions.bookingAnticipationUnit = 'minutes'
}

const anticipationSummary = computed(() => {
  const d = anticipationDays.value
  const h = anticipationHours.value
  const m = anticipationMinutes.value
  const parts: string[] = []
  if (d > 0) parts.push(`${d} ${d === 1 ? 'día' : 'días'}`)
  if (h > 0) parts.push(`${h} ${h === 1 ? 'hora' : 'horas'}`)
  if (m > 0) parts.push(`${m} ${m === 1 ? 'minuto' : 'minutos'}`)
  return parts.length ? parts.join(' ') : 'Sin anticipación'
})

const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

// Per-language booking texts - direct reference to store object
const currentBookingTexts = computed(() => {
  const seo = store.contentSEO[store.currentLanguage]
  if (!seo) return { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  if (!seo.bookingTexts) {
    seo.bookingTexts = { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  }
  return seo.bookingTexts
})

// Map Modal Logic
const isMapModalOpen = ref(false)
const pickupModalType = ref<'meeting_point' | 'hotel_pickup'>('meeting_point')
// Index of the meeting point being edited (when modal is in 'meeting_point' mode).
const editingMeetingPointIdx = ref<number>(-1)

const pickupModalData = computed(() => {
  if (pickupModalType.value === 'meeting_point') {
    const point = store.bookingOptions.meetingPoints[editingMeetingPointIdx.value]
    return {
      lat: point?.lat ?? null,
      lng: point?.lng ?? null,
      description: point?.descriptions?.[store.currentLanguage] || '',
    }
  } else {
    return {
      lat: store.bookingOptions.pickupCenterLat,
      lng: store.bookingOptions.pickupCenterLng,
      radius: store.bookingOptions.pickupRadiusKm,
      description: currentBookingTexts.value.pickupLocationDescription || '',
    }
  }
})

const openPickupModal = (type: 'meeting_point' | 'hotel_pickup') => {
  pickupModalType.value = type
  isMapModalOpen.value = true
}

const openMeetingPointModal = (idx: number) => {
  editingMeetingPointIdx.value = idx
  pickupModalType.value = 'meeting_point'
  isMapModalOpen.value = true
}

const newMeetingPointId = () => `mp-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`

const addMeetingPoint = () => {
  store.bookingOptions.meetingPoints.push({
    id: newMeetingPointId(),
    lat: null,
    lng: null,
    descriptions: {},
  })
  store.isDirty = true
}

const removeMeetingPoint = (idx: number) => {
  store.bookingOptions.meetingPoints.splice(idx, 1)
  store.isDirty = true
}

const moveMeetingPoint = (idx: number, delta: number) => {
  const target = idx + delta
  const arr = store.bookingOptions.meetingPoints
  if (target < 0 || target >= arr.length) return
  const [item] = arr.splice(idx, 1)
  arr.splice(target, 0, item)
  store.isDirty = true
}

const updatePointDescription = (idx: number, value: string) => {
  const point = store.bookingOptions.meetingPoints[idx]
  if (!point) return
  if (!point.descriptions) point.descriptions = {}
  point.descriptions[store.currentLanguage] = value
  store.isDirty = true
}

const handlePickupSave = (data: any) => {
  if (pickupModalType.value === 'meeting_point') {
    const point = store.bookingOptions.meetingPoints[editingMeetingPointIdx.value]
    if (point) {
      point.lat = data.lat
      point.lng = data.lng
      if (!point.descriptions) point.descriptions = {}
      if (data.description) point.descriptions[store.currentLanguage] = data.description
      // Keep legacy single-point fields in sync with the first entry so anything
      // still reading meetingPointLat/Lng keeps working until callers migrate.
      if (editingMeetingPointIdx.value === 0) {
        store.bookingOptions.meetingPointLat = data.lat
        store.bookingOptions.meetingPointLng = data.lng
      }
      store.isDirty = true
    }
  } else {
    store.bookingOptions.pickupCenterLat = data.lat
    store.bookingOptions.pickupCenterLng = data.lng
    store.bookingOptions.pickupRadiusKm = data.radius
    currentBookingTexts.value.pickupLocationDescription = data.description
    store.isDirty = true
  }
  isMapModalOpen.value = false
}

const policyTypes = [
  { id: 'standard', name: 'Standard (Global)', description: 'Políticas pre-establecidas por Inca Lake para todos sus tours.' },
  { id: 'custom', name: 'Personalizada', description: 'Políticas únicas para esta actividad específica.' }
] as const

const personalFields = {
  first_name: 'Nombre',
  last_name: 'Apellido',
  birthdate: 'Fecha de Nacimiento',
  nationality: 'Nacionalidad',
  phone_whatsapp: 'Número de WhatsApp',
  email: 'Correo Electrónico',
  dietary_restrictions: 'Restricciones Alimentarias',
  gender: 'Género'
}

const operationalFields = {
  peru_entry_date: 'Fecha de ingreso al Perú',
  hotel_name: 'Nombre de su hotel',
  passport_copy: 'Copia de pasaporte o ID',
  arrival_flight: 'Vuelo de llegada',
  departure_flight: 'Vuelo de salida',
  weight_kg: 'Peso (kg)',
  height_m: 'Altura (m)',
  arrival_bus_company: 'Cía de bus de llegada',
  arrival_train: 'Tren de llegada'
}

const guideTypes = [
  { id: 'live_guide', name: 'Guía de tour en vivo' },
  { id: 'audio_guide', name: 'Audio Guía y Audífonos' },
  { id: 'informative_brochures', name: 'Folletos informativos' },
  { id: 'no_guide', name: 'Sin Guía / Tickets' },
  { id: 'none', name: 'No mostrar nada' }
] as const

const guideLanguages = [
  { id: 1, name: 'Español' },
  { id: 2, name: 'Inglés' },
  { id: 3, name: 'Francés' },
  { id: 4, name: 'Alemán' },
  { id: 5, name: 'Portugués' },
  { id: 6, name: 'Italiano' }
]

const calculateExampleTime = () => {
  const q = store.bookingOptions.bookingAnticipationQuantity
  const u = store.bookingOptions.bookingAnticipationUnit

  if (u === 'minutes') {
    const m = q % 60
    const h = Math.floor(q / 60)
    if (h === 0) return `${q} minutos antes (a las 6:${(60 - m).toString().padStart(2, '0')} AM)`
    const remaining = m === 0 ? '' : ` ${m} minutos`
    return `${h}h${remaining} antes del inicio`
  }

  if (u === 'hours') {
    if (q >= 7) {
      return `las ${24 - (q - 7)}:00 del día anterior`
    } else {
      return `las ${7 - q}:00 AM del mismo día`
    }
  } else {
    return `${q === 1 ? 'un día' : q + ' días'} antes del inicio`
  }
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.5);
}

.material-symbols-outlined.filled {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
