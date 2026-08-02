<script setup lang="ts">
// KPI card shared by the v2 dashboard, bookings and reviews pages — the same
// label/value/icon-in-tinted-square recipe was inlined in each grid.
withDefaults(defineProps<{
  label: string
  value: string | number
  icon: string
  bgClass?: string
  iconClass?: string
}>(), { bgClass: 'bg-primary/10', iconClass: 'text-primary' })
</script>

<template>
  <UCard :ui="{ body: 'p-5' }">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ label }}</p>
        <p class="text-2xl xl:text-3xl font-bold tabular-nums mt-2 truncate">{{ value }}</p>
      </div>
      <div :class="['size-11 rounded-xl flex items-center justify-center shrink-0', bgClass]">
        <UIcon :name="icon" :class="['size-6', iconClass]" />
      </div>
    </div>
  </UCard>
</template>
