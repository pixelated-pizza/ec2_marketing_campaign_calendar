<template>
    <div class="flex flex-col bg-gray-100 dark:bg-gray-900">
        <!-- Header / toolbar -->
        <div class="bg-white dark:bg-gray-800 mb-5 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm text-gray-900 dark:text-white text-left tracking-wide drop-shadow-sm py-3 p-4">
                Excel Imports
            </h4>

            <div class="flex flex-wrap items-center gap-2 ml-5 pb-3 pr-5">
                <Select :modelValue="activeId" :options="imports" optionLabel="name" optionValue="id"
                    placeholder="No imports yet" class="w-80" :disabled="!imports.length"
                    @update:modelValue="selectImport">
                    <template #option="{ option }">
                        <div class="flex flex-col">
                            <span>{{ option.name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ option.row_count }} rows<span v-if="option.original_filename"> · {{
                                    option.original_filename }}</span>
                            </span>
                        </div>
                    </template>
                </Select>

                <Button label="Import xlsx" icon="pi pi-upload" severity="success" @click="openImportDialog" />
                <Button label="Add Row" icon="pi pi-plus" severity="secondary" :disabled="!meta" @click="addRow" />
                <Button label="Delete Selected Rows" icon="pi pi-trash" severity="danger" outlined :disabled="!meta"
                    @click="askDeleteSelectedRows" />
                <Button label="Rename" icon="pi pi-pencil" severity="secondary" text :disabled="!meta"
                    @click="openRename" />
                <Button label="Delete Import" icon="pi pi-times" severity="danger" text :disabled="!meta"
                    @click="showDeleteImportDialog = true" />

                <span v-if="meta" class="ml-auto flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ meta.row_count }} rows · {{ meta.headers.length }} columns</span>
                    <span v-if="saveState === 'saving'" class="flex items-center gap-1">
                        <i class="pi pi-spin pi-spinner"></i> Saving…
                    </span>
                    <span v-else-if="saveState === 'saved'" class="flex items-center gap-1 text-green-600">
                        <i class="pi pi-check"></i> Saved
                    </span>
                    <span v-else-if="saveState === 'error'" class="flex items-center gap-1 text-red-500">
                        <i class="pi pi-exclamation-triangle"></i> Not saved
                    </span>
                </span>
            </div>
        </div>

        <div class="relative w-full bg-white dark:bg-gray-900">
            <!-- Univer needs a container with an explicit height -->
            <div ref="univerContainer" class="xlsx-univer-container w-full h-[calc(100vh-250px)] min-h-[500px]"></div>

            <!-- Empty state -->
            <div v-if="!loading && !meta && !sheetLoading"
                class="absolute inset-0 bg-white dark:bg-gray-900 flex flex-col gap-3 justify-center items-center z-10">
                <i class="pi pi-file-excel text-5xl text-gray-400"></i>
                <p class="text-gray-600 dark:text-gray-300">Import an Excel file to view and edit its data here.</p>
                <Button label="Import xlsx" icon="pi pi-upload" severity="success" @click="openImportDialog" />
            </div>

            <!-- Loading -->
            <div v-show="loading || sheetLoading"
                class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex flex-col gap-4 justify-center items-center z-20">
                <p class="text-gray-600 dark:text-gray-300 text-lg">Loading data...</p>
                <Skeleton height="2rem" width="70%" />
                <Skeleton height="2rem" width="50%" />
                <Skeleton height="1rem" width="90%" />
                <Skeleton height="1rem" width="85%" />
            </div>
        </div>

        <!-- Import wizard -->
        <Dialog v-model:visible="showImportDialog" modal header="Import Excel file"
            :style="{ width: '60rem' }" :breakpoints="{ '1100px': '95vw' }" :closable="!importing">
            <div class="flex flex-col gap-4">
                <!-- 1. file -->
                <div class="flex flex-wrap items-center gap-3">
                    <input ref="fileInput" type="file" accept=".xlsx,.xlsm" class="hidden" @change="onFilePicked" />
                    <Button :label="upload ? 'Choose a different file' : 'Choose .xlsx file'" icon="pi pi-file-excel"
                        severity="secondary" :loading="uploading" :disabled="importing" @click="fileInput.click()" />
                    <span v-if="upload" class="text-sm text-gray-600 dark:text-gray-300">{{ upload.filename }}</span>
                    <span v-else-if="uploading" class="text-sm text-gray-500">Reading workbook…</span>
                </div>

                <Message v-if="importError" severity="error" size="small">{{ importError }}</Message>

                <template v-if="upload">
                    <!-- 2. sheet + name -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 mb-1">Sheet</label>
                            <Select v-model="selectedSheet" :options="upload.sheets" optionLabel="name"
                                optionValue="name" class="w-full" filter :disabled="importing"
                                @update:modelValue="onSheetChanged">
                                <template #option="{ option }">
                                    <span>{{ option.name }}</span>
                                    <span class="text-xs text-gray-500 ml-2">{{ option.rows }} rows</span>
                                </template>
                            </Select>
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 mb-1">Import name</label>
                            <InputText v-model="importName" class="w-full" :disabled="importing" />
                        </div>
                    </div>

                    <Message v-if="tooWide" severity="warn" size="small">
                        This sheet is {{ selectedSheetInfo.columns }} columns wide. Only the first 200 columns are
                        imported. Grid-style sheets (like a calendar) fit better as a table-shaped sheet.
                    </Message>

                    <!-- 3. header row -->
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <label class="text-gray-700 dark:text-gray-300">Header row</label>
                            <InputNumber v-model="headerRow" :min="1" :max="Math.max(preview.rows.length, 1)"
                                showButtons buttonLayout="horizontal" inputClass="w-16 text-center"
                                :disabled="importing" />
                            <span class="text-xs text-gray-500">
                                Click a row below to use it as the header. Rows above it are skipped.
                            </span>
                        </div>

                        <div class="relative border border-gray-200 dark:border-gray-700 rounded overflow-auto max-h-80">
                            <div v-if="previewLoading" class="p-6 text-center text-gray-500">
                                <i class="pi pi-spin pi-spinner mr-2"></i>Loading preview…
                            </div>
                            <table v-else-if="preview.rows.length" class="text-xs border-collapse">
                                <tbody>
                                    <tr v-for="(row, ri) in preview.rows" :key="ri"
                                        class="cursor-pointer"
                                        :class="[
                                            ri + 1 === headerRow
                                                ? 'bg-emerald-100 dark:bg-emerald-900/40 font-semibold'
                                                : ri + 1 < headerRow
                                                    ? 'opacity-40'
                                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                                        ]" @click="headerRow = ri + 1">
                                        <td class="sticky left-0 px-2 py-1 text-right text-gray-500 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                                            {{ ri + 1 }}
                                        </td>
                                        <td v-for="ci in previewColumns" :key="ci"
                                            class="px-2 py-1 border-b border-gray-100 dark:border-gray-800 whitespace-nowrap max-w-[16rem] overflow-hidden text-ellipsis">
                                            {{ previewCell(row[ci - 1]) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-else class="p-6 text-center text-gray-500">This sheet has no data.</div>
                        </div>
                        <p v-if="preview.columns > previewColumns" class="text-xs text-gray-500 mt-1">
                            Showing the first {{ previewColumns }} of {{ preview.columns }} columns. All columns are imported.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model="fillMerged" inputId="fillMerged" binary :disabled="importing" />
                        <label for="fillMerged" class="text-sm text-gray-700 dark:text-gray-300">
                            Repeat the value of merged cells in every row they span
                        </label>
                    </div>
                </template>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" :disabled="importing" @click="closeImportDialog" />
                <Button :label="importing ? 'Importing...' : 'Import'" icon="pi pi-check" severity="success"
                    :loading="importing"
                    :disabled="importing || !upload || !selectedSheet || !importName.trim() || !preview.rows.length"
                    @click="runImport" />
            </template>
        </Dialog>

        <!-- Rename -->
        <Dialog v-model:visible="showRenameDialog" modal header="Rename import" :style="{ width: '26rem' }">
            <InputText v-model="renameValue" class="w-full" @keyup.enter="saveRename" />
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showRenameDialog = false" />
                <Button label="Save" icon="pi pi-check" severity="success" :disabled="!renameValue.trim()"
                    @click="saveRename" />
            </template>
        </Dialog>

        <!-- Delete rows -->
        <Dialog v-model:visible="showDeleteRowsDialog" header="Delete rows" modal :closable="false"
            :style="{ width: '400px' }">
            <p>
                Delete {{ rowsToDelete.ids.length }} selected
                {{ rowsToDelete.ids.length === 1 ? 'row' : 'rows' }}? This cannot be undone.
            </p>
            <template #footer>
                <Button label="No" severity="secondary" text @click="showDeleteRowsDialog = false" />
                <Button label="Yes, delete" severity="danger" @click="confirmDeleteRows" />
            </template>
        </Dialog>

        <!-- Delete import -->
        <Dialog v-model:visible="showDeleteImportDialog" header="Delete import" modal :closable="false"
            :style="{ width: '420px' }">
            <p>
                Delete the import "{{ meta?.name }}" and all {{ meta?.row_count }} of its rows? This cannot be undone.
            </p>
            <template #footer>
                <Button label="No" severity="secondary" text @click="showDeleteImportDialog = false" />
                <Button label="Yes, delete" severity="danger" @click="deleteImportConfirmed" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref } from "vue";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Message from "primevue/message";
import Skeleton from "primevue/skeleton";
import { useXlsxUniver } from "@/js/composables/useXlsxUniver.js";

const fileInput = ref(null);

const {
    loading,
    sheetLoading,
    univerContainer,
    imports,
    activeId,
    meta,
    saveState,
    selectImport,
    addRow,
    askDeleteSelectedRows,
    confirmDeleteRows,
    showDeleteRowsDialog,
    rowsToDelete,
    showRenameDialog,
    renameValue,
    openRename,
    saveRename,
    showDeleteImportDialog,
    deleteImportConfirmed,
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
} = useXlsxUniver();
</script>

<style>
.xlsx-univer-container {
    width: 100%;
    min-height: 500px;
}
</style>
