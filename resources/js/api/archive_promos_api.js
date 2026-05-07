import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function fetchArchivedPromotions() {
  const { data } = await api.get("/archived_promotions");
  return data;
}

export async function fetchArchivedPromotion(id) {
  const { data } = await api.get(`/archived_promotions/${id}`);
  return data;
}

export async function createArchivedPromotion(promo) {
  const { data } = await api.post("/archived_promotions", promo);
  return data;
}

export async function updateArchivedPromotion(id, updates) {
  const { data } = await api.put(`/archived_promotions/${id}`, updates);
  return data;
}
export async function deleteArchivedPromotion(id) {
  await api.delete(`/archived_promotions/${id}`);
  return true;
}

export async function fetchUnarchivePromotion(id) {
  const { data } = await api.put(`/archived_promotions/unarchive-promo/${id}`);
  return data;
}
