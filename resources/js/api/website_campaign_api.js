import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function fetchWC() {
  const { data } = await api.get("/website_campaign");
  return data;
}

export async function createWC(campaign) {
  const { data } = await api.post("/website_campaign", campaign);
  return data;
}

export async function updateWC(id, updates) {
  const { data } = await api.put(`/website_campaign/${id}`, updates);
  return data;
}

export async function deleteWC(id) {
  await api.delete(`/website_campaign/${id}`);
  return true;
}

export async function fetchStores() {
  const { data } = await api.get("/stores");
  return Array.isArray(data) ? data : [];
}

export async function fetchSections() {
  const { data } = await api.get("/sections");
  return Array.isArray(data) ? data : [];
}


