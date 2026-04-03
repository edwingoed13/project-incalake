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

        <!-- Blocks Tab -->
        <div v-if="activeTab === 'blocks'" class="space-y-10">
          <!-- Add New Block Form -->
          <div class="p-8 bg-red-50/50 dark:bg-red-900/10 rounded-3xl border border-red-200 dark:border-red-900/30 space-y-6">
            <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span class="material-symbols-outlined text-red-500">add_circle</span>
              Agregar Bloqueo de Fechas
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center text-[10px]">1</span>
                  Desde
                </label>
                <div class="relative group">
                  <input
                    type="date"
                    v-model="newBlock.startDate"
                    class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all outline-none"
                  >
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-red-500 transition-colors">event</span>
                </div>
              </div>

              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center text-[10px]">2</span>
                  Hasta
                </label>
                <div class="relative group">
                  <input
                    type="date"
                    v-model="newBlock.endDate"
                    class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all outline-none"
                  >
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-red-500 transition-colors">event</span>
                </div>
              </div>
            </div>

            <div class="space-y-3">
              <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                <span class="size-6 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center text-[10px]">3</span>
                Motivo del Bloqueo
              </label>
              <textarea
                v-model="newBlock.reason"
                placeholder="Describa el motivo del bloqueo (ej: Mantenimiento, vacaciones, evento privado...)"
                class="w-full px-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all outline-none resize-none"
                rows="3"
              ></textarea>
            </div>

            <button
              @click="addBlock"
              :disabled="!newBlock.startDate || !newBlock.endDate || !newBlock.reason"
              class="px-6 py-3 rounded-2xl bg-red-500 text-white font-bold hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-[20px]">save</span>
              GUARDAR BLOQUEO
            </button>
          </div>

          <!-- Blocks List -->
          <div class="space-y-4">
            <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Lista de Bloqueos</h4>

            <div v-if="store.availability.blocks && store.availability.blocks.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(block, index) in store.availability.blocks"
                :key="block.id || index"
                class="p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-start justify-between hover:shadow-lg transition-all"
              >
                <div class="flex items-start gap-4">
                  <div class="size-10 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">block</span>
                  </div>
                  <div class="space-y-1">
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">{{ block.reason }}</h5>
                    <p class="text-xs text-slate-500">
                      <span class="font-medium">Desde:</span> {{ formatDate(block.startDate) }} -
                      <span class="font-medium">Hasta:</span> {{ formatDate(block.endDate) }}
                    </p>
                  </div>
                </div>
                <button
                  @click="removeBlock(index)"
                  class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                  title="Eliminar bloqueo"
                >
                  <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
              </div>
            </div>

            <div v-else class="py-12 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl flex flex-col items-center justify-center text-slate-400">
              <span class="material-symbols-outlined text-5xl mb-2 opacity-30">event_busy</span>
              <p class="text-sm font-medium">No hay bloqueos configurados</p>
              <p class="text-xs opacity-60 mt-1">Agrega fechas bloqueadas arriba</p>
            </div>
          </div>
        </div>

        <!-- Offers Tab -->
        <div v-if="activeTab === 'offers'" class="space-y-10">
          <!-- Add New Offer Form -->
          <div class="p-8 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/10 dark:to-emerald-900/10 rounded-3xl border border-green-200 dark:border-green-900/30 space-y-6">
            <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span class="material-symbols-outlined text-green-500">add_circle</span>
              Agregar Oferta Especial
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-green-500/10 text-green-500 flex items-center justify-center text-[10px]">1</span>
                  Desde
                </label>
                <div class="relative group">
                  <input
                    type="date"
                    v-model="newOffer.startDate"
                    class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-green-500/10 focus:border-green-500 transition-all outline-none"
                  >
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-green-500 transition-colors">event</span>
                </div>
              </div>

              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-green-500/10 text-green-500 flex items-center justify-center text-[10px]">2</span>
                  Hasta
                </label>
                <div class="relative group">
                  <input
                    type="date"
                    v-model="newOffer.endDate"
                    class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-green-500/10 focus:border-green-500 transition-all outline-none"
                  >
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-green-500 transition-colors">event</span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-green-500/10 text-green-500 flex items-center justify-center text-[10px]">3</span>
                  Descuento
                </label>
                <input
                  type="number"
                  v-model="newOffer.discount"
                  min="1"
                  placeholder="Ej: 20"
                  class="w-full px-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-green-500/10 focus:border-green-500 transition-all outline-none"
                >
              </div>

              <div class="space-y-3">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">
                  Tipo de Descuento
                </label>
                <select
                  v-model="newOffer.discountType"
                  class="w-full px-4 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-green-500/10 focus:border-green-500 transition-all outline-none"
                >
                  <option value="percentage">Porcentaje (%)</option>
                  <option value="amount">Monto Fijo (USD)</option>
                </select>
              </div>

              <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-300">
                  <span class="size-6 rounded-full bg-green-500/10 text-green-500 flex items-center justify-center text-[10px]">4</span>
                  Color
                </label>
                <div class="flex gap-2">
                  <button
                    v-for="color in offerColors"
                    :key="color.value"
                    @click="newOffer.color = color.value"
                    class="size-12 rounded-xl border-2 transition-all flex items-center justify-center"
                    :style="{ backgroundColor: color.value }"
                    :class="newOffer.color === color.value ? 'ring-4 ring-offset-2 ring-slate-400' : 'hover:scale-110'"
                    :title="color.label"
                  >
                    <span v-if="newOffer.color === color.value" class="material-symbols-outlined text-white text-[20px] drop-shadow-lg">check</span>
                  </button>
                </div>
              </div>
            </div>

            <button
              @click="addOffer"
              :disabled="!newOffer.startDate || !newOffer.endDate || !newOffer.discount"
              class="px-6 py-3 rounded-2xl bg-green-500 text-white font-bold hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-[20px]">save</span>
              GUARDAR OFERTA
            </button>
          </div>

          <!-- Offers List -->
          <div class="space-y-4">
            <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Lista de Ofertas</h4>

            <div v-if="store.availability.offers && store.availability.offers.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(offer, index) in store.availability.offers"
                :key="offer.id || index"
                class="p-4 bg-white dark:bg-slate-800 rounded-2xl border-2 flex items-start justify-between hover:shadow-lg transition-all"
                :style="{ borderColor: offer.color }"
              >
                <div class="flex items-start gap-4">
                  <div class="size-10 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: offer.color + '20' }">
                    <span class="material-symbols-outlined text-[20px]" :style="{ color: offer.color }">sell</span>
                  </div>
                  <div class="space-y-1">
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">
                      {{ offer.discount }}{{ offer.discountType === 'percentage' ? '%' : ' USD' }} de descuento
                    </h5>
                    <p class="text-xs text-slate-500">
                      <span class="font-medium">Desde:</span> {{ formatDate(offer.startDate) }} -
                      <span class="font-medium">Hasta:</span> {{ formatDate(offer.endDate) }}
                    </p>
                  </div>
                </div>
                <button
                  @click="removeOffer(index)"
                  class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                  title="Eliminar oferta"
                >
                  <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
              </div>
            </div>

            <div v-else class="py-12 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl flex flex-col items-center justify-center text-slate-400">
              <span class="material-symbols-outlined text-5xl mb-2 opacity-30">local_offer</span>
              <p class="text-sm font-medium">No hay ofertas configuradas</p>
              <p class="text-xs opacity-60 mt-1">Agrega ofertas especiales arriba</p>
            </div>
          </div>
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
import { ref, reactive } from 'vue'

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

