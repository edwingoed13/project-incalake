<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

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
const culqiLoaded = ref(false)

// Culqi amount must be in cents
const amountInCents = computed(() => Math.round(props.amount * 100))

// Load Culqi Custom Checkout script
const loadCulqiScript = () => {
  return new Promise((resolve, reject) => {
    console.log('🔍 Checking for CulqiCheckout...')

    // Check if CulqiCheckout is already available
    if (typeof window.CulqiCheckout !== 'undefined') {
      console.log('✅ CulqiCheckout already available')
      culqiLoaded.value = true
      resolve(true)
      return
    }

    // Remove any old Culqi v3 scripts
    const oldScripts = document.querySelectorAll('script[src*="checkout.culqi.com"]')
    if (oldScripts.length > 0) {
      console.log('🗑️ Removing old Culqi v3 scripts:', oldScripts.length)
      oldScripts.forEach(s => s.remove())
    }

    // Check if script already exists in DOM
    const existingScript = document.querySelector('script[src*="js.culqi.com/checkout-js"]')
    if (existingScript) {
      console.log('⏳ CulqiCheckout script exists, waiting for it to load...')
      // Wait for CulqiCheckout to be available
      let attempts = 0
      const checkInterval = setInterval(() => {
        attempts++
        console.log(`   Attempt ${attempts}/30: typeof CulqiCheckout =`, typeof window.CulqiCheckout)
        if (typeof window.CulqiCheckout !== 'undefined') {
          clearInterval(checkInterval)
          console.log('✅ CulqiCheckout is now available')
          culqiLoaded.value = true
          resolve(true)
        } else if (attempts > 30) { // 15 seconds timeout
          clearInterval(checkInterval)
          console.error('❌ CulqiCheckout failed to load after 15 seconds')
          reject(new Error('CulqiCheckout timeout'))
        }
      }, 500)
      return
    }

    // Create and load Culqi Custom Checkout script
    console.log('📦 Creating new script tag for Custom Checkout...')
    const script = document.createElement('script')
    script.id = 'culqi-checkout-custom'
    script.src = 'https://js.culqi.com/checkout-js'
    script.async = false // Make it synchronous
    script.defer = false

    script.onload = () => {
      console.log('✅ Culqi Custom Checkout script loaded (onload fired)')
      // Wait for CulqiCheckout to be available
      let attempts = 0
      const checkInterval = setInterval(() => {
        attempts++
        console.log(`   Checking ${attempts}/20: typeof CulqiCheckout =`, typeof window.CulqiCheckout)
        if (typeof window.CulqiCheckout !== 'undefined') {
          clearInterval(checkInterval)
          console.log('✅ CulqiCheckout class is available!')
          culqiLoaded.value = true
          resolve(true)
        } else if (attempts > 20) {
          clearInterval(checkInterval)
          console.error('❌ CulqiCheckout not available after script load')
          reject(new Error('CulqiCheckout not available after loading'))
        }
      }, 100)
    }

    script.onerror = (err) => {
      console.error('❌ Failed to load Culqi Custom Checkout script')
      console.error('Error details:', err)
      reject(new Error('Failed to load Culqi Custom Checkout script'))
    }

    console.log('📌 Appending script to document head...')
    document.head.appendChild(script)
    console.log('✓ Script tag appended')
  })
}

// Initialize Culqi Custom Checkout instance
const initializeCulqi = () => {
  if (typeof window.CulqiCheckout === 'undefined') {
    console.error('❌ CulqiCheckout not available')
    return false
  }

  try {
    console.log('🚀 Initializing Culqi Custom Checkout...')

    // Settings
    const settings = {
      title: 'Inca Lake Travel',
      currency: props.currency || 'USD',
      amount: amountInCents.value,
      description: props.description || 'Tour Booking',
      // order: 'ord_test_xxx', // Optional for other payment methods
    }

    // Client
    const client = {
      email: props.customerEmail || '',
    }

    // Payment Methods (only cards for now)
    const paymentMethods = {
      tarjeta: true,
      yape: false,
      billetera: false,
      bancaMovil: false,
      agente: false,
      cuotealo: false,
    }

    // Options
    const options = {
      lang: 'auto',
      installments: false,
      modal: true, // Modal popup
      paymentMethods: paymentMethods,
      paymentMethodsSort: Object.keys(paymentMethods),
    }

    // Appearance - Personalized with brand colors
    const appearance = {
      theme: 'default',
      hiddenCulqiLogo: false,
      hiddenBannerContent: false,
      hiddenBanner: false,
      hiddenToolBarAmount: false,
      hiddenEmail: false,
      menuType: 'sidebar',
      buttonCardPayText: `Pay ${props.currency} ${props.amount.toFixed(2)}`,
      logo: null,
      defaultStyle: {
        bannerColor: '#667eea',
        buttonBackground: '#667eea',
        menuColor: '#764ba2',
        linksColor: '#667eea',
        buttonTextColor: '#ffffff',
        priceColor: '#667eea',
      },
    }

    // Config
    const config = {
      settings,
      client,
      options,
      appearance,
    }

    console.log('Config:', config)

    // Create Culqi instance - MUST assign to window.Culqi
    window.Culqi = new window.CulqiCheckout(props.publicKey, config)
    console.log('✅ Culqi instance created')

    // Set callback according to official documentation
    window.Culqi.culqi = handleCulqiAction
    console.log('✅ Callback assigned to window.Culqi')

    return true

  } catch (error) {
    console.error('❌ Error initializing Culqi:', error)
    errorMessage.value = 'Error initializing payment: ' + error.message
    return false
  }
}

