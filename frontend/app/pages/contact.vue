<template>
  <!-- pt-24 clears the fixed navbar — the page used to start at py-12 and the
       title was sliced in half by it. -->
  <div class="min-h-screen bg-background-light pt-24 pb-12 md:pt-28 md:pb-16">
    <div class="max-w-5xl mx-auto px-4 md:px-6">
      <div class="text-center mb-8 md:mb-10">
        <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2">{{ t('contact_title') }}</h1>
        <p class="text-sm md:text-base text-slate-500">{{ t('contact_subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 md:gap-6">
        <!-- Contact form -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
          <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4">{{ t('contact_form_title') }}</h2>

          <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label for="contact-name" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                {{ t('contact_name') }} *
              </label>
              <input
                id="contact-name"
                v-model="form.name"
                type="text"
                required
                maxlength="120"
                autocomplete="name"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
              />
            </div>

            <div>
              <label for="contact-email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                {{ t('contact_email') }} *
              </label>
              <input
                id="contact-email"
                v-model="form.email"
                type="email"
                required
                maxlength="160"
                autocomplete="email"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
              />
            </div>

            <div>
              <label for="contact-phone" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                {{ t('contact_phone') }}
              </label>
              <input
                id="contact-phone"
                v-model="form.phone"
                type="tel"
                maxlength="40"
                autocomplete="tel"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
              />
            </div>

            <div>
              <label for="contact-message" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                {{ t('contact_message') }} *
              </label>
              <textarea
                id="contact-message"
                v-model="form.message"
                rows="5"
                required
                maxlength="3000"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-y"
              ></textarea>
            </div>

            <button type="submit" :disabled="loading" class="btn-primary w-full disabled:opacity-60 disabled:cursor-not-allowed">
              <Icon v-if="loading" name="material-symbols:progress-activity" class="size-5 animate-spin" />
              {{ loading ? t('contact_sending') : t('contact_send') }}
            </button>

            <div v-if="error" class="flex items-start gap-2 px-3 py-2.5 bg-red-50 border border-red-200 rounded-xl" role="alert">
              <Icon name="material-symbols:error-outline" class="size-4 text-red-500 shrink-0 mt-0.5" />
              <span class="text-xs font-semibold text-red-700">{{ error }}</span>
            </div>
          </form>

          <!-- Success replaces the form: the message is really stored + emailed
               now, so the confirmation can point at what happens next. -->
          <div v-else class="text-center py-6">
            <div class="size-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <Icon name="material-symbols:check-circle-outline" class="size-8 text-green-600" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-1.5">{{ t('contact_success_title') }}</h3>
            <p class="text-sm text-slate-500 mb-5">{{ t('contact_success_body') }}</p>
            <a :href="whatsappHref" target="_blank" rel="noopener" class="btn-primary">
              <Icon name="material-symbols:chat" class="size-5" />
              {{ t('contact_whatsapp_cta') }}
            </a>
          </div>
        </div>

        <!-- Contact info -->
        <div class="space-y-5">
          <!-- WhatsApp first: it is how this business actually talks to travellers -->
          <a
            :href="whatsappHref"
            target="_blank"
            rel="noopener"
            class="group flex items-center gap-4 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:border-primary/50 hover:shadow-md transition-all"
          >
            <div class="size-12 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
              <Icon name="material-symbols:chat" class="size-6 text-green-600" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-900 group-hover:text-primary transition-colors">
                {{ t('contact_whatsapp_cta') }}
              </p>
              <p class="text-sm text-slate-500">{{ PHONE_DISPLAY }}</p>
            </div>
            <Icon name="material-symbols:arrow-forward" class="size-5 text-slate-300 ml-auto group-hover:translate-x-1 group-hover:text-primary transition-all" />
          </a>

          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
            <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4">{{ t('contact_info_title') }}</h2>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <Icon name="material-symbols:call-outline" class="size-5 text-primary shrink-0 mt-0.5" />
                <div>
                  <h3 class="text-sm font-bold text-slate-900">{{ t('contact_phone_label') }}</h3>
                  <a :href="`tel:+51${PHONE_RAW}`" class="text-sm text-slate-500 hover:text-primary transition-colors">
                    {{ PHONE_DISPLAY }}
                  </a>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Icon name="material-symbols:mail-outline" class="size-5 text-primary shrink-0 mt-0.5" />
                <div>
                  <h3 class="text-sm font-bold text-slate-900">{{ t('contact_email') }}</h3>
                  <a :href="`mailto:${EMAIL}`" class="text-sm text-slate-500 hover:text-primary transition-colors">{{ EMAIL }}</a>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Icon name="material-symbols:location-on-outline" class="size-5 text-primary shrink-0 mt-0.5" />
                <div>
                  <h3 class="text-sm font-bold text-slate-900">{{ t('contact_location') }}</h3>
                  <p class="text-sm text-slate-500">Puno, Perú</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Icon name="material-symbols:schedule-outline" class="size-5 text-primary shrink-0 mt-0.5" />
                <div>
                  <h3 class="text-sm font-bold text-slate-900">{{ t('contact_hours') }}</h3>
                  <p class="text-sm text-slate-500">{{ t('contact_hours_value') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { t, locale } = useI18n()
const { api } = useApi()

// Single source for the real contact details. The page used to publish a made
// up address and phone ("Jr. Lima 123", "+51 951 234 567") plus href="#" social
// links — worse than showing nothing, since a traveller may actually dial it.
const PHONE_RAW = '982769453'
const PHONE_DISPLAY = '+51 982 769 453'
const EMAIL = 'reservas@incalake.com'

const whatsappHref = computed(() =>
  `https://wa.me/51${PHONE_RAW}?text=${encodeURIComponent(t('contact_whatsapp_text'))}`
)

useHead({
  title: 'Contacto | Incalake Tours',
  meta: [
    {
      name: 'description',
      content: 'Contáctanos para reservar tu tour en el Lago Titicaca o resolver cualquier duda. Escríbenos por WhatsApp al +51 982 769 453.'
    }
  ]
})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  message: '',
})

const loading = ref(false)
const success = ref(false)
const error = ref<string | null>(null)

async function handleSubmit() {
  loading.value = true
  error.value = null

  try {
    await api('/contact', {
      method: 'POST',
      body: {
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        message: form.message,
        language: locale.value,
      },
    })
    success.value = true
    form.name = ''
    form.email = ''
    form.phone = ''
    form.message = ''
  } catch (e: any) {
    // 429 = the anti-spam throttle; anything else is a genuine failure. Either
    // way the traveller gets WhatsApp as an escape hatch, never a fake success.
    error.value = e?.statusCode === 429 ? t('contact_error_throttled') : t('contact_error')
  } finally {
    loading.value = false
  }
}
</script>
