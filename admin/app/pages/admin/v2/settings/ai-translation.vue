<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface AISettings {
  provider: string
  api_key: string
  model: string
  custom_prompt: string
  is_active: boolean
  settings: {
    temperature: number
    max_tokens: number
  }
}

const config = useRuntimeConfig()
const toast = useToast()

const providers = [
  {
    id: 'openai',
    name: 'OpenAI',
    icon: '🤖',
    accent: 'from-emerald-500 to-emerald-600',
    apiUrl: 'https://platform.openai.com/api-keys',
    models: [
      { id: 'gpt-4o', name: 'GPT-4 Optimized', description: 'Más rápido y económico' },
      { id: 'gpt-4o-mini', name: 'GPT-4o Mini', description: 'Ultra rápido' },
      { id: 'gpt-4-turbo', name: 'GPT-4 Turbo', description: 'Más potente' },
    ],
  },
  {
    id: 'anthropic',
    name: 'Anthropic',
    icon: '🔮',
    accent: 'from-amber-500 to-orange-600',
    apiUrl: 'https://console.anthropic.com/settings/keys',
    models: [
      { id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet', description: 'Equilibrado' },
      { id: 'claude-3-opus-20240229', name: 'Claude 3 Opus', description: 'Más potente' },
      { id: 'claude-3-haiku-20240307', name: 'Claude 3 Haiku', description: 'Más rápido' },
    ],
  },
  {
    id: 'gemini',
    name: 'Gemini',
    icon: '✨',
    accent: 'from-blue-500 to-green-500',
    apiUrl: 'https://makersuite.google.com/app/apikey',
    models: [
      { id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash', description: 'Más nuevo y rápido' },
      { id: 'gemini-2.5-pro', name: 'Gemini 2.5 Pro', description: 'Más potente' },
      { id: 'gemini-2.0-flash', name: 'Gemini 2.0 Flash', description: 'Equilibrado' },
    ],
  },
  {
    id: 'deepseek',
    name: 'DeepSeek',
    icon: '🧠',
    accent: 'from-indigo-500 to-purple-600',
    apiUrl: 'https://platform.deepseek.com/api_keys',
    models: [
      { id: 'deepseek-chat', name: 'DeepSeek Chat', description: 'Propósito general' },
      { id: 'deepseek-reasoner', name: 'DeepSeek Reasoner', description: 'Razonamiento avanzado' },
    ],
  },
]

const settings = ref<AISettings>({
  provider: 'openai',
  api_key: '',
  model: '',
  custom_prompt: '',
  is_active: true,
  settings: { temperature: 0.3, max_tokens: 4000 },
})

const showApiKey = ref(false)
const isSaving = ref(false)
const isTesting = ref(false)
const isLoading = ref(true)

const selectedProvider = computed(() => providers.find(p => p.id === settings.value.provider))

const modelOptions = computed(() =>
  (selectedProvider.value?.models || []).map(m => ({
    label: `${m.name} — ${m.description}`,
    value: m.id,
  })),
)

const selectProvider = (providerId: string) => {
  settings.value.provider = providerId
  settings.value.model = ''
}

const loadSettings = async () => {
  isLoading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/ai-translation-settings`)
    if (response.ok) {
      const data = await response.json()
      if (data.data) {
        settings.value = {
          ...data.data,
          is_active: data.data.is_active ?? true,
          settings: typeof data.data.settings === 'string'
            ? JSON.parse(data.data.settings)
            : data.data.settings || { temperature: 0.3, max_tokens: 4000 },
        }
      }
    }
  } catch (err) {
    console.error('Error loading settings:', err)
  } finally {
    isLoading.value = false
  }
}

const saveSettings = async () => {
  if (!settings.value.api_key || !settings.value.model) {
    toast.add({
      title: 'Faltan campos',
      description: 'API Key y modelo son obligatorios.',
      color: 'warning',
      icon: 'i-lucide-info',
    })
    return
  }

  isSaving.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/ai-translation-settings`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(settings.value),
    })
    if (response.ok) {
      toast.add({
        title: 'Configuración guardada',
        icon: 'i-lucide-circle-check',
        color: 'success',
      })
    } else {
      const err = await response.json()
      throw new Error(err.message || 'Error al guardar')
    }
  } catch (err: any) {
    toast.add({
      title: 'Error al guardar',
      description: err.message || 'Error de conexión al servidor',
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
  } finally {
    isSaving.value = false
  }
}

const testConnection = async () => {
  if (!settings.value.api_key || !settings.value.model) {
    toast.add({
      title: 'Configura primero',
      description: 'Necesitas API Key y modelo antes de probar.',
      color: 'warning',
      icon: 'i-lucide-info',
    })
    return
  }

  isTesting.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/ai-translation-test`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        provider: settings.value.provider,
        api_key: settings.value.api_key,
        model: settings.value.model,
      }),
    })
    if (response.ok) {
      toast.add({
        title: 'Conexión exitosa',
        description: 'El proveedor de IA respondió correctamente.',
        icon: 'i-lucide-circle-check',
        color: 'success',
      })
    } else {
      const err = await response.json()
      throw new Error(err.message || 'Verifica tu API Key')
    }
  } catch (err: any) {
    toast.add({
      title: 'Error de conexión',
      description: err.message || 'Verifica tu configuración.',
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
  } finally {
    isTesting.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>

<template>
  <UDashboardPanel id="ai-translation-v2">
    <template #header>
      <UDashboardNavbar title="Traducción IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UBadge
            :color="settings.is_active ? 'success' : 'neutral'"
            variant="subtle"
            size="md"
            :icon="settings.is_active ? 'i-lucide-circle-check' : 'i-lucide-circle-pause'"
          >
            {{ settings.is_active ? 'Activo' : 'Desactivado' }}
          </UBadge>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-5 pb-32">
        <div class="flex items-start gap-3">
          <div class="size-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
            <UIcon name="i-lucide-sparkles" class="size-6" />
          </div>
          <div>
            <h2 class="text-2xl font-bold">Configuración de Traducción IA</h2>
            <p class="text-sm text-muted mt-1">
              Configura tu proveedor de IA para traducir automáticamente el contenido de los tours
            </p>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="py-20 flex flex-col items-center gap-3">
          <UIcon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin" />
          <p class="text-sm text-muted">Cargando configuración...</p>
        </div>

        <template v-else>
          <!-- Provider selection -->
          <UCard :ui="{ body: 'p-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-server" class="size-5 text-primary" />
                <h3 class="text-base font-bold">Proveedor de IA</h3>
              </div>
            </template>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <button
                v-for="provider in providers"
                :key="provider.id"
                type="button"
                :class="[
                  'relative p-4 rounded-xl border-2 transition-all hover:scale-105',
                  settings.provider === provider.id
                    ? 'border-primary bg-primary/5 shadow-lg shadow-primary/20'
                    : 'border-default hover:border-muted',
                ]"
                @click="selectProvider(provider.id)"
              >
                <div class="flex flex-col items-center gap-2">
                  <div :class="['size-12 rounded-lg flex items-center justify-center text-2xl bg-gradient-to-br', provider.accent]">
                    {{ provider.icon }}
                  </div>
                  <span class="text-sm font-semibold">{{ provider.name }}</span>
                </div>
                <UIcon
                  v-if="settings.provider === provider.id"
                  name="i-lucide-circle-check"
                  class="absolute top-2 right-2 size-5 text-primary"
                />
              </button>
            </div>
          </UCard>

          <!-- Configuration form -->
          <UCard :ui="{ body: 'p-5 space-y-5' }">
            <template #header>
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-settings-2" class="size-5 text-primary" />
                <h3 class="text-base font-bold">Configuración del proveedor</h3>
              </div>
            </template>

            <UFormField label="API Key" required>
              <UInput
                v-model="settings.api_key"
                :type="showApiKey ? 'text' : 'password'"
                :placeholder="`Ingresa tu API Key de ${selectedProvider?.name}`"
                icon="i-lucide-key"
                class="w-full"
              >
                <template #trailing>
                  <UButton
                    :icon="showApiKey ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                    color="neutral"
                    variant="link"
                    size="xs"
                    @click="showApiKey = !showApiKey"
                  />
                </template>
              </UInput>
              <template #help>
                Obtén tu API key desde
                <a :href="selectedProvider?.apiUrl" target="_blank" class="text-primary hover:underline">
                  {{ selectedProvider?.apiUrl }}
                </a>
              </template>
            </UFormField>

            <UFormField label="Modelo" required>
              <USelectMenu
                v-model="settings.model"
                :items="modelOptions"
                value-key="value"
                placeholder="Selecciona un modelo"
                icon="i-lucide-cpu"
                class="w-full"
              />
            </UFormField>

            <UFormField label="Prompt personalizado (opcional)">
              <UTextarea
                v-model="settings.custom_prompt"
                :rows="5"
                placeholder="Personaliza las instrucciones. Deja vacío para usar el prompt por defecto."
                class="w-full font-mono"
              />
              <template #help>
                Variables disponibles:
                <code class="px-1.5 py-0.5 bg-elevated rounded text-[10px]">{source_language}</code>,
                <code class="px-1.5 py-0.5 bg-elevated rounded text-[10px]">{target_language}</code>,
                <code class="px-1.5 py-0.5 bg-elevated rounded text-[10px]">{content}</code>
              </template>
            </UFormField>

            <UAlert
              color="info"
              variant="subtle"
              icon="i-lucide-info"
              title="Prompt por defecto"
              description="You are a professional tourism translator. Translate the following tour content from {source_language} to {target_language}. Maintain the tone, SEO optimization, and tourism-specific terminology. Preserve HTML tags and formatting."
              :ui="{ description: 'font-mono text-[11px] leading-relaxed' }"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <UFormField label="Temperatura (creatividad)" hint="0 = conservador · 1 = creativo">
                <div class="flex items-center gap-3">
                  <input
                    v-model.number="settings.settings.temperature"
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    class="flex-1 accent-primary"
                  />
                  <UBadge color="primary" variant="subtle" size="sm" class="font-mono w-12 justify-center">
                    {{ settings.settings.temperature }}
                  </UBadge>
                </div>
              </UFormField>

              <UFormField label="Max tokens" hint="Máximo de tokens por respuesta">
                <UInput
                  v-model.number="settings.settings.max_tokens"
                  type="number"
                  min="100"
                  max="16000"
                  step="100"
                  icon="i-lucide-hash"
                  class="w-full"
                />
              </UFormField>
            </div>

            <div class="flex items-center justify-between p-4 bg-elevated/40 rounded-xl">
              <div>
                <h4 class="text-sm font-bold">Activar Traducción IA</h4>
                <p class="text-xs text-muted mt-0.5">
                  Habilita o deshabilita el sistema de traducción automática
                </p>
              </div>
              <USwitch v-model="settings.is_active" size="lg" />
            </div>
          </UCard>

          <!-- Usage stats (placeholder) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UCard :ui="{ body: 'p-4' }">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-info/10 flex items-center justify-center">
                  <UIcon name="i-lucide-languages" class="size-5 text-info" />
                </div>
                <div>
                  <p class="text-xs text-muted">Traducciones este mes</p>
                  <p class="text-xl font-bold tabular-nums">0</p>
                </div>
              </div>
            </UCard>
            <UCard :ui="{ body: 'p-4' }">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-success/10 flex items-center justify-center">
                  <UIcon name="i-lucide-dollar-sign" class="size-5 text-success" />
                </div>
                <div>
                  <p class="text-xs text-muted">Costo estimado</p>
                  <p class="text-xl font-bold tabular-nums">$0.00</p>
                </div>
              </div>
            </UCard>
            <UCard :ui="{ body: 'p-4' }">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-secondary/10 flex items-center justify-center">
                  <UIcon name="i-lucide-gauge" class="size-5 text-secondary" />
                </div>
                <div>
                  <p class="text-xs text-muted">Tiempo promedio</p>
                  <p class="text-xl font-bold tabular-nums">—</p>
                </div>
              </div>
            </UCard>
          </div>
        </template>
      </div>

      <!-- Sticky action bar -->
      <div
        v-if="!isLoading"
        class="sticky bottom-0 left-0 right-0 bg-default/95 backdrop-blur-sm border-t border-default p-4 flex items-center justify-end gap-3 flex-wrap"
      >
        <UButton
          icon="i-lucide-cable"
          color="neutral"
          variant="outline"
          size="lg"
          :loading="isTesting"
          :disabled="!settings.api_key || !settings.model"
          @click="testConnection"
        >
          {{ isTesting ? 'Probando...' : 'Probar conexión' }}
        </UButton>
        <UButton
          icon="i-lucide-save"
          color="primary"
          size="lg"
          :loading="isSaving"
          @click="saveSettings"
        >
          {{ isSaving ? 'Guardando...' : 'Guardar configuración' }}
        </UButton>
      </div>
    </template>
  </UDashboardPanel>
</template>
