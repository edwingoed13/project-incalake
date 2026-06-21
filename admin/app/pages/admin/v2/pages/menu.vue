<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Language {
  id: number
  code: string
  country: string
}

interface MenuItem {
  label: string
  url: string
  type: 'internal' | 'external'
  visible: boolean
}

interface MenuContent {
  items: MenuItem[]
}

const config = useRuntimeConfig()
const auth = useAuthStore()
const toast = useToast()
const { confirm } = useConfirm()

const langFlags: Record<string, string> = {
  ES: '🇪🇸', EN: '🇬🇧', PT: '🇵🇹', FR: '🇫🇷', DE: '🇩🇪', IT: '🇮🇹',
}

const languages = ref<Language[]>([])
const currentLang = ref<Language | null>(null)
const allContents = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const published = ref(true)

// Starting point for a brand-new language: the site's default header links.
const defaultForm = (): MenuContent => ({
  items: [
    { label: 'Tours', url: '/tours', type: 'internal', visible: true },
    { label: 'Nosotros', url: '/about', type: 'internal', visible: true },
    { label: 'Contacto', url: '/contact', type: 'internal', visible: true },
  ],
})

const form = ref<MenuContent>(defaultForm())

const headers = () => ({
  Authorization: `Bearer ${auth.token || localStorage.getItem('auth_token') || ''}`,
  Accept: 'application/json',
})

const esContent = computed(() => allContents.value.find(c => c.language_code === 'ES'))
const hasSpanishContent = computed(() => !!esContent.value)

const getStatus = (langId: number): 'published' | 'draft' | 'empty' => {
  const c = allContents.value.find(x => x.language_id === langId)
  if (!c) return 'empty'
  return c.published ? 'published' : 'draft'
}

const statusColor = (status: string) => {
  if (status === 'published') return 'bg-success'
  if (status === 'draft') return 'bg-warning'
  return 'bg-elevated'
}

const fetchLanguages = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/languages?all=true`)
    if (res.success) languages.value = res.data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar los idiomas.', color: 'error', icon: 'i-lucide-alert-triangle' })
  }
}

const fetchContents = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/menu`, { headers: headers() })
    if (res.success) allContents.value = res.data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudo cargar el menú.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

// Normalize loaded content (older/partial data may miss fields).
const normalize = (content: any): MenuContent => {
  const items = Array.isArray(content?.items) ? content.items : []
  return {
    items: items.map((i: any): MenuItem => ({
      label: String(i?.label ?? ''),
      url: String(i?.url ?? ''),
      type: i?.type === 'external' ? 'external' : 'internal',
      visible: i?.visible !== false,
    })),
  }
}

const selectLanguage = (lang: Language) => {
  currentLang.value = lang
  const existing = allContents.value.find(c => c.language_id === lang.id)
  if (existing) {
    form.value = normalize(existing.content)
    published.value = existing.published
  } else {
    form.value = defaultForm()
    published.value = false
  }
}

const addItem = () => {
  form.value.items.push({ label: '', url: '/', type: 'internal', visible: true })
}
const removeItem = (idx: number) => {
  form.value.items.splice(idx, 1)
}
const moveItem = (idx: number, dir: -1 | 1) => {
  const next = idx + dir
  if (next < 0 || next >= form.value.items.length) return
  const arr = form.value.items
  ;[arr[idx], arr[next]] = [arr[next], arr[idx]]
}

const copyFromSpanish = () => {
  if (!esContent.value) return
  form.value = normalize(esContent.value.content)
  toast.add({ title: 'Estructura copiada de Español', description: 'Traduce las etiquetas y guarda.', icon: 'i-lucide-copy', color: 'info' })
}

