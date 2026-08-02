<script setup lang="ts">
// Star row shared by ratings and review cards. With `total` the row length is
// fixed and the unfilled tail greys out (e.g. 4.2 of 5); without it, exactly
// `value` filled stars render (review-card pattern).
withDefaults(defineProps<{
  value: number
  total?: number
  size?: 'xs' | 'sm'
}>(), { size: 'sm' })
</script>

<template>
  <span class="inline-flex items-center gap-0.5">
    <Icon
      v-for="i in (total || Math.max(0, Math.round(value)))"
      :key="i"
      name="material-symbols:star"
      :class="[
        size === 'xs' ? 'text-xs' : 'text-sm',
        !total || i <= Math.round(value) ? 'text-yellow-400' : 'text-slate-300',
      ]"
    />
  </span>
</template>
