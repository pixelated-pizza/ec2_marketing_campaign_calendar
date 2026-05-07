<template>
  <div class="relative">
    <router-view v-slot="{ Component }">
        <component :is="Component" />
    </router-view>

    <GlobalLoader />
  </div>
</template>

<script setup>
import GlobalLoader from './components/GlobalLoader.vue'
</script>