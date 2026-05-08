<template>
    <div class="flex h-screen w-screen overflow-hidden bg-gray-50 dark:bg-gray-950 text-gray-800 dark:text-gray-100 font-sans">
        <div class="hidden lg:flex w-1/2 relative items-center justify-center bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 dark:from-slate-900 dark:via-gray-900 dark:to-black overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.15),transparent_70%)] animate-pulse"></div>

            <div class="relative z-10 text-center px-12">
                <div class="ecommerce-world mx-auto mb-12">
                    <div class="globe"></div>
                    <i class="pi pi-shopping-cart float-icon" style="--i: 1"></i>
                    <i class="pi pi-box float-icon" style="--i: 2"></i>
                    <i class="pi pi-truck float-icon" style="--i: 3"></i>
                    <i class="pi pi-globe float-icon" style="--i: 4"></i>
                    <i class="pi pi-store float-icon" style="--i: 5"></i>
                </div>
                <h1 class="text-5xl font-extrabold tracking-tight text-white mb-4">
                    MillsBrands&copy;
                </h1>
                <p class="text-indigo-100 text-lg font-light max-w-sm mx-auto">
                    The world of seamless eCommerce, managed in one powerful calendar.
                </p>
            </div>
        </div>

        <div class="flex w-full lg:w-1/2 items-center justify-center p-8 bg-white dark:bg-gray-900 transition-colors duration-300">
            <div class="w-full max-w-md space-y-8">
                <div class="text-center">
                    <img :src="appIconUrl" alt="Logo" class="w-20 h-20 mx-auto mb-6 rounded-2xl shadow-lg" />
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Marketing Campaign Calendar</p>
                </div>

                <Button 
                    label="Continue with Google" 
                    icon="pi pi-google" 
                    outlined 
                    class="w-full py-3 hover:bg-gray-50 dark:hover:bg-gray-800" 
                    @click="handleFirebaseLogin" 
                />

                <div class="relative flex items-center py-2">
                    <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                    <span class="flex-shrink mx-4 text-gray-400 text-xs uppercase tracking-widest font-semibold">Or email login</span>
                    <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Email Address</label>
                        <InputText 
                            id="email" 
                            v-model="email" 
                            type="email"
                            required
                            placeholder="name@company.com" 
                            class="w-full p-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800" 
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Password</label>
                            <router-link to="/forgot-password" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Forgot password?</router-link>
                        </div>
                        <Password 
                            id="password" 
                            v-model="password" 
                            :feedback="false" 
                            toggleMask 
                            required
                            placeholder="••••••••" 
                            class="w-full"
                            inputClass="w-full p-3 border border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model="rememberMe" :binary="true" inputId="remember" />
                        <label for="remember" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer">Remember this device</label>
                    </div>

                    <Button 
                        type="submit" 
                        label="Sign In" 
                        icon="pi pi-lock" 
                        class="w-full p-3 font-bold mt-2" 
                        :loading="loading" 
                    />
                </form>

                <p class="text-center text-sm text-gray-500 mt-8">
                    Don't have an account? <span class="text-indigo-600 dark:text-indigo-400 font-medium cursor-pointer hover:underline">Contact Admin</span>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, getCurrentInstance } from "vue";
import { useRouter } from "vue-router";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import { login, loginWithFirebase } from "@/js/api/login_api.js";
import { auth } from "@/js/app.js";
import { signInWithEmailAndPassword } from "firebase/auth";
import { GoogleAuthProvider, signInWithPopup } from "firebase/auth";
import { useUIStore } from "@/js/stores/ui.js";
const ui = useUIStore();
const loading = ref(false);
const email = ref("");
const password = ref("");
const rememberMe = ref(false);
const router = useRouter();
const { appContext } = getCurrentInstance();
const $toastr = appContext.config.globalProperties.$toastr;
const appIconUrl = `${import.meta.env.VITE_BASE_URL || ''}/app_icon.png`;

