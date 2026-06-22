import { defineStore } from "pinia";
import {
    fetchWSD,
    createWSD,
    updateWSD,
    deleteWSD,
    fetchBlankWSD,
    previewImportWSD,
    commitImportWSD,
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
        async loadWSD(force = false) {
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
                        statusOrder: { RUNNING: 1, UPCOMING: 2, ENDED: 3 }[status],
                    };
                });
            } catch (err) {
                this.error = err.message || "Failed to load website sale details.";
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

        // Create or update — keyed by event_name + channel_name + start_date on the backend.
        async addWSD(newData) {
            const saved = await createWSD(newData);

            const index = this.websiteSaleDetails.findIndex((w) => w.wsd_id === saved.wsd_id);

            if (index !== -1) {
                this.websiteSaleDetails[index] = { ...this.websiteSaleDetails[index], ...saved };
            } else {
                this.websiteSaleDetails.push(saved);
            }
            return saved;
        },

        async updateWSD(wsd_id, updates) {
            const updated = await updateWSD(wsd_id, updates);
            const index = this.websiteSaleDetails.findIndex((w) => w.wsd_id === wsd_id);
            if (index !== -1) this.websiteSaleDetails[index] = updated;
            return updated;
        },

        async removeWSD(id) {
            await deleteWSD(id);
            this.websiteSaleDetails = this.websiteSaleDetails.filter((w) => w.wsd_id !== id);
        },

        async getBlankWSD(params) {
            return await fetchBlankWSD(params);
        },

        // Re-run now just shifts dates on the same WSD row — no campaigns module involved.
        async rerunCampaign(wsd_id, newStartDate, newEndDate) {
            return await this.updateWSD(wsd_id, { start_date: newStartDate, end_date: newEndDate });
        },

        async previewImport(rows) {
            return await previewImportWSD(rows);
        },

        async commitImport(rows) {
            const result = await commitImportWSD(rows);
            await this.loadWSD(true);
            return result;
        },
    },
});