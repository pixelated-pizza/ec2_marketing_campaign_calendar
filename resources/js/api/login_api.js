import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_APP_URL + "/api", 
  withCredentials: true,
});

export async function login(credentials) {
  const { data } = await api.post("/login", credentials);
  return data;
}

export async function loginWithFirebase(idToken) {
  const { data } = await api.post("/login/firebase", { idToken });
  return data;
}

export async function getName() {
  const token = localStorage.getItem("auth_token");
  const { data } = await api.get("/get-name", {
    headers: { Authorization: `Bearer ${token}` }
  });
  return data; 
}


export async function logout() {
  const { data } = await api.post("/logout");
  return data;
}