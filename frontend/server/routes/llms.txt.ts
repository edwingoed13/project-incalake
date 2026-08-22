// llms.txt — a plain-text index for AI crawlers (ChatGPT, Perplexity, Claude).
// The convention asks for a short description of what the site is plus links to
// the pages worth reading, so an assistant does not have to infer the catalogue
// by crawling a JavaScript app. Generated from the live tour list rather than
// hand-maintained: a stale hand-written one is worse than none.
const clean = (raw: unknown): string =>
  String(raw ?? '').trim().replace(/\s+/g, '').replace(/\/+$/, '')

export default defineEventHandler(async (event) => {
  const { public: { apiBase, siteUrl } } = useRuntimeConfig()
  const base = clean(apiBase)
  const site = clean(siteUrl) || 'https://incalake.com'

  let tours: any[] = []
  try {
    const res: any = await $fetch(`${base}/tours`, { params: { per_page: 60, active: 1 } })
    tours = res?.data?.data ?? res?.data ?? []
  } catch (e: any) {
    console.error('[llms.txt] could not read tours:', e?.message || e)
  }

  const lines = [
    '# Incalake Tours',
    '',
    '> Agencia local en Puno, Perú, especializada en el Lago Titicaca: Islas',
    '> Flotantes de los Uros, Taquile, Amantaní y Sillustani, además de rutas a',
    '> Cusco, Arequipa y el Salar de Uyuni. Guías nativos, salidas diarias,',
    '> reserva online con confirmación inmediata.',
    '',
    '## Tours',
    '',
  ]

  for (const t of tours) {
    if (!t?.slug) continue
    const city = t.city?.slug || 'puno'
    const price = t.min_price ? ` — desde ${t.min_price} ${t.currency || 'USD'}` : ''
    const desc = String(t.short_description || '').replace(/\s+/g, ' ').trim().slice(0, 160)
    lines.push(`- [${t.title}](${site}/es/${city}/${t.slug})${price}${desc ? `: ${desc}` : ''}`)
  }

  lines.push(
    '',
    '## Páginas',
    '',
    `- [Todos los tours](${site}/es/tours)`,
    `- [Acerca de Incalake](${site}/es/about)`,
    `- [Contacto](${site}/es/contact)`,
    '',
    '## Contacto',
    '',
    '- Email: reservas@incalake.com',
    '- WhatsApp: +51 982 769 453',
    '- Ubicación: Puno, Perú',
    '',
  )

  setHeader(event, 'content-type', 'text/plain; charset=utf-8')
  // An hour: long enough to cost nothing, short enough that a new tour shows up
  // the same day.
  setHeader(event, 'cache-control', 'public, max-age=3600')
  return lines.join(String.fromCharCode(10))
})
