const DAY = 86400000;
const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const MONTHS_SHORT = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const MONTHS_LONG = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

const FIRST_DAY_COL = 2; 
const HEADER_ROWS = 4;
const LANE_HEIGHT = 60;
const MAX_DAYS = 120;

const COLORS = {
    month: "#FFF2CC",
    week: "#00FFFF",
    label: "#CFE2F3",
    section: "#D9D9D9",
    channels: ["#FFE599", "#CFE2F3", "#EAD1DC", "#D9EAD3"],
    line: "#BFBFBF",
    defaultCampaign: "#60A5FA",
};


export function parseDate(v) {
    if (!v) return null;
    if (v instanceof Date) return v;
    const d = new Date(String(v).replace(" ", "T")); 
    return isNaN(d) ? null : d;
}


export function formatDateTime(date) {
    const d = new Date(date);
    const p = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
const dayDiff = (a, b) => Math.round((startOfDay(b) - startOfDay(a)) / DAY);

function weekNumber(d) {
    const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    const day = t.getUTCDay() || 7;
    t.setUTCDate(t.getUTCDate() + 4 - day);
    const yearStart = new Date(Date.UTC(t.getUTCFullYear(), 0, 1));
    return Math.ceil(((t - yearStart) / DAY + 1) / 7);
}

function fmtTime(d) {
    return d.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function textColorFor(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex || "");
    if (!m) return "#FFFFFF";
    const n = parseInt(m[1], 16);
    const lum = (0.299 * ((n >> 16) & 255) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
    return lum > 0.65 ? "#111827" : "#FFFFFF";
}

const border = (color = COLORS.line) => ({
    t: { s: 1, cl: { rgb: color } },
    b: { s: 1, cl: { rgb: color } },
    l: { s: 1, cl: { rgb: color } },
    r: { s: 1, cl: { rgb: color } },
});

const cellStyle = ({ bg, color = "#111827", bold = 0, size = 10, line }) => ({
    ff: "Arial",
    fs: size,
    bl: bold,
    ht: 2,
    vt: 2,
    tb: 3,
    ...(bg ? { bg: { rgb: bg } } : {}),
    cl: { rgb: color },
    bd: border(line),
});

// ---------------------------------------------------------------------------
// Builder
// ---------------------------------------------------------------------------
/**
 * @param {Object}   opts
 * @param {Array}    opts.campaigns  filtered campaigns from your API
 * @param {Array}    opts.channels   channels in display order
 * @param {Date[]}   opts.range      [firstDay, lastDay] shown as columns
 * @returns {{ data: Object, lookup: Object }} workbook data + click lookup
 */
export function buildCampaignSheet({ campaigns, channels, range }) {
    const rangeStart = startOfDay(range[0]);
    const rangeEnd = startOfDay(range[1]);
    const nDays = Math.max(1, Math.min(dayDiff(rangeStart, rangeEnd) + 1, MAX_DAYS));
    const days = Array.from(
        { length: nDays },
        (_, i) => new Date(rangeStart.getFullYear(), rangeStart.getMonth(), rangeStart.getDate() + i),
    );

    const cellData = {};
    const mergeData = [];
    const rowData = {};
    const columnData = { 0: { w: 190 }, 1: { w: 170 } };
    // "row:col" -> what a click on that cell means (campaign to edit, channel to add to)
    const lookup = {};

    const put = (r, c, cell) => {
        if (!cellData[r]) cellData[r] = {};
        cellData[r][c] = cell;
    };

    // Fill a rectangular range with one style, label only the top-left cell, merge if bigger than 1 cell
    const block = (r1, c1, r2, c2, value, style) => {
        for (let r = r1; r <= r2; r++) {
            for (let c = c1; c <= c2; c++) {
                put(r, c, r === r1 && c === c1 ? { v: value, s: style } : { s: style });
            }
        }
        if (r2 > r1 || c2 > c1) {
            mergeData.push({ startRow: r1, endRow: r2, startColumn: c1, endColumn: c2 });
        }
    };

    for (let r = 0; r < HEADER_ROWS; r++) rowData[r] = { h: 22 };

    // ----- header rows ------------------------------------------------------
    const groupDays = (keyFn, row, labelFn, style) => {
        let i = 0;
        while (i < nDays) {
            let j = i;
            const key = keyFn(days[i]);
            while (j + 1 < nDays && keyFn(days[j + 1]) === key) j++;
            block(row, FIRST_DAY_COL + i, row, FIRST_DAY_COL + j, labelFn(days[i]), style);
            i = j + 1;
        }
    };

    groupDays(
        (d) => `${d.getFullYear()}-${d.getMonth()}`,
        0,
        (d) => MONTHS_LONG[d.getMonth()],
        cellStyle({ bg: COLORS.month, bold: 1 }),
    );
    groupDays(
        (d) => weekNumber(d),
        1,
        (d) => `Week ${weekNumber(d)}`,
        cellStyle({ bg: COLORS.week, bold: 1 }),
    );

    const plain = cellStyle({ bg: "#FFFFFF", size: 9 });
    days.forEach((d, i) => {
        columnData[FIRST_DAY_COL + i] = { w: 74 };
        put(2, FIRST_DAY_COL + i, { v: `${d.getDate()}-${MONTHS_SHORT[d.getMonth()]}`, s: plain });
        put(3, FIRST_DAY_COL + i, { v: WEEKDAYS[d.getDay()], s: plain });
    });

    const headerLabel = cellStyle({ bg: COLORS.label, bold: 1 });
    block(0, 0, 2, 1, "", cellStyle({ bg: "#FFFFFF" }));
    put(3, 0, { v: "SECTIONS", s: headerLabel });
    put(3, 1, { v: "SPECIFICS", s: headerLabel });


    // ----- campaign rows grouped by section -------------------------------
    let row = HEADER_ROWS;

    // Display sections in this order. Unknown sections appear afterward.
    const preferredSectionOrder = [
        "Website Campaigns",
        "Brand Sites: Valk, Equipmed, Baumr-AG, Medihub",
        "eBay and Other Resellers",
    ];

    // Group channels using the existing database section_name field.
    const groupedChannels = new Map();

    channels.forEach((ch) => {
        const sectionName = ch.section_name || "Other";

        if (!groupedChannels.has(sectionName)) {
            groupedChannels.set(sectionName, []);
        }

        groupedChannels.get(sectionName).push(ch);
    });

    const sortedSections = [...groupedChannels.entries()].sort(
        ([sectionA], [sectionB]) => {
            const indexA = preferredSectionOrder.indexOf(sectionA);
            const indexB = preferredSectionOrder.indexOf(sectionB);

            if (indexA !== -1 && indexB !== -1) {
                return indexA - indexB;
            }

            if (indexA !== -1) return -1;
            if (indexB !== -1) return 1;

            return sectionA.localeCompare(sectionB);
        }
    );

    let channelColorIndex = 0;

    sortedSections.forEach(([sectionName, sectionChannels]) => {
        const sectionStartRow = row;

        // Sort specifics within each section using the database sort_order.
        sectionChannels.sort(
            (a, b) =>
                Number(a.sort_order ?? 999999) -
                Number(b.sort_order ?? 999999)
        );

        sectionChannels.forEach((ch) => {
            const items = campaigns
                .filter(
                    (c) =>
                        String(c.channel_id) === String(ch.channel_id)
                )
                .map((c) => ({
                    c,
                    s: parseDate(c.start_date),
                    e: parseDate(c.end_date),
                }))
                .filter((x) => x.s && x.e)
                .map((x) => ({
                    ...x,
                    si: Math.max(0, dayDiff(rangeStart, x.s)),
                    ei: Math.min(nDays - 1, dayDiff(rangeStart, x.e)),
                }))
                .filter(
                    (x) =>
                        x.ei >= 0 &&
                        x.si <= nDays - 1 &&
                        x.ei >= x.si
                )
                .sort((a, b) => a.si - b.si || a.ei - b.ei);

            // Keep overlapping campaigns on separate lanes.
            const laneEnds = [];

            const placed = items.map((x) => {
                let lane = laneEnds.findIndex(
                    (end) => end < x.si
                );

                if (lane === -1) {
                    lane = laneEnds.length;
                    laneEnds.push(x.ei);
                } else {
                    laneEnds[lane] = x.ei;
                }

                return { ...x, lane };
            });

            const laneCount = Math.max(1, laneEnds.length);
            const firstRow = row;

            for (let l = 0; l < laneCount; l++) {
                rowData[firstRow + l] = { h: LANE_HEIGHT };
            }

            placed.forEach(({ c, s, e, si, ei, lane }) => {
                const bg = c.background_color || COLORS.defaultCampaign;

                block(
                    firstRow + lane,
                    FIRST_DAY_COL + si,
                    firstRow + lane,
                    FIRST_DAY_COL + ei,
                    `${c.name}\n${fmtTime(s)} – ${fmtTime(e)}`,
                    cellStyle({
                        bg,
                        color: textColorFor(bg),
                        size: 9,
                        line: "#FFFFFF",
                    })
                );

                for (
                    let col = FIRST_DAY_COL + si;
                    col <= FIRST_DAY_COL + ei;
                    col++
                ) {
                    lookup[`${firstRow + lane}:${col}`] = {
                        type: "campaign",
                        id: String(c.campaign_id),
                    };
                }
            });

            // Column B: merge the channel name across its lanes.
            block(
                firstRow,
                1,
                firstRow + laneCount - 1,
                1,
                ch.name,
                cellStyle({
                    bg: COLORS.channels[
                        channelColorIndex % COLORS.channels.length
                    ],
                    bold: 1,
                })
            );

            channelColorIndex++;

            for (let l = 0; l < laneCount; l++) {
                lookup[`${firstRow + l}:1`] = {
                    type: "channel",
                    channelId: String(ch.channel_id),
                };
            }

            row += laneCount;
        });

        // Column A: merge only the rows belonging to this section.
        if (row > sectionStartRow) {
            block(
                sectionStartRow,
                0,
                row - 1,
                0,
                sectionName,
                cellStyle({
                    bg: COLORS.section,
                    bold: 1,
                })
            );
        }
    });


    const data = {
        id: "campaign-calendar",
        name: "Campaign Calendar",
        appVersion: "3.0.0-alpha",
        locale: "enUS",
        styles: {},
        sheetOrder: ["main"],
        sheets: {
            main: {
                id: "main",
                name: "Weekly Calendar",
                rowCount: row + 3,
                columnCount: FIRST_DAY_COL + nDays + 2,
                defaultColumnWidth: 74,
                defaultRowHeight: 22,
                showGridlines: 1,
                freeze: { xSplit: FIRST_DAY_COL, ySplit: HEADER_ROWS, startRow: HEADER_ROWS, startColumn: FIRST_DAY_COL },
                columnData,
                rowData,
                mergeData,
                cellData,
            },
        },
    };

    return { data, lookup };
}