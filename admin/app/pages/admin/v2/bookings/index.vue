<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { TableColumn } from '@nuxt/ui'
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
  group_count?: number
  group_total?: number
  is_group?: boolean
  status: string
  payment_status: string
  payment_state?: 'full' | 'partial' | 'refunded' | 'unpaid'
  amount_paid?: number
  amount_remaining?: number
  currency?: string
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

// --- UTable: columns, selection, column visibility ---
const bookingsTable = ref<any>(null)
const rowSelection = ref<Record<string, boolean>>({})
const columnVisibility = ref<Record<string, boolean>>({})

const columns: TableColumn<Booking>[] = [
  { id: 'select' },
  { accessorKey: 'booking_code', header: 'Código' },
  { accessorKey: 'customer_name', header: 'Cliente' },
  { accessorKey: 'tour_title', header: 'Tour' },
  { accessorKey: 'tour_date', header: 'Fecha' },
  { id: 'pax', header: 'Pax', meta: { class: { th: 'text-center', td: 'text-center' } } },
  { id: 'total', header: 'Total', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'balance', header: 'Saldo', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { accessorKey: 'status', header: 'Estado' },
  { id: 'payment', header: 'Pago' },
  { id: 'actions', header: '', meta: { class: { th: 'text-right', td: 'text-right' } } },
]

const hideableColumns = [
  { id: 'tour_date', label: 'Fecha' },
  { id: 'pax', label: 'Pax' },
  { id: 'balance', label: 'Saldo' },
  { id: 'payment', label: 'Pago' },
]
const columnMenuItems = computed(() =>
  hideableColumns.map(c => ({
    label: c.label,
    type: 'checkbox' as const,
    checked: columnVisibility.value[c.id] !== false,
    onUpdateChecked(val: boolean) {
      columnVisibility.value = { ...columnVisibility.value, [c.id]: val }
    },
    onSelect(e: Event) { e.preventDefault() },
  }))
)

const selectedBookings = computed<Booking[]>(() => {
  void rowSelection.value // reactive dependency
  const rows = bookingsTable.value?.tableApi?.getFilteredSelectedRowModel().rows ?? []
  return rows.map((r: any) => r.original as Booking)
})
const clearSelection = () => { rowSelection.value = {} }

const filters = ref({
  search: '',
  status: 'all',
  payment_state: 'all',
  payment_method: 'all',
  date: '',
})

const hasActiveFilters = computed(() =>
  !!filters.value.search ||
  filters.value.status !== 'all' ||
  filters.value.payment_state !== 'all' ||
  filters.value.payment_method !== 'all' ||
  !!filters.value.date
)

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

// USelect (reka-ui) forbids an empty-string value, so "Todos" uses the
// 'all' sentinel and loadBookings() treats it as "no filter".
const statusOptions = [
  { label: 'Todos', value: 'all' },
  { label: 'Confirmado', value: 'confirmed' },
  { label: 'Cancelado', value: 'cancelled' },
]
const paymentStateOptions = [
  { label: 'Todos', value: 'all' },
  { label: 'Pagado total', value: 'full' },
  { label: 'Pago parcial', value: 'partial' },
  { label: 'Reembolsado', value: 'refunded' },
]
const paymentMethodOptions = [
  { label: 'Todos', value: 'all' },
  { label: 'Culqi', value: 'culqi' },
  { label: 'PayPal', value: 'paypal' },
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
  rowSelection.value = {} // selection is page-local; reset on every (re)load
  try {
    const params = new URLSearchParams({
      page: String(pagination.value.current_page),
      per_page: String(pagination.value.per_page),
      ...(filters.value.search && { search: filters.value.search }),
      ...(filters.value.status !== 'all' && { status: filters.value.status }),
      ...(filters.value.payment_state !== 'all' && { payment_state: filters.value.payment_state }),
      ...(filters.value.payment_method !== 'all' && { payment_method: filters.value.payment_method }),
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
      .reduce((sum, b) => sum + amountReceived(b), 0)
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
  filters.value = { search: '', status: 'all', payment_state: 'all', payment_method: 'all', date: '' }
  pagination.value.current_page = 1
  loadBookings()
}

const paymentStateBadge: Record<string, { color: 'success' | 'warning' | 'neutral' | 'error'; label: string }> = {
  full: { color: 'success', label: 'Pagado total' },
  partial: { color: 'warning', label: 'Pago parcial' },
  refunded: { color: 'neutral', label: 'Reembolsado' },
  unpaid: { color: 'error', label: 'Sin pagar' },
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
}

const totalPax = (booking: Booking) =>
  booking.total_participants || ((booking.adults || 0) + (booking.children || 0) + (booking.infants || 0))

// For a multi-tour purchase the row represents the whole purchase, so show
// the group total (the contract value), not the primary tour's total.
const bookingTotal = (booking: Booking) =>
  booking.is_group && booking.group_total != null
    ? Number(booking.group_total)
    : parseFloat(String(booking.total || booking.total_amount || 0))

// Money actually received: for a partial (advance) payment this is only the
// deposit, not the full total — so revenue must not count the unpaid balance.
const amountReceived = (booking: Booking) =>
  booking.payment_state === 'partial' && booking.amount_paid != null
    ? Number(booking.amount_paid)
    : bookingTotal(booking)

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
  if (booking.status !== 'confirmed') {
    transitions.push({
      label: booking.status === 'cancelled' ? 'Reactivar (confirmar)' : 'Confirmar',
      icon: 'i-lucide-circle-check',
      color: 'success',
      onSelect: () => confirmBooking(booking),
    })
  }
  if (booking.status !== 'cancelled') {
    transitions.push({ label: 'Cancelar', icon: 'i-lucide-circle-x', color: 'error', onSelect: () => cancelBooking(booking) })
  }
  if (transitions.length) items.push(transitions)
  return items
}

// --- Bulk actions over the selected rows ---
const bulkRequest = (b: Booking, action: 'confirm' | 'cancel') =>
  fetch(`${config.public.apiUrl}/bookings/${b.id}/${action}`, {
    method: 'POST',
    headers: { ...authHeader(), 'Content-Type': 'application/json' },
  }).then(r => { if (!r.ok) throw new Error() })

const bulkConfirm = async () => {
  const targets = selectedBookings.value.filter(b => b.status !== 'confirmed')
  if (!targets.length) {
    toast.add({ title: 'Nada que confirmar', description: 'Las seleccionadas ya están confirmadas.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  const ok = await confirm({
    title: `Confirmar ${targets.length} reserva(s)`,
    description: `Vas a confirmar ${targets.length} reserva(s) pendiente(s) seleccionada(s).`,
    confirmLabel: 'Confirmar todas', confirmColor: 'success', confirmIcon: 'i-lucide-circle-check',
    icon: 'i-lucide-circle-check', iconColor: 'success',
  })
  if (!ok) return
  const results = await Promise.allSettled(targets.map(b => bulkRequest(b, 'confirm')))
  const okCount = results.filter(r => r.status === 'fulfilled').length
  toast.add({ title: `${okCount}/${targets.length} confirmada(s)`, color: okCount === targets.length ? 'success' : 'warning', icon: 'i-lucide-circle-check' })
  await loadBookings()
}

const bulkCancel = async () => {
  const targets = selectedBookings.value.filter(b => b.status !== 'cancelled')
  if (!targets.length) {
    toast.add({ title: 'Nada que cancelar', description: 'Las seleccionadas ya están canceladas.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  const ok = await confirm({
    title: `Cancelar ${targets.length} reserva(s)`,
    description: `Vas a cancelar ${targets.length} reserva(s). Esta acción puede requerir reembolso.`,
    confirmLabel: 'Cancelar todas', cancelLabel: 'Volver', confirmColor: 'error', confirmIcon: 'i-lucide-circle-x',
    icon: 'i-lucide-triangle-alert', iconColor: 'warning',
  })
  if (!ok) return
  const results = await Promise.allSettled(targets.map(b => bulkRequest(b, 'cancel')))
  const okCount = results.filter(r => r.status === 'fulfilled').length
  toast.add({ title: `${okCount}/${targets.length} cancelada(s)`, color: okCount === targets.length ? 'warning' : 'error', icon: 'i-lucide-circle-x' })
  await loadBookings()
}

onMounted(() => {
  // Deep-link from the dashboard ("recent bookings"): ?code=BK-... pre-fills
  // the search so the booking is shown immediately (no more /admin/bookings/:id 404).
  const code = (useRoute().query.code as string) || ''
  if (code) filters.value.search = code
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
          <UDropdownMenu :items="[columnMenuItems]" :content="{ align: 'end' }">
            <UButton icon="i-lucide-columns-3" color="neutral" variant="ghost">Columnas</UButton>
          </UDropdownMenu>
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
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
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
              <USelect
                v-model="filters.status"
                :items="statusOptions"
                class="w-full"
                @update:model-value="() => { pagination.current_page = 1; loadBookings() }"
              />
            </UFormField>
            <UFormField label="Estado de pago">
              <USelect
                v-model="filters.payment_state"
                :items="paymentStateOptions"
                class="w-full"
                @update:model-value="() => { pagination.current_page = 1; loadBookings() }"
              />
            </UFormField>
            <UFormField label="Método de pago">
              <USelect
                v-model="filters.payment_method"
                :items="paymentMethodOptions"
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
          <div v-if="hasActiveFilters" class="flex justify-end mt-3">
            <UButton variant="ghost" size="sm" icon="i-lucide-x" @click="resetFilters">Limpiar filtros</UButton>
          </div>
        </UCard>

        <!-- Bulk actions bar -->
        <div v-if="selectedBookings.length" class="flex items-center gap-2 flex-wrap px-1">
          <UBadge color="primary" variant="subtle" size="md" icon="i-lucide-check-square">
            {{ selectedBookings.length }} seleccionada(s)
          </UBadge>
          <UButton size="sm" color="success" variant="soft" icon="i-lucide-circle-check" @click="bulkConfirm">
            Confirmar
          </UButton>
          <UButton size="sm" color="error" variant="soft" icon="i-lucide-circle-x" @click="bulkCancel">
            Cancelar
          </UButton>
          <UButton size="sm" color="neutral" variant="ghost" icon="i-lucide-x" @click="clearSelection">
            Limpiar
          </UButton>
        </div>

        <!-- Table -->
        <UCard :ui="{ body: 'p-0' }">
          <UTable
            ref="bookingsTable"
            v-model:row-selection="rowSelection"
            v-model:column-visibility="columnVisibility"
            :data="bookings"
            :columns="columns"
            :loading="loading"
            sticky
            class="max-h-[70vh]"
            :ui="{ thead: 'bg-elevated/50', th: 'text-[10px] font-black uppercase tracking-widest text-muted py-3', td: 'py-3' }"
          >
            <template #select-header="{ table }">
              <UCheckbox
                :model-value="table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected()"
                aria-label="Seleccionar todo"
                @update:model-value="(v: any) => table.toggleAllPageRowsSelected(!!v)"
              />
            </template>
            <template #select-cell="{ row }">
              <UCheckbox
                :model-value="row.getIsSelected()"
                aria-label="Seleccionar fila"
                @update:model-value="(v: any) => row.toggleSelected(!!v)"
              />
            </template>

            <template #booking_code-cell="{ row }">
              <div class="flex items-center gap-1.5 whitespace-nowrap">
                <UBadge color="neutral" variant="subtle" size="sm" class="font-mono">{{ row.original.booking_code }}</UBadge>
                <UBadge v-if="row.original.is_group" color="primary" variant="subtle" size="sm" icon="i-heroicons-squares-2x2">
                  {{ row.original.group_count }} tours
                </UBadge>
              </div>
            </template>

            <template #customer_name-cell="{ row }">
              <p class="font-semibold truncate max-w-[200px]">{{ row.original.customer_name }}</p>
              <p class="text-xs text-muted truncate max-w-[200px]">{{ row.original.customer_email }}</p>
            </template>

            <template #tour_title-cell="{ row }">
              <p class="truncate max-w-[260px]">{{ row.original.tour_title || row.original.tour?.title || 'N/A' }}</p>
              <p v-if="row.original.is_group" class="text-xs text-muted">
                + {{ (row.original.group_count || 1) - 1 }} tour(s) más · ver detalle
              </p>
            </template>

            <template #tour_date-cell="{ row }">
              <span class="whitespace-nowrap">{{ formatDate(row.original.tour_date) }}</span>
            </template>

            <template #pax-cell="{ row }">
              <span class="tabular-nums">{{ totalPax(row.original) || '-' }}</span>
            </template>

            <template #total-cell="{ row }">
              <span class="font-bold tabular-nums whitespace-nowrap">${{ bookingTotal(row.original).toFixed(2) }}</span>
            </template>

            <template #balance-cell="{ row }">
              <span
                v-if="row.original.payment_state === 'partial' && row.original.amount_remaining != null && row.original.amount_remaining > 0"
                class="font-bold tabular-nums whitespace-nowrap text-amber-600"
                title="Saldo a cobrar el día del tour"
              >
                ${{ row.original.amount_remaining.toFixed(2) }}
              </span>
              <span v-else class="text-muted">—</span>
            </template>

            <template #status-cell="{ row }">
              <UBadge
                :color="statusBadge[row.original.status]?.color || 'neutral'"
                variant="subtle"
                size="sm"
                :icon="statusBadge[row.original.status]?.icon"
              >
                {{ statusBadge[row.original.status]?.label || row.original.status }}
              </UBadge>
            </template>

            <template #payment-cell="{ row }">
              <div class="flex flex-col items-start gap-1">
                <UBadge :color="paymentBadge(row.original.payment_status)" variant="subtle" size="sm">
                  {{ (row.original.payment_method || 'N/A').toUpperCase() }}
                </UBadge>
                <UBadge
                  v-if="row.original.payment_state && paymentStateBadge[row.original.payment_state]"
                  :color="paymentStateBadge[row.original.payment_state].color"
                  variant="soft"
                  size="sm"
                  class="whitespace-nowrap"
                >
                  {{ paymentStateBadge[row.original.payment_state].label }}
                </UBadge>
              </div>
            </template>

            <template #actions-cell="{ row }">
              <div class="inline-flex items-center gap-1 justify-end w-full">
                <UButton icon="i-lucide-eye" color="neutral" variant="ghost" size="sm" title="Ver detalles" @click="viewBooking(row.original)" />
                <UDropdownMenu :items="rowActions(row.original)" :content="{ align: 'end' }">
                  <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" />
                </UDropdownMenu>
              </div>
            </template>

            <template #empty>
              <div class="py-12 flex flex-col items-center text-center gap-3">
                <UIcon name="i-lucide-inbox" class="size-12 text-muted" />
                <p class="text-sm text-muted">No hay reservas con los filtros seleccionados.</p>
                <UButton v-if="hasActiveFilters" variant="outline" size="sm" @click="resetFilters">
                  Limpiar filtros
                </UButton>
              </div>
            </template>
          </UTable>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="p-4 border-t border-default flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-muted">
              Mostrando <span class="font-semibold text-default">{{ pagination.from }}-{{ pagination.to }}</span>
              de <span class="font-semibold text-default">{{ pagination.total }}</span> reservas
            </p>
            <UPagination
              :page="pagination.current_page"
              :total="pagination.total"
              :items-per-page="pagination.per_page"
              @update:page="changePage"
            />
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
