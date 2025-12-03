<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';

const props = defineProps({
  formatFile: Function,
  saveFile: Function,
});

const showFileManager = defineModel('visible', { type: Boolean, default: false });
</script>
<template>
  <div class="row gx-2 py-2 mx-0" style="background: #ffff001f">
    <div class="col-3">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-control-sm" />
        <button class="btn btn-sm btn-primary" @click="saveFile">Save→</button>
      </div>
    </div>

    <div class="col-auto align-self-center">
      <button class="btn fas fa-magic btn-success" @click="formatFile"></button>
    </div>

    <div class="col text-end">
      <button class="btn btn-success fas fa-folder-open" @click="showFileManager = !showFileManager"></button>
    </div>
  </div>
</template>
