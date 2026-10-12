<template>
    <div class="cfs-wrapper card">
        <!-- Header Actions -->
        <div class="flex items-center mb-4 gap-2">
            <Button label="+ Add Featured SKU" @click="openModal" />
            <Button label="+ Bulk Add SKUs" @click="bulkModalVisible = true" />
            <BulkAddFeaturedSkusModal v-model="bulkModalVisible" />
            <Button label="Clear All" icon="pi pi-trash" severity="danger" @click="clearAllDialogVisible = true" />
        </div>

        <div class="flex flex-col h-[calc(100vh-250px)] min-h-[400px]">
            <div class="items-center">
                <h4 class="text-white dark:text-white text-center font-bold text-md tracking-wide">
                    Price & Inventory Tracking
                </h4>
            </div>
            <span v-if="lastSynced" class="text-black dark:text-white text-md">
                Last synced: {{ lastSynced.toLocaleTimeString("en-AU") }}
            </span>

            <div class="relative flex-1 min-h-0 mt-2 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                :data-ag-theme-mode="isDark ? 'dark' : 'light'">
                <AgGridVue style="position: absolute; width: 100%; height: 100%; left: 0; top: 0" :theme="gridTheme"
                    :columnDefs="columnDefs" :rowData="sortedFeaturedSkus" :defaultColDef="defaultColDef"
                    :getRowId="getRowId" :getRowClass="getRowClass" :loading="store.isLoading"
                    :stopEditingWhenCellsLoseFocus="true" :singleClickEdit="false" :enterNavigatesVertically="true"
                    :enterNavigatesVerticallyAfterEdit="true" :enableCellTextSelection="true" :ensureDomOrder="true"
                    :localeText="{ noRowsToShow: 'No featured SKUs found.' }" @grid-ready="onGridReady"
                    @cell-value-changed="onCellValueChanged" />
            </div>
        </div>

        <Dialog v-model:visible="deleteDialogVisible" header="Confirm Deletion" modal :style="{ width: '400px' }">
            <p>Are you sure you want to delete this Featured SKU?</p>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="deleteDialogVisible = false" />
                <Button label="Delete" severity="danger" @click="deleteConfirmed" />
            </template>
        </Dialog>

        <!-- Add Featured SKU Modal -->
        <Dialog v-model:visible="modalVisible" header="Add Featured SKU" :style="{ width: '600px' }" modal>
            <div class="flex flex-col gap-4">
                <!-- SKU Lookup -->
                <div class="flex gap-2">
                    <div class="flex flex-col gap-1 flex-1">
                        <label class="font-medium">SKU <span class="text-red-500">*</span></label>
                        <InputText v-model="form.sku" placeholder="e.g. MOWRIDBMS32A" @keyup.enter="lookupSku" />
                    </div>
                    <div class="flex items-end">
                        <Button label="Lookup" :loading="lookingUp" @click="lookupSku" />
                    </div>
                </div>

                <!-- Neto Data Preview -->
                <div v-if="netoData" class="bg-gray-50 dark:bg-gray-900 rounded p-3 grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">RRP:</span>
                        {{ formatPrice(netoData.rrp) }}
                    </div>
                    <div>
                        <span class="text-gray-500">Website Price:</span>
                        {{ formatPrice(netoData.website_price) }}
                    </div>
                    <div>
                        <span class="text-gray-500">Special Price:</span>
                        {{
                            netoData.special_price
                                ? formatPrice(netoData.special_price)
                                : "—"
                        }}
                    </div>
                    <div>
                        <span class="text-gray-500">Stock:</span>
                        {{ netoData.stock ?? "0" }}
                    </div>
                </div>
                <small v-if="lookupError" class="text-red-500">{{
                    lookupError
                }}</small>

                <!-- Form Fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Category Name</label>
                        <InputText v-model="form.category_name" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Block ID</label>
                        <InputText v-model="form.block_id" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Block Name</label>
                        <InputText v-model="form.block_name" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Identifier</label>
                        <InputText v-model="form.identifier" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Type</label>
                        <InputText v-model="form.type" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Website</label>
                        <InputText v-model="form.website" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Product Landing Page</label>
                        <InputText v-model="form.product_landing_page" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-medium">Creative Location</label>
                        <InputText v-model="form.creative_location" />
                    </div>
                    <div class="flex flex-col gap-1 col-span-2">
                        <label class="font-medium">Landing Page</label>
                        <InputText v-model="form.landing_page" />
                    </div>
                    <div class="flex flex-col gap-1 col-span-2">
                        <label class="font-medium">Note</label>
                        <Textarea v-model="form.note" rows="3" />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="closeModal" />
                <Button label="Add Featured SKU" :loading="store.isSaving" :disabled="!netoData" @click="submit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="clearAllDialogVisible" header="Confirm Clear All" modal :style="{ width: '400px' }">
            <p>
                Are you sure you want to delete
                <strong>all Featured SKUs</strong>? This action cannot be
                undone.
            </p>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="clearAllDialogVisible = false" />
                <Button label="Delete All" severity="danger" @click="clearAllConfirmed" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useCategoryFeaturedSkusStore } from "@/js/stores/category_featured_skus_store.js";