const save = async () => {
  if (!currentLang.value) return
  // Light validation: every item needs a label and url.
  const bad = form.value.items.find(i => !i.label.trim() || !i.url.trim())
  if (bad) {
    toast.add({ title: 'Faltan datos', description: 'Cada ítem necesita etiqueta y URL.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  saving.value = true
  try {
    const res: any = await $fetch(`${config.public.apiUrl}/admin/pages/menu`, {
      method: 'PUT',
      headers: headers(),
      body: {
        language_id: currentLang.value.id,
        content: form.value,
        published: published.value,
      },
    })
    if (res.success) {
      toast.add({ title: 'Menú guardado', description: `Idioma: ${currentLang.value.country}`, icon: 'i-lucide-circle-check', color: 'success' })
      await fetchContents()
      if (currentLang.value) selectLanguage(currentLang.value)
    }
  } catch (e: any) {
    toast.add({ title: 'Error al guardar', description: e.data?.message || e.message, color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchLanguages()
  await fetchContents()
  const esLang = languages.value.find(l => l.code === 'ES')
  if (esLang) selectLanguage(esLang)
})
</script>

<template>
  <UDashboardPanel id="menu-page-v2">
    <template #header>
      <UDashboardNavbar title="Menú principal">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="currentLang && currentLang.code !== 'ES' && hasSpanishContent"
            icon="i-lucide-copy"
            color="secondary"
            variant="subtle"
            @click="copyFromSpanish"
          >
            Copiar estructura de ES
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-6 pb-32">
        <div>
          <h1 class="admin-h1">Menú de navegación (header)</h1>
          <p class="text-sm text-muted mt-1">Define los enlaces del menú principal por idioma. El orden de la lista es el orden del menú.</p>
        </div>

        <!-- Language tabs -->
        <div class="flex items-center gap-2 flex-wrap">
          <UButton
            v-for="lang in languages"
            :key="lang.id"
            :color="currentLang?.id === lang.id ? 'primary' : 'neutral'"
            :variant="currentLang?.id === lang.id ? 'solid' : 'subtle'"
            size="md"
            class="font-bold"
            @click="selectLanguage(lang)"
          >
            <span class="text-base">{{ langFlags[lang.code] || '🌐' }}</span>
            <span>{{ lang.country }}</span>
            <span :class="['size-2 rounded-full ml-1', statusColor(getStatus(lang.id))]" :title="getStatus(lang.id)" />
          </UButton>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="py-20 flex flex-col items-center gap-3">
          <UIcon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin" />
          <p class="text-sm text-muted">Cargando menú...</p>
        </div>

        <!-- Editor -->
        <div v-else-if="currentLang" class="space-y-3">
          <div
            v-for="(item, idx) in form.items"
            :key="idx"
            class="flex items-start gap-3 p-3 rounded-xl border border-default bg-elevated/30"
            :class="{ 'opacity-60': !item.visible }"
          >
            <!-- Reorder -->
            <div class="flex flex-col gap-0.5 pt-1 shrink-0">
              <UButton icon="i-lucide-chevron-up" size="xs" color="neutral" variant="ghost" :disabled="idx === 0" @click="moveItem(idx, -1)" />
              <UButton icon="i-lucide-chevron-down" size="xs" color="neutral" variant="ghost" :disabled="idx === form.items.length - 1" @click="moveItem(idx, 1)" />
            </div>

            <!-- Fields -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-3 min-w-0">
              <UFormField label="Etiqueta" class="md:col-span-4">
                <UInput v-model="item.label" placeholder="Ej: Tours" class="w-full font-semibold" />
              </UFormField>
              <UFormField label="URL" class="md:col-span-5">
                <UInput v-model="item.url" :placeholder="item.type === 'external' ? 'https://...' : '/tours'" class="w-full font-mono text-sm" />
              </UFormField>
              <UFormField label="Tipo" class="md:col-span-3">
                <div class="flex gap-1">
                  <UButton
                    size="xs"
                    class="flex-1 justify-center"
                    :color="item.type === 'internal' ? 'primary' : 'neutral'"
                    :variant="item.type === 'internal' ? 'solid' : 'subtle'"
                    @click="item.type = 'internal'"
                  >
                    Interno
                  </UButton>
                  <UButton
                    size="xs"
                    class="flex-1 justify-center"
                    :color="item.type === 'external' ? 'primary' : 'neutral'"
                    :variant="item.type === 'external' ? 'solid' : 'subtle'"
                    @click="item.type = 'external'"
                  >
                    Externo
                  </UButton>
                </div>
              </UFormField>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1 pt-6 shrink-0">
              <UButton
                :icon="item.visible ? 'i-lucide-eye' : 'i-lucide-eye-off'"
                size="xs"
                :color="item.visible ? 'success' : 'neutral'"
                variant="ghost"
                :title="item.visible ? 'Visible' : 'Oculto'"
                @click="item.visible = !item.visible"
              />
              <UButton icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" title="Eliminar" @click="removeItem(idx)" />
            </div>
          </div>

          <div v-if="!form.items.length" class="py-10 text-center text-muted">
            <UIcon name="i-lucide-list" class="size-8 mx-auto mb-2" />
            <p class="text-sm">Sin ítems. Agrega el primer enlace.</p>
          </div>

          <UButton icon="i-lucide-plus" color="neutral" variant="subtle" block @click="addItem">
            Agregar ítem
          </UButton>

          <UAlert
            color="neutral"
            variant="subtle"
            icon="i-lucide-info"
            class="mt-2"
            description="Internos: ruta del sitio (ej. /tours, /about). Se adapta al idioma automáticamente. Externos: URL completa (https://...), abren en una pestaña nueva."
          />
        </div>
      </div>

      <!-- Sticky action bar -->
      <div
        v-if="currentLang && !loading"
        class="sticky bottom-0 left-0 right-0 bg-default/95 backdrop-blur-sm border-t border-default p-4 flex items-center justify-between gap-3 flex-wrap"
      >
        <div class="flex items-center gap-3">
          <USwitch v-model="published" label="Publicado" />
          <UBadge :color="published ? 'success' : 'warning'" variant="subtle" size="sm" :icon="published ? 'i-lucide-eye' : 'i-lucide-file-text'">
            {{ published ? 'Visible al público' : 'Solo borrador' }}
          </UBadge>
          <span class="text-xs text-muted hidden md:inline">
            Editando: <span class="font-bold">{{ currentLang.country }}</span>
          </span>
        </div>
        <UButton icon="i-lucide-save" color="primary" size="lg" :loading="saving" @click="save">
          {{ saving ? 'Guardando...' : 'Guardar menú' }}
        </UButton>
      </div>
    </template>
  </UDashboardPanel>
</template>
