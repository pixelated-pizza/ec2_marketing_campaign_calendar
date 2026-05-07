import { defineStore } from "pinia";
import {
    fetchOnsiteCampaign,
    createOnsiteCampaign,
    updateOnsiteCampaign,
    deleteOnsiteCampaign,
    archiveOnsiteCampaign,
} from "@/js/api/onsite_campaigns_api.js";
import { fetchStores } from "@/js/api/website_campaign_api";
import { fetchSections } from "@/js/api/website_campaign_api";
import { fetchCampaignTypes } from "@/js/api/onsite_campaigns_api";

const toAUDateTime = (date) => {
    if (!date) return null;

    const d = new Date(date);

    const auDate = new Date(
        d.toLocaleString("en-US", { timeZone: "Australia/Sydney" }),
    );

    const year = auDate.getFullYear();
    const month = String(auDate.getMonth() + 1).padStart(2, "0");
    const day = String(auDate.getDate()).padStart(2, "0");

    const hours = String(auDate.getHours()).padStart(2, "0");
    const minutes = String(auDate.getMinutes()).padStart(2, "0");
    const seconds = String(auDate.getSeconds()).padStart(2, "0");

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

export const useOnsiteCampaignStore = defineStore("onsiteCampaign", {
    state: () => ({
        campaigns: [],
        stores: [],
        sections: [],
        campaign_types: [],
        loading: false,
    }),
    actions: {
        async loadCampaigns() {
            this.loading = true;
            try {
                const response = await fetchOnsiteCampaign();
                // Make sure campaigns is always an array
                this.campaigns = Array.isArray(response)
                    ? response
                    : (response?.data ?? []);
            } catch (error) {
                console.error("Failed to load campaigns", error);
                this.campaigns = [];
            } finally {
                this.loading = false;
            }
        },
        async addCampaign(campaign) {
            try {
                const payload = {
                    name: campaign.name,
                    campaign_type_id: campaign.campaign_type_id,
                    section_id: campaign.section_id,
                    store_id: campaign.store_id,
                    store_id: campaign.is_all_store ? null : campaign.store_id,
                    start_date: campaign.start_date
                        ? toAUDateTime(campaign.start_date)
                        : null,
                    end_date: campaign.end_date
                        ? toAUDateTime(campaign.end_date)
                        : null,
                };
                const newCampaign = await createOnsiteCampaign(payload);
                this.campaigns.push(newCampaign);
            } catch (error) {
                console.error("Failed to add campaign", error);
            }
        },
        async editCampaign(id, updates) {
            try {
                const updatedCampaign = await updateOnsiteCampaign(id, updates);
                const index = this.campaigns.findIndex(
                    (c) => String(c.wc_id) === String(id),
                );

                if (index !== -1) {
                    if (
                        updatedCampaign &&
                        typeof updatedCampaign === "object"
                    ) {
                        this.campaigns[index] = {
                            ...this.campaigns[index],
                            ...updatedCampaign,
                        };
                    } else {
                        this.campaigns[index] = {
                            ...this.campaigns[index],
                            ...updates,
                        };
                    }
                }
            } catch (error) {
                console.error("Failed to update campaign", error);
            }
        },

        async removeCampaign(id) {
            try {
                await deleteOnsiteCampaign(id);
                this.campaigns = this.campaigns.filter((c) => c.wc_id !== id);
            } catch (error) {
                console.error("Failed to delete campaign", error);
            }
        },

        async archiveCampaign(id, is_archived) {
            try {
                const updatedCampaign = await archiveOnsiteCampaign(
                    id,
                    is_archived,
                );
                const index = this.campaigns.findIndex((c) => c.wc_id === id);
                if (index !== -1) {
                    this.campaigns[index] = updatedCampaign;
                }
            } catch (error) {
                console.error("Failed to archive campaign", error);
            }
        },

        async loadStores() {
            try {
                this.stores = await fetchStores();
                return this.stores;
            } catch (error) {
                console.error("Failed to load stores", error);
            }
        },

        async loadSections() {
            try {
                this.sections = await fetchSections();
            } catch (error) {
                console.error("Failed to load sections", error);
            }
        },

        async loadCampaignTypes() {
            try {
                const types = await fetchCampaignTypes();
                this.campaign_types = types;
            } catch (error) {
                console.error("Failed to load campaign types", error);
            }
        },
    },
    getters: {
        allCampaigns: (state) => state.campaigns,
        isLoading: (state) => state.loading,
    },
});
