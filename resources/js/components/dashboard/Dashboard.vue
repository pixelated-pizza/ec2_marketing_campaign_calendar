<template>
    <div class="h-full p-6 bg-gray-50 dark:bg-gray-900 font-sans">
        <!-- Dashboard Header -->
        <!-- <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                    Dashboard
                </h1>
            </div>
            <div
                class="flex items-center gap-2 self-start sm:self-auto bg-white dark:bg-gray-800 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm text-xs text-gray-600 dark:text-gray-300">
                <CalendarClock class="w-4 h-4 text-blue-500" />
                <span>Updated Today</span>
            </div>
        </div> -->

        <!-- Metric Stat Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <!-- Total Campaigns -->
            <div class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700/70 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 cursor-pointer"
                @click="openModal('total')">
                <div v-if="loading" class="flex items-center gap-3">
                    <Skeleton shape="circle" size="2.75rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="50%" height="0.875rem" />
                        <Skeleton width="30%" height="1.5rem" />
                    </div>
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total
                            Campaigns</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.total }}
                        </div>
                    </div>
                    <div
                        class="p-3 bg-gray-100 dark:bg-gray-700/50 group-hover:bg-gray-200 dark:group-hover:bg-gray-700 rounded-xl transition-colors">
                        <ListChecks class="w-6 h-6 text-gray-700 dark:text-gray-200" />
                    </div>
                </div>
            </div>

            <!-- Active Campaigns -->
            <div class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700/70 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-600/50 transition-all duration-200 cursor-pointer"
                @click="openModal('active')">
                <div v-if="loading" class="flex items-center gap-3">
                    <Skeleton shape="circle" size="2.75rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="50%" height="0.875rem" />
                        <Skeleton width="30%" height="1.5rem" />
                    </div>
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Ongoing Campaign/s</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.active }}
                        </div>
                    </div>
                    <div
                        class="p-3 bg-emerald-50 dark:bg-emerald-950/40 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/50 rounded-xl transition-colors">
                        <PlayCircle class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>

            <!-- Upcoming -->
            <div class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700/70 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600/50 transition-all duration-200 cursor-pointer"
                @click="openModal('upcoming')">
                <div v-if="loading" class="flex items-center gap-3">
                    <Skeleton shape="circle" size="2.75rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="50%" height="0.875rem" />
                        <Skeleton width="30%" height="1.5rem" />
                    </div>
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Upcoming</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.upcoming }}
                        </div>
                    </div>
                    <div
                        class="p-3 bg-blue-50 dark:bg-blue-950/40 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50 rounded-xl transition-colors">
                        <CalendarClock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700/70 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-600/50 transition-all duration-200 cursor-pointer"
                @click="openModal('completed')">
                <div v-if="loading" class="flex items-center gap-3">
                    <Skeleton shape="circle" size="2.75rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="50%" height="0.875rem" />
                        <Skeleton width="30%" height="1.5rem" />
                    </div>
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Completed</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ stats.completed }}
                        </div>
                    </div>
                    <div
                        class="p-3 bg-purple-50 dark:bg-purple-950/40 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/50 rounded-xl transition-colors">
                        <CheckCircle2 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700/70 shadow-sm overflow-hidden flex flex-col">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white !text-lg">Website Sales and Promotions Completed Per Month</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-center">
                    <div v-if="loading" class="flex flex-col gap-3">
                        <Skeleton height="12rem" width="100%" borderRadius="12px" />
                    </div>
                    <CampaignTimelineChart v-else :campaigns="campaigns" />
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700/70 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h3 class="font-semibold text-gray-900 dark:text-white !text-lg">Promotions</h3>
                </div>
                <div class="p-6 flex-1">
                    <template v-if="loading">
                        <div class="flex flex-col gap-4 w-full">
                            <Skeleton height="2.5rem" width="100%" borderRadius="8px" />
                            <Skeleton height="1.5rem" width="70%" />
                            <Skeleton height="1.5rem" width="90%" />
                            <Skeleton height="8rem" width="100%" borderRadius="8px" />
                        </div>
                    </template>
                    <Tabs v-else v-model:value="activeTab" class="w-full">
                        <TabList class="mb-4">
                            <Tab value="0">Internal Promotions</Tab>
                            <Tab value="1">External Promotions</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="0">
                                <InternalPromotions />
                            </TabPanel>
                            <TabPanel value="1">
                                <ExternalPromotions v-if="activeTab === '1'" />
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showModal" modal :draggable="false" :closable="false" class="campaign-dialog"
            :style="{ width: '720px', maxHeight: '85vh' }">
            <template #header>
                <div class="flex justify-between items-center w-full px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <h2 class="!text-lg font-bold capitalize dark:text-white text-gray-900 tracking-wide">
                            {{ selectedStatus }} Campaigns
                        </h2>
                    </div>
                    <Button icon="pi pi-times" text rounded severity="secondary" @click="showModal = false" />
                </div>
            </template>

            <div class="p-4 space-y-4">
                <div class="relative">
                    <input v-model="searchTerm" type="text" placeholder="Search campaign name or channel..."
                        class="w-full pl-10 pr-4 py-2.5 dark:bg-gray-800 bg-gray-100 dark:text-gray-100 text-gray-900 placeholder-gray-400 dark:placeholder-gray-500 rounded-lg text-sm border border-transparent focus:border-blue-500 focus:bg-white dark:focus:bg-gray-900 transition-all focus:outline-none" />
                    <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
                </div>

                <!-- Data Table -->
                <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700/60">
                    <DataTable :value="filteredCampaignsSorted" scrollable scrollHeight="50vh" stripedRows removableSort
                        size="small" :rowHover="true" responsiveLayout="scroll">
                        <Column field="name" header="Campaign" sortable>
                            <template #body="{ data }">
                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ data.channel_name ? `${data.channel_name} - ${data.name}` : data.name }}
                                </span>
                            </template>
                        </Column>

                        <Column field="start_date" header="Start Date" sortable>
                            <template #body="{ data }">
                                <span class="text-xs font-mono text-gray-600 dark:text-gray-400">
                                    {{ new Date(data.start_date).toLocaleDateString("en-GB") }}
                                </span>
                            </template>
                        </Column>

                        <Column field="end_date" header="End Date" sortable>
                            <template #body="{ data }">
                                <span class="text-xs font-mono text-gray-600 dark:text-gray-400">
                                    {{ new Date(data.end_date).toLocaleDateString("en-GB") }}
                                </span>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No campaigns found matching your search.
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </Dialog>
    </div>
