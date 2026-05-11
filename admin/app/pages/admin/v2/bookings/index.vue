<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import BookingDetailsModalV2 from '~/components/v2/BookingDetailsModalV2.vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Booking {
  id: number
  booking_code: string
  customer_name: string
  customer_email: string
  tour_title?: string
  tour?: { title?: string }
  tour_date: string
  total_participants?: number
  adults?: number
  children?: number
  infants?: number
  total?: number | string
  total_amount?: number | string
  status: string
  payment_status: string
  payment_method?: string
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()

const loading = ref(true)
const bookings = ref<Booking[]>([])
const selectedBooking = ref<Booking | null>(null)

const filters = ref({
  search: '',
  status: '',
  payment_method: '',
  date: '',
})

const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})

const stats = ref({
  total: 0,
  confirmed: 0,
  pending: 0,
  revenue: '0.00',
})

const statusOptions = [
  { label: 'Todos', value: '' },
  { label: 'Pendiente', value: 'pending' },
  { label: 'Confirmado', value: 'confirmed' },
  { label: 'Cancelado', value: 'cancelled' },
  { label: 'Completado', value: 'completed' },
]
const paymentMethodOptions = [
  { label: 'Todos', value: '' },
  { label: 'Culqi', value: 'culqi' },
  { label: 'PayPal', value: 'paypal' },
  { label: 'Efectivo', value: 'cash' },
  { label: 'Transferencia', value: 'transfer' },
]

const statusBadge: Record<string, { color: 'warning' | 'success' | 'error' | 'info' | 'neutral'; label: string; icon: string }> = {
  pending: { color: 'warning', label: 'Pendiente', icon: 'i-lucide-clock' },
  confirmed: { color: 'success', label: 'Confirmado', icon: 'i-lucide-circle-check' },
  cancelled: { color: 'error', label: 'Cancelado', icon: 'i-lucide-circle-x' },
  completed: { color: 'info', label: 'Completado', icon: 'i-lucide-check-check' },
}

const paymentBadge = (status: string): 'success' | 'warning' | 'neutral' => {
  if (status === 'paid') return 'success'
  if (status === 'pending') return 'warning'
  return 'neutral'
}

const visiblePages = computed(() => {
  const pages: number[] = []
  const current = pagination.value.current_page
  const total = pagination.value.last_page
  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) pages.push(i)
  return pages
})

const statsCards = computed(() => [
  {
    label: 'Total reservas',
    value: stats.value.total,
    icon: 'i-lucide-receipt',
    bgClass: 'bg-info/10',
    iconClass: 'text-info',
  },
  {
    label: 'Confirmadas',
    value: stats.value.confirmed,
    icon: 'i-lucide-circle-check',
    bgClass: 'bg-success/10',
    iconClass: 'text-success',
  },
  {
    label: 'Pendientes',
    value: stats.value.pending,
    icon: 'i-lucide-clock',
    bgClass: 'bg-warning/10',
    iconClass: 'text-warning',
  },
  {
    label: 'Ingresos (página)',
    value: `$${stats.value.revenue}`,
    icon: 'i-lucide-dollar-sign',
    bgClass: 'bg-primary/10',
    iconClass: 'text-primary',
  },
])

const authHeader = () => ({
  Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
})

const loadBookings = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: String(pagination.value.current_page),
      per_page: String(pagination.value.per_page),
      ...(filters.value.search && { search: filters.value.search }),
      ...(filters.value.status && { status: filters.value.status }),
      ...(filters.value.payment_method && { payment_method: filters.value.payment_method }),
      ...(filters.value.date && { date: filters.value.date }),
    })

    const response = await fetch(`${config.public.apiUrl}/bookings?${params}`, {
      headers: authHeader(),
    })
    if (!response.ok) throw new Error('Error al cargar reservas')

    const data = await response.json()
    bookings.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from || 0,
      to: data.to || 0,
    }

    stats.value.total = data.total
    stats.value.confirmed = bookings.value.filter(b => b.status === 'confirmed').length
    stats.value.pending = bookings.value.filter(b => b.status === 'pending').length
    stats.value.revenue = bookings.value
      .filter(b => b.payment_status === 'paid')
      .reduce((sum, b) => sum + parseFloat(String(b.total || b.total_amount || 0)), 0)
      .toFixed(2)
  } catch (err) {
    console.error('Error loading bookings:', err)
    toast.add({
      title: 'Error',
      description: 'No se pudieron cargar las reservas.',
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
  } finally {
    loading.value = false
  }
}

let searchTimeout: any
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    loadBookings()
  }, 400)
}

const changePage = (page: number) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadBookings()
}

