{{-- Selector de Método de Pago --}}
<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        Método de Pago Aceptado *
    </h4>
    <div class="space-y-3">
        <label class="flex items-center cursor-pointer">
            <input type="radio" wire:model="payment_method" value="all" class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 focus:ring-indigo-500">
            <span class="ml-3 text-gray-700 dark:text-gray-300">Todos los métodos (PayPal y Culqi)</span>
        </label>
        <label class="flex items-center cursor-pointer">
            <input type="radio" wire:model="payment_method" value="paypal" class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 focus:ring-indigo-500">
            <span class="ml-3 text-gray-700 dark:text-gray-300">Solo PayPal</span>
        </label>
        <label class="flex items-center cursor-pointer">
            <input type="radio" wire:model="payment_method" value="culqi" class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 focus:ring-indigo-500">
            <span class="ml-3 text-gray-700 dark:text-gray-300">Solo Culqi</span>
        </label>
    </div>
    @error('payment_method')
        <span class="text-red-600 text-xs block mt-2">{{ $message }}</span>
    @enderror
</div>

<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Precios por Etapa de Edad, Nacionalidad y Cantidad</h3>

<div class="space-y-6">
    {{-- Nivel 1: ETAPAS DE EDAD --}}
    @foreach ($this->ageStages as $ageStage)
        <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6">
            {{-- Header de Edad con Checkbox para Activar --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $ageStage['description'] }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Rango de edad: {{ $ageStage['min_age'] }} - {{ $ageStage['max_age'] == 999 ? '+' : $ageStage['max_age'] }} años
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model.live="prices.{{ $ageStage['id'] }}.active"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
                    </label>
                </div>
            </div>

            @if(isset($prices[$ageStage['id']]) &&
                isset($prices[$ageStage['id']]['active']) &&
                $prices[$ageStage['id']]['active'] === true)
                {{-- Botón para Agregar Nacionalidad --}}
                <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="addNationality({{ $ageStage['id'] }})"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Agregar Nacionalidad
                    </button>
                </div>

                {{-- Nivel 2: NACIONALIDADES --}}
                <div class="space-y-4">
                    @foreach(($prices[$ageStage['id']]['nationalities'] ?? []) as $natIndex => $nationalityData)
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-300 dark:border-gray-600 p-4">
                            {{-- Header de Nacionalidad --}}
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    {{-- Selector de Nacionalidad --}}
                                    <div class="flex items-center gap-4 mb-3">
                                        <select
                                            wire:model.live="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.nationality_id"
                                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                        >
                                            <option value="">-- Seleccionar Nacionalidad --</option>
                                            @foreach($this->nationalities as $nationality)
                                                <option value="{{ $nationality['id'] }}">{{ $nationality['description'] }}</option>
                                            @endforeach
                                        </select>

                                        <button
                                            type="button"
                                            wire:click="removeNationality({{ $ageStage['id'] }}, {{ $natIndex }})"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 px-3 py-2 text-sm font-medium"
                                            title="Eliminar nacionalidad"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Rango de Edad para esta Nacionalidad --}}
                                    @if(isset($prices[$ageStage['id']]['nationalities'][$natIndex]['nationality_id']) &&
                                        !empty($prices[$ageStage['id']]['nationalities'][$natIndex]['nationality_id']))
                                        <div class="flex items-center gap-4 mb-4">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Rango de edad:
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    wire:model="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.age_min"
                                                    class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                                    placeholder="{{ $ageStage['min_age'] }}"
                                                    min="{{ $ageStage['min_age'] }}"
                                                    max="{{ $ageStage['max_age'] }}"
                                                >
                                                <span class="text-gray-500">-</span>
                                                <input
                                                    type="number"
                                                    wire:model="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.age_max"
                                                    class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                                    placeholder="{{ $ageStage['max_age'] }}"
                                                    min="{{ $ageStage['min_age'] }}"
                                                    max="{{ $ageStage['max_age'] }}"
                                                >
                                                <span class="text-xs text-gray-500 dark:text-gray-400">años</span>
                                            </div>
                                        </div>

                                        {{-- Nivel 3: RANGOS DE PRECIOS (Cantidad de PAX) --}}
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                            <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Precios por Cantidad de PAX</h5>

                                            {{-- Tabla de Rangos --}}
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                                        <tr>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Desde (pax)</th>
                                                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">-</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hasta (pax)</th>
                                                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">:</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Precio USD</th>
                                                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                        @foreach(($prices[$ageStage['id']]['nationalities'][$natIndex]['ranges'] ?? []) as $rangeIndex => $range)
                                                            <tr class="bg-white dark:bg-gray-800">
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        wire:model="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.ranges.{{ $rangeIndex }}.from"
                                                                        min="1"
                                                                        class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                                                        placeholder="1"
                                                                    >
                                                                </td>
                                                                <td class="px-2 py-2 text-center text-gray-500">-</td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        wire:model="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.ranges.{{ $rangeIndex }}.to"
                                                                        min="1"
                                                                        class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                                                        placeholder="10"
                                                                    >
                                                                </td>
                                                                <td class="px-2 py-2 text-center text-gray-500">:</td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        wire:model="prices.{{ $ageStage['id'] }}.nationalities.{{ $natIndex }}.ranges.{{ $rangeIndex }}.price"
                                                                        step="0.01"
                                                                        min="0"
                                                                        class="w-28 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                                                        placeholder="0.00"
                                                                    >
                                                                </td>
                                                                <td class="px-2 py-2 text-center">
                                                                    <button
                                                                        type="button"
                                                                        wire:click="removePriceRange({{ $ageStage['id'] }}, {{ $natIndex }}, {{ $rangeIndex }})"
                                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                                    >
                                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                                        </svg>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- Botón Agregar Rango de Precio --}}
                                            <div class="mt-3">
                                                <button
                                                    type="button"
                                                    wire:click="addPriceRange({{ $ageStage['id'] }}, {{ $natIndex }})"
                                                    class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Agregar Rango de Precio
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">Selecciona una nacionalidad para configurar precios</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if(empty($prices[$ageStage['id']]['nationalities']))
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic text-center py-4">
                            Haz clic en "Agregar Nacionalidad" para configurar precios
                        </p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Activa esta etapa de edad para configurar precios</p>
            @endif
        </div>
    @endforeach
</div>

@include('livewire.tour-wizard.tax-section')
<div class="mt-6 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
    <p class="text-sm text-blue-800 dark:text-blue-200">
        💡 <strong>Tip:</strong> El sistema de 3 niveles te permite definir:<br>
        <strong>1.</strong> Etapa de Edad (Niño, Adulto, etc.)<br>
        <strong>2.</strong> Nacionalidad (General, Peruano, Latinoamericano, Extranjero)<br>
        <strong>3.</strong> Cantidad de PAX con precios escalonados (1-5: $50, 6-10: $45, etc.)
    </p>
</div>
