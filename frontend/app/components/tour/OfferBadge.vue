<script setup lang="ts">
// The site-wide "% OFF" chip. One component instead of 9 inline variants
// (rounded-md vs -full, 9px vs 10px, bold vs black, green vs custom color).
// - solid: white text on green/custom bg — for use over images.
// - tint:  colored text on a soft tinted bg — for use inside lists/summaries.
const props = withDefaults(defineProps<{
  label?: string              // ready-made text, e.g. "20% OFF"
  discount?: number | string  // or build it from discount + type
  discountType?: string       // 'percentage' | fixed amount
  color?: string | null       // custom offer color (hex) from the admin
  variant?: 'solid' | 'tint'
  size?: 'xs' | 'sm'
}>(), { variant: 'solid', size: 'sm' })

const text = computed(() =>
  props.label || `${props.discount}${props.discountType === 'percentage' ? '%' : ' USD'} OFF`,
)

const style = computed(() => {
  if (!props.color) return undefined
  return props.variant === 'tint'
    ? { backgroundColor: `${props.color}20`, color: props.color }
    : { backgroundColor: props.color }
})
</script>

<template>
  <span
    class="inline-flex items-center gap-0.5 rounded-full font-bold shadow-sm whitespace-nowrap"
    :class="[
      size === 'xs' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-1 text-[10px]',
      variant === 'tint' ? 'bg-green-500/10 text-green-600 shadow-none' : 'bg-green-500 text-white',
    ]"
    :style="style"
  >
    <Icon name="material-symbols:sell-outline" :class="size === 'xs' ? 'text-[10px]' : 'text-xs'" />
    <slot>{{ text }}</slot>
  </span>
</template>
