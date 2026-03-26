<div x-data>
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

    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Multimedia - Imágenes</h3>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Subir Imágenes (Máx {{ $maxImages }} - JPG, PNG, WebP - 5MB c/u)
            </label>
            
            <input type="file" wire:model="images" accept=".jpg,.jpeg,.png,.webp" multiple @if(count($tempImages) >= $maxImages) disabled @endif class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

            @error('images.*') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span>Progreso</span>
                <span>{{ count($tempImages) }} / {{ $maxImages }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $this->progressPercentage }}%"></div>
            </div>
        </div>

        @if(count($tempImages) > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($tempImages as $index => $image)
                    <div class="relative group border rounded-lg overflow-hidden h-40">
                        <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" class="w-full h-full object-cover">
                        
                        <div class="absolute inset-0 bg-black bg-opacity-60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1">
                            @if($index === 0)
                                <span class="text-white text-xs bg-yellow-500 px-2 py-1 rounded">
                                    ★ Principal
                                </span>
                            @else
                                <button type="button" wire:click="setPrimaryImage({{ $index }})" class="text-white text-xs bg-yellow-500 px-2 py-1 rounded hover:bg-yellow-600">
                                    ★ Principal
                                </button>
                            @endif

                            {{-- Botón Editar/Crop --}}
                            <button
                                type="button"
                                onclick="openImageCropperForEdit({{ $index }}, '{{ $image['url'] }}')"
                                class="text-white text-xs bg-blue-600 px-2 py-1 rounded hover:bg-blue-700">
                                ✂️ Editar
                            </button>

                            <div class="flex gap-1">
                                <button type="button" wire:click="moveImageUp({{ $index }})" @if($index === 0) disabled @endif class="text-white text-xs bg-gray-600 px-2 py-1 rounded hover:bg-gray-700 {{ $index === 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    ↑
                                </button>
                                <button type="button" wire:click="moveImageDown({{ $index }})" @if($index === count($tempImages) - 1) disabled @endif class="text-white text-xs bg-gray-600 px-2 py-1 rounded hover:bg-gray-700 {{ $index === count($tempImages) - 1 ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    ↓
                                </button>
                            </div>

                            <button type="button" wire:click="removeTempImage({{ $index }})" class="text-white text-xs bg-red-600 px-2 py-1 rounded hover:bg-red-700">
                                Eliminar
                            </button>
                        </div>

                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-1 truncate">
                            {{ $image['filename'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <p class="text-gray-500 dark:text-gray-400">
                    No hay imágenes. Comienza arrastrando o seleccionando archivos aquí.
                </p>
            </div>
        @endif

        <div class="mt-4 text-sm text-gray-500">
            <p>La primera imagen será la principal del tour.</p>
            <p>Utiliza los botones de flecha para reordenar las imágenes.</p>
            <p>✂️ Haz clic en "Editar" para recortar, rotar o ajustar cualquier imagen.</p>
        </div>
    </div>

    {{-- Script para manejar el crop de imágenes --}}
    <script>
        // Función para abrir el cropper para editar una imagen existente
        window.openImageCropperForEdit = function(imageIndex, imageUrl) {
            window.imageCropper.open(imageUrl, imageIndex, function(index, croppedImageData) {
                // Enviar la imagen recortada a Livewire
                @this.updateCroppedImage(index, croppedImageData);
            });
        };

        // Evento para abrir automáticamente el cropper cuando se sube una nueva imagen
        document.addEventListener('livewire:load', function() {
            Livewire.on('imageUploaded', function(data) {
                const { index, imageUrl } = data;

                // Abrir cropper automáticamente para la nueva imagen
                window.imageCropper.open(imageUrl, index, function(idx, croppedImageData) {
                    @this.updateCroppedImage(idx, croppedImageData);
                });
            });
        });
    </script>
</div>