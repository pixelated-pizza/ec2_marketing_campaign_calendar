import { defineStore } from 'pinia';
import { fetchWebsitePromotions } from '@/js/api/website_promos_api.js';
import { fetchStores } from '@/js/api/website_campaign_api';

export const useWebsitePromosStore = defineStore('websitePromos', {
  state: () => ({
    websitePromotions: [],
    stores: [],
    loading: false,
    loaded: false, 
  }),
  actions: {
    // Load promotions with caching
    async loadWebsitePromotions(force = false) {
      if (this.loaded && !force) return;

      this.loading = true;
      try {
        this.websitePromotions = await fetchWebsitePromotions();
        this.loaded = true; 
      } catch (error) {
        console.error('Failed to load website promotions', error);
      } finally {
        this.loading = false;
      }
    },

    clearPromotionsCache() {
      this.loaded = false;
      this.websitePromotions = [];
    },

    async loadStores(force = false) {
      if (this.stores.length && !force) return;

      try {
        this.stores = await fetchStores();
      } catch (error) {
        console.error('Failed to load stores', error);
      }
    },
  },
  getters: {
    allWebsitePromotions: (state) => state.websitePromotions,
  },
});