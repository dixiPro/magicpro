import { createApp } from 'vue';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ConfirmationService from 'primevue/confirmationservice';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const components = [
  //
  ToastService,
  Toast,
  ConfirmDialog,
  Dialog,
  InputText,
];

import MailSystemAdmin from './app/mailSystemAdmin/mailSystemAdmin.vue';
const app = createApp(MailSystemAdmin);

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

app.mount('#mailSystemAdmin');
