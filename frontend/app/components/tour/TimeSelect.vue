<script setup lang="ts">
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
} from '@headlessui/vue'
import { ClockIcon, ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

interface Option {
  value: string
  label: string
}

const props = withDefaults(defineProps<{
  modelValue: string
  options: Option[]
  placeholder?: string
}>(), {
  placeholder: 'Select time',
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
}>()

const selected = computed<string>({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const selectedLabel = computed(() => {
  const match = props.options.find(o => o.value === props.modelValue)
  return match?.label || props.placeholder
})
</script>

<template>
  <Listbox v-model="selected">
    <div class="relative">
      <ListboxButton
        class="relative w-full pl-10 pr-9 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-left text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary min-h-[48px]"
      >
        <ClockIcon class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-slate-400" aria-hidden="true" />
        <span :class="modelValue ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400'">
          {{ selectedLabel }}
        </span>
        <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 size-4 text-slate-400" aria-hidden="true" />
      </ListboxButton>

      <transition
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <ListboxOptions
          class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white dark:bg-slate-800 py-1 text-sm shadow-lg ring-1 ring-black/5 focus:outline-none border border-slate-200 dark:border-slate-700"
        >
          <ListboxOption
            v-for="opt in options"
            :key="opt.value"
            v-slot="{ active, selected: isSelected }"
            :value="opt.value"
            as="template"
          >
            <li
              :class="[
                'relative cursor-pointer select-none pl-9 pr-4 py-2.5 min-h-[44px] flex items-center',
                active ? 'bg-primary/10 text-primary' : 'text-slate-700 dark:text-slate-200',
              ]"
            >
              <CheckIcon
                v-if="isSelected"
                class="absolute left-2.5 size-4 text-primary"
                aria-hidden="true"
              />
              <span :class="isSelected ? 'font-bold' : 'font-medium'">{{ opt.label }}</span>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
  </Listbox>
</template>
