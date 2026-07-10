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

## 🟠 FASE 2 — Simetría de tokens (pedido original)
- [ ] 2.1 Definir en `frontend main.css`: `.btn-primary/.btn-secondary` reales (rounded-xl, min-h-[52px], font-extrabold, hover bg-primary-dark), `.section-title` (text-2xl md:text-3xl font-black tracking-tight), `.micro-label` (text-[11px]), `.input-base` — y limpiar el `.btn` indigo muerto que ya existe
- [ ] 2.2 Aplicar botón unificado: home (hero, ver-todos), listado (FAB, retry, load-more), detalle (sticky, BookingCard), cart (checkout CTA), CheckoutForm, Culqi, confirmación, footer subscribe
- [ ] 2.3 Aplicar `.section-title` en headings de home/detalle/listado (elegir rampa única)
- [ ] 2.4 Unificar micro-labels (rampa: solo text-[11px] y text-xs; eliminar 8/9/10px sueltos donde sea visible móvil)
- [ ] 2.5 Labels de formulario: una convención (text-xs font-bold uppercase tracking-wider text-slate-600) en CheckoutForm/TravelersForm/Inquiry/cart-edit
- [ ] 2.6 Inputs: receta única (px-4 py-3 bg-slate-50 border-slate-200 rounded-lg focus:ring-2 ring-primary/30 + focus:border-primary; ≥16px en móvil)
- [ ] 2.7 Espaciado: py-8 md:py-12 secciones home; gutters px-4 sm:px-6 lg:px-8 en las 3 páginas; pt del navbar consistente
- [ ] 2.8 Home: h1 semántico (hero → h1, logo navbar → div/span)

## 🟠 FASE 3 — Componentes compartidos
- [ ] 3.1 `TourCard.vue` (variants: grid|list|compact|related) + helpers a composable `useTourCard` → reemplazar 7 inline (home×2, listado×3, related, saved)
- [ ] 3.2 `OfferBadge.vue` → 9 sitios
- [ ] 3.3 `WishlistHeartButton.vue` (encapsula toggle + flyTo) → 10 sitios
- [ ] 3.4 Adoptar `TourQuantityStepper` en cart edit + AvailabilityInquiryModal
- [ ] 3.5 `CartTotals.vue` + `TaxBreakdownPopover.vue` (cart + CheckoutSummary comparten verbatim)
- [ ] 3.6 `SectionHeader.vue` + `useSnapScroll()` (4 flechas idénticas home, 2 funciones gemelas)
- [ ] 3.7 Adoptar `common/EmptyState.vue` en 7 empty states inline; unificar spinner (LoadingSpinner)
- [ ] 3.8 `StarRating.vue` (~8 loops, unificar color a text-rating)
- [ ] 3.9 Precio consistente: siempre CON símbolo (`formatConverted(x)`) en tarjetas

## 🟡 FASE 4 — i18n flujo de pago
- [ ] 4.1 paypal.vue completo a t() (el peor: página entera hardcoded EN/ES)
- [ ] 4.2 culqi.vue + CulqiCheckoutFixed (botón "Pay/Processing")
- [ ] 4.3 booking-confirmation (mezclas ES/EN), TravelersForm, cart (política estándar está EN en un modal y ES en otro)
- [ ] 4.4 Home: "Our Offers"/"Special Deals" → t(); detalle: badges/trust/CTA a t()
- [ ] 4.5 confirm() nativos → modal propio (cart.vue:180, bookingCode:729)

