<template>
    <div class="article-form">
        <header class="page-header">
            <h1 class="page-title">{{ isEdit ? 'Редактирование статьи' : 'Новая статья' }}</h1>
            <router-link to="/articles" class="back-link">← К списку</router-link>
        </header>

        <form @submit.prevent="save" class="form-card">
            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <!-- Две колонки: метаданные слева, картинка и публикация справа (одинаковая высота) -->
            <div class="form-grid">
                <div class="col-meta">
                    <div class="form-group">
                        <label>Заголовок *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            required
                            placeholder="Заголовок статьи"
                            @input="maybeSlugFromTitle"
                        />
                    </div>
                    <div class="form-group">
                        <label>URL (slug)</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="form-control"
                            placeholder="url-slug-статьи"
                        />
                        <span class="form-hint">Оставьте пустым — подставится из заголовка</span>
                    </div>
                    <div class="form-group">
                        <label>Краткое описание (анонс)</label>
                        <textarea
                            v-model="form.excerpt"
                            class="form-control textarea-2rows"
                            rows="2"
                            maxlength="250"
                            placeholder="Показывается в карусели на главной"
                        ></textarea>
                        <span class="form-hint">{{ (form.excerpt || '').length }}/250</span>
                    </div>

                    <div class="form-group">
                        <label>Категория</label>
                        <div class="category-chips">
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                type="button"
                                class="chip"
                                :class="{ 'chip--active': form.category_id === cat.id }"
                                @click="form.category_id = form.category_id === cat.id ? null : cat.id"
                            >
                                {{ cat.name }}
                            </button>
                        </div>
                    </div>

                    <div class="form-group meta-group">
                        <label>Мета (SEO)</label>
                        <input
                            v-model="form.meta_title"
                            type="text"
                            class="form-control"
                            placeholder="Meta title"
                            maxlength="255"
                        />
                        <textarea
                            v-model="form.meta_description"
                            class="form-control textarea-2rows"
                            rows="2"
                            placeholder="Meta description"
                            maxlength="500"
                        ></textarea>
                        <input
                            v-model="form.meta_keywords"
                            type="text"
                            class="form-control"
                            placeholder="Meta keywords (через запятую)"
                            maxlength="500"
                        />
                    </div>
                </div>

                <aside class="col-sidebar">
                    <div class="sidebar-block sidebar-block--image">
                        <ImageDropzone v-model="form.image" label="Картинка" fill-height />
                    </div>
                    <div class="sidebar-block sidebar-block--tags">
                        <div class="form-group">
                            <label>Теги (для быстрого поиска)</label>
                            <div class="tags-row">
                                <span
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    class="tag-chip-wrap"
                                    :class="{ 'tag-chip-wrap--on': form.tag_ids.includes(tag.id) }"
                                >
                                    <button
                                        type="button"
                                        class="tag-chip"
                                        :class="{ 'tag-chip--on': form.tag_ids.includes(tag.id) }"
                                        @click="toggleTag(tag.id)"
                                    >
                                        {{ tag.name }}
                                    </button>
                                    <button
                                        type="button"
                                        class="tag-chip-remove"
                                        title="Удалить тег из системы"
                                        @click.stop="removeTag(tag)"
                                    >
                                        ×
                                    </button>
                                </span>
                            </div>
                            <div class="tag-add">
                                <input
                                    v-model="newTagName"
                                    type="text"
                                    class="form-control tag-add-input"
                                    placeholder="Новый тег"
                                    maxlength="100"
                                    @keydown.enter.prevent="addNewTag"
                                />
                                <button type="button" class="tag-add-btn" @click="addNewTag" :disabled="!newTagName.trim()">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="sidebar-block sidebar-block--publish">
                        <div class="form-group">
                            <label>Опубликовать</label>
                            <input
                                v-model="form.published_at"
                                type="datetime-local"
                                class="form-control"
                            />
                            <span class="form-hint">Пусто — черновик, не показывается на главной</span>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Текст статьи на всю ширину внизу -->
            <section class="body-section">
                <label class="body-label">Текст статьи *</label>
                <RichTextEditor v-model="form.body" placeholder="Введите текст статьи..." />
            </section>

            <div class="form-actions">
                <router-link to="/articles" class="btn btn-secondary">Отмена</router-link>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    {{ loading ? 'Сохранение…' : (isEdit ? 'Сохранить' : 'Создать') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import RichTextEditor from './RichTextEditor.vue';
import ImageDropzone from './ImageDropzone.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id && route.params.id !== 'new');
const articleId = computed(() => (isEdit.value ? Number(route.params.id) : null));

const form = reactive({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    image: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    published_at: '',
    category_id: null,
    tag_ids: [],
});

const categories = ref([]);
const tags = ref([]);
const newTagName = ref('');
const loading = ref(false);
const errorMessage = ref('');
const slugManuallyEdited = ref(false);

function maybeSlugFromTitle() {
    if (!slugManuallyEdited.value && !form.slug) {
        form.slug = slugify(form.title);
    }
}

function slugify(s) {
    return String(s)
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\p{L}\p{N}-]/gu, '');
}

