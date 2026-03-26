// TipTap Standalone - Sin conflictos con Livewire
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';

// Almacenar editores globalmente para evitar conflictos
window.tiptapEditors = window.tiptapEditors || {};

window.initTiptapEditor = function(elementId, wireModel) {
    // Si ya existe un editor para este elemento, destruirlo primero
    if (window.tiptapEditors[elementId]) {
        window.tiptapEditors[elementId].destroy();
        delete window.tiptapEditors[elementId];
    }

    const element = document.getElementById(elementId);
    if (!element) {
        return;
    }

    // Crear editor sin Alpine/Livewire
    const editor = new Editor({
        element: element,
        extensions: [
            StarterKit,
            Table.configure({
                resizable: true,
            }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        content: '',
        onUpdate: ({ editor }) => {
            // Actualizar un campo oculto si existe
            const hiddenInput = document.getElementById(elementId + '-hidden');
            if (hiddenInput) {
                hiddenInput.value = editor.getHTML();
                // NO disparar eventos que Livewire pueda interceptar
            }
        },
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none focus:outline-none min-h-[200px] p-4',
            }
        }
    });

    // Guardar referencia global
    window.tiptapEditors[elementId] = editor;

    // Exponer funciones para los botones
    window.tiptapCommands = window.tiptapCommands || {};
    window.tiptapCommands[elementId] = {
        toggleBold: () => {
            editor.chain().focus().toggleBold().run();
            updateToolbar(elementId);
        },
        toggleItalic: () => {
            editor.chain().focus().toggleItalic().run();
            updateToolbar(elementId);
        },
        toggleHeading: (level) => {
            editor.chain().focus().toggleHeading({ level }).run();
            updateToolbar(elementId);
        },
        toggleBulletList: () => {
            editor.chain().focus().toggleBulletList().run();
            updateToolbar(elementId);
        },
        toggleOrderedList: () => {
            editor.chain().focus().toggleOrderedList().run();
            updateToolbar(elementId);
        },
        setLink: () => {
            const url = prompt('URL:');
            if (url) {
                editor.chain().focus().setLink({ href: url }).run();
            }
            updateToolbar(elementId);
        },
        insertTable: () => {
            editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
            updateToolbar(elementId);
        },
        undo: () => {
            editor.chain().focus().undo().run();
        },
        redo: () => {
            editor.chain().focus().redo().run();
        },
        getHTML: () => {
            return editor.getHTML();
        },
        isActive: (type, attrs = {}) => {
            return editor.isActive(type, attrs);
        }
    };

    // Función para actualizar el estado de los botones
    function updateToolbar(editorId) {
        const toolbar = document.querySelector(`[data-editor="${editorId}"]`);
        if (!toolbar) return;

        const commands = window.tiptapCommands[editorId];

        // Actualizar clases de botones activos
        toolbar.querySelectorAll('[data-command]').forEach(button => {
            const command = button.dataset.command;
            const param = button.dataset.param;

            let isActive = false;
            if (command === 'heading' && param) {
                isActive = commands.isActive('heading', { level: parseInt(param) });
            } else if (command === 'bold') {
                isActive = commands.isActive('bold');
            } else if (command === 'italic') {
                isActive = commands.isActive('italic');
            } else if (command === 'bulletList') {
                isActive = commands.isActive('bulletList');
            } else if (command === 'orderedList') {
                isActive = commands.isActive('orderedList');
            } else if (command === 'link') {
                isActive = commands.isActive('link');
            }

            if (isActive) {
                button.classList.add('is-active');
            } else {
                button.classList.remove('is-active');
            }
        });
    }

    // Actualizar toolbar cuando cambie la selección
    editor.on('selectionUpdate', () => {
        updateToolbar(elementId);
    });

    return editor;
};

// Función para sincronizar con Livewire manualmente
window.syncTiptapWithLivewire = function(editorId) {
    const editor = window.tiptapEditors[editorId];
    const hiddenInput = document.getElementById(editorId + '-hidden');

    if (editor && hiddenInput) {
        hiddenInput.value = editor.getHTML();
        // Disparar evento change para que Livewire lo capture
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

// Función para reinicializar todos los editores TipTap en una página
window.reinitAllTiptapEditors = function() {
    // Esperar a que Alpine haya procesado los elementos
    setTimeout(() => {
        // Buscar todos los divs con IDs que empiezan con 'tiptap-'
        const editorElements = document.querySelectorAll('[id^="tiptap-"]');

        editorElements.forEach(el => {
            const editorId = el.id;

            // Solo procesar elementos que no terminan en '-hidden' (esos son los inputs)
            if (editorId.endsWith('-hidden')) return;

            // Verificar si ya existe un editor para este elemento
            if (window.tiptapEditors[editorId]) {
                return;
            }

            // Buscar el input hidden correspondiente para obtener el contenido inicial
            const hiddenInput = document.getElementById(editorId + '-hidden');
            const initialContent = hiddenInput ? hiddenInput.value : '';

            // Crear el editor
            const editor = window.initTiptapEditor(editorId);

            // Cargar contenido inicial si existe
            if (editor && initialContent) {
                editor.commands.setContent(initialContent);
            }
        });
    }, 100);
};

// Escuchar eventos de Livewire para reinicializar editores cuando cambia el paso
document.addEventListener('livewire:navigated', () => {
    window.reinitAllTiptapEditors();
});

document.addEventListener('livewire:load', () => {
    window.reinitAllTiptapEditors();
});

// También escuchar eventos personalizados del wizard
document.addEventListener('DOMContentLoaded', () => {
    // Escuchar cuando Livewire actualiza el componente
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        succeed(({ snapshot, effect }) => {
            // Reinicializar editores después de que Livewire actualice el DOM
            window.reinitAllTiptapEditors();
        });
    });
});