## 🟡 FASE 5 — Admin
- [ ] 5.1 Barrido micro-label ad-hoc (49 usos, 15 archivos) → `admin-label` (incl. layout sidebar, BookingDetailsModal, Step1/6, th bookings, Step8 9px)
- [ ] 5.2 `alert()/confirm()` → useToast/useConfirm: tourWizard.ts:882,918,1181,1183; Step6:1131; Step3:505; layout logout:155
- [ ] 5.3 `StatCard.vue` compartida (5 grids hardcodeadas) 
- [ ] 5.4 Step8Availability + Step8FinalReview → WizardSection
- [ ] 5.5 ModalShell común (botón cerrar md rounded-full, footer bg-elevated/30) en 5 form-modals + clonar
- [ ] 5.6 Redirects `/admin/* → /admin/v2/*` + borrar WizardHeader.vue (muerto) y páginas viejas
- [ ] 5.7 Labels EN → ES (nav "Reviews", "Home Page")
- [ ] 5.8 UTable+UPagination en tours/reviews/categories/users/languages (patrón bookings) — GRANDE, opcional por página

## 🟢 FASE 6 — Limpieza backend
- [ ] 6.1 Borrar subsistema eventos muerto (EventServiceProvider wiring + ProcessNewTour/UpdateTourCache/SendBookingNotification + 4 jobs nunca despachados)
- [ ] 6.2 Borrar TourController muertos: cloneManual/cloneWithAI/translateWithAI/generateTourCodeForLanguage (1015-1305, refs a relaciones inexistentes)
- [ ] 6.3 Quitar `UPDATE_TOUR_DEBUG` (TourService:80) + verbosidad TourTranslationService:84, CacheService, logs Culqi con request completo
- [ ] 6.4 Borrar .bak/.old/.txt + scripts raíz (check_tours.php, test_*.php, fix_save.php, confirmCulqiPayment_updated.php, tour4.json) + public/test-*.html, calendar-test.php, seed-tour-options.php (ya usado)
- [ ] 6.5 Retiro Livewire admin (app/Livewire, resources/views/admin+livewire, rutas web, composer) — tras confirmar nada lo usa
- [ ] 6.6 Contrato de respuesta: render global en bootstrap/app.php (422/404/500 uniformes {success,message,errors}), BookingController::index paginador crudo, claves booking→data
- [ ] 6.7 Caché: bump en Category writes (nombres viejos hasta 24h en detalle); cachear /pages/{page} y /reviews; borrar métodos CacheService muertos (tags() rompe en file driver)
- [ ] 6.8 Mailables → ShouldQueue + resolver queue prod (worker cron o sync consciente)
- [ ] 6.9 Config: reservas@incalake.com y URL admin Vercel hardcodeados → config/env (7 sitios)
- [ ] 6.10 Duplicación: trait ResolvesLanguage, reglas Tour compartidas Store/Update, servicio común Culqi/PayPal
- [ ] 6.11 Migraciones no reproducibles (gallery_layout ×4, bookings create ×2, cities slug ×2) — consolidar (solo si se necesita entorno fresco)

## 🟢 FASE 7 — Responsive fino
- [ ] 7.1 Touch targets ≥44px: corazón mobile 36px, share/save galería 36px, × chips 20px, steppers cart 32px, iconos editar/eliminar cart 28px, admin row actions xs, WizardStepper móvil
- [ ] 7.2 Inputs ≥16px móvil (TravelersForm, Inquiry) — evitar zoom iOS
- [ ] 7.3 Offsets sticky vs navbar de altura variable (56/68px hardcodeados en 3 sitios)
- [ ] 7.4 Grid del Inquiry modal apretado en móvil (fecha/adultos/niños en cuartos)

---

## Estado de sesión
- Servidores locales: admin :3000, frontend :3001 (levantar con `npm run dev` en cada uno)
- Trabajo previo YA en prod: menú editable, google reviews slider, availability inquiry, datos requeridos fix, SEO editable, tooltips dificultad, cart 3 pasos, calendario/horario válidos en cart edit, auto-recarga deploy + SWR cortos, nota pago parcial (local, sin push aún — VERIFICAR si quedó pusheado antes de tocar TourBookingCard)
- Pendiente local sin push al escribir esto: nota de pago parcial en TourBookingCard + acordeones detalle + barra sticky secciones (VERIFICAR con git status)
