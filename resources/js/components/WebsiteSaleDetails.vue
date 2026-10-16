<template>
    <div class="wsd-wrapper bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg shadow-sm"
        style="height: calc(97vh - 90px); display: flex; flex-direction: column;">
        <div v-if="loading" class="flex flex-col gap-3 w-full h-full p-4">
            <Skeleton height="1.5rem" width="100%" />
            <Skeleton height="1.5rem" width="100%" />
            <Skeleton height="100%" />
        </div>

        <div v-else class="p-2 flex-1 min-h-0 flex flex-col">
            <div
                class="mb-2 flex justify-between items-center flex-wrap gap-2 bg-gray-100 dark:bg-zinc-800 p-2 rounded border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-mono" :class="saveStatusClass">{{ saveStatusText }}</span>
                    <input v-model="quickFilter" type="search" placeholder="Search all columns…"
                        class="text-xs py-1 px-2 w-56 rounded border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900"
                        @keydown.enter="searchRows" />
                    <span class="text-xs text-gray-500">{{ visibleCount }} of {{ totalCount }} rows</span>
                    <Button label="Search" icon="pi pi-search" size="small" outlined @click="searchRows" />
                    <Button label="Clear search" icon="pi pi-filter-slash" size="small" text @click="clearSearch" />
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <input ref="excelInput" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="handleExcelSelected" />
                    <Button label="Import Excel" icon="pi pi-file-excel" size="small" outlined
                        @click="excelInput?.click()" />
                    <input ref="csvInput" type="file" accept=".csv" class="hidden" @change="handleCsvSelected" />
                    <Button label="Import CSV" icon="pi pi-upload" size="small" outlined @click="csvInput?.click()" />
                    <Button label="Add row" icon="pi pi-plus" size="small" outlined @click="addRow" />
                    <Button label="Delete selected" icon="pi pi-trash" size="small" outlined severity="danger"
                        @click="deleteSelected" />
                    <Button label="Pull" icon="pi pi-download" size="small" outlined :loading="syncing"
                        @click="pullNow" />
                    <Button label="Push" icon="pi pi-upload" size="small" outlined :loading="pushing"
                        @click="pushNow" />
                </div>
            </div>

            <div
                class="mb-2 flex items-center gap-2 flex-wrap bg-white dark:bg-zinc-900 p-2 rounded border border-gray-200 dark:border-zinc-700">
                <input v-model="snapshotName" type="text" maxlength="160"
                    placeholder="Snapshot name (e.g. Before October changes)"
                    class="text-xs py-1 px-2 w-72 rounded border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900" />
                <Button label="Capture snapshot" icon="pi pi-camera" size="small" outlined :loading="snapshotBusy"
                    @click="captureSnapshot" />
                <select v-model="selectedSnapshotId"
                    class="text-xs py-1 px-2 rounded border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 max-w-80">
                    <option value="">Choose a snapshot to restore…</option>
                    <option v-for="item in snapshots" :key="item.id" :value="String(item.id)">
                        {{ item.name }} — {{ formatSnapshotDate(item.created_at) }}
                    </option>
                </select>
                <Button label="Restore snapshot" icon="pi pi-history" size="small" outlined severity="secondary"
                    :disabled="!selectedSnapshotId" :loading="snapshotBusy" @click="restoreSnapshot" />
                <span class="text-xs text-gray-500">Snapshots include the workbook layout and cell data.</span>
            </div>

            <div class="flex-1 min-h-0 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden">
                <div ref="univerContainer" class="w-full h-full"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, shallowRef, onMounted, onBeforeUnmount, getCurrentInstance, nextTick } from "vue";
import Button from "primevue/button";
import Skeleton from "primevue/skeleton";
import Papa from "papaparse";
import ExcelJS from "exceljs";
import axios from "axios";
import { useWSDStore } from "@/js/stores/wsd_store.js";
import { createUniver, LocaleType, mergeLocales } from "@univerjs/presets";
import { UniverSheetsCorePreset } from "@univerjs/preset-sheets-core";
import UniverPresetSheetsCoreEnUS from "@univerjs/preset-sheets-core/locales/en-US";
import "@univerjs/preset-sheets-core/lib/index.css";

