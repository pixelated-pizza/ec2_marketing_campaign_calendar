import api from "./api.js";
import { authHeader } from "./user_api.js";

const opts = () => ({ headers: authHeader() });

export const fetchXlsxImports = async () => (await api.get("/xlsx-imports", opts())).data;

export const fetchXlsxImport = async (id) => (await api.get(`/xlsx-imports/${id}`, opts())).data;

// Step 1: upload the workbook, get { token, filename, sheets: [{ name, rows, columns }] }
export const uploadXlsx = async (file) => {
    const form = new FormData();
    form.append("file", file);
    return (await api.post("/xlsx-imports/upload", form, opts())).data;
};

// Step 2: top rows of a sheet, so the header row can be chosen
export const previewXlsxSheet = async (token, sheet) =>
    (await api.get(`/xlsx-imports/upload/${token}/preview`, { ...opts(), params: { sheet } })).data;

// Step 3: parse the chosen sheet and store it
export const importXlsx = async (payload) => (await api.post("/xlsx-imports", payload, opts())).data;

export const updateXlsxImport = async (id, payload) =>
    (await api.put(`/xlsx-imports/${id}`, payload, opts())).data;

export const deleteXlsxImport = async (id) => (await api.delete(`/xlsx-imports/${id}`, opts())).data;

export const addXlsxRow = async (id, data = null) =>
    (await api.post(`/xlsx-imports/${id}/rows`, { data }, opts())).data;

export const bulkUpdateXlsxRows = async (id, rows) =>
    (await api.post(`/xlsx-imports/${id}/rows/bulk-update`, { rows }, opts())).data;

export const bulkDeleteXlsxRows = async (id, ids) =>
    (await api.post(`/xlsx-imports/${id}/rows/bulk-delete`, { ids }, opts())).data;

// rows: [{ id: number|null, data?: any[] }] in sheet order; returns { ids }
export const syncXlsxRows = async (id, rows) =>
    (await api.post(`/xlsx-imports/${id}/sync`, { rows }, opts())).data;
