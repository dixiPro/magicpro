<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue';

import { BaseTree, Draggable, pro, OpenIcon } from '@he-tree/vue';
import '@he-tree/vue/style/default.css';
import '@he-tree/vue/style/material-design.css';
import { dragContext } from '@he-tree/vue';

import { useArticleStore } from '../store';
const store = useArticleStore();

// статья
watch(
  () => store.article.id,
  () => {
    makeHeTree(store.article.id);
  }
);

// атрибуты узла
watch(
  () => store.article,
  () => {
    if (store?.article?.title && typeof currentNodeNode?.text != undefined) {
      currentNodeNode.text = store?.article?.title;
      currentNodeNode.menuOn = store?.article?.menuOn;
      currentNodeNode.isRoute = store?.article?.isRoute;
    }
  },
  { deep: true }
);

onMounted(() => {
  makeHeTree(store.article.id);
});

// пробежаться по дереву и применить func к каждому узлу
// func = (node) => { что то сделать с node}
function walkTree(node, func) {
  func(node);
  if (node?.children) {
    node.children.forEach((el) => {
      walkTree(el, func);
    });
  }
}

// текущий узел
let currentNodeNode = null;

// текущий id в дереве
const currentNodeId = ref(store.article.id);

watch(currentNodeId, () => {
  store.loadRec(currentNodeId.value);
});
// // версия для перестройки дерева
const version = ref(0);
// дерево
const treeData = ref([]);
// меню по правой кнопки мыши компонент
const menu = ref();
// пункты меню
const items = ref([]);

async function makeHeTree(idArticle) {
  const tree = await apiArt({
    command: 'makeHeTree',
    id: idArticle,
  });
  currentNodeId.value = idArticle;

  //
  treeData.value = tree;

  walkTree(treeData.value[0], (n) => {
    n.lazy = n?.directory && !n?.children ? true : false;
    if (n?.id === idArticle) {
      currentNodeNode = n;
    }
  });
}

async function createRec(node, stat, tree) {
  try {
    const article = await apiArt({
      command: 'createNew',
      id: node.id,
    });
    const newNode = {
      id: article.id,
      text: article.title,
      parentId: article.parentId,
      directory: false,
      npp: node.npp,
      children: [],
    };

    const parent = stat;
    tree.add(newNode, parent, stat.children.length);
    parent.open = true; // раскрыть родителя
    currentNodeId.value = article.id;
    document.showToast('Создано');
  } catch (error) {
    return;
  }
}

async function deleteRec(node, stat, tree) {
  if (node.id == 1) {
    document.showToast('Не стоит удалять root');
    return;
  }
  if (!(await document.confirmDialog('Удалить ?' + node.id))) return;

  const parent = stat.parent;
  if (!parent) {
    document.showToast('Ошибка удаления parent не найден', 'error');
  }

  let article;
  try {
    article = await apiArt({
      command: 'deleteById',
      id: node.id,
    });
  } catch (error) {
    return;
  }

  const ok = tree.remove(stat); // true/false
  if (!ok) {
    document.showToast('Ошибка удаления элемента, перезагрузите', 'error');
    return;
  }

  currentNodeId.value = parent.data.id ?? null; // перевести указатель на родителя (если он есть)
  parent.open = true; // на всякий случай раскрыть
  parent.data.directory = article.directory; // может все дети удалились
  parent.data.text = article.title; // обновить название
  tree.openNodeAndParents(parent); // гарантировать видимость
  document.showToast('Удалено');
}
// по проавой кнопке формируем меню
function onRightClick(event, node, stat, tree) {
  items.value = [
    {
      label: 'Новой вкладке',
      icon: 'fas fa-link',
      command: () => {
        window.open(location.pathname + '#' + node.id, '_blank');
      },
    },

    {
      label: 'Создать',
      icon: 'fas fa-plus',
      command: () => createRec(node, stat, tree),
    },
    {
      label: 'Удалить',
      icon: 'fas fa-trash',
      command: () => deleteRec(node, stat, tree),
    },
    {
      label: 'Копировать',
      icon: 'fas fa-copy',
      command: () => copyRec(node, stat, tree),
    },
    // ...(node.type == "folder" ? [{ label: "Копировать ВСё", icon: "fas fa-clone", command: () => createEmmit("copyAll", node) }] : [])
  ];
  // показ меню
  menu.value.show(event);
}

