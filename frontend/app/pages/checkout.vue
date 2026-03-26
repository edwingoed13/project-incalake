<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark py-8 lg:py-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-7xl">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl lg:text-4xl font-black text-primary-light dark:text-primary-dark mb-2">
          Complete Your Booking
        </h1>
        <p class="text-secondary-light dark:text-secondary-dark">
          Fill in your details to secure your reservation
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
              Dismiss
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
          Your cart is empty
        </h2>
        <p class="text-secondary-light dark:text-secondary-dark mb-6">
          Add some tours to your cart before checking out
        </p>
        <button
          @click="router.push('/tours')"
          class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-all duration-200"
        >
          <span class="material-symbols-outlined">explore</span>
          Explore Tours
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
            Processing Your Booking
          </h3>
          <p class="text-secondary-light dark:text-secondary-dark">
            Please wait while we prepare your payment...
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
        customer_email: formData.customer_email,
        customer_phone: formData.customer_phone,
        customer_country: formData.customer_country,
        customer_notes: formData.customer_notes || '',
        pickup_location: formData.pickup_location || undefined,
        payment_method: formData.payment_method || 'culqi'
      }

      bookings.push(checkoutData)
    }

    // For now, create booking for the first item
    // TODO: Support multiple bookings in one transaction
    const response = await bookingStore.createBooking(bookings[0])

    if (!response || !response.booking) {
      throw new Error('Failed to create booking')
    }

    const booking = response.booking
    console.log('Booking created successfully:', booking.booking_code)

    // Redirect to payment page based on payment method
    const paymentMethod = formData.payment_method || 'culqi'

    // Encode email for URL
    const encodedEmail = encodeURIComponent(formData.customer_email)

    if (paymentMethod === 'culqi') {
      router.push(`/payment/culqi?booking=${booking.booking_code}&email=${encodedEmail}`)
    } else if (paymentMethod === 'paypal') {
      router.push(`/payment/paypal?booking=${booking.booking_code}&email=${encodedEmail}`)
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
