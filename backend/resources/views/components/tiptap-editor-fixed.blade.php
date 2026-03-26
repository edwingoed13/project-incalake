@php
    $editorId = 'editor-' . Str::random(8);
    $wireModel = $attributes->get('wire:model');
@endphp

<div
    wire:ignore
    x-data="{
        content: @entangle($wireModel).live,
        editorInstance: null,
        editorId: '{{ $editorId }}'
    }"
    x-init="
        setTimeout(() => {
            if (typeof initTiptapEditor === 'function') {
                initTiptapEditor($el, content, '{{ $editorId }}', '{{ $wireModel }}');
            }
        }, 100);
    "
    class="tiptap-editor-wrapper"
>
    <!-- Editor Container -->
    <div id="{{ $editorId }}" class="tiptap-container">
        <!-- La toolbar y el editor se crearán dinámicamente por JavaScript -->
    </div>
</div>

<script>
// Asegurar que la función solo se defina una vez
if (typeof window.initTiptapEditor === 'undefined') {
    window.initTiptapEditor = function(element, initialContent, editorId, wireModel) {
        console.log('Inicializando Tiptap para:', editorId);

        // Importar dinámicamente los módulos de Tiptap
        import('https://cdn.jsdelivr.net/npm/@tiptap/core@2.1.13/+esm').then(({ Editor }) => {
            import('https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@2.1.13/+esm').then((StarterKitModule) => {
                const StarterKit = StarterKitModule.default;

                // Crear el contenedor del editor
                const container = document.getElementById(editorId);
                if (!container) {
                    console.error('No se encontró el contenedor:', editorId);
                    return;
                }

                // Crear la toolbar
                const toolbar = document.createElement('div');
                toolbar.className = 'tiptap-toolbar border border-gray-300 dark:border-gray-600 rounded-t-lg bg-gray-50 dark:bg-gray-700 p-2 flex flex-wrap gap-1';

                // Botón Bold
                const boldBtn = document.createElement('button');
                boldBtn.type = 'button';
                boldBtn.className = 'tiptap-btn-bold p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                boldBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h8a4 4 0 100-8H6v8z M6 12h9a4 4 0 110 8H6v-8z"/></svg>';
                boldBtn.title = 'Negrita (Ctrl+B)';

                // Botón Italic
                const italicBtn = document.createElement('button');
                italicBtn.type = 'button';
                italicBtn.className = 'tiptap-btn-italic p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                italicBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4M14 4l-4 16m-4 0h4"/></svg>';
                italicBtn.title = 'Cursiva (Ctrl+I)';

                // Separador
                const separator1 = document.createElement('div');
                separator1.className = 'w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1';

                // Botón H2
                const h2Btn = document.createElement('button');
                h2Btn.type = 'button';
                h2Btn.className = 'tiptap-btn-h2 p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                h2Btn.innerHTML = '<span class="text-sm font-bold">H2</span>';
                h2Btn.title = 'Título 2';

                // Botón H3
                const h3Btn = document.createElement('button');
                h3Btn.type = 'button';
                h3Btn.className = 'tiptap-btn-h3 p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                h3Btn.innerHTML = '<span class="text-sm font-bold">H3</span>';
                h3Btn.title = 'Título 3';

                // Botón Párrafo
                const pBtn = document.createElement('button');
                pBtn.type = 'button';
                pBtn.className = 'tiptap-btn-p p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                pBtn.innerHTML = '<span class="text-sm">P</span>';
                pBtn.title = 'Párrafo';

                // Separador 2
                const separator2 = document.createElement('div');
                separator2.className = 'w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1';

                // Botón Lista
                const listBtn = document.createElement('button');
                listBtn.type = 'button';
                listBtn.className = 'tiptap-btn-list p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                listBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
                listBtn.title = 'Lista con viñetas';

                // Botón Lista Numerada
                const olBtn = document.createElement('button');
                olBtn.type = 'button';
                olBtn.className = 'tiptap-btn-ol p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
                olBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                olBtn.title = 'Lista numerada';

                // Agregar botones a la toolbar
                toolbar.appendChild(boldBtn);
                toolbar.appendChild(italicBtn);
                toolbar.appendChild(separator1);
                toolbar.appendChild(h2Btn);
                toolbar.appendChild(h3Btn);
                toolbar.appendChild(pBtn);
                toolbar.appendChild(separator2);
                toolbar.appendChild(listBtn);
                toolbar.appendChild(olBtn);

                // Crear el contenedor del editor
                const editorDiv = document.createElement('div');
                editorDiv.className = 'tiptap-content prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] border border-t-0 border-gray-300 dark:border-gray-600 rounded-b-lg bg-white dark:bg-gray-800 focus:outline-none';

                // Agregar toolbar y editor al contenedor
                container.innerHTML = '';
                container.appendChild(toolbar);
                container.appendChild(editorDiv);

                // Crear el editor de Tiptap
                const editor = new Editor({
                    element: editorDiv,
                    extensions: [StarterKit],
                    content: initialContent || '<p></p>',
                    editorProps: {
                        attributes: {
                            class: 'focus:outline-none min-h-[150px]',
                            style: 'font-weight: 400;'
                        }
                    },
                    onUpdate: ({ editor }) => {
                        const html = editor.getHTML();

                        // Actualizar Livewire
                        if (window.Livewire && wireModel) {
                            const component = element.closest('[wire\\:id]');
                            if (component) {
                                const componentId = component.getAttribute('wire:id');
                                const livewireComponent = window.Livewire.find(componentId);
                                if (livewireComponent) {
                                    livewireComponent.set(wireModel, html);
                                }
                            }
                        }

                        updateButtonStates(editor);
                    }
                });

                // Función para actualizar estados de botones
                function updateButtonStates(editor) {
                    // Bold
                    if (editor.isActive('bold')) {
                        boldBtn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        boldBtn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    // Italic
                    if (editor.isActive('italic')) {
                        italicBtn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        italicBtn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    // Headings
                    if (editor.isActive('heading', { level: 2 })) {
                        h2Btn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        h2Btn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    if (editor.isActive('heading', { level: 3 })) {
                        h3Btn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        h3Btn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    if (editor.isActive('paragraph')) {
                        pBtn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        pBtn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    if (editor.isActive('bulletList')) {
                        listBtn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        listBtn.classList.remove('bg-indigo-600', 'text-white');
                    }

                    if (editor.isActive('orderedList')) {
                        olBtn.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        olBtn.classList.remove('bg-indigo-600', 'text-white');
                    }
                }

                // Event listeners para los botones
                boldBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleBold().run();
                    updateButtonStates(editor);
                });

                italicBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleItalic().run();
                    updateButtonStates(editor);
                });

                h2Btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleHeading({ level: 2 }).run();
                    updateButtonStates(editor);
                });

                h3Btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleHeading({ level: 3 }).run();
                    updateButtonStates(editor);
                });

                pBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().setParagraph().run();
                    updateButtonStates(editor);
                });

                listBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleBulletList().run();
                    updateButtonStates(editor);
                });

                olBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    editor.chain().focus().toggleOrderedList().run();
                    updateButtonStates(editor);
                });

                // Actualizar estados cuando cambia la selección
                editor.on('selectionUpdate', () => {
                    updateButtonStates(editor);
                });

                // Actualizar estados iniciales
                setTimeout(() => updateButtonStates(editor), 100);

                // Guardar referencia del editor
                element.editorInstance = editor;

                console.log('✅ Tiptap Editor inicializado correctamente:', editorId);
            }).catch(error => {
                console.error('Error al cargar StarterKit:', error);
            });
        }).catch(error => {
            console.error('Error al cargar Tiptap Core:', error);
        });
    };
}
</script>

