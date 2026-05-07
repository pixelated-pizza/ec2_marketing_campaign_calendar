<template>
    <BlockUI :blocked="processing" fullScreen>
        <div
            class="flex flex-col bg-white dark:bg-gray-900 h-auto max-h-[88vh] overflow-auto"
        >
            <div class="card dark:bg-gray-900 bg-white rounded-lg mt-2">
                <h4 class="text-gray-200 font-semibold text-lg text-center p-2">
                    Website Campaign Calendar - Edisons & Mytopia
                </h4>

                <div
                    class="h-[450px] overflow-hidden rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800"
                >
                    <template v-if="loading">
                        <div class="flex flex-col gap-4 w-full h-full p-3">
                            <p class="text-gray-400 text-lg">
                                Loading Gantt Chart...
                            </p>
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

                    <FullCalendar
                        v-else
                        ref="calendarRef"
                        :options="calendarOptions"
                        class="w-full h-full fc-theme"
                    />
                </div>

                <div class="rounded-sm">
                    <template v-if="loading">
                        <div
                            class="flex flex-col gap-4 w-full h-full dark:bg-black bg-white p-5 rounded-md"
                        >
                            <p class="text-gray-400 text-lg">
                                Loading data table...
                            </p>
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
                    <DataTable
                        v-else
                        :value="filteredCampaigns"
                        sortField="start_date"
                        :sortOrder="-1"
                        dataKey="wc_id"
                        paginator
                        :rows="5"
                        :rowsPerPageOptions="[5, 10, 20, 50]"
                        showGridlines
                        scrollable
                        scrollHeight="500px"
                        stickyHeader
                        tableStyle="min-width: 60rem;"
                        :loading="loading"
                        class="p-datatable-sm"
                        tableLayout="fixed"
                    >
                        <template #header>
                            <div
                                class="table-header flex flex-wrap gap-2 items-center p-2"
                            >
                                <!-- Search -->
                                <IconField class="w-60 relative">
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText
                                        placeholder="Search"
                                        v-model="searchQuery"
                                        class="w-full"
                                    />
                                </IconField>
                                <div class="relative w-60">
                                    <Select
                                        v-model="filterStore"
                                        :options="store.stores"
                                        optionLabel="store_name"
                                        optionValue="store_id"
                                        placeholder="Filter by Website"
                                        class="w-full"
                                    />
                                    <button
                                        v-if="filterStore"
                                        @click="filterStore = null"
                                        class="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200"
                                    >
                                        <i class="pi pi-times"></i>
                                    </button>
                                </div>
                                <div class="relative w-60">
                                    <Select
                                        v-model="filterSection"
                                        :options="store.sections"
                                        optionLabel="name"
                                        optionValue="section_id"
                                        placeholder="Filter by Section"
                                        class="w-full"
                                    />
                                    <button
                                        v-if="filterSection"
                                        @click="filterSection = null"
                                        class="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200"
                                    >
                                        <i class="pi pi-times"></i>
                                    </button>
                                </div>
                                <Button
                                    type="button"
                                    icon="pi pi-plus"
                                    label="Add Campaign / Promo"
                                    size="small"
                                    severity="success"
                                    :loading="loading"
                                    @click="openAddModal"
                                />
                            </div>
                        </template>

                        <Column field="name" header="Banner Name" class="w-1/4">
                            <template #body="{ data }">
                                <span>{{ data.name }}</span>
                            </template>
                        </Column>

                        <Column
                            field="start_date"
                            header="Start Date"
                            class="w-1/6"
                        >
                            <template #body="{ data }">
                                <span>{{ formatDate(data.start_date) }}</span>
                            </template>
                        </Column>

                        <Column
                            field="end_date"
                            header="End Date"
                            class="w-1/6"
                        >
                            <template #body="{ data }">
                                <span>{{ formatDate(data.end_date) }}</span>
                            </template>
                        </Column>

                        <Column field="store_id" header="Website" class="w-1/6">
                            <template #body="{ data }">
                                <span>{{ data.websiteDisplay }}</span>
                            </template>
                        </Column>

                        <Column
                            field="section_id"
                            header="Section"
                            class="w-1/6"
                        >
                            <template #body="{ data }">
                                <span>{{ data.section?.name }}</span>
                            </template>
                        </Column>

                        <Column header="Status" class="w-1/6">
                            <template #body="{ data }">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded text-sm font-medium',
                                        isUpcoming(data)
                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                            : isRunning(data)
                                              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                              : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    ]"
                                >
                                    {{ getStatus(data) }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Action" class="w-1/6">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-2">
                                    <Button
                                        type="button"
                                        icon="pi pi-pencil"
                                        label="Edit"
                                        size="small"
                                        severity="info"
                                        :loading="
                                            loading &&
                                            editTargetId === data.wc_id
                                        "
                                        @click="openEditModal(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="p-4 text-center text-gray-400">
                                No Data available.
                            </div>
                        </template>
                    </DataTable>
                </div>

                <Dialog
                    v-model:visible="showDialog"
                    modal
                    :header="dialogHeader"
                    :style="{ width: '30vw' }"
                    class="bg-white dark:bg-gray-900"
                >
                    <div class="flex flex-col gap-3">
                        <div>
                            <label
                                class="block text-gray-700 dark:text-gray-300 mb-1"
                                >Name</label
                            >
                            <InputText
                                v-model="form.name"
                                class="w-full"
                                placeholder="Name of the Website Sale / Promotion"
                            />
                            <Message
                                v-if="errors.name"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.name }}
                            </Message>
                        </div>

                        <div>
                            <label
                                class="block text-gray-700 dark:text-gray-300 mb-1"
                                >Campaign Category</label
                            >
                            <Select
                                v-model="form.campaign_type_id"
                                :options="store.campaign_types"
                                optionLabel="campaign_type_name"
                                optionValue="campaign_type_id"
                                class="w-full"
                                placeholder="Campaign Category"
                            />
                            <Message
                                v-if="errors.campaign_type_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.campaign_type_id }}
                            </Message>
                        </div>

                        <div>
                            <label
                                class="block text-gray-700 dark:text-gray-300 mb-1"
                                >Where do we place this?</label
                            >
                            <Select
                                v-model="form.section_id"
                                :options="store.sections"
                                optionLabel="name"
                                optionValue="section_id"
                                class="w-full"
                                placeholder="Section Name"
                            />
                            <Message
                                v-if="errors.section_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.section_id }}
                            </Message>
                        </div>

                        <div>
                            <label
                                class="block text-gray-700 dark:text-gray-300 mb-1"
                                >What Website?</label
                            >
                            <div v-if="!isEditingDuplicateName">
                                <div class="flex items-center gap-2 mb-2 mt-2">
                                    <Checkbox
                                        v-model="form.is_all_store"
                                        inputId="all_store"
                                        binary
                                        :true-value="true"
                                        :false-value="false"
                                    />
                                    <label
                                        for="all_store"
                                        class="text-gray-700 dark:text-gray-300 cursor-pointer"
                                    >
                                        Apply to Both Websites
                                    </label>
                                </div>
                            </div>

                            <Select
                                v-model="form.store_id"
                                :disabled="form.is_all_store"
                                :options="store.stores"
                                optionLabel="store_name"
                                optionValue="store_id"
                                class="w-full"
                                placeholder="Name of the Website"
                            />

                            <Message
                                v-if="errors.store_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.store_id }}
                            </Message>
                        </div>

                        <div>
                            <label
                                class="block dark:text-gray-300 mb-1 text-black"
                                >Start Date</label
                            >
                            <DatePicker
                                v-model="form.start_date"
                                showTime
                                hourFormat="12"
                                timePickerMode="slider"
                                :stepMinute="1"
                                :stepSecond="1"
                                fluid
                            />
                            <Message
                                v-if="errors.start_date"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.start_date }}
                            </Message>
                        </div>

                        <div>
                            <label
                                class="block text-gray-700 dark:text-gray-300 mb-1"
                                >End Date</label
                            >
                            <DatePicker
                                v-model="form.end_date"
                                showTime
                                hourFormat="12"
                                timePickerMode="slider"
                                :stepMinute="1"
                                :stepSecond="1"
                                fluid
                            />
                            <Message
                                v-if="errors.end_date"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ errors.end_date }}
                            </Message>
                        </div>
                    </div>

                    <template #footer>
                        <div class="flex justify-between w-full">
                            <div class="flex gap-2">
                                <Button
                                    v-if="editTargetId"
                                    label="Archive"
                                    icon="pi pi-folder"
                                    severity="warn"
                                    @click="
                                        confirmArchive({
                                            ...form,
                                            wc_id: editTargetId,
                                        })
                                    "
                                />
                                <Button
                                    v-if="editTargetId"
                                    label="Delete"
                                    icon="pi pi-trash"
                                    :loading="processing"
                                    :disabled="processing"
                                    severity="danger"
                                    @click="
                                        confirmDelete({
                                            ...form,
                                            wc_id: editTargetId,
                                        })
                                    "
                                />
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    label="Cancel"
                                    severity="secondary"
                                    @click="closeDialog"
                                />
                                <Button
                                    :label="saving ? 'Saving...' : saveLabel"
                                    icon="pi pi-check"
                                    severity="success"
                                    :disabled="processing"
                                    @click="saveCampaign"
                                    :loading="saving"
                                />
                            </div>
                        </div>
                    </template>
                </Dialog>

                <Dialog
                    v-model:visible="showArchiveDialog"
                    header="Confirm Archive"
                    :modal="true"
                    :closable="false"
                    :style="{ width: '400px' }"
                >
                    <p>
                        Are you sure you want to archive the campaign "{{
                            campaignToArchive?.name
                        }}"?
                    </p>
                    <template #footer>
                        <Button
                            label="No"
                            severity="danger"
                            text
                            @click="showArchiveDialog = false"
                        />
                        <Button
                            label="Yes"
                            severity="success"
                            :loading="processing"
                            :disabled="processing"
                            @click="archiveConfirmed"
                        />
                    </template>
                </Dialog>

                <Dialog
                    v-model:visible="showDeleteDialog"
                    header="Delete Campaign"
                    :modal="true"
                    :closable="false"
                    :style="{ width: '400px' }"
                >
                    <p>
                        Are you sure you want to delete the campaign "{{
                            campaignToDelete?.name
                        }}"?
                    </p>
                    <template #footer>
                        <Button
                            label="No"
                            severity="danger"
                            text
                            @click="showDeleteDialog = false"
                        />
                        <Button
                            label="Yes"
                            severity="success"
                            :loading="processing"
                            :disabled="processing"
                            @click="deleteConfirmed"
                        />
                    </template>
                </Dialog>
            </div>
        </div>
    </BlockUI>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import interactionPlugin from "@fullcalendar/interaction";
