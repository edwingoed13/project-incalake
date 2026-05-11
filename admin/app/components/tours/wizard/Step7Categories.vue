<template>
  <div class="flex flex-col gap-5 pb-20">
    <!-- Header Section -->
    <UAlert
      icon="i-lucide-shield-check"
      color="primary"
      variant="subtle"
      title="Clasificación de actividad"
      description="Selecciona las categorías que mejor definan esta experiencia. Ayuda a los viajeros a encontrar tu tour vía filtros de búsqueda."
    />

    <!-- Categories -->
    <UCard :ui="{ body: 'p-4 sm:p-4 space-y-4' }">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-tags" class="size-5 text-primary" />
          <div>
            <h4 class="text-base font-bold">Categorías del tour</h4>
            <p class="text-xs text-muted">Define cómo aparece el tour en filtros de búsqueda. Recomendado: 1-3 categorías.</p>
          </div>
        </div>
        <UBadge :color="store.selectedCategories.length > 0 ? 'primary' : 'neutral'" variant="subtle" size="md">
          {{ store.selectedCategories.length }} de {{ categories.length }}
        </UBadge>
      </div>

      <!-- Selected categories as chips -->
      <div v-if="selectedCategoriesList.length > 0" class="flex flex-wrap gap-2">
        <div
          v-for="cat in selectedCategoriesList"
          :key="'sel-' + cat.id"
          class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1.5 rounded-full bg-primary text-white text-xs font-bold shadow-sm"
        >
          <span class="material-symbols-outlined text-sm">{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
          <UButton
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            size="xs"
            class="!text-white hover:!bg-white/20"
            :title="`Quitar ${cat.name}`"
            @click="toggleCategory(cat.id)"
          />
        </div>
      </div>

      <!-- Add via dropdown -->
      <div v-if="loadingCategories" class="p-3 text-center text-sm text-muted border-2 border-dashed border-default rounded-lg flex items-center justify-center gap-2">
        <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
        Cargando categorías...
      </div>
      <UPopover
        v-else-if="availableCategories.length > 0"
        :ui="{ content: 'w-80 max-w-[90vw]' }"
      >
        <UButton
          icon="i-lucide-plus"
          color="primary"
          variant="outline"
          size="md"
          trailing-icon="i-lucide-chevron-down"
        >
          {{ selectedCategoriesList.length === 0 ? 'Seleccionar categorías' : 'Agregar más categorías' }}
        </UButton>

        <template #content>
          <div class="p-2 space-y-2">
            <UInput
              v-model="categorySearch"
              placeholder="Buscar categoría..."
              icon="i-lucide-search"
              size="sm"
              class="w-full"
              autofocus
            />
            <div class="max-h-72 overflow-y-auto space-y-0.5">
              <button
                v-for="cat in filteredAvailableCategories"
                :key="cat.id"
                type="button"
                class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-left text-sm hover:bg-elevated transition-colors"
                @click="toggleCategory(cat.id)"
              >
                <span class="material-symbols-outlined text-base text-muted">{{ cat.icon }}</span>
                <span class="flex-1 truncate">{{ cat.name }}</span>
                <UIcon name="i-lucide-plus" class="size-3.5 text-muted" />
              </button>
              <div
                v-if="filteredAvailableCategories.length === 0"
                class="px-2 py-4 text-center text-xs text-muted italic"
              >
                <template v-if="categorySearch">Sin coincidencias para «{{ categorySearch }}»</template>
                <template v-else>Todas las categorías ya están seleccionadas</template>
              </div>
            </div>
          </div>
        </template>
      </UPopover>

      <!-- All taken -->
      <UAlert
        v-else
        color="success"
        variant="subtle"
        icon="i-lucide-circle-check"
        title="Todas las categorías están seleccionadas"
        description="Has incluido las 39 categorías disponibles. Considera quitar las que no apliquen para mejorar la relevancia en filtros."
      />

      <!-- Action: clear all -->
      <div v-if="store.selectedCategories.length > 0" class="flex justify-end">
        <UButton
          icon="i-lucide-x"
          color="neutral"
          variant="ghost"
          size="xs"
          @click="store.selectedCategories = []"
        >
          Quitar todas
        </UButton>
      </div>
    </UCard>

    <!-- Empty State Warning -->
    <UAlert
      v-if="store.selectedCategories.length === 0"
      icon="i-lucide-triangle-alert"
      color="warning"
      variant="subtle"
      title="Sin categorías seleccionadas"
      description="Se recomienda seleccionar al menos una categoría para mejorar la visibilidad del tour en los resultados de búsqueda."
    />

    <!-- Tags catalog (CRUD + per-tour selection) -->
    <UCard :ui="{ body: 'p-4 sm:p-4 space-y-4' }">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
            <UIcon name="i-lucide-tag" class="size-5" />
          </div>
          <div>
            <h4 class="text-base font-bold">Etiquetas <span class="text-muted font-normal text-xs">(opcional)</span></h4>
            <p class="text-xs text-muted">Las etiquetas refinan la búsqueda del cliente más allá de las categorías. Actívalas si tu tour tiene un perfil particular.</p>
          </div>
        </div>
        <USwitch
          v-model="tagsEnabled"
          color="primary"
          size="md"
          :label="tagsEnabled ? 'Activadas' : 'Desactivadas'"
        />
      </div>

      <!-- Contenido del bloque de tags — solo si el toggle está ON -->
      <div v-if="tagsEnabled" class="space-y-4 pt-2 border-t border-default">
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <UBadge color="primary" variant="subtle" size="sm" icon="i-lucide-check">
            {{ store.selectedTags.length }} seleccionada{{ store.selectedTags.length === 1 ? '' : 's' }}
          </UBadge>
          <UButton
            icon="i-lucide-plus"
            color="primary"
            size="sm"
            @click="openTagModal()"
          >
            Nueva etiqueta
          </UButton>
        </div>

        <UInput
          v-model="tagSearch"
          placeholder="Buscar etiqueta..."
          icon="i-lucide-search"
          size="md"
          class="w-full"
        />

        <!-- Loading -->
        <div v-if="loadingTags" class="p-6 text-center text-sm text-muted border-2 border-dashed border-default rounded-xl flex items-center justify-center gap-2">
          <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
          Cargando etiquetas...
        </div>

        <!-- Empty -->
        <UAlert
          v-else-if="filteredTags.length === 0"
          color="neutral"
          variant="subtle"
          icon="i-lucide-tag"
          :title="tagSearch ? 'Sin coincidencias' : 'Sin etiquetas'"
          :description="tagSearch ? `No hay etiquetas que coincidan con «${tagSearch}». Click en \'Nueva etiqueta\' para crearla.` : 'No hay etiquetas todavía. Click en \'Nueva etiqueta\' para crear la primera.'"
        />

        <!-- Chips -->
        <div v-else class="flex flex-wrap gap-2">
          <div
            v-for="tag in filteredTags"
            :key="tag.id"
            class="group inline-flex items-center rounded-full border transition-all overflow-hidden"
            :class="store.selectedTags.includes(tag.id)
              ? 'bg-primary border-primary text-white shadow-sm shadow-primary/20'
              : 'bg-default border-default text-default hover:border-primary/40'"
          >
            <button
              type="button"
              class="pl-3 pr-2 py-1.5 text-xs font-bold"
              @click="toggleTag(tag.id)"
            >
              {{ tag.name }}
            </button>
            <UButton
              icon="i-lucide-pencil"
              :color="store.selectedTags.includes(tag.id) ? 'neutral' : 'primary'"
              variant="ghost"
              size="xs"
              class="opacity-0 group-hover:opacity-100 transition-opacity"
              :title="`Editar ${tag.name}`"
              @click="openTagModal(tag)"
            />
            <UButton
              icon="i-lucide-trash-2"
              color="error"
              variant="ghost"
              size="xs"
              class="opacity-0 group-hover:opacity-100 transition-opacity mr-1"
              :title="`Eliminar ${tag.name}`"
              @click="deleteTag(tag)"
            />
          </div>
        </div>
      </div>

      <!-- Hint when toggle off -->
      <UAlert
        v-else
        color="neutral"
        variant="subtle"
        icon="i-lucide-info"
        title="Etiquetas desactivadas"
        description="La mayoría de tours funcionan bien solo con categorías. Activa este bloque solo si tu tour tiene un perfil particular (fotográfico, místico, premium, etc.)."
      />
    </UCard>

    <!-- Tag editor modal -->
    <UModal
      :open="tagModalOpen"
      :ui="{ content: 'max-w-md' }"
      :dismissible="!tagSaving"
      @update:open="(v) => !v && closeTagModal()"
    >
      <template #content>
        <div class="bg-default rounded-lg flex flex-col max-h-[90vh]">
          <div class="px-6 py-4 border-b border-default flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                <UIcon :name="editingTag?.id ? 'i-lucide-pencil' : 'i-lucide-tag'" class="size-5 text-primary" />
              </div>
              <h3 class="text-lg font-bold">{{ editingTag?.id ? 'Editar etiqueta' : 'Nueva etiqueta' }}</h3>
            </div>
            <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" :disabled="tagSaving" @click="closeTagModal" />
          </div>

          <div class="flex-1 overflow-y-auto p-6 space-y-3">
            <p class="text-xs text-muted">
              Ingresa el nombre por idioma. Si dejas un idioma vacío usará el español como fallback.
            </p>

            <UFormField
              v-for="lang in tagLanguages"
              :key="lang.code"
              :label="`${lang.label} (${lang.code})`"
              :required="lang.code === 'ES'"
            >
              <UInput
                v-model="tagForm.translations[lang.code]"
                :placeholder="lang.code === 'ES' ? 'Ej. Uros, Sillustani, Foto Tour' : ''"
                class="w-full"
              />
            </UFormField>

            <UFormField class="pt-2">
              <USwitch v-model="tagForm.active" color="primary" label="Activa (visible en el sitio)" />
            </UFormField>
          </div>

          <div class="px-6 py-4 bg-elevated/30 border-t border-default flex justify-end gap-2 shrink-0">
            <UButton color="neutral" variant="ghost" :disabled="tagSaving" @click="closeTagModal">Cancelar</UButton>
            <UButton
              color="primary"
              :icon="editingTag?.id ? 'i-lucide-save' : 'i-lucide-plus'"
              :loading="tagSaving"
              :disabled="!tagForm.translations.ES"
              @click="submitTag"
            >
              {{ editingTag?.id ? 'Guardar cambios' : 'Crear etiqueta' }}
            </UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useAuthStore } from '~/stores/auth'
