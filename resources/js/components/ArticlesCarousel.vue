<template>
    <div class="articles-carousel" ref="carouselRef">
        <div v-if="loading" class="carousel-loading">Загрузка статей…</div>
        <div v-else-if="!articles.length" class="carousel-empty">Пока нет опубликованных статей.</div>
        <template v-else>
        <div class="carousel-wrapper">
            <div 
                class="carousel-container"
                :style="{ transform: `translateX(-${currentIndex * (itemWidth + gap)}px)` }"
            >
                <div
                    v-for="article in articles"
                    :key="article.id"
                    class="carousel-item"
                    :style="{ width: `${itemWidth}px`, marginRight: `${gap}px` }"
                >
                    <router-link :to="`/article/${article.id}`" class="article-card-link">
                        <div class="article-card">
                            <div class="article-image">
                                <img
                                    v-if="article.image"
                                    :src="article.image"
                                    :alt="article.title"
                                    class="article-image-img"
                                />
                                <div
                                    v-else
                                    class="article-image-placeholder"
                                >
                                    {{ article.title }}
                                </div>
                            </div>
                            <div class="article-content">
                                <div v-if="article.category || article.author" class="article-meta">
                                    <span v-if="article.category" class="article-category">{{ article.category.name }}</span>
                                    <span v-if="article.author" class="article-author">
                                        <img
                                            v-if="article.author.avatar"
                                            :src="article.author.avatar"
                                            :alt="article.author.name"
                                            class="author-avatar"
                                        />
                                        <span class="author-name">{{ article.author.name }}</span>
                                    </span>
                                </div>
                                <h3 class="article-title">{{ article.title }}</h3>
                                <p class="article-excerpt">{{ article.excerpt || '' }}</p>
                                <span class="article-date">{{ formatDate(article.published_at) }}</span>
                                <div v-if="article.tags?.length" class="article-tags">
                                    <span v-for="t in article.tags" :key="t.id" class="tag-pill">{{ t.name }}</span>
                                </div>
                            </div>
                        </div>
                    </router-link>
                </div>
            </div>
        </div>
        
        <!-- Навигация -->
        <div v-if="articles.length" class="carousel-nav">
            <button 
                class="nav-button prev"
                @click="prevSlide"
                :disabled="!canGoPrev"
                aria-label="Предыдущий слайд"
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button 
                class="nav-button next"
                @click="nextSlide"
                :disabled="!canGoNext"
                aria-label="Следующий слайд"
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        
        <!-- Точки индикации -->
        <div v-if="articles.length" class="carousel-dots">
            <button
                v-for="(dot, index) in dots"
                :key="index"
                class="dot"
                :class="{ active: index === currentDotIndex }"
                @click="goToSlide(index * itemsPerView)"
                :aria-label="`Перейти к слайду ${index + 1}`"
            ></button>
        </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import api from '../services/api';

const props = defineProps({
    tagSlug: { type: String, default: '' },
    categorySlug: { type: String, default: '' },
});

const articles = ref([]);
const loading = ref(true);

async function loadArticles() {
    loading.value = true;
    try {
        const params = {};
        if (props.tagSlug) params.tag = props.tagSlug;
        if (props.categorySlug) params.category = props.categorySlug;
        const { data } = await api.get('/articles', { params });
        const list = data && (Array.isArray(data) ? data : (data.data || []));
        articles.value = Array.isArray(list) ? list : [];
    } catch (e) {
        articles.value = [];
    } finally {
        loading.value = false;
    }
}

const carouselRef = ref(null);
const currentIndex = ref(0);
const gap = ref(10);
const containerWidth = ref(1200);
const itemsPerView = ref(3);
let autoplayInterval = null;

// Вычисляем ширину одного элемента
const itemWidth = computed(() => {
    if (containerWidth.value <= 0 || itemsPerView.value <= 0) {
        return 300; // Fallback ширина
    }
    return (containerWidth.value - (itemsPerView.value - 1) * gap.value) / itemsPerView.value;
});

// Максимальный индекс для прокрутки
const maxIndex = computed(() => {
    return Math.max(0, articles.value.length - itemsPerView.value);
});

// Можем ли идти вперед
const canGoNext = computed(() => {
    return currentIndex.value < maxIndex.value;
});

// Можем ли идти назад
const canGoPrev = computed(() => {
    return currentIndex.value > 0;
});

// Точки индикации
const dots = computed(() => {
    const totalDots = Math.ceil(articles.value.length / itemsPerView.value);
    return Array.from({ length: totalDots }, (_, i) => i);
});

// Текущий индекс точки
const currentDotIndex = computed(() => {
    return Math.floor(currentIndex.value / itemsPerView.value);
});

