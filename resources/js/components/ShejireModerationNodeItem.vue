<template>
    <div class="node-item" :class="{ 'node-item--nested': depth > 0 }" :style="{ paddingLeft: `${(depth || 0) * 24 + 12}px` }">
        <div class="node-card">
            <div class="node-main">
                <span class="node-name">{{ node.display_name || node.full_name }}</span>
                <span v-if="node.birth_date || node.death_date" class="node-dates">
                    {{ formatDate(node.birth_date) }} — {{ formatDate(node.death_date) }}
                </span>
            </div>
            <p v-if="node.moderator_comment" class="node-comment">{{ truncate(node.moderator_comment, 120) }}</p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    node: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    formatDate: { type: Function, required: true },
});

function truncate(str, maxLen) {
    if (!str || typeof str !== 'string') return '';
    const s = str.trim();
    if (s.length <= maxLen) return s;
    return s.slice(0, maxLen) + '…';
}
</script>

<style scoped>
.node-item {
    margin-bottom: 4px;
}

.node-item--nested .node-card {
    border-left: 3px solid #e5e7eb;
}

.node-item:last-child {
    margin-bottom: 0;
}

.node-card {
    padding: 12px 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.node-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.node-main {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 8px;
}

.node-name {
    font-weight: 600;
    font-size: 15px;
    color: var(--color-text);
    word-break: break-word;
}

.node-dates {
    font-size: 13px;
    color: var(--color-text-muted);
    white-space: nowrap;
}

.node-comment {
    margin: 8px 0 0;
    font-size: 13px;
    color: var(--color-text-muted);
    line-height: 1.4;
    word-break: break-word;
}
</style>