const wsdStore = useWSDStore();
const toastr = getCurrentInstance()?.appContext.config.globalProperties.$toastr;
const loading = ref(true);
const syncing = ref(false);
const pushing = ref(false);
const snapshotBusy = ref(false);
const saveStatusText = ref("Loading...");
const saveStatusClass = ref("text-gray-400");
const quickFilter = ref("");
const visibleCount = ref(0);
const totalCount = ref(0);
const csvInput = ref(null);
const excelInput = ref(null);
const univerContainer = ref(null);
const snapshotName = ref("");
const snapshots = ref([]);
const selectedSnapshotId = ref("");

let univerInstance = null;
let univerAPI = null;
let workbook = null;
let sheet = null;
let saveTimer = null;
let eventDisposables = [];
let internalUpdate = false;

const LAYOUT_ENDPOINT = "/api/website-sale-details/layout";
const SNAPSHOTS_ENDPOINT = "/api/website-sale-details/snapshots";
const DEFAULT_SHEET_ID = "website-sale-details";
const DEFAULT_SHEET_NAME = "Website Sale Details";

function setSaveStatus(text, className = "text-gray-400") {
    saveStatusText.value = text;
    saveStatusClass.value = className;
}
function notify(kind, message) {
    if (toastr?.[kind]) toastr[kind](message);
    else if (kind === "error") console.error(message);
    else console.log(message);
}
function safeDispose(disposable) {
    try { disposable?.dispose?.(); } catch (_) { /* already disposed */ }
}

function createBasicWorkbookData(headers, rows) {
    const normalizedHeaders = headers.length ? headers : ["Column A", "Column B", "Column C"];
    const cellData = { 0: {} };
    normalizedHeaders.forEach((header, col) => {
        cellData[0][col] = { v: String(header ?? ""), s: { bl: 1, vt: 2, tb: 2 } };
    });
    rows.forEach((row, i) => {
        cellData[i + 1] = {};
        normalizedHeaders.forEach((header, col) => {
            cellData[i + 1][col] = { v: row[header] == null ? "" : String(row[header]), s: { vt: 2, tb: 2 } };
        });
    });
    return {
        id: "wsd-workbook", name: DEFAULT_SHEET_NAME, appVersion: "1.0.0", locale: LocaleType.EN_US,
        styles: {}, sheetOrder: [DEFAULT_SHEET_ID],
        sheets: {
            [DEFAULT_SHEET_ID]: {
                id: DEFAULT_SHEET_ID, name: DEFAULT_SHEET_NAME,
                rowCount: Math.max(rows.length + 1, 100), columnCount: Math.max(normalizedHeaders.length, 3),
                defaultRowHeight: 24, defaultColumnWidth: 160,
                freeze: { xSplit: 0, ySplit: 1, startRow: 1, startColumn: 0 }, cellData,
            }
        },
    };
}

function getPrimarySheetData(data) {
    const id = data?.sheetOrder?.[0] || Object.keys(data?.sheets || {})[0];
    return id ? data?.sheets?.[id] : null;
}

function getCellValue(cell) {
    if (!cell) return "";
    if (cell.v !== undefined && cell.v !== null) return cell.v;
    return cell.f ? `=${cell.f}` : "";
}

function extractHeadersRows(data) {
    const ws = getPrimarySheetData(data);
    if (!ws) return { headers: ["Column A", "Column B", "Column C"], rows: [] };
    const cellData = ws.cellData || {};
    const headerRow = cellData[0] || {};
    const columnCount = Math.max(Number(ws.columnCount) || 0, ...Object.keys(headerRow).map(Number).filter(Number.isFinite).map(n => n + 1), 0);
    const headers = Array.from({ length: columnCount }, (_, col) => {
        const value = getCellValue(headerRow[col]);
        return String(value ?? "").trim() || `Column ${col + 1}`;
    });
    const rowIndexes = Object.keys(cellData).map(Number).filter(n => Number.isInteger(n) && n > 0).sort((a, b) => a - b);
    const rows = [];
    rowIndexes.forEach(rowIndex => {
        const source = cellData[rowIndex] || {};
        const row = {};
        let hasValue = false;
        headers.forEach((header, col) => {
            const value = getCellValue(source[col]);
            row[header] = value;
            if (value !== "" && value !== null && value !== undefined) hasValue = true;
        });
        if (hasValue) rows.push(row);
    });
    return { headers, rows };
}

