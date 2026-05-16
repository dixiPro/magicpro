<script setup>
import { ref, onMounted, watch, toRaw } from 'vue';
import Tree from 'primevue/tree';
import { apiArt } from '../../apiCall';
import { useArticleStore } from '../store';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const store = useArticleStore();

const treeData = ref([]);
const expandedKeys = ref({});
const selectionKeys = ref({});
const currentNodeId = ref(store.article.id);

const menu = ref();
const items = ref([]);

let currentNode = null;
let cutNode = null;

// статья
watch(
  () => store.article.id,
  () => {
    makeTree(store.article.id);
  },
);

// атрибуты узла
watch(
  () => store.article,
  () => {
    if (currentNode && store?.article?.title) {
      currentNode.label = store.article.title;
      currentNode.menuOn = store.article.menuOn;
      currentNode.isRoute = store.article.isRoute;
      document.title = store.article.title;
    }
  },
  { deep: true },
);

watch(currentNodeId, (id) => {
  store.loadRec(id);
});

onMounted(() => {
  makeTree(store.article.id);
});

// API node -> PrimeVue node
function toPv(n) {
  const node = {
    key: n.id,
    label: n.text,
    parentId: n.parentId,
    npp: n.npp,
    menuOn: n.menuOn,
    isRoute: n.isRoute,
    directory: !!n.directory,
    leaf: !n.directory,
    children: Array.isArray(n.children) ? n.children.map(toPv) : [],
  };
  return node;
}

function walk(node, fn) {
  fn(node);
  if (node.children) node.children.forEach((c) => walk(c, fn));
}

function findParent(nodes, key, parent = null) {
  for (const n of nodes) {
    if (n.key === key) return parent;
    if (n.children?.length) {
      const f = findParent(n.children, key, n);
      if (f) return f;
    }
  }
  return null;
}

function findParentAndPosition(key, nodes, parentKey = null) {
  let i = 0;
  for (const node of nodes) {
    if (node.key === key) return { parentKey, position: i, siblings: nodes };
    if (node.children) {
      const res = findParentAndPosition(key, node.children, node.key);
      if (res) return res;
    }
    i++;
  }
  return null;
}

async function makeTree(idArticle) {
  const tree = await apiArt({ command: 'makeHeTree', id: idArticle });
  treeData.value = tree.map(toPv);

  expandedKeys.value = {};
  currentNode = null;
  walk(treeData.value[0], (n) => {
    if (n.children && n.children.length) expandedKeys.value[n.key] = true;
    if (n.key === idArticle) currentNode = n;
  });

  currentNodeId.value = idArticle;
  selectionKeys.value = { [idArticle]: true };
}

// раскрытие папки — ленивая подгрузка
async function onNodeExpand(node) {
  if (node.children && node.children.length) return;
  if (!node.directory) return;
  try {
    node.loading = true;
    const childs = await apiArt({ command: 'getChildrens', id: node.key });
    node.children = childs.map(toPv);
    node.leaf = node.children.length === 0;
  } catch (e) {
    // ignore
  } finally {
    node.loading = false;
  }
}

// клик по узлу
function onNodeSelect(node) {
  currentNodeId.value = node.key;
}

// правая кнопка
function onRightClick(event, node) {
  items.value = [
    {
      label: t('new_tab'),
      icon: 'fas fa-link',
      command: () => window.open(location.pathname + '#' + node.key, '_blank'),
    },
    {
      label: t('create'),
      icon: 'fas fa-plus',
      command: () => createRec(node),
    },
    {
      label: t('delete'),
      icon: 'fas fa-trash',
      command: () => deleteRec(node),
    },
    {
      label: t('copy'),
      icon: 'fas fa-copy',
      command: () => copyRec(node),
    },
    {
      label: t('cut'),
      icon: 'fas fa-cut',
      command: () => {
        cutNode = node;
        document.showToast('Вырезано: ' + node.label);
      },
    },
    ...(cutNode && cutNode.key !== node.key
      ? [
          {
            label: t('insert'),
            icon: 'fas fa-paste',
            command: () => pasteRec(node),
          },
        ]
      : []),
  ];
  menu.value.show(event);
}

async function pasteRec(node) {
  if (!cutNode) return;
  try {
    await apiArt({
      command: 'move',
      id: cutNode.key,
      newParentId: node.key,
      idBrotherUp: 0,
    });
    cutNode = null;
    await makeTree(currentNodeId.value);
    document.showToast('Вставлено');
  } catch (e) {
    return;
  }
}

async function createRec(node) {
  try {
    const article = await apiArt({ command: 'createNew', id: node.key });
    const newNode = {
      key: article.id,
      label: article.title,
      parentId: article.parentId,
      npp: node.npp,
      directory: false,
      leaf: true,
      children: [],
    };
    if (!Array.isArray(node.children)) node.children = [];
    node.children.push(newNode);
    node.directory = true;
    node.leaf = false;
    expandedKeys.value = { ...expandedKeys.value, [node.key]: true };
    currentNodeId.value = article.id;
    selectionKeys.value = { [article.id]: true };
    document.showToast('Создано');
  } catch (e) {
    return;
  }
}

