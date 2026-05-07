import axios from 'axios';

const BASE_URL = '/api';

/**
 * Fetches all users.
 * No dedicated GET /users route exists yet — this calls GET /get-name
 * which returns the currently authenticated user.
 * Swap the endpoint below once a GET /users admin route is added.
 */
export const fetchCurrentUser = () =>
    axios.get(`${BASE_URL}/get-name`, {
        headers: authHeader(),
    });

export const loginUser = (email, password, remember = false) =>
    axios.post(`${BASE_URL}/login`, { email, password, remember });

export const loginWithFirebase = (idToken) =>
    axios.post(`${BASE_URL}/login/firebase`, { idToken });

export const logoutUser = () =>
    axios.post(`${BASE_URL}/logout`, {}, { headers: authHeader() });

// ---------------------------------------------------------------------------
// Helper: pull token from localStorage and build Authorization header
// ---------------------------------------------------------------------------
export function authHeader() {
    const token = localStorage.getItem('auth_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}