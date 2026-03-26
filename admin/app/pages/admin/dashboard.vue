<template>
  <div>
    <!-- Welcome Section -->
    <div class="mb-8">
      <h3 class="text-2xl font-bold">¡Hola de nuevo, {{ authStore.user?.name || 'Admin' }}! 👋</h3>
      <p class="text-slate-500 dark:text-slate-400">Aquí tienes un resumen de lo que ha pasado hoy en Incalake.</p>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      <div v-for="stat in stats" :key="stat.title" class="glass-card p-6 rounded-2xl shadow-sm hover:translate-y-[-4px] hover:shadow-xl hover:shadow-primary/10 transition-all group">
         <div class="flex items-center justify-between mb-4">
           <div :class="stat.colorClass" class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-[0_8px_16px_rgba(0,0,0,0.1)] group-hover:scale-110 transition-transform">
             <component :is="stat.icon" class="w-6 h-6 stroke-[1.5]" />
           </div>
           <span :class="stat.trend > 0 ? 'text-green-500 bg-green-500/10' : 'text-red-500 bg-red-500/10'" class="text-xs font-bold px-2 py-1 rounded-lg">
             {{ stat.trend > 0 ? '+' : '' }}{{ stat.trend }}%
           </span>
         </div>
         <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">{{ stat.title }}</p>
         <h4 class="text-2xl font-bold mt-1 tabular-nums">{{ stat.value }}</h4>
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
         <div class="space-y-6">
            <div v-for="i in 4" :key="i" class="flex items-center gap-4 group">
               <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-xs text-primary group-hover:bg-primary group-hover:text-white transition-all">
                 CL
               </div>
               <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">Cliente #{{ 1024 + i }}</p>
                  <p class="text-xs text-slate-500">Tour Montaña 7 Colores</p>
               </div>
               <div class="text-right text-xs">
                  <p class="font-bold tabular-nums">$129.00</p>
                  <p class="text-green-500 font-medium">Pagado</p>
               </div>
            </div>
         </div>
         <NuxtLink to="/admin/tours" class="mt-auto w-full py-3 bg-primary/5 hover:bg-primary text-primary hover:text-white rounded-xl text-sm font-bold transition-all flex items-center justify-center">Ver todos los tours</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { markRaw } from 'vue'
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

const stats = [
  { title: 'Ventas Totales', value: '$24,500', trend: 12, icon: markRaw(CurrencyDollarIcon), colorClass: 'bg-indigo-600' },
  { title: 'Reservas Nuevas', value: '156', trend: 4.5, icon: markRaw(ShoppingCartIcon), colorClass: 'bg-emerald-600' },
  { title: 'Clientes Activos', value: '1,204', trend: -2.4, icon: markRaw(UserGroupIcon), colorClass: 'bg-violet-600' },
  { title: 'Tours Destacados', value: '42', trend: 8.1, icon: markRaw(MapIcon), colorClass: 'bg-amber-600' },
]
</script>
