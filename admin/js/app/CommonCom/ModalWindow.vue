<script setup>
import { ref, onBeforeUnmount } from 'vue';

const visible = defineModel('visible');
const emit = defineEmits(['close']);

const props = defineProps({
  header: { type: String, default: '' },
  zIndex: { type: Number, default: 2000 },

  initialWidth: { type: [String, Number], default: 800 },
  initialHeight: { type: [String, Number], default: 600 },
});

// стартовый размер из пропсов
const width = ref(Number(props.initialWidth));
const height = ref(Number(props.initialHeight));

const left = ref(200);
const top = ref(150);

// --- DRAG ---
let drag = false;
let dragStartX = 0;
let dragStartY = 0;
let startLeft = 0;
let startTop = 0;

function onDragDown(e) {
  e.preventDefault();
  drag = true;
  dragStartX = e.clientX;
  dragStartY = e.clientY;
  startLeft = left.value;
  startTop = top.value;

  window.addEventListener('mousemove', onDragMove);
  window.addEventListener('mouseup', onDragUp);
}

function onDragMove(e) {
  if (!drag) return;
  left.value = startLeft + (e.clientX - dragStartX);
  top.value = startTop + (e.clientY - dragStartY);
}

function onDragUp() {
  drag = false;
  window.removeEventListener('mousemove', onDragMove);
  window.removeEventListener('mouseup', onDragUp);
}

// --- RESIZE ---
let resizing = false;
let startX = 0;
let startY = 0;
let startW = 0;
let startH = 0;

const minW = 200;
const minH = 150;

function onResizeDown(e) {
  e.stopPropagation();
  e.preventDefault();

  resizing = true;
  startX = e.clientX;
  startY = e.clientY;
  startW = width.value;
  startH = height.value;

  window.addEventListener('mousemove', onResizeMove);
  window.addEventListener('mouseup', onResizeUp);
}

function onResizeMove(e) {
  if (!resizing) return;
  width.value = Math.max(minW, startW + (e.clientX - startX));
  height.value = Math.max(minH, startH + (e.clientY - startY));
}

function onResizeUp() {
  resizing = false;
  window.removeEventListener('mousemove', onResizeMove);
  window.removeEventListener('mouseup', onResizeUp);
}

function close() {
  visible.value = false;
  emit('close');
}

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onResizeMove);
  window.removeEventListener('mouseup', onResizeUp);
  window.removeEventListener('mousemove', onDragMove);
  window.removeEventListener('mouseup', onDragUp);
});

const maximized = ref(false);
let prev = { left: 0, top: 0, width: 0, height: 0 };

function toggleMax() {
  if (!maximized.value) {
    prev = {
      left: left.value,
      top: top.value,
      width: width.value,
      height: height.value,
    };
    left.value = 0;
    top.value = 0;
    width.value = window.innerWidth;
    height.value = window.innerHeight;
    maximized.value = true;
  } else {
    left.value = prev.left;
    top.value = prev.top;
    width.value = prev.width;
    height.value = prev.height;
    maximized.value = false;
  }
}
</script>

<template>
  <div v-if="visible" class="overlay" :style="{ zIndex: props.zIndex }">
    <div
      class="win"
      :style="{
        width: width + 'px',
        height: height + 'px',
        left: left + 'px',
        top: top + 'px',
        zIndex: props.zIndex + 1,
      }"
    >
      <div class="header" @mousedown="onDragDown" @dblclick="toggleMax">
        <div class="header-text">
          <slot name="header">
            {{ props.header }}
          </slot>
        </div>
        <button class="close" @click="close">×</button>
      </div>

      <div class="body">
        <slot />
      </div>

      <div class="resize-br" @mousedown.stop="onResizeDown"></div>
    </div>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
}

.win {
  position: absolute;
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.header {
  height: 32px;
  background: #e5e5e5;
  display: flex;
  align-items: center;
  padding: 0 8px;
  cursor: move;
  user-select: none;
}

.header-text {
  flex: 1;
  font-size: 14px;
  line-height: 1;
  color: #000;
}

.close {
  margin-left: 20px;
  border: none;
  background: none;
  font-size: 28px;
  cursor: pointer;
}

.body {
  padding: 16px;
  width: 100%;
  height: calc(100% - 32px);
  box-sizing: border-box;
  overflow: auto;
}

.resize-br {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 16px;
  height: 16px;
  cursor: nwse-resize;
}

.resize-br::after {
  content: '';
  position: absolute;
  right: 3px;
  bottom: 3px;
  width: 10px;
  height: 10px;
  border-right: 2px solid #888;
  border-bottom: 2px solid #888;
  pointer-events: none;
}
</style>
