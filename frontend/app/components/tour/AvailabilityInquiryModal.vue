<script setup lang="ts">
// Availability request form for tours flagged `require_availability`. Instead of
// an instant booking, the customer sends a lead (name/contact/date/pax) which
// the backend stores + emails to reservas@incalake.com. Labels are hardcoded
// Spanish to match the rest of the booking card.
const props = defineProps<{
  open: boolean
  tour: any
  prefillDate?: string
  prefillAdults?: number
  prefillChildren?: number
}>()

const emit = defineEmits<{ close: [] }>()

const { api } = useApi()
const { locale } = useI18n()

const form = reactive({
  name: '', email: '', phone: '', message: '',
  preferred_date: '', adults: 1, children: 0,
})
const sending = ref(false)
const sent = ref(false)
const error = ref('')

watch(() => props.open, (o) => {
  if (!o) return
  form.preferred_date = props.prefillDate || ''
  form.adults = props.prefillAdults || 1
  form.children = props.prefillChildren || 0
  sent.value = false
  error.value = ''
})

async function submit() {
  error.value = ''
  if (!form.name.trim()) { error.value = 'Ingresa tu nombre.'; return }
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(form.email)) { error.value = 'Ingresa un email válido.'; return }
  sending.value = true
  try {
    await api('/availability-inquiry', {
      method: 'POST',
      body: {
        tour_id: props.tour?.id,
        tour_title: props.tour?.title,
        name: form.name,
        email: form.email,
        phone: form.phone,
        preferred_date: form.preferred_date || null,
        adults: form.adults,
        children: form.children,
        message: form.message,
        language: locale.value,
      },
    })
    sent.value = true
  } catch {
    error.value = 'No se pudo enviar. Por favor, intenta nuevamente.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="sheet">
      <div v-if="open" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>

        <div class="relative w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl max-h-[92vh] overflow-y-auto">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3">
            <div>
              <h3 class="text-lg font-black text-slate-900">Consultar disponibilidad</h3>
              <p v-if="tour?.title" class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ tour.title }}</p>
            </div>
            <button @click="emit('close')" class="size-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 shrink-0" aria-label="Cerrar">
              <Icon name="material-symbols:close" class="text-xl" />
            </button>
          </div>

          <!-- Success -->
          <div v-if="sent" class="p-8 text-center">
            <div class="size-14 rounded-full bg-trust-soft text-trust flex items-center justify-center mx-auto mb-4">
              <Icon name="material-symbols:check-circle" class="text-3xl" />
            </div>
            <h4 class="text-lg font-black text-slate-900 mb-1">¡Consulta enviada!</h4>
            <p class="text-sm text-slate-500 mb-5">Revisaremos la disponibilidad y te responderemos pronto por correo.</p>
            <button @click="emit('close')" class="w-full min-h-[48px] bg-primary text-white font-bold rounded-xl active:scale-[0.98] transition-transform">
              Listo
            </button>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="submit" class="p-5 space-y-3.5">
            <p class="flex items-start gap-2 text-xs text-slate-500 bg-slate-50 rounded-lg p-3">
              <Icon name="material-symbols:info-outline" class="size-4 text-primary shrink-0 mt-0.5" />
              Este tour requiere confirmar disponibilidad. Déjanos tus datos y te respondemos pronto.
            </p>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Fecha deseada</label>
                <input v-model="form.preferred_date" type="date" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Adultos</label>
                  <input v-model.number="form.adults" type="number" min="1" max="99" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Niños</label>
                  <input v-model.number="form.children" type="number" min="0" max="99" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nombre completo *</label>
              <input v-model="form.name" type="text" placeholder="Tu nombre" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                <input v-model="form.email" type="email" placeholder="tu@email.com" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp / Teléfono</label>
                <input v-model="form.phone" type="tel" placeholder="+51 ..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Mensaje (opcional)</label>
              <textarea v-model="form.message" rows="3" placeholder="¿Alguna preferencia o pregunta?" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
            </div>

            <div v-if="error" class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg" role="alert">
              <Icon name="material-symbols:error-outline" class="size-4 text-red-500 shrink-0" />
              <span class="text-xs font-semibold text-red-700">{{ error }}</span>
            </div>

            <button type="submit" :disabled="sending" class="w-full min-h-[52px] bg-primary hover:bg-primary-dark text-white font-extrabold rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-[0.98] inline-flex items-center justify-center gap-2 disabled:opacity-60">
              <Icon v-if="sending" name="material-symbols:progress-activity" class="size-5 animate-spin" />
              {{ sending ? 'Enviando...' : 'Enviar consulta' }}
            </button>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
