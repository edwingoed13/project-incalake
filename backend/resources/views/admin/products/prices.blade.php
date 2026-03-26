@extends('admin.layout')
@section('title', 'Precios del Producto')
@section('page-title', 'Precios: ' . $product->code)
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('admin.products.show', $product) }}" class="text-indigo-600 hover:text-indigo-900">Volver al producto</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lista de Precios</h3>

                @if($product->priceDetails->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rango/Categoria</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Precio</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Descripcion</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($product->priceDetails as $price)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $price->ageStage->name ?? $price->age_range ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $price->currency }} {{ number_format($price->price, 2) }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $price->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <form action="{{ route('admin.products.prices.destroy', [$product, $price]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Eliminar precio?')" class="text-red-600 hover:text-red-900">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No hay precios configurados</p>
                @endif
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Agregar Precio</h3>

                <form action="{{ route('admin.products.prices.store', $product) }}" method="POST">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Etapa de Edad</label>
                            <select name="age_stage_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar (opcional)</option>
                                @foreach($ageStages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rango de Edad</label>
                            <input type="text" name="age_range" placeholder="Ej: 0-2 años" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Precio *</label>
                            <input type="number" name="price" step="0.01" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Moneda *</label>
                            <select name="currency" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="USD">USD</option>
                                <option value="PEN">PEN</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripcion</label>
                            <input type="text" name="description" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Agregar Precio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
