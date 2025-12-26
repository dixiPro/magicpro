<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';
import { useArticleStore } from '../store';
const store = useArticleStore();

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const newValidName = ref('');
const swapForName = ref('');
</script>

<template>
  <Drawer v-model:visible="store.statusAddPannel" :header="t('route_params')" position="right" style="width: 700px">
    <div class="">
      <div class="my-2 d-flex">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.menuOn" />
        </div>
        <div class="ms-2">{{ t('menu_on') }}</div>
      </div>
      <div class="d-flex my-2">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.routeParams.useController" />
        </div>
        <div class="ms-2">
          {{ t('use_controller') }}
        </div>
      </div>
      <div class="my-2 d-flex">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.isRoute" />
        </div>
        <div class="ms-2">{{ t('is_route') }}</div>
      </div>

      <div v-if="store.article.isRoute">
        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.adminOnly" />
          </div>
          <div class="ms-2">{{ t('only_admin') }}</div>
        </div>

        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.utmParamsEnable" />
          </div>
          <div class="ms-2">{{ t('utm_enable') }}</div>
        </div>

        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.getEnable" />
          </div>
          <div class="ms-2">
            <div>{{ t('use_getpatams') }}</div>
            <div v-if="store.article.routeParams.getEnable">
              <div v-if="store.article.routeParams.keysArr.length === 0">{{ t('available_all_params') }}</div>
              <div v-else="store.article.routeParams.keysArr.length === 0">{{ t('available_selected_params') }}</div>
              <!--  -->
              <div class="d-table">
                <div class="d-table-row" v-for="(value, index) in store.article.routeParams.keysArr" :key="index">
                  <div class="d-table-cell px-1 py-1 align-middle" style="min-width: 300px">
                    <input class="form-control form-control-sm" type="text" v-model="store.article.routeParams.keysArr[index]" />
                  </div>
                  <div class="d-table-cell px-1 py-1">
                    <i
                      class="fas fa-arrow-circle-down"
                      v-show="store.article.routeParams.keysArr.length - 1 > index && store.article.routeParams.bindKeys"
                      @click="
                        store.article.routeParams.swap = store.article.routeParams.keysArr[index + 1];
                        store.article.routeParams.keysArr[index + 1] = store.article.routeParams.keysArr[index];
                        store.article.routeParams.keysArr[index] = store.article.routeParams.swap;
                        delete store.article.routeParams.swap;
                      "
                    ></i>
                  </div>
                  <div class="d-table-cell px-1 py-1">
                    <i
                      class="fas fa-arrow-circle-up"
                      v-if="index > 0 && store.article.routeParams.bindKeys"
                      @click="
                        swapForName = store.article.routeParams.keysArr[index - 1];
                        store.article.routeParams.keysArr[index - 1] = store.article.routeParams.keysArr[index];
                        store.article.routeParams.keysArr[index] = swapForName;
                      "
                    ></i>
                  </div>

                  <div class="d-table-cell px-1 py-1">
                    <i class="fas fa-trash" @click="store.article.routeParams.keysArr.splice(index, 1)"></i>
                  </div>
                </div>
              </div>
              <div class="mt-2">{{ t('add_route_params') }}</div>
              <div class="row my-2">
                <div class="col-9">
                  <input class="form-control form-control-sm" type="text" v-model="newValidName" />
                </div>
                <div class="col-1">
                  <span
                    v-if="newValidName.trim() != ''"
                    class="fas fa-plus"
                    @click="
                      store.article.routeParams.keysArr.push(newValidName);
                      newValidName = '';
                    "
                  ></span>
                  <span v-else class="fas fa-plus"></span>
                </div>
              </div>

              <div class="d-flex my-3" v-if="store.article.routeParams.keysArr.length > 0">
                <div class="nowrap">
                  <ToggleSwitch v-model="store.article.routeParams.bindKeys" />
                </div>
                <div class="ms-2">
                  <div>{{ t('bind_params') }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- готовый -->
        <div class="my-2">{{ t('ready_url_rout') }}</div>
        <div>
          <strong>
            <span>/{{ store.article.name }}</span>
            <span v-if="store.article.routeParams.getEnable">
              <span v-if="store.article.routeParams.keysArr.length == 0">/name1/value1/.../nameN/valueN</span>
              <span v-else="store.article.routeParams.keysArr.length == 0">
                <span v-for="value in store.article.routeParams.keysArr">
                  <span v-if="!store.article.routeParams.bindKeys">/{{ value }}</span
                  >/value_{{ value }}</span
                >
              </span>
            </span>

            <span v-if="store.article.routeParams.utmParamsEnable">?utm_rametrs...</span>
          </strong>
        </div>
      </div>
    </div>

    <button class="btn btn-sm btn-primary" @click="store.saveRec()">{{ t('save') }}</button>

    <pre>
        {{ store.article }}
    </pre>
  </Drawer>
</template>
<style>
.p-drawer-header {
  padding-bottom: 0 !important;
  padding-top: 10px;
}
</style>
