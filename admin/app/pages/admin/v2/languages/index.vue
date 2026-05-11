<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LanguageFormModalV2 from '~/components/v2/LanguageFormModalV2.vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

interface Language {
  id: number
  country: string
  code: string
  created_at?: string
  updated_at?: string
}

const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()

const loading = ref(true)
const languages = ref<Language[]>([])
const searchQuery = ref('')

const showFormModal = ref(false)
const editingLanguage = ref<Language | null>(null)

const languageFlags: Record<string, string> = {
  ES: '🇪🇸', EN: '🇬🇧', PT: '🇵🇹', FR: '🇫🇷', DE: '🇩🇪', IT: '🇮🇹',
  RU: '🇷🇺', ZH: '🇨🇳', CN: '🇨🇳', JP: '🇯🇵', JA: '🇯🇵', KR: '🇰🇷', KO: '🇰🇷',
  NL: '🇳🇱', PL: '🇵🇱', SV: '🇸🇪', NO: '🇳🇴', DA: '🇩🇰', FI: '🇫🇮',
  AR: '🇸🇦', HE: '🇮🇱', TR: '🇹🇷', EL: '🇬🇷',
}

const getFlag = (code: string) => languageFlags[code?.toUpperCase()] || '🌐'

const authHeader = () => ({
  Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
})

