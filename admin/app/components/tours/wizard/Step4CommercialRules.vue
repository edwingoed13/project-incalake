<template>
  <div class="flex flex-col gap-10 pb-20">
    <!-- Payment Methods -->
    <section class="glass-card p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
      <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">credit_card</span>
        Método de Pago Aceptado *
      </h4>
      <div class="flex flex-col gap-3">
        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-transparent hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
          <input type="radio" v-model="store.commercialRules.paymentMethod" value="all" class="w-5 h-5 text-primary border-slate-300 dark:border-slate-700 focus:ring-primary bg-transparent" />
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Todos los métodos (PayPal y Culqi)</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-transparent hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
          <input type="radio" v-model="store.commercialRules.paymentMethod" value="culqi" class="w-5 h-5 text-primary border-slate-300 dark:border-slate-700 focus:ring-primary bg-transparent" />
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Solo Culqi</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-transparent hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
          <input type="radio" v-model="store.commercialRules.paymentMethod" value="paypal" class="w-5 h-5 text-primary border-slate-300 dark:border-slate-700 focus:ring-primary bg-transparent" />
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Solo PayPal</span>
        </label>
      </div>
    </section>

    <!-- Pricing Hierarchical (3 Levels) -->
    <section class="space-y-6">
      <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Precios por Etapa de Edad, Nacionalidad y Cantidad</h3>
      
      <div class="space-y-8">
        <div 
          v-for="ageStage in store.commercialRules.ageStages" 
          :key="ageStage.id"
          class="glass-card rounded-2xl border-2 transition-all overflow-hidden"
          :class="ageStage.active ? 'border-primary/30 shadow-lg shadow-primary/5' : 'border-slate-200 dark:border-slate-800 opacity-60'"
        >
          <!-- Age Stage Header -->
          <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center border-b border-slate-100 dark:border-slate-800">
            <div>
              <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ ageStage.description }}</h4>
              <p class="text-xs text-slate-500 font-medium tracking-wide">
                Rango de edad: {{ ageStage.minAge }} - {{ ageStage.maxAge >= 99 ? '+' : ageStage.maxAge }} años
              </p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-950 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-800 shadow-sm">
              <input type="checkbox" v-model="ageStage.active" class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" />
              <span class="text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-400">Activo</span>
            </label>
          </div>

          <!-- Nationalities (only if Active) -->
          <div v-if="ageStage.active" class="p-6 space-y-6">
            <div class="flex justify-start">
               <button 
                @click="addNationality(ageStage)"
                class="px-4 py-2 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20 active:scale-95"
               >
                 <span class="material-symbols-outlined text-sm">add_circle</span>
                 Agregar Nacionalidad
               </button>
            </div>

            <div class="space-y-6">
              <div 
                v-for="(nat, natIndex) in ageStage.nationalities" 
                :key="nat.id"
                class="rounded-xl border border-slate-200 dark:border-slate-800 p-5 bg-white dark:bg-slate-950 shadow-sm space-y-5"
              >
                <!-- Nationality Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                  <div class="flex items-center gap-4 flex-1">
                    <select 
                      v-model="nat.nationalityId"
                      class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary outline-none min-w-[200px] dark:text-white"
                    >
                      <option value="">-- Seleccionar Nacionalidad --</option>
                      <option value="general">General</option>
                      <option value="peruano">Peruano</option>
                      <option value="latino">Latinoamericano</option>
                      <option value="extranjero">Extranjero</option>
                    </select>

                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                      <span>Rango:</span>
                      <input v-model.number="nat.ageMin" type="number" class="w-14 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded text-center focus:ring-primary" />
                      <span>-</span>
                      <input v-model.number="nat.ageMax" type="number" class="w-14 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded text-center focus:ring-primary" />
                    </div>
                  </div>

                  <button @click="removeNationality(ageStage, natIndex)" class="text-slate-300 hover:text-rose-500 transition-colors">
                    <span class="material-symbols-outlined text-sm">delete</span>
                  </button>
                </div>

                <!-- Price Ranges Table -->
                <div v-if="nat.nationalityId" class="space-y-4">
                  <div class="bg-slate-50/50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-800">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Precios por Cantidad de PAX</h5>
                    
                    <div class="overflow-hidden">
                      <table class="w-full text-left border-collapse">
                        <thead>
                          <tr class="text-[10px] font-black uppercase text-slate-400">
                            <th class="pb-3 px-2">Desde (Pax)</th>
                            <th class="pb-3 text-center">-</th>
                            <th class="pb-3 px-2">Hasta (Pax)</th>
                            <th class="pb-3 text-center">:</th>
                            <th class="pb-3 px-2">Precio USD</th>
                            <th class="pb-3"></th>
                          </tr>
                        </thead>
                        <tbody class="space-y-2">
                          <tr v-for="(range, rIndex) in nat.ranges" :key="range.id" class="group">
                            <td class="py-1 px-2">
                              <input v-model.number="range.from" type="number" class="w-20 px-3 py-1.5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg text-sm focus:ring-primary dark:text-white" />
                            </td>
                            <td class="py-1 text-center text-slate-300">-</td>
                            <td class="py-1 px-2">
                              <input v-model.number="range.to" type="number" class="w-20 px-3 py-1.5 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg text-sm focus:ring-primary dark:text-white" />
                            </td>
                            <td class="py-1 text-center text-slate-300">:</td>
                            <td class="py-1 px-2">
                              <div class="flex items-center gap-1 bg-white dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-lg px-2 py-1.5 focus-within:border-primary transition-all">
                                <span class="text-[10px] font-bold text-slate-400">$</span>
                                <input v-model.number="range.price" type="number" step="0.01" class="w-full bg-transparent border-none p-0 focus:ring-0 text-sm font-mono dark:text-white" />
                              </div>
                            </td>
                            <td class="py-1 text-right">
                              <button @click="removeRange(nat, rIndex)" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-all">
                                <span class="material-symbols-outlined text-xs">close</span>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <button 
                      @click="addRange(nat)"
                      class="mt-4 px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all"
                    >
                      <span class="material-symbols-outlined text-xs">add</span>
                      Agregar Rango de Precio
                    </button>
                  </div>
                </div>
                <div v-else class="text-center py-4 text-xs italic text-slate-400 font-medium">
                  Selecciona una nacionalidad para configurar precios
                </div>
              </div>

               <div v-if="ageStage.nationalities.length === 0" class="text-center py-8 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-2xl text-slate-400 text-sm">
                Haz clic en "Agregar Nacionalidad" para configurar precios
              </div>
            </div>
          </div>
          <div v-else class="p-6 text-center text-sm italic text-slate-400">
            Activa esta etapa de edad para configurar precios
          </div>
        </div>
      </div>
    </section>

    <!-- General Config Section -->
    <section class="glass-card p-8 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-8 bg-white dark:bg-slate-950 shadow-sm relative overflow-hidden">
      <div class="absolute -left-10 -top-10 size-40 bg-primary/5 rounded-full blur-3xl"></div>
      
      <h4 class="text-lg font-black text-slate-900 dark:text-white tracking-widest uppercase flex items-center gap-3 relative z-10">
        <span class="size-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
           <span class="material-symbols-outlined text-sm">settings</span>
        </span>
        Configuración de Precios Generales
      </h4>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
        <!-- Taxes -->
        <div class="space-y-3">
          <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-tighter">
            Tasas e impuestos a aplicar (%)
          </label>
          <div class="flex items-center gap-3">
            <div class="flex-1 flex items-center gap-3 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl px-5 py-3 focus-within:ring-2 focus-within:ring-primary transition-all">
              <input 
                type="number" 
                v-model.number="store.commercialRules.taxPercentage" 
                step="0.01" min="0" max="100"
                class="w-full bg-transparent border-none p-0 focus:ring-0 text-xl font-black dark:text-white"
              />
              <span class="text-xl font-black text-slate-300">%</span>
            </div>
          </div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
            Este porcentaje se añadirá al precio final del tour para cubrir comisiones de pasarela o impuestos locales.
          </p>
        </div>

        <!-- First Payment -->
        <div class="space-y-3">
          <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-tighter">
            Porcentaje de primera cuota (%)
          </label>
          <div class="flex items-center gap-3">
            <div class="flex-1 flex items-center gap-3 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl px-5 py-3 focus-within:ring-2 focus-within:ring-primary transition-all">
              <input 
                type="number" 
                v-model.number="store.commercialRules.advancePaymentPercentage" 
                min="1" max="100"
                class="w-full bg-transparent border-none p-0 focus:ring-0 text-xl font-black dark:text-white"
              />
              <span class="text-xl font-black text-slate-300">%</span>
            </div>
          </div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
            100% = Pago completo requerido | Menor a 100% = Permite reserva con pago inicial.
          </p>
        </div>
      </div>

      <!-- Warning Message -->
      <div 
        v-if="store.commercialRules.advancePaymentPercentage < 100"
        class="mt-4 p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-all animate-in fade-in slide-in-from-top-2"
      >
        <span class="material-symbols-outlined text-amber-500">warning</span>
        <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">
          <strong>Importante:</strong> Al permitir un pago menor al 100%, los clientes podrán reservar pagando solo una parte. El monto restante deberá ser gestionado directamente con el cliente o al inicio del tour.
        </p>
      </div>
    </section>

    <!-- Tips -->
    <div class="p-6 bg-primary/5 rounded-3xl border border-primary/10 flex items-start gap-4">
      <span class="material-symbols-outlined text-primary">lightbulb</span>
      <div class="space-y-2">
        <h5 class="text-sm font-bold text-primary italic">Estructura Profesional de Precios</h5>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
          Este sistema de 3 niveles te permite una flexibilidad total: define precios base, ajusta según el origen del pasajero (Local vs Extranjero) y recompensa a los grupos grandes con descuentos automáticos por volumen de PAX.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import type { AgeStagePrice, NationalityPrice } from '~/stores/tourWizard'

const store = useTourWizardStore()

const addNationality = (ageStage: AgeStagePrice) => {
  ageStage.nationalities.push({
    id: crypto.randomUUID(),
    nationalityId: '',
    ageMin: ageStage.minAge,
    ageMax: ageStage.maxAge,
    ranges: [
      { id: crypto.randomUUID(), from: 1, to: 1, price: 0 }
    ]
  })
}

const removeNationality = (ageStage: AgeStagePrice, index: number) => {
  ageStage.nationalities.splice(index, 1)
}

const addRange = (nat: NationalityPrice) => {
  const lastRange = nat.ranges[nat.ranges.length - 1]
  const nextFrom = lastRange ? lastRange.to + 1 : 1
  
  nat.ranges.push({
    id: crypto.randomUUID(),
    from: nextFrom,
    to: nextFrom,
    price: lastRange ? lastRange.price : 0
  })
}

const removeRange = (nat: NationalityPrice, index: number) => {
  nat.ranges.splice(index, 1)
}
</script>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.7);
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-in {
  animation: fade-in 0.3s ease-out forwards;
}
</style>
