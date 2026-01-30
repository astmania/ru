<template>
    <div class="image-dropzone" :class="{ 'image-dropzone--fill': fillHeight }">
        <label class="label">{{ label }}</label>

        <div
            class="drop-area"
            :class="{ 'is-dragover': isDragOver, 'has-image': !!modelValue }"
            @dragenter.prevent="onDragEnter"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
            @click="openFilePicker"
            role="button"
            tabindex="0"
            @keydown.enter.prevent="openFilePicker"
            @keydown.space.prevent="openFilePicker"
        >
            <template v-if="modelValue">
                <img :src="modelValue" alt="preview" class="preview" />
                <div class="overlay">
                    <button type="button" class="overlay-btn" @click.stop="openFilePicker">
                        Заменить
                    </button>
                    <button type="button" class="overlay-btn overlay-btn--danger" @click.stop="clear">
                        Удалить
                    </button>
                </div>
            </template>

            <template v-else>
                <div class="placeholder">
                    <div class="icon">＋</div>
                    <div class="title">Перетащите картинку сюда</div>
                    <div class="subtitle">или нажмите, чтобы выбрать файл</div>
                    <div class="hint">PNG / JPG / WEBP, до 5 MB</div>
                </div>
            </template>

            <div v-if="uploading" class="loading">
                Загрузка…
            </div>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden-input"
            @change="onFilePicked"
        />

        <div v-if="error" class="error">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../services/api';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Картинка' },
    fillHeight: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const isDragOver = ref(false);
const uploading = ref(false);
const error = ref('');

function openFilePicker() {
    fileInput.value?.click();
}

function onDragEnter() {
    isDragOver.value = true;
}

function onDragOver() {
    isDragOver.value = true;
}

function onDragLeave() {
    isDragOver.value = false;
}

function onDrop(e) {
    isDragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) upload(file);
}

function onFilePicked(e) {
    const file = e.target.files?.[0];
    if (file) upload(file);
    // сбрасываем, чтобы можно было выбрать тот же файл повторно
    e.target.value = '';
}

function clear() {
    emit('update:modelValue', '');
}

async function upload(file) {
    error.value = '';

    if (!file.type?.startsWith('image/')) {
        error.value = 'Выберите файл изображения';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        error.value = 'Файл слишком большой (макс. 5 MB)';
        return;
    }

    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('image', file);
        const { data } = await api.post('/admin/articles/upload-image', fd);
        emit('update:modelValue', data?.url || '');
        if (!data?.url) error.value = 'Не удалось получить URL картинки';
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            (e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(', ') : '') ||
            'Ошибка загрузки картинки';
    } finally {
        uploading.value = false;
    }
}
</script>

<style scoped>
.image-dropzone {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.image-dropzone--fill {
    flex: 1;
    min-height: 0;
}

.image-dropzone--fill .drop-area {
    flex: 1;
    min-height: 180px;
}

.image-dropzone--fill .drop-area .placeholder {
    height: 100%;
}

.image-dropzone--fill .drop-area .preview {
    height: 100%;
    min-height: 0;
}

.label {
    font-size: 13px;
    font-weight: 500;
    color: var(--color-text);
}

.drop-area {
    position: relative;
    border: 1px dashed var(--color-border);
    border-radius: 10px;
    background: var(--color-surface);
    min-height: 240px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.drop-area.is-dragover {
    border-color: var(--color-text-muted);
    background: rgba(0, 0, 0, 0.02);
}

.placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 24px;
    color: var(--color-text-muted);
}

.icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--color-text);
    margin-bottom: 12px;
    background: var(--color-bg);
}

.title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text);
}

.subtitle {
    font-size: 13px;
    margin-top: 4px;
}

.hint {
    font-size: 12px;
    margin-top: 10px;
}

.preview {
    width: 100%;
    height: 100%;
    min-height: 240px;
    object-fit: cover;
    display: block;
}

.overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0));
    opacity: 0;
    transition: opacity 0.15s ease;
}

.drop-area.has-image:hover .overlay {
    opacity: 1;
}

.overlay-btn {
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(0, 0, 0, 0.35);
    color: #fff;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    cursor: pointer;
    backdrop-filter: blur(6px);
}

.overlay-btn:hover {
    background: rgba(0, 0, 0, 0.5);
}

.overlay-btn--danger {
    border-color: rgba(220, 38, 38, 0.5);
}

.loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    color: var(--color-text);
}

.hidden-input {
    display: none;
}

.error {
    font-size: 13px;
    color: var(--color-error);
}
</style>

