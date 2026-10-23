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
                    <input ref="csvInput" type="file" accept=".csv" class="hidden" @change="handleCsvSelected"
                        style="display: none;" />
                    <Button label="Import CSV" icon="pi pi-upload" size="small" outlined @click="csvInput?.click()"
                        style="display: none;" />
                    <Button label="Add row" icon="pi pi-plus" size="small" outlined @click="addRow"
                        style="display: none;" />
                    <Button label="Delete selected" icon="pi pi-trash" size="small" outlined severity="danger"
                        @click="deleteSelected" style="display: none;" />
                    <Button label="Pull" icon="pi pi-download" size="small" outlined :loading="syncing" @click="pullNow"
                        style="display: none;" />
                    <Button label="Push" icon="pi pi-upload" size="small" outlined :loading="pushing" @click="pushNow"
                        style="display: none;" />
                </div>
            </div>

            <div style="display: none;"
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
            </div>

            <div class="flex-1 min-h-0 border border-gray-300 dark:border-zinc-700 rounded overflow-hidden">
                <div ref="univerContainer" class="w-full h-full"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, getCurrentInstance, nextTick } from "vue";
import Button from "primevue/button";
import Skeleton from "primevue/skeleton";
import Papa from "papaparse";
import ExcelJS from "exceljs";
import axios from "axios";
import { useWeeklyCalendarStore } from "@/js/stores/weekly_calendar_store.js";
import { createUniver, LocaleType, mergeLocales } from "@univerjs/presets";
import { UniverSheetsCorePreset } from "@univerjs/preset-sheets-core";
import UniverPresetSheetsCoreEnUS from "@univerjs/preset-sheets-core/locales/en-US";
import "@univerjs/preset-sheets-core/lib/index.css";

import { UniverSheetsHyperLinkPreset } from "@univerjs/preset-sheets-hyper-link";
import UniverPresetSheetsHyperLinkEnUS from "@univerjs/preset-sheets-hyper-link/locales/en-US";
import "@univerjs/preset-sheets-hyper-link/lib/index.css";

const weeklyCalendarStore = useWeeklyCalendarStore();
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

const LAYOUT_ENDPOINT = "/api/weekly-calendar/layout";
const SNAPSHOTS_ENDPOINT = "/api/weekly-calendar/snapshots";
const DEFAULT_SHEET_ID = "weekly-calendar";
const DEFAULT_SHEET_NAME = "Weekly Calendar";

const BORDER_STYLE = {
    thin: 1, hair: 2, dotted: 3, dashed: 4, dashDot: 5, dashDotDot: 6,
    medium: 7, mediumDashed: 8, mediumDashDot: 9, mediumDashDotDot: 10,
    slantDashDot: 11, thick: 12, double: 13,
};

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

function packWorkbook(data) {
    return { __json: JSON.stringify(data) };
}

function unpackWorkbook(raw) {
    if (!raw) return raw;
    try {
        if (typeof raw === "string") return JSON.parse(raw);
        if (typeof raw.__json === "string") return JSON.parse(raw.__json);
    } catch (error) {
        console.error("[wsd-sheet] Could not parse stored workbook:", error);
    }
    return raw; // old layouts saved before this change
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
        await weeklyCalendarStore.saveSheet(headers, rows);
        weeklyCalendarStore.headers = headers;
        weeklyCalendarStore.rows = rows;
        totalCount.value = rows.length;
        if (!quickFilter.value.trim()) visibleCount.value = rows.length;
        else searchRows();
    }
    await axios.put(LAYOUT_ENDPOINT, { workbook_data: packWorkbook(data) });
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
            [LocaleType.EN_US]: mergeLocales(
                UniverPresetSheetsCoreEnUS,
                UniverPresetSheetsHyperLinkEnUS
            ),
        },
        presets: [
            UniverSheetsCorePreset({ container: univerContainer.value }),
            UniverSheetsHyperLinkPreset(),
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

    eventDisposables.push(
        univerAPI.addEvent(
            univerAPI.Event.CommandExecuted,
            () => schedulePersist({ saveRows: false })
        )
    );

    setSaveStatus("Ready", "text-green-600");
}


