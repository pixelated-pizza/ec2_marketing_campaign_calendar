// Single source of truth for the sheet's known columns — imported by both
// WSDFortuneSheet.vue (building the initial sheet) and ImportWSDModal.vue
// (matching CSV headers so imported cells get the same styling, e.g. wrap).
export const COLUMNS = [
    { field: "status", header: "Status", width: 90, type: "status", readOnly: true },
    { field: "channel_name", header: "Channel", width: 160 },
    { field: "event_name", header: "Event Name", width: 220 },
    { field: "start_date", header: "Start Date", width: 150, type: "date" },
    { field: "end_date", header: "End Date", width: 150, type: "date" },
    { field: "featured_products_sheet_url", header: "Featured Products Sheet", width: 240 },
    { field: "run_sheet_url", header: "Run Sheet", width: 240 },
    { field: "event_master_sheet_url", header: "Event Master Sheet", width: 240 },
    { field: "ess", header: "ESS to Execute", width: 140 },
    { field: "cms_to_audit", header: "CMS to Audit", width: 140 },
    { field: "terms_conditions", header: "T&Cs", width: 220, wrap: true },
    { field: "mockup_banner_locations", header: "Mockup & Banner Locations", width: 220 },
    { field: "is_sku_list_to_feature", header: "SKU List to Feature?", width: 140, type: "yesno" },
    { field: "featured_banner_text", header: "Featured Banner Text", width: 220, wrap: true },
    { field: "sku_in_category_creative", header: "SKU in Category Creative", width: 220, wrap: true },
    { field: "url_text", header: "URL Text", width: 200, wrap: true },
];

// header text (lowercased) -> column def, for matching CSV headers
export const COLUMNS_BY_HEADER = new Map(COLUMNS.map((c) => [c.header.trim().toLowerCase(), c]));

// A cell counts as "there" only if it actually has a non-blank value.
// FortuneSheet's own delete/clear operations don't shrink `celldata` — they
// just blank out `v` on cells that used to hold something, leaving dead
// entries behind. Treating those as real content is what causes imports to
// get appended hundreds of rows/columns past anything visible.
export function cellHasContent(cell) {
    const v = cell?.v?.v;
    return v !== null && v !== undefined && String(v).trim() !== "";
}

export function pruneBlankCells(celldata) {
    return Array.isArray(celldata) ? celldata.filter(cellHasContent) : [];
}

// Decide wrap purely from content, not from matching a column's header text
// to a fixed list — CSV headers vary in wording ("Featured Banner Text" vs
// "Featured Category Banners Text") and a hardcoded match silently misses
// anything that doesn't spell it exactly the same way. URLs are the one
// exception: long but shouldn't wrap.
const WRAP_LENGTH_THRESHOLD = 40;

export function shouldWrapText(text) {
    if (!text) return false;
    if (/^https?:\/\//i.test(text.trim())) return false;
    return text.includes("\n") || text.length > WRAP_LENGTH_THRESHOLD;
}