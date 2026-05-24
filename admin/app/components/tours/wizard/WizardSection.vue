<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  icon?: string
  description?: string
  /** When true the header becomes a toggle and the body collapses. */
  collapsible?: boolean
}>(), { collapsible: false })

// For collapsible mode the parent controls open state via v-model:open
// (so persistence/localStorage stays in the step). Defaults open.
const open = defineModel<boolean>('open', { default: true })
</script>

<template>
  <UCard
    :ui="{
      header: collapsible ? 'p-0' : 'p-4 sm:p-5',
      body: (collapsible && !open) ? 'p-0 sm:p-0' : 'p-4 sm:p-5',
    }"
  >
    <template #header>
      <!-- Collapsible: the whole header is a toggle button -->
      <button
        v-if="collapsible"
        type="button"
        class="w-full p-3 sm:p-4 flex items-center gap-2.5 text-left hover:bg-elevated/40 transition-colors"
        @click="open = !open"
      >
        <UIcon
          name="i-lucide-chevron-down"
          class="size-4 text-muted transition-transform shrink-0"
          :class="{ 'rotate-180': open }"
        />
        <div v-if="icon" class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
          <UIcon :name="icon" class="size-5 text-primary" />
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-base font-bold text-highlighted leading-tight">{{ title }}</h3>
          <p v-if="description" class="text-xs text-muted mt-0.5 leading-snug">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="shrink-0" @click.stop>
          <slot name="actions" />
        </div>
      </button>

      <!-- Static header -->
      <div v-else class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 min-w-0">
          <div v-if="icon" class="size-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <UIcon :name="icon" class="size-5 text-primary" />
          </div>
          <div class="min-w-0">
            <h3 class="text-base font-bold text-highlighted leading-tight">{{ title }}</h3>
            <p v-if="description" class="text-xs text-muted mt-0.5 leading-snug">{{ description }}</p>
          </div>
        </div>
        <div v-if="$slots.actions" class="shrink-0">
          <slot name="actions" />
        </div>
      </div>
    </template>

    <div v-show="!collapsible || open">
      <slot />
    </div>
  </UCard>
</template>
