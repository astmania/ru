<template>
    <div class="shejire-tree-form">
        <div class="container">
            <header class="page-header">
                <router-link to="/shejire" class="back-link">← К списку</router-link>
                <h1 class="page-title">{{ isEdit ? 'Редактирование родословной' : 'Новая родословная' }}</h1>
                <p v-if="isEdit && tree?.status === 'approved'" class="page-hint">
                    После сохранения родословная будет отправлена на повторную модерацию перед публикацией.
                </p>
            </header>

            <div v-if="loading && !tree" class="loading">Загрузка…</div>
            <template v-else>
                <div class="form-section">
                    <div class="form-group">
                        <label>Название (необязательно)</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            placeholder="Например: Род Ивановых"
                            maxlength="255"
                        />
                    </div>
                </div>

                <div class="nodes-section">
                    <h2 class="section-title">Персоны в родословной</h2>
                    <p class="section-hint">Добавляйте персон с полным ФИО, датами рождения и смерти. Краткое описание — комментарий для модератора.</p>

                    <div class="diagram-preview">
                        <ShejireDiagram v-if="nodes.length" :nodes="nodes" :on-node-click="openEditNode" />
                        <div v-else class="empty-preview">Добавьте первую персону</div>
                    </div>

                    <div class="nodes-list">
                        <div
                            v-for="node in nodes"
                            :key="node.id"
                            class="node-row"
                        >
                            <div class="node-info">
                                <span class="node-display">{{ node.display_name || node.full_name }}</span>
                                <span v-if="node.birth_date || node.death_date" class="node-dates">
                                    {{ formatDate(node.birth_date) }} — {{ formatDate(node.death_date) }}
                                </span>
                            </div>
                            <div class="node-actions">
                                <button type="button" class="btn-icon" @click="openEditNode(node)" title="Редактировать">✎</button>
                                <button type="button" class="btn-icon btn-icon--danger" @click="removeNode(node)" title="Удалить">×</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" @click="openAddNode">
                        + Добавить персону
                    </button>
                </div>

                <div class="form-actions">
                    <router-link to="/shejire" class="btn btn-secondary">Отмена</router-link>
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="save">
                        {{ saving ? 'Сохранение…' : (isEdit ? 'Сохранить' : 'Создать') }}
                    </button>
                </div>
            </template>

            <!-- Модальное окно добавления/редактирования персоны -->
            <div v-if="showNodeModal" class="modal-overlay" @click.self="closeNodeModal">
                <div class="modal">
                    <h3 class="modal-title">{{ editingNode ? 'Редактировать персону' : 'Добавить персону' }}</h3>
                    <form @submit.prevent="submitNode" class="node-form">
                        <div class="form-group">
                            <label>Полное ФИО *</label>
                            <input
                                v-model="nodeForm.full_name"
                                type="text"
                                class="form-control"
                                required
                                placeholder="Иванов Иван Иванович"
                                maxlength="255"
                            />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Дата рождения</label>
                                <input
                                    v-model="nodeForm.birth_date"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <div class="form-group">
                                <label>Дата смерти</label>
                                <input
                                    v-model="nodeForm.death_date"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Родитель (необязательно)</label>
                            <select v-model="nodeForm.parent_id" class="form-control">
                                <option :value="null">— Без родителя (корень) —</option>
                                <option
                                    v-for="n in nodes.filter(x => x.id !== editingNode?.id)"
                                    :key="n.id"
                                    :value="n.id"
                                >
                                    {{ n.display_name || n.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Комментарий для модератора</label>
                            <textarea
                                v-model="nodeForm.moderator_comment"
                                class="form-control"
                                rows="3"
                                placeholder="Краткое описание для одобрения публикации"
                                maxlength="1000"
                            ></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" @click="closeNodeModal">Отмена</button>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import ShejireDiagram from './ShejireDiagram.vue';

const route = useRoute();
const router = useRouter();
const treeId = computed(() => route.params.id);
const isEdit = computed(() => !!treeId.value && treeId.value !== 'new');

const tree = ref(null);
const loading = ref(true);
const saving = ref(false);
const nodes = ref([]);
const showNodeModal = ref(false);
const editingNode = ref(null);

const form = reactive({
    title: '',
});

const nodeForm = reactive({
    full_name: '',
    birth_date: '',
    death_date: '',
    parent_id: null,
    moderator_comment: '',
});

function formatDate(val) {
    if (!val) return '—';
    const d = new Date(val);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).padStart(4, '0');
    return `${day}.${month}.${year}`;
}

async function loadTree() {
    if (!isEdit.value) {
        loading.value = false;
        return;
    }
    loading.value = true;
    try {
        const { data } = await api.get(`/shejire/${treeId.value}`);
        tree.value = data;
        form.title = data.title || '';
        nodes.value = (data.nodes || []).map((n) => ({ ...n }));
    } catch (e) {
        tree.value = null;
    } finally {
        loading.value = false;
    }
}

function openAddNode() {
    editingNode.value = null;
    nodeForm.full_name = '';
    nodeForm.birth_date = '';
    nodeForm.death_date = '';
    nodeForm.parent_id = null;
    nodeForm.moderator_comment = '';
    showNodeModal.value = true;
}

function openEditNode(node) {
    if (!node) return;
    editingNode.value = node;
    nodeForm.full_name = node.full_name || '';
    nodeForm.birth_date = formatDateForInput(node.birth_date);
    nodeForm.death_date = formatDateForInput(node.death_date);
    nodeForm.parent_id = node.parent_id ?? null;
    nodeForm.moderator_comment = node.moderator_comment || '';
    showNodeModal.value = true;
}

function formatDateForInput(val) {
    if (!val) return '';
    if (typeof val === 'string') return val.slice(0, 10);
    try {
        const d = new Date(val);
        return d.toISOString().slice(0, 10);
    } catch {
        return '';
    }
}

function closeNodeModal() {
    showNodeModal.value = false;
    editingNode.value = null;
}

function showSaveError(err, context) {
    const msg = err.response?.data?.message || 'Ошибка сохранения';
    const errors = err.response?.data?.errors;
    const details = errors
        ? Object.entries(errors)
            .map(([k, v]) => `${k}: ${Array.isArray(v) ? v.join(', ') : v}`)
            .join('\n')
        : '';
    alert(context ? `${msg} (персона: ${context})${details ? '\n' + details : ''}` : (details ? msg + '\n' + details : msg));
}

function submitNode() {
    const payload = {
        full_name: nodeForm.full_name.trim(),
        birth_date: nodeForm.birth_date || null,
        death_date: nodeForm.death_date || null,
        parent_id: nodeForm.parent_id,
        moderator_comment: nodeForm.moderator_comment.trim() || null,
    };

    if (editingNode.value) {
        const idx = nodes.value.findIndex((n) => n.id === editingNode.value.id);
        if (idx !== -1) {
            Object.assign(nodes.value[idx], { ...nodes.value[idx], ...payload });
        }
    } else {
        const tempId = 'temp-' + Date.now();
        nodes.value.push({
            id: tempId,
            ...payload,
            display_name: computeDisplayName(payload.full_name),
        });
    }
    closeNodeModal();
}

function computeDisplayName(fullName) {
    const parts = String(fullName || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length < 2) return fullName;
    const surname = parts[0];
    const initials = parts.slice(1).map((p) => p.charAt(0).toUpperCase() + '.').join('');
    return `${surname} ${initials}`;
}

function removeNode(node) {
    if (!confirm('Удалить персону?')) return;
    const idx = nodes.value.findIndex((n) => n.id === node.id);
    if (idx !== -1) nodes.value.splice(idx, 1);
}

async function save() {
    saving.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/shejire/${treeId.value}`, { title: form.title });
            const existingIds = new Set(nodes.value.filter((n) => typeof n.id === 'number').map((n) => n.id));
            for (const node of nodes.value) {
                if (typeof node.id === 'string' && node.id.startsWith('temp-')) {
                    const { data } = await api.post(`/shejire/${treeId.value}/nodes`, {
                        full_name: node.full_name,
                        birth_date: node.birth_date || null,
                        death_date: node.death_date || null,
                        parent_id: node.parent_id,
                        moderator_comment: node.moderator_comment || null,
                    });
                    node.id = data.id;
                } else if (existingIds.has(node.id)) {
                    await api.put(`/shejire/${treeId.value}/nodes/${node.id}`, {
                        full_name: node.full_name,
                        birth_date: node.birth_date || null,
                        death_date: node.death_date || null,
                        parent_id: node.parent_id,
                        moderator_comment: node.moderator_comment || null,
                    });
                }
            }
            const toDelete = (tree.value?.nodes || []).filter((n) => !nodes.value.some((x) => x.id === n.id));
            for (const n of toDelete) {
                await api.delete(`/shejire/${treeId.value}/nodes/${n.id}`);
            }
            router.push(`/shejire/${treeId.value}`);
        } else {
            const { data } = await api.post('/shejire', { title: form.title });
            const treeIdNew = data.id;
            const idMap = {};
            let remaining = [...nodes.value];
            while (remaining.length) {
                const batch = remaining.filter((n) => !n.parent_id || idMap[n.parent_id]);
                if (!batch.length) {
                    // Запасной вариант: если из-за порядка/связей batch пуст — сохраняем оставшихся как корневые
                    for (const node of remaining) {
                        try {
                            const { data: created } = await api.post(`/shejire/${treeIdNew}/nodes`, {
                                full_name: node.full_name,
                                birth_date: node.birth_date || null,
                                death_date: node.death_date || null,
                                parent_id: null,
                                moderator_comment: node.moderator_comment || null,
                            });
                            idMap[node.id] = created.id;
                        } catch (err) {
                            showSaveError(err, node.full_name);
                            throw err;
                        }
                    }
                    remaining = [];
                    break;
                }
                for (const node of batch) {
                    const parentId = node.parent_id ? idMap[node.parent_id] : null;
                    try {
                        const { data: created } = await api.post(`/shejire/${treeIdNew}/nodes`, {
                            full_name: node.full_name,
                            birth_date: node.birth_date || null,
                            death_date: node.death_date || null,
                            parent_id: parentId,
                            moderator_comment: node.moderator_comment || null,
                        });
                        idMap[node.id] = created.id;
                    } catch (err) {
                        showSaveError(err, node.full_name);
                        throw err;
                    }
                }
                remaining = remaining.filter((n) => !idMap[n.id]);
            }
            router.push(`/shejire/${treeIdNew}`);
        }
    } catch (e) {
        if (!e.response) throw e;
        showSaveError(e);
    } finally {
        saving.value = false;
    }
}

onMounted(loadTree);
</script>

<style scoped>
.shejire-tree-form {
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

.page-title {
    margin: 0 0 8px;
    font-size: 24px;
    font-weight: 700;
    color: var(--color-text);
}

.page-hint {
    margin: 0;
    font-size: 14px;
    color: var(--color-text-muted);
}

.form-section {
    margin-bottom: 28px;
}

.form-group {
    margin-bottom: 16px;
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
    max-width: 400px;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.form-row {
    display: flex;
    gap: 16px;
}

.form-row .form-group {
    flex: 1;
}

.nodes-section {
    margin-bottom: 28px;
}

.section-title {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
}

.section-hint {
    margin: 0 0 16px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.diagram-preview {
    padding: 24px;
    background: #f3f4f6;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: auto;
    max-height: 500px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #9ca3af #e5e7eb;
}

.diagram-preview::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.diagram-preview::-webkit-scrollbar-track {
    background: #e5e7eb;
    border-radius: 5px;
}

.diagram-preview::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 5px;
}

.diagram-preview::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

.empty-preview {
    text-align: center;
    padding: 40px;
    color: var(--color-text-muted);
}

.nodes-list {
    margin-bottom: 16px;
}

.node-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    margin-bottom: 8px;
}

.node-display {
    font-weight: 600;
    color: var(--color-text);
}

.node-dates {
    font-size: 13px;
    color: var(--color-text-muted);
    margin-left: 12px;
}

.btn-icon {
    padding: 6px 10px;
    font-size: 14px;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
}

.btn-icon--danger {
    color: #dc2626;
    border-color: #dc2626;
}

.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
}

.btn {
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
}

.btn-primary {
    background: var(--color-accent);
    color: #fff;
}

.btn-secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 1px solid var(--color-border);
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

.node-form .form-control {
    max-width: 100%;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}
</style>
