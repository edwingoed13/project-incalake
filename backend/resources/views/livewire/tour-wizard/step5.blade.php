<div>
    {{-- Modal de Crop de Imagen --}}
    <div id="cropperModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="window.imageCropper.close()"></div>

            {{-- Centrar modal --}}
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full">
                            {{-- Header --}}
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Editar Imagen
                                </h3>
                                <button type="button" onclick="window.imageCropper.close()" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Controles de Crop --}}
                            <div class="mb-4 flex flex-wrap gap-2">
                                {{-- Aspect Ratios --}}
                                <div class="flex gap-1 border-r pr-2">
                                    <button type="button" onclick="window.imageCropper.setAspectRatio(NaN)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Libre">
                                        Libre
                                    </button>
                                    <button type="button" onclick="window.imageCropper.setAspectRatio(1)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="1:1">
                                        1:1
                                    </button>
                                    <button type="button" onclick="window.imageCropper.setAspectRatio(16/9)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="16:9">
                                        16:9
                                    </button>
                                    <button type="button" onclick="window.imageCropper.setAspectRatio(4/3)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="4:3">
                                        4:3
                                    </button>
                                </div>

                                {{-- Rotación --}}
                                <div class="flex gap-1 border-r pr-2">
                                    <button type="button" onclick="window.imageCropper.rotate(-90)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Rotar izquierda">
                                        ↶ 90°
                                    </button>
                                    <button type="button" onclick="window.imageCropper.rotate(90)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Rotar derecha">
                                        ↷ 90°
                                    </button>
                                </div>

                                {{-- Voltear --}}
                                <div class="flex gap-1 border-r pr-2">
                                    <button type="button" onclick="window.imageCropper.flip('horizontal')" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Voltear horizontal">
                                        ↔ Horizontal
                                    </button>
                                    <button type="button" onclick="window.imageCropper.flip('vertical')" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Voltear vertical">
                                        ↕ Vertical
                                    </button>
                                </div>

                                {{-- Zoom --}}
                                <div class="flex gap-1 border-r pr-2">
                                    <button type="button" onclick="window.imageCropper.zoom(0.1)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Acercar">
                                        🔍+
                                    </button>
                                    <button type="button" onclick="window.imageCropper.zoom(-0.1)" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded" title="Alejar">
                                        🔍-
                                    </button>
                                </div>

                                {{-- Reset --}}
                                <button type="button" onclick="window.imageCropper.reset()" class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded" title="Resetear">
                                    🔄 Resetear
                                </button>
                            </div>

                            {{-- Área de Crop --}}
                            <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden" style="max-height: 500px;">
                                <img id="cropperImage" src="" alt="Imagen para recortar" class="max-w-full">
                            </div>

                            {{-- Instrucciones --}}
                            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                <p>💡 Arrastra para mover la imagen, usa la rueda del ratón para zoom, arrastra las esquinas del área de recorte para ajustar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer con botones --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" onclick="window.imageCropper.save()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Guardar
                    </button>
                    <button type="button" onclick="window.imageCropper.close()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Multimedia</h3>
    <div class="space-y-6">
    <!-- Video de YouTube -->
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-10 h-10 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Video de YouTube <span class="text-xs text-gray-500">(opcional)</span>
                </label>
                <input
                    type="url"
                    wire:model.blur="youtube_url"
                    placeholder="https://www.youtube.com/watch?v=... o https://youtube.com/shorts/..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"
                >
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Pega el enlace completo del video de YouTube (incluye soporte para YouTube Shorts)
                </p>
                @error('youtube_url')
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Vista previa del video -->
        @if($youtube_url)
            @php
                // Extraer el ID del video de YouTube y detectar si es un Short
                $videoId = null;
                $isShort = false;

                // Detectar YouTube Shorts
                if (preg_match('/youtube\.com\/shorts\/([^"&?\/ ]{11})/', $youtube_url, $matches)) {
                    $videoId = $matches[1];
                    $isShort = true;
                }
                // Detectar videos regulares
                elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $youtube_url, $matches)) {
                    $videoId = $matches[1];
                    $isShort = false;
                }
            @endphp
            @if($videoId)
                <div class="mt-4">
                    <div class="flex justify-between items-center mb-2">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Vista previa:</p>
                        @if($isShort)
                            <span class="text-xs bg-red-600 text-white px-2 py-1 rounded">
                                📱 YouTube Short (Vertical)
                            </span>
                        @else
                            <span class="text-xs bg-gray-600 text-white px-2 py-1 rounded">
                                🎥 Video Regular (Horizontal)
                            </span>
                        @endif
                    </div>

                    @if($isShort)
                        {{-- Vista previa vertical para Shorts --}}
                        <div class="flex justify-center">
                            <div class="relative rounded-lg overflow-hidden" style="width: 360px; height: 640px; max-width: 100%;">
                                <iframe
                                    class="absolute top-0 left-0 w-full h-full"
                                    src="https://www.youtube.com/embed/{{ $videoId }}"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    @else
                        {{-- Vista previa horizontal para videos regulares --}}
                        <div class="relative" style="padding-bottom: 56.25%; height: 0; overflow: hidden;">
                            <iframe
                                class="absolute top-0 left-0 w-full h-full rounded-lg"
                                src="https://www.youtube.com/embed/{{ $videoId }}"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <!-- Info de Detección Automática -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-lg border border-indigo-200 dark:border-indigo-800 p-6">
        <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Detección Automática de Layout
        </h4>

        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
            El sistema detecta automáticamente el mejor layout según el contenido:
        </p>

        <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg">📱</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Video Short (Vertical)</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <strong>Desktop:</strong> Video 300px + 3 imágenes curadas (1 grande + 2 pequeñas)<br>
                        <strong>Mobile:</strong> Video vertical + 2 imágenes con botón "Ver todas"<br>
                        <span class="text-indigo-600 dark:text-indigo-400">Altura: 500px | Optimizado para conversión</span>
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg">🎬</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Video Regular (Horizontal)</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <strong>Desktop:</strong> Video 50% + Mosaico 50% (4-6 imágenes con 1ª destacada)<br>
                        <strong>Mobile:</strong> Video arriba + 2 imágenes con contador<br>
                        <span class="text-indigo-600 dark:text-indigo-400">Altura: 500px | Balance perfecto contenido/performance</span>
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg">🖼️</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Sin Video</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Galería en mosaico: 1 imagen grande + 4 pequeñas</p>
                </div>
            </div>
        </div>

        @if($youtube_url)
            @php
                $isShort = str_contains($youtube_url, '/shorts/');
            @endphp
            <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        Layout detectado:
                        <span class="text-indigo-600 dark:text-indigo-400">
                            {{ $isShort ? '📱 Video Short (Vertical)' : '🎬 Video Regular (Horizontal)' }}
                        </span>
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Zona de carga de imágenes -->
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>

            <div class="mt-4">
                <label for="images" class="relative cursor-pointer bg-indigo-600 rounded-md font-medium text-white py-2 px-4 hover:bg-indigo-700 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                    <span>Seleccionar Imágenes</span>
                    <input
                        id="images"
                        name="images"
                        type="file"
                        class="sr-only"
                        wire:model="images"
                        multiple
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                    >
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    JPG, PNG o WEBP. Máximo 5MB por imagen.
                </p>
            </div>

            <!-- Loading state -->
            <div wire:loading wire:target="images" class="mt-4">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-600 dark:text-gray-400">Cargando imágenes...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Vista previa de imágenes cargadas -->
    @if(count($tempImages) > 0)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                Imágenes cargadas ({{ count($tempImages) }})
            </h4>

            <div class="space-y-6">
                @foreach($tempImages as $index => $image)
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Vista Previa de la Imagen -->
                            <div class="relative">
                                <div class="aspect-w-16 aspect-h-12 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                                    <img
                                        src="{{ $image['url'] }}"
                                        alt="Vista previa {{ $index + 1 }}"
                                        class="w-full h-full object-cover"
                                    >
                                </div>

                                <!-- Indicador de orden -->
                                <div class="absolute top-2 left-2 bg-indigo-600 text-white text-xs px-2 py-1 rounded font-semibold">
                                    #{{ $index + 1 }}
                                    @if($index === 0)
                                        <span class="ml-1">⭐ Principal</span>
                                    @endif
                                </div>

                                <!-- Botones de acción -->
                                <div class="absolute top-2 right-2 flex gap-1">
                                    <!-- Botón Editar/Crop -->
                                    <button
                                        type="button"
                                        onclick="openImageCropperForEdit({{ $index }}, '{{ $image['url'] }}')"
                                        class="bg-blue-600 text-white p-1.5 rounded-full hover:bg-blue-700 transition"
                                        title="Editar/Recortar imagen"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                        </svg>
                                    </button>

                                    <!-- Botón eliminar -->
                                    <button
                                        type="button"
                                        wire:click="removeImage({{ $index }})"
                                        class="bg-red-600 text-white p-1.5 rounded-full hover:bg-red-700 transition"
                                        title="Eliminar imagen"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Info del archivo -->
                                <div class="mt-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $image['filename'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ number_format($image['size'] / 1024, 1) }} KB
                                    </p>
                                </div>
                            </div>

                            <!-- Campos de SEO -->
                            <div class="md:col-span-2 space-y-3">
                                <!-- Texto ALT (SEO) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Texto ALT * <span class="text-xs text-gray-500">(importante para SEO)</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model.blur="tempImages.{{ $index }}.alt_text"
                                        placeholder="Ej: Tour Lago Titicaca Puno con vista panorámica"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500"
                                        maxlength="125"
                                    >
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ strlen($image['alt_text'] ?? '') }}/125 caracteres
                                    </p>
                                </div>

                                <!-- Title (Título de la Imagen) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Título de la Imagen
                                    </label>
                                    <input
                                        type="text"
                                        wire:model.blur="tempImages.{{ $index }}.title_text"
                                        placeholder="Ej: Vista del Lago Titicaca desde Puno"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500"
                                        maxlength="100"
                                    >
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ strlen($image['title_text'] ?? '') }}/100 caracteres
                                    </p>
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Descripción de la Imagen
                                    </label>
                                    <textarea
                                        wire:model.blur="tempImages.{{ $index }}.description"
                                        placeholder="Describe lo que se ve en la imagen..."
                                        rows="2"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500"
                                        maxlength="250"
                                    ></textarea>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ strlen($image['description'] ?? '') }}/250 caracteres
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                <p>💡 La primera imagen será la imagen principal del tour</p>
                <p>💡 Completa los campos ALT y descripción para mejorar el SEO</p>
            </div>
        </div>
    @endif

    <!-- Mensaje cuando no hay imágenes -->
    @if(count($tempImages) == 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Sin imágenes
                    </h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        Es recomendable agregar al menos 3 imágenes atractivas del tour para mejorar las conversiones.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Errores de validación -->
    @error('images.*')
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                </div>
            </div>
        </div>
    @enderror

    @script
    <script>
        // Función para abrir el cropper para editar una imagen existente
        window.openImageCropperForEdit = function(imageIndex, imageUrl) {
            console.log('openImageCropperForEdit llamado con:', imageIndex, imageUrl);
            if (window.imageCropper) {
                window.imageCropper.open(imageUrl, imageIndex, function(index, croppedImageData) {
                    // Enviar la imagen recortada a Livewire
                    $wire.updateCroppedImage(index, croppedImageData);
                });
            } else {
                console.error('window.imageCropper no está disponible');
            }
        };

        // Evento para abrir automáticamente el cropper cuando se sube una nueva imagen
        $wire.on('imageUploaded', (event) => {
            console.log('Evento imageUploaded recibido:', event);
            const data = event[0] || event;
            const { index, imageUrl } = data;

            if (window.imageCropper) {
                // Abrir cropper automáticamente para la nueva imagen
                setTimeout(() => {
                    window.imageCropper.open(imageUrl, index, function(idx, croppedImageData) {
                        $wire.updateCroppedImage(idx, croppedImageData);
                    });
                }, 500);
            } else {
                console.error('window.imageCropper no está disponible');
            }
        });
    </script>
    @endscript
</div>