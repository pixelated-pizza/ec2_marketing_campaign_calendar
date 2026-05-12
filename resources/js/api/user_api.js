import api from './api.js';

export const fetchCurrentUser = () =>
    api.get('/get-name', { headers: authHeader() });

export const loginUser = (email, password, remember = false) =>
    api.post('/login', { email, password, remember });

export const loginWithFirebase = (idToken) =>
    api.post('/login/firebase', { idToken });

export const logoutUser = () =>
    api.post('/logout', {}, { headers: authHeader() });

export function authHeader() {
    const token = localStorage.getItem('auth_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}