// Block management
const newBlock = reactive({
  startDate: '',
  endDate: '',
  reason: ''
})

const addBlock = () => {
  if (!newBlock.startDate || !newBlock.endDate || !newBlock.reason) return

  if (!store.availability.blocks) {
    store.availability.blocks = []
  }

  store.availability.blocks.push({
    id: crypto.randomUUID(),
    startDate: newBlock.startDate,
    endDate: newBlock.endDate,
    reason: newBlock.reason
  })

  // Reset form
  newBlock.startDate = ''
  newBlock.endDate = ''
  newBlock.reason = ''
}

const removeBlock = (index: number) => {
  if (store.availability.blocks) {
    store.availability.blocks.splice(index, 1)
  }
}

// Offer management
const newOffer = reactive({
  startDate: '',
  endDate: '',
  discount: null as number | null,
  discountType: 'percentage',
  color: '#449d44'
})

const offerColors = [
  { label: 'Azul', value: '#286090' },
  { label: 'Verde', value: '#449d44' },
  { label: 'Celeste', value: '#31b0d5' },
  { label: 'Naranja', value: '#f0ad4e' },
  { label: 'Rojo', value: '#d9534f' }
]

const addOffer = () => {
  if (!newOffer.startDate || !newOffer.endDate || !newOffer.discount) return

  if (!store.availability.offers) {
    store.availability.offers = []
  }

  store.availability.offers.push({
    id: crypto.randomUUID(),
    startDate: newOffer.startDate,
    endDate: newOffer.endDate,
    discount: newOffer.discount,
    discountType: newOffer.discountType,
    color: newOffer.color
  })

  // Reset form
  newOffer.startDate = ''
  newOffer.endDate = ''
  newOffer.discount = null
  newOffer.discountType = 'percentage'
  newOffer.color = '#449d44'
}

const removeOffer = (index: number) => {
  if (store.availability.offers) {
    store.availability.offers.splice(index, 1)
  }
}

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
  // Parse as local date (not UTC) to avoid timezone offset showing previous day
  const [year, month, day] = dateStr.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
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