function excelColor(color) {
    const raw = color?.argb || color?.rgb || color?.hex;
    if (!raw) return undefined;
    const value = String(raw).replace(/^#/, "");
    return { rgb: `#${value.length === 8 ? value.slice(2) : value}` };
}
function excelBorderSide(side) {
    if (!side?.style) return undefined;
    return { s: BORDER_STYLE[side.style] || 1, cl: excelColor(side.color) || { rgb: "#000000" } };
}

function getCellValue(cell) {
    if (!cell) return "";
    if (cell.v !== undefined && cell.v !== null) return cell.v;
    const text = cell.p?.body?.dataStream;
    if (typeof text === "string") return text.replace(/\r\n$/, "");
    return cell.f ? `=${cell.f}` : "";
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
    style.tb = 3;                                   // was 2
    if (typeof alignment.textRotation === "number" && alignment.textRotation) {
        style.tr = { a: alignment.textRotation };                           // was a bare number
    }
    const bd = {};
    if (border.top) bd.t = excelBorderSide(border.top);
    if (border.bottom) bd.b = excelBorderSide(border.bottom);
    if (border.left) bd.l = excelBorderSide(border.left);
    if (border.right) bd.r = excelBorderSide(border.right);
    if (Object.keys(bd).length) style.bd = bd;
    if (cell.numFmt && cell.numFmt !== "General") style.n = { pattern: cell.numFmt };  // was a string
    return Object.keys(style).length ? style : undefined;
}

function plainValue(value) {
    if (value == null) return "";
    if (value instanceof Date) return value.toISOString().slice(0, 10);
    if (typeof value !== "object") return value;

    if (Array.isArray(value.richText)) return value.richText.map(p => p.text ?? "").join("");
    if ("result" in value || value.formula || value.sharedFormula) return plainValue(value.result);
    if (value.hyperlink || "text" in value) return plainValue(value.text ?? value.hyperlink);
    if (value.error) return String(value.error);
    return "";
}

function excelValue(cell) {
    const v = plainValue(cell.value);
    if (typeof v === "object") {
        try { return cell.text ?? ""; } catch { return ""; }
    }
    return v;
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
        const rowInfo = {};

        const MIN_ROW_HEIGHT = 24;
        const MAX_ROW_HEIGHT = 120;
        const LINE_HEIGHT = 18;
        const VERTICAL_PADDING = 8;

        let maxLines = 1;

        for (let colIndex = 1; colIndex <= columnCount; colIndex++) {
            const cell = row.getCell(colIndex);
            const value = excelValue(cell);
            const text = String(value ?? "");

            if (!text) continue;

            const columnWidth = worksheet.getColumn(colIndex).width || 12;
            const estimatedCharsPerLine = Math.max(
                1,
                Math.floor(columnWidth * 1.1)
            );

            const lines = text.split(/\r\n|\r|\n/).reduce(
                (total, line) =>
                    total + Math.max(
                        1,
                        Math.ceil(line.length / estimatedCharsPerLine)
                    ),
                0
            );

            maxLines = Math.max(maxLines, lines);
        }

        const calculatedHeight = Math.min(
            MAX_ROW_HEIGHT,
            Math.max(
                MIN_ROW_HEIGHT,
                maxLines * LINE_HEIGHT + VERTICAL_PADDING
            )
        );

        rowInfo.h = Math.max(
            calculatedHeight,
            row.height ? row.height * 96 / 72 : 0
        );

        if (row.hidden) rowInfo.hd = 1;
        if (Object.keys(rowInfo).length) rowData[rowIndex - 1] = rowInfo;
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
            const url = excelLink(cell);
            const raw = cell.value;
            const richParts = raw && Array.isArray(raw.richText) ? raw.richText : null;
            const text = typeof value === "string" ? value : "";
            const multiline = /[\r\n]/.test(text);

            const entry = {};
            let style = excelCellStyle(cell);

            if (text !== "" && (url || richParts || multiline)) {
                entry.p = buildRichDoc(richParts || [{ text }], url);
                if (multiline) style = { ...(style || {}), tb: 3 };   // keep wrapping on
            } else {
                entry.v = value;
            }
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
            workbook_data: packWorkbook(data),
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
        const data = unpackWorkbook(response.data?.workbook_data ?? response.data?.data?.workbook_data); a
        if (!data) throw new Error("The server did not return workbook data.");
        replaceWorkbook(data);
        const { headers, rows } = extractHeadersRows(data);
        await weeklyCalendarStore.saveSheet(headers, rows);
        weeklyCalendarStore.headers = headers;
        weeklyCalendarStore.rows = rows;
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: packWorkbook(data) });
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
        const targetSheet = excelWorkbook.worksheets.find(ws => ws.name.trim().toLowerCase() === "weekly calendar")
            || excelWorkbook.worksheets[0];
        if (!targetSheet) throw new Error("No worksheet was found in this Excel file.");
        if (targetSheet.name.trim().toLowerCase() !== "weekly calendar") {
            const useFirst = window.confirm(`This file has no “Weekly Calendar” worksheet. Import the first worksheet, “${targetSheet.name}”, instead?`);
            if (!useFirst) return;
        }
        const data = convertExcelWorksheet(targetSheet, file.name);
        replaceWorkbook(data);
        const { headers, rows } = extractHeadersRows(data);
        await weeklyCalendarStore.saveSheet(headers, rows);
        weeklyCalendarStore.headers = headers;
        weeklyCalendarStore.rows = rows;
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: packWorkbook(data) });
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        setSaveStatus("Excel imported", "text-green-600");
        await loadSnapshotList();
        notify("success", `Imported “${targetSheet.name}” (${targetSheet.rowCount} rows × ${targetSheet.columnCount} columns).`);
    } catch (error) {
        console.error("[weekly-calendar-sheet] Excel import failed:", error);
        setSaveStatus("Excel import failed", "text-red-500");
        notify("error", error.response?.data?.message || error.message || "Could not import this Excel file.");
    } finally {
        event.target.value = "";
    }
}

