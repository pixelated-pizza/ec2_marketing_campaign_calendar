<template>
    <div
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700/70 shadow-sm flex flex-col p-5 mb-6">

        <!-- Header -->
        <div
            class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100 dark:border-gray-700/60 shrink-0">
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

        <Dialog v-model:visible="showModal" :modal="true" :dismissableMask="true" :closable="false"
            :pt="{ root: { class: 'border-0 bg-transparent shadow-none' }, mask: { class: 'backdrop-blur-[1px]' } }">
            <template #container>
                <div v-if="selectedCampaign"
                    class="w-[420px] max-w-[92vw] rounded-2xl bg-[#eef2f9] dark:bg-gray-800 shadow-xl px-6 pt-4 pb-6 text-gray-700 dark:text-gray-200">

                    <!-- Top action row -->
                    <div class="flex items-center justify-end mb-1 -mr-2">
                        <button type="button"
                            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-black/5 dark:hover:bg-white/10"
                            aria-label="Close" @click="showModal = false">
                            <i class="pi pi-times text-base"></i>
                        </button>
                    </div>

                    <!-- Title row: colored dot + title + date -->
                    <div class="flex items-start gap-5">
                        <span class="mt-[7px] w-4 h-4 rounded-[4px] shrink-0"
                            :class="statusDotClass(computeStatus(selectedCampaign))"></span>
                        <div class="min-w-0">
                            <div
                                class="text-[22px] leading-[30px] font-normal m-0 text-gray-900 dark:text-white break-words">
                                {{ selectedCampaign.event_name || selectedCampaign.name || 'Campaign Details' }}
                            </div>
                            <div class="text-sm leading-5 font-normal m-0 mt-0.5 text-gray-700 dark:text-gray-300">
                                {{ formatDate(selectedCampaign.start_date) }} – {{ formatDate(selectedCampaign.end_date)
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Detail rows: icon + text -->
                    <div class="mt-6 flex flex-col gap-4 text-sm">
                        <!-- Channel -->
                        <div class="flex items-center gap-5">
                            <i class="pi pi-shop w-4 text-center text-base text-gray-600 dark:text-gray-400"></i>
                            <span>{{ selectedCampaign.channel_name || '—' }}</span>
                        </div>

                        <!-- Start date -->
                        <div class="flex items-center gap-5">
                            <i class="pi pi-calendar w-4 text-center text-base text-gray-600 dark:text-gray-400"></i>
                            <span>Start: {{ formatDate(selectedCampaign.start_date) }}</span>
                        </div>

                        <!-- End date -->
                        <div class="flex items-center gap-5">
                            <i
                                class="pi pi-calendar-times w-4 text-center text-base text-gray-600 dark:text-gray-400"></i>
                            <span>End: {{ formatDate(selectedCampaign.end_date) }}</span>
                        </div>

                        <!-- Status (computed) -->
                        <div class="flex items-center gap-5">
                            <i class="pi pi-info-circle w-4 text-center text-base text-gray-600 dark:text-gray-400"></i>
                            <span class="font-semibold text-[10px] tracking-wide uppercase px-2.5 py-0.5 rounded"
                                :class="statusBadgeClass(computeStatus(selectedCampaign))">
                                {{ computeStatus(selectedCampaign) }}
                            </span>
                        </div>

                        <!-- Status (from record) -->
                        <div v-if="selectedCampaign.status" class="flex items-center gap-5">
                            <i class="pi pi-tag w-4 text-center text-base text-gray-600 dark:text-gray-400"></i>
                            <span class="font-semibold text-[10px] tracking-wide uppercase px-2.5 py-0.5 rounded"
                                :class="statusBadgeClass(selectedCampaign.status)">
                                {{ selectedCampaign.status }}
                            </span>
                        </div>
                    </div>
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

const statusDotClass = (status) => {
    switch ((status || '').toLowerCase()) {
        case 'active':
        case 'ongoing':
            return 'bg-green-600';
        case 'upcoming':
        case 'scheduled':
            return 'bg-blue-600';
        case 'completed':
        case 'ended':
            return 'bg-gray-500';
        default:
            return 'bg-emerald-700';
    }
};

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