<template>
    <div class="dark:bg-gray-900 bg-white p-5">
        <h4 class="font-semibold text-lg text-center p-2 dark:text-white">
            Website Sale Details
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

        <div v-else>
            <div
                class="mb-2 flex justify-content-between flex-wrap gap-4 w-full"
            >
                <div class="flex items-center w-full md:w-auto">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText
                            placeholder="Search website sale"
                            v-model="searchQuery"
                        />
                    </IconField>
                </div>
                <div class="flex items-center gap-2">
                    <label
                        for="campaignFilter"
                        class="text-black dark:text-white select-none mr-2"
                    >
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

            <DataTable
                :value="filteredCampaigns"
                dataKey="campaign_id"
                showGridlines
                scrollable
                scrollHeight="60vh"
                scrollDirection="both"
                size="small"
                class="text-md mt-5"
                editMode="cell"
                @cell-edit-complete="onCellEditComplete"
                :rowClass="rowClass"
                paginator
                :rows="15"
                :loading="loading"
            >
                <!-- Frozen left -->
                <Column
                    header="Status"
                    frozen
                    sortable
                    sortField="statusOrder"
                    style="min-width: 110px"
                >
                    <template #body="{ data }">
                        <Badge
                            :value="data.status"
                            :severity="getSeverity(data.status)"
                            class="font-semibold"
                        />
                    </template>
                </Column>
                <Column
                    field="store_name"
                    header="Channel"
                    frozen
                    style="min-width: 130px"
                />
                <Column
                    field="name"
                    header="Event Name"
                    frozen
                    style="min-width: 220px"
                />

                <!-- Scrollable -->
                <Column
                    field="start_date"
                    header="Start Date"
                    style="min-width: 190px"
                >
                    <template #body="{ data }">{{
                        formatDate(data.start_date)
                    }}</template>
                </Column>
                <Column
                    field="end_date"
                    header="End Date"
                    style="min-width: 190px"
                >
                    <template #body="{ data }">{{
                        formatDate(data.end_date)
                    }}</template>
                </Column>

                <Column
                    field="featured_products_sheet_url"
                    header="Featured Products Sheet"
                    style="min-width: 210px"
                >
                    <template #body="{ data }">
                        <a
                            v-if="data.featured_products_sheet_url"
                            :href="data.featured_products_sheet_url"
                            target="_blank"
                            class="text-green-500 hover:underline text-sm break-all"
                            @click.stop
                        >
                            {{ data.featured_products_sheet_url }}
                        </a>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="run_sheet"
                    header="Run Sheet"
                    style="min-width: 180px"
                >
                    <template #body="{ data }">
                        <a
                            v-if="data.run_sheet"
                            :href="data.run_sheet"
                            target="_blank"
                            class="text-green-500 hover:underline text-sm break-all"
                            @click.stop
                        >
                            {{ data.run_sheet }}
                        </a>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="event_master_sheet"
                    header="Event Master Sheet"
                    style="min-width: 180px"
                >
                    <template #body="{ data }">
                        <a
                            v-if="data.event_master_sheet"
                            :href="data.event_master_sheet"
                            target="_blank"
                            class="text-blue-500 hover:underline text-sm break-all"
                            @click.stop
                        >
                            {{ data.event_master_sheet }}
                        </a>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="ess"
                    header="ESS to Execute"
                    style="min-width: 160px"
                >
                    <template #body="{ data }">{{ data.ess || "—" }}</template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="cms_to_audit"
                    header="CMS to Audit"
                    style="min-width: 160px"
                >
                    <template #body="{ data }">{{
                        data.cms_to_audit || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="terms_conditions"
                    header="T&Cs"
                    style="min-width: 180px"
                >
                    <template #body="{ data }">
                        {{ data.terms_conditions || "Auto generated" }}
                    </template>
                    <template #editor="{ data, field }">
                        <Textarea
                            v-model="data[field]"
                            class="w-full"
                            rows="3"
                            autoResize
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="mockup_banner_locations"
                    header="Mockup & Banner Locations"
                    style="min-width: 200px"
                >
                    <template #body="{ data }">{{
                        data.mockup_banner_locations || "—"
                    }}</template>
                    <template #editor="{ data, field }">
                        <InputText
                            v-model="data[field]"
                            class="w-full"
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="is_sku_list_to_feature"
                    header="SKU List to Feature?"
                    style="min-width: 160px"
                >
                    <template #body="{ data }">
                        <span
                            :class="
                                data.is_sku_list_to_feature == 1 ||
                                data.is_sku_list_to_feature === true
                                    ? 'text-green-500 font-semibold'
                                    : 'text-red-400 font-semibold'
                            "
                        >
                            {{
                                data.is_sku_list_to_feature == 1 ||
                                data.is_sku_list_to_feature === true
                                    ? "Yes"
                                    : "No"
                            }}
                        </span>
                    </template>
                    <template #editor="{ data, field }">
                        <Select
                            v-model="data[field]"
                            :options="[
                                { label: 'Yes', value: 1 },
                                { label: 'No', value: 0 },
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </template>
                </Column>

                <Column
                    field="featured_banner_text"
                    header="Featured Banner Text"
                    style="min-width: 200px"
                >
                    <template #body="{ data }">
                        <span
                            v-html="autoLink(data.featured_banner_text)"
                            class="text-sm"
                        ></span>
                    </template>
                    <template #editor="{ data, field }">
                        <Textarea
                            v-model="data[field]"
                            class="w-full"
                            rows="3"
                            autoResize
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="sku_in_category_creative"
                    header="SKU in Category Creative"
                    style="min-width: 200px"
                >
                    <template #body="{ data }">
                        <span
                            v-html="
                                formatMultiline(data.sku_in_category_creative)
                            "
                            class="text-sm"
                        ></span>
                    </template>
                    <template #editor="{ data, field }">
                        <Textarea
                            v-model="data[field]"
                            class="w-full"
                            rows="3"
                            autoResize
                            autofocus
                        />
                    </template>
                </Column>

                <Column
                    field="url_text"
                    header="URL Text"
                    style="min-width: 180px"
                >
                    <template #body="{ data }">
                        <span
                            v-html="formatMultiline(data.url_text)"
                            class="text-sm"
                        ></span>
                    </template>
                    <template #editor="{ data, field }">
                        <Textarea
                            v-model="data[field]"
                            class="w-full"
                            rows="3"
                            autoResize
                            autofocus
                        />
                    </template>
                </Column>

                <!-- Frozen right -->
                <Column
                    header="Actions"
                    frozen
                    alignFrozen="right"
                    style="min-width: 160px"
                >
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button
                                icon="pi pi-refresh"
                                v-if="isCompleted(data)"
                                label="Re-run Campaign"
                                severity="warn"
                                size="small"
                                raised
                                @click="openRerunModal(data)"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-10 text-gray-400">
                        <i class="pi pi-inbox text-3xl mb-3 block"></i>
                        <p class="text-lg font-semibold">No Data Yet</p>
                        <p class="text-sm">
                            Website sale details will appear here.
                        </p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Re-run Modal — unchanged -->
        <Dialog
            v-model:visible="rerunModalVisible"
            header="Re-run Campaign"
            :modal="true"
            :closable="true"
            class="w-96"
        >
            <div class="flex flex-col gap-3">
                <label class="font-semibold">New Start Date</label>
                <DatePicker
                    v-model="newStartDate"
                    showTime
                    hourFormat="12"
                    stepMinute="1"
                    stepSecond="1"
                    fluid
                />
                <label class="font-semibold">New End Date</label>
                <DatePicker
                    v-model="newEndDate"
                    showTime
                    hourFormat="12"
                    stepMinute="1"
                    stepSecond="1"
                    fluid
                />
                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        class="p-button-secondary"
                        @click="rerunModalVisible = false"
                    />
                    <Button
                        label="Submit"
                        icon="pi pi-check"
                        class="p-button-success"
                        @click="submitRerunCampaign"
                    />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Skeleton from "primevue/skeleton";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import { useWSDStore } from "@/js/stores/wsd_store";
