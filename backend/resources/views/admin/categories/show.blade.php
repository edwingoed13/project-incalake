@extends('admin.layout')

@section('title', 'Ver Categoría')

@section('page-title', 'Detalles de la Categoría')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header with Actions -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $category->name }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Código: {{ $category->categoryCode->code }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.categories.edit', $category) }}"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Editar
            </a>
            <a href="{{ route('admin.categories.index') }}"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Category Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de la Categoría</h3>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Código</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $category->categoryCode->code }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre (Español)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $category->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre (Inglés)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $categoryEn->name ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Productos</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $category->products->count() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Products List -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Productos Asociados</h3>

                @if($category->products->count() > 0)
                    <div class="space-y-3">
                        @foreach($category->products as $product)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ json_decode($product->title)->es ?? 'Sin título' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product->code }}</p>
                                </div>
                                <a href="{{ route('admin.products.show', $product) }}"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Ver producto →
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay productos asociados a esta categoría</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Metadata -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Metadata</h3>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $category->id }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $category->created_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Última Actualización</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $category->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acciones</h3>

                <div class="space-y-3">
                    <a href="{{ route('admin.categories.edit', $category) }}"
                        class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Editar Categoría
                    </a>

                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                        onsubmit="return confirm('¿Estás seguro de eliminar esta categoría? Se eliminarán ambas versiones (ES/EN) y la relación con {{ $category->products->count() }} producto(s).');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="block w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Eliminar Categoría
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
