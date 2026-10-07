<template>
    <div class="flex flex-col bg-gray-100 dark:bg-gray-900">
        <div class="bg-white dark:bg-gray-800 mb-5 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm text-gray-900 dark:text-white text-center tracking-wide drop-shadow-sm py-3">
                Website Sales, Promotions, Marketplaces Campaigns
            </h4>

            <div class="flex flex-wrap items-center gap-2 ml-5 pb-3">
                <Select v-model="selectedChannel" :options="channels" optionLabel="name" optionValue="channel_id"
                    placeholder="All Channels" class="w-48" showClear />

                <InputText v-model="searchTerm" placeholder="Search Campaigns..." class="w-60" />

                <DatePicker v-model="dateRange" selectionMode="range" dateFormat="yy-mm-dd"
                    placeholder="Filter by Date Range" class="w-64" showIcon />

                <Button label="Reset" icon="pi pi-refresh" class="p-button-secondary" @click="resetFilters" />
            </div>
        </div>

        <div class="gantt-wrapper relative w-full overflow-auto touch-pan-y">
            <div ref="ganttContainer"
                class="gantt-container w-full bg-white dark:bg-gray-900 h-[calc(100vh-250px)]"></div>

            <div v-show="loading"
                class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex flex-col gap-4 justify-center items-center z-10">
                <p class="text-gray-600 dark:text-gray-300 text-lg">
                    Loading calendar...
                </p>

                <Skeleton height="2rem" width="70%" />
                <Skeleton height="2rem" width="50%" />
                <Skeleton height="1rem" width="90%" />
                <Skeleton height="1rem" width="85%" />
                <Skeleton height="1rem" width="95%" />
            </div>
        </div>
    </div>
</template>
<script setup>
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Skeleton from "primevue/skeleton";
import { useCampaignGantt } from "@/js/composables/useCampaignGantt.js";

const {
  loading,
  ganttContainer,
  selectedChannel,
  searchTerm,
  dateRange,
  channels,
  resetFilters,
} = useCampaignGantt();

</script>

<style>
.gantt-container {
    width: 100%;
    min-height: 500px;
}

.dark .gantt_container,
.dark .gantt_layout_root,
.dark .gantt_grid,
.dark .gantt_grid_scale,
.dark .gantt_grid_data,
.dark .gantt_data_area,
.dark .gantt_task_scale,
.dark .gantt_task_bg,
.dark .gantt_task,
.dark .gantt_scale_line,
.dark .gantt_task_cell,
.dark .gantt_task_row,
.dark .gantt_task_bg,
.dark .gantt_task_content {
    background: #111827 !important;
    border-color: #374151 !important;
}

.dark .gantt_grid_head_cell,
.dark .gantt_grid_scale {
    background: #1f2937 !important;
    color: #e5e7eb !important;
}

.dark .gantt_tree_content,
.dark .gantt_grid_head_text,
.dark .gantt_task_content,
.dark .gantt_scale_cell {
    color: #e5e7eb !important;
}

.dark .gantt_task_bg {
    background: #111827 !important;
}

.dark .gantt_today_line {
    background: #60a5fa !important;
}

.dark .gantt_lightbox {
    background: #111827 !important;
    color: #e5e7eb !important;
}

.hide-add-btn .gantt_add {
    display: none !important;
}
</style>
