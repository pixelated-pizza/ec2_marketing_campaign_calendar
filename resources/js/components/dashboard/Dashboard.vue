<template>
    <div class="p-5 card min-h-[88vh]">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition-all duration-200 border border-gray-200 dark:border-gray-700/60 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer hover:-translate-y-0.5"
                @click="openModal('total')">
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2.5 dark:bg-gray-500/30 bg-gray-100 rounded-lg">
                        <ListChecks class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                    </div>
                    <div>
                        <div class="dark:text-gray-400 text-gray-500 text-sm font-medium">
                            Total Campaigns
                        </div>
                        <div class="text-2xl font-bold dark:text-white text-black mt-1">
                            {{ stats.total }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition-all duration-200 border border-gray-200 dark:border-gray-700/60 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer hover:-translate-y-0.5"
                @click="openModal('active')">
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2.5 dark:bg-green-500/20 bg-green-100 rounded-lg">
                        <PlayCircle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <div class="dark:text-gray-400 text-gray-500 text-sm font-medium">
                            Current Active Campaigns
                        </div>
                        <div class="text-2xl font-bold dark:text-white text-black mt-1">
                            {{ stats.active }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition-all duration-200 border border-gray-200 dark:border-gray-700/60 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer hover:-translate-y-0.5"
                @click="openModal('upcoming')">
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2.5 dark:bg-blue-500/20 bg-blue-100 rounded-lg">
                        <CalendarClock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <div class="dark:text-gray-400 text-gray-500 text-sm font-medium">
                            Upcoming
                        </div>
                        <div class="text-2xl font-bold dark:text-white text-black mt-1">
                            {{ stats.upcoming }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition-all duration-200 border border-gray-200 dark:border-gray-700/60 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer hover:-translate-y-0.5"
                @click="openModal('completed')">
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2.5 dark:bg-purple-500/20 bg-purple-100 rounded-lg">
                        <CheckCircle2 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <div class="dark:text-gray-400 text-gray-500 text-sm font-medium">
                            Completed
                        </div>
                        <div class="text-2xl font-bold dark:text-white text-black mt-1">
                            {{ stats.completed }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-row gap-2">
            <div class="w-1/2 border">
                <CampaignTimelineChart v-if="!loading" :campaigns="campaigns" />

            </div>
            <div class="w-1/2 border">
                <div>
                    <template v-if="loading">
                        <div class="flex flex-col gap-4 w-full mt-5">
                            <p class="text-gray-400 text-lg">Loading Data...</p>
                            <Skeleton height="2rem" width="70%" />
                            <Skeleton height="2rem" width="50%" />
                            <Skeleton height="1rem" width="90%" />
                            <Skeleton height="1rem" width="85%" />
                            <Skeleton height="1rem" width="95%" />
                            <div class="flex-1 mt-2">
                                <Skeleton height="100%" borderRadius="8px" />
                            </div>
                        </div>
                    </template>
                    <Tabs v-else v-model:value="activeTab">
                        <TabList>
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


        <Dialog v-model:visible="showModal" modal :draggable="false" :closable="false"
            class="campaign-dialog min-h-[200px]" :style="{ width: '700px', maxHeight: '85vh' }">
            <template #header>
                <div class="flex justify-between items-center w-full">
                    <h2 class="text-xl font-bold capitalize dark:text-white text-gray-900 tracking-wide">
                        {{ selectedStatus }} Campaigns
                    </h2>

                    <Button icon="pi pi-times" text rounded severity="secondary" @click="showModal = false" />
                </div>
            </template>

            <div class="px-3 pb-3 mt-5">
                <input v-model="searchTerm" type="text" placeholder="Search campaign..."
                    class="w-full px-3 py-2 dark:bg-gray-800 bg-gray-100 dark:text-gray-200 text-gray-800 placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="p-2">
                <DataTable :value="filteredCampaignsSorted" scrollable scrollHeight="60vh" stripedRows showGridlines
                    removableSort size="small" :rowHover="true" responsiveLayout="scroll">
                    <Column field="name" header="Campaign" sortable>
                        <template #body="{ data }">
                            <span class="font-medium">
                                {{
                                    data.channel_name
                                        ? `${data.channel_name} - ${data.name}`
                                        : data.name
                                }}
                            </span>
                        </template>
                    </Column>

                    <Column field="start_date" header="Start Date" sortable>
                        <template #body="{ data }">
                            {{ new Date(data.start_date).toLocaleDateString("en-GB") }}
                        </template>
                    </Column>

                    <Column field="end_date" header="End Date" sortable>
                        <template #body="{ data }">
                            {{ new Date(data.end_date).toLocaleDateString("en-GB") }}
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No campaigns found.
                        </div>
                    </template>
                </DataTable>
            </div>
        </Dialog>

    </div>
</template>
<script setup>
import { ref, onMounted, computed, nextTick, watch } from "vue";
import InternalPromotions from "@/js/components/dashboard/InternalPromotions.vue";
import ExternalPromotions from "@/js/components/dashboard/ExternalPromotions.vue";
import { fetchCampaigns } from "@/js/api/campaign_service.js";
import {
    ListChecks,
    PlayCircle,
    CalendarClock,
    CheckCircle2,
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
