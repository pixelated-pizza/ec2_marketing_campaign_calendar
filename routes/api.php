<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\WebsiteCampaignController;
use App\Http\Controllers\WebsiteCampaignTypeController;
use App\Http\Controllers\CategoryChannelController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArchivedPromotionController;
use App\Http\Controllers\ArchivedWebsiteSaleController;
use App\Http\Controllers\WebsitePromoController;
use App\Http\Controllers\CategoryFeaturedSkusController;
use App\Http\Controllers\UserController;


use App\Http\Controllers\SheetMirrorController;

use App\Http\Controllers\NETOProductController;
use App\Http\Controllers\XlsxImportController;

use App\Http\Controllers\WebsiteSaleDetailsSnapshotController;
use App\Http\Controllers\WeeklyCalendarSnapshotController;

Route::get('/user', function (Request $request) {
return $request->user();
})->middleware('auth:sanctum');

Route::get('/heartbeat', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/firebase', [AuthController::class, 'firebaseLogin']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/get-name', [AuthController::class, 'getUser'])->middleware('auth:sanctum');

Route::prefix('campaigns')->group(function () {    Route::get('/', [CampaignController::class, 'index']);     
    Route::get('/{id}', [CampaignController::class, 'show']);   
    Route::post('/', [CampaignController::class, 'store']);     
    Route::put('/{id}', [CampaignController::class, 'update']); 
    Route::delete('/{id}', [CampaignController::class, 'destroy']); 
});

Route::prefix('category_channels')->group(function () {
    Route::get('/', [CategoryChannelController::class, 'index']);     
    Route::get('/{id}', [CategoryChannelController::class, 'show']);   
});

Route::prefix('website_campaign')->group(function() {
    Route::get('/', [CampaignController::class, 'index']);     
    Route::get('/{id}', [CampaignController::class, 'show']);   
    Route::post('/', [CampaignController::class, 'store']);     
    Route::put('/{id}', [CampaignController::class, 'update']); 
    Route::delete('/{id}', [CampaignController::class, 'destroy']); 
});


Route::prefix('stores')->group(function() {
    Route::get('/', [StoreController::class, 'index']);
});

Route::prefix('sections')->group(function() {
    Route::get('/', [SectionController::class, 'index']);
});

Route::prefix('website_campaign_types')->group(function() {
    Route::get('/', [WebsiteCampaignTypeController::class, 'index']);
});

Route::prefix('onsite_campaign')->group(function() {
    Route::get('/', [WebsiteCampaignController::class, 'index']);     
    Route::get('/{id}', [WebsiteCampaignController::class, 'show']);   
    Route::post('/', [WebsiteCampaignController::class, 'store']);     
    Route::put('/{id}', [WebsiteCampaignController::class, 'update']); 
    Route::delete('/{id}', [WebsiteCampaignController::class, 'destroy']);
    Route::patch('/archive/{id}', [WebsiteCampaignController::class, 'archive_campaign']);
});

// Route::prefix('website_sale_details')->group(function () {
//     Route::get('/', [WebsiteSaleDetailsController::class, 'index']);
//     Route::post('/', [WebsiteSaleDetailsController::class, 'store']);
//     Route::get('/blank', [WebsiteSaleDetailsController::class, 'blank']); // was /blank/{wc_id}
//     Route::delete('/', [WebsiteSaleDetailsController::class, 'destroyAll']);
//     Route::get('/{id}', [WebsiteSaleDetailsController::class, 'show']);
//     Route::put('/{id}', [WebsiteSaleDetailsController::class, 'update']);
//     Route::delete('/{id}', [WebsiteSaleDetailsController::class, 'destroy']);

//     Route::post('/image/{wsd_id}', [WebsiteSaleDetailsController::class, 'uploadImage']);   // was {wc_id}
//     Route::delete('/image/{wsd_id}', [WebsiteSaleDetailsController::class, 'deleteImage']); // was {wc_id}

//     Route::post('/import/preview', [WebsiteSaleDetailsImportController::class, 'preview']);
//     Route::post('/import/commit', [WebsiteSaleDetailsImportController::class, 'commit']);
// });


Route::prefix('archived_promotions')->group(function() {
    Route::get('/', [ArchivedPromotionController::class, 'index']);     
    Route::get('/{id}', [ArchivedPromotionController::class, 'show']);   
    Route::post('/', [ArchivedPromotionController::class, 'store']);     
    Route::put('/{id}', [ArchivedPromotionController::class, 'update']); 
    Route::delete('/{id}', [ArchivedPromotionController::class, 'destroy']);
    Route::put('/unarchive-promo/{id}', [ArchivedPromotionController::class, 'unarchive']); 
});

Route::prefix('archived_website_sales')->group(function() {
    Route::get('/', [ArchivedWebsiteSaleController::class, 'index']);     
    Route::get('/{id}', [ArchivedWebsiteSaleController::class, 'show']);   
    Route::post('/', [ArchivedWebsiteSaleController::class, 'store']);     
    Route::put('/unarchive/{id}', [ArchivedWebsiteSaleController::class, 'unarchive']); 
});

