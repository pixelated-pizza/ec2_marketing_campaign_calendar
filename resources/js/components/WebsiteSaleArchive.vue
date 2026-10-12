<template>
    <div class="wsa-wrapper flex flex-col h-[calc(100vh-120px)] min-h-[500px] dark:bg-gray-900 bg-white p-5">
        <h4 class="font-semibold text-lg text-center p-2 app-dark:text-white">
            Archived Website Sale Details
        </h4>

        <template v-if="loading">
            <div class="flex flex-col gap-4 w-full h-full">
                <p class="text-gray-400 text-lg">Loading Data Table...</p>
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

        <div v-else class="flex flex-col flex-1 min-h-0">
            <div class="mb-2 flex justify-content-between flex-wrap gap-4 w-full">
                <div class="flex items-center w-full md:w-auto">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText placeholder="Search archived sale" v-model="searchQuery" />
                    </IconField>
                </div>
                <div class="flex items-center gap-2">
                    <label for="campaignFilter" class="text-black dark:text-white select-none mr-2">
                        Filter Campaigns:
                    </label>
                    <Select
                        v-model="campaignFilter"
                        inputId="campaignFilter"
                        class="w-48"
                        :options="[
                            { label: 'All', value: 'ALL' },
                            { label: 'Running', value: 'RUNNING' },
                            { label: 'Ended', value: 'ENDED' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                    />
                </div>
            </div>

            <!-- GRID: the old expandable row details are now regular columns -->
            <div
                class="relative flex-1 min-h-[300px] mt-3 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                :data-ag-theme-mode="isDark ? 'dark' : 'light'"
            >
                <AgGridVue
                    style="position: absolute; width: 100%; height: 100%; left: 0; top: 0"
                    :theme="gridTheme"
                    :columnDefs="columnDefs"
                    :rowData="filteredCampaigns"
                    :defaultColDef="defaultColDef"
                    :getRowId="getRowId"
                    :pagination="true"
                    :paginationPageSize="15"
                    :paginationPageSizeSelector="[15, 30, 50]"
                    :enableCellTextSelection="true"
                    :ensureDomOrder="true"
                    :suppressCellFocus="true"
                    :localeText="{ noRowsToShow: 'No archived sales available' }"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { AgGridVue } from "ag-grid-vue3";
import Skeleton from "primevue/skeleton";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import { useArchiveWebsiteSalesStore } from "@/js/stores/archive_website_sales_store";
import { useAgGridTheme } from "@/js/composables/useAgGridTheme.js";
import ExcelSetFilter from "@/js/components/utils/ExcelSetFilter.vue";

const archivedStore = useArchiveWebsiteSalesStore();

const { isDark, gridTheme } = useAgGridTheme();

const loading = ref(false);
const searchQuery = ref("");
const campaignFilter = ref("ALL");

// ---------------------------------------------------------------------------
// Existing logic (unchanged)
// ---------------------------------------------------------------------------
const replacer = (val) => (val == null || val === "" ? "No Data Yet" : val);

const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

const getStatus = (data) => {
    if (!data.start_date || !data.end_date) return "UPCOMING";
    const today = new Date();
    const start = new Date(data.start_date);
    const end = new Date(data.end_date);
    if (today >= start && today <= end) return "RUNNING";
    if (today > end) return "ENDED";
    return "UPCOMING";
};

const filteredCampaigns = computed(() => {
    let campaigns = [...archivedStore.archivedWebsiteSales];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        campaigns = campaigns.filter(
            (c) =>
                c.name?.toLowerCase().includes(q) ||
                c.store_name?.toLowerCase().includes(q) ||
                String(c.start_date).toLowerCase().includes(q) ||
                String(c.end_date).toLowerCase().includes(q),
        );
    }
    if (campaignFilter.value === "RUNNING") {
        campaigns = campaigns.filter((c) => getStatus(c) === "RUNNING");
    } else if (campaignFilter.value === "ENDED") {
        campaigns = campaigns.filter((c) => getStatus(c) === "ENDED");
    }
    campaigns.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
    return campaigns;
});

onMounted(async () => {
    loading.value = true;
    await archivedStore.loadArchivedWebsiteSales();
    loading.value = false;
});

// ---------------------------------------------------------------------------
// Grid
// ---------------------------------------------------------------------------
const getRowId = (params) => String(params.data.campaign_id);

const timestamp = (row, key) => (row && row[key] ? new Date(row[key]).getTime() : 0);

const STATUS_CLASSES = {
    RUNNING: "bg-green-500 text-white",
    ENDED: "bg-red-600 text-white",
    UPCOMING: "bg-yellow-500 text-black",
};

function statusRenderer(params) {
    const el = document.createElement("span");
    el.className = `inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${
        STATUS_CLASSES[params.value] || "bg-blue-500 text-white"
    }`;
    el.textContent = params.value;
    return el;
}

