<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Declare CulqiCheckout and 3DS on window
declare global {
  interface Window {
    CulqiCheckout: any
    Culqi: any
    Culqi3DS: any
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

    // Initialize 3DS if available
    if (window.Culqi3DS) {
      try {
        window.Culqi3DS.settings = {
          chargeEndpoint: '', // Backend handles the charge, not 3DS directly
          publicKey: props.publicKey,
        }
        console.log('✅ Culqi 3DS initialized')
      } catch (e) {
        console.warn('3DS init skipped:', e)
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
  <div>
    <!-- Error Message -->
    <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <p class="text-sm text-red-700 dark:text-red-300">{{ errorMessage }}</p>
    </div>

    <!-- Pay Button -->
    <button
      type="button"
      @click="openPayment"
      :disabled="processing || !culqiReady"
      class="w-full bg-primary hover:brightness-110 disabled:bg-slate-400 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 active:scale-[0.98]"
    >
      <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
      <span v-else-if="!culqiReady" class="material-symbols-outlined text-lg animate-pulse">hourglass_empty</span>
      <span v-else class="material-symbols-outlined text-lg">lock</span>
      <span v-if="processing">Processing...</span>
      <span v-else-if="!culqiReady">Loading...</span>
      <span v-else>Pay {{ currency }} {{ amount.toFixed(2) }}</span>
    </button>
  </div>
</template>