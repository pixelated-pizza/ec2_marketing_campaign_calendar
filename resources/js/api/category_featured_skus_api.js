import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api",
  withCredentials: true,
});

export async function fetchFeaturedSkus() {
  const { data } = await api.get("/category-featured-skus");
  return data;
}

export async function createFeaturedSku(payload) {
  const { data } = await api.post("/category-featured-skus", payload);
  return data;
}

export async function deleteFeaturedSku(id) {
  const { data } = await api.delete(`/category-featured-skus/${id}`);
  return data;
}

export async function createBulkFeaturedSkus(payload) {
  const { data } = await api.post("/category-featured-skus/bulk", payload);
  return data;
}

export async function syncFeaturedSkus() {
  const { data } = await api.post("/category-featured-skus/sync");
  return data;
}

export async function updateFeaturedSku(id, payload) {
  const { data } = await api.put(`/category-featured-skus/${id}`, payload);
  return data;
}

export async function deleteAll() {
  const { data } = await api.delete("/category-featured-skus");
  return data;
}