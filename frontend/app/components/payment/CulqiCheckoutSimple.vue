<script setup lang="ts">
import { ref, onMounted } from 'vue'

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
  console.log('💳 CulqiCheckoutSimple mounted!')
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

    const config = {
      settings: {
        title: 'Inca Lake Travel',
        currency: props.currency || 'USD',
        amount: amountInCents,
        description: props.description || 'Tour Booking',
      },
      client: {
        email: props.customerEmail || '',
      },
      options: {
        lang: 'auto',
        installments: false,
        modal: true,
        paymentMethods: {
          tarjeta: true,
          yape: false,
          billetera: false,
          bancaMovil: false,
          agente: false,
          cuotealo: false,
        },
      },
      appearance: {
        theme: 'default',
        hiddenCulqiLogo: false,
        hiddenBannerContent: false,
        hiddenBanner: false,
        hiddenToolBarAmount: false,
        menuType: 'sidebar',
        buttonCardPayText: `Pay ${props.currency} ${props.amount.toFixed(2)}`,
        defaultStyle: {
          bannerColor: '#667eea',
          buttonBackground: '#667eea',
          menuColor: '#764ba2',
          linksColor: '#667eea',
          buttonTextColor: '#ffffff',
          priceColor: '#667eea',
        },
      },
    }

    console.log('Config:', config)

    // Create instance
    const CulqiInstance = new window.CulqiCheckout(props.publicKey, config)
    console.log('✅ Instance created')

    // Set callback
    CulqiInstance.culqi = () => {
      console.log('📞 Callback executed')

      if (CulqiInstance.token) {
        console.log('✅ Token received:', CulqiInstance.token)

        const paymentData = {
          token: CulqiInstance.token,
          email: CulqiInstance.token.email,
          card_brand: CulqiInstance.token.card_brand || null,
          card_number: CulqiInstance.token.card_number || null,
          type: 'card'
        }

        // Close modal
        if (typeof CulqiInstance.close === 'function') {
          CulqiInstance.close()
        }

        emit('success', CulqiInstance.token.id, paymentData)

      } else if (CulqiInstance.error) {
        console.error('❌ Error:', CulqiInstance.error)
        errorMessage.value = CulqiInstance.error.user_message || 'Payment error'
        emit('error', errorMessage.value)
      }
    }

    // Store instance globally
    window.Culqi = CulqiInstance
    console.log('✅ Culqi initialized and ready')

  } catch (error: any) {
    console.error('❌ Error initializing Culqi:', error)
    errorMessage.value = 'Failed to initialize payment: ' + error.message
  }
}

// Open payment modal
const openPayment = () => {
  console.log('🔍 Opening payment...')

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
    console.log('📦 Opening Culqi modal...')
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
      <span v-else-if="!culqiReady">Loading...</span>
      <span v-else>Pay {{ currency }} {{ amount.toFixed(2) }}</span>
    </button>

    <!-- Test Mode Info -->
    <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
      <p class="text-xs text-amber-700 dark:text-amber-300">
        <strong>Test Card:</strong> 4111 1111 1111 1111 | 12/{{ new Date().getFullYear() + 1 }} | 123
      </p>
    </div>
  </div>
</template>