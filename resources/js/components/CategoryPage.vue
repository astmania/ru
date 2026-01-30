<template>
    <div class="category-page">
        <div class="container">
            <header class="category-header">
                <h1 class="category-title">{{ categoryName }}</h1>
                <p v-if="categoryDescription" class="category-subtitle">{{ categoryDescription }}</p>
            </header>

            <!-- Поиск, теги, переключатель вида -->
            <div class="toolbar">
                <div class="toolbar-search">
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="form-control search-input"
                        placeholder="Поиск по статьям в разделе..."
                        @input="onSearchInput"
                    />
                </div>
                <div class="toolbar-tags" v-if="tags.length">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        class="tag-chip"
                        :class="{ 'tag-chip--active': selectedTagSlug === tag.slug }"
                        @click="toggleTag(tag.slug)"
                    >
                        {{ tag.name }}
                    </button>
                </div>
                <div class="toolbar-view">
                    <button
                        type="button"
                        class="view-btn"
                        :class="{ active: viewMode === 'grid' }"
                        @click="viewMode = 'grid'"
                        title="Сетка"
                        aria-label="Вид сеткой"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="view-btn"
                        :class="{ active: viewMode === 'list' }"
                        @click="viewMode = 'list'"
                        title="Список"
                        aria-label="Вид списком"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div v-if="loading" class="loading">Загрузка статей…</div>
            <div v-else-if="!articles.length" class="empty-state">В этом разделе пока нет статей</div>
            <template v-else>
                <div :class="['articles-wrap', viewMode === 'grid' ? 'articles-grid' : 'articles-list']">
                    <article
                        v-for="article in articles"
                        :key="article.id"
                        class="article-item"
                        :class="{ 'article-item--list': viewMode === 'list' }"
                    >
                        <router-link :to="`/article/${article.id}`" class="article-link">
                            <div v-if="viewMode === 'grid'" class="article-image-wrap">
                                <img
                                    v-if="article.image"
                                    :src="article.image"
                                    :alt="article.title"
                                    class="article-image"
                                />
                                <div v-else class="article-image-placeholder">{{ article.title }}</div>
                            </div>
                            <div v-else class="article-image-wrap article-image-wrap--thumb">
                                <img
                                    v-if="article.image"
                                    :src="article.image"
                                    :alt="article.title"
                                    class="article-image"
                                />
                                <div v-else class="article-image-placeholder article-image-placeholder--thumb">?</div>
                            </div>
                            <div class="article-body">
                                <h3 class="article-title">{{ article.title }}</h3>
                                <p class="article-excerpt">{{ article.excerpt || '' }}</p>
                                <div class="article-meta">
                                    <span v-if="article.author" class="article-author">
                                        <img
                                            v-if="article.author.avatar"
                                            :src="article.author.avatar"
                                            :alt="article.author.name"
                                            class="author-avatar"
                                        />
                                        <span>{{ article.author.name }}</span>
                                    </span>
                                    <span class="article-date">{{ formatDate(article.published_at) }}</span>
                                </div>
                                <div v-if="article.tags?.length" class="article-tags">
                                    <span v-for="t in article.tags" :key="t.id" class="tag-pill">{{ t.name }}</span>
                                </div>
                            </div>
                        </router-link>
                    </article>
                </div>

                <!-- Пагинация -->
                <div v-if="pagination.last_page > 1" class="pagination">
                    <button
                        type="button"
                        class="pagination-btn"
                        :disabled="pagination.current_page <= 1"
                        @click="goToPage(pagination.current_page - 1)"
                        aria-label="Предыдущая страница"
                    >
                        ‹
                    </button>
                    <span class="pagination-info">
                        {{ pagination.current_page }} из {{ pagination.last_page }}
                    </span>
                    <button
                        type="button"
                        class="pagination-btn"
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="goToPage(pagination.current_page + 1)"
                        aria-label="Следующая страница"
                    >
                        ›
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const slug = computed(() => String(route.params.slug || ''));

const categories = ref([]);
const tags = ref([]);
const articles = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const searchDebounce = ref(null);
const selectedTagSlug = ref('');
const viewMode = ref('grid'); // 'grid' | 'list'
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0,
});

const categoryName = computed(() => {
    const found = categories.value.find((c) => c.slug === slug.value);
    return found?.name || 'Раздел';
});
const categoryDescription = computed(() => {
    const found = categories.value.find((c) => c.slug === slug.value);
    return found?.description || '';
});

async function loadCategories() {
    try {
        const { data } = await api.get('/categories');
        categories.value = Array.isArray(data) ? data : [];
    } catch (e) {
        categories.value = [];
    }
}

async function loadTags() {
    try {
        const { data } = await api.get('/tags');
        tags.value = Array.isArray(data) ? data : [];
    } catch (e) {
        tags.value = [];
    }
}