// Open Culqi Custom Checkout modal
const openCulqiCheckout = () => {
  console.log('🔍 Button clicked, checking Culqi status...')
  console.log('- culqiLoaded:', culqiLoaded.value)
  console.log('- CulqiCheckout:', typeof window.CulqiCheckout)
  console.log('- Public Key:', props.publicKey)
  console.log('- Amount:', amountInCents.value)

  if (!culqiLoaded.value || typeof window.CulqiCheckout === 'undefined') {
    console.error('❌ CulqiCheckout not ready')
    errorMessage.value = 'Payment system not ready. Please wait a moment and try again.'
    return
  }

  processing.value = true
  errorMessage.value = null

  try {
    // Initialize if not already done
    if (!window.Culqi) {
      const initialized = initializeCulqi()
      if (!initialized) {
        processing.value = false
        return
      }
    }

    console.log('📦 Opening Culqi Custom Checkout modal...')
    window.Culqi.open()
    console.log('✅ Modal opened successfully')
    processing.value = false

  } catch (error) {
    console.error('❌ Error opening Culqi:', error)
    console.error('Error details:', error.message, error.stack)
    errorMessage.value = 'Error opening payment modal: ' + error.message
    processing.value = false
  }
}

// Handle Culqi Custom Checkout response
const handleCulqiAction = () => {
  console.log('📞 Culqi callback executed')
  console.log('Window.Culqi:', window.Culqi)

  if (!window.Culqi) {
    console.error('No Culqi reference found')
    return
  }

  // Check for token (successful payment)
  if (window.Culqi.token) {
    console.log('✅ Payment token received:', window.Culqi.token)

    const paymentData = {
      token: window.Culqi.token,
      email: window.Culqi.token.email,
      card_brand: window.Culqi.token.card_brand || null,
      card_number: window.Culqi.token.card_number || null,
      card_type: window.Culqi.token.card_type || null,
      type: 'card'
    }

    // Close the modal
    window.Culqi.close()

    emit('success', window.Culqi.token.id, paymentData)

  } else if (window.Culqi.order) {
    // Order created (for other payment methods like PagoEfectivo)
    console.log('✅ Order received:', window.Culqi.order)
    // Handle order if needed in the future

  } else if (window.Culqi.error) {
    // Payment error
    console.error('❌ Payment error:', window.Culqi.error)
    errorMessage.value = window.Culqi.error.user_message || 'Error processing payment. Please try again.'
    emit('error', errorMessage.value)
  }
}

// Initialize on mount
onMounted(async () => {
  console.log('🎯 CulqiCheckout component mounted!')
  console.log('Props received:', {
    publicKey: props.publicKey,
    amount: props.amount,
    currency: props.currency,
    description: props.description,
    customerEmail: props.customerEmail
  })

  try {
    // Load Culqi Custom Checkout script
    await loadCulqiScript()

    // Initialize Culqi instance
    initializeCulqi()

    console.log('✅ Culqi Custom Checkout initialized successfully')

  } catch (error) {
    console.error('Failed to initialize Culqi:', error)
    errorMessage.value = 'Error loading payment system. Please refresh the page.'
  }
})
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
              <li>✓ PCI DSS compliant</li>
              <li>✓ 3DS protection for cards</li>
              <li>✓ End-to-end encryption</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Pay Button -->
      <button
        type="button"
        @click="openCulqiCheckout"
        :disabled="processing || !culqiLoaded"
        class="w-full bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary disabled:from-gray-400 disabled:to-gray-500 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-3 transform hover:scale-[1.02] disabled:transform-none disabled:hover:scale-100"
      >
        <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
        <span v-else-if="!culqiLoaded" class="material-symbols-outlined text-xl animate-pulse">hourglass_empty</span>
        <span v-else class="material-symbols-outlined text-xl">lock</span>
        <span v-if="processing">Processing...</span>
        <span v-else-if="!culqiLoaded">Loading payment system...</span>
        <span v-else>Pay {{ currency }} {{ amount.toFixed(2) }}</span>
      </button>

      <!-- Trust Badge -->
      <div class="flex items-center justify-center gap-2 text-xs text-secondary-light dark:text-secondary-dark">
        <span class="material-symbols-outlined text-base">shield</span>
        <span>Secure payment powered by Culqi</span>
      </div>
    </div>
  </div>
</template>