// Определяем количество элементов в зависимости от ширины экрана
const updateItemsPerView = () => {
    const width = window.innerWidth;
    if (width >= 1000) {
        itemsPerView.value = 3;
    } else if (width >= 600) {
        itemsPerView.value = 2;
    } else {
        itemsPerView.value = 1;
    }
    
    // Обновляем ширину контейнера с небольшой задержкой для корректного измерения
    setTimeout(() => {
        if (carouselRef.value) {
            const rect = carouselRef.value.getBoundingClientRect();
            containerWidth.value = rect.width || 1200;
        } else {
            // Fallback: используем ширину окна минус отступы
            containerWidth.value = Math.min(width - 40, 1200);
        }
        
        // Корректируем текущий индекс, если он выходит за границы
        const newMaxIndex = Math.max(0, articles.value.length - itemsPerView.value);
        if (currentIndex.value > newMaxIndex) {
            currentIndex.value = Math.max(0, newMaxIndex);
        }
    }, 100);
};

const prevSlide = () => {
    if (currentIndex.value > 0) {
        currentIndex.value = Math.max(0, currentIndex.value - itemsPerView.value);
    }
};

const nextSlide = () => {
    if (currentIndex.value < maxIndex.value) {
        currentIndex.value = Math.min(maxIndex.value, currentIndex.value + itemsPerView.value);
    }
};

const goToSlide = (index) => {
    currentIndex.value = Math.max(0, Math.min(maxIndex.value, index));
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Автопрокрутка
const startAutoplay = () => {
    stopAutoplay();
    autoplayInterval = setInterval(() => {
        if (currentIndex.value >= maxIndex.value) {
            currentIndex.value = 0;
        } else {
            nextSlide();
        }
    }, 5000);
};

const stopAutoplay = () => {
    if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
    }
};

onMounted(() => {
    loadArticles();
    setTimeout(() => {
        updateItemsPerView();
    }, 100);
    window.addEventListener('resize', updateItemsPerView);
    startAutoplay();
});

watch(() => [props.tagSlug, props.categorySlug], () => {
    loadArticles();
});

onUnmounted(() => {
    window.removeEventListener('resize', updateItemsPerView);
    stopAutoplay();
});
</script>

<style scoped>
.articles-carousel {
    position: relative;
    width: 100%;
    padding: 0 10px;
    overflow: hidden;
}

.carousel-wrapper {
    width: 100%;
    overflow: hidden;
    position: relative;
    margin: 0 auto;
}

.carousel-container {
    display: flex;
    transition: transform 0.4s ease-in-out;
    will-change: transform;
}

.carousel-item {
    flex-shrink: 0;
}

.article-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform var(--transition), box-shadow var(--transition), border-radius var(--transition);
}

.article-card:hover {
    transform: translateY(0px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-radius: var(--radius) !important;
}

.article-image {
    width: 100%;
    height: 300px;
    overflow: hidden;
    background: var(--color-border);
}

.article-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
}

.article-image-img {
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
    color: white;
    font-size: 18px;
    padding: 20px;
    text-align: center;
}

.carousel-loading,
.carousel-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--color-text-muted);
}

.article-content {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    font-size: 12px;
    color: var(--color-text-muted);
    margin-bottom: 8px;
}

.article-meta .article-category {
    font-weight: 500;
    color: var(--color-text);
}

.article-author {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.article-meta .article-author::before {
    content: none;
}

.article-meta .article-category + .article-author::before {
    content: '· ';
}

.author-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.author-name {
    font-weight: 500;
}

.article-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 12px;
    color: var(--color-text);
}

.article-excerpt {
    font-size: 15px;
    color: var(--color-text-muted);
    line-height: 1.6;
    margin-bottom: 16px;
    flex: 1;
}

.article-date {
    font-size: 13px;
    color: var(--color-text-muted);
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

/* Навигация */
.carousel-nav {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.nav-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--color-border);
    background: var(--color-surface);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    color: var(--color-text);
    padding: 0;
}

.nav-button:hover:not(:disabled) {
    background: var(--color-accent);
    color: white;
    border-color: var(--color-accent);
}

.nav-button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* Точки индикации */
.carousel-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: none;
    background: var(--color-border);
    cursor: pointer;
    transition: background 0.15s ease;
    padding: 0;
}

.dot.active {
    background: var(--color-accent);
}

.dot:hover {
    background: var(--color-accent);
    opacity: 0.7;
}

@media (max-width: 768px) {
    .article-image {
        height: 200px;
    }
    
    .article-content {
        padding: 16px;
    }
    
    .article-title {
        font-size: 18px;
    }
}
</style>