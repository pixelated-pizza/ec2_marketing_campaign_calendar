# Website Sale Details — Excel import and MySQL snapshots patch

This patch adds:

- Excel `.xlsx` / `.xls` import using ExcelJS.
- Best-effort conversion of cell values, formulas, font/fill/alignment/borders, number formats, merged ranges, row heights/hidden rows, column widths/hidden columns, and frozen panes into Univer workbook data.
- MySQL persistence of the active workbook layout.
- Named snapshots of the workbook data **and layout**, with restore support.
- Existing `useWSDStore` save, Google Sheets pull/push, and CSV import calls retained.

## 1. Front-end dependency

From the Laravel/Vite project root:

```bash
npm install exceljs axios
```

`axios` is often already installed in Laravel/Vite projects; don't install it again if it is already present.

Replace the existing Website Sale Details component with:

`resources/js/components/WebsiteSaleDetails.vue`

Adjust the destination path to match the actual path where your current component lives. The store import in this patch assumes:

```js
import { useWSDStore } from "@/js/stores/wsd_store.js";
```

## 2. Backend files

Copy these files into the matching Laravel paths:

- `app/Models/WebsiteSaleDetailsLayout.php`
- `app/Models/WebsiteSaleDetailsSnapshot.php`
- `app/Http/Controllers/WebsiteSaleDetailsSnapshotController.php`
- both migration files under `database/migrations/`

Add the routes from `routes/api-snippet.php` to `routes/api.php` **inside the same authentication/authorization middleware group as your existing `/api/sheet-mirror/...` routes**. Do not expose this API publicly; the snapshots contain business data.

Run:

```bash
php artisan migrate
php artisan route:list --path=website-sale-details
```

Expected API routes:

- `GET /api/website-sale-details/layout`
- `PUT /api/website-sale-details/layout`
- `GET /api/website-sale-details/snapshots`
- `POST /api/website-sale-details/snapshots`
- `GET /api/website-sale-details/snapshots/{snapshot}`
- `POST /api/website-sale-details/snapshots/{snapshot}/restore`
- `DELETE /api/website-sale-details/snapshots/{snapshot}`

## 3. Start Vite

```bash
npm run dev
```

Open Website Sale Details and import the Excel workbook. The importer searches for a worksheet named `Website Sale Details` (trailing spaces are ignored). If it isn't present, it asks whether to import the first worksheet.

## Workbook behavior and limitations

- The supplied workbook's target worksheet is named `Website Sale Details ` (with a trailing space); the importer normalizes this correctly.
- Excel features are converted into Univer's workbook format. ExcelJS can read many common styles and layout attributes, but some advanced Excel features may not have a direct Univer equivalent. Verify the imported result against Excel before treating it as a perfect visual copy.
- Formulas are carried over where ExcelJS exposes them. External workbook links, macros, pivot tables, embedded objects, and some conditional formatting features are not recreated by this conversion.
- MySQL stores the active workbook JSON separately from the existing `sheet-mirror` table. The existing Pinia mirror continues to hold row-oriented data for the existing Google Sheets workflows.
- Pull from Google Sheets updates cell values while retaining the current local layout where possible; Google Sheets formatting is not imported.
- The snapshot list is limited to the latest 100 snapshots in this starter implementation. Deleting snapshots is exposed as an API endpoint but not wired to a UI button yet.
- The `Delete selected` action intentionally remains a notification because selected-row handling needs to be connected to Univer selection ranges before deleting data safely.

## Before production

1. Test with a copy of the workbook first.
2. Confirm authentication/authorization is applied to all routes.
3. Test import, manual cell edits, reload, snapshot capture, restore, Google Sheets pull, and push.
4. Confirm MySQL `LONGTEXT` and PHP request limits are large enough for the workbook JSON. If large imports hit request-size limits, upload the Excel file to Laravel and perform conversion server-side or increase the appropriate request limits.
5. Consider limiting upload size and snapshot retention according to your team's data-retention policy.
