<template>
  <div class="flex flex-col gap-8 pb-20">
    <!-- Main Header -->
    <section class="glass-card p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 relative overflow-hidden group">
      <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
        <span class="material-symbols-outlined text-[120px] fill-1 text-primary">calendar_month</span>
      </div>
      
      <div class="relative z-10 max-w-2xl">
        <div class="flex items-center gap-3 mb-4">
          <div class="size-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
            <span class="material-symbols-outlined filled">calendar_today</span>
          </div>
          <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Disponibilidad y Calendario</h3>
        </div>
        <p class="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
          Configura cuándo estará disponible tu tour, bloquea fechas especiales y gestiona las reglas de reserva.
        </p>
      </div>
    </section>

    <!-- Essential Requirement Toggle -->
    <section class="glass-card p-8 rounded-3xl border border-slate-200 dark:border-slate-800">
      <div class="flex items-center justify-between gap-6">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
            <span class="material-symbols-outlined filled">verified_user</span>
          </div>
          <div>
            <h4 class="font-bold text-slate-900 dark:text-white">Requerir Verificación de Disponibilidad</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Los clientes deberán consultar si hay cupos antes de proceder al pago.</p>
          </div>
        </div>
        <div 
          @click="store.availability.requireAvailability = !store.availability.requireAvailability"
          class="relative w-14 h-8 rounded-full transition-colors cursor-pointer"
          :class="store.availability.requireAvailability ? 'bg-primary' : 'bg-slate-200 dark:bg-slate-700'"
        >
          <div 
            class="absolute top-1 left-1 size-6 bg-white rounded-full transition-transform shadow-sm"
            :class="store.availability.requireAvailability ? 'translate-x-6' : 'translate-x-0'"
          ></div>
        </div>
      </div>
    </section>

    <!-- Tabs Container -->
    <div class="glass-card rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden">
      <!-- Tabs Header -->
      <div class="flex border-b border-slate-100 dark:border-slate-800">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="flex-1 py-5 flex items-center justify-center gap-2 font-bold text-sm transition-all border-b-2"
          :class="activeTab === tab.id 
            ? 'text-primary border-primary bg-primary/5' 
            : 'text-slate-400 border-transparent hover:text-slate-600 dark:hover:text-slate-200'"
        >
          <span class="material-symbols-outlined text-[20px]" :class="activeTab === tab.id ? 'filled' : ''">{{ tab.icon }}</span>
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content -->
      <div class="p-10">
        <!-- Availability Tab -->
        <div v-if="activeTab === 'availability'" class="space-y-10">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Date From -->
            <div class="space-y-3">
              <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                <span class="size-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px]">1</span>
                Fecha Desde
              </label>
              <div class="relative group">
                <input 
                  type="date" 
                  v-model="store.availability.start"
                  class="w-full pl-12 pr-4 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all outline-none"
                >
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">event_available</span>
              </div>
            </div>

            <!-- Date To -->
            <div class="space-y-3">
              <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                <span class="size-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px]">2</span>
                Fecha Hasta
              </label>
              <div class="relative group">
                <input 
                  type="date" 
                  v-model="store.availability.end"
                  class="w-full pl-12 pr-4 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all outline-none"
                >
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">event_busy</span>
              </div>
            </div>
          </div>

          <!-- Active Days -->
          <div class="space-y-6">
            <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
              <span class="size-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px]">3</span>
              Días de la Semana Disponibles
            </label>
            <div class="flex flex-wrap gap-4">
              <button 
                v-for="day in weekDays" 
                :key="day.value"
                @click="toggleDay(day.value)"
                class="flex-1 min-w-[100px] py-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-1 group relative overflow-hidden"
                :class="store.availability.activeDays.includes(day.value)
                  ? 'border-primary bg-primary/10'
                  : 'border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 hover:border-slate-200'"
              >
                <span class="text-xs font-black uppercase tracking-widest transition-colors" :class="store.availability.activeDays.includes(day.value) ? 'text-primary' : 'text-slate-400 group-hover:text-slate-500'">
                  {{ day.label }}
                </span>
                <span class="material-symbols-outlined text-[20px]" :class="store.availability.activeDays.includes(day.value) ? 'text-primary filled' : 'text-slate-300'">
                  {{ store.availability.activeDays.includes(day.value) ? 'check_circle' : 'circle' }}
                </span>
              </button>
            </div>
          </div>

          <!-- Special Holidays -->
          <div class="space-y-6">
            <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
              <span class="size-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px]">4</span>
              Bloquear Feriados Nacionales
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div 
                v-for="holiday in holidays" 
                :key="holiday.value"
                @click="toggleSpecialDay(holiday.value)"
                class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-center gap-4 group"
                :class="store.availability.specialDays.includes(holiday.value)
                  ? 'border-red-500 bg-red-500/5 ring-4 ring-red-500/5'
                  : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'"
              >
                <div class="size-10 rounded-xl flex items-center justify-center transition-colors"
                  :class="store.availability.specialDays.includes(holiday.value) ? 'bg-red-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 group-hover:bg-slate-200'"
                >
                  <span class="material-symbols-outlined text-[20px]">{{ holiday.icon }}</span>
                </div>
                <div>
                  <h5 class="text-xs font-bold transition-colors" :class="store.availability.specialDays.includes(holiday.value) ? 'text-red-600 dark:text-red-400' : 'dark:text-white'">
                    {{ holiday.label }}
                  </h5>
                  <p class="text-[10px] text-slate-400 font-medium">{{ holiday.date }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary Alert -->
          <div class="p-6 bg-primary/5 border border-primary/20 rounded-3xl flex items-start gap-4">
            <div class="size-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">info</span>
            </div>
            <div>
              <h5 class="text-sm font-bold text-primary">Resumen de Lanzamiento</h5>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                Tu tour estará disponible desde el <b>{{ formatDate(store.availability.start) }}</b> hasta el <b>{{ formatDate(store.availability.end) }}</b>, operando <b>{{ store.availability.activeDays.length }} días</b> por semana.
                <span v-if="store.availability.specialDays.length"> Se han bloqueado <b>{{ store.availability.specialDays.length }} feriados</b> críticos.</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Blocks Tab (Placeholder) -->
        <div v-if="activeTab === 'blocks'" class="py-20 flex flex-col items-center justify-center text-center space-y-6">
           <div class="size-24 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center animate-pulse">
              <span class="material-symbols-outlined text-5xl">block</span>
           </div>
           <div class="space-y-2">
              <h4 class="text-xl font-bold dark:text-white">Gestión de Bloqueos Específicos</h4>
              <p class="text-sm text-slate-500 max-w-sm mx-auto">Esta función te permitirá bloquear fechas puntuales fuera del calendario regular. Estará disponible en la próxima actualización.</p>
           </div>
           <button class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-500 cursor-not-allowed">Próximamente</button>
        </div>

        <!-- Offers Tab (Placeholder) -->
        <div v-if="activeTab === 'offers'" class="py-20 flex flex-col items-center justify-center text-center space-y-6">
           <div class="size-24 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center animate-pulse">
              <span class="material-symbols-outlined text-5xl">sell</span>
           </div>
           <div class="space-y-2">
              <h4 class="text-xl font-bold dark:text-white">Ofertas y Descuentos</h4>
              <p class="text-sm text-slate-500 max-w-sm mx-auto">Configura precios especiales para temporadas bajas o lanzamientos. Estará disponible en la próxima actualización.</p>
           </div>
           <button class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-500 cursor-not-allowed">Próximamente</button>
        </div>
      </div>
    </div>

    <!-- Final Review / Publish Section -->
    <section class="glass-card p-10 rounded-[2.5rem] border-2 border-primary bg-primary/5 flex flex-col md:flex-row items-center justify-between gap-8">
       <div class="flex items-center gap-6">
          <div class="size-20 rounded-3xl bg-primary text-white flex items-center justify-center shadow-2xl shadow-primary/40 relative overflow-hidden">
             <span class="material-symbols-outlined text-4xl filled relative z-10">task_alt</span>
             <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
          </div>
          <div>
             <h4 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">¡Todo Listo!</h4>
             <p class="text-slate-500 dark:text-slate-400 font-medium italic">Tu tour está configurado y listo para ser publicado.</p>
          </div>
       </div>
       <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
          <button @click="handlePublish" class="px-10 py-5 rounded-2xl bg-primary text-white font-black shadow-xl shadow-primary/30 hover:scale-105 active:scale-95 transition-all text-sm flex items-center justify-center gap-2">
             <span class="material-symbols-outlined text-xl">rocket_launch</span>
             PUBLICAR TOUR AHORA
          </button>
       </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import { ref } from 'vue'

const store = useTourWizardStore()

const activeTab = ref('availability')

const tabs = [
  { id: 'availability', label: 'Disponibilidad Regular', icon: 'event_available' },
  { id: 'blocks', label: 'Bloqueos de Fechas', icon: 'event_busy' },
  { id: 'offers', label: 'Ofertas Especiales', icon: 'local_offer' }
]

const weekDays = [
  { label: 'Lun', value: 1 },
  { label: 'Mar', value: 2 },
  { label: 'Mie', value: 3 },
  { label: 'Jue', value: 4 },
  { label: 'Vie', value: 5 },
  { label: 'Sab', value: 6 },
  { label: 'Dom', value: 0 }
]

const holidays = [
  { label: 'Navidad', value: '25-12', date: '25 de Diciembre', icon: 'celebration' },
  { label: 'Fin de Año', value: '31-12', date: '31 de Diciembre', icon: 'auto_awesome' },
  { label: 'Año Nuevo', value: '01-01', date: '01 de Enero', icon: 'restaurant' },
  { label: 'Fiestas Patrias', value: '28-07', date: '28 de Julio', icon: 'flag' }
]

const toggleDay = (day: number) => {
  const index = store.availability.activeDays.indexOf(day)
  if (index === -1) {
    store.availability.activeDays.push(day)
  } else {
    store.availability.activeDays.splice(index, 1)
  }
}

const toggleSpecialDay = (value: string) => {
  const index = store.availability.specialDays.indexOf(value)
  if (index === -1) {
    store.availability.specialDays.push(value)
  } else {
    store.availability.specialDays.splice(index, 1)
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '---'
  return new Date(dateStr).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
}

const router = useRouter()

const handlePublish = async () => {
  // Option: We could set a status flag here if our backend supported it from the frontend:
  // store.basicInfo.status = 'published'
  // store.basicInfo.active = true
  
  await store.saveCurrentProgress()
  
  if (!store.loading) { // If it finished saving successfully and didn't crash
    alert('¡Felicidades! Se ha guardado la disponibilidad. El tour ya puede ser revisado en el catálogo.')
    router.push('/admin/tours')
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
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
