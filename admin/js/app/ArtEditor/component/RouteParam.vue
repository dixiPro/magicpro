<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';
import { useArticleStore } from '../store';
const store = useArticleStore();

const newValidName = ref('');
const swapForName = ref('');
</script>

<template>
  <Drawer v-model:visible="store.statusAddPannel" header="Параметры" position="right" style="width: 600px">
    <div class="">
      <div class="my-2 d-flex">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.menuOn" />
        </div>
        <div class="ms-2">menu</div>
      </div>
      <div class="d-flex my-2">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.routeParams.useController" />
        </div>
        <div class="ms-2">
          Испольовать контроллер
          <div class="small">Если нет, то будет вызываться просто вьюха</div>
        </div>
      </div>
      <div class="my-2 d-flex">
        <div class="nowrap">
          <ToggleSwitch v-model="store.article.isRoute" />
        </div>
        <div class="ms-2">route</div>
      </div>

      <div v-if="store.article.isRoute">
        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.adminOnly" />
          </div>
          <div class="ms-2">Только Админ (для отладки)</div>
        </div>

        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.utmParamsEnable" />
          </div>
          <div class="ms-2">Разрешить Utm ?=utm...</div>
        </div>

        <div class="d-flex my-2">
          <div class="nowrap">
            <ToggleSwitch v-model="store.article.routeParams.getEnable" />
          </div>
          <div class="ms-2">
            <div>Использовать GET параметры</div>
            <div v-if="store.article.routeParams.getEnable">
              <div class="mt-2">Допустимые параметры</div>
              <div class="small">Если пусто то все</div>
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

              <div class="d-flex my-2" v-if="store.article.routeParams.keysArr.length > 0">
                <div class="nowrap">
                  <ToggleSwitch v-model="store.article.routeParams.bindKeys" />
                </div>
                <div class="ms-2">
                  <div>Привязывать параметры</div>
                  <div class="small">Только для параметорв в / /</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5">
        <button class="btn btn-success btn-sm" @click="store.getController()">Обычный контроллер</button>
      </div>
      <div class="my-2">
        <button class="btn btn-success btn-sm" @click="store.getLiveWareController()">LiveWare контроллер</button>
      </div>

      <div class="my-2">
        <a :href="'http://mpro2.test/a_dmin/api/exportArticle?id=' + store.article.id" class="btn btn-success btn-sm"> Экспорт </a>
      </div>
    </div>

    <pre>
        {{ store.article }}
    </pre>
  </Drawer>
</template>
