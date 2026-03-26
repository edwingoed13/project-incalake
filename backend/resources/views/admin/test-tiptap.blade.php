@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Prueba del Editor Tiptap</h1>

    <!-- Prueba de estilos directos -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Prueba de Estilos HTML Directos:</h2>
        <p class="mb-2">Texto normal</p>
        <p class="mb-2"><strong>Texto en negrita con strong</strong></p>
        <p class="mb-2"><b>Texto en negrita con b</b></p>
        <p class="mb-2"><em>Texto en cursiva con em</em></p>
        <p class="mb-2"><i>Texto en cursiva con i</i></p>
        <p class="mb-2"><strong><em>Texto en negrita y cursiva</em></strong></p>
    </div>

    <!-- Editor Tiptap -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Editor Tiptap:</h2>
        <div x-data="setupEditor('<p>Prueba de texto normal</p><p><strong>Prueba de texto en negrita</strong></p><p><em>Prueba de texto en cursiva</em></p><p><strong><em>Prueba de texto en negrita y cursiva</em></strong></p>')" class="tiptap-editor-wrapper">

            <!-- Toolbar -->
            <div class="tiptap-toolbar flex items-center gap-2 p-2 border border-gray-300 dark:border-gray-600 rounded-t-lg bg-gray-50 dark:bg-gray-700">
                <button
                    @click="editor.chain().focus().toggleBold().run()"
                    :class="{'bg-indigo-600 text-white': editor?.isActive('bold'), 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200': !editor?.isActive('bold')}"
                    class="p-2 rounded hover:bg-indigo-700"
                    title="Negrita"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 4a1 1 0 00-1 1v10a1 1 0 001 1h4.5a3.5 3.5 0 001.852-6.468A3.5 3.5 0 0010.5 4H6zm4.5 7H7V9h3.5a1 1 0 110 2zM7 7v-.5V7h3.5a1 1 0 110 2H7V7z"/>
                    </svg>
                </button>

                <button
                    @click="editor.chain().focus().toggleItalic().run()"
                    :class="{'bg-indigo-600 text-white': editor?.isActive('italic'), 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200': !editor?.isActive('italic')}"
                    class="p-2 rounded hover:bg-indigo-700"
                    title="Cursiva"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9 3a1 1 0 011-1h4a1 1 0 110 2h-1.126l-2.5 10H12a1 1 0 110 2H8a1 1 0 110-2h1.126l2.5-10H10a1 1 0 010-2z"/>
                    </svg>
                </button>
            </div>

            <!-- Editor -->
            <div x-ref="editor"
                 class="prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] border border-t-0 border-gray-300 dark:border-gray-600 rounded-b-lg bg-white dark:bg-gray-800 focus:outline-none">
            </div>
        </div>
    </div>

    <!-- Vista previa del HTML generado -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">HTML Generado por Tiptap:</h2>
        <pre id="html-output" class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-xs overflow-x-auto"></pre>
    </div>

    <!-- Prueba de renderizado del HTML -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Renderizado del HTML:</h2>
        <div id="html-render" class="prose prose-sm dark:prose-invert"></div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    // Actualizar vista previa cuando el editor cambie
    setInterval(() => {
        const editor = document.querySelector('.tiptap-editor-wrapper [x-ref="editor"] .ProseMirror');
        if (editor) {
            const html = editor.innerHTML;
            document.getElementById('html-output').textContent = html;
            document.getElementById('html-render').innerHTML = html;
        }
    }, 500);
});
</script>

<style>
    /* Debug: Forzar estilos muy obvios para prueba */
    #html-render strong,
    #html-render b,
    .ProseMirror strong,
    .ProseMirror b {
        font-weight: 900 !important;
        color: #dc2626 !important; /* Rojo para hacer obvio que funciona */
        background-color: yellow !important; /* Fondo amarillo para debug */
    }

    #html-render em,
    #html-render i,
    .ProseMirror em,
    .ProseMirror i {
        font-style: italic !important;
        color: #2563eb !important; /* Azul para hacer obvio que funciona */
        text-decoration: underline !important; /* Subrayado para debug */
    }
</style>
@endsection