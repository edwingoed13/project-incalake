<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 flex flex-col">
    <!-- Mobile Header -->
    <header class="lg:hidden h-16 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sticky top-0 z-40">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-primary rounded flex items-center justify-center text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path></svg>
        </div>
        <span class="font-bold tracking-tight">Incalake <span class="text-primary">CMS</span></span>
      </div>
      <button @click="isSidebarOpen = !isSidebarOpen" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
      </button>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
      <!-- Sidebar Desktop & Mobile Overlay -->
      <aside
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transform transition-transform duration-300 lg:relative lg:translate-x-0"
        :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      >
        <div class="h-full flex flex-col">
          <!-- Logo Section -->
          <div class="hidden lg:flex items-center gap-3 h-16 px-6 border-b border-slate-200 dark:border-slate-800">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/20">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path></svg>
            </div>
            <span class="font-bold text-lg tracking-tight">Incalake <span class="text-primary">CMS</span></span>
          </div>

          <!-- Navigation -->
          <nav class="flex-1 overflow-y-auto p-4 space-y-1 custom-scrollbar">
            <!-- Dashboard -->
            <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-2">Principal</div>
            <NuxtLink to="/admin/dashboard" @click="closeMobileSidebar" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-sm" :class="isActive('/admin/dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'">
              <span class="material-symbols-outlined text-base">dashboard</span>
              Dashboard
            </NuxtLink>

            <!-- Reservas -->
            <MenuSection
              title="Reservas"
              icon="calendar_month"
              :items="[
                { label: 'Clientes (Web/OTAs)', path: '/admin/bookings', icon: 'people' },
                { label: 'Reviews', path: '/admin/reviews', icon: 'reviews' },
                { label: 'Chatbot', path: '/admin/chatbot', icon: 'smart_toy', badge: 'Próximamente' }
              ]"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- Servicios -->
            <MenuSection
              v-if="authStore.hasPermission('tours.view') || authStore.hasPermission('categories.view') || authStore.hasPermission('languages.view')"
              title="Servicios"
              icon="inventory_2"
              :items="servicesMenuItems"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- Finanzas -->
            <MenuSection
              title="Finanzas"
              icon="payments"
              :items="[
                { label: 'Pagos', path: '/admin/payments', icon: 'credit_card', badge: 'Próximamente' },
                { label: 'Reservas Rápidas (Culqi)', path: '/admin/quick-bookings', icon: 'flash_on', badge: 'Próximamente' },
                { label: 'Facturación', path: '/admin/invoicing', icon: 'receipt_long', badge: 'Próximamente' }
              ]"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- Operaciones -->
            <MenuSection
              title="Operaciones"
              icon="settings_suggest"
              :items="[
                { label: 'Calendario', path: '/admin/calendar', icon: 'calendar_today', badge: 'Próximamente' },
                { label: 'Disponibilidad', path: '/admin/availability', icon: 'event_available', badge: 'Próximamente' },
                { label: 'Proveedores', path: '/admin/suppliers', icon: 'store', badge: 'Próximamente' },
                { label: 'Guías y Recursos', path: '/admin/guides', icon: 'badge', badge: 'Próximamente' }
              ]"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- Marketing -->
            <MenuSection
              title="Marketing"
              icon="campaign"
              :items="[
                { label: 'Analytics', path: '/admin/analytics', icon: 'analytics', badge: 'Próximamente' },
                { label: 'Preguntas Web', path: '/admin/web-questions', icon: 'help', badge: 'Próximamente' },
                { label: 'Galería Web', path: '/admin/web-gallery', icon: 'photo_library', badge: 'Próximamente' },
                { label: 'Reportes', path: '/admin/reports', icon: 'summarize', badge: 'Próximamente' },
                { label: 'Cupones', path: '/admin/coupons', icon: 'local_offer' }
              ]"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- Transporte -->
            <MenuSection
              title="Transporte"
              icon="local_shipping"
              :items="[
                { label: 'Buses', path: '/admin/buses', icon: 'directions_bus', badge: 'Próximamente' },
                { label: 'Aeropuerto', path: '/admin/airport', icon: 'flight', badge: 'Próximamente' },
                { label: 'Traslados', path: '/admin/transfers', icon: 'airport_shuttle', badge: 'Próximamente' }
              ]"
              @closeMobileSidebar="closeMobileSidebar"
            />

            <!-- OTA Manager -->
            <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-4">Integraciones</div>
            <NuxtLink to="/admin/ota-manager" @click="closeMobileSidebar" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-sm" :class="isActive('/admin/ota-manager') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'">
              <span class="material-symbols-outlined text-base">public</span>
              OTA Manager
              <span class="ml-auto text-[9px] bg-amber-500/10 text-amber-600 dark:text-amber-400 px-1.5 py-0.5 rounded-full font-bold">Próximamente</span>
            </NuxtLink>

            <!-- Configuración -->
            <MenuSection
              v-if="authStore.hasPermission('users.view') || authStore.hasPermission('settings.view')"
              title="Configuración"
              icon="settings"
              :items="settingsMenuItems"
              @closeMobileSidebar="closeMobileSidebar"
            />
          </nav>

          <!-- User Footer -->
          <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3 px-2 py-3">
              <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-500 overflow-hidden">
                <img v-if="authStore.user?.avatar" :src="authStore.user.avatar" class="w-full h-full object-cover" />
                <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold truncate">{{ authStore.user?.name || 'Administrador' }}</p>
                <p class="text-xs text-slate-500 truncate">{{ authStore.user?.email || 'admin@incalake.com' }}</p>
              </div>
              <button @click="logout" title="Cerrar sesión" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <!-- Topbar -->
        <header class="hidden lg:flex h-16 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 items-center justify-between px-8 sticky top-0 z-30">
          <div class="flex items-center gap-4">
            <h2 class="font-bold text-lg">{{ pageTitle }}</h2>
          </div>
          <div class="flex items-center gap-4">
             <!-- Search -->
             <div class="relative hidden xl:block">
               <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
               </span>
               <input class="pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all w-64" placeholder="Buscar..." />
             </div>

             <button @click="toggleTheme" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-all">
               <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
               <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
             </button>

             <div class="h-8 w-px bg-slate-200 dark:border-slate-800 ml-2"></div>
             <div class="flex items-center gap-3">
               <div class="text-right hidden sm:block">
                 <p class="text-xs font-bold leading-none">{{ authStore.user?.role || 'Super Admin' }}</p>
                 <p class="text-[10px] text-green-500 font-medium">Online</p>
               </div>
             </div>
          </div>
        </header>

        <!-- Content Content -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 lg:p-8">
           <slot />
        </div>
      </main>

      <!-- Mobile Sidebar Overlay -->
      <div v-if="isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 lg:hidden"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const isDark = ref(false)

