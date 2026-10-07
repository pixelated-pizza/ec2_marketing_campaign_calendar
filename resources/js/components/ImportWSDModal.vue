<template>
    <Dialog v-model:visible="visible" header="Import CSV" :modal="true" class="w-[36rem] text-xs dark:bg-zinc-900 dark:text-zinc-100">
        <div class="flex flex-col gap-3 p-1">
            <div>
                <label class="font-semibold text-xs text-gray-700 dark:text-zinc-300 block mb-1">CSV file</label>
                <input type="file" accept=".csv,text/csv" @change="onFileChange"
                    class="text-xs text-gray-700 dark:text-zinc-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 dark:file:bg-zinc-800 file:text-gray-700 dark:file:text-zinc-200" />
            </div>

            <p v-if="parseError" class="text-xs text-red-500 dark:text-red-400">{{ parseError }}</p>

            <template v-if="parsedRows.length">
                <p class="text-xs text-gray-600 dark:text-zinc-400">
                    {{ parsedRows.length }} row(s) detected across {{ headers.length }} column(s).
                    <span v-if="newHeaderCount" class="text-amber-600 dark:text-amber-400">
                        {{ newHeaderCount }} column(s) don't match the sheet and will be added as new columns.
                    </span>
                </p>

                <div class="overflow-auto max-h-52 border border-gray-200 dark:border-zinc-700 rounded">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th v-for="h in headers" :key="h"
                                    class="px-2 py-1 text-left border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 whitespace-nowrap">
                                    {{ h }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in parsedRows.slice(0, 5)" :key="i">
                                <td v-for="(cell, j) in row" :key="j"
                                    class="px-2 py-1 border-b border-gray-100 dark:border-zinc-800 truncate max-w-[10rem]">
                                    {{ cell }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="parsedRows.length > 5" class="text-gray-400 dark:text-zinc-600 px-2 py-1">
                        …and {{ parsedRows.length - 5 }} more row(s)
                    </p>
                </div>
            </template>

            <div class="mt-2 flex justify-end gap-1.5">
                <Button label="Cancel" size="small" severity="secondary" class="text-xs" @click="close" />
                <Button label="Import" size="small" severity="success" class="text-xs"
                    :loading="importing" :disabled="!parsedRows.length" @click="confirmImport" />
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { ref } from "vue";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import { useWSDStore } from "@/js/stores/wsd_store.js";
import { pruneBlankCells, shouldWrapText } from "@/js/config/wsd_columns.js";

// must match the key used in WSDFortuneSheet.vue
const SHEET_SNAPSHOT_KEY = "website-sale-details";

const wsdStore = useWSDStore();
const emit = defineEmits(["imported"]);

const visible = ref(false);
const importing = ref(false);
const parseError = ref("");
const headers = ref([]);
const parsedRows = ref([]);
const newHeaderCount = ref(0);

function open() {
    visible.value = true;
    importing.value = false;
    parseError.value = "";
    headers.value = [];
    parsedRows.value = [];
    newHeaderCount.value = 0;
}
function close() {
    visible.value = false;
}
defineExpose({ open });

/* --------------------------- minimal CSV parser --------------------------- *
 * Handles quoted fields, embedded commas, escaped quotes ("") and both
 * \n and \r\n line endings, without adding a dependency. */
function parseCSV(text) {
    const rows = [];
    let row = [];
    let field = "";
    let inQuotes = false;

    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const next = text[i + 1];

        if (inQuotes) {
            if (char === '"' && next === '"') {
                field += '"';
                i++;
            } else if (char === '"') {
                inQuotes = false;
            } else if (char === "\r") {
                // skip — normalize CRLF line endings inside quoted
                // multi-line fields the same way we do outside them
            } else {
                field += char;
            }
        } else if (char === '"') {
            inQuotes = true;
        } else if (char === ",") {
            row.push(field);
            field = "";
        } else if (char === "\r") {
            // skip, \n handles the line break
        } else if (char === "\n") {
            row.push(field);
            rows.push(row);
            row = [];
            field = "";
        } else {
            field += char;
        }
    }
    if (field.length || row.length) {
        row.push(field);
        rows.push(row);
    }
    return rows;
}

function onFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    parseError.value = "";
    headers.value = [];
    parsedRows.value = [];

    const reader = new FileReader();
    reader.onload = () => {
        try {
            const rows = parseCSV(String(reader.result)).filter(
                (r) => r.length && r.some((v) => String(v ?? "").trim() !== ""),
            );
            if (!rows.length) {
                parseError.value = "That file doesn't look like it has any rows.";
                return;
            }
            headers.value = rows[0].map((h) => String(h ?? "").trim());
            parsedRows.value = rows.slice(1);
        } catch (err) {
            console.error("[import] CSV parse failed", err);
            parseError.value = "Couldn't parse that file as CSV.";
        }
    };
    reader.onerror = () => {
        parseError.value = "Couldn't read that file.";
    };
    reader.readAsText(file);
}

