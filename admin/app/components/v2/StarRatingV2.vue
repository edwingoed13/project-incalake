<script setup lang="ts">
import { ref, computed } from 'vue'

/**
 * The star rating, filled.
 *
 * The three places that drew stars all wrote `fill-yellow-400` next to
 * `text-yellow-400`, and none of them filled anything: UIcon renders Lucide
 * through a CSS mask, so only the painted pixels — the outline — take the
 * colour, and the `fill` utility never reaches an SVG at all. Even in `svg`
 * mode it would lose, because Lucide bakes `fill="none"` onto the path itself
 * and a presentation attribute beats an inherited CSS value.
 *
 * So the star is drawn here, from Lucide's own geometry, with the fill under
 * our control. The stroke stays on either way: it is what keeps a chosen star
 * exactly the same size as an empty one, instead of shrinking as it fills.
 */
interface Props {
  modelValue: number
  readonly?: boolean
  /** Tailwind size class for each star. */
  size?: string
  max?: number
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
  size: 'size-5',
  max: 5,
})

const emit = defineEmits<{ 'update:modelValue': [value: number] }>()

// While the pointer is over the row, show what clicking would give.
const hovered = ref(0)
const shown = computed(() => (hovered.value || props.modelValue))

const STAR = 'M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z'
</script>

<template>
  <!-- Read-only: decorative stars plus the one label a screen reader needs. -->
  <div
    v-if="readonly"
    class="flex items-center gap-0.5"
    role="img"
    :aria-label="`${modelValue} de ${max} estrellas`"
  >
    <svg
      v-for="i in max"
      :key="i"
      viewBox="0 0 24 24"
      aria-hidden="true"
      :class="[size, i <= modelValue ? 'text-yellow-400' : 'text-muted']"
      :fill="i <= modelValue ? 'currentColor' : 'none'"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path :d="STAR" />
    </svg>
  </div>

  <div v-else class="flex items-center gap-0.5" @mouseleave="hovered = 0">
    <button
      v-for="i in max"
      :key="i"
      type="button"
      class="p-1 rounded transition-transform hover:scale-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
      :aria-label="i === 1 ? '1 estrella' : `${i} estrellas`"
      :aria-pressed="i === modelValue"
      @click="emit('update:modelValue', i)"
      @mouseenter="hovered = i"
      @focus="hovered = i"
      @blur="hovered = 0"
    >
      <svg
        viewBox="0 0 24 24"
        aria-hidden="true"
        :class="[size, 'transition-colors', i <= shown ? 'text-yellow-400' : 'text-muted']"
        :fill="i <= shown ? 'currentColor' : 'none'"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path :d="STAR" />
      </svg>
    </button>
  </div>
</template>
