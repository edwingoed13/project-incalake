@extends('admin.layout')

@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto: ' . $product->code)

@section('header-actions')
    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.products.update', $product) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Básica</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code (readonly) -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Código del Producto
                    </label>
                    <input type="text" id="code" value="{{ $product->code }}" readonly
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400 bg-gray-100 text-gray-600 shadow-sm cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">El código no se puede modificar</p>
                </div>

                <!-- Service -->
                <div>
                    <label for="service_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Servicio <span class="text-red-500">*</span>
                    </label>
                    <select name="service_id" id="service_id" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('service_id') border-red-500 @enderror">
                        <option value="">Seleccionar servicio</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id', $product->service_id) == $service->id ? 'selected' : '' }}>
                                {{ $service->page_title }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title Spanish -->
                <div class="md:col-span-2">
                    <label for="title_es" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Título (Español) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title_es" id="title_es" value="{{ old('title_es', $title->es ?? '') }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title_es') border-red-500 @enderror">
                    @error('title_es')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title English -->
                <div class="md:col-span-2">
                    <label for="title_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Título (Inglés) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title_en" id="title_en" value="{{ old('title_en', $title->en ?? '') }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title_en') border-red-500 @enderror">
                    @error('title_en')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subtitle Spanish -->
                <div class="md:col-span-2">
                    <label for="subtitle_es" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Subtítulo (Español)
                    </label>
                    <input type="text" name="subtitle_es" id="subtitle_es" value="{{ old('subtitle_es', $subtitle->es ?? '') }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Subtitle English -->
                <div class="md:col-span-2">
                    <label for="subtitle_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Subtítulo (Inglés)
                    </label>
                    <input type="text" name="subtitle_en" id="subtitle_en" value="{{ old('subtitle_en', $subtitle->en ?? '') }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalles del Tour</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Duration -->
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Duración <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="duration" id="duration" value="{{ old('duration', $product->duration) }}" required
                        placeholder="Ej: 1!2 (1 día, 2 noches)"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('duration') border-red-500 @enderror">
                    @error('duration')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Capacity -->
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Capacidad (Personas) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $product->capacity) }}" required min="1"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('capacity') border-red-500 @enderror">
                    @error('capacity')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Time -->
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Hora de Inicio
                    </label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time', $product->start_time ?? '09:00') }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Status -->
                <div class="flex items-center pt-8">
                    <input type="checkbox" name="status" id="status" value="1" {{ old('status', $product->status) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                    <label for="status" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        Producto activo (visible en web)
                    </label>
                </div>

                <!-- Nearest City -->
                <div>
                    <label for="nearest_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ciudad Cercana
                    </label>
                    <input type="text" name="nearest_city" id="nearest_city" value="{{ old('nearest_city', $product->nearest_city) }}"
                        placeholder="Ej: Puno"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Nearest Airport -->
                <div>
                    <label for="nearest_airport" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Aeropuerto Cercano
                    </label>
                    <input type="text" name="nearest_airport" id="nearest_airport" value="{{ old('nearest_airport', $product->nearest_airport) }}"
                        placeholder="Ej: Aeropuerto Inca Manco Cápac"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Categories Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Categorías</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($categories as $category)
                    <div class="flex items-center">
                        <input type="checkbox" name="categories[]" id="category_{{ $category->id }}" value="{{ $category->id }}"
                            {{ in_array($category->id, old('categories', $product->categories->pluck('id')->toArray())) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                        <label for="category_{{ $category->id }}" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            {{ $category->name }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Actualizar Producto
            </button>
        </div>
    </form>
</div>
@endsection
