import { ref, computed, nextTick, onMounted, onBeforeUnmount, getCurrentInstance } from "vue";
import { useUIStore } from "@/js/stores/ui.js";

import { createUniver, LocaleType, mergeLocales } from "@univerjs/presets";
import { UniverSheetsCorePreset } from "@univerjs/preset-sheets-core";
import sheetsCoreEnUS from "@univerjs/preset-sheets-core/locales/en-US";
import "@univerjs/preset-sheets-core/lib/index.css";

import {
    fetchXlsxImports,
    fetchXlsxImport,
    uploadXlsx,
    previewXlsxSheet,
    importXlsx,
    updateXlsxImport,
    deleteXlsxImport,
    addXlsxRow,
    bulkUpdateXlsxRows,
    bulkDeleteXlsxRows,
    syncXlsxRows,
} from "@/js/api/xlsx_import_api";
import { buildXlsxWorkbook, HEADER_ROWS, SPARE_ROWS } from "./xlsxSheetData.js";

const FLUSH_DELAY = 700; // ms of quiet before edits are saved
const UPDATE_CHUNK = 500;
const STRUCTURE_COMMANDS =
    /^sheet\.command\.(remove-row|insert-row|insert-multi-rows|move-rows)|^univer\.command\.(undo|redo)$/;

