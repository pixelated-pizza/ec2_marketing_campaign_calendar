<template>
    <div class="xl-filter">
        <input
            v-model="search"
            type="search"
            class="xl-search"
            placeholder="Search"
            @keydown.enter="apply"
        />

        <div class="xl-list">
            <label class="xl-row">
                <input
                    type="checkbox"
                    :checked="allChecked"
                    :indeterminate="partiallyChecked"
                    :disabled="!matches.length"
                    @change="toggleAll($event.target.checked)"
                />
                <span>{{ search ? '(Select All Search Results)' : '(Select All)' }}</span>
            </label>

            <label v-for="o in shown" :key="'k' + o.key" class="xl-row">
                <input
                    type="checkbox"
                    :checked="draft.has(o.key)"
                    @change="toggleOne(o.key, $event.target.checked)"
                />
                <span :title="o.label">{{ o.label }}</span>
            </label>

            <div v-if="!matches.length" class="xl-note">No matches</div>
            <div v-else-if="matches.length > shown.length" class="xl-note">
                Showing the first {{ shown.length }} of {{ matches.length }}. Type to narrow the list.
            </div>
        </div>

        <div class="xl-actions">
            <button type="button" class="xl-btn xl-btn-primary" :disabled="draft.size === 0" @click="apply">OK</button>
            <button type="button" class="xl-btn" @click="cancel">Cancel</button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    params: { type: Object, required: true }
});

const MAX_ROWS = 2000;
const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

const applied = ref(null); // Set of allowed values, or null when the filter is off
const draft = ref(new Set()); // checkbox state while the popup is open
const options = ref([]); // [{ key, label }]
const search = ref('');
let hidePopup = null;
let snapshot = null; // checkbox state saved while a search is active

// Empty cells share one key so they can be filtered as "(Blanks)"
const toKey = (value) => (value === null || value === undefined ? '' : String(value));

function collectOptions() {
    const keys = new Set();
    props.params.api.forEachNode((node) => {
        if (!node.data) return;
        // cascade like Excel: only offer values that pass the other columns' filters
        if (!props.params.doesRowPassOtherFilter(node)) return;
        keys.add(toKey(props.params.getValue(node)));
    });

    const list = [...keys].sort((a, b) => {
        if (a === '') return 1; // blanks last
        if (b === '') return -1;
        return collator.compare(a, b);
    });

    options.value = list.map((key) => ({
        key,
        label: key === '' ? '(Blanks)' : key.replace(/\s+/g, ' ')
    }));
}

const matches = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return options.value;
    return options.value.filter((o) => o.label.toLowerCase().includes(q));
});

// Like Excel: typing in the search box ticks only the matching results,
// and clearing the box brings back the previous selection.
watch(search, (q) => {
    if (q.trim()) {
        if (!snapshot) snapshot = new Set(draft.value);
        draft.value = new Set(matches.value.map((o) => o.key));
    } else if (snapshot) {
        draft.value = snapshot;
        snapshot = null;
    }
});

const shown = computed(() => matches.value.slice(0, MAX_ROWS));

const allChecked = computed(() => matches.value.length > 0 && matches.value.every((o) => draft.value.has(o.key)));
const partiallyChecked = computed(() => !allChecked.value && matches.value.some((o) => draft.value.has(o.key)));

function toggleAll(checked) {
    const next = new Set(draft.value);
    for (const o of matches.value) {
        if (checked) next.add(o.key);
        else next.delete(o.key);
    }
    draft.value = next;
}

function toggleOne(key, checked) {
    const next = new Set(draft.value);
    if (checked) next.add(key);
    else next.delete(key);
    draft.value = next;
}

function apply() {
    if (draft.value.size === 0) return;
    const everything = options.value.every((o) => draft.value.has(o.key));
    applied.value = everything ? null : new Set(draft.value);
    props.params.filterChangedCallback();
    hidePopup?.();
}

function cancel() {
    hidePopup?.();
}

// --- AG Grid filter interface ---
function doesFilterPass({ node }) {
    if (!applied.value) return true;
    return applied.value.has(toKey(props.params.getValue(node)));
}

function isFilterActive() {
    return applied.value !== null;
}

function getModel() {
    return applied.value ? { values: [...applied.value] } : null;
}

function setModel(model) {
    applied.value = model && Array.isArray(model.values) ? new Set(model.values) : null;
}

function afterGuiAttached(p) {
    hidePopup = p?.hidePopup ?? null;
    snapshot = null;
    search.value = '';
    collectOptions();
    draft.value = applied.value ? new Set(applied.value) : new Set(options.value.map((o) => o.key));
}

defineExpose({ doesFilterPass, isFilterActive, getModel, setModel, afterGuiAttached });
</script>

<style>
/* Not scoped on purpose: AG Grid mounts filters in a popup where scoped styles don't reach.
   Every selector is prefixed with .xl-filter so it can't leak into the rest of the app. */
.xl-filter {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 260px;
    padding: 10px;
    box-sizing: border-box;
    font-size: 12px;
    line-height: 1.4;
}

.xl-filter .xl-search {
    display: block;
    width: 100%;
    height: auto;
    margin: 0;
    padding: 4px 8px;
    box-sizing: border-box;
    font: inherit;
    color: inherit;
    background: transparent;
    border: 1px solid var(--ag-border-color, rgba(128, 128, 128, 0.5));
    border-radius: 3px;
    outline: none;
}

.xl-filter .xl-search:focus {
    border-color: #217346;
}

.xl-filter .xl-list {
    display: block;
    max-height: 220px;
    overflow-y: auto;
    padding: 4px 6px;
    border: 1px solid var(--ag-border-color, rgba(128, 128, 128, 0.5));
    border-radius: 3px;
}

.xl-filter .xl-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 3px 0;
    cursor: pointer;
}

.xl-filter .xl-row input {
    flex: 0 0 auto;
    width: 14px;
    height: 14px;
    margin: 0;
    accent-color: #217346;
}

.xl-filter .xl-row span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.xl-filter .xl-note {
    padding: 6px 0 2px;
    opacity: 0.7;
}

.xl-filter .xl-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.xl-filter .xl-btn {
    padding: 4px 16px;
    font: inherit;
    color: inherit;
    cursor: pointer;
    background: transparent;
    border: 1px solid var(--ag-border-color, rgba(128, 128, 128, 0.5));
    border-radius: 3px;
}

.xl-filter .xl-btn-primary {
    color: #fff;
    background: #217346;
    border-color: #217346;
}

.xl-filter .xl-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
