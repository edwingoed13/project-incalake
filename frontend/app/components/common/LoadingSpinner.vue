<template>
  <div class="flex flex-col items-center justify-center" :class="containerClass">
    <!-- Spinner -->
    <div
      class="animate-spin rounded-full border-b-2"
      :class="[sizeClass, colorClass]"
    ></div>

    <!-- Optional Text -->
    <p v-if="text" class="mt-4 text-sm font-medium" :class="textColorClass">
      {{ text }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'white' | 'slate'
  text?: string
  fullScreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  color: 'primary',
  fullScreen: false
})

const sizeClass = computed(() => {
  const sizes = {
    sm: 'h-6 w-6',
    md: 'h-12 w-12',
    lg: 'h-16 w-16',
    xl: 'h-24 w-24'
  }
  return sizes[props.size]
})

const colorClass = computed(() => {
  const colors = {
    primary: 'border-primary',
    white: 'border-white',
    slate: 'border-slate-600'
  }
  return colors[props.color]
})

const textColorClass = computed(() => {
  const colors = {
    primary: 'text-secondary-light dark:text-secondary-dark',
    white: 'text-white',
    slate: 'text-slate-600 dark:text-slate-400'
  }
  return colors[props.color]
})

const containerClass = computed(() => {
  return props.fullScreen ? 'min-h-screen py-20' : 'py-12'
})
</script>
