<template>
    <BlockUI :blocked="processing" fullScreen>
        <div
            class="flex flex-col h-auto max-h-[88vh] overflow-auto p-3 card"
        >
            <h4
                class="text-gray-200 font-semibold text-lg text-center p-2 mt-5"
            >
                Website Promotions Details
            </h4>
            <div>
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
                <div v-else>
                    <div>
                        <div class="flex justify-between items-center mb-5">
                            <!-- SEARCH -->
                            <span class="p-input-icon-left w-80">
                                <InputText
                                    v-model="globalFilter"
                                    placeholder="Search promotions..."
                                    class="w-full"
                                />
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

                        <DataTable
                            :value="mergedPromotions"
                            v-model:selection="selectedPromos"
                            selectionMode="multiple"
                            dataKey="promo_name"
                            :globalFilterFields="[
                                'promo_name',
                                'storeNames',
                                'status',
                            ]"
                            :filters="{
                                global: {
                                    value: globalFilter,
                                    matchMode: 'contains',
                                },
                            }"
                            class="p-datatable-sm"
                            rowHover
                            paginator
                            :rows="20"
                            :loading="loading"
                            scrollable
                            scrollDirection="both"
                            style="min-width: 100%"
                        >
                            <!-- Frozen left -->
                            <Column
                                selectionMode="multiple"
                                frozen
                                style="width: 3rem"
                            />
                            <Column
                                header="Status"
                                frozen
                                style="min-width: 120px"
                            >
                                <template #body="{ data }">
                                    <span
                                        class="text-md font-bold"
                                        :class="statusBadge(data.status)"
                                    >
                                        {{ data.status }}
                                    </span>
                                </template>
                            </Column>
                            <Column
                                field="promo_name"
                                header="Promotion"
                                frozen
                                style="min-width: 300px"
                            >
                                <template #body="{ data }">
                                    <div
                                        class="whitespace-normal break-all max-w-[400px]"
                                    >
                                        {{ data.promo_name }}
                                    </div>
                                </template>
                            </Column>

                            <!-- Scrollable columns -->
                            <Column
                                header="Coupon Code"
                                style="min-width: 140px"
                            >
                                <template #body="{ data }">
                                    <Badge severity="contrast">
                                        <p class="text-white dark:text-black">
                                            {{ data.coupon_code || "N/A" }}
                                        </p>
                                    </Badge>
                                </template>
                            </Column>
                            <Column
                                header="Coupon Label"
                                style="min-width: 140px"
                            >
                                <template #body="{ data }">
                                    {{ data.coupon_label || "—" }}
                                </template>
                            </Column>
                            <Column
                                header="Description"
                                style="min-width: 250px"
                            >
                                <template #body="{ data }">
                                    <div
                                        class="whitespace-normal break-words max-w-[300px]"
                                    >
                                        {{
                                            data.description ||
                                            "No description available"
                                        }}
                                    </div>
                                </template>
                            </Column>
                            <Column
                                header="Terms & Conditions"
                                style="min-width: 250px"
                            >
                                <template #body="{ data }">
                                    <div
                                        class="whitespace-normal break-words max-w-[300px]"
                                    >
                                        {{
                                            Array.isArray(
                                                data.terms_and_conditions,
                                            )
                                                ? data.terms_and_conditions.join(
                                                      ", ",
                                                  )
                                                : data.terms_and_conditions ||
                                                  "No T&Cs"
                                        }}
                                    </div>
                                </template>
                            </Column>
                            <Column header="Creatives" style="min-width: 180px">
                                <template #body="{ data }">
                                    {{
                                        Array.isArray(data.creatives)
                                            ? data.creatives.join(", ")
                                            : data.creatives || "N/A"
                                    }}
                                </template>
                            </Column>
                            <Column header="Scope" style="min-width: 320px">
                                <template #body="{ data }">
                                    <div class="flex gap-2">
                                        <span
                                            class="text-md px-2 py-1 rounded"
                                            :class="
                                                data.does_include_parts
                                                    ? 'bg-green-400 text-black font-bold'
                                                    : 'bg-red-600 dark:text-white font-bold'
                                            "
                                        >
                                            {{
                                                data.does_include_parts
                                                    ? "Parts Included"
                                                    : "Parts Excluded"
                                            }}
                                        </span>
                                        <span
                                            class="text-md px-2 py-1 rounded"
                                            :class="
                                                data.does_include_marketplace_products
                                                    ? 'bg-green-400 text-black font-bold'
                                                    : 'bg-red-600 dark:text-white font-bold'
                                            "
                                        >
                                            {{
                                                data.does_include_marketplace_products
                                                    ? "Marketplace Included"
                                                    : "Marketplace Excluded"
                                            }}
                                        </span>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Website" style="min-width: 150px">
                                <template #body="{ data }">
                                    {{ data.storeNames }}
                                </template>
                            </Column>
                            <Column
                                header="Start Date"
                                style="min-width: 200px"
                            >
                                <template #body="{ data }">
                                    <Badge severity="success">
                                        <p
                                            class="text-gray-800 dark:text-gray-900"
                                        >
                                            {{
                                                dayjs(data.start_date).format(
                                                    "MMMM D, YYYY [at] hh:mm A",
                                                )
                                            }}
                                        </p>
                                    </Badge>
                                </template>
                            </Column>
                            <Column header="End Date" style="min-width: 200px">
                                <template #body="{ data }">
                                    <Badge severity="danger">
                                        <p class="text-black">
                                            {{
                                                dayjs(data.end_date).format(
                                                    "MMMM D, YYYY [at] hh:mm A",
                                                )
                                            }}
                                        </p>
                                    </Badge>
                                </template>
                            </Column>
                            <Column
                                header="Last Updated"
                                style="min-width: 200px"
                            >
                                <template #body="{ data }">
                                    <span class="text-xs text-gray-500">
                                        {{
                                            dayjs(data.updated_at).format(
                                                "MMMM D, YYYY [at] hh:mm A",
                                            )
                                        }}
                                    </span>
                                </template>
                            </Column>

                            <!-- Frozen right -->
                            <Column
                                header="Actions"
                                frozen
                                alignFrozen="right"
                                style="min-width: 120px"
                            >
                                <template #body="{ data }">
                                    <Button
                                        icon="pi pi-pencil"
                                        severity="success"
                                        label="Edit"
                                        rounded
                                        text
                                        size="medium"
                                        @click="editPromo(data)"
                                    />
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-10 text-gray-400">
                                    <i
                                        class="pi pi-inbox text-3xl mb-3 block"
                                    ></i>
                                    <p class="text-lg font-semibold">
                                        No Data Yet
                                    </p>
                                    <p class="text-sm">
                                        Details of the website promotions will
                                        appear here.
                                    </p>
                                </div>
                            </template>
                        </DataTable>
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
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
                                        Promotion Name
                                    </label>
                                    <InputText
                                        v-model="editForm.promo_name"
                                        class="w-full"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
                                        Website Store
                                    </label>
                                    <div>
                                        <div class="flex flex-col gap-3">
                                            <div
                                                v-for="store in stores"
                                                :key="store.store_id"
                                                class="flex items-center gap-3"
                                            >
                                                <Checkbox
                                                    v-model="
                                                        editForm.website_stores
                                                    "
                                                    :value="store.store_id"
                                                />
                                                <span
                                                    class="text-sm text-gray-300"
                                                >
                                                    {{ store.store_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
                                        Description
                                    </label>
                                    <Textarea
                                        v-model="editForm.description"
                                        rows="3"
                                        autoResize
                                        class="w-full"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
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
                            <div
                                class="border-t border-gray-700 pt-4 grid grid-cols-2 gap-4"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
                                        Coupon Label
                                    </label>
                                    <InputText
                                        v-model="editForm.coupon_label"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
                                        Coupon Code
                                    </label>
                                    <InputText v-model="editForm.coupon_code" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
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
                                    <label
                                        class="block text-xs font-medium text-gray-400 mb-1"
                                    >
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
                                    <Checkbox
                                        v-model="editForm.does_include_parts"
                                        binary
                                    />
                                    <span class="text-sm text-gray-300"
                                        >Includes Parts</span
                                    >
                                </div>

                                <div class="flex items-center gap-3">
                                    <Checkbox
                                        v-model="
                                            editForm.does_include_marketplace_products
                                        "
                                        binary
                                    />
                                    <span class="text-sm text-gray-300">
                                        Includes Marketplace
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- FOOTER -->
                        <template #footer>
                            <div class="flex justify-end gap-3">
                                <Button
                                    label="Cancel"
                                    text
                                    severity="secondary"
                                    @click="editVisible = false"
                                />
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
                        <Button
                            label="Cancel"
                            severity="primary"
                            @click="showArchiveDialog = false"
                        />
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
import { onMounted, ref, getCurrentInstance, computed } from "vue";
import dayjs from "dayjs";
import { useWebsitePromosStore } from "@/js/stores/website_promos_store.js";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Skeleton from "primevue/skeleton";
import Tag from "primevue/tag";
import DatePicker from "primevue/datepicker";
import BlockUI from "primevue/blockui";

import {
    createWebsitePromotion,
    updateWebsitePromotion,
    deleteWebsitePromotion,
    archiveWebsitePromotion,
} from "@/js/api/website_promos_api";
import { fetchStores } from "@/js/api/website_campaign_api.js";
const websitePromosStore = useWebsitePromosStore();
import { useUIStore } from "@/js/stores/ui.js";

const ui = useUIStore();

const stores = ref([]);
const $instance = getCurrentInstance();
const toastr = $instance.appContext.config.globalProperties.$toastr;

const editVisible = ref(false);
const editForm = ref({});

const expandedRows = ref({});
const showArchiveDialog = ref(false);

const selectedPromos = ref([]);
const globalFilter = ref("");

const loading = ref(true);

const processing = ref(false);

const editPromo = (mergedPromo) => {
    openEditDialog(mergedPromo);
};

const openEditDialog = (mergedPromo) => {
    editForm.value = {
        ...mergedPromo,
        promoRows: mergedPromo.promoRows || [],
        website_stores: mergedPromo.storeIds || [],
        start_date: mergedPromo.start_date
            ? dayjs(mergedPromo.start_date).toDate()
            : null,
        end_date: mergedPromo.end_date
            ? dayjs(mergedPromo.end_date).toDate()
            : null,
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
                        ? dayjs(editForm.value.start_date).format(
                              "YYYY-MM-DD HH:mm:ss",
                          )
                        : null,
                    end_date: editForm.value.end_date
                        ? dayjs(editForm.value.end_date).format(
                              "YYYY-MM-DD HH:mm:ss",
                          )
                        : null,
                    does_include_parts: editForm.value.does_include_parts,
                    does_include_marketplace_products:
                        editForm.value.does_include_marketplace_products,
                    website_store: storeId,
                };

                await updateWebsitePromotion(promo.promo_id, payload);

                await websitePromosStore.loadWebsitePromotions(true);

                ui.hideLoader();
            }
        }

        const existingStoreIds = existingPromos.map((p) => p.store.store_id);
        const newStores = selectedStores.filter(
            (id) => !existingStoreIds.includes(id),
        );

        for (const storeId of newStores) {
            const payload = {
                promo_name: editForm.value.promo_name,
                description: editForm.value.description,
                terms_and_conditions: editForm.value.terms_and_conditions,
                coupon_label: editForm.value.coupon_label,
                coupon_code: editForm.value.coupon_code,
                start_date: editForm.value.start_date
                    ? dayjs(editForm.value.start_date).format(
                          "YYYY-MM-DD HH:mm:ss",
                      )
                    : null,
                end_date: editForm.value.end_date
                    ? dayjs(editForm.value.end_date).format(
                          "YYYY-MM-DD HH:mm:ss",
                      )
                    : null,

                does_include_parts: editForm.value.does_include_parts,
                does_include_marketplace_products:
                    editForm.value.does_include_marketplace_products,
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
        for (const mergedPromo of selectedPromos.value) {
            for (const row of mergedPromo.promoRows) {
                await archiveWebsitePromotion(row.promo_id);
            }
        }

        await websitePromosStore.loadWebsitePromotions();
        toastr.success("Selected promotions archived successfully.");

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
            map[key].stores.push(
                promo.store.store_name.replace(/^Website - /, ""),
            );
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

    try {
        await Promise.all([
            websitePromosStore.loadWebsitePromotions(true),
            loadStores(),
        ]);
    } catch (err) {
        console.error("Failed to load data:", err);
    } finally {
        loading.value = false;
    }
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
</script>
