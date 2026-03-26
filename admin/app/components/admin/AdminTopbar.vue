<template>
  <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-[60]">
    <div class="flex items-center gap-4">
      <!-- Logo Section -->
      <NuxtLink to="/admin/dashboard" class="flex items-center gap-2 pr-4 border-r border-slate-200 dark:border-slate-800 group">
        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/20 group-hover:scale-110 transition-transform">
          <span class="material-symbols-outlined text-xl">flag</span>
        </div>
        <span class="font-bold text-slate-800 dark:text-white tracking-tight">
          Incalake <span class="text-primary">CMS</span>
        </span>
      </NuxtLink>

      <!-- Page Title & Subtitle -->
      <div class="flex flex-col">
        <h2 class="font-bold text-sm text-slate-800 dark:text-slate-100 leading-none">{{ title }}</h2>
        <p v-if="wizardStore?.basicInfo?.title" class="text-[10px] text-slate-400 font-medium mt-1">
          Drafting: <span class="text-primary font-bold">{{ wizardStore.basicInfo.title }}</span>
        </p>
        <p v-else-if="subtitle" class="text-[10px] text-slate-400 font-medium mt-1">{{ subtitle }}</p>
      </div>
    </div>

    <div class="flex items-center gap-4">
      <!-- Search Input -->
      <div class="relative hidden sm:block">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
          <span class="material-symbols-outlined text-lg">search</span>
        </span>
        <input 
          class="pl-10 pr-4 py-1.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all w-64 text-slate-600 dark:text-slate-300 placeholder:text-slate-400" 
          placeholder="Buscar..." 
        />
      </div>

      <!-- Theme Toggle -->
      <button @click="toggleTheme" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-all">
        <span v-if="isDark" class="material-symbols-outlined">light_mode</span>
        <span v-else class="material-symbols-outlined">dark_mode</span>
      </button>

      <!-- Divider -->
      <div class="h-8 w-px bg-slate-200 dark:bg-slate-800"></div>

      <!-- User Profile Info -->
      <div class="flex items-center gap-3">
        <div class="text-right">
          <p class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-none">Super Admin</p>
          <p class="text-[10px] text-green-500 font-bold uppercase tracking-wider">Online</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useTourWizardStore } from '~/stores/tourWizard'

defineProps({
  title: {
    type: String,
    default: 'Gestión de Tours'
  },
  subtitle: {
    type: String,
    default: ''
  }
})

const wizardStore = useTourWizardStore()
const isDark = ref(true)

const toggleTheme = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
})
</script>
