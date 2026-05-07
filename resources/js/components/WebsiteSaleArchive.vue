<template>
    <div class="dark:bg-gray-900 bg-white p-5">
        <h4 class="font-semibold text-lg text-center p-2 app-dark:text-white">
            Archived Website Sale Details
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
            <div>
                <div
                    class="mb-2 flex justify-content-between flex-wrap gap-4 w-full"
                >
                    <div class="flex items-center w-full md:w-auto">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                placeholder="Search archived sale"
                                v-model="searchQuery"
                            />
                        </IconField>
                    </div>
                    <div class="flex items-center gap-2">
                        <label
                            for="campaignFilter"
                            class="text-black dark:text-white select-none mr-2"
                            >Filter Campaigns:</label
                        >
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
                    v-model:expandedRows="expandedRows"
                    dataKey="campaign_id"
                    showGridlines
                    scrollable
                    scrollHeight="60vh"
                    scrollDirection="both"
                    tableStyle="min-width: 100rem;"
                    size="small"
                    class="text-md bg-gray-900 mt-5"
                    emptyMessage="No archived sales available"
                    @rowExpand="onRowExpand"
                    @rowCollapse="onRowCollapse"
                    paginator
                    :rows="15"
                >
                    <Column expander style="width: 3rem" header="Expand" />
                    <Column header="Status" sortable sortField="statusOrder">
                        <template #body="{ data }">
                            <Badge
                                :value="data.status"
                                :severity="getSeverity(data.status)"
                                class="font-semibold"
                            />
                        </template>
                    </Column>
                    <Column field="store_name" header="Channel" />
                    <Column field="name" header="Event Name" />
                    <Column field="start_date" header="Event Start Date">
                        <template #body="{ data }">{{
                            formatDate(data.start_date)
                        }}</template>
                    </Column>
                    <Column field="end_date" header="Event End Date">
                        <template #body="{ data }">{{
                            formatDate(data.end_date)
                        }}</template>
                    </Column>

                    <template #expansion="{ data }">
                        <Transition
                            enter-active-class="expand-enter-active"
                            leave-active-class="expand-leave-active"
                            enter-from-class="expand-enter-from"
                            enter-to-class="expand-enter-to"
                            leave-from-class="expand-leave-from"
                            leave-to-class="expand-leave-to"
                        >
                            <div
                                class="p-4 text-black dark:text-gray-300 border border-gray-300 dark:border-gray-600"
                            >
                                <h4
                                    class="font-semibold text-lg mb-3 text-center text-black dark:text-white"
                                >
                                    Archived Website Sale Details for
                                    <span class="text-green-500">{{
                                        data.name
                                    }}</span>
                                    ({{ data.store_name }})
                                </h4>

                                <DataTable
                                    :value="buildTextTable(data)"
                                    showGridlines
                                    size="small"
                                    class="text-sm mb-5 mt-5 text-black dark:text-gray-200"
                                    tableStyle="min-width: 60rem"
                                >
                                    <Column
                                        field="featured_banner_text"
                                        header="Featured Category Banners Text"
                                    >
                                        <template #body="{ data: row }">
                                            <span
                                                v-html="
                                                    autoLink(
                                                        row.featured_banner_text,
                                                    )
                                                "
                                            ></span>
                                        </template>
                                    </Column>

                                    <Column
                                        field="sku_in_category_creative"
                                        header="SKU Feature in Category Creative"
                                    >
                                        <template #body="{ data: row }">
                                            <span
                                                v-html="
                                                    formatMultiline(
                                                        row.sku_in_category_creative,
                                                    )
                                                "
                                            ></span>
                                        </template>
                                    </Column>

                                    <Column
                                        field="url_text"
                                        header="URL Text in Landing Page Link"
                                    >
                                        <template #body="{ data: row }">
                                            <span
                                                v-html="
                                                    formatMultiline(
                                                        row.url_text,
                                                    )
                                                "
                                            ></span>
                                        </template>
                                    </Column>
                                </DataTable>

                                <table class="min-w-full rounded-lg text-md">
                                    <tbody>
                                        <tr
                                            v-for="(field, i) in fieldsList"
                                            :key="field.key"
                                            :class="[
                                                'border border-gray-300 dark:border-gray-800',
                                                i % 2 === 1
                                                    ? 'bg-gray-100 dark:bg-gray-900/50'
                                                    : '',
                                            ]"
                                        >
                                            <td
                                                class="font-semibold text-md py-2 px-3 w-1/3 text-black dark:text-gray-200"
                                            >
                                                {{ field.label }}
                                            </td>
                                            <td class="py-2 px-3">
                                                <span
                                                    class="text-black dark:text-gray-200"
                                                >
                                                    <template
                                                        v-if="
                                                            [
                                                                'event_master_sheet',
                                                                'run_sheet',
                                                                'featured_products_sheet_url',
                                                            ].includes(
                                                                field.key,
                                                            ) && data[field.key]
                                                        "
                                                    >
                                                        <a
                                                            :href="
                                                                data[field.key]
                                                            "
                                                            target="_blank"
                                                            :class="
                                                                field.key ===
                                                                'event_master_sheet'
                                                                    ? 'text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-500'
                                                                    : 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-500'
                                                            "
                                                        >
                                                            {{
                                                                replacer(
                                                                    data[
                                                                        field
                                                                            .key
                                                                    ],
                                                                )
                                                            }}
                                                        </a>
                                                    </template>
                                                    <template v-else>
                                                        {{
                                                            replacer(
                                                                data[field.key],
                                                            )
                                                        }}
                                                    </template>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </Transition>
                    </template>
                    <template #empty>
                        <div class="text-center py-10 text-gray-400">
                            <i class="pi pi-inbox text-3xl mb-3 block"></i>
                            <p class="text-lg font-semibold">No Data Yet</p>
                            <p class="text-sm">
                                Archived website sales will appear
                                here.
                            </p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Skeleton from "primevue/skeleton";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Badge from "primevue/badge";
