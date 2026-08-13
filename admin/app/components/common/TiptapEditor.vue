<template>
  <div v-if="editor" class="tiptap-editor-container border border-default rounded-xl overflow-hidden bg-white dark:bg-elevated transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 p-2 border-b border-default bg-elevated/40">
      <button 
        type="button"
        @click="editor.chain().focus().toggleBold().run()"
        :class="{ 'bg-primary text-white': editor.isActive('bold') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Bold"
      >
        <UIcon name="i-lucide-bold" class="size-4" />
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleItalic().run()"
        :class="{ 'bg-primary text-white': editor.isActive('italic') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Italic"
      >
        <UIcon name="i-lucide-italic" class="size-4" />
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'bg-primary text-white': editor.isActive('underline') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Underline"
      >
        <UIcon name="i-lucide-underline" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <button 
        type="button"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'bg-primary text-white': editor.isActive('heading', { level: 2 }) }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted font-bold text-xs"
        title="H2"
      >
        H2
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'bg-primary text-white': editor.isActive('heading', { level: 3 }) }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted font-bold text-xs"
        title="H3"
      >
        H3
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <button 
        type="button"
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'bg-primary text-white': editor.isActive('bulletList') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Bullet List"
      >
        <UIcon name="i-lucide-list" class="size-4" />
      </button>
      <button 
        type="button"
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'bg-primary text-white': editor.isActive('orderedList') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Ordered List"
      >
        <UIcon name="i-lucide-list-ordered" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <button 
        type="button"
        @click="setLink"
        :class="{ 'bg-primary text-white': editor.isActive('link') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Link"
      >
        <UIcon name="i-lucide-link" class="size-4" />
      </button>
      <button
        type="button"
        @click="editor.chain().focus().unsetLink().run()"
        v-if="editor.isActive('link')"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Unlink"
      >
        <UIcon name="i-lucide-unlink" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <!-- Highlight -->
      <button
        type="button"
        @click="editor.chain().focus().toggleHighlight().run()"
        :class="{ 'bg-primary text-white': editor.isActive('highlight') }"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Resaltar texto"
      >
        <UIcon name="i-lucide-highlighter" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <!-- Text alignment -->
      <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ 'bg-primary text-white': editor.isActive({ textAlign: 'left' }) }" title="Alinear a la izquierda" class="p-2 rounded hover:bg-accented transition-colors text-muted">
        <UIcon name="i-lucide-align-left" class="size-4" />
      </button>
      <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ 'bg-primary text-white': editor.isActive({ textAlign: 'center' }) }" title="Centrar" class="p-2 rounded hover:bg-accented transition-colors text-muted">
        <UIcon name="i-lucide-align-center" class="size-4" />
      </button>
      <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ 'bg-primary text-white': editor.isActive({ textAlign: 'right' }) }" title="Alinear a la derecha" class="p-2 rounded hover:bg-accented transition-colors text-muted">
        <UIcon name="i-lucide-align-right" class="size-4" />
      </button>
      <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()" :class="{ 'bg-primary text-white': editor.isActive({ textAlign: 'justify' }) }" title="Justificar" class="p-2 rounded hover:bg-accented transition-colors text-muted">
        <UIcon name="i-lucide-align-justify" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <!-- Image upload -->
      <button
        type="button"
        @click="triggerImageUpload"
        :disabled="uploading"
        class="p-2 rounded hover:bg-accented transition-colors text-muted disabled:opacity-50"
        title="Subir imagen"
      >
        <UIcon v-if="!uploading" name="i-lucide-image" class="size-4" />
        <UIcon v-else name="i-lucide-loader-circle" class="animate-spin size-4" />
      </button>
      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onImageSelected" />

      <!-- Image by URL -->
      <button
        type="button"
        @click="setImageByUrl"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Imagen por URL"
      >
        <UIcon name="i-lucide-image-plus" class="size-4" />
      </button>

      <!-- YouTube embed -->
      <button
        type="button"
        @click="setYoutubeVideo"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Insertar video de YouTube"
      >
        <UIcon name="i-lucide-youtube" class="size-4" />
      </button>

      <div class="w-px h-4 bg-accented mx-1"></div>

      <!-- Insert table -->
      <button
        type="button"
        @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
        class="p-2 rounded hover:bg-accented transition-colors text-muted"
        title="Insertar tabla 3x3"
      >
        <UIcon name="i-lucide-table" class="size-4" />
      </button>

      <!-- Table editing controls — only visible when cursor is inside a table -->
      <template v-if="editor.isActive('table')">
        <button type="button" @click="editor.chain().focus().addColumnBefore().run()" title="Agregar columna a la izquierda" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-indent-decrease" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().addColumnAfter().run()" title="Agregar columna a la derecha" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-indent-increase" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().deleteColumn().run()" title="Eliminar columna" class="p-2 rounded hover:bg-accented transition-colors text-rose-500">
          <UIcon name="i-lucide-columns-3" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().addRowBefore().run()" title="Agregar fila arriba" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-arrow-up-to-line" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().addRowAfter().run()" title="Agregar fila abajo" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-arrow-down-to-line" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().deleteRow().run()" title="Eliminar fila" class="p-2 rounded hover:bg-accented transition-colors text-rose-500">
          <UIcon name="i-lucide-rows-3" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().toggleHeaderRow().run()" title="Alternar fila de encabezado" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-pilcrow" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().mergeOrSplit().run()" title="Combinar / dividir celdas" class="p-2 rounded hover:bg-accented transition-colors text-muted">
          <UIcon name="i-lucide-table-cells-merge" class="size-4" />
        </button>
        <button type="button" @click="editor.chain().focus().deleteTable().run()" title="Eliminar tabla" class="p-2 rounded hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-colors text-rose-500">
          <UIcon name="i-lucide-trash-2" class="size-4" />
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
import { Image } from '@tiptap/extension-image'
import { Youtube } from '@tiptap/extension-youtube'
import { TextAlign } from '@tiptap/extension-text-align'
import { Highlight } from '@tiptap/extension-highlight'
import { ref, watch, onBeforeUnmount } from 'vue'
import { useAuthStore } from '~/stores/auth'

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
    Image.configure({
      inline: false,
      allowBase64: true,
      HTMLAttributes: { class: 'tiptap-image' },
    }),
    Youtube.configure({
      controls: true,
      nocookie: true,
      width: 640,
      height: 360,
      HTMLAttributes: { class: 'tiptap-youtube' },
    }),
    TextAlign.configure({
      types: ['heading', 'paragraph'],
      alignments: ['left', 'center', 'right', 'justify'],
    }),
    Highlight.configure({
      multicolor: false,
      HTMLAttributes: { class: 'tiptap-highlight' },
    }),
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

