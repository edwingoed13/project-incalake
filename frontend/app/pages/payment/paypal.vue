<template>
  <div class="min-h-screen bg-background-light pt-24 pb-8 lg:pt-28 lg:pb-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl lg:text-4xl font-black text-primary-light mb-2">
          {{ t('payment_complete_title') }}        </h1>
        <p class="text-secondary-light">
          {{ t('payment_secure_paypal') }}        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="spinner size-12 mb-4"></div>
        <p class="text-secondary-light">{{ t('loading_payment') }}</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8">
        <div class="flex items-start gap-3">
          <Icon name="material-symbols:error-outline" class="text-red-600 text-2xl" />
          <div>
            <h3 class="font-bold text-red-900 mb-1">Error Loading Payment</h3>
            <p class="text-red-700">{{ error }}</p>
            <button
              @click="router.push(localePath('/cart'))"
              class="mt-3 text-sm font-semibold text-red-600 hover:text-red-700"
            >
              {{ t('return_cart') }}            </button>
          </div>
        </div>
      </div>

      <!-- Payment Content -->
      <div v-else-if="booking" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: Booking Summary -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <h3 class="text-xl font-black text-primary-light mb-4">
            {{ t('booking_details') }}          </h3>

          <div class="space-y-4">
            <!-- Booking Code -->
            <div class="pb-4 border-b border-slate-200">
              <p class="text-xs text-secondary-light mb-1">{{ t('booking_code_label') }}</p>
              <p class="text-lg font-black text-primary">{{ booking.booking_code }}</p>
            </div>

            <!-- Tour Info -->
            <div class="pb-4 border-b border-slate-200">
              <p class="text-xs text-secondary-light mb-1">Tour</p>
              <p class="font-bold text-primary-light">
                {{ booking.tour?.title || 'Tour' }}
              </p>
            </div>

            <!-- Date -->
            <div class="pb-4 border-b border-slate-200">
              <p class="text-xs text-secondary-light mb-1">{{ t('date') }}</p>
              <p class="font-semibold text-primary-light">
                {{ formatDate(booking.tour_date) }}
              </p>
            </div>

            <!-- Participants -->
            <div class="pb-4 border-b border-slate-200">
              <p class="text-xs text-secondary-light mb-2">{{ t('travelers') }}</p>
              <div class="space-y-1 text-sm">
                <div v-if="booking.participants?.adults" class="flex justify-between">
                  <span class="text-secondary-light">{{ t('adults') }}</span>
                  <span class="font-semibold text-primary-light">
                    {{ booking.participants.adults }}
                  </span>
                </div>
                <div v-if="booking.participants?.children" class="flex justify-between">
                  <span class="text-secondary-light">{{ t('children_label') }}</span>
                  <span class="font-semibold text-primary-light">
                    {{ booking.participants.children }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="pb-4 border-b border-slate-200">
              <p class="text-xs text-secondary-light mb-2">{{ t('customer') }}</p>
              <div class="space-y-1 text-sm">
                <p class="font-semibold text-primary-light">
                  {{ booking.customer?.name }}
                </p>
                <p class="text-secondary-light">
                  {{ booking.customer?.email }}
                </p>
              </div>
            </div>

            <!-- Total — shared breakdown block (same as cart + Culqi) -->
            <CheckoutOrderTotals
              class="pt-2"
              :items-label="`${t('subtotal')} (${allBookings.length} ${allBookings.length === 1 ? t('booking') : t('bookings')})`"
              :subtotal="subtotalAmount"
              :tax="taxAmount"
              :total="payNowAmount"
              :total-label="paymentMode === 'advance' && hasAdvanceOption ? t('pay_now_label') : t('total_to_pay')"
              :balance-label="t('balance_due_day')"
              :balance="paymentMode === 'advance' && hasAdvanceOption ? balanceAmount : null"
              :usd-approx="payNowAmount"
            />
          </div>
        </div>

        <!-- Right Column: Payment Method -->
        <div>
          <!-- Payment mode (deposit vs full) — only when the tour offers a deposit -->
          <div v-if="hasAdvanceOption" class="mb-4 bg-white rounded-xl border border-slate-200 p-4 space-y-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t('pay_mode_question') }}</p>
            <!-- Full payment first = the default/recommended (same as Culqi) -->
            <label
              class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
              :class="paymentMode === 'full' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="paymentMode" value="full" class="text-primary focus:ring-primary" />
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                  {{ t('pay_full_now') }}                  <span class="px-1.5 py-0.5 rounded bg-trust/10 text-trust text-[10px] font-black uppercase tracking-wide">{{ t('recommended_label') }}</span>
                </p>
                <p class="text-[11px] text-slate-500">{{ t('no_balance_due') }}</p>
              </div>
              <span class="text-sm font-black text-primary">{{ currencyStore.formatConverted(fullTotal) }}</span>
            </label>
            <label
              class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
              :class="paymentMode === 'advance' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
            >
              <input type="radio" v-model="paymentMode" value="advance" class="text-primary focus:ring-primary" />
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-800">{{ t('pay_deposit') }}</p>
                <p class="text-[11px] text-slate-500">{{ t('balance_cash_day', { amount: currencyStore.formatConverted(fullTotal - advanceTotal) }) }}</p>
              </div>
              <span class="text-sm font-black text-primary">{{ currencyStore.formatConverted(advanceTotal) }}</span>
            </label>

            <!-- Partial payment: the balance is collected in person, cash only -->
            <div v-if="paymentMode === 'advance'" class="flex items-start gap-1.5 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
              <Icon name="material-symbols:info-outline" class="text-amber-600 text-sm mt-0.5 shrink-0" />
              <p class="text-[11px] text-amber-800 leading-snug">
                El saldo restante se paga únicamente <strong>en efectivo</strong>, directamente al operador, antes de iniciar el tour.
              </p>
            </div>
          </div>

          <!-- Recoverable payment error (cancellation, declined card, etc.) -->
          <div v-if="paymentError" class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <Icon name="material-symbols:info-outline" class="text-amber-600 text-xl" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-amber-900">{{ paymentError }}</p>
                <p class="text-xs text-amber-700 mt-1">Puedes intentarlo de nuevo abajo.</p>
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
              <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <div class="flex items-start gap-3">
                  <Icon name="material-symbols:warning-outline" class="text-yellow-600 text-2xl" />
                  <div>
                    <h3 class="font-bold text-yellow-900 mb-1">Configuration Error</h3>
                    <p class="text-yellow-700">Payment gateway is not configured properly.</p>
                  </div>
                </div>
              </div>
            </template>
            <template #fallback>
              <div class="flex justify-center py-8">
                <div class="spinner size-8"></div>
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
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center">
          <div class="spinner size-16 mx-auto mb-4"></div>
          <h3 class="text-xl font-black text-primary-light mb-2">
            Confirming Payment
          </h3>
          <p class="text-secondary-light">
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
const currencyStore = useCurrencyStore()
const localePath = useLocalePath()
const { t } = useI18n()

const loading = ref(true)
const error = ref<string | null>(null)
const paymentError = ref<string | null>(null)
const processingPayment = ref(false)
const booking = ref<any>(null)
const allBookings = ref<any[]>([])
const paymentConfig = ref<any>(null)

// Multi-tour cart: one PayPal capture for the SUM of every booking. The
// customer can pay the full total or the advance (deposit) when offered.
// Breakdown mirrors the Culqi page (subtotal + transaction fees) so both
// payment methods present the money identically.
const subtotalAmount = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.subtotal || b.pricing?.total || 0), 0)
)
const taxAmount = computed(() =>
  allBookings.value.reduce((sum, b) => sum + (b.pricing?.tax_amount || 0), 0)
)
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
    router.push(`${localePath(`/booking-confirmation/${allBookings.value[0].booking_code}`)}?email=${encodeURIComponent(email)}`)

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