Route::prefix('website_promos')->group(function() {
    Route::get('/', [WebsitePromoController::class, 'index']);     
    Route::get('/{id}', [WebsitePromoController::class, 'show']);   
    Route::post('/', [WebsitePromoController::class, 'store']);     
    Route::put('/{id}', [WebsitePromoController::class, 'update']); 
    Route::delete('/{id}', [WebsitePromoController::class, 'destroy']);
    Route::patch('/archive/{id}', [WebsitePromoController::class, 'archive']);
    // Route::put('/unarchive/{id}', [WebsitePromoController::class, 'unarchive']); 
});

Route::prefix('neto')->group(function () {
    Route::get('products', [NETOProductController::class, 'index']);
});

Route::prefix('category-featured-skus')->group(function () {
    Route::get('/',        [CategoryFeaturedSkusController::class, 'index']);
    Route::post('/',       [CategoryFeaturedSkusController::class, 'store']);
    Route::post('/bulk',   [CategoryFeaturedSkusController::class, 'bulkStore']);
    Route::post('/sync',   [CategoryFeaturedSkusController::class, 'sync']);
    Route::put('/{id}',    [CategoryFeaturedSkusController::class, 'update']);
    Route::delete('/{id}', [CategoryFeaturedSkusController::class, 'destroy']);
    Route::delete('/',     [CategoryFeaturedSkusController::class, 'deleteAll']);
});

Route::prefix('sheet-mirror/{key}')->group(function () {
    Route::get('/', [SheetMirrorController::class, 'index']);
    Route::get('/embed-url', [SheetMirrorController::class, 'embedUrl']);
    Route::post('/pull', [SheetMirrorController::class, 'pull']);
    Route::post('/push', [SheetMirrorController::class, 'push']);
    Route::post('/save', [SheetMirrorController::class, 'save']); // NEW
});

Route::post('/sheet-mirror/{key}/webhook', [SheetMirrorController::class, 'webhook']);

// Excel (.xlsx) imports: upload -> pick sheet/header row -> import, then edit / delete rows
Route::middleware('auth:sanctum')->prefix('xlsx-imports')->group(function () {
    Route::get('/', [XlsxImportController::class, 'index']);
    Route::post('/upload', [XlsxImportController::class, 'upload']);
    Route::get('/upload/{token}/preview', [XlsxImportController::class, 'preview']);
    Route::post('/', [XlsxImportController::class, 'store']);

    Route::get('/{import}', [XlsxImportController::class, 'show'])->whereNumber('import');
    Route::put('/{import}', [XlsxImportController::class, 'update'])->whereNumber('import');
    Route::delete('/{import}', [XlsxImportController::class, 'destroy'])->whereNumber('import');

    Route::post('/{import}/rows', [XlsxImportController::class, 'addRow'])->whereNumber('import');
    Route::post('/{import}/rows/bulk-update', [XlsxImportController::class, 'bulkUpdate'])->whereNumber('import');
    Route::post('/{import}/rows/bulk-delete', [XlsxImportController::class, 'bulkDelete'])->whereNumber('import');
    Route::post('/{import}/sync', [XlsxImportController::class, 'sync'])->whereNumber('import');
});

Route::prefix('website-sale-details')->group(function () {
    Route::get('/layout', [
        WebsiteSaleDetailsSnapshotController::class,
        'showLayout',
    ]);

    Route::put('/layout', [
        WebsiteSaleDetailsSnapshotController::class,
        'saveLayout',
    ]);

    Route::get('/snapshots', [
        WebsiteSaleDetailsSnapshotController::class,
        'indexSnapshots',
    ]);

    Route::post('/snapshots', [
        WebsiteSaleDetailsSnapshotController::class,
        'createSnapshot',
    ]);

    Route::get('/snapshots/{snapshot}', [
        WebsiteSaleDetailsSnapshotController::class,
        'showSnapshot',
    ]);

    Route::post('/snapshots/{snapshot}/restore', [
        WebsiteSaleDetailsSnapshotController::class,
        'restoreSnapshot',
    ]);

    Route::delete('/snapshots/{snapshot}', [
        WebsiteSaleDetailsSnapshotController::class,
        'deleteSnapshot',
    ]);
});

Route::prefix('weekly-calendar')->group(function () {
    Route::get('/layout', [
        WeeklyCalendarSnapshotController::class,
        'showLayout',
    ]);

    Route::put('/layout', [
        WeeklyCalendarSnapshotController::class,
        'saveLayout',
    ]);

    Route::get('/snapshots', [
        WeeklyCalendarSnapshotController::class,
        'indexSnapshots',
    ]);

    Route::post('/snapshots', [
        WeeklyCalendarSnapshotController::class,
        'createSnapshot',
    ]);

    Route::get('/snapshots/{snapshot}', [
        WeeklyCalendarSnapshotController::class,
        'showSnapshot',
    ]);

    Route::post('/snapshots/{snapshot}/restore', [
        WeeklyCalendarSnapshotController::class,
        'restoreSnapshot',
    ]);

    Route::delete('/snapshots/{snapshot}', [
        WeeklyCalendarSnapshotController::class,
        'deleteSnapshot',
    ]);
});
