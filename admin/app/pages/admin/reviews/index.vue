<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Reviews & Testimonials</h3>
        <p class="text-slate-500 dark:text-slate-400">{{ stats?.total || 0 }} reviews, {{ stats?.unassigned || 0 }} unassigned.</p>
      </div>
      <button @click="showAddModal = true" class="px-6 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2">
        <span class="material-symbols-outlined">add</span>
        New Review
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-5 gap-3 mb-6">
      <div v-for="stat in statsCards" :key="stat.label" class="glass-card rounded-xl border border-slate-200 dark:border-slate-800 p-4">
        <p class="text-2xl font-black" :class="stat.color">{{ stat.value }}</p>
        <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <!-- Filters -->
      <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-3 bg-white/50 dark:bg-slate-900/50">
        <div class="relative flex-1 min-w-[200px]">
          <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><span class="material-symbols-outlined text-lg">search</span></span>
          <input v-model="searchQuery" @input="debounceSearch" class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm" placeholder="Search name, comment, tour..." />
        </div>
        <select v-model="filterRating" @change="fetchReviews(1)" class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm font-semibold">
          <option value="">All Ratings</option>
          <option value="5">5 Stars</option>
          <option value="4">4 Stars</option>
        </select>
        <select v-model="filterPublished" @change="fetchReviews(1)" class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm font-semibold">
          <option value="">All Status</option>
          <option value="1">Published</option>
          <option value="0">Hidden</option>
        </select>
        <select v-model="filterFeatured" @change="fetchReviews(1)" class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm font-semibold">
          <option value="">All</option>
          <option value="1">Featured</option>
        </select>
      </div>

      <!-- Reviews Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 w-10">#</th>
              <th class="px-4 py-3">Customer</th>
              <th class="px-4 py-3">Review</th>
              <th class="px-4 py-3 w-24">Rating</th>
              <th class="px-4 py-3 w-20">Lang</th>
              <th class="px-4 py-3 w-32">Tour</th>
              <th class="px-4 py-3 w-28 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-if="loading">
              <td colspan="7" class="py-16 text-center"><div class="size-8 border-4 border-primary/20 border-t-primary rounded-full animate-spin mx-auto"></div></td>
            </tr>
            <tr v-else-if="reviews.length === 0">
              <td colspan="7" class="py-16 text-center text-slate-400 text-sm">No reviews found</td>
            </tr>
            <tr v-for="review in reviews" :key="review.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
              <td class="px-4 py-3 text-[10px] text-slate-400 font-mono">{{ review.id }}</td>
              <td class="px-4 py-3">
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ review.name }}</p>
                <p class="text-[10px] text-slate-400">{{ review.review_date }}</p>
              </td>
              <td class="px-4 py-3 max-w-md">
                <p v-if="review.title" class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">{{ review.title }}</p>
                <p class="text-[11px] text-slate-500 truncate">{{ review.comment }}</p>
                <p v-if="review.opinion" class="text-[10px] text-primary font-medium mt-0.5 truncate">{{ review.opinion }}</p>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                  <span v-for="i in review.rating" :key="i" class="material-symbols-outlined text-yellow-400 text-xs" style="font-variation-settings: 'FILL' 1">star</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{ review.language }}</span>
              </td>
              <td class="px-4 py-3">
                <span v-if="review.tour" class="text-[10px] font-bold text-green-600">{{ review.tour.code }}</span>
                <span v-else class="text-[10px] text-orange-500">—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="togglePublished(review)" class="p-1.5 rounded-lg transition-colors" :class="review.published ? 'text-green-500 hover:bg-green-50' : 'text-slate-400 hover:bg-slate-100'" :title="review.published ? 'Hide' : 'Publish'">
                    <span class="material-symbols-outlined text-base">{{ review.published ? 'visibility' : 'visibility_off' }}</span>
                  </button>
                  <button @click="toggleFeatured(review)" class="p-1.5 rounded-lg transition-colors" :class="review.featured ? 'text-primary hover:bg-primary/10' : 'text-slate-400 hover:bg-slate-100'" :title="review.featured ? 'Unfeature' : 'Feature'">
                    <span class="material-symbols-outlined text-base" :style="review.featured ? 'font-variation-settings: FILL 1' : ''">star</span>
                  </button>
                  <button @click="openEditModal(review)" class="p-1.5 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors" title="Edit">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </button>
                  <button @click="confirmDelete(review)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-slate-500">
        <p>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} reviews)</p>
        <div class="flex gap-1">
          <button @click="fetchReviews(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 disabled:opacity-30">
            <span class="material-symbols-outlined text-sm">chevron_left</span>
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="fetchReviews(page)"
            class="w-8 h-8 rounded-lg font-bold text-xs transition-all"
            :class="meta.current_page === page ? 'bg-primary text-white' : 'hover:bg-slate-100'"
          >{{ page }}</button>
          <button @click="fetchReviews(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 disabled:opacity-30">
            <span class="material-symbols-outlined text-sm">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Review Modal -->
    <Teleport to="body">
      <div v-if="showAddModal || editingReview" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ editingReview ? 'Edit Review' : 'Add Review' }}</h3>
            <button @click="closeModal" class="p-1 hover:bg-slate-100 rounded-lg"><span class="material-symbols-outlined text-slate-400">close</span></button>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Name *</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" placeholder="Customer name" />
              </div>
              <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Date</label>
                <input v-model="form.review_date" type="text" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" placeholder="mar. 2026" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Rating *</label>
                <div class="flex gap-1">
                  <button v-for="i in 5" :key="i" @click="form.rating = i" class="p-1">
                    <span class="material-symbols-outlined text-2xl transition-colors" :class="i <= form.rating ? 'text-yellow-400' : 'text-slate-300'" :style="i <= form.rating ? 'font-variation-settings: FILL 1' : ''">star</span>
                  </button>
                </div>
              </div>
              <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Language</label>
                <select v-model="form.language" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm">
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                  <option value="pt">Portuguese</option>
                  <option value="it">Italian</option>
                </select>
              </div>
            </div>

            <div>
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Title</label>
              <input v-model="form.title" type="text" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm" placeholder="Review title" />
            </div>

            <div>
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Comment *</label>
              <textarea v-model="form.comment" rows="4" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm resize-none" placeholder="Customer review..."></textarea>
            </div>

            <div>
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Assign to Tour</label>
              <div class="relative">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-slate-400 text-lg">search</span>
                  <input
                    v-model="tourSearchQuery"
                    @input="searchTours"
                    type="text"
                    class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm"
                    placeholder="Search tour by name..."
                  />
                </div>
                <!-- Selected tour -->
                <div v-if="form.tour_id && selectedTourName" class="mt-2 flex items-center justify-between px-3 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                  <span class="text-xs font-bold text-green-700 dark:text-green-400 truncate">{{ selectedTourName }}</span>
                  <button @click="form.tour_id = null; selectedTourName = ''" class="text-red-400 hover:text-red-600 ml-2">
                    <span class="material-symbols-outlined text-sm">close</span>
                  </button>
                </div>
                <!-- Search results dropdown -->
                <div v-if="tourResults.length > 0" class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                  <button
                    v-for="tour in tourResults"
                    :key="tour.id"
                    @click="selectTour(tour)"
                    class="w-full text-left px-3 py-2 hover:bg-primary/5 transition-colors flex items-center gap-2"
                  >
                    <span class="text-[10px] font-mono font-bold text-slate-400">{{ tour.code }}</span>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">{{ tour.title }}</span>
                  </button>
                </div>
              </div>
              <div class="mt-2">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1 block">Original opinion (reference)</label>
                <input v-model="form.opinion" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-xs text-slate-500" readonly />
              </div>
            </div>

            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.published" type="checkbox" class="w-4 h-4 rounded text-primary" />
                <span class="text-sm font-semibold">Published</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.featured" type="checkbox" class="w-4 h-4 rounded text-primary" />
                <span class="text-sm font-semibold">Featured</span>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
            <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
            <button @click="saveReview" :disabled="saving" class="px-6 py-2 bg-primary text-white text-sm font-bold rounded-lg shadow-lg disabled:opacity-50 flex items-center gap-2">
              <span v-if="saving" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: 'admin' })

