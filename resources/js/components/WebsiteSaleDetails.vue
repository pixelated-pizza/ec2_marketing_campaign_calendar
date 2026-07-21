<template>
    <div class="card bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg shadow-sm transition-colors duration-200"
        style="height: calc(95vh - 90px); display: flex; flex-direction: column;">
        <h4
            class="font-semibold text-sm text-left p-2 border-b border-gray-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-100 bg-gray-50 dark:bg-zinc-800/50">
            Website Sale Details
        </h4>

        <template v-if="loading">
            <div class="flex flex-col gap-3 w-full h-full p-4 bg-white dark:bg-zinc-900">
                <p class="text-gray-400 dark:text-zinc-500 text-xs font-mono">Loading Data Rows...</p>
                <Skeleton height="1.5rem" width="100%" class="dark:bg-zinc-800" />
                <Skeleton height="1.5rem" width="100%" class="dark:bg-zinc-800" />
                <Skeleton height="1.5rem" width="100%" class="dark:bg-zinc-800" />
                <div class="flex-1 mt-2">
                    <Skeleton height="100%" borderRadius="4px" class="dark:bg-zinc-800" />
                </div>
            </div>
        </template>

        <div v-else style="flex: 1; min-height: 0; display: flex; flex-direction: column;"
            class="p-2 bg-white dark:bg-zinc-900 transition-colors duration-200">
            <div
                class="mb-2 flex justify-between items-center flex-wrap gap-2 w-full bg-gray-100 dark:bg-zinc-800 p-1.5 rounded border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center gap-1.5 w-full md:w-auto">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search text-xs text-gray-400 dark:text-zinc-500" />
                        </InputIcon>
                        <InputText placeholder="Search cells..." v-model="searchQuery"
                            class="p-inputtext-sm text-xs py-1 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 border-gray-300 dark:border-zinc-700 w-44 focus:ring-1 focus:ring-blue-500" />
                    </IconField>


                    <Button label="Import CSV" icon="pi pi-upload" size="small" severity="secondary" outlined
                        class="text-xs py-1 px-2 border-gray-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-700"
                        @click="importModal?.open()" />

                    <Button label="New Event" icon="pi pi-plus" size="small" severity="success"
                        class="text-xs py-1 px-2 bg-emerald-600 border-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:border-emerald-600 text-white"
                        @click="openNewEventDialog" />

                    <Button v-if="isAdmin" label="Delete All" icon="pi pi-trash" size="small" severity="danger" outlined
                        class="text-xs py-1 px-2" @click="openDeleteAllDialog" />


                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <label for="yearFilter"
                            class="text-xs text-gray-600 dark:text-zinc-400 select-none font-medium">
                            Year:
                        </label>
                        <Select v-model="yearFilter" inputId="yearFilter"
                            class="w-24 text-xs dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-200" size="small"
                            :options="yearOptions" optionLabel="label" optionValue="value" />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label for="campaignFilter"
                            class="text-xs text-gray-600 dark:text-zinc-400 select-none font-medium">
                            Status:
                        </label>
                        <Select v-model="campaignFilter" inputId="campaignFilter"
                            class="w-28 text-xs dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-200" size="small"
                            :options="[
                                { label: 'All', value: 'ALL' },
                                { label: 'Running', value: 'RUNNING' },
                                { label: 'Ended', value: 'ENDED' },
                            ]" optionLabel="label" optionValue="value" />
                    </div>
                </div>
            </div>

            <div class="flex-1 min-height-0 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden">
                <DataTable :value="filteredCampaigns" dataKey="wsd_id" showGridlines scrollable scrollDirection="both"
                    scrollHeight="flex" size="small" class="p-datatable-spreadsheet" editMode="cell"
                    @cell-edit-complete="onCellEditComplete" :rowClass="rowClass" paginator :rows="25"
                    :loading="loading">

                    <Column header="Status" frozen sortable sortField="statusOrder" style="min-width: 90px">
                        <template #body="{ data }">
                            <span class="font-semibold text-xxs tracking-wide uppercase px-1.5 py-0.5 rounded-sm"
                                :class="statusBadgeClass(data.status)">
                                {{ data.status }}
                            </span>
                        </template>
                    </Column>

                    <Column field="channel_name" header="Channel" frozen style="min-width: 140px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ data.channel_name }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="event_name" header="Event Name" frozen style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ data.event_name }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="start_date" header="Start Date" style="min-width: 150px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ formatDate(data.start_date) }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <DatePicker v-model="data[field]" showTime hourFormat="12" size="small"
                                class="w-full cell-input dark:bg-zinc-900" fluid />
                        </template>
                    </Column>

                    <Column field="end_date" header="End Date" style="min-width: 150px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ formatDate(data.end_date) }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <DatePicker v-model="data[field]" showTime hourFormat="12" size="small"
                                class="w-full cell-input dark:bg-zinc-900" fluid />
                        </template>
                    </Column>

                    <Column field="featured_products_sheet_url" header="Featured Products Sheet"
                        style="min-width: 200px">
                        <template #body="{ data }">
                            <a v-if="data.featured_products_sheet_url" :href="data.featured_products_sheet_url"
                                target="_blank"
                                class="text-blue-600 dark:text-blue-400 hover:underline text-xs block truncate"
                                @click.stop>
                                {{ data.featured_products_sheet_url }}
                            </a>
                            <span v-else class="text-gray-400 dark:text-zinc-600 font-light">—</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="run_sheet_url" header="Run Sheet" style="min-width: 200px">
                        <template #body="{ data }">
                            <a v-if="data.run_sheet_url" :href="data.run_sheet_url" target="_blank"
                                class="text-blue-600 dark:text-blue-400 hover:underline text-xs block truncate"
                                @click.stop>
                                {{ data.run_sheet_url }}
                            </a>
                            <span v-else class="text-gray-400 dark:text-zinc-600 font-light">—</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="event_master_sheet_url" header="Event Master Sheet" style="min-width: 200px">
                        <template #body="{ data }">
                            <a v-if="data.event_master_sheet_url" :href="data.event_master_sheet_url" target="_blank"
                                class="text-blue-600 dark:text-blue-400 hover:underline text-xs block truncate"
                                @click.stop>
                                {{ data.event_master_sheet_url }}
                            </a>
                            <span v-else class="text-gray-400 dark:text-zinc-600 font-light">—</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="ess" header="ESS to Execute" style="min-width: 130px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ data.ess || "—" }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="cms_to_audit" header="CMS to Audit" style="min-width: 130px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ data.cms_to_audit || "—" }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="terms_conditions" header="T&Cs" style="min-width: 180px" class="cell-wrap">
                        <template #body="{ data }">
                            <div class="truncate max-h-12 dark:text-zinc-200">{{ data.terms_conditions || "Auto generated" }}</div>
                        </template>
                        <template #editor="{ data, field }">
                            <Textarea v-model="data[field]"
                                class="w-full cell-textarea text-zinc-800 dark:text-zinc-100" rows="2" autofocus />
                        </template>
                    </Column>

                    <Column field="mockup_banner_locations" header="Mockup & Banner Locations" style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="dark:text-zinc-200">{{ data.mockup_banner_locations || "—" }}</span>
                        </template>
                        <template #editor="{ data, field }">
                            <InputText v-model="data[field]" class="w-full cell-input text-zinc-800 dark:text-zinc-100"
                                autofocus />
                        </template>
                    </Column>

                    <Column field="is_sku_list_to_feature" header="SKU List to Feature?" style="min-width: 120px">
                        <template #body="{ data }">
                            <span
                                :class="isYes(data.is_sku_list_to_feature) ? 'text-green-600 dark:text-emerald-400 font-medium' : 'text-gray-400 dark:text-zinc-500'">
                                {{ isYes(data.is_sku_list_to_feature) ? "Yes" : "No" }}
                            </span>
                        </template>
                        <template #editor="{ data, field }">
                            <Select v-model="data[field]" :options="[
                                { label: 'Yes', value: 1 },
                                { label: 'No', value: 0 },
                            ]" optionLabel="label" optionValue="value" class="w-full cell-select" />
                        </template>
                    </Column>

                    <Column field="featured_banner_text" header="Featured Banner Text" style="min-width: 180px"
                        class="cell-wrap">
                        <template #body="{ data }">
                            <span v-html="autoLink(data.featured_banner_text)"
                                class="text-xs dark:text-zinc-200"></span>
                        </template>
                        <template #editor="{ data, field }">
                            <Textarea v-model="data[field]"
                                class="w-full cell-textarea text-zinc-800 dark:text-zinc-100" rows="2" autofocus />
                        </template>
                    </Column>

                    <Column field="sku_in_category_creative" header="SKU in Category Creative" style="min-width: 180px"
                        class="cell-wrap">
                        <template #body="{ data }">
                            <span v-html="formatMultiline(data.sku_in_category_creative)"
                                class="text-xs dark:text-zinc-200"></span>
                        </template>
                        <template #editor="{ data, field }">
                            <Textarea v-model="data[field]"
                                class="w-full cell-textarea text-zinc-800 dark:text-zinc-100" rows="2" autofocus />
                        </template>
                    </Column>

                    <Column field="url_text" header="URL Text" style="min-width: 160px" class="cell-wrap">
                        <template #body="{ data }">
                            <span v-html="formatMultiline(data.url_text)" class="text-xs dark:text-zinc-200"></span>
                        </template>
                        <template #editor="{ data, field }">
                            <Textarea v-model="data[field]"
                                class="w-full cell-textarea text-zinc-800 dark:text-zinc-100" rows="2" autofocus />
                        </template>
                    </Column>

                    <Column header="Actions" frozen alignFrozen="right" style="min-width: 70px">
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-center">
                                <Button icon="pi pi-refresh" v-if="isCompleted(data)" title="Re-run Campaign"
                                    severity="warn" size="small" class="p-0.5 w-6 h-6 dark:text-amber-400" text rounded
                                    @click="openRerunModal(data)" />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-6 text-gray-400 dark:text-zinc-600">
                            <i class="pi pi-inbox text-xl mb-1 block"></i>
                            <p class="text-xs font-semibold">No active rows found</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="rerunModalVisible" header="Re-run Campaign" :modal="true"
            class="w-80 text-xs dark:bg-zinc-900 dark:text-zinc-100">
            <div class="flex flex-col gap-2 p-1">
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300">New Start Date</label>
                <DatePicker v-model="newStartDate" showTime hourFormat="12" size="small" class="dark:bg-zinc-800"
                    fluid />
                <label class="font-semibold text-xs mt-1 text-gray-700 dark:text-zinc-300">New End Date</label>
                <DatePicker v-model="newEndDate" showTime hourFormat="12" size="small" class="dark:bg-zinc-800" fluid />
                <div class="mt-3 flex justify-end gap-1.5">
                    <Button label="Cancel" size="small" severity="secondary" class="text-xs dark:text-zinc-400"
                        @click="rerunModalVisible = false" />
                    <Button label="Submit" size="small" severity="success" class="text-xs"
                        @click="submitRerunCampaign" />
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="newEventModalVisible" header="New Sale Event" :modal="true"
            class="w-80 text-xs dark:bg-zinc-900 dark:text-zinc-100">
            <div class="flex flex-col gap-2 p-1">
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300">Channel</label>
                <InputText v-model="newEvent.channel_name" placeholder="e.g. Website Mytopia" size="small"
                    class="dark:bg-zinc-800 dark:text-zinc-100 dark:border-zinc-700" fluid />
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300">Event Name</label>
                <InputText v-model="newEvent.event_name" placeholder="e.g. Hot Summer Deals" size="small"
                    class="dark:bg-zinc-800 dark:text-zinc-100 dark:border-zinc-700" fluid />
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300">Start Date</label>
                <DatePicker v-model="newEvent.start_date" showTime hourFormat="12" size="small" class="dark:bg-zinc-800"
                    fluid />
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300">End Date</label>
                <DatePicker v-model="newEvent.end_date" showTime hourFormat="12" size="small" class="dark:bg-zinc-800"
                    fluid />
                <div class="mt-3 flex justify-end gap-1.5">
                    <Button label="Cancel" size="small" severity="secondary" class="text-xs dark:text-zinc-400"
                        @click="newEventModalVisible = false" />
                    <Button label="Create" size="small" severity="success" class="text-xs" @click="submitNewEvent" />
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="deleteAllConfirmVisible" header="Delete All Records" :modal="true"
            class="w-80 text-xs dark:bg-zinc-900 dark:text-zinc-100" @hide="closeDeleteAllDialog">
            <div class="flex flex-col gap-3 p-1">
                <p class="text-sm text-gray-700 dark:text-zinc-300">
                    This will permanently delete <strong>all</strong> records across the following:
                </p>
                <ul class="text-xs text-gray-600 dark:text-zinc-400 list-disc list-inside space-y-1">
                    <li>Website Sale Details (this page)</li>
                    <li>Website &amp; Marketplaces Campaigns</li>
                    <li>Website Sales / Promotions — Mytopia &amp; Edisons</li>
                </ul>
                <p class="text-xs text-red-500 dark:text-red-400 font-medium">
                    This cannot be undone.
                </p>
                <div class="mt-2 flex justify-end gap-1.5">
                    <Button label="Cancel" size="small" severity="secondary" class="text-xs"
                        @click="closeDeleteAllDialog" />
                    <Button :label="deleteCountdown > 0 ? `Delete All (${deleteCountdown})` : 'Delete All'"
                        :disabled="deleteCountdown > 0" size="small" severity="danger" class="text-xs"
                        :loading="deletingAll" @click="submitDeleteAll" />
                </div>
            </div>
        </Dialog>

        <ImportWSDModal ref="importModal" @imported="onImported" />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from "vue";