// копирование
async function copyRec(node, stat, tree) {
  try {
    const article = await apiArt({
      command: 'copyRec',
      id: node.id,
    });
    const newNode = {
      id: article.id,
      text: article.title,
      parentId: node.id,
      directory: false,
      npp: node.npp,
      children: [],
    };
    const parent = stat.parent;
    tree.add(newNode, parent, node.npp);
    parent.open = true; // раскрыть родителя
    currentNodeId.value = article.id;
    document.showToast('Скопировано');
  } catch (error) {
    return;
  }
}

// по клику на текст
function onTextClick(node, stat) {
  currentNodeId.value = node.id;
}

// при нажатии на папку читаем детей и открываем
async function onFolderClick(node, stat, tree) {
  if (node.lazy && !stat?.data?.lazyLoad) {
    stat.data.lazyLoad = true;
    let children;
    try {
      node._loading = true;
      children = await apiArt({ command: 'getChildrens', id: node.id });
      lazyLoad = false;
    } catch (error) {
    } finally {
      node._loading = false;
      stat.data.lazy = false;
    }
    tree.batchUpdate(() => {
      tree.addMulti(children, stat, stat.children.length);
      stat.open = true;
    });
  } else {
    stat.open = !stat.open;
  }
}

// переместить узел
let lastPos = null;

function onDragStart() {
  const { dragNode } = dragContext;
  const parent = dragNode.parent ?? null;
  const index = parent ? parent.children.indexOf(dragNode) : 0;
  lastPos = { parent, index };
  currentNodeId.value = dragNode.data.id;
}
//  после перемещения
async function onAfterDrop() {
  const { dragNode, targetInfo } = dragContext;
  const id = dragNode.data.id;
  if (id === 1) {
    rollback(dragNode);
    return;
  }
  const parentStat = targetInfo?.parent ?? null;
  const newParentId = parentStat?.data?.id ?? null;
  if (!newParentId) {
    rollback(dragNode);
    return;
  }
  // состав соседей и позиция
  const list = targetInfo.siblings ?? parentStat.children ?? [];
  const idx = list.findIndex((s) => s === dragNode || s.data?.id === id);
  const idBrotherUp = idx > 0 ? list[idx - 1].data?.id ?? 0 : 0;

  try {
    const res = await apiArt({
      command: 'move',
      id: id,
      newParentId,
      idBrotherUp,
    });
    if (parentStat) parentStat.open = true;
  } catch (e) {
    rollback(dragNode);
    return;
  }
}

function rollback(dragNode) {
  const { targetTree } = dragContext;
  if (lastPos?.parent && targetTree) {
    targetTree.move(dragNode, lastPos.parent, lastPos.index);
    lastPos.parent.open = true;
  }
}
</script>

<template>
  <!-- <pre>{{ JSON.stringify(treeData, null, 2) }} </pre> -->
  <div class="ms-2 tree-pannel">
    <Draggable class="mtl-tree my-2" v-model="treeData" :key="version" @after-drop="onAfterDrop" :ondragstart="onDragStart" treeLine>
      <template #default="{ node, stat, tree }">
        <div class="d-flex pointer" @dragover.stop.prevent="node.lazy ? onFolderClick(node, stat, tree) : ''">
          <div>
            <OpenIcon v-if="stat.children.length" :open="stat.open" class="m-0 p-0" @click.native="stat.open = !stat.open" />
          </div>

          <div v-if="node.lazy" @click.native="onFolderClick(node, stat, tree)">
            <i class="fas fa-chevron-right me-1"></i>
          </div>

          <div
            @contextmenu.prevent="onRightClick($event, node, stat, tree)"
            :class="{
              active: node.id == currentNodeId,
              notroute: !node.isRoute,
              route: node.isRoute,
            }"
            @click="onTextClick(node, stat)"
          >
            <span :style="node.directory ? 'margin-left:-3px' : 'margin-left:3px'"></span>{{ node.text }}

            <i v-if="node.isRoute" class="icon-small fa-link fas mx-1"></i>
            <i v-if="node.menuOn" class="icon-small fas fa-eye mx-1"></i>
          </div>
        </div>
      </template>
    </Draggable>

    <ContextMenu ref="menu" :model="items" />
  </div>
</template>
<style>
.active {
  font-weight: bold;
}

.pointer {
  cursor: pointer;
}

.notroute {
  color: #555;
}

.route {
  color: #007700;
}

.he-tree__open-icon svg {
  width: 20px;
  height: 20px;
}

.he-tree {
  --he-tree-indent: 34px;
  /* стандартно около 16px */
}

.p-contextmenu ul {
  padding: 0;
}

/* убираем подчеркивание */
.p-contextmenu-item-link {
  text-decoration: none !important;
}

.icon-small {
  font-size: 11px;
}
</style>