async function loadTags() {
    try {
        const { data } = await api.get('/tags');
        tags.value = Array.isArray(data) ? data : [];
    } catch (e) {
        tags.value = [];
    }
}

function toggleTag(id) {
    const i = form.tag_ids.indexOf(id);
    if (i === -1) form.tag_ids.push(id);
    else form.tag_ids.splice(i, 1);
}

async function removeTag(tag) {
    if (!confirm(`Удалить тег «${tag.name}» из системы? Он будет убран у всех статей.`)) return;
    try {
        await api.delete(`/admin/tags/${tag.id}`);
        const idx = tags.value.findIndex((t) => t.id === tag.id);
        if (idx !== -1) tags.value.splice(idx, 1);
        const i = form.tag_ids.indexOf(tag.id);
        if (i !== -1) form.tag_ids.splice(i, 1);
    } catch (e) {
        alert(e.response?.data?.message || 'Не удалось удалить тег');
    }
}

async function addNewTag() {
    const name = newTagName.value.trim();
    if (!name) return;
    try {
        const { data } = await api.post('/admin/tags', { name });
        if (data?.id) {
            if (!tags.value.find((t) => t.id === data.id)) tags.value.push(data);
            if (!form.tag_ids.includes(data.id)) form.tag_ids.push(data.id);
        }
        newTagName.value = '';
    } catch (e) {
        const existing = tags.value.find((t) => t.name.toLowerCase() === name.toLowerCase());
        if (existing && !form.tag_ids.includes(existing.id)) form.tag_ids.push(existing.id);
        newTagName.value = '';
    }
}

watch(() => form.slug, () => {
    slugManuallyEdited.value = true;
});

async function loadCategories() {
    try {
        const { data } = await api.get('/categories');
        categories.value = data ?? [];
    } catch (e) {
        categories.value = [];
    }
}

async function loadArticle() {
    if (!articleId.value) return;
    loading.value = true;
    try {
        const { data } = await api.get(`/admin/articles/${articleId.value}`);
        form.title = data.title ?? '';
        form.slug = data.slug ?? '';
        form.excerpt = data.excerpt ?? '';
        form.body = data.body ?? '';
        form.image = data.image ?? '';
        form.meta_title = data.meta_title ?? '';
        form.meta_description = data.meta_description ?? '';
        form.meta_keywords = data.meta_keywords ?? '';
        form.published_at = data.published_at
            ? new Date(data.published_at).toISOString().slice(0, 16)
            : '';
        form.category_id = data.category_id ?? null;
        form.tag_ids = (data.tags || []).map((t) => t.id);
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Статья не найдена';
    } finally {
        loading.value = false;
    }
}

async function save() {
    errorMessage.value = '';
    loading.value = true;
    try {
        const payload = {
            title: form.title,
            slug: form.slug || undefined,
            excerpt: form.excerpt || null,
            body: form.body,
            image: form.image || null,
            meta_title: form.meta_title || null,
            meta_description: form.meta_description || null,
            meta_keywords: form.meta_keywords || null,
            published_at: form.published_at || null,
            category_id: form.category_id || null,
            tag_ids: form.tag_ids.length ? form.tag_ids : null,
        };

        if (isEdit.value) {
            await api.put(`/admin/articles/${articleId.value}`, payload);
            router.push('/articles');
        } else {
            await api.post('/admin/articles', payload);
            router.push('/articles');
        }
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Ошибка сохранения';
        if (e.response?.data?.errors) {
            errorMessage.value += ' ' + Object.values(e.response.data.errors).flat().join(', ');
        }
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadCategories();
    loadTags();
    if (isEdit.value) loadArticle();
});
</script>

<style scoped>
.article-form {
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 32px;
}

/* Шапка: нормальные размеры, читаемая иерархия */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.page-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
    letter-spacing: -0.02em;
}

