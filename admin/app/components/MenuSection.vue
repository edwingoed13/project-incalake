<template>
  <div class="mb-1">
    <!-- Section Header (Collapsible) -->
    <button
      @click="toggleSection"
      class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-sm hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 group"
    >
      <span class="material-symbols-outlined text-base">{{ icon }}</span>
      <span class="flex-1 text-left font-semibold">{{ title }}</span>
      <span
        class="material-symbols-outlined text-base transition-transform duration-200"
        :class="isExpanded ? 'rotate-180' : ''"
      >
        expand_more
      </span>
    </button>

    <!-- Section Items (Collapsible Content) -->
    <transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-screen"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 max-h-screen"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="isExpanded" class="mt-1 space-y-0.5 ml-3 pl-3 border-l-2 border-slate-200 dark:border-slate-800">
        <NuxtLink
          v-for="item in items"
          :key="item.path"
          :to="item.path"
          @click="closeMobileSidebar"
          class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs relative group/item"
          :class="isItemActive(item.path)
            ? 'bg-primary/10 text-primary font-semibold'
            : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'"
        >
          <span class="material-symbols-outlined text-sm">{{ item.icon }}</span>
          <span class="flex-1">{{ item.label }}</span>

          <!-- Badge -->
          <span
            v-if="item.badge"
            class="text-[8px] px-1.5 py-0.5 rounded-full font-bold"
            :class="getBadgeClass(item.badge)"
          >
            {{ item.badge }}
          </span>
        </NuxtLink>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

interface MenuItem {
  label: string
  path: string
  icon: string
  badge?: string
}

interface Props {
  title: string
  icon: string
  items: MenuItem[]
}

const props = defineProps<Props>()
const emit = defineEmits(['closeMobileSidebar'])

const route = useRoute()
const isExpanded = ref(isAnyItemActive())

function isAnyItemActive() {
  return props.items.some(item => route.path === item.path || route.path.startsWith(item.path + '/'))
}

const isItemActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const toggleSection = () => {
  isExpanded.value = !isExpanded.value
}

const closeMobileSidebar = () => {
  emit('closeMobileSidebar')
}

const getBadgeClass = (badgeText: string) => {
  if (badgeText === 'Nuevo') {
    return 'bg-green-500/10 text-green-600 dark:text-green-400'
  } else if (badgeText === 'Próximamente') {
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
  } else if (badgeText === 'Beta') {
    return 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
  }
  return 'bg-slate-500/10 text-slate-600 dark:text-slate-400'
}

// Watch for route changes to auto-expand sections
watch(() => route.path, () => {
  if (isAnyItemActive() && !isExpanded.value) {
    isExpanded.value = true
  }
})
</script>