const loadLanguages = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/languages?all=true`, { headers: authHeader() })
    if (!response.ok) throw new Error()
    const data = await response.json()
    languages.value = data.data || data
  } catch {
    toast.add({ title: 'Error', description: 'No se pudieron cargar los idiomas.', color: 'error', icon: 'i-lucide-alert-triangle' })
  } finally {
    loading.value = false
  }
}

const filtered = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return languages.value
  return languages.value.filter((l) => {
    const hay = `${l.country || ''} ${l.code || ''}`.toLowerCase()
    return hay.includes(q)
  })
})

const sortedFiltered = computed(() =>
  [...filtered.value].sort((a, b) => (a.country || '').localeCompare(b.country || '')),
)

const openCreate = () => {
  editingLanguage.value = null
  showFormModal.value = true
}

const openEdit = (language: Language) => {
  editingLanguage.value = language
  showFormModal.value = true
}

const handleSaved = () => {
  showFormModal.value = false
  editingLanguage.value = null
  loadLanguages()
}

const handleClose = () => {
  showFormModal.value = false
  editingLanguage.value = null
}

const deleteLanguage = async (language: Language) => {
  const ok = await confirm({
    title: 'Eliminar idioma',
    description: `Vas a eliminar "${language.country}" (${language.code}). Si tiene traducciones asociadas la operación fallará.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  try {
    const response = await fetch(`${config.public.apiUrl}/admin/languages/${language.id}`, {
      method: 'DELETE',
      headers: authHeader(),
    })
    if (!response.ok) throw new Error()
    toast.add({ title: 'Idioma eliminado', icon: 'i-lucide-circle-check', color: 'success' })
    await loadLanguages()
  } catch {
    toast.add({
      title: 'Error al eliminar',
      description: 'Puede que tenga traducciones asociadas.',
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
  }
}

const rowActions = (language: Language) => [
  [{ label: 'Editar', icon: 'i-lucide-pencil', onSelect: () => openEdit(language) }],
  [{ label: 'Eliminar', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteLanguage(language) }],
]

onMounted(() => {
  loadLanguages()
})
</script>

<template>
  <UDashboardPanel id="languages-v2">
    <template #header>
      <UDashboardNavbar title="Idiomas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="ghost"
            :loading="loading"
            @click="loadLanguages"
          >
            Actualizar
          </UButton>
          <UButton icon="i-lucide-plus" @click="openCreate">Nuevo idioma</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="p-6 space-y-4">
        <div>
          <h2 class="text-2xl font-bold">Gestión de idiomas</h2>
          <p class="text-sm text-muted mt-1">Idiomas disponibles para traducciones del catálogo</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total idiomas</p>
                <p class="text-3xl font-bold tabular-nums mt-2">{{ languages.length }}</p>
              </div>
              <div class="size-11 rounded-xl bg-info/10 flex items-center justify-center">
                <UIcon name="i-lucide-languages" class="size-6 text-info" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Con código ISO</p>
                <p class="text-3xl font-bold tabular-nums mt-2 text-success">{{ languages.filter(l => l.code).length }}</p>
              </div>
              <div class="size-11 rounded-xl bg-success/10 flex items-center justify-center">
                <UIcon name="i-lucide-hash" class="size-6 text-success" />
              </div>
            </div>
          </UCard>
          <UCard :ui="{ body: 'p-5' }">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Idioma por defecto</p>
                <p class="text-3xl font-bold mt-2 flex items-center gap-2">
                  <span class="text-2xl">🇪🇸</span>
                  <span class="text-primary text-lg">ES</span>
                </p>
              </div>
              <div class="size-11 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon name="i-lucide-star" class="size-6 text-primary" />
              </div>
            </div>
          </UCard>
        </div>

        <!-- Toolbar -->
        <div class="flex items-end justify-between gap-3 flex-wrap">
          <UInput
            v-model="searchQuery"
            placeholder="Buscar por nombre o código..."
            icon="i-lucide-search"
            size="lg"
            class="flex-1 min-w-[280px]"
          >
            <template v-if="searchQuery" #trailing>
              <UButton icon="i-lucide-x" color="neutral" variant="link" size="xs" @click="searchQuery = ''" />
            </template>
          </UInput>
          <p class="text-xs text-muted ml-2">
            {{ filtered.length }} de {{ languages.length }}
          </p>
        </div>

        <!-- List -->
        <UCard :ui="{ body: 'p-0' }">
          <!-- Loading -->
          <div v-if="loading && languages.length === 0" class="p-6 space-y-3">
            <div v-for="i in 4" :key="i" class="flex items-center gap-4">
              <USkeleton class="size-10 rounded-lg" />
              <div class="flex-1 space-y-2">
                <USkeleton class="h-4 w-1/3" />
                <USkeleton class="h-3 w-1/6" />
              </div>
              <USkeleton class="h-6 w-12" />
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="sortedFiltered.length === 0" class="p-12 flex flex-col items-center text-center gap-3">
            <UIcon name="i-lucide-languages" class="size-12 text-muted" />
            <h3 class="text-lg font-semibold">
              {{ languages.length === 0 ? 'No hay idiomas' : 'Sin resultados' }}
            </h3>
            <p class="text-sm text-muted">
              {{ languages.length === 0 ? 'Crea el primer idioma del sistema' : 'Prueba con otro término' }}
            </p>
            <UButton v-if="languages.length === 0" icon="i-lucide-plus" color="primary" @click="openCreate">
              Nuevo idioma
            </UButton>
            <UButton v-else icon="i-lucide-x" variant="outline" @click="searchQuery = ''">
              Limpiar búsqueda
            </UButton>
          </div>

          <!-- Rows -->
          <ul v-else class="divide-y divide-default">
            <li
              v-for="language in sortedFiltered"
              :key="language.id"
              class="px-5 py-3 flex items-center gap-3 hover:bg-elevated/30 transition-colors"
            >
              <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 text-2xl">
                {{ getFlag(language.code) }}
              </div>

              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold truncate">{{ language.country || '(Sin nombre)' }}</p>
                <p class="text-xs text-muted mt-0.5">ID: {{ language.id }}</p>
              </div>

              <UBadge color="neutral" variant="subtle" size="md" class="font-mono">
                <UIcon name="i-lucide-hash" class="size-3" />
                {{ language.code }}
              </UBadge>

              <UBadge
                v-if="language.code === 'ES'"
                color="primary"
                variant="subtle"
                size="sm"
                icon="i-lucide-star"
              >
                Default
              </UBadge>

              <UDropdownMenu :items="rowActions(language)" :content="{ align: 'end' }">
                <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" />
              </UDropdownMenu>
            </li>
          </ul>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>

  <LanguageFormModalV2
    :open="showFormModal"
    :language="editingLanguage"
    @close="handleClose"
    @saved="handleSaved"
  />
</template>
