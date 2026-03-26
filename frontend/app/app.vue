<template>
  <div>
    <CommonNavbar />
    <NuxtPage />
    <CommonFooter />
  </div>
</template>

<script setup lang="ts">
// Configuración global de SEO y fuentes
useHead({
  title: 'Voyager | Discover Your Next Adventure',
  meta: [
    {
      name: 'description',
      content: 'Descubre los mejores tours en Puno y el Lago Titicaca. Visita las Islas Flotantes de los Uros, Taquile, Amantaní y más destinos increíbles.'
    },
    {
      name: 'keywords',
      content: 'tours puno, lago titicaca, islas uros, taquile, amantani, tours peru'
    }
  ],
  link: [
    {
      rel: 'preconnect',
      href: 'https://fonts.googleapis.com'
    },
    {
      rel: 'preconnect',
      href: 'https://fonts.gstatic.com',
      crossorigin: ''
    },
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap'
    },
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block'
    }
  ]
})
</script>

<style>
/* Ocultar el texto de los iconos hasta que la fuente esté cargada */
.material-symbols-outlined {
  font-family: 'Material Symbols Outlined';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
  font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
}

/* Ocultar placeholders de imágenes durante la carga */
img[src=""] {
  visibility: hidden;
}
</style>
