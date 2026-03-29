export default defineNuxtRouteMiddleware((to, from) => {
  if (process.server) return

  const authStore = useAuthStore()

  // Mapeo de rutas a permisos requeridos
  const routePermissions: { [key: string]: string } = {
    '/admin/users': 'users.view',
    '/admin/categories': 'categories.view',
    '/admin/languages': 'languages.view',
    '/admin/settings': 'settings.view',
    '/admin/settings/ai-translation': 'settings.ai',
  }

  // Verificar si la ruta requiere permisos específicos
  const requiredPermission = routePermissions[to.path]

  if (requiredPermission && !authStore.hasPermission(requiredPermission)) {
    // Redirigir al dashboard si no tiene el permiso
    return navigateTo('/admin/dashboard')
  }
})
