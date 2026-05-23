<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const toast = useToast()

interface DashboardStats {
  revenue: { value: string; trend: number }
  bookings: { value: number; trend: number }
  pax: { value: string | number; month: number; trend: number }
  tours: { value: number; translations: number; trend: number }
}

interface RecentBooking {
  id: number | string
  booking_code?: string
  customer_name: string
  tour_title?: string
  total?: number | string
  currency?: string
  payment_status?: string
  status?: string
  created_at?: string
}

interface SalesPoint {
  ym: string
  label: string
  year: number
  revenue: number
  bookings: number
}

interface SalesChartResponse {
  series: SalesPoint[]
  totals: { revenue: number; bookings: number; max_revenue: number }
}

const loading = ref(true)
const loadingBookings = ref(true)
const loadingChart = ref(true)
const dashboardData = ref<DashboardStats>({
  revenue: { value: '0.00', trend: 0 },
  bookings: { value: 0, trend: 0 },
  pax: { value: 0, month: 0, trend: 0 },
  tours: { value: 0, translations: 0, trend: 0 },
})
const recentBookings = ref<RecentBooking[]>([])
const salesChart = ref<SalesChartResponse>({
  series: [],
  totals: { revenue: 0, bookings: 0, max_revenue: 0 },
})

const stats = computed(() => [
  {
    key: 'revenue',
    label: 'Ventas del mes',
    value: `$${dashboardData.value.revenue.value}`,
    trend: dashboardData.value.revenue.trend,
    icon: 'i-lucide-dollar-sign',
    bgClass: 'bg-primary/10',
    iconClass: 'text-primary',
    subtitle: 'ingresos confirmados',
  },
  {
    key: 'bookings',
    label: 'Reservas del mes',
    value: dashboardData.value.bookings.value,
    trend: dashboardData.value.bookings.trend,
    icon: 'i-lucide-shopping-cart',
    bgClass: 'bg-success/10',
    iconClass: 'text-success',
    subtitle: 'reservas confirmadas',
  },
  {
    key: 'pax',
    label: 'Total pasajeros',
    value: dashboardData.value.pax.value,
    trend: dashboardData.value.pax.trend,
    icon: 'i-lucide-users',
    bgClass: 'bg-info/10',
    iconClass: 'text-info',
    subtitle: `${dashboardData.value.pax.month} este mes`,
  },
  {
    key: 'tours',
    label: 'Tours publicados',
    value: dashboardData.value.tours.value,
    trend: dashboardData.value.tours.trend,
    icon: 'i-lucide-map',
    bgClass: 'bg-warning/10',
    iconClass: 'text-warning',
    subtitle: `${dashboardData.value.tours.translations} traducciones`,
  },
])

