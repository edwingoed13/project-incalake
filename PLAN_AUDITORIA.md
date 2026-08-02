# Plan de mejoras — Auditoría integral (frontend + admin + backend)

> Archivo de seguimiento vivo. Marcar `[x]` al completar cada ítem.
> Generado de 6 auditorías paralelas (públicas, flujo compra, admin, reuso, seguridad backend, calidad backend).
> Regla de trabajo: implementar por fase → build-verify → revisar en localhost → commit/push con OK del usuario.

## Decisiones tomadas (usuario)
1. **Pago parcial**: ambos métodos como Culqi → default **"pagar todo"** + badge Recomendado (PayPal hoy defaultea 'advance' → cambiar).
2. **Página legacy `frontend/app/pages/tours/[slug].vue`** (985 líneas dup): **eliminar** (verificar único enlace: `saved.vue:218` fallback) + redirect si aplica.
3. **Registro/usuarios**: la creación de usuarios es funcionalidad del panel admin (UserController ya escribe `role` admin/staff/customer). `/auth/register` público queda inofensivo una vez el middleware admin bloquee a `customer`.

## ⚠️ Notas críticas para no romper prod
- `AdminMiddleware` (web) hace `redirect()->route('login')` → **NO usar en API**. Crear middleware API JSON (`admin.api`) que use `User::canAccessAdminPanel()` (columna `role IN ('admin','staff')`).
- **Riesgo de lockout**: `admin@incalake.com` fue creado por seeder SIN columna role (default `customer`) + spatie 'Super Admin' (que el middleware no mira). → Incluir **migración de promoción** idempotente: `role='admin'` para emails admin conocidos / usuarios con spatie Super Admin. Correr `migrate.php?key=` ANTES de que el middleware entre en efecto (mismo deploy está bien: purge-cache al final).
- Rutas de confirmación (`/bookings/{id}/travelers|full-details|save-*|pickup-details|notify-completed|validate-hotel`) las usa el **flujo público** del cliente → NO ponerlas tras auth. Gate por **token/email de la reserva** (query param) y actualizar el frontend confirmación para enviarlos.
- El admin frontend llama **sin Authorization header** a: Step6 (`children`, `eligible-children`, `eligible-parents`, `set-parent`), posiblemente dashboard y ai-translation-settings → al protegerlas, **añadir headers** en esos fetch.
- Deploy backend: GitHub Actions FTP → luego `migrate.php?key=` (si hay migración) → `purge-cache.php?key=` SIEMPRE (rutas/config cacheadas).

---