const resetFilters = () => {
  filters.value = { search: '', status: '', payment_method: '', date: '' }
  pagination.value.current_page = 1
  loadBookings()
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
}

const totalPax = (booking: Booking) =>
  booking.total_participants || ((booking.adults || 0) + (booking.children || 0) + (booking.infants || 0))

const viewBooking = (booking: Booking) => {
  selectedBooking.value = booking
}

const confirmBooking = async (booking: Booking) => {
  const ok = await confirm({
    title: 'Confirmar reserva',
    description: `Vas a confirmar la reserva ${booking.booking_code} de ${booking.customer_name}.`,
    confirmLabel: 'Confirmar reserva',
    confirmColor: 'success',
    confirmIcon: 'i-lucide-circle-check',
    icon: 'i-lucide-circle-check',
    iconColor: 'success',
  })
  if (!ok) return
  try {
    const response = await fetch(`${config.public.apiUrl}/bookings/${booking.id}/confirm`, {
      method: 'POST',
      headers: { ...authHeader(), 'Content-Type': 'application/json' },
    })
    if (!response.ok) throw new Error()
    toast.add({ title: 'Reserva confirmada', icon: 'i-lucide-circle-check', color: 'success' })
    await loadBookings()
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo confirmar.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const cancelBooking = async (booking: Booking) => {
  const ok = await confirm({
    title: 'Cancelar reserva',
    description: `Vas a cancelar la reserva ${booking.booking_code} de ${booking.customer_name}. Esta acción puede requerir reembolso.`,
    confirmLabel: 'Cancelar reserva',
    cancelLabel: 'Volver',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-circle-x',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'warning',
  })
  if (!ok) return
  try {
    const response = await fetch(`${config.public.apiUrl}/bookings/${booking.id}/cancel`, {
      method: 'POST',
      headers: { ...authHeader(), 'Content-Type': 'application/json' },
    })
    if (!response.ok) throw new Error()
    toast.add({ title: 'Reserva cancelada', icon: 'i-lucide-circle-x', color: 'warning' })
    await loadBookings()
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cancelar.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const rowActions = (booking: Booking) => {
  const items: any[][] = [
    [{ label: 'Ver detalles', icon: 'i-lucide-eye', onSelect: () => viewBooking(booking) }],
  ]
  const transitions: any[] = []
  if (booking.status === 'pending') {
    transitions.push({ label: 'Confirmar', icon: 'i-lucide-circle-check', color: 'success', onSelect: () => confirmBooking(booking) })
  }
  if (booking.status !== 'cancelled') {
    transitions.push({ label: 'Cancelar', icon: 'i-lucide-circle-x', color: 'error', onSelect: () => cancelBooking(booking) })
  }
  if (transitions.length) items.push(transitions)
  return items
}

onMounted(() => {
  loadBookings()
})
</script>

<template>
  <UDashboardPanel id="bookings-v2">
    <template #header>
      <UDashboardNavbar title="Reservas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="loadBookings"
          >
            Actualizar
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <div>
          <h2 class="text-2xl font-bold">Gestión de reservas</h2>
          <p class="text-sm text-muted mt-1">Reservas de clientes (web + OTAs)</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <UCard v-for="card in statsCards" :key="card.label" :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ card.label }}</p>
                <p class="text-3xl font-bold tabular-nums mt-2 truncate">{{ card.value }}</p>
              </div>
              <div :class="['size-11 rounded-xl flex items-center justify-center shrink-0', card.bgClass]">
                <UIcon :name="card.icon" :class="['size-6', card.iconClass]" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Filters -->
        <UCard :ui="{ body: 'p-4' }">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <UFormField label="Buscar">
              <UInput
                v-model="filters.search"
                placeholder="Código, nombre, email..."
                icon="i-lucide-search"
                class="w-full"
                @input="debouncedSearch"
              />
            </UFormField>
            <UFormField label="Estado">
              <USelectMenu
                v-model="filters.status"
                :items="statusOptions"
                value-key="value"
                class="w-full"
                @update:model-value="() => { pagination.current_page = 1; loadBookings() }"
              />
            </UFormField>
            <UFormField label="Método de pago">
              <USelectMenu
                v-model="filters.payment_method"
                :items="paymentMethodOptions"
                value-key="value"
                class="w-full"
                @update:model-value="() => { pagination.current_page = 1; loadBookings() }"
              />
            </UFormField>
            <UFormField label="Fecha del tour">
              <UInput
                v-model="filters.date"
                type="date"
                class="w-full"
                @change="() => { pagination.current_page = 1; loadBookings() }"
              />
            </UFormField>
          </div>
          <div v-if="filters.search || filters.status || filters.payment_method || filters.date" class="flex justify-end mt-3">
            <UButton variant="ghost" size="sm" icon="i-lucide-x" @click="resetFilters">Limpiar filtros</UButton>
          </div>
        </UCard>

        <!-- Table -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading -->
          <div v-if="loading && bookings.length === 0" class="p-6 space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-center gap-4">
              <USkeleton class="h-4 w-20" />
              <div class="flex-1 space-y-1">
                <USkeleton class="h-3 w-1/3" />
                <USkeleton class="h-2 w-1/2" />
              </div>
              <USkeleton class="h-6 w-20" />
              <USkeleton class="h-6 w-16" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="bookings.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-inbox" class="size-12 text-muted" />
            <p class="text-sm text-muted">No hay reservas con los filtros seleccionados.</p>
            <UButton v-if="filters.search || filters.status || filters.payment_method || filters.date" variant="outline" size="sm" @click="resetFilters">
              Limpiar filtros
            </UButton>
          </div>

          <!-- Table -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-elevated/50 border-b border-default">
                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-muted">
                  <th class="px-4 py-3">Código</th>
                  <th class="px-4 py-3">Cliente</th>
                  <th class="px-4 py-3">Tour</th>
                  <th class="px-4 py-3">Fecha</th>
                  <th class="px-4 py-3 text-center">Pax</th>
                  <th class="px-4 py-3 text-right">Total</th>
                  <th class="px-4 py-3">Estado</th>
                  <th class="px-4 py-3">Pago</th>
                  <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-elevated/30 transition-colors">
                  <td class="px-4 py-3 whitespace-nowrap">
                    <UBadge color="neutral" variant="subtle" size="sm" class="font-mono">{{ booking.booking_code }}</UBadge>
                  </td>
                  <td class="px-4 py-3 min-w-0">
                    <p class="font-semibold truncate max-w-[200px]">{{ booking.customer_name }}</p>
                    <p class="text-xs text-muted truncate max-w-[200px]">{{ booking.customer_email }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="truncate max-w-[260px]">{{ booking.tour_title || booking.tour?.title || 'N/A' }}</p>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(booking.tour_date) }}</td>
                  <td class="px-4 py-3 text-center tabular-nums">{{ totalPax(booking) || '-' }}</td>
                  <td class="px-4 py-3 text-right whitespace-nowrap font-bold tabular-nums">
                    ${{ parseFloat(String(booking.total || booking.total_amount || 0)).toFixed(2) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <UBadge
                      :color="statusBadge[booking.status]?.color || 'neutral'"
                      variant="subtle"
                      size="sm"
                      :icon="statusBadge[booking.status]?.icon"
                    >
                      {{ statusBadge[booking.status]?.label || booking.status }}
                    </UBadge>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <UBadge :color="paymentBadge(booking.payment_status)" variant="subtle" size="sm">
                      {{ (booking.payment_method || 'N/A').toUpperCase() }}
                    </UBadge>
                  </td>
                  <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-1">
                      <UButton
                        icon="i-lucide-eye"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        title="Ver detalles"
                        @click="viewBooking(booking)"
                      />
                      <UDropdownMenu :items="rowActions(booking)" :content="{ align: 'end' }">
                        <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" />
                      </UDropdownMenu>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="p-4 border-t border-default flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-muted">
              Mostrando <span class="font-semibold text-default">{{ pagination.from }}-{{ pagination.to }}</span>
              de <span class="font-semibold text-default">{{ pagination.total }}</span> reservas
            </p>
            <div class="flex items-center gap-1">
              <UButton
                icon="i-lucide-chevron-left"
                color="neutral"
                variant="ghost"
                size="sm"
                :disabled="pagination.current_page === 1"
                @click="changePage(pagination.current_page - 1)"
              />
              <UButton
                v-for="page in visiblePages"
                :key="page"
                :color="page === pagination.current_page ? 'primary' : 'neutral'"
                :variant="page === pagination.current_page ? 'solid' : 'ghost'"
                size="sm"
                @click="changePage(page)"
              >
                {{ page }}
              </UButton>
              <UButton
                icon="i-lucide-chevron-right"
                color="neutral"
                variant="ghost"
                size="sm"
                :disabled="pagination.current_page === pagination.last_page"
                @click="changePage(pagination.current_page + 1)"
              />
            </div>
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <BookingDetailsModalV2
    v-if="selectedBooking"
    :booking="selectedBooking"
    :open="!!selectedBooking"
    @close="selectedBooking = null"
    @updated="loadBookings"
  />
</template>
