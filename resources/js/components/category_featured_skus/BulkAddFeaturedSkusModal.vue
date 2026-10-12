<template>
    <Dialog
        v-model:visible="visible"
        header="Bulk Add Featured SKUs"
        :style="{ width: '90vw', maxWidth: '1200px' }"
        modal
    >
        <div v-if="step === 1" class="flex flex-col gap-4">
            <label class="font-medium">Enter SKUs (one per line)</label>
            <Textarea
                v-model="skuInput"
                rows="10"
                placeholder="MOWRIDBMS32A&#10;LGSELEBMRATN2&#10;PRES-ELEC-11095"
                class="w-full font-mono"
            />
            <small class="text-gray-500"
                >{{ skuLines.length }} SKU(s) entered</small
            >
        </div>

        <div v-if="step === 2" class="flex flex-col gap-4">
            <div
                v-if="lookupErrors.length"
                class="bg-red-50 border border-red-200 rounded p-3"
            >
                <p class="font-medium text-red-600 mb-1">
                    SKUs not found in Neto:
                </p>
                <ul class="text-sm text-red-500 list-disc pl-4">
                    <li v-for="err in lookupErrors" :key="err">{{ err }}</li>
                </ul>
            </div>

            <!-- Legend -->
            <div class="flex gap-4 text-sm items-center flex-wrap">
                <span class="font-medium">SOH Legend:</span>
                <span class="flex items-center gap-1">
                    <span
                        class="inline-block w-3 h-3 rounded-full bg-red-500"
                    ></span>
                    Critical (0–5)
                </span>
                <span class="flex items-center gap-1">
                    <span
                        class="inline-block w-3 h-3 rounded-full bg-orange-400"
                    ></span>
                    Low (6–10)
                </span>
                <span class="flex items-center gap-1">
                    <span
                        class="inline-block w-3 h-3 rounded-full bg-yellow-400"
                    ></span>
                    Medium (11–20)
                </span>
                <span class="flex items-center gap-1">
                    <span
                        class="inline-block w-3 h-3 rounded-full bg-green-500"
                    ></span>
                    Good (21+)
                </span>
            </div>

            <div class="bulk-grid relative border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                style="height: 55vh" :data-ag-theme-mode="isDark ? 'dark' : 'light'">
                <AgGridVue style="position: absolute; width: 100%; height: 100%; left: 0; top: 0" :theme="gridTheme"
                    :columnDefs="columnDefs" :rowData="sortedRows" :defaultColDef="defaultColDef"
                    :getRowClass="getRowClass" :singleClickEdit="true" :stopEditingWhenCellsLoseFocus="true"
                    :enterNavigatesVertically="true" :enterNavigatesVerticallyAfterEdit="true"
                    :enableCellTextSelection="true" :ensureDomOrder="true"
                    :localeText="{ noRowsToShow: 'No SKUs to add' }" />
            </div>
            <small class="text-gray-500">Click any cell to edit.</small>
        </div>
        <template #footer>
            <!-- Step 1 Footer -->
            <template v-if="step === 1">
                <Button label="Cancel" severity="secondary" @click="close" />
                <Button
                    label="Lookup SKUs →"
                    :loading="lookingUp"
                    :disabled="!skuLines.length"
                    @click="lookupAll"
                />
            </template>

            <!-- Step 2 Footer -->
            <template v-if="step === 2">
                <Button label="← Back" severity="secondary" @click="step = 1" />
                <Button
                    label="Save All"
                    :loading="store.isSaving"
                    :disabled="!rows.length"
                    @click="submit"
                />
            </template>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { useCategoryFeaturedSkusStore } from "@/js/stores/category_featured_skus_store.js";
import { useNetoStore } from "@/js/stores/neto_store.js";
import { AgGridVue } from "ag-grid-vue3";
import { useAgGridTheme } from "@/js/composables/useAgGridTheme.js";

const props = defineProps({ modelValue: Boolean });
const emit = defineEmits(["update:modelValue"]);

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

const { isDark, gridTheme } = useAgGridTheme({ fontSize: 12 });

const store = useCategoryFeaturedSkusStore();
const netoStore = useNetoStore();

const step = ref(1);
const skuInput = ref("");
const lookingUp = ref(false);
const lookupErrors = ref([]);
const rows = ref([]);

const skuLines = computed(() =>
    skuInput.value
        .split("\n")
        .map((s) => s.trim())
        .filter(Boolean),
);

