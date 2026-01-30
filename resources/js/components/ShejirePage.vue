<template>
    <div class="shejire-page">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title">Шежіре</h1>
                <p class="page-subtitle">Генеалогическое древо и родословная</p>
            </header>

            <div class="shejire-actions">
                <router-link v-if="isAuthenticated" to="/shejire/new" class="btn btn-primary">
                    + Добавить родословную
                </router-link>
                <router-link v-if="user?.is_moderator" to="/shejire-moderation" class="btn btn-secondary">
                    Модерация
                </router-link>
            </div>

            <div v-if="loading" class="loading">Загрузка…</div>
            <div v-else-if="!trees.length" class="empty-state">
                <img src="/storage/img/tree-diagram-svgrepo-com.svg" alt="" class="empty-icon" />
                <p>Пока нет родословных</p>
                <p v-if="isAuthenticated" class="hint">Добавьте свою родословную — модератор проверит и опубликует</p>
            </div>
            <div v-else class="trees-grid">
                <div
                    v-for="tree in trees"
                    :key="tree.id"
                    class="tree-card"
                    :class="{ 'tree-card--pending': tree.status === 'pending', 'tree-card--rejected': tree.status === 'rejected' }"
                >
                    <router-link :to="`/shejire/${tree.id}`" class="tree-card-link">
                        <div class="tree-card-title">{{ tree.title || 'Родословная' }}</div>
                        <div class="tree-card-meta">
                            <span v-if="tree.user">{{ tree.user.name }}</span>
                            <span v-if="tree.status === 'pending'" class="badge badge--pending">На модерации</span>
                            <span v-else-if="tree.status === 'approved'" class="badge badge--approved">Опубликован</span>
                            <span v-else-if="tree.status === 'rejected'" class="badge badge--rejected">Отклонено</span>
                        </div>
                        <div class="tree-card-nodes">{{ tree.nodes?.length || 0 }} персон</div>
                    </router-link>
                    <div v-if="isAuthenticated && tree.user_id === user?.id && (tree.status === 'pending' || tree.status === 'approved')" class="tree-card-actions">
                        <router-link :to="`/shejire/${tree.id}/edit`" class="btn btn-sm">Редактировать</router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';

const { user, isAuthenticated } = useAuth();
const trees = ref([]);
const loading = ref(true);

async function loadTrees() {
    loading.value = true;
    try {
        const { data } = await api.get('/shejire');
        const list = Array.isArray(data) ? data : [];
        list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        trees.value = list;
    } catch (e) {
        trees.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(loadTrees);
</script>

<style scoped>
.shejire-page {
    padding: 28px 0 40px;
    min-height: 60vh;
}

.page-header {
    margin-bottom: 24px;
    text-align: center;
}

.page-title {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--color-text);
}

.page-subtitle {
    margin: 8px 0 0;
    color: var(--color-text-muted);
    font-size: 16px;
}

.shejire-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    border: none;
    transition: background 0.15s ease;
}

.btn-primary {
    background: var(--color-accent);
    color: #fff;
}

.btn-primary:hover {
    background: var(--color-accent-hover, #374151);
}

.btn-secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.btn-secondary:hover {
    background: var(--color-bg);
    border-color: var(--color-text-muted);
    color: #374151;
}

.loading {
    text-align: center;
    padding: 40px;
    color: var(--color-text-muted);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--color-surface);
    border: 1px dashed var(--color-border);
    border-radius: 12px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    opacity: 0.5;
    margin-bottom: 16px;
}

.empty-state p {
    margin: 0 0 8px;
    color: var(--color-text-muted);
}

.empty-state .hint {
    font-size: 14px;
}

.trees-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.tree-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    position: relative;
}

.tree-card:hover {
    border-color: var(--color-text-muted);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.tree-card--pending {
    border-color: #f59e0b;
}

.tree-card--rejected {
    border-color: #ef4444;
    opacity: 0.8;
}

.tree-card-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
}

.tree-card-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-text);
}

.tree-card-meta {
    font-size: 13px;
    color: var(--color-text-muted);
    margin-bottom: 8px;
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 12px;
    border-radius: 6px;
    margin-left: 8px;
    position: absolute;
    top: -6%;
    left: 0;
}

.badge--pending {
    background: #fef3c7;
    color: #92400e;
}

.badge--approved {
    background: #d1fae5;
    color: #065f46;
}

.badge--rejected {
    background: #fee2e2;
    color: #991b1b;
}

.tree-card-nodes {
    font-size: 13px;
    color: var(--color-text-muted);
}

.tree-card-actions {
    padding: 12px 20px;
    border-top: 1px solid var(--color-border);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}
</style>