const config = useRuntimeConfig()
const API = config.public.apiUrl
const auth = useAuthStore()
const headers = () => ({ Authorization: `Bearer ${auth.token}`, Accept: 'application/json' })

const reviews = ref<any[]>([])
const meta = ref<any>(null)
const stats = ref<any>(null)
const loading = ref(false)
const saving = ref(false)
const searchQuery = ref('')
const filterRating = ref('')
const filterPublished = ref('')
const filterFeatured = ref('')
const showAddModal = ref(false)
const editingReview = ref<any>(null)

const defaultForm = () => ({ name: '', review_date: '', rating: 5, title: '', comment: '', language: 'en', opinion: '', published: true, featured: false, tour_id: null as number | null })
const form = ref(defaultForm())
const tourSearchQuery = ref('')
const tourResults = ref<any[]>([])
const selectedTourName = ref('')
let tourSearchTimer: any = null

const statsCards = computed(() => [
  { label: 'Total', value: stats.value?.total || 0, color: 'text-slate-900 dark:text-white' },
  { label: 'Published', value: stats.value?.published || 0, color: 'text-green-600' },
  { label: 'Featured', value: stats.value?.featured || 0, color: 'text-primary' },
  { label: 'Avg Rating', value: stats.value?.avg_rating || 0, color: 'text-yellow-500' },
  { label: 'No Tour', value: stats.value?.unassigned || 0, color: 'text-orange-500' },
])