<style>
    /* Estilos EXTREMOS para hacer la negrita MUY visible */
    .tiptap-content {
        font-weight: 400 !important;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    .tiptap-content p {
        font-weight: 400 !important;
    }

    .tiptap-content strong,
    .tiptap-content b {
        font-weight: 900 !important;
        color: #000 !important;
        text-shadow: 0.5px 0 0 #000, -0.5px 0 0 #000 !important;
        letter-spacing: -0.03em !important;
        -webkit-font-smoothing: auto !important;
    }

    .dark .tiptap-content strong,
    .dark .tiptap-content b {
        color: #fff !important;
        text-shadow: 0.5px 0 0 #fff, -0.5px 0 0 #fff !important;
    }

    .tiptap-content em,
    .tiptap-content i {
        font-style: italic !important;
        font-family: Georgia, serif !important;
        font-size: 1.05em !important;
    }

    .tiptap-content h2 {
        font-size: 1.5rem !important;
        font-weight: 900 !important;
        margin: 1rem 0 0.75rem !important;
    }

    .tiptap-content h3 {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        margin: 0.75rem 0 0.5rem !important;
    }

    .tiptap-content ul,
    .tiptap-content ol {
        padding-left: 1.5rem !important;
        margin: 0.5rem 0 !important;
    }

    .tiptap-content ul {
        list-style-type: disc !important;
    }

    .tiptap-content ol {
        list-style-type: decimal !important;
    }

    .tiptap-content li {
        display: list-item !important;
    }
</style>