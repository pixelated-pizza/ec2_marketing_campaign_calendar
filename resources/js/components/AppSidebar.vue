<template>
    <aside
        :class="[
            'fixed start-0 top-0 z-50 flex h-screen flex-col border-e border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900',
            isSidebarWide ? 'w-[290px]' : 'w-[90px]',
            isMobileOpen ? 'translate-x-0' : 'max-xl:-translate-x-full',
            'xl:translate-x-0'
        ]"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
    >
        <!-- Logo -->
        <div :class="['flex items-center pb-6 pt-6', isSidebarWide ? 'justify-start' : 'xl:justify-center']">
            <router-link to="/dashboard" class="flex items-center gap-3">
                <img :src="iconUrl" alt="MarketMap" class="h-10 w-10 shrink-0 rounded-full object-cover" />
                <span v-if="isSidebarWide" class="text-xl font-semibold text-gray-800 dark:text-white/90">MarketMap</span>
            </router-link>
        </div>

        <!-- Menu -->
        <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto">
            <nav class="mb-6">
                <AppMenu />
            </nav>
        </div>
    </aside>
</template>

<script setup>
import AppMenu from './AppMenu.vue';
import { useLayout } from '@/js/layouts/composables/layout';

const { layoutState, isSidebarWide, isMobileOpen, isDesktop, setSidebarHovered } = useLayout();

const iconUrl = `${import.meta.env.VITE_BASE_URL || ''}/app_icon.png`;

const onMouseEnter = () => {
    if (isDesktop() && layoutState.staticMenuInactive) setSidebarHovered(true);
};

const onMouseLeave = () => setSidebarHovered(false);
</script>
