@extends('admin.layout')

@section('title', 'Ver Producto')

@section('page-title', 'Detalles del Producto')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header with Actions -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ json_decode($product->title)->es ?? 'Sin título' }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Código: {{ $product->code }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.products.edit', $product) }}"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Editar
            </a>
            <a href="{{ route('admin.products.index') }}"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                Volver
            </a>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
        @if($product->status)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Activo
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                Inactivo
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Básica</h3>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Título (Español)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ json_decode($product->title)->es ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Título (Inglés)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ json_decode($product->title)->en ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subtítulo (Español)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ json_decode($product->subtitle)->es ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subtítulo (Inglés)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ json_decode($product->subtitle)->en ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Servicio</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->service->page_title ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Código del Producto</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $product->code }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Details -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalles del Tour</h3>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duración</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->duration ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Capacidad</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->capacity }} personas</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hora de Inicio</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->start_time ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ciudad Cercana</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->nearest_city ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aeropuerto Cercano</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->nearest_airport ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Categories -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Categorías</h3>

                @if($product->categories->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->categories as $category)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin categorías asignadas</p>
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
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->id }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Última Actualización</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    @if($product->deleted_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Eliminado</dt>
                        <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $product->deleted_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acciones</h3>

                <div class="space-y-3">
                    <a href="{{ route('admin.products.edit', $product) }}"
                        class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Editar Producto
                    </a>

                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                        onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="block w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Eliminar Producto
                        </button>
                    </form>
                </div>
            </div>

            <!-- Product Management -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Gestión del Producto</h3>

                <div class="space-y-3">
                    <a href="{{ route('admin.products.gallery', $product) }}"
                        class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        📷 Galería de Imágenes
                    </a>

                    <a href="{{ route('admin.products.tabs', $product) }}"
                        class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        📋 Tabs/Itinerarios
                    </a>

                    <a href="{{ route('admin.products.prices', $product) }}"
                        class="block w-full text-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        💲 Precios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
