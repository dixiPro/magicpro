<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';
import { useArticleStore } from '../store';
const store = useArticleStore();

const gotoArticle = ref('');

const url = computed(() => {
  return store.article.name === 'index' ? '/' : '/' + store.article.name;
});
</script>
<template>
  <div class="row gx-2 py-2 mx-0" style="background: #ffff001f">
    <div class="col-auto">
      <a v-if="store.article.isRoute" target="_blank" class="fas fa-external-link-alt btn btn-sm btn-primary" :href="url"></a>
      <span v-else class="btn btn-sm btn-secondary fas fa-external-link-alt"></span>
    </div>

    <div class="col-3">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-control-sm" v-model="store.article.name" />
        <button v-if="!store.statusTranslitButton" class="btn btn-sm btn-primary" @click="store.translit()">Translit→</button>
      </div>
    </div>

    <div class="col-3" v-if="store.statusTranslitButton">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-control-sm" v-model="store.article.title" />
        <button class="btn btn-sm btn-primary" @click="store.saveRec()">Save→</button>
      </div>
    </div>

    <div class="col-1">
      <div class="input-group input-group-sm">
        <input placeholder="перейти" type="text" class="form-control" v-model="gotoArticle" />
        <button class="btn btn-primary fas fa-angle-right" @click="store.gotoArticleByName(gotoArticle)"></button>
      </div>
    </div>

    <div class="col-auto">
      <span
        class="btn btn-success"
        :class="{
          'fas fas fa-sitemap': store.modeTreeSplitter == 'hidden',
          'fas fa-ellipsis-h': store.modeTreeSplitter === 'open',
        }"
        @click="store.toggleTreeSplitter()"
      ></span>
    </div>
    <div class="col-auto" v-if="store.article.routeParams.useController">
      <span
        class="btn btn-success"
        :class="{
          'fas fa-angle-left': store.modeEditorSplitter === 'full',
          'fas fa-angle-right': store.modeEditorSplitter === 'view',
          'fa fa-columns': store.modeEditorSplitter === 'controller',
        }"
        @click="store.toggleEditorSplitter()"
      ></span>
    </div>
    <div class="col-auto align-self-center">
      <button class="btn fas fa-magic btn-success" @click="store.formatDocument()">1</button>
    </div>
    <div class="col-auto">
      <button @click="store.statusHelp = !store.statusHelp" class="fas fa-question btn btn-success"></button>
    </div>
    <div class="col-auto">
      <select v-model="store.aceTheme" class="form-select form-select-sm">
        <option v-for="theme in store.aceThemes" :key="theme" :value="theme">
          {{ theme }}
        </option>
      </select>
    </div>

    <div class="col text-end">
      <button v-if="store.hasTwig" class="btn btn-success fas fa-sync-alt btn-sm me-2" @click="store.convertFromMro()"></button>

      <button class="btn btn-success fas fa-folder-open" @click="store.statusFileManager = !store.statusFileManager"></button>
      <button class="ms-1 btn btn-success fas fa-bars" @click="store.statusAddPannel = !store.statusAddPannel"></button>
    </div>
  </div>
</template>
