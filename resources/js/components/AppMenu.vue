<template>
    <div class="flex flex-col gap-4">
        <div v-for="group in filteredModel" :key="group.label">
            <div
                :class="[
                    'mb-3 flex text-xs uppercase leading-5 text-gray-400',
                    isSidebarWide ? 'justify-start' : 'xl:justify-center'
                ]"
            >
                <template v-if="isSidebarWide">{{ group.label }}</template>
                <i v-else class="pi pi-ellipsis-h"></i>
        </div>

            <ul class="flex flex-col gap-1">
                <AppMenuItem v-for="item in group.items" :key="item.to" :item="item" />
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import { useAuth } from '@/js/composables/useAuth';
import { useLayout } from '@/js/layouts/composables/layout';

const { canAccess } = useAuth();
const { isSidebarWide } = useLayout();

const model = [
    {
        label: 'Home',
        items: [
            { label: 'Dashboard', icon: 'pi pi-chart-bar', to: '/dashboard', route: 'Dashboard' },
            { label: 'Website Sale Calendar', icon: 'pi pi-calendar', to: '/campaign-calendar', route: 'CampaignCalendar' }
        ]
    },
    {
        label: 'Marketing Calendar',
        items: [
            { label: 'Website & Marketplaces Campaigns', icon: 'pi pi-globe', to: '/campaigns', route: 'Campaigns' },
            { label: 'Website Sales / Promotions - Mytopia & Edisons', icon: 'pi pi-globe', to: '/website_campaigns', route: 'WebsiteCampaigns' },
            { label: 'Website Sale Details', icon: 'pi pi-pen-to-square', to: '/website-sale', route: 'WebsiteSale' },
            { label: 'Website Promotion Details', icon: 'pi pi-at', to: '/website-promo', route: 'WebsitePromo' },
            { label: '[Sites] Category Featured SKUs', icon: 'pi pi-asterisk', to: '/category-featured-skus', route: 'CategoryFeaturedSkus' }
        ]
    },
    {
        label: 'Archive',
        items: [
            { label: 'Website Promotions', icon: 'pi pi-chart-line', to: '/website-promotions-archive', route: 'WebsitePromoArchive' },
            { label: 'Website Sales', icon: 'pi pi-globe', to: '/website-sale-archive', route: 'WebsiteSaleArchive' }
        ]
    },
    {
        label: 'User Management',
        items: [{ label: 'User Accounts', icon: 'pi pi-user', to: '/user-mgmt', route: 'UserMgmt' }]
    }
];

/**
 * Filter items the current role can access.
 * Groups with no visible items are removed entirely.
 */
const filteredModel = computed(() =>
    model
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => canAccess(item.route))
        }))
        .filter((group) => group.items.length > 0)
);
</script>
