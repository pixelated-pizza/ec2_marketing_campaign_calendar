import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import {
    loginUser,
    loginWithFirebase,
    logoutUser,
    fetchCurrentUser,
} from '@/js/api/user_api';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('auth_token') ?? null);
    const user  = ref(null);   // { name, email, role_name }
    const error = ref(null);
    const loading = ref(false);

    const isAuthenticated = computed(() => !!token.value);
    const userName        = computed(() => user.value?.name ?? '');
    const userRole        = computed(() => user.value?.role_name ?? '');


    /** Standard email / password login */
    async function login(email, password, remember = false) {
        loading.value = true;
        error.value   = null;

        try {
            const { data } = await loginUser(email, password, remember);

            _persistSession(data);
            return true;
        } catch (err) {
            error.value = _extractMessage(err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function firebaseLogin(idToken) {
        loading.value = true;
        error.value   = null;

        try {
            const { data } = await loginWithFirebase(idToken);

            _persistSession(data);
            return true;
        } catch (err) {
            error.value = _extractMessage(err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await logoutUser();
        } catch {
        } finally {
            _clearSession();
        }
    }

    async function fetchUser() {
        if (!token.value) return;

        loading.value = true;
        error.value   = null;

        try {
            const { data } = await fetchCurrentUser();
            user.value = {
                id:        data.id,
                name:      data.name,
                email:     data.email,
                role_id:   data.role_id,
                role_name: data.role_name,
            };
        } catch (err) {
            _clearSession();
            error.value = _extractMessage(err);
        } finally {
            loading.value = false;
        }
    }

    function _persistSession(data) {
        token.value = data.token;
        user.value  = data.user;        
        localStorage.setItem('auth_token', data.token);
    }

    function _clearSession() {
        token.value = null;
        user.value  = null;
        localStorage.removeItem('auth_token');
    }

    function _extractMessage(err) {
        return (
            err?.response?.data?.message ??
            err?.message ??
            'An unexpected error occurred.'
        );
    }

    return {
        token,
        user,
        error,
        loading,
        isAuthenticated,
        userName,
        userRole,
        login,
        firebaseLogin,
        logout,
        fetchUser,
    };
});