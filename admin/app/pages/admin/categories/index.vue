<template>
  <div>
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Categorías</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestión de categorías de tours</p>
      </div>
      <div class="flex gap-3">
        <button @click="showCreateModal = true" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors flex items-center gap-2">
          <span class="material-symbols-outlined text-base">add</span>
          Nueva Categoría
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Categorías</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ categories.length }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">category</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Activas</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ activeCount }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Inactivas</p>
            <p class="text-2xl font-bold text-slate-600 dark:text-slate-400 mt-1">{{ inactiveCount }}</p>
          </div>
          <div class="w-12 h-12 bg-slate-100 dark:bg-slate-900/20 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-400">block</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center border border-slate-200 dark:border-slate-700">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
      <p class="mt-4 text-slate-600 dark:text-slate-400">Cargando categorías...</p>
    </div>

    <!-- Categories Grid -->
    <div v-else-if="categories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="category in categories"
        :key="category.id"
        class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <span class="material-symbols-outlined text-primary text-2xl">{{ category.icon || 'category' }}</span>
            </div>
            <div>
              <h3 class="font-bold text-slate-900 dark:text-white">{{ category.name }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ category.slug }}</p>
            </div>
          </div>
          <span
            :class="category.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'"
            class="px-2 py-1 text-xs font-bold rounded-full"
          >
            {{ category.active ? 'Activa' : 'Inactiva' }}
          </span>
        </div>

        <p v-if="category.description" class="text-sm text-slate-600 dark:text-slate-400 mb-4 line-clamp-2">
          {{ category.description }}
        </p>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
          <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="material-symbols-outlined text-base">tour</span>
            <span>{{ category.tours_count || 0 }} tours</span>
          </div>

          <div class="flex gap-2">
            <button
              @click="editCategory(category)"
              class="p-2 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors"
              title="Editar"
            >
              <span class="material-symbols-outlined text-base">edit</span>
            </button>
            <button
              @click="deleteCategory(category)"
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
      <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4">category</span>
      <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No hay categorías</h3>
      <p class="text-slate-600 dark:text-slate-400 mb-6">Crea tu primera categoría para organizar los tours</p>
      <button
        @click="showCreateModal = true"
        class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
      >
        <span class="material-symbols-outlined">add</span>
        Nueva Categoría
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <CategoryFormModal
      v-if="showCreateModal || editingCategory"
      :category="editingCategory"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const loading = ref(true)
const categories = ref([])
const showCreateModal = ref(false)
const editingCategory = ref(null)

const activeCount = computed(() => categories.value.filter((c: any) => c.active).length)
const inactiveCount = computed(() => categories.value.filter((c: any) => !c.active).length)

const loadCategories = async () => {
  loading.value = true
  try {
    const response = await fetch(`${config.public.apiUrl}/categories`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar categorías')

    const data = await response.json()
    categories.value = data.data || data
  } catch (error) {
    console.error('Error loading categories:', error)
  } finally {
    loading.value = false
  }
}

const editCategory = async (category: any) => {
  try {
    // Fetch full category data with translations
    const response = await fetch(`${config.public.apiUrl}/categories/${category.id}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al cargar categoría')

    const data = await response.json()
    editingCategory.value = data.data
  } catch (error) {
    console.error('Error loading category:', error)
    alert('Error al cargar los datos de la categoría')
  }
}

const deleteCategory = async (category: any) => {
  if (!confirm(`¿Eliminar la categoría "${category.name}"?`)) return

  try {
    const response = await fetch(`${config.public.apiUrl}/admin/categories/${category.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (!response.ok) throw new Error('Error al eliminar categoría')

    await loadCategories()
  } catch (error) {
    console.error('Error deleting category:', error)
    alert('Error al eliminar la categoría. Puede que tenga tours asociados.')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingCategory.value = null
}

const handleSaved = () => {
  closeModal()
  loadCategories()
}

onMounted(() => {
  loadCategories()
})
</script>
