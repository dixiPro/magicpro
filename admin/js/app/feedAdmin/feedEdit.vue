<script setup>
import { computed, onMounted, watch } from 'vue';
import { useFeedStore } from './store.js';
import feedStructure from './feedStructure.vue';

const props = defineProps({
  feedId: { type: [String, Number], required: true },
});

const store = useFeedStore();

const feedId = computed(() => Number(props.feedId));

// ленту читаем здесь, обе вкладки берут её из стора
watch(feedId, () => store.load(feedId.value));

onMounted(() => {
  store.load(feedId.value);
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink :to="store.groupId ? { name: 'feeds', params: { groupId: store.groupId } } : { name: 'groups' }" class="text-decoration-none me-2">
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      <code>{{ store.code }}</code> {{ store.title }}
    </h1>

    <feedStructure />
  </div>
</template>

<style scoped></style>
