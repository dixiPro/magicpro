<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';

// vue-i18n
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { useToast } from 'primevue/usetoast';
const toast = useToast();
import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

import { showSpinner, spinnerHide } from '../Service/spinnerService';
document.spinnerServiceShow = showSpinner;
document.spinnerServiceHide = spinnerHide;

onMounted(() => {
  // глобальные сервисы диалог подтверждения и тосты

  document.showToast = (msg = '', severity = 'success') => {
    const life = severity === 'success' ? 5000 : 60 * 1000;
    toast.add({ severity: severity, detail: msg, life: life });
    if (severity === 'error') {
      console.log(msg);
    }
  };
  document.confirmDialog = async (message) => {
    return new Promise((resolve, reject) => {
      confirm.require({
        message,
        header: '',
        icon: 'fas fa-question',
        acceptLabel: t('yes'),
        rejectLabel: t('no'),
        accept: () => resolve(true),
        reject: () => resolve(false), // или reject(), если хотите ошибку
      });
    });
  };
});
</script>

<template>
  <!-- тосты -->
  <Toast position="top-right"></Toast>
  <!-- Дилог Да Нет -->
  <ConfirmDialog></ConfirmDialog>
</template>
<style scoped></style>