import { useNetoStore } from "@/js/stores/neto_store.js";
import { getCurrentInstance } from "vue";
import BulkAddFeaturedSkusModal from "@/js/components/category_featured_skus/BulkAddFeaturedSkusModal.vue";
import { AgGridVue } from "ag-grid-vue3";
import { useAgGridTheme } from "@/js/composables/useAgGridTheme.js";
import ExcelSetFilter from "@/js/components/utils/ExcelSetFilter.vue";
const bulkModalVisible = ref(false);

const { isDark, gridTheme } = useAgGridTheme();
let gridApi = null;

const stockSnapshot = ref({});

const store = useCategoryFeaturedSkusStore();
const netoStore = useNetoStore();
const lastSynced = ref(null);
let syncInterval = null;

const deleteDialogVisible = ref(false);
const skuToDelete = ref(null);
const clearAllDialogVisible = ref(false);

const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;

const today = new Date().toLocaleDateString("en-AU", {
    day: "2-digit",
    month: "short",
    year: "numeric",
});

function getStockDeducted(data) {
    const baseline = stockSnapshot.value[data.sku_featured_id];
    if (baseline === undefined) return null;
    return baseline - (data.qty ?? 0);
}

function saveSnapshot(skus) {
    const snapshot = {};
    skus.forEach((sku) => {
        // Only record if not already saved — preserve the original baseline
        if (stockSnapshot.value[sku.sku_featured_id] === undefined) {
            snapshot[sku.sku_featured_id] = sku.qty ?? 0;
        } else {
            snapshot[sku.sku_featured_id] =
                stockSnapshot.value[sku.sku_featured_id];
        }
    });
    stockSnapshot.value = snapshot;
    localStorage.setItem(
        "featured_skus_stock_snapshot",
        JSON.stringify(snapshot),
    );
}

function loadSnapshot() {
    const saved = localStorage.getItem("featured_skus_stock_snapshot");
    if (saved) {
        stockSnapshot.value = JSON.parse(saved);
    }
}

onMounted(() => {
    loadSnapshot(); // Load existing snapshot first

    store.loadFeaturedSkus().then(() => {
        saveSnapshot(store.allFeaturedSkus); // Fill in any missing SKUs
        gridApi?.refreshCells({ force: true });
        lastSynced.value = new Date();
    });

    syncInterval = setInterval(
        () => {
            store.loadFeaturedSkus(true);
            lastSynced.value = new Date();
        },
        15 * 60 * 1000,
    );
});

onUnmounted(() => {
    clearInterval(syncInterval);
});

// Modal state
const modalVisible = ref(false);
const lookingUp = ref(false);
const lookupError = ref(null);
const netoData = ref(null);

