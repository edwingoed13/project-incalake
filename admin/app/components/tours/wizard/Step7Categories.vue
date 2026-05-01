<template>
  <div class="flex flex-col gap-10 pb-20">
    <!-- Header Section -->
    <section class="glass-card p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 relative overflow-hidden group">
      <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
        <span class="material-symbols-outlined text-[120px] fill-1 text-primary">verified</span>
      </div>
      
      <div class="relative z-10 max-w-2xl">
        <div class="flex items-center gap-3 mb-4">
          <div class="size-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
            <span class="material-symbols-outlined filled">verified</span>
          </div>
          <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Clasificación de Actividad</h3>
        </div>
        <p class="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
          Selecciona las categorías que mejor definan esta experiencia. Esto ayuda a los viajeros a encontrar tu tour mediante filtros de búsqueda inteligentes.
        </p>
      </div>
    </section>

    <!-- Categories -->
    <section class="space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-3 px-2">
        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Categorías Disponibles</h4>
        <div class="flex items-center gap-2">
          <button
            v-if="store.selectedCategories.length > 0"
            type="button"
            @click="store.selectedCategories = []"
            class="text-[10px] font-bold text-slate-400 hover:text-rose-500 transition-colors"
          >
            Limpiar
          </button>
          <div class="text-[10px] font-bold text-primary bg-primary/5 px-3 py-1 rounded-full">
            {{ store.selectedCategories.length }} / {{ categories.length }}
          </div>
        </div>
      </div>

      <!-- Search input -->
      <div class="relative">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
        <input
          v-model="categorySearch"
          type="search"
          placeholder="Buscar categoría (ej. aventura, naturaleza, cultural)"
          class="w-full pl-12 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all outline-none"
        />
      </div>

      <!-- Selected chips (pinned) -->
      <div v-if="selectedCategoriesList.length > 0" class="p-4 rounded-2xl bg-primary/5 border border-primary/10 space-y-2">
        <div class="text-[10px] font-black uppercase tracking-widest text-primary/70">Seleccionadas</div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="cat in selectedCategoriesList"
            :key="'sel-' + cat.id"
            type="button"
            @click="toggleCategory(cat.id)"
            class="inline-flex items-center gap-1.5 pl-3 pr-2 py-1.5 rounded-full bg-primary text-white text-xs font-bold shadow-sm hover:bg-primary/90 transition-all"
          >
            <span class="material-symbols-outlined text-sm filled">{{ cat.icon }}</span>
            <span>{{ cat.name }}</span>
            <span class="material-symbols-outlined text-sm hover:scale-110 transition-transform">close</span>
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loadingCategories" class="p-6 text-center text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
        <span class="material-symbols-outlined animate-spin text-lg align-middle mr-2">progress_activity</span>
        Cargando categorías...
      </div>

      <!-- All categories as chips (wraps naturally, no truncation) -->
      <div v-else-if="filteredCategories.length > 0" class="flex flex-wrap gap-2">
        <button
          v-for="cat in filteredCategories"
          :key="cat.id"
          type="button"
          @click="toggleCategory(cat.id)"
          :title="cat.description"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-full border text-xs font-bold transition-all select-none"
          :class="store.selectedCategories.includes(cat.id)
            ? 'bg-primary border-primary text-white shadow-sm shadow-primary/20'
            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-primary/40 hover:text-primary'"
        >
          <span class="material-symbols-outlined text-base" :class="store.selectedCategories.includes(cat.id) ? 'filled' : ''">{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>

      <!-- No results -->
      <div v-else class="p-6 text-center text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
        <template v-if="categorySearch">No se encontraron categorías para «{{ categorySearch }}»</template>
        <template v-else>No hay categorías disponibles</template>
      </div>
    </section>

    <!-- Empty State Warning -->
    <Transition name="fade">
      <div v-if="store.selectedCategories.length === 0" class="p-8 bg-amber-500/5 rounded-3xl border border-dashed border-amber-500/20 text-center space-y-4">
        <div class="size-16 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto mb-2 animate-bounce">
          <span class="material-symbols-outlined text-3xl filled">warning</span>
        </div>
        <div class="space-y-1">
          <h4 class="text-lg font-bold text-amber-700 dark:text-amber-400">Sin Categorías Seleccionadas</h4>
          <p class="text-sm text-slate-500 max-w-md mx-auto italic">Se recomienda seleccionar al menos una categoría para mejorar la visibilidad del tour en los resultados de búsqueda.</p>
        </div>
      </div>
    </Transition>

    <!-- Tags catalog (CRUD + per-tour selection) -->
    <section class="glass-card p-8 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="size-10 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center">
            <span class="material-symbols-outlined filled">label</span>
          </div>
          <div>
            <h4 class="text-xl font-bold dark:text-white">Etiquetas</h4>
            <p class="text-xs text-slate-500">Marca las que apliquen a este tour. Crea nuevas si no existen.</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div class="text-[10px] font-bold text-violet-500 bg-violet-500/5 px-3 py-1 rounded-full">
            {{ store.selectedTags.length }} seleccionadas
          </div>
          <button
            type="button"
            @click="openTagModal()"
            class="px-3 py-2 bg-violet-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-violet-600 transition-all flex items-center gap-1.5"
          >
            <span class="material-symbols-outlined text-sm">add</span>
            Nueva etiqueta
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="relative">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
        <input
          v-model="tagSearch"
          type="search"
          placeholder="Buscar etiqueta..."
          class="w-full pl-12 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none"
        />
      </div>

      <!-- Loading -->
      <div v-if="loadingTags" class="p-6 text-center text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
        <span class="material-symbols-outlined animate-spin text-lg align-middle mr-2">progress_activity</span>
        Cargando etiquetas...
      </div>

      <!-- Empty -->
      <div v-else-if="filteredTags.length === 0" class="p-6 text-center text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
        <template v-if="tagSearch">No hay coincidencias para «{{ tagSearch }}». Click en "Nueva etiqueta" para crearla.</template>
        <template v-else>No hay etiquetas todavía. Click en "Nueva etiqueta" para crear la primera.</template>
      </div>

      <!-- Chips -->
      <div v-else class="flex flex-wrap gap-2">
        <div
          v-for="tag in filteredTags"
          :key="tag.id"
          class="group inline-flex items-center gap-1 rounded-full border transition-all"
          :class="store.selectedTags.includes(tag.id)
            ? 'bg-violet-500 border-violet-500 text-white shadow-sm shadow-violet-500/20'
            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300'"
        >
          <button
            type="button"
            @click="toggleTag(tag.id)"
            class="pl-3 pr-1.5 py-2 text-xs font-bold"
          >
            {{ tag.name }}
          </button>
          <button
            type="button"
            @click="openTagModal(tag)"
            :title="`Editar ${tag.name}`"
            class="p-1.5 opacity-0 group-hover:opacity-100 transition-opacity rounded-full hover:bg-black/10"
          >
            <span class="material-symbols-outlined text-[14px]">edit</span>
          </button>
          <button
            type="button"
            @click="deleteTag(tag)"
            :title="`Eliminar ${tag.name}`"
            class="p-1.5 mr-1 opacity-0 group-hover:opacity-100 transition-opacity rounded-full hover:bg-rose-500/20"
          >
            <span class="material-symbols-outlined text-[14px]" :class="store.selectedTags.includes(tag.id) ? 'text-white' : 'text-rose-500'">delete</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Tag editor modal -->
    <div v-if="tagModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="closeTagModal">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-lg font-bold dark:text-white">{{ editingTag?.id ? 'Editar etiqueta' : 'Nueva etiqueta' }}</h3>
          <button type="button" @click="closeTagModal" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-xs text-slate-500">Ingresa el nombre por idioma. Si dejas un idioma vacío usará el español como fallback.</p>
          <div v-for="lang in tagLanguages" :key="lang.code" class="space-y-1">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ lang.label }} ({{ lang.code }})</label>
            <input
              v-model="tagForm.translations[lang.code]"
              type="text"
              :placeholder="lang.code === 'ES' ? 'Ej. Uros, Sillustani, Foto Tour' : ''"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none dark:text-white"
            />
          </div>
          <label class="flex items-center gap-2 pt-2">
            <input v-model="tagForm.active" type="checkbox" class="size-4 text-violet-500 rounded focus:ring-violet-500" />
            <span class="text-sm text-slate-600 dark:text-slate-400">Activa (visible en el sitio)</span>
          </label>
        </div>
        <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
          <button type="button" @click="closeTagModal" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancelar</button>
          <button
            type="button"
            @click="submitTag"
            :disabled="!tagForm.translations.ES || tagSaving"
            class="px-5 py-2 bg-violet-500 text-white rounded-xl text-sm font-bold hover:bg-violet-600 disabled:opacity-50 transition-all flex items-center gap-2"
          >
            <span v-if="tagSaving" class="material-symbols-outlined animate-spin text-sm">sync</span>
            {{ editingTag?.id ? 'Guardar cambios' : 'Crear etiqueta' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { useAuthStore } from '~/stores/auth'
import { ref, reactive, computed, onMounted } from 'vue'

const store = useTourWizardStore()
const config = useRuntimeConfig()

type Category = { id: number; name: string; description: string; icon: string; code?: string }

const categorySearch = ref('')
const categories = ref<Category[]>([])
const loadingCategories = ref(false)

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
    alert('Sesión expirada.')
    return
  }
  // Trim values
  const translations: Record<string, string> = {}
  for (const [code, name] of Object.entries(tagForm.translations)) {
    if ((name || '').trim()) translations[code] = name.trim()
  }
  if (!translations.ES) {
    alert('El nombre en español es obligatorio.')
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
    alert('Error al guardar etiqueta: ' + (err?.data?.message || err?.message || 'desconocido'))
  } finally {
    tagSaving.value = false
  }
}

const deleteTag = async (tag: Tag) => {
  if (!confirm(`¿Eliminar la etiqueta "${tag.name}"? Se quitará de todos los tours que la usen.`)) return
  const auth = useAuthStore()
  try {
    await $fetch(`${config.public.apiUrl}/admin/tags/${tag.id}/delete`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${auth.token}`, 'Accept': 'application/json' },
    })
    tags.value = tags.value.filter(t => t.id !== tag.id)
    store.selectedTags = store.selectedTags.filter(id => id !== tag.id)
  } catch (err: any) {
    console.error('[Step7] Tag delete failed:', err)
    alert('Error al eliminar etiqueta: ' + (err?.data?.message || err?.message || 'desconocido'))
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
