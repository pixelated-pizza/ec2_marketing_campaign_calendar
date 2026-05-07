import axios from "axios";

const baseURL =
  import.meta.env.VITE_APP_URL || `${window.location.origin}/api`;

const api = axios.create({
  baseURL,
  withCredentials: true,
});

api.interceptors.request.use(async (config) => {
  if (config.data && !(config.data instanceof FormData) && typeof config.data === "object") {
    config.data = JSON.parse(
      JSON.stringify(config.data, (_, v) => (v === "" ? null : v))
    );
  }

  return config;
});

export default api;
