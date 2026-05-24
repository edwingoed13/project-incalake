<template>
  <div>
    <CommonNavbar />
    <NuxtPage />
    <CommonFooter />
  </div>
</template>

<script setup lang="ts">
// Dynamic <html lang> per locale (es-PE, en-US, …) — replaces the hardcoded
// lang:'es' that used to live in nuxt.config for all 6 locales. The canonical
// is emitted automatically by @nuxtjs/seo from site.url + path (verified
// absolute). hreflang alternates are a follow-up: tour slugs are localized, so
// a naive path-swap would point to the wrong URLs — needs per-locale slugs.
const { locale, locales } = useI18n()
const htmlLang = computed(() => {
  const match = (locales.value as any[]).find(l => l.code === locale.value)
  return (match?.iso || match?.language || locale.value) as string
})
useHead({ htmlAttrs: { lang: htmlLang } })

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
