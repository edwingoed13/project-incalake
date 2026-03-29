<template>
  <div class="flex flex-col gap-10 pb-20">
    <!-- Language selector -->
    <div class="flex items-center gap-3 px-5 py-3 bg-slate-50/80 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-800">
      <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-lg font-bold">translate</span>
      </div>
      <div>
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Editando opciones de reserva en</p>
        <div class="flex items-center gap-2 mt-1">
          <button
            v-for="lang in tourLanguages"
            :key="lang"
            @click="store.currentLanguage = lang"
            class="px-2 py-0.5 rounded text-[10px] font-black uppercase transition-all"
            :class="store.currentLanguage === lang ? 'bg-primary text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500 hover:bg-slate-300 dark:hover:bg-slate-700'"
          >
            {{ lang }}
          </button>
        </div>
      </div>
    </div>

    <!-- 1. Políticas y Cancelaciones -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
          <span class="material-symbols-outlined filled">policy</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">1. Políticas y Cancelaciones</h3>
      </div>

      <div class="space-y-6">
        <div class="flex flex-wrap gap-4">
          <label 
            v-for="type in policyTypes" 
            :key="type.id"
            class="flex-1 min-w-[200px] cursor-pointer group"
          >
            <input 
              type="radio" 
              name="policyType" 
              :value="type.id" 
              v-model="store.bookingOptions.policyType"
              class="hidden"
            />
            <div 
              class="h-full p-4 rounded-2xl border-2 transition-all flex items-center gap-3"
              :class="store.bookingOptions.policyType === type.id 
                ? 'border-primary bg-primary/5 shadow-lg shadow-primary/5' 
                : 'border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 hover:border-slate-200 dark:hover:border-slate-700'"
            >
              <div class="size-6 rounded-full border-2 flex items-center justify-center transition-colors"
                :class="store.bookingOptions.policyType === type.id ? 'border-primary bg-primary' : 'border-slate-300 dark:border-slate-600'"
              >
                <div v-if="store.bookingOptions.policyType === type.id" class="size-2 bg-white rounded-full"></div>
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-black uppercase tracking-widest" :class="store.bookingOptions.policyType === type.id ? 'text-primary' : 'text-slate-500'">{{ type.name }}</span>
                <span class="text-[10px] text-slate-400 font-medium">{{ type.description }}</span>
              </div>
            </div>
          </label>
        </div>

        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">
            {{ store.bookingOptions.policyType === 'standard' ? 'Políticas Pre-establecidas (Editables)' : 'Descripción Personalizada' }}
          </label>
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-950">
            <TiptapEditor 
              v-if="store.bookingOptions.policyType === 'standard'"
              v-model="currentBookingTexts.policyDescription"
              placeholder="Escribe las políticas estándar aquí..."
            />
            <TiptapEditor 
              v-else
              v-model="currentBookingTexts.policyDescriptionCustom"
              placeholder="Escribe las políticas personalizadas para esta actividad..."
            />
          </div>
          <p v-if="store.bookingOptions.policyType === 'standard'" class="text-[10px] text-blue-500 font-bold flex items-center gap-2 px-2">
            <span class="material-symbols-outlined text-sm">info</span>
            Estas son las políticas estándar de Inca Lake. Puedes modificarlas si esta actividad lo requiere.
          </p>
        </div>
      </div>
    </section>

    <!-- 2. Tiempo de Anticipación -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-6">
      <div class="flex items-center gap-3">
        <div class="size-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
          <span class="material-symbols-outlined filled">schedule</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">2. Tiempo de Anticipación</h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
        <div class="space-y-3">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cantidad</label>
          <input 
            v-model.number="store.bookingOptions.bookingAnticipationQuantity"
            type="number" 
            min="1"
            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-3 px-4 focus:ring-2 focus:ring-primary focus:border-transparent outline-none text-slate-700 dark:text-white font-bold"
          />
        </div>
        <div class="space-y-3">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Unidad de Tiempo</label>
          <div class="flex bg-slate-100 dark:bg-slate-900 rounded-xl p-1 border border-slate-200 dark:border-slate-800">
             <button 
                @click="store.bookingOptions.bookingAnticipationUnit = 'hours'"
                class="flex-1 py-2 text-xs font-black uppercase tracking-widest rounded-lg transition-all"
                :class="store.bookingOptions.bookingAnticipationUnit === 'hours' ? 'bg-white dark:bg-slate-800 text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700'"
             >Horas</button>
             <button 
                @click="store.bookingOptions.bookingAnticipationUnit = 'days'"
                class="flex-1 py-2 text-xs font-black uppercase tracking-widest rounded-lg transition-all"
                :class="store.bookingOptions.bookingAnticipationUnit === 'days' ? 'bg-white dark:bg-slate-800 text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700'"
             >Días</button>
          </div>
        </div>
      </div>
      <div class="p-4 bg-amber-500/5 rounded-2xl border border-amber-500/10 flex items-center gap-4">
         <span class="material-symbols-outlined text-amber-500">lightbulb</span>
         <p class="text-[10px] md:text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
            Ejemplo: Si el tour inicia a las 7:00 AM y configuras <strong>{{ store.bookingOptions.bookingAnticipationQuantity }} {{ store.bookingOptions.bookingAnticipationUnit === 'hours' ? 'horas' : 'días' }}</strong> de anticipación, los clientes podrán reservar hasta las {{ calculateExampleTime() }}.
         </p>
      </div>
    </section>

    <!-- 3 & 4. Datos Requeridos -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-8">
      <div class="flex items-center gap-3">
        <div class="size-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
          <span class="material-symbols-outlined filled">person_add</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">3. Requerimientos de Información</h3>
      </div>

      <div class="flex gap-4 p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 w-fit">
        <button 
          @click="store.bookingOptions.dataRequirementType = 'leader'"
          class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition-all"
          :class="store.bookingOptions.dataRequirementType === 'leader' ? 'bg-white dark:bg-slate-800 text-primary shadow-md' : 'text-slate-500'"
        >Solo LÍDER</button>
        <button 
          @click="store.bookingOptions.dataRequirementType = 'all'"
          class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition-all"
          :class="store.bookingOptions.dataRequirementType === 'all' ? 'bg-white dark:bg-slate-800 text-primary shadow-md' : 'text-slate-500'"
        >TODOS los pasajeros</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        <!-- Personal Info -->
        <div class="space-y-4">
          <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-100 dark:border-slate-800 pb-2 flex justify-between">
            Información Personal
            <span class="text-[9px] lowercase font-medium text-slate-400 italic">datos básicos</span>
          </h4>
          <div class="grid grid-cols-1 gap-2">
            <label 
              v-for="(label, key) in personalFields" 
              :key="key"
              class="flex items-center gap-3 p-3 rounded-xl border border-transparent hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors cursor-pointer group"
            >
              <div class="relative size-5">
                <input 
                  type="checkbox" 
                  :value="key" 
                  v-model="store.bookingOptions.personalInfoRequired"
                  class="peer absolute inset-0 opacity-0 cursor-pointer"
                />
                <div class="size-5 rounded-md border-2 border-slate-300 dark:border-slate-700 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 transition-all flex items-center justify-center">
                  <span class="material-symbols-outlined text-white text-[14px] font-black scale-0 peer-checked:scale-100 transition-transform">check</span>
                </div>
              </div>
              <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ label }}</span>
            </label>
          </div>
        </div>

        <!-- Operational Info -->
        <div class="space-y-4">
           <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b border-slate-100 dark:border-slate-800 pb-2 flex justify-between">
            Información Operacional
            <span class="text-[9px] lowercase font-medium text-slate-400 italic">datos específicos</span>
          </h4>
          <div class="grid grid-cols-1 gap-2">
            <label 
              v-for="(label, key) in operationalFields" 
              :key="key"
              class="flex items-center gap-3 p-3 rounded-xl border border-transparent hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors cursor-pointer group"
            >
              <div class="relative size-5">
                <input 
                  type="checkbox" 
                  :value="key" 
                  v-model="store.bookingOptions.operationalInfoRequired"
                  class="peer absolute inset-0 opacity-0 cursor-pointer"
                />
                <div class="size-5 rounded-md border-2 border-slate-300 dark:border-slate-700 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 transition-all flex items-center justify-center">
                  <span class="material-symbols-outlined text-white text-[14px] font-black scale-0 peer-checked:scale-100 transition-transform">check</span>
                </div>
              </div>
              <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ label }}</span>
            </label>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. Opciones de Recojo -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-8">
      <div class="flex items-center gap-3">
        <div class="size-10 rounded-xl bg-violet-500/10 text-violet-500 flex items-center justify-center">
          <span class="material-symbols-outlined filled">hail</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">4. Opciones de Recojo</h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Meeting Point -->
        <div 
          class="p-6 rounded-[1.5rem] border-2 transition-all space-y-4"
          :class="store.bookingOptions.enableMeetingPoint ? 'border-primary bg-primary/[0.02]' : 'border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30'"
        >
          <label class="flex items-center gap-4 cursor-pointer">
             <div class="relative size-6">
                <input type="checkbox" v-model="store.bookingOptions.enableMeetingPoint" class="peer absolute inset-0 opacity-0 cursor-pointer" />
                <div class="size-6 rounded-lg border-2 border-slate-300 dark:border-slate-700 peer-checked:border-primary peer-checked:bg-primary transition-all flex items-center justify-center">
                   <span class="material-symbols-outlined text-white text-[16px] font-black scale-0 peer-checked:scale-100 transition-transform">check</span>
                </div>
             </div>
             <div class="flex flex-col">
                <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Punto de Encuentro</span>
                <span class="text-[10px] text-slate-400 font-medium">El cliente debe llegar a un lugar específico</span>
             </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableMeetingPoint" class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
               <textarea 
                v-model="currentBookingTexts.meetingPointDescription"
                placeholder="Ejemplo: Plaza de Armas de Puno, frente a la Catedral..."
                rows="3"
                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-1 focus:ring-primary outline-none transition-all dark:text-white"
               ></textarea>
               <button @click="openPickupModal('meeting_point')" class="w-full py-3 bg-slate-900 dark:bg-slate-800 text-white rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-black transition-colors">
                  <span class="material-symbols-outlined text-sm">location_on</span>
                  Configurar en el Mapa
               </button>
               
               <div v-if="store.bookingOptions.meetingPointLat && store.bookingOptions.meetingPointLng" class="p-3 bg-emerald-500/5 rounded-xl border border-emerald-500/10 active:scale-95 transition-all">
                  <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-2">
                     <span class="material-symbols-outlined text-sm">check_circle</span>
                     Ubicación marcada correctamente
                  </p>
               </div>
            </div>
          </Transition>
        </div>

        <!-- Hotel Pickup -->
        <div 
          class="p-6 rounded-[1.5rem] border-2 transition-all space-y-4"
          :class="store.bookingOptions.enableHotelPickup ? 'border-primary bg-primary/[0.02]' : 'border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30'"
        >
          <label class="flex items-center gap-4 cursor-pointer">
             <div class="relative size-6">
                <input type="checkbox" v-model="store.bookingOptions.enableHotelPickup" class="peer absolute inset-0 opacity-0 cursor-pointer" />
                <div class="size-6 rounded-lg border-2 border-slate-300 dark:border-slate-700 peer-checked:border-primary peer-checked:bg-primary transition-all flex items-center justify-center">
                   <span class="material-symbols-outlined text-white text-[16px] font-black scale-0 peer-checked:scale-100 transition-transform">check</span>
                </div>
             </div>
             <div class="flex flex-col">
                <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Recojo de Hotel</span>
                <span class="text-[10px] text-slate-400 font-medium">Recojo en hoteles dentro de un radio establecido</span>
             </div>
          </label>

           <Transition name="fade">
            <div v-if="store.bookingOptions.enableHotelPickup" class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
               <textarea 
                v-model="currentBookingTexts.pickupLocationDescription"
                placeholder="Ejemplo: Hoteles del centro de la ciudad y alrededores..."
                rows="2"
                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-1 focus:ring-primary outline-none transition-all dark:text-white"
               ></textarea>
               <div class="flex items-center gap-4">
                  <div class="flex-1 space-y-2">
                     <span class="text-[9px] font-black uppercase text-slate-400">Radio (Km)</span>
                     <input type="number" v-model="store.bookingOptions.pickupRadiusKm" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs font-bold" />
                  </div>
                  <button @click="openPickupModal('hotel_pickup')" class="flex-[2] h-[42px] mt-4 bg-slate-900 dark:bg-slate-800 text-white rounded-xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-black transition-colors">
                    <span class="material-symbols-outlined text-sm">radio_button_checked</span>
                    Configurar Radio
                  </button>
               </div>

               <div v-if="store.bookingOptions.pickupCenterLat && store.bookingOptions.pickupCenterLng" class="p-3 bg-emerald-500/5 rounded-xl border border-emerald-500/10">
                  <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-2">
                     <span class="material-symbols-outlined text-sm">check_circle</span>
                     Radio de {{ store.bookingOptions.pickupRadiusKm }}km configurado
                  </p>
               </div>

               <textarea 
                v-model="currentBookingTexts.dropoffLocationDescription"
                placeholder="Punto de retorno (opcional)..."
                rows="2"
                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-1 focus:ring-primary outline-none transition-all dark:text-white"
               ></textarea>
            </div>
          </Transition>
        </div>
      </div>
      
      <div v-if="!store.bookingOptions.enableMeetingPoint && !store.bookingOptions.enableHotelPickup" class="p-6 bg-rose-500/10 rounded-2xl border border-rose-500/20 animate-pulse">
         <div class="flex items-center gap-3 text-rose-500">
            <span class="material-symbols-outlined filled text-xl">warning</span>
            <p class="text-xs font-bold uppercase tracking-widest">Alerta de Seguridad</p>
         </div>
         <p class="text-xs text-rose-600 dark:text-rose-400 mt-2 font-medium">Debes habilitar al menos una opción de recojo para que el tour sea reservasble.</p>
      </div>
    </section>

    <!-- 6. Asociar Guías -->
    <section class="glass-card p-8 rounded-[2rem] border border-slate-200 dark:border-slate-800 space-y-8">
      <div class="flex items-center gap-3">
        <div class="size-10 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center">
          <span class="material-symbols-outlined filled">record_voice_over</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">5. Configuración de Guía</h3>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div class="space-y-4">
          <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Tipo de Acompañante</h4>
          <div class="flex flex-col gap-3">
             <label 
              v-for="guide in guideTypes" 
              :key="guide.id"
              class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all cursor-pointer group"
              :class="store.bookingOptions.guideType === guide.id ? 'border-primary bg-primary/5 shadow-sm' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'"
             >
                <input type="radio" v-model="store.bookingOptions.guideType" :value="guide.id" class="hidden" />
                <div class="size-5 rounded-full border-2 flex items-center justify-center" :class="store.bookingOptions.guideType === guide.id ? 'border-primary' : 'border-slate-300 dark:border-slate-700'">
                   <div v-if="store.bookingOptions.guideType === guide.id" class="size-2.5 bg-primary rounded-full"></div>
                </div>
                <div class="flex flex-col">
                   <span class="text-xs font-black uppercase tracking-tight" :class="store.bookingOptions.guideType === guide.id ? 'text-primary' : 'text-slate-600 dark:text-slate-300'">{{ guide.name }}</span>
                </div>
             </label>
          </div>
        </div>

        <div v-if="store.bookingOptions.guideType === 'live_guide'" class="space-y-4 animate-in fade-in slide-in-from-right-5">
           <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Idiomas Disponibles</h4>
           <div class="grid grid-cols-2 gap-3">
              <label 
                v-for="lang in guideLanguages" 
                :key="lang.id"
                class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 transition-all cursor-pointer group"
                :class="store.bookingOptions.guideLanguages.includes(lang.id) ? 'bg-primary/5 border-primary/20' : ''"
              >
                 <input type="checkbox" :value="lang.id" v-model="store.bookingOptions.guideLanguages" class="hidden" />
                 <div class="size-4 rounded border-2 transition-all flex items-center justify-center" :class="store.bookingOptions.guideLanguages.includes(lang.id) ? 'border-primary bg-primary' : 'border-slate-300 dark:border-slate-700'">
                    <span class="material-symbols-outlined text-white text-[12px] font-black" v-if="store.bookingOptions.guideLanguages.includes(lang.id)">check</span>
                 </div>
                 <span class="text-xs font-bold" :class="store.bookingOptions.guideLanguages.includes(lang.id) ? 'text-primary' : 'text-slate-600 dark:text-slate-400'">{{ lang.name }}</span>
              </label>
           </div>
        </div>
      </div>
    </section>

    <!-- Map Modal -->
    <PickupMapModal 
      :is-open="isMapModalOpen"
      :type="pickupModalType"
      :initial-data="pickupModalData"
      @close="isMapModalOpen = false"
      @save="handlePickupSave"
    />
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import TiptapEditor from '~/components/common/TiptapEditor.vue'
import PickupMapModal from '~/components/tours/wizard/PickupMapModal.vue'
import { ref, computed } from 'vue'

