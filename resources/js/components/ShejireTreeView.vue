<template>
    <div class="shejire-tree-view">
        <div class="container">
            <header class="page-header">
                <router-link to="/shejire" class="back-link">← К списку</router-link>
                <p v-if="tree?.user" class="page-meta">Автор: {{ tree.user.name }}</p>
                <h1 class="page-title">{{ tree?.title || 'Родословная' }}</h1>
                <div v-if="isAuthenticated && tree?.user_id === user?.id && (tree?.status === 'pending' || tree?.status === 'approved')" class="page-actions">
                    <router-link :to="`/shejire/${tree.id}/edit`" class="btn btn-primary">
                        {{ tree?.status === 'approved' ? 'Редактировать (повторная модерация)' : 'Редактировать' }}
                    </router-link>
                </div>
            </header>

            <div v-if="loading" class="loading">Загрузка…</div>
            <div v-else-if="!tree" class="error">Родословная не найдена</div>
            <div v-else-if="tree.status === 'rejected'" class="rejected-message">
                <p>Родословная отклонена модератором</p>
                <p v-if="tree.rejected_reason" class="reason">{{ tree.rejected_reason }}</p>
            </div>
            <div v-else-if="tree.status === 'pending'" class="pending-message">
                <p>Родословная на модерации. После одобрения она будет опубликована.</p>
            </div>
            <div v-else class="diagram-wrapper">
                <ShejireDiagram :nodes="tree.nodes" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';
import ShejireDiagram from './ShejireDiagram.vue';
import { useAuth } from '../composables/useAuth';

const route = useRoute();
const { user, isAuthenticated } = useAuth();
const tree = ref(null);
const loading = ref(true);

const treeId = computed(() => route.params.id);

async function loadTree() {
    if (!treeId.value) return;
    loading.value = true;
    try {
        const { data } = await api.get(`/shejire/${treeId.value}`);
        tree.value = data;
    } catch (e) {
        tree.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(loadTree);
</script>

<style scoped>
.shejire-tree-view {
    padding: 28px 0 40px;
}

.page-header {
    margin-bottom: 28px;
}

.back-link {
    display: inline-block;
    font-size: 14px;
    color: var(--color-text-muted);
    text-decoration: none;
    margin-bottom: 12px;
}

.back-link:hover {
    color: var(--color-text);
}

.page-title {
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 700;
    color: var(--color-text);
    text-align: center;
}

.page-meta {
    margin: 0 0 12px;
    font-size: 14px;
    color: var(--color-text-muted);
    text-align: center;
}

.page-actions {
    margin-top: 12px;
}

.btn {
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--color-accent);
    color: #fff;
}

.loading,
.error {
    text-align: center;
    padding: 40px;
    color: var(--color-text-muted);
}

.rejected-message,
.pending-message {
    padding: 32px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    text-align: center;
}

.rejected-message {
    border-color: #ef4444;
}

.pending-message {
    border-color: #f59e0b;
}

.reason {
    margin-top: 12px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.diagram-wrapper {
    margin-top: 24px;
    padding: 32px 24px;
    background: #f3f4f6;
    border-radius: 12px;
    overflow: auto;
    max-height: 500px;
}
</style>
