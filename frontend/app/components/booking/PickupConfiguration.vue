<template>
  <div class="max-w-2xl mx-auto">
    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
    </div>

    <div v-else class="space-y-5">
      <!-- ¿Cómo prefiere llegar? (solo cuando AMBAS opciones están habilitadas) -->
      <div v-if="bothEnabled && !selectedMethod" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
          <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">directions_bus</span>
            ¿Cómo prefieres llegar al tour?
          </h4>
          <p class="text-xs text-slate-500 mt-1">Este tour ofrece dos opciones. Elige la que más te convenga.</p>
        </div>
        <div class="p-5 grid sm:grid-cols-2 gap-3">
          <button
            type="button"
            @click="chooseMethod('hotel')"
            class="text-left border-2 border-slate-200 hover:border-primary rounded-2xl p-4 transition-all active:bg-primary/5"
          >
            <span class="material-symbols-outlined text-primary text-2xl">hotel</span>
            <p class="text-sm font-bold text-slate-800 mt-2">Recojo en mi hotel</p>
            <p class="text-xs text-slate-500 mt-0.5">Te recogemos en la puerta de tu alojamiento.</p>
          </button>
          <button
            type="button"
            @click="chooseMethod('meeting')"
            class="text-left border-2 border-slate-200 hover:border-primary rounded-2xl p-4 transition-all active:bg-primary/5"
          >
            <span class="material-symbols-outlined text-primary text-2xl">location_on</span>
            <p class="text-sm font-bold text-slate-800 mt-2">Ir al punto de encuentro</p>
            <p class="text-xs text-slate-500 mt-0.5">Te esperamos en el punto de encuentro indicado.</p>
          </button>
        </div>
      </div>

      <!-- Cambiar de método (cuando ambas están habilitadas y ya se eligió una) -->
      <button
        v-if="bothEnabled && selectedMethod"
        type="button"
        @click="resetMethod"
        class="inline-flex items-center gap-1 text-xs font-semibold text-primary active:text-primary/70"
      >
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Cambiar método de recojo
      </button>

      <!-- Hotel Pickup -->
      <div v-if="selectedMethod === 'hotel'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
          <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">hotel</span>
            Recojo en tu hotel
          </h4>
          <p class="text-xs text-slate-500 mt-1">Busca tu hotel para verificar si el recojo está incluido</p>
        </div>

        <div class="p-5 space-y-4">
          <!-- Hotel Search -->
          <div>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 block">Busca tu hotel</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
              <input
                ref="hotelSearchInput"
                type="text"
                class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="Nombre del hotel..."
                :disabled="isValidating"
              />
            </div>
          </div>

          <!-- Map -->
          <div ref="mapContainer" class="w-full h-64 sm:h-80 rounded-xl border border-slate-200 bg-slate-100"></div>

          <!-- Within Radius Result -->
          <div v-if="hotelValidation && isWithinRadius" class="bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-green-600 text-xl">check_circle</span>
              <div class="flex-1">
                <h5 class="text-sm font-bold text-green-800">¡Recojo incluido!</h5>
                <p class="text-xs text-green-700 mt-0.5">Tu hotel está dentro de la zona de recojo gratuito</p>
                <p class="text-xs text-green-600 mt-1 font-medium">{{ hotelValidation.hotel_name }}</p>
              </div>
            </div>
            <button
              @click="selectHotelPickup"
              :disabled="isSaving"
              class="w-full mt-3 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors disabled:opacity-50"
            >
              {{ isSaving ? 'Guardando...' : 'Confirmar recojo en hotel' }}
            </button>
          </div>

          <!-- Outside Radius Result -->
          <div v-else-if="hotelValidation && !isWithinRadius" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <span class="material-symbols-outlined text-amber-600 text-xl">warning</span>
              <div class="flex-1">
                <h5 class="text-sm font-bold text-amber-800">Fuera de la zona de recojo gratuito</h5>
                <p class="text-xs text-amber-700 mt-0.5">A {{ hotelValidation.distance?.toFixed(1) }} km de la zona incluida</p>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <p class="text-xs font-bold text-slate-700">Elige una opción:</p>

              <!-- Pay Extra -->
              <label class="block cursor-pointer">
                <div
                  class="border-2 rounded-xl p-3 transition-all"
                  :class="pickupChoice === 'hotel_pickup' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                  @click="pickupChoice = 'hotel_pickup'"
                >
                  <div class="flex items-center gap-3">
                    <input type="radio" v-model="pickupChoice" value="hotel_pickup" class="text-primary focus:ring-primary" />
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <span class="text-sm font-semibold">Recojo en hotel (costo adicional)</span>
                        <span class="text-primary font-bold text-sm">+${{ extraCost }}</span>
                      </div>
                      <p class="text-[10px] text-slate-500 mt-0.5">{{ requiresApproval ? 'Sujeto a confirmación' : 'Confirmación inmediata' }}</p>
                    </div>
                  </div>
                </div>
              </label>

              <!-- Meeting Point -->
              <label v-if="tourConfig.enable_meeting_point" class="block cursor-pointer">
                <div
                  class="border-2 rounded-xl p-3 transition-all"
                  :class="pickupChoice === 'meeting_point' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                  @click="pickupChoice = 'meeting_point'"
                >
                  <div class="flex items-center gap-3">
                    <input type="radio" v-model="pickupChoice" value="meeting_point" class="text-primary focus:ring-primary" />
                    <div class="flex-1">
                      <span class="text-sm font-semibold">Punto de encuentro (gratis)</span>
                      <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ tourConfig.meeting_point_description }}</p>
                      <button @click.stop.prevent="showMeetingPointOnMap" class="text-primary text-xs font-semibold mt-1">Ver en el mapa</button>
                    </div>
                  </div>
                </div>
              </label>

              <!-- WhatsApp option -->
              <a
                :href="`https://wa.me/51999999999?text=Hola, necesito recojo desde ${hotelValidation?.hotel_name} para la reserva ${bookingId}`"
                target="_blank"
                class="flex items-center gap-2 px-3 py-2 bg-green-500/10 text-green-700 rounded-xl text-xs font-semibold hover:bg-green-500/20 transition-colors"
              >
                <span class="material-symbols-outlined text-base">chat</span>
                Escríbenos por WhatsApp para coordinar un caso especial
              </a>
            </div>

            <button
              @click="saveConfig"
              :disabled="!pickupChoice || isSaving"
              class="w-full mt-4 bg-primary hover:brightness-110 text-white py-2.5 rounded-xl text-sm font-bold transition-all disabled:opacity-50"
            >
              {{ isSaving ? 'Guardando...' : 'Confirmar selección' }}
            </button>
          </div>

          <!-- Error -->
          <div v-if="validationError" class="bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ validationError }}</p>
          </div>
        </div>
      </div>

      <!-- Meeting Point -->
      <div v-else-if="selectedMethod === 'meeting'" class="bg-white rounded-2xl border border-slate-200 p-5">
        <h4 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-4">
          <span class="material-symbols-outlined text-primary">location_on</span>
          Punto de encuentro
        </h4>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
          <p class="text-blue-800 text-sm font-semibold">{{ tourConfig.meeting_point_description }}</p>
        </div>
        <div ref="meetingMapContainer" class="w-full h-48 sm:h-64 rounded-xl border border-slate-200 mt-3"></div>
        <button
          @click="confirmMeetingPoint"
          :disabled="isSaving"
          class="w-full mt-4 bg-primary text-white py-2.5 rounded-xl text-sm font-bold hover:brightness-110 transition-all disabled:opacity-50"
        >
          {{ isSaving ? 'Guardando...' : 'Confirmar punto de encuentro' }}
        </button>
      </div>

      <!-- No options -->
      <div v-else-if="!tourConfig.enable_hotel_pickup && !tourConfig.enable_meeting_point" class="bg-slate-50 rounded-2xl p-6 text-center">
        <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">info</span>
        <p class="text-sm text-slate-500">Este tour no tiene opciones de recojo configuradas. Nos pondremos en contacto contigo con los detalles.</p>
        <button @click="emit('completed', {})" class="mt-4 bg-primary text-white px-6 py-2.5 rounded-xl text-sm font-bold">Continuar</button>
      </div>

      <div v-if="saveError" class="bg-red-50 border border-red-200 rounded-xl p-3">
        <p class="text-red-700 text-sm">{{ saveError }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useHotelPickupValidation } from '~/composables/useHotelPickupValidation'