const store = useTourWizardStore()

const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

// Per-language booking texts
const currentBookingTexts = computed(() => {
  const seo = store.contentSEO[store.currentLanguage]
  if (!seo) return { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  if (!seo.bookingTexts) {
    seo.bookingTexts = { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  }
  return seo.bookingTexts
})

// Map Modal Logic
const isMapModalOpen = ref(false)
const pickupModalType = ref<'meeting_point' | 'hotel_pickup'>('meeting_point')

const pickupModalData = computed(() => {
  if (pickupModalType.value === 'meeting_point') {
    return {
      lat: store.bookingOptions.meetingPointLat,
      lng: store.bookingOptions.meetingPointLng,
      description: currentBookingTexts.value.meetingPointDescription
    }
  } else {
    return {
      lat: store.bookingOptions.pickupCenterLat,
      lng: store.bookingOptions.pickupCenterLng,
      radius: store.bookingOptions.pickupRadiusKm,
      description: currentBookingTexts.value.pickupLocationDescription
    }
  }
})

const openPickupModal = (type: 'meeting_point' | 'hotel_pickup') => {
  pickupModalType.value = type
  isMapModalOpen.value = true
}

const handlePickupSave = (data: any) => {
  if (pickupModalType.value === 'meeting_point') {
    store.bookingOptions.meetingPointLat = data.lat
    store.bookingOptions.meetingPointLng = data.lng
    currentBookingTexts.value.meetingPointDescription = data.description
  } else {
    store.bookingOptions.pickupCenterLat = data.lat
    store.bookingOptions.pickupCenterLng = data.lng
    store.bookingOptions.pickupRadiusKm = data.radius
    currentBookingTexts.value.pickupLocationDescription = data.description
  }
  isMapModalOpen.value = false
}

const policyTypes = [
  { id: 'standard', name: 'Standard (Global)', description: 'Políticas pre-establecidas por Inca Lake para todos sus tours.' },
  { id: 'custom', name: 'Personalizada', description: 'Políticas únicas para esta actividad específica.' }
] as const

const personalFields = {
  first_name: 'Nombre',
  last_name: 'Apellido',
  birthdate: 'Fecha de Nacimiento',
  nationality: 'Nacionalidad',
  phone_whatsapp: 'Número de WhatsApp',
  email: 'Correo Electrónico',
  dietary_restrictions: 'Restricciones Alimentarias',
  gender: 'Género'
}

const operationalFields = {
  peru_entry_date: 'Fecha de ingreso al Perú',
  hotel_name: 'Nombre de su hotel',
  passport_copy: 'Copia de pasaporte o ID',
  arrival_flight: 'Vuelo de llegada',
  departure_flight: 'Vuelo de salida',
  weight_kg: 'Peso (kg)',
  height_m: 'Altura (m)',
  arrival_bus_company: 'Cía de bus de llegada',
  arrival_train: 'Tren de llegada'
}

const guideTypes = [
  { id: 'live_guide', name: 'Guía de tour en vivo' },
  { id: 'audio_guide', name: 'Audio Guía y Audífonos' },
  { id: 'informative_brochures', name: 'Folletos informativos' },
  { id: 'no_guide', name: 'Sin Guía / Tickets' },
  { id: 'none', name: 'No mostrar nada' }
] as const

const guideLanguages = [
  { id: 1, name: 'Español' },
  { id: 2, name: 'Inglés' },
  { id: 3, name: 'Francés' },
  { id: 4, name: 'Alemán' },
  { id: 5, name: 'Portugués' },
  { id: 6, name: 'Italiano' }
]

const calculateExampleTime = () => {
  const q = store.bookingOptions.bookingAnticipationQuantity
  const u = store.bookingOptions.bookingAnticipationUnit
  
  if (u === 'hours') {
    if (q >= 7) {
      return `las ${24 - (q - 7)}:00 del día anterior`
    } else {
      return `las ${7 - q}:00 AM del mismo día`
    }
  } else {
    return `${q === 1 ? 'un día' : q + ' días'} antes del inicio`
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

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