.back-link {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-text-muted);
    text-decoration: none;
    padding: 8px 14px;
    border-radius: var(--radius);
    border: 1px solid var(--color-border);
    background: var(--color-surface);
    transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}

.back-link:hover {
    color: var(--color-text);
    border-color: var(--color-text-muted);
    background: var(--color-bg);
}

.form-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 28px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Две колонки: 60% левая, 40% правая */
.form-grid {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 28px;
    align-items: stretch;
}

.col-meta {
    display: flex;
    flex-direction: column;
}

.col-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-height: 0;
}

.sidebar-block--image {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.sidebar-block--image :deep(.image-dropzone) {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.sidebar-block--image :deep(.drop-area) {
    flex: 1;
    min-height: 200px;
}

.sidebar-block--publish {
    flex-shrink: 0;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    font-family: inherit;
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    transition: border-color 0.15s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-text-muted);
}

.form-hint {
    font-size: 12px;
    color: var(--color-text-muted);
    margin-top: 4px;
    display: block;
}

/* Компактные textarea ровно на 2 строки */
.textarea-2rows {
    resize: none;
    line-height: 1.4;
    height: calc(2em * 1.4 + 2 * 10px);
    min-height: 0;
}

.category-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.category-chips .chip {
    padding: 8px 14px;
    font-size: 14px;
    font-family: inherit;
    font-weight: 500;
    color: var(--color-text-muted);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}

.category-chips .chip:hover {
    color: var(--color-text);
    border-color: var(--color-text-muted);
    background: var(--color-bg);
}

.category-chips .chip.chip--active {
    color: var(--color-text);
    background: var(--color-surface);
    border-color: var(--color-text);
    box-shadow: 0 0 0 1px var(--color-text);
}

.meta-group .form-control {
    margin-top: 6px;
}

.meta-group .form-control + .form-control {
    margin-top: 8px;
}

.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.tag-chip-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: var(--color-surface);
    overflow: hidden;
}
tag-chip-remove
.tag-chip-wrap--on {
    border-color: var(--color-accent);
}

.tag-chip-wrap--on .tag-chip-remove {
    color: #6b7280;
}

.tag-chip-wrap--on .tag-chip-remove:hover {
    color: #fff;
    background: rgba(0, 0, 0, 1);
}

.tag-chip-remove {
    padding: 6px 6px 6px 2px;
    font-size: 14px;
    line-height: 1;
    color: var(--color-text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease;
}

.tag-chip-remove:hover {
    color: var(--color-error);
    background: var(--color-error-bg);
}

.tag-chip {
    padding: 6px 10px 6px 12px;
    font-size: 13px;
    font-family: inherit;
    color: var(--color-text-muted);
    background: transparent;
    border: none;
    border-radius: 0;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease;
}

.tag-chip-wrap:not(:has(.tag-chip--on)) .tag-chip:hover {
    color: var(--color-text);
    background: var(--color-bg);
}

.tag-chip-wrap .tag-chip.tag-chip--on {
    color: #fff;
    background: var(--color-accent);
}

.tag-add {
    display: flex;
    gap: 8px;
}

.tag-add-input {
    flex: 1;
    max-width: 200px;
}

.tag-add-btn {
    padding: 8px 14px;
    font-size: 13px;
    font-family: inherit;
    font-weight: 500;
    color: var(--color-text);
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.tag-add-btn:hover:not(:disabled) {
    background: var(--color-surface);
    border-color: var(--color-text-muted);
}

.tag-add-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Текст статьи — на всю ширину внизу */
.body-section {
    padding-top: 14px;
    border-top: 1px solid var(--color-border);
}

.body-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: 10px;
}

.body-section :deep(.rich-text-editor) {
    width: 100%;
}

.body-section :deep(.editor-content) {
    min-height: 320px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    padding-top: 28px;
    border-top: 1px solid var(--color-border);
}

.form-actions .btn-secondary,
.form-actions .btn-primary {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    width: auto;
    min-width: 120px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.form-actions .btn-secondary {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.form-actions .btn-secondary:hover {
    background: var(--color-bg);
    border-color: var(--color-text-muted);
}

.form-actions .btn-primary {
    background: var(--color-accent);
    border: none;
    color: #fff;
}

.form-actions .btn-primary:hover:not(:disabled) {
    background: var(--color-accent-hover, #374151);
}

.form-actions .btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 900px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .sidebar-block--image {
        min-height: 240px;
    }

    .sidebar-block--image :deep(.drop-area) {
        min-height: 220px;
    }
}
</style>