import { useOnsiteCampaignStore } from "@/js/stores/onsite_campaign_store.js";
import { getCurrentInstance } from "vue";
import { useUIStore } from "@/js/stores/ui.js";
import "@/css/wsd.css";

const ui = useUIStore();
const store = useOnsiteCampaignStore();
const wsdStore = useWSDStore();
const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;

const rerunModalVisible = ref(false);
const rerunCampaignData = ref(null);
const newStartDate = ref("");
const newEndDate = ref("");
const campaignFilter = ref("ALL");
const searchQuery = ref("");
const loading = ref(false);

const editingCampaign = ref(null);

const getStatus = (data) => {
    if (!data.start_date || !data.end_date) return "UPCOMING";
    const today = new Date();
    const start = new Date(data.start_date);
    const end = new Date(data.end_date);
    if (today >= start && today <= end) return "RUNNING";
    if (today > end) return "ENDED";
    return "UPCOMING";
};

const updateStatuses = (list) =>
    list.map((c) => {
        const status = getStatus(c);
        return {
            ...c,
            status,
            statusOrder: { RUNNING: 1, UPCOMING: 2, ENDED: 3 }[status],
        };
    });

const addStatusFields = (list) =>
    list.map((c) => {
        const status = getStatus(c);
        return {
            ...c,
            status,
            statusOrder: { RUNNING: 1, UPCOMING: 2, ENDED: 3 }[status],
        };
    });

const isRunning = (data) => getStatus(data) === "RUNNING";
const isCompleted = (data) => getStatus(data) === "ENDED";

// Inline cell edit — fires saveChanges per cell
const onCellEditComplete = async (event) => {
    const { data, newValue, field } = event;
    if (newValue === data[field]) return;
    data[field] = newValue;
    await saveChanges(data);
};

const formatMultiline = (text) => {
    if (!text) return "No Data Yet";
    return text.replace(/\n/g, "<br>");
};

