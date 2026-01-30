<template>
    <div class="article-view">
        <div v-if="loading" class="loading">Загрузка…</div>
        <div v-else-if="error" class="error">{{ error }}</div>
        <article v-else-if="article" class="article-body">
            <header class="article-header">
                <h1>{{ article.title }}</h1>
                <div class="meta">
                    <span v-if="article.category" class="meta-category">{{ article.category.name }}</span>
                    <span v-if="article.author" class="meta-author">
                        <img
                            v-if="article.author.avatar"
                            :src="article.author.avatar"
                            :alt="article.author.name"
                            class="author-avatar"
                        />
                        <span>{{ article.author.name }}</span>
                    </span>
                    <span v-if="article.published_at">{{ formatDate(article.published_at) }}</span>
                </div>
                <div v-if="article.tags?.length" class="article-tags">
                    <span v-for="t in article.tags" :key="t.id" class="tag-pill">{{ t.name }}</span>
                </div>
                <img
                    v-if="article.image"
                    :src="article.image"
                    :alt="article.title"
                    class="article-image"
                />
            </header>
            <div class="content" v-html="article.body"></div>
            <div class="back">
                <router-link to="/">← На главную</router-link>
            </div>
        </article>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const idOrSlug = computed(() => route.params.idOrSlug);
const article = ref(null);
const loading = ref(true);
const error = ref('');

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get(`/articles/${idOrSlug.value}`);
        article.value = data;
    } catch (e) {
        error.value = e.response?.status === 404 ? 'Статья не найдена' : 'Ошибка загрузки';
    } finally {
        loading.value = false;
    }
}

function formatDate(str) {
    if (!str) return '';
    return new Date(str).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function setPageMeta() {
    const a = article.value;
    if (!a) return;
    document.title = a.meta_title || a.title;
    let desc = document.querySelector('meta[name="description"]');
    if (a.meta_description) {
        if (!desc) {
            desc = document.createElement('meta');
            desc.name = 'description';
            document.head.appendChild(desc);
        }
        desc.content = a.meta_description;
    }
}

watch(article, setPageMeta, { immediate: true });

onMounted(load);
</script>

<style scoped>
.article-view {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 32px;
}

.loading,
.error {
    text-align: center;
    padding: 48px;
    color: var(--color-text-muted);
}

.error {
    color: var(--color-error);
}

.article-header {
    margin-bottom: 32px;
}

.article-header h1 {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 12px;
    line-height: 1.3;
}

.meta {
    font-size: 14px;
    color: var(--color-text-muted);
    margin-bottom: 16px;
}

.meta span + span::before {
    content: ' · ';
}

.meta-category {
    font-weight: 500;
    color: var(--color-text);
}

.meta-author {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.author-avatar {
    width: 32px;
    height: 32px;
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
    font-size: 12px;
    padding: 4px 10px;
    background: var(--color-bg);
    color: var(--color-text-muted);
    border-radius: 6px;
}

.article-image {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: var(--radius);
}

.content {
    line-height: 1.7;
}

.content :deep(h2) {
    font-size: 1.4em;
    margin: 1.5em 0 0.5em;
}

.content :deep(h3) {
    font-size: 1.2em;
    margin: 1.2em 0 0.4em;
}

.content :deep(p) {
    margin-bottom: 1em;
}

.content :deep(ul),
.content :deep(ol) {
    margin: 0.5em 0 1em;
    padding-left: 1.5em;
}

.content :deep(blockquote) {
    border-left: 3px solid var(--color-border);
    padding-left: 1em;
    margin: 1em 0;
    color: var(--color-text-muted);
}

.content :deep(a) {
    color: var(--color-accent);
    text-decoration: underline;
}

.back {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
}

.back a {
    color: var(--color-text-muted);
    font-size: 14px;
}

.back a:hover {
    color: var(--color-text);
}
</style>
