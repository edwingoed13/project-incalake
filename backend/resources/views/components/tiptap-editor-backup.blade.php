@props(['wire:model' => null])

<div
    x-data="setupEditor(@entangle($attributes->wire('model')))"
    x-init="() => init($refs.editor)"
    wire:ignore
    {{ $attributes->whereDoesntStartWith('wire:model') }}
    class="tiptap-editor-wrapper"
>
    <!-- Toolbar -->
    <div class="tiptap-toolbar border border-gray-300 dark:border-gray-600 rounded-t-lg bg-gray-50 dark:bg-gray-700 p-2 flex flex-wrap gap-1">
        <!-- Text formatting buttons -->
        <button
            type="button"
            @click="editor.chain().focus().toggleBold().run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('bold') }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Negrita"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h8a4 4 0 100-8H6v8z M6 12h9a4 4 0 110 8H6v-8z"/>
            </svg>
        </button>

        <button
            type="button"
            @click="editor.chain().focus().toggleItalic().run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('italic') }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Cursiva"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4M14 4l-4 16m-4 0h4"/>
            </svg>
        </button>

        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <!-- Headings -->
        <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('heading', { level: 2 }) }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Título"
        >
            <span class="text-sm font-bold">H2</span>
        </button>

        <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('heading', { level: 3 }) }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Subtítulo"
        >
            <span class="text-sm font-bold">H3</span>
        </button>

        <button
            type="button"
            @click="editor.chain().focus().setParagraph().run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('paragraph') }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Párrafo"
        >
            <span class="text-sm">P</span>
        </button>

        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <!-- Lists -->
        <button
            type="button"
            @click="editor.chain().focus().toggleBulletList().run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('bulletList') }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Lista con viñetas"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <button
            type="button"
            @click="editor.chain().focus().toggleOrderedList().run()"
            :class="{ 'bg-gray-200 dark:bg-gray-600': editor?.isActive('orderedList') }"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
            title="Lista numerada"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
            </svg>
        </button>

        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <!-- Undo/Redo -->
        <button
            type="button"
            @click="editor.chain().focus().undo().run()"
            :disabled="!editor?.can().undo()"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            title="Deshacer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
        </button>

        <button
            type="button"
            @click="editor.chain().focus().redo().run()"
            :disabled="!editor?.can().redo()"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            title="Rehacer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
            </svg>
        </button>
    </div>

    <!-- Editor -->
    <div
        x-ref="editor"
        class="prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] border border-t-0 border-gray-300 dark:border-gray-600 rounded-b-lg bg-white dark:bg-gray-800 focus:outline-none"
    ></div>
</div>

<style>
    /* Estilos del editor Tiptap - Con negrita MUY visible */
    .tiptap-editor-wrapper .ProseMirror {
        min-height: 200px;
        outline: none;
        padding: 1rem;
        color: inherit;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-weight: 400;
    }

    .tiptap-editor-wrapper .ProseMirror p {
        margin-bottom: 0.5rem;
        font-weight: inherit;
    }

    /* FORZAR NEGRITA EXTREMADAMENTE VISIBLE */
    .tiptap-editor-wrapper .ProseMirror strong,
    .tiptap-editor-wrapper .ProseMirror b {
        font-weight: 900 !important;
        color: #000000 !important;
        text-shadow: 0.5px 0px 0px currentColor !important;
        letter-spacing: -0.02em !important;
    }

    /* Negrita en modo oscuro */
    .dark .tiptap-editor-wrapper .ProseMirror strong,
    .dark .tiptap-editor-wrapper .ProseMirror b {
        font-weight: 900 !important;
        color: #ffffff !important;
        text-shadow: 0.5px 0px 0px currentColor !important;
    }

    /* CURSIVA MUY VISIBLE */
    .tiptap-editor-wrapper .ProseMirror em,
    .tiptap-editor-wrapper .ProseMirror i {
        font-style: italic !important;
        font-family: Georgia, serif !important;
        font-size: 1.05em !important;
    }

    /* Combinación negrita + cursiva */
    .tiptap-editor-wrapper .ProseMirror strong em,
    .tiptap-editor-wrapper .ProseMirror strong i,
    .tiptap-editor-wrapper .ProseMirror b em,
    .tiptap-editor-wrapper .ProseMirror b i {
        font-weight: 900 !important;
        font-style: italic !important;
        font-family: Georgia, serif !important;
    }

    /* Listas */
    .tiptap-editor-wrapper .ProseMirror ul,
    .tiptap-editor-wrapper .ProseMirror ol {
        padding-left: 1.5rem !important;
        margin-bottom: 0.5rem !important;
    }

    .tiptap-editor-wrapper .ProseMirror ul {
        list-style-type: disc !important;
    }

    .tiptap-editor-wrapper .ProseMirror ol {
        list-style-type: decimal !important;
    }

    .tiptap-editor-wrapper .ProseMirror li {
        margin-bottom: 0.25rem;
        display: list-item !important;
    }

    /* Encabezados */
    .tiptap-editor-wrapper .ProseMirror h2 {
        font-size: 1.5rem !important;
        font-weight: 900 !important;
        margin-bottom: 0.75rem !important;
        margin-top: 1rem !important;
        letter-spacing: -0.02em !important;
    }

    .tiptap-editor-wrapper .ProseMirror h3 {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.5rem !important;
        margin-top: 0.75rem !important;
        letter-spacing: -0.01em !important;
    }

    /* Focus styles */
    .tiptap-editor-wrapper .ProseMirror:focus {
        outline: none;
    }

    .tiptap-editor-wrapper:focus-within .tiptap-toolbar {
        @apply border-indigo-500 dark:border-indigo-400;
    }

    /* Hack adicional para Windows */
    @media screen and (-webkit-min-device-pixel-ratio:0) {
        .tiptap-editor-wrapper .ProseMirror strong,
        .tiptap-editor-wrapper .ProseMirror b {
            -webkit-text-stroke: 0.3px !important;
        }
    }
</style>