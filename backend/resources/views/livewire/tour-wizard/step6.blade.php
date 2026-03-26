<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Opciones para Reservar</h3>

<div class="space-y-8">
    {{-- 1. POLÍTICAS Y CANCELACIONES --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            1. POLÍTICAS Y CANCELACIONES PARA LA ACTIVIDAD A RESERVAR
        </h4>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                SELECCIONE TIPO DE POLÍTICA:
            </label>
            <div class="space-y-2">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="policy_type" value="standard"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Política Stantard (Políticas pre-establecidas por Inca Lake)
                    </span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="policy_type" value="custom"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Política Personalizada (Política personalizada para cada actividad)
                    </span>
                </label>
            </div>
            @error('policy_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Editor TipTap para políticas (usa campos separados según el tipo) --}}
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <span class="{{ $policy_type === 'standard' ? '' : 'hidden' }}">
                    POLÍTICAS DE CANCELACIÓN (Pre-establecidas por Inca Lake - Editables):
                </span>
                <span class="{{ $policy_type === 'custom' ? '' : 'hidden' }}">
                    DESCRIPCIÓN DE LAS POLÍTICAS PARA LA ACTIVIDAD:
                </span>
            </label>

            {{-- Editor para política standard - usa policy_description --}}
            <div class="{{ $policy_type === 'standard' ? '' : 'hidden' }}">
                <input type="hidden" id="tiptap-policy-standard-editor-hidden" wire:model.defer="policy_description" value="{{ $policy_description ?? '' }}">

                <div class="tiptap-wrapper" wire:ignore>
                    <div class="tiptap-toolbar" data-editor="tiptap-policy-standard-editor">
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleBold()" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleItalic()" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleHeading(2)" title="H2">H2</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleHeading(3)" title="H3">H3</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleBulletList()" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].toggleOrderedList()" title="Lista numerada">1.</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].insertTable()" title="Tabla">⊞</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].undo()" title="Deshacer">↶</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-standard-editor'].redo()" title="Rehacer">↷</button>
                    </div>
                    <div id="tiptap-policy-standard-editor" class="tiptap-content"></div>
                </div>

                <div class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    ℹ️ Estas son las políticas estándar de Inca Lake (editables). Puede modificarlas según necesite.
                </div>
            </div>

            {{-- Editor para política personalizada - usa policy_description_custom --}}
            <div class="{{ $policy_type === 'custom' ? '' : 'hidden' }}">
                <input type="hidden" id="tiptap-policy-custom-editor-hidden" wire:model.defer="policy_description_custom" value="{{ $policy_description_custom ?? '' }}">

                <div class="tiptap-wrapper" wire:ignore>
                    <div class="tiptap-toolbar" data-editor="tiptap-policy-custom-editor">
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleBold()" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleItalic()" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleHeading(2)" title="H2">H2</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleHeading(3)" title="H3">H3</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleBulletList()" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].toggleOrderedList()" title="Lista numerada">1.</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].insertTable()" title="Tabla">⊞</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].undo()" title="Deshacer">↶</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-policy-custom-editor'].redo()" title="Rehacer">↷</button>
                    </div>
                    <div id="tiptap-policy-custom-editor" class="tiptap-content"></div>
                </div>
            </div>

            @error('policy_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            @error('policy_description_custom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- 2. TIEMPO DE ANTICIPACIÓN PARA LA RESERVA --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            2. INDIQUE TIEMPO DE ANTICIPACIÓN PARA LA RESERVA
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            El cliente no podrá reservar si el tiempo restante hasta el inicio del tour es menor al especificado.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tiempo de anticipación:
                </label>
                <input type="number" wire:model="booking_anticipation_quantity" min="1"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                @error('booking_anticipation_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Seleccione tiempo en:
                </label>
                <select wire:model="booking_anticipation_unit"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="hours">Horas</option>
                    <option value="days">Días</option>
                </select>
                @error('booking_anticipation_unit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                ℹ️ Ejemplo: Si el tour inicia a las 7:00 AM y configuras 5 horas de anticipación,
                los clientes podrán reservar hasta las 2:00 AM del mismo día.
            </p>
        </div>
    </div>

    {{-- 3. INFORMACIÓN OPERACIONAL --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            3. Información Operacional
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Selecciona la información que necesitas de los clientes:
        </p>

        <div class="mb-4 space-y-2">
            <label class="flex items-center cursor-pointer">
                <input type="radio" wire:model="data_requirement_type" value="leader"
                       class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Requerir datos solo del lider
                </span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="radio" wire:model="data_requirement_type" value="all"
                       class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Requerir todos los datos de los clientes
                </span>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
            @php
                $operationalFields = [
                    'peru_entry_date' => 'Fecha de ingreso al Perú (Para enviarte información útil, cuando estés aquí) Opcional',
                    'hotel_name' => 'Nombre de su hotel',
                    'passport_copy' => 'Copia de pasaporte o ID (enviar a reservas@incalake.com)',
                    'arrival_flight' => 'Vuelo de llegada',
                    'departure_flight' => 'Vuelo de salida',
                    'weight_kg' => 'Peso (kg)',
                    'height_m' => 'Altura (m)',
                    'arrival_bus_company' => 'Compania de bus en la que llega',
                    'departure_bus_company' => 'Compania de bus en la que se va',
                    'arrival_train' => 'Tren de llegada',
                    'departure_train' => 'Tren de Salida',
                ];
            @endphp

            @foreach($operationalFields as $key => $label)
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox" wire:model="operational_info_required" value="{{ $key }}"
                           class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 4. INFORMACIÓN PERSONAL --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            4. Información Personal
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @php
                $personalFields = [
                    'first_name' => 'Nombre',
                    'last_name' => 'Apellido',
                    'birthdate' => 'Fecha de Nacimiento',
                    'nationality' => 'Nacionalidad',
                    'phone_whatsapp' => 'Número de teléfono (whatsapp)',
                    'email' => 'E-mail',
                    'dietary_restrictions' => 'Restricciones Alimentarias',
                    'gender' => 'Género',
                ];
            @endphp

            @foreach($personalFields as $key => $label)
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox" wire:model="personal_info_required" value="{{ $key }}"
                           class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 5. OPCIONES DE RECOJO - MEJORADO PARA PERMITIR AMBAS OPCIONES --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            5. CONFIGURE OPCIONES DE RECOJO DISPONIBLES
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Configure las opciones de recojo que estarán disponibles para el cliente.
            Puede habilitar una o ambas opciones. El cliente elegirá su preferencia al momento de reservar.
        </p>

        {{-- OPCIÓN 1: PUNTO DE ENCUENTRO --}}
        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <label class="flex items-start cursor-pointer mb-3">
                <input type="checkbox" wire:model.live="enable_meeting_point" value="1"
                       class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <span class="ml-2">
                    <strong class="text-gray-700 dark:text-gray-300">Habilitar Punto de Encuentro</strong><br>
                    <span class="text-gray-500 dark:text-gray-400 text-xs">
                        Los clientes deberán presentarse en un punto específico
                    </span>
                </span>
            </label>

            @if($enable_meeting_point)
                <div class="ml-6 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción del punto de encuentro:
                        </label>
                        <textarea wire:model="meeting_point_description" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Ejemplo: Plaza de armas de Puno, frente a la Catedral"></textarea>
                    </div>

                    <button type="button"
                            onclick="window.openPickupModal('meeting_point')"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configurar en el Mapa
                    </button>

                    @if($meeting_point_lat && $meeting_point_lng)
                        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <p class="text-sm text-green-800 dark:text-green-200">
                                ✓ Punto configurado<br>
                                <span class="text-xs">Coordenadas: {{ round($meeting_point_lat, 6) }}, {{ round($meeting_point_lng, 6) }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- OPCIÓN 2: RECOJO DE HOTEL --}}
        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <label class="flex items-start cursor-pointer mb-3">
                <input type="checkbox" wire:model.live="enable_hotel_pickup" value="1"
                       class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <span class="ml-2">
                    <strong class="text-gray-700 dark:text-gray-300">Habilitar Recojo de Hotel</strong><br>
                    <span class="text-gray-500 dark:text-gray-400 text-xs">
                        Recojo en hoteles dentro de un radio específico
                    </span>
                </span>
            </label>

            @if($enable_hotel_pickup)
                <div class="ml-6 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción del área de recojo:
                        </label>
                        <textarea wire:model="pickup_location_description" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Ejemplo: Hoteles del centro de Puno y alrededores"></textarea>
                    </div>

                    <button type="button"
                            onclick="window.openPickupModal('hotel_pickup')"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configurar Radio en el Mapa
                    </button>

                    @if($pickup_center_lat && $pickup_center_lng && $pickup_radius_km)
                        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <p class="text-sm text-green-800 dark:text-green-200">
                                ✓ Radio configurado<br>
                                Radio: {{ $pickup_radius_km }} km ({{ $pickup_radius_km * 1000 }} metros)<br>
                                <span class="text-xs">Centro: {{ round($pickup_center_lat, 6) }}, {{ round($pickup_center_lng, 6) }}</span>
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Donde terminará la actividad (opcional):
                        </label>
                        <textarea wire:model="dropoff_location_description" rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Ejemplo: Retorno al mismo punto de recojo"></textarea>
                    </div>
                </div>
            @endif
        </div>

        @if(!$enable_meeting_point && !$enable_hotel_pickup)
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    ⚠️ Debe habilitar al menos una opción de recojo para que los clientes puedan reservar.
                </p>
            </div>
        @endif
    </div>

    {{-- 6. ASOCIAR GUÍAS --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
            6. ASOCIAR GUIAS A LA ACTIVIDAD/SERVICIO (OPCIONAL)
        </h4>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Indicar tipo de guía:
            </label>
            <div class="space-y-2">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="guide_type" value="live_guide"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Guía de tour en vivo</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="guide_type" value="audio_guide"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Audio Guía y Audífonos</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="guide_type" value="informative_brochures"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Folletos informativos</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="guide_type" value="no_guide"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sin Guía / No es necesario seleccionar el lenguage (Ejemplo: Tickets de entrada, pasajes)</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" wire:model.live="guide_type" value="none"
                           class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">No mostrar nada</span>
                </label>
            </div>
        </div>

        @if($guide_type === 'live_guide')
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Seleccione idiomas del guía de tour en vivo:
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @php
                        $availableLanguages = [
                            ['id' => 1, 'name' => 'Español', 'code' => 'ES'],
                            ['id' => 2, 'name' => 'Inglés', 'code' => 'EN'],
                            ['id' => 3, 'name' => 'Francés', 'code' => 'FR'],
                            ['id' => 4, 'name' => 'Alemán', 'code' => 'DE'],
                            ['id' => 5, 'name' => 'Portugués', 'code' => 'PT'],
                            ['id' => 6, 'name' => 'Italiano', 'code' => 'IT'],
                        ];
                    @endphp
                    @foreach($availableLanguages as $lang)
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="guide_languages" value="{{ $lang['id'] }}"
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $lang['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Resumen --}}
    <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
        <p class="text-sm text-green-800 dark:text-green-200">
            <strong>✓ Configuración guardada:</strong><br>
            Política: <strong>{{ $policy_type === 'standard' ? 'Estándar' : 'Personalizada' }}</strong><br>
            Anticipación: <strong>{{ $booking_anticipation_quantity }} {{ $booking_anticipation_unit === 'hours' ? 'horas' : 'días' }}</strong><br>
            Datos requeridos: <strong>{{ $data_requirement_type === 'leader' ? 'Solo del líder' : 'De todos los pasajeros' }}</strong><br>
            Campos operacionales: <strong>{{ count($operational_info_required ?? []) }}</strong><br>
            Campos personales: <strong>{{ count($personal_info_required ?? []) }}</strong><br>
            Opciones de recojo habilitadas:
            <strong>
                @if($enable_meeting_point && $enable_hotel_pickup)
                    Punto de encuentro y Recojo de hotel
                @elseif($enable_meeting_point)
                    Solo punto de encuentro
                @elseif($enable_hotel_pickup)
                    Solo recojo de hotel
                @else
                    Ninguna (⚠️ Debe configurar al menos una)
                @endif
            </strong><br>
            Tipo de guía: <strong>
                @if($guide_type === 'live_guide') Guía de tour en vivo
                @elseif($guide_type === 'audio_guide') Audio Guía
                @elseif($guide_type === 'informative_brochures') Folletos informativos
                @elseif($guide_type === 'no_guide') Sin Guía
                @else No mostrar
                @endif
            </strong>
            @if($guide_type === 'live_guide' && count($guide_languages ?? []) > 0)
                <br>Idiomas de guía: <strong>{{ count($guide_languages ?? []) }} seleccionados</strong>
            @endif
        </p>
    </div>
</div>

{{-- Include the pickup configuration modal --}}
@include('livewire.tour-wizard.step6-pickup-modal')

<script>
window.openPickupModal = function(type) {
    // Setear el tipo de configuración actual
    window.currentPickupConfigType = type;

    const modal = document.getElementById('pickupModal');
    if (modal) {
        // Actualizar título del modal según el tipo
        const title = modal.querySelector('.modal-title');
        if (title) {
            if (type === 'meeting_point') {
                title.textContent = 'Configurar Punto de Encuentro';
            } else {
                title.textContent = 'Configurar Radio de Recojo';
            }
        }
        modal.classList.remove('hidden');
    }
};

window.closePickupModal = function() {
    const modal = document.getElementById('pickupModal');
    if (modal) modal.classList.add('hidden');
};

// Inicializar editores TipTap cuando cambie el policy_type
function initPolicyEditors() {
    setTimeout(function() {
        // Verificar qué editor debe estar visible
        const standardEditor = document.getElementById('tiptap-policy-standard-editor');
        const customEditor = document.getElementById('tiptap-policy-custom-editor');

        if (standardEditor && !window.tiptapEditors?.['tiptap-policy-standard-editor']) {
            const editor = window.initTiptapEditor('tiptap-policy-standard-editor', 'policy_description');
            if (editor) {
                const hiddenInput = document.getElementById('tiptap-policy-standard-editor-hidden');
                const content = hiddenInput?.value;
                if (content) {
                    editor.commands.setContent(content);
                } else {
                    // Contenido por defecto
                    editor.commands.setContent(`<h3>Políticas de Cancelación</h3>
<table>
  <thead>
    <tr>
      <th>Periodo de Anticipación para Anulación</th>
      <th>Penalidad</th>
      <th>Detalles</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Hasta 48 horas antes del inicio del tour</td>
      <td>20% del total</td>
      <td>Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.</td>
    </tr>
    <tr>
      <td>Dentro de las 48 horas antes del inicio del tour</td>
      <td>100% del total</td>
      <td>Monto total acordado del servicio.</td>
    </tr>
  </tbody>
</table>`);
                }
            }
        }

        if (customEditor && !window.tiptapEditors?.['tiptap-policy-custom-editor']) {
            const editor = window.initTiptapEditor('tiptap-policy-custom-editor', 'policy_description_custom');
            if (editor) {
                const hiddenInput = document.getElementById('tiptap-policy-custom-editor-hidden');
                const content = hiddenInput?.value;
                if (content) {
                    editor.commands.setContent(content);
                }
            }
        }
    }, 200);
}

// Ejecutar al cargar la página
initPolicyEditors();

// Re-ejecutar cuando Livewire actualice el componente
document.addEventListener('livewire:update', initPolicyEditors);
if (window.Livewire) {
    Livewire.hook('message.processed', () => {
        initPolicyEditors();
    });
}
</script>