import dayGridPlugin from "@fullcalendar/daygrid";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import DatePicker from "primevue/datepicker";
import Message from "primevue/message";

import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Calendar from "primevue/calendar";
import { useOnsiteCampaignStore } from "@/js/stores/onsite_campaign_store.js";
import { Select } from "primevue";
import Skeleton from "primevue/skeleton";
import { useUIStore } from "@/js/stores/ui.js";

const ui = useUIStore();

const $instance = getCurrentInstance();
const toastr = $instance.appContext.config.globalProperties.$toastr;

const store = useOnsiteCampaignStore();

const processing = ref(false);

const calendarRef = ref(null);
const loading = ref(false);
const saving = ref(false);
const showDialog = ref(false);
const showArchiveDialog = ref(false);
const showDeleteDialog = ref(false);

const filterStore = ref(null);
const filterSection = ref(null);

const campaignToArchive = ref(null);
const campaignToDelete = ref(null);

const editTargetId = ref(null);

const searchQuery = ref("");

const today = new Date();
const todayStr =
    today.getFullYear() +
    "-" +
    String(today.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(today.getDate()).padStart(2, "0");

const ganttColors = ["#B04D4D", "#387EB0"];

const form = ref({
    name: "",
    campaign_type_id: "",
    section_id: "",
    store_id: "",
    start_date: "",
    is_all_store: false,
    end_date: "",
});

const errors = ref({
    name: null,
    campaign_type_id: null,
    section_id: null,
    store_id: null,
    is_all_store: null,
    start_date: null,
    end_date: null,
});

const calendarOptions = ref({
    schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
    plugins: [resourceTimelinePlugin, dayGridPlugin, interactionPlugin],
    initialView: "resourceTimelineMonth",
    headerToolbar: {
        left: "prev,next today",
        center: "title",
    },
    resourceAreaHeaderContent: "On-site Campaigns",
    resources: [],
    events: [],
    editable: true,
    selectable: true,
    height: 400,

    slotLabelFormat: [{ weekday: "short", day: "numeric", month: "short" }],

    slotLabelDidMount: (info) => {
        info.el.style.padding = "6px 0";
    },
    eventDrop: async (info) => {
        const { id, start, end } = info.event;
        const campaign = store.campaigns.find((c) => String(c.wc_id) === id);
        if (!campaign) return;

        try {
            await store.editCampaign(id, {
                ...campaign,
                start_date: start ? formatDateTime(start) : null,
                end_date: end ? formatDateTime(end) : null,
            });

            toastr.success({
                severity: "success",
                summary: "Campaign Updated",
                detail: `${campaign.name} date updated.`,
                life: 2000,
            });

            await store.loadCampaigns();
            updateCalendarResourcesAndEvents();
        } catch (error) {
            toastr.error("Failed to update campaign date.");
            info.revert();
        }
    },
    eventResize: async (info) => {
        const { id, start, end } = info.event;
        const campaign = store.campaigns.find((c) => String(c.wc_id) === id);
        if (!campaign) return;

        try {
            await store.editCampaign(id, {
                ...campaign,
                start_date: start ? formatDateTime(start) : null,
                end_date: end ? formatDateTime(end) : null,
            });

            toastr.success({
                severity: "success",
                summary: "Campaign Resized",
                detail: `${campaign.name} duration updated.`,
                life: 2000,
            });

            await store.loadCampaigns();
            updateCalendarResourcesAndEvents();
        } catch (error) {
            toastr.error("Failed to update campaign duration.");
            info.revert();
        }
    },
    eventClick: (info) => {
        const id = info.event.id;
        const campaign = store.campaigns.find((c) => String(c.wc_id) === id);
        if (!campaign) return;

        openEditModal(campaign);
    },
});

const filteredCampaigns = computed(() => {
    // First filter campaigns by search / store / section
    const campaigns = store.campaigns.filter((c) => {
        const matchesSearch =
            !searchQuery.value ||
            c.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            c.store?.store_name
                ?.toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            c.section?.name
                ?.toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            String(c.start_date)
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            String(c.end_date)
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase());

        const matchesStore =
            !filterStore.value ||
            String(c.store_id) === String(filterStore.value);
        const matchesSection =
            !filterSection.value ||
            String(c.section_id) === String(filterSection.value);

        return matchesSearch && matchesStore && matchesSection;
    });

    const grouped = {};
    campaigns.forEach((c) => {
        const cleanStoreName =
            c.store?.store_name?.replace(/^Website - /, "") || "";

        if (!grouped[c.name]) {
            grouped[c.name] = {
                ...c,
                websiteDisplay: cleanStoreName ? [cleanStoreName] : [],
            };
        } else {
            if (
                cleanStoreName &&
                !grouped[c.name].websiteDisplay.includes(cleanStoreName)
            ) {
                grouped[c.name].websiteDisplay.push(cleanStoreName);
            }
        }
    });

    return Object.values(grouped).map((c) => ({
        ...c,
        websiteDisplay: c.websiteDisplay.join(" & "),
    }));
});

