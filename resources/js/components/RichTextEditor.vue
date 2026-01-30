<template>
    <div class="rich-text-editor">
        <div v-if="editor" class="editor-toolbar">
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
                title="Жирный"
            >
                <strong>B</strong>
            </button>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
                title="Курсив"
            >
                <em>I</em>
            </button>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('strike') }"
                @click="editor.chain().focus().toggleStrike().run()"
                title="Зачёркнутый"
            >
                <s>S</s>
            </button>
            <span class="toolbar-sep"></span>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('heading', { level: 2 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                title="Заголовок 2"
            >
                H2
            </button>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('heading', { level: 3 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                title="Заголовок 3"
            >
                H3
            </button>
            <span class="toolbar-sep"></span>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
                title="Маркированный список"
            >
                •
            </button>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
                title="Нумерованный список"
            >
                1.
            </button>
            <span class="toolbar-sep"></span>
            <button
                type="button"
                class="toolbar-btn"
                :class="{ active: editor.isActive('blockquote') }"
                @click="editor.chain().focus().toggleBlockquote().run()"
                title="Цитата"
            >
                "
            </button>
            <button
                type="button"
                class="toolbar-btn"
                @click="addLink"
                title="Ссылка"
            >
                🔗
            </button>
        </div>
        <editor-content :editor="editor" class="editor-content" />
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Введите текст статьи...' },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false, HTMLAttributes: { target: '_blank', rel: 'noopener' } }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'editor-prose',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
        editor.value.commands.setContent(val || '', false);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function addLink() {
    const url = window.prompt('URL ссылки:');
    if (url) {
        editor.value?.chain().focus().setLink({ href: url }).run();
    }
}
</script>

<style scoped>
.rich-text-editor {
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--color-surface);
}

.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 8px;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-bg);
}

.toolbar-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
}

.toolbar-btn:hover {
    background: var(--color-border);
    color: var(--color-text);
}

.toolbar-btn.active {
    background: var(--color-accent);
    color: #fff;
}

.toolbar-sep {
    width: 1px;
    height: 24px;
    background: var(--color-border);
    margin: 0 4px;
    align-self: center;
}

.editor-content {
    min-height: 280px;
}

.editor-content :deep(.tiptap) {
    padding: 16px;
    min-height: 280px;
    outline: none;
}

.editor-content :deep(.tiptap p.is-editor-empty:first-child::before) {
    color: var(--color-text-muted);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.editor-content :deep(.tiptap h2) {
    font-size: 1.4em;
    margin: 1em 0 0.4em;
}

.editor-content :deep(.tiptap h3) {
    font-size: 1.2em;
    margin: 0.8em 0 0.3em;
}

.editor-content :deep(.tiptap ul),
.editor-content :deep(.tiptap ol) {
    padding-left: 1.5em;
    margin: 0.5em 0;
}

.editor-content :deep(.tiptap blockquote) {
    border-left: 3px solid var(--color-border);
    padding-left: 1em;
    margin: 0.5em 0;
    color: var(--color-text-muted);
}

.editor-content :deep(.tiptap a) {
    color: var(--color-accent);
    text-decoration: underline;
}
</style>
