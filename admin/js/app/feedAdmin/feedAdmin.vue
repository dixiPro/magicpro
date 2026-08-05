<script setup>
// Оболочка админки лент. Экран выбирает роутер, здесь только общее:
// контейнер, тосты и диалог подтверждения.
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
</script>

<template>
  <div class="container my-3">
    <RouterView />

    <TosatConfirm></TosatConfirm>
  </div>
</template>

<style scoped></style>
