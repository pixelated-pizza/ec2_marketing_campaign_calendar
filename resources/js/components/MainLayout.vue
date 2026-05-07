<template>
  <div class="flex w-screen h-screen overflow-hidden">
    <aside v-if="route.path !== '/login'" :class="[
      sidebarOpen ? 'basis-64' : 'basis-16',
      'text-gray-200 transition-[flex-basis] duration-300 shadow-lg flex flex-col justify-between h-full overflow-hidden'
    ]">

      <div>
        <div class="p-4 flex items-center border-b border-gray-700">
          <div class="flex-1 flex justify-center">
            <img v-show="sidebarOpen" :src="'/app_icon.png'" alt="MarketMap" class="w-24 h-24 rounded-full" />
          </div>

          <button @click="sidebarOpen = !sidebarOpen"
            class="focus:outline-none text-gray-400 hover:text-white transition ml-2">
            <i class="pi pi-bars text-xl"></i>
          </button>
        </div>

        <nav class="mt-4 space-y-2">
          <li>
            <router-link to="/dashboard"
              class="flex items-center px-4 py-2 hover:bg-gray-700 hover:text-white transition"
              active-class="bg-gray-700 dark:text-white border-l-4 border-blue-500" v-show="sidebarOpen">
              <i class="pi pi-chart-bar mr-2 w-4 text-center"></i>
              Dashboard
            </router-link>
          </li>

          <li class="px-2">
            <button @click="calendarMenuOpen = !calendarMenuOpen"
              class="w-full flex justify-between items-center px-2 py-2 text-sm font-medium hover:bg-gray-700 hover:text-white transition"
              v-show="sidebarOpen">
              <span class="flex items-center">
                <i class="pi pi-calendar-clock mr-2 w-4 text-center"></i>
                Marketing Calendar
              </span>
              <i :class="['pi pi-chevron-right transition-transform', { 'rotate-90': calendarMenuOpen }]"></i>
            </button>

            <ul v-show="calendarMenuOpen" class="pl-6 mt-2 space-y-1 text-sm text-gray-300">
              <li>
                <button @click="weeklyMenuOpen = !weeklyMenuOpen"
                  class="w-full flex justify-between items-center px-2 py-1 hover:bg-gray-600 transition"
                  v-show="sidebarOpen">
                  <span class="flex items-center">
                    <i class="pi pi-calendar mr-2 w-4 text-center"></i>
                    Weekly Calendar
                  </span>
                  <i :class="['pi pi-chevron-right transition-transform', { 'rotate-90': weeklyMenuOpen }]"></i>
                </button>

                <ul v-show="weeklyMenuOpen" class="pl-6 mt-2 space-y-1 text-xs text-gray-400">
                  <li class="px-2 py-1  hover:bg-gray-600">
                    <router-link to="/campaigns" class="flex items-center" v-show="sidebarOpen"
                      active-class="bg-gray-700 text-white p-2">
                      <i class="pi pi-globe mr-2 w-4 text-center"></i>
                      Website & Marketplaces Campaigns
                    </router-link>
                  </li>
                  <li class="px-2 py-1  hover:bg-gray-600">
                    <router-link to="/website_campaigns" class="flex items-center" v-show="sidebarOpen"
                      active-class="bg-gray-700 text-white p-2">
                      <i class="pi pi-globe mr-2 w-4 text-center"></i>
                      Website Sales / Promotions - Mytopia & Edisons
                    </router-link>
                  </li>
                </ul>
              </li>

              <li class="px-2 py-1  hover:bg-gray-600">
                <router-link to="/website-sale" class="flex items-center" v-show="sidebarOpen"
                  active-class="bg-gray-700 text-white p-2">
                  <i class="pi pi-pen-to-square mr-2 w-4 text-center"></i>
                  Website Sale Details
                </router-link>
              </li>
              <li class="px-2 py-1  hover:bg-gray-600">
                <router-link to="/website-promo" class="flex items-center" v-show="sidebarOpen"
                  active-class="bg-gray-700 text-white p-2">
                  <i class="pi pi-pen-to-square mr-2 w-4 text-center"></i>
                  Website Promotion Details
                </router-link>
              </li>
            </ul>
          </li>
          <li class="px-2">
            <button @click="archiveMenuOpen = !archiveMenuOpen"
              class="w-full flex justify-between items-center px-2 py-2 text-sm font-medium hover:bg-gray-700 hover:text-white transition"
              v-show="sidebarOpen">
              <span class="flex items-center">
                <i class="pi pi-book mr-2 w-4 text-center"></i>
                Archive
              </span>
              <i :class="['pi pi-chevron-right transition-transform', { 'rotate-90': archiveMenuOpen }]"></i>
            </button>
            <ul v-show="archiveMenuOpen" class="pl-6 mt-2 space-y-1 text-sm">
              <li class="px-2 py-1  hover:bg-gray-600">
                <router-link to="/website-promotions-archive" class="flex items-center" v-show="sidebarOpen"
                  active-class="bg-gray-700 text-white p-2">
                  <i class="pi pi-chart-line mr-2 w-4 text-center"></i>
                  Website Promotions
                </router-link>
              </li>
              <li class="px-2 py-1  hover:bg-gray-600">
                <router-link to="/website-sale-archive" class="flex items-center" v-show="sidebarOpen"
                  active-class="bg-gray-700 text-white p-2">
                  <i class="pi pi-globe mr-2 w-4 text-center"></i>
                  Website Sales
                </router-link>
              </li>
              <li class="px-2 py-1  hover:bg-gray-600">
                <router-link to="/#" class="flex items-center" v-show="sidebarOpen"
                  active-class="bg-gray-700 text-white p-2">
                  <i class="pi pi-shopping-bag mr-2 w-4 text-center"></i>
                  Marketplace Promotions
                </router-link>
              </li>
            </ul>
          </li>
        </nav>
      </div>

      <div class="border-t border-gray-700 p-4 items-center justify-between">
        <div v-show="sidebarOpen" class="flex items-center gap-2 text-sm font-medium text-gray-300">
          <i class="pi pi-user text-gray-400"></i>
          <span class="font-semibold text-white truncate">{{ userName }}</span>
          <Button @click="handleLogout" icon="pi pi-sign-out" rounded outlined severity="contrast"
            class="hover:text-red-400 transition ml-auto" v-show="sidebarOpen" />
          <Button @click="handleLogout" icon="pi pi-sign-out" rounded outlined severity="contrast"
            class="w-full hover:text-red-400 transition" v-show="!sidebarOpen" />
        </div>

      </div>
    </aside>

    <div class="flex flex-col flex-1 h-full">

      <header v-if="route.path !== '/login'"
        class="relative col-start-2 flex items-center justify-between bg-black backdrop-blur-md text-white px-6 shadow-lg border-b border-gray-700 h-16 min-h-[4rem] shrink-0">
        <h1 class="text-sm font-semibold tracking-wide drop-shadow-sm">
          MarketMap
        </h1>


        <div class="relative flex gap-2 items-center">
          <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
            <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
          </button>
          <Button icon="pi pi-bell" rounded outlined severity="contrast" class="hover:text-yellow-400 transition"
            @click="notificationsOpen = !notificationsOpen" />
          <span v-if="newNotifications.length"
            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5">
            {{ newNotifications.length }}
          </span>

        </div>

      </header>

      <teleport to="body">
        <div v-if="notificationsOpen"
          class="fixed top-14 right-6 w-96 bg-[#1c1e21] text-gray-200 shadow-2xl rounded-xl z-[3000] border border-gray-800 overflow-hidden">
          <div class="flex items-center justify-between px-4 py-2 border-b border-gray-800 bg-[#242526]">
            <div class="flex space-x-2 text-sm font-medium">
              <button v-for="tab in ['All', 'Upcoming', 'Active', 'Completed']" :key="tab" @click="activeTab = tab"
                :class="[
                  'px-3 py-1 rounded-full transition',
                  activeTab === tab
                    ? 'bg-blue-500 text-white'
                    : 'hover:bg-[#3a3b3c] text-gray-400'
                ]">
                {{ tab }}
              </button>
            </div>

            <button class="text-gray-400 hover:text-gray-200 transition" @click="notificationsOpen = false">
              <i class="pi pi-times"></i>
            </button>
          </div>

          <div class="max-h-96 overflow-y-auto divide-y divide-gray-800">
            <template v-if="newNotificationsSorted.length">
              <div
                class="px-4 py-2 text-xs uppercase tracking-wide text-gray-400 bg-[#242526] border-b border-gray-800">
                New
              </div>

              <div v-for="c in newNotificationsSorted" :key="`new-${c.campaign_id}`"
                class="p-3 hover:bg-[#3a3b3c] transition cursor-pointer flex items-start gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full flex-shrink-0"
                  :class="iconColor(c.channel_name)">
                  <i :class="channelIcon(c.channel_name)"></i>
                </div>

                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-100 leading-tight flex items-center gap-1">
                    {{ c.name }}
                    <span v-if="dayjs(c.start_date).isAfter(dayjs().subtract(1, 'day'))"
                      class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ c.channel_name }} —
                    <span v-if="today.isBefore(dayjs(c.start_date))">
                      Starts {{ dayjs(c.start_date).fromNow() }}
                    </span>
                    <span v-else-if="today.isAfter(dayjs(c.end_date))">
                      Ended {{ dayjs(c.end_date).fromNow() }}
                    </span>
                    <span v-else>
                      Now live
                    </span>
                  </p>
                </div>

                <span class="text-xs text-gray-500 whitespace-nowrap self-center">
                  {{ formatDate(c.start_date) }}
                </span>
              </div>
            </template>

            <template v-if="earlierNotificationsSorted.length">
              <div
                class="px-4 py-2 text-xs uppercase tracking-wide text-gray-400 bg-[#242526] border-b border-gray-800">
                Earlier
              </div>

              <div v-for="c in earlierNotificationsSorted" :key="`old-${c.campaign_id}`"
                class="p-3 hover:bg-[#3a3b3c] transition cursor-pointer flex items-start gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full flex-shrink-0"
                  :class="iconColor(c.channel_name)">
                  <i :class="channelIcon(c.channel_name)"></i>
                </div>

                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-100 leading-tight">
                    {{ c.name }}
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ c.channel_name }} —
                    <span v-if="today.isBefore(dayjs(c.start_date))">
                      Starts {{ dayjs(c.start_date).fromNow() }}
                    </span>
                    <span v-else-if="today.isAfter(dayjs(c.end_date))">
                      Ended {{ dayjs(c.end_date).fromNow() }}
                    </span>
                    <span v-else>
                      Now live
                    </span>
                  </p>
                </div>

                <span class="text-xs text-gray-500 whitespace-nowrap self-center">
                  {{ formatDate(c.start_date) }}
                </span>
              </div>
            </template>

            <div v-if="!filteredNotifications.length" class="p-4 text-center text-sm text-gray-500">
              No notifications yet
            </div>
          </div>

        </div>
      </teleport>

      <main class="flex-1 overflow-auto bg-gray-900">
        <transition name="fade" mode="out-in">
          <router-view v-slot="{ Component }">
            <Suspense>
              <component :is="Component" />
              <template #fallback>
                <div class="p-6 space-y-6">
                  <div v-for="n in 3" :key="n" class="h-6 bg-gray-800 rounded animate-pulse"></div>
                </div>
              </template>
            </Suspense>
          </router-view>
        </transition>
      </main>
    </div>

    <Dialog header="Logging out" :visible.sync="loggingOut" modal closable="false" :dismissable-mask="false">
      <div class="flex items-center gap-2">
        <i class="pi pi-spin pi-spinner text-xl"></i>
        <span>Please wait...</span>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, getCurrentInstance, watch, computed, onMounted, onUnmounted } from "vue";
