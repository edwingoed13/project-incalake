<template>
  <div
    class="rounded-xl p-4 md:p-6 border"
    :class="[
      dismissible ? 'relative pr-12' : '',
      typeClasses
    ]"
    role="alert"
  >
    <div class="flex items-start gap-3">
      <!-- Icon -->
      <span class="material-symbols-outlined flex-shrink-0" :class="iconClass">
        {{ iconName }}
      </span>

      <div class="flex-1">
        <!-- Title -->
        <h3 v-if="title" class="font-bold mb-1" :class="titleClass">
          {{ title }}
        </h3>

        <!-- Message -->
        <div :class="messageClass">
          <slot>
            {{ message }}
          </slot>
        </div>

        <!-- Action Button -->
        <button
          v-if="actionText"
          @click="$emit('action')"
          class="mt-3 font-bold text-sm hover:underline"
          :class="actionClass"
        >
          {{ actionText }}
        </button>
      </div>

      <!-- Dismiss Button -->
      <button
        v-if="dismissible"
        @click="$emit('dismiss')"
        class="flex-shrink-0 hover:opacity-70 transition-opacity"
        :class="dismissClass"
        aria-label="Dismiss"
      >
        <span class="material-symbols-outlined text-xl">close</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  type?: 'error' | 'warning' | 'success' | 'info'
  title?: string
  message?: string
  dismissible?: boolean
  actionText?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'error',
  dismissible: false
})

defineEmits<{
  dismiss: []
  action: []
}>()

const iconName = computed(() => {
  const icons = {
    error: 'error',
    warning: 'warning',
    success: 'check_circle',
    info: 'info'
  }
  return icons[props.type]
})

const typeClasses = computed(() => {
  const classes = {
    error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
    warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
    success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
    info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
  }
  return classes[props.type]
})

const iconClass = computed(() => {
  const classes = {
    error: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    success: 'text-green-600 dark:text-green-400',
    info: 'text-blue-600 dark:text-blue-400'
  }
  return classes[props.type]
})

const titleClass = computed(() => {
  const classes = {
    error: 'text-red-900 dark:text-red-100',
    warning: 'text-yellow-900 dark:text-yellow-100',
    success: 'text-green-900 dark:text-green-100',
    info: 'text-blue-900 dark:text-blue-100'
  }
  return classes[props.type]
})

const messageClass = computed(() => {
  const classes = {
    error: 'text-red-700 dark:text-red-300',
    warning: 'text-yellow-700 dark:text-yellow-300',
    success: 'text-green-700 dark:text-green-300',
    info: 'text-blue-700 dark:text-blue-300'
  }
  return classes[props.type] + ' text-sm'
})

const actionClass = computed(() => {
  const classes = {
    error: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    success: 'text-green-600 dark:text-green-400',
    info: 'text-blue-600 dark:text-blue-400'
  }
  return classes[props.type]
})

const dismissClass = computed(() => {
  const classes = {
    error: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    success: 'text-green-600 dark:text-green-400',
    info: 'text-blue-600 dark:text-blue-400'
  }
  return classes[props.type]
})
</script>
