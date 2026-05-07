<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useColorMode } from '@vueuse/core'

// VueUse color mode ref
const mode = useColorMode()

// Controls dropdown visibility
const open = ref(false)
function toggleMenu() {
  open.value = !open.value
}

function closeMenu() {
  open.value = false
}
</script>

<template>
  <div class="relative inline-block">
    <button 
      @click="toggleMenu" 
      class="flex items-center justify-center w-10 h-10 rounded-full border border-gray-500 dark:border-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition relative"
      aria-label="Toggle theme"
    >
      <Icon 
        icon="radix-icons:moon" 
        class="h-5 w-5 transition-all duration-300 dark:rotate-0 dark:scale-0 rotate-0 scale-100" 
      />
      <Icon 
        icon="radix-icons:sun" 
        class="absolute h-5 w-5 transition-all duration-300 dark:rotate-0 dark:scale-100 rotate-0 scale-0" 
      />
    </button>

    <div 
      v-show="open" 
      @click.outside="closeMenu" 
      class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 border rounded shadow-md z-50 overflow-hidden"
    >
      <button 
        @click="mode = 'light'; closeMenu()" 
        class="w-full text-left px-3 py-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
      >
        Light
      </button>
      <button 
        @click="mode = 'dark'; closeMenu()" 
        class="w-full text-left px-3 py-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
      >
        Dark
      </button>
      <button 
        @click="mode = 'auto'; closeMenu()" 
        class="w-full text-left px-3 py-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
      >
        System
      </button>
    </div>
  </div>
</template>
