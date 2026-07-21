const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api",
  withCredentials: true,
});

export async function fetchWSD() {
  const { data } = await api.get("website_sale_details");
  return data;
}

export async function createWSD(wsd) {
  const { data } = await api.post("website_sale_details", wsd);
  return data;
}

export async function updateWSD(id, updates) {
  const { data } = await api.put(`website_sale_details/${id}`, updates);
  return data;
}

export async function deleteWSD(id) {
  await api.delete(`website_sale_details/${id}`);
  return true;
}

export async function fetchBlankWSD({ event_name, channel_name, start_date, end_date }) {
  const { data } = await api.get("website_sale_details/blank", {
    params: { event_name, channel_name, start_date, end_date },
  });
  return data.data;
}

export async function uploadWSDImage(wsdId, file) {
  const formData = new FormData();
  formData.append("image", file);
  const { data } = await api.post(`website_sale_details/image/${wsdId}`, formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return data;
}

export async function deleteWSDImage(wsdId) {
  const { data } = await api.delete(`website_sale_details/image/${wsdId}`);
  return data;
}

export async function previewImportWSD(rows) {
  const { data } = await api.post("website_sale_details/import/preview", { rows });
  return data;
}

export async function commitImportWSD(rows) {
  const { data } = await api.post("website_sale_details/import/commit", { rows });
  return data;
}

export async function deleteAllWSD() {
    await api.delete("website_sale_details");
    return true;
}