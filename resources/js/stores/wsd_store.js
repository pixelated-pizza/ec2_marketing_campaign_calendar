import { defineStore } from "pinia";
import {
    fetchWSD,
    createWSD,
    updateWSD,
    deleteWSD,
    fetchBlankWSD,
} from "@/js/api/wsd_api.js";

export const useWSDStore = defineStore("wsd", {
    state: () => ({
        websiteSaleDetails: [],
        loading: false,
        error: null,
        loaded: false,
        lastFetched: null,
    }),

    actions: {
        // Load WSD with caching
        async loadWSD(force = false) {
            const STALE_MS = 5 * 60 * 1000;
            const isStale =
                !this.lastFetched || Date.now() - this.lastFetched > STALE_MS;
            if (this.loaded && !force) return;

            this.loading = true;
            this.error = null;

            try {
                const data = await fetchWSD();
                const today = new Date().toISOString().split("T")[0];

                this.websiteSaleDetails = data.map((c) => {
                    let status = "UPCOMING";

                    if (today >= c.start_date && today <= c.end_date) {
                        status = "RUNNING";
                    } else if (today > c.end_date) {
                        status = "ENDED";
                    }

                    return {
                        ...c,
                        status,
                        statusOrder: {
                            RUNNING: 1,
                            UPCOMING: 2,
                            ENDED: 3,
                        }[status],
                    };
                });

                this.loaded = true; // mark cache valid
            } catch (err) {
                this.error =
                    err.message || "Failed to load website sale details.";
            } finally {
                this.loading = false;
            }
            this.lastFetched = Date.now();
            this.loaded = true;
        },

        clearCache() {
            this.loaded = false;
            this.websiteSaleDetails = [];
        },

        async addWSD(newData) {
            const created = await createWSD(newData);

            const index = this.websiteSaleDetails.findIndex(
                (w) =>
                    (w.wc_id ?? w.campaign_id) ===
                    (created.wc_id ?? created.campaign_id),
            );

            if (index !== -1) {
                this.websiteSaleDetails[index] = {
                    ...this.websiteSaleDetails[index],
                    ...created,
                };
            } else {
                this.websiteSaleDetails.push(created);
            }
        },

        async updateWSD(wsd_id, updates) {
            const updated = await updateWSD(wsd_id, updates);
            const index = this.websiteSaleDetails.findIndex(
                (w) => w.wsd_id === wsd_id,
            );
            if (index !== -1) this.websiteSaleDetails[index] = updated;
            return updated;
        },

        async removeWSD(id) {
            await deleteWSD(id);
            this.websiteSaleDetails = this.websiteSaleDetails.filter(
                (w) => w.wsd_id !== id,
            );
        },

        async getBlankWSD(wc_id) {
            return await fetchBlankWSD(wc_id);
        },

        async rerunCampaign(id, newStartDate, newEndDate) {
            const updates = {
                start_date: newStartDate,
                end_date: newEndDate,
            };
            return await this.updateWSD(id, updates);
        },
    },
});
