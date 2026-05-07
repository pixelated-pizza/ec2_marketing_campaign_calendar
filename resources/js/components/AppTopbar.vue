<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
        <i class="pi pi-bars"></i>
      </button>
      <router-link to="/" class="layout-topbar-logo">
        <span>MarketMap</span>
      </router-link>
    </div>

    <div class="layout-topbar-actions">
      <div class="layout-config-menu">
        <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
          <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
        </button>

        <!-- <div class="relative">
          <Button
            icon="pi pi-bell"
            rounded
            outlined
            severity="contrast"
            class="layout-topbar-action hover:text-yellow-400 transition"
            @click="notificationsOpen = !notificationsOpen"
          />

          <span
            v-if="newNotifications.length"
            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5"
          >
            {{ newNotifications.length }}
          </span>
        </div> -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import { useLayout } from '@/js/layouts/composables/layout';

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();

const notificationsOpen = ref(false);
const newNotifications = ref([]);
</script>