const defaultForm = () => ({
    sku: "",
    category_name: "",
    block_id: "",
    block_name: "",
    identifier: "",
    type: "",
    website: "",
    product_landing_page: "",
    creative_location: "",
    landing_page: "",
    note: "",
});

const editableFields = [
    "category_name",
    "block_id",
    "block_name",
    "identifier",
    "type",
    "website",
    "product_landing_page",
    "creative_location",
    "landing_page",
    "note",
];

const form = ref(defaultForm());

function openModal() {
    form.value = defaultForm();
    netoData.value = null;
    lookupError.value = null;
    modalVisible.value = true;
}

function closeModal() {
    modalVisible.value = false;
}

async function lookupSku() {
    if (!form.value.sku.trim()) return;

    lookingUp.value = true;
    lookupError.value = null;
    netoData.value = null;

    try {
        await netoStore.loadProducts(form.value.sku.trim(), true);
        const product = netoStore.allProducts[0];

        if (!product) {
            lookupError.value = `SKU "${form.value.sku}" not found in Neto.`;
            return;
        }

        netoData.value = product;
    } catch (e) {
        lookupError.value = "Failed to fetch SKU from Neto.";
    } finally {
        lookingUp.value = false;
    }
}

async function submit() {
    if (!netoData.value) return;

    await store.addFeaturedSku({
        ...form.value,
        rrp: netoData.value.rrp,
        website_price: netoData.value.website_price,
        special_price: netoData.value.special_price,
        qty: netoData.value.stock,
    });

    closeModal();
}

// AG Grid has already written the new value into the row; just persist it
async function onCellValueChanged(event) {
    const { data, newValue, oldValue, colDef } = event;
    const field = colDef.field;

    if (!editableFields.includes(field)) return;
    if (newValue === oldValue) return;

    await store.updateFeaturedSku(data.sku_featured_id, {
        [field]: newValue,
    });
}

async function clearAllConfirmed() {
    await store.deleteAllSkus();
    clearAllDialogVisible.value = false;

    toastr.success("All Featured SKUs have been deleted.");
}

function confirmDelete(skuId) {
    skuToDelete.value = skuId;
    deleteDialogVisible.value = true;
}

async function deleteConfirmed() {
    if (!skuToDelete.value) return;

    await store.removeFeaturedSku(skuToDelete.value);
    deleteDialogVisible.value = false;
    skuToDelete.value = null;

    toastr.success("Featured SKU deleted successfully.");
}

function formatPrice(value) {
    return new Intl.NumberFormat("en-AU", {
        style: "currency",
        currency: "AUD",
    }).format(value);
}

const sortedFeaturedSkus = computed(() =>
    [...store.allFeaturedSkus].sort((a, b) => (a.qty ?? 0) - (b.qty ?? 0)),
);

function stockRowClass(data) {
    const qty = data.qty ?? 0;

    if (qty <= 5) {
        return "low-stock-row";
    }

    return "";
}

// ---------------------------------------------------------------------------
// Grid
// ---------------------------------------------------------------------------
const getRowId = (params) => String(params.data.sku_featured_id);
const getRowClass = (params) => stockRowClass(params.data);

function onGridReady(params) {
    gridApi = params.api;
}

const num = (value) => Number(value) || 0;

const moneyColumn = (key, headerName, width, { blankWhenEmpty = false } = {}) => ({
    colId: key,
    headerName,
    valueGetter: (p) => {
        const v = p.data?.[key];
        if (blankWhenEmpty ? !v : v == null || v === "") return "";
        return formatPrice(v);
    },
    comparator: (a, b, nodeA, nodeB) => num(nodeA.data?.[key]) - num(nodeB.data?.[key]),
    width,
});

const signedMoney = (v) => (v > 0 ? "+" : "") + formatPrice(v);

const textColumn = (field, headerName, width) => ({
    colId: field,
    field,
    headerName,
    width,
    editable: true,
});

