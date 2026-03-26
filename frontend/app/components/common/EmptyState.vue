<template>
  <div class="flex flex-col items-center justify-center text-center" :class="containerClass">
    <!-- Icon -->
    <div class="mb-4" :class="iconSizeClass">
      <span v-if="icon" class="material-symbols-outlined" :class="iconColorClass" :style="{ fontSize: iconSize }">
        {{ icon }}
      </span>
      <slot v-else name="icon">
        <span class="material-symbols-outlined text-slate-300 dark:text-slate-600" :style="{ fontSize: iconSize }">
          inbox
        </span>
      </slot>
    </div>

    <!-- Title -->
    <h3 class="text-xl md:text-2xl font-black text-primary-light dark:text-primary-dark mb-2">
      <slot name="title">
        {{ title || 'No results found' }}
      </slot>
    </h3>

    <!-- Description -->
    <p class="text-secondary-light dark:text-secondary-dark mb-6 max-w-md">
      <slot name="description">
        {{ description || 'Try adjusting your search or filters to find what you\'re looking for.' }}
      </slot>
    </p>

    <!-- Action Button -->
    <slot name="action">
      <button
        v-if="actionText"
        @click="$emit('action')"
        class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-colors inline-flex items-center gap-2"
      >
        <span class="material-symbols-outlined" v-if="actionIcon">{{ actionIcon }}</span>
        {{ actionText }}
      </button>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  icon?: string
  iconSize?: 'sm' | 'md' | 'lg' | 'xl'
  iconColor?: 'primary' | 'slate' | 'gray'
  title?: string
  description?: string
  actionText?: string
  actionIcon?: string
  fullScreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  iconSize: 'xl',
  iconColor: 'slate',
  fullScreen: false
})

defineEmits<{
  action: []
}>()

const containerClass = computed(() => {
  return props.fullScreen ? 'min-h-screen py-20' : 'py-12 px-4'
})

const iconSize = computed(() => {
  const sizes = {
    sm: '48px',
    md: '64px',
    lg: '80px',
    xl: '96px'
  }
  return sizes[props.iconSize]
})

const iconSizeClass = computed(() => {
  const classes = {
    sm: 'text-5xl',
    md: 'text-6xl',
    lg: 'text-7xl',
    xl: 'text-8xl'
  }
  return classes[props.iconSize]
})

const iconColorClass = computed(() => {
  const colors = {
    primary: 'text-primary',
    slate: 'text-slate-300 dark:text-slate-600',
    gray: 'text-gray-300 dark:text-gray-600'
  }
  return colors[props.iconColor]
})
</script>
