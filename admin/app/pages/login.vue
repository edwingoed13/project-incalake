<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '~/composables/useAuth'

definePageMeta({ layout: 'auth' })

const { login } = useAuth()
const router = useRouter()
const colorMode = useColorMode()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

// Unified with the rest of the admin via useColorMode() — no more manual
// localStorage('theme') + dark-class toggling that caused the light/dark flicker.
const isDark = computed(() => colorMode.value === 'dark')
const toggleTheme = () => { colorMode.preference = isDark.value ? 'light' : 'dark' }

const handleLogin = async () => {
  errorMsg.value = ''
  isLoading.value = true
  try {
    await login(email.value, password.value)
    await router.push('/admin/v2')
  } catch (e: any) {
    errorMsg.value = e.message || 'Error desconocido al validar.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="w-full max-w-sm relative">
    <!-- Theme toggle (synced with the whole admin) -->
    <UButton
      :icon="isDark ? 'i-lucide-sun' : 'i-lucide-moon'"
      color="neutral"
      variant="ghost"
      size="lg"
      class="fixed top-4 right-4"
      aria-label="Cambiar tema"
      @click="toggleTheme"
    />

    <!-- Brand -->
    <div class="flex flex-col items-center text-center mb-8">
      <div class="size-14 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30 mb-4">
        <UIcon name="i-lucide-compass" class="size-7" />
      </div>
      <h1 class="text-2xl font-bold tracking-tight">Incalake <span class="text-primary">CMS</span></h1>
      <p class="text-sm text-muted mt-1">Accede al panel de control</p>
    </div>

    <!-- Card -->
    <div class="bg-elevated/40 backdrop-blur border border-default rounded-2xl shadow-xl p-6 sm:p-8">
      <form class="space-y-4" @submit.prevent="handleLogin">
        <UFormField label="Correo electrónico">
          <UInput
            v-model="email"
            type="email"
            icon="i-lucide-mail"
            placeholder="admin@incalake.com"
            size="lg"
            autocomplete="email"
            :disabled="isLoading"
            class="w-full"
          />
        </UFormField>

        <UFormField label="Contraseña">
          <UInput
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            icon="i-lucide-lock-keyhole"
            placeholder="••••••••"
            size="lg"
            autocomplete="current-password"
            :disabled="isLoading"
            class="w-full"
          >
            <template #trailing>
              <UButton
                :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                color="neutral"
                variant="link"
                size="sm"
                tabindex="-1"
                aria-label="Mostrar u ocultar contraseña"
                @click="showPassword = !showPassword"
              />
            </template>
          </UInput>
        </UFormField>

        <UAlert
          v-if="errorMsg"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          :title="errorMsg"
        />

        <UButton
          type="submit"
          color="primary"
          size="lg"
          block
          :loading="isLoading"
          :trailing-icon="isLoading ? undefined : 'i-lucide-arrow-right'"
        >
          {{ isLoading ? 'Validando...' : 'Entrar' }}
        </UButton>
      </form>
    </div>

    <p class="text-center text-xs text-muted mt-6">© {{ new Date().getFullYear() }} Incalake CMS · Tourism management</p>
  </div>
</template>