function formatDateTime(date) {
    if (!date) return null;

    const d = date instanceof Date ? date : new Date(date);

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");

    const hours = String(d.getHours()).padStart(2, "0");
    const minutes = String(d.getMinutes()).padStart(2, "0");
    const seconds = String(d.getSeconds()).padStart(2, "0");

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function isRunning(data) {
    const now = new Date();
    const start = new Date(data.start_date);
    const end = new Date(data.end_date);
    return now >= start && now <= end;
}

function isCompleted(data) {
    const now = new Date();
    const end = new Date(data.end_date);
    return now > end;
}

function isUpcoming(data) {
    const now = new Date();
    const start = new Date(data.start_date);
    return now < start;
}
function getStatus(data) {
    return isRunning(data)
        ? "Active"
        : isCompleted(data)
          ? "Completed"
          : "Upcoming";
}

async function loadCampaigns() {
    loading.value = true;
    await Promise.all([
        store.loadCampaignTypes(),
        store.loadCampaigns(),
        store.loadStores(),
        store.loadSections(),
    ]);

    updateCalendarResourcesAndEvents();
    loading.value = false;
}

function updateCalendarResourcesAndEvents() {
    const resourcesBuilt = ref(false);
    const resources = store.stores.map((st) => {
        const sections = store.campaign_types
            .filter((sec) =>
                store.campaigns.some(
                    (c) =>
                        String(c.store_id) === String(st.store_id) &&
                        String(c.campaign_type_id) ===
                            String(sec.campaign_type_id),
                ),
            )
            .map((sec) => ({
                id: `${String(st.store_id)}-${String(sec.campaign_type_id)}`,
                title: sec.campaign_type_name,
            }));

        return {
            id: `store-${String(st.store_id)}`,
            title: st.store_name,
            children: sections,
        };
    });

    const sectionIndexMap = {};

    const events = store.campaigns.map((c) => {
        const key = `${c.store_id}-${c.campaign_type_id}`;

        if (!(key in sectionIndexMap)) {
            sectionIndexMap[key] = 0;
        }

        const color = ganttColors[sectionIndexMap[key] % ganttColors.length];
        sectionIndexMap[key]++;

        return {
            id: String(c.wc_id),
            resourceId: key,
            title: c.name,
            start: c.start_date
                ? new Date(c.start_date).toLocaleDateString("en-CA")
                : null,
            end: c.end_date
                ? new Date(
                      new Date(c.end_date).setDate(
                          new Date(c.end_date).getDate() + 1,
                      ),
                  ).toLocaleDateString("en-CA")
                : null,
            backgroundColor: color,
            borderColor: color,
            textColor: "#ffffff",
        };
    });

    calendarOptions.value = { ...calendarOptions.value, resources, events };

    const calendarApi = calendarRef.value?.getApi();
    if (calendarApi) {
        if (typeof calendarApi.removeAllResources === "function") {
            resources.forEach((r) => calendarApi.addResource(r));
        }

        if (typeof calendarApi.removeAllEvents === "function") {
            events.forEach((e) => calendarApi.addEvent(e));
        }
    }
}

async function openAddModal() {
    editTargetId.value = null;

    // Ensure campaign types are loaded first
    if (!store.campaign_types.length) {
        await store.loadCampaignTypes();
    }

    form.value = {
        name: "",
        campaign_type_id: "",
        section_id: "",
        store_id: "",
        start_date: null,
        end_date: null,
        is_all_store: false,
    };

    showDialog.value = true;
}

function openEditModal(c) {
    editTargetId.value = c.wc_id;
    form.value = {
        name: c.name,
        campaign_type_id: c.campaign_type_id,
        section_id: c.section_id,
        is_all_store: Boolean(c.is_applied_to_both_stores),
        store_id: c.store_id,
        start_date: c.start_date ? new Date(c.start_date) : null,
        end_date: c.end_date ? new Date(c.end_date) : null,
    };
    showDialog.value = true;
}

function closeDialog() {
    showDialog.value = false;
    editTargetId.value = null;
}

const dialogHeader = computed(() =>
    editTargetId.value ? "Edit Campaign" : "Add Campaign",
);
const saveLabel = computed(() => (editTargetId.value ? "Update" : "Create"));

async function saveCampaign() {
    processing.value = true;
    ui.showLoader();

    if (!validateForm()) {
        toastr.warning("Please correct the errors before saving.");
        processing.value = false;
        ui.hideLoader();
        return;
    }

    saving.value = true;

    const payload = {
        name: form.value.name,
        campaign_type_id: form.value.campaign_type_id,
        section_id: form.value.section_id,
        store_id: form.value.store_id,
        is_applied_to_both_stores: form.value.is_all_store,
        start_date: form.value.start_date
            ? formatDateTime(form.value.start_date)
            : null,
        end_date: form.value.end_date
            ? formatDateTime(form.value.end_date)
            : null,
    };

    try {
        if (editTargetId.value) {
            await store.editCampaign(editTargetId.value, payload);

            if (form.value.is_all_store) {
                const promises = store.stores
                    .filter(
                        (s) => String(s.store_id) !== String(payload.store_id),
                    )
                    .map((s) =>
                        store.addCampaign({ ...payload, store_id: s.store_id }),
                    );

                const results = await Promise.allSettled(promises);

                const failedStores = results
                    .map((res, idx) =>
                        res.status === "rejected"
                            ? store.stores[idx].store_name
                            : null,
                    )
                    .filter(Boolean);

                if (failedStores.length) {
                    toastr.warning(
                        `Campaign updated, but failed to sync to: ${failedStores.join(", ")}`,
                    );
                    ui.hideLoader();
                } else {
                    toastr.success(
                        "Campaign updated and synced to all stores.",
                    );
                    ui.hideLoader();
                }
            } else {
                toastr.success("Campaign updated.");
                processing.value = false;
                ui.hideLoader();
            }

            ui.hideLoader();
        } else {
            if (form.value.is_all_store) {
                const promises = store.stores.map((s) =>
                    store.addCampaign({ ...payload, store_id: s.store_id }),
                );
                const results = await Promise.allSettled(promises);

                const failedStores = results
                    .map((res, idx) =>
                        res.status === "rejected"
                            ? store.stores[idx].store_name
                            : null,
                    )
                    .filter(Boolean);

                if (failedStores.length) {
                    toastr.warning(
                        `Campaign added, but failed on: ${failedStores.join(", ")}`,
                    );
                    ui.hideLoader();
                } else {
                    toastr.success("Campaign added to all stores.");
                    ui.hideLoader();
                }
            } else {
                await store.addCampaign(payload);
                toastr.success("Campaign added.");
                processing.value = false;
                ui.hideLoader();
            }
        }

        showDialog.value = false;
        await store.loadCampaigns();
        updateCalendarResourcesAndEvents();
    } catch (err) {
        console.error(err);
        toastr.error("Failed to save campaign."); // only fires if main operation fails
        ui.hideLoader();
    } finally {
        saving.value = false;
        processing.value = false;
        ui.hideLoader();
    }
}

function confirmArchive(campaign) {
    campaignToArchive.value = campaign;
    showArchiveDialog.value = true;
}
function confirmDelete(campaign) {
    campaignToDelete.value = campaign;
    showDeleteDialog.value = true;
}

function formatDate(val) {
    if (!val) return "";

    try {
        const d = new Date(val);

        const datePart = d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });

        const timePart = d.toLocaleTimeString("en-US", {
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        });

        return `${datePart} at ${timePart}`;
    } catch {
        return val;
    }
}

