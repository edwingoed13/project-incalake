<div x-data>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Categorías y Puntos del Mapa</h3>

        <h4 class="text-md font-medium mb-3">Seleccionar Categorías</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
            @foreach($this->categories as $category)
                <button type="button"
                    wire:click="toggleCategory({{ $category['id'] }})"
                    :class="jsIncludes($selectedCategories, {{ $category['id'] }})
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                    class="p-3 rounded-lg border-2 hover:border-indigo-400 transition-colors text-left">
                    <span class="font-medium">{{ $this->getCategoryName($category) }}</span>
                </button>
            @endforeach
        </div>

        @if(count($selectedCategories) === 0)
            <p class="text-gray-500 dark:text-gray-400 text-sm">No has seleccionado ninguna categoría.</p>
        @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-4">
                <p class="text-green-700 dark:text-green-400 text-sm">
                    ✓ {{ count($selectedCategories) }} {{ count($selectedCategories) === 1 ? 'categoría seleccionada' : 'categorías seleccionadas' }}
                </p>
            </div>
        @endif
    </div>
</div>

<script>
function jsIncludes(arr, item) {
    return Array.isArray(arr) && arr.includes(item);
}
</script>