<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-2">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Idiomas</h1>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
        >
          <span class="material-symbols-outlined">add</span>
          Nuevo Idioma
        </button>
      </div>
      <p class="text-slate-600 dark:text-slate-400">Gestiona los idiomas disponibles para traducciones</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-blue-600 dark:text-blue-400">language</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Idiomas</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ languages.length }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-green-600 dark:text-green-400">translate</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Códigos ISO</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ languages.filter(l => l.code).length }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <span class="material-symbols-outlined text-3xl text-purple-600 dark:text-purple-400">public</span>
          </div>
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Regiones</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ languages.length }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <div class="animate-spin inline-block w-12 h-12 border-4 border-primary border-t-transparent rounded-full mb-4"></div>
      <p class="text-slate-600 dark:text-slate-400">Cargando idiomas...</p>
    </div>

    <!-- Languages Grid -->
    <div v-else-if="languages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="language in languages"
        :key="language.id"
        class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 hover:border-primary/50 dark:hover:border-primary/50 transition-all hover:shadow-lg group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
              <span class="material-symbols-outlined text-2xl text-primary">language</span>
            </div>
            <div>
              <h3 class="font-bold text-lg text-slate-900 dark:text-white">{{ language.country }}</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">Código: {{ language.code }}</p>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            ID: {{ language.id }}
          </div>
          <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="editLanguage(language)"
              class="p-2 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors"
              title="Editar"
            >
              <span class="material-symbols-outlined text-base">edit</span>
            </button>
            <button
              @click="deleteLanguage(language)"
              class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              title="Eliminar"
            >
              <span class="material-symbols-outlined text-base">delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4">language</span>
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No hay idiomas</h3>
      <p class="text-slate-600 dark:text-slate-400 mb-6">Crea tu primer idioma para habilitar traducciones</p>
      <button
        @click="showCreateModal = true"
        class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
      >
        <span class="material-symbols-outlined">add</span>
        Nuevo Idioma
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <LanguageFormModal
      v-if="showCreateModal || editingLanguage"
      :language="editingLanguage"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const loading = ref(true)
const languages = ref([])
const showCreateModal = ref(false)
const editingLanguage = ref(null)

const loadLanguages = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/languages`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar idiomas')

    const data = await response.json()
    languages.value = data.data || data
  } catch (error) {
    console.error('Error loading languages:', error)
  } finally {
    loading.value = false
  }
}

const editLanguage = async (language: any) => {
  try {
    const response = await fetch(`${config.public.apiUrl}/languages/${language.id}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar idioma')

    const data = await response.json()
    editingLanguage.value = data.data
  } catch (error) {
    console.error('Error loading language:', error)
    alert('Error al cargar los datos del idioma')
  }
}

const deleteLanguage = async (language: any) => {
  if (!confirm(`¿Eliminar el idioma "${language.country}"?`)) return

  try {
    const response = await fetch(`${config.public.apiUrl}/admin/languages/${language.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al eliminar idioma')

    await loadLanguages()
  } catch (error) {
    console.error('Error deleting language:', error)
    alert('Error al eliminar el idioma. Puede que tenga traducciones asociadas.')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingLanguage.value = null
}

const handleSaved = () => {
  closeModal()
  loadLanguages()
}

onMounted(() => {
  loadLanguages()
})
</script>
