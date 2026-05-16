<script setup>
import { toRaw, ref, reactive, onMounted, watch, computed, nextTick } from 'vue';
import { useApi } from '../../composables/useApi.js';
import Tree from 'primevue/tree';
import { goUrl } from '@/router';
import { useStore } from '@/store';
const store = useStore();

const treeData = ref([]);
const dataReady = ref(false);

async function getChildrens(item_id) {
  try {
    const data = await useApi({
      url: '/apiitem/getChildrens',
      data: {
        parent_id: item_id,
      },
    });

    const res = data.map((el) => {
      const type = store.listOfTypes?.[el.type_id];
      const res = {
        key: el.id,
        parent_id: item_id,
        label: el.siteTitle,
        leaf: true,
        loading: false,
        type_id: el.type_id,
      };
      if (type?.structure?.icon) {
        res.icon = store.listOfTypes?.[el.type_id]?.structure?.icon;
      }
      if (type?.structure?.children) {
        res.leaf = false;
      }
      return res;
    });
    return res;
  } catch (error) {
    console.log(error);
  }
}

async function loadRootData() {
  treeData.value = await getChildrens(store.root_id);
}
onMounted(async () => {
  await loadRootData();
  dataReady.value = true;
});

async function onNodeExpand(node) {
  if (node.children?.length > 0) {
    return;
  }
  try {
    node.loading = true;
    node.children = await getChildrens(node.key);
    node.loading = false;
  } catch (error) {
    console.log(error);
  }
}

function goLink(node) {
  return store.listOfTypes[node.type_id]?.structure?.children
    ? store.listOfTypes[node.type_id]?.structure?.children
    : store.listOfTypes[node.type_id]?.structure?.edit;
}

function findParentAndPosition(key, nodes, parentKey = null) {
  let i = 0;
  for (const node of nodes) {
    if (node.key === key) {
      return { parentKey, position: i };
    }

    if (node.children) {
      const res = findParentAndPosition(key, node.children, node.key);
      if (res) return res;
    }
    i++;
  }
  return null;
}

async function onNodeDrop(event) {
  // console.log(toRaw(event));
  const dragNode = toRaw(event.dragNode);
  if (!dragNode) {
    document.showToast('Ошибка перетаскивания', 'error');
    return;
  }
  const newPos = findParentAndPosition(dragNode.key, treeData.value);
  newPos.parentKey = newPos.parentKey == null ? store.root_id : newPos.parentKey;

  try {
    await useApi({
      url: '/apiitem/moveMainParent',
      data: {
        id: dragNode.key,
        old_parent_id: dragNode.parent_id,
        new_parent_id: newPos.parentKey,
        position: newPos.position + 1,
      },
    });

    dragNode.parent_id = newPos.parentKey;
  } catch (error) {
    console.log('node-drop error:', error);
  }
}
</script>

<template>
  <div v-if="dataReady" class="tree">
    <div class="tree">
      <Tree
        v-model:value="treeData"
        @node-expand="onNodeExpand"
        @node-drop="onNodeDrop"
        loadingMode="icon"
        class="w-full md:w-[30rem]"
        draggableNodes
        droppableNodes
      >
        <template #default="slotProps">
          <RouterLink :to="{ name: goLink(slotProps.node), params: { id: slotProps.node.key } }"
            >{{ slotProps.node.label }} {{ slotProps.node.key }} {{ slotProps.node.position }}</RouterLink
          >
        </template>
      </Tree>
      <!-- <pre> 
treeData
  {{ treeData }}
</pre
      > -->
    </div>
  </div>
</template>

<style scoped>
:deep(.p-tree) {
  padding: 0;
}

:deep(.p-tree-root-children) {
  padding-left: 0;
}

:deep(.p-tree-node-children) {
  padding-left: 1rem;
}
.tree {
  overflow-y: auto;
  height: 95%;
  overflow-x: hidden;
  white-space: nowrap;
}
</style>
