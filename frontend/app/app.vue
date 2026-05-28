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
      // Pinned axes (@24,400,0,0 = static instance) → the font is ~312 KB
      // instead of ~3.8 MB for the full variable font. That huge file was why
      // icons took seconds to appear. Trade-off: a fixed weight (font-bold on
      // icons won't thicken them — negligible visually).
      // display=block keeps the GLYPHS invisible (not the ligature text like
      // "search"/"favorite") until the font loads; paired with the
      // visibility-hidden CSS + ms-ready script for a flash-free, fast load.
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block'
    }
  ],
  script: [
    {
      // Reveal Material Symbols icons only once the font is ready, so users
      // never see the raw icon names flash on first load. Safety timeout shows
      // them after 2.5s even if font detection fails.
      key: 'ms-ready',
      tagPosition: 'head',
      innerHTML: "(function(){var h=document.documentElement,r=function(){h.classList.add('ms-ready')};try{if(document.fonts&&document.fonts.load){document.fonts.load(\"24px 'Material Symbols Outlined'\").then(r).catch(r);setTimeout(r,2500)}else{r()}}catch(e){r()}})()",
    },
  ],
})
</script>

<style>
/* Hasta que la fuente de íconos esté lista (clase ms-ready, puesta por el
   script del <head>), ocultar los glyphs — NO mostrar el texto del ligature
   ("search", "favorite", "location_on"...). visibility:hidden reserva el
   espacio, así no hay saltos de layout cuando aparecen los íconos. */
html:not(.ms-ready) .material-symbols-outlined {
  visibility: hidden;
}

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