import { Suspense } from "vue";
import { useRoute, useRouter } from "vue-router";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import { logout } from "@/js/api/login_api.js";
import { useUserStore } from '@/js/utils/user.js';
import { fetchCampaigns } from '@/js/api/campaign_service.js';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

import { useLayout } from '@/js/layouts/composables/layout';

const { toggleDarkMode, isDarkTheme } = useLayout();

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.extend(timezone);

const loggingOut = ref(false);

const userStore = useUserStore();
const notificationsOpen = ref(false);
const campaigns = ref([]);
const today = dayjs();

let timer;

const { appContext } = getCurrentInstance();
const $toastr = appContext.config.globalProperties.$toastr

const route = useRoute();
const router = useRouter();

const sidebarOpen = ref(true);
const calendarMenuOpen = ref(true);
const weeklyMenuOpen = ref(true);
const archiveMenuOpen = ref(true);


const isLoggedIn = ref(!!localStorage.getItem("auth_token"));

const activeTab = ref('All');

const filteredNotifications = computed(() => {
  switch (activeTab.value) {
    case 'Upcoming':
      return upcomingCampaigns.value;
    case 'Active':
      return latestCampaigns.value;
    case 'Completed':
      return endedCampaigns.value;
    default:
      return allNotifications.value;
  }
});

