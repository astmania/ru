<template>
    <div class="main-page">
        <!-- Лозунг -->
        <!-- <section class="hero-section">
            <div class="container">
                <h1 class="hero-title">{{ slogan }}</h1>
                <p class="hero-subtitle">{{ subtitle }}</p>
            </div>
        </section> -->

        <!-- Разделы статей теперь в header -->

        <!-- Баннер -->
        <section class="banner-section">
            <div class="container">
                <div class="banner">
                    <div class="banner-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                        Баннер изображение
                    </div>
                </div>
            </div>
        </section>

        <!-- Книги (сетка) -->
        <section class="books-section">
            <div class="container">
                <h2 class="section-title">Книги</h2>
                <BooksGrid />
            </div>
        </section>

        <!-- Статьи (карусель) -->
        <section class="articles-section">
            <div class="container">
                <h2 class="section-title">Статьи</h2>
                <div v-if="tags.length" class="quick-search">
                    <span class="quick-search-label">Быстрый поиск:</span>
                    <div class="quick-search-tags">
                        <button
                            type="button"
                            class="quick-tag"
                            :class="{ active: !activeTagSlug }"
                            @click="activeTagSlug = ''"
                        >
                            Все
                        </button>
                        <button
                            v-for="tag in tags"
                            :key="tag.id"
                            type="button"
                            class="quick-tag"
                            :class="{ active: activeTagSlug === tag.slug }"
                            @click="activeTagSlug = activeTagSlug === tag.slug ? '' : tag.slug"
                        >
                            {{ tag.name }}
                        </button>
                    </div>
                </div>
                <ArticlesCarousel :tag-slug="activeTagSlug" />
            </div>
        </section>

        <!-- Обратная связь -->
        <section class="contact-section">
            <div class="container">
                <ContactForm />
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '../services/api';
import BooksGrid from './BooksGrid.vue';
import ArticlesCarousel from './ArticlesCarousel.vue';
import ContactForm from './ContactForm.vue';

const slogan = ref('Қазақ халқының мәдениеті мен тарихы');
const subtitle = ref('Біздің порталда қазақ халқының бай мұрасын зерттеңіз');

const tags = ref([]);
const activeTagSlug = ref('');

async function loadTags() {
    try {
        const { data } = await api.get('/tags');
        tags.value = Array.isArray(data) ? data : [];
    } catch (e) {
        tags.value = [];
    }
}

onMounted(() => {
    loadTags();
});

onUnmounted(() => {
});
</script>

<style scoped>
.main-page {
    min-height: 100vh;
    padding-top: 0;
}

.quick-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.quick-search-label {
    font-size: 14px;
    color: var(--color-text-muted);
    font-weight: 500;
}

.quick-search-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.quick-tag {
    padding: 6px 12px;
    font-size: 13px;
    font-family: inherit;
    color: var(--color-text-muted);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}

.quick-tag:hover {
    color: var(--color-text);
    border-color: var(--color-text-muted);
    background: var(--color-bg);
}

.quick-tag.active {
    color: var(--color-text);
    background: var(--color-accent);
    border-color: var(--color-accent);
    color: #fff;
}
</style>
