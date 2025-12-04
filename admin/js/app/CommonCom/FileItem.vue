<script setup>
import { computed } from 'vue';

const props = defineProps({
  el: { type: Object, required: true },
  viewFull: Boolean,
  viewSize: Number,
  path: String,
  onRightClick: Function,
});

const ext = computed(() => {
  const n = props.el.name || '';
  return n.includes('.') ? n.split('.').pop().toLowerCase() : '';
});

function calcImgStyle(x, y) {
  if (props.viewSize > x && props.viewSize > y) {
    return `width: ${x} px; height:  ${y} px;`;
  }

  if (x >= y) {
    return 'width:' + (props.viewSize - 2) + 'px;';
  } else {
    return 'height:' + (props.viewSize - 2) + 'px;';
  }
}
function getExt(name) {
  return name.split('.').pop().toLowerCase();
}
</script>

<template>
  <div v-if="!viewFull" class="d-flex my-1" @contextmenu.prevent="onRightClick($event, el)">
    <div class="me-2 icon">
      <i
        :class="{
          'far fa-file-image': el.isImage,
          'far fa-file': !el.isImage,
        }"
      ></i>
    </div>

    <div>
      {{ el.name }}
      <span v-if="el.isImage">
        <span class="ms-1" style="font-size: 85%">({{ el.width }}x{{ el.height }})</span>
      </span>
    </div>
  </div>

  <div v-else class="thumb" :style="`width:${viewSize}px;`" @contextmenu.prevent="onRightClick($event, el)">
    <div class="thumb-pic" v-if="el.isImage">
      <img :src="path + el.name + '?' + el.date" :style="calcImgStyle(el.width, el.height)" />
      <div class="file-name">{{ el.name }}<br />({{ el.width }}x{{ el.height }})</div>
    </div>

    <div class="thumb-file" v-else>
      <i class="far fa-file" style="font-size: 300%; color: #777"></i>
      <!-- <div class="fs-5 d-inline-block bg-dark text-light rounded px-2">{{ ext }}</div> -->
      <div style="word-break: break-all">{{ el.name }}</div>
    </div>
  </div>
</template>
<style>
.thumb {
  margin: 10px;
  position: relative;
  cursor: pointer;
  border: 1px solid #ddd;
}

.thumb-pic {
  /* margin: 10px; */
  position: relative;
}

.thumb-file {
  position: relative;
  padding: 12px 8px;
  border-radius: 2px;
  height: 100%;
  font-size: 16px;
}

.thumb-pic .file-name {
  font-size: 12px;
  padding: 2px;
  line-height: 120%;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
