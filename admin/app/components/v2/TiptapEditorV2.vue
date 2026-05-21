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

const toast = useToast()

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: { class: 'text-primary underline' },
    }),
    Placeholder.configure({ placeholder: props.placeholder || 'Escribe aquí...' }),
    Table.configure({ resizable: true, HTMLAttributes: { class: 'tiptap-table' } }),
    TableRow,
    TableHeader,
    TableCell,
    Image.configure({ inline: false, allowBase64: true, HTMLAttributes: { class: 'tiptap-image' } }),
    Youtube.configure({ controls: true, nocookie: true, width: 640, height: 360, HTMLAttributes: { class: 'tiptap-youtube' } }),
    TextAlign.configure({ types: ['heading', 'paragraph'], alignments: ['left', 'center', 'right', 'justify'] }),
    Highlight.configure({ multicolor: false, HTMLAttributes: { class: 'tiptap-highlight' } }),
  ],
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
})

watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue, { emitUpdate: false })
  }
})

const setLink = () => {
  const previousUrl = editor.value?.getAttributes('link').href
  const url = window.prompt('URL del enlace:', previousUrl)
  if (url === null) return
  if (url === '') {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

const triggerImageUpload = () => fileInput.value?.click()

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
      headers: { Authorization: `Bearer ${auth.token}` },
    })

    if (response?.success && (response.path || response.url)) {
      // Build the src from the API host + returned path so it never depends on
      // the server's APP_URL being correct (a relative/wrong-host url renders a
      // broken <img>). The API host always serves /storage.
      const apiBase = (config.public.apiUrl as string).replace(/\/api\/?$/, '')
      const src = response.path
        ? `${apiBase}/storage/${String(response.path).replace(/^\/?(storage\/)?/, '')}`
        : response.url
      editor.value?.chain().focus().setImage({ src, alt: file.name }).run()
      toast.add({ title: 'Imagen subida', icon: 'i-lucide-circle-check', color: 'success' })
    } else {
      toast.add({ title: 'Error al subir', color: 'error', icon: 'i-lucide-alert-triangle' })
    }
  } catch (err: any) {
    toast.add({
      title: 'Error al subir imagen',
      description: err?.data?.message || err?.message,
      color: 'error',
      icon: 'i-lucide-alert-triangle',
    })
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

onBeforeUnmount(() => editor.value?.destroy())

const isActive = (name: string, attrs?: any) => editor.value?.isActive(name, attrs) ?? false
</script>

<template>
  <div
    v-if="editor"
    class="border border-default rounded-xl overflow-hidden bg-default transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary"
  >
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-0.5 p-2 border-b border-default bg-elevated/30">
      <!-- Inline formatting -->
      <UButton
        size="xs"
        icon="i-lucide-bold"
        :color="isActive('bold') ? 'primary' : 'neutral'"
        :variant="isActive('bold') ? 'solid' : 'ghost'"
        title="Negrita (Ctrl+B)"
        @click="editor.chain().focus().toggleBold().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-italic"
        :color="isActive('italic') ? 'primary' : 'neutral'"
        :variant="isActive('italic') ? 'solid' : 'ghost'"
        title="Cursiva (Ctrl+I)"
        @click="editor.chain().focus().toggleItalic().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-underline"
        :color="isActive('underline') ? 'primary' : 'neutral'"
        :variant="isActive('underline') ? 'solid' : 'ghost'"
        title="Subrayado (Ctrl+U)"
        @click="editor.chain().focus().toggleUnderline().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-strikethrough"
        :color="isActive('strike') ? 'primary' : 'neutral'"
        :variant="isActive('strike') ? 'solid' : 'ghost'"
        title="Tachado"
        @click="editor.chain().focus().toggleStrike().run()"
      />

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Headings -->
      <UButton
        size="xs"
        :color="isActive('heading', { level: 2 }) ? 'primary' : 'neutral'"
        :variant="isActive('heading', { level: 2 }) ? 'solid' : 'ghost'"
        title="Encabezado H2"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
      >
        <span class="font-black text-xs">H2</span>
      </UButton>
      <UButton
        size="xs"
        :color="isActive('heading', { level: 3 }) ? 'primary' : 'neutral'"
        :variant="isActive('heading', { level: 3 }) ? 'solid' : 'ghost'"
        title="Encabezado H3"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
      >
        <span class="font-black text-xs">H3</span>
      </UButton>

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Lists -->
      <UButton
        size="xs"
        icon="i-lucide-list"
        :color="isActive('bulletList') ? 'primary' : 'neutral'"
        :variant="isActive('bulletList') ? 'solid' : 'ghost'"
        title="Lista con viñetas"
        @click="editor.chain().focus().toggleBulletList().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-list-ordered"
        :color="isActive('orderedList') ? 'primary' : 'neutral'"
        :variant="isActive('orderedList') ? 'solid' : 'ghost'"
        title="Lista numerada"
        @click="editor.chain().focus().toggleOrderedList().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-quote"
        :color="isActive('blockquote') ? 'primary' : 'neutral'"
        :variant="isActive('blockquote') ? 'solid' : 'ghost'"
        title="Cita"
        @click="editor.chain().focus().toggleBlockquote().run()"
      />

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Link / Highlight -->
      <UButton
        size="xs"
        icon="i-lucide-link"
        :color="isActive('link') ? 'primary' : 'neutral'"
        :variant="isActive('link') ? 'solid' : 'ghost'"
        title="Insertar/Editar enlace"
        @click="setLink"
      />
      <UButton
        v-if="isActive('link')"
        size="xs"
        icon="i-lucide-link-2-off"
        color="neutral"
        variant="ghost"
        title="Quitar enlace"
        @click="editor.chain().focus().unsetLink().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-highlighter"
        :color="isActive('highlight') ? 'warning' : 'neutral'"
        :variant="isActive('highlight') ? 'subtle' : 'ghost'"
        title="Resaltar texto"
        @click="editor.chain().focus().toggleHighlight().run()"
      />

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Alignment -->
      <UButton
        size="xs"
        icon="i-lucide-align-left"
        :color="isActive({ textAlign: 'left' }) ? 'primary' : 'neutral'"
        :variant="isActive({ textAlign: 'left' }) ? 'solid' : 'ghost'"
        title="Alinear izquierda"
        @click="editor.chain().focus().setTextAlign('left').run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-align-center"
        :color="isActive({ textAlign: 'center' }) ? 'primary' : 'neutral'"
        :variant="isActive({ textAlign: 'center' }) ? 'solid' : 'ghost'"
        title="Centrar"
        @click="editor.chain().focus().setTextAlign('center').run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-align-right"
        :color="isActive({ textAlign: 'right' }) ? 'primary' : 'neutral'"
        :variant="isActive({ textAlign: 'right' }) ? 'solid' : 'ghost'"
        title="Alinear derecha"
        @click="editor.chain().focus().setTextAlign('right').run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-align-justify"
        :color="isActive({ textAlign: 'justify' }) ? 'primary' : 'neutral'"
        :variant="isActive({ textAlign: 'justify' }) ? 'solid' : 'ghost'"
        title="Justificar"
        @click="editor.chain().focus().setTextAlign('justify').run()"
      />

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Media -->
      <UButton
        size="xs"
        :icon="uploading ? 'i-lucide-loader-circle' : 'i-lucide-image'"
        color="neutral"
        variant="ghost"
        :disabled="uploading"
        title="Subir imagen desde tu equipo"
        :ui="{ leadingIcon: uploading ? 'animate-spin' : '' }"
        @click="triggerImageUpload"
      />
      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onImageSelected" />
      <UButton
        size="xs"
        icon="i-lucide-image-plus"
        color="neutral"
        variant="ghost"
        title="Insertar imagen por URL"
        @click="setImageByUrl"
      />
      <UButton
        size="xs"
        icon="i-lucide-youtube"
        color="neutral"
        variant="ghost"
        title="Insertar video de YouTube"
        @click="setYoutubeVideo"
      />

      <USeparator orientation="vertical" class="h-5 mx-1" />

      <!-- Table -->
      <UButton
        size="xs"
        icon="i-lucide-table"
        color="neutral"
        variant="ghost"
        title="Insertar tabla 3x3"
        @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
      />

      <!-- Table controls (solo si el cursor está en una tabla) -->
      <template v-if="isActive('table')">
        <USeparator orientation="vertical" class="h-5 mx-1" />
        <UBadge color="info" variant="subtle" size="xs" class="mr-1" icon="i-lucide-table-2">Tabla</UBadge>
        <UButton size="xs" icon="i-lucide-arrow-left-from-line" color="neutral" variant="ghost" title="Columna a la izquierda" @click="editor.chain().focus().addColumnBefore().run()" />
        <UButton size="xs" icon="i-lucide-arrow-right-from-line" color="neutral" variant="ghost" title="Columna a la derecha" @click="editor.chain().focus().addColumnAfter().run()" />
        <UButton size="xs" icon="i-lucide-column-delete" color="error" variant="ghost" title="Eliminar columna" @click="editor.chain().focus().deleteColumn().run()" />
        <UButton size="xs" icon="i-lucide-arrow-up-from-line" color="neutral" variant="ghost" title="Fila arriba" @click="editor.chain().focus().addRowBefore().run()" />
        <UButton size="xs" icon="i-lucide-arrow-down-from-line" color="neutral" variant="ghost" title="Fila abajo" @click="editor.chain().focus().addRowAfter().run()" />
        <UButton size="xs" icon="i-lucide-row-delete" color="error" variant="ghost" title="Eliminar fila" @click="editor.chain().focus().deleteRow().run()" />
        <UButton size="xs" icon="i-lucide-heading" color="neutral" variant="ghost" title="Alternar encabezado" @click="editor.chain().focus().toggleHeaderRow().run()" />
        <UButton size="xs" icon="i-lucide-merge" color="neutral" variant="ghost" title="Combinar / dividir celdas" @click="editor.chain().focus().mergeOrSplit().run()" />
        <UButton size="xs" icon="i-lucide-trash-2" color="error" variant="ghost" title="Eliminar tabla" @click="editor.chain().focus().deleteTable().run()" />
      </template>

      <div class="flex-1" />

      <!-- Undo / Redo (always at the end) -->
      <UButton
        size="xs"
        icon="i-lucide-undo-2"
        color="neutral"
        variant="ghost"
        title="Deshacer (Ctrl+Z)"
        :disabled="!editor.can().undo()"
        @click="editor.chain().focus().undo().run()"
      />
      <UButton
        size="xs"
        icon="i-lucide-redo-2"
        color="neutral"
        variant="ghost"
        title="Rehacer (Ctrl+Y)"
        :disabled="!editor.can().redo()"
        @click="editor.chain().focus().redo().run()"
      />
    </div>

    <!-- Editor surface -->
    <EditorContent
      :editor="editor"
      class="tiptap-v2 prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] outline-none"
    />
  </div>
</template>

<style>
.tiptap-v2 .tiptap p.is-editor-empty:first-child::before {
  color: var(--ui-text-muted);
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}
.tiptap-v2 .tiptap {
  outline: none !important;
}
.tiptap-v2 .tiptap p {
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}
.tiptap-v2 .tiptap ul {
  list-style-type: disc;
  padding-left: 1.5em;
}
.tiptap-v2 .tiptap ol {
  list-style-type: decimal;
  padding-left: 1.5em;
}
.tiptap-v2 .tiptap blockquote {
  border-left: 3px solid var(--ui-primary);
  padding-left: 1em;
  color: var(--ui-text-muted);
  font-style: italic;
}

.tiptap-v2 .tiptap table,
.tiptap-v2 table.tiptap-table {
  border-collapse: collapse;
  width: 100%;
  margin: 0.75em 0;
  font-size: 0.875rem;
}
.tiptap-v2 .tiptap table th,
.tiptap-v2 .tiptap table td,
.tiptap-v2 table.tiptap-table th,
.tiptap-v2 table.tiptap-table td {
  border: 1px solid var(--ui-border);
  padding: 0.5rem 0.75rem;
  vertical-align: top;
  text-align: left;
}
.tiptap-v2 .tiptap table th,
.tiptap-v2 table.tiptap-table th {
  background: var(--ui-bg-elevated);
  font-weight: 700;
  color: var(--ui-text);
}
.tiptap-v2 .tiptap table tr:nth-child(even) td,
.tiptap-v2 table.tiptap-table tbody tr:nth-child(even) td {
  background: var(--ui-bg-elevated);
}

.tiptap-v2 .tiptap img,
.tiptap-v2 img.tiptap-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 0.75em 0;
}
.tiptap-v2 .tiptap img.ProseMirror-selectednode {
  outline: 3px solid var(--ui-primary);
}

.tiptap-v2 .tiptap iframe,
.tiptap-v2 iframe.tiptap-youtube {
  border-radius: 8px;
  margin: 0.75em 0;
  max-width: 100%;
}

.tiptap-v2 .tiptap mark,
.tiptap-v2 mark.tiptap-highlight {
  background: rgb(254 240 138);
  padding: 0 2px;
  border-radius: 3px;
}

.dark .tiptap-v2 .tiptap mark,
.dark .tiptap-v2 mark.tiptap-highlight {
  background: rgb(146 64 14);
  color: rgb(254 240 138);
}
</style>