## 🚨 FASE 0 — Seguridad backend (URGENTE, API viva)
- [x] 0.1 Middleware `EnsureAdminApi` (alias `admin.api`) creado + registrado en bootstrap/app.php
- [x] 0.2 Migración `2026_07_06_000100_promote_admin_users_role_column` (admin@incalake.com + spatie Super Admin → role='admin')
- [x] 0.3 Grupo sanctum: todo admin/* + bookings index envuelto en `admin.api`
- [x] 0.4 Rutas sueltas movidas/protegidas: wizard utils dentro de admin/tours; clone/clone-ai/delete-translation con auth+admin (mismos paths); ai-translation-settings/test y dashboard/* gated; cancel/confirm de bookings → admin (solo el panel los usa, ya mandaba token)
- [x] 0.5 Admin: Authorization añadido en v2/index (dashboard×3), ai-translation (×3), tours/index (clone + delete-translation), Step1 (generate-code), Step6 (×5)
- [x] 0.6 Gate token/email en los 7 métodos de BookingConfirmationController (authorizeBookingAccess: token propio / token del primario del grupo / email) + frontend: useBookingAccess + accessQs en 10 llamadas ([bookingCode].vue ×7, PickupConfiguration, useHotelPickupValidation ×2)
- [x] 0.7 Precio autoritativo server-side en create (tiers por age_stage — lowest=adulto, oferta por fecha con fórmula del storefront, tax); total_amount del cliente solo se loggea si difiere
- [x] 0.8 debug_data y log de $request->all() eliminados del 422 de create
- [x] 0.9 Throttle availability-inquiry 5/min + grupo confirmación 30/min
- [x] 0.10 CORS: patrón restringido a incalake-frontend*/incalake-admin*.vercel.app
- [x] 0.11 PHP lint OK + builds frontend/admin OK (falta prueba manual en localhost)
- [x] 0.12 Deploy hecho (migrate + purge OK). Smoke: (a)✅ admin navega y Step6 carga/busca, (b)✅ dashboard/stats bloqueado (redirige a login del web guard en navegador; 401 JSON vía SPA — normalizar en F6), (c)✅ confirmación carga con ?email= (el token de la reserva vieja estaba EXPIRADO por el TTL de 7 días preexistente — no relacionado al deploy), (d)✅ full-details sin token → 403, (e)✅ reserva de prueba en prod: total correcto → **FASE 0 COMPLETA**
- [x] 0.12b fix(admin): dropdown de variantes del Step 6 se abre hacia arriba (quedaba tras la barra Anterior/Siguiente) — 3e5fc3d
- [ ] 0.14 (F1 candidata) Extender TTL del confirmation_token (7 días es corto si reservan con semanas de anticipación) o auto-renovar al pagar
- [ ] 0.13 (Usuario) Verificar `QUEUE_CONNECTION` en .env prod: si `database` → falta worker/cron; si `sync` → emails bloquean checkout
> Nota deploy: correr migrate.php ANTES de probar login admin (la promoción de rol). Si el admin quedara bloqueado igual: revisar en BD `users.role` del usuario que usan.

## 🔴 FASE 1 — Bugs frontend de pago — CÓDIGO COMPLETO (pendiente revisión + push)
- [x] 1.1 PayPal: formatConverted en todos los montos + desglose subtotal/tasas + aviso USD en moneda extranjera (espejo de Culqi)
- [x] 1.2 PayPal: "Pagar todo" primero + badge Recomendado (default ya era 'full')
- [x] 1.3 Checkout: `:disabled="!acceptedTerms"` real en el CTA
- [x] 1.4 Cart: sanitizeHtml en los 4 v-html de políticas
- [x] 1.5 localePath en los 7 redirects (checkout ×3, culqi ×2, paypal ×2)
- [x] 1.6 Borrados: legacy tours/[slug].vue (985 líneas), CulqiCheckout.vue y CulqiCheckoutSimple.vue (muertos); fallback de saved.vue → /puno/{slug}
- [x] 1.7 TTL confirmation_token 7d→30d + renovación al markAsPaid (backend)

## 🟠 FASE 2 — Simetría de tokens — CÓDIGO COMPLETO (pendiente revisión + push)
- [x] 2.1 Tokens en main.css: `.btn-primary` (+`.btn-lg`), `.btn-outline-primary`, `.section-title`, `.section-label`, `.form-label`, `.input-base`; bloque indigo muerto eliminado (0 usos verificado)
- [x] 2.2 Botón unificado en 15 CTAs: TourBookingCard×3, detalle sticky, inquiry submit, Culqi pay, CheckoutForm submit, home hero search, listado retry/clear/load-more, footer subscribe, cart checkout, confirmación×3. Los disabled ahora los maneja el token. (FAB de filtros queda redondo a propósito)
- [x] 2.3 `.section-title` en 7 headings de home + 4 del detalle; `.section-label` en 3 labels
- [x] 2.5 `.form-label` en CheckoutForm×5, Inquiry×7, TravelersForm×3
- [x] 2.6 `.input-base` en Inquiry×7 (CheckoutForm ya ES la receta base y tiene bindings de error-border — no tocado; TravelersForm inputs quedan para F7 por el binding de error)
- [ ] 2.4 Micro-labels 8/9/10px restantes → se resuelven en F3 con TourCard (los "Desde/from" viven en las tarjetas)
- [ ] 2.7 Espaciado/gutters — diferido (bajo impacto, alto churn)
- [x] 2.8 h1 semántico: hero home h2→h1, logo navbar h1→span, logo footer h2→p

