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

            <DataTable
                :value="sortedRows"
                editMode="cell"
                @cell-edit-complete="onCellEdit"
                tableStyle="min-width: 80rem"
                :rowClass="stockRowClass"
            >
                <Column field="sku" header="SKU" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="font-mono font-medium">{{
                            data.sku
                        }}</span>
                    </template>
                </Column>
                <Column field="rrp" header="RRP" style="min-width: 100px">
                    <template #body="{ data }">{{
                        formatPrice(data.rrp)
                    }}</template>
                </Column>
                <Column
                    field="website_price"
                    header="Website Price"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        formatPrice(data.website_price)
                    }}</template>
                </Column>
                <Column
                    field="special_price"
                    header="Special Price"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        data.special_price
                            ? formatPrice(data.special_price)
                            : "—"
                    }}</template>
                </Column>
                <Column field="qty" header="SOH" style="min-width: 80px">
                    <template #body="{ data }">
                        <span
                            :class="stockTextClass(data.qty)"
                            class="font-semibold"
                        >
                            {{ data.qty ?? "0" }}
                        </span>
                    </template>
                </Column>
                <Column
                    field="category_name"
                    header="Category"
                    style="min-width: 150px"
                >
                    <template #body="{ data }">{{
                        data.category_name || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="block_id"
                    header="Block ID"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        data.block_id || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="block_name"
                    header="Block Name"
                    style="min-width: 130px"
                >
                    <template #body="{ data }">{{
                        data.block_name || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="identifier"
                    header="Identifier"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        data.identifier || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column field="type" header="Type" style="min-width: 100px">
                    <template #body="{ data }">{{ data.type || "—" }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="website"
                    header="Website"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        data.website || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="product_landing_page"
                    header="Product Landing Page"
                    style="min-width: 170px"
                >
                    <template #body="{ data }">{{
                        data.product_landing_page || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="creative_location"
                    header="Creative Location"
                    style="min-width: 150px"
                >
                    <template #body="{ data }">{{
                        data.creative_location || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="landing_page"
                    header="Landing Page"
                    style="min-width: 130px"
                >
                    <template #body="{ data }">{{
                        data.landing_page || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column
                    field="price_change"
                    header="Price Change"
                    style="min-width: 120px"
                >
                    <template #body="{ data }">{{
                        data.price_change || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column field="note" header="Note" style="min-width: 150px">
                    <template #body="{ data }">{{ data.note || "—" }}</template>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>
                <Column header="" style="min-width: 60px">
                    <template #body="{ index }">
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            @click="removeRow(index)"
                        />
                    </template>
                </Column>
            </DataTable>
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

const props = defineProps({ modelValue: Boolean });
const emit = defineEmits(["update:modelValue"]);

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

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

function stockRowClass(data) {
    const qty = data.qty ?? 0;
    if (qty <= 5)  return '!bg-red-500';
    return '';
}

function stockTextClass(qty) {
    const q = qty ?? 0;
    if (q <= 5)  return 'text-white';
    if (q <= 10) return 'text-orange-500';
    if (q <= 20) return 'text-yellow-600';
    return 'text-green-600';
}

function onCellEdit(event) {
    const { data, newValue, field } = event;
    data[field] = newValue;
}

function removeRow(index) {
    rows.value.splice(index, 1);
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
</script>
