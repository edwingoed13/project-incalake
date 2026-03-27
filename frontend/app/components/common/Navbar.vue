<template>
  <!-- Sticky Premium Navbar -->
  <header
    class="fixed top-0 z-[100] w-full transition-all duration-500 border-b"
    :class="[
      isScrolled
        ? 'bg-white/95 backdrop-blur-xl border-slate-200 py-3 shadow-lg'
        : 'bg-white/80 backdrop-blur-md border-slate-100 py-5'
    ]"
  >
    <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
      <!-- Logo Section -->
      <NuxtLink to="/" class="group flex items-center gap-3 active:scale-95 transition-transform">
        <div class="size-10 bg-primary group-hover:rotate-[15deg] transition-transform rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/30">
           <span class="material-symbols-outlined font-bold text-2xl">explore</span>
        </div>
        <div class="flex flex-col">
          <h1
            class="text-xl font-black tracking-tighter uppercase italic leading-none transition-colors text-slate-900"
          >
            Incalake
          </h1>
          <span
            class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 self-start"
          >
            Editorial Voyager
          </span>
        </div>
      </NuxtLink>

      <!-- Center Navigation Links -->
      <nav class="hidden md:flex items-center gap-10">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.path"
          :to="link.path"
          class="text-[11px] font-black uppercase tracking-widest transition-all hover:text-primary relative group text-slate-600"
        >
          {{ link.label }}
          <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all group-hover:w-full"></span>
        </NuxtLink>
      </nav>

      <!-- Right Actions Section -->
      <div class="flex items-center gap-4">
        <button
          class="hidden sm:block text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 active:scale-95 text-slate-500"
        >
          Account
        </button>
        <button class="bg-primary text-white text-xs font-black uppercase tracking-[0.1em] px-7 py-3 rounded-2xl shadow-xl shadow-primary/20 hover:brightness-110 active:scale-95 transition-all">
          Book a Tour
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const isScrolled = ref(false)

const navLinks = [
  { label: 'Destinations', path: '/destinations' },
  { label: 'Experiences', path: '/tours' },
  { label: 'About Us', path: '/about' },
  { label: 'Support', path: '/contact' }
]

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
/* Navbar Transitions & Effects */
</style>
