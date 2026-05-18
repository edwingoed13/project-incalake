<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Props {
  booking: any
  open: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  updated: []
}>()

const config = useRuntimeConfig()
const API = config.public.apiUrl
const fullDetails = ref<any>(null)
const loadingDetails = ref(true)

onMounted(async () => {
  try {
    const res: any = await $fetch(`${API}/bookings/${props.booking.id}/full-details`)
    if (res.success) fullDetails.value = res.data
  } catch {
    // optional — sections just won't render
  } finally {
    loadingDetails.value = false
  }
})

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('es-ES', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const statusBadge: Record<string, { color: 'warning' | 'success' | 'error' | 'info' | 'neutral'; label: string; icon: string }> = {
  pending: { color: 'warning', label: 'Pendiente', icon: 'i-lucide-clock' },
  confirmed: { color: 'success', label: 'Confirmado', icon: 'i-lucide-circle-check' },
  cancelled: { color: 'error', label: 'Cancelado', icon: 'i-lucide-circle-x' },
  completed: { color: 'info', label: 'Completado', icon: 'i-lucide-check-check' },
}

const paymentStatusBadge: Record<string, { color: 'success' | 'warning' | 'error' | 'neutral'; label: string }> = {
  paid: { color: 'success', label: 'Pagado' },
  pending: { color: 'warning', label: 'Pendiente' },
  failed: { color: 'error', label: 'Fallido' },
  refunded: { color: 'neutral', label: 'Reembolsado' },
}

// Multi-tour purchase: every booking paid in the same charge (from
// /full-details). Empty/1 -> single-tour layout.
const groupTours = computed(() => (fullDetails.value as any)?.group || [])
const isMultiTour = computed(() => groupTours.value.length > 1)

// For a multi-tour purchase the Payment section must show the WHOLE
// purchase (what was charged), not just the primary booking's amount.
const groupSubtotal = computed(() =>
  groupTours.value.reduce((s: number, t: any) => s + Number(t.subtotal || 0), 0)
)
const groupTotal = computed(() =>
  groupTours.value.reduce((s: number, t: any) => s + Number(t.total || 0), 0)
)

const pickupLabel = (tr: any) => {
  if (!tr.pickup_configured) return null
  if (tr.pickup_choice === 'hotel_pickup') {
    return tr.pickup_hotel ? `Recojo en hotel: ${tr.pickup_hotel}` : 'Recojo en hotel'
  }
  return 'Punto de encuentro'
}

const totalPax = computed(() => {
  const b = props.booking
  return b.total_participants || ((b.adults || 0) + (b.children || 0) + (b.infants || 0))
})

const close = () => {
  emit('update:open', false)
  emit('close')
}
</script>

<template>
  <UModal :open="open" :ui="{ content: 'max-w-3xl' }" @update:open="(v) => !v && close()">
    <template #content>
      <div class="flex flex-col max-h-[90vh] bg-default rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-default flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <UIcon name="i-lucide-receipt" class="size-5 text-primary" />
            </div>
            <div>
              <h2 class="text-lg font-bold">Detalles de reserva</h2>
              <p class="text-xs text-muted mt-0.5 font-mono">{{ booking.booking_code }}</p>
            </div>
          </div>
          <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="close" />
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <!-- Status row -->
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <UBadge
              :color="statusBadge[booking.status]?.color || 'neutral'"
              variant="subtle"
              size="lg"
              :icon="statusBadge[booking.status]?.icon"
            >
              {{ statusBadge[booking.status]?.label || booking.status }}
            </UBadge>
            <span class="text-xs text-muted">
              Creado: <span class="font-medium text-default">{{ formatDateTime(booking.created_at) }}</span>
            </span>
          </div>

          <!-- Cliente -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-user" class="size-3.5" />
              Información del cliente
            </h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted mb-1">Nombre</p>
                <p class="text-sm font-medium">{{ booking.customer_name || '—' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Email</p>
                <p class="text-sm font-medium truncate">{{ booking.customer_email || '—' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Teléfono</p>
                <p class="text-sm font-medium">{{ booking.customer_phone || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">País</p>
                <p class="text-sm font-medium">{{ booking.customer_country || 'N/A' }}</p>
              </div>
            </div>
          </section>

          <USeparator />

          <!-- Tour(s) -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-map-pin" class="size-3.5" />
              {{ isMultiTour ? `Tours de la compra (${groupTours.length})` : 'Información del tour' }}
            </h3>

            <!-- Multi-tour purchase: one row per tour -->
            <div v-if="isMultiTour" class="space-y-2">
              <div
                v-for="(tr, i) in groupTours"
                :key="tr.booking_code + i"
                class="rounded-lg border border-default p-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ tr.tour_title || 'N/A' }}</p>
                    <p class="text-xs text-muted mt-0.5">
                      {{ formatDate(tr.tour_date) }}<span v-if="tr.tour_time"> · {{ String(tr.tour_time).substring(0,5) }}</span>
                      · {{ (tr.adults || 0) + (tr.children || 0) }} pax
                    </p>
                    <p class="text-[10px] font-mono text-muted mt-0.5">{{ tr.booking_code }}</p>
                  </div>
                  <p class="text-sm font-bold tabular-nums shrink-0">{{ tr.currency || '' }} {{ Number(tr.total || 0).toFixed(2) }}</p>
                </div>
                <div class="mt-2 pt-2 border-t border-default flex items-center gap-2 flex-wrap">
                  <template v-if="pickupLabel(tr)">
                    <UBadge
                      :color="tr.pickup_choice === 'hotel_pickup' ? 'info' : 'success'"
                      variant="subtle"
                      size="xs"
                      :icon="tr.pickup_choice === 'hotel_pickup' ? 'i-lucide-hotel' : 'i-lucide-flag'"
                    >
                      {{ pickupLabel(tr) }}
                    </UBadge>
                    <span v-if="tr.pickup_extra_cost > 0" class="text-[10px] font-bold text-warning">
                      + ${{ Number(tr.pickup_extra_cost).toFixed(2) }}
                    </span>
                  </template>
                  <UBadge v-else color="neutral" variant="subtle" size="xs" icon="i-lucide-clock">
                    Pickup pendiente de configurar
                  </UBadge>
                </div>
              </div>
            </div>

            <!-- Single tour -->
            <div v-else class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <p class="text-xs text-muted mb-1">Tour</p>
                <p class="text-sm font-medium">{{ booking.tour_title || booking.tour?.title || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Fecha del tour</p>
                <p class="text-sm font-medium">{{ formatDate(booking.tour_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Pasajeros</p>
                <p class="text-sm font-medium">{{ totalPax }} personas</p>
              </div>
              <div v-if="booking.adults">
                <p class="text-xs text-muted mb-1">Adultos</p>
                <p class="text-sm font-medium">{{ booking.adults }}</p>
              </div>
              <div v-if="booking.children">
                <p class="text-xs text-muted mb-1">Niños</p>
                <p class="text-sm font-medium">{{ booking.children }}</p>
              </div>
            </div>
          </section>

          <USeparator />

          <!-- Pago -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-credit-card" class="size-3.5" />
              Información de pago
            </h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-muted mb-1">Método de pago</p>
                <UBadge color="neutral" variant="subtle" size="sm">
                  {{ booking.payment_method?.toUpperCase() || 'N/A' }}
                </UBadge>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Estado de pago</p>
                <UBadge
                  :color="paymentStatusBadge[booking.payment_status]?.color || 'neutral'"
                  variant="subtle"
                  size="sm"
                >
                  {{ paymentStatusBadge[booking.payment_status]?.label || booking.payment_status }}
                </UBadge>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Subtotal{{ isMultiTour ? ` (${groupTours.length} tours)` : '' }}</p>
                <p class="text-sm font-medium tabular-nums">
                  ${{ isMultiTour ? groupSubtotal.toFixed(2) : parseFloat(String(booking.subtotal || booking.total || 0)).toFixed(2) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Total {{ isMultiTour ? 'de la compra' : '' }}</p>
                <p class="text-xl font-bold tabular-nums text-primary">
                  ${{ isMultiTour ? groupTotal.toFixed(2) : parseFloat(String(booking.total || booking.total_amount || 0)).toFixed(2) }}
                </p>
              </div>
            </div>
          </section>

          <!-- Solicitudes especiales -->
          <template v-if="booking.special_requests">
            <USeparator />
            <section>
              <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-message-square" class="size-3.5" />
                Solicitudes especiales
              </h3>
              <UAlert color="warning" variant="subtle" :description="booking.special_requests" />
            </section>
          </template>

          <!-- Loading full details -->
          <template v-if="loadingDetails">
            <USeparator />
            <div class="space-y-3">
              <USkeleton class="h-4 w-32" />
              <USkeleton class="h-20 w-full" />
            </div>
          </template>

          <!-- Pickup (single-tour only; multi-tour shows pickup per tour above) -->
          <template v-if="fullDetails?.pickup_detail && !isMultiTour">
            <USeparator />
            <section>
              <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-map-pinned" class="size-3.5" />
                Configuración de pickup
              </h3>
              <UCard :ui="{ body: 'p-4 space-y-2' }">
                <div class="flex items-center gap-2 flex-wrap">
                  <UBadge
                    :color="fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'info' : 'success'"
                    variant="subtle"
                    size="sm"
                    :icon="fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'i-lucide-hotel' : 'i-lucide-flag'"
                  >
                    {{ fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'Hotel Pickup' : 'Meeting Point' }}
                  </UBadge>
                  <UBadge
                    v-if="fullDetails.pickup_detail.requires_logistics_approval"
                    color="warning"
                    variant="subtle"
                    size="sm"
                    icon="i-lucide-shield-alert"
                  >
                    {{ fullDetails.pickup_detail.approval_status || 'Pending Approval' }}
                  </UBadge>
                </div>
                <div v-if="fullDetails.pickup_detail.hotel_name" class="pt-1">
                  <p class="text-sm font-semibold">{{ fullDetails.pickup_detail.hotel_name }}</p>
                  <p class="text-xs text-muted">{{ fullDetails.pickup_detail.hotel_address }}</p>
                </div>
                <div v-if="fullDetails.pickup_detail.distance_from_center" class="flex gap-4 text-xs text-muted pt-1">
                  <span>Distancia: {{ parseFloat(fullDetails.pickup_detail.distance_from_center).toFixed(1) }} km</span>
                  <span v-if="fullDetails.pickup_detail.extra_pickup_cost > 0" class="font-bold text-warning">
                    Extra: ${{ parseFloat(fullDetails.pickup_detail.extra_pickup_cost).toFixed(2) }}
                  </span>
                </div>
              </UCard>
            </section>
          </template>

          <!-- Travelers -->
          <template v-if="fullDetails?.travelers?.length">
            <USeparator />
            <section>
              <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-users" class="size-3.5" />
                Viajeros ({{ fullDetails.travelers.length }})
              </h3>
              <div class="space-y-2">
                <UCard
                  v-for="(t, idx) in fullDetails.travelers"
                  :key="t.id"
                  :ui="{ body: 'p-3' }"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="size-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                      :class="t.is_leader ? 'bg-primary/10 text-primary' : 'bg-elevated text-muted'"
                    >
                      {{ idx + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-semibold truncate">{{ t.full_name }}</p>
                        <UBadge v-if="t.is_leader" color="primary" variant="subtle" size="xs" icon="i-lucide-crown">Leader</UBadge>
                        <UBadge v-if="t.age_group && t.age_group !== 'adult'" color="warning" variant="subtle" size="xs">
                          {{ t.age_group }}
                        </UBadge>
                      </div>
                      <div class="flex gap-3 text-[10px] text-muted mt-1 flex-wrap">
                        <span v-if="t.nationality" class="flex items-center gap-1">
                          <UIcon name="i-lucide-globe" class="size-3" />
                          {{ t.nationality }}
                        </span>
                        <span v-if="t.doc_type && t.doc_number" class="flex items-center gap-1">
                          <UIcon name="i-lucide-id-card" class="size-3" />
                          {{ t.doc_type.toUpperCase() }}: {{ t.doc_number }}
                        </span>
                        <span v-if="t.special_needs" class="flex items-center gap-1 text-warning">
                          <UIcon name="i-lucide-heart-pulse" class="size-3" />
                          {{ t.special_needs }}
                        </span>
                      </div>
                    </div>
                  </div>
                </UCard>
              </div>
            </section>
          </template>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="close">Cerrar</UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>
