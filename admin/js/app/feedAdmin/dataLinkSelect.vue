<script setup>
import { ref, watch } from 'vue';
import { apiFeed } from './api.js';

/**
 * A record of a reference feed, picked by its name.
 *
 * A link field stores the id of the target record, and a person knows the
 * target by its title, not by a number: the list shows the titles and gives
 * back the id.
 *
 * Read with itemsList and not itemsLookup: the lookup stops at twenty, and a
 * reference feed such as the types of a site easily has more. The order is the
 * own order of that feed, so it is set where the feed itself is sorted.
 */
const props = defineProps({
  // { feed_id, display_code } from the schema of the link field
  relation: { type: Object, default: null },
});

const value = defineModel({ type: [String, Number], default: '' });

// as many as one page of itemsList gives: a reference feed is short
const PER_PAGE = 250;

const options = ref([]);

async function load() {
  options.value = [];

  if (!props.relation?.feed_id) return;

  try {
    // the schema keeps the column of the title, the record comes with codes
    const target = await apiFeed({ command: 'feedGet', id: props.relation.feed_id });

    const code = (target.schema?.fields ?? []).find((field) => field.column === props.relation.display_code)?.code ?? '';

    const data = await apiFeed({
      command: 'itemsList',
      feedId: props.relation.feed_id,
      page: 1,
      perPage: PER_PAGE,
      orderBy: '__position',
      direction: 'asc',
    });

    options.value = data.items.map((item) => ({ id: item.id, text: (code && item.fields[code]) || '#' + item.id }));
  } catch (error) {}
}

watch(() => props.relation?.feed_id, load, { immediate: true });
</script>

<template>
  <select v-model="value" class="form-select form-select-sm w-auto">
    <option value="">—</option>
    <option v-for="option in options" :key="option.id" :value="option.id">{{ option.text }}</option>
  </select>
</template>