/* ------------------- merge parsed CSV rows into a sheet object ------------------- *
 * Matches CSV headers to the sheet's existing header row (row 0) by text,
 * case-insensitively. Unmatched headers are appended as brand-new columns
 * so this keeps working even after someone renames/reorders/adds columns. */
function mergeCsvIntoSheet(sheet, csvHeaders, csvRows) {
    // Drop any dense `data` array carried over from the sheet's last save —
    // we're only updating celldata here, and leaving a stale `data` behind
    // lets it shadow these very rows the next time the sheet loads.
    // Also drop dead blanked-out cells (left behind by delete/clear
    // operations) so they can't be mistaken for real content below.
    const { data, ...sheetRest } = sheet;
    const celldata = pruneBlankCells(sheetRest.celldata);

    const existingHeaders = new Map(); // lowercased header text -> column index
    let maxCol = -1;
    for (const cell of celldata) {
        if (cell.r === 0) {
            const text = String(cell.v?.v ?? cell.v?.m ?? "").trim().toLowerCase();
            if (text) existingHeaders.set(text, cell.c);
            maxCol = Math.max(maxCol, cell.c);
        }
    }

    let newCols = 0;
    const colMap = csvHeaders.map((h) => {
        const key = h.trim().toLowerCase();
        if (existingHeaders.has(key)) return existingHeaders.get(key);
        maxCol += 1;
        newCols += 1;
        existingHeaders.set(key, maxCol);
        celldata.push({ r: 0, c: maxCol, v: { v: h, m: h, bl: 1, bg: "#f8f9fa", fc: "#5f6368" } });
        return maxCol;
    });
    newHeaderCount.value = newCols;

    let maxRow = 0;
    for (const cell of celldata) if (cell.r > maxRow) maxRow = cell.r;
    let nextRow = maxRow + 1;

    for (const row of csvRows) {
        if (row.every((v) => !String(v ?? "").trim())) continue; // skip blank rows
        row.forEach((value, i) => {
            const c = colMap[i];
            if (c === undefined) return;
            const text = String(value ?? "");
            const style = shouldWrapText(text) ? { tb: 2 } : {};
            celldata.push({ r: nextRow, c, v: { v: value, m: text, ...style } });
        });
        nextRow += 1;
    }

    return {
        ...sheetRest,
        celldata,
        row: Math.max(sheetRest.row || 0, nextRow + 5),
        column: Math.max(sheetRest.column || 0, maxCol + 1 + 5),
    };
}

async function confirmImport() {
    importing.value = true;
    try {
        const rawSnapshot = await wsdStore.loadSheetSnapshot(SHEET_SNAPSHOT_KEY);
        const sheets =
            Array.isArray(rawSnapshot) && rawSnapshot.length
                ? rawSnapshot
                : [{ name: "Website Sale Details", celldata: [], row: 1, column: headers.value.length }];

        const merged = [...sheets];
        merged[0] = mergeCsvIntoSheet(merged[0], headers.value, parsedRows.value);

        await wsdStore.saveSheetSnapshot(SHEET_SNAPSHOT_KEY, merged);

        emit("imported", { details_saved: parsedRows.value.length, skipped: 0 });
        close();
    } catch (err) {
        console.error("[import] failed to save merged snapshot", err);
        parseError.value = "Import failed while saving — check the console.";
    } finally {
        importing.value = false;
    }
}
</script>