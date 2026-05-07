import { defineStore } from 'pinia';
import { fetchNetoProducts } from '@/js/api/neto_api.js';

export const useNetoStore = defineStore('neto', {
  state: () => ({
    products: [],
    total:    0,
    loading:  false,
    loaded:   false,
  }),

  actions: {
    async loadProducts(sku = null, force = false) {
      if (this.loaded && !force) return;

      this.loading = true;
      try {
        const data    = await fetchNetoProducts(sku);
        this.products = data.rows;
        this.total    = data.total;
        this.loaded   = true;
      } catch (error) {
        console.error('Failed to load Neto products', error);
      } finally {
        this.loading = false;
      }
    },

    clearCache() {
      this.loaded   = false;
      this.products = [];
      this.total    = 0;
    },
  },

  getters: {
    allProducts: (state) => state.products,
    isLoading:   (state) => state.loading,
  },
});