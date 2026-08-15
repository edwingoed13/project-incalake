<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

// `bare` drops the row chrome (border/bg/padding) so the stepper can live
// inside the dashboard navbar, which provides its own spacing.
withDefaults(defineProps<{ bare?: boolean }>(), { bare: false })

const store = useTourWizardStore()

// --- Overflow affordance ----------------------------------------------------
// At laptop widths the 9 steps don't fit and the row scrolls — but nothing
// SAID so: on a 1366 screen the stepper simply ended at step 7 and operators
// had no cue that Disponibilidad/Revisión existed. Edge fades appear when
// there is more content on that side, and the current step keeps itself
// scrolled into view.
const scroller = ref<HTMLElement | null>(null)
const canLeft = ref(false)
const canRight = ref(false)

const updateEdges = () => {
  const el = scroller.value
  if (!el) return
  canLeft.value = el.scrollLeft > 4
  canRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4
}

const scrollCurrentIntoView = () => {
  nextTick(() => {
    scroller.value
      ?.querySelector<HTMLElement>('[data-current="true"]')
      ?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' })
  })
}

let ro: ResizeObserver | null = null
onMounted(() => {
  updateEdges()
  scrollCurrentIntoView()
  ro = new ResizeObserver(updateEdges)
  if (scroller.value) ro.observe(scroller.value)
})
onBeforeUnmount(() => ro?.disconnect())

watch(() => store.currentStep, () => {
  scrollCurrentIntoView()
  nextTick(updateEdges)
})

const steps = [
  { id: 1, label: 'Información', shortLabel: 'Info', icon: 'i-lucide-info' },
  { id: 2, label: 'Contenido', shortLabel: 'Contenido', icon: 'i-lucide-file-text' },
  { id: 3, label: 'SEO', shortLabel: 'SEO', icon: 'i-lucide-search' },
  { id: 4, label: 'Precios', shortLabel: 'Precios', icon: 'i-lucide-dollar-sign' },
  { id: 5, label: 'Multimedia', shortLabel: 'Media', icon: 'i-lucide-image' },
  { id: 6, label: 'Reservas', shortLabel: 'Reservas', icon: 'i-lucide-calendar-check' },
  { id: 7, label: 'Disponibilidad', shortLabel: 'Calendario', icon: 'i-lucide-calendar-days' },
  { id: 8, label: 'Revisión', shortLabel: 'Final', icon: 'i-lucide-check-circle' },
]

const stepState = (id: number): 'completed' | 'current' | 'pending' => {
  if (id < store.currentStep) return 'completed'
  if (id === store.currentStep) return 'current'
  return 'pending'
}

// Essential-data status for the dot overlay. Returns undefined for the
// optional steps (6-9) so they render no dot. Reads the store getter.
const dataStatus = (id: number): 'complete' | 'empty' | undefined =>
  store.stepStatuses[id]
</script>

<template>
  <!-- Below md the navbar's right-side buttons squeeze this slot to zero width
       anyway, so the stepper hides deliberately and the step dropdown in the
       wizard's bottom bar takes over as navigator. -->
  <div :class="bare ? 'hidden md:block relative w-full min-w-0' : 'relative border-b border-default bg-elevated/20 px-4 lg:px-6 py-2.5'">
    <ol ref="scroller" class="flex items-center gap-1 overflow-x-auto" @scroll.passive="updateEdges">
      <li
        v-for="(step, idx) in steps"
        :key="step.id"
        class="flex items-center gap-1 shrink-0"
      >
        <button
          type="button"
          class="group flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all hover:bg-elevated"
          :data-current="stepState(step.id) === 'current'"
          @click="store.goToStep(step.id)"
        >
          <!-- Circle -->
          <div class="relative shrink-0">
            <div
              :class="[
                'size-7 rounded-full flex items-center justify-center text-xs font-black transition-all',
                stepState(step.id) === 'completed' && 'bg-success text-white shadow-sm',
                stepState(step.id) === 'current' && 'bg-primary text-white shadow-md shadow-primary/30 ring-4 ring-primary/15',
                stepState(step.id) === 'pending' && 'bg-elevated text-muted ring-1 ring-default group-hover:ring-2 group-hover:ring-primary/30',
              ]"
            >
              <UIcon v-if="stepState(step.id) === 'completed'" name="i-lucide-check" class="size-4" />
              <span v-else>{{ step.id }}</span>
            </div>
            <!-- Essential-data dot (steps 1-5 only): green = filled, amber =
                 still empty. Lets the operator spot incomplete core steps
                 without opening each. Hidden on the step they're currently on
                 to avoid clutter. -->
            <span
              v-if="dataStatus(step.id) && stepState(step.id) !== 'current'"
              :class="[
                'absolute -top-0.5 -right-0.5 size-2.5 rounded-full ring-2 ring-default',
                dataStatus(step.id) === 'complete' ? 'bg-success' : 'bg-amber-400',
              ]"
              :title="dataStatus(step.id) === 'complete' ? 'Datos esenciales completos' : 'Faltan datos esenciales'"
            />
          </div>

          <!-- Label visibility is progressive:
               · the CURRENT step always shows its label (so even on a phone
                 you read "③ SEO", not just a circle),
               · every other step reveals from lg up.
               Short label until xl, full label from xl, so the 9-step row
               never overflows on mid-width laptops. -->
          <p
            class="text-xs font-bold tracking-tight whitespace-nowrap"
            :class="[
              stepState(step.id) === 'current' ? 'block' : 'hidden lg:block',
              stepState(step.id) === 'current' && 'text-primary',
              stepState(step.id) === 'completed' && 'text-default',
              stepState(step.id) === 'pending' && 'text-muted',
            ]"
          >
            <span class="xl:hidden">{{ step.shortLabel }}</span>
            <span class="hidden xl:inline">{{ step.label }}</span>
          </p>
        </button>

        <!-- Connector -->
        <div
          v-if="idx < steps.length - 1"
          :class="[
            'h-0.5 w-2.5 lg:w-4 transition-colors shrink-0',
            stepState(step.id) === 'completed' ? 'bg-success' : 'bg-default',
          ]"
        />
      </li>
    </ol>

    <!-- Edge fades: visible only when hay más pasos hacia ese lado. -->
    <div
      v-if="canLeft"
      class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center"
      style="background: linear-gradient(to right, var(--ui-bg), transparent)"
    >
      <UIcon name="i-lucide-chevron-left" class="size-3.5 text-muted" />
    </div>
    <div
      v-if="canRight"
      class="pointer-events-none absolute inset-y-0 right-0 w-8 flex items-center justify-end"
      style="background: linear-gradient(to left, var(--ui-bg), transparent)"
    >
      <UIcon name="i-lucide-chevron-right" class="size-3.5 text-muted" />
    </div>
  </div>
</template>
