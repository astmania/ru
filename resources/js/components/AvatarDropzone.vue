<template>
    <div class="avatar-dropzone">
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
                <img :src="modelValue" alt="Аватар" class="avatar-preview" />
                <div class="overlay">
                    <button type="button" class="overlay-btn" @click.stop="openFilePicker">Заменить</button>
                    <button type="button" class="overlay-btn overlay-btn--danger" @click.stop="clear">Удалить</button>
                </div>
            </template>
            <template v-else>
                <div class="placeholder">
                    <div class="icon">＋</div>
                    <div class="title">Перетащите фото сюда</div>
                    <div class="subtitle">или нажмите для выбора</div>
                    <div class="hint">JPG, PNG, WEBP, до 2 MB</div>
                </div>
            </template>
            <div v-if="uploading" class="loading">Загрузка…</div>
        </div>
        <input ref="fileInput" type="file" accept="image/*" class="hidden-input" @change="onFilePicked" />
        <div v-if="error" class="error">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../services/api';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'uploaded']);

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
    if (file.size > 2 * 1024 * 1024) {
        error.value = 'Файл слишком большой (макс. 2 MB)';
        return;
    }

    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('avatar', file);
        const { data } = await api.post('/user/upload-avatar', fd);
        const url = data?.avatar || data?.user?.avatar;
        if (url) {
            emit('update:modelValue', url);
            emit('uploaded', data?.user);
        } else {
            error.value = 'Не удалось загрузить аватар';
        }
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            (e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(', ') : '') ||
            'Ошибка загрузки';
    } finally {
        uploading.value = false;
    }
}
</script>

<style scoped>
.avatar-dropzone {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.drop-area {
    position: relative;
    width: 200px;
    height: 200px;
    border: 2px dashed var(--color-border);
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.drop-area.is-dragover {
    border-color: var(--color-accent);
    background: rgba(0, 0, 0, 0.02);
}

.avatar-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 16px;
    color: var(--color-text-muted);
}

.icon {
    font-size: 32px;
    margin-bottom: 8px;
    color: var(--color-text);
}

.title {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-text);
}

.subtitle {
    font-size: 12px;
    margin-top: 2px;
}

.hint {
    font-size: 11px;
    margin-top: 8px;
}

.overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.15s ease;
}

.drop-area.has-image:hover .overlay {
    opacity: 1;
}

.overlay-btn {
    padding: 8px 14px;
    font-size: 13px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    background: rgba(0, 0, 0, 0.4);
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
}

.overlay-btn:hover {
    background: rgba(0, 0, 0, 0.6);
}

.loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    font-weight: 500;
}

.hidden-input {
    display: none;
}

.error {
    font-size: 13px;
    color: #dc2626;
}
</style>
