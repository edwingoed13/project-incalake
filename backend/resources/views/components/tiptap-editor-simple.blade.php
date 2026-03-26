@props(['wire:model' => null])

<div class="tiptap-editor-wrapper">
    <!-- Hidden input for Livewire -->
    <input type="hidden" wire:model="{{ $attributes->wire('model')->value() }}" x-ref="hiddenInput">

    <!-- Toolbar -->
    <div class="border border-gray-300 dark:border-gray-600 rounded-t-lg bg-gray-50 dark:bg-gray-700 p-2 flex flex-wrap gap-1">
        <button type="button" onclick="formatText('bold')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Negrita">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h8a4 4 0 100-8H6v8z M6 12h9a4 4 0 110 8H6v-8z"/>
            </svg>
        </button>

        <button type="button" onclick="formatText('italic')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Cursiva">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4M14 4l-4 16m-4 0h4"/>
            </svg>
        </button>

        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <button type="button" onclick="formatText('formatBlock', 'h2')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Título">
            <span class="text-sm font-bold">H2</span>
        </button>

        <button type="button" onclick="formatText('formatBlock', 'h3')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Subtítulo">
            <span class="text-sm font-bold">H3</span>
        </button>

        <button type="button" onclick="formatText('formatBlock', 'p')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Párrafo">
            <span class="text-sm">P</span>
        </button>

        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <button type="button" onclick="formatText('insertUnorderedList')" class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600" title="Lista">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    <!-- Editor -->
    <div
        id="tiptap-editor-{{ Str::random(8) }}"
        contenteditable="true"
        class="prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] border border-t-0 border-gray-300 dark:border-gray-600 rounded-b-lg bg-white dark:bg-gray-800 focus:outline-none"
        oninput="updateContent(this)"
    >{!! $attributes->wire('model')->value() ?? '' !!}</div>
</div>

<style>
    /* Estilos para el editor - MÁXIMA VISIBILIDAD */
    .tiptap-editor-wrapper [contenteditable] {
        font-weight: 400;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    .tiptap-editor-wrapper [contenteditable] strong,
    .tiptap-editor-wrapper [contenteditable] b {
        font-weight: 900 !important;
        color: #000 !important;
        text-shadow: 0.5px 0 0 currentColor !important;
    }

    .dark .tiptap-editor-wrapper [contenteditable] strong,
    .dark .tiptap-editor-wrapper [contenteditable] b {
        color: #fff !important;
        font-weight: 900 !important;
    }

    .tiptap-editor-wrapper [contenteditable] em,
    .tiptap-editor-wrapper [contenteditable] i {
        font-style: italic !important;
        font-family: Georgia, serif !important;
    }

    .tiptap-editor-wrapper [contenteditable] h2 {
        font-size: 1.5rem !important;
        font-weight: 900 !important;
        margin: 1rem 0 0.75rem !important;
    }

    .tiptap-editor-wrapper [contenteditable] h3 {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        margin: 0.75rem 0 0.5rem !important;
    }
</style>

<script>
    function formatText(command, value = null) {
        document.execCommand(command, false, value);
        // Actualizar el contenido después de formatear
        const editor = document.querySelector('[contenteditable="true"]:focus');
        if (editor) {
            updateContent(editor);
        }
    }

    function updateContent(element) {
        const content = element.innerHTML;
        // Buscar el input hidden asociado
        const wrapper = element.closest('.tiptap-editor-wrapper');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        if (hiddenInput) {
            hiddenInput.value = content;
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Disparar evento de Livewire si está disponible
        if (window.Livewire) {
            const wireModel = hiddenInput.getAttribute('wire:model');
            if (wireModel) {
                const component = wrapper.closest('[wire\\:id]');
                if (component) {
                    const componentId = component.getAttribute('wire:id');
                    window.Livewire.find(componentId).set(wireModel, content);
                }
            }
        }
    }
</script>