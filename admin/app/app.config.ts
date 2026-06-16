// Tokens del shell del admin (Nuxt UI v4).
// Primary 'sky' elegido por ser profesional, confiable y armonioso con el verde
// de estados success (referencia: Linear, GitHub, Stripe).
// Cambiado desde 'indigo' el 2026-05-10.
export default defineAppConfig({
  ui: {
    colors: {
      primary: 'sky',
      neutral: 'slate',
    },
    // UBadge: Nuxt UI v4's default xs size is text-[8px] — illegible at a
    // glance. Bump xs→10px and sm→11px globally so every badge in the admin
    // (status chips, codes, "standard", language tags, etc.) is readable
    // without editing 25+ call sites. Padding nudged up to match.
    badge: {
      variants: {
        size: {
          xs: { base: 'text-[10px]/4 px-1.5 py-0.5 gap-1 rounded-sm' },
          sm: { base: 'text-[11px]/4 px-2 py-0.5 gap-1 rounded-sm' },
        },
      },
    },
  },
})
