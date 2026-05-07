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
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";
import FullCalendar from "@fullcalendar/vue3";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import { fetchWC } from "@/js/api/website_campaign_api.js";
import { fetchChannels } from "@/js/api/campaign_service";
import Skeleton from "primevue/skeleton";
import { useUIStore } from "@/js/stores/ui.js";
const ui = useUIStore();

const calendarRef = ref(null);
const channels = ref([]);
const loading = ref(true);

const isDark = ref(false);

const ganttColors = [
  "#3B82F6", 
  "#10B981", 
  "#F59E0B", 
  "#EF4444", 
  "#8B5CF6", 
  "#06B6D4", 
  "#EC4899", 
  "#84CC16", 
];

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

  resourceAreaHeaderContent: "Website",
  resources: [],
  events: [],

  editable: false,
  selectable: false,
  aspectRatio: 2,
  height: 275,

  slotLabelFormat: [
    { weekday: 'short', day: 'numeric', month: 'short' }
  ],

  eventDidMount(info) {
    const { title, start, end } = info.event;

    const startStr = start.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });

    const endAdj = new Date(end);
    endAdj.setDate(endAdj.getDate() - 1);

    const endStr = endAdj.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });

    const content = `
      <div class="text-sm">
        <strong>${title}</strong><br>
        ${startStr} → ${endStr}
      </div>
    `;

    tippy(info.el, {
      content,
      allowHTML: true,
      theme: "light-border",
      placement: "top",
      delay: [200, 0],
    });
  },
});


const formatLocalDate = (date) => {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

async function loadCampaigns() {
  try {
    const allChannels = await fetchChannels();

    channels.value = allowedChannels
      .map((name) => allChannels.find((c) => c.name === name))
      .filter(Boolean);

    const allowedIds = channels.value.map((c) => c.channel_id);

    calendarOptions.value.resources = channels.value.map((c) => ({
      id: String(c.channel_id),
      title: c.name,
    }));

    const campaigns = await fetchWC();
    const filtered = campaigns.filter((c) => allowedIds.includes(c.channel_id));

    filtered.sort((a, b) => {
      if (a.channel_id !== b.channel_id) return a.channel_id - b.channel_id;
      return new Date(a.start_date) - new Date(b.start_date);
    });

    const channelIndexMap = {};

    const adjusted = filtered.map((c) => {
      if (!(c.channel_id in channelIndexMap)) {
        channelIndexMap[c.channel_id] = 0;
      }

      const index = channelIndexMap[c.channel_id]++;
      const color = ganttColors[index % ganttColors.length];

      const start = new Date(c.start_date);
      const end = new Date(c.end_date);
      end.setDate(end.getDate() + 1);

      return {
        id: String(c.campaign_id),
        resourceId: String(c.channel_id),
        title: c.name,
        start: formatLocalDate(start),
        end: formatLocalDate(end),
        backgroundColor: color,
        borderColor: color,
        textColor: "#ffffff",
      };
    });



    if (adjusted.length > 0) {
      const minStart = new Date(
        Math.min(...adjusted.map((e) => new Date(e.start)))
      );
      const maxEnd = new Date(
        Math.max(...adjusted.map((e) => new Date(e.end)))
      );

      calendarOptions.value.visibleRange = {
        start: formatLocalDate(minStart),
        end: formatLocalDate(maxEnd),
      };

      calendarOptions.value.slotDuration = { days: 1 };
    }

    calendarOptions.value.events = adjusted;

    if (calendarRef.value) {
      const api = calendarRef.value.getApi();
      api.refetchResources();
      api.refetchEvents();
    }
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
  padding: 2px 4px;
}
</style>