function replaceWorkbook(data) {
    console.trace("[wsd-sheet] Workbook is being replaced");
    if (!univerAPI) return;
    internalUpdate = true;
    try {
        if (workbook?.getId) univerAPI.disposeUnit(workbook.getId());
        workbook = univerAPI.createWorkbook(data);
        sheet = workbook.getActiveSheet();
    } finally {
        // Let the initial render finish before change events can trigger a save.
        requestAnimationFrame(() => { internalUpdate = false; });
    }
}

function currentWorkbookData() {
    if (!workbook?.save) throw new Error("No active Univer workbook is available.");
    return workbook.save();
}

async function persistWorkbook({ saveRows = true } = {}) {
    if (internalUpdate || !workbook) return;
    const data = currentWorkbookData();
    if (saveRows) {
        const { headers, rows } = extractHeadersRows(data);
        await wsdStore.saveSheet(headers, rows);
        wsdStore.headers = headers;
        wsdStore.rows = rows;
        totalCount.value = rows.length;
        if (!quickFilter.value.trim()) visibleCount.value = rows.length;
        else searchRows();
    }
    await axios.put(LAYOUT_ENDPOINT, { workbook_data: data });
    setSaveStatus("Saved", "text-green-600");
}


function schedulePersist({ saveRows = false } = {}) {
    if (internalUpdate || !workbook) return;

    setSaveStatus("Saving…", "text-amber-500");
    clearTimeout(saveTimer);

    saveTimer = setTimeout(async () => {
        try {
            await persistWorkbook({ saveRows });
        } catch (error) {
            console.error("[wsd-sheet] Persist failed:", error);
            setSaveStatus("Save failed", "text-red-500");
        }
    }, 800);
}



function initializeUniver(data) {
    if (!univerContainer.value || univerInstance) return;

    const result = createUniver({
        locale: LocaleType.EN_US,
        locales: {
            [LocaleType.EN_US]: mergeLocales(UniverPresetSheetsCoreEnUS),
        },
        presets: [
            UniverSheetsCorePreset({
                container: univerContainer.value,
            }),
        ],
    });

    univerInstance = result.univer;
    univerAPI = result.univerAPI;

    // Load the workbook once. Do not rebuild it after edits.
    replaceWorkbook(data);

    // Cell-value changes update the data mirror and save the layout.
    eventDisposables.push(
        univerAPI.addEvent(
            univerAPI.Event.SheetValueChanged,
            () => schedulePersist({ saveRows: true })
        )
    );

    // Commands such as column resizing and formatting should save
    // the current workbook layout without rewriting the data mirror.
    eventDisposables.push(
        univerAPI.addEvent(
            univerAPI.Event.CommandExecuted,
            () => schedulePersist({ saveRows: false })
        )
    );

    // Intentionally do not subscribe to SheetSkeletonChanged.
    // It can fire for layout/rendering changes that don't need a save.

    setSaveStatus("Ready", "text-green-600");
}


