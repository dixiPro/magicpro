<script setup>
import { ref, toRaw, computed } from 'vue';
import { useArticleStore } from '../store';
import { storeToRefs } from 'pinia';

const store = useArticleStore();

// const leftPannel = ref('tree');
const result = ref([]);
const findText = ref('MagicController');

async function findInBase() {
  result.value = await store.search(findText.value);
}

async function goArticle(id) {
  await store.loadRec(id);
  if (store.article.body.search(findText.value) >= 0) {
    store.searchTextView = findText.value;
  }
  if (store.article.controller.search(findText.value) >= 0) {
    store.searchTextController = findText.value;
  }
}
</script>

<template>
  <div class="ms-2 tree-pannel">
    <div class="row my-1">
      <div class="input-group input-group-sm">
        <button class="btn btn-primary fas fa-times" @click="store.statusLeftPannel = 'tree'"></button>
        <input placeholder="найти" type="text" class="form-control" v-model="findText" />
        <button class="btn btn-primary fas fa-angle-right" @click="findInBase()"></button>
      </div>
    </div>

    <div v-for="rec in result">
      <span v-text="rec.title" @click="goArticle(rec.id)" class="pointer" :class="{ active: store.article.id == rec.id }"></span>
    </div>
  </div>
</template>
<style scoped>
.active {
  font-weight: bold;
}
</style>
