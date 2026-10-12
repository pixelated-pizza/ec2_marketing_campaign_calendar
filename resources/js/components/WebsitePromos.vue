<template>
    <BlockUI :blocked="processing" fullScreen>
        <div class="wp-wrapper flex flex-col h-[calc(100vh-120px)] min-h-[500px] overflow-auto p-3 card">
            <h4 class="text-gray-200 font-semibold text-lg text-center p-2 mt-5">
                Website Promotions Details
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
                    <div class="flex justify-between items-center mb-5">
                        <!-- SEARCH -->
                        <span class="p-input-icon-left w-80">
                            <InputText v-model="globalFilter" placeholder="Search promotions..." class="w-full" />
                        </span>

                        <Button
                            icon="pi pi-folder"
                            v-if="selectedPromos.length > 0"
                            label="Archive Selected"
                            severity="contrast"
                            :disabled="!selectedPromos.length"
                            @click="openBulkArchiveDialog"
                        />
                    </div>

                    <!-- GRID -->
                    <div
                        class="relative flex-1 min-h-[300px] border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                        :data-ag-theme-mode="isDark ? 'dark' : 'light'"
                    >
                        <AgGridVue
                            style="position: absolute; width: 100%; height: 100%; left: 0; top: 0"
                            :theme="gridTheme"
                            :columnDefs="columnDefs"
                            :rowData="mergedPromotions"
                            :defaultColDef="defaultColDef"
                            :getRowId="getRowId"
                            :rowSelection="rowSelection"
                            :selectionColumnDef="selectionColumnDef"
                            :quickFilterText="globalFilter"
                            :pagination="true"
                            :paginationPageSize="20"
                            :paginationPageSizeSelector="[20, 50, 100]"
                            :enableCellTextSelection="true"
                            :ensureDomOrder="true"
                            :suppressCellFocus="true"
                            :localeText="{ noRowsToShow: 'No promotions yet' }"
                            @grid-ready="onGridReady"
                            @selection-changed="onSelectionChanged"
                        />
                    </div>

                    <Dialog
                        v-model:visible="editVisible"
                        modal
                        header="Edit Website Promotion"
                        :style="{ width: '640px' }"
                        class="p-fluid"
                    >
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-col gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Promotion Name
                                    </label>
                                    <InputText v-model="editForm.promo_name" class="w-full" />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Website Store
                                    </label>
                                    <div>
                                        <div class="flex flex-col gap-3">
                                            <div
                                                v-for="store in stores"
                                                :key="store.store_id"
                                                class="flex items-center gap-3"
                                            >
                                                <Checkbox v-model="editForm.website_stores" :value="store.store_id" />
                                                <span class="text-sm text-gray-300">
                                                    {{ store.store_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Description
                                    </label>
                                    <Textarea v-model="editForm.description" rows="3" autoResize class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Terms & Conditions (if applied)
                                    </label>
                                    <Textarea
                                        v-model="editForm.terms_and_conditions"
                                        rows="3"
                                        autoResize
                                        class="w-full"
                                    />
                                </div>
                            </div>
                            <div class="border-t border-gray-700 pt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Coupon Label
                                    </label>
                                    <InputText v-model="editForm.coupon_label" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Coupon Code
                                    </label>
                                    <InputText v-model="editForm.coupon_code" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        Start Date
                                    </label>
                                    <DatePicker
                                        v-model="editForm.start_date"
                                        showTime
                                        hourFormat="12"
                                        stepMinute="1"
                                        stepSecond="1"
                                        fluid
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1">
                                        End Date
                                    </label>
                                    <DatePicker
                                        v-model="editForm.end_date"
                                        showTime
                                        hourFormat="12"
                                        stepMinute="1"
                                        stepSecond="1"
                                        fluid
                                    />
                                </div>
                            </div>
                            <div class="flex gap-8 pt-2">
                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="editForm.does_include_parts" binary />
                                    <span class="text-sm text-gray-300">Includes Parts</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox v-model="editForm.does_include_marketplace_products" binary />
                                    <span class="text-sm text-gray-300">Includes Marketplace</span>
                                </div>
                            </div>
                        </div>
                        <!-- FOOTER -->
                        <template #footer>
                            <div class="flex justify-end gap-3">
                                <Button label="Cancel" text severity="secondary" @click="editVisible = false" />
                                <Button
                                    label="Save Changes"
                                    icon="pi pi-check"
                                    severity="success"
                                    :loading="processing"
                                    :disabled="processing"
                                    raised
                                    @click="saveEdit"
                                />
                            </div>
                        </template>
                    </Dialog>
                </div>

                <Dialog
                    v-model:visible="showArchiveDialog"
                    header="Confirm Archive"
                    :modal="true"
                    :closable="false"
                    :style="{ width: '400px' }"
                >
                    <p>
                        Are you sure you want to archive these
                        <strong>{{ selectedPromos.length }}</strong>
                        selected promotion(s)?
                    </p>

                    <template #footer>
                        <Button label="Cancel" severity="primary" @click="showArchiveDialog = false" />
                        <Button
                            label="Yes, Archive"
                            severity="danger"
                            :loading="processing"
                            :disabled="processing"
                            @click="archiveConfirmed"
                        />
                    </template>
                </Dialog>
            </div>
        </div>
    </BlockUI>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, getCurrentInstance, computed } from "vue";
import dayjs from "dayjs";
import { useWebsitePromosStore } from "@/js/stores/website_promos_store.js";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import Skeleton from "primevue/skeleton";
import DatePicker from "primevue/datepicker";
import BlockUI from "primevue/blockui";
import { AgGridVue } from "ag-grid-vue3";
import {
    ModuleRegistry,
    AllCommunityModule,
    themeQuartz,
    colorSchemeDark,
    colorSchemeLight,
} from "ag-grid-community";
import ExcelSetFilter from "@/js/components/utils/ExcelSetFilter.vue";

import {
    createWebsitePromotion,
    updateWebsitePromotion,
    deleteWebsitePromotion,
    archiveWebsitePromotion,
} from "@/js/api/website_promos_api";
import { fetchStores } from "@/js/api/website_campaign_api.js";
const websitePromosStore = useWebsitePromosStore();
import { useUIStore } from "@/js/stores/ui.js";

ModuleRegistry.registerModules([AllCommunityModule]);

const ui = useUIStore();

const stores = ref([]);
const $instance = getCurrentInstance();
const toastr = $instance.appContext.config.globalProperties.$toastr;

const editVisible = ref(false);
const editForm = ref({});

const showArchiveDialog = ref(false);

const selectedPromos = ref([]);
const globalFilter = ref("");

const loading = ref(true);

const processing = ref(false);

let gridApi = null;

const isDark = ref(document.documentElement.classList.contains("app-dark"));
let themeObserver = null;

const gridTheme = computed(() =>
    themeQuartz.withPart(isDark.value ? colorSchemeDark : colorSchemeLight).withParams({
        fontFamily: "Arial, sans-serif",
        fontSize: 13,
        spacing: 6,
        headerFontSize: 13,
        headerFontWeight: 600,
        wrapperBorder: false,
        borderColor: { ref: "foregroundColor", mix: 0.22 },
        rowBorder: true,
        columnBorder: true,
        headerColumnBorder: true,
        headerRowBorder: true,
    }),
);

const getRowId = (params) => params.data.promo_name;

const rowSelection = {
    mode: "multiRow",
    checkboxes: true,
    headerCheckbox: true,
    selectAll: "filtered",
    enableClickSelection: false,
};

const selectionColumnDef = { pinned: "left", width: 48, resizable: false };

function onGridReady(params) {
    gridApi = params.api;
}

function onSelectionChanged() {
    selectedPromos.value = gridApi ? gridApi.getSelectedRows() : [];
}

const editPromo = (mergedPromo) => {
    openEditDialog(mergedPromo);
};

const openEditDialog = (mergedPromo) => {
    editForm.value = {
        ...mergedPromo,
        promoRows: mergedPromo.promoRows || [],
        website_stores: mergedPromo.storeIds || [],
        start_date: mergedPromo.start_date ? dayjs(mergedPromo.start_date).toDate() : null,
        end_date: mergedPromo.end_date ? dayjs(mergedPromo.end_date).toDate() : null,
    };

    editVisible.value = true;
};
const saveEdit = async () => {
    processing.value = true;
    ui.showLoader();
    try {
        const selectedStores = editForm.value.website_stores || [];

        const existingPromos = websitePromosStore.websitePromotions.filter(
            (p) => p.promo_name === editForm.value.promo_name,
        );

        for (const promo of existingPromos) {
            const storeId = promo.store.store_id;

            if (!selectedStores.includes(storeId)) {
                await deleteWebsitePromotion(promo.promo_id);
                ui.hideLoader();
            } else {
                const payload = {
                    promo_name: editForm.value.promo_name,
                    description: editForm.value.description,
                    terms_and_conditions: editForm.value.terms_and_conditions,
                    coupon_label: editForm.value.coupon_label,
                    coupon_code: editForm.value.coupon_code,
                    start_date: editForm.value.start_date
                        ? dayjs(editForm.value.start_date).format("YYYY-MM-DD HH:mm:ss")
                        : null,
                    end_date: editForm.value.end_date
                        ? dayjs(editForm.value.end_date).format("YYYY-MM-DD HH:mm:ss")
                        : null,
                    does_include_parts: editForm.value.does_include_parts,
                    does_include_marketplace_products: editForm.value.does_include_marketplace_products,
                    website_store: storeId,
                };

                await updateWebsitePromotion(promo.promo_id, payload);

                await websitePromosStore.loadWebsitePromotions(true);

                ui.hideLoader();
            }
        }

        const existingStoreIds = existingPromos.map((p) => p.store.store_id);
        const newStores = selectedStores.filter((id) => !existingStoreIds.includes(id));

        for (const storeId of newStores) {
            const payload = {
                promo_name: editForm.value.promo_name,
                description: editForm.value.description,
                terms_and_conditions: editForm.value.terms_and_conditions,
                coupon_label: editForm.value.coupon_label,
                coupon_code: editForm.value.coupon_code,
                start_date: editForm.value.start_date
                    ? dayjs(editForm.value.start_date).format("YYYY-MM-DD HH:mm:ss")
                    : null,
                end_date: editForm.value.end_date
                    ? dayjs(editForm.value.end_date).format("YYYY-MM-DD HH:mm:ss")
                    : null,

                does_include_parts: editForm.value.does_include_parts,
                does_include_marketplace_products: editForm.value.does_include_marketplace_products,
                website_store: storeId,
            };

            await createWebsitePromotion(payload);
        }

        await websitePromosStore.loadWebsitePromotions(true);
        editVisible.value = false;
        toastr.success("Promotion updated successfully.");
    } catch (error) {
        console.error("Failed to update promotion", error);
        toastr.error("Failed to update promotion. See console for details.");
    } finally {
        processing.value = false;
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

const openBulkArchiveDialog = () => {
    showArchiveDialog.value = true;
};

const getPromoStatus = (promo) => {
    if (!promo.start_date || !promo.end_date) return "UPCOMING";

    const today = dayjs();
    const start = dayjs(promo.start_date);
    const end = dayjs(promo.end_date);

    if (today.isAfter(end)) return "ENDED";
    if (today.isBefore(start)) return "UPCOMING";
    return "RUNNING";
};

const archiveConfirmed = async () => {
    processing.value = true;
    try {
        // read the live selection from the grid so we never archive stale rows
        const rows = gridApi ? gridApi.getSelectedRows() : selectedPromos.value;

        for (const mergedPromo of rows) {
            for (const row of mergedPromo.promoRows) {
                await archiveWebsitePromotion(row.promo_id);
            }
        }

        await websitePromosStore.loadWebsitePromotions();
        toastr.success("Selected promotions archived successfully.");

        gridApi?.deselectAll();
        selectedPromos.value = [];
    } catch (error) {
        console.error("Archive failed:", error);
        toastr.error("Failed to archive promotions.");
    } finally {
        processing.value = false;
        showArchiveDialog.value = false;
    }
};

const mergedPromotions = computed(() => {
    const map = {};

    websitePromosStore.websitePromotions.forEach((promo) => {
        const key = promo.promo_name;
        if (!map[key]) {
            map[key] = { ...promo, stores: [], storeIds: [], promoRows: [] };
        }

        if (promo.store?.store_name) {
            map[key].stores.push(promo.store.store_name.replace(/^Website - /, ""));
        }

        if (promo.store?.store_id) {
            map[key].storeIds.push(promo.store.store_id);
        }

        map[key].promoRows.push(promo);
    });

    return Object.values(map).map((promo) => ({
        ...promo,
        storeNames: promo.stores.length ? promo.stores.join(" & ") : "-",
        status: getPromoStatus(promo),
        storeIds: promo.storeIds,
        promoRows: promo.promoRows,
        promoIds: promo.promoRows.map((p) => p.promo_id),
    }));
});

onMounted(async () => {
    loading.value = true;

    themeObserver = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains("app-dark");
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });

    try {
        await Promise.all([websitePromosStore.loadWebsitePromotions(true), loadStores()]);
    } catch (err) {
        console.error("Failed to load data:", err);
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
});

const statusBadge = (status) => {
    switch (status) {
        case "RUNNING":
            return "bg-green-400 text-black px-2 py-1 rounded";
        case "ENDED":
            return "bg-red-600 text-white px-2 py-1 rounded font-bold";
        case "UPCOMING":
            return "bg-yellow-500 text-black px-2 py-1 rounded";
        default:
            return "bg-gray-300 px-2 py-1 rounded";
    }
};

const formatDate = (value) => (value ? dayjs(value).format("MMMM D, YYYY [at] hh:mm A") : "—");
const timestamp = (row, key) => (row && row[key] ? dayjs(row[key]).valueOf() : 0);
const joinIfArray = (value) => (Array.isArray(value) ? value.join(", ") : value);

function statusRenderer(params) {
    const el = document.createElement("span");
    el.className = `text-md font-bold ${statusBadge(params.value)}`;
    el.textContent = params.value;
    return el;
}

function couponCodeRenderer(params) {
    const el = document.createElement("span");
    el.className =
        "inline-block rounded-full bg-gray-900 px-2.5 py-0.5 text-xs font-semibold text-white dark:bg-white dark:text-black";
    el.textContent = params.value;
    return el;
}

function badgeRenderer(classes) {
    return (params) => {
        const el = document.createElement("span");
        el.className = `inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${classes}`;
        el.textContent = params.value;
        return el;
    };
}

function scopeRenderer(params) {
    const wrap = document.createElement("div");
    wrap.className = "flex flex-wrap gap-2";

    (params.value || "").split(" | ").forEach((label) => {
        const included = label.endsWith("Included");
        const el = document.createElement("span");
        el.className = `text-md px-2 py-1 rounded font-bold ${
            included ? "bg-green-400 text-black" : "bg-red-600 dark:text-white"
        }`;
        el.textContent = label;
        wrap.appendChild(el);
    });

    return wrap;
}

function actionsRenderer(params) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
        "inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium text-emerald-500 hover:bg-emerald-500/10";
    btn.innerHTML = '<i class="pi pi-pencil"></i><span>Edit</span>';
    btn.addEventListener("click", () => editPromo(params.data));
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
        colId: "status",
        headerName: "Status",
        valueGetter: (p) => p.data?.status,
        cellRenderer: statusRenderer,
        pinned: "left",
        width: 130,
    },
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
        colId: "coupon_code",
        headerName: "Coupon Code",
        valueGetter: (p) => p.data?.coupon_code || "N/A",
        cellRenderer: couponCodeRenderer,
        width: 150,
    },
    {
        colId: "coupon_label",
        headerName: "Coupon Label",
        valueGetter: (p) => p.data?.coupon_label || "—",
        width: 150,
    },
    {
        colId: "description",
        headerName: "Description",
        valueGetter: (p) => p.data?.description || "No description available",
        width: 300,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "terms_and_conditions",
        headerName: "Terms & Conditions",
        valueGetter: (p) => joinIfArray(p.data?.terms_and_conditions) || "No T&Cs",
        width: 300,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "creatives",
        headerName: "Creatives",
        valueGetter: (p) => joinIfArray(p.data?.creatives) || "N/A",
        width: 200,
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
        width: 360,
        autoHeight: true,
    },
    {
        colId: "storeNames",
        headerName: "Website",
        valueGetter: (p) => p.data?.storeNames,
        width: 170,
        wrapText: true,
        autoHeight: true,
    },
    {
        colId: "start_date",
        headerName: "Start Date",
        valueGetter: (p) => formatDate(p.data?.start_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "start_date") - timestamp(nodeB.data, "start_date"),
        cellRenderer: badgeRenderer("bg-green-400 text-gray-900"),
        width: 240,
    },
    {
        colId: "end_date",
        headerName: "End Date",
        valueGetter: (p) => formatDate(p.data?.end_date),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "end_date") - timestamp(nodeB.data, "end_date"),
        cellRenderer: badgeRenderer("bg-red-500 text-black"),
        width: 240,
    },
    {
        colId: "updated_at",
        headerName: "Last Updated",
        valueGetter: (p) => formatDate(p.data?.updated_at),
        comparator: (a, b, nodeA, nodeB) => timestamp(nodeA.data, "updated_at") - timestamp(nodeB.data, "updated_at"),
        cellClass: "text-xs text-gray-500",
        width: 230,
    },
    {
        colId: "actions",
        headerName: "Actions",
        cellRenderer: actionsRenderer,
        pinned: "right",
        width: 120,
        sortable: false,
        filter: false,
        resizable: false,
    },
];
</script>

<style>
.wp-wrapper .ag-cell {
    display: flex;
    align-items: center;
    line-height: 1.45;
    padding-top: 6px;
    padding-bottom: 6px;
}

.wp-wrapper .ag-cell.ag-cell-wrap-text {
    word-break: break-word;
}
</style>
