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
            {{ store.selectedCategories.length }} / {{ mockCategories.length }}
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

      <!-- All categories as chips (wraps naturally, no truncation) -->
      <div v-if="filteredCategories.length > 0" class="flex flex-wrap gap-2">
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
        No se encontraron categorías para «{{ categorySearch }}»
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

    <!-- Secondary Management (Optional Tags/Keywords) -->
    <section class="glass-card p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 space-y-6">
       <div class="flex items-center gap-3">
          <div class="size-10 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center">
            <span class="material-symbols-outlined filled">label</span>
          </div>
          <h4 class="text-xl font-bold dark:text-white">Etiquetas Sugeridas</h4>
       </div>
       <div class="flex flex-wrap gap-2">
          <button 
            v-for="tag in suggestedTags" 
            :key="tag"
            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all"
            :class="selectedTags.includes(tag) ? 'bg-violet-500 border-violet-500 text-white' : 'border-slate-200 dark:border-slate-800 text-slate-400 hover:border-violet-300'"
            @click="toggleTag(tag)"
          >
            {{ tag }}
          </button>
       </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { ref, computed } from 'vue'

const store = useTourWizardStore()

const categorySearch = ref('')

const normalize = (s: string) => s
  .toLowerCase()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')

const filteredCategories = computed(() => {
  const q = normalize(categorySearch.value.trim())
  if (!q) return mockCategories
  return mockCategories.filter(c =>
    normalize(c.name).includes(q) || normalize(c.description).includes(q)
  )
})

const selectedCategoriesList = computed(() =>
  mockCategories.filter(c => store.selectedCategories.includes(c.id))
)

const toggleCategory = (id: number) => {
  const idx = store.selectedCategories.indexOf(id)
  if (idx === -1) store.selectedCategories.push(id)
  else store.selectedCategories.splice(idx, 1)
}

const mockCategories = [
  { id: 1, name: 'Turismo Cultural', icon: 'account_balance', description: 'Enfoque en la cultura y patrimonio.' },
  { id: 2, name: 'Turismo Vivencial', icon: 'diversity_3', description: 'Experiencias directas con comunidades.' },
  { id: 3, name: 'Turismo Rural', icon: 'agriculture', description: 'Actividades en el entorno rural.' },
  { id: 4, name: 'Turismo Místico', icon: 'self_improvement', description: 'Rituales y espiritualidad ancestral.' },
  { id: 5, name: 'Turismo Histórico', icon: 'history_edu', description: 'Visitas a sitios de relevancia histórica.' },
  { id: 6, name: 'Turismo Religioso', icon: 'church', description: 'Peregrinaciones y fe religiosa.' },
  { id: 7, name: 'Turismo Arqueológico', icon: 'temple_hindu', description: 'Ruinas y monumentos antiguos.' },
  { id: 8, name: 'Turismo Etnográfico', icon: 'groups', description: 'Estudio y vivencia de costumbres etnográficas.' },
  { id: 9, name: 'Turismo de Naturaleza', icon: 'nature_people', description: 'Observación y disfrute de la naturaleza.' },
  { id: 10, name: 'Turismo de Aventura', icon: 'hiking', description: 'Deportes extremos y desafíos físicos.' },
  { id: 11, name: 'Ecoturismo', icon: 'eco', description: 'Turismo ecológico y sostenible.' },
  { id: 12, name: 'Turismo de Montaña', icon: 'landscape', description: 'Actividades en entornos montañosos.' },
  { id: 13, name: 'Turismo de Trekking', icon: 'hiking', description: 'Caminatas por senderos naturales.' },
  { id: 14, name: 'Turismo de Observación de Aves', icon: 'flutter_dash', description: 'Birdwatching y avistamiento faunístico.' },
  { id: 15, name: 'Turismo Fotográfico', icon: 'photo_camera', description: 'Enfoque en capturar paisajes y momentos.' },
  { id: 16, name: 'Turismo Científico', icon: 'biotech', description: 'Investigación y trasfondo científico.' },
  { id: 17, name: 'Turismo de Sol y Playa', icon: 'beach_access', description: 'Relajación en costas y balnearios.' },
  { id: 18, name: 'Turismo Termal', icon: 'hot_tub', description: 'Baños en aguas termales curativas.' },
  { id: 19, name: 'Turismo de Spa', icon: 'spa', description: 'Bienestar y tratamientos estéticos.' },
  { id: 20, name: 'Turismo Romántico', icon: 'favorite', description: 'Viajes de pareja y lunas de miel.' },
  { id: 21, name: 'Turismo Familiar', icon: 'family_restroom', description: 'Actividades para todas las edades.' },
  { id: 22, name: 'Turismo Temático', icon: 'theater_comedy', description: 'Centrado en un tema específico o hobby.' },
  { id: 23, name: 'Turismo Urbano', icon: 'location_city', description: 'Exploración de metrópolis y entornos urbanos.' },
  { id: 24, name: 'Turismo Gastronómico', icon: 'restaurant', description: 'Degustación de cocina local y tradicional.' },
  { id: 25, name: 'Turismo de Compras', icon: 'shopping_bag', description: 'Visitas a centros comerciales y ferias.' },
  { id: 26, name: 'Turismo de Eventos', icon: 'event', description: 'Asistencia a ferias, congresos o eventos.' },
  { id: 27, name: 'Turismo de Festividades', icon: 'celebration', description: 'Participación en fiestas locales y carnavales.' },
  { id: 28, name: 'Turismo Musical', icon: 'music_note', description: 'Conciertos, festivales y tours musicales.' },
  { id: 29, name: 'Turismo Cinematográfico', icon: 'movie', description: 'Visitas a locaciones de películas famosas.' },
  { id: 30, name: 'Turismo Educativo', icon: 'school', description: 'Aprendizaje y visitas académicas.' },
  { id: 31, name: 'Turismo Académico', icon: 'menu_book', description: 'Congresos y simposios universitarios.' },
  { id: 32, name: 'Turismo Idiomático', icon: 'translate', description: 'Inmersión para aprender nuevos idiomas.' },
  { id: 33, name: 'Turismo LGBTQ+ Friendly', icon: 'diversity_1', description: 'Destinos y servicios inclusivos.' },
  { id: 34, name: 'Turismo Corporativo', icon: 'business_center', description: 'Viajes de empresas y convenciones.' },
  { id: 35, name: 'Turismo de Negocios', icon: 'monitoring', description: 'Cierre de contratos y reuniones comerciales.' },
  { id: 36, name: 'Turismo de Incentivos', icon: 'workspace_premium', description: 'Premios corporativos para empleados.' },
  { id: 37, name: 'Turismo Ferroviario', icon: 'train', description: 'Viajes escénicos en tren.' },
  { id: 38, name: 'Turismo en Bicicleta', icon: 'directions_bike', description: 'Cicloturismo y rutas en bici.' },
  { id: 39, name: 'Turismo Deportivo', icon: 'sports_soccer', description: 'Asistencia a partidos o práctica de deportes.' }
]

const suggestedTags = ['Uros', 'Taquile', 'Amantani', 'Sillustani', 'Tierra de los Incas', 'Atractivos Cercanos', 'Turismo Vivencial', 'Foto Tour']
const selectedTags = ref<string[]>([])

const toggleTag = (tag: string) => {
  const index = selectedTags.value.indexOf(tag)
  if (index === -1) {
    selectedTags.value.push(tag)
  } else {
    selectedTags.value.splice(index, 1)
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
