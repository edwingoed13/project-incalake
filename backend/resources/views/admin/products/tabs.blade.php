@extends('admin.layout')
@section('title', 'Tabs del Producto')
@section('page-title', 'Tabs/Itinerarios: ' . $product->code)
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
        <div class="lg:col-span-2 space-y-6">
            @if($product->tab)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tab Principal</h3>
                        <form action="{{ route('admin.products.tabs.destroy', [$product, $product->tab->id]) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Eliminar tab principal?')" class="text-red-600 hover:text-red-900">Eliminar</button>
                        </form>
                    </div>
                    <p class="text-sm text-gray-900 dark:text-white"><strong>ES:</strong> {{ json_decode($product->tab->title)->es ?? '-' }}</p>
                    <p class="text-sm text-gray-900 dark:text-white"><strong>EN:</strong> {{ json_decode($product->tab->title)->en ?? '-' }}</p>
                </div>
            @endif

            @if($product->additionalTabs->count() > 0)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tabs Adicionales</h3>
                    <div class="space-y-4">
                        @foreach($product->additionalTabs as $tab)
                            <div class="border-b dark:border-gray-700 pb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-white"><strong>ES:</strong> {{ json_decode($tab->title)->es ?? '-' }}</p>
                                        <p class="text-sm text-gray-900 dark:text-white"><strong>EN:</strong> {{ json_decode($tab->title)->en ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">Orden: {{ $tab->order }}</p>
                                    </div>
                                    <form action="{{ route('admin.products.tabs.destroy', [$product, $tab->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Eliminar tab?')" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!$product->tab && $product->additionalTabs->count() == 0)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No hay tabs configurados</p>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Agregar Tab</h3>

                <form action="{{ route('admin.products.tabs.store', $product) }}" method="POST">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                            <select name="type" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="main">Tab Principal</option>
                                <option value="additional">Tab Adicional</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Titulo (ES)</label>
                            <input type="text" name="title_es" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Titulo (EN)</label>
                            <input type="text" name="title_en" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contenido (ES)</label>
                            <textarea name="content_es" rows="4" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contenido (EN)</label>
                            <textarea name="content_en" rows="4" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Orden</label>
                            <input type="number" name="order" value="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Agregar Tab
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