const props = defineProps<{ bookingId: number | string }>()
const emit = defineEmits<{ completed: [data: any]; error: [msg: string] }>()

const { api } = useApi()
const hotelSearchInput = ref<HTMLInputElement | null>(null)
const mapContainer = ref<HTMLElement | null>(null)
const meetingMapContainer = ref<HTMLElement | null>(null)
const isLoading = ref(true)
const tourConfig = ref<any>({})
const bookingIdRef = computed(() => props.bookingId)

// When the admin enables BOTH meeting point and hotel pickup, the customer
// must be able to choose freely between them up front (not only as a
// fallback when their hotel is outside the radius).
const bothEnabled = computed(
  () => !!(tourConfig.value.enable_hotel_pickup && tourConfig.value.enable_meeting_point)
)
const selectedMethod = ref<'hotel' | 'meeting' | null>(null)

const {
  hotelValidation, pickupChoice, isValidating, validationError,
  isSaving, saveError, isWithinRadius, extraCost, requiresApproval,
  initializeHotelSearch, savePickupConfiguration, showMeetingPointOnMap, cleanup
} = useHotelPickupValidation(bookingIdRef, tourConfig)

function initSelectedMap() {
  setTimeout(() => {
    if (selectedMethod.value === 'hotel' && hotelSearchInput.value && mapContainer.value) {
      initializeHotelSearch(hotelSearchInput.value, mapContainer.value)
    } else if (selectedMethod.value === 'meeting' && meetingMapContainer.value) {
      initMeetingPointMap()
    }
  }, 150)
}