function excelColor(color) {
    const raw = color?.argb || color?.rgb || color?.hex;
    if (!raw) return undefined;
    const value = String(raw).replace(/^#/, "");
    return { rgb: `#${value.length === 8 ? value.slice(2) : value}` };
}
function excelBorderSide(side) {
    if (!side || !side.style) return undefined;
    const styleMap = { thin: 1, hair: 1, dotted: 1, dashed: 1, dashDot: 1, dashDotDot: 1, slantDashDot: 1, medium: 2, thick: 3, double: 3 };
    return { s: styleMap[side.style] || 1, cl: excelColor(side.color) || { rgb: "#000000" } };
}
function excelCellStyle(cell) {
    const style = {};
    const font = cell.font || {};
    const fill = cell.fill || {};
    const alignment = cell.alignment || {};
    const border = cell.border || {};
    if (font.bold) style.bl = 1;
    if (font.italic) style.it = 1;
    if (font.underline) style.ul = { s: 1 };
    if (font.strike) style.st = { s: 1 };
    if (font.size) style.fs = font.size;
    if (font.name) style.ff = font.name;
    if (font.color) style.cl = excelColor(font.color);
    if (fill.type === "pattern" && fill.pattern === "solid") style.bg = excelColor(fill.fgColor);
    const horizontal = { left: 1, center: 2, right: 3, justify: 4, distributed: 4 };
    const vertical = { top: 1, middle: 2, bottom: 3, distributed: 3, justify: 3 };
    if (alignment.horizontal && horizontal[alignment.horizontal]) style.ht = horizontal[alignment.horizontal];
    if (alignment.vertical && vertical[alignment.vertical]) style.vt = vertical[alignment.vertical];
    if (alignment.wrapText) style.tb = 2;
    if (alignment.textRotation) style.tr = alignment.textRotation;
    const bd = {};
    if (border.top) bd.t = excelBorderSide(border.top);
    if (border.bottom) bd.b = excelBorderSide(border.bottom);
    if (border.left) bd.l = excelBorderSide(border.left);
    if (border.right) bd.r = excelBorderSide(border.right);
    if (Object.keys(bd).length) style.bd = bd;
    if (cell.numFmt) style.n = cell.numFmt;
    return Object.keys(style).length ? style : undefined;
}

function excelValue(cell) {
    const value = cell.value;

    if (value == null) return "";

    if (value instanceof Date) {
        return value.toISOString().slice(0, 10);
    }

    if (typeof value !== "object") {
        return value;
    }

    if (Array.isArray(value.richText)) {
        return value.richText.map(part => part.text ?? "").join("");
    }

    if (value.hyperlink) {
        return value.text ?? value.hyperlink;
    }

    if ("result" in value) {
        return value.result == null ? "" : String(value.result);
    }

    if (value.sharedFormula) {
        return value.result == null ? "" : String(value.result);
    }

    if (typeof value.text === "string") {
        return value.text;
    }

    if (value.error) {
        return String(value.error);
    }

    console.warn("[Excel import] Unhandled cell value:", value);
    return JSON.stringify(value);
}

function columnLetterToZeroBased(letters) {
    let n = 0;
    for (const char of letters) n = n * 26 + char.charCodeAt(0) - 64;
    return n - 1;
}
function convertExcelWorksheet(worksheet, fileName) {
    const rowCount = Math.max(worksheet.rowCount || worksheet.actualRowCount || 1, 1);
    const columnCount = Math.max(worksheet.columnCount || worksheet.actualColumnCount || 1, 1);
    const cellData = {};
    const rowData = {};
    const columnData = {};

    for (let rowIndex = 1; rowIndex <= rowCount; rowIndex++) {
        const row = worksheet.getRow(rowIndex);
        if (row.hidden) {
            rowData[rowIndex - 1] = { hd: 1 };
        } else if (row.height) {
            rowData[rowIndex - 1] = {
                h: row.height * 96 / 72
            };
        }
        const rowCells = {};
        for (let colIndex = 1; colIndex <= columnCount; colIndex++) {
            const cell = row.getCell(colIndex);
            if (cell.value == null && !cell.style) continue;
            const rawValue = cell.value;

            if (
                rawValue &&
                typeof rawValue === "object" &&
                !Array.isArray(rawValue)
            ) {
                console.log(
                    "[Excel cell inspection]",
                    cell.address,
                    JSON.stringify(rawValue, null, 2)
                );
            }

            const value = excelValue(cell);
            const entry = value && typeof value === "object" && "v" in value
                ? { v: value.v, ...(value.f ? { f: value.f } : {}) }
                : { v: value };
            const style = excelCellStyle(cell);
            if (style) entry.s = style;
            rowCells[colIndex - 1] = entry;
        }
        if (Object.keys(rowCells).length) cellData[rowIndex - 1] = rowCells;
    }

    for (let colIndex = 1; colIndex <= columnCount; colIndex++) {
        const col = worksheet.getColumn(colIndex);
        const column = {};
        if (col.width) column.w = Math.max(40, col.width * 7.2);
        if (col.hidden) column.hd = 1;
        if (Object.keys(column).length) columnData[colIndex - 1] = column;
    }

    const mergeData = [];
    const rawMerges = worksheet.model?.merges;
    const mergeRefs = Array.isArray(rawMerges) ? rawMerges : Object.keys(worksheet._merges || {});
    for (const ref of mergeRefs) {
        const match = String(ref).match(/^\$?([A-Z]+)\$?(\d+):\$?([A-Z]+)\$?(\d+)$/i);
        if (!match) continue;
        mergeData.push({
            startRow: Number(match[2]) - 1,
            endRow: Number(match[4]) - 1,
            startColumn: columnLetterToZeroBased(match[1].toUpperCase()),
            endColumn: columnLetterToZeroBased(match[3].toUpperCase()),
        });
    }

    const view = (worksheet.views || []).find(item => item.state === "frozen");
    const xSplit = view ? Number(view.xSplit || 0) : 0;
    const ySplit = view ? Number(view.ySplit || 0) : 0;
    return {
        id: "wsd-workbook", name: fileName || DEFAULT_SHEET_NAME, appVersion: "1.0.0", locale: LocaleType.EN_US,
        styles: {}, sheetOrder: [DEFAULT_SHEET_ID],
        sheets: {
            [DEFAULT_SHEET_ID]: {
                id: DEFAULT_SHEET_ID, name: DEFAULT_SHEET_NAME,
                rowCount, columnCount, defaultRowHeight: 24, defaultColumnWidth: 120,
                ...(Object.keys(rowData).length ? { rowData } : {}),
                ...(Object.keys(columnData).length ? { columnData } : {}),
                ...(mergeData.length ? { mergeData } : {}),
                freeze: { xSplit, ySplit, startRow: ySplit, startColumn: xSplit },
                cellData,
            }
        },
    };
}

async function loadSnapshotList() {
    try {
        const response = await axios.get(SNAPSHOTS_ENDPOINT);
        snapshots.value = response.data?.data ?? response.data?.snapshots ?? response.data ?? [];
    } catch (error) {
        console.error("[wsd-sheet] Could not load snapshots:", error);
    }
}
function formatSnapshotDate(value) {
    if (!value) return "unknown date";
    return new Date(value).toLocaleString();
}
async function captureSnapshot(customName = "") {
    snapshotBusy.value = true;
    try {
        const data = currentWorkbookData();
        const name = (customName || snapshotName.value || "Manual snapshot").trim();
        await axios.post(SNAPSHOTS_ENDPOINT, {
            name,
            source_filename: null,
            workbook_data: data,
        });
        snapshotName.value = "";
        await loadSnapshotList();
        notify("success", "Snapshot captured, including cell data and layout.");
    } catch (error) {
        console.error("[wsd-sheet] Snapshot failed:", error);
        notify("error", error.response?.data?.message || "Could not capture snapshot.");
    } finally {
        snapshotBusy.value = false;
    }
}
async function restoreSnapshot() {
    if (!selectedSnapshotId.value) return;
    if (!window.confirm("Restore this snapshot? The current workbook will be replaced. A separate snapshot is recommended first.")) return;
    snapshotBusy.value = true;
    try {
        await captureSnapshot(`Before restore ${new Date().toLocaleString()}`);
        const response = await axios.post(`${SNAPSHOTS_ENDPOINT}/${selectedSnapshotId.value}/restore`);
        const data = response.data?.workbook_data ?? response.data?.data?.workbook_data;
        if (!data) throw new Error("The server did not return workbook data.");
        replaceWorkbook(data);
        const { headers, rows } = extractHeadersRows(data);
        await wsdStore.saveSheet(headers, rows);
        wsdStore.headers = headers;
        wsdStore.rows = rows;
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: data });
        setSaveStatus("Snapshot restored", "text-green-600");
        notify("success", "Snapshot restored.");
    } catch (error) {
        console.error("[wsd-sheet] Restore failed:", error);
        notify("error", error.response?.data?.message || "Could not restore snapshot.");
    } finally {
        snapshotBusy.value = false;
    }
}

