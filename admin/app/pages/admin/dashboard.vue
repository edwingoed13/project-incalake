<template>
  <div>
    <!-- Welcome Section -->
    <div class="mb-8">
      <h3 class="text-2xl font-bold">¡Hola de nuevo, {{ authStore.user?.name || 'Admin' }}! 👋</h3>
      <p class="text-slate-500 dark:text-slate-400">Aquí tienes un resumen de lo que ha pasado este mes en Incalake.</p>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      <div v-for="stat in statsCards" :key="stat.title" class="glass-card p-6 rounded-2xl shadow-sm hover:translate-y-[-4px] hover:shadow-xl hover:shadow-primary/10 transition-all group">
         <div class="flex items-center justify-between mb-4">
           <div :class="stat.colorClass" class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-[0_8px_16px_rgba(0,0,0,0.1)] group-hover:scale-110 transition-transform">
             <component :is="stat.icon" class="w-6 h-6 stroke-[1.5]" />
           </div>
           <span v-if="!loading" :class="stat.trend >= 0 ? 'text-green-500 bg-green-500/10' : 'text-red-500 bg-red-500/10'" class="text-xs font-bold px-2 py-1 rounded-lg">
             {{ stat.trend >= 0 ? '+' : '' }}{{ stat.trend }}%
           </span>
         </div>
         <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">{{ stat.title }}</p>
         <h4 v-if="!loading" class="text-2xl font-bold mt-1 tabular-nums">{{ stat.value }}</h4>
         <div v-else class="h-8 w-24 bg-slate-200 dark:bg-slate-700 rounded animate-pulse mt-1"></div>
         <p v-if="stat.subtitle" class="text-xs text-slate-400 mt-1">{{ stat.subtitle }}</p>
      </div>
    </div>

    <!-- Charts / Main Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 glass-card rounded-2xl p-6 h-96 flex flex-col items-center justify-center text-slate-400 border-dashed">
         <span class="material-symbols-outlined text-5xl mb-4 opacity-20">analytics</span>
         <p class="text-sm">Gráfico de Ventas Mensuales (Próximamente)</p>
         <div class="mt-4 flex gap-2">
           <div v-for="i in 12" :key="i" class="w-4 bg-primary/20 rounded-t-lg" :style="{ height: Math.random() * 100 + 'px' }"></div>
         </div>
      </div>

      <div class="glass-card rounded-2xl p-6 flex flex-col">
         <h5 class="font-bold mb-6 flex items-center gap-2">
            <span class="w-2 h-2 bg-primary rounded-full animate-ping"></span>
            Reservas Recientes
         </h5>

         <!-- Loading state -->
         <div v-if="loadingBookings" class="space-y-6">
           <div v-for="i in 4" :key="i" class="flex items-center gap-4">
             <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 animate-pulse"></div>
             <div class="flex-1">
               <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded animate-pulse mb-1"></div>
               <div class="h-3 w-24 bg-slate-200 dark:bg-slate-700 rounded animate-pulse"></div>
             </div>
           </div>
         </div>

         <!-- Empty state -->
         <div v-else-if="recentBookings.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-400">
           <span class="material-symbols-outlined text-4xl mb-2 opacity-30">receipt_long</span>
           <p class="text-sm">No hay reservas confirmadas aún</p>
         </div>

         <!-- Bookings list -->
         <div v-else class="space-y-5">
            <div v-for="booking in recentBookings" :key="booking.id" class="flex items-center gap-4 group">
               <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-xs text-primary group-hover:bg-primary group-hover:text-white transition-all">
                 {{ getInitials(booking.customer_name) }}
               </div>
               <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">{{ booking.customer_name }}</p>
                  <p class="text-xs text-slate-500 truncate">{{ booking.tour_title || 'Tour' }}</p>
               </div>
               <div class="text-right text-xs">
                  <p class="font-bold tabular-nums">${{ parseFloat(booking.total || 0).toFixed(2) }}</p>
                  <p class="text-green-500 font-medium">Pagado</p>
               </div>
            </div>
         </div>
         <NuxtLink to="/admin/bookings" class="mt-auto w-full py-3 bg-primary/5 hover:bg-primary text-primary hover:text-white rounded-xl text-sm font-bold transition-all flex items-center justify-center">Ver todas las reservas</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, markRaw } from 'vue'
import { useAuthStore } from '~/stores/auth'
import {
  CurrencyDollarIcon,
  ShoppingCartIcon,
  UserGroupIcon,
  MapIcon
} from '@heroicons/vue/24/outline'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl

const loading = ref(true)
const loadingBookings = ref(true)
const recentBookings = ref<any[]>([])

const dashboardData = ref({
  revenue: { value: '0.00', trend: 0 },
  bookings: { value: 0, trend: 0 },
  pax: { value: 0, month: 0, trend: 0 },
  tours: { value: 0, translations: 0, trend: 0 },
})

const statsCards = computed(() => [
  {
    title: 'Ventas del Mes',
    value: `$${dashboardData.value.revenue.value}`,
    trend: dashboardData.value.revenue.trend,
    icon: markRaw(CurrencyDollarIcon),
    colorClass: 'bg-indigo-600',
    subtitle: null
  },
  {
    title: 'Reservas del Mes',
    value: dashboardData.value.bookings.value,
    trend: dashboardData.value.bookings.trend,
    icon: markRaw(ShoppingCartIcon),
    colorClass: 'bg-emerald-600',
    subtitle: 'reservas confirmadas'
  },
  {
    title: 'Total Pasajeros',
    value: dashboardData.value.pax.value,
    trend: dashboardData.value.pax.trend,
    icon: markRaw(UserGroupIcon),
    colorClass: 'bg-violet-600',
    subtitle: `${dashboardData.value.pax.month} este mes`
  },
  {
    title: 'Tours Publicados',
    value: dashboardData.value.tours.value,
    trend: dashboardData.value.tours.trend,
    icon: markRaw(MapIcon),
    colorClass: 'bg-amber-600',
    subtitle: `${dashboardData.value.tours.translations} traducciones`
  },
])

const getInitials = (name: string) => {
  if (!name) return '??'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const fetchStats = async () => {
  try {
    const data: any = await $fetch(`${apiUrl}/dashboard/stats`)
    dashboardData.value = data
  } catch (error) {
    console.error('Error loading dashboard stats:', error)
  } finally {
    loading.value = false
  }
}

const fetchRecentBookings = async () => {
  try {
    const data: any = await $fetch(`${apiUrl}/dashboard/recent-bookings`)
    recentBookings.value = data
  } catch (error) {
    console.error('Error loading recent bookings:', error)
  } finally {
    loadingBookings.value = false
  }
}

onMounted(() => {
  fetchStats()
  fetchRecentBookings()
})
</script>
