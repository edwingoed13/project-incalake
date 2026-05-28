<template>
  <div class="min-h-screen bg-default text-default flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Soft brand accents -->
    <div class="absolute -top-32 -left-32 size-80 rounded-full bg-primary/10 blur-3xl pointer-events-none" />
    <div class="absolute -bottom-32 -right-32 size-96 rounded-full bg-primary/10 blur-3xl pointer-events-none" />
    <slot />
  </div>
</template>

<script setup lang="ts">
// Theme is handled globally by useColorMode() (Nuxt color mode), same as the
// rest of the admin. The old manual `localStorage('theme')` + dark-class toggle
// here fought the global color mode and caused the light/dark inconsistency.
</script>
