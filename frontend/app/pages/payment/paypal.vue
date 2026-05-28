<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark pt-24 pb-8 lg:pt-28 lg:pb-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl lg:text-4xl font-black text-primary-light dark:text-primary-dark mb-2">
          Complete Your Payment
        </h1>
        <p class="text-secondary-light dark:text-secondary-dark">
          Secure payment powered by PayPal
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
        <p class="text-secondary-light dark:text-secondary-dark">Loading payment information...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 mb-8">
        <div class="flex items-start gap-3">
          <Icon name="material-symbols:error-outline" class="text-red-600 dark:text-red-400 text-2xl" />
          <div>
            <h3 class="font-bold text-red-900 dark:text-red-100 mb-1">Error Loading Payment</h3>
            <p class="text-red-700 dark:text-red-300">{{ error }}</p>
            <button
              @click="router.push('/cart')"
              class="mt-3 text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
            >
              Return to Cart
            </button>
          </div>
        </div>
      </div>

      <!-- Payment Content -->
      <div v-else-if="booking" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: Booking Summary -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6">
          <h3 class="text-xl font-black text-primary-light dark:text-primary-dark mb-4">
            Booking Details
          </h3>

          <div class="space-y-4">
            <!-- Booking Code -->
            <div class="pb-4 border-b border-slate-200 dark:border-slate-700">
              <p class="text-xs text-secondary-light dark:text-secondary-dark mb-1">Booking Code</p>
              <p class="text-lg font-black text-primary">{{ booking.booking_code }}</p>
            </div>

            <!-- Tour Info -->
            <div class="pb-4 border-b border-slate-200 dark:border-slate-700">
              <p class="text-xs text-secondary-light dark:text-secondary-dark mb-1">Tour</p>
              <p class="font-bold text-primary-light dark:text-primary-dark">
                {{ booking.tour?.title || 'Tour' }}
              </p>
            </div>

            <!-- Date -->
            <div class="pb-4 border-b border-slate-200 dark:border-slate-700">
              <p class="text-xs text-secondary-light dark:text-secondary-dark mb-1">Date</p>
              <p class="font-semibold text-primary-light dark:text-primary-dark">
                {{ formatDate(booking.tour_date) }}
              </p>
            </div>

            <!-- Participants -->
            <div class="pb-4 border-b border-slate-200 dark:border-slate-700">
              <p class="text-xs text-secondary-light dark:text-secondary-dark mb-2">Participants</p>
              <div class="space-y-1 text-sm">
                <div v-if="booking.participants?.adults" class="flex justify-between">
                  <span class="text-secondary-light dark:text-secondary-dark">Adults</span>
                  <span class="font-semibold text-primary-light dark:text-primary-dark">
                    {{ booking.participants.adults }}
                  </span>
                </div>
                <div v-if="booking.participants?.children" class="flex justify-between">
                  <span class="text-secondary-light dark:text-secondary-dark">Children</span>
                  <span class="font-semibold text-primary-light dark:text-primary-dark">
                    {{ booking.participants.children }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="pb-4 border-b border-slate-200 dark:border-slate-700">
              <p class="text-xs text-secondary-light dark:text-secondary-dark mb-2">Customer</p>
              <div class="space-y-1 text-sm">
                <p class="font-semibold text-primary-light dark:text-primary-dark">
                  {{ booking.customer?.name }}
                </p>
                <p class="text-secondary-light dark:text-secondary-dark">
                  {{ booking.customer?.email }}
                </p>
              </div>
            </div>

            <!-- Total -->
            <div class="pt-2">
              <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-primary-light dark:text-primary-dark">
                  {{ paymentMode === 'advance' && hasAdvanceOption ? 'Pagas ahora' : 'Total' }}
                </span>
                <span class="text-2xl font-black text-primary">
                  ${{ payNowAmount.toFixed(2) }} {{ booking.pricing?.currency || 'USD' }}
                  <span v-if="allBookings.length > 1" class="block text-xs font-semibold text-slate-500 mt-1">
                    {{ allBookings.length }} tours
                  </span>
                </span>
              </div>
              <div v-if="paymentMode === 'advance' && hasAdvanceOption" class="flex justify-between items-center mt-1 text-xs text-slate-500">
                <span>Saldo a pagar el día del tour</span>
                <span class="font-semibold">${{ balanceAmount.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Payment Method -->
        <div>
          <!-- Payment mode (deposit vs full) — only when the tour offers a deposit -->
          <div v-if="hasAdvanceOption" class="mb-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">¿Cuánto deseas pagar ahora?</p>
            <label
              class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
              :class="paymentMode === 'advance' ? 'border-primary bg-primary/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input type="radio" v-model="paymentMode" value="advance" class="text-primary focus:ring-primary" />
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Pagar adelanto</p>
                <p class="text-[11px] text-slate-500">Saldo ${{ (fullTotal - advanceTotal).toFixed(2) }} el día del tour</p>
              </div>
              <span class="text-sm font-black text-primary">${{ advanceTotal.toFixed(2) }}</span>
            </label>
            <label
              class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
              :class="paymentMode === 'full' ? 'border-primary bg-primary/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input type="radio" v-model="paymentMode" value="full" class="text-primary focus:ring-primary" />
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Pagar todo ahora</p>
                <p class="text-[11px] text-slate-500">Sin saldo pendiente</p>
              </div>
              <span class="text-sm font-black text-primary">${{ fullTotal.toFixed(2) }}</span>
            </label>
          </div>

          <!-- Recoverable payment error (cancellation, declined card, etc.) -->
          <div v-if="paymentError" class="mb-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <Icon name="material-symbols:info-outline" class="text-amber-600 dark:text-amber-400 text-xl" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ paymentError }}</p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">Puedes intentarlo de nuevo abajo.</p>
              </div>
              <button @click="paymentError = null" class="text-amber-600 hover:text-amber-700">
                <Icon name="material-symbols:close" class="text-lg" />
              </button>
            </div>
          </div>
          <ClientOnly>
            <template v-if="paymentConfig?.paypal_client_id">
              <PaymentPayPalCheckout
                :client-id="paymentConfig.paypal_client_id"
                :amount="payNowAmount"
                :currency="'USD'"
                :description="allBookings.length > 1 ? `Incalake - ${allBookings.length} tours` : `Booking ${booking.booking_code} - ${booking.tour?.title || 'Tour'}`"
                :customer-email="booking.customer?.email || ''"
                :customer-first-name="booking.customer?.first_name || ''"
                :customer-last-name="booking.customer?.last_name || ''"
                :customer-phone="booking.customer?.phone || ''"
                :customer-country="booking.customer?.country || ''"
                @success="handlePaymentSuccess"
                @error="handlePaymentError"
              />
            </template>
            <template v-else>
              <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
                <div class="flex items-start gap-3">
                  <Icon name="material-symbols:warning-outline" class="text-yellow-600 dark:text-yellow-400 text-2xl" />
                  <div>
                    <h3 class="font-bold text-yellow-900 dark:text-yellow-100 mb-1">Configuration Error</h3>
                    <p class="text-yellow-700 dark:text-yellow-300">Payment gateway is not configured properly.</p>
                  </div>
                </div>
              </div>
            </template>
            <template #fallback>
              <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            </template>
          </ClientOnly>
        </div>
      </div>
    </div>

    <!-- Processing Payment Modal -->
    <Teleport to="body">
      <div
        v-if="processingPayment"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      >
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl p-8 max-w-md w-full text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto mb-4"></div>
          <h3 class="text-xl font-black text-primary-light dark:text-primary-dark mb-2">
            Confirming Payment
          </h3>
          <p class="text-secondary-light dark:text-secondary-dark">
            Please wait while we confirm your payment...
          </p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
// Store useBookingStore and useCartStore are auto-imported

// SEO Meta Tags
useHead({
  title: 'Payment - Complete Your Booking',
  meta: [
    {
      name: 'description',
      content: 'Complete your payment securely with PayPal.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const cartStore = useCartStore()

const loading = ref(true)
const error = ref<string | null>(null)
const paymentError = ref<string | null>(null)
const processingPayment = ref(false)
const booking = ref<any>(null)
const allBookings = ref<any[]>([])
const paymentConfig = ref<any>(null)

// Multi-tour cart: one PayPal capture for the SUM of every booking. The
// customer can pay the full total or the advance (deposit) when offered.
const fullTotal = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.total || 0), 0)
)
const advanceTotal = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.amount_to_pay ?? b.pricing?.total ?? 0), 0)
)
const hasAdvanceOption = computed(() => advanceTotal.value > 0 && advanceTotal.value < fullTotal.value - 0.01)
const paymentMode = ref<'full' | 'advance'>('full')
const payNowAmount = computed(() =>
  paymentMode.value === 'advance' && hasAdvanceOption.value ? advanceTotal.value : fullTotal.value
)
const balanceAmount = computed(() => Math.max(0, fullTotal.value - payNowAmount.value))

onMounted(async () => {
  try {
    // Query param may be a single code or "BK-1,BK-2" for a multi-tour cart.
    const bookingParam = route.query.booking as string
    const email = route.query.email as string

    if (!bookingParam) {
      throw new Error('Booking code is required')
    }

    const codes = bookingParam.split(',').map(c => c.trim()).filter(Boolean)
    for (const code of codes) {
      const response: any = await bookingStore.getBooking(code, email)
      const bookingData = response?.booking || response?.data || response
      if (bookingData && bookingData.booking_code) {
        allBookings.value.push(bookingData)
      }
    }

    if (allBookings.value.length === 0) {
      throw new Error('Booking not found')
    }

    // Primary booking drives display + customer fields (same as Culqi page).
    booking.value = allBookings.value[0]

    // Get payment configuration
    const { api } = useApi()
    const configResponse: any = await api('/payment/config')
    paymentConfig.value = configResponse?.data || configResponse
    console.log('[PayPal Page] Payment config:', paymentConfig.value)

    if (!paymentConfig.value?.paypal_client_id) {
      throw new Error('PayPal no esta configurado correctamente')
    }

    // Default to the deposit when the tour offers it (customer can switch).
    if (hasAdvanceOption.value) paymentMode.value = 'advance'

    loading.value = false

  } catch (err: any) {
    console.error('Error loading payment:', err)
    error.value = err.message || 'Failed to load payment information'
    loading.value = false
  }
})

// Format date helper
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Handle successful payment
const handlePaymentSuccess = async (orderId: string, paymentData: any) => {
  try {
    processingPayment.value = true

    // One PayPal order/capture for the whole group; backend marks all paid
    // and sends ONE consolidated confirmation email.
    const groupIds = allBookings.value.map(b => b.id)
    const result = await bookingStore.confirmPayPalPayment(
      allBookings.value[0].id,
      orderId,
      paymentData,
      groupIds,
      paymentMode.value
    )

    if (!result) {
      throw new Error('Payment confirmation failed')
    }

    // Clear cart after successful payment
    cartStore.clearCart()

    // One code in the URL — the confirmation page resolves the whole
    // multi-tour group from the payment record on the backend.
    const email = route.query.email as string || booking.value.customer?.email || ''
    router.push(`/booking-confirmation/${allBookings.value[0].booking_code}?email=${encodeURIComponent(email)}`)

  } catch (err: any) {
    console.error('Payment confirmation error:', err)
    error.value = err.message || 'Failed to confirm payment'
    processingPayment.value = false
  }
}

// Handle payment error (non-fatal, user can retry)
const handlePaymentError = (errorMsg: string) => {
  paymentError.value = errorMsg
  console.warn('Payment error (recoverable):', errorMsg)
}
</script>