function formatDate(date) {
    if (!date) return "";
    return new Date(date).toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

const filteredCampaigns = computed(() => {
    let campaigns = wsdStore.websiteSaleDetails;

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

    if (campaignFilter.value === "RUNNING")
        campaigns = campaigns.filter((c) => isRunning(c));
    else if (campaignFilter.value === "ENDED")
        campaigns = campaigns.filter((c) => isCompleted(c));

    return [...campaigns].sort(
        (a, b) => new Date(b.start_date) - new Date(a.start_date),
    );
});

const rowClass = (data) => {
    const index = filteredCampaigns.value.indexOf(data);
    return index % 2 === 0 ? "row-even" : "row-odd";
};

const saveChanges = async (data) => {
    ui.showLoader();
    try {
        const payload = {
            wc_id: data.wc_id ?? data.campaign_id,
            terms_conditions: data.terms_conditions,
            featured_products_sheet_url: data.featured_products_sheet_url,
            mockup_banner_locations: data.mockup_banner_locations,
            event_master_sheet_url: data.event_master_sheet,
            run_sheet_url: data.run_sheet,
            is_sku_list_to_feature: data.is_sku_list_to_feature,
            ess: data.ess,
            cms_to_audit: data.cms_to_audit,
            featured_banner_text: data.featured_banner_text,
            sku_in_category_creative: data.sku_in_category_creative,
            url_text: data.url_text,
        };

        await wsdStore.addWSD(payload);
        toastr.success("Details saved successfully.");
        data.isEditing = false;
        editingCampaign.value = null;
        await wsdStore.loadWSD();
        const updated = addStatusFields(wsdStore.websiteSaleDetails);
        wsdStore.websiteSaleDetails.splice(
            0,
            wsdStore.websiteSaleDetails.length,
            ...updated,
        );
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            Object.entries(err.response.data.errors).forEach(
                ([field, messages]) => {
                    toastr.error(
                        messages[0],
                        `Error in ${field.replace(/_/g, " ")}`,
                    );
                },
            );
        } else {
            console.error("Save failed:", err);
            toastr.error("An error occurred while saving.");
        }
    } finally {
        ui.hideLoader();
    }
};

// const cancelEdit = (data) => {
//     data.isEditing = false;
//     editingCampaign.value = null;
//     const restored = buildTextTable(data);
//     editableTextTable.splice(0, editableTextTable.length, ...restored);
//     wsdStore.loadWSD().then(() => {
//         wsdStore.websiteSaleDetails = addStatusFields(
//             wsdStore.websiteSaleDetails,
//         );
//     });
// };

const autoLink = (text) => {
    if (!text) return "No Data Yet";
    const urlRegex = /(https?:\/\/[^\s)<>"']+)/g;
    return text
        .replace(/\n/g, "<br>")
        .replace(
            urlRegex,
            (url) =>
                `<a href="${url}" target="_blank" class="text-blue-400 underline">${url}</a>`,
        );
};

const getSeverity = (status) => {
    switch (status) {
        case "RUNNING":
            return "success";
        case "ENDED":
            return "danger";
        case "UPCOMING":
            return "warn";
        default:
            return "info";
    }
};

const openRerunModal = (campaign) => {
    rerunCampaignData.value = campaign;
    newStartDate.value = campaign.start_date;
    newEndDate.value = campaign.end_date;
    rerunModalVisible.value = true;
};

const submitRerunCampaign = async () => {
    ui.showLoader();
    if (!newStartDate.value || !newEndDate.value) {
        toastr.error("Please select both start and end dates.");
        return;
    }
    if (newEndDate.value < newStartDate.value) {
        toastr.error("End date cannot be before start date.");
        return;
    }

    try {
        const payload = {
            wc_id: rerunCampaignData.value.campaign_id,
            name: rerunCampaignData.value.name,
            start_date: newStartDate.value,
            end_date: newEndDate.value,
        };

        await store.editCampaign(rerunCampaignData.value.campaign_id, payload);
        await wsdStore.loadWSD();

        toastr.success("Campaign has been successfully re-run.");
        rerunModalVisible.value = false;
        rerunCampaignData.value = null;
        await store.loadCampaigns();
        await wsdStore.loadWSD();
    } catch (error) {
        console.error(error);
        toastr.error("Failed to re-run campaign.");
    } finally {
        ui.hideLoader();
    }
};

setInterval(() => {
    wsdStore.websiteSaleDetails.forEach((c) => {
        const today = new Date();
        const start = new Date(c.start_date);
        const end = new Date(c.end_date);
        if (today >= start && today <= end) c.status = "RUNNING";
        else if (today > end) c.status = "ENDED";
        else c.status = "UPCOMING";
        c.statusOrder = { RUNNING: 1, UPCOMING: 2, ENDED: 3 }[c.status];
    });
}, 30 * 1000);

onMounted(async () => {
    loading.value = true;
    await wsdStore.loadWSD();
    wsdStore.websiteSaleDetails = updateStatuses(wsdStore.websiteSaleDetails);
    loading.value = false;
});
</script>
<style scoped></style>
