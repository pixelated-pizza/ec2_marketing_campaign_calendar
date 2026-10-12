<template>
    <div class="wpa-wrapper flex flex-col w-full h-[calc(100vh-120px)] min-h-[500px] card px-6 py-5">
        <h4 class="text-gray-200 font-semibold text-lg text-center p-2 mt-5">
            Archived Website Promotions
        </h4>

        <div class="flex flex-col flex-1 min-h-0">
            <template v-if="loading">
                <div class="flex flex-col gap-4 w-full h-full mt-5">
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

            <div v-else class="flex flex-col flex-1 min-h-0">
                <div
                    class="relative flex-1 min-h-[300px] border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                    :data-ag-theme-mode="isDark ? 'dark' : 'light'"
                >
                    <AgGridVue
                        style="position: absolute; width: 100%; height: 100%; left: 0; top: 0"
                        :theme="gridTheme"
                        :columnDefs="columnDefs"
                        :rowData="archiveStore.archivedPromotions"
                        :defaultColDef="defaultColDef"
                        :getRowId="getRowId"
                        :pagination="true"
                        :paginationPageSize="20"
                        :paginationPageSizeSelector="[20, 50, 100]"
                        :enableCellTextSelection="true"
                        :ensureDomOrder="true"
                        :suppressCellFocus="true"
                        :localeText="{ noRowsToShow: 'No archived promotions yet' }"
                    />
                </div>

                <Dialog
                    v-model:visible="showArchiveDialog"
                    header="Confirm Unarchive"
                    :modal="true"
                    :closable="false"
                    :style="{ width: '400px' }"
                >
                    <p>Are you sure you want to archive this campaign?</p>
                    <template #footer>
                        <Button label="No" severity="danger" text @click="showArchiveDialog = false" />
                        <Button label="Yes" severity="success" @click="unArchiveConfirmed" />
                    </template>
                </Dialog>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, getCurrentInstance } from "vue";
import dayjs from "dayjs";
import { AgGridVue } from "ag-grid-vue3";
import { useArchivePromosStore } from "@/js/stores/archive_promos_store.js";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import Skeleton from "primevue/skeleton";
import { useAgGridTheme } from "@/js/composables/useAgGridTheme.js";
import ExcelSetFilter from "@/js/components/utils/ExcelSetFilter.vue";

import { fetchUnarchivePromotion } from "@/js/api/archive_promos_api";
import { fetchStores } from "@/js/api/website_campaign_api.js";
const archiveStore = useArchivePromosStore();

const { isDark, gridTheme } = useAgGridTheme();

const stores = ref([]);
const $instance = getCurrentInstance();
const toastr = $instance.appContext.config.globalProperties.$toastr;

const showArchiveDialog = ref(false);
const promoToUnarchive = ref(null);

const loading = ref(true);

const confirmUnarchive = (promo) => {
    promoToUnarchive.value = promo;
    showArchiveDialog.value = true;
};

const unArchiveConfirmed = async () => {
    try {
        console.log("Unarchived Promo ID:", promoToUnarchive.value.promo_id);

        await fetchUnarchivePromotion(promoToUnarchive.value.promo_id);
        await archiveStore.loadArchivedPromotions();

        toastr.success("Promotion unarchived successfully.");
    } catch (error) {
        console.error("Failed to unarchive promotion", error);
        toastr.error("Failed to unarchive promotion.");
    } finally {
        showArchiveDialog.value = false;
        promoToUnarchive.value = null;
    }
};

const loadStores = async () => {
    try {
        const data = await fetchStores();
        stores.value = data.map((store) => ({
            store_id: store.store_id,
            store_name: store.store_name,
        }));
    } catch (error) {
        console.error("Failed to load stores", error);
    }
};

onMounted(async () => {
    loading.value = true;

    await Promise.all([archiveStore.loadArchivedPromotions(), loadStores()]);

    loading.value = false;
});

const statusBadge = (status) => {
    switch (status) {
        case "ACTIVE":
            return "bg-green-500 text-gray-800 px-2 py-1 rounded";
        case "ENDED":
            return "bg-red-600 text-gray-800 px-2 py-1 rounded font-bold";
        case "UP NEXT":
            return "bg-yellow-500 text-black px-2 py-1 rounded";
        default:
            return "bg-gray-300 px-2 py-1 rounded";
    }
};

const getRowId = (params) => String(params.data.promo_id);

