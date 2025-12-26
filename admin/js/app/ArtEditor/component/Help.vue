<script setup>
import { useArticleStore } from '../store';
const store = useArticleStore();

import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
  <Dialog v-model:visible="store.statusHelp" header="Справка" modal class="w-50">
    <ul class="list-unstyled m-0">
      <li>
        <kbd>Ctrl+S</kbd> <span class="ms-2">{{ t('save') }}</span>
      </li>
      <li>
        <kbd>Shift_</kbd><span class="ms-2">{{ t('blade_snippet') }}</span>
      </li>
      <li>
        <kbd>Alt+1</kbd><span class="ms-2">{{ t('go_selected') }}</span>
      </li>
      <li>
        <kbd>Alt+/</kbd><span class="ms-2">{{ t('blade_comment') }}</span>
      </li>
    </ul>
  </Dialog>
</template>
