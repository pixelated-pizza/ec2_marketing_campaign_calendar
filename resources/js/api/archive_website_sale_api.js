import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function fetchArchivedWebsiteSale() {
  const { data } = await api.get("/archived_website_sales");
  return data;
}

export async function fetchArchivedWebsiteSaleById(id) {
  const { data } = await api.get(`/archived_website_sales/${id}`);
  return data;
}

export async function createArchivedWebsiteSale(sale) {
  const { data } = await api.post("/archived_website_sales", sale);
  return data;
}

export async function fetchUnarchiveWebsiteSale(id) {
  const { data } = await api.put(`/archived_website_sales/unarchive/${id}`);
  return data;
}
