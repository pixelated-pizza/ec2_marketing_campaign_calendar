import { computed } from "vue";
import { ROUTE_PERMISSIONS } from "@/js/constant/roles";

function getStoredUser() {
    try {
        return JSON.parse(localStorage.getItem("auth_user") ?? "null");
    } catch {
        return null;
    }
}

export function useAuth() {
    const user = computed(() => getStoredUser());
    const role = computed(() => user.value?.role_name ?? null);


    function canAccess(routeName) {
        const allowed = ROUTE_PERMISSIONS[routeName];
        if (!allowed) return true;                    
        if (!role.value) return false;                
        return allowed.includes(role.value);
    }

    return { user, role, canAccess };
}