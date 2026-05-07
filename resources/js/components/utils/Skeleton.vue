<template>
  <div
    class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gray-800 animate-pulse rounded-md z-10"
  >
    <p class="text-gray-400 text-lg mt-4">{{ message }}</p>

    <div
      v-for="(bar, i) in bars"
      :key="i"
      class="bg-gray-700 rounded"
      :class="bar.class"
      :style="{ width: bar.width, height: bar.height }"
    ></div>

    <div
      v-if="showArea"
      class="w-full h-full bg-gray-900/50 mt-4 rounded"
    ></div>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: 'Loading...',
  },
  showArea: {
    type: Boolean,
    default: true,
  },
  bars: {
    type: Array,
    default: () => [
      { width: '75%', height: '1.5rem' },
      { width: '50%', height: '1.5rem' },
    ],
  },
})
</script>
