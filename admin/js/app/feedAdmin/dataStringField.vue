<script setup>
import { ref, computed, watch, onMounted } from 'vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Строковое поле записи.
 *
 * Слот в базе — varchar(255), а в одну строку инпута столько не помещается:
 * конец текста уезжает за край, и оператор правит вслепую. Поэтому textarea,
 * которая растёт под содержимое, а не полоса с прокруткой внутри.
 *
 * Счётчик появляется у края, а не над каждым полем: пока до потолка далеко,
 * цифры только мешают. Перебор не запрещается здесь — база обрежет хвост сама, —
 * а показывается: красная рамка и счётчик поверх лимита.
 *
 * `max` нулём выключает счётчик: у строки внутри `__data` потолка нет, там json.
 */
const props = defineProps({
  max: { type: Number, default: 255 },
});

const value = defineModel({ type: String, default: '' });

const area = ref(null);

const length = computed(() => (value.value ?? '').length);
const over = computed(() => props.max > 0 && length.value > props.max);
const counter = computed(() => props.max > 0 && length.value > props.max - 30);

/**
 * Высота по содержимому.
 *
 * Сначала `auto`, потом `scrollHeight`: без сброса высота умеет только расти —
 * `scrollHeight` у растянутого поля равен его же высоте, и удаление строк её
 * не вернёт.
 */
function grow() {
  const el = area.value;

  if (!el) return;

  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

// post: значение уже в DOM, иначе меряется предыдущий текст
watch(value, grow, { flush: 'post' });

onMounted(grow);
</script>

<template>
  <textarea
    ref="area"
    v-model="value"
    rows="1"
    class="form-control form-control-sm"
    :class="{ 'border-danger': over }"
    style="overflow: hidden; resize: none"
  ></textarea>

  <div v-if="counter" class="small" :class="over ? 'text-danger' : 'text-muted'">
    {{ length }} / {{ max }}
    <span v-if="over">· {{ t('feed_string_over') }}</span>
  </div>
</template>

<style scoped></style>
