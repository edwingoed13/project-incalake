<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-2xl">account_balance_wallet</span>
      </div>
      <div>
        <h3 class="text-lg font-black text-primary-light dark:text-primary-dark">
          Pay with PayPal
        </h3>
        <p class="text-sm text-secondary-light dark:text-secondary-dark">
          Pay securely with your PayPal account
        </p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
    </div>

    <!-- PayPal Button Container -->
    <div ref="paypalContainer" class="min-h-[200px]"></div>

    <!-- Trust Badge -->
    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-secondary-light dark:text-secondary-dark">
      <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-base">verified_user</span>
      <span>PayPal buyer protection included</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { loadScript } from '@paypal/paypal-js'

interface Props {
  clientId: string
  amount: number
  currency: string
  description: string
  customerEmail?: string
  customerFirstName?: string
  customerLastName?: string
  customerPhone?: string
  customerCountry?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  success: [orderId: string, paymentData: any]
  error: [error: string]
}>()

const paypalContainer = ref<HTMLElement | null>(null)
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const initializePayPal = async () => {
  try {
    loading.value = true
    errorMessage.value = null

    // Load PayPal SDK
    const paypal = await loadScript({
      clientId: props.clientId,
      currency: props.currency,
      intent: 'capture'
    })

    if (!paypal || !paypal.Buttons) {
      throw new Error('PayPal SDK failed to load')
    }

    // Render PayPal buttons
    paypal.Buttons({
      style: {
        layout: 'vertical',
        color: 'gold',
        shape: 'rect',
        label: 'paypal'
      },

      createOrder: async (data: any, actions: any) => {
        const payer: any = {}
        if (props.customerFirstName || props.customerLastName) {
          payer.name = {
            given_name: props.customerFirstName || '',
            surname: props.customerLastName || '',
          }
        }
        if (props.customerEmail) payer.email_address = props.customerEmail
        if (props.customerPhone) {
          payer.phone = {
            phone_type: 'MOBILE',
            phone_number: { national_number: props.customerPhone.replace(/\D/g, '') }
          }
        }
        if (props.customerCountry) {
          payer.address = { country_code: props.customerCountry }
        }

        return actions.order.create({
          purchase_units: [{
            description: props.description,
            amount: {
              currency_code: props.currency,
              value: props.amount.toFixed(2)
            }
          }],
          ...(Object.keys(payer).length > 0 ? { payer } : {}),
          application_context: {
            shipping_preference: 'NO_SHIPPING'
          }
        })
      },

      onApprove: async (data: any) => {
        // Do NOT capture in client. Send order_id to backend for secure capture.
        // Backend will validate amount and mark booking as paid.
        try {
          emit('success', data.orderID, {
            order_id: data.orderID,
            payer_id: data.payerID,
          })
        } catch (error: any) {
          errorMessage.value = 'Error processing PayPal approval'
          emit('error', errorMessage.value)
        }
      },

      onError: (err: any) => {
        console.error('PayPal error:', err)
        errorMessage.value = 'Error processing payment with PayPal'
        emit('error', errorMessage.value)
      },

      onCancel: (data: any) => {
        errorMessage.value = 'Payment cancelled by user'
        emit('error', errorMessage.value)
      }
    }).render(paypalContainer.value!)

    loading.value = false
  } catch (error: any) {
    console.error('Error initializing PayPal:', error)
    errorMessage.value = 'Error loading PayPal. Please reload the page.'
    loading.value = false
    emit('error', errorMessage.value)
  }
}

onMounted(() => {
  initializePayPal()
})
</script>