function excelLink(cell) {
    const v = cell.value;
    if (v && typeof v === "object") {
        if (v.hyperlink) return v.hyperlink;
        const formula = v.formula || v.sharedFormula;
        const m = typeof formula === "string" && formula.match(/^\s*HYPERLINK\(\s*"([^"]+)"/i);
        if (m) return m[1];
    }
    return null;
}

function runStyle(font = {}) {
    const ts = {};
    if (font.bold) ts.bl = 1;
    if (font.italic) ts.it = 1;
    if (font.underline) ts.ul = { s: 1 };
    if (font.strike) ts.st = { s: 1 };
    if (font.size) ts.fs = font.size;
    if (font.name) ts.ff = font.name;
    if (font.color) ts.cl = excelColor(font.color);
    return ts;
}

// Builds a Univer rich-text doc: "\n" becomes a paragraph break ("\r"), runs keep per-part formatting.
function buildRichDoc(parts, url = null) {
    const norm = parts.map(p => ({ ...p, text: String(p.text ?? "").replace(/\r\n?/g, "\n") }));
    const text = norm.map(p => p.text).join("");

    const textRuns = [];
    let offset = 0;
    norm.forEach(p => {
        const ts = p.font ? runStyle(p.font) : {};
        if (p.text.length && Object.keys(ts).length) {
            textRuns.push({ st: offset, ed: offset + p.text.length, ts });
        }
        offset += p.text.length;
    });

    const paragraphs = [];
    for (let i = 0; i < text.length; i++) if (text[i] === "\n") paragraphs.push({ startIndex: i });
    paragraphs.push({ startIndex: text.length });

    const body = {
        dataStream: `${text.replace(/\n/g, "\r")}\r\n`,
        paragraphs,
    };
    if (textRuns.length) body.textRuns = textRuns;
    if (url && text.length) {
        body.customRanges = [{
            startIndex: 0,
            endIndex: text.length - 1,
            rangeId: `link-${Math.random().toString(36).slice(2, 10)}`,
            rangeType: 0,
            properties: { url },
        }];
    }
    return { id: "d", documentStyle: {}, body };
}

