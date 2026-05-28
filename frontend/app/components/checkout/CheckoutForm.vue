<script setup lang="ts">
import { countries, countryFlag, getDialCode } from '~/utils/countries'

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
const { t, locale } = useI18n()
const { api } = useApi()

// Full tour details — the policies configured in admin Step 6 live in
// booking_texts / policy fields, which aren't always copied onto the cart item.
const tourDetails = ref<Record<number, any>>({})
onMounted(async () => {
  for (const item of cartStore.items) {
    if (!tourDetails.value[item.tourId]) {
      try {
        const res = await api(`/tours/${item.tourId}?language=${locale.value.toUpperCase()}`)
        tourDetails.value[item.tourId] = (res as any)?.data || null
      } catch (e) {}
    }
  }
})

// Resolve policies the same way the cart does (item → tour detail → booking_texts).
function getItemPolicies(item: any) {
  const detail = tourDetails.value[item.tourId]
  const bt = detail?.booking_texts || {}
  const policyType = detail?.policy_type || 'standard'
  let policyContent = ''
  if (policyType === 'custom') {
    policyContent = bt.policyDescriptionCustom || bt.policyDescription || detail?.policy_description_custom || ''
  } else {
    policyContent = bt.policyDescription || detail?.policy_description || ''
  }
  return {
    title: item.tourTitle,
    policies: item.policies || detail?.policies || policyContent || '',
    cancellationPolicy: item.cancellationPolicy || detail?.cancellation_policy || '',
  }
}

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

const selectedDialCode = computed(() => getDialCode(customerCountry.value))

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

  // Group items by tour, resolving each tour's real policies (Step 6).
  cartStore.items.forEach(item => {
    if (!uniqueTours.has(item.tourId)) {
      uniqueTours.set(item.tourId, getItemPolicies(item))
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
  <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm p-4 sm:p-6 border border-slate-200 dark:border-slate-800">
    <div class="mb-5 sm:mb-6">
      <h2 class="text-lg sm:text-xl font-black">{{ t('checkout.customer_info') }}</h2>
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
        <Icon name="material-symbols:info-outline" class="text-sm" />
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

      <!-- Phone / WhatsApp with integrated country selector -->
      <div>
        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
          {{ t('checkout.phone_whatsapp') }} *
        </label>
        <CheckoutPhoneInput
          v-model:phone="customerPhone"
          v-model:country="customerCountry"
          :phone-error="errors.customer_phone"
          :country-error="errors.customer_country"
        />
        <p v-if="errors.customer_phone" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.customer_phone }}
        </p>
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
            <Icon name="material-symbols:credit-card-outline" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-400'" class="text-2xl" />
            <span class="text-sm font-bold" :class="paymentMethod === 'culqi' ? 'text-primary' : 'text-slate-700 dark:text-slate-300'">{{ t('checkout.card') }}</span>
            <div class="flex items-center gap-1">
              <span class="px-1.5 py-0.5 rounded text-[8px] font-black tracking-wide bg-white border border-slate-200 text-[#1a1f71]">VISA</span>
              <span class="px-1.5 py-0.5 rounded text-[8px] font-black tracking-wide bg-white border border-slate-200 text-[#eb001b]">MC</span>
              <span class="px-1.5 py-0.5 rounded text-[8px] font-black tracking-wide bg-white border border-slate-200 text-[#006fcf]">AMEX</span>
            </div>
          </label>
          <label
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-all"
            :class="paymentMethod === 'paypal' ? 'border-[#0070ba] bg-[#0070ba]/5' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'"
          >
            <input type="radio" v-model="paymentMethod" value="paypal" class="sr-only" />
            <Icon name="material-symbols:account-balance-wallet-outline" :class="paymentMethod === 'paypal' ? 'text-[#0070ba]' : 'text-slate-400'" class="text-2xl" />
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
        <Icon name="material-symbols:credit-card-outline" class="text-2xl" />
        <span>{{ t('checkout.continue_payment') }}</span>
      </button>
    </form>

    <!-- Policies Modal -->
    <AppModal v-model="showPoliciesModal" :title="modalTitle" max-width="max-w-2xl">
      <div v-for="(tour, index) in toursPolicies" :key="index" class="mb-6 last:mb-0">
        <!-- Show tour title if multiple tours -->
        <div v-if="toursPolicies.length > 1" class="mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
          <h4 class="text-base font-bold text-primary">{{ tour.title }}</h4>
        </div>

        <div class="space-y-4">
          <div v-if="tour.policies">
            <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
              <Icon name="material-symbols:description-outline" class="text-blue-500 text-2xl" />
              {{ t('tour_policies') }}
            </h5>
            <div class="prose prose-sm max-w-none text-sm text-slate-700 dark:text-slate-300 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800" v-html="sanitizeHtml(tour.policies)"></div>
          </div>

          <div v-if="tour.cancellationPolicy">
            <h5 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
              <Icon name="material-symbols:report-outline" class="text-red-500 text-2xl" />
              {{ t('cancellation_policy') }}
            </h5>
            <div class="prose prose-sm max-w-none text-sm text-slate-700 dark:text-slate-300 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800" v-html="sanitizeHtml(tour.cancellationPolicy)"></div>
          </div>

          <!-- No policies configured for this tour -->
          <div v-if="!tour.policies && !tour.cancellationPolicy" class="text-sm text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
            {{ t('standard_policy') }} — <a href="mailto:reservas@incalake.com" class="text-primary font-semibold">reservas@incalake.com</a>
          </div>
        </div>
      </div>

      <template #footer>
        <button
          @click="closePoliciesModal"
          class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg transition-colors"
        >
          {{ t('checkout.dismiss') }}
        </button>
      </template>
    </AppModal>
  </div>
</template>
