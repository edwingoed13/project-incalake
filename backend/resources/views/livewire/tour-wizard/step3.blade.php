@php
    $primaryLanguage = collect($this->languages)->firstWhere('id', $this->primary_language_id);
@endphp

    @if($primaryLanguage)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
            Contenido del Tour - {{ $primaryLanguage['country'] }}
        </h3>

    <div class="space-y-6">
        {{-- DESCRIPCIÓN LARGA / DETALLES DEL TOUR --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Descripción Detallada del Tour * (Editor TipTap)
            </label>

            <div
                wire:ignore
                x-data="{
                    editorId: 'tiptap-editor-{{ $primaryLanguage['id'] }}',
                    initialContent: @js($translations[$primaryLanguage['id']]['long_description'] ?? '')
                }"
                x-init="$nextTick(() => {
                    const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.long_description');
                    if (editor && initialContent) {
                        editor.commands.setContent(initialContent);
                    }
                })"
            >
                {{-- Input hidden para sincronización con Livewire --}}
                <input
                    type="hidden"
                    id="tiptap-editor-{{ $primaryLanguage['id'] }}-hidden"
                    wire:model.defer="translations.{{ $primaryLanguage['id'] }}.long_description"
                    value="{{ $translations[$primaryLanguage['id']]['long_description'] ?? '' }}"
                >

                <div class="tiptap-wrapper">
                    <!-- Toolbar -->
                    <div class="tiptap-toolbar" data-editor="tiptap-editor-{{ $primaryLanguage['id'] }}">
                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleBold()"
                            data-command="bold"
                            class="font-bold"
                            title="Negrita (Ctrl+B)"
                        >
                            B
                        </button>
                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleItalic()"
                            data-command="italic"
                            class="italic"
                            title="Cursiva (Ctrl+I)"
                        >
                            I
                        </button>

                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleHeading(2)"
                            data-command="heading"
                            data-param="2"
                            title="Título 2"
                        >
                            H2
                        </button>
                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleHeading(3)"
                            data-command="heading"
                            data-param="3"
                            title="Título 3"
                        >
                            H3
                        </button>

                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleBulletList()"
                            data-command="bulletList"
                            title="Lista con viñetas"
                        >
                            •
                        </button>
                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].toggleOrderedList()"
                            data-command="orderedList"
                            title="Lista numerada"
                        >
                            1.
                        </button>

                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                        <button
                            type="button"
                            onclick="window.tiptapCommands['tiptap-editor-{{ $primaryLanguage['id'] }}'].setLink()"
                            data-command="link"
                            title="Insertar enlace"
                        >
                            🔗
                        </button>
                    </div>

                    <!-- Editor Content -->
                    <div id="tiptap-editor-{{ $primaryLanguage['id'] }}"></div>
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Usa el editor para dar formato al texto: <strong>negritas</strong>, <em>cursivas</em>, listas, títulos, etc.
                </p>

            </div>
        </div>

        {{-- ITINERARIO --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Itinerario del Tour
            </label>

            <div
                wire:ignore
                x-data="{
                    editorId: 'tiptap-itinerary-{{ $primaryLanguage['id'] }}',
                    initialContent: @js($translations[$primaryLanguage['id']]['itinerary'] ?? '')
                }"
                x-init="$nextTick(() => {
                    const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.itinerary');
                    if (editor && initialContent) {
                        editor.commands.setContent(initialContent);
                    }
                })"
            >
                <input
                    type="hidden"
                    id="tiptap-itinerary-{{ $primaryLanguage['id'] }}-hidden"
                    wire:model.defer="translations.{{ $primaryLanguage['id'] }}.itinerary"
                    value="{{ $translations[$primaryLanguage['id']]['itinerary'] ?? '' }}"
                >

                <div class="tiptap-wrapper">
                    <div class="tiptap-toolbar" data-editor="tiptap-itinerary-{{ $primaryLanguage['id'] }}">
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleBold()" data-command="bold" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleItalic()" data-command="italic" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleHeading(2)" data-command="heading" data-param="2" title="Título 2">H2</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleHeading(3)" data-command="heading" data-param="3" title="Título 3">H3</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleBulletList()" data-command="bulletList" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].toggleOrderedList()" data-command="orderedList" title="Lista numerada">1.</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-itinerary-{{ $primaryLanguage['id'] }}'].setLink()" data-command="link" title="Enlace">🔗</button>
                    </div>
                    <div id="tiptap-itinerary-{{ $primaryLanguage['id'] }}"></div>
                </div>
            </div>
        </div>

        {{-- QUÉ INCLUYE --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ¿Qué Incluye?
            </label>

            <div wire:ignore x-data="{ editorId: 'tiptap-includes-{{ $primaryLanguage['id'] }}', initialContent: @js($translations[$primaryLanguage['id']]['what_includes'] ?? '') }" x-init="$nextTick(() => { const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.what_includes'); if (editor && initialContent) { editor.commands.setContent(initialContent); } })">
                <input type="hidden" id="tiptap-includes-{{ $primaryLanguage['id'] }}-hidden" wire:model.defer="translations.{{ $primaryLanguage['id'] }}.what_includes" value="{{ $translations[$primaryLanguage['id']]['what_includes'] ?? '' }}">
                <div class="tiptap-wrapper">
                    <div class="tiptap-toolbar" data-editor="tiptap-includes-{{ $primaryLanguage['id'] }}">
                        <button type="button" onclick="window.tiptapCommands['tiptap-includes-{{ $primaryLanguage['id'] }}'].toggleBold()" data-command="bold" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-includes-{{ $primaryLanguage['id'] }}'].toggleItalic()" data-command="italic" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-includes-{{ $primaryLanguage['id'] }}'].toggleBulletList()" data-command="bulletList" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-includes-{{ $primaryLanguage['id'] }}'].toggleOrderedList()" data-command="orderedList" title="Lista numerada">1.</button>
                    </div>
                    <div id="tiptap-includes-{{ $primaryLanguage['id'] }}"></div>
                </div>
            </div>
        </div>

        {{-- QUÉ NO INCLUYE --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ¿Qué NO Incluye?
            </label>

            <div wire:ignore x-data="{ editorId: 'tiptap-notincludes-{{ $primaryLanguage['id'] }}', initialContent: @js($translations[$primaryLanguage['id']]['what_not_includes'] ?? '') }" x-init="$nextTick(() => { const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.what_not_includes'); if (editor && initialContent) { editor.commands.setContent(initialContent); } })">
                <input type="hidden" id="tiptap-notincludes-{{ $primaryLanguage['id'] }}-hidden" wire:model.defer="translations.{{ $primaryLanguage['id'] }}.what_not_includes" value="{{ $translations[$primaryLanguage['id']]['what_not_includes'] ?? '' }}">
                <div class="tiptap-wrapper">
                    <div class="tiptap-toolbar" data-editor="tiptap-notincludes-{{ $primaryLanguage['id'] }}">
                        <button type="button" onclick="window.tiptapCommands['tiptap-notincludes-{{ $primaryLanguage['id'] }}'].toggleBold()" data-command="bold" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-notincludes-{{ $primaryLanguage['id'] }}'].toggleItalic()" data-command="italic" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-notincludes-{{ $primaryLanguage['id'] }}'].toggleBulletList()" data-command="bulletList" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-notincludes-{{ $primaryLanguage['id'] }}'].toggleOrderedList()" data-command="orderedList" title="Lista numerada">1.</button>
                    </div>
                    <div id="tiptap-notincludes-{{ $primaryLanguage['id'] }}"></div>
                </div>
            </div>
        </div>

        {{-- RECOMENDACIONES --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Recomendaciones
            </label>

            <div wire:ignore x-data="{ editorId: 'tiptap-recommendations-{{ $primaryLanguage['id'] }}', initialContent: @js($translations[$primaryLanguage['id']]['recommendations'] ?? '') }" x-init="$nextTick(() => { const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.recommendations'); if (editor && initialContent) { editor.commands.setContent(initialContent); } })">
                <input type="hidden" id="tiptap-recommendations-{{ $primaryLanguage['id'] }}-hidden" wire:model.defer="translations.{{ $primaryLanguage['id'] }}.recommendations" value="{{ $translations[$primaryLanguage['id']]['recommendations'] ?? '' }}">
                <div class="tiptap-wrapper">
                    <div class="tiptap-toolbar" data-editor="tiptap-recommendations-{{ $primaryLanguage['id'] }}">
                        <button type="button" onclick="window.tiptapCommands['tiptap-recommendations-{{ $primaryLanguage['id'] }}'].toggleBold()" data-command="bold" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-recommendations-{{ $primaryLanguage['id'] }}'].toggleItalic()" data-command="italic" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-recommendations-{{ $primaryLanguage['id'] }}'].toggleBulletList()" data-command="bulletList" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-recommendations-{{ $primaryLanguage['id'] }}'].toggleOrderedList()" data-command="orderedList" title="Lista numerada">1.</button>
                    </div>
                    <div id="tiptap-recommendations-{{ $primaryLanguage['id'] }}"></div>
                </div>
            </div>
        </div>

        {{-- QUÉ LLEVAR --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ¿Qué Llevar?
            </label>

            <div wire:ignore x-data="{ editorId: 'tiptap-tobring-{{ $primaryLanguage['id'] }}', initialContent: @js($translations[$primaryLanguage['id']]['what_to_bring'] ?? '') }" x-init="$nextTick(() => { const editor = window.initTiptapEditor(editorId, 'translations.{{ $primaryLanguage['id'] }}.what_to_bring'); if (editor && initialContent) { editor.commands.setContent(initialContent); } })">
                <input type="hidden" id="tiptap-tobring-{{ $primaryLanguage['id'] }}-hidden" wire:model.defer="translations.{{ $primaryLanguage['id'] }}.what_to_bring" value="{{ $translations[$primaryLanguage['id']]['what_to_bring'] ?? '' }}">
                <div class="tiptap-wrapper">
                    <div class="tiptap-toolbar" data-editor="tiptap-tobring-{{ $primaryLanguage['id'] }}">
                        <button type="button" onclick="window.tiptapCommands['tiptap-tobring-{{ $primaryLanguage['id'] }}'].toggleBold()" data-command="bold" class="font-bold" title="Negrita">B</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-tobring-{{ $primaryLanguage['id'] }}'].toggleItalic()" data-command="italic" class="italic" title="Cursiva">I</button>
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" onclick="window.tiptapCommands['tiptap-tobring-{{ $primaryLanguage['id'] }}'].toggleBulletList()" data-command="bulletList" title="Lista">•</button>
                        <button type="button" onclick="window.tiptapCommands['tiptap-tobring-{{ $primaryLanguage['id'] }}'].toggleOrderedList()" data-command="orderedList" title="Lista numerada">1.</button>
                    </div>
                    <div id="tiptap-tobring-{{ $primaryLanguage['id'] }}"></div>
                </div>
            </div>
        </div>

        {{-- POLÍTICAS GENERALES --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Políticas Generales
            </label>
            <textarea
                wire:model="translations.{{ $primaryLanguage['id'] }}.policies"
                rows="5"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-sans focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Describe las políticas generales del tour..."
            ></textarea>
        </div>

        {{-- POLÍTICA DE CANCELACIÓN --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Política de Cancelación *
            </label>
            <textarea
                wire:model="translations.{{ $primaryLanguage['id'] }}.cancellation_policy"
                rows="5"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-sans focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ej: Cancelación gratuita hasta 24 horas antes. Después de ese plazo se cobra el 50%..."
            ></textarea>
        </div>

        {{-- MAPA INTERACTIVO --}}
        <div class="mt-8"
             x-data="tourMapComponent()"
             x-init="init()"
             wire:ignore>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Mapa del Tour
                </h3>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Registre las rutas desde el inicio hasta el final en orden según el recorrido del tour.
                </p>

                {{-- Formulario para agregar puntos --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre del Lugar *
                        </label>
                        <input
                            type="text"
                            id="pointNameInput"
                            x-model="pointName"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Buscar lugar..."
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tipo de Lugar *
                        </label>
                        <select
                            x-model="pointType"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Seleccione...</option>
                            <option value="punto_parada">Punto de Parada</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="lugar_turistico">Lugar Turístico</option>
                            <option value="aeropuerto">Aeropuerto</option>
                            <option value="estacion_tren">Estación de Tren</option>
                            <option value="terminal_terrestre">Terminal Terrestre (Bus)</option>
                            <option value="museo">Museo</option>
                            <option value="punto_reunion">Punto de Reunión</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción
                        </label>
                        <textarea
                            x-model="pointDescription"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Dirección o descripción del lugar..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Coordenadas
                        </label>
                        <input
                            type="text"
                            x-model="pointCoordinates"
                            readonly
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-600 text-gray-900 dark:text-gray-100"
                            placeholder="Seleccione en el mapa..."
                        >
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            @click="addPoint()"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Agregar Punto
                        </button>
                        <button
                            type="button"
                            @click="clearForm()"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                            Limpiar
                        </button>
                    </div>
                </div>

                {{-- Mapa de Google --}}
                <div id="tourMapCanvas" class="w-full h-96 rounded-lg border border-gray-300 dark:border-gray-600 mb-4"></div>

                {{-- Lista de puntos agregados --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">
                            Puntos del Tour (<span x-text="points.length"></span>)
                        </h4>
                    </div>

                    <div x-show="points.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        No hay puntos agregados
                    </div>

                    <ul x-show="points.length > 0" class="space-y-2">
                        <template x-for="(point, index) in points" :key="index">
                            <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center flex-1">
                                    <span class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full font-bold mr-3" x-text="point.order"></span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="point.name"></p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="getTypeLabel(point.type)"></p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removePoint(index)"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="text-center py-12">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Selecciona un idioma en el Paso 1</h3>
        <button type="button" wire:click="goToStep(1)" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            Volver al Paso 1
        </button>
    </div>
@endif