watch(
  () => route.path,
  async () => {
    if (localStorage.getItem("auth_token") && !userStore.name) {
      await userStore.fetchUser();
    }
  },
  { immediate: true }
);

watch(() => route.path, () => {
  calendarMenuOpen.value = true;
  weeklyMenuOpen.value = true;
});

const userName = computed(() => userStore.name);

async function handleLogout() {
  try {
    loggingOut.value = true;

    await logout();
    localStorage.removeItem("auth_token");
    isLoggedIn.value = false;

    router.push("/login");
    $toastr.success("Logged out successfully!");
  } catch (err) {
    $toastr.error("Logout failed.");
  } finally {
    loggingOut.value = false;
    $toastr.success("Logged out successfully!");
  }
}


const newNotifications = computed(() => {
  const cutoff = dayjs().subtract(1, 'day');
  return allNotifications.value.filter(n => dayjs(n.start_date).isAfter(cutoff));
});

const newNotificationsSorted = computed(() =>
  filteredNotifications.value
    .filter(c => dayjs(c.start_date).isAfter(dayjs().subtract(1, 'day')))
    .sort((a, b) => dayjs(b.start_date) - dayjs(a.start_date))
);

const earlierNotificationsSorted = computed(() =>
  filteredNotifications.value
    .filter(c => dayjs(c.start_date).isBefore(dayjs().subtract(1, 'day')))
    .sort((a, b) => dayjs(b.start_date) - dayjs(a.start_date))
);

