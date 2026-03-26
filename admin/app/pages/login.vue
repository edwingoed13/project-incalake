<template>
  <div :class="{ dark: isDark }" class="w-full absolute inset-0">
    <div class="bg-background-light dark:bg-background-dark min-h-screen flex flex-col transition-colors duration-300 w-full relative text-slate-900 dark:text-slate-100 font-display">

    <!-- Header / Toolbar -->
    <header class="w-full px-6 py-4 flex justify-between items-center fixed top-0 z-50">
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/20">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path></svg>
        </div>
        <span class="font-bold text-xl tracking-tight">Incalake <span class="text-primary">CMS</span></span>
      </div>
      <div class="flex items-center gap-3">
        <button @click="toggleTheme" class="p-2.5 rounded-xl bg-slate-200/50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-all">
          <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-6 relative overflow-hidden">
      <!-- Background Orbs -->
      <div class="absolute top-1/4 -left-20 w-72 h-72 bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-primary/10 rounded-full blur-[120px] pointer-events-none"></div>
      
      <div class="w-full max-w-[460px] relative z-10">
        <div class="glass-card rounded-2xl p-8 md:p-10 shadow-2xl bg-white/70 dark:bg-white/5 border border-primary/10 dark:border-white/10 backdrop-blur-xl">
          <!-- Heading -->
          <div class="mb-10 text-center md:text-left">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Welcome back</h1>
            <p class="text-slate-500 dark:text-slate-400">Acceder al Panel de Control</p>
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="space-y-6">
            
            <!-- Email Field -->
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-700 dark:text-slate-300 ml-1">Email Address</label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <input 
                  type="email" 
                  v-model="email"
                  class="w-full pl-11 pr-4 py-3.5 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 disabled:opacity-50" 
                  placeholder="admin@incalake.com" 
                  required
                  :disabled="isLoading"
                />
              </div>
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
              <div class="flex justify-between items-center px-1">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                <a class="text-xs font-semibold text-primary hover:underline" href="#">Forgot password?</a>
              </div>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <input 
                  type="password" 
                  v-model="password"
                  class="w-full pl-11 pr-12 py-3.5 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 disabled:opacity-50" 
                  placeholder="••••••••" 
                  required
                  :disabled="isLoading"
                />
              </div>
            </div>

            <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-3 rounded-lg text-center font-medium">
              {{ errorMsg }}
            </div>

            <!-- Remember Me -->
            <div class="flex items-center gap-2 px-1">
              <input type="checkbox" id="remember" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary/30 bg-transparent" />
              <label for="remember" class="text-sm text-slate-600 dark:text-slate-400 select-none">Keep me signed in</label>
            </div>

            <!-- Action Button -->
            <button 
              type="submit" 
              class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/25 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isLoading"
            >
              <span v-if="isLoading">Validando...</span>
              <template v-else>
                Entrar
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
              </template>
            </button>
          </form>

        </div>

        <!-- Footer Links -->
        <p class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400">
          Problemas de acceso? <a class="text-primary font-bold hover:underline" href="#">Soporte</a>
        </p>
      </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="w-full py-6 px-6 text-center text-xs text-slate-400 dark:text-slate-600">
      © 2026 Incalake CMS. All rights reserved. Built for modern enterprise tourism management.
    </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '~/composables/useAuth'

definePageMeta({ layout: 'auth' })

const { login } = useAuth()
const router = useRouter()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const isDark = ref(true)

onMounted(() => {
  // Configurar modo oscuro por defecto o según prefiera el sistema
  if (document) {
     document.documentElement.classList.add('dark')
  }
})

const toggleTheme = () => {
  isDark.value = !isDark.value
  if(isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const handleLogin = async () => {
  errorMsg.value = ''
  isLoading.value = true

  try {
     await login(email.value, password.value)
     router.push('/admin/dashboard')
  } catch(e: any) {
     errorMsg.value = e.message || 'Error desconocido al validar.'
  } finally {
     isLoading.value = false
  }
}
</script>

