<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="container mx-auto px-4 max-w-4xl">
      <h1 class="text-4xl font-bold text-center mb-8">Sobre Nosotros</h1>

      <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
        <p class="text-lg text-gray-700 mb-4">
          En <strong>Incalake Tours</strong>, nos especializamos en ofrecer experiencias únicas y memorables en el lago navegable más alto del mundo: el Lago Titicaca.
        </p>
        <p class="text-gray-700 mb-4">
          Con más de 10 años de experiencia, nuestro equipo de guías locales expertos te llevará a descubrir la rica cultura andina, las islas flotantes de los Uros, Taquile, Amantaní y muchos más destinos fascinantes en Puno, Perú.
        </p>
        <p class="text-gray-700">
          Nos comprometemos a brindar servicios de alta calidad, seguridad y respeto por el medio ambiente y las comunidades locales.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
          <div class="text-4xl mb-4">🏔️</div>
          <h3 class="text-xl font-bold mb-2">Experiencia Local</h3>
          <p class="text-gray-600">Guías nativos con profundo conocimiento de la cultura andina</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
          <div class="text-4xl mb-4">⭐</div>
          <h3 class="text-xl font-bold mb-2">Alta Calidad</h3>
          <p class="text-gray-600">Servicios premium con los mejores estándares</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
          <div class="text-4xl mb-4">🌍</div>
          <h3 class="text-xl font-bold mb-2">Turismo Responsable</h3>
          <p class="text-gray-600">Respeto por el medio ambiente y las comunidades</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'Sobre Nosotros | Incalake Tours',
  meta: [
    {
      name: 'description',
      content: 'Conoce a Incalake Tours, tu operador turístico de confianza en Puno y el Lago Titicaca. Más de 10 años de experiencia ofreciendo tours inolvidables.'
    }
  ]
})
</script>
