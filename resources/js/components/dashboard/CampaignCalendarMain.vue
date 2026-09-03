<template>
    <div
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700/70 shadow-sm flex flex-col p-5 mb-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100 dark:border-gray-700/60 shrink-0">
            <div>
                <h2 class="!text-xl font-bold text-gray-900 dark:text-white">
                    Website Sale Calendar
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Overview of all campaign timeline spans across weeks
                </p>
            </div>

            <button @click="loadCampaigns" :disabled="loading"
                class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span>Refresh</span>
            </button>
        </div>

        <!-- Fixed Height Scrollable Calendar Container -->
        <div class="w-full h-[calc(100vh-220px)] min-h-[500px] overflow-y-auto pr-1">
            <div v-if="loading" class="flex flex-col gap-3 h-full">
                <Skeleton height="3rem" width="100%" borderRadius="8px" />
                <Skeleton height="100%" width="100%" borderRadius="12px" />
            </div>

            <CampaignCalendar v-else :campaigns="campaigns" @eventClick="handleEventClick" />
        </div>

        <!-- Dialog Modal -->
        <Dialog v-model:visible="showModal"
            :header="selectedCampaign?.event_name || selectedCampaign?.name || 'Campaign Details'" :modal="true"
            :dismissableMask="true" class="w-full max-w-2xl text-xs dark:bg-gray-800 dark:text-gray-100">
            <div v-if="selectedCampaign" class="flex flex-col gap-4 p-2 text-xs">
                <!-- Status & Channel Header -->
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Channel:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ selectedCampaign.channel_name || '—' }}</span>
                    </div>
                    <span v-if="selectedCampaign.status"
                        class="font-semibold text-[10px] tracking-wide uppercase px-2.5 py-0.5 rounded"
                        :class="statusBadgeClass(selectedCampaign.status)">
                        {{ selectedCampaign.status }}
                    </span>
                </div>

                <!-- Timeline Dates -->
                <div
                    class="grid grid-cols-2 gap-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600/60">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">Start Date</span>
                        <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ formatDate(selectedCampaign.start_date) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">End Date</span>
                        <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ formatDate(selectedCampaign.end_date) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Status:</span>
                    <span class="font-semibold text-[10px] tracking-wide uppercase px-2.5 py-0.5 rounded inline-block"
                        :class="statusBadgeClass(computeStatus(selectedCampaign))">
                        {{ computeStatus(selectedCampaign) }}
                    </span>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end pt-2">
                    <Button label="Close" severity="secondary" size="small" class="text-xs"
                        @click="showModal = false" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import CampaignCalendar from "@/js/components/dashboard/CampaignCalendar.vue";
import { fetchCampaigns, fetchCampaignById } from "@/js/api/campaign_service.js";
import Skeleton from "primevue/skeleton";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

const campaigns = ref([]);
const loading = ref(true);

const modalLoading = ref(false);

const showModal = ref(false);
const selectedCampaign = ref(null);

const emit = defineEmits(["eventClick"]);

async function loadCampaigns() {
    try {
        loading.value = true;
        const result = await fetchCampaigns();
        campaigns.value = result || [];
    } catch (err) {
        console.error("Failed to fetch campaigns for calendar:", err);
    } finally {
        loading.value = false;
    }
}

function computeStatus(campaign) {
    if (!campaign?.start_date || !campaign?.end_date) return "UNKNOWN";

    const now = new Date();
    const start = new Date(campaign.start_date);
    const end = new Date(campaign.end_date);

    if (now < start) return "UPCOMING";
    if (now > end) return "ENDED";
    return "ONGOING";
}

async function handleEventClick(campaign) {
    emit("eventClick", campaign);
    showModal.value = true;
    selectedCampaign.value = campaign;
    modalLoading.value = true;

    try {
        const fullCampaign = await fetchCampaignById(campaign.campaign_id);
        selectedCampaign.value = fullCampaign;
    } catch (err) {
        console.error("Failed to fetch campaign details:", err);
    } finally {
        modalLoading.value = false;
    }
}


function formatDate(dateStr) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleString("en-US", {
        year: "numeric",
        month: "numeric",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

function statusBadgeClass(status) {
    switch (status?.toUpperCase()) {
        case "ONGOING":
            return "bg-green-100 text-green-800 dark:bg-emerald-500/20 dark:text-emerald-300";
        case "ENDED":
            return "bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300";
        case "UPCOMING":
            return "bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300";
        default:
            return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
    }
}

function autoLink(text) {
    if (!text) return "—";
    const urlRegex = /(https?:\/\/[^\s)<>"']+)/g;
    return text
        .replace(/\n/g, "<br>")
        .replace(urlRegex, (url) => `<a href="${url}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">${url}</a>`);
}

onMounted(() => {
    loadCampaigns();
});
</script>