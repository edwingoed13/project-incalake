<template>
  <div class="min-h-screen bg-background-light pt-24 pb-8 lg:pt-28 lg:pb-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-6xl">
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
      <div v-else-if="booking" class="grid grid-cols-1 lg:grid-cols-5 gap-4 lg:gap-6">
        <!-- Left: Order Summary — same cards, in the same order, as the Culqi
             page. This column used to be a single box of label/value rows,
             which made the two payment pages look like different products. -->
        <div class="lg:col-span-3 space-y-4">
          <!-- Header -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-1">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <Icon name="material-symbols:receipt-long-outline" class="text-primary text-2xl" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-800">{{ t('order_summary') }}</h2>
                <p class="text-xs text-slate-400">{{ allBookings.length }} {{ allBookings.length === 1 ? t('tour_selected') : t('tours_selected') }}</p>
              </div>
            </div>
          </div>

          <!-- Tour Cards -->
          <div
            v-for="(b, idx) in allBookings"
            :key="b.booking_code"
            class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
          >
            <div class="p-5">
              <div class="flex gap-4">
                <div class="size-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-sm font-bold shrink-0">
                  {{ idx + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm font-bold text-slate-800 mb-1">{{ b.tour?.title || 'Tour' }}</h3>
                  <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1">
                      <Icon name="material-symbols:calendar-today-outline" class="text-xs" />
                      {{ formatDate(b.tour_date) }}
                    </span>
                    <span class="flex items-center gap-1">
                      <Icon name="material-symbols:group-outline" class="text-xs" />
                      {{ b.participants?.adults || 0 }} {{ t('adults').toLowerCase() }}
                      <template v-if="b.participants?.children">, {{ b.participants.children }} {{ t('children_label').toLowerCase() }}</template>
                    </span>
                  </div>
                </div>
                <div v-if="allBookings.length > 1" class="text-right shrink-0">
                  <p class="text-base font-black text-slate-800">{{ currencyStore.formatConverted(b.pricing?.subtotal || b.pricing?.total || 0) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">{{ t('customer') }}</h4>
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold">
                {{ getInitials(booking.customer?.name || '') }}
              </div>
              <div>
                <p class="text-sm font-bold text-slate-800">{{ booking.customer?.name }}</p>
                <p class="text-xs text-slate-500">{{ booking.customer?.email }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Payment Method -->
        <div class="lg:col-span-2 space-y-4">
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
          <!-- One card holding the amount and the button that charges it,
               same as the Culqi page. The totals used to sit in the left
               column, a screen away from the action. -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
          <CheckoutOrderTotals
            class="mb-5"
            :items-label="`${t('subtotal')} (${allBookings.length} ${allBookings.length === 1 ? t('booking') : t('bookings')})`"
            :subtotal="subtotalAmount"
            :tax="taxAmount"
            :total="payNowAmount"
            :total-label="paymentMode === 'advance' && hasAdvanceOption ? t('pay_now_label') : t('total_to_pay')"
            :balance-label="t('balance_due_day')"
            :balance="paymentMode === 'advance' && hasAdvanceOption ? balanceAmount : null"
            :usd-approx="grandTotal"
          />

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
const { t, locale } = useI18n()

// The booking pages showed every date in en-US regardless of the language the
// customer booked in — "Thu, Sep 3, 2026" on a Spanish reservation. Map the
// active locale to a real BCP-47 tag so the date speaks the same language as
// the page around it.
const INTL_LOCALES: Record<string, string> = {
  es: 'es-ES', en: 'en-US', pt: 'pt-BR', fr: 'fr-FR', de: 'de-DE', it: 'it-IT',
}
const intlLocale = computed(() => INTL_LOCALES[String(locale.value)] || 'es-ES')


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

// Kept out of the address bar once it has been used — see below.
const verifiedEmail = ref('')

onMounted(async () => {
  try {
    // Query param may be a single code or "BK-1,BK-2" for a multi-tour cart.
    const bookingParam = route.query.booking as string
    const email = route.query.email as string
    verifiedEmail.value = email || ''

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

    // Credentials have done their job: take the customer's email out of the
    // address bar so it stops travelling through history, screenshots and logs.
    if (typeof window !== 'undefined' && window.history?.replaceState) {
      window.history.replaceState({}, '', window.location.pathname)
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

// Initials for the customer avatar, same as the Culqi page.
function getInitials(name: string) {
  return (name || '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(p => p[0]?.toUpperCase() || '')
    .join('')
}

// Format date helper
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString(intlLocale.value, {
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
    const email = booking.value.customer?.email || verifiedEmail.value || ''
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
