<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Información Básica del Tour</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- IDIOMA PRINCIPAL PRIMERO --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Idioma Principal del Tour * <span class="text-xs text-gray-500">(selecciona primero para generar el código)</span>
        </label>
        <select wire:model.live="primary_language_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="">Seleccionar idioma...</option>
            @foreach ($this->languages as $lang)
                <option value="{{ $lang['id'] }}">{{ $lang['country'] }} ({{ $lang['code'] }})</option>
            @endforeach
        </select>
        @error('primary_language_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
    </div>

    {{-- CÓDIGO AUTO-GENERADO --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Código del Tour * <span class="text-xs text-gray-500">(generado automáticamente)</span>
        </label>
        <input type="text" wire:model="code" readonly class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 cursor-not-allowed">
        @error('code') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        @if($code)
            <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ Código generado: <strong>{{ $code }}</strong></p>
        @endif
    </div>

    {{-- CIUDAD DE ORIGEN CON GOOGLE PLACES AUTOCOMPLETE --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Ciudad de salida *
            <span class="text-xs text-gray-500">(escribe para buscar)</span>
        </label>
        <input
            type="text"
            id="city_autocomplete"
            wire:model.blur="city_name"
            placeholder="Ej: Puno, Peru"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            autocomplete="off"
        >
        @error('city_name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        @if($city_name)
            <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ Ciudad seleccionada: <strong>{{ $city_name }}</strong></p>
        @endif
    </div>

    {{-- TIPO DE SERVICIO --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de servicio *</label>
        <select wire:model="service_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="tour">Tour</option>
            <option value="package">Paquete</option>
            <option value="experience">Experiencia</option>
            <option value="transport">Transporte</option>
        </select>
    </div>

    {{-- DIFICULTAD --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dificultad *</label>
        <select wire:model="difficulty" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="easy">Fácil</option>
            <option value="moderate">Moderado</option>
            <option value="hard">Difícil</option>
        </select>
    </div>

    {{-- CAPACIDAD MÁXIMA --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Capacidad máxima * <span class="text-xs text-gray-500">(por defecto: 99 personas)</span>
        </label>
        <input type="number" wire:model="capacity" min="1" max="999" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
        @error('capacity') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
    </div>

    {{-- SECCIÓN DE HORARIOS Y DURACIÓN --}}
    <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-6 mt-4">
        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">Horarios y Duración</h4>
    </div>

    {{-- HORA DE SALIDA --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hora de Salida *</label>
        <div class="flex gap-2">
            <input type="time" wire:model="departure_time" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <select wire:model="departure_period" class="w-24 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
            </select>
        </div>
        @error('departure_time') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        @error('departure_period') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
    </div>

    {{-- DURACIÓN APROXIMADA --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duración Aproximada *</label>
        <div class="flex gap-2">
            <input type="number" wire:model="duration_quantity" min="1" placeholder="2" class="w-24 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <select wire:model="duration_unit" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="minutes">Minutos</option>
                <option value="hours">Horas</option>
                <option value="days">Días</option>
            </select>
        </div>
        @error('duration_quantity') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        @error('duration_unit') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
    </div>

    {{-- ZONA HORARIA --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zona Horaria *</label>
        <select wire:model="timezone" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="America/Lima">Hora Peruana (GMT-5)</option>
            <option value="America/La_Paz">Hora Boliviana (GMT-4)</option>
        </select>
        @error('timezone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
    </div>

    {{-- TOUR ACTIVO --}}
    <div class="flex items-center">
        <input type="checkbox" wire:model="active" id="active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
        <label for="active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Tour activo</label>
    </div>
</div>