function searchRows() {
    const query = quickFilter.value.trim().toLowerCase();
    const headers = weeklyCalendarStore.headers || [];
    const rows = weeklyCalendarStore.rows || [];
    visibleCount.value = !query ? rows.length : rows.filter(row => headers.some(header => String(row[header] ?? "").toLowerCase().includes(query))).length;
}
function clearSearch() {
    quickFilter.value = "";
    visibleCount.value = totalCount.value;
}
function addRow() {
    if (!sheet) return;
    const headers = weeklyCalendarStore.headers || [];
    const nextRow = Math.max(sheet.getMaxRows?.() || 1, (weeklyCalendarStore.rows || []).length + 1);
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
                await weeklyCalendarStore.saveSheet(headers, rows);
                weeklyCalendarStore.headers = headers;
                weeklyCalendarStore.rows = rows;
                await axios.put(LAYOUT_ENDPOINT, { workbook_data: packWorkbook(data) });
                totalCount.value = rows.length;
                visibleCount.value = rows.length;
                setSaveStatus("CSV imported", "text-green-600");
                notify("success", `Imported ${rows.length} CSV rows.`);
            } catch (error) {
                console.error("[weekly-calendar] CSV import failed:", error);
                notify("error", error.response?.data?.message || error.message || "CSV import failed.");
            } finally { event.target.value = ""; }
        },
        error: error => { notify("error", error.message || "Could not parse CSV."); event.target.value = ""; },
    });
}
async function pullNow() {
    syncing.value = true;
    try {
        await weeklyCalendarStore.pullFromSheet();
        const data = currentWorkbookData();
        const ws = getPrimarySheetData(data);
        const headers = weeklyCalendarStore.headers || [];
        const rows = weeklyCalendarStore.rows || [];
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
        await axios.put(LAYOUT_ENDPOINT, { workbook_data: packWorkbook(data) });
        totalCount.value = rows.length;
        visibleCount.value = rows.length;
        setSaveStatus("Pulled data; retained local layout", "text-green-600");
        notify("success", "Pulled Google Sheets data while retaining the locally stored layout.");
    } catch (error) {
        console.error("[weekly-calendar] Pull failed:", error);
        setSaveStatus("Pull failed", "text-red-500");
        notify("error", error.response?.data?.message || "Pull failed.");
    } finally { syncing.value = false; }
}
async function pushNow() {
    pushing.value = true;
    try {
        await persistWorkbook();
        await weeklyCalendarStore.pushToSheet();
        setSaveStatus("Pushed to Google Sheets", "text-green-600");
        notify("success", "Pushed current table data to Google Sheets.");
    } catch (error) {
        console.error("[weekly-calendar] Push failed:", error);
        setSaveStatus("Push failed", "text-red-500");
        notify("error", error.response?.data?.message || "Push failed.");
    } finally { pushing.value = false; }
}


onMounted(async () => {
    try {
        await weeklyCalendarStore.loadMirror();
    } catch (error) {
        console.warn(
            "[weekly-calendar] Mirror load failed; starting with empty data.",
            error
        );
        weeklyCalendarStore.headers = [];
        weeklyCalendarStore.rows = [];
    }

    // Remove the loading skeleton so the Univer container is rendered.
    loading.value = false;
    await nextTick();

    try {
        const response = await axios.get(LAYOUT_ENDPOINT);
        const savedLayout = unpackWorkbook(
            response.data?.workbook_data ?? response.data?.data?.workbook_data
        );

        const initialData =
            savedLayout ??
            createBasicWorkbookData(
                weeklyCalendarStore.headers || [],
                weeklyCalendarStore.rows || []
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
            weeklyCalendarStore.headers = headers;
            weeklyCalendarStore.rows = rows;
        }

        totalCount.value = (weeklyCalendarStore.rows || []).length;
        visibleCount.value = totalCount.value;

        await loadSnapshotList();
    } catch (error) {
        console.error(
            "[weekly-calendar] Layout load/Univer initialization failed:",
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
