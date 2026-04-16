<script setup lang="ts">
import { countries, countryFlag } from '~/utils/countries'

interface Props {
  pickupAvailable?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  submit: [data: {
    customer_first_name: string
    customer_last_name: string
    customer_name: string
    customer_email: string
    customer_phone: string
    customer_country: string
    customer_notes: string
    pickup_location?: string
    payment_method: string
  }]
}>()

const cartStore = useCartStore()
const { t } = useI18n()

// Form data
const customerFirstName = ref('')
const customerLastName = ref('')
const customerEmail = ref('')
const customerPhone = ref('')
const customerCountry = ref('PE')
const customerNotes = ref('')
const pickupLocation = ref('')
const paymentMethod = ref<'culqi' | 'paypal'>('culqi')
const acceptedTerms = ref(false)

// Modal state
const showPoliciesModal = ref(false)
const toursPolicies = ref<Array<{ title: string, policies: string, cancellationPolicy: string }>>([])

// Form validation
const errors = ref<Record<string, string>>({})

const validateForm = () => {
  errors.value = {}

  if (!customerFirstName.value.trim()) {
    errors.value.customer_first_name = t('checkout.first_name_required')
  }

  if (!customerLastName.value.trim()) {
    errors.value.customer_last_name = t('checkout.last_name_required')
  }

  if (!customerEmail.value.trim()) {
    errors.value.customer_email = t('checkout.email_required')
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.value)) {
    errors.value.customer_email = t('checkout.email_invalid')
  }

  if (!customerPhone.value.trim()) {
    errors.value.customer_phone = t('checkout.phone_required')
  }

  if (!customerCountry.value) {
    errors.value.customer_country = t('checkout.country_required')
  }

  if (props.pickupAvailable && !pickupLocation.value.trim()) {
    errors.value.pickup_location = t('checkout.pickup_required')
  }

  if (!acceptedTerms.value) {
    errors.value.accepted_terms = t('checkout.terms_required')
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = (e: Event) => {
  e.preventDefault()

  if (validateForm()) {
    const fullName = `${customerFirstName.value.trim()} ${customerLastName.value.trim()}`.trim()
    emit('submit', {
      customer_first_name: customerFirstName.value.trim(),
      customer_last_name: customerLastName.value.trim(),
      customer_name: fullName,
      customer_email: customerEmail.value,
      customer_phone: customerPhone.value,
      customer_country: customerCountry.value,
      customer_notes: customerNotes.value,
      pickup_location: pickupLocation.value,
      payment_method: paymentMethod.value
    })
  }
}

// Show policies modal
const viewPolicies = () => {
  const uniqueTours = new Map()

  // Group items by tour to get unique policies
  cartStore.items.forEach(item => {
    if (!uniqueTours.has(item.tourId)) {
      uniqueTours.set(item.tourId, {
        title: item.tourTitle,
        policies: item.policies || 'No general policies available.',
        cancellationPolicy: item.cancellationPolicy || 'No cancellation policy available.'
      })
    }
  })

  toursPolicies.value = Array.from(uniqueTours.values())
  showPoliciesModal.value = true
  document.body.style.overflow = 'hidden'
}

const closePoliciesModal = () => {
  showPoliciesModal.value = false
  toursPolicies.value = []
  document.body.style.overflow = ''
}

const modalTitle = computed(() => {
  if (toursPolicies.value.length === 0) return t('checkout.policies')
  if (toursPolicies.value.length === 1) return `${t('checkout.policies')} - ${toursPolicies.value[0].title}`
  return t('checkout.policies_tours', { n: toursPolicies.value.length })
})
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-800">
    <div class="mb-6">
      <h2 class="text-xl font-black">{{ t('checkout.customer_info') }}</h2>
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">info</span>
        {{ t('checkout.customer_info_note') }}
      </p>
    </div>

    <form @submit="handleSubmit" class="space-y-4">
      <!-- First Name + Last Name (international standard) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="customer_first_name" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
            {{ t('checkout.first_name') }} *
          </label>
          <input
            id="customer_first_name"
            v-model="customerFirstName"
            type="text"
            autocomplete="given-name"
            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            :class="errors.customer_first_name ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
            :placeholder="t('checkout.first_name_placeholder')"
          />
          <p v-if="errors.customer_first_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ errors.customer_first_name }}
          </p>
        </div>
        <div>
          <label for="customer_last_name" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
            {{ t('checkout.last_name') }} *
          </label>
          <input
            id="customer_last_name"
            v-model="customerLastName"
            type="text"
            autocomplete="family-name"
            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            :class="errors.customer_last_name ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
            :placeholder="t('checkout.last_name_placeholder')"
          />
          <p v-if="errors.customer_last_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ errors.customer_last_name }}
          </p>
        </div>
      </div>

      <!-- Customer Email -->
      <div>
        <label for="customer_email" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.email') }} *
        </label>
        <input
          id="customer_email"
          v-model="customerEmail"
          type="email"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.customer_email ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="john@example.com"
        />
        <p v-if="errors.customer_email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_email }}
        </p>
      </div>

      <!-- Customer Phone -->
      <div>
        <label for="customer_phone" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.phone') }} *
        </label>
        <input
          id="customer_phone"
          v-model="customerPhone"
          type="tel"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.customer_phone ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          placeholder="+51 999 999 999"
        />
        <p v-if="errors.customer_phone" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_phone }}
        </p>
      </div>

      <!-- Customer Country -->
      <div>
        <label for="customer_country" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.country') }} *
        </label>
        <select
          id="customer_country"
          v-model="customerCountry"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors appearance-none"
          :class="errors.customer_country ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
        >
          <option v-for="country in countries" :key="country.code" :value="country.code">
            {{ countryFlag(country.code) }} {{ country.name }}
          </option>
        </select>
        <p v-if="errors.customer_country" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_country }}
        </p>
      </div>

      <!-- Pickup Location (if available) -->
      <div v-if="pickupAvailable">
        <label for="pickup_location" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.pickup_location') }} *
        </label>
        <input
          id="pickup_location"
          v-model="pickupLocation"
          type="text"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          :class="errors.pickup_location ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
          :placeholder="t('checkout.pickup_placeholder')"
        />
        <p v-if="errors.pickup_location" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.pickup_location }}
        </p>
      </div>

      <!-- Customer Notes -->
      <div>
        <label for="customer_notes" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.notes') }}
        </label>
        <textarea
          id="customer_notes"
          v-model="customerNotes"
          rows="3"
          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
          :placeholder="t('checkout.notes_placeholder')"
        ></textarea>
      </div>

      <!-- Payment Method -->
      <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
          {{ t('checkout.payment_method') }}
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
            :class="paymentMethod === 'culqi' ? 'border-primary bg-primary/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
          >
            <input type="radio" v-model="paymentMethod" value="culqi" class="sr-only" />
            <span class="material-symbols-outlined text-2xl" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-400'">credit_card</span>
            <span class="text-sm font-bold" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-700 dark:text-slate-300'">{{ t('checkout.card') }}</span>
            <span class="text-[10px] text-slate-500">Visa, Mastercard, Amex</span>
          </label>
          <label
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
            :class="paymentMethod === 'paypal' ? 'border-[#0070ba] bg-[#0070ba]/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
          >
            <input type="radio" v-model="paymentMethod" value="paypal" class="sr-only" />
            <span class="material-symbols-outlined text-2xl" :class="paymentMethod === 'paypal' ? 'text-[#0070ba]' : 'text-slate-400'">account_balance_wallet</span>
            <span class="text-sm font-bold" :class="paymentMethod === 'paypal' ? 'text-[#0070ba]' : 'text-slate-700 dark:text-slate-300'">PayPal</span>
            <span class="text-[10px] text-slate-500">{{ t('checkout.paypal_or_card') }}</span>
          </label>
        </div>
      </div>

      <!-- Terms and Conditions Checkbox -->
      <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
        <label class="flex items-start cursor-pointer">
          <input
            v-model="acceptedTerms"
            type="checkbox"
            class="mt-1 mr-3 w-5 h-5 text-primary border-slate-300 dark:border-slate-600 rounded focus:ring-primary"
          />
          <span class="text-sm text-slate-700 dark:text-slate-300">
            {{ t('checkout.accept_terms_pre') }}
            <a
              href="#"
              @click.prevent="viewPolicies"
              class="text-primary hover:text-primary/80 font-semibold underline"
            >
              {{ t('checkout.accept_terms_link') }}
            </a>
            {{ t('checkout.accept_terms_post') }}
          </span>
        </label>
        <p v-if="errors.accepted_terms" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.accepted_terms }}
        </p>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="w-full font-black py-4 px-6 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
        :class="acceptedTerms ? 'bg-primary hover:bg-primary/90 text-white shadow-primary/20' : 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-not-allowed'"
      >
        <span class="material-symbols-outlined">payment</span>
        <span>{{ t('checkout.continue_payment') }}</span>
      </button>
    </form>

    <!-- Policies Modal -->
    <Teleport to="body">
      <div
        v-if="showPoliciesModal && toursPolicies.length > 0"
        class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4"
        @click.self="closePoliciesModal"
      >
        <div class="bg-white dark:bg-slate-900 rounded-xl max-w-3xl w-full max-h-[85vh] overflow-hidden shadow-2xl">
          <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-black">{{ modalTitle }}</h3>
              <button
                @click="closePoliciesModal"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"
              >
                <span class="material-symbols-outlined text-2xl">close</span>
              </button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto max-h-[65vh]">
            <div v-for="(tour, index) in toursPolicies" :key="index" class="mb-6 last:mb-0">
              <!-- Show tour title if multiple tours -->
              <div v-if="toursPolicies.length > 1" class="mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
                <h4 class="text-lg font-bold text-primary">{{ tour.title }}</h4>
              </div>

              <div class="space-y-4">
                <div>
                  <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-500">description</span>
                    General Policies
                  </h5>
                  <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    {{ tour.policies }}
                  </div>
                </div>

                <div>
                  <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500">report</span>
                    Cancellation Policy
                  </h5>
                  <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                    {{ tour.cancellationPolicy }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="closePoliciesModal"
              class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
