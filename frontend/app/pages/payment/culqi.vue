<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark py-8 lg:py-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl lg:text-4xl font-black text-primary-light dark:text-primary-dark mb-2">
          Complete Your Payment
        </h1>
        <p class="text-secondary-light dark:text-secondary-dark">
          Secure payment powered by Culqi
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
          <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">error</span>
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
                <span class="text-lg font-bold text-primary-light dark:text-primary-dark">Total</span>
                <span class="text-2xl font-black text-primary">
                  ${{ booking.pricing?.total?.toFixed(2) || '0.00' }} {{ booking.pricing?.currency || 'USD' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Payment Method -->
        <div>
          <CulqiCheckout
            v-if="paymentConfig?.culqi_public_key"
            :public-key="paymentConfig.culqi_public_key"
            :amount="booking.pricing?.total || 0"
            :currency="booking.pricing?.currency || 'USD'"
            :description="`Booking ${booking.booking_code} - ${booking.tour?.title || 'Tour'}`"
            :customer-email="booking.customer?.email || ''"
            @success="handlePaymentSuccess"
            @error="handlePaymentError"
          />

          <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-2xl">warning</span>
              <div>
                <h3 class="font-bold text-yellow-900 dark:text-yellow-100 mb-1">Configuration Error</h3>
                <p class="text-yellow-700 dark:text-yellow-300">Payment gateway is not configured properly.</p>
              </div>
            </div>
          </div>
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
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
// Stores and composables like useBookingStore, useCartStore, and useApi are auto-imported

// SEO Meta Tags
useHead({
  title: 'Payment - Complete Your Booking',
  meta: [
    {
      name: 'description',
      content: 'Complete your payment securely with Culqi.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ],
  script: [
    {
      src: 'https://checkout.culqi.com/js/v4',
      async: true
    }
  ]
})

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const cartStore = useCartStore()

const loading = ref(true)
const error = ref<string | null>(null)
const processingPayment = ref(false)
const booking = ref<any>(null)
const paymentConfig = ref<any>(null)

onMounted(async () => {
  try {
    // Get booking code and email from query params
    const bookingCode = route.query.booking as string
    const email = route.query.email as string

    if (!bookingCode) {
      throw new Error('Booking code is required')
    }

    // Load booking details with email for verification
    const bookingData = await bookingStore.getBooking(bookingCode, email)

    if (!bookingData) {
      throw new Error('Booking not found')
    }

    booking.value = bookingData.booking || bookingData

    // Get payment configuration
    const { api } = useApi()
    const configResponse = await api('/payment/config')
    paymentConfig.value = configResponse.data || configResponse

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
const handlePaymentSuccess = async (token: string, paymentData: any) => {
  try {
    processingPayment.value = true

    // Confirm payment with backend
    const result = await bookingStore.confirmCulqiPayment(
      booking.value.id,
      token,
      paymentData
    )

    if (!result) {
      throw new Error('Payment confirmation failed')
    }

    // Clear cart after successful payment
    cartStore.clearCart()

    // Redirect to booking confirmation
    router.push(`/booking-confirmation/${booking.value.booking_code}`)

  } catch (err: any) {
    console.error('Payment confirmation error:', err)
    error.value = err.message || 'Failed to confirm payment'
    processingPayment.value = false
  }
}

// Handle payment error
const handlePaymentError = (errorMsg: string) => {
  error.value = errorMsg
  console.error('Payment error:', errorMsg)
}
</script>
