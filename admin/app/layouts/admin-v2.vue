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
  // Sección "Principal"
  const main = [
    { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/admin/v2' },
  ]

  // Sección "Reservas"
  const reservations = [
    { label: 'Clientes (Web/OTAs)', icon: 'i-lucide-users', to: '/admin/v2/bookings' },
    { label: 'Reviews', icon: 'i-lucide-star', to: '/admin/v2/reviews' },
    { label: 'Chatbot', icon: 'i-lucide-bot', to: '/admin/chatbot', badge: { label: 'Pronto', ...SOON } },
  ]

  // Sección "Servicios" (con permisos)
  const services: any[] = []
  if (auth.hasPermission?.('tours.view')) services.push({ label: 'Tours', icon: 'i-lucide-map-pin', to: '/admin/v2/tours' })
  services.push({ label: 'Productos', icon: 'i-lucide-shopping-bag', to: '/admin/products', badge: { label: 'Pronto', ...SOON } })
  if (auth.hasPermission?.('categories.view')) services.push({ label: 'Categorías', icon: 'i-lucide-tags', to: '/admin/v2/categories' })
  if (auth.hasPermission?.('languages.view')) services.push({ label: 'Idiomas', icon: 'i-lucide-languages', to: '/admin/v2/languages' })
  services.push({ label: 'Paquetes', icon: 'i-lucide-package', to: '/admin/packages', badge: { label: 'Pronto', ...SOON } })
  services.push({ label: 'Home Page', icon: 'i-lucide-house', to: '/admin/v2/pages/home' })

  // Sección "Finanzas"
  const finance = [
    { label: 'Pagos', icon: 'i-lucide-credit-card', to: '/admin/payments', badge: { label: 'Pronto', ...SOON } },
    { label: 'Reservas Rápidas (Culqi)', icon: 'i-lucide-zap', to: '/admin/quick-bookings', badge: { label: 'Pronto', ...SOON } },
    { label: 'Facturación', icon: 'i-lucide-receipt-text', to: '/admin/invoicing', badge: { label: 'Pronto', ...SOON } },
  ]

  // Sección "Operaciones"
  const operations = [
    { label: 'Calendario', icon: 'i-lucide-calendar-days', to: '/admin/calendar', badge: { label: 'Pronto', ...SOON } },
    { label: 'Disponibilidad', icon: 'i-lucide-calendar-check', to: '/admin/availability', badge: { label: 'Pronto', ...SOON } },
    { label: 'Proveedores', icon: 'i-lucide-store', to: '/admin/suppliers', badge: { label: 'Pronto', ...SOON } },
    { label: 'Guías y Recursos', icon: 'i-lucide-id-card', to: '/admin/guides', badge: { label: 'Pronto', ...SOON } },
  ]

  // Sección "Marketing"
  const marketing = [
    { label: 'Analytics', icon: 'i-lucide-chart-line', to: '/admin/analytics', badge: { label: 'Pronto', ...SOON } },
    { label: 'Preguntas Web', icon: 'i-lucide-circle-help', to: '/admin/web-questions', badge: { label: 'Pronto', ...SOON } },
    { label: 'Galería Web', icon: 'i-lucide-image', to: '/admin/web-gallery', badge: { label: 'Pronto', ...SOON } },
    { label: 'Reportes', icon: 'i-lucide-file-text', to: '/admin/reports', badge: { label: 'Pronto', ...SOON } },
    { label: 'Cupones', icon: 'i-lucide-ticket-percent', to: '/admin/coupons', badge: { label: 'Pronto', ...SOON } },
  ]

  // Sección "Transporte"
  const transport = [
    { label: 'Buses', icon: 'i-lucide-bus', to: '/admin/buses', badge: { label: 'Pronto', ...SOON } },
    { label: 'Aeropuerto', icon: 'i-lucide-plane', to: '/admin/airport', badge: { label: 'Pronto', ...SOON } },
    { label: 'Traslados', icon: 'i-lucide-car', to: '/admin/transfers', badge: { label: 'Pronto', ...SOON } },
  ]

  // Integraciones / Configuración
  const integrations = [
    { label: 'OTA Manager', icon: 'i-lucide-globe', to: '/admin/ota-manager', badge: { label: 'Pronto', ...SOON } },
  ]

  const settings: any[] = []
  if (auth.hasPermission?.('users.view')) settings.push({ label: 'Usuarios y Roles', icon: 'i-lucide-shield-user', to: '/admin/v2/users' })
  if (auth.hasPermission?.('settings.ai')) settings.push({ label: 'Traducción IA', icon: 'i-lucide-sparkles', to: '/admin/v2/settings/ai-translation', badge: { label: 'Nuevo', color: 'primary' as const, variant: 'subtle' as const } })

  return { main, reservations, services, finance, operations, marketing, transport, integrations, settings }
})

const searchGroups = computed(() => [
  {
    id: 'principal',
    label: 'Principal',
    items: [
      { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/admin/v2' },
      { label: 'Tours', icon: 'i-lucide-map-pin', to: '/admin/v2/tours' },
      { label: 'Reservas', icon: 'i-lucide-calendar-check', to: '/admin/v2/bookings' },
      { label: 'Reviews', icon: 'i-lucide-star', to: '/admin/v2/reviews' },
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
      { label: 'Home Page', icon: 'i-lucide-house', to: '/admin/v2/pages/home' },
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
  if (confirm('¿Cerrar sesión?')) {
    await auth.logout()
    await router.push('/login')
  }
}

const userMenuItems = computed(() => [
  [
    {
      label: auth.user?.name || 'Administrador',
      type: 'label' as const,
      avatar: { src: auth.user?.avatar || `https://api.dicebear.com/7.x/initials/svg?seed=${auth.user?.name || 'Admin'}` },
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

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Reservas</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.reservations" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed && links.services.length" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Servicios</div>
        <UNavigationMenu v-if="links.services.length" :collapsed="collapsed" :items="links.services" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Finanzas</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.finance" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Operaciones</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.operations" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Marketing</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.marketing" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Transporte</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.transport" orientation="vertical" :ui="navUi" />

        <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Integraciones</div>
        <UNavigationMenu :collapsed="collapsed" :items="links.integrations" orientation="vertical" :ui="navUi" />

        <template v-if="links.settings.length">
          <div v-if="!collapsed" class="px-3 pt-3 text-[10px] font-black uppercase tracking-widest text-muted">Configuración</div>
          <UNavigationMenu :collapsed="collapsed" :items="links.settings" orientation="vertical" :ui="navUi" />
        </template>
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
              :src="auth.user?.avatar || `https://api.dicebear.com/7.x/initials/svg?seed=${auth.user?.name || 'Admin'}`"
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
