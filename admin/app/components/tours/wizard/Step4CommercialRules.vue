<template>
  <div class="flex flex-col gap-6">
    <!-- Payment Methods -->
    <WizardSection title="Método de pago aceptado *" icon="i-lucide-credit-card">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <button
          v-for="opt in paymentMethodOptions"
          :key="opt.value"
          type="button"
          :class="[
            'p-4 rounded-xl border-2 text-left transition-all flex items-start gap-3',
            store.commercialRules.paymentMethod === opt.value
              ? 'border-primary bg-primary/5 shadow-md shadow-primary/10'
              : 'border-default hover:border-muted',
          ]"
          @click="store.commercialRules.paymentMethod = opt.value"
        >
          <div :class="['size-9 rounded-lg flex items-center justify-center shrink-0', store.commercialRules.paymentMethod === opt.value ? 'bg-primary text-white' : 'bg-elevated text-muted']">
            <UIcon :name="opt.icon" class="size-5" />
          </div>
          <div class="min-w-0">
            <p class="font-bold text-sm">{{ opt.label }}</p>
            <p class="text-[11px] text-muted mt-0.5">{{ opt.description }}</p>
          </div>
          <UIcon
            v-if="store.commercialRules.paymentMethod === opt.value"
            name="i-lucide-circle-check"
            class="size-4 text-primary shrink-0 ml-auto"
          />
        </button>
      </div>
    </WizardSection>

    <!-- Pricing — Age Stage Selector + Editor -->
    <WizardSection
      title="Precios por etapa de edad"
      icon="i-lucide-coins"
      description="Configura precios por nacionalidad y cantidad de pasajeros"
    >
      <template #actions>
        <UBadge
          v-if="hasConflicts"
          color="error"
          variant="subtle"
          size="md"
          icon="i-lucide-triangle-alert"
        >
          {{ Object.keys(conflictsByRange).length }} rango(s) en conflicto
        </UBadge>
      </template>

      <div class="space-y-4">
      <UAlert
        v-if="hasConflicts"
        color="error"
        variant="subtle"
        icon="i-lucide-triangle-alert"
        title="Hay rangos en conflicto"
        description="Los rangos no pueden solaparse ni duplicarse dentro de la misma nacionalidad. Ejemplo válido: 1-1, 2-5, 6-20. Resuelve los rangos resaltados antes de guardar."
      />

      <!-- Add Age Stage -->
      <div class="flex items-center justify-between gap-3 flex-wrap pb-2">
        <p class="text-sm font-semibold">
          Etapas de edad configuradas ({{ store.commercialRules.ageStages.length }})
        </p>
        <UDropdownMenu
          :items="[
            availablePresets.map(p => ({
              label: p.description,
              icon: 'i-lucide-circle-plus',
              kbds: [`${p.minAge}-${p.maxAge >= 99 ? '+' : p.maxAge}`],
              onSelect: () => addAgeStage(p),
            })),
            [{ label: 'Personalizada', icon: 'i-lucide-pencil', onSelect: () => addAgeStage() }],
          ]"
          :content="{ align: 'end' }"
        >
          <UButton icon="i-lucide-plus" color="primary" size="sm" trailing-icon="i-lucide-chevron-down">
            Agregar etapa
          </UButton>
        </UDropdownMenu>
      </div>

      <!-- Age Stages — Each one collapsible -->
      <div class="space-y-3">
        <div
          v-for="(ageStage, stageIndex) in store.commercialRules.ageStages"
          :key="ageStage.id"
          :class="[
            'rounded-xl border-2 overflow-hidden transition-all',
            ageStage.active ? 'border-primary/30' : 'border-default opacity-70',
          ]"
        >
          <!-- Collapsible Header -->
          <button
            type="button"
            class="w-full px-5 py-4 bg-elevated/40 flex items-center justify-between gap-3 flex-wrap hover:bg-elevated/60 transition-colors text-left"
            @click="toggleStage(String(ageStage.id))"
          >
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <UIcon
                name="i-lucide-chevron-down"
                class="size-5 text-muted transition-transform shrink-0"
                :class="{ 'rotate-180': isStageExpanded(String(ageStage.id)) }"
              />
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-base">{{ ageStage.description }}</h4>
                  <UBadge color="neutral" variant="subtle" size="xs">
                    {{ ageStage.minAge }} - {{ ageStage.maxAge >= 99 ? '+' : ageStage.maxAge }} años
                  </UBadge>
                  <UBadge
                    v-if="ageStage.nationalities.length > 0"
                    color="primary"
                    variant="subtle"
                    size="xs"
                    icon="i-lucide-flag"
                  >
                    {{ ageStage.nationalities.length }} nacionalidad{{ ageStage.nationalities.length === 1 ? '' : 'es' }}
                  </UBadge>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-default border border-default" @click.stop>
              <USwitch v-model="ageStage.active" color="primary" size="sm" />
              <span class="text-xs font-bold uppercase tracking-widest text-muted">
                {{ ageStage.active ? 'Activa' : 'Inactiva' }}
              </span>
            </div>
          </button>

          <!-- Collapsible Body -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out overflow-hidden"
            leave-active-class="transition-all duration-200 ease-in overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="isStageExpanded(String(ageStage.id))" class="border-t border-default">
              <!-- Stage Metadata (editable) -->
              <div class="px-5 py-4 bg-elevated/20 border-b border-default">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto_auto] gap-3 items-end">
                  <UFormField label="Nombre de la etapa">
                    <UInput
                      v-model="ageStage.description"
                      placeholder="Ej: Adulto, Niño, Adulto Mayor..."
                      icon="i-lucide-tag"
                      class="w-full"
                    />
                  </UFormField>
                  <UFormField label="Edad mín">
                    <UInputNumber v-model="ageStage.minAge" :min="0" :max="120" class="w-24" />
                  </UFormField>
                  <UFormField label="Edad máx">
                    <UInputNumber v-model="ageStage.maxAge" :min="0" :max="120" class="w-24" />
                  </UFormField>
                  <UButton
                    icon="i-lucide-trash-2"
                    color="error"
                    variant="ghost"
                    title="Eliminar etapa de edad"
                    @click="removeAgeStage(stageIndex)"
                  >
                    Eliminar etapa
                  </UButton>
                </div>
              </div>

              <div v-if="ageStage.active" class="p-5 space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <p class="text-sm font-semibold">
                    Nacionalidades configuradas ({{ ageStage.nationalities.length }})
                  </p>
                  <UButton
                    icon="i-lucide-plus"
                    color="success"
                    size="sm"
                    @click="addNationality(ageStage)"
                  >
                    Agregar nacionalidad
                  </UButton>
                </div>

                <div class="space-y-3">
                  <UCard
                    v-for="(nat, natIndex) in ageStage.nationalities"
                    :key="nat.id"
                    :ref="el => { if (el) natRefs[nat.id] = (el as any)?.$el || el as HTMLElement }"
                    :ui="{ body: 'p-4 space-y-4' }"
                  >
                    <!-- Nationality Header -->
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                      <div class="flex items-center gap-3 flex-wrap flex-1">
                        <UFormField label="Nacionalidad" :ui="{ wrapper: 'min-w-[220px]' }" class="flex-1">
                          <USelect
                            v-model="nat.nationalityId"
                            :items="nationalityOptions"
                            placeholder="Seleccionar nacionalidad"
                            class="w-full"
                          />
                        </UFormField>
                      </div>
                      <UButton
                        icon="i-lucide-trash-2"
                        color="error"
                        variant="ghost"
                        size="sm"
                        title="Eliminar nacionalidad"
                        @click="removeNationality(ageStage, natIndex)"
                      />
                    </div>

                    <!-- Pricing table -->
                    <div v-if="nat.nationalityId" class="space-y-3">
                      <p class="text-[10px] font-black uppercase tracking-widest text-muted">Precios por cantidad de pasajeros</p>

                      <div class="bg-elevated/30 rounded-lg border border-default overflow-hidden">
                        <div class="grid grid-cols-[1fr_1fr_1.4fr_auto] gap-2 px-3 py-2 border-b border-default text-[10px] font-black uppercase tracking-widest text-muted">
                          <span>Desde (pax)</span>
                          <span>Hasta (pax)</span>
                          <span>Precio USD</span>
                          <span class="w-7"></span>
                        </div>
                        <div class="divide-y divide-default">
                          <div
                            v-for="(range, rIndex) in nat.ranges"
                            :key="range.id"
                          >
                            <div
                              class="grid grid-cols-[1fr_1fr_1.4fr_auto] gap-2 px-3 py-2 items-center group transition-colors"
                              :class="{ 'bg-error/5': conflictsByRange[range.id] }"
                            >
                              <UInputNumber
                                v-model="range.from"
                                :min="1"
                                class="w-full"
                                :ui="{ root: conflictsByRange[range.id] ? 'ring-2 ring-error/30' : '' }"
                              />
                              <UInputNumber
                                v-model="range.to"
                                :min="1"
                                class="w-full"
                                :ui="{ root: conflictsByRange[range.id] ? 'ring-2 ring-error/30' : '' }"
                              />
                              <UInput
                                v-model.number="range.price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full"
                              >
                                <template #leading>
                                  <span class="text-xs font-bold text-muted">$</span>
                                </template>
                              </UInput>
                              <UButton
                                icon="i-lucide-x"
                                color="error"
                                variant="ghost"
                                size="xs"
                                class="opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 transition-opacity"
                                title="Eliminar rango"
                                @click="removeRange(nat, rIndex)"
                              />
                            </div>
                            <div
                              v-if="conflictsByRange[range.id]"
                              class="px-3 pb-2 -mt-1 text-[11px] text-error font-semibold flex items-start gap-1.5"
                            >
                              <UIcon name="i-lucide-triangle-alert" class="size-3.5 shrink-0 mt-0.5" />
                              <span>{{ conflictsByRange[range.id].join(' · ') }}</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <UButton
                        icon="i-lucide-plus"
                        color="primary"
                        variant="soft"
                        size="sm"
                        @click="addRange(nat)"
                      >
                        Agregar rango de precio
                      </UButton>
                    </div>

                    <p v-else class="text-center text-xs italic text-muted py-2">
                      Selecciona una nacionalidad para configurar precios
                    </p>
                  </UCard>

                  <UAlert
                    v-if="ageStage.nationalities.length === 0"
                    color="neutral"
                    variant="subtle"
                    icon="i-lucide-info"
                    title="Sin nacionalidades configuradas"
                    description='Haz clic en "Agregar nacionalidad" para definir precios para esta etapa de edad.'
                  />
                </div>
              </div>

              <div v-else class="p-6 text-center">
                <UIcon name="i-lucide-circle-pause" class="size-8 text-muted mx-auto mb-2" />
                <p class="text-sm text-muted">Activa esta etapa de edad para configurar precios</p>
              </div>
            </div>
          </Transition>
        </div>
      </div>
      </div>
    </WizardSection>

    <!-- General Config Section -->
    <WizardSection title="Configuración de precios generales" icon="i-lucide-settings-2">
      <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <UFormField label="Tasas e impuestos (%)" hint="Se añadirá al precio final para cubrir comisiones o impuestos locales">
          <UInput
            v-model.number="store.commercialRules.taxPercentage"
            type="number"
            step="0.01"
            min="0"
            max="100"
            class="w-full"
          >
            <template #trailing>
              <span class="text-sm font-bold text-muted">%</span>
            </template>
          </UInput>
        </UFormField>

        <UFormField label="Primera cuota (%)" hint="100% = pago completo · Menos = permite reserva con pago inicial">
          <UInput
            v-model.number="store.commercialRules.advancePaymentPercentage"
            type="number"
            min="1"
            max="100"
            class="w-full"
          >
            <template #trailing>
              <span class="text-sm font-bold text-muted">%</span>
            </template>
          </UInput>
        </UFormField>
      </div>

      <UAlert
        v-if="store.commercialRules.advancePaymentPercentage < 100"
        color="warning"
        variant="subtle"
        icon="i-lucide-triangle-alert"
        title="Reserva parcial habilitada"
        description="Los clientes podrán reservar pagando solo una parte. El monto restante deberá gestionarse directamente con el cliente o al inicio del tour."
      />
      </div>
    </WizardSection>

    <!-- Tips -->
    <UAlert
      color="primary"
      variant="subtle"
      icon="i-lucide-lightbulb"
      title="Estructura profesional de precios"
      description="Este sistema de 3 niveles te da flexibilidad total: define precios base, ajusta según el origen del pasajero (Local vs Extranjero) y recompensa a los grupos grandes con descuentos automáticos por volumen de PAX."
    />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref } from 'vue'