async function deleteRec(node) {
  if (node.key == 1) {
    document.showToast('Не стоит удалять root');
    return;
  }
  if (!(await document.confirmDialog('Удалить ?' + node.key))) return;

  const parent = findParent(treeData.value, node.key);
  if (!parent) {
    document.showToast('Ошибка удаления parent не найден', 'error');
    return;
  }

  let article;
  try {
    article = await apiArt({ command: 'deleteById', id: node.key });
  } catch (e) {
    return;
  }

  const idx = parent.children.findIndex((c) => c.key === node.key);
  if (idx === -1) {
    document.showToast('Ошибка удаления элемента, перезагрузите', 'error');
    return;
  }
  parent.children.splice(idx, 1);
  parent.directory = !!article.directory;
  parent.leaf = !parent.directory;
  parent.label = article.title;
  expandedKeys.value = { ...expandedKeys.value, [parent.key]: true };

  currentNodeId.value = parent.key;
  selectionKeys.value = { [parent.key]: true };
  document.showToast('Удалено');
}

async function copyRec(node) {
  try {
    const article = await apiArt({ command: 'copyRec', id: node.key });
    const newNode = {
      key: article.id,
      label: article.title,
      parentId: node.key,
      npp: node.npp,
      directory: false,
      leaf: true,
      children: [],
    };
    const parent = findParent(treeData.value, node.key);
    if (parent) {
      parent.children.push(newNode);
      expandedKeys.value = { ...expandedKeys.value, [parent.key]: true };
    }
    currentNodeId.value = article.id;
    selectionKeys.value = { [article.id]: true };
    document.showToast('Скопировано');
  } catch (e) {
    return;
  }
}

// перемещение узла — только внутри своего родителя
async function onNodeDrop(event) {
  const dragNode = toRaw(event.dragNode);
  if (!dragNode) return;

  const pos = findParentAndPosition(dragNode.key, treeData.value);
  if (!pos) return;

  // родитель сменился — откат, без вызова API
  if (pos.parentKey !== dragNode.parentId) {
    await makeTree(currentNodeId.value);
    return;
  }

  const idBrotherUp = pos.position > 0 ? pos.siblings[pos.position - 1].key : 0;

  try {
    await apiArt({
      command: 'move',
      id: dragNode.key,
      newParentId: pos.parentKey,
      idBrotherUp,
    });
  } catch (e) {
    console.log('node-drop error:', e);
  }
}
</script>

<template>
  <div class="ms-2 tree-pannel">
    <Tree
      v-model:selectionKeys="selectionKeys"
      v-model:expandedKeys="expandedKeys"
      v-model:value="treeData"
      selectionMode="single"
      loadingMode="icon"
      draggableNodes
      droppableNodes
      @node-select="onNodeSelect"
      @node-expand="onNodeExpand"
      @node-drop="onNodeDrop"
      class="my-2 tree-pannel"
    >
      <template #default="{ node }">
        <span
          class="pointer"
          @contextmenu.prevent="onRightClick($event, node)"
          :class="{
            active: node.key == currentNodeId,
            notroute: !node.isRoute,
            route: node.isRoute,
          }"
        >
          {{ node.label }}
          <i v-if="node.isRoute" class="icon-small fa-link fas mx-1"></i>
          <i v-if="node.menuOn" class="icon-small fas fa-eye mx-1"></i>
        </span>
      </template>
    </Tree>

    <ContextMenu ref="menu" :model="items" />
  </div>
</template>

<style scoped>
.tree-pannel {
  background: none;
}

.active {
  font-weight: bold;
}

.pointer {
  cursor: pointer;
}

.route {
  color: var(--bs-success);
}

[data-bs-theme='dark'] .route {
  color: #9dff00;
}

.notroute {
  color: var(--bs-secondary-color);
}

[data-bs-theme='dark'] .notroute {
  color: #eee;
}

/* .p-contextmenu ul {
  padding: 0;
}

.p-contextmenu-item-link {
  text-decoration: none !important;
} */

.icon-small {
  font-size: 11px;
}

:deep(.p-tree) {
  --p-tree-node-padding: 0 !important;
}

:deep(.p-tree) {
  --p-tree-node-gap: 0 !important;
}

:deep(.p-tree) {
  --p-icon-size: 12px;
}

:deep(.p-tree-node-toggle-button) {
  width: 12px;
  height: 12px;
  min-width: 12px;
  margin: 0;
  margin-right: 5px;
  padding: 0;
}

:deep(.p-tree) {
  padding: 0 !important;
  margin: 0 !important;
  font-size: 0.9rem;
}

:deep(.p-tree-root-children) {
  padding-left: 0;
  padding: 0 !important;
  margin: 0 !important;
}

:deep(.p-tree-node-content) {
  padding: 0;
}

:deep(.p-tree-node-children) {
  padding: 0;
  padding-left: 1rem;
}
:deep(.p-tree) {
  --p-tree-node-hover-background: rgba(var(--bs-body-color-rgb), 0.08);
  --p-tree-node-hover-color: var(--bs-body-color);

  --p-tree-node-selected-background: rgba(var(--bs-body-color-rgb), 0.14);
  --p-tree-node-selected-color: var(--bs-body-color);
}

:global(.p-contextmenu-root-list),
:global(.p-contextmenu-submenu) {
  padding-left: 0 !important;
}
</style>
