<template>
    <div class="shejire-diagram">
        <template v-if="rootNodes.length">
            <div class="tree-root">
                <template v-for="node in rootNodes" :key="node.id">
                    <div class="tree-node-wrap">
                        <ShejireNodeCard :node="node" :on-node-click="onNodeClick" />
                        <div v-if="getChildren(node).length" class="tree-children">
                            <ShejireTreeBranch
                                :nodes="nodes"
                                :parent-node="node"
                                :get-children="getChildren"
                                :on-node-click="onNodeClick"
                            />
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <div v-else class="empty-diagram">Нет персон в родословной</div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ShejireNodeCard from './ShejireNodeCard.vue';
import ShejireTreeBranch from './ShejireTreeBranch.vue';

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    onNodeClick: { type: Function, default: null },
});

const rootNodes = computed(() => {
    const list = props.nodes || [];
    return list.filter((n) => !n.parent_id);
});

function getChildren(node) {
    const list = props.nodes || [];
    return list.filter((n) => n.parent_id === node.id);
}
</script>

<style scoped>
.shejire-diagram {
    display: inline-flex;
    justify-content: center;
    padding: 32px 0;
    min-width: max-content;
}

.tree-root {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.tree-node-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.tree-children {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.empty-diagram {
    padding: 48px;
    color: #6b7280;
    font-size: 15px;
    text-align: center;
}
</style>
