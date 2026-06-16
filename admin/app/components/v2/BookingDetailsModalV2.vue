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

// Friendly labels for the traveler document types (stored as short codes).
const DOC_TYPE_LABELS: Record<string, string> = {
  passport: 'Pasaporte',
  dni: 'DNI',
  ce: 'Carné de extranjería',
  cedula: 'Cédula',
  run: 'RUN',
  rut: 'RUT',
  other: 'Documento',
}
const docTypeLabel = (code?: string) => (code ? (DOC_TYPE_LABELS[code] ?? code.toUpperCase()) : '')

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

const paymentStateBadge: Record<string, { color: 'success' | 'warning' | 'neutral' | 'error'; label: string }> = {
  full: { color: 'success', label: 'Pagado total' },
  partial: { color: 'warning', label: 'Pago parcial' },
  refunded: { color: 'neutral', label: 'Reembolsado' },
  unpaid: { color: 'error', label: 'Sin pagar' },
}

// Multi-tour purchase: every booking paid in the same charge (from
// /full-details). Empty/1 -> single-tour layout.
const groupTours = computed(() => (fullDetails.value as any)?.group || [])
const isMultiTour = computed(() => groupTours.value.length > 1)

const groupSubtotal = computed(() =>
  groupTours.value.reduce((s: number, t: any) => s + Number(t.subtotal || 0), 0)
)
const groupTotal = computed(() =>
  groupTours.value.reduce((s: number, t: any) => s + Number(t.total || 0), 0)
)

// Payment figures come from the list row (already derived from what was
// actually charged): payment_state + amount_paid + amount_remaining.
const grandTotal = computed(() =>
  isMultiTour.value
    ? groupTotal.value
    : Number(props.booking.group_total ?? props.booking.total ?? props.booking.total_amount ?? 0)
)
const isPartial = computed(() => props.booking.payment_state === 'partial')
const amountPaid = computed(() =>
  isPartial.value && props.booking.amount_paid != null ? Number(props.booking.amount_paid) : grandTotal.value
)
const balanceDue = computed(() =>
  isPartial.value && props.booking.amount_remaining != null ? Number(props.booking.amount_remaining) : 0
)
const currencyCode = computed(() => props.booking.currency || groupTours.value[0]?.currency || 'USD')

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

const whatsappHref = computed(() => {
  const phone = String(props.booking.customer_phone || '').replace(/[^\d]/g, '')
  return phone ? `https://wa.me/${phone}` : null
})

const close = () => {
  emit('update:open', false)
  emit('close')
}
</script>