async function archiveConfirmed() {
    showArchiveDialog.value = false;
    if (!campaignToArchive.value) return;

    loading.value = true;
    try {
        await store.archiveCampaign(campaignToArchive.value.wc_id, true);
        await store.loadCampaigns();
        updateCalendarResourcesAndEvents();
        toastr.success("Campaign Archived");
    } catch (error) {
        toastr.error("Failed to archive campaign.");
    } finally {
        loading.value = false;
        campaignToArchive.value = null;
    }

    showDialog.value = false;
    editTargetId.value = null;
}

async function deleteConfirmed() {
    showDeleteDialog.value = false;
    ui.showLoader();
    if (!campaignToDelete.value) return;

    loading.value = true;
    try {
        await store.removeCampaign(campaignToDelete.value.wc_id);
        await store.loadCampaigns(true);
        updateCalendarResourcesAndEvents();
        toastr.success(
            `Campaign "${campaignToDelete.value.name}" has been successfully deleted.`,
        );
        ui.hideLoader();
    } catch (error) {
        toastr.error("Failed to delete campaign.");
        ui.hideLoader();
    } finally {
        loading.value = false;
        campaignToDelete.value = null;
        ui.hideLoader();
    }

    showDialog.value = false;
    editTargetId.value = null;
    ui.hideLoader();
}

