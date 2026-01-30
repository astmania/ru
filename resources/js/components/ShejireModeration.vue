<template>
    <div class="shejire-moderation">
        <div class="container">
            <header class="page-header">
                <router-link to="/shejire" class="back-link">← К Шежіре</router-link>
                <h1 class="page-title">Модерация родословных</h1>
                <p class="page-subtitle">
                    <span class="badge badge--count">{{ trees.length }}</span>
                    {{ trees.length === 1 ? 'ожидает одобрения' : 'ожидают одобрения' }}
                </p>
            </header>

            <div v-if="loading" class="loading">Загрузка…</div>
            <div v-else-if="!trees.length" class="empty-state">
                <div class="empty-icon">✓</div>
                <p>Нет родословных на модерации</p>
            </div>
            <div v-else class="trees-list">
                <article v-for="tree in trees" :key="tree.id" class="moderation-card">
                    <header class="card-header">
                        <h2 class="card-title">{{ tree.title || 'Родословная' }}</h2>
                        <div class="card-meta">
                            <span class="meta-author">
                                <span class="meta-label">Автор:</span>
                                {{ tree.user?.name }}
                                <span class="meta-email">({{ tree.user?.email }})</span>
                            </span>
                            <span class="meta-count">{{ tree.nodes?.length || 0 }} персон</span>
                        </div>
                    </header>

                    <div class="card-content">
                        <!-- Визуальная диаграмма -->
                        <section class="diagram-section">
                            <h3 class="section-title">Структура родословной</h3>
                            <div class="diagram-wrapper">
                                <ShejireDiagram :nodes="tree.nodes || []" />
                            </div>
                        </section>

                        <!-- Список персон с иерархией -->
                        <section class="nodes-section">
                            <h3 class="section-title">Список персон</h3>
                            <div class="nodes-tree">
                                <ModerationNodeItem
                                    v-for="item in nodesWithDepth(tree.nodes)"
                                    :key="item.node.id"
                                    :node="item.node"
                                    :depth="item.depth"
                                    :format-date="formatDate"
                                />
                            </div>
                        </section>
                    </div>

                    <footer class="card-actions">
                        <button
                            type="button"
                            class="btn btn-success"
                            :disabled="processing === tree.id"
                            @click="approve(tree)"
                        >
                            Одобрить
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="processing === tree.id"
                            @click="openReject(tree)"
                        >
                            Отклонить
                        </button>
                    </footer>
                </article>
            </div>

            <!-- Модальное окно отклонения -->
            <div v-if="rejectingTree" class="modal-overlay" @click.self="rejectingTree = null">
                <div class="modal">
                    <h3 class="modal-title">Отклонить родословную</h3>
                    <div class="form-group">
                        <label>Причина (необязательно)</label>
                        <textarea
                            v-model="rejectReason"
                            class="form-control"
                            rows="3"
                            placeholder="Укажите причину отклонения для автора"
                            maxlength="500"
                        ></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="rejectingTree = null">Отмена</button>
                        <button type="button" class="btn btn-danger" @click="reject">Отклонить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import ShejireDiagram from './ShejireDiagram.vue';
import ModerationNodeItem from './ShejireModerationNodeItem.vue';

const trees = ref([]);
const loading = ref(true);
const processing = ref(null);
const rejectingTree = ref(null);
const rejectReason = ref('');

function formatDate(val) {
    if (!val) return '—';
    const d = new Date(val);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).padStart(4, '0');
    return `${day}.${month}.${year}`;
}

function rootNodes(nodes) {
    if (!Array.isArray(nodes)) return [];
    return nodes.filter((n) => !n.parent_id).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
}

function nodesWithDepth(nodes) {
    if (!Array.isArray(nodes)) return [];
    const list = [];
    const roots = rootNodes(nodes);
    function walk(node, depth) {
        list.push({ node, depth });
        const children = nodes
            .filter((n) => n.parent_id === node.id)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        children.forEach((c) => walk(c, depth + 1));
    }
    roots.forEach((r) => walk(r, 0));
    return list;
}

async function loadTrees() {
    loading.value = true;
    try {
        const { data } = await api.get('/shejire/moderation');
        trees.value = Array.isArray(data) ? data : [];
    } catch (e) {
        trees.value = [];
    } finally {
        loading.value = false;
    }
}

async function approve(tree) {
    processing.value = tree.id;
    try {
        await api.post(`/shejire/moderation/${tree.id}/approve`);
        trees.value = trees.value.filter((t) => t.id !== tree.id);
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка');
    } finally {
        processing.value = null;
    }
}

function openReject(tree) {
    rejectingTree.value = tree;
    rejectReason.value = '';
}

async function reject() {
    if (!rejectingTree.value) return;
    const tree = rejectingTree.value;
    processing.value = tree.id;
    try {
        await api.post(`/shejire/moderation/${tree.id}/reject`, {
            rejected_reason: rejectReason.value.trim() || null,
        });
        trees.value = trees.value.filter((t) => t.id !== tree.id);
        rejectingTree.value = null;
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка');
    } finally {
        processing.value = null;
    }
}

onMounted(loadTrees);
</script>

<style scoped>
.shejire-moderation {
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
}

.page-subtitle {
    margin: 0;
    font-size: 15px;
    color: var(--color-text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge--count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: var(--color-accent);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    border-radius: 12px;
}

.loading {
    text-align: center;
    padding: 40px;
    color: var(--color-text-muted);
}

.empty-state {
    text-align: center;
    padding: 60px;
    background: var(--color-surface);
    border: 1px dashed var(--color-border);
    border-radius: 12px;
    color: var(--color-text-muted);
}

.empty-icon {
    font-size: 48px;
    color: #10b981;
    margin-bottom: 16px;
}

.trees-list {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.moderation-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.card-header {
    padding: 24px 24px 20px;
    background: linear-gradient(to bottom, #fafafa, var(--color-surface));
    border-bottom: 1px solid var(--color-border);
}

.card-title {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 700;
    color: var(--color-text);
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.meta-author {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 4px;
}

.meta-label {
    font-weight: 500;
    color: var(--color-text);
}

.meta-email {
    font-size: 13px;
    opacity: 0.9;
}

.meta-count {
    font-weight: 500;
}

.card-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    padding: 24px;
}

@media (max-width: 900px) {
    .card-content {
        grid-template-columns: 1fr;
    }
}

.diagram-section,
.nodes-section {
    min-width: 0;
}

.section-title {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.diagram-wrapper {
    padding: 20px;
    background: #f3f4f6;
    border-radius: 12px;
    overflow: auto;
    max-height: 360px;
    border: 1px solid var(--color-border);
}

.nodes-tree {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 360px;
    overflow-y: auto;
}

.card-actions {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    background: #fafafa;
    border-top: 1px solid var(--color-border);
}

.btn {
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    transition: background 0.15s ease;
}

.btn-success {
    background: #059669;
    color: #fff;
}

.btn-success:hover:not(:disabled) {
    background: #047857;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.btn-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.btn-secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: var(--color-surface);
    border-radius: 12px;
    padding: 24px;
    max-width: 480px;
    width: 90%;
}

.modal-title {
    margin: 0 0 20px;
    font-size: 18px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}
</style>
