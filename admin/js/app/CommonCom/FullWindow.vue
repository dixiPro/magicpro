<script setup>
// The same slots as ModalWindow, but the whole browser window: no overlay, no
// drag, no close button. For a page that is opened as a window of its own.
</script>

<template>
  <div class="full">
    <div class="full-header">
      <slot name="header" />
    </div>

    <div class="full-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.full {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.full-header {
  flex: 0 0 auto;
  padding: 8px 8px 0;
}

.full-body {
  flex: 1 1 auto;
  padding: 0 8px 8px;
  overflow: auto;
}
</style>
