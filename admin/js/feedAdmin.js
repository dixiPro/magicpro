import { createApp } from 'vue';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ConfirmationService from 'primevue/confirmationservice';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';

const components = [
  //
  ToastService,
  Toast,
  ConfirmDialog,
  Dialog,
  InputText,
];

import FeedAdmin from './app/feedAdmin/feedAdmin.vue';
const app = createApp(FeedAdmin);

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

// экраны разведены по адресам через хеш: #/, #/group/2, #/feed/3
import router from './app/feedAdmin/router.js';
app.use(router);

app.mount('#feedAdmin');
