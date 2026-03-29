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
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.tour?.title || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Fecha</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(booking.tour_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Pasajeros</p>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ booking.total_passengers }} personas</p>
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
              <p class="text-sm font-medium text-slate-900 dark:text-white">${{ booking.subtotal || booking.total_amount }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Total</p>
              <p class="text-lg font-bold text-primary">${{ booking.total_amount }}</p>
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
interface Props {
  booking: any
}

defineProps<Props>()
defineEmits(['close', 'updated'])

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