async function handleExcelSelected(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    try {
        if (workbook) await captureSnapshot(`Before import ${new Date().toLocaleString()}`);
        const buffer = await file.arrayBuffer();
        const excelWorkbook = new ExcelJS.Workbook();
        await excelWorkbook.xlsx.load(buffer);
        const targetSheet = excelWorkbook.worksheets.find(ws => ws.name.trim().toLowerCase() === "website sale details")
            || excelWorkbook.worksheets[0];
        if (!targetSheet) throw new Error("No worksheet was found in this Excel file.");
        if (targetSheet.name.trim().toLowerCase() !== "website sale details") {
            const useFirst = window.confirm(`This file has no “Website Sale Details” worksheet. Import the first worksheet, “${targetSheet.name}”, instead?`);
            if (!useFirst) return;
        }
        const data = convertExcelWorksheet(targetSheet, file.name);
        replaceWorkbook(data);
        const { headers, rows } = extractHeadersRows(data);
        await wsdStore.saveSheet(headers, rows);
        wsdStore.headers = headers;
        wsdStore.rows = rows;
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: data });
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        setSaveStatus("Excel imported", "text-green-600");
        await loadSnapshotList();
        notify("success", `Imported “${targetSheet.name}” (${targetSheet.rowCount} rows × ${targetSheet.columnCount} columns).`);
    } catch (error) {
        console.error("[wsd-sheet] Excel import failed:", error);
        setSaveStatus("Excel import failed", "text-red-500");
        notify("error", error.response?.data?.message || error.message || "Could not import this Excel file.");
    } finally {
        event.target.value = "";
    }
}

