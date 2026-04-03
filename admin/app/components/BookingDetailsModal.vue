<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-slate-900 dark:text-white">Detalles de Reserva</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ booking.booking_code }}</p>
        </div>
        <button @click="$emit('close')" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
        <!-- Customer Info -->
        <div class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Información del Cliente</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Nombre</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.customer_name }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Email</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.customer_email }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Teléfono</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.customer_phone || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">País</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.customer_country || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Tour Info -->
        <div class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Información del Tour</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Tour</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.tour_title || booking.tour?.title || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Fecha del Tour</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(booking.tour_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Pasajeros</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.total_participants || ((booking.adults || 0) + (booking.children || 0) + (booking.infants || 0)) }} personas</p>
            </div>
            <div v-if="booking.adults">
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Adultos</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.adults }}</p>
            </div>
            <div v-if="booking.children">
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Niños</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.children }}</p>
            </div>
          </div>
        </div>

        <!-- Payment Info -->
        <div class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Información de Pago</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Método de Pago</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.payment_method?.toUpperCase() || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Estado de Pago</p>
              <span :class="getPaymentStatusClass(booking.payment_status)" class="inline-block px-2 py-1 text-xs font-semibold rounded">
                {{ getPaymentStatusLabel(booking.payment_status) }}
              </span>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Subtotal</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">${{ booking.subtotal || booking.total || '0.00' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Total</p>
              <p class="text-lg font-bold text-primary">${{ booking.total || booking.total_amount || '0.00' }}</p>
            </div>
          </div>
        </div>

        <!-- Booking Status -->
        <div class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Estado de Reserva</h3>
          <div class="flex items-center gap-3">
            <span :class="getStatusClass(booking.status)" class="px-3 py-1.5 text-sm font-bold rounded-full">
              {{ getStatusLabel(booking.status) }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">
              Creado: {{ formatDateTime(booking.created_at) }}
            </span>
          </div>
        </div>

        <!-- Special Requests -->
        <div v-if="booking.special_requests" class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">Solicitudes Especiales</h3>
          <p class="text-sm text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900 p-4 rounded-lg">{{ booking.special_requests }}</p>
        </div>

        <!-- Pickup Configuration -->
        <div v-if="fullDetails?.pickup_detail" class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">location_on</span>
            Pickup Configuration
          </h3>
          <div class="bg-slate-50 dark:bg-slate-900 rounded-xl p-4 space-y-2">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                :class="fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                {{ fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'Hotel Pickup' : 'Meeting Point' }}
              </span>
              <span v-if="fullDetails.pickup_detail.requires_logistics_approval" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-700">
                {{ fullDetails.pickup_detail.approval_status || 'Pending Approval' }}
              </span>
            </div>
            <div v-if="fullDetails.pickup_detail.hotel_name" class="text-sm">
              <p class="font-semibold text-slate-800 dark:text-white">{{ fullDetails.pickup_detail.hotel_name }}</p>
              <p class="text-xs text-slate-500">{{ fullDetails.pickup_detail.hotel_address }}</p>
            </div>
            <div v-if="fullDetails.pickup_detail.distance_from_center" class="flex gap-4 text-xs text-slate-500">
              <span>Distance: {{ parseFloat(fullDetails.pickup_detail.distance_from_center).toFixed(1) }} km</span>
              <span v-if="fullDetails.pickup_detail.extra_pickup_cost > 0" class="font-bold text-amber-600">
                Extra: ${{ parseFloat(fullDetails.pickup_detail.extra_pickup_cost).toFixed(2) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Travelers -->
        <div v-if="fullDetails?.travelers?.length" class="mb-6">
          <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">group</span>
            Travelers ({{ fullDetails.travelers.length }})
          </h3>
          <div class="space-y-2">
            <div v-for="(t, idx) in fullDetails.travelers" :key="t.id" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900 rounded-xl">
              <div class="size-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                :class="t.is_leader ? 'bg-primary/10 text-primary' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                {{ idx + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-white">
                  {{ t.full_name }}
                  <span v-if="t.is_leader" class="text-[9px] bg-primary/10 text-primary px-1.5 py-0.5 rounded ml-1 font-bold">LEADER</span>
                  <span v-if="t.age_group !== 'adult'" class="text-[9px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded ml-1 font-bold uppercase">{{ t.age_group }}</span>
                </p>
                <div class="flex gap-3 text-[10px] text-slate-500 mt-0.5">
                  <span v-if="t.nationality">{{ t.nationality }}</span>
                  <span v-if="t.doc_type && t.doc_number">{{ t.doc_type.toUpperCase() }}: {{ t.doc_number }}</span>
                  <span v-if="t.special_needs" class="text-amber-600">{{ t.special_needs }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Props {
  booking: any
}

const props = defineProps<Props>()
defineEmits(['close', 'updated'])

const config = useRuntimeConfig()
const API = config.public.apiUrl
const fullDetails = ref<any>(null)

onMounted(async () => {
  try {
    const res: any = await $fetch(`${API}/bookings/${props.booking.id}/full-details`)
    if (res.success) fullDetails.value = res.data
  } catch (e) { /* ignore - fields just won't show */ }
})

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClass = (status: string) => {
  const classes = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    confirmed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[status as keyof typeof classes] || 'bg-slate-100 text-slate-700'
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

const getPaymentStatusClass = (status: string) => {
  if (status === 'paid') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  if (status === 'pending') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
  if (status === 'failed') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
}

const getPaymentStatusLabel = (status: string) => {
  const labels = {
    paid: 'Pagado',
    pending: 'Pendiente',
    failed: 'Fallido',
    refunded: 'Reembolsado'
  }
  return labels[status as keyof typeof labels] || status
}
</script>
