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