import { useTourWizardStore } from '~/stores/tourWizard'
import WizardSection from './WizardSection.vue'
import type { AgeStagePrice, NationalityPrice } from '~/stores/tourWizard'

const store = useTourWizardStore()
const { confirm } = useConfirm()

const natRefs = reactive<Record<string, HTMLElement>>({})

// Each age stage is a collapsible. State persisted in localStorage so F5 keeps it.
const {
  expandedSections: expandedStages,
  toggleSection: toggleStage,
  isSectionExpanded: isStageExpanded,
} = useCollapsibles('wizard:step4-stages')

// Track whether we've already seeded the default (open first stage) — once seeded,
// the user's choice to close everything is respected on subsequent loads.
const SEED_KEY = 'wizard:step4-stages:seeded'
const initExpanded = () => {
  if (!import.meta.client) return
  if (sessionStorage.getItem(SEED_KEY)) return
  if (store.commercialRules.ageStages.length && expandedStages.value.size === 0) {
    expandedStages.value = new Set([String(store.commercialRules.ageStages[0].id)])
    sessionStorage.setItem(SEED_KEY, '1')
  }
}
initExpanded()

const paymentMethodOptions = [
  { value: 'all', label: 'Todos los métodos', description: 'PayPal + Culqi', icon: 'i-lucide-credit-card' },
  { value: 'culqi', label: 'Solo Culqi', description: 'Tarjetas locales (Perú)', icon: 'i-lucide-circle-dollar-sign' },
  { value: 'paypal', label: 'Solo PayPal', description: 'Pagos internacionales', icon: 'i-lucide-globe' },
]