import { ref, reactive, computed, onMounted, watch } from 'vue'

const store = useTourWizardStore()
const config = useRuntimeConfig()
const toast = useToast()
const { confirm } = useConfirm()

type Category = { id: number; name: string; description: string; icon: string; code?: string }

const categorySearch = ref('')
const categories = ref<Category[]>([])
const loadingCategories = ref(false)

// Tags toggle — OFF por default. Si el tour ya tiene tags asignados al cargar,
// auto-activar para no esconder la selección existente.
const tagsEnabled = ref(false)
watch(
  () => store.selectedTags.length,
  (count) => { if (count > 0) tagsEnabled.value = true },
  { immediate: true },
)

const normalize = (s: string) => (s || '')
  .toLowerCase()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')

// Resolve an icon for a backend category based on code or name keywords
const iconFor = (code: string, name: string): string => {
  const slug = normalize(code || name).replace(/\s+/g, '-')
  if (iconByCode[slug]) return iconByCode[slug]
  const norm = normalize(name)
  for (const [keyword, icon] of Object.entries(iconByKeyword)) {
    if (norm.includes(keyword)) return icon
  }
  return 'category'
}

const filteredCategories = computed(() => {
  const q = normalize(categorySearch.value.trim())
  if (!q) return categories.value
  return categories.value.filter(c =>
    normalize(c.name).includes(q) || normalize(c.description).includes(q)
  )
})

