<template>
    <div class="h-screen overflow-hidden bg-gray-50 font-outfit text-gray-800 dark:bg-gray-900 dark:text-gray-100 xl:flex">
        <AppSidebar />

        <!-- mobile backdrop -->
        <div v-if="isMobileOpen" class="fixed inset-0 z-40 bg-gray-900/50 xl:hidden" @click="hideMobileMenu" />

        <div
            class="flex h-screen min-w-0 flex-1 flex-col overflow-y-auto transition-[margin] duration-300 ease-in-out"
            :class="layoutState.staticMenuInactive ? 'xl:ms-[90px]' : 'xl:ms-[290px]'"
        >
            <AppTopbar />

            <main class="w-full flex-1 p-4 pb-20 md:p-6 md:pb-6">
                <router-view v-slot="{ Component }">
                    <component :is="Component" />
                </router-view>
            </main>
        </div>
    </div>

    <Toast />
</template>

<script setup>
import { onMounted } from 'vue';
import { useLayout } from '@/js/layouts/composables/layout';
import { useUserStore } from '@/js/utils/user.js';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const { layoutState, isMobileOpen, hideMobileMenu } = useLayout();

const userStore = useUserStore();

onMounted(async () => {
    await userStore.fetchUser();
});
</script>
