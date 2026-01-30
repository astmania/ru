<template>
    <div ref="branchRef" class="tree-branch">
        <div class="tree-line tree-line--down"></div>
        <div
            v-if="children.length >= 2"
            ref="horizontalRef"
            class="tree-line tree-line--horizontal"
            :style="horizontalStyle"
        ></div>
        <div ref="levelRef" class="tree-level">
            <div
                v-for="child in children"
                :key="child.id"
                class="tree-node-wrap tree-node-wrap--child"
            >
                <div class="tree-drop">
                    <div class="tree-line tree-line--down"></div>
                </div>
                <ShejireNodeCard :node="child" :on-node-click="onNodeClick" />
                <div v-if="getChildren(child).length" class="tree-children tree-children--nested">
                    <ShejireTreeBranch
                        :nodes="nodes"
                        :parent-node="child"
                        :get-children="getChildren"
                        :on-node-click="onNodeClick"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUpdated, onUnmounted, watch } from 'vue';
import ShejireNodeCard from './ShejireNodeCard.vue';

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    parentNode: { type: Object, required: true },
    getChildren: { type: Function, required: true },
    onNodeClick: { type: Function, default: null },
});

const children = computed(() => props.getChildren(props.parentNode));

const branchRef = ref(null);
const levelRef = ref(null);
const horizontalRef = ref(null);
const horizontalStyle = ref({});

function updateHorizontalLine() {
    if (!branchRef.value || !levelRef.value || !horizontalRef.value || children.value.length < 2) {
        horizontalStyle.value = {};
        return;
    }
    const drops = levelRef.value.querySelectorAll('.tree-drop');
    if (drops.length < 2) {
        horizontalStyle.value = {};
        return;
    }
    const first = drops[0];
    const last = drops[drops.length - 1];
    const branchRect = branchRef.value.getBoundingClientRect();
    const firstRect = first.getBoundingClientRect();
    const lastRect = last.getBoundingClientRect();
    const firstCenter = firstRect.left - branchRect.left + firstRect.width / 2;
    const lastCenter = lastRect.left - branchRect.left + lastRect.width / 2;
    const width = Math.max(0, lastCenter - firstCenter);
    const left = firstCenter;
    horizontalStyle.value = {
        width: `${width}px`,
        marginLeft: `${left}px`,
        alignSelf: 'flex-start',
    };
}

onMounted(() => {
    setTimeout(updateHorizontalLine, 0);
    window.addEventListener('resize', updateHorizontalLine);
});

onUpdated(() => {
    setTimeout(updateHorizontalLine, 0);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateHorizontalLine);
});

watch(children, () => {
    setTimeout(updateHorizontalLine, 0);
}, { deep: true });
</script>

<style scoped>
.tree-branch {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.tree-line {
    background: #9ca3af;
    pointer-events: none;
}

.tree-line--down {
    width: 1px;
    height: 20px;
}

.tree-line--horizontal {
    height: 1px;
    min-width: 0;
}

.tree-level {
    display: flex;
    flex-wrap: nowrap;
    gap: 4px;
    align-items: flex-start;
    justify-content: center;
    margin-top: 0;
}

.tree-node-wrap--child {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.tree-drop {
    display: flex;
    justify-content: center;
    width: 100%;
    pointer-events: none;
}

.tree-drop .tree-line--down {
    height: 20px;
}

</style>