const selectedCategoriesList = computed(() =>
  categories.value.filter(c => store.selectedCategories.includes(c.id))
)

// Categorías NO seleccionadas (las que aparecen en el dropdown)
const availableCategories = computed(() =>
  categories.value.filter(c => !store.selectedCategories.includes(c.id))
)

const filteredAvailableCategories = computed(() => {
  const q = normalize(categorySearch.value.trim())
  if (!q) return availableCategories.value
  return availableCategories.value.filter(c =>
    normalize(c.name).includes(q) || normalize(c.description).includes(q)
  )
})

const toggleCategory = (id: number) => {
  const idx = store.selectedCategories.indexOf(id)
  if (idx === -1) store.selectedCategories.push(id)
  else store.selectedCategories.splice(idx, 1)
}

const fetchCategories = async () => {
  loadingCategories.value = true
  try {
    const response: any = await $fetch(`${config.public.apiUrl}/categories`, {
      params: { per_page: 200, sort_by: 'id', sort_order: 'asc' },
    })
    const list = response?.data || []
    categories.value = list.map((c: any): Category => ({
      id: c.id,
      name: c.name || c.code || 'Categoría',
      description: c.description || '',
      icon: iconFor(c.code || '', c.name || ''),
      code: c.code,
    }))
  } catch (err) {
    console.error('[Step7] Error loading categories:', err)
  } finally {
    loadingCategories.value = false
  }
}

