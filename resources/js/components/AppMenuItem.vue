<template>
    <li>
        <router-link
            :to="item.to"
            :title="item.label"
            :class="[
                'menu-item group',
                isActive ? 'menu-item-active' : 'menu-item-inactive',
                isSidebarWide ? 'xl:justify-start' : 'xl:justify-center'
            ]"
            @click="itemClick"
        >
            <span
                :class="[
                    isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive',
                    'flex h-5 w-5 shrink-0 items-center justify-center text-lg'
                ]"
            >
                <i :class="item.icon"></i>
            </span>
            <span v-if="isSidebarWide" class="truncate">{{ item.label }}</span>
        </router-link>
    </li>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useLayout } from '@/js/layouts/composables/layout';
import { useUIStore } from '@/js/stores/ui';

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    }
});

const route = useRoute();
const ui = useUIStore();
const { layoutState, isSidebarWide } = useLayout();

const isActive = computed(() => route.path === props.item.to);

// Same behaviour as before: flash the global loader, close any open menu state
const itemClick = () => {
    ui.showLoader();
    setTimeout(() => ui.hideLoader(), 50);
    layoutState.overlayMenuActive = false;
    layoutState.mobileMenuActive = false;
    layoutState.menuHoverActive = false;
};
</script>