async function chooseMethod(method: 'hotel' | 'meeting') {
  selectedMethod.value = method
  await nextTick()
  initSelectedMap()
}

async function resetMethod() {
  cleanup()
  hotelValidation.value = null
  pickupChoice.value = null
  validationError.value = null
  selectedMethod.value = null
}

async function loadPickupDetails() {
  try {
    const res = await api(`/bookings/${props.bookingId}/pickup-details`)
    const data = (res as any)?.data || res
    tourConfig.value = data.tour_config || {}

    // Single option enabled -> go straight into it. Both -> show the chooser.
    if (tourConfig.value.enable_hotel_pickup && !tourConfig.value.enable_meeting_point) {
      selectedMethod.value = 'hotel'
    } else if (tourConfig.value.enable_meeting_point && !tourConfig.value.enable_hotel_pickup) {
      selectedMethod.value = 'meeting'
    }

    await nextTick()
    if (selectedMethod.value) initSelectedMap()
  } catch (e: any) {
    emit('error', 'Error al cargar las opciones de recojo')
  } finally {
    isLoading.value = false
  }
}

async function initMeetingPointMap() {
  if (!meetingMapContainer.value) return
  try {
    await useGoogleMaps()
  } catch (e) {
    console.error('Google Maps failed to load', e)
    return
  }
  const lat = parseFloat(tourConfig.value.meeting_point_lat) || -15.8402
  const lng = parseFloat(tourConfig.value.meeting_point_lng) || -70.0219
  const map = new google.maps.Map(meetingMapContainer.value, {
    center: { lat, lng }, zoom: 16, mapTypeControl: false, streetViewControl: false
  })
  new google.maps.Marker({ map, position: { lat, lng }, title: 'Punto de encuentro', animation: google.maps.Animation.DROP })
}

async function selectHotelPickup() {
  pickupChoice.value = 'hotel_pickup'
  await saveConfig()
}

async function confirmMeetingPoint() {
  pickupChoice.value = 'meeting_point'
  await saveConfig()
}

async function saveConfig() {
  const success = await savePickupConfiguration()
  if (success) {
    emit('completed', {
      pickupChoice: pickupChoice.value,
      hotelName: hotelValidation.value?.hotel_name,
      extraCost: extraCost.value
    })
  }
}

onMounted(() => loadPickupDetails())
onUnmounted(() => cleanup())
</script>
