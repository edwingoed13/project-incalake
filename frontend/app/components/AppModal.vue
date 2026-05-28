<script setup lang="ts">
// Reusable modal for the public site (Tailwind).
// - v-model for open state
// - Bottom-sheet on mobile, centered dialog on desktop
// - Closes on backdrop click, Escape, and the X button; locks body scroll
// - Slots: #title (or `title` prop), default (body), #footer
const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  maxWidth?: string
}>(), { title: '', maxWidth: 'max-w-lg' })

const emit = defineEmits<{ 'update:modelValue': [boolean] }>()

function close() { emit('update:modelValue', false) }

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.modelValue) close()
}

watch(() => props.modelValue, (open) => {
  if (import.meta.client) document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => { if (import.meta.client) window.addEventListener('keydown', onKey) })
onBeforeUnmount(() => {
  if (import.meta.client) {
    window.removeEventListener('keydown', onKey)
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4"
        role="dialog"
        aria-modal="true"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
        <div
          class="relative w-full bg-white dark:bg-slate-900 shadow-2xl flex flex-col rounded-t-2xl sm:rounded-2xl max-h-[90vh] sm:max-h-[85vh]"
          :class="maxWidth"
        >
          <!-- Header -->
          <div class="flex items-center justify-between gap-3 p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 shrink-0">
            <h3 class="text-base font-bold text-slate-800 dark:text-white truncate">
              <slot name="title">{{ title }}</slot>
            </h3>
            <button
              type="button"
              @click="close"
              class="-mr-1 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0"
              aria-label="Cerrar"
            >
              <Icon name="material-symbols:close" class="text-2xl" />
            </button>
          </div>

          <!-- Body -->
          <div class="p-4 sm:p-5 overflow-y-auto">
            <slot />
          </div>

          <!-- Footer (optional) -->
          <div v-if="$slots.footer" class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Rich (Tiptap) content inside modals: tables scroll horizontally on mobile
   instead of crushing/overflowing; columns stay aligned, cells keep a min width. */
:deep(.prose table) {
  display: block;
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  border-collapse: collapse;
  margin: 0.5rem 0;
  font-size: 0.8rem;
}
:deep(.prose th),
:deep(.prose td) {
  border: 1px solid rgb(226 232 240);
  padding: 0.35rem 0.55rem;
  text-align: left;
  vertical-align: top;
  min-width: 4rem;
}
:deep(.prose th) {
  background: rgb(248 250 252);
  font-weight: 700;
  color: rgb(51 65 85);
}
:deep(.prose img),
:deep(.prose iframe) {
  max-width: 100%;
  height: auto;
}
</style>