const getInitials = (name: string) => {
  if (!name) return '??'
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const formatTrend = (trend: number) => `${trend >= 0 ? '+' : ''}${trend}%`
const trendColor = (trend: number): 'success' | 'error' | 'neutral' => {
  if (trend > 0) return 'success'
  if (trend < 0) return 'error'
  return 'neutral'
}
const trendIcon = (trend: number) =>
  trend > 0 ? 'i-lucide-trending-up' : trend < 0 ? 'i-lucide-trending-down' : 'i-lucide-minus'

const fetchStats = async () => {
  try {
    const data = await $fetch<DashboardStats>(`${apiUrl}/dashboard/stats`)
    dashboardData.value = data
  } catch (error) {
    console.error('Error loading dashboard stats:', error)
    toast.add({
      title: 'Error al cargar estadísticas',
      description: 'No se pudieron obtener los datos del dashboard.',
      icon: 'i-lucide-alert-triangle',
      color: 'error',
    })
  } finally {
    loading.value = false
  }
}

const fetchRecentBookings = async () => {
  try {
    const data = await $fetch<RecentBooking[]>(`${apiUrl}/dashboard/recent-bookings`)
    recentBookings.value = data || []
  } catch (error) {
    console.error('Error loading recent bookings:', error)
  } finally {
    loadingBookings.value = false
  }
}

const fetchSalesChart = async () => {
  try {
    const data = await $fetch<SalesChartResponse>(`${apiUrl}/dashboard/sales-chart`)
    salesChart.value = data
  } catch (error) {
    console.error('Error loading sales chart:', error)
  } finally {
    loadingChart.value = false
  }
}

const refreshAll = async () => {
  loading.value = true
  loadingBookings.value = true
  loadingChart.value = true
  await Promise.all([fetchStats(), fetchRecentBookings(), fetchSalesChart()])
  toast.add({
    title: 'Dashboard actualizado',
    icon: 'i-lucide-refresh-cw',
    color: 'success',
  })
}

onMounted(() => {
  fetchStats()
  fetchRecentBookings()
  fetchSalesChart()
})

const formatCurrency = (n: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n)

const barHeight = (revenue: number): number => {
  const max = salesChart.value.totals.max_revenue
  if (!max) return 4
  // Cap at 85% so the hover tooltip above the tallest bar has headroom.
  return Math.max(4, Math.round((revenue / max) * 85))
}

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Buenos días'
  if (hour < 19) return 'Buenas tardes'
  return 'Buenas noches'
})
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Dashboard">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading || loadingBookings"
            @click="refreshAll"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-plus" to="/admin/v2/tours/new/edit">Nuevo tour</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-6">
        <!-- Welcome -->
        <div class="flex items-end justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-2xl font-bold">
              {{ greeting }}, {{ authStore.user?.name || 'Admin' }} 👋
            </h2>
            <p class="text-sm text-muted mt-1">Resumen de actividad de Incalake este mes</p>
          </div>
          <UBadge color="neutral" variant="subtle" icon="i-lucide-clock" size="lg">
            {{ new Date().toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
          </UBadge>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <UCard v-for="stat in stats" :key="stat.key" :ui="{ body: 'p-5' }">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-2 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ stat.label }}</p>
                <USkeleton v-if="loading" class="h-9 w-28" />
                <p v-else class="text-3xl font-bold tabular-nums truncate">{{ stat.value }}</p>
                <div class="flex items-center gap-2 flex-wrap">
                  <UBadge v-if="!loading" :color="trendColor(stat.trend)" variant="subtle" size="sm" :icon="trendIcon(stat.trend)">
                    {{ formatTrend(stat.trend) }}
                  </UBadge>
                  <span v-if="stat.subtitle && !loading" class="text-xs text-muted truncate">{{ stat.subtitle }}</span>
                </div>
              </div>
              <div :class="['size-11 rounded-xl flex items-center justify-center shrink-0', stat.bgClass]">
                <UIcon :name="stat.icon" :class="['size-6', stat.iconClass]" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Chart + Recent bookings -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <UCard class="lg:col-span-2" :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                  <h3 class="font-semibold">Ventas mensuales</h3>
                  <p class="text-xs text-muted mt-0.5">Últimos 12 meses · reservas pagadas</p>
                </div>
                <div class="flex items-center gap-2">
                  <UBadge color="success" variant="subtle" icon="i-lucide-dollar-sign" size="md">
                    {{ formatCurrency(salesChart.totals.revenue) }}
                  </UBadge>
                  <UBadge color="primary" variant="subtle" icon="i-lucide-shopping-cart" size="md">
                    {{ salesChart.totals.bookings }} reservas
                  </UBadge>
                </div>
              </div>
            </template>

            <div class="h-72 flex flex-col">
              <div v-if="loadingChart" class="flex-1 flex items-end justify-around gap-2 px-2">
                <USkeleton v-for="i in 12" :key="i" class="flex-1" :style="{ height: `${30 + (i * 7) % 60}%` }" />
              </div>

              <div v-else class="flex-1 flex items-end justify-around gap-2 px-2 border-b border-default">
                <div
                  v-for="point in salesChart.series"
                  :key="point.ym"
                  class="flex-1 h-full group relative cursor-pointer flex items-end"
                >
                  <!-- invisible hover catcher so the whole column is hoverable -->
                  <div class="absolute inset-0" />
                  <!-- bar (solid primary, no track behind it) -->
                  <div
                    class="relative w-full rounded-t-md transition-all duration-300"
                    :class="point.revenue > 0
                      ? 'bg-primary group-hover:brightness-110 shadow-sm'
                      : 'bg-muted/30 group-hover:bg-muted/50'"
                    :style="{ height: `${barHeight(point.revenue)}%` }"
                  >
                    <!-- tooltip anchored to THIS bar's top edge -->
                    <div
                      class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:block bg-default border border-default rounded-lg shadow-lg px-2.5 py-1.5 text-xs z-20 pointer-events-none whitespace-nowrap"
                    >
                      <p class="font-bold">{{ point.label }} {{ point.year }}</p>
                      <p class="text-success tabular-nums">{{ formatCurrency(point.revenue) }}</p>
                      <p class="text-muted tabular-nums">{{ point.bookings }} reservas</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex justify-around mt-2 text-[10px] text-muted font-medium px-2">
                <span v-for="point in salesChart.series" :key="point.ym" class="flex-1 text-center">{{ point.label }}</span>
              </div>
            </div>
          </UCard>

          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="font-semibold flex items-center gap-2">
                  <span class="size-2 bg-success rounded-full animate-pulse" />
                  Reservas recientes
                </h3>
                <UButton variant="link" size="xs" to="/admin/v2/bookings" trailing-icon="i-lucide-arrow-right">
                  Ver todas
                </UButton>
              </div>
            </template>

            <div v-if="loadingBookings" class="space-y-4">
              <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                <USkeleton class="size-10 rounded-full" />
                <div class="flex-1 space-y-2">
                  <USkeleton class="h-3 w-32" />
                  <USkeleton class="h-2 w-24" />
                </div>
              </div>
            </div>

            <div v-else-if="recentBookings.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
              <UIcon name="i-lucide-receipt" class="size-10 text-muted mb-2" />
              <p class="text-sm text-muted">No hay reservas recientes</p>
            </div>

            <div v-else class="space-y-3">
              <NuxtLink
                v-for="b in recentBookings"
                :key="b.id"
                :to="`/admin/v2/bookings?code=${b.booking_code || ''}`"
                class="flex items-center gap-3 p-2 -mx-2 rounded-lg hover:bg-elevated transition-colors group"
              >
                <UAvatar
                  :alt="b.customer_name"
                  :text="getInitials(b.customer_name)"
                  size="md"
                  :ui="{ root: 'bg-primary/10 text-primary' }"
                />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">
                    {{ b.customer_name || 'Sin nombre' }}
                  </p>
                  <p class="text-xs text-muted truncate">{{ b.tour_title || 'Tour' }}</p>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-sm font-bold tabular-nums">{{ b.currency || 'USD' }} {{ parseFloat(String(b.total || 0)).toFixed(2) }}</p>
                  <UBadge color="success" variant="subtle" size="sm">Pagado</UBadge>
                </div>
              </NuxtLink>
            </div>
          </UCard>
        </div>

        <!-- Quick actions -->
        <UCard :ui="{ body: 'p-5' }">
          <template #header>
            <h3 class="font-semibold">Accesos rápidos</h3>
          </template>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <UButton block color="neutral" variant="outline" icon="i-lucide-plus-circle" size="lg" to="/admin/v2/tours/new/edit">
              Crear tour
            </UButton>
            <UButton block color="neutral" variant="outline" icon="i-lucide-calendar" size="lg" to="/admin/v2/bookings">
              Reservas
            </UButton>
            <UButton block color="neutral" variant="outline" icon="i-lucide-tags" size="lg" to="/admin/v2/categories">
              Categorías
            </UButton>
            <UButton block color="neutral" variant="outline" icon="i-lucide-star" size="lg" to="/admin/v2/reviews">
              Reseñas
            </UButton>
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>
</template>