// Image upload via the existing /admin/tours/upload-image endpoint.
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

const triggerImageUpload = () => {
  fileInput.value?.click()
}

const onImageSelected = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploading.value = true
  try {
    const config = useRuntimeConfig()
    const auth = useAuthStore()
    const formData = new FormData()
    formData.append('image', file)

    const response: any = await $fetch(`${config.public.apiUrl}/admin/tours/upload-image`, {
      method: 'POST',
      body: formData,
      headers: { 'Authorization': `Bearer ${auth.token}` },
    })

    if (response?.success && response.url) {
      editor.value?.chain().focus().setImage({ src: response.url, alt: file.name }).run()
    } else {
      alert('No se pudo subir la imagen.')
    }
  } catch (err: any) {
    console.error('[TiptapEditor] image upload failed:', err)
    alert('Error al subir imagen: ' + (err?.data?.message || err?.message || 'desconocido'))
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const setImageByUrl = () => {
  const url = window.prompt('URL de la imagen:')
  if (!url) return
  editor.value?.chain().focus().setImage({ src: url }).run()
}

const setYoutubeVideo = () => {
  const url = window.prompt('URL de YouTube (ej. https://www.youtube.com/watch?v=...):')
  if (!url) return
  editor.value?.chain().focus().setYoutubeVideo({ src: url }).run()
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

/* Images inside the editor */
.tiptap img,
img.tiptap-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 0.75em 0;
}

.tiptap img.ProseMirror-selectednode {
  outline: 3px solid #6366f1;
}

/* YouTube embeds */
.tiptap iframe,
iframe.tiptap-youtube {
  border-radius: 8px;
  margin: 0.75em 0;
  max-width: 100%;
}

/* Highlighted text */
.tiptap mark,
mark.tiptap-highlight {
  background: #fde68a;
  padding: 0 2px;
  border-radius: 3px;
}

/* Text alignment — let TipTap's data-text-align attr drive the style */
.tiptap p[style*="text-align: center"],
.tiptap h1[style*="text-align: center"],
.tiptap h2[style*="text-align: center"],
.tiptap h3[style*="text-align: center"] { text-align: center; }
.tiptap p[style*="text-align: right"],
.tiptap h1[style*="text-align: right"],
.tiptap h2[style*="text-align: right"],
.tiptap h3[style*="text-align: right"] { text-align: right; }
.tiptap p[style*="text-align: justify"],
.tiptap h1[style*="text-align: justify"],
.tiptap h2[style*="text-align: justify"],
.tiptap h3[style*="text-align: justify"] { text-align: justify; }
</style>
