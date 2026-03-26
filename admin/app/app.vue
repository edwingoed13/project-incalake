<template>
  <div class="relative min-h-screen">
    <div class="mesh-gradient"></div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup lang="ts">
// Los links están en nuxt.config.ts, pero podemos reforzarlos aquí si es necesario
useHead({
  title: 'Incalake CMS - Cloud Panel',
  meta: [
    { name: 'description', content: 'Modern Tourism CMS for Incalake' }
  ]
})
</script>
