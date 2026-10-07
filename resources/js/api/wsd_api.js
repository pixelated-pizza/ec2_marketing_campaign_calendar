const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api",
  withCredentials: true,
});

export async function fetchSheetSnapshot(key) {
  const { data } = await api.get(`sheet_snapshots/${key}`);
  return data.payload ?? null;
}

export async function saveSheetSnapshot(key, payload) {
  const { data } = await api.put(`sheet_snapshots/${key}`, { payload });
  return data;
}