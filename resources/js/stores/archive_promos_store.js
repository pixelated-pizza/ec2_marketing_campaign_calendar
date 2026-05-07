import { defineStore } from 'pinia';
import { fetchArchivedPromotions, fetchUnarchivePromotion } from '@/js/api/archive_promos_api.js';
import { fetchStores } from '@/js/api/website_campaign_api';

export const useArchivePromosStore = defineStore('archivePromos', {
    state: () => ({
        archivedPromotions: [],
        stores: [],
        loading: false,
    }),
    actions: {
        async loadArchivedPromotions() {
            this.loading = true;
            try {
                this.archivedPromotions = await fetchArchivedPromotions();
            } catch (error) {
                console.error('Failed to load archived promotions', error);
            }
            this.loading = false;
        },
        async loadStores() {
            try {
                this.stores = await fetchStores();
            } catch (error) {
                console.error('Failed to load stores', error);
            }
        },
        async unarchivePromotion(id) {
            try {
                await fetchUnarchivePromotion(id);
                await this.loadArchivedPromotions();
            } catch (error) {
                console.error('Failed to unarchive promotion', error);
            }
        }
    },
    getters: {
        allArchivedPromotions: (state) => state.archivedPromotions,
        isLoading: (state) => state.loading,
    }
});