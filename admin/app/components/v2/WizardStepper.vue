<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { computed } from 'vue'

// `bare` drops the row chrome (border/bg/padding) so the stepper can live
// inside the dashboard navbar, which provides its own spacing.
withDefaults(defineProps<{ bare?: boolean }>(), { bare: false })

const store = useTourWizardStore()

const steps = [
  { id: 1, label: 'Información', shortLabel: 'Info', icon: 'i-lucide-info' },
  { id: 2, label: 'Contenido', shortLabel: 'Contenido', icon: 'i-lucide-file-text' },
  { id: 3, label: 'SEO', shortLabel: 'SEO', icon: 'i-lucide-search' },
  { id: 4, label: 'Precios', shortLabel: 'Precios', icon: 'i-lucide-dollar-sign' },
  { id: 5, label: 'Multimedia', shortLabel: 'Media', icon: 'i-lucide-image' },
  { id: 6, label: 'Reservas', shortLabel: 'Reservas', icon: 'i-lucide-calendar-check' },
  { id: 7, label: 'Categorías', shortLabel: 'Tags', icon: 'i-lucide-tags' },
  { id: 8, label: 'Disponibilidad', shortLabel: 'Calendario', icon: 'i-lucide-calendar-days' },
  { id: 9, label: 'Revisión', shortLabel: 'Final', icon: 'i-lucide-check-circle' },
]

const stepState = (id: number): 'completed' | 'current' | 'pending' => {
  if (id < store.currentStep) return 'completed'
  if (id === store.currentStep) return 'current'
  return 'pending'
}
</script>

<template>
  <div :class="bare ? 'w-full min-w-0 overflow-x-auto' : 'border-b border-default bg-elevated/20 px-4 lg:px-6 py-2.5'">
    <ol class="flex items-center gap-1 overflow-x-auto">
      <li
        v-for="(step, idx) in steps"
        :key="step.id"
        class="flex items-center gap-1 shrink-0"
      >
        <button
          type="button"
          class="group flex items-center gap-2 px-2 py-1.5 rounded-lg transition-all hover:bg-elevated"
          @click="store.goToStep(step.id)"
        >
          <!-- Circle -->
          <div
            :class="[
              'size-7 rounded-full flex items-center justify-center text-xs font-black transition-all shrink-0',
              stepState(step.id) === 'completed' && 'bg-success text-white shadow-sm',
              stepState(step.id) === 'current' && 'bg-primary text-white shadow-md shadow-primary/30 ring-4 ring-primary/15',
              stepState(step.id) === 'pending' && 'bg-elevated text-muted ring-1 ring-default group-hover:ring-2 group-hover:ring-primary/30',
            ]"
          >
            <UIcon v-if="stepState(step.id) === 'completed'" name="i-lucide-check" class="size-4" />
            <span v-else>{{ step.id }}</span>
          </div>

          <!-- Label: every step shows its name (hidden on mobile = circles only).
               The "Paso N" subtitle is dropped to keep the bar compact. -->
          <p
            class="text-xs font-bold tracking-tight whitespace-nowrap hidden sm:block"
            :class="[
              stepState(step.id) === 'current' && 'text-primary',
              stepState(step.id) === 'completed' && 'text-default',
              stepState(step.id) === 'pending' && 'text-muted',
            ]"
          >
            {{ step.label }}
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
  </div>
</template>