import { useArchiveWebsiteSalesStore } from "@/js/stores/archive_website_sales_store";

const archivedStore = useArchiveWebsiteSalesStore();

const loading = ref(false);
const searchQuery = ref("");
const campaignFilter = ref("ALL");
const expandedRows = ref(null);

const fieldsList = [
    {
        key: "featured_products_sheet_url",
        label: "Products to be included in featured category (URL of the sheet)",
    },
    { key: "run_sheet", label: "Website Sale Execution - Run Sheet" },
    { key: "event_master_sheet", label: "Event Master Sheet" },
    { key: "ess", label: "ESS to Execute" },
    { key: "cms_to_audit", label: "CMS to Audit" },
    { key: "terms_conditions", label: "T&C's" },
    { key: "mockup_banner_locations", label: "Mock Up & Banner Locations" },
    { key: "is_sku_list_to_feature", label: "SKU List to Feature Provided?" },
];

const replacer = (val) => (val == null || val === "" ? "No Data Yet" : val);

const buildTextTable = (data) => [
    {
        featured_banner_text: data.featured_banner_text || "",
        sku_in_category_creative: data.sku_in_category_creative || "",
        url_text: data.url_text || "",
    },
];

const formatMultiline = (text) =>
    text ? text.replace(/\n/g, "<br>") : "No Data Yet";

const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
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

const onRowExpand = (event) => {
    expandedRows.value = { [event.data.campaign_id]: true };
};

const onRowCollapse = () => {
    expandedRows.value = null;
};

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

const filteredCampaigns = computed(() => {
    let campaigns = [...archivedStore.archivedWebsiteSales];
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
    if (campaignFilter.value === "RUNNING") {
        campaigns = campaigns.filter((c) => getStatus(c) === "RUNNING");
    } else if (campaignFilter.value === "ENDED") {
        campaigns = campaigns.filter((c) => getStatus(c) === "ENDED");
    }
    campaigns.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
    return campaigns;
});

onMounted(async () => {
    loading.value = true;
    await archivedStore.loadArchivedWebsiteSales();
    loading.value = false;
});
</script>

<style scoped>
td.py-2 {
    min-height: 2.5rem;
}
</style>
