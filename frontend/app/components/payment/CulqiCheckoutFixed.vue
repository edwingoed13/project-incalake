<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Declare CulqiCheckout on window
declare global {
  interface Window {
    CulqiCheckout: any
    Culqi: any
  }
}

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
const culqiReady = ref(false)

// Culqi amount must be in cents
const amountInCents = Math.round(props.amount * 100)

// Initialize Culqi when component mounts
onMounted(() => {
  console.log('💳 CulqiCheckoutFixed mounted!')
  console.log('Public Key:', props.publicKey)
  console.log('Amount:', props.amount, props.currency)

  // Check if CulqiCheckout is available
  const checkCulqi = setInterval(() => {
    if (typeof window !== 'undefined' && window.CulqiCheckout) {
      clearInterval(checkCulqi)
      console.log('✅ CulqiCheckout is available!')
      culqiReady.value = true
      initializeCulqi()
    }
  }, 100)

  // Timeout after 10 seconds
  setTimeout(() => {
    clearInterval(checkCulqi)
    if (!culqiReady.value) {
      console.error('❌ CulqiCheckout not available after 10 seconds')
      errorMessage.value = 'Payment system failed to load'
    }
  }, 10000)
})

// Initialize Culqi instance
const initializeCulqi = () => {
  try {
    console.log('🚀 Initializing Culqi Custom Checkout...')

    // Configuration according to official documentation
    const settings = {
      title: 'Inca Lake Travel',
      currency: props.currency || 'USD',
      amount: amountInCents,
    }

    const client = {
      email: props.customerEmail || '',
    }

    const paymentMethods = {
      tarjeta: true,
      yape: false,
      billetera: false,
      bancaMovil: false,
      agente: false,
      cuotealo: false,
    }

    const options = {
      lang: 'es',
      installments: false,
      modal: true,
      paymentMethods: paymentMethods,
      paymentMethodsSort: Object.keys(paymentMethods)
    }

    const appearance = {
      theme: 'default',
      hiddenCulqiLogo: false,
      hiddenBannerContent: false,
      hiddenBanner: false,
      hiddenToolBarAmount: false,
      hiddenEmail: false,
      menuType: 'sidebar',
      buttonCardPayText: `Pagar ${props.currency} ${props.amount.toFixed(2)}`,
      logo: null,
      defaultStyle: {
        bannerColor: '#667eea',
        buttonBackground: '#667eea',
        menuColor: '#764ba2',
        linksColor: '#667eea',
        buttonTextColor: '#ffffff',
        priceColor: '#667eea'
      }
    }

    const config = {
      settings,
      client,
      options,
      appearance
    }

    console.log('Config:', config)

    // Create instance according to documentation
    window.Culqi = new window.CulqiCheckout(props.publicKey, config)
    console.log('✅ Culqi instance created')

    // IMPORTANT: Set callback according to documentation
    window.Culqi.culqi = function() {
      console.log('📞 Culqi callback executed!')

      if (window.Culqi.token) {
        console.log('✅ Token received:', window.Culqi.token)

        const paymentData = {
          token: window.Culqi.token,
          email: window.Culqi.token.email,
          card_brand: window.Culqi.token.card_brand || null,
          card_number: window.Culqi.token.card_number || null,
          card_type: window.Culqi.token.card_type || null,
          type: 'card'
        }

        // Close modal
        window.Culqi.close()

        emit('success', window.Culqi.token.id, paymentData)

      } else if (window.Culqi.order) {
        console.log('✅ Order received:', window.Culqi.order)
        // Handle order if needed in the future
        window.Culqi.close()

      } else if (window.Culqi.error) {
        console.error('❌ Error:', window.Culqi.error)
        errorMessage.value = window.Culqi.error.user_message || 'Payment error'
        emit('error', errorMessage.value)
      }
    }

    console.log('✅ Culqi initialized and ready')

  } catch (error: any) {
    console.error('❌ Error initializing Culqi:', error)
    errorMessage.value = 'Failed to initialize payment: ' + error.message
  }
}

// Open payment modal
const openPayment = () => {
  console.log('🔍 Opening payment modal...')

  if (!culqiReady.value) {
    console.error('Culqi not ready')
    errorMessage.value = 'Payment system not ready'
    return
  }

  if (!window.Culqi) {
    console.error('Culqi instance not found')
    errorMessage.value = 'Payment not initialized'
    return
  }

  processing.value = true
  errorMessage.value = null

  try {
    console.log('📦 Opening Culqi modal with Culqi.open()...')
    // Use the correct method according to documentation
    window.Culqi.open()
    console.log('✅ Modal opened')
    processing.value = false

  } catch (error: any) {
    console.error('❌ Error opening modal:', error)
    errorMessage.value = 'Failed to open payment: ' + error.message
    processing.value = false
  }
}
</script>

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
          Powered by Culqi
        </p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <p class="text-sm text-red-700 dark:text-red-300">{{ errorMessage }}</p>
    </div>

    <!-- Payment Methods Info -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-blue-100 dark:border-blue-800 mb-4">
      <h4 class="text-sm font-bold text-primary-light dark:text-primary-dark mb-2">
        💳 Accepted Cards
      </h4>
      <div class="flex gap-2">
        <span class="text-xs bg-white dark:bg-slate-800 px-2 py-1 rounded">Visa</span>
        <span class="text-xs bg-white dark:bg-slate-800 px-2 py-1 rounded">Mastercard</span>
        <span class="text-xs bg-white dark:bg-slate-800 px-2 py-1 rounded">Amex</span>
        <span class="text-xs bg-white dark:bg-slate-800 px-2 py-1 rounded">Diners</span>
      </div>
    </div>

    <!-- Pay Button -->
    <button
      type="button"
      @click="openPayment"
      :disabled="processing || !culqiReady"
      class="w-full bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary disabled:from-gray-400 disabled:to-gray-500 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-3"
    >
      <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
      <span v-else-if="!culqiReady" class="material-symbols-outlined text-xl animate-pulse">hourglass_empty</span>
      <span v-else class="material-symbols-outlined text-xl">lock</span>
      <span v-if="processing">Processing...</span>
      <span v-else-if="!culqiReady">Loading payment system...</span>
      <span v-else>Pay {{ currency }} {{ amount.toFixed(2) }}</span>
    </button>

    <!-- Test Mode Info -->
    <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
      <p class="text-xs text-amber-700 dark:text-amber-300">
        <strong>Test Card:</strong> 4111 1111 1111 1111 | 12/2025 | 123
      </p>
    </div>

    <!-- Security Badge -->
    <div class="flex items-center justify-center gap-2 text-xs text-secondary-light dark:text-secondary-dark mt-3">
      <span class="material-symbols-outlined text-base">shield</span>
      <span>Secure payment powered by Culqi</span>
    </div>
  </div>
</template>