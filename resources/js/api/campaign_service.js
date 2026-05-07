import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function fetchCampaigns() {
  const { data } = await api.get("/campaigns");
  return data;
}

export async function createCampaign(campaign) {
  const { data } = await api.post("/campaigns", campaign);
  return data;
}

export async function updateCampaign(id, updates) {
  const { data } = await api.put(`/campaigns/${id}`, updates);
  return data;
}

export async function deleteCampaign(id) {
  await api.delete(`/campaigns/${id}`);
  return true;
}

export async function fetchChannels() {
  const { data } = await api.get("/category_channels");
  return Array.isArray(data) ? data : [];
}


