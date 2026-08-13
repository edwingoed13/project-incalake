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
      <Icon :name="msIcon(iconName)" class="flex-shrink-0 text-2xl" :class="iconClass" />

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
        <Icon name="material-symbols:close" class="text-xl" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { msIcon } from '~/utils/icons'

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
    error: 'bg-red-50 border-red-200',
    warning: 'bg-yellow-50 border-yellow-200',
    success: 'bg-green-50 border-green-200',
    info: 'bg-blue-50 border-blue-200'
  }
  return classes[props.type]
})

const iconClass = computed(() => {
  const classes = {
    error: 'text-red-600',
    warning: 'text-yellow-600',
    success: 'text-green-600',
    info: 'text-blue-600'
  }
  return classes[props.type]
})

const titleClass = computed(() => {
  const classes = {
    error: 'text-red-900',
    warning: 'text-yellow-900',
    success: 'text-green-900',
    info: 'text-blue-900'
  }
  return classes[props.type]
})

const messageClass = computed(() => {
  const classes = {
    error: 'text-red-700',
    warning: 'text-yellow-700',
    success: 'text-green-700',
    info: 'text-blue-700'
  }
  return classes[props.type] + ' text-sm'
})

const actionClass = computed(() => {
  const classes = {
    error: 'text-red-600',
    warning: 'text-yellow-600',
    success: 'text-green-600',
    info: 'text-blue-600'
  }
  return classes[props.type]
})

const dismissClass = computed(() => {
  const classes = {
    error: 'text-red-600',
    warning: 'text-yellow-600',
    success: 'text-green-600',
    info: 'text-blue-600'
  }
  return classes[props.type]
})
</script>
