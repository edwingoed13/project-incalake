<script setup lang="ts">
// Touch-friendly +/- stepper. 44×44px targets, disabled at bounds, a11y live
// count. Used for adults/children in the booking card. The upper bound is a
// cross-field constraint (adults + children ≤ maxPax), so the parent computes
// `atMax` and passes it in rather than the stepper owning the max.
const props = defineProps<{
  label: string
  hint?: string
  min?: number
  atMax?: boolean
}>()

const model = defineModel<number>({ required: true })

function dec() { if (model.value > (props.min ?? 0)) model.value-- }
function inc() { if (!props.atMax) model.value++ }
</script>

<template>
  <div class="flex items-center justify-between border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-white dark:bg-slate-800">
    <div class="leading-tight min-w-0">
      <span class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ label }}</span>
      <span v-if="hint" class="block text-xs text-slate-500">{{ hint }}</span>
    </div>
    <div class="flex items-center gap-1.5 shrink-0">
      <button
        type="button"
        @click="dec"
        :disabled="model <= (min ?? 0)"
        class="w-11 h-11 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 active:scale-95 transition disabled:opacity-40 disabled:active:scale-100"
        :aria-label="`Quitar ${label}`"
      >
        <Icon name="material-symbols:remove" class="size-5" />
      </button>
      <span class="w-7 text-center font-bold text-sm tabular-nums" aria-live="polite">{{ model }}</span>
      <button
        type="button"
        @click="inc"
        :disabled="atMax"
        class="w-11 h-11 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 active:scale-95 transition disabled:opacity-40 disabled:active:scale-100"
        :aria-label="`Agregar ${label}`"
      >
        <Icon name="material-symbols:add" class="size-5" />
      </button>
    </div>
  </div>
</template>
