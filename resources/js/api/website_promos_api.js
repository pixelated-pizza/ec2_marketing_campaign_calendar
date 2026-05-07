import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function fetchWebsitePromotions() {
  const { data } = await api.get("/website_promos");
  return data;
}

export async function fetchWebsitePromotion(id) {
  const { data } = await api.get(`/website_promos/${id}`);
  return data;
}

export async function createWebsitePromotion(promo) {
  const { data } = await api.post("/website_promos", promo);
  return data;
}

export async function updateWebsitePromotion(id, updates) {
  const { data } = await api.put(`/website_promos/${id}`, updates);
  return data;
}
export async function deleteWebsitePromotion(id) {
  await api.delete(`/website_promos/${id}`);
  return true;
}

export async function archiveWebsitePromotion(id, is_archived) {
  const { data } = await api.patch(`/website_promos/archive/${id}`, { is_archived });
  return data;
}

