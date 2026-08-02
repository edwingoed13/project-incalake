<script setup lang="ts">
// Shared chrome for the v2 form modals (category/language/review/user/clone):
// UModal wiring + header with tinted icon, title and close button. Each modal
// keeps its own <form> (submit/validation/footer) in the default slot.
withDefaults(defineProps<{
  open: boolean
  title: string
  icon: string
  /** Tailwind max-width class for the modal content. */
  width?: string
  /** Blocks dismissing and the close button while a save is in flight. */
  busy?: boolean
  /** Caps the modal at 90vh; the slot content should scroll itself. */
  scrollable?: boolean
}>(), { width: 'max-w-md', busy: false, scrollable: false })

const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <UModal :open="open" :ui="{ content: width }" :dismissible="!busy" @update:open="(v: boolean) => !v && emit('close')">
    <template #content>
      <div class="bg-default rounded-lg" :class="scrollable ? 'flex flex-col max-h-[90vh]' : ''">
        <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3 shrink-0">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <UIcon :name="icon" class="size-5 text-primary" />
            </div>
            <div class="min-w-0">
              <h2 class="text-lg font-bold">{{ title }}</h2>
              <p v-if="$slots.subtitle" class="text-xs text-muted mt-0.5 truncate"><slot name="subtitle" /></p>
            </div>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" :disabled="busy" @click="emit('close')" />
        </div>
        <slot />
      </div>
    </template>
  </UModal>
</template>