onMounted(fetchCategories)

// Icon lookup by code (most reliable — backend codes are stable)
const iconByCode: Record<string, string> = {
  'turismo-cultural': 'account_balance',
  'turismo-vivencial': 'diversity_3',
  'turismo-rural': 'agriculture',
  'turismo-mistico': 'self_improvement',
  'turismo-historico': 'history_edu',
  'turismo-religioso': 'church',
  'turismo-arqueologico': 'temple_hindu',
  'turismo-etnografico': 'groups',
  'turismo-naturaleza': 'nature_people',
  'turismo-aventura': 'hiking',
  'ecoturismo': 'eco',
  'turismo-montana': 'landscape',
  'turismo-trekking': 'hiking',
  'turismo-aves': 'flutter_dash',
  'turismo-fotografico': 'photo_camera',
  'turismo-cientifico': 'biotech',
  'turismo-sol-playa': 'beach_access',
  'turismo-termal': 'hot_tub',
  'turismo-spa': 'spa',
  'turismo-romantico': 'favorite',
  'turismo-familiar': 'family_restroom',
  'turismo-tematico': 'theater_comedy',
  'turismo-urbano': 'location_city',
  'turismo-gastronomico': 'restaurant',
  'turismo-compras': 'shopping_bag',
  'turismo-eventos': 'event',
  'turismo-festividades': 'celebration',
  'turismo-musical': 'music_note',
  'turismo-cinematografico': 'movie',
  'turismo-educativo': 'school',
  'turismo-academico': 'menu_book',
  'turismo-idiomatico': 'translate',
  'turismo-lgbtq': 'diversity_1',
  'turismo-corporativo': 'business_center',
  'turismo-negocios': 'monitoring',
  'turismo-incentivos': 'workspace_premium',
  'turismo-ferroviario': 'train',
  'turismo-bicicleta': 'directions_bike',
  'turismo-deportivo': 'sports_soccer',
}

// Fallback: match by keyword in name if code is not recognized
const iconByKeyword: Record<string, string> = {
  cultural: 'account_balance',
  vivencial: 'diversity_3',
  rural: 'agriculture',
  mistic: 'self_improvement',
  histor: 'history_edu',
  religi: 'church',
  arqueolog: 'temple_hindu',
  etnograf: 'groups',
  naturaleza: 'nature_people',
  aventura: 'hiking',
  ecoturismo: 'eco',
  montana: 'landscape',
  trekking: 'hiking',
  ave: 'flutter_dash',
  fotograf: 'photo_camera',
  cientif: 'biotech',
  playa: 'beach_access',
  termal: 'hot_tub',
  spa: 'spa',
  romant: 'favorite',
  familiar: 'family_restroom',
  tematic: 'theater_comedy',
  urbano: 'location_city',
  gastron: 'restaurant',
  compras: 'shopping_bag',
  evento: 'event',
  festivid: 'celebration',
  musical: 'music_note',
  cine: 'movie',
  educ: 'school',
  academ: 'menu_book',
  idiom: 'translate',
  lgbtq: 'diversity_1',
  corporativ: 'business_center',
  negocio: 'monitoring',
  incentiv: 'workspace_premium',
  ferrov: 'train',
  bicicleta: 'directions_bike',
  deport: 'sports_soccer',
}

// ===== Tags catalog (fetched from /api/tags) =====
type Tag = { id: number; slug: string; name: string; translations: Record<string, string>; active: boolean }

const tags = ref<Tag[]>([])
const loadingTags = ref(false)
const tagSearch = ref('')

const tagLanguages = [
  { code: 'ES', label: 'Español' },
  { code: 'EN', label: 'English' },
  { code: 'PT', label: 'Português' },
  { code: 'FR', label: 'Français' },
  { code: 'DE', label: 'Deutsch' },
  { code: 'IT', label: 'Italiano' },
]

const filteredTags = computed(() => {
  const q = normalize(tagSearch.value.trim())
  if (!q) return tags.value
  return tags.value.filter(t =>
    normalize(t.name).includes(q)
    || normalize(t.slug).includes(q)
    || Object.values(t.translations || {}).some(n => normalize(n || '').includes(q))
  )
})

const fetchTags = async () => {
  loadingTags.value = true
  try {
    const response: any = await $fetch(`${config.public.apiUrl}/tags`, {
      params: { include_inactive: 1 },
    })
    tags.value = (response?.data || []).map((t: any) => ({
      id: t.id,
      slug: t.slug,
      name: t.name,
      translations: t.translations || {},
      active: t.active !== false,
    }))
  } catch (err) {
    console.error('[Step7] Error loading tags:', err)
  } finally {
    loadingTags.value = false
  }
}