const visiblePages = computed(() => {
  if (!meta.value) return []
  const c = meta.value.current_page
  const l = meta.value.last_page
  const pages = []
  for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) pages.push(i)
  return pages
})

let debounceTimer: any = null
const debounceSearch = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchReviews(1), 300) }

async function fetchReviews(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '10' })
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (filterRating.value) params.set('rating', filterRating.value)
    if (filterPublished.value !== '') params.set('published', filterPublished.value)
    if (filterFeatured.value) params.set('featured', filterFeatured.value)
    const res: any = await $fetch(`${API}/admin/reviews?${params}`, { headers: headers() })
    if (res.success) { reviews.value = res.data; meta.value = res.meta }
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

async function fetchStats() {
  try {
    const res: any = await $fetch(`${API}/admin/reviews/stats`, { headers: headers() })
    if (res.success) stats.value = res.data
  } catch (e) { console.error(e) }
}

async function togglePublished(r: any) { await updateReview(r.id, { published: !r.published }); r.published = !r.published; fetchStats() }
async function toggleFeatured(r: any) { await updateReview(r.id, { featured: !r.featured }); r.featured = !r.featured; fetchStats() }

async function updateReview(id: number, data: any) {
  await $fetch(`${API}/admin/reviews/${id}`, { method: 'PUT', headers: headers(), body: data })
}

function searchTours() {
  clearTimeout(tourSearchTimer)
  if (tourSearchQuery.value.length < 2) { tourResults.value = []; return }
  tourSearchTimer = setTimeout(async () => {
    try {
      const res: any = await $fetch(`${API}/tours?search=${encodeURIComponent(tourSearchQuery.value)}&per_page=8&active=1`)
      tourResults.value = res.data || []
    } catch (e) { tourResults.value = [] }
  }, 150)
}

function selectTour(tour: any) {
  form.value.tour_id = tour.id
  selectedTourName.value = `[${tour.code}] ${tour.title}`
  tourResults.value = []
  tourSearchQuery.value = ''
}

function openEditModal(review: any) {
  editingReview.value = review
  form.value = { ...review, tour_id: review.tour_id || null }
  selectedTourName.value = review.tour ? `[${review.tour.code}] ${review.opinion || ''}` : ''
  tourSearchQuery.value = ''
  tourResults.value = []
}

function closeModal() {
  showAddModal.value = false
  editingReview.value = null
  form.value = defaultForm()
  selectedTourName.value = ''
  tourSearchQuery.value = ''
  tourResults.value = []
}

async function saveReview() {
  if (!form.value.name || !form.value.comment) { alert('Name and comment required'); return }
  saving.value = true
  try {
    if (editingReview.value) {
      await $fetch(`${API}/admin/reviews/${editingReview.value.id}`, { method: 'PUT', headers: headers(), body: form.value })
    } else {
      // Create new - use the update endpoint with a new record approach
      const res: any = await $fetch(`${API}/admin/reviews`, { method: 'POST', headers: headers(), body: form.value })
      if (!res.success) throw new Error(res.message)
    }
    closeModal()
    fetchReviews(meta.value?.current_page || 1)
    fetchStats()
  } catch (e: any) { alert('Error: ' + (e.data?.message || e.message)) }
  finally { saving.value = false }
}

async function confirmDelete(review: any) {
  if (confirm(`Delete review from "${review.name}"?`)) {
    await $fetch(`${API}/admin/reviews/${review.id}`, { method: 'DELETE', headers: headers() })
    reviews.value = reviews.value.filter(r => r.id !== review.id)
    fetchStats()
  }
}

onMounted(() => { fetchReviews(); fetchStats() })
</script>

<style scoped>
.glass-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); }
.dark .glass-card { background: rgba(15,23,42,0.5); }
</style>
