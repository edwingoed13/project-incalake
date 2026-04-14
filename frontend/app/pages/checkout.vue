<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark pt-24 pb-8 lg:pt-28 lg:pb-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-7xl">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl lg:text-4xl font-black text-primary-light dark:text-primary-dark mb-2">
          {{ t('checkout.title') }}
        </h1>
        <p class="text-secondary-light dark:text-secondary-dark">
          {{ t('checkout.subtitle') }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 mb-8">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">error</span>
          <div>
            <h3 class="font-bold text-red-900 dark:text-red-100 mb-1">Error</h3>
            <p class="text-red-700 dark:text-red-300">{{ error }}</p>
            <button
              @click="error = null"
              class="mt-3 text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
            >
              {{ t('checkout.dismiss') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty Cart State -->
      <div v-else-if="cartStore.isEmpty" class="bg-white dark:bg-slate-900 rounded-xl p-12 text-center shadow-lg">
        <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 mb-4 block" style="font-size: 96px;">
          shopping_cart
        </span>
        <h2 class="text-2xl font-black text-primary-light dark:text-primary-dark mb-2">
          {{ t('checkout.empty_cart') }}
        </h2>
        <p class="text-secondary-light dark:text-secondary-dark mb-6">
          {{ t('checkout.empty_cart_desc') }}
        </p>
        <button
          @click="router.push('/tours')"
          class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-all duration-200"
        >
          <span class="material-symbols-outlined">explore</span>
          {{ t('checkout.explore_tours') }}
        </button>
      </div>

      <!-- Checkout Content -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Checkout Form -->
        <div class="lg:col-span-2">
          <CheckoutForm
            :cart-items="cartStore.items"
            @submit="handleCheckoutSubmit"
          />
        </div>

        <!-- Right Column: Summary Sidebar -->
        <div class="lg:col-span-1">
          <CheckoutSummary />
        </div>
      </div>
    </div>

    <!-- Payment Processing Modal -->
    <Teleport to="body">
      <div
        v-if="processingPayment"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      >
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl p-8 max-w-md w-full text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto mb-4"></div>
          <h3 class="text-xl font-black text-primary-light dark:text-primary-dark mb-2">
            {{ t('checkout.processing') }}
          </h3>
          <p class="text-secondary-light dark:text-secondary-dark">
            {{ t('checkout.processing_desc') }}
          </p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
// In Nuxt 3/4, useCartStore and useBookingStore are auto-imported

const { t } = useI18n()

// SEO Meta Tags
useHead({
  title: 'Checkout - Complete Your Booking',
  meta: [
    {
      name: 'description',
      content: 'Complete your tour booking with secure payment options. Fill in your details and confirm your reservation.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})

const router = useRouter()
const cartStore = useCartStore()
const bookingStore = useBookingStore()

const loading = ref(false)
const error = ref<string | null>(null)
const processingPayment = ref(false)

// Load cart from localStorage on mount
onMounted(() => {
  cartStore.loadFromLocalStorage()

  // Redirect if cart is empty after loading
  if (cartStore.isEmpty) {
    console.warn('Cart is empty, redirecting to tours page')
  }
})

// Handle checkout form submission
const handleCheckoutSubmit = async (formData: any) => {
  try {
    error.value = null
    processingPayment.value = true

    // Prepare booking data for each cart item
    const bookings = []

    for (const item of cartStore.items) {
      const checkoutData = {
        tour_id: item.tourId,
        tour_date: item.selectedDate,
        tour_time: item.selectedTime || undefined,
        adults: item.adults,
        children: item.children,
        infants: 0, // Not implemented yet in cart
        customer_name: formData.customer_name,
        customer_first_name: formData.customer_first_name,
        customer_last_name: formData.customer_last_name,
        customer_email: formData.customer_email,
        customer_phone: formData.customer_phone,
        customer_country: formData.customer_country,
        customer_notes: formData.customer_notes || '',
        pickup_location: formData.pickup_location || undefined,
        payment_method: formData.payment_method || 'culqi',
        // Include pricing information with discounts
        base_price: item.basePrice,
        original_price: item.originalPrice || item.basePrice,
        total_amount: item.total,
        has_offer: item.hasOffer || false,
        offer_discount: item.offerDiscount || 0,
        offer_discount_type: item.offerDiscountType || ''
      }

      bookings.push(checkoutData)
    }

    // Create booking for each cart item
    const bookingCodes: string[] = []
    let totalAmount = 0

    for (const bookingData of bookings) {
      const response = await bookingStore.createBooking(bookingData)
      if (!response || !response.booking) {
        throw new Error('Failed to create booking')
      }
      bookingCodes.push(response.booking.booking_code)
      totalAmount += response.booking.pricing?.total || bookingData.total_amount || 0
      console.log('Booking created:', response.booking.booking_code)
    }

    // Redirect to payment page with all booking codes
    const paymentMethod = formData.payment_method || 'culqi'
    const encodedEmail = encodeURIComponent(formData.customer_email)
    const codesParam = bookingCodes.join(',')

    if (paymentMethod === 'culqi') {
      router.push(`/payment/culqi?booking=${codesParam}&email=${encodedEmail}`)
    } else if (paymentMethod === 'paypal') {
      router.push(`/payment/paypal?booking=${codesParam}&email=${encodedEmail}`)
    } else {
      throw new Error('Invalid payment method')
    }

  } catch (err: any) {
    console.error('Checkout error:', err)
    error.value = err.message || 'An error occurred during checkout. Please try again.'
    processingPayment.value = false
  }
}
</script>
