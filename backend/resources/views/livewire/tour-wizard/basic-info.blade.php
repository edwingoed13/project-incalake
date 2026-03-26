<div x-data>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Información Básica</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Idioma Principal *</label>
                <select wire:model.live="primary_language_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="">-- Seleccionar --</option>
                    @foreach($this->languages as $language)
                        <option value="{{ $language['id'] }}" {{ $primary_language_id == $language['id'] ? 'selected' : '' }}>
                            {{ $language['name'] }} ({{ $language['code'] }})
                        </option>
                    @endforeach
                </select>
                @error('primary_language_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código del Tour *</label>
                <input type="text" wire:model="code" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" placeholder="Ej: ES-1234">
                @error('code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ciudad *</label>
                <input type="text" wire:model="city_name" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" placeholder="Ej: Cusco">
                @error('city_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Servicio *</label>
                <select wire:model="service_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="tour" {{ $service_type == 'tour' ? 'selected' : '' }}>Tour</option>
                    <option value="package" {{ $service_type == 'package' ? 'selected' : '' }}>Package</option>
                    <option value="experience" {{ $service_type == 'experience' ? 'selected' : '' }}>Experience</option>
                    <option value="transport" {{ $service_type == 'transport' ? 'selected' : '' }}>Transport</option>
                </select>
                @error('service_type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dificultad *</label>
                <select wire:model="difficulty" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="easy" {{ $difficulty == 'easy' ? 'selected' : '' }}>Fácil</option>
                    <option value="moderate" {{ $difficulty == 'moderate' ? 'selected' : '' }}>Moderada</option>
                    <option value="hard" {{ $difficulty == 'hard' ? 'selected' : '' }}>Difícil</option>
                </select>
                @error('difficulty') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Público Objetivo *</label>
                <select wire:model="target_audience" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="all" {{ $target_audience == 'all' ? 'selected' : '' }}>Todos</option>
                    <option value="families" {{ $target_audience == 'families' ? 'selected' : '' }}>Familias</option>
                    <option value="adults" {{ $target_audience == 'adults' ? 'selected' : '' }}>Adultos</option>
                    <option value="adventure" {{ $target_audience == 'adventure' ? 'selected' : '' }}>Aventura</option>
                    <option value="seniors" {{ $target_audience == 'seniors' ? 'selected' : '' }}>Personas Mayores</option>
                </select>
                @error('target_audience') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Capacidad Máxima *</label>
                <input type="number" wire:model="capacity" min="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" placeholder="Ej: 99">
                @error('capacity') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cupos Disponibles</label>
                <input type="number" wire:model="cupos" min="0" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" placeholder="Ej: 50">
            </div>
        </div>

        <h4 class="text-md font-semibold mt-6 mb-4">Horario y Duración</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hora de Salida *</label>
                <div class="flex">
                    <input type="time" wire:model="departure_time" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg-l p-2.5 dark:bg-gray-700 dark:text-white flex-1">
                    <select wire:model="departure_period" class="border border-gray-300 dark:border-gray-600 rounded-lg-r p-2.5 dark:bg-gray-700 dark:text-white">
                        <option value="AM" {{ $departure_period == 'AM' ? 'selected' : '' }}>AM</option>
                        <option value="PM" {{ $departure_period == 'PM' ? 'selected' : '' }}>PM</option>
                    </select>
                </div>
                @error('departure_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                @error('departure_period') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duración (Cantidad) *</label>
                <input type="number" wire:model="duration_quantity" min="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" placeholder="Ej: 2">
                @error('duration_quantity') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unidad de Duración *</label>
                <select wire:model="duration_unit" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="days" {{ $duration_unit == 'days' ? 'selected' : '' }}>Días</option>
                    <option value="hours" {{ $duration_unit == 'hours' ? 'selected' : '' }}>Horas</option>
                    <option value="minutes" {{ $duration_unit == 'minutes' ? 'selected' : '' }}>Minutos</option>
                </select>
                @error('duration_unit') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zona Horaria *</label>
                <select wire:model="timezone" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                    <option value="America/Lima" {{ $timezone == 'America/Lima' ? 'selected' : '' }}>Perú (GMT-5)</option>
                    <option value="America/La_Paz" {{ $timezone == 'America/La_Paz' ? 'selected' : '' }}>Bolivia (GMT-4)</option>
                    <option value="UTC" {{ $timezone == 'UTC' ? 'selected' : '' }}>UTC</option>
                </select>
                @error('timezone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>