<template>
  <USlideover
    :open="open"
    :ui="{ content: 'max-w-2xl w-full' }"
    @update:open="(v) => !v && close()"
  >
    <template #content>
      <div class="flex flex-col h-full bg-default">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-default">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <UIcon name="i-lucide-receipt" class="size-5 text-primary" />
              </div>
              <div class="min-w-0">
                <h2 class="text-lg font-bold leading-tight">Detalles de reserva</h2>
                <p class="text-xs text-muted font-mono truncate">{{ booking.booking_code }}</p>
              </div>
            </div>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="md" :ui="{ base: 'rounded-full' }" @click="close" />
          </div>
          <div class="flex items-center gap-2 flex-wrap mt-3">
            <UBadge
              :color="statusBadge[booking.status]?.color || 'neutral'"
              variant="subtle"
              size="sm"
              :icon="statusBadge[booking.status]?.icon"
            >
              {{ statusBadge[booking.status]?.label || booking.status }}
            </UBadge>
            <UBadge
              v-if="booking.payment_state && paymentStateBadge[booking.payment_state]"
              :color="paymentStateBadge[booking.payment_state].color"
              variant="soft"
              size="sm"
            >
              {{ paymentStateBadge[booking.payment_state].label }}
            </UBadge>
            <UBadge v-if="isMultiTour" color="primary" variant="subtle" size="sm" icon="i-lucide-squares-2x2">
              {{ groupTours.length }} tours
            </UBadge>
            <span class="text-[11px] text-muted ml-auto">Creado: {{ formatDateTime(booking.created_at) }}</span>
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-5 space-y-6">
          <!-- Payment summary (operations: paid now vs balance) -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-credit-card" class="size-3.5" />
              Pago
            </h3>
            <div class="rounded-xl border border-default overflow-hidden">
              <div class="grid grid-cols-3 divide-x divide-default">
                <div class="p-3">
                  <p class="text-[10px] text-muted uppercase font-semibold">Total</p>
                  <p class="text-base font-bold tabular-nums mt-0.5">{{ currencyCode }} {{ grandTotal.toFixed(2) }}</p>
                </div>
                <div class="p-3">
                  <p class="text-[10px] text-muted uppercase font-semibold">Pagado</p>
                  <p class="text-base font-bold tabular-nums mt-0.5 text-success">{{ currencyCode }} {{ amountPaid.toFixed(2) }}</p>
                </div>
                <div class="p-3">
                  <p class="text-[10px] text-muted uppercase font-semibold">Saldo</p>
                  <p class="text-base font-bold tabular-nums mt-0.5" :class="balanceDue > 0 ? 'text-warning' : 'text-muted'">
                    {{ currencyCode }} {{ balanceDue.toFixed(2) }}
                  </p>
                </div>
              </div>
              <div class="px-3 py-2 bg-elevated/40 border-t border-default flex items-center justify-between gap-2 text-xs">
                <span class="text-muted">Método</span>
                <UBadge color="neutral" variant="subtle" size="sm">{{ (booking.payment_method || 'N/A').toUpperCase() }}</UBadge>
              </div>
              <div v-if="balanceDue > 0" class="px-3 py-2 bg-warning/10 border-t border-warning/20 text-[11px] text-warning font-semibold flex items-center gap-1.5">
                <UIcon name="i-lucide-alert-triangle" class="size-3.5" />
                Cobrar saldo de {{ currencyCode }} {{ balanceDue.toFixed(2) }} el día del tour
              </div>
            </div>
          </section>

          <!-- Cliente -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-user" class="size-3.5" />
              Cliente
            </h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
              <div>
                <p class="text-xs text-muted mb-1">Nombre</p>
                <p class="text-sm font-medium">{{ booking.customer_name || '—' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">País</p>
                <p class="text-sm font-medium">{{ booking.customer_country || 'N/A' }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-xs text-muted mb-1">Email</p>
                <a :href="`mailto:${booking.customer_email}`" class="text-sm font-medium text-primary hover:underline truncate block">{{ booking.customer_email || '—' }}</a>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Teléfono</p>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium">{{ booking.customer_phone || 'N/A' }}</span>
                  <a v-if="whatsappHref" :href="whatsappHref" target="_blank" class="text-success hover:opacity-80" title="WhatsApp">
                    <UIcon name="i-lucide-message-circle" class="size-4" />
                  </a>
                </div>
              </div>
            </div>
          </section>

          <USeparator />

          <!-- Tour(s) -->
          <section>
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-map-pin" class="size-3.5" />
              {{ isMultiTour ? `Tours de la compra (${groupTours.length})` : 'Tour' }}
            </h3>

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
                    Pickup pendiente
                  </UBadge>
                </div>
              </div>
            </div>

            <div v-else class="rounded-lg border border-default p-4 grid grid-cols-2 gap-x-4 gap-y-3">
              <div class="col-span-2">
                <p class="text-xs text-muted mb-1">Tour</p>
                <p class="text-sm font-medium">{{ booking.tour_title || booking.tour?.title || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Fecha</p>
                <p class="text-sm font-medium">{{ formatDate(booking.tour_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-muted mb-1">Pasajeros</p>
                <p class="text-sm font-medium">{{ totalPax }} ({{ booking.adults || 0 }}A<template v-if="booking.children"> · {{ booking.children }}N</template>)</p>
              </div>
            </div>
          </section>

          <!-- Pickup (single-tour only; multi-tour shows pickup per tour above) -->
          <template v-if="fullDetails?.pickup_detail && !isMultiTour">
            <USeparator />
            <section>
              <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-map-pinned" class="size-3.5" />
                Pickup
              </h3>
              <div class="rounded-lg border border-default p-4 space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                  <UBadge
                    :color="fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'info' : 'success'"
                    variant="subtle"
                    size="sm"
                    :icon="fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'i-lucide-hotel' : 'i-lucide-flag'"
                  >
                    {{ fullDetails.pickup_detail.final_choice === 'hotel_pickup' ? 'Recojo en hotel' : 'Punto de encuentro' }}
                  </UBadge>
                  <UBadge
                    v-if="fullDetails.pickup_detail.requires_logistics_approval"
                    color="warning"
                    variant="subtle"
                    size="sm"
                    icon="i-lucide-shield-alert"
                  >
                    {{ fullDetails.pickup_detail.approval_status || 'Pendiente de aprobación' }}
                  </UBadge>
                </div>
                <div v-if="fullDetails.pickup_detail.hotel_name">
                  <p class="text-sm font-semibold">{{ fullDetails.pickup_detail.hotel_name }}</p>
                  <p class="text-xs text-muted">{{ fullDetails.pickup_detail.hotel_address }}</p>
                </div>
                <div v-if="fullDetails.pickup_detail.distance_from_center" class="flex gap-4 text-xs text-muted">
                  <span>Distancia: {{ parseFloat(fullDetails.pickup_detail.distance_from_center).toFixed(1) }} km</span>
                  <span v-if="fullDetails.pickup_detail.extra_pickup_cost > 0" class="font-bold text-warning">
                    Extra: ${{ parseFloat(fullDetails.pickup_detail.extra_pickup_cost).toFixed(2) }}
                  </span>
                </div>
              </div>
            </section>
          </template>

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

          <!-- Travelers -->
          <template v-if="fullDetails?.travelers?.length">
            <USeparator />
            <section>
              <h3 class="text-[10px] font-black uppercase tracking-widest text-muted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-users" class="size-3.5" />
                Viajeros ({{ fullDetails.travelers.length }})
              </h3>
              <div class="space-y-2">
                <div
                  v-for="(t, idx) in fullDetails.travelers"
                  :key="t.id"
                  class="rounded-lg border border-default p-3"
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
                        <UBadge v-if="t.is_leader" color="primary" variant="subtle" size="xs" icon="i-lucide-crown">Líder</UBadge>
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
                          {{ docTypeLabel(t.doc_type) }}: {{ t.doc_number }}
                        </span>
                        <span v-if="t.special_needs" class="flex items-center gap-1 text-warning">
                          <UIcon name="i-lucide-heart-pulse" class="size-3" />
                          {{ t.special_needs }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </template>
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="close">Cerrar</UButton>
        </div>
      </div>
    </template>
  </USlideover>
</template>
