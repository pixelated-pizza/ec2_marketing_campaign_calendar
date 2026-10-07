import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import toastr from "toastr";
import "toastr/build/toastr.min.css";
import PrimeVue from 'primevue/config';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import Aura from '@primeuix/themes/aura';
import { definePreset } from '@primeuix/themes';
import { ref } from "vue";
import ganttastic from '@infectoone/vue-ganttastic';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import VueApexCharts from "vue3-apexcharts";

import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";

// PrimeVue Aura with the TailAdmin brand colour as the primary palette
const BrandPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#ecf3ff',
      100: '#dde9ff',
      200: '#c2d6ff',
      300: '#9cb9ff',
      400: '#7592ff',
      500: '#465fff',
      600: '#3641f5',
      700: '#2a31d8',
      800: '#252dae',
      900: '#262e89',
      950: '#161950'
    }
  }
});

const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
  measurementId: "G-BT5TNC3RGC"
};


import '../css/assets/tailwind.css';      
import '../css/assets/styles.scss';       

import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'

dayjs.extend(utc)
dayjs.extend(timezone)

dayjs.tz.setDefault('Australia/Sydney')

export const isPageLoading = ref(false);

router.beforeEach((to, from, next) => {
  isPageLoading.value = true;
  next();
});

router.afterEach(() => {
  setTimeout(() => {
    isPageLoading.value = false;
  }, 200);
});


toastr.options = {
  closeButton: true,
  debug: false,
  newestOnTop: true,
  progressBar: true,
  positionClass: "toast-top-right",
  preventDuplicates: true,
  onclick: null,
  showDuration: "300",
  hideDuration: "1000",
  timeOut: "3000",
  extendedTimeOut: "1000",
  showEasing: "swing",
  hideEasing: "linear",
  showMethod: "fadeIn",
  hideMethod: "fadeOut"
};

const app = createApp(App);

app.config.globalProperties.$backendUrl = import.meta.env.VITE_APP_URL

const firebase = initializeApp(firebaseConfig);

const pinia = createPinia();
app.config.globalProperties.$toastr = toastr;

for (const [name, comp] of Object.entries(ElementPlusIconsVue)) {
  app.component(name, comp);
}

app.use(PrimeVue, {
  theme: {
    preset: BrandPreset,
    options: {
      darkModeSelector: '.app-dark'
    }
  }
}).use(pinia)
  .use(ToastService)
  .use(VueApexCharts)
  .use(ConfirmationService)
  .use(router).mount('#app');

  export const auth = getAuth(firebase);

