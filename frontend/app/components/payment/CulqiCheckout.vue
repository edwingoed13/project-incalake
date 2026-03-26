<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-2xl">credit_card</span>
      </div>
      <div>
        <h3 class="text-lg font-black text-primary-light dark:text-primary-dark">
          Secure Payment
        </h3>
        <p class="text-sm text-secondary-light dark:text-secondary-dark">
          Cards or Yape
        </p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-xl">error</span>
        <div>
          <p class="text-sm font-semibold text-red-900 dark:text-red-100">Payment Error</p>
          <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ errorMessage }}</p>
        </div>
      </div>
    </div>

    <div class="space-y-4">
      <!-- Payment Methods Available -->
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-blue-100 dark:border-blue-800">
        <h4 class="text-sm font-bold text-primary-light dark:text-primary-dark mb-3 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
          Available Payment Methods
        </h4>
        <ul class="space-y-2 text-sm text-secondary-light dark:text-secondary-dark">
          <li class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-base">done</span>
            <span><strong>Cards:</strong> Visa, Mastercard, Amex, Diners</span>
          </li>
          <li class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-base">done</span>
            <span><strong>Yape:</strong> Quick payment from your phone</span>
          </li>
        </ul>
      </div>

      <!-- Security Badge -->
      <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800">
        <div class="flex items-start gap-2">
          <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-xl">verified_user</span>
          <div>
            <p class="text-xs font-semibold text-green-900 dark:text-green-100">100% Secure Payments</p>
            <ul class="text-xs text-green-700 dark:text-green-300 mt-1 space-y-0.5">
              <li>✓ 3DS protection for international cards</li>
              <li>✓ End-to-end encryption</li>
              <li>✓ SSL security certificate</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Pay Button -->
      <button
        @click="openCulqiCheckout"
        :disabled="processing"
        class="w-full bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary disabled:from-gray-400 disabled:to-gray-500 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-3 transform hover:scale-[1.02] disabled:transform-none disabled:hover:scale-100"
      >
        <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
        <span v-else class="material-symbols-outlined text-xl">lock</span>
        <span v-if="processing">Processing payment...</span>
        <span v-else>Pay with Culqi</span>
      </button>

      <!-- Trust Badge -->
      <div class="flex items-center justify-center gap-2 text-xs text-secondary-light dark:text-secondary-dark">
        <span class="material-symbols-outlined text-base">shield</span>
        <span>Secure and encrypted transaction by Culqi</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

interface Props {
  publicKey: string
  amount: number
  currency: string
  description: string
  customerEmail: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  success: [token: string, paymentData: any]
  error: [error: string]
}>()

const processing = ref(false)
const errorMessage = ref<string | null>(null)
const culqiInstance = ref<any>(null)

// Culqi amount must be in cents
const amountInCents = computed(() => Math.round(props.amount * 100))

const initializeCulqiCustomCheckout = () => {
  // Check if CulqiCheckout is available
  if (typeof window.CulqiCheckout === 'undefined') {
    errorMessage.value = 'Error loading Culqi. Please reload the page.'
    return
  }

  // Settings configuration
  const settings = {
    title: 'Inca Lake Travel',
    currency: props.currency,
    amount: amountInCents.value,
  }

  // Client configuration
  const client = {
    email: props.customerEmail,
  }

  // Payment methods configuration (only cards and Yape)
  const paymentMethods = {
    tarjeta: true,  // Credit/Debit cards
    yape: true,     // Yape mobile payment
  }

  // Options configuration
  const options = {
    lang: 'auto',
    modal: true,
    paymentMethods: paymentMethods,
    paymentMethodsSort: ['tarjeta', 'yape'],
  }

  // Appearance configuration with sliderTop menu
  const appearance = {
    theme: 'default',
    menuType: 'sliderTop', // sliderTop menu style
    buttonCardPayText: 'Pay now',
    defaultStyle: {
      bannerColor: '#0077cc',
      buttonBackground: '#0077cc',
      menuColor: '#0077cc',
      linksColor: '#0077cc',
      buttonTextColor: '#ffffff',
      priceColor: '#1a202c',
    },
  }

  // Complete configuration
  const config = {
    settings,
    client,
    options,
    appearance,
  }

  console.log('🔧 Culqi config:', config)

  // Create Culqi instance
  culqiInstance.value = new window.CulqiCheckout(props.publicKey, config)

  // Set callback handler
  culqiInstance.value.culqi = handleCulqiAction
}

const handleCulqiAction = () => {
  const Culqi = culqiInstance.value

  if (Culqi.token) {
    // Token created successfully
    const token = Culqi.token.id
    const paymentData = {
      token: Culqi.token,
      email: Culqi.token.email,
      // Card data (only for card payments)
      card_brand: Culqi.token.card_brand || null,
      card_number: Culqi.token.card_number || null,
      card_type: Culqi.token.card_type || null,
      // Payment type (tarjeta, yape, etc.)
      type: Culqi.token.type || 'tarjeta'
    }

    Culqi.close()
    processing.value = false

    console.log('✅ Culqi token created:', token)
    emit('success', token, paymentData)

  } else if (Culqi.order) {
    // Order created successfully (for PagoEfectivo, billeteras, etc.)
    const order = Culqi.order
    Culqi.close()
    processing.value = false

    console.log('✅ Culqi order created:', order)

  } else if (Culqi.error) {
    // Error occurred
    const error = Culqi.error
    errorMessage.value = error.user_message || 'Error processing payment'
    processing.value = false

    console.error('❌ Culqi error:', error)
    emit('error', errorMessage.value)
  }
}

const openCulqiCheckout = () => {
  if (!culqiInstance.value) {
    errorMessage.value = 'Culqi is not properly initialized.'
    return
  }

  processing.value = true
  errorMessage.value = null

  console.log('🚀 Opening Culqi checkout...')

  // Open Culqi custom checkout
  try {
    culqiInstance.value.open()
  } catch (error) {
    console.error('❌ Error opening Culqi:', error)
    errorMessage.value = 'Error opening Culqi checkout'
    processing.value = false
  }
}

onMounted(() => {
  initializeCulqiCustomCheckout()

  // Listen for Culqi close event
  window.addEventListener('message', (event) => {
    if (event.data === 'CULQI_CLOSE') {
      processing.value = false
    }
  })
})
</script>
