<script setup lang="ts">
// Tap-toggle popover/tooltip — works on touch (unlike CSS :hover tooltips).
// Trigger: an info icon by default, or override via the #trigger slot.
// Content: default slot. Closes on outside tap and Escape.
const props = withDefaults(defineProps<{
  icon?: string
  width?: string
  label?: string
}>(), { icon: 'info', width: 'w-52', label: 'Más información' })

const open = ref(false)

function onKey(e: KeyboardEvent) { if (e.key === 'Escape') open.value = false }
onMounted(() => { if (import.meta.client) window.addEventListener('keydown', onKey) })
onBeforeUnmount(() => { if (import.meta.client) window.removeEventListener('keydown', onKey) })
</script>

<template>
  <span class="relative inline-flex items-center align-middle">
    <button
      type="button"
      @click.stop="open = !open"
      class="inline-flex items-center"
      :aria-label="label"
      :aria-expanded="open"
    >
      <slot name="trigger">
        <span class="material-symbols-outlined text-slate-400 text-sm">{{ icon }}</span>
      </slot>
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="open"
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 p-2 bg-slate-800 text-white text-[10px] rounded-lg shadow-lg z-50 text-left font-normal normal-case"
        :class="width"
      >
        <slot />
        <span class="block w-2 h-2 bg-slate-800 rotate-45 absolute -bottom-1 left-1/2 -translate-x-1/2"></span>
      </div>
    </Transition>

    <!-- Outside-tap catcher -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
  </span>
</template>