// Cargar permisos al iniciar
const loadPermissions = async () => {
  if (!authStore.token) return

  try {
    const config = useRuntimeConfig()
    const response = await fetch(`${config.public.apiUrl}/auth/permissions`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.success && data.data) {
        authStore.setPermissions(data.data.permissions, data.data.role)
      }
    }
  } catch (error) {
    console.error('Error loading permissions:', error)
  }
}

// Inicializar tema desde localStorage
onMounted(() => {
  if (process.client) {
    const savedTheme = localStorage.getItem('theme')
    isDark.value = savedTheme === 'dark'

    // Aplicar tema guardado
    if (isDark.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }

    // Cargar permisos
    loadPermissions()
  }
})

// Computed menu items filtrados por permisos
const servicesMenuItems = computed(() => {
  const items = []

  if (authStore.hasPermission('tours.view')) {
    items.push({ label: 'Tours', path: '/admin/tours', icon: 'tour' })
  }

  items.push({ label: 'Productos', path: '/admin/products', icon: 'shopping_bag', badge: 'Próximamente' })

  if (authStore.hasPermission('categories.view')) {
    items.push({ label: 'Categorías', path: '/admin/categories', icon: 'category' })
  }

  if (authStore.hasPermission('languages.view')) {
    items.push({ label: 'Idiomas', path: '/admin/languages', icon: 'language' })
  }

  items.push({ label: 'Paquetes', path: '/admin/packages', icon: 'card_giftcard', badge: 'Próximamente' })
  items.push({ label: 'Home Page', path: '/admin/pages/home', icon: 'home' })

  return items
})

const settingsMenuItems = computed(() => {
  const items = []

  if (authStore.hasPermission('users.view')) {
    items.push({ label: 'Usuarios y Roles', path: '/admin/users', icon: 'admin_panel_settings' })
  }

  if (authStore.hasPermission('settings.ai')) {
    items.push({ label: 'Traducción IA', path: '/admin/settings/ai-translation', icon: 'translate', badge: 'Nuevo' })
  }

  return items
})

const pageTitle = computed(() => {
  const path = route.path
  if (path === '/admin/dashboard') return 'Dashboard Overview'
  if (path === '/admin/tours') return 'Gestión de Tours'
  if (path.startsWith('/admin/tours/')) return 'Editor de Tour'
  if (path === '/admin/categories') return 'Categorías'
  if (path === '/admin/languages') return 'Idiomas'
  if (path === '/admin/bookings') return 'Reservas Recientes'
  if (path === '/admin/coupons') return 'Cupones y Descuentos'
  if (path === '/admin/users') return 'Usuarios y Roles'
  if (path === '/admin/settings/ai-translation') return 'Configuración de Traducción IA'
  return 'Panel de Control'
})

const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const closeMobileSidebar = () => {
  isSidebarOpen.value = false
}

const toggleTheme = () => {
  isDark.value = !isDark.value

  if (process.client) {
    if (isDark.value) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }
}

const logout = async () => {
  if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
    await authStore.logout()
    await router.push('/login')
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}
</style>
