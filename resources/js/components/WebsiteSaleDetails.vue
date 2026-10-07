<template>
    <div class="wsd-wrapper bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg shadow-sm"
        style="height: calc(97vh - 90px); display: flex; flex-direction: column;">

        <template v-if="loading">
            <div class="flex flex-col gap-3 w-full h-full p-4">
                <Skeleton height="1.5rem" width="100%" />
                <Skeleton height="1.5rem" width="100%" />
                <div class="flex-1 mt-2">
                    <Skeleton height="100%" borderRadius="4px" />
                </div>
            </div>
        </template>

        <div v-else class="p-2" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
            <div
                class="mb-2 flex justify-between items-center flex-wrap gap-2 w-full bg-gray-100 dark:bg-zinc-800 p-1.5 rounded border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono" :class="saveStatusClass">{{ saveStatusText }}</span>

                    <input v-model="quickFilter" type="search" placeholder="Search all columns…"
                        class="text-xs py-1 px-2 w-56 rounded border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-gray-800 dark:text-zinc-100 focus:outline-none focus:border-blue-500" />

                    <span class="text-xs text-gray-500 dark:text-zinc-400">
                        {{ visibleCount }} of {{ totalCount }} rows
                    </span>

                    <Button label="Clear filters" icon="pi pi-filter-slash" size="small" text
                        class="text-xs py-1 px-2" @click="clearFilters" />
                </div>
                <div class="flex items-center gap-2">
                    <Button label="Add row" icon="pi pi-plus" size="small" outlined class="text-xs py-1 px-2"
                        @click="addRow" />
                    <Button label="Delete selected" icon="pi pi-trash" size="small" outlined severity="danger"
                        class="text-xs py-1 px-2" :disabled="selectedCount === 0" @click="deleteSelected" />

                    <input ref="csvInput" type="file" accept=".csv" class="hidden" @change="handleCsvSelected" />
                    <Button label="Import CSV" icon="pi pi-upload" size="small" outlined class="text-xs py-1 px-2"
                        @click="csvInput.click()" />

                    <span v-if="wsdStore.lastSyncedAt" class="text-xs text-gray-500 dark:text-zinc-400">
                        Last pulled: {{ new Date(wsdStore.lastSyncedAt).toLocaleTimeString() }}
                    </span>
                </div>
            </div>

            <div class="flex-1 min-height-0 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden"
                style="position: relative;" :data-ag-theme-mode="isDark ? 'dark' : 'light'">
                <AgGridVue style="position:absolute; width:100%; height:100%; left:0; top:0;" :theme="gridTheme"
                    :columnDefs="columnDefs" :rowData="rowData" :defaultColDef="defaultColDef"
                    :getRowId="getRowId" :rowSelection="rowSelection" :suppressFieldDotNotation="true"
                    :enableCellTextSelection="true" :ensureDomOrder="true" :stopEditingWhenCellsLoseFocus="true"
                    :singleClickEdit="false" :quickFilterText="quickFilter" @grid-ready="onGridReady"
                    @cell-value-changed="onCellValueChanged" @selection-changed="onSelectionChanged"
                    @filter-changed="updateCounts" @row-data-updated="updateCounts" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, shallowRef, onMounted, onBeforeUnmount, getCurrentInstance } from "vue";
import Button from "primevue/button";
import Skeleton from "primevue/skeleton";
import { useWSDStore } from "@/js/stores/wsd_store.js";
import Papa from "papaparse";
import { AgGridVue } from "ag-grid-vue3";
import {
    ModuleRegistry,
    AllCommunityModule,
    themeQuartz,
    colorSchemeVariable,
} from "ag-grid-community";

ModuleRegistry.registerModules([AllCommunityModule]);

const wsdStore = useWSDStore();
const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;

const loading = ref(false);
const syncing = ref(false);
const pushing = ref(false);
const saveStatusText = ref("");
const saveStatusClass = ref("text-gray-400");
const selectedCount = ref(0);
const quickFilter = ref("");
const visibleCount = ref(0);
const totalCount = ref(0);
const csvInput = ref(null);

// grid state
const columnDefs = shallowRef([]);
const rowData = shallowRef([]);
let gridApi = null;
let currentHeaders = [];
let saveTimer = null;
let ridCounter = 0;

