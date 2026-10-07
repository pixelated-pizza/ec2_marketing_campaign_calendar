<template>
    <header
        class="sticky top-0 z-30 flex h-[64px] w-full shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 md:px-6"
    >
        <div class="flex items-center gap-3">
            <button
                type="button"
                aria-label="Toggle sidebar"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5"
                @click="toggleMenu"
            >
                <i :class="['pi', isMobileOpen ? 'pi-times' : 'pi-bars']"></i>
            </button>

            <router-link to="/dashboard" class="text-lg font-semibold text-gray-800 dark:text-white/90 xl:hidden">
                MarketMap
            </router-link>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                aria-label="Toggle dark mode"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5"
                @click="toggleDarkMode"
            >
                <i :class="['pi', isDarkTheme ? 'pi-moon' : 'pi-sun']"></i>
            </button>

            <UserMenu />
        </div>
    </header>
</template>

<script setup>
import UserMenu from './UserMenu.vue';
import { useLayout } from '@/js/layouts/composables/layout';

const { toggleMenu, toggleDarkMode, isDarkTheme, isMobileOpen } = useLayout();
</script>
