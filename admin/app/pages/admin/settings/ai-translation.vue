<template>
  <NuxtLayout name="admin">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
            <span class="material-symbols-outlined text-2xl">translate</span>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Configuración de Traducción IA</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Configure su proveedor de IA para traducciones automáticas de tours</p>
          </div>
        </div>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="successMessage" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-start gap-3">
        <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
        <div class="flex-1">
          <p class="text-sm font-medium text-green-900 dark:text-green-100">{{ successMessage }}</p>
        </div>
        <button @click="successMessage = ''" class="text-green-600 hover:text-green-700">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-3">
        <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
        <div class="flex-1">
          <p class="text-sm font-medium text-red-900 dark:text-red-100">{{ errorMessage }}</p>
        </div>
        <button @click="errorMessage = ''" class="text-red-600 hover:text-red-700">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <!-- Main Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
        <!-- Provider Selection -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
          <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-4">
            Proveedor de IA
          </label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button
              v-for="provider in providers"
              :key="provider.id"
              @click="selectProvider(provider.id)"
              class="relative p-4 rounded-xl border-2 transition-all hover:scale-105"
              :class="settings.provider === provider.id
                ? 'border-primary bg-primary/5 shadow-lg shadow-primary/20'
                : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
            >
              <div class="flex flex-col items-center gap-2">
                <div
                  class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
                  :style="{ background: provider.color }"
                >
                  {{ provider.icon }}
                </div>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ provider.name }}</span>
              </div>
              <div v-if="settings.provider === provider.id" class="absolute top-2 right-2">
                <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
              </div>
            </button>
          </div>
        </div>

        <!-- Configuration Form -->
        <div class="p-6 space-y-6">
          <!-- API Key -->
          <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              API Key
              <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="settings.api_key"
                :type="showApiKey ? 'text' : 'password'"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all pr-12"
                :placeholder="`Ingrese su ${selectedProvider?.name} API Key`"
              />
              <button
                @click="showApiKey = !showApiKey"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
              >
                <span class="material-symbols-outlined text-xl">{{ showApiKey ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
              Obtenga su API key desde:
              <a :href="selectedProvider?.apiUrl" target="_blank" class="text-primary hover:underline">
                {{ selectedProvider?.apiUrl }}
              </a>
            </p>
          </div>

          <!-- Model Selection -->
          <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              Modelo
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="settings.model"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
            >
              <option value="">Seleccione un modelo</option>
              <option
                v-for="model in selectedProvider?.models"
                :key="model.id"
                :value="model.id"
              >
                {{ model.name }} - {{ model.description }}
              </option>
            </select>
          </div>

          <!-- Custom Prompt -->
          <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
              Prompt Personalizado (Opcional)
            </label>
            <textarea
              v-model="settings.custom_prompt"
              rows="6"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"
              placeholder="Personalice las instrucciones para la traducción. Deje vacío para usar el prompt predeterminado."
            ></textarea>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
              Variables disponibles: <code class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded">{source_language}</code>,
              <code class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded">{target_language}</code>,
              <code class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded">{content}</code>
            </p>
          </div>

          <!-- Default Prompt Info -->
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex gap-3">
              <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-xl">info</span>
              <div class="flex-1">
                <h4 class="text-sm font-bold text-blue-900 dark:text-blue-100 mb-1">Prompt Predeterminado</h4>
                <p class="text-xs text-blue-700 dark:text-blue-300 font-mono leading-relaxed">
                  "You are a professional tourism translator. Translate the following tour content from {source_language} to {target_language}.
                  Maintain the tone, SEO optimization, and tourism-specific terminology. Preserve HTML tags and formatting."
                </p>
              </div>
            </div>
          </div>

          <!-- Additional Settings -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Temperature -->
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Temperatura (Creatividad)
              </label>
              <div class="flex items-center gap-4">
                <input
                  v-model.number="settings.settings.temperature"
                  type="range"
                  min="0"
                  max="1"
                  step="0.1"
                  class="flex-1"
                />
                <span class="text-sm font-mono text-slate-600 dark:text-slate-400 w-12 text-right">
                  {{ settings.settings.temperature }}
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">0 = Conservador, 1 = Creativo</p>
            </div>

            <!-- Max Tokens -->
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Max Tokens
              </label>
              <input
                v-model.number="settings.settings.max_tokens"
                type="number"
                min="100"
                max="16000"
                step="100"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              />
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Máximo de tokens por respuesta</p>
            </div>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
            <div>
              <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Activar Traducción IA</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Habilite o deshabilite el sistema de traducción automática</p>
            </div>
            <button
              @click="settings.is_active = !settings.is_active"
              class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
              :class="settings.is_active ? 'bg-primary' : 'bg-slate-300 dark:bg-slate-600'"
            >
              <span
                class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform shadow-sm"
                :class="settings.is_active ? 'translate-x-6' : 'translate-x-1'"
              ></span>
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-3">
          <button
            @click="testConnection"
            :disabled="isTesting || !settings.api_key || !settings.model"
            class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <span class="material-symbols-outlined text-lg">{{ isTesting ? 'progress_activity' : 'cable' }}</span>
            {{ isTesting ? 'Probando...' : 'Probar Conexión' }}
          </button>
          <button
            @click="saveSettings"
            :disabled="isSaving"
            class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-primary/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <span class="material-symbols-outlined text-lg">{{ isSaving ? 'progress_activity' : 'save' }}</span>
            {{ isSaving ? 'Guardando...' : 'Guardar Configuración' }}
          </button>
        </div>
      </div>

      <!-- Usage Stats (Placeholder) -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">translate</span>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Traducciones este mes</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">0</p>
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <span class="material-symbols-outlined text-green-600 dark:text-green-400">payments</span>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Costo estimado</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">$0.00</p>
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">speed</span>
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Tiempo promedio</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">--</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: false
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

const providers = [
  {
    id: 'openai',
    name: 'OpenAI',
    icon: '🤖',
    color: 'linear-gradient(135deg, #10a37f 0%, #1a7f64 100%)',
    apiUrl: 'https://platform.openai.com/api-keys',
    models: [
      { id: 'gpt-4o', name: 'GPT-4 Optimized', description: 'Más rápido y económico' },
      { id: 'gpt-4o-mini', name: 'GPT-4o Mini', description: 'Ultra rápido' },
      { id: 'gpt-4-turbo', name: 'GPT-4 Turbo', description: 'Más potente' },
    ]
  },
  {
    id: 'anthropic',
    name: 'Anthropic',
    icon: '🔮',
    color: 'linear-gradient(135deg, #D4A373 0%, #B8860B 100%)',
    apiUrl: 'https://console.anthropic.com/settings/keys',
    models: [
      { id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet', description: 'Equilibrado' },
      { id: 'claude-3-opus-20240229', name: 'Claude 3 Opus', description: 'Más potente' },
      { id: 'claude-3-haiku-20240307', name: 'Claude 3 Haiku', description: 'Más rápido' },
    ]
  },
  {
    id: 'gemini',
    name: 'Gemini',
    icon: '✨',
    color: 'linear-gradient(135deg, #4285f4 0%, #34a853 100%)',
    apiUrl: 'https://makersuite.google.com/app/apikey',
    models: [
      { id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash', description: 'Más nuevo y rápido' },
      { id: 'gemini-2.5-pro', name: 'Gemini 2.5 Pro', description: 'Más potente' },
      { id: 'gemini-2.0-flash', name: 'Gemini 2.0 Flash', description: 'Equilibrado' },
    ]
  },
  {
    id: 'deepseek',
    name: 'DeepSeek',
    icon: '🧠',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    apiUrl: 'https://platform.deepseek.com/api_keys',
    models: [
      { id: 'deepseek-chat', name: 'DeepSeek Chat', description: 'Propósito general' },
      { id: 'deepseek-reasoner', name: 'DeepSeek Reasoner', description: 'Razonamiento avanzado' },
    ]
  }
]

const settings = ref<AISettings>({
  provider: 'openai',
  api_key: '',
  model: '',
  custom_prompt: '',
  is_active: true,
  settings: {
    temperature: 0.3,
    max_tokens: 4000
  }
})

const showApiKey = ref(false)
const isSaving = ref(false)
const isTesting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const selectedProvider = computed(() => {
  return providers.find(p => p.id === settings.value.provider)
})

const selectProvider = (providerId: string) => {
  settings.value.provider = providerId
  settings.value.model = '' // Reset model when changing provider
}

const loadSettings = async () => {
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
            : data.data.settings || { temperature: 0.3, max_tokens: 4000 }
        }
      }
    }
  } catch (error) {
    console.error('Error loading settings:', error)
  }
}

