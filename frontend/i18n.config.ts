// Vue I18n runtime options. Messages live in i18n/locales/{code}.ts and are
// loaded lazily by @nuxtjs/i18n (see nuxt.config.ts → i18n.lazy + langDir).
// Keep this file lean — adding back a messages: {} block would re-bundle
// every locale into the main JS and defeat the split.
export default defineI18nConfig(() => ({
  legacy: false,
  // pt/fr/de/it are only ~30% translated; missing keys fall back to English
  // (complete) instead of rendering the raw key (e.g. "loading_tours").
  fallbackLocale: 'en',
}))
