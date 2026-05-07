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
use App\Http\Controllers\WebsiteSaleDetailsController;
use App\Http\Controllers\ArchivedPromotionController;
use App\Http\Controllers\ArchivedWebsiteSaleController;
use App\Http\Controllers\WebsitePromoController;
use App\Http\Controllers\CategoryFeaturedSkusController;

use App\Http\Controllers\NETOProductController;

Route::get('/user', function (Request $request) {
return $request->user();
})->middleware('auth:sanctum');

Route::get('/heartbeat', function () {
    return response()->json(['status' => 'ok']);
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

Route::prefix('website_sale_details')->group(function() {
    Route::get('/', [WebsiteSaleDetailsController::class, 'index']);     
    Route::post('/', [WebsiteSaleDetailsController::class, 'store']);
    Route::put('/{id}',[WebsiteSaleDetailsController::class, 'update']);
    Route::get('/blank/{wc_id}', [WebsiteSaleDetailsController::class, 'blank']);
    
    Route::post('/image/{wc_id}',   [WebsiteSaleDetailsController::class, 'uploadImage']);
    Route::delete('/image/{wc_id}', [WebsiteSaleDetailsController::class, 'deleteImage']);
});


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