## 🟠 FASE 3 — Componentes compartidos — BATCH A ✅ PUSHEADO (5014664 + d612202)
- [x] 3.2 `tour/OfferBadge.vue` (solid|tint, xs|sm, color custom) → aplicado en home offers, listado ×3, saved (los tinted de cart/checkout quedan para 3.5)
- [x] 3.3 `tour/WishlistHeartButton.vue` (payload + toggle + flyTo encapsulados) → listado ×3, galería del detalle (overlay dark), related tours; funciones toggleWishlist/toggleSaveRelated inline eliminadas (el botón labeled "Guardar" del header del detalle conserva toggleSave)
- [x] 3.4 `TourQuantityStepper` adoptado en cart edit (32px→44px targets)
- [x] 3.9 Precio SIEMPRE con símbolo en tarjetas del listado (6 formatConverted(x,false) → (x))
- [x] Extra: `useBookingWindow` — ventana de anticipación fecha+hora (tz del tour) compartida detalle+cart; backstop server-side en BookingController (9095be2)
- [x] Extra: home muestra últimos 15 reviews publicados (min 4★) en vez de featured; edades incoherentes ocultas en widget
- [x] 3.1 `tour/TourCard.vue` → home destacados + ofertas (accent green, badge, dificultad); las variantes del listado (grid/list/mobile con precio tachado/duración/can-hover), saved y related se dejan inline A PROPÓSITO — layouts genuinamente distintos, unificarlos sería prop-explosion
- [x] 3.5 `checkout/OrderTotals.vue` → cart sidebar + culqi + paypal (subtotal, fees con popover + desglose por ítem + % uniforme, total, saldo advance, aviso USD)
- [x] 3.6 `common/SectionHeader.vue` + `useSnapScroll()` → testimonios + Google slider
- [x] 3.7 Spinner unificado vía token `.spinner` (9 sitios); `EmptyState.vue` y `LoadingSpinner.vue` eran código muerto (0 usos) → eliminados; botón de cart vacío → btn-primary
- [x] 3.8 `common/StarRating.vue` → 4 filas de estrellas de la home (2 amarillos distintos unificados)

## 🟡 FASE 4 — i18n flujo de pago
- [x] 4.1 paypal.vue a t() — header/detalles/error/modo-adelanto (1bb441f); queda solo el meta title SEO
- [x] 4.2 culqi.vue + CulqiCheckoutFixed — botón Pay/Processing/Loading, adults/children, modo adelanto (1bb441f)
- [x] 4.3 TravelersForm + 15 labels de campos de viajero en 6 idiomas + validación líder (3731b71); confirmación y políticas del cart completados en batch 3
- [x] 4.4 Home offers/search (1bb441f) + detalle badges/trust/CTA/share (batch 3)
- [x] 4.5 useConfirmDialog + CommonConfirmDialog global — cart bulk-delete y copiar-viajeros (3731b71)

