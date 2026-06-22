<template>
    <Dialog v-model:visible="visible" header="Import Website Sale Details" :modal="true" :closable="!busy"
        class="w-full" style="max-width: 1100px">

        <!-- Step 1: Upload -->
        <div v-if="step === 'upload'" class="flex flex-col gap-3">
            <p class="text-sm text-gray-500">
                Upload a CSV export. You'll map its columns to fields before anything is saved.
            </p>
            <input type="file" accept=".csv" @change="onFileSelected" />
        </div>

        <!-- Step 2: Column mapping -->
        <div v-else-if="step === 'mapping'" class="flex flex-col gap-3">
            <p class="text-sm text-gray-500">
                Map each CSV column to a field. Channel, Event Name, Start Date and End Date
                identify the event — rows with the same combo update the existing record.
            </p>
            <div class="overflow-auto" style="max-height: 50vh">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="p-2">CSV Column</th>
                            <th class="p-2">Sample Value</th>
                            <th class="p-2">Maps To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(header, i) in csvHeaders" :key="i" class="border-b">
                            <td class="p-2 font-medium">{{ header || '(blank header)' }}</td>
                            <td class="p-2 text-gray-400 truncate max-w-xs">{{ sampleValue(i) }}</td>
                            <td class="p-2">
                                <Select v-model="columnMap[i]" :options="targetFields" optionLabel="label"
                                    optionValue="value" class="w-64" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end gap-2 mt-2">
                <Button label="Back" class="p-button-secondary" @click="step = 'upload'" />
                <Button label="Preview" :loading="busy" @click="runPreview" />
            </div>
        </div>

        <!-- Step 3: Review -->
        <div v-else-if="step === 'review'" class="flex flex-col gap-3">
            <p class="text-sm text-gray-500">
                {{ validCount }} of {{ previewRows.length }} rows are ready to import
                ({{ previewRows.length - validCount }} skipped — missing Channel or Event Name,
                e.g. year-grouping rows from the sheet).
            </p>
            <div class="overflow-auto" style="max-height: 50vh">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="p-2">Channel</th>
                            <th class="p-2">Event Name</th>
                            <th class="p-2">Dates</th>
                            <th class="p-2">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in previewRows" :key="row.row_index" class="border-b"
                            :class="{ 'opacity-40': !row.valid }">
                            <td class="p-2">{{ row.channel_name }}</td>
                            <td class="p-2">{{ row.event_name }}</td>
                            <td class="p-2 text-xs">{{ row.start_date }} → {{ row.end_date }}</td>
                            <td class="p-2">
                                <span v-if="!row.valid" class="text-gray-400">Skipped — missing data</span>
                                <span v-else-if="row.will_update" class="text-amber-500 font-semibold">Will update existing</span>
                                <span v-else class="text-green-600 font-semibold">Will create new</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end gap-2 mt-2">
                <Button label="Back" class="p-button-secondary" @click="step = 'mapping'" />
                <Button label="Confirm Import" severity="success" :loading="busy" @click="runCommit" />
            </div>
        </div>

        <!-- Step 4: Done -->
        <div v-else-if="step === 'done'" class="flex flex-col gap-2 text-center py-6">
            <i class="pi pi-check-circle text-4xl text-green-500"></i>
            <p>{{ result.details_saved }} record(s) saved, {{ result.skipped }} skipped.</p>

            <div v-if="result.errors?.length" class="text-left text-sm mt-3">
                <p class="text-red-500 font-semibold mb-1">{{ result.errors.length }} row(s) failed:</p>
                <div class="max-h-48 overflow-auto border border-red-900 rounded p-2 bg-black/20">
                    <div v-for="(err, i) in result.errors" :key="i" class="text-red-400 py-0.5 border-b border-red-900/40">
                        <strong>{{ err.row || '(row ' + i + ')' }}:</strong> {{ err.error }}
                    </div>
                </div>
            </div>

            <Button label="Close" class="mt-3 mx-auto" @click="close" />
        </div>
    </Dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Button from "primevue/button";
import Papa from "papaparse";
import { useWSDStore } from "@/js/stores/wsd_store";

const wsdStore = useWSDStore();
const emit = defineEmits(["imported"]);

const visible = ref(false);
const step = ref("upload");
const busy = ref(false);

const csvHeaders = ref([]);
const csvRows = ref([]);
const columnMap = ref({});
const previewRows = ref([]);
const result = ref(null);

