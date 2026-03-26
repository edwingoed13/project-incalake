<div x-data>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Método de Pago y Precios</h3>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Método de Pago *</label>
            <select wire:model="payment_method" class="w-full md:w-1/3 border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white">
                <option value="paypal" {{ $payment_method == 'paypal' ? 'selected' : '' }}>PayPal</option>
                <option value="culqi" {{ $payment_method == 'culqi' ? 'selected' : '' }}>Culqi</option>
                <option value="all" {{ $payment_method == 'all' ? 'selected' : '' }}>Todos</option>
            </select>
            @error('payment_method') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <h4 class="text-md font-semibold mb-4">Configuración de Precios por Etapa de Edad</h4>

        @foreach($this->ageStages as $ageStage)
            @php
                $ageStagePrices = $prices[$ageStage['id']] ?? ['active' => true, 'ranges' => []];
            @endphp

            <div class="mb-6 border rounded-lg p-4 {{ $ageStagePrices['active'] ?? false ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700 opacity-60' }}">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-medium">
                        {{ $ageStage['name'] }} 
                        <span class="text-sm text-gray-500">({{ $ageStage['min_age'] }} - {{ $ageStage['max_age'] }} años)</span>
                    </h5>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                            wire:model.live="prices.{{ $ageStage['id'] }}.active" 
                            wire:click="togglePriceRange({{ $ageStage['id'] }})"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-2 text-sm">Activar</span>
                    </label>
                </div>

                @if(isset($ageStagePrices['ranges']) && count($ageStagePrices['ranges']) > 0)
                    @foreach($ageStagePrices['ranges'] as $rangeIndex => $range)
                        <div class="flex items-end gap-3 mb-3">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Desde</label>
                                <input type="number" 
                                    wire:model="prices.{{ $ageStage['id'] }}.ranges.{{ $rangeIndex }}.from" 
                                    min="1" 
                                    wire:keydown.enter.prevent
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 dark:bg-gray-700 dark:text-white"
                                    @if((!($ageStagePrices['active'] ?? false))) disabled @endif>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                                <input type="number" 
                                    wire:model="prices.{{ $ageStage['id'] }}.ranges.{{ $rangeIndex }}.to" 
                                    min="1" 
                                    wire:keydown.enter.prevent
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 dark:bg-gray-700 dark:text-white"
                                    @if((!($ageStagePrices['active'] ?? false))) disabled @endif>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Precio (USD)</label>
                                <input type="number" 
                                    wire:model="prices.{{ $ageStage['id'] }}.ranges.{{ $rangeIndex }}.price" 
                                    min="0.01" step="0.01" 
                                    wire:keydown.enter.prevent
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 dark:bg-gray-700 dark:text-white"
                                    @if((!($ageStagePrices['active'] ?? false))) disabled @endif>
                            </div>
                            <button 
                                type="button"
                                wire:click="removePriceRange({{ $ageStage['id'] }}, {{ $rangeIndex }})"
                                wire:confirm="¿Eliminar este rango de precios?"
                                class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                x-tooltip="'Eliminar'">
                                ×
                            </button>
                        </div>
                        @error("prices.{$ageStage['id']}.ranges.{$rangeIndex}.from") <p class="text-red-500 text-sm mb-1">{{ $message }}</p> @enderror
                        @error("prices.{$ageStage['id']}.ranges.{$rangeIndex}.to") <p class="text-red-500 text-sm mb-1">{{ $message }}</p> @enderror
                        @error("prices.{$ageStage['id']}.ranges.{$rangeIndex}.price") <p class="text-red-500 text-sm mb-1">{{ $message }}</p> @enderror
                    @endforeach
                @endif

                <button 
                    type="button"
                    wire:click="addPriceRange({{ $ageStage['id'] }})"
                    class="mt-2 px-3 py-1.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm"
                    @if(!($ageStagePrices['active'] ?? false)) disabled @endif>
                    + Agregar Rango
                </button>
            </div>
        @endforeach

        @error('prices') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
    </div>
</div>