function validateForm() {
    let valid = true;

    // Reset
    Object.keys(errors.value).forEach((key) => (errors.value[key] = null));

    if (!form.value.name || form.value.name.trim() === "") {
        errors.value.name = "Name is required.";
        valid = false;
    }

    if (!form.value.campaign_type_id) {
        errors.value.campaign_type_id = "Select a campaign category.";
        valid = false;
    }

    if (!form.value.section_id) {
        errors.value.section_id = "Select a section.";
        valid = false;
    }

    if (!form.value.is_all_store && !form.value.store_id) {
        errors.value.store_id =
            "Select a website unless applying to both websites.";
        valid = false;
    }

    if (!form.value.start_date) {
        errors.value.start_date = "Start date is required.";
        valid = false;
    }

    if (!form.value.end_date) {
        errors.value.end_date = "End date is required.";
        valid = false;
    }

    if (form.value.start_date && form.value.end_date) {
        if (new Date(form.value.end_date) < new Date(form.value.start_date)) {
            errors.value.end_date =
                "End date cannot be earlier than start date.";
            valid = false;
        }
    }

    return valid;
}

const isEditingDuplicateName = computed(() => {
    if (!editTargetId.value) return false; // only relevant when editing
    const sameNameCampaigns = store.campaigns.filter(
        (c) =>
            c.name === form.value.name &&
            c.wc_id !== editTargetId.value
    );
    // If another campaign with the same name exists, hide checkbox
    return sameNameCampaigns.length > 0;
});

onMounted(async () => {
    await loadCampaigns();
});
</script>

<style scoped>
.p-datatable .p-sortable-column.p-highlight {
    background: rgba(59, 130, 246, 0.08) !important;
}

html.app-dark .p-datatable .p-sortable-column.p-highlight {
    background: #1f2937 !important;
    color: #e5e7eb !important;
}

html.app-dark .fc-datagrid-cell-main{
    background: #1f2937 !important;
    color: #e5e7eb !important;
}

html.app-dark .p-datatable .p-datatable-tbody > tr > td.p-highlight {
    background: #111827 !important;
    color: #e5e7eb !important;
}

.p-datatable .p-sortable-column,
.p-datatable .p-sortable-column.p-highlight {
    transition: background-color 0.15s ease;
}
.fc-datagrid-cell-main{
    color: black !important;
}
</style>
