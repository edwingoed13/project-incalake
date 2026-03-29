<template>
  <div>
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Reservas</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestión de reservas de clientes</p>
      </div>
      <div class="flex gap-3">
        <button @click="loadBookings" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
          <span class="material-symbols-outlined text-base mr-2 align-middle">refresh</span>
          Actualizar
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 mb-6 border border-slate-200 dark:border-slate-700">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Buscar</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Código, nombre, email..."
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
            @input="debouncedSearch"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
            @change="loadBookings"
          >
            <option value="">Todos</option>
            <option value="pending">Pendiente</option>
            <option value="confirmed">Confirmado</option>
            <option value="cancelled">Cancelado</option>
            <option value="completed">Completado</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Método de Pago</label>
          <select
            v-model="filters.payment_method"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
            @change="loadBookings"
          >
            <option value="">Todos</option>
            <option value="culqi">Culqi</option>
            <option value="paypal">PayPal</option>
            <option value="cash">Efectivo</option>
            <option value="transfer">Transferencia</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha</label>
          <input
            v-model="filters.date"
            type="date"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
            @change="loadBookings"
          />
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Reservas</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">receipt_long</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Confirmadas</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.confirmed }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pendientes</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.pending }}</p>
          </div>
          <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">schedule</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Ingresos</p>
            <p class="text-2xl font-bold text-primary dark:text-primary-400 mt-1">${{ stats.revenue }}</p>
          </div>
          <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">payments</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
      <p class="mt-4 text-slate-600 dark:text-slate-400">Cargando reservas...</p>
    </div>

    <!-- Bookings Table -->
    <div v-else-if="bookings.length > 0" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Código</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Cliente</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Tour</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Fecha</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Pax</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Total</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Pago</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
              <td class="px-4 py-4 whitespace-nowrap">
                <span class="font-mono text-sm font-semibold text-slate-900 dark:text-white">{{ booking.booking_code }}</span>
              </td>
              <td class="px-4 py-4">
                <div>
                  <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.customer_name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ booking.customer_email }}</p>
                </div>
              </td>
              <td class="px-4 py-4">
                <p class="text-sm text-slate-900 dark:text-white max-w-xs truncate">{{ booking.tour?.title || 'N/A' }}</p>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(booking.tour_date) }}</p>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <p class="text-sm text-slate-900 dark:text-white">{{ booking.total_passengers }}</p>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">${{ booking.total_amount }}</p>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="getStatusClass(booking.status)" class="px-2 py-1 text-xs font-bold rounded-full">
                  {{ getStatusLabel(booking.status) }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="getPaymentClass(booking.payment_status)" class="px-2 py-1 text-xs font-semibold rounded">
                  {{ booking.payment_method?.toUpperCase() || 'N/A' }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="viewBooking(booking)" class="p-2 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Ver detalles">
                    <span class="material-symbols-outlined text-base">visibility</span>
                  </button>
                  <button v-if="booking.status === 'pending'" @click="confirmBooking(booking)" class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Confirmar">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                  </button>
                  <button v-if="booking.status !== 'cancelled'" @click="cancelBooking(booking)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Cancelar">
                    <span class="material-symbols-outlined text-base">cancel</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <div class="text-sm text-slate-600 dark:text-slate-400">
          Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }} reservas
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            Anterior
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :class="page === pagination.current_page ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
            class="px-3 py-1 text-sm border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-primary hover:text-white transition-colors"
          >
            {{ page }}
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4">inbox</span>
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No hay reservas</h3>
      <p class="text-slate-600 dark:text-slate-400">No se encontraron reservas con los filtros seleccionados</p>
    </div>

    <!-- View Booking Modal -->
    <BookingDetailsModal
      v-if="selectedBooking"
      :booking="selectedBooking"
      @close="selectedBooking = null"
      @updated="loadBookings"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const loading = ref(true)
const bookings = ref([])
const selectedBooking = ref(null)

const filters = ref({
  search: '',
  status: '',
  payment_method: '',
  date: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const stats = ref({
  total: 0,
  confirmed: 0,
  pending: 0,
  revenue: 0
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const total = pagination.value.last_page

  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const loadBookings = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.value.current_page.toString(),
      per_page: pagination.value.per_page.toString(),
      ...(filters.value.search && { search: filters.value.search }),
      ...(filters.value.status && { status: filters.value.status }),
      ...(filters.value.payment_method && { payment_method: filters.value.payment_method }),
      ...(filters.value.date && { date: filters.value.date })
    })

    const response = await fetch(`${config.public.apiUrl}/bookings?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar reservas')

    const data = await response.json()
    bookings.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from,
      to: data.to
    }

    // Calculate stats
    stats.value.total = data.total
    stats.value.confirmed = bookings.value.filter((b: any) => b.status === 'confirmed').length
    stats.value.pending = bookings.value.filter((b: any) => b.status === 'pending').length
    stats.value.revenue = bookings.value
      .filter((b: any) => b.payment_status === 'paid')
      .reduce((sum: number, b: any) => sum + parseFloat(b.total_amount || 0), 0)
      .toFixed(2)
  } catch (error) {
    console.error('Error loading bookings:', error)
  } finally {
    loading.value = false
  }
}

let searchTimeout: NodeJS.Timeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    loadBookings()
  }, 500)
}

const changePage = (page: number) => {
  pagination.value.current_page = page
  loadBookings()
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusClass = (status: string) => {
  const classes = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    confirmed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[status as keyof typeof classes] || 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
}

const getStatusLabel = (status: string) => {
  const labels = {
    pending: 'Pendiente',
    confirmed: 'Confirmado',
    cancelled: 'Cancelado',
    completed: 'Completado'
  }
  return labels[status as keyof typeof labels] || status
}

const getPaymentClass = (status: string) => {
  if (status === 'paid') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  if (status === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
}

const viewBooking = (booking: any) => {
  selectedBooking.value = booking
}

const confirmBooking = async (booking: any) => {
  if (!confirm('¿Confirmar esta reserva?')) return

  try {
    const response = await fetch(`${config.public.apiUrl}/bookings/${booking.id}/confirm`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al confirmar reserva')

    await loadBookings()
  } catch (error) {
    console.error('Error confirming booking:', error)
    alert('Error al confirmar la reserva')
  }
}

const cancelBooking = async (booking: any) => {
  if (!confirm('¿Cancelar esta reserva?')) return

  try {
    const response = await fetch(`${config.public.apiUrl}/bookings/${booking.id}/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al cancelar reserva')

    await loadBookings()
  } catch (error) {
    console.error('Error cancelling booking:', error)
    alert('Error al cancelar la reserva')
  }
}

onMounted(() => {
  loadBookings()
})
</script>
