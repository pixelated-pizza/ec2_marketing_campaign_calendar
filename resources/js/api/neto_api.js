import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api",
  withCredentials: true,
});

export async function fetchNetoProducts(sku = null) {
  const params = sku ? { sku } : {};
  const { data } = await api.get("/neto/products", { params });
  return data;
}