async function lookupAll() {
    lookingUp.value = true;
    lookupErrors.value = [];
    rows.value = [];

    // Fetch all SKUs at once by passing comma separated — Neto accepts multiple
    await netoStore.loadProducts(skuLines.value.join(","), true);

    const found = netoStore.allProducts;
    const foundMap = Object.fromEntries(found.map((p) => [p.sku, p]));

    skuLines.value.forEach((sku) => {
        const product = foundMap[sku];
        if (product) {
            rows.value.push({
                sku: product.sku,
                rrp: product.rrp,
                website_price: product.website_price,
                special_price: product.special_price,
                qty: product.stock,
                category_name: "",
                block_id: "",
                block_name: "",
                identifier: "",
                type: "",
                website: "",
                product_landing_page: "",
                creative_location: "",
                landing_page: "",
                price_change: "",
                note: "",
            });
        } else {
            lookupErrors.value.push(sku);
        }
    });

    lookingUp.value = false;
    step.value = 2;
}

const sortedRows = computed(() =>
    [...rows.value].sort((a, b) => (a.qty ?? 0) - (b.qty ?? 0))
);

function getRowClass(params) {
    return (params.data?.qty ?? 0) <= 5 ? "bulk-low-stock" : "";
}

function stockTextClass(qty) {
    const q = qty ?? 0;
    if (q <= 5)  return 'text-white';
    if (q <= 10) return 'text-orange-500';
    if (q <= 20) return 'text-yellow-600';
    return 'text-green-600';
}

// AG Grid writes edits straight into the row objects, so no edit handler is needed.

// Remove by row object (the grid is sorted differently from `rows`, so an index would hit the wrong row)
function removeRow(row) {
    const i = rows.value.indexOf(row);
    if (i !== -1) rows.value.splice(i, 1);
}

async function submit() {
    await store.addBulkFeaturedSkus({ items: rows.value });
    close();
}

function close() {
    visible.value = false;
    step.value = 1;
    skuInput.value = "";
    rows.value = [];
    lookupErrors.value = [];
}

function formatPrice(value) {
    return new Intl.NumberFormat("en-AU", {
        style: "currency",
        currency: "AUD",
    }).format(value);
}

// ---------------------------------------------------------------------------
// Grid
// ---------------------------------------------------------------------------
const dash = (p) => p.value || "\u2014";

const editableText = (field, headerName, width) => ({
    colId: field,
    field,
    headerName,
    width,
    editable: true,
    valueFormatter: dash,
});

function deleteRenderer(params) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.title = "Remove";
    btn.setAttribute("aria-label", "Remove row");
    btn.className = "inline-flex h-8 w-8 items-center justify-center rounded-full text-red-500 hover:bg-red-500/10";
    btn.innerHTML = '<i class="pi pi-trash"></i>';
    btn.addEventListener("click", () => removeRow(params.data));
    return btn;
}

const defaultColDef = {
    sortable: false,
    resizable: true,
    filter: false,
    editable: false,
    minWidth: 80,
    wrapHeaderText: true,
    autoHeaderHeight: true,
};

const columnDefs = [
    {
        colId: "sku",
        field: "sku",
        headerName: "SKU",
        pinned: "left",
        width: 150,
        cellClass: "font-mono font-medium",
    },
    { colId: "rrp", field: "rrp", headerName: "RRP", width: 110, valueFormatter: (p) => formatPrice(p.value) },
    {
        colId: "website_price",
        field: "website_price",
        headerName: "Website Price",
        width: 130,
        valueFormatter: (p) => formatPrice(p.value),
    },
    {
        colId: "special_price",
        field: "special_price",
        headerName: "Special Price",
        width: 130,
        valueFormatter: (p) => (p.value ? formatPrice(p.value) : "\u2014"),
    },
    {
        colId: "qty",
        headerName: "SOH",
        valueGetter: (p) => String(p.data?.qty ?? "0"),
        cellClass: (p) => `font-semibold ${stockTextClass(p.data?.qty)}`,
        width: 90,
    },
    editableText("category_name", "Category", 150),
    editableText("block_id", "Block ID", 120),
    editableText("block_name", "Block Name", 130),
    editableText("identifier", "Identifier", 120),
    editableText("type", "Type", 100),
    editableText("website", "Website", 120),
    editableText("product_landing_page", "Product Landing Page", 170),
    editableText("creative_location", "Creative Location", 150),
    editableText("landing_page", "Landing Page", 130),
    editableText("price_change", "Price Change", 120),
    editableText("note", "Note", 150),
    {
        colId: "remove",
        headerName: "",
        cellRenderer: deleteRenderer,
        pinned: "right",
        width: 70,
        resizable: false,
    },
];
</script>

<style>
.bulk-grid .ag-row.bulk-low-stock {
    background-color: #ef4444 !important;
}

.bulk-grid .ag-cell {
    display: flex;
    align-items: center;
}
</style>
