import { createRouter, createWebHistory } from "vue-router";
import AppLayout from "@/js/components/AppLayout.vue";
import Dashboard from "@/js/components/dashboard/Dashboard.vue";
import Forbidden from "@/js/components/Forbidden.vue";
import { ROUTE_PERMISSIONS } from "@/js/constant/roles";

function isAuthenticated() {
    return !!localStorage.getItem("auth_token");
}

function currentRole() {
    try {
        return JSON.parse(localStorage.getItem("auth_user") ?? "null")?.role_name ?? null;
    } catch {
        return null;
    }
}

function canAccessRoute(routeName) {
    const allowed = ROUTE_PERMISSIONS[routeName];
    if (!allowed) return true;

    const role = currentRole();
    if (!role) return false;

    return allowed.includes(role);
}

const routes = [
    { path: "/", redirect: "/login" },

    {
        path: "/login",
        name: "Login",
        component: () => import("@/js/components/login/Login.vue"),
    },

    {
        path: "/403",
        name: "Forbidden",
        component: Forbidden,
    },

    {
        path: "/",
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            { path: "dashboard", name: "Dashboard", component: Dashboard },

            {
                path: "campaigns",
                name: "Campaigns",
                component: () => import("@/js/components/campaigns/Campaigns.vue"),
            },

            {
                path: "website_campaigns",
                name: "WebsiteCampaigns",
                component: () => import("@/js/components/WebsiteCampaigns.vue"),
            },

            {
                path: "website-sale",
                name: "WebsiteSale",
                component: () => import("@/js/components/WebsiteSaleDetails.vue"),
            },

            {
                path: "website-promo",
                name: "WebsitePromo",
                component: () => import("@/js/components/WebsitePromos.vue"),
            },

            {
                path: "website-promotions-archive",
                name: "WebsitePromoArchive",
                component: () => import("@/js/components/WebsitePromoArchive.vue"),
            },

            {
                path: "website-sale-archive",
                name: "WebsiteSaleArchive",
                component: () => import("@/js/components/WebsiteSaleArchive.vue"),
            },

            {
                path: "marketing-dates",
                name: "KeyMarketingDates",
                component: () => import("@/js/components/KeyMarketingDates.vue"),
            },

            {
                path: "user-mgmt",
                name: "UserMgmt",
                component: () => import("@/js/components/super_admin/UserMgmt.vue"),
            },

            {
                path: "forgot-password",
                name: "ResetPassword",
                component: () => import("@/js/components/login/ResetPassword.vue"),
            },

            {
                path: "category-featured-skus",
                name: "CategoryFeaturedSkus",
                component: () =>
                    import("@/js/components/category_featured_skus/CategoryFeaturedSkus.vue"),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory("/markmap/"),
    routes,
});

let initialNavDone = false;

router.beforeEach((to, from, next) => {
    if (to.meta.requiresAuth && !isAuthenticated()) return next("/login");

    if (to.path === "/login" && isAuthenticated()) return next("/dashboard");

    if (to.name && !["Login", "Forbidden"].includes(to.name)) {
        if (!canAccessRoute(to.name)) return next("/403");
    }

    next();
});

router.afterEach(() => {
    initialNavDone = true;
});

export default router;