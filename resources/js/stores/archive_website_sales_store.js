import { defineStore } from 'pinia';
import { fetchArchivedWebsiteSale, fetchUnarchiveWebsiteSale } from '@/js/api/archive_website_sale_api.js';
import { fetchStores } from '@/js/api/website_campaign_api';

export const useArchiveWebsiteSalesStore = defineStore('archiveWebsiteSales', {
  state: () => ({
    archivedWebsiteSales: [],
    stores: [],
    loading: false,
    loaded: false, 
  }),

  actions: {
    async loadArchivedWebsiteSales(force = false) {
      if (this.loaded && !force) return;

      this.loading = true;
      try {
        this.archivedWebsiteSales = await fetchArchivedWebsiteSale();
        this.loaded = true; 
      } catch (error) {
        console.error('Failed to load archived website sales', error);
      } finally {
        this.loading = false;
      }
    },

    async loadStores() {
      try {
        this.stores = await fetchStores();
      } catch (error) {
        console.error('Failed to load stores', error);
      }
    },

    async unarchiveWebsiteSale(id) {
      try {
        await fetchUnarchiveWebsiteSale(id);
        this.loaded = false;
        await this.loadArchivedWebsiteSales();
      } catch (error) {
        console.error('Failed to unarchive website sale', error);
      }
    },

    clearCache() {
      this.loaded = false;
      this.archivedWebsiteSales = [];
    }
  },

  getters: {
    allArchivedWebsiteSales: (state) => state.archivedWebsiteSales,
    isLoading: (state) => state.loading,
  }
});