const saveSettings = async () => {
  console.log('Intentando guardar:', {
    provider: settings.value.provider,
    hasApiKey: !!settings.value.api_key,
    apiKeyLength: settings.value.api_key?.length,
    model: settings.value.model
  })

  if (!settings.value.api_key || !settings.value.model) {
    errorMessage.value = 'Por favor complete todos los campos obligatorios'
    return
  }

  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(`${config.public.apiUrl}/ai-translation-settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(settings.value)
    })

    if (response.ok) {
      successMessage.value = '✅ Configuración guardada exitosamente'
    } else {
      const error = await response.json()
      errorMessage.value = error.message || 'Error al guardar la configuración'
    }
  } catch (error) {
    errorMessage.value = 'Error de conexión al servidor'
  } finally {
    isSaving.value = false
  }
}

const testConnection = async () => {
  if (!settings.value.api_key || !settings.value.model) {
    errorMessage.value = 'Configure API Key y Modelo antes de probar'
    return
  }

  isTesting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(`${config.public.apiUrl}/ai-translation-test`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        provider: settings.value.provider,
        api_key: settings.value.api_key,
        model: settings.value.model
      })
    })

    if (response.ok) {
      successMessage.value = '✅ Conexión exitosa! El proveedor de IA está funcionando correctamente'
    } else {
      const error = await response.json()
      errorMessage.value = `❌ Error de conexión: ${error.message || 'Verifique su API Key'}`
    }
  } catch (error) {
    errorMessage.value = '❌ Error al probar la conexión. Verifique su configuración.'
  } finally {
    isTesting.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>
