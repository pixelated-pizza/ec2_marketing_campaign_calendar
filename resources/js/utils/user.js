
import { defineStore } from "pinia";
import { getName } from "@/js/api/login_api.js";

export const useUserStore = defineStore("user", {
  state: () => ({
    name: null,
  }),
  actions: {
    async fetchUser() {
      try {
        const res = await getName();
        this.name = res.name;
      } catch (e) {
        console.error("Failed to fetch user", e);
      }
    },
  },
});
