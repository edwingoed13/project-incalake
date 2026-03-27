<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex items-center justify-center p-4">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

// Inicializar tema desde localStorage
onMounted(() => {
  if (process.client) {
    const savedTheme = localStorage.getItem('theme')

    // Solo aplicar dark mode si el usuario lo guardó
    if (savedTheme === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }
})
</script>
