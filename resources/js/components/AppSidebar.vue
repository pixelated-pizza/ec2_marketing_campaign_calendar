<template>
    <aside :class="['layout-sidebar', sidebarClass]">
        <div>
            <!-- Logo -->
            <div class="p-4 flex items-center border-b border-gray-700">
                <div class="flex-1 flex justify-center">
                    <img
                        :src="`${import.meta.env.VITE_BASE_URL}/favicon.ico`"
                        alt="MarketMap"
                        class="w-24 h-24 rounded-full"
                    />
                </div>
            </div>
            <AppMenu />
        </div>

        <!-- User / Logout -->
        <div
            class="border-t border-gray-700 p-4 flex items-center gap-2 text-sm font-medium text-gray-300"
        >
            <i class="pi pi-user text-gray-400"></i>
            <span
                class="font-semibold dark:text-white text-gray-900 truncate"
                >{{ userName }}</span
            >
            <Button
                icon="pi pi-sign-out"
                rounded
                outlined
                severity="contrast"
                class="hover:text-red-400 transition ml-auto"
                @click="showLogoutDialog = true"
            />
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
                <Button
                    label="Cancel"
                    severity="danger"
                    text
                    @click="showLogoutDialog = false"
                />
                <Button
                    label="Logout"
                    severity="success"
                    :loading="loggingOut"
                    :disabled="loggingOut"
                    @click="confirmLogout"
                />
            </template>
        </Dialog>
    </aside>
</template>

<script setup>
import { ref, computed, onMounted,  } from "vue";
import AppMenu from "./AppMenu.vue";
import Dialog from "primevue/dialog";
import { useUserStore } from "@/js/utils/user.js";
import Button from "primevue/button";
import { logout } from "@/js/api/login_api.js";
import { useRouter } from "vue-router";
import { useUIStore } from "@/js/stores/ui";
import { getCurrentInstance } from "vue";
const { appContext } = getCurrentInstance();

const $toastr = appContext.config.globalProperties.$toastr;

const router = useRouter();
const userStore = useUserStore();
const ui = useUIStore();

const userName = computed(() => userStore.name);

const showLogoutDialog = ref(false);
const loggingOut = ref(false);

async function confirmLogout() {
  loggingOut.value = true;
  ui.showLoader();
  try {
    await logout();
    localStorage.removeItem('auth_token');
    localStorage.removeItem("auth_user");
    router.push('/login'); 
  } catch (err) {
    console.error(err);
  } finally {
    loggingOut.value = false;
    showLogoutDialog.value = false;
    $toastr.success("You have been logged out.", "Logout Successful");
    ui.hideLoader();
  }
    
}

const sidebarClass = computed(() => [
    "text-gray-200 shadow-lg flex flex-col justify-between h-full overflow-hidden",
]);

onMounted(async () => {
    await userStore.fetchUser();
});
</script>
