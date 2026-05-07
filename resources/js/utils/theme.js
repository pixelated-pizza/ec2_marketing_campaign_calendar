import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  const darkMode = ref(true); 
  function toggleDark() {
    darkMode.value = !darkMode.value;
    applyTheme();
  }

  function applyTheme() {
    if (darkMode.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }

  applyTheme();

  return { darkMode, toggleDark };
});
