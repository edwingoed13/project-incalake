<script setup lang="ts">
defineProps<{
  title: string
  icon?: string
  description?: string
}>()
</script>

<template>
  <UCard :ui="{ header: 'p-4 sm:p-5', body: 'p-4 sm:p-5' }">
    <template #header>
      <div class="flex items-center justify-between gap-3 flex-wrap">
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

    <slot />
  </UCard>
</template>