onMounted(async () => {
  campaigns.value = await fetchCampaigns();
});

const latestCampaigns = computed(() =>
  campaigns.value.filter(c => today.isAfter(dayjs(c.start_date).startOf('day')) && today.isBefore(dayjs(c.end_date).endOf('day')))
);

const upcomingCampaigns = computed(() =>
  campaigns.value.filter(c => today.isBefore(dayjs(c.start_date).startOf('day')))
);

const endedCampaigns = computed(() =>
  campaigns.value.filter(c => today.isAfter(dayjs(c.end_date).endOf('day')))
);

const allNotifications = computed(() => [
  ...latestCampaigns.value,
  ...upcomingCampaigns.value,
  ...endedCampaigns.value
]);

function channelIcon(channel) {
  const map = {
    Edisons: "pi pi-bolt",
    Mytopia: "pi pi-star",
    eBay: "pi pi-shopping-cart",
    BigW: "pi pi-tag",
    Amazon: "pi pi-amazon",
    Kogan: "pi pi-globe",
    "Hot Deals": "pi pi=fire"
  };
  return map[channel] || "pi pi-bullhorn";
}

function iconColor(channel) {
  const map = {
    Facebook: "bg-blue-600/20 text-blue-400",
    Edisons: "bg-yellow-600/20 text-yellow-400",
    Mytopia: "bg-pink-600/20 text-pink-400",
    eBay: "bg-red-600/20 text-red-400",
    BigW: "bg-indigo-600/20 text-indigo-400",
    Amazon: "bg-orange-600/20 text-orange-400",
    Kogan: "bg-green-600/20 text-green-400",
    "Hot Deals": "bg-red-600/20 text-red-400",
  };
  return map[channel] || "bg-gray-700/40 text-gray-300";
}

watch(notificationsOpen, (isOpen) => {
  if (isOpen) {
    setTimeout(() => {
      newNotifications.value.splice(0);
    }, 1500);
  }
});


function formatDate(date) {
  return dayjs(date).format('DD MMM YYYY');
}

let poller = null;

onMounted(() => {
  poller = setInterval(async () => {
    const latest = await fetchCampaigns();
    const oldIds = campaigns.value.map(c => c.campaign_id);
    const newOnes = latest.filter(c => !oldIds.includes(c.campaign_id));

    if (newOnes.length > 0) {
      campaigns.value = latest;

      $toastr.info(`${newOnes.length} new campaign(s) added`);

    }
  }, 10000);
});
onUnmounted(() => {
  clearInterval(timer);
  clearInterval(poller);
});


</script>
<style scoped>
.active-sidebar-link {
  background-color: #374151;
  color: white;
  border-left: 4px solid #3b82f6;
}
</style>
