<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
  el: { type: Object, required: true },
  viewFull: Boolean,
  viewSize: Number,
  path: String,
});

const fileSize = ref('');

function clacFileSize(params) {
  if (props.el.type !== 'file') return 'dir';
  let i = Number(props.el.size);

  if (i > 1000000) {
    return (i / 1000000).toFixed(1) + ' m';
  }
  if (i > 1000) {
    return (i / 1000).toFixed(1) + ' k';
  }
  return i + ' b';
}

onMounted(() => {
  fileSize.value = clacFileSize();
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
</script>

<template>
  <div v-if="!viewFull" class="d-flex my-1">
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
      <span class="ms-1 file-size" v-text="fileSize"></span>
    </div>
  </div>

  <div v-else class="thumb" :style="`width:${viewSize}px;`">
    <div class="thumb-pic" v-if="el.isImage">
      <img :src="path + el.name + '?' + el.date" :style="calcImgStyle(el.width, el.height)" />
      <div class="file-name">{{ el.name }}<br />({{ el.width }}x{{ el.height }})</div>
      <div class="file-name file-size"><span v-text="fileSize"></span></div>
    </div>

    <div class="thumb-file" v-else>
      <i class="far fa-file" style="font-size: 300%; color: #777"></i>
      <!-- <div class="fs-5 d-inline-block bg-dark text-light rounded px-2">{{ ext }}</div> -->
      <div style="word-break: break-all">{{ el.name }}</div>
      <div class="text-center file-size"><span v-text="fileSize"></span></div>
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
.file-size {
  font-size: 80%;
}
</style>
