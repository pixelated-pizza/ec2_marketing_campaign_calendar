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
            <div ref="ganttContainer" style="min-height: 500px; height: 600px"
                class="gantt-container w-full bg-white dark:bg-gray-900"></div>

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
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

let gantt = window.gantt;

import Select from "primevue/select";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import Skeleton from "primevue/skeleton";
import { useUIStore } from "@/js/stores/ui.js";
import { getCurrentInstance } from "vue";
const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;

const loading = ref(true);
const ui = useUIStore();

import { debounce } from "lodash";

import {
    fetchCampaigns,
    createCampaign,
    updateCampaign,
    deleteCampaign,
    fetchChannels,
} from "@/js/api/campaign_service";

const ganttContainer = ref(null);
const hiddenCampaigns = ref(new Set());
const selectedChannel = ref(null);
const searchTerm = ref("");
let allCampaigns = [];
const dateRange = ref(null);
const newTasks = new Set();
const justCreatedTasks = new Set();
const channels = ref([]);
let resizeObserver;

function formatDateForDB(date) {
    const d = new Date(date.getTime() - 1000);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day} 23:59:59`;
}

function formatLocalDateTime(date) {
    const d = new Date(date);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const h = String(d.getHours()).padStart(2, "0");
    const min = String(d.getMinutes()).padStart(2, "0");
    return `${y}-${m}-${day} ${h}:${min}`;
}

function applyGanttTheme() {
    const isDark = document.documentElement.classList.contains("app-dark");

    if (isDark) {
        gantt.setSkin("dark");
    } else {
        gantt.setSkin("meadow");
    }

    requestAnimationFrame(() => {
        gantt.render();
        gantt.setSizes();
    });
}

function autoAdjustTimeline(filteredTasks = []) {
    if (!filteredTasks.length) return;

    let minDate = new Date();
    let maxDate = new Date();

    filteredTasks.forEach((task) => {
        if (!task.start_date || !task.end_date) return;
        const start = new Date(task.start_date);
        const end = new Date(task.end_date);
        if (isNaN(start) || isNaN(end)) return;
        if (start < minDate) minDate = start;
        if (end > maxDate) maxDate = end;
    });

    const padding = 3;
    minDate = new Date(minDate.getTime() - padding * 86400000);
    maxDate = new Date(maxDate.getTime() + padding * 86400000);

    gantt.config.start_date = minDate;
    gantt.config.end_date = maxDate;

    gantt.render();
    gantt.setSizes();
}

gantt.config.time_step = 1;

async function initGantt() {
    gantt.config.grid_resize = true;
    gantt.config.grid_buttons = true;

    gantt.templates.grid_row_class = (start, end, task) => {
        return task.type === "project" ? "" : "hide-add-btn";
    };

    gantt.config.columns = [
        {
            name: "text",
            label: "Campaigns",
            tree: true,
            width: window.innerWidth < 640 ? 180 : 400,
            template: (task) =>
                task.type !== "project"
                    ? `<span style="margin-left:8px">${task.text}</span>`
                    : `<b>${task.text}</b>`,
        },
        {
            name: "add",
            label: "",
            width: 44,
            resize: false,
            template: (task) =>
                task.type === "project" ? "<div class='add_child'>+</div>" : "",
        },
    ];

    gantt.config.date_format = "%Y-%m-%d %H:%i";
    gantt.config.xml_date = "%Y-%m-%d %H:%i";
    gantt.config.drag_move = true;
    gantt.config.drag_resize = true;
    gantt.config.drag_links = true;

    const fmt = gantt.date.date_to_str("%Y-%m-%d %H:%i");

    gantt.templates.tooltip_text = (start, end, task) => `
        Task: <b>${task.text}</b><br/>
        Start: <b>${fmt(start)}</b><br/>
        End: <b>${fmt(end)}</b><br/>
        Progress: <b>${Math.round((task.progress || 0) * 100)}%</b>
    `;

    gantt.config.scales = [
        { unit: "day", step: 1, format: "%d %M" },
    ];

    gantt.config.scale_height = 60;
    gantt.config.work_time = true;
    gantt.config.skip_off_time = true;

    gantt.config.lightbox.sections = [
        {
            name: "description",
            height: 70,
            map_to: "text",
            type: "textarea",
            label: "Campaign Name / Promo",
        },
        {
            name: "time",
            type: "time",
            map_to: "auto",
            time_format: ["%d", "%m", "%Y", "%H:%i"],
            label: "Time period (Start - End)",
        },
    ];

    gantt.init(ganttContainer.value);

    ganttContainer.value.addEventListener("change", (e) => {
        if (e.target.classList.contains("hide-checkbox")) {
            const taskId = e.target.dataset.taskId;
            const checked = e.target.checked;
            if (checked) {
                hiddenCampaigns.value.add(taskId);
            } else {
                hiddenCampaigns.value.delete(taskId);
            }
            applyHiddenCampaigns();
        }
    });


    gantt.attachEvent("onBeforeRender", () => {
        console.trace("🔁 gantt.render() called from:");
        return true;
    });

    gantt.attachEvent("onAfterTaskUpdate", async (id, task) => {
        if (newTasks.has(id) || justCreatedTasks.has(id)) return;
        ui.showLoader();
        task.parent = `channel_${task.channel_id}`;
        try {
            await updateCampaign(id, {
                name: task.text,
                start_date: formatLocalDateTime(task.start_date),
                end_date: formatLocalDateTime(task.end_date),
                channel_id: task.channel_id,
                background_color: task.color || null,
            });
            toastr.success("Campaign updated successfully.");
        } catch (err) {
            console.error("Error updating campaign:", err);
            toastr.error("Failed to update campaign.");
        } finally {
            justCreatedTasks.delete(id);
            ui.hideLoader();
        }
    });

    gantt.attachEvent("onAfterTaskDelete", async (id, task) => {
        if (newTasks.has(id)) {
            newTasks.delete(id);
            return;
        }
        if (task.type !== "project") {
            ui.showLoader();
            try {
                await deleteCampaign(id);
                toastr.success("Campaign deleted successfully.");
            } catch (err) {
                console.error("Failed to delete campaign:", err);
                toastr.error("Failed to delete campaign.");
            } finally {
                ui.hideLoader();
            }
        }
    });

    gantt.attachEvent("onBeforeTaskDelete", function (id, task) {
        if (task.type === "project" && !task.$virtual) {
            toastr.warning("Channels cannot be deleted.");
            return false;
        }
        return true;
    });

    gantt.attachEvent("onTaskCreated", function (task) {
        task.type = "task";
        task.text = "New Campaign";
        task.start_date = new Date();
        task.end_date = new Date(task.start_date.getTime() + 13 * 24 * 60 * 60 * 1000);
        if (task.parent) {
            task.channel_id = task.parent.replace("channel_", "");
        }
        newTasks.add(task.id);
        return true;
    });

    gantt.attachEvent("onLightboxSave", async (id, task) => {
        const defaultColor = "#60A5FA";
        ui.showLoader();

        if (newTasks.has(id)) {
            try {
                const payload = {
                    name: task.text || "New Campaign / Promo",
                    start_date: formatLocalDateTime(task.start_date),
                    end_date: formatDateForDB(task.end_date),
                    channel_id: task.channel_id,
                    background_color: task.color || defaultColor,
                };
                const saved = await createCampaign(payload);
                gantt.changeTaskId(id, saved.campaign_id);
                newTasks.delete(id);
                justCreatedTasks.add(saved.campaign_id);
                toastr.success("Campaign created successfully.");
            } catch (err) {
                console.error("Error creating campaign:", err);
                task.failedToSave = true;
                gantt.updateTask(id);
                toastr.error("Failed to create campaign. Retry saving.");
                ui.hideLoader();
                return false;
            }
            ui.hideLoader();
            return false;
        }

        try {
            await updateCampaign(id, {
                name: task.text,
                start_date: formatLocalDateTime(task.start_date),
                end_date: formatDateForDB(task.end_date),
                channel_id: task.channel_id,
                background_color: task.color || defaultColor,
            });
            toastr.success("Campaign updated successfully.");
        } catch (err) {
            console.error("Error updating campaign:", err);
            toastr.error("Failed to update campaign.");
            ui.hideLoader();
            return false;
        }

        ui.hideLoader();
        return true;
    });
}

async function loadCampaigns() {
    try {
        const campaigns = await fetchCampaigns();
        allCampaigns = campaigns;
        renderFilteredCampaigns();
    } catch (err) {
        console.error("Failed to load campaigns:", err);
    }
}

function renderFilteredCampaigns() {
    if (!channels.value.length) return;

    // 1. Sort Channels
    const preferredOrder = ["Edisons", "Mytopia"];
    const sortedChannels = [...channels.value].sort((a, b) => {
        const ai = preferredOrder.indexOf(a.name);
        const bi = preferredOrder.indexOf(b.name);
        if (ai !== -1 && bi !== -1) return ai - bi;
        if (ai !== -1) return -1;
        if (bi !== -1) return 1;
        return a.name.localeCompare(b.name);
    });

    const channelMap = {};
    const data = [];
    const recurringMap = new Map();

    // 2. Add Top-Level Channel Folders
    sortedChannels.forEach((c) => {
        const parentId = `channel_${c.channel_id}`;
        channelMap[String(c.channel_id)] = parentId;
        data.push({
            id: parentId,
            text: c.name,
            channel_id: String(c.channel_id),
            type: gantt.config.types.project,
            open: true,
            hide_bar: true,
            readonly: true,
            start_date: new Date(),
            end_date: new Date(new Date().getTime() + 86400000 * 365),
        });
    });

    // 3. Filter Campaigns (Default to Current Month if no date picker range selected)
    const [startFilter, endFilter] = dateRange.value || [];
    
    // Calculate start and end bounds of current month
    const now = new Date();
    const currentMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const currentMonthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);

    const filtered = allCampaigns.filter((c) => {
        const matchChannel = selectedChannel.value
            ? c.channel_id === selectedChannel.value
            : true;
        const matchSearch = searchTerm.value
            ? c.name.toLowerCase().includes(searchTerm.value.toLowerCase())
            : true;

        const campaignStart = new Date(c.start_date);
        const campaignEnd = new Date(c.end_date);

        // Date Filter Logic:
        let matchDate = true;
        if (startFilter && endFilter) {
            // User manually picked a date range
            matchDate = campaignEnd >= startFilter && campaignStart <= endFilter;
        } else {
            // Default: Only campaigns active during the current month
            matchDate = campaignEnd >= currentMonthStart && campaignStart <= currentMonthEnd;
        }

        return matchChannel && matchSearch && matchDate;
    });

    // 4. Nest Campaigns under Channels or Recurring Groups
    sortedChannels.forEach((c) => {
        filtered
            .filter((f) => String(f.channel_id) === String(c.channel_id))
            .forEach((campaign) => {
                const startDate = campaign.start_date ? new Date(campaign.start_date) : new Date();
                const endDate = campaign.end_date
                    ? new Date(campaign.end_date)
                    : new Date(startDate.getTime() + 86400000);
                if (isNaN(startDate) || isNaN(endDate)) return;

                const channelParentId = channelMap[String(campaign.channel_id)];
                if (!channelParentId) return;

                const cleanedName = campaign.name.replace(/\s*\d{4}/g, "").trim();
                const isRecurring = campaign.parent_campaign_id || campaign.is_recurring || (campaign.name !== cleanedName);

                let parentId = channelParentId;

                if (isRecurring) {
                    const groupKey = `${campaign.channel_id}_${cleanedName}`;
                    if (!recurringMap.has(groupKey)) {
                        const recurringGroupId = `group_${groupKey}`;
                        data.push({
                            id: recurringGroupId,
                            text: cleanedName,
                            type: gantt.config.types.project,
                            parent: channelParentId,
                            open: true,
                            hide_bar: true,
                            readonly: true,
                        });
                        recurringMap.set(groupKey, recurringGroupId);
                    }
                    parentId = recurringMap.get(groupKey);
                }

                data.push({
                    id: campaign.campaign_id,
                    text: campaign.name,
                    type: "task",
                    start_date: startDate,
                    end_date: endDate,
                    channel_id: String(campaign.channel_id),
                    color: campaign.background_color,
                    parent: parentId,
                });
            });
    });

    // 5. Render into Gantt
    gantt.clearAll();
    gantt.parse({ data });

    if (filtered.length) {
        setTimeout(() => {
            autoAdjustTimeline(filtered);
            applyHiddenCampaigns();
        }, 50);
    } else {
        gantt.render();
        applyHiddenCampaigns();
    }
}

function applyHiddenCampaigns() {
    gantt.eachTask((task) => {
        const row = gantt.getTaskRowNode(task.id);
        if (!row) return;
        if (hiddenCampaigns.value.has(task.id)) {
            row.style.display = "none";
            const bar = gantt.getTaskNode(task.id);
            if (bar) bar.style.display = "none";
        } else {
            row.style.display = "";
            const bar = gantt.getTaskNode(task.id);
            if (bar) bar.style.display = "";
        }
    });
}

watch([searchTerm, selectedChannel, dateRange], () => {
    if (!channels.value.length || !ganttInitialized) return;
    renderFilteredCampaigns();
});

function resetFilters() {
    searchTerm.value = "";
    selectedChannel.value = null;
    dateRange.value = null;
    renderFilteredCampaigns();
}

function watchDarkMode() {
    const observer = new MutationObserver(() => {
        applyGanttTheme();
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"],
    });
    return observer;
}

let darkObserver;
let ganttInitialized = false;

onMounted(async () => {
    if (ganttInitialized) return;
    ganttInitialized = true;
    try {
        loading.value = true;
        await nextTick();

        setTimeout(async () => {
    try {
        channels.value = await fetchChannels();
        darkObserver = watchDarkMode();

        if (!ganttContainer.value) {
            loading.value = false;
            return;
        }

        await initGantt();
        await loadCampaigns();

        // ✅ Apply skin LAST, after all renders are done
        const isDark = document.documentElement.classList.contains("app-dark");
        gantt.setSkin(isDark ? "dark" : "meadow");

        let resizeTimeout;
        resizeObserver = new ResizeObserver(() => {
            if (!ganttInitialized) return;
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => gantt.setSizes(), 100);
        });
        resizeObserver.observe(ganttContainer.value);
    } catch (err) {
        console.error("Error initializing gantt:", err);
    } finally {
        loading.value = false;
        await nextTick();
        gantt.setSizes();
    }
}, 0);
    } catch (err) {
        console.error(err);
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    try {
        if (resizeObserver) resizeObserver.disconnect();
        if (darkObserver) darkObserver.disconnect();
        gantt.clearAll();
        gantt.detachAllEvents();
    } catch (e) {
        console.warn("Failed to cleanup gantt", e);
    }
});
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
