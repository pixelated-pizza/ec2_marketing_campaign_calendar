<?php

use App\Http\Controllers\WebsiteSaleDetailsSnapshotController;
use Illuminate\Support\Facades\Route;

/*
 * Add these routes to routes/api.php, inside the SAME authentication /
 * authorization middleware group used by the existing sheet-mirror routes.
 * Do not expose workbook snapshots publicly: they contain business data.
 */
Route::prefix('website-sale-details')->group(function () {
    Route::get('/layout', [WebsiteSaleDetailsSnapshotController::class, 'showLayout']);
    Route::put('/layout', [WebsiteSaleDetailsSnapshotController::class, 'saveLayout']);

    Route::get('/snapshots', [WebsiteSaleDetailsSnapshotController::class, 'indexSnapshots']);
    Route::post('/snapshots', [WebsiteSaleDetailsSnapshotController::class, 'createSnapshot']);
    Route::get('/snapshots/{snapshot}', [WebsiteSaleDetailsSnapshotController::class, 'showSnapshot']);
    Route::post('/snapshots/{snapshot}/restore', [WebsiteSaleDetailsSnapshotController::class, 'restoreSnapshot']);
    Route::delete('/snapshots/{snapshot}', [WebsiteSaleDetailsSnapshotController::class, 'deleteSnapshot']);
});
