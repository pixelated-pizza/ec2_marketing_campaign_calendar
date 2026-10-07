<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex items-center gap-3 rounded-lg px-1 py-1 text-gray-700 dark:text-gray-300"
            @click="open = !open"
        >
            <span
                class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-500 dark:bg-brand-500/15 dark:text-brand-400"
            >
                {{ initial }}
            </span>
            <span class="hidden max-w-[160px] truncate text-theme-sm font-medium sm:block">{{ userName || 'Account' }}</span>
            <i class="pi pi-angle-down text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
        </button>

        <div
            v-if="open"
            class="absolute end-0 mt-3 w-[220px] rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="border-b border-gray-200 pb-3 dark:border-gray-800">
                <span class="block truncate text-theme-sm font-medium text-gray-700 dark:text-gray-300">{{ userName }}</span>
                <span v-if="userRole" class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ userRole }}</span>
            </div>

            <button
                type="button"
                class="mt-3 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5"
                @click="openLogoutDialog"
            >
                <i class="pi pi-sign-out"></i>
                <span>Sign out</span>
            </button>
        </div>

        <Dialog
            v-model:visible="showLogoutDialog"
            header="Confirm Logout"
            :modal="true"
            :closable="false"
            :style="{ width: '400px' }"
        >
            <p>Are you sure you want to logout?</p>
            <template #footer>
                <Button label="Cancel" severity="danger" text @click="showLogoutDialog = false" />
                <Button
                    label="Logout"
                    severity="success"
                    :loading="loggingOut"
                    :disabled="loggingOut"
                    @click="confirmLogout"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/js/utils/user.js';
import { logout } from '@/js/api/login_api.js';
import { useUIStore } from '@/js/stores/ui';

const { appContext } = getCurrentInstance();
const $toastr = appContext.config.globalProperties.$toastr;

const router = useRouter();
const userStore = useUserStore();
const ui = useUIStore();

const root = ref(null);
const open = ref(false);
const showLogoutDialog = ref(false);
const loggingOut = ref(false);

const userName = computed(() => userStore.name);
const userRole = computed(() => userStore.role_name);
const initial = computed(() => (userName.value ? userName.value.trim().charAt(0).toUpperCase() : '?'));

function openLogoutDialog() {
    open.value = false;
    showLogoutDialog.value = true;
}

// Logout logic is unchanged from the previous sidebar implementation
async function confirmLogout() {
    loggingOut.value = true;
    ui.showLoader();
    try {
        await logout();
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        router.push('/login');
    } catch (err) {
        console.error(err);
    } finally {
        loggingOut.value = false;
        showLogoutDialog.value = false;
        $toastr.success('You have been logged out.', 'Logout Successful');
        ui.hideLoader();
    }
}

// close the dropdown when clicking outside of it
const onDocumentClick = (e) => {
    if (open.value && root.value && !root.value.contains(e.target)) open.value = false;
};

onMounted(() => document.addEventListener('click', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));
</script>
