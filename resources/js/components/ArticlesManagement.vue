<template>
    <div class="articles-management">
        <header class="page-header">
            <h1 class="page-title">Статьи</h1>
            <router-link to="/articles/new" class="btn-create">Создать статью</router-link>
        </header>

        <div class="filters-bar">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Поиск по заголовку или анонсу..."
                class="filter-input"
                @input="debouncedLoad"
            />
            <select v-model="filters.category" @change="loadArticles" class="filter-select">
                <option value="">Все категории</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <select v-model="filters.published" @change="loadArticles" class="filter-select">
                <option value="">Все</option>
                <option value="1">Опубликованные</option>
                <option value="0">Черновики</option>
            </select>
        </div>

        <div class="table-card" v-if="!loading && articles.data?.length">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Заголовок</th>
                        <th>Категория</th>
                        <th>Анонс</th>
                        <th>Публикация</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="article in articles.data" :key="article.id">
                        <td class="cell-title">
                            <span class="title-text">{{ article.title }}</span>
                            <span v-if="article.slug" class="slug-text">{{ article.slug }}</span>
                        </td>
                        <td>
                            <span v-if="article.category" class="chip chip-category">{{ article.category.name }}</span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="cell-excerpt">{{ (article.excerpt || '').slice(0, 80) }}{{ (article.excerpt || '').length > 80 ? '…' : '' }}</td>
                        <td>
                            <span v-if="article.published_at" class="chip chip-success">
                                {{ formatDate(article.published_at) }}
                            </span>
                            <span v-else class="chip chip-draft">Черновик</span>
                        </td>
                        <td class="cell-actions">
                            <router-link
                                :to="`/article/${article.id}`"
                                target="_blank"
                                class="action-btn action-btn-view"
                                title="Открыть"
                            >
                                <span aria-hidden="true">👁</span>
                            </router-link>
                            <router-link
                                :to="`/articles/${article.id}/edit`"
                                class="action-btn action-btn-edit"
                                title="Редактировать"
                            >
                                <span aria-hidden="true">✏️</span>
                            </router-link>
                            <button
                                type="button"
                                @click="deleteArticle(article)"
                                class="action-btn action-btn-delete"
                                title="Удалить"
                            >
                                <span aria-hidden="true">🗑️</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else-if="loading" class="state-message state-loading">
            <span class="state-spinner"></span> Загрузка…
        </div>
        <div v-else-if="articles.data && !articles.data.length" class="state-message state-empty">
            Статей пока нет. <router-link to="/articles/new" class="state-link">Создать первую</router-link>
        </div>

        <nav v-if="articles.last_page > 1" class="pagination-bar" aria-label="Пагинация">
            <button
                type="button"
                @click="changePage(articles.current_page - 1)"
                :disabled="articles.current_page === 1"
                class="pagination-btn"
            >
                Назад
            </button>
            <span class="pagination-info">Страница {{ articles.current_page }} из {{ articles.last_page }}</span>
            <button
                type="button"
                @click="changePage(articles.current_page + 1)"
                :disabled="articles.current_page === articles.last_page"
                class="pagination-btn"
            >
                Вперед
            </button>
        </nav>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../services/api';

const articles = ref({ data: [], current_page: 1, last_page: 1 });
const categories = ref([]);
const loading = ref(false);
const filters = reactive({ search: '', published: '', category: '' });
let debounceTimer = null;

async function loadCategories() {
    try {
        const { data } = await api.get('/categories');
        categories.value = data ?? [];
    } catch (e) {
        categories.value = [];
    }
}

async function loadArticles(page = 1) {
    loading.value = true;
    try {
    const params = {
        page,
        per_page: 15,
        search: filters.search || undefined,
        published: filters.published || undefined,
        category: filters.category || undefined,
    };
    const { data } = await api.get('/admin/articles', { params });
    articles.value = data;
    } catch (e) {
        console.error('Ошибка загрузки статей', e);
        articles.value = { data: [], current_page: 1, last_page: 1 };
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadArticles(1), 300);
}

function changePage(page) {
    loadArticles(page);
}

async function deleteArticle(article) {
    if (!confirm(`Удалить статью «${article.title}»?`)) return;
    try {
        await api.delete(`/admin/articles/${article.id}`);
        loadArticles(articles.value.current_page);
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка удаления');
    }
}

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

onMounted(() => {
    loadCategories();
    loadArticles();
});
</script>

<style scoped>
.articles-management {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 32px;
    min-height: 60vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}

.page-title {
    font-size: 26px;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
    letter-spacing: -0.02em;
}

.btn-create {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    font-family: inherit;
    color: #fff;
    background: var(--color-accent);
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease, transform 0.1s ease;
}

.btn-create:hover {
    background: var(--color-accent-hover, #374151);
    transform: translateY(-1px);
}

.filters-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.filter-input {
    flex: 1;
    min-width: 220px;
    max-width: 400px;
    padding: 10px 14px;
    font-size: 14px;
    font-family: inherit;
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    transition: border-color 0.15s ease;
}

.filter-input:focus {
    outline: none;
    border-color: var(--color-text-muted);
}

.filter-input::placeholder {
    color: var(--color-text-muted);
    opacity: 0.8;
}

.filter-select {
    min-width: 160px;
    padding: 10px 14px;
    font-size: 14px;
    font-family: inherit;
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    cursor: pointer;
}

.table-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--color-bg);
    border-bottom: 1px solid var(--color-border);
}

.data-table th {
    padding: 14px 18px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.data-table td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--color-text);
    border-bottom: 1px solid var(--color-border);
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.data-table tbody tr:hover {
    background: rgba(0, 0, 0, 0.02);
}

.cell-title {
    vertical-align: top;
}

.title-text {
    display: block;
    font-weight: 600;
    color: var(--color-text);
}

.slug-text {
    display: block;
    font-size: 12px;
    color: var(--color-text-muted);
    margin-top: 4px;
}

.cell-excerpt {
    max-width: 300px;
    font-size: 13px;
    color: var(--color-text-muted);
    line-height: 1.45;
}

.chip {
    display: inline-block;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 6px;
}

.chip-success {
    background: #dcfce7;
    color: #166534;
}

.chip-draft {
    background: #f3f4f6;
    color: #6b7280;
}

.chip-category {
    background: #f3f4f6;
    color: var(--color-text);
    font-size: 13px;
}

.text-muted {
    color: var(--color-text-muted);
    font-size: 13px;
}

.cell-actions {
    white-space: nowrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin: 0 2px;
    padding: 0;
    font-size: 16px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.action-btn:hover {
    background: var(--color-bg);
    border-color: var(--color-text-muted);
}

.action-btn-view:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #3b82f6;
}

.action-btn-edit:hover {
    background: #fefce8;
    border-color: #ca8a04;
    color: #ca8a04;
}

.action-btn-delete:hover {
    background: #fef2f2;
    border-color: #dc2626;
    color: #dc2626;
}

.state-message {
    padding: 56px 24px;
    text-align: center;
    font-size: 15px;
    color: var(--color-text-muted);
}

.state-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.state-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--color-border);
    border-top-color: var(--color-accent);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.state-link {
    color: var(--color-accent);
    font-weight: 500;
    text-decoration: none;
}

.state-link:hover {
    text-decoration: underline;
}

.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
}

.pagination-info {
    font-size: 14px;
    color: var(--color-text-muted);
}

.pagination-btn {
    padding: 8px 16px;
    font-size: 14px;
    font-family: inherit;
    font-weight: 500;
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--color-bg);
    border-color: var(--color-text-muted);
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
</style>
