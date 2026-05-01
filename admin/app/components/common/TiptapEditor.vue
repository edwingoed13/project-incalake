<template>
  <div v-if="editor" class="tiptap-editor-container border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-950 transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
      <button 
        type="button"
        @click="editor.chain().focus().toggleBold().run()"
        :class="{ 'bg-primary text-white': editor.isActive('bold') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Bold"
      >
        <span class="material-symbols-outlined text-sm">format_bold</span>
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleItalic().run()"
        :class="{ 'bg-primary text-white': editor.isActive('italic') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Italic"
      >
        <span class="material-symbols-outlined text-sm">format_italic</span>
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'bg-primary text-white': editor.isActive('underline') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Underline"
      >
        <span class="material-symbols-outlined text-sm">format_underlined</span>
      </button>

      <div class="w-px h-4 bg-slate-200 dark:bg-slate-800 mx-1"></div>

      <button 
        type="button"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'bg-primary text-white': editor.isActive('heading', { level: 2 }) }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400 font-bold text-xs"
        title="H2"
      >
        H2
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'bg-primary text-white': editor.isActive('heading', { level: 3 }) }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400 font-bold text-xs"
        title="H3"
      >
        H3
      </button>

      <div class="w-px h-4 bg-slate-200 dark:bg-slate-800 mx-1"></div>

      <button 
        type="button"
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-primary text-white': editor.isActive('bulletList') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Bullet List"
      >
        <span class="material-symbols-outlined text-sm">format_list_bulleted</span>
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'bg-primary text-white': editor.isActive('orderedList') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Ordered List"
      >
        <span class="material-symbols-outlined text-sm">format_list_numbered</span>
      </button>

      <div class="w-px h-4 bg-slate-200 dark:bg-slate-800 mx-1"></div>

      <button 
        type="button"
        @click="setLink"
        :class="{ 'bg-primary text-white': editor.isActive('link') }"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Link"
      >
        <span class="material-symbols-outlined text-sm">link</span>
      </button>
      <button
        type="button"
        @click="editor.chain().focus().unsetLink().run()"
        v-if="editor.isActive('link')"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Unlink"
      >
        <span class="material-symbols-outlined text-sm">link_off</span>
      </button>

      <div class="w-px h-4 bg-slate-200 dark:bg-slate-800 mx-1"></div>

      <!-- Insert table -->
      <button
        type="button"
        @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
        class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400"
        title="Insertar tabla 3x3"
      >
        <span class="material-symbols-outlined text-sm">table</span>
      </button>

      <!-- Table editing controls — only visible when cursor is inside a table -->
      <template v-if="editor.isActive('table')">
        <button type="button" @click="editor.chain().focus().addColumnBefore().run()" title="Agregar columna a la izquierda" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">format_indent_decrease</span>
        </button>
        <button type="button" @click="editor.chain().focus().addColumnAfter().run()" title="Agregar columna a la derecha" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">format_indent_increase</span>
        </button>
        <button type="button" @click="editor.chain().focus().deleteColumn().run()" title="Eliminar columna" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-rose-500">
          <span class="material-symbols-outlined text-sm">view_column</span>
        </button>
        <button type="button" @click="editor.chain().focus().addRowBefore().run()" title="Agregar fila arriba" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">vertical_align_top</span>
        </button>
        <button type="button" @click="editor.chain().focus().addRowAfter().run()" title="Agregar fila abajo" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">vertical_align_bottom</span>
        </button>
        <button type="button" @click="editor.chain().focus().deleteRow().run()" title="Eliminar fila" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-rose-500">
          <span class="material-symbols-outlined text-sm">table_rows</span>
        </button>
        <button type="button" @click="editor.chain().focus().toggleHeaderRow().run()" title="Alternar fila de encabezado" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">view_headline</span>
        </button>
        <button type="button" @click="editor.chain().focus().mergeOrSplit().run()" title="Combinar / dividir celdas" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-400">
          <span class="material-symbols-outlined text-sm">join</span>
        </button>
        <button type="button" @click="editor.chain().focus().deleteTable().run()" title="Eliminar tabla" class="p-2 rounded hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-colors text-rose-500">
          <span class="material-symbols-outlined text-sm">delete</span>
        </button>
      </template>
    </div>

    <!-- Editor Surface -->
    <EditorContent :editor="editor" class="prose prose-sm dark:prose-invert max-w-none p-4 min-h-[150px] outline-none" />
  </div>
</template>

<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps<{
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-primary underline'
      }
    }),
    Placeholder.configure({
      placeholder: props.placeholder || 'Start typing...'
    }),
    Table.configure({
      resizable: true,
      HTMLAttributes: { class: 'tiptap-table' }
    }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

// Sync external changes
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue, { emitUpdate: false })
  }
})

const setLink = () => {
  const previousUrl = editor.value?.getAttributes('link').href
  const url = window.prompt('URL:', previousUrl)

  // cancelled
  if (url === null) {
    return
  }

  // empty
  if (url === '') {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  // update link
  editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<style>
/* Tiptap specific styles */
.tiptap p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.tiptap {
  outline: none !important;
}

.tiptap p {
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}

.tiptap ul {
  list-style-type: disc;
  padding-left: 1.5em;
}

.tiptap ol {
  list-style-type: decimal;
  padding-left: 1.5em;
}

.tiptap table,
table.tiptap-table {
  border-collapse: collapse;
  width: 100%;
  margin: 0.75em 0;
  font-size: 0.875rem;
}

.tiptap table th,
.tiptap table td,
table.tiptap-table th,
table.tiptap-table td {
  border: 1px solid #e2e8f0;
  padding: 0.5rem 0.75rem;
  vertical-align: top;
  text-align: left;
}

.tiptap table th,
table.tiptap-table th {
  background: #f8fafc;
  font-weight: 700;
  color: #475569;
}

.tiptap table tr:nth-child(even) td,
table.tiptap-table tbody tr:nth-child(even) td {
  background: #fafafa;
}
</style>