function searchRows() {
    const query = quickFilter.value.trim().toLowerCase();
    const headers = wsdStore.headers || [];
    const rows = wsdStore.rows || [];
    visibleCount.value = !query ? rows.length : rows.filter(row => headers.some(header => String(row[header] ?? "").toLowerCase().includes(query))).length;
}
function clearSearch() {
    quickFilter.value = "";
    visibleCount.value = totalCount.value;
}
function addRow() {
    if (!sheet) return;
    const headers = wsdStore.headers || [];
    const nextRow = Math.max(sheet.getMaxRows?.() || 1, (wsdStore.rows || []).length + 1);
    const values = [headers.map(() => "")];
    sheet.getRange(nextRow, 0, 1, headers.length).setValues(values);
    schedulePersist();
}
function deleteSelected() {
    notify("info", "Row deletion needs an explicit row-selection workflow. No rows were deleted.");
}
async function handleCsvSelected(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    Papa.parse(file, {
        header: false, skipEmptyLines: true,
        complete: async results => {
            try {
                const csvRows = results.data;
                if (!csvRows.length) throw new Error("CSV appears to be empty.");
                const headers = csvRows[0].map((value, index) => String(value ?? "").trim() || `Column ${index + 1}`);
                const rows = csvRows.slice(1).map(values => Object.fromEntries(headers.map((header, index) => [header, values[index] ?? ""])));
                const data = createBasicWorkbookData(headers, rows);
                replaceWorkbook(data);
                await wsdStore.saveSheet(headers, rows);
                wsdStore.headers = headers;
                wsdStore.rows = rows;
                await axios.put(LAYOUT_ENDPOINT, { workbook_data: data });
                totalCount.value = rows.length;
                visibleCount.value = rows.length;
                setSaveStatus("CSV imported", "text-green-600");
                notify("success", `Imported ${rows.length} CSV rows.`);
            } catch (error) {
                console.error("[wsd-sheet] CSV import failed:", error);
                notify("error", error.response?.data?.message || error.message || "CSV import failed.");
            } finally { event.target.value = ""; }
        },
        error: error => { notify("error", error.message || "Could not parse CSV."); event.target.value = ""; },
    });
}
async function pullNow() {
    syncing.value = true;
    try {
        await wsdStore.pullFromSheet();
        const data = currentWorkbookData();
        const ws = getPrimarySheetData(data);
        const headers = wsdStore.headers || [];
        const rows = wsdStore.rows || [];
        const cellData = { ...(ws.cellData || {}) };
        cellData[0] = { ...(cellData[0] || {}) };
        headers.forEach((header, col) => { cellData[0][col] = { ...(cellData[0][col] || {}), v: String(header ?? "") }; });
        rows.forEach((row, rowIndex) => {
            const idx = rowIndex + 1;
            cellData[idx] = { ...(cellData[idx] || {}) };
            headers.forEach((header, col) => { cellData[idx][col] = { ...(cellData[idx][col] || {}), v: row[header] ?? "" }; });
        });
        ws.cellData = cellData;
        ws.rowCount = Math.max(Number(ws.rowCount) || 0, rows.length + 1);
        replaceWorkbook(data);
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: data });
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        setSaveStatus("Pulled data; retained local layout", "text-green-600");
        notify("success", "Pulled Google Sheets data while retaining the locally stored layout.");
    } catch (error) {
        console.error("[wsd-sheet] Pull failed:", error);
        setSaveStatus("Pull failed", "text-red-500");
        notify("error", error.response?.data?.message || "Pull failed.");
    } finally { syncing.value = false; }
}
async function pushNow() {
    pushing.value = true;
    try {
        await persistWorkbook();
        await wsdStore.pushToSheet();
        setSaveStatus("Pushed to Google Sheets", "text-green-600");
        notify("success", "Pushed current table data to Google Sheets.");
    } catch (error) {
        console.error("[wsd-sheet] Push failed:", error);
        setSaveStatus("Push failed", "text-red-500");
        notify("error", error.response?.data?.message || "Push failed.");
    } finally { pushing.value = false; }
}


