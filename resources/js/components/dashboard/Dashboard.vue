<template>
    <div class="flex flex-col h-auto max-h-[88vh] overflow-auto p-5 card">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
                class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition"
                @click="openModal('total')"
            >
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2 dark:bg-gray-500/50 bg-black rounded-lg">
                        <ListChecks class="w-6 h-6 text-gray-300" />
                    </div>
                    <div>
                        <div class="dark:text-white text-black text-sm">
                            Total Campaigns
                        </div>
                        <div
                            class="text-2xl font-bold dark:text-white text-black mt-1"
                        >
                            {{ stats.total }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                class=" dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition"
                @click="openModal('active')"
            >
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2 dark:bg-green-600/60 bg-green rounded-lg">
                        <PlayCircle class="w-6 h-6 text-green-400" />
                    </div>
                    <div>
                        <div class="dark:text-white text-black text-sm">
                            Current Active Campaigns
                        </div>
                        <div
                            class="text-2xl font-bold dark:text-white text-black mt-1"
                        >
                            {{ stats.active }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition"
                @click="openModal('upcoming')"
            >
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2 bg-blue-600/20 rounded-lg">
                        <CalendarClock class="w-6 h-6 text-blue-400" />
                    </div>
                    <div>
                        <div class="dark:text-white text-black text-sm">
                            Upcoming
                        </div>
                        <div
                            class="text-2xl font-bold dark:text-white text-black mt-1"
                        >
                            {{ stats.upcoming }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="dark:bg-gray-800/50 bg-white rounded-xl p-5 flex flex-col items-start transition"
                @click="openModal('completed')"
            >
                <div v-if="loading" class="flex items-center gap-3 w-full">
                    <Skeleton shape="circle" size="2.5rem" />
                    <div class="flex flex-col gap-2 w-full">
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="1.5rem" />
                    </div>
                </div>

                <div v-else class="flex items-center gap-3">
                    <div class="p-2 bg-purple-600/20 rounded-lg">
                        <CheckCircle2 class="w-6 h-6 text-purple-400" />
                    </div>
                    <div>
                        <div class="dark:text-white text-black text-sm">
                            Completed
                        </div>
                        <div
                            class="text-2xl font-bold dark:text-white text-black mt-1"
                        >
                            {{ stats.completed }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <CampaignTimelineChart v-if="!loading" :campaigns="campaigns" />

        <Dialog
            v-model:visible="showModal"
            modal
            :draggable="false"
            :closable="false"
            class="campaign-dialog min-h-[200px]"
            :style="{ width: '480px', maxHeight: '85vh' }"
        >
            <template #header>
                <div class="flex justify-between items-center w-full">
                    <h2
                        class="text-xl font-bold capitalize text-white tracking-wide"
                    >
                        {{ selectedStatus }} Campaigns
                    </h2>
                    <button
                        @click="showModal = false"
                        class="text-gray-400 hover:text-white text-xl font-bold transition"
                    >
                        ✕
                    </button>
                </div>
            </template>
            <div class="px-3 pb-3 mt-5">
                <input
                    v-model="searchTerm"
                    type="text"
                    placeholder="Search campaign..."
                    class="w-full px-3 py-2 bg-gray-800 text-gray-200 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div class="p-2 overflow-y-auto max-h-[65vh] space-y-3">
                <transition-group name="list" tag="div">
                    <div
                        v-for="c in filteredCampaignsSorted"
                        :key="c.id"
                        class="cursor-pointer p-4 bg-gray-800 rounded-lg border border-gray-700 hover:border-blue-500 hover:bg-gray-700 transition duration-300 ease-in-out group"
                    >
                        <div class="flex justify-between items-center mb-1">
                            <h3
                                class="font-semibold text-white group-hover:text-blue-400 transition"
                            >
                                {{
                                    c.channel_name
                                        ? `${c.channel_name} - ${c.name}`
                                        : c.name
                                }}
                            </h3>
                            <span
                                class="text-xs px-2 py-1 rounded-full"
                                :class="{
                                    'bg-green-600/20 text-green-400 border border-green-600/40':
                                        selectedStatus === 'active',
                                    'bg-yellow-600/20 text-yellow-400 border border-yellow-600/40':
                                        selectedStatus === 'upcoming',
                                    'bg-red-600/20 text-red-400 border border-red-600/40':
                                        selectedStatus === 'expired',
                                }"
                            >
                                {{ selectedStatus }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{
                                new Date(c.start_date).toLocaleDateString(
                                    "en-GB",
                                )
                            }}
                            →
                            {{
                                new Date(c.end_date).toLocaleDateString("en-GB")
                            }}
                        </div>
                    </div>
                </transition-group>

                <p
                    v-if="filteredCampaigns.length === 0"
                    class="text-gray-400 text-sm text-center mt-4"
                >
                    No campaigns found.
                </p>
            </div>
        </Dialog>
        <div class="mt-5">
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
