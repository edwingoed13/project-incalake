<template>
  <div>
    <!-- Top progress bar on every route change so SPA navigation (e.g. clicking
         a tour from the home/listing) gives immediate feedback while data loads. -->
    <NuxtLoadingIndicator color="#4f46e5" :height="3" />
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
      href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap'
    }
    // Material Symbols icons are no longer loaded as a web font — they're inline
    // SVG via @nuxt/icon (see nuxt.config `icon`). This removed the 312 KB font
    // request and the icon-name flash entirely.
  ],
})
</script>

<style>
/* Ocultar placeholders de imágenes durante la carga */
img[src=""] {
  visibility: hidden;
}
</style>