onMounted(async () => {
    try {
        await wsdStore.loadMirror();
    } catch (error) {
        console.warn(
            "[wsd-sheet] Mirror load failed; starting with empty data.",
            error
        );
        wsdStore.headers = [];
        wsdStore.rows = [];
    }

    // Remove the loading skeleton so the Univer container is rendered.
    loading.value = false;
    await nextTick();

    try {
        const response = await axios.get(LAYOUT_ENDPOINT);
        const savedLayout =
            response.data?.workbook_data ??
            response.data?.data?.workbook_data;

        const initialData =
            savedLayout ??
            createBasicWorkbookData(
                wsdStore.headers || [],
                wsdStore.rows || []
            );

        // The container now exists, so Univer can initialize correctly.
        initializeUniver(initialData);

        if (!univerInstance || !workbook) {
            throw new Error(
                "Univer did not initialize. Check the browser console."
            );
        }

        if (savedLayout) {
            const { headers, rows } = extractHeadersRows(savedLayout);
            wsdStore.headers = headers;
            wsdStore.rows = rows;
        }

        totalCount.value = (wsdStore.rows || []).length;
        visibleCount.value = totalCount.value;

        await loadSnapshotList();
    } catch (error) {
        console.error(
            "[wsd-sheet] Layout load/Univer initialization failed:",
            error
        );
        setSaveStatus("Initialization failed", "text-red-500");
        notify(
            "error",
            error.response?.data?.message ||
            error.message ||
            "Could not load the spreadsheet. Check the layout API and browser console."
        );
    }
});


onBeforeUnmount(() => {
    clearTimeout(saveTimer);
    eventDisposables.forEach(safeDispose);
    eventDisposables = [];
    if (univerInstance) univerInstance.dispose();
    univerInstance = null;
    univerAPI = null;
    workbook = null;
    sheet = null;
});
</script>

<style>
.wsd-wrapper {
    min-width: 0;
}

.wsd-wrapper .univer-container {
    width: 100%;
    height: 100%;
}
</style>
