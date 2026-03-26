@extends('admin.layout')

@section('title', 'Editar Categoría')

@section('page-title', 'Editar Categoría: ' . $category->categoryCode->code)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Basic Information Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de la Categoría</h3>

            <div class="space-y-6">
                <!-- Code (readonly) -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Código
                    </label>
                    <input type="text" id="code" value="{{ $category->categoryCode->code }}" readonly
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400 bg-gray-100 text-gray-600 shadow-sm cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">El código no se puede modificar</p>
                </div>

                <!-- Name Spanish -->
                <div>
                    <label for="name_es" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre (Español) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name_es" id="name_es" value="{{ old('name_es', $category->name) }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name_es') border-red-500 @enderror">
                    @error('name_es')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name English -->
                <div>
                    <label for="name_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre (Inglés) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name_en" id="name_en" value="{{ old('name_en', $categoryEn->name ?? '') }}" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name_en') border-red-500 @enderror">
                    @error('name_en')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Actualizar Categoría
            </button>
        </div>
    </form>
</div>
@endsection