const nationalityOptions = [
  { value: 'general', label: 'General' },
  { value: 'peruano', label: 'Peruano' },
  { value: 'latino', label: 'Latinoamericano' },
  { value: 'extranjero', label: 'Extranjero' },
]

// Plantillas comunes de etapas de edad — para que se agreguen con un click
const stagePresets = [
  { description: 'Infante', minAge: 0, maxAge: 2 },
  { description: 'Niño', minAge: 3, maxAge: 11 },
  { description: 'Adolescente', minAge: 12, maxAge: 17 },
  { description: 'Adulto', minAge: 18, maxAge: 64 },
  { description: 'Adulto Mayor', minAge: 65, maxAge: 99 },
]

const usedDescriptions = computed(() =>
  new Set(store.commercialRules.ageStages.map((s) => s.description.toLowerCase().trim())),
)

const availablePresets = computed(() =>
  stagePresets.filter((p) => !usedDescriptions.value.has(p.description.toLowerCase())),
)

const addAgeStage = (preset?: { description: string; minAge: number; maxAge: number }) => {
  const newId = crypto.randomUUID()
  const stage: AgeStagePrice = {
    id: newId,
    description: preset?.description || 'Nueva etapa',
    minAge: preset?.minAge ?? 0,
    maxAge: preset?.maxAge ?? 99,
    active: true,
    nationalities: [],
  }
  store.commercialRules.ageStages.push(stage)
  // Auto-expand the new stage
  expandedStages.value = new Set([...expandedStages.value, String(newId)])
}