onMounted(fetchTags)

const toggleTag = (id: number) => {
  const idx = store.selectedTags.indexOf(id)
  if (idx === -1) store.selectedTags.push(id)
  else store.selectedTags.splice(idx, 1)
}

// ===== Tag CRUD modal =====
const tagModalOpen = ref(false)
const editingTag = ref<Tag | null>(null)
const tagSaving = ref(false)

const emptyTranslations = (): Record<string, string> => ({ ES: '', EN: '', PT: '', FR: '', DE: '', IT: '' })

const tagForm = reactive<{ translations: Record<string, string>; active: boolean }>({
  translations: emptyTranslations(),
  active: true,
})

const openTagModal = (tag?: Tag) => {
  editingTag.value = tag || null
  if (tag) {
    tagForm.translations = { ...emptyTranslations(), ...(tag.translations || {}) }
    tagForm.active = tag.active
  } else {
    tagForm.translations = emptyTranslations()
    tagForm.active = true
  }
  tagModalOpen.value = true
}

const closeTagModal = () => {
  tagModalOpen.value = false
  editingTag.value = null
}

const submitTag = async () => {
  const auth = useAuthStore()
  if (!auth.token) {
    toast.add({
      title: 'Sesión expirada',
      description: 'Vuelve a iniciar sesión para continuar.',
      icon: 'i-lucide-log-out',
      color: 'error',
    })
    return
  }
  // Trim values
  const translations: Record<string, string> = {}
  for (const [code, name] of Object.entries(tagForm.translations)) {
    if ((name || '').trim()) translations[code] = name.trim()
  }
  if (!translations.ES) {
    toast.add({
      title: 'Falta el nombre en español',
      description: 'El idioma principal es obligatorio.',
      icon: 'i-lucide-info',
      color: 'warning',
    })
    return
  }

  tagSaving.value = true
  try {
    const url = editingTag.value?.id
      ? `${config.public.apiUrl}/admin/tags/${editingTag.value.id}`
      : `${config.public.apiUrl}/admin/tags`
    const response: any = await $fetch(url, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${auth.token}`, 'Accept': 'application/json' },
      body: {
        translations,
        active: tagForm.active,
      },
    })
    if (response?.success && response.data) {
      const saved: Tag = {
        id: response.data.id,
        slug: response.data.slug,
        name: response.data.name,
        translations: response.data.translations || {},
        active: response.data.active !== false,
      }
      if (editingTag.value?.id) {
        const idx = tags.value.findIndex(t => t.id === editingTag.value!.id)
        if (idx !== -1) tags.value.splice(idx, 1, saved)
      } else {
        tags.value.push(saved)
        // Auto-select the just-created tag
        if (!store.selectedTags.includes(saved.id)) store.selectedTags.push(saved.id)
      }
      closeTagModal()
    }
  } catch (err: any) {
    console.error('[Step7] Tag save failed:', err)
    toast.add({
      title: 'Error al guardar etiqueta',
      description: err?.data?.message || err?.message || 'Error desconocido',
      icon: 'i-lucide-triangle-alert',
      color: 'error',
    })
  } finally {
    tagSaving.value = false
  }
}

const deleteTag = async (tag: Tag) => {
  const ok = await confirm({
    title: 'Eliminar etiqueta',
    description: `Vas a eliminar "${tag.name}". Se quitará de todos los tours que la usen.`,
    confirmLabel: 'Eliminar',
    confirmColor: 'error',
    confirmIcon: 'i-lucide-trash-2',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  const auth = useAuthStore()
  try {
    await $fetch(`${config.public.apiUrl}/admin/tags/${tag.id}/delete`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${auth.token}`, 'Accept': 'application/json' },
    })
    tags.value = tags.value.filter(t => t.id !== tag.id)
    store.selectedTags = store.selectedTags.filter(id => id !== tag.id)
    toast.add({
      title: 'Etiqueta eliminada',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
  } catch (err: any) {
    console.error('[Step7] Tag delete failed:', err)
    toast.add({
      title: 'Error al eliminar',
      description: err?.data?.message || err?.message || 'Error desconocido',
      icon: 'i-lucide-triangle-alert',
      color: 'error',
    })
  }
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.5);
}

.material-symbols-outlined.filled {
  font-family: 'Material Symbols Outlined' !important;
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  display: inline-block;
  line-height: 1;
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
