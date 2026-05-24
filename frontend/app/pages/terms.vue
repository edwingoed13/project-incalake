<template>
  <div class="min-h-screen bg-slate-50 dark:bg-background-dark pt-24 pb-20 px-5 md:px-6">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <header class="mb-10 md:mb-14">
        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-primary mb-2">Incalake Tours</p>
        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900 dark:text-white">
          Términos y Privacidad
        </h1>
        <p class="mt-3 text-sm text-slate-500">Última actualización: {{ lastUpdated }}</p>
      </header>

      <!-- Quick nav -->
      <nav class="flex flex-wrap gap-2 mb-10">
        <a
          v-for="s in sections"
          :key="s.id"
          :href="`#${s.id}`"
          class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:border-primary/40 hover:text-primary transition-colors"
        >
          {{ s.title }}
        </a>
      </nav>

      <!-- Términos -->
      <section id="terminos" class="scroll-mt-24 mb-12">
        <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mb-4">1. Términos y Condiciones</h2>
        <div class="space-y-5 text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Reservas</h3>
            <p>Al realizar una reserva en Incalake Tours aceptas estos términos. La reserva queda confirmada únicamente tras la recepción del pago (total o del anticipo indicado) y el envío del comprobante correspondiente a tu correo electrónico.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Pagos</h3>
            <p>Los precios están expresados en dólares estadounidenses (USD). Los pagos con tarjeta se procesan mediante pasarelas seguras; las conversiones a otras monedas son referenciales y el cargo final se realiza en USD.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Cancelaciones y reembolsos</h3>
            <p>Cada tour puede tener su propia política de cancelación, indicada en la página del tour y en tu comprobante de reserva. Salvo que se indique lo contrario, las cancelaciones realizadas con la anticipación señalada dan derecho a reembolso. No se reembolsan servicios no utilizados ni inasistencias (no-show).</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Responsabilidades del viajero</h3>
            <p>El viajero es responsable de presentarse a tiempo en el punto de encuentro, de contar con la documentación requerida y de comunicar cualquier condición médica o alimentaria relevante. Incalake Tours puede modificar itinerarios por razones de seguridad, clima o fuerza mayor.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Limitación de responsabilidad</h3>
            <p>Incalake Tours actúa como operador y/o intermediario de servicios turísticos y no se responsabiliza por daños derivados de causas ajenas a su control. Recomendamos contar con un seguro de viaje.</p>
          </div>
        </div>
      </section>

      <!-- Privacidad -->
      <section id="privacidad" class="scroll-mt-24 mb-12">
        <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mb-4">2. Política de Privacidad</h2>
        <div class="space-y-5 text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Datos que recopilamos</h3>
            <p>Recopilamos los datos que nos proporcionas al reservar (nombre, correo, teléfono, país y detalles de la reserva) y datos técnicos básicos de navegación para mejorar el servicio.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Uso de la información</h3>
            <p>Usamos tus datos para gestionar y confirmar tus reservas, comunicarnos contigo sobre tu tour y, si lo autorizas, enviarte información comercial. No vendemos tus datos a terceros.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Cookies</h3>
            <p>Utilizamos cookies propias y de terceros para recordar preferencias (como idioma y moneda) y analizar el uso del sitio. Puedes gestionarlas desde la configuración de tu navegador.</p>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Tus derechos</h3>
            <p>Puedes solicitar el acceso, la rectificación o la eliminación de tus datos personales escribiéndonos a <a :href="`mailto:${contactEmail}`" class="text-primary font-semibold hover:underline">{{ contactEmail }}</a>.</p>
          </div>
        </div>
      </section>

      <!-- Contacto -->
      <section id="contacto" class="scroll-mt-24">
        <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mb-4">3. Contacto</h2>
        <p class="text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">
          Para cualquier consulta sobre estos términos o tu privacidad, escríbenos a
          <a :href="`mailto:${contactEmail}`" class="text-primary font-semibold hover:underline">{{ contactEmail }}</a>.
        </p>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
const contactEmail = 'reservas@incalake.com'

const sections = [
  { id: 'terminos', title: 'Términos' },
  { id: 'privacidad', title: 'Privacidad' },
  { id: 'contacto', title: 'Contacto' },
]

// Static "last updated" so SSR and client hydration match (no live Date()).
const lastUpdated = '24 de mayo de 2026'

useHead({
  title: 'Términos y Privacidad | Incalake Tours',
  meta: [
    {
      name: 'description',
      content: 'Términos y condiciones de reserva y política de privacidad de Incalake Tours — tours en Puno y el Lago Titicaca.',
    },
    // Legal pages shouldn't compete for search rankings.
    { name: 'robots', content: 'noindex, follow' },
  ],
})
</script>