const removeAgeStage = async (index: number) => {
  const stage = store.commercialRules.ageStages[index]
  if (!stage) return
  const ok = await confirm({
    title: 'Eliminar etapa de edad',
    description: `Vas a eliminar "${stage.description}" y todas sus configuraciones de precios y nacionalidades.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  store.commercialRules.ageStages.splice(index, 1)
  const next = new Set(expandedStages.value)
  next.delete(String(stage.id))
  expandedStages.value = next
}

const addNationality = async (ageStage: AgeStagePrice) => {
  const newNat = {
    id: crypto.randomUUID(),
    nationalityId: '',
    ageMin: ageStage.minAge,
    ageMax: ageStage.maxAge,
    ranges: [
      { id: crypto.randomUUID(), from: 1, to: 1, price: 0 }
    ]
  }
  ageStage.nationalities.push(newNat)
  await nextTick()
  const el = natRefs[newNat.id]
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    const select = el.querySelector('select') as HTMLSelectElement | null
    select?.focus()
  }
}

const removeNationality = (ageStage: AgeStagePrice, index: number) => {
  ageStage.nationalities.splice(index, 1)
}

const addRange = (nat: NationalityPrice) => {
  // Pick the smallest 'from' that doesn't overlap any existing range, regardless
  // of insertion order. If the user has 1-1 and 5-5, the next slot is 6-6.
  const maxTo = nat.ranges.length
    ? Math.max(...nat.ranges.map(r => Number(r.to) || 0))
    : 0
  const nextFrom = maxTo + 1
  const lastPrice = nat.ranges[nat.ranges.length - 1]?.price ?? 0

  nat.ranges.push({
    id: crypto.randomUUID(),
    from: nextFrom,
    to: nextFrom,
    price: lastPrice,
  })
}

const removeRange = (nat: NationalityPrice, index: number) => {
  nat.ranges.splice(index, 1)
}

/**
 * Conflict detection per nationality. Returns a map keyed by range.id with the
 * list of human-readable issues for that row. Watch the whole store reactively
 * so the UI updates as the editor types.
 */
const conflictsByRange = computed(() => {
  const map: Record<string, string[]> = {}

  for (const stage of store.commercialRules.ageStages) {
    if (!stage.active) continue
    for (const nat of stage.nationalities) {
      const rs = nat.ranges
      for (let i = 0; i < rs.length; i++) {
        const r = rs[i]
        const id = r.id
        const errs = map[id] || []
        const from = Number(r.from)
        const to = Number(r.to)

        // Invalid bounds
        if (!Number.isFinite(from) || from < 1) errs.push('"Desde" debe ser ≥ 1')
        if (!Number.isFinite(to) || to < 1) errs.push('"Hasta" debe ser ≥ 1')
        if (Number.isFinite(from) && Number.isFinite(to) && from > to) {
          errs.push('"Desde" no puede ser mayor que "Hasta"')
        }

        // Compare against previous ranges in the same nationality
        for (let j = 0; j < i; j++) {
          const o = rs[j]
          const ofrom = Number(o.from)
          const oto = Number(o.to)
          if (!Number.isFinite(ofrom) || !Number.isFinite(oto)) continue

          const overlaps = from <= oto && to >= ofrom
          if (overlaps) {
            const exact = from === ofrom && to === oto
            if (exact) {
              errs.push(`Duplicado del rango ${ofrom}-${oto}`)
            } else if (from >= ofrom && to <= oto) {
              errs.push(`Está contenido en ${ofrom}-${oto}`)
            } else if (from <= ofrom && to >= oto) {
              errs.push(`Contiene al rango ${ofrom}-${oto}`)
            } else {
              errs.push(`Se solapa con ${ofrom}-${oto}`)
            }
          }
        }

        if (errs.length) map[id] = errs
      }
    }
  }
  return map
})

const hasConflicts = computed(() => Object.keys(conflictsByRange.value).length > 0)

// Expose to the wizard: this is referenced by the Save flow if you want to block
// (hook this up in WizardInsightsSidebar later with a watch on store).
defineExpose({ hasConflicts, conflictsByRange })
</script>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.7);
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-in {
  animation: fade-in 0.3s ease-out forwards;
}
</style>