export function useXlsxUniver() {
    const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;
    const ui = useUIStore();

    // ----- state -----------------------------------------------------------
    const loading = ref(true);
    const sheetLoading = ref(false);
    const univerContainer = ref(null);

    const imports = ref([]);
    const activeId = ref(null);
    const meta = ref(null);
    const saveState = ref("idle"); // idle | saving | saved | error

    const activeImport = computed(() => imports.value.find((i) => i.id === activeId.value) || null);

    // ----- non-reactive bookkeeping ------------------------------------------
    let univer = null;
    let univerAPI = null;
    let darkObserver = null;
    let flushTimer = null;
    let suppress = 0; // > 0 while we write to the sheet ourselves
    let pending = 0; // queued save jobs
    let queue = Promise.resolve(); // saves run one after another
    let knownIds = new Set(); // row ids the server has
    let headerCount = 0;
    const dirtyRows = new Set();
    let headerDirty = false;
    let structureDirty = false;

    const isDarkMode = () => document.documentElement.classList.contains("app-dark");
    const getSheet = () => univerAPI?.getActiveWorkbook?.()?.getActiveSheet?.() ?? null;
    const rowId = (sheet, r) => sheet.getRowCustomMetadata(r)?.rid ?? null;
    const toStored = (v) => (v === undefined || v === null || v === "" ? null : v);

    function silently(fn) {
        suppress++;
        try {
            return fn();
        } finally {
            setTimeout(() => (suppress = Math.max(0, suppress - 1)), 60);
        }
    }

    function readRows(sheet, first, count, cols) {
        if (count <= 0 || cols <= 0) return [];
        return sheet
            .getRange(first, 0, count, cols)
            .getValues()
            .map((line) => line.map(toStored));
    }

    // ----- save queue --------------------------------------------------------
    function enqueue(job) {
        pending++;
        saveState.value = "saving";
        queue = queue
            .then(job)
            .then(() => {
                pending--;
                if (pending === 0) saveState.value = "saved";
            })
            .catch((err) => {
                pending--;
                saveState.value = "error";
                console.error("Save failed:", err);
                toastr.error(err?.response?.data?.message || "Could not save your changes.");
            });
        return queue;
    }

    function scheduleFlush() {
        clearTimeout(flushTimer);
        flushTimer = setTimeout(flushNow, FLUSH_DELAY);
    }

    /** Send everything that changed since the last save. */
    function flushNow() {
        clearTimeout(flushTimer);
        flushTimer = null;

        const id = activeId.value;
        const sheet = getSheet();
        if (!id || !sheet) {
            dirtyRows.clear();
            headerDirty = false;
            structureDirty = false;
            return queue;
        }
        const cols = headerCount;

        if (headerDirty) {
            headerDirty = false;
            const labels = (readRows(sheet, 0, 1, cols)[0] || []).map((v) => (v == null ? "" : String(v)));
            enqueue(async () => {
                const updated = await updateXlsxImport(id, { headers: labels });
                if (id !== activeId.value) return;
                meta.value = { ...meta.value, headers: updated.headers };
                // The server tidies blank / duplicate headers; show what it stored
                const sh = getSheet();
                if (sh && updated.headers.some((h, i) => h !== labels[i])) {
                    silently(() => sh.getRange(0, 0, 1, cols).setValues([updated.headers]));
                }
            });
        }

        const rows = [...dirtyRows].filter((r) => r >= HEADER_ROWS).sort((a, b) => a - b);
        const dirtySnapshot = new Set(rows);
        dirtyRows.clear();

        const needsSync = structureDirty || rows.some((r) => !rowId(sheet, r) || !knownIds.has(rowId(sheet, r)));
        structureDirty = false;

        if (needsSync) return runSync(id, sheet, cols, dirtySnapshot);

        if (rows.length) {
            const updates = rows.map((r) => ({ id: rowId(sheet, r), data: readRows(sheet, r, 1, cols)[0] }));
            for (let i = 0; i < updates.length; i += UPDATE_CHUNK) {
                const chunk = updates.slice(i, i + UPDATE_CHUNK);
                enqueue(() => bulkUpdateXlsxRows(id, chunk));
            }
        }
        return queue;
    }

    /**
     * Structural change (rows inserted / deleted / moved, or a row typed into the
     * empty area): send the row order and let the backend create / delete / re-order.
     */
    function runSync(id, sheet, cols, dirty = new Set()) {
        const maxRows = sheet.getMaxRows();
        const body = readRows(sheet, HEADER_ROWS, maxRows - HEADER_ROWS, cols);

        const ids = body.map((_, i) => rowId(sheet, i + HEADER_ROWS));
        let last = -1;
        body.forEach((line, i) => {
            if (ids[i] || line.some((v) => v !== null)) last = i;
        });

        const seen = new Set();
        const payload = [];
        for (let i = 0; i <= last; i++) {
            let rid = ids[i];
            // unknown id (undo of a delete) or duplicated id (a row copied by "insert row") = new row
            if (rid && (!knownIds.has(rid) || seen.has(rid))) rid = null;
            if (rid) seen.add(rid);
            payload.push(rid && !dirty.has(i + HEADER_ROWS) ? { id: rid } : { id: rid, data: body[i] });
        }

        return enqueue(async () => {
            const { ids: saved } = await syncXlsxRows(id, payload);
            if (id !== activeId.value) return;

            const sh = getSheet();
            if (sh) {
                silently(() => {
                    saved.forEach((newId, i) => {
                        if (ids[i] !== newId) sh.setRowCustomMetadata(i + HEADER_ROWS, { rid: newId });
                    });
                });
            }
            knownIds = new Set(saved);
            bumpRowCount(saved.length);
        });
    }

    function bumpRowCount(n) {
        if (meta.value) meta.value = { ...meta.value, row_count: n };
        const item = activeImport.value;
        if (item) item.row_count = n;
    }

    // ----- Univer lifecycle ----------------------------------------------------
    function destroyUniver() {
        const instance = univer;
        univer = null;
        univerAPI = null;
        if (instance) {
            try {
                instance.dispose();
            } catch (e) {
                console.warn("Failed to dispose Univer:", e);
            }
        }
    }

    function registerSheetEvents() {
        const E = univerAPI.Event;

        // Cell edits, pastes, clears (and undo/redo of them)
        univerAPI.addEvent(E.SheetValueChanged, (params) => {
            if (suppress > 0) return;
            for (const range of params.effectedRanges || []) {
                for (let r = range.getRow(); r <= range.getLastRow(); r++) {
                    if (r === 0) headerDirty = true;
                    else dirtyRows.add(r);
                }
            }
            scheduleFlush();
        });

        // Row insert / delete / move, undo / redo
        univerAPI.addEvent(E.CommandExecuted, (e) => {
            if (suppress > 0 || !STRUCTURE_COMMANDS.test(e.id)) return;
            structureDirty = true;
            scheduleFlush();
        });
    }

    function renderSheet(rows) {
        if (!univerContainer.value) return;
        destroyUniver();

        const created = createUniver({
            locale: LocaleType.EN_US,
            locales: { [LocaleType.EN_US]: mergeLocales(sheetsCoreEnUS) },
            presets: [
                UniverSheetsCorePreset({
                    container: univerContainer.value,
                    header: true,
                    toolbar: true,
                    formulaBar: true,
                    footer: false,
                    contextMenu: true,
                }),
            ],
        });

        univer = created.univer;
        univerAPI = created.univerAPI;
        univerAPI.toggleDarkMode?.(isDarkMode());

        univerAPI.createWorkbook(buildXlsxWorkbook(meta.value, rows));
        registerSheetEvents();
    }

    // ----- loading data --------------------------------------------------------
    async function loadList() {
        imports.value = await fetchXlsxImports();
    }

    async function selectImport(id) {
        if (id === activeId.value && univer) return;
        await flushNow();

        sheetLoading.value = true;
        try {
            const data = await fetchXlsxImport(id);
            activeId.value = id;
            meta.value = data.import;
            headerCount = data.import.headers.length;
            knownIds = new Set(data.rows.map((r) => r.id));
            dirtyRows.clear();
            headerDirty = false;
            structureDirty = false;
            saveState.value = "idle";

            await nextTick();
            renderSheet(data.rows);
        } catch (err) {
            console.error("Failed to load import:", err);
            toastr.error("Failed to load that import.");
        } finally {
            sheetLoading.value = false;
        }
    }

    function clearSheet() {
        activeId.value = null;
        meta.value = null;
        knownIds = new Set();
        destroyUniver();
    }

    // ----- add / delete rows -----------------------------------------------------
    const showDeleteRowsDialog = ref(false);
    const rowsToDelete = ref({ rows: [], ids: [] });

    function lastDataRow(sheet) {
        for (let r = sheet.getMaxRows() - 1; r >= HEADER_ROWS; r--) {
            if (rowId(sheet, r)) return r;
        }
        return 0;
    }

    function ensureRows(sheet, needed) {
        const max = sheet.getMaxRows();
        if (needed >= max) silently(() => sheet.insertRowsAfter(max - 1, needed - max + SPARE_ROWS));
    }

    async function addRow() {
        const id = activeId.value;
        const sheet = getSheet();
        if (!id || !sheet) return;

        await flushNow();
        ui.showLoader();
        try {
            const row = await addXlsxRow(id);
            const target = lastDataRow(sheet) + 1;
            ensureRows(sheet, target + 1);
            silently(() => sheet.setRowCustomMetadata(target, { rid: row.id }));
            knownIds.add(row.id);
            bumpRowCount(knownIds.size);
            sheet.getRange(target, 0).activate();
        } catch (err) {
            console.error("Failed to add row:", err);
            toastr.error("Failed to add a row.");
        } finally {
            ui.hideLoader();
        }
    }

    function selectedDataRows(sheet) {
        const selection = sheet.getSelection?.();
        const ranges = selection?.getActiveRangeList?.() || [selection?.getActiveRange?.()].filter(Boolean);

        const rows = new Set();
        for (const range of ranges) {
            for (let r = range.getRow(); r <= range.getLastRow(); r++) {
                if (r >= HEADER_ROWS && rowId(sheet, r)) rows.add(r);
            }
        }
        return [...rows].sort((a, b) => a - b);
    }

    function askDeleteSelectedRows() {
        const sheet = getSheet();
        if (!sheet) return;

        const rows = selectedDataRows(sheet);
        if (!rows.length) {
            toastr.info("Select one or more data rows first (click a row number or any cell in the row).");
            return;
        }
        rowsToDelete.value = { rows, ids: rows.map((r) => rowId(sheet, r)) };
        showDeleteRowsDialog.value = true;
    }

    async function confirmDeleteRows() {
        const id = activeId.value;
        const sheet = getSheet();
        const { rows, ids } = rowsToDelete.value;
        showDeleteRowsDialog.value = false;
        if (!id || !sheet || !ids.length) return;

        await flushNow();
        ui.showLoader();
        try {
            await bulkDeleteXlsxRows(id, ids);

            // Remove from the sheet bottom-up so earlier positions stay valid
            const groups = [];
            rows.forEach((r) => {
                const g = groups[groups.length - 1];
                if (g && g.start + g.count === r) g.count++;
                else groups.push({ start: r, count: 1 });
            });
            silently(() => {
                for (let i = groups.length - 1; i >= 0; i--) sheet.deleteRows(groups[i].start, groups[i].count);
            });

            ids.forEach((x) => knownIds.delete(x));
            bumpRowCount(knownIds.size);
            toastr.success(ids.length === 1 ? "Row deleted." : `${ids.length} rows deleted.`);
        } catch (err) {
            console.error("Failed to delete rows:", err);
            toastr.error("Failed to delete the selected rows.");
        } finally {
            ui.hideLoader();
        }
    }

    // ----- rename / delete import --------------------------------------------------
    const showRenameDialog = ref(false);
    const renameValue = ref("");
    const showDeleteImportDialog = ref(false);

    function openRename() {
        if (!meta.value) return;
        renameValue.value = meta.value.name;
        showRenameDialog.value = true;
    }

    async function saveRename() {
        const name = renameValue.value.trim();
        if (!name || !activeId.value) return;

        ui.showLoader();
        try {
            const updated = await updateXlsxImport(activeId.value, { name });
            meta.value = { ...meta.value, name: updated.name };
            const item = activeImport.value;
            if (item) item.name = updated.name;
            showRenameDialog.value = false;
            toastr.success("Renamed.");
        } catch (err) {
            console.error("Rename failed:", err);
            toastr.error("Failed to rename.");
        } finally {
            ui.hideLoader();
        }
    }

    async function deleteImportConfirmed() {
        const id = activeId.value;
        showDeleteImportDialog.value = false;
        if (!id) return;

        ui.showLoader();
        try {
            dirtyRows.clear();
            headerDirty = false;
            structureDirty = false;
            await deleteXlsxImport(id);
            imports.value = imports.value.filter((i) => i.id !== id);
            clearSheet();
            toastr.success("Import deleted.");
            if (imports.value.length) await selectImport(imports.value[0].id);
        } catch (err) {
            console.error("Delete failed:", err);
            toastr.error("Failed to delete the import.");
        } finally {
            ui.hideLoader();
        }
    }

    // ----- import wizard -------------------------------------------------------------
    const showImportDialog = ref(false);
    const uploading = ref(false);
    const importing = ref(false);
    const previewLoading = ref(false);
    const upload = ref(null); // { token, filename, sheets }
    const selectedSheet = ref(null);
    const headerRow = ref(1);
    const preview = ref({ columns: 0, rows: [] });
    const importName = ref("");
    const fillMerged = ref(false);
    const importError = ref("");

    const MAX_IMPORT_COLS = 200; // keep in sync with XlsxImportService::MAX_COLS
    const selectedSheetInfo = computed(
        () => upload.value?.sheets.find((s) => s.name === selectedSheet.value) || null,
    );
    const tooWide = computed(() => (selectedSheetInfo.value?.columns || 0) > MAX_IMPORT_COLS);

    const PREVIEW_MAX_COLS = 30;
    const previewColumns = computed(() => Math.min(preview.value.columns, PREVIEW_MAX_COLS));

    function resetWizard() {
        upload.value = null;
        selectedSheet.value = null;
        headerRow.value = 1;
        preview.value = { columns: 0, rows: [] };
        importName.value = "";
        fillMerged.value = false;
        importError.value = "";
    }

    function openImportDialog() {
        resetWizard();
        showImportDialog.value = true;
    }

    function closeImportDialog() {
        showImportDialog.value = false;
    }

    async function onFilePicked(event) {
        const file = event.target.files?.[0];
        event.target.value = ""; // allow picking the same file again
        if (!file) return;

        importError.value = "";
        if (!/\.(xlsx|xlsm)$/i.test(file.name)) {
            importError.value = "Please choose an .xlsx file.";
            return;
        }

        uploading.value = true;
        try {
            upload.value = await uploadXlsx(file);
            selectedSheet.value = upload.value.sheets[0]?.name ?? null;
            await onSheetChanged();
        } catch (err) {
            upload.value = null;
            importError.value = err?.response?.data?.message || "Could not read that file.";
        } finally {
            uploading.value = false;
        }
    }

    async function onSheetChanged() {
        if (!upload.value || !selectedSheet.value) return;

        headerRow.value = 1;
        const base = upload.value.filename.replace(/\.(xlsx|xlsm)$/i, "");
        importName.value = `${base} - ${selectedSheet.value.trim()}`;

        previewLoading.value = true;
        importError.value = "";
        try {
            preview.value = await previewXlsxSheet(upload.value.token, selectedSheet.value);
        } catch (err) {
            preview.value = { columns: 0, rows: [] };
            importError.value = err?.response?.data?.message || "Could not read that sheet.";
        } finally {
            previewLoading.value = false;
        }
    }

    function previewCell(v) {
        if (v === null || v === undefined || v === "") return "";
        const s = String(v).replace(/\s+/g, " ");
        return s.length > 40 ? s.slice(0, 40) + "…" : s;
    }

    async function runImport() {
        if (!upload.value || !selectedSheet.value || !importName.value.trim()) return;

        importing.value = true;
        importError.value = "";
        try {
            const created = await importXlsx({
                token: upload.value.token,
                sheet: selectedSheet.value,
                header_row: headerRow.value,
                name: importName.value.trim(),
                fill_merged: fillMerged.value,
            });
            await loadList();
            showImportDialog.value = false;
            toastr.success(`Imported ${created.row_count} rows.`);
            await selectImport(created.id);
        } catch (err) {
            console.error("Import failed:", err);
            importError.value = err?.response?.data?.message || "The import failed.";
        } finally {
            importing.value = false;
        }
    }

    // ----- init ------------------------------------------------------------------------
    const onBeforeUnload = (e) => {
        if (pending > 0 || dirtyRows.size || headerDirty || structureDirty) {
            e.preventDefault();
            e.returnValue = "";
        }
    };

    onMounted(async () => {
        loading.value = true;
        try {
            await loadList();
            if (imports.value.length) await selectImport(imports.value[0].id);
        } catch (err) {
            console.error("Failed to load imports:", err);
            toastr.error("Failed to load imports.");
        } finally {
            loading.value = false;
        }

        darkObserver = new MutationObserver(() => univerAPI?.toggleDarkMode?.(isDarkMode()));
        darkObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
        window.addEventListener("beforeunload", onBeforeUnload);
    });

    onBeforeUnmount(() => {
        window.removeEventListener("beforeunload", onBeforeUnload);
        darkObserver?.disconnect();
        flushNow(); // save anything still waiting, then tear down
        queue.finally(destroyUniver);
    });

    return {
        loading,
        sheetLoading,
        univerContainer,
        imports,
        activeId,
        meta,
        saveState,
        selectImport,
        // rows
        addRow,
        askDeleteSelectedRows,
        confirmDeleteRows,
        showDeleteRowsDialog,
        rowsToDelete,
        // rename / delete
        showRenameDialog,
        renameValue,
        openRename,
        saveRename,
        showDeleteImportDialog,
        deleteImportConfirmed,
        // wizard
        showImportDialog,
        uploading,
        importing,
        previewLoading,
        upload,
        selectedSheet,
        selectedSheetInfo,
        headerRow,
        preview,
        previewColumns,
        tooWide,
        importName,
        fillMerged,
        importError,
        openImportDialog,
        closeImportDialog,
        onFilePicked,
        onSheetChanged,
        previewCell,
        runImport,
    };
}
