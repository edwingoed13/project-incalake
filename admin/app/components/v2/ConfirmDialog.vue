<script setup lang="ts">
const { state, accept, cancel } = useConfirm()

const iconBgClass = computed(() => {
  switch (state.value.iconColor) {
    case 'success': return 'bg-success/10'
    case 'error': return 'bg-error/10'
    case 'warning': return 'bg-warning/10'
    case 'info': return 'bg-info/10'
    case 'primary': return 'bg-primary/10'
    default: return 'bg-elevated'
  }
})
const iconColorClass = computed(() => {
  switch (state.value.iconColor) {
    case 'success': return 'text-success'
    case 'error': return 'text-error'
    case 'warning': return 'text-warning'
    case 'info': return 'text-info'
    case 'primary': return 'text-primary'
    default: return 'text-muted'
  }
})
</script>

<template>
  <UModal :open="state.open" :ui="{ content: 'max-w-md' }" :dismissible="!state.loading" @update:open="(v) => !v && cancel()">
    <template #content>
      <div class="bg-default rounded-lg">
        <div class="p-6">
          <div class="flex items-start gap-4">
            <div :class="['size-11 rounded-full flex items-center justify-center shrink-0', iconBgClass]">
              <UIcon :name="state.icon || 'i-lucide-circle-alert'" :class="['size-6', iconColorClass]" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-bold leading-tight">{{ state.title }}</h3>
              <p v-if="state.description" class="text-sm text-muted mt-2 leading-relaxed">{{ state.description }}</p>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" :disabled="state.loading" @click="cancel">
            {{ state.cancelLabel }}
          </UButton>
          <UButton
            :color="state.confirmColor"
            :icon="state.confirmIcon"
            :loading="state.loading"
            @click="accept"
          >
            {{ state.confirmLabel }}
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>
