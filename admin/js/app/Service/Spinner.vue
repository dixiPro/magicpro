<template>
  <div class="spinner"></div>
</template>

<script setup>
/* пусто */
</script>

<style>
.spinner {
  position: fixed;
  z-index: 4000;
  width: 30px;
  height: 30px;
  right: 20px;
  top: 10px;
  border: 6px solid #05f111;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