</template>
<script setup>
import { ref, onMounted, computed, nextTick, watch } from "vue";
import InternalPromotions from "@/js/components/dashboard/InternalPromotions.vue";
import ExternalPromotions from "@/js/components/dashboard/ExternalPromotions.vue";
import CampaignCalendar from "@/js/components/dashboard/CampaignCalendar.vue";
import { fetchCampaigns } from "@/js/api/campaign_service.js";
import {
    ListChecks,
    PlayCircle,
    CalendarClock,
    CheckCircle2,
    Search
} from "lucide-vue-next";
import Skeleton from "primevue/skeleton";
import CampaignTimelineChart from "@/js/components/CampaignTimelineChart.vue";

import Tabs from "primevue/tabs";
import TabList from "primevue/tablist";
import Tab from "primevue/tab";
import TabPanels from "primevue/tabpanels";
import TabPanel from "primevue/tabpanel";
import Dialog from "primevue/dialog";

const activeTab = ref("0");
const searchTerm = ref("");
const loading = ref(true);
const stats = ref({
    total: 0,
    active: 0,
    upcoming: 0,
    completed: 0,
});
const campaigns = ref([]);
const showModal = ref(false);
const selectedStatus = ref("");
const filteredCampaigns = ref([]);

function openModal(status) {
    selectedStatus.value = status;
    showModal.value = true;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (status === "total") {
        filteredCampaigns.value = campaigns.value;
    } else if (status === "active") {
        filteredCampaigns.value = campaigns.value.filter((c) => {
            const start = new Date(c.start_date);
            const end = new Date(c.end_date);
            start.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);
            return today >= start && today <= end;
        });
    } else if (status === "upcoming") {
        filteredCampaigns.value = campaigns.value.filter((c) => {
            const start = new Date(c.start_date);
            start.setHours(0, 0, 0, 0);
            return start > today;
        });
    } else if (status === "completed") {
        filteredCampaigns.value = campaigns.value.filter((c) => {
            const end = new Date(c.end_date);
            end.setHours(0, 0, 0, 0);
            return end < today;
        });
    }
}

const filteredCampaignsSorted = computed(() => {
    if (!filteredCampaigns.value) return [];

    const term = searchTerm.value.toLowerCase();

    return filteredCampaigns.value.filter((c) =>
        c?.name?.toLowerCase().includes(term)
    );
});



onMounted(async () => {
    try {
        loading.value = true;

        const result = await fetchCampaigns();
        await nextTick();

        setTimeout(() => {
            campaigns.value = result;
            stats.value.total = result.length;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let active = 0;
            let upcoming = 0;
            let completed = 0;

            for (let i = 0; i < result.length; i++) {
                const c = result[i];
                const start = new Date(c.start_date);
                const end = new Date(c.end_date);

                start.setHours(0, 0, 0, 0);
                end.setHours(0, 0, 0, 0);

                if (today >= start && today <= end) {
                    active++;
                } else if (start > today) {
                    upcoming++;
                } else if (end < today) {
                    completed++;
                }
            }

            stats.value.active = active;
            stats.value.upcoming = upcoming;
            stats.value.completed = completed;

            loading.value = false;
        }, 0);

    } catch (err) {
        console.error("Failed to fetch campaigns:", err);
        loading.value = false;
    }
});

watch(activeTab, async () => {
    await nextTick();
});
</script>
<style scoped>
:deep(.campaign-dialog .p-dialog-content) {
    background-color: #111827;
    color: white;
    padding: 0;
    border-radius: 0.75rem;
}

:deep(.campaign-dialog .p-dialog-header) {
    background-color: #1f2937;
    border-bottom: 1px solid #374151;
    padding: 1rem;
}

:deep(.p-dialog-mask) {
    background-color: rgba(0, 0, 0, 0.7);
}

.list-enter-active,
.list-leave-active {
    transition: all 0.25s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.list-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
