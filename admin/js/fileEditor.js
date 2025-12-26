import { createApp } from 'vue';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';

import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ConfirmationService from 'primevue/confirmationservice';
import Dialog from 'primevue/dialog';
import Splitter from 'primevue/splitter';
import SplitterPanel from 'primevue/splitterpanel';
import ContextMenu from 'primevue/contextmenu';
import Drawer from 'primevue/drawer';
import FileUpload from 'primevue/fileupload';
import ToggleSwitch from 'primevue/toggleswitch';

// локальные компоненты
import FileEditor from './app/FileEditor/FileEditor.vue';

const components = [
  //
  ToastService,
  Toast,
  ConfirmDialog,
  Dialog,
  Splitter,
  SplitterPanel,
  ContextMenu,
  Drawer,
  FileUpload,
  ToggleSwitch,
];

const app = createApp(FileEditor);

// регистрац компонентов
components.forEach((component) => {
  app.component(component.name, component);
});

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      cssLayer: { name: 'primevue', order: 'theme, base, primevue' },
    },
  },
});

app.use(ConfirmationService);
app.use(ToastService);

import { createPinia } from 'pinia';
app.use(createPinia());

import i18n from './app/CommonCom/translate';
app.use(i18n);

app.mount('#file_editor');

import * as ApiCallFunctions from './app/apiCall.js';
Object.assign(globalThis, ApiCallFunctions);