// Text with clickable URLs. Built with DOM nodes (no innerHTML), line breaks kept via pre-wrap.
function linkifyRenderer(params) {
    const el = document.createElement("div");
    el.style.whiteSpace = "pre-wrap";

    const text = params.data?.[params.colDef.context?.key] ?? "";
    if (!text) {
        el.textContent = "No Data Yet";
        return el;
    }

    String(text)
        .split(/(https?:\/\/[^\s)<>"']+)/g)
        .forEach((part) => {
            if (/^https?:\/\//.test(part)) {
                const a = document.createElement("a");
                a.href = part;
                a.target = "_blank";
                a.rel = "noopener";
                a.className = "text-blue-400 underline";
                a.textContent = part;
                el.appendChild(a);
            } else if (part) {
                el.appendChild(document.createTextNode(part));
            }
        });

    return el;
}

// A sheet / document URL shown as a link when present
function sheetLinkRenderer(params) {
    const url = params.data?.[params.colDef.context?.key];
    if (!url) {
        const span = document.createElement("span");
        span.textContent = "No Data Yet";
        return span;
    }

    const a = document.createElement("a");
    a.href = url;
    a.target = "_blank";
    a.rel = "noopener";
    a.className = params.colDef.context?.linkClass || "text-green-600 dark:text-green-400 hover:underline";
    a.textContent = url;
    return a;
}

const textColumn = (key, headerName, extra = {}) => ({
    colId: key,
    headerName,
    valueGetter: (p) => replacer(p.data?.[key]),
    width: 260,
    wrapText: true,
    autoHeight: true,
    cellStyle: { whiteSpace: "pre-wrap" },
    ...extra,
});

const defaultColDef = {
    sortable: true,
    resizable: true,
    filter: ExcelSetFilter,
    floatingFilter: false,
    minWidth: 110,
    wrapHeaderText: true,
    autoHeaderHeight: true,
};

const columnDefs = [
    {
        colId: "status",
        headerName: "Status",
        valueGetter: (p) => p.data?.status,
        comparator: (a, b, nodeA, nodeB) => (nodeA.data?.statusOrder ?? 0) - (nodeB.data?.statusOrder ?? 0),
        cellRenderer: statusRenderer,
        pinned: "left",
        width: 130,
    },
    { colId: "store_name", headerName: "Channel", field: "store_name", width: 170, wrapText: true, autoHeight: true },
    {
        colId: "name",
        headerName: "Event Name",
        field: "name",
        pinned: "left",
        width: 260,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "start_date",
        headerName: "Event Start Date",
        valueGetter: (p) => formatDate(p.data?.start_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "start_date") - timestamp(nodeB.data, "start_date"),
        width: 230,
    },
    {
        colId: "end_date",
        headerName: "Event End Date",
        valueGetter: (p) => formatDate(p.data?.end_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "end_date") - timestamp(nodeB.data, "end_date"),
        width: 230,
    },

    // ---- fields that used to live in the expanded row ----
    {
        ...textColumn("featured_banner_text", "Featured Category Banners Text", { width: 320 }),
        cellRenderer: linkifyRenderer,
        context: { key: "featured_banner_text" },
    },
    textColumn("sku_in_category_creative", "SKU Feature in Category Creative", { width: 300 }),
    textColumn("url_text", "URL Text in Landing Page Link", { width: 300 }),
    {
        ...textColumn("featured_products_sheet_url", "Products to be included in featured category (URL of the sheet)", {
            width: 320,
        }),
        cellRenderer: sheetLinkRenderer,
        context: { key: "featured_products_sheet_url" },
    },
    {
        ...textColumn("run_sheet", "Website Sale Execution - Run Sheet", { width: 300 }),
        cellRenderer: sheetLinkRenderer,
        context: { key: "run_sheet" },
    },
    {
        ...textColumn("event_master_sheet", "Event Master Sheet", { width: 300 }),
        cellRenderer: sheetLinkRenderer,
        context: { key: "event_master_sheet", linkClass: "text-blue-600 dark:text-blue-400 hover:underline" },
    },
    textColumn("ess", "ESS to Execute", { width: 240 }),
    textColumn("cms_to_audit", "CMS to Audit", { width: 240 }),
    textColumn("terms_conditions", "T&C's", { width: 300 }),
    textColumn("mockup_banner_locations", "Mock Up & Banner Locations", { width: 300 }),
    textColumn("is_sku_list_to_feature", "SKU List to Feature Provided?", { width: 200 }),
];
</script>

<style>
.wsa-wrapper .ag-cell {
    display: flex;
    align-items: center;
    line-height: 1.45;
    padding-top: 6px;
    padding-bottom: 6px;
}

.wsa-wrapper .ag-cell.ag-cell-wrap-text {
    word-break: break-word;
}
</style>
