import api from "./api.js";

export async function fetchOnsiteCampaign() {
  const { data } = await api.get("/onsite_campaign");
  return data;
}

export async function createOnsiteCampaign(campaign) {
  const { data } = await api.post("/onsite_campaign", campaign);
  return data;
}

export async function updateOnsiteCampaign(id, updates) {
  const { data } = await api.put(`/onsite_campaign/${id}`, updates);
  return data;
}

export async function deleteOnsiteCampaign(id) {
  await api.delete(`/onsite_campaign/${id}`);
  return true;
}

export async function archiveOnsiteCampaign(id, is_archived) {
  const { data } = await api.patch(`/onsite_campaign/archive/${id}`, { is_archived });
  return data;
}

export async function fetchStores() {
  const { data } = await api.get("/stores");
  return Array.isArray(data) ? data : [];
}

export async function fetchSections() {
  const { data } = await api.get("/sections");
  return Array.isArray(data) ? data : [];
}

export async function fetchCampaignTypes() {
  const { data } = await api.get("/website_campaign_types");
  return Array.isArray(data) ? data : [];
}
