<template>
  <Transition name="fade">
    <div
      v-if="ui.globalLoading"
      class="fixed inset-0 z-[9999] flex items-center justify-center
             bg-white/70 dark:bg-black/70 backdrop-blur-sm"
    >
      <div class="flex flex-col items-center gap-4">
        <i class="pi pi-spin pi-spinner text-4xl text-primary-500"></i>
        <p class="text-sm font-medium">Loading, please wait...</p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { useUIStore } from '../stores/ui.js';
const ui = useUIStore()
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>