// ---------------------------------------------------------------------------
// Theme (follows the app's `dark` class on <html>)
// ---------------------------------------------------------------------------
const isDark = ref(document.documentElement.classList.contains("dark"));
let themeObserver = null;

const gridTheme = themeQuartz.withPart(colorSchemeVariable).withParams({
    fontFamily: "Arial, sans-serif",
    fontSize: 12,
    spacing: 5,
    headerFontSize: 12,
    headerFontWeight: 600,
    wrapperBorder: false,
    // visible grid lines between every row and column (adapts to light/dark)
    borderColor: { ref: "foregroundColor", mix: 0.22 },
    rowBorder: true,
    columnBorder: true,
    headerColumnBorder: true,
    headerRowBorder: true,
});

const MIN_COLUMN_WIDTH = 90;
const MAX_COLUMN_WIDTH = 480;
const CELL_H_PADDING = 32;

let measureCtx = null;
function measureTextWidth(text, font = "12px Arial") {
    if (!measureCtx) measureCtx = document.createElement("canvas").getContext("2d");
    measureCtx.font = font;
    return measureCtx.measureText(String(text ?? "")).width;
}

function estimateColumnWidth(header, rows) {
    // longest single word in the header (header text wraps)
    let maxWidth = 0;
    for (const word of String(header).split(/\s+/)) {
        maxWidth = Math.max(maxWidth, measureTextWidth(word, "600 12px Arial"));
    }

    for (const row of rows) {
        for (const line of String(row[header] ?? "").split(/\r?\n/)) {
            maxWidth = Math.max(maxWidth, measureTextWidth(line));
        }
    }

    return Math.min(MAX_COLUMN_WIDTH, Math.max(MIN_COLUMN_WIDTH, Math.ceil(maxWidth + CELL_H_PADDING)));
}

const defaultColDef = {
    editable: true,
    resizable: true,
    sortable: true,
    filter: "agTextColumnFilter",       
    floatingFilter: true,     
    filterParams: { debounceMs: 200, buttons: ["reset"] },
    wrapText: true,
    autoHeight: true,          
    wrapHeaderText: true,
    autoHeaderHeight: true,
    minWidth: MIN_COLUMN_WIDTH,
    cellClass: "wsd-cell",
    cellEditor: "agLargeTextCellEditor",
    cellEditorPopup: true,
    cellEditorParams: { maxLength: 10000, rows: 12, cols: 60 },
};

const rowSelection = {
    mode: "multiRow",
    checkboxes: true,
    headerCheckbox: false,
    enableClickSelection: false,
};

const getRowId = (params) => params.data.__rid;

function nextRid() {
    ridCounter += 1;
    return `r${ridCounter}`;
}

function buildColumnDefs(headers, rows) {
    return headers.map((h, i) => ({
        colId: `c${i}`,
        field: h,
        headerName: h.replace(/\s+/g, " ").trim(),
        headerTooltip: h.replace(/\s+/g, " ").trim(),
        width: estimateColumnWidth(h, rows),
        pinned: undefined,
    }));
}

function applyDataToGrid(headers, rows) {
    currentHeaders = headers;
    columnDefs.value = buildColumnDefs(headers, rows);
    rowData.value = rows.map((r) => ({ ...r, __rid: nextRid() }));
}

function collectRows() {
    const rows = [];
    gridApi.forEachNode((node) => {
        const data = node.data;
        if (!data) return;

        const hasContent = currentHeaders.some((h) => {
            const v = data[h];
            return v !== null && v !== undefined && v !== "";
        });
        if (!hasContent) return;

        const obj = {};
        currentHeaders.forEach((h) => {
            obj[h] = data[h] ?? "";
        });
        rows.push(obj);
    });
    return rows;
}

function scheduleSave() {
    saveStatusText.value = "Saving…";
    saveStatusClass.value = "text-amber-500";

    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveImmediately, 300);
}

async function saveImmediately() {
    saveTimer = null;
    try {
        if (!gridApi || currentHeaders.length === 0) {
            saveStatusText.value = "Skipped empty save";
            saveStatusClass.value = "text-amber-500";
            return;
        }

        await wsdStore.saveSheet(currentHeaders, collectRows());
        saveStatusText.value = "Saved";
        saveStatusClass.value = "text-green-600";
    } catch (e) {
        console.error("[wsd-sheet] save failed:", e);
        saveStatusText.value = "Save failed";
        saveStatusClass.value = "text-red-500";
    }
}