## 🟡 FASE 5 — Admin
- [ ] 5.1 Barrido micro-label ad-hoc (49 usos, 15 archivos) → `admin-label` (incl. layout sidebar, BookingDetailsModal, Step1/6, th bookings, Step8 9px)
- [x] 5.2 alert/confirm → toast/dialog: tourWizard ×4, Step6 attach-variant, Step3 delete-section, logout v2 (2917d34); el confirm del layout admin VIEJO se deja (regla: no tocar superficie vieja)
- [x] 5.3 V2StatCard → bookings + reviews (19763e8); el stat del dashboard queda inline a propósito (skeleton/trend/revenue) 
- [x] 5.4 DESCARTADO con razón: las cards de Step8 son especializadas (calendario sticky, col-spans, p-0) — WizardSection las forzaría
- [x] 5.5 V2FormModalShell en los 4 form-modals + clonar (subtitle slot, scrollable, busy)
- [x] 5.6 Admin viejo retirado (11 páginas, layout, AdminTopbar, WizardHeader; −4,253 líneas) + catch-all 301 /admin/** → /admin/v2/** — OK del cliente recibido — f21faf1
- [x] 5.7 Nav "Reviews"→"Reseñas", "Home Page"→"Página de inicio" (2917d34)
- [x] 5.8 UPagination unificado (reviews tenía pager artesanal; tours tenía computed muerto); row-lists se quedan a propósito (mejor fit que UTable) y catálogos chicos sin paginar no la necesitan

## 🟢 FASE 6 — Limpieza backend
- [x] 6.1 Subsistema eventos borrado: 4 events + 3 listeners + 4 jobs + EventServiceProvider (nunca registrado en bootstrap/providers.php) — 28cc1df
- [x] 6.2 TourController: cloneManual/cloneWithAI + helpers AI privados borrados (−294 líneas; rutas usan TourCloneController) — 28cc1df
- [x] 6.3 UPDATE_TOUR_DEBUG + log booking_texts fuera; respuesta Culqi loggea solo charge_id/outcome (el raw llevaba email+metadata de tarjeta al log) — 28cc1df
- [x] 6.4 Basura borrada: scripts raíz, .bak/.old, ejecutables de public/ (calendar-test, seed-tour-options, test-*.html) + ruta /test-tiptap — 28cc1df. OJO: borrar residuos en el docroot de prod a mano (FTP no borra)
- [x] 6.5 Livewire retirado (91 archivos, −12,614 líneas): app/Livewire, 10 Admin web controllers, vistas admin+livewire, config, grupo /admin web, alias+clase AdminMiddleware, dep composer. Usuario confirmó que nadie entra por api.incalake.com/admin — 51aa964
- [ ] 6.6 Contrato de respuesta: render global en bootstrap/app.php (422/404/500 uniformes {success,message,errors}), BookingController::index paginador crudo, claves booking→data
- [ ] 6.7 Caché: bump en Category writes (nombres viejos hasta 24h en detalle); cachear /pages/{page} y /reviews; borrar métodos CacheService muertos (tags() rompe en file driver)
- [ ] 6.8 Mailables → ShouldQueue + resolver queue prod (worker cron o sync consciente)
- [ ] 6.9 Config: reservas@incalake.com y URL admin Vercel hardcodeados → config/env (7 sitios)
- [ ] 6.10 Duplicación: trait ResolvesLanguage, reglas Tour compartidas Store/Update, servicio común Culqi/PayPal
- [ ] 6.11 Migraciones no reproducibles (gallery_layout ×4, bookings create ×2, cities slug ×2) — consolidar (solo si se necesita entorno fresco)

## 🟢 FASE 7 — Responsive fino
- [x] 7.1 Touch targets 44px móvil: heart (size-11 md:size-9/10), share galería, editar/eliminar cart. Chips/admin xs quedan (secundarios, riesgo layout)
- [x] 7.2 TravelersForm text-base sm:text-sm ×4 (input-base ya era 16px) + 3 strings ES → i18n
- [x] 7.3 Token .sticky-below-nav (56/68px) en los 3 sitios
- [x] 7.4 Inquiry móvil: fecha ancho completo, pax debajo

---

## Estado de sesión
- Servidores locales: admin :3000, frontend :3001 (levantar con `npm run dev` en cada uno)
- Trabajo previo YA en prod: menú editable, google reviews slider, availability inquiry, datos requeridos fix, SEO editable, tooltips dificultad, cart 3 pasos, calendario/horario válidos en cart edit, auto-recarga deploy + SWR cortos, nota pago parcial (local, sin push aún — VERIFICAR si quedó pusheado antes de tocar TourBookingCard)
- Pendiente local sin push al escribir esto: nota de pago parcial en TourBookingCard + acordeones detalle + barra sticky secciones (VERIFICAR con git status)
