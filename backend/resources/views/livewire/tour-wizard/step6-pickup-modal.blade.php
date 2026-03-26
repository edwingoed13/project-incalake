{{-- Modal de Configuración de Recojo con Google Maps --}}
<div id="pickupModal" class="fixed inset-0 z-50 hidden overflow-y-auto" onclick="if(event.target.id==='pickupModal') window.closePickupModal()">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" ></div>

        {{-- Modal panel --}}
        <div onclick="event.stopPropagation()" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle" style="max-width: 1200px; width: 90%;">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        <span id="modalTitle">Configurar Opciones de Recojo</span>
                    </h3>
                    <button type="button" onclick="window.closePickupModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Controles del mapa --}}
                    <div class="lg:col-span-1 space-y-4">
                        {{-- Búsqueda de ubicación --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Buscar ubicación:
                            </label>
                            <input type="text"
                                id="searchLocation"
                                placeholder="Ej: Plaza de Armas Puno"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                            <button type="button"
                                onclick="window.searchLocation()"
                                class="mt-2 w-full px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Buscar
                            </button>
                        </div>

                        <div id="meetingPointControls" style="display:none;">
                            {{-- Descripción del punto de encuentro --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Descripción del punto:
                                </label>
                                <textarea
                                    id="meetingPointDesc"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    placeholder="Ej: Frente a la Catedral de Puno"
                                   ></textarea>
                            </div>

                            {{-- Coordenadas del punto --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Coordenadas del punto:
                                </label>
                                <div class="space-y-2">
                                    <input type="text"
                                        id="meetingLat"
                                        readonly
                                        placeholder="Latitud"
                                        class="w-full rounded-md bg-gray-100 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                    <input type="text"
                                        id="meetingLng"
                                        readonly
                                        placeholder="Longitud"
                                        class="w-full rounded-md bg-gray-100 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                </div>
                            </div>
                        </div>

                        <div id="hotelPickupControls" style="display:none;">
                            {{-- Configuración de radio de recojo --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Radio de recojo:
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="number"
                                        id="pickupRadiusKm"
                                        min="0.1"
                                        max="10"
                                        step="0.1"
                                        value="1"
                                        onchange="window.updateRadius(this.value)"
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">km</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    = <span id="radiusInMeters">1000</span> metros
                                </p>
                            </div>

                            {{-- Descripción del centro --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Descripción del centro:
                                </label>
                                <textarea
                                    id="pickupCenterDesc"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    placeholder="Ej: Plaza de Armas de Puno"
                                   ></textarea>
                            </div>

                            {{-- Coordenadas del centro --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Centro del radio:
                                </label>
                                <div class="space-y-2">
                                    <input type="text"
                                        id="centerLat"
                                        readonly
                                        placeholder="Latitud"
                                        class="w-full rounded-md bg-gray-100 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                    <input type="text"
                                        id="centerLng"
                                        readonly
                                        placeholder="Longitud"
                                        class="w-full rounded-md bg-gray-100 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- Instrucciones --}}
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                            <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                <strong>Instrucciones:</strong><br>
                                <span id="modalInstructions">
                                    • Haz clic en el mapa para marcar ubicaciones<br>
                                    • Puedes arrastrar los marcadores para ajustar
                                </span>
                            </p>
                        </div>
                    </div>

                    {{-- Mapa --}}
                    <div class="lg:col-span-2">
                        <div id="pickupMap" class="w-full h-96 lg:h-full rounded-lg border border-gray-300 dark:border-gray-600" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                    onclick="window.savePickupConfig()"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Guardar Configuración
                </button>
                <button type="button"
                    onclick="window.closePickupModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    console.log('✅ Script inline cargando...');
    window.openPickupModal = function() {
        console.log('📍 Función openPickupModal llamada!');
        const modal = document.getElementById('pickupModal');
        if (modal) {
            modal.classList.remove('hidden');
            console.log('✅ Modal abierto');
        } else {
            console.error('❌ Modal no encontrado');
        }
    };
    window.closePickupModal = function() {
        document.getElementById('pickupModal')?.classList.add('hidden');
    };
    const btn = document.getElementById('openPickupModalBtn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Click en botón detectado!');
            window.openPickupModal();
        });
        console.log('✅ Event listener agregado al botón');
    } else {
        console.log('⚠️ Botón no encontrado al cargar script');
    }
    console.log('✅ Script inline cargado. window.openPickupModal:', typeof window.openPickupModal);
</script><script src="/js/pickup-modal-complete.js"></script>