async function loadArticles() {
    if (!slug.value) return;
    loading.value = true;
    try {
        const params = {
            category: slug.value,
            per_page: pagination.value.per_page,
            page: pagination.value.current_page,
        };
        if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
        if (selectedTagSlug.value) params.tag = selectedTagSlug.value;

        const { data } = await api.get('/articles', { params });

        const list = data?.data ?? data;
        articles.value = Array.isArray(list) ? list : [];

        if (data && typeof data.current_page === 'number') {
            pagination.value = {
                current_page: data.current_page,
                last_page: data.last_page ?? 1,
                per_page: data.per_page ?? 12,
                total: data.total ?? 0,
            };
        }
    } catch (e) {
        articles.value = [];
    } finally {
        loading.value = false;
    }
}

function onSearchInput() {
    if (searchDebounce.value) clearTimeout(searchDebounce.value);
    searchDebounce.value = setTimeout(() => {
        pagination.value.current_page = 1;
        loadArticles();
    }, 300);
}

function toggleTag(tagSlug) {
    selectedTagSlug.value = selectedTagSlug.value === tagSlug ? '' : tagSlug;
    pagination.value.current_page = 1;
    loadArticles();
}

function goToPage(page) {
    if (page < 1 || page > pagination.value.last_page) return;
    pagination.value.current_page = page;
    loadArticles();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

onMounted(() => {
    loadCategories();
    loadTags();
    loadArticles();
});

watch(slug, () => {
    selectedTagSlug.value = '';
    searchQuery.value = '';
    pagination.value.current_page = 1;
    loadCategories();
    loadTags();
    loadArticles();
});
</script>

<style scoped>
.category-page {
    padding: 28px 0 40px;
}

.category-header {
    margin-bottom: 24px;
}

.category-title {
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 700;
    color: var(--color-text);
}

.category-subtitle {
    margin: 0;
    font-size: 15px;
    color: var(--color-text-muted);
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    margin-bottom: 24px;
    padding: 16px 0;
    border-bottom: 1px solid var(--color-border);
}

.toolbar-search {
    flex: 1;
    min-width: 200px;
}

.search-input {
    width: 100%;
    max-width: 320px;
    padding: 10px 14px;
    font-size: 15px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.toolbar-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-chip {
    padding: 6px 12px;
    font-size: 13px;
    border: 1px solid var(--color-border);
    border-radius: 20px;
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}

.tag-chip:hover {
    background: var(--color-bg);
    border-color: var(--color-text-muted);
}

.tag-chip--active {
    background: var(--color-accent);
    border-color: var(--color-accent);
    color: #fff;
}

.toolbar-view {
    display: flex;
    gap: 4px;
}

.view-btn {
    padding: 8px 10px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-surface);
    color: var(--color-text-muted);
    cursor: pointer;
    transition: all 0.15s;
}

.view-btn:hover {
    color: var(--color-text);
    border-color: var(--color-text-muted);
}

.view-btn.active {
    background: var(--color-accent);
    border-color: var(--color-accent);
    color: #fff;
}

.loading,
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--color-text-muted);
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.articles-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.article-item {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.15s, border-color 0.15s;
}

.article-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: var(--color-text-muted);
}

.article-item--list .article-link {
    display: flex;
    flex-direction: row;
    align-items: stretch;
}

.article-item--list .article-image-wrap--thumb {
    width: 180px;
    min-width: 180px;
    height: 120px;
}

.article-item--list .article-body {
    flex: 1;
    padding: 20px 24px;
}

.article-link {
    display: block;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.article-image-wrap {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: var(--color-border);
}

.article-image-wrap--thumb {
    flex-shrink: 0;
    height: 100%;
    min-height: 140px;
}

.article-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.article-image-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    padding: 16px;
    text-align: center;
}

.article-image-placeholder--thumb {
    font-size: 24px;
}

.article-body {
    padding: 20px;
}

.article-title {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
    line-height: 1.3;
}

.article-excerpt {
    margin: 0 0 12px;
    font-size: 14px;
    color: var(--color-text-muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.article-item--list .article-excerpt {
    -webkit-line-clamp: 1;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    font-size: 13px;
    color: var(--color-text-muted);
}

.article-author {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.author-avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.tag-pill {
    font-size: 11px;
    padding: 3px 8px;
    background: var(--color-bg);
    color: var(--color-text-muted);
    border-radius: 6px;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
}

.pagination-btn {
    padding: 8px 16px;
    font-size: 16px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.15s;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--color-accent);
    border-color: var(--color-accent);
    color: #fff;
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination-info {
    font-size: 14px;
    color: var(--color-text-muted);
}

@media (max-width: 768px) {
    .article-item--list .article-link {
        flex-direction: column;
    }

    .article-item--list .article-image-wrap--thumb {
        width: 100%;
        min-width: 100%;
        height: 160px;
    }
}
</style>
