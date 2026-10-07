import { defineStore } from 'pinia';
import axios from 'axios';

const KEY = 'website-sale-details';

export const useWSDStore = defineStore('wsd', {
    state: () => ({
        headers: [],
        rows: [],
        lastSyncedAt: null,
        sheetEmbedUrl: null,
    }),

    actions: {
        async loadMirror() {
            const { data } = await axios.get(`/api/sheet-mirror/${KEY}`);
            this.headers = data.headers;
            this.rows = data.rows;
            this.lastSyncedAt = data.last_synced_at;
        },
        async loadSheetEmbedUrl() {
            const { data } = await axios.get(`/api/sheet-mirror/${KEY}/embed-url`);
            this.sheetEmbedUrl = data.url;
        },
        async pullFromSheet() {
            const { data } = await axios.post(`/api/sheet-mirror/${KEY}/pull`);
            await this.loadMirror();
            return data;
        },
        async pushToSheet() {
            const { data } = await axios.post(`/api/sheet-mirror/${KEY}/push`);
            return data;
        },
        // NEW: save Luckysheet edits straight to MySQL
        async saveSheet(headers, rows) {
            const { data } = await axios.post(`/api/sheet-mirror/${KEY}/save`, { headers, rows });
            this.headers = headers;
            this.rows = rows;
            return data;
        },
    },
});