import { useUserStore } from "@/js/utils/user.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Skeleton from "primevue/skeleton";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import DatePicker from "primevue/datepicker";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import { useWSDStore } from "@/js/stores/wsd_store.js";
import { getCurrentInstance } from "vue";
import { useUIStore } from "@/js/stores/ui.js";
import ImportWSDModal from "./ImportWSDModal.vue";
import "@/css/wsd.css";

const ui = useUIStore();
const wsdStore = useWSDStore();
const userStore = useUserStore();

const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;

const isAdmin = computed(() =>
    userStore.role_name?.toLowerCase() === 'administrator'
);

const rerunModalVisible = ref(false);
const rerunCampaignData = ref(null);
const newStartDate = ref("");
const newEndDate = ref("");

const newEventModalVisible = ref(false);
const newEvent = reactive({ channel_name: "", event_name: "", start_date: "", end_date: "" });

const campaignFilter = ref("ALL");
const yearFilter = ref("ALL");
const searchQuery = ref("");
const loading = ref(false);
const importModal = ref(null);

const deleteAllConfirmVisible = ref(false);
const deletingAll = ref(false);

const STATUS_ORDER = { RUNNING: 1, UPCOMING: 2, ENDED: 3 };

const deleteCountdown = ref(5);
let countdownInterval = null;

