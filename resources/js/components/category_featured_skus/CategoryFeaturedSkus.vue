<template>
    <div class="card">
        <!-- Header Actions -->
        <div class="flex items-center mb-4 gap-2">
            <Button label="+ Add Featured SKU" @click="openModal" />
            <Button label="+ Bulk Add SKUs" @click="bulkModalVisible = true" />
            <BulkAddFeaturedSkusModal v-model="bulkModalVisible" />
            <Button label="Clear All" icon="pi pi-trash" severity="danger" @click="clearAllDialogVisible = true" />
        </div>

        <div class="card">
            <div class="items-center">
                <h2 class="text-white dark:text-white text-center font-bold text-lg tracking-wide">
                    Price & Inventory Tracking
                </h2>
            </div>
            <span v-if="lastSynced" class="text-black dark:text-white text-md">
                Last synced: {{ lastSynced.toLocaleTimeString("en-AU") }}
            </span>

            <DataTable :value="sortedFeaturedSkus" :rowClass="stockRowClass" :loading="store.isLoading" editMode="cell"
                @cell-edit-complete="onCellEdit" scrollable showGridlines scrollDirection="both"
                style="min-width: 100%">
                <template #empty>No featured SKUs found.</template>

                <Column field="sku" header="SKU" frozen style="min-width: 130px" />
                <Column field="rrp" header="RRP" style="min-width: 110px">
                    <template #body="{ data }">{{
                        formatPrice(data.rrp)
                    }}</template>
                </Column>
                <Column field="website_price" header="Website Price" style="min-width: 130px">
                    <template #body="{ data }">{{
                        formatPrice(data.website_price)
                    }}</template>
                </Column>
                <Column field="special_price" header="Special Price" style="min-width: 120px">
                    <template #body="{ data }">{{
                        data.special_price
                            ? formatPrice(data.special_price)
                            : "—"
                    }}</template>
                </Column>
                <Column field="price_change" header="Price Change" style="min-width: 130px">
                    <template #body="{ data }">
                        <span v-if="data.price_change && data.price_change != 0" :class="data.price_change > 0
                            ? 'text-green-600'
                            : 'text-red-500'
                            ">
                            {{ data.price_change > 0 ? "+" : ""
                            }}{{ formatPrice(data.price_change) }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                </Column>
                <Column field="qty" :header="`SOH as of ${today}`" style="min-width: 150px">
                    <template #body="{ data }">
                        <span :class="data.qty <= 0 ? 'text-red-500' : ''">
                            {{ data.qty ?? "0" }}
                        </span>
                    </template>
                </Column>
                <Column header="Stock Deducted" style="min-width: 140px">
                    <template #body="{ data }">
                        <span v-if="getStockDeducted(data) > 0" class="text-red-500 font-medium">
                            −{{ getStockDeducted(data) }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                </Column>

                <Column header="Stock Added" style="min-width: 140px">
                    <template #body="{ data }">
                        <span v-if="getStockDeducted(data) < 0" class="text-green-600 font-medium">
                            +{{ Math.abs(getStockDeducted(data)) }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                </Column>
                <Column field="category_name" header="Category" style="min-width: 150px">
                    <template #body="{ data }">{{
                        data.category_name || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="block_name" header="Block" style="min-width: 130px">
                    <template #body="{ data }">{{
                        data.block_name || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="website" header="Website" style="min-width: 120px">
                    <template #body="{ data }">{{
                        data.website || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="type" header="Type" style="min-width: 100px">
                    <template #body="{ data }">{{ data.type || "—" }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="block_id" header="Block ID" style="min-width: 120px">
                    <template #body="{ data }">{{
                        data.block_id || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="identifier" header="Identifier" style="min-width: 120px">
                    <template #body="{ data }">{{
                        data.identifier || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="product_landing_page" header="Product Landing Page" style="min-width: 170px">
                    <template #body="{ data }">{{
                        data.product_landing_page || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="creative_location" header="Creative Location" style="min-width: 150px">
                    <template #body="{ data }">{{
                        data.creative_location || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="landing_page" header="Landing Page" style="min-width: 130px">
                    <template #body="{ data }">{{
                        data.landing_page || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>
                <Column field="note" header="Note" style="min-width: 150px">
                    <template #body="{ data }">{{ data.note || "—" }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" autofocus />
                    </template>
                </Column>

                <!-- Frozen right -->
                <Column header="Actions" frozen alignFrozen="right" style="min-width: 80px">
                    <template #body="{ data }">
                        <Button icon="pi pi-trash" severity="danger" text
                            @click="confirmDelete(data.sku_featured_id)" />
                    </template>
                </Column>
            </DataTable>
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
const bulkModalVisible = ref(false);

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

async function onCellEdit(event) {
    const { data, newValue, field } = event;

    if (!editableFields.includes(field)) return;
    if (newValue === data[field]) return;

    data[field] = newValue;

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
</script>
<style scoped>
.low-stock-row>td {
    background-color: #ef4444 !important;
    color: white !important;
}
</style>
