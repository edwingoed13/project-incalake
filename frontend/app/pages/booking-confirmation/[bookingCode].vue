<template>
  <div class="min-h-screen bg-slate-50 pt-20 md:pt-24 pb-8">
    <div class="max-w-3xl mx-auto px-3 sm:px-4">

      <!-- Error -->
      <div v-if="error" class="bg-white rounded-2xl shadow-sm p-6 text-center mt-4">
        <span class="material-symbols-outlined text-red-400 text-4xl mb-3 block">error</span>
        <h2 class="text-base font-bold text-slate-800 mb-1">{{ t('booking_not_found') }}</h2>
        <p class="text-sm text-slate-500">{{ errorMessage }}</p>
      </div>

      <!-- Loading -->
      <div v-else-if="pending" class="flex justify-center py-20">
        <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
      </div>

      <!-- Content -->
      <div v-else-if="booking">
        <!-- Success Header -->
        <div class="text-center mb-5 md:mb-8">
          <div class="inline-flex items-center justify-center size-12 md:size-16 bg-green-100 rounded-full mb-2">
            <span class="material-symbols-outlined text-green-600 text-3xl md:text-4xl">check_circle</span>
          </div>
          <h1 class="text-lg md:text-2xl font-black text-slate-800">{{ t('booking_confirmed') }}</h1>
          <p class="text-xs text-slate-500 mt-0.5">{{ t('code') }}: <span class="font-mono font-bold text-primary">{{ booking.booking_code }}</span></p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-between mb-5 md:mb-8 px-2">
          <template v-for="(s, idx) in steps" :key="idx">
            <button
              @click="currentStep = idx"
              class="flex flex-col items-center gap-1 group"
            >
              <div
                class="size-8 md:size-9 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="idx === currentStep
                  ? 'bg-primary text-white shadow-md'
                  : completedSteps.has(idx)
                    ? 'bg-green-100 text-green-700'
                    : 'bg-slate-100 text-slate-400'"
              >
                <span v-if="completedSteps.has(idx)" class="material-symbols-outlined text-sm">check</span>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <span
                class="text-[10px] font-semibold leading-tight text-center"
                :class="idx === currentStep ? 'text-primary' : completedSteps.has(idx) ? 'text-green-600' : 'text-slate-400'"
              >{{ s.label }}</span>
            </button>
            <!-- Connector line -->
            <div
              v-if="idx < steps.length - 1"
              class="flex-1 h-px mb-4"
              :class="completedSteps.has(idx) ? 'bg-green-300' : 'bg-slate-200'"
            ></div>
          </template>
        </div>

        <!-- Step 0: Booking Summary -->
        <div v-if="currentStep === 0" class="space-y-3">
          <!-- Tour(s) of this purchase — one card per tour, single code -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="isMultiTour" class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-primary text-base">confirmation_number</span>
              <span class="text-xs font-bold text-slate-700">{{ purchaseTours.length }} tours en esta compra</span>
            </div>

            <div
              v-for="(tr, i) in purchaseTours"
              :key="tr.booking_code + i"
              class="flex items-center gap-3 p-3 md:p-4"
              :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : 'border-b border-slate-50'"
            >
              <div class="size-14 md:size-16 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100">
                <img
                  v-if="tr.tour_image"
                  :src="getImageUrl(tr.tour_image)"
                  :alt="tr.tour_title"
                  class="w-full h-full object-cover"
                />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-slate-800 leading-tight line-clamp-2">{{ tr.tour_title }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                  <span class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">calendar_today</span>
                    {{ formatDate(tr.tour_date) }}
                  </span>
                  <span v-if="formatTime(tr.tour_time)" class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">schedule</span>
                    {{ formatTime(tr.tour_time) }}
                  </span>
                  <span class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">group</span>
                    {{ (tr.adults || 0) + (tr.children || 0) }}
                  </span>
                </p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold text-primary">{{ currencyStore.formatConverted(tr.total || 0) }}</p>
              </div>
            </div>

            <!-- Purchase totals -->
            <div class="grid grid-cols-3 divide-x divide-slate-100">
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ isMultiTour ? 'Tours' : t('travelers') }}</p>
                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ isMultiTour ? purchaseTours.length : (booking.participants?.adults || 0) }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ t('total') }}</p>
                <p class="text-sm font-bold text-primary mt-0.5">{{ currencyStore.formatConverted(grandTotal) }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">Estado</p>
                <p class="text-sm font-bold mt-0.5" :class="paymentSummary?.is_partial ? 'text-amber-600' : 'text-green-600'">
                  {{ paymentSummary?.is_partial ? 'Adelanto pagado' : t('status_paid') }}
                </p>
              </div>
            </div>

            <!-- Partial payment breakdown -->
            <div v-if="paymentSummary?.is_partial" class="border-t border-slate-100 px-3 py-2.5 bg-amber-50/60 flex items-center justify-between gap-2 flex-wrap">
              <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                <span class="material-symbols-outlined text-amber-600 text-sm">payments</span>
                Pagaste <span class="font-bold text-slate-800">{{ currencyStore.formatConverted(paymentSummary.paid_now) }}</span>
              </span>
              <span class="text-xs text-right">
                <span class="text-slate-500">Saldo el día del tour:</span>
                <span class="font-bold text-amber-700 ml-1">{{ currencyStore.formatConverted(paymentSummary.balance_due) }}</span>
              </span>
            </div>
          </div>

          <!-- Customer info (collapsible on mobile) -->
          <details class="bg-white rounded-xl border border-slate-100 shadow-sm group">
            <summary class="flex items-center justify-between p-3 md:p-4 cursor-pointer list-none">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">person</span>
                <span class="text-sm font-bold text-slate-800">{{ t('customer_info') }}</span>
              </div>
              <span class="material-symbols-outlined text-slate-400 text-lg transition-transform group-open:rotate-180">expand_more</span>
            </summary>
            <div class="px-3 md:px-4 pb-3 md:pb-4 space-y-2 text-sm border-t border-slate-50 pt-3">
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('name') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('email') }}</span>
                <span class="font-semibold text-slate-800 text-xs break-all">{{ booking.customer?.email }}</span>
              </div>
              <div v-if="booking.customer?.phone" class="flex justify-between">
                <span class="text-slate-500">{{ t('phone') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.phone }}</span>
              </div>
            </div>
          </details>

          <!-- Voucher: prints / saves the confirmation as PDF (works on mobile
               via the browser's print dialog; no backend PDF needed). -->
          <button @click="downloadVoucher" class="w-full flex items-center justify-center gap-2 p-3 bg-white border border-slate-200 rounded-xl text-slate-700 font-semibold text-sm active:bg-slate-50 transition-colors">
            <span class="material-symbols-outlined text-lg">download</span>
            {{ t('voucher') }}
          </button>

          <button @click="currentStep = 1" class="w-full bg-primary active:bg-primary/80 text-white py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 active:scale-[0.98]">
            {{ t('continue_pickup') }}
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
          </button>
        </div>

        <!-- Step 1: Pickup Configuration -->
        <div v-else-if="currentStep === 1">
          <!-- MULTI-TOUR: one pickup per tour (list + modal) -->
          <template v-if="isMultiTour">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
              <div class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-base">directions_bus</span>
                <span class="text-xs font-bold text-slate-700">Punto de recojo por tour ({{ configuredCount }}/{{ purchaseTours.length }})</span>
              </div>
              <div
                v-for="(tr, i) in purchaseTours"
                :key="tr.booking_code + i"
                class="flex items-center gap-3 p-3 md:p-4"
                :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : ''"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate">{{ tr.tour_title }}</p>
                  <p class="text-[11px] text-slate-500 mt-0.5">
                    {{ formatDate(tr.tour_date) }}<span v-if="formatTime(tr.tour_time)"> · {{ formatTime(tr.tour_time) }}</span>
                  </p>
                  <p v-if="isTourPickupDone(tr)" class="text-[11px] text-green-600 font-semibold mt-0.5 inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                    {{ tr.pickup_type === 'hotel_pickup' ? `Recojo en hotel${tr.pickup_hotel ? ': ' + tr.pickup_hotel : ''}` : 'Punto de encuentro confirmado' }}
                  </p>
                  <p v-else class="text-[11px] text-amber-600 font-semibold mt-0.5">Pendiente de configurar</p>
                </div>
                <button
                  @click="openPickupModal(tr)"
                  class="shrink-0 px-3 py-2 rounded-lg text-xs font-bold transition-colors"
                  :class="isTourPickupDone(tr) ? 'bg-slate-100 text-slate-600 active:bg-slate-200' : 'bg-primary text-white active:bg-primary/80'"
                >
                  {{ isTourPickupDone(tr) ? 'Editar' : 'Configurar' }}
                </button>
              </div>
            </div>

            <div class="flex gap-2 mt-3">
              <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
              <button
                @click="onPickupCompleted({})"
                :disabled="configuredCount < purchaseTours.length"
                class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 active:bg-primary/80 transition-colors"
              >
                {{ configuredCount < purchaseTours.length ? `Faltan ${purchaseTours.length - configuredCount}` : t('save_continue') }}
              </button>
            </div>

            <!-- Per-tour pickup modal -->
            <Teleport to="body">
              <div v-if="activePickupTour" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="activePickupTour = null"></div>
                <div class="relative bg-slate-50 w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
                  <div class="sticky top-0 bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between z-10">
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-slate-800 truncate">{{ activePickupTour.tour_title }}</p>
                      <p class="text-[11px] text-slate-500">{{ formatDate(activePickupTour.tour_date) }}</p>
                    </div>
                    <button @click="activePickupTour = null" class="p-1.5 text-slate-400 active:text-slate-700">
                      <span class="material-symbols-outlined">close</span>
                    </button>
                  </div>
                  <div class="p-4">
                    <BookingPickupConfiguration
                      :key="activePickupTour.id"
                      :booking-id="activePickupTour.id"
                      @completed="onTourPickupSaved"
                      @error="(msg: string) => console.error(msg)"
                    />
                  </div>
                </div>
              </div>
            </Teleport>
          </template>

          <!-- SINGLE TOUR: unchanged inline flow -->
          <template v-else>
            <BookingPickupConfiguration
              :booking-id="booking.id"
              @completed="onPickupCompleted"
              @error="(msg: string) => console.error(msg)"
            />
            <div class="flex gap-2 mt-3">
              <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
              <button @click="skipStep(1)" class="flex-1 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold text-slate-500 active:bg-slate-200">{{ t('skip_for_now') }}</button>
            </div>
          </template>
        </div>

        <!-- Step 2: Traveler Details -->
        <div v-else-if="currentStep === 2">
          <!-- MULTI-TOUR: travelers per tour (each capped at its own pax) -->
          <template v-if="isMultiTour">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
              <div class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-base">group</span>
                <span class="text-xs font-bold text-slate-700">Viajeros por tour ({{ toursTravelersDone }}/{{ purchaseTours.length }})</span>
              </div>
              <div
                v-for="(tr, i) in purchaseTours"
                :key="tr.id"
                :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : ''"
              >
                <button
                  @click="openTourId = openTourId === tr.id ? null : tr.id"
                  class="w-full flex items-center justify-between gap-2 p-3 md:p-4 text-left"
                >
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ tr.tour_title }}</p>
                    <p class="text-[11px] mt-0.5 font-semibold inline-flex items-center gap-1" :class="isTourTravelersDone(tr) ? 'text-green-600' : 'text-amber-600'">
                      <span class="material-symbols-outlined text-xs">{{ isTourTravelersDone(tr) ? 'check_circle' : 'group' }}</span>
                      {{ filledCount(tr.id) }}/{{ tourMax(tr) }} viajeros
                    </p>
                  </div>
                  <span class="material-symbols-outlined text-slate-400 transition-transform shrink-0" :class="openTourId === tr.id ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div v-show="openTourId === tr.id" class="px-3 md:px-4 pb-4 border-t border-slate-50 pt-3">
                  <button
                    v-if="i > 0"
                    type="button"
                    @click="copyFromPrevious(i)"
                    class="mb-3 inline-flex items-center gap-1 text-xs font-semibold text-primary active:text-primary/70"
                  >
                    <span class="material-symbols-outlined text-sm">content_copy</span>
                    Copiar viajeros del tour anterior
                  </button>
                  <BookingTravelersForm v-model="travelersByTour[tr.id]" :max-travelers="tourMax(tr)" />
                </div>
              </div>
            </div>
          </template>

          <!-- SINGLE TOUR -->
          <template v-else>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm">
              <div class="flex items-center justify-between p-3 md:p-4 border-b border-slate-50">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">group</span>
                  {{ t('step_travelers') }}
                  <span class="text-xs font-normal text-slate-400">({{ travelers.length }}/{{ maxTravelers }})</span>
                </h3>
              </div>
              <div class="p-3 md:p-4">
                <BookingTravelersForm v-model="travelers" :max-travelers="maxTravelers" />
              </div>
            </div>
          </template>

          <div v-if="travelerError" class="mt-2 bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ travelerError }}</p>
          </div>

          <div class="flex gap-2 mt-3">
            <button @click="currentStep = 1" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
            <button @click="saveTravelers" :disabled="savingTravelers" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 flex items-center justify-center gap-2 active:bg-primary/80 active:scale-[0.98] transition-transform">
              <span v-if="savingTravelers" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              {{ savingTravelers ? t('saving') : t('save_continue') }}
            </button>
          </div>
        </div>

        <!-- Step 3: Complete -->
        <div v-else-if="currentStep === 3" class="text-center">
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="inline-flex items-center justify-center size-14 bg-green-100 rounded-full mb-3">
              <span class="material-symbols-outlined text-green-600 text-3xl">celebration</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">{{ t('all_set') }}</h3>
            <p class="text-xs text-slate-500 mb-5">We'll send you a confirmation email with all the details.<br />See you on {{ formatDate(booking.tour_date) }}!</p>

            <div class="flex flex-col gap-2">
              <a
                :href="`https://wa.me/51982769453?text=Hi! My booking code is ${booking.booking_code}`"
                target="_blank"
                class="flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-xl font-bold text-sm active:bg-green-600 transition-colors"
              >
                <span class="material-symbols-outlined text-lg">chat</span>
                {{ t('whatsapp_contact') }}
              </a>
              <NuxtLink to="/" class="flex items-center justify-center gap-2 py-3 bg-slate-100 rounded-xl font-semibold text-sm text-slate-600 active:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">home</span>
                {{ t('back_home') }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { t, locale } = useI18n()
const currencyStore = useCurrencyStore()
const route = useRoute()
const { api } = useApi()
const config = useRuntimeConfig()

const bookingCode = route.params.bookingCode as string
const email = route.query.email as string
const token = route.query.token as string

const currentStep = ref(0)
const completedSteps = ref(new Set<number>())

const steps = computed(() => [
  { label: t('step_summary') },
  { label: t('step_pickup') },
  { label: t('step_travelers') },
  { label: t('step_complete') },
])

// Fetch booking
const { data: response, pending, error } = await useAsyncData(
  `booking-${bookingCode}`,
  () => {
    if (token) {
      return api(`/bookings/token/${token}`)
    }
    const params = email ? `?email=${encodeURIComponent(email)}` : ''
    return api(`/bookings/${bookingCode}${params}`)
  }
)

const booking = computed(() => (response.value as any)?.booking || null)
const errorMessage = computed(() => (error.value as any)?.data?.message || 'Invalid or expired link')

// Every tour of the purchase (multi-tour cart paid in one charge). The
// backend resolves the group from the payment record, so this works from the
// email link too — one code in the URL, all tours shown here.
const purchaseTours = computed(() => {
  const grp = (response.value as any)?.group
  if (Array.isArray(grp) && grp.length > 1) return grp
  const b = booking.value
  if (!b) return []
  return [{
    booking_code: b.booking_code,
    tour_title: b.tour?.title || b.tour_title,
    tour_image: b.tour?.featured_image,
    tour_date: b.tour_date,
    tour_time: b.tour_time,
    adults: b.participants?.adults ?? b.adults ?? 0,
    children: b.participants?.children ?? b.children ?? 0,
    currency: b.pricing?.currency || b.currency,
    total: b.pricing?.total ?? b.total ?? 0,
  }]
})
const isMultiTour = computed(() => purchaseTours.value.length > 1)

// Payment summary from the API: how much was charged now vs the balance due
// (for tours paid with a deposit/advance).
const paymentSummary = computed(() => (response.value as any)?.payment_summary || null)
const grandTotal = computed(() =>
  paymentSummary.value?.grand_total ?? purchaseTours.value.reduce((s, x) => s + (x.total || 0), 0)
)

// Per-tour pickup (multi-tour). Seed from backend `pickup_configured` flags;
// add ids as the customer saves each one via the modal.
const configuredPickupIds = ref<Set<number>>(new Set())
const activePickupTour = ref<any | null>(null)

watch(purchaseTours, (tours) => {
  for (const tr of tours) {
    if (tr?.id && tr.pickup_configured) configuredPickupIds.value.add(tr.id)
  }
}, { immediate: true })

const isTourPickupDone = (tr: any) =>
  !!tr && (tr.pickup_configured || configuredPickupIds.value.has(tr.id))

const configuredCount = computed(() =>
  purchaseTours.value.filter((tr: any) => isTourPickupDone(tr)).length
)

function openPickupModal(tr: any) {
  activePickupTour.value = tr
}

function onTourPickupSaved() {
  if (activePickupTour.value?.id) {
    configuredPickupIds.value.add(activePickupTour.value.id)
    // keep the row's status label in sync without a refetch
    activePickupTour.value.pickup_configured = true
  }
  activePickupTour.value = null
}

// Travelers form
const travelers = ref<any[]>([])                       // single-tour purchase
const travelersByTour = ref<Record<number, any[]>>({}) // multi-tour: keyed by booking id
const openTourId = ref<number | null>(null)
const savingTravelers = ref(false)
const travelerError = ref<string | null>(null)

// Cap travelers at the participants paid for (adults + children): a 1-pax tour
// shows only the leader (can't add anyone); a 2-pax tour allows just 1 extra.
const maxTravelers = computed(() => {
  const b: any = booking.value
  const a = b?.participants?.adults ?? b?.adults ?? 0
  const c = b?.participants?.children ?? b?.children ?? 0
  return Math.max(1, a + c)
})

// Per-tour cap + status (multi-tour): each tour is capped at its OWN pax, so a
// purchase mixing a 1-pax tour and a 3-pax tour collects the right travelers.
const tourMax = (tr: any) => Math.max(1, (tr?.adults || 0) + (tr?.children || 0))
const filledCount = (id: number) => (travelersByTour.value[id] || []).filter((x: any) => x.full_name?.trim()).length
const isTourTravelersDone = (tr: any) => filledCount(tr.id) >= 1
const toursTravelersDone = computed(() => purchaseTours.value.filter((tr: any) => isTourTravelersDone(tr)).length)

function seedTravelers(adults: number, children: number, leaderName?: string) {
  const a = adults || 0, c = children || 0
  const total = Math.max(1, a + c)
  return Array.from({ length: total }, (_, i) => ({
    full_name: i === 0 ? (leaderName || '') : '',
    nationality: '', doc_type: 'passport', doc_number: '',
    age_group: i < a ? 'adult' : 'child', special_needs: '', is_leader: i === 0,
  }))
}

function mapTraveler(tr: any) {
  return {
    full_name: tr.full_name || '', nationality: tr.nationality || '',
    doc_type: tr.doc_type || 'passport', doc_number: tr.doc_number || '',
    age_group: tr.age_group || 'adult', special_needs: tr.special_needs || '',
    is_leader: tr.is_leader || false,
  }
}

// Reuse the travelers of the previous tour (same people on multiple tours),
// trimmed to this tour's capacity.
function copyFromPrevious(i: number) {
  const prev = purchaseTours.value[i - 1]
  const cur = purchaseTours.value[i]
  if (!prev || !cur) return
  const src = travelersByTour.value[prev.id] || []
  const copy = src.slice(0, tourMax(cur)).map((x: any, idx: number) => ({ ...x, is_leader: idx === 0 }))
  if (copy.length) travelersByTour.value[cur.id] = copy
}

// Load existing data when booking is available
watch(booking, async (b) => {
  if (!b) return

  // MULTI-TOUR: seed every tour synchronously (so v-model keys exist), then load
  // any already-saved travelers per tour.
  if (isMultiTour.value) {
    for (const tr of purchaseTours.value) {
      travelersByTour.value[tr.id] = seedTravelers(tr.adults, tr.children, b.customer?.name)
    }
    openTourId.value = purchaseTours.value[0]?.id ?? null
    let anySaved = false
    await Promise.all(purchaseTours.value.map(async (tr: any) => {
      try {
        const res: any = await api(`/bookings/${tr.id}/travelers`)
        const existing = res?.data || []
        if (existing.length) {
          travelersByTour.value[tr.id] = existing.map(mapTraveler)
          anySaved = true
        }
      } catch {}
    }))
    if (anySaved) completedSteps.value.add(2)
    return
  }

  // SINGLE TOUR: full details (travelers + pickup)
  try {
    const details = await api(`/bookings/${b.id}/full-details`)
    const data = (details as any)?.data || details

    if (data?.travelers?.length) {
      travelers.value = data.travelers.map(mapTraveler)
      completedSteps.value.add(2)
    } else {
      travelers.value = seedTravelers(b.participants?.adults || 1, b.participants?.children || 0, b.customer?.name)
    }

    if (data?.pickup_detail) completedSteps.value.add(1)
    if (completedSteps.value.has(1) && completedSteps.value.has(2)) currentStep.value = 3
  } catch (e) {
    travelers.value = seedTravelers(b.participants?.adults || 1, b.participants?.children || 0, b.customer?.name)
  }
}, { immediate: true })

useHead({
  title: 'Booking Confirmation',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }]
})

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  // Derive the storage origin from the API base so a missing/wrong
  // NUXT_PUBLIC_STORAGE_BASE (which defaults to localhost) can't break images.
  const origin = String(config.public.apiBase || '').replace(/\/api\/?$/, '')
  const clean = String(path).replace(/^\/+/, '').replace(/^storage\//, '')
  return `${origin}/storage/${clean}`
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  // Tolerate "YYYY-MM-DD", "YYYY-MM-DD HH:mm:ss" and ISO "....THH:mm:ssZ".
  const datePart = String(dateString).split('T')[0].split(' ')[0]
  const [y, m, d] = datePart.split('-').map(Number)
  if (!y || !m || !d) return ''
  const dt = new Date(y, m - 1, d)
  if (isNaN(dt.getTime())) return ''
  const lang = (locale?.value as string) || 'es'
  return dt.toLocaleDateString(lang, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
}

function formatTime(t?: string) {
  if (!t) return ''
  const [hh, mm] = String(t).split(':')
  const h = parseInt(hh, 10)
  if (isNaN(h)) return ''
  const ampm = h >= 12 ? 'PM' : 'AM'
  return `${h % 12 || 12}:${mm ?? '00'} ${ampm}`
}

function onPickupCompleted(data: any) {
  completedSteps.value.add(1)
  currentStep.value = 2
}

function skipStep(step: number) {
  currentStep.value = step + 1
}

async function saveTravelers() {
  travelerError.value = null

  // MULTI-TOUR: validate + save each tour against its OWN booking id.
  if (isMultiTour.value) {
    for (const tr of purchaseTours.value) {
      const list = travelersByTour.value[tr.id] || []
      if (!list[0]?.full_name?.trim()) {
        travelerError.value = `Falta el responsable en "${tr.tour_title}"`
        openTourId.value = tr.id
        return
      }
    }
    savingTravelers.value = true
    try {
      for (const tr of purchaseTours.value) {
        const valid = (travelersByTour.value[tr.id] || []).filter((x: any) => x.full_name?.trim())
        await api(`/bookings/${tr.id}/travelers`, { method: 'POST', body: { travelers: valid } })
      }
      completedSteps.value.add(2)
      currentStep.value = 3
    } catch (e: any) {
      travelerError.value = t('error_saving')
    } finally {
      savingTravelers.value = false
    }
    return
  }

  // SINGLE TOUR
  if (!travelers.value[0]?.full_name?.trim()) {
    travelerError.value = t('leader_required')
    return
  }
  const validTravelers = travelers.value.filter(tr => tr.full_name?.trim())
  if (validTravelers.length === 0) {
    travelerError.value = t('traveler_required')
    return
  }
  savingTravelers.value = true
  try {
    await api(`/bookings/${booking.value.id}/travelers`, {
      method: 'POST',
      body: { travelers: validTravelers }
    })
    completedSteps.value.add(2)
    currentStep.value = 3
  } catch (e: any) {
    travelerError.value = t('error_saving')
  } finally {
    savingTravelers.value = false
  }
}

// Voucher = the printable confirmation page. window.print() lets the user
// save it as PDF / print, and works on mobile (no backend PDF endpoint needed).
function downloadVoucher() {
  if (import.meta.client) window.print()
}
</script>

<style>
@media print { nav, footer, button { display: none !important; } }
</style>