const submitDeleteAll = async () => {
    deletingAll.value = true;
    try {
        await wsdStore.deleteAll();
        toastr.success("All records deleted across WSD, campaigns, and website campaigns.");
        closeDeleteAllDialog();
    } catch (err) {
        console.error(err);
        toastr.error("Failed to delete all records.");
    } finally {
        deletingAll.value = false;
    }
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

const withStatus = (list) =>
    list.map((c) => {
        const status = getStatus(c);
        return { ...c, status, statusOrder: STATUS_ORDER[status] };
    });

const isYes = (val) => val == 1 || val === true;
const isRunning = (data) => getStatus(data) === "RUNNING";
const isCompleted = (data) => getStatus(data) === "ENDED";

const onCellEditComplete = async (event) => {
    const { data, newValue, field } = event;
    if (newValue === data[field]) return;

    // Convert Date objects from DatePicker to ISO string before saving
    data[field] = newValue instanceof Date
        ? newValue.toISOString().slice(0, 19).replace('T', ' ')
        : newValue;

    await saveChanges(data);
};

const formatMultiline = (text) => (text ? text.replace(/\n/g, "<br>") : "—");

function formatDate(date) {
    if (!date) return "—";
    return new Date(date).toLocaleString("en-US", {
        year: "numeric",
        month: "numeric",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

const yearOptions = computed(() => {
    const years = new Set(
        wsdStore.websiteSaleDetails
            .filter((c) => c.start_date)
            .map((c) => new Date(c.start_date).getFullYear()),
    );
    const sorted = [...years].sort((a, b) => b - a);
    return [{ label: "All", value: "ALL" }, ...sorted.map((y) => ({ label: String(y), value: y }))];
});

const filteredCampaigns = computed(() => {
    let campaigns = wsdStore.websiteSaleDetails;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        campaigns = campaigns.filter(
            (c) =>
                c.event_name?.toLowerCase().includes(q) ||
                c.channel_name?.toLowerCase().includes(q) ||
                String(c.start_date).toLowerCase().includes(q) ||
                String(c.end_date).toLowerCase().includes(q),
        );
    }

    if (campaignFilter.value === "RUNNING") campaigns = campaigns.filter(isRunning);
    else if (campaignFilter.value === "ENDED") campaigns = campaigns.filter(isCompleted);

    if (yearFilter.value !== "ALL") {
        campaigns = campaigns.filter(
            (c) => c.start_date && new Date(c.start_date).getFullYear() === yearFilter.value,
        );
    }

    return [...campaigns].sort((a, b) => {
        const statusDiff = (STATUS_ORDER[a.status] ?? 99) - (STATUS_ORDER[b.status] ?? 99);
        if (statusDiff !== 0) return statusDiff;
        return new Date(b.start_date) - new Date(a.start_date);
    });
});

const rowClass = (data) => {
    const index = filteredCampaigns.value.indexOf(data);
    return index % 2 === 0 ? "row-even" : "row-odd";
};

const refreshData = async () => {
    await wsdStore.loadWSD();
    wsdStore.websiteSaleDetails = withStatus(wsdStore.websiteSaleDetails);
};


const openDeleteAllDialog = () => {
    deleteCountdown.value = 5;
    deleteAllConfirmVisible.value = true;
    countdownInterval = setInterval(() => {
        if (deleteCountdown.value > 0) {
            deleteCountdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const saveChanges = async (data) => {
    ui.showLoader();
    try {
        const payload = {
            channel_name: data.channel_name,
            event_name: data.event_name,
            start_date: data.start_date,
            end_date: data.end_date,
            terms_conditions: data.terms_conditions,
            featured_products_sheet_url: data.featured_products_sheet_url,
            mockup_banner_locations: data.mockup_banner_locations,
            event_master_sheet_url: data.event_master_sheet_url,
            run_sheet_url: data.run_sheet_url,
            is_sku_list_to_feature: data.is_sku_list_to_feature,
            ess: data.ess,
            cms_to_audit: data.cms_to_audit,
            featured_banner_text: data.featured_banner_text,
            sku_in_category_creative: data.sku_in_category_creative,
            url_text: data.url_text,
        };

        await wsdStore.updateWSD(data.wsd_id, payload);
        toastr.success("Cell synced.");
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            Object.entries(err.response.data.errors).forEach(([field, messages]) => {
                toastr.error(messages[0], `Error in ${field.replace(/_/g, " ")}`);
            });
        } else {
            console.error("Save failed:", err);
            toastr.error("An error occurred while saving.");
        }
    } finally {
        ui.hideLoader();
    }
};

const autoLink = (text) => {
    if (!text) return "—";
    const urlRegex = /(https?:\/\/[^\s)<>"']+)/g;
    return text
        .replace(/\n/g, "<br>")
        .replace(urlRegex, (url) => `<a href="${url}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">${url}</a>`);
};

const openRerunModal = (campaign) => {
    rerunCampaignData.value = campaign;
    newStartDate.value = campaign.start_date;
    newEndDate.value = campaign.end_date;
    rerunModalVisible.value = true;
};

const submitRerunCampaign = async () => {
    if (!newStartDate.value || !newEndDate.value) {
        toastr.error("Please select both start and end dates.");
        return;
    }
    if (newEndDate.value < newStartDate.value) {
        toastr.error("End date cannot be before start date.");
        return;
    }

    ui.showLoader();
    try {
        await wsdStore.rerunCampaign(rerunCampaignData.value.wsd_id, newStartDate.value, newEndDate.value);
        toastr.success("Campaign has been successfully re-run.");
        rerunModalVisible.value = false;
        rerunCampaignData.value = null;
        await refreshData();
    } catch (error) {
        console.error(error);
        toastr.error("Failed to re-run campaign.");
    } finally {
        ui.hideLoader();
    }
};

const openNewEventDialog = () => {
    newEvent.channel_name = "";
    newEvent.event_name = "";
    newEvent.start_date = "";
    newEvent.end_date = "";
    newEventModalVisible.value = true;
};

const submitNewEvent = async () => {
    if (!newEvent.channel_name || !newEvent.event_name) {
        toastr.error("Channel and Event Name are required.");
        return;
    }

    ui.showLoader();
    try {
        await wsdStore.addWSD({ ...newEvent });
        toastr.success("Event row appended.");
        newEventModalVisible.value = false;
        await refreshData();
    } catch (err) {
        console.error("Create failed:", err);
        toastr.error("Failed to create event.");
    } finally {
        ui.hideLoader();
    }
};

const closeDeleteAllDialog = () => {
    clearInterval(countdownInterval);
    deleteCountdown.value = 5;
    deleteAllConfirmVisible.value = false;
};

const statusBadgeClass = (status) => {
    switch (status) {
        case "RUNNING": return "bg-green-100 text-green-800 dark:bg-emerald-400 dark:text-white";
        case "ENDED": return "bg-red-100 text-red-800 dark:bg-red-400 dark:text-white";
        case "UPCOMING": return "bg-yellow-100 text-yellow-800 dark:bg-yellow-500 dark:text-yellow-300";
        default: return "bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-300";
    }
};

const onImported = async (result) => {
    if (!result) return;
    toastr.success(`${result.details_saved} saved, ${result.skipped} skipped.`);
    await refreshData();
};

let statusInterval = null;

onMounted(async () => {
    if (!userStore.role_name) {
        await userStore.fetchUser();
    }

    loading.value = true;
    await refreshData();
    loading.value = false;

    statusInterval = setInterval(() => {
        wsdStore.websiteSaleDetails = withStatus(wsdStore.websiteSaleDetails);
    }, 30 * 1000);
});

onUnmounted(() => {
    if (statusInterval) clearInterval(statusInterval);
});
</script>

<style scoped>
.text-xxs {
    font-size: 0.68rem;
}

/* Structural setup wrapper matching desktop dimensions */
:deep(.p-datatable-wrapper) {
    overflow-x: auto !important;
    overflow-y: auto !important;
}

:deep(.p-datatable-table) {
    min-width: max-content !important;
    border-collapse: separate;
    border-spacing: 0;
}

/* --- LIGHT MODE STYLES (Default) --- */
.p-datatable-spreadsheet {
    background: #ffffff !important;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8f9fa !important;
    color: #5f6368 !important;
    font-weight: 400 !important;
    font-size: 0.72rem;
    font-family: Roboto, Arial, sans-serif;
    border-right: 1px solid #bdc1c6 !important;
    border-bottom: 1px solid #bdc1c6 !important;
    border-left: none !important;
    border-top: none !important;
    padding: 3px 6px !important;
    text-align: center;
    white-space: nowrap;
}

:deep(.p-datatable-tbody > tr > td) {
    background: #ffffff !important;
    color: #202124 !important;
    border-right: 1px solid #e1e3e6 !important;
    border-bottom: 1px solid #e1e3e6 !important;
    border-left: none !important;
    border-top: none !important;
    padding: 2px 5px !important;
    font-size: 0.75rem;
    font-family: Roboto, Arial, sans-serif;
    height: 24px !important;
    white-space: nowrap;
}

:deep(.p-datatable-tbody > tr > td *) {
    color: #202124 !important;
}

/* Keep link colors clear */
:deep(.p-datatable-tbody > tr > td a),
:deep(.p-datatable-tbody > tr > td a *) {
    color: #1a73e8 !important;
}

:deep(.p-datatable-tbody > tr:hover > td) {
    background: #f1f3f4 !important;
}

:deep(.p-datatable-tbody > tr > td.p-cell-editing) {
    padding: 0 !important;
    box-shadow: inset 0 0 0 2px #1a73e8 !important;
    background: #ffffff !important;
    z-index: 2;
}


/* --- DARK MODE STYLES --- */
.dark .p-datatable-spreadsheet,
.dark :deep(.p-datatable-wrapper),
.dark :deep(.p-datatable-table) {
    background: #1a1b1e !important;
}

.dark :deep(.p-datatable-thead > tr > th) {
    background: #202124 !important;
    color: #9aa0a6 !important;
    border-right: 1px solid #4f5259 !important;
    border-bottom: 1px solid #4f5259 !important;
}

.dark :deep(.p-datatable-tbody > tr > td) {
    background: #1a1b1e !important;
    color: #e8eaed !important;
    border-right: 1px solid #2f3136 !important;
    border-bottom: 1px solid #2f3136 !important;
}

/* Force dark text color cascade */
.dark :deep(.p-datatable-tbody > tr > td *) {
    color: #e8eaed !important;
}

/* Dark mode links */
.dark :deep(.p-datatable-tbody > tr > td a),
.dark :deep(.p-datatable-tbody > tr > td a *) {
    color: #8ab4f8 !important;
}

.dark :deep(.p-datatable-tbody > tr:hover > td) {
    background: #292a2d !important;
}

.dark :deep(.p-datatable-tbody > tr > td.p-cell-editing) {
    box-shadow: inset 0 0 0 2px #8ab4f8 !important;
    background: #1a1b1e !important;
}


/* --- UTILITIES & OVERRIDES --- */
:deep(.cell-input),
:deep(.cell-textarea),
:deep(.cell-select) {
    border: none !important;
    border-radius: 0 !important;
    padding: 2px 5px !important;
    font-size: 0.75rem !important;
    background: transparent !important;
    width: 100%;
    height: 100%;
}

:deep(.cell-input:focus),
:deep(.cell-textarea:focus) {
    outline: none !important;
    box-shadow: none !important;
}

:deep(.cell-wrap > .p-datatable-cell-value) {
    white-space: normal !important;
    word-break: break-word;
}

:deep(.p-datatable-frozen-column) {
    border-right: 2px solid #bdc1c6 !important;
}

.dark :deep(.p-datatable-frozen-column) {
    border-right: 2px solid #4f5259 !important;
}

:deep(.p-datatable-thead) {
    position: sticky;
    top: 0;
    z-index: 3;
}
</style>