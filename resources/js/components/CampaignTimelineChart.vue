<template>
  <div
    class="apex-wrapper mt-5 p-5"
    :style="{ background: isDark ? '#000524' : '#ffffff' }"
  >
    <apexchart
      v-if="chartReady"
      type="bar"
      height="200"
      :options="chartOptions"
      :series="series"
    />
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

function buildCompletedCampaignCount(campaigns) {
  const map = {};

  campaigns.forEach((c, index) => {
    if (!c.end_date) return;

    const end = new Date(c.end_date);
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

const series = ref([{ name: "Completed Campaigns", data: [] }]);

// Force chart remount on theme change so ApexCharts picks up new options
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

  return {
    chart: {
      type: "bar",
      foreColor: textColor, // ← adapts axis labels, legend text
      toolbar: { show: false },
      zoom: { enabled: true },
      background: "transparent"
    },

    title: {
      text: "Website Sales or Promotions Currently Running or Completed Per Month",
      align: "center",
      style: {
        fontSize: "16px",
        fontWeight: "600",
        color: textColor // ← was hardcoded #ffffff
      }
    },

    plotOptions: {
      bar: {
        borderRadius: 6,
        horizontal: false,
        columnWidth: "50%"
      }
    },

    dataLabels: { enabled: false },
    colors: ["#00BAEC"],

    xaxis: {
      type: "category",
      title: {
        text: "Month",
        style: { color: textColor }
      },
      labels: { style: { colors: textColor } }
    },

    yaxis: {
      min: 0,
      title: {
        text: "Completed Campaigns",
        style: { color: textColor }
      },
      labels: { style: { colors: textColor } }
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
            padding: 8px;
            color: ${tooltipText};
            background: ${tooltipBg};
            border-radius: 6px;
            font-size: 13px;
            border: 1px solid ${tooltipBorder};
          ">
            <strong>Count: ${dp.y}</strong>
            <ul style="margin: 5px 0 0 15px; padding: 0; list-style-type: disc;">
              ${campaignList}
            </ul>
          </div>
        `;
      }
    },

    grid: { borderColor: gridColor }
  };
});

watch(
  () => props.campaigns,
  async (newVal) => {
    if (!newVal || !newVal.length) return;
    await nextTick();
    setTimeout(() => {
      const processed = buildCompletedCampaignCount(newVal);
      series.value = [{ name: "Completed Campaigns", data: processed }];
    }, 0);
  },
  { immediate: true }
);
</script>
<style scoped>
:global(.app-dark) .apex-wrapper {
  background: #000524; /* dark mode */
}
</style>