function onGridReady(params) {
    gridApi = params.api;
}

function onCellValueChanged(e) {
    if (e.oldValue === e.newValue) return;
    scheduleSave();
}

function onSelectionChanged() {
    selectedCount.value = gridApi ? gridApi.getSelectedRows().length : 0;
}

function updateCounts() {
    if (!gridApi) return;
    let total = 0;
    gridApi.forEachNode(() => { total += 1; });
    totalCount.value = total;
    visibleCount.value = gridApi.getDisplayedRowCount();
}

function clearFilters() {
    quickFilter.value = "";
    gridApi?.setFilterModel(null);
}

function addRow() {
    if (!gridApi || currentHeaders.length === 0) return;

    const row = { __rid: nextRid() };
    currentHeaders.forEach((h) => { row[h] = ""; });

    gridApi.applyTransaction({ add: [row] });

    const lastIndex = gridApi.getDisplayedRowCount() - 1;
    gridApi.ensureIndexVisible(lastIndex, "bottom");
    gridApi.startEditingCell({ rowIndex: lastIndex, colKey: "c0" });
}

function deleteSelected() {
    if (!gridApi) return;
    const selected = gridApi.getSelectedRows();
    if (!selected.length) return;

    gridApi.applyTransaction({ remove: selected });
    selectedCount.value = 0;
    scheduleSave();
}

function loadFromStore() {
    const headers = wsdStore.headers.length ? wsdStore.headers : ["Column A", "Column B", "Column C"];
    applyDataToGrid(headers, wsdStore.rows);
}

async function pullNow() {
    syncing.value = true;
    try {
        await wsdStore.pullFromSheet();
        loadFromStore();
        toastr.success("Pulled latest from Google Sheet.");
    } catch (e) {
        toastr.error("Pull failed.");
    } finally {
        syncing.value = false;
    }
}

async function pushNow() {
    pushing.value = true;
    try {
        await wsdStore.pushToSheet();
        toastr.success("Pushed current data to Google Sheet.");
    } catch (e) {
        toastr.error("Push failed.");
    } finally {
        pushing.value = false;
    }
}

function handleCsvSelected(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    Papa.parse(file, {
        header: false,
        skipEmptyLines: true,
        complete: async (results) => {
            try {
                const rows2d = results.data;
                if (!rows2d.length) { toastr.warning("CSV appears to be empty."); return; }

                const headers = rows2d[0].map((h) => String(h ?? "").trim());
                const dataRows = rows2d.slice(1).map((row) => {
                    const obj = {};
                    headers.forEach((h, i) => { if (h) obj[h] = row[i] ?? ""; });
                    return obj;
                });

                wsdStore.headers = headers.filter((h) => h !== "");
                wsdStore.rows = dataRows;

                loadFromStore();
                await wsdStore.saveSheet(wsdStore.headers, wsdStore.rows);
                toastr.success(`Imported ${dataRows.length} row(s).`);
            } catch (err) {
                console.error("[wsd-sheet] CSV import failed:", err);
                toastr.error("Import failed — check the CSV format.");
            } finally {
                e.target.value = "";
            }
        },
        error: (err) => {
            console.error("[wsd-sheet] CSV parse error:", err);
            toastr.error("Could not parse that CSV.");
        },
    });
}

onMounted(async () => {
    loading.value = true;

    themeObserver = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains("dark");
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });

    try {
        await wsdStore.loadMirror();
    } catch (e) {
        console.warn("[wsd-sheet] No saved sheet found. Starting with an empty sheet.", e);
        wsdStore.headers = [];
        wsdStore.rows = [];
    }

    loadFromStore();
    loading.value = false;
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();

    if (saveTimer) {
        clearTimeout(saveTimer);
        saveImmediately();
    }
});
</script>

<style>

.wsd-wrapper .ag-cell.wsd-cell {
    white-space: pre-wrap;    
    word-break: break-word;   
    line-height: 1.45;
    padding-top: 6px;
    padding-bottom: 6px;
    align-items: flex-start;
}

.wsd-wrapper .ag-header-cell-label {
    align-items: center;
}
</style>