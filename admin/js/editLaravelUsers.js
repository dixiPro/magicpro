import { createApp } from 'vue';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
// import "primeicons/primeicons.css";

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
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

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
  InputText,
];

import LaravelUsersPage from './app/EditLaravelUsers/LaravelUsersPage.vue';
const app = createApp(LaravelUsersPage);

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

import i18n from './app/CommonCom/translate.js';
app.use(i18n);

app.mount('#edit_users');
