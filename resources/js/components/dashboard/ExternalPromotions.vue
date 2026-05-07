<template>
  <div
    class="w-full mt-3 rounded-lg p-3 min-h-[275px]"
    :class="isDark ? 'theme-dark' : 'theme-light'"
    :style="{ background: isDark ? '#000524' : '#ffffff' }"
  >
    <template v-if="loading">
      <div class="flex flex-col h-full gap-4">
        <p class="text-gray-400 text-lg">Loading Gantt Chart...</p>
        <Skeleton height="1.5rem" width="60%" />
        <Skeleton height="1.5rem" width="80%" />
        <Skeleton height="1.5rem" width="40%" />
        <Skeleton height="1.5rem" width="60%" />
        <Skeleton height="1.5rem" width="70%" />
        <Skeleton height="1.5rem" width="90%" />
        <Skeleton height="1.5rem" width="40%" />
        <Skeleton height="1.5rem" width="60%" />
      </div>
    </template>
    <FullCalendar v-else ref="calendarRef" :options="calendarOptions" class="w-full h-full" />
  </div>
</template>
<script setup>
import { ref, onMounted, onActivated, nextTick } from "vue";
import FullCalendar from "@fullcalendar/vue3"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import resourceTimelinePlugin from "@fullcalendar/resource-timeline"
import { fetchWC } from "@/js/api/website_campaign_api.js";
import { fetchChannels } from "@/js/api/campaign_service";
import Skeleton from 'primevue/skeleton';

const channels = ref([]);

const loading = ref(true);

const isDark = ref(false);

const allowedChannels = [
  "Mytopia",
  "Edisons",
  "Hot Deals",
  "Additional Campaigns",
  "Adhoc Promos/Coupons",
];

const calendarOptions = ref({
  schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
  plugins: [resourceTimelinePlugin, dayGridPlugin, interactionPlugin],
  initialView: "resourceTimelineMonth",
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "resourceTimelineMonth",
  },
  resourceAreaHeaderContent: "Sales Channel",
  resources: [],
  resourceOrder: null,
  events: [],
  editable: false,
  selectable: false,
  aspectRatio: 2,
  height: 275,

  slotLabelFormat: [
    { weekday: 'short', day: 'numeric', month: 'short' }
  ],
});

function parseEndDate(dateStr) {
  const d = new Date(dateStr);
  d.setDate(d.getDate() + 1);
  d.setHours(0, 0, 0, 0);
  return d.toISOString().split("T")[0];
}

async function loadCampaigns() {
  try {
    const allChannels = await fetchChannels();
    channels.value = allChannels.filter(
      (c) => !allowedChannels.includes(c.name)
    );

    const priorityOrder = ["eBay", "Amazon", "MyDeal/WMP"];

    channels.value.sort((a, b) => {
      const aPriority = priorityOrder.indexOf(a.name);
      const bPriority = priorityOrder.indexOf(b.name);

      if (aPriority !== -1 && bPriority !== -1) {
        return aPriority - bPriority;
      }
      if (aPriority !== -1) return -1;
      if (bPriority !== -1) return 1;
      return a.name.localeCompare(b.name);
    });

    const externalIds = channels.value.map((c) => c.channel_id);

    calendarOptions.value.resources = channels.value.map((c) => ({
      id: String(c.channel_id),
      title: c.name,
    }));

    const campaigns = await fetchWC();

    const filteredCampaigns = campaigns.filter((c) =>
      externalIds.includes(c.channel_id)
    );

    filteredCampaigns.sort((a, b) => {
      if (a.channel_id !== b.channel_id) {
        return a.channel_id - b.channel_id;
      }
      return new Date(a.start_date) - new Date(b.start_date);
    });

    const adjusted = [];
    const lastEndByChannel = {};

    for (const c of filteredCampaigns) {
      let start = new Date(c.start_date);
      const end = new Date(parseEndDate(c.end_date));

      if (lastEndByChannel[c.channel_id]) {
        const prevEnd = new Date(lastEndByChannel[c.channel_id]);
        if (start.getTime() === prevEnd.getTime()) {
          const lastEvent = adjusted[adjusted.length - 1];
          lastEvent.end = end.toISOString().split("T")[0];

          lastEndByChannel[c.channel_id] = end;
          continue;
        }
      }

      lastEndByChannel[c.channel_id] = end;

      adjusted.push({
        id: String(c.campaign_id),
        resourceId: String(c.channel_id),
        title: c.name,
        start: start.toISOString().split("T")[0],
        end: end.toISOString().split("T")[0],
        backgroundColor: c.background_color || "#3b82f6",
        borderColor: c.background_color || "#3b82f6",
        textColor: "#fff",
      });
    }

    calendarOptions.value.events = adjusted;
  } catch (err) {
    console.error("Failed to load campaigns:", err);
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  const observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains("app-dark");
  });
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"]
  });
  isDark.value = document.documentElement.classList.contains("app-dark");

  await loadCampaigns();
});

onActivated(async () => {
  await nextTick();
  if (calendarRef.value) {
    calendarRef.value.getApi().updateSize();
  }
});
</script>
<style scoped>
/* Dark mode */
:deep(.theme-dark .fc-toolbar-title) {
  font-size: 1.1rem;
  font-weight: 600;
  color: #f9fafb;
}
:deep(.theme-dark .fc-datagrid-cell-main) {
  font-weight: 500;
  color: #f3f4f6;
}
:deep(.theme-dark .fc-timeline-slot-frame) {
  font-weight: 500;
  color: #f3f4f6;
}

/* Light mode */
:deep(.theme-light .fc-toolbar-title) {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
}
:deep(.theme-light .fc-datagrid-cell-main) {
  font-weight: 500;
  color: #111827;
}
:deep(.theme-light .fc-timeline-slot-frame) {
  font-weight: 500;
  color: #111827;
}

.fc-event {
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
  padding: 2px 4px;
}
</style>
