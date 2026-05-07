import { defineStore } from "pinia";
import {
    fetchFeaturedSkus,
    createFeaturedSku,
    deleteFeaturedSku,
    createBulkFeaturedSkus,
    syncFeaturedSkus,
    updateFeaturedSku,
    deleteAll,
} from "@/js/api/category_featured_skus_api.js";

export const useCategoryFeaturedSkusStore = defineStore(
    "categoryFeaturedSkus",
    {
        state: () => ({
            featuredSkus: [],
            loading: false,
            loaded: false,
            saving: false,
        }),

        actions: {
            async loadFeaturedSkus(force = false) {
                if (this.loaded && !force) return;

                this.loading = true;
                try {
                    this.featuredSkus = await fetchFeaturedSkus();
                    this.loaded = true;
                } catch (error) {
                    console.error("Failed to load featured SKUs", error);
                } finally {
                    this.loading = false;
                }
            },
            async loadFeaturedSkus(force = false) {
                if (this.loaded && !force) return;

                this.loading = true;
                try {
                    await syncFeaturedSkus(); // sync live Neto data first
                    this.featuredSkus = await fetchFeaturedSkus(); // then load updated DB data
                    this.loaded = true;
                } catch (error) {
                    console.error("Failed to load featured SKUs", error);
                } finally {
                    this.loading = false;
                }
            },

            async addFeaturedSku(payload) {
                this.saving = true;
                try {
                    await createFeaturedSku(payload);
                    this.loaded = false;
                    await this.loadFeaturedSkus();
                } catch (error) {
                    console.error("Failed to add featured SKU", error);
                    throw error;
                } finally {
                    this.saving = false;
                }
            },
            async updateFeaturedSku(id, payload) {
                try {
                    await updateFeaturedSku(id, payload);
                    this.loaded = false;
                    await this.loadFeaturedSkus();
                } catch (error) {
                    console.error("Failed to update featured SKU", error);
                    throw error;
                }
            },
            async addBulkFeaturedSkus(payload) {
                this.saving = true;
                try {
                    await createBulkFeaturedSkus(payload);
                    this.loaded = false;
                    await this.loadFeaturedSkus();
                } catch (error) {
                    console.error("Failed to bulk add featured SKUs", error);
                    throw error;
                } finally {
                    this.saving = false;
                }
            },

            async removeFeaturedSku(id) {
                try {
                    await deleteFeaturedSku(id);
                    this.loaded = false;
                    await this.loadFeaturedSkus();
                } catch (error) {
                    console.error("Failed to delete featured SKU", error);
                }
            },

            async deleteAllSkus() {
                this.loading = true;

                try {
                    await deleteAll();

                    this.loaded = false;
                    await this.loadFeaturedSkus(true); // reload clean state
                } catch (error) {
                    console.error("Failed to delete all featured SKUs", error);
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            clearCache() {
                this.loaded = false;
                this.featuredSkus = [];
            },
        },

        getters: {
            allFeaturedSkus: (state) => state.featuredSkus,
            isLoading: (state) => state.loading,
            isSaving: (state) => state.saving,
        },
    },
);