function deleteRenderer(params) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.title = "Delete";
    btn.setAttribute("aria-label", "Delete featured SKU");
    btn.className = "inline-flex h-8 w-8 items-center justify-center rounded-full text-white hover:bg-gray-100 dark:hover:bg-gray-800";
    btn.innerHTML = '<i class="pi pi-trash"></i>';
    btn.addEventListener("click", () => confirmDelete(params.data.sku_featured_id));
    return btn;
}

const defaultColDef = {
    sortable: true,
    resizable: true,
    filter: ExcelSetFilter,
    floatingFilter: false,
    minWidth: 90,
    wrapHeaderText: true,
    autoHeaderHeight: true,
};

const columnDefs = [
    { colId: "sku", field: "sku", headerName: "SKU", pinned: "left", width: 150 },
    moneyColumn("rrp", "RRP", 110),
    moneyColumn("website_price", "Website Price", 130),
    moneyColumn("special_price", "Special Price", 130, { blankWhenEmpty: true }),
    {
        colId: "price_change",
        headerName: "Price Change",
        valueGetter: (p) => {
            const v = p.data?.price_change;
            return v && v != 0 ? signedMoney(v) : "";
        },
        comparator: (a, b, nodeA, nodeB) => num(nodeA.data?.price_change) - num(nodeB.data?.price_change),
        cellClass: (p) => (!p.value ? "" : num(p.data?.price_change) > 0 ? "text-green-600" : "text-red-500"),
        width: 130,
    },
    {
        colId: "qty",
        headerName: `SOH as of ${today}`,
        valueGetter: (p) => String(p.data?.qty ?? "0"),
        comparator: (a, b, nodeA, nodeB) => num(nodeA.data?.qty) - num(nodeB.data?.qty),
        cellClass: (p) => ((p.data?.qty ?? 0) <= 0 ? "text-red-500" : ""),
        width: 150,
    },
    {
        colId: "stock_deducted",
        headerName: "Stock Deducted",
        valueGetter: (p) => {
            const d = getStockDeducted(p.data);
            return d > 0 ? `\u2212${d}` : "";
        },
        comparator: (a, b, nodeA, nodeB) => num(getStockDeducted(nodeA.data)) - num(getStockDeducted(nodeB.data)),
        cellClass: (p) => (p.value ? "text-red-500 font-medium" : ""),
        width: 140,
    },
    {
        colId: "stock_added",
        headerName: "Stock Added",
        valueGetter: (p) => {
            const d = getStockDeducted(p.data);
            return d < 0 ? `+${Math.abs(d)}` : "";
        },
        comparator: (a, b, nodeA, nodeB) => num(getStockDeducted(nodeB.data)) - num(getStockDeducted(nodeA.data)),
        cellClass: (p) => (p.value ? "text-green-600 font-medium" : ""),
        width: 140,
    },
    textColumn("category_name", "Category", 150),
    textColumn("block_name", "Block", 130),
    textColumn("website", "Website", 120),
    textColumn("type", "Type", 100),
    textColumn("block_id", "Block ID", 120),
    textColumn("identifier", "Identifier", 120),
    textColumn("product_landing_page", "Product Landing Page", 170),
    textColumn("creative_location", "Creative Location", 150),
    textColumn("landing_page", "Landing Page", 130),
    textColumn("note", "Note", 150),
    {
        colId: "actions",
        headerName: "Actions",
        cellRenderer: deleteRenderer,
        pinned: "right",
        width: 90,
        sortable: false,
        filter: false,
        resizable: false,
    },
];
</script>
<style>
.cfs-wrapper .ag-row.low-stock-row {
    background-color: #ef4444 !important;
    color: white !important;
}

.cfs-wrapper .ag-row.low-stock-row .ag-cell {
    color: white !important;
}

.cfs-wrapper .ag-cell {
    display: flex;
    align-items: center;
}
</style>
