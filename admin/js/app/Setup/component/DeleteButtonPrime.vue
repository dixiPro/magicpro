<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';

const props = defineProps({
  action: Function,
});

const confirm = ref(false);

async function handleClick() {
  if (confirm.value) {
    props.action();
    confirm.value = false;
    return;
  }
  confirm.value = true;
}
</script>

<template>
  <div style="display: inline-block; white-space: nowrap">
    <Button icon="pi pi-trash" severity="secondary" size="small" @click="handleClick" />
    <Button v-if="confirm" icon="pi pi-times" severity="secondary" size="small" @click="confirm = false" class="mr-1" />
  </div>
</template>
