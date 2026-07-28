<template>
  <div class="apex-wrapper p-5" :style="{ background: isDark ? '#000524' : '#ffffff' }">
    <div class="range-toggle mb-3">
      <button v-for="opt in rangeOptions" :key="opt.value" class="range-btn"
        :class="{ active: selectedRange === opt.value }" :style="rangeBtnStyle(opt.value)"
        @click="selectedRange = opt.value">
        {{ opt.label }}
      </button>
    </div>

    <apexchart v-if="chartReady" type="bar" height="300" :options="chartOptions" :series="series" />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch, nextTick } from "vue";

const isDark = ref(false);
const chartReady = ref(true);

const props = defineProps({
  campaigns: {
    type: Array,
    required: true
  }
});

const rangeOptions = [
  { label: "6 months", value: 6 },
  { label: "12 months", value: 12 },
  // { label: "All", value: "all" }
];
const selectedRange = ref(12);

function rangeBtnStyle(value) {
  const active = selectedRange.value === value;
  return {
    background: active ? "#00BAEC" : "transparent",
    color: active ? "#fff" : isDark.value ? "#d1d5db" : "#374151",
    border: `1px solid ${active ? "#00BAEC" : isDark.value ? "#374151" : "#e5e7eb"}`
  };
}

function buildCompletedCampaignCount(campaigns) {
  const map = {};
  const now = new Date();

  campaigns.forEach((c, index) => {
    if (!c.end_date) return;

    const start = c.start_date ? new Date(c.start_date) : null;
    const end = new Date(c.end_date);

    // 1. Exclude FUTURE campaigns (haven't started yet)
    if (start && start > now) return;

    // 2. Exclude CURRENTLY RUNNING campaigns (started, but haven't ended yet)
    if (end >= now) return; 

    // If it passed both checks above, the campaign is COMPLETED (end < now)

    const monthKey = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, "0")}`;

    if (!map[monthKey]) {
      map[monthKey] = { campaigns: {} };
    }

    const name = c.name || `Campaign ${c.id ?? index}`;
    const channelName =
      typeof c.channel === "string"
        ? c.channel
        : c.channel?.name || "Unknown";

    if (!map[monthKey].campaigns[name]) {
      map[monthKey].campaigns[name] = new Set();
    }

    map[monthKey].campaigns[name].add(channelName);
  });

  return Object.entries(map)
    .sort(([a], [b]) => new Date(a + "-01") - new Date(b + "-01"))
    .map(([month, { campaigns }]) => {
      const campaignList = Object.entries(campaigns).map(
        ([name, channels]) => {
          const channelStr = Array.from(channels).join(" & ");
          return channels.size > 1 ? `${name} - ${channelStr}` : name;
        }
      );

      return {
        x: new Date(month + "-01").toLocaleString("default", {
          month: "short",
          year: "numeric"
        }),
        y: Object.keys(campaigns).length,
        campaigns: campaignList
      };
    });
}

const allProcessed = ref([]);
const series = ref([{ name: "Completed Campaigns", data: [] }]);

// Slice the processed data to the selected range and refresh series
const applyRange = () => {
  const data =
    selectedRange.value === "all"
      ? allProcessed.value
      : allProcessed.value.slice(-selectedRange.value);
  series.value = [{ name: "Completed Campaigns", data }];
};

watch(selectedRange, applyRange);

async function forceChartRemount() {
  chartReady.value = false;
  await nextTick();
  chartReady.value = true;
}

onMounted(() => {
  const observer = new MutationObserver(async () => {
    const dark = document.documentElement.classList.contains("app-dark");
    if (isDark.value !== dark) {
      isDark.value = dark;
      await forceChartRemount();
    }
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"]
  });

  isDark.value = document.documentElement.classList.contains("app-dark");
});

const chartOptions = computed(() => {
  const textColor = isDark.value ? "#ffffff" : "#111827";
  const gridColor = isDark.value ? "#374151" : "#e5e7eb";
  const tooltipBg = isDark.value ? "#1f2937" : "#f3f4f6";
  const tooltipBorder = isDark.value ? "#374151" : "#e5e7eb";
  const tooltipText = isDark.value ? "#fff" : "#111827";

  // fewer bars visible -> wider columns; more bars -> narrower
  const barCount = series.value[0]?.data?.length || 1;
  const columnWidth = barCount <= 12 ? "40%" : barCount <= 24 ? "55%" : "70%";

  return {
    chart: {
      type: "bar",
      foreColor: textColor,
      toolbar: {
        show: true,
        tools: { download: false, selection: false, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true }
      },
      zoom: { enabled: true, type: "x" },
      background: "transparent"
    },

    plotOptions: {
      bar: { borderRadius: 6, horizontal: false, columnWidth }
    },

    dataLabels: { enabled: false },
    colors: ["#00BAEC"],

    xaxis: {
      type: "category",
      title: { text: "Month", style: { color: textColor } },
      labels: {
        style: { colors: textColor, fontSize: "12px" },
        rotate: -45,
        rotateAlways: barCount > 8,
        trim: true,
        hideOverlappingLabels: true
      }
    },

    yaxis: {
      min: 0,
      forceNiceScale: true,
      title: { text: "Completed Campaigns", style: { color: textColor } },
      labels: {
        style: { colors: textColor },
        formatter: (val) => Math.round(val)
      }
    },

    tooltip: {
      enabled: true,
      shared: false,
      followCursor: true,
      custom: ({ series, seriesIndex, dataPointIndex, w }) => {
        const dp = w.config.series[seriesIndex].data[dataPointIndex];
        const campaignList = dp.campaigns
          .map(name => `<li>${name}</li>`)
          .join("");

        return `
      <div style="
        padding: 10px 12px;
        color: ${tooltipText};
        background: ${tooltipBg};
        border-radius: 6px;
        font-size: 13px;
        border: 1px solid ${tooltipBorder};
        max-width: 320px;
        white-space: normal;
        word-wrap: break-word;
      ">
        <strong>Count: ${dp.y}</strong>
        <ul style="
          margin: 6px 0 0 15px;
          padding: 0;
          list-style-type: disc;
          line-height: 1.5;
        ">
          ${campaignList}
        </ul>
      </div>
    `;
      }
    },

    grid: { borderColor: gridColor },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: { height: 300 },
          xaxis: { labels: { fontSize: "10px" } }
        }
      }
    ]
  };
});

watch(
  () => props.campaigns,
  async (newVal) => {
    if (!newVal || !newVal.length) return;
    await nextTick();
    setTimeout(() => {
      allProcessed.value = buildCompletedCampaignCount(newVal);
      applyRange();
    }, 0);
  },
  { immediate: true }
);
</script>

<style scoped>
:global(.app-dark) .apex-wrapper {
  background: #000524;
}

.range-toggle {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.range-btn {
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.range-btn:hover {
  opacity: 0.85;
}
</style>