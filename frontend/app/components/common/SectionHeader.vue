<script setup lang="ts">
// Section heading with optional kicker label and slider arrows. The arrows
// emit `scroll(±1)` — pair with useSnapScroll's container in the parent.
defineProps<{
  title: string
  label?: string
  showArrows?: boolean
}>()

const emit = defineEmits<{ (e: 'scroll', dir: number): void }>()
const { t } = useI18n()
</script>

<template>
  <div class="flex items-end justify-between gap-4 mb-6 md:mb-10">
    <div class="min-w-0">
      <p v-if="label" class="section-label mb-2">{{ label }}</p>
      <h3 class="section-title">{{ title }}</h3>
      <slot name="subtitle" />
    </div>
    <div v-if="showArrows" class="hidden sm:flex gap-2 shrink-0">
      <button
        :aria-label="t('previous')"
        class="size-11 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all"
        @click="emit('scroll', -1)"
      >
        <Icon name="material-symbols:chevron-left" class="text-lg" />
      </button>
      <button
        :aria-label="t('next')"
        class="size-11 rounded-full border border-slate-200 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all"
        @click="emit('scroll', 1)"
      >
        <Icon name="material-symbols:chevron-right" class="text-lg" />
      </button>
    </div>
    <slot name="right" />
  </div>
</template>
