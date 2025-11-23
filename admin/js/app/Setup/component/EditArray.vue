<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';
import DeleteButton from './DeleteButton.vue';

const props = defineProps({
  defaultValue: { type: Array, required: true },
  mutable: { type: Boolean, required: true },
});

const inputArray = defineModel();

function deleteKey(key) {
  inputArray.value.splice(key, 1);
}

function addNew() {
  if (!newElement.value.trim()) {
    return;
  }
  inputArray.value.push(newElement.value);
  newElement.value = '';
}

const newElement = ref('');
</script>

<template>
  <div v-for="(val, key) in inputArray" :key="key" class="row my-1">
    <div class="col-12">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-sm" v-model="inputArray[key]" :disabled="!mutable" />
        <DeleteButton :action="() => deleteKey(key)"></DeleteButton>
        <!-- <button
          class="fas fa-trash pointer btn btn-sm"
          @click="deleteKey(key)"
        ></button> -->
      </div>
    </div>
  </div>

  <div class="row my-1">
    <div class="col-12">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control form-sm" v-model="newElement" :disabled="!mutable" />

        <button class="btn btn-sm fas fa-plus pointer" @click="addNew()"></button>
      </div>
    </div>
  </div>
</template>
