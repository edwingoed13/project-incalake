import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // Si no está autenticado y no está en la página de login, redirigir
  if (!authStore.isAuthenticated && to.path !== '/login') {
    return navigateTo('/login')
  }

  // Si ya está autenticado e intenta ir al login, mandarlo al dashboard
  if (authStore.isAuthenticated && to.path === '/login') {
    return navigateTo('/admin/dashboard')
  }
})
