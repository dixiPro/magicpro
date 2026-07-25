<script setup>
import { ref } from 'vue';
import MailMessagesTable from './mailMessagesTable.vue';
import MailAddressesTable from './mailAddressesTable.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const tab = ref('messages'); // 'messages' | 'addresses'
</script>

<template>
  <div class="container my-3">
    <!-- разделы верхнего уровня -->
    <div class="btn-group my-3" role="group">
      <button type="button" class="btn btn-sm text-nowrap" :class="tab === 'messages' ? 'btn-primary' : 'btn-outline-primary'" @click="tab = 'messages'">
        {{ t('mail_tab_messages') }}
      </button>
      <button type="button" class="btn btn-sm text-nowrap" :class="tab === 'addresses' ? 'btn-primary' : 'btn-outline-primary'" @click="tab = 'addresses'">
        {{ t('mail_tab_addresses') }}
      </button>
    </div>

    <MailMessagesTable v-if="tab === 'messages'" />
    <MailAddressesTable v-if="tab === 'addresses'" />
  </div>
</template>
