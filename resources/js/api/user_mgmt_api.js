import api from './api.js';
import { authHeader } from './user_api.js';

export const fetchUsers = () =>
    api.get('/users', { headers: authHeader() });

export const createUser = (payload) =>
    api.post('/users', payload, { headers: authHeader() });

export const updateUser = (id, payload) =>
    api.put(`/users/${id}`, payload, { headers: authHeader() });

export const deleteUser = (id) =>
    api.delete(`/users/${id}`, { headers: authHeader() });

export const fetchCurrentUser = () =>
    api.get('/get-name', { headers: authHeader() });