const joinIfArray = (value) => (Array.isArray(value) ? value.join(", ") : value);
const formatDay = (value) => (value ? dayjs.utc(value).tz().format("DD-MM-YYYY") : "-");
const timestamp = (row, key) => (row && row[key] ? dayjs(row[key]).valueOf() : 0);

function statusRenderer(params) {
    const el = document.createElement("span");
    el.className = `text-md font-bold ${statusBadge(params.value)}`;
    el.textContent = params.value;
    return el;
}

function scopeRenderer(params) {
    const wrap = document.createElement("div");
    wrap.className = "flex flex-wrap gap-2";

    (params.value || "").split(" | ").forEach((label) => {
        const included = label.endsWith("Included");
        const el = document.createElement("span");
        el.className = `text-md px-2 py-1 rounded ${included ? "bg-green-600" : "bg-red-600 text-white"}`;
        el.textContent = label;
        wrap.appendChild(el);
    });

    return wrap;
}

function unarchiveRenderer(params) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
        "inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium text-amber-500 hover:bg-amber-500/10";
    btn.innerHTML = '<i class="pi pi-folder"></i><span>Unarchive</span>';
    btn.addEventListener("click", () => confirmUnarchive(params.data));
    return btn;
}

const defaultColDef = {
    sortable: true,
    resizable: true,
    filter: ExcelSetFilter,
    floatingFilter: false,
    minWidth: 100,
    wrapHeaderText: true,
    autoHeaderHeight: true,
};

const columnDefs = [
    {
        colId: "promo_name",
        headerName: "Promotion",
        field: "promo_name",
        pinned: "left",
        width: 300,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "scope",
        headerName: "Scope",
        valueGetter: (p) =>
            [
                p.data?.does_include_parts ? "Parts Included" : "Parts Excluded",
                p.data?.does_include_marketplace_products ? "Marketplace Included" : "Marketplace Excluded",
            ].join(" | "),
        cellRenderer: scopeRenderer,
        width: 340,
        autoHeight: true,
    },
    {
        colId: "website_store",
        headerName: "Website",
        valueGetter: (p) => p.data?.website_store || "-",
        width: 160,
    },
    {
        colId: "status",
        headerName: "Status",
        valueGetter: (p) => p.data?.status,
        cellRenderer: statusRenderer,
        width: 130,
    },
    {
        colId: "description",
        headerName: "Description",
        valueGetter: (p) => p.data?.description || "No description",
        width: 300,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "terms_and_conditions",
        headerName: "Terms & Conditions",
        valueGetter: (p) => joinIfArray(p.data?.terms_and_conditions) || "-",
        width: 300,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "coupon_label",
        headerName: "Coupon Label",
        valueGetter: (p) => p.data?.coupon_label || "-",
        width: 150,
    },
    {
        colId: "coupon_code",
        headerName: "Coupon Code",
        valueGetter: (p) => p.data?.coupon_code || "-",
        width: 150,
    },
    {
        colId: "creatives",
        headerName: "Creatives",
        valueGetter: (p) => joinIfArray(p.data?.creatives) || "-",
        width: 200,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "start_date",
        headerName: "Start Date",
        valueGetter: (p) => formatDay(p.data?.start_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "start_date") - timestamp(nodeB.data, "start_date"),
        cellClass: "text-green-500",
        width: 140,
    },
    {
        colId: "end_date",
        headerName: "End Date",
        valueGetter: (p) => formatDay(p.data?.end_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "end_date") - timestamp(nodeB.data, "end_date"),
        cellClass: "text-green-500",
        width: 140,
    },
    {
        colId: "updated_at",
        headerName: "Last Updated",
        valueGetter: (p) => (p.data?.updated_at ? dayjs(p.data.updated_at).format("DD-MM-YYYY HH:mm") : "-"),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "updated_at") - timestamp(nodeB.data, "updated_at"),
        cellClass: "text-xs text-gray-400",
        width: 160,
    },
    {
        colId: "actions",
        headerName: "Actions",
        cellRenderer: unarchiveRenderer,
        pinned: "right",
        width: 150,
        sortable: false,
        filter: false,
        resizable: false,
    },
];
</script>

<style>
.wpa-wrapper .ag-cell {
    display: flex;
    align-items: center;
    line-height: 1.45;
    padding-top: 6px;
    padding-bottom: 6px;
}

.wpa-wrapper .ag-cell.ag-cell-wrap-text {
    word-break: break-word;
}
</style>
