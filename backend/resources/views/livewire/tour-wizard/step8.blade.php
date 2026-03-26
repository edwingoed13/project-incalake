<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg p-6 text-white">
        <h2 class="text-2xl font-bold flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Disponibilidad y Calendario
        </h2>
        <p class="mt-2 text-indigo-100">Configure la disponibilidad, bloqueos y ofertas especiales del tour</p>
    </div>

    {{-- Requerir Disponibilidad --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input
                    type="checkbox"
                    id="require_availability"
                    wire:model="require_availability"
                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                >
            </div>
            <div class="ml-3">
                <label for="require_availability" class="font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                    Requerir verificación de disponibilidad antes de la compra
                </label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Al activar este campo, los clientes deberán verificar la disponibilidad antes de poder comprar el servicio/actividad.
                </p>
            </div>
        </div>
    </div>

    {{-- Tabs: Disponibilidad, Bloqueos, Ofertas --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
         x-data="{ activeTab: 'availability' }">

        {{-- Tab Headers --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button
                    type="button"
                    @click="activeTab = 'availability'"
                    :class="activeTab === 'availability' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Disponibilidad
                </button>
                <button
                    type="button"
                    @click="activeTab = 'blocks'"
                    :class="activeTab === 'blocks' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                    Bloqueos
                </button>
                <button
                    type="button"
                    @click="activeTab = 'offers'"
                    :class="activeTab === 'offers' ? 'border-yellow-600 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                    Ofertas
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- DISPONIBILIDAD TAB --}}
            <div x-show="activeTab === 'availability'" x-transition
                 x-data="{
                     availStart: @entangle('availability_data.start'),
                     availEnd: @entangle('availability_data.end'),
                     activeDays: @entangle('availability_data.active_days'),
                     specialDays: @entangle('availability_data.special_days')
                 }"
                 x-init="
                     if (!activeDays) activeDays = [0,1,2,3,4,5,6];
                     if (!specialDays) specialDays = [];
                 ">
                <div class="space-y-6">
                    <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                    <strong>Importante:</strong> Para cualquier cambio haga clic en el botón "Guardar" al final del wizard.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Fecha Desde --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-600 text-white rounded-full text-xs mr-2">1</span>
                                Fecha Desde
                            </label>
                            <input
                                type="date"
                                x-model="availStart"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Seleccione fecha"
                            >
                        </div>

                        {{-- Fecha Hasta --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-600 text-white rounded-full text-xs mr-2">2</span>
                                Fecha Hasta
                            </label>
                            <input
                                type="date"
                                x-model="availEnd"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Seleccione fecha"
                            >
                        </div>
                    </div>

                    {{-- Días Disponibles --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-600 text-white rounded-full text-xs mr-2">3</span>
                            Días Disponibles
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6, 'Domingo' => 0] as $day => $value)
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        value="{{ $value }}"
                                        x-model="activeDays"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Bloquear Feriados --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-600 text-white rounded-full text-xs mr-2">4</span>
                            Bloquear Feriados y Días Especiales
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            Al seleccionar se bloquearán las fechas de todos los años que estén dentro de la disponibilidad
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    value="25-12"
                                    x-model="specialDays"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    NAVIDAD <span class="text-gray-500">(25 de Diciembre)</span>
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    value="31-12"
                                    x-model="specialDays"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    FIN DE AÑO <span class="text-gray-500">(31 de Diciembre)</span>
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    value="01-01"
                                    x-model="specialDays"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    AÑO NUEVO <span class="text-gray-500">(01 de Enero)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Calendario Visual --}}
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Vista de Calendario
                        </h4>

                        <div
                            x-data="availabilityCalendarComponent()"
                            x-init="
                                availabilityStart = availStart;
                                availabilityEnd = availEnd;
                                activeDays = activeDays;
                                specialDaysBlocked = specialDays;
                                init();

                                // Watch for changes and update calendar
                                $watch('availStart', value => { availabilityStart = value; updateCalendarDisplay(); });
                                $watch('availEnd', value => { availabilityEnd = value; updateCalendarDisplay(); });
                                $watch('activeDays', value => { activeDays = value; updateCalendarDisplay(); });
                                $watch('specialDays', value => { specialDaysBlocked = value; updateCalendarDisplay(); });
                            "
                            wire:ignore
                        >
                            <div id="availability-calendar" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"></div>

                            {{-- Leyenda --}}
                            <div class="mt-4 flex flex-wrap gap-4 text-xs">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded" style="background-color: #dcfce7; border: 1px solid #22c55e;"></div>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400">Disponible</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded" style="background-color: #fee2e2; opacity: 0.5;"></div>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400">Día de la semana no disponible</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded" style="background-color: #fef3c7; border: 1px solid #f59e0b;"></div>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400">Feriado bloqueado</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen Visual --}}
                    <div class="bg-indigo-50 dark:bg-indigo-900 rounded-lg p-4 mt-4">
                        <h4 class="text-sm font-medium text-indigo-800 dark:text-indigo-200 mb-3">
                            📅 Resumen de Disponibilidad
                        </h4>
                        <div class="space-y-2 text-sm text-indigo-700 dark:text-indigo-300">
                            <p x-show="availStart && availEnd">
                                <strong>Período:</strong>
                                <span x-text="availStart"></span> al <span x-text="availEnd"></span>
                            </p>
                            <p>
                                <strong>Días activos:</strong>
                                <span x-text="activeDays.length"></span> de 7 días
                            </p>
                            <p x-show="specialDays.length > 0">
                                <strong>Feriados bloqueados:</strong>
                                <span x-text="specialDays.length"></span> festivos
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUEOS TAB --}}
            <div x-show="activeTab === 'blocks'" x-transition>
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Bloqueos de Fechas</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Configure fechas específicas donde el tour no estará disponible</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Funcionalidad en desarrollo...</p>
                </div>
            </div>

            {{-- OFERTAS TAB --}}
            <div x-show="activeTab === 'offers'" x-transition>
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-yellow-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Ofertas Especiales</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Configure descuentos para fechas específicas</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Funcionalidad en desarrollo...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    💡 Tip: Calendario Interactivo
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>El calendario permitirá visualizar en tiempo real las fechas disponibles, bloqueadas y con ofertas especiales.</p>
                </div>
            </div>
        </div>
    </div>
</div>