const targetFields = [
    { value: "skip", label: "— Skip —" },
    { value: "store_name", label: "Channel (identifies event)" },
    { value: "name", label: "Event Name (identifies event)" },
    { value: "start_date", label: "Start Date (identifies event)" },
    { value: "end_date", label: "End Date" },
    { value: "terms_conditions", label: "T&Cs" },
    { value: "mockup_banner_locations", label: "Mockup & Banner Locations" },
    { value: "featured_products_sheet_url", label: "Featured Products Sheet URL" },
    { value: "event_master_sheet_url", label: "Event Master Sheet URL" },
    { value: "run_sheet_url", label: "Run Sheet URL" },
    { value: "is_sku_list_to_feature", label: "SKU List to Feature? (Yes/No)" },
    { value: "ess", label: "ESS to Execute" },
    { value: "cms_to_audit", label: "CMS to Audit" },
    { value: "featured_banner_text", label: "Featured Banner Text" },
    { value: "sku_in_category_creative", label: "SKU in Category Creative" },
    { value: "url_text", label: "URL Text" },
];

const guessMap = {
    store_name: ["channel"],
    name: ["event name"],
    start_date: ["start date"],
    end_date: ["end date"],
    terms_conditions: ["t&c"],
    mockup_banner_locations: ["mock up", "banner location"],
    event_master_sheet_url: ["event master sheet"],
    run_sheet_url: ["run sheet"],
    is_sku_list_to_feature: ["sku list to feature"],
    ess: ["ess"],
    cms_to_audit: ["cms"],
    featured_banner_text: ["featured category banners text"],
    sku_in_category_creative: ["sku feature in"],
    url_text: ["url text"],
};

function autoGuess(header) {
    const h = (header || "").toLowerCase();
    for (const [field, keywords] of Object.entries(guessMap)) {
        if (keywords.some((k) => h.includes(k))) return field;
    }
    return "skip";
}

function open() {
    visible.value = true;
    step.value = "upload";
    csvHeaders.value = [];
    csvRows.value = [];
    columnMap.value = {};
    previewRows.value = [];
    result.value = null;
}

function close() {
    visible.value = false;
    emit("imported", result.value);
}

function onFileSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    Papa.parse(file, {
        skipEmptyLines: true,
        complete: (res) => {
            const data = res.data;
            const headerRowIndex = data.findIndex((r) =>
                r.some((cell) => String(cell).toLowerCase().includes("event name")),
            );
            const headerRow = headerRowIndex >= 0 ? data[headerRowIndex] : data[0];
            const bodyRows = data.slice((headerRowIndex >= 0 ? headerRowIndex : 0) + 1);

            csvHeaders.value = headerRow;
            csvRows.value = bodyRows.filter((r) => r.some((cell) => String(cell).trim() !== ""));

            const map = {};
            headerRow.forEach((h, i) => (map[i] = autoGuess(h)));
            columnMap.value = map;

            step.value = "mapping";
        },
    });
}

function sampleValue(colIndex) {
    const row = csvRows.value.find((r) => r[colIndex]);
    return row ? row[colIndex] : "";
}

function buildMappedRows() {
    return csvRows.value
        .map((row) => {
            const mapped = { fields: {} };
            Object.entries(columnMap.value).forEach(([colIndex, target]) => {
                if (target === "skip") return;
                const value = row[colIndex] ?? "";
                if (["store_name", "name", "start_date", "end_date"].includes(target)) {
                    mapped[target] = value;
                } else {
                    mapped.fields[target] = value;
                }
            });
            return mapped;
        })
        .filter((row) => (row.name && row.name.trim()) || (row.store_name && row.store_name.trim()));
}

const validCount = computed(() => previewRows.value.filter((r) => r.valid).length);

async function runPreview() {
    busy.value = true;
    try {
        const rows = buildMappedRows();
        const data = await wsdStore.previewImport(rows);
        previewRows.value = data.rows;
        step.value = "review";
    } catch (err) {
        console.error("Preview failed:", err);
    } finally {
        busy.value = false;
    }
}

async function runCommit() {
    busy.value = true;
    try {
        const rows = previewRows.value
            .filter((r) => r.valid)
            .map((r) => ({
                event_name: r.event_name,
                channel_name: r.channel_name,
                start_date: r.start_date,
                end_date: r.end_date,
                fields: r.fields,
            }));
        const data = await wsdStore.commitImport(rows);
        result.value = data;
        step.value = "done";
    } catch (err) {
        console.error("Import failed:", err);
    } finally {
        busy.value = false;
    }
}

defineExpose({ open });
</script>