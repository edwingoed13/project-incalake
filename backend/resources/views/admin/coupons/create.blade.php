@extends('admin.layout')

@section('title', 'Crear Cupón')
@section('page-title', 'Crear Nuevo Cupón de Descuento')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <form action="{{ route('admin.coupons.store') }}" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Cupón</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Código -->
                <div class="md:col-span-2">
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Código del Cupón <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required
                        placeholder="Ej: VERANO2026, DESCUENTO20"
                        style="text-transform: uppercase;"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('code') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">El código se convertirá automáticamente a mayúsculas</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipo de Descuento -->
                <div>
                    <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipo de Descuento <span class="text-red-500">*</span>
                    </label>
                    <select name="discount_type" id="discount_type" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('discount_type') border-red-500 @enderror"
                        onchange="toggleMaxDiscount()">
                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                        <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Monto Fijo ($)</option>
                    </select>
                    @error('discount_type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Valor del Descuento -->
                <div>
                    <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Valor del Descuento <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="discount_value" id="discount_value" value="{{ old('discount_value') }}"
                        step="0.01" min="0" required
                        placeholder="20"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('discount_value') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="discount_hint">Ingresa el porcentaje (ej: 20 para 20%)</p>
                    @error('discount_value')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Compra Mínima -->
                <div>
                    <label for="min_purchase" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Compra Mínima (USD)
                    </label>
                    <input type="number" name="min_purchase" id="min_purchase" value="{{ old('min_purchase') }}"
                        step="0.01" min="0"
                        placeholder="100.00"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('min_purchase') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional - Monto mínimo de compra requerido</p>
                    @error('min_purchase')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descuento Máximo (solo para porcentaje) -->
                <div id="max_discount_field">
                    <label for="max_discount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descuento Máximo (USD)
                    </label>
                    <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount') }}"
                        step="0.01" min="0"
                        placeholder="50.00"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('max_discount') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Solo para descuentos de porcentaje</p>
                    @error('max_discount')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Límite de Usos -->
                <div>
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Límite de Usos
                    </label>
                    <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit') }}"
                        min="1" step="1"
                        placeholder="100"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('usage_limit') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dejar vacío para usos ilimitados</p>
                    @error('usage_limit')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha de Inicio -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fecha de Inicio <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha de Fin -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fecha de Fin <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descripción -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descripción
                    </label>
                    <textarea name="description" id="description" rows="3"
                        placeholder="Descripción del cupón (uso interno)"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado Activo -->
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Cupón activo (puede ser usado inmediatamente)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.coupons.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Crear Cupón
            </button>
        </div>
    </form>
</div>

<script>
function toggleMaxDiscount() {
    const type = document.getElementById('discount_type').value;
    const field = document.getElementById('max_discount_field');
    const hint = document.getElementById('discount_hint');

    if (type === 'percentage') {
        field.style.display = 'block';
        hint.textContent = 'Ingresa el porcentaje (ej: 20 para 20%)';
    } else {
        field.style.display = 'none';
        hint.textContent = 'Ingresa el monto fijo en USD (ej: 10.00)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleMaxDiscount();

    // Convertir código a mayúsculas mientras escribe
    document.getElementById('code').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
});
</script>
@endsection