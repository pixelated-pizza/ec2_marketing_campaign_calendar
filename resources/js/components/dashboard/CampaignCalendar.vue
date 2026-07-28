<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700/70 shadow-sm">
    <FullCalendar :options="calendarOptions" />
  </div>
</template>

<script setup>
import { computed } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

const props = defineProps({
  campaigns: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["eventClick"]);

const formattedEvents = computed(() => {
  return props.campaigns.map((c) => ({
    id: c.id || c.name,
    title: c.channel_name ? `${c.name} - ${c.channel_name}` : c.name,
    start: c.start_date,
    end: c.end_date,
    allDay: true,
    backgroundColor: getCampaignColor(c),
    borderColor: "transparent",
    extendedProps: { ...c },
  }));
});

function getCampaignColor(campaign) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const start = new Date(campaign.start_date);
  const end = new Date(campaign.end_date);

  if (today >= start && today <= end) return "#10b981"; // Active: Emerald
  if (start > today) return "#3b82f6"; // Upcoming: Blue
  return "#6b7280"; // Completed: Gray
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "",
  },
  events: formattedEvents.value,
  height: "auto",
  editable: false,
  selectable: true,
  eventClick: (info) => {
    emit("eventClick", info.event.extendedProps);
  },
}));
</script>

<style>
/* Style overrides to match Google Calendar aesthetic & Dark Mode */
.fc {
  --fc-border-color: #e5e7eb;
  --fc-button-bg-color: #3b82f6;
  --fc-button-border-color: #3b82f6;
  --fc-button-hover-bg-color: #2563eb;
  font-family: inherit;
}

.app-dark .fc {
  --fc-border-color: #374151;
  --fc-page-bg-color: #1f2937;
  --fc-neutral-bg-color: #1f2937;
  --fc-daygrid-bg-color: #1f2937;
  --fc-list-event-hover-bg-color: #374151;
  color: #f3f4f6;
}

.dark .fc .fc-col-header-cell,
.dark .fc .fc-daygrid-day {
  background-color: #1f2937 !important;
}

.dark .fc .fc-col-header-cell-cushion {
  color: #9ca3af;
}

.dark .fc .fc-day-today {
  background-color: #374151 !important;
}

.fc .fc-toolbar-title {
  font-size: 1.125rem;
  font-weight: 700;
}

.fc .fc-button {
  border-radius: 0.5rem;
  font-weight: 500;
  text-transform: capitalize;
  padding: 0.4rem 0.8rem;
}

.fc-event {
  border-radius: 6px;
  padding: 2px 6px;
  font-size: 0.825rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
</style>