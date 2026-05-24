<template>
  <div>
    <CommonNavbar />
    <NuxtPage />
    <CommonFooter />
  </div>
</template>

<script setup lang="ts">
// i18n SEO: dynamic <html lang>/dir, locale-aware canonical and hreflang
// alternate links for every page. Tour detail pages refine the alternates
// with their per-locale slugs via useSetI18nParams (the slugs differ per
// language, so a plain path-swap would be wrong).
const i18nHead = useLocaleHead()
useHead({
  htmlAttrs: {
    lang: () => i18nHead.value.htmlAttrs?.lang,
    dir: () => i18nHead.value.htmlAttrs?.dir,
  },
  link: () => i18nHead.value.link || [],
  meta: () => i18nHead.value.meta || [],
})

// Default site meta + fonts (titleTemplate '%s - Incalake Tours' lives in nuxt.config)
useHead({
  title: 'Tours en Puno y Lago Titicaca',
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
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap'
    },
    {
      // display=swap (was 'block', which blocked first paint on every page)
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap'
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
