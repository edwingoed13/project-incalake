<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import ConfirmDialog from '~/components/v2/ConfirmDialog.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const open = ref(false)

// Nav link sizing: 44px tall touch target below lg (where the sidebar is a
// mobile/tablet drawer), compact on lg+ where it's a mouse-driven inline
// rail. Below lg the WCAG 44px minimum matters; above it, density wins.
const navUi = { link: 'min-h-11 lg:min-h-0 py-2.5 lg:py-1.5' }

const SOON = { color: 'neutral' as const, variant: 'subtle' as const }

// Carga permisos al iniciar (igual que el layout actual)
onMounted(async () => {
  if (!auth.token) return
  try {
    const config = useRuntimeConfig()
    const response = await fetch(`${config.public.apiUrl}/auth/permissions`, {
      headers: { 'Authorization': `Bearer ${auth.token}`, 'Accept': 'application/json' },
    })
    if (response.ok) {
      const data = await response.json()
      if (data.success && data.data) {
        auth.setPermissions(data.data.permissions, data.data.role)
      }
    }
  } catch (err) {
    console.error('Error loading permissions:', err)
  }
})

const links = computed(() => {
  // Solo destinos REALES en las secciones. Antes, 16 de los 26 ítems eran
  // enlaces muertos «Pronto» repartidos en 7 secciones: en 1080p empujaban
  // Usuarios/Configuración bajo el pliegue y en móvil el cajón era un túnel.
  const main = [
    { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/admin/v2' },
  ]

  const reservations = [
    { label: 'Clientes (Web/OTAs)', icon: 'i-lucide-users', to: '/admin/v2/bookings' },
    { label: 'Reseñas', icon: 'i-lucide-star', to: '/admin/v2/reviews' },
  ]

  const services: any[] = []
  if (auth.hasPermission?.('tours.view')) services.push({ label: 'Tours', icon: 'i-lucide-map-pin', to: '/admin/v2/tours' })
  if (auth.hasPermission?.('categories.view')) services.push({ label: 'Categorías', icon: 'i-lucide-tags', to: '/admin/v2/categories' })
  if (auth.hasPermission?.('languages.view')) services.push({ label: 'Idiomas', icon: 'i-lucide-languages', to: '/admin/v2/languages' })
  services.push({ label: 'Página de inicio', icon: 'i-lucide-house', to: '/admin/v2/pages/home' })
  services.push({ label: 'Menú', icon: 'i-lucide-menu', to: '/admin/v2/pages/menu' })

  const settings: any[] = []
  if (auth.hasPermission?.('users.view')) settings.push({ label: 'Usuarios y Roles', icon: 'i-lucide-shield-user', to: '/admin/v2/users' })
  if (auth.hasPermission?.('settings.ai')) settings.push({ label: 'Traducción IA', icon: 'i-lucide-sparkles', to: '/admin/v2/settings/ai-translation', badge: { label: 'Nuevo', color: 'primary' as const, variant: 'subtle' as const } })

  // Todo lo futuro, plegado en un único grupo al fondo. Sigue visible (el
  // roadmap comunica), pero ya no cuesta una pantalla de scroll.
  const soon = [{
    label: 'Próximamente',
    icon: 'i-lucide-hourglass',
    defaultOpen: false,
    children: [
      { label: 'Chatbot', icon: 'i-lucide-bot', to: '/admin/chatbot' },
      { label: 'Productos', icon: 'i-lucide-shopping-bag', to: '/admin/products' },
      { label: 'Paquetes', icon: 'i-lucide-package', to: '/admin/packages' },
      { label: 'Pagos', icon: 'i-lucide-credit-card', to: '/admin/payments' },
      { label: 'Reservas Rápidas (Culqi)', icon: 'i-lucide-zap', to: '/admin/quick-bookings' },
      { label: 'Facturación', icon: 'i-lucide-receipt-text', to: '/admin/invoicing' },
      { label: 'Calendario', icon: 'i-lucide-calendar-days', to: '/admin/calendar' },
      { label: 'Disponibilidad', icon: 'i-lucide-calendar-check', to: '/admin/availability' },
      { label: 'Proveedores', icon: 'i-lucide-store', to: '/admin/suppliers' },
      { label: 'Guías y Recursos', icon: 'i-lucide-id-card', to: '/admin/guides' },
      { label: 'Analytics', icon: 'i-lucide-chart-line', to: '/admin/analytics' },
      { label: 'Preguntas Web', icon: 'i-lucide-circle-help', to: '/admin/web-questions' },
      { label: 'Galería Web', icon: 'i-lucide-image', to: '/admin/web-gallery' },
      { label: 'Reportes', icon: 'i-lucide-file-text', to: '/admin/reports' },
      { label: 'Cupones', icon: 'i-lucide-ticket-percent', to: '/admin/coupons' },
      { label: 'Buses', icon: 'i-lucide-bus', to: '/admin/buses' },
      { label: 'Aeropuerto', icon: 'i-lucide-plane', to: '/admin/airport' },
      { label: 'Traslados', icon: 'i-lucide-car', to: '/admin/transfers' },
      { label: 'OTA Manager', icon: 'i-lucide-globe', to: '/admin/ota-manager' },
    ],
  }]

  return { main, reservations, services, settings, soon }
})

const searchGroups = computed(() => [
  {
    id: 'principal',
    label: 'Principal',
    items: [
      { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/admin/v2' },
      { label: 'Tours', icon: 'i-lucide-map-pin', to: '/admin/v2/tours' },
      { label: 'Reservas', icon: 'i-lucide-calendar-check', to: '/admin/v2/bookings' },
      { label: 'Reseñas', icon: 'i-lucide-star', to: '/admin/v2/reviews' },
    ],
  },
  {
    id: 'config',
    label: 'Catálogos',
    items: [
      { label: 'Categorías', icon: 'i-lucide-tags', to: '/admin/v2/categories' },
      { label: 'Idiomas', icon: 'i-lucide-languages', to: '/admin/v2/languages' },
      { label: 'Cupones', icon: 'i-lucide-ticket-percent', to: '/admin/coupons', badge: { label: 'Pronto', ...SOON } },
    ],
  },
  {
    id: 'system',
    label: 'Sistema',
    items: [
      { label: 'Usuarios', icon: 'i-lucide-shield-user', to: '/admin/v2/users' },
      { label: 'Página de inicio', icon: 'i-lucide-house', to: '/admin/v2/pages/home' },
      { label: 'Menú', icon: 'i-lucide-menu', to: '/admin/v2/pages/menu' },
    ],
  },
])

const colorMode = useColorMode()

// 3-state theme: 'system' (follows OS), 'light', 'dark'.
// colorMode.preference is what we save; colorMode.value is the resolved current state.
type ThemePref = 'system' | 'light' | 'dark'
const themePref = computed<ThemePref>({
  get: () => (colorMode.preference as ThemePref) || 'system',
  set: (v) => { colorMode.preference = v },
})

const themeLabel = computed(() => {
  if (themePref.value === 'light') return 'Claro'
  if (themePref.value === 'dark') return 'Oscuro'
  return 'Sistema'
})
const themeIcon = computed(() => {
  if (themePref.value === 'light') return 'i-lucide-sun'
  if (themePref.value === 'dark') return 'i-lucide-moon'
  return 'i-lucide-monitor'
})

const logout = async () => {
  const ok = await useConfirm().confirm({
    title: '¿Cerrar sesión?',
    confirmLabel: 'Cerrar sesión',
    icon: 'i-lucide-log-out',
  })
  if (!ok) return
  await auth.logout()
  await router.push('/login')
}

const userMenuItems = computed(() => [
  [
    {
      label: auth.user?.name || 'Administrador',
      type: 'label' as const,
      // Sin dicebear: iniciales locales via alt — nada de red externa ni de
      // filtrar el nombre del usuario a un tercero.
      avatar: { src: auth.user?.avatar || undefined, alt: auth.user?.name || 'Admin' },
    },
  ],
  [
    { label: 'Configuración', icon: 'i-lucide-settings', to: '/admin/v2/settings/ai-translation' },
    {
      label: `Tema: ${themeLabel.value}`,
      icon: themeIcon.value,
      children: [
        {
          label: 'Sistema',
          icon: 'i-lucide-monitor',
          kbds: themePref.value === 'system' ? ['✓'] : undefined,
          onSelect: () => { themePref.value = 'system' },
        },
        {
          label: 'Claro',
          icon: 'i-lucide-sun',
          kbds: themePref.value === 'light' ? ['✓'] : undefined,
          onSelect: () => { themePref.value = 'light' },
        },
        {
          label: 'Oscuro',
          icon: 'i-lucide-moon',
          kbds: themePref.value === 'dark' ? ['✓'] : undefined,
          onSelect: () => { themePref.value = 'dark' },
        },
      ],
    },
  ],
  [
    { label: 'Cerrar sesión', icon: 'i-lucide-log-out', color: 'error' as const, onSelect: () => logout() },
  ],
])
</script>

<template>
  <UDashboardGroup>
    <UDashboardSidebar
      id="admin-v2"
      v-model:open="open"
      collapsible
      resizable
      class="bg-elevated/25"
    >
      <template #header="{ collapsed }">
        <div class="flex items-center gap-2.5">
          <div class="size-9 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-sm">
            <UIcon name="i-lucide-compass" class="size-5" />
          </div>
          <div v-if="!collapsed" class="flex flex-col leading-tight">
            <span class="text-sm font-bold">Incalake</span>
            <span class="text-[10px] text-muted uppercase tracking-widest">CMS</span>
          </div>
        </div>
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <UNavigationMenu :collapsed="collapsed" :items="links.main" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 admin-label">Reservas</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.reservations" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed && links.services.length" class="px-3 pt-3 admin-label">Servicios</div>
        <UNavigationMenu v-if="links.services.length" :collapsed="collapsed" :items="links.services" orientation="vertical" :ui="navUi" />

        <template v-if="links.settings.length">
          <div v-if="!collapsed" class="px-3 pt-3 admin-label">Configuración</div>
          <UNavigationMenu :collapsed="collapsed" :items="links.settings" orientation="vertical" :ui="navUi" />
        </template>

        <!-- Roadmap plegado: un grupo, no siete secciones de enlaces muertos. -->
        <div class="mt-auto pt-3 border-t border-default">
          <UNavigationMenu :collapsed="collapsed" :items="links.soon" orientation="vertical" :ui="navUi" />
        </div>
      </template>

      <template #footer="{ collapsed }">
        <UDropdownMenu :items="userMenuItems" :content="{ side: 'top', align: 'start' }">
          <UButton
            color="neutral"
            variant="ghost"
            class="w-full"
            :class="collapsed ? 'justify-center' : 'justify-start'"
          >
            <UAvatar
              :src="auth.user?.avatar || undefined"
              :alt="auth.user?.name || 'Admin'"
              size="sm"
            />
            <template v-if="!collapsed">
              <div class="flex flex-col leading-tight items-start min-w-0 flex-1 ml-1">
                <span class="text-xs font-semibold truncate w-full text-left">{{ auth.user?.name || 'Administrador' }}</span>
                <span class="text-[10px] text-muted truncate w-full text-left">{{ auth.user?.email || 'admin@incalake.com' }}</span>
              </div>
              <UIcon name="i-lucide-ellipsis-vertical" class="size-4 text-muted shrink-0" />
            </template>
          </UButton>
        </UDropdownMenu>
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="searchGroups" />

    <slot />

    <ConfirmDialog />
  </UDashboardGroup>
</template>