const handleLogin = async () => {
    loading.value = true;
    ui.showLoader();

    try {
        const response = await login({
            email: email.value,
            password: password.value,
            remember: rememberMe.value,
        });

        // Save token
        if (response.token) localStorage.setItem("auth_token", response.token);
        if (response.user)  localStorage.setItem("auth_user", JSON.stringify(response.user));

        $toastr.success("Welcome back!", "Login Successful");
        router.push("/dashboard");

        // Prefetch common pages
        Promise.all([
            import("@/js/components/campaigns/Campaigns.vue"),
            import("@/js/components/WebsiteCampaigns.vue"),
            import("@/js/components/WebsiteSaleDetails.vue"),
            import("@/js/components/WebsitePromos.vue"),
        ]);
    } catch (err) {
        let message;

        // backend returned 422 for invalid email domain
        if (err.response?.status === 422) {
            message = err.response.data?.message || "Invalid email";
        } else if (err.response?.status === 401) {
            message = err.response.data?.message || "Invalid credentials";
        } else {
            message = err.message || "Login failed";
        }

        $toastr.error(message, "Login Failed");
    } finally {
        ui.hideLoader();
        loading.value = false;
    }
};

const handleFirebaseLogin = async () => {
    loading.value = true;
    ui.showLoader();

    try {
        const provider = new GoogleAuthProvider();
        const result = await signInWithPopup(auth, provider);
        const idToken = await result.user.getIdToken();

        const response = await loginWithFirebase(idToken);

        if (response.token) localStorage.setItem("auth_token", response.token);
        if (response.user)  localStorage.setItem("auth_user", JSON.stringify(response.user));

        $toastr.success("Welcome back!", "Google Login Successful");
        router.push("/dashboard");

        Promise.all([
            import("@/js/components/campaigns/Campaigns.vue"),
            import("@/js/components/WebsiteCampaigns.vue"),
            import("@/js/components/WebsiteSaleDetails.vue"),
            import("@/js/components/WebsitePromos.vue"),
        ]);
    } catch (err) {
        let message;

        if (err.response?.status === 422) {
            message = err.response.data?.message || "Invalid email";
        } else if (err.response?.status === 401) {
            message = err.response.data?.message || "Invalid credentials";
        } else {
            message = err.message || "Google login failed";
        }
        ui.hideLoader();

        $toastr.error(message, "Login Failed");
        console.error("Google login error:", err);
    } finally {
        loading.value = false;
        ui.hideLoader();
    }
};
</script>

<style scoped>
.ecommerce-world {
    position: relative;
    width: 220px;
    height: 220px;
}

.globe {
    position: absolute;
    inset: 0;
    margin: auto;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, #818cf8, #4338ca 70%);
    box-shadow: 0 0 50px rgba(99, 102, 241, 0.4);
    animation: pulseGlobe 4s ease-in-out infinite;
}

.float-icon {
    position: absolute;
    font-size: 1.5rem;
    color: white;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    padding: 12px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: floatIcons 6s ease-in-out infinite;
}

/* Precise positioning for icons around the globe */
.float-icon[style*="--i: 1"] { top: 0%; left: 50%; transform: translateX(-50%); animation-delay: 0s; }
.float-icon[style*="--i: 2"] { bottom: 10%; left: 5%; animation-delay: 1.2s; }
.float-icon[style*="--i: 3"] { bottom: 10%; right: 5%; animation-delay: 2.4s; }
.float-icon[style*="--i: 4"] { top: 35%; left: -5%; animation-delay: 3.6s; }
.float-icon[style*="--i: 5"] { top: 35%; right: -5%; animation-delay: 4.8s; }

@keyframes pulseGlobe {
    0%, 100% { transform: scale(1); opacity: 0.9; }
    50% { transform: scale(1.08); opacity: 1; }
}

@keyframes floatIcons {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}
</style>
