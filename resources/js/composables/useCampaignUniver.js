import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, getCurrentInstance } from "vue";
import { useUIStore } from "@/js/stores/ui.js";

// ---- Univer setup -----------------------------------------------------------
// If your existing wsd-sheet component imports Univer differently (plugins
// instead of presets, other CSS path), copy ITS imports and createUniver call
// here. Only the workbook data and the click handling are specific to this page.
import { createUniver, LocaleType, mergeLocales } from "@univerjs/presets";
import { UniverSheetsCorePreset } from "@univerjs/preset-sheets-core";
import sheetsCoreEnUS from "@univerjs/preset-sheets-core/locales/en-US";
import "@univerjs/preset-sheets-core/lib/index.css";
// -----------------------------------------------------------------------------

import {
    fetchCampaigns,
    createCampaign,
    updateCampaign,
    deleteCampaign,
    fetchChannels,
} from "@/js/api/campaign_service";
import { buildCampaignSheet, parseDate, formatDateTime } from "./campaignSheetData.js";

const DEFAULT_COLOR = "#60A5FA";
const DAY = 86400000;

export function useCampaignUniver() {
    const toastr = getCurrentInstance().appContext.config.globalProperties.$toastr;
    const ui = useUIStore();

    const loading = ref(true);
    const univerContainer = ref(null);

    const channels = ref([]);
    const allCampaigns = ref([]);

    const selectedChannel = ref(null);
    const searchTerm = ref("");
    const dateRange = ref(null);

    // Add / edit dialog
    const showDialog = ref(false);
    const showDeleteDialog = ref(false);
    const saving = ref(false);
    const editId = ref(null);
    const form = ref(emptyForm());
    const errors = ref({ name: null, channel_id: null, start_date: null, end_date: null });

    let univerAPI = null;
    let darkObserver = null;
    let renderTimer = null;
    let clickLookup = {}; // "row:col" -> campaign / channel, rebuilt with each render

    function emptyForm() {
        return { name: "", channel_id: null, start_date: null, end_date: null, color: DEFAULT_COLOR };
    }

    // -----------------------------------------------------------------------
    // Same channel order and filters as the dhtmlx version
    // -----------------------------------------------------------------------
    const sortedChannels = computed(() => {
        const preferredOrder = ["Edisons", "Mytopia"];
        return [...channels.value].sort((a, b) => {
            const ai = preferredOrder.indexOf(a.name);
            const bi = preferredOrder.indexOf(b.name);
            if (ai !== -1 && bi !== -1) return ai - bi;
            if (ai !== -1) return -1;
            if (bi !== -1) return 1;
            return a.name.localeCompare(b.name);
        });
    });

    // Columns shown: the picked range, otherwise the current month
    const windowRange = computed(() => {
        const [s, e] = dateRange.value || [];
        if (s && e) return [s, e];
        const now = new Date();
        return [
            new Date(now.getFullYear(), now.getMonth(), 1),
            new Date(now.getFullYear(), now.getMonth() + 1, 0),
        ];
    });

    const filteredCampaigns = computed(() => {
        const [rangeStart, rangeEnd] = windowRange.value;
        const windowStart = new Date(rangeStart.getFullYear(), rangeStart.getMonth(), rangeStart.getDate());
        const windowEnd = new Date(rangeEnd.getFullYear(), rangeEnd.getMonth(), rangeEnd.getDate(), 23, 59, 59);

        return allCampaigns.value.filter((c) => {
            const matchChannel = selectedChannel.value ? c.channel_id === selectedChannel.value : true;
            const matchSearch = searchTerm.value
                ? c.name.toLowerCase().includes(searchTerm.value.toLowerCase())
                : true;

            const start = parseDate(c.start_date);
            const end = parseDate(c.end_date);
            if (!start || !end) return false;

            return matchChannel && matchSearch && end >= windowStart && start <= windowEnd;
        });
    });

    // -----------------------------------------------------------------------
    // Data loading
    // -----------------------------------------------------------------------
    async function loadCampaigns() {
        try {
            allCampaigns.value = await fetchCampaigns();
        } catch (err) {
            console.error("Failed to load campaigns:", err);
        }
    }

    // -----------------------------------------------------------------------
    // Click handling
    // -----------------------------------------------------------------------
    function handleCellClick(row, column) {
        const hit = clickLookup[`${row}:${column}`];
        if (!hit) return;

        if (hit.type === "campaign") openEditDialog(hit.id);
        else if (hit.type === "channel") openAddDialog(hit.channelId);
    }

    function registerSheetEvents() {
        const E = univerAPI.Event || {};

        // Preferred: a real cell click event
        if (E.CellClicked) {
            univerAPI.addEvent(E.CellClicked, (p) => handleCellClick(p.row, p.column));
        } else if (E.SelectionChanged) {
            // Fallback for versions without CellClicked: use the selected cell
            univerAPI.addEvent(E.SelectionChanged, (p) => {
                const sel = p.selections?.[0];
                const range = sel?.range ?? sel;
                if (range) handleCellClick(range.startRow, range.startColumn);
            });
        }

        // Keep the sheet display-only: block typing into cells
        if (E.BeforeSheetEditStart) {
            univerAPI.addEvent(E.BeforeSheetEditStart, (p) => {
                p.cancel = true;
            });
        }
        univerAPI.getActiveWorkbook?.()?.setEditable?.(false);
    }

    // -----------------------------------------------------------------------
    // Univer lifecycle
    // -----------------------------------------------------------------------
    const isDarkMode = () => document.documentElement.classList.contains("app-dark");

    function destroyUniver() {
        try {
            univerAPI?.dispose();
        } catch (e) {
            console.warn("Failed to dispose Univer", e);
        }
        univerAPI = null;
        if (univerContainer.value) univerContainer.value.innerHTML = "";
    }

    function renderSheet() {
        if (!univerContainer.value) return;
        destroyUniver();

        const created = createUniver({
            locale: LocaleType.EN_US,
            locales: { [LocaleType.EN_US]: mergeLocales(sheetsCoreEnUS) },
            presets: [
                UniverSheetsCorePreset({
                    container: univerContainer.value,
                    // Hide the spreadsheet chrome so it reads as a calendar
                    header: false,
                    toolbar: false,
                    formulaBar: false,
                    footer: false,
                    contextMenu: false,
                }),
            ],
        });

        univerAPI = created.univerAPI;
        univerAPI.toggleDarkMode?.(isDarkMode());

        const { data, lookup } = buildCampaignSheet({
            campaigns: filteredCampaigns.value,
            channels: sortedChannels.value,
            range: windowRange.value,
        });
        clickLookup = lookup;

        univerAPI.createWorkbook(data);
        registerSheetEvents();
    }

    // Debounced so typing in the search box doesn't rebuild on every key
    function scheduleRender() {
        clearTimeout(renderTimer);
        renderTimer = setTimeout(renderSheet, 250);
    }

    watch([filteredCampaigns, sortedChannels, windowRange], scheduleRender);

    function resetFilters() {
        searchTerm.value = "";
        selectedChannel.value = null;
        dateRange.value = null;
    }

    // -----------------------------------------------------------------------
    // Add / edit / delete dialog
    // -----------------------------------------------------------------------
    function resetErrors() {
        Object.keys(errors.value).forEach((k) => (errors.value[k] = null));
    }

    function openAddDialog(channelId) {
        editId.value = null;
        resetErrors();

        const start = new Date();
        start.setHours(9, 0, 0, 0); // 9:00 AM
        const end = new Date(start.getTime() + 13 * DAY);
        end.setHours(23, 59, 0, 0); // 11:59 PM

        const preselect = channelId ?? selectedChannel.value;
        form.value = {
            ...emptyForm(),
            channel_id: preselect != null ? String(preselect) : null,
            start_date: start,
            end_date: end,
        };
        showDialog.value = true;
    }

    function openEditDialog(id) {
        const c = allCampaigns.value.find((x) => String(x.campaign_id) === String(id));
        if (!c) return;

        editId.value = c.campaign_id;
        resetErrors();
        form.value = {
            name: c.name,
            channel_id: String(c.channel_id),
            start_date: parseDate(c.start_date),
            end_date: parseDate(c.end_date),
            color: c.background_color || DEFAULT_COLOR,
        };
        showDialog.value = true;
    }

    function closeDialog() {
        showDialog.value = false;
        editId.value = null;
    }

    function validateForm() {
        resetErrors();
        let valid = true;

        if (!form.value.name || !form.value.name.trim()) {
            errors.value.name = "Name is required.";
            valid = false;
        }
        if (!form.value.channel_id) {
            errors.value.channel_id = "Select a channel.";
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
        if (
            form.value.start_date &&
            form.value.end_date &&
            new Date(form.value.end_date) <= new Date(form.value.start_date)
        ) {
            errors.value.end_date = "End must be after start.";
            valid = false;
        }
        return valid;
    }

    async function saveCampaign() {
        if (!validateForm()) {
            toastr.warning("Please correct the errors before saving.");
            return;
        }

        const payload = {
            name: form.value.name.trim(),
            start_date: formatDateTime(form.value.start_date),
            end_date: formatDateTime(form.value.end_date),
            channel_id: form.value.channel_id,
            background_color: form.value.color || DEFAULT_COLOR,
        };

        saving.value = true;
        ui.showLoader();
        try {
            if (editId.value) {
                await updateCampaign(editId.value, payload);
                toastr.success("Campaign updated successfully.");
            } else {
                await createCampaign(payload);
                toastr.success("Campaign created successfully.");
            }
            closeDialog();
            await loadCampaigns(); // the watcher redraws the sheet
        } catch (err) {
            console.error("Error saving campaign:", err);
            toastr.error(editId.value ? "Failed to update campaign." : "Failed to create campaign.");
        } finally {
            saving.value = false;
            ui.hideLoader();
        }
    }

    function confirmDelete() {
        showDeleteDialog.value = true;
    }

    async function deleteConfirmed() {
        if (!editId.value) return;

        showDeleteDialog.value = false;
        ui.showLoader();
        try {
            await deleteCampaign(editId.value);
            toastr.success("Campaign deleted successfully.");
            closeDialog();
            await loadCampaigns();
        } catch (err) {
            console.error("Failed to delete campaign:", err);
            toastr.error("Failed to delete campaign.");
        } finally {
            ui.hideLoader();
        }
    }

    // -----------------------------------------------------------------------
    // Init
    // -----------------------------------------------------------------------
    onMounted(async () => {
        loading.value = true;
        try {
            channels.value = await fetchChannels();
            await loadCampaigns();
        } catch (err) {
            console.error("Failed to load campaign data:", err);
        } finally {
            loading.value = false;
            await nextTick();
            renderSheet();
        }

        darkObserver = new MutationObserver(() => univerAPI?.toggleDarkMode?.(isDarkMode()));
        darkObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });
    });

    onBeforeUnmount(() => {
        clearTimeout(renderTimer);
        darkObserver?.disconnect();
        destroyUniver();
    });

    return {
        loading,
        univerContainer,
        selectedChannel,
        searchTerm,
        dateRange,
        channels,
        resetFilters,
        // dialog
        showDialog,
        showDeleteDialog,
        saving,
        editId,
        form,
        errors,
        openAddDialog,
        closeDialog,
        saveCampaign,
        confirmDelete,
        deleteConfirmed,
    };
}