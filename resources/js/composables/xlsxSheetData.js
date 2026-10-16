export const HEADER_ROWS = 1;
export const SPARE_ROWS = 40; 
const MIN_COL_WIDTH = 90;
const MAX_COL_WIDTH = 340;
const SAMPLE_ROWS = 150;

const HEADER_STYLE = {
    bl: 1,
    bg: { rgb: "#E5E7EB" },
    cl: { rgb: "#111827" },
    vt: 2,
    bd: {
        b: { s: 1, cl: { rgb: "#9CA3AF" } },
    },
};

// Univer cell types: 1 = string, 2 = number, 3 = boolean
export function cellFromValue(v) {
    if (v === null || v === undefined || v === "") return undefined;
    if (typeof v === "number") return { v, t: 2 };
    if (typeof v === "boolean") return { v: v ? 1 : 0, t: 3 };
    return { v: String(v), t: 1 };
}

function columnWidth(header, rows, col) {
    let longest = String(header ?? "").length;
    const limit = Math.min(rows.length, SAMPLE_ROWS);
    for (let i = 0; i < limit; i++) {
        const v = rows[i].data[col];
        if (v == null) continue;
        const len = String(v).split("\n")[0].length;
        if (len > longest) longest = len;
    }
    return Math.min(MAX_COL_WIDTH, Math.max(MIN_COL_WIDTH, longest * 7 + 20));
}

export function buildXlsxWorkbook(meta, rows) {
    const headers = meta.headers || [];
    const cols = headers.length;

    const cellData = { 0: {} };
    const rowData = { 0: { h: 28 } };
    const columnData = {};

    headers.forEach((h, c) => {
        cellData[0][c] = { v: h, t: 1, s: HEADER_STYLE };
        columnData[c] = { w: columnWidth(h, rows, c) };
    });

    rows.forEach((row, i) => {
        const r = i + HEADER_ROWS;
        const line = {};
        for (let c = 0; c < cols; c++) {
            const cell = cellFromValue(row.data[c]);
            if (cell) line[c] = cell;
        }
        cellData[r] = line;
        rowData[r] = { custom: { rid: row.id } };
    });

    const sheetId = "data";

    return {
        id: `xlsx-import-${meta.id}`,
        name: meta.name,
        appVersion: "3.0.0-alpha",
        locale: "enUS",
        styles: {},
        sheetOrder: [sheetId],
        sheets: {
            [sheetId]: {
                id: sheetId,
                name: (meta.sheet_name || "Data").slice(0, 31),
                rowCount: rows.length + HEADER_ROWS + SPARE_ROWS,
                columnCount: Math.max(cols, 1), // columns are fixed by the import; the backend stores exactly these
                defaultColumnWidth: 120,
                defaultRowHeight: 24,
                showGridlines: 1,
                freeze: { xSplit: 0, ySplit: HEADER_ROWS, startRow: HEADER_ROWS, startColumn: 0 },
                columnData,
                rowData,
                cellData,
            },
        },
    };
}
