<template>
    <div class="flex h-screen items-center justify-center bg-gray-100 dark:bg-gray-950">
        <div class="bg-white dark:bg-gray-900 p-8 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-center">
                Reset Password
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <InputText
                        v-model="email"
                        placeholder="Enter your email"
                        fluid
                    />
                </div>

                <Button
                    label="Send Reset Link"
                    class="w-full"
                    :loading="loading"
                    @click="handleReset"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, getCurrentInstance } from "vue";
import { useRouter } from "vue-router";
import { sendPasswordResetEmail } from "firebase/auth";
import { auth } from "@/js/app.js";
import InputText from "primevue/inputtext";
import Button from "primevue/button";

const email = ref("");
const loading = ref(false);
const router = useRouter();
const { appContext } = getCurrentInstance();
const $toastr = appContext.config.globalProperties.$toastr;

const handleReset = async () => {
    if (!email.value) {
        $toastr.error("Please enter your email");
        return;
    }

    loading.value = true;
    try {
        await sendPasswordResetEmail(auth, email.value);

        $toastr.success("Password reset email sent!");
        router.push("/");
    } catch (error) {
        const message = error.message || "Failed to send reset email";
        $toastr.error(message);
    } finally {
        loading.value = false;
    }
};
</script>