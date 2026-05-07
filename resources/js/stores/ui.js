// stores/ui.js
import { defineStore } from 'pinia'
import { ref, onMounted } from 'vue'

export const useUIStore = defineStore('ui', () => {
    const globalLoading = ref(false)
    const isDark = ref(false)

    const showLoader = () => globalLoading.value = true
    const hideLoader = () => globalLoading.value = false

    const initDarkMode = () => {
        const observer = new MutationObserver(() => {
            isDark.value = document.documentElement.classList.contains("app-dark");
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"]
        });
        isDark.value = document.documentElement.classList.contains("app-dark");
    }

    return { globalLoading, showLoader, hideLoader, isDark, initDarkMode }
})