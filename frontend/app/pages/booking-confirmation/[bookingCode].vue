<template>
  <div v-if="booking" class="min-h-screen bg-background-light dark:bg-background-dark py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      <!-- Success Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full mb-4">
          <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-5xl">check_circle</span>
        </div>
        <h1 class="text-3xl font-black text-primary-light dark:text-primary-dark mb-2">Booking Confirmed!</h1>
        <p class="text-lg text-secondary-light dark:text-secondary-dark">
          Your booking has been successfully confirmed
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-3 justify-center mb-8">
        <button
          @click="downloadPDF"
          class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-colors"
        >
          <span class="material-symbols-outlined">download</span>
          Download Voucher
        </button>
        <button
          @click="shareBooking"
          class="inline-flex items-center gap-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-primary-light dark:text-primary-dark font-bold py-3 px-6 rounded-lg transition-colors"
        >
          <span class="material-symbols-outlined">share</span>
          Share
        </button>
        <button
          @click="sendEmailCopy"
          :disabled="emailSending"
          class="inline-flex items-center gap-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-primary-light dark:text-primary-dark font-bold py-3 px-6 rounded-lg transition-colors disabled:opacity-50"
        >
          <span class="material-symbols-outlined">{{ emailSending ? 'hourglass_empty' : 'email' }}</span>
          {{ emailSending ? 'Sending...' : 'Email Copy' }}
        </button>
      </div>

      <!-- Booking Details Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 md:p-8 mb-6">
        <!-- Booking Code -->
        <div class="text-center pb-6 mb-6 border-b border-slate-200 dark:border-slate-700">
          <p class="text-sm text-secondary-light dark:text-secondary-dark mb-2">Booking Code</p>
          <p class="text-3xl font-black text-primary">{{ booking.booking_code }}</p>
          <p class="text-sm text-secondary-light dark:text-secondary-dark mt-2">
            Save this code for future reference
          </p>
        </div>

        <!-- Tour Info -->
        <div class="mb-6">
          <h2 class="text-xl font-bold mb-4">Detalles del Tour</h2>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-600">Tour:</span>
              <span class="font-semibold text-right">{{ booking.tour?.title }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Fecha:</span>
              <span class="font-semibold">{{ formatDate(booking.tour_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Participantes:</span>
              <span class="font-semibold">{{ booking.number_of_people }} persona(s)</span>
            </div>
            <div v-if="booking.pickup_location" class="flex justify-between">
              <span class="text-gray-600">Punto de recojo:</span>
              <span class="font-semibold text-right">{{ booking.pickup_location }}</span>
            </div>
            <div v-if="booking.pickup_time" class="flex justify-between">
              <span class="text-gray-600">Hora de recojo:</span>
              <span class="font-semibold">{{ booking.pickup_time }}</span>
            </div>
          </div>
        </div>

        <!-- Customer Info -->
        <div class="mb-6 pb-6 border-b">
          <h2 class="text-xl font-bold mb-4">Datos del Cliente</h2>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-600">Nombre:</span>
              <span class="font-semibold">{{ booking.customer_first_name }} {{ booking.customer_last_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Email:</span>
              <span class="font-semibold">{{ booking.customer_email }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Teléfono:</span>
              <span class="font-semibold">{{ booking.customer_phone }}</span>
            </div>
            <div v-if="booking.customer_country" class="flex justify-between">
              <span class="text-gray-600">País:</span>
              <span class="font-semibold">{{ booking.customer_country }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Info -->
        <div class="mb-6">
          <h2 class="text-xl font-bold mb-4">Información de Pago</h2>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-600">Estado:</span>
              <span :class="[
                'font-semibold px-3 py-1 rounded-full text-sm',
                booking.payment_status === 'completed' ? 'bg-green-100 text-green-700' :
                booking.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                'bg-red-100 text-red-700'
              ]">
                {{ formatPaymentStatus(booking.payment_status) }}
              </span>
            </div>
            <div class="flex justify-between text-lg">
              <span class="text-gray-600">Total:</span>
              <span class="font-bold text-blue-600">${{ booking.total_amount }} USD</span>
            </div>
            <div v-if="booking.advance_amount" class="flex justify-between">
              <span class="text-gray-600">Adelanto pagado:</span>
              <span class="font-semibold">${{ booking.advance_amount }} USD</span>
            </div>
            <div v-if="booking.remaining_amount" class="flex justify-between">
              <span class="text-gray-600">Saldo pendiente:</span>
              <span class="font-semibold text-orange-600">${{ booking.remaining_amount }} USD</span>
            </div>
          </div>
        </div>

        <!-- Policies -->
        <div v-if="booking.tour?.policy_description || booking.tour?.policy_description_custom" class="bg-blue-50 rounded-lg p-4">
          <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Políticas de Cancelación
          </h3>
          <div class="text-sm text-blue-800">
            <p v-if="booking.tour.policy_type === 'custom' && booking.tour.policy_description_custom">
              {{ booking.tour.policy_description_custom }}
            </p>
            <p v-else-if="booking.tour.policy_description">
              {{ booking.tour.policy_description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Próximos Pasos</h2>
        <div class="space-y-3 text-sm text-gray-600">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <p>Recibirás un correo de confirmación con todos los detalles de tu reserva</p>
          </div>
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p>Te contactaremos 24 horas antes del tour para confirmar detalles del recojo</p>
          </div>
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
            </svg>
            <p>Si tienes preguntas, contáctanos al +51 951 234 567 o info@incalake.com</p>
          </div>
        </div>
      </div>

      <!-- CTA Buttons -->
      <div class="flex flex-col sm:flex-row gap-4">
        <button
          @click="window.print()"
          class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
          </svg>
          Imprimir
        </button>
        <NuxtLink
          to="/tours"
          class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition text-center"
        >
          Explorar Más Tours
        </NuxtLink>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else-if="pending" class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Cargando reserva...</p>
    </div>
  </div>

  <!-- Error State -->
  <div v-else class="min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
      <svg class="w-20 h-20 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <p class="text-xl text-gray-900 font-semibold mb-2">Reserva no encontrada</p>
      <p class="text-gray-600 mb-6">{{ errorMessage }}</p>
      <NuxtLink
        to="/tours"
        class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
      >
        Ver Tours
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { api } = useApi()

const bookingCode = route.params.bookingCode as string
const token = route.query.token as string
const emailSending = ref(false)

// Fetch booking data
const { data: response, pending, error } = await useAsyncData(
  `booking-${bookingCode}`,
  async () => {
    try {
      if (token) {
        // Token-based access from email
        return await api(`/bookings/token/${token}`)
      } else {
        // Code-based access (requires email verification in production)
        const email = route.query.email as string
        const params = email ? `?email=${encodeURIComponent(email)}` : ''
        return await api(`/bookings/${bookingCode}${params}`)
      }
    } catch (err: any) {
      throw err
    }
  }
)

const booking = computed(() => response.value?.booking || null)
const errorMessage = computed(() => error.value?.data?.message || 'The link is invalid or has expired')

// Clean token from URL after loading (security optimization)
onMounted(() => {
  if (token && booking.value) {
    const router = useRouter()
    router.replace({
      name: 'booking-confirmation-bookingCode',
      params: { bookingCode: booking.value.booking_code }
    })
  }
})

// SEO
useHead({
  title: booking.value ? `Booking ${booking.value.booking_code} | Incalake Tours` : 'Booking Confirmation',
  meta: [
    { name: 'robots', content: 'noindex, nofollow' }
  ]
})

// Helper functions
function formatDate(dateString: string) {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatPaymentStatus(status: string) {
  const map: Record<string, string> = {
    pending: 'Pending',
    completed: 'Completed',
    partial: 'Partial Payment',
    failed: 'Failed',
    refunded: 'Refunded'
  }
  return map[status] || status
}

// Download PDF Voucher
async function downloadPDF() {
  try {
    const config = useRuntimeConfig()
    const pdfUrl = `${config.public.apiBase}/bookings/${booking.value.booking_code}/pdf`

    // Open PDF in new tab
    window.open(pdfUrl, '_blank')
  } catch (err) {
    console.error('Error downloading PDF:', err)
    alert('Error downloading voucher. Please try again.')
  }
}

// Share Booking
async function shareBooking() {
  const shareData = {
    title: `Booking Confirmation - ${booking.value.booking_code}`,
    text: `I've booked ${booking.value.tour?.title} with Incalake Tours!`,
    url: window.location.href
  }

  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      // Fallback: Copy to clipboard
      await navigator.clipboard.writeText(window.location.href)
      alert('Link copied to clipboard!')
    }
  } catch (err) {
    console.error('Error sharing:', err)
  }
}

// Send Email Copy
async function sendEmailCopy() {
  emailSending.value = true
  try {
    await api(`/bookings/${booking.value.booking_code}/resend-email`, {
      method: 'POST'
    })
    alert('Confirmation email sent successfully!')
  } catch (err) {
    console.error('Error sending email:', err)
    alert('Error sending email. Please try again.')
  } finally {
    emailSending.value = false
  }
}
</script>

<style>
@media print {
  nav, footer, button {
    display: none !important;
  }
}
</style>
