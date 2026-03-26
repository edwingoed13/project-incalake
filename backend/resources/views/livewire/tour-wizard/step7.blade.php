<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Categorías del Tour</h3>

<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Selecciona las categorías que mejor describen este tour. Puedes seleccionar múltiples categorías.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($this->categories as $category)
            @php
                $categoryObj = \App\Models\CategoryNew::find($category['id']);
            @endphp
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input
                        type="checkbox"
                        wire:model="selectedCategories"
                        value="{{ $category['id'] }}"
                        id="category-{{ $category['id'] }}"
                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    >
                </div>
                <div class="ml-3 text-sm">
                    <label for="category-{{ $category['id'] }}" class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        {{ $categoryObj ? $categoryObj->getName('es') : 'Sin nombre' }}
                    </label>
                    @if($categoryObj)
                        @php
                            $description = $categoryObj->getDescription('es');
                        @endphp
                        @if($description)
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $description }}</p>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if(count($selectedCategories) > 0)
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>{{ count($selectedCategories) }} categoría(s) seleccionada(s)</strong>
            </p>
        </div>
    @else
        <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                ⚠️ Se recomienda seleccionar al menos una categoría para mejorar la visibilidad del tour.
            </p>
        </div>
    @endif
</div>
