<template>
    <div
        class="node-card"
        :class="{ 'node-card--clickable': onNodeClick }"
        :title="onNodeClick ? 'Кликните для редактирования' : (node.birth_date || node.death_date ? `${formatDate(node.birth_date)} — ${formatDate(node.death_date)}` : null)"
        @click="onNodeClick && onNodeClick(node)"
    >
        <div class="node-name">{{ node.display_name || node.full_name }}</div>
        <div v-if="node.birth_date || node.death_date" class="node-dates node-dates--hover">
            {{ formatDate(node.birth_date) }} — {{ formatDate(node.death_date) }}
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    node: { type: Object, required: true },
    onNodeClick: { type: Function, default: null },
});

function formatDate(val) {
    if (!val) return '—';
    const d = new Date(val);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).padStart(4, '0');
    return `${day}.${month}.${year}`;
}
</script>

<style scoped>
.node-card {
    padding: 2px 6px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    text-align: center;
    cursor: default;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
    flex-shrink: 0;
}

.node-card--clickable {
    cursor: pointer;
    position: relative;
    z-index: 1;
}

.node-card--clickable:hover {
    border-color: var(--color-accent, #374151);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.node-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border-color: #9ca3af;
}

.node-name {
    font-weight: 500;
    font-size: 15px;
    color: #374151;
    white-space: nowrap;
}

.node-dates {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
    white-space: nowrap;
}

/* Даты показываются только при наведении */
.node-dates--hover {
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    margin-top: 0;
    transition: opacity 0.2s ease, max-height 0.2s ease, margin 0.2s ease;
}

.node-card:hover .node-dates--hover {
    opacity: 1;
    max-height: 48px;
    margin-top: 4px;
}
</style>
