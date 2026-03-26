{{-- Sección de Tasas/Impuestos y Pago Parcial --}}
<div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        Configuración de Precios Generales
    </h4>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Tasas e Impuestos --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tasas e impuestos a aplicar (%)
            </label>
            <div class="flex items-center">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    wire:model="tax_percentage"
                    class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"
                    placeholder="7.00"
                >
                <span class="ml-2 text-gray-700 dark:text-gray-300">%</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Este porcentaje se añadirá al precio final del tour
            </p>
            @error('tax_percentage')
                <span class="text-red-600 text-xs block mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Porcentaje de Primera Cuota --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Porcentaje de primera cuota (%)
            </label>
            <div class="flex items-center">
                <input
                    type="number"
                    min="1"
                    max="100"
                    wire:model="advance_payment_percentage"
                    class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"
                    placeholder="100"
                >
                <span class="ml-2 text-gray-700 dark:text-gray-300">%</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                100% = Pago completo | Menor = Pago parcial permitido
            </p>
            @error('advance_payment_percentage')
                <span class="text-red-600 text-xs block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Información adicional --}}
    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
            <strong>⚠️ Importante:</strong> Si el porcentaje de primera cuota es menor al 100%, los clientes podrán hacer un pago parcial para reservar y coordinar el resto con la agencia.
        </p>
    </div>
</div>