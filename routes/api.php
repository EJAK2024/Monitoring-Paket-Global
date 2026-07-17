<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\ContainerTrackingController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PortController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\SupplierRiskController;
use App\Http\Controllers\Api\VizController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{id}', [CountryController::class, 'show']);
Route::get('/risk', [RiskController::class, 'index']);
Route::get('/supplier-risk', [SupplierRiskController::class, 'index']);
Route::get('/supplier-risk/{id}', [SupplierRiskController::class, 'show']);
Route::get('/supplier-risk/{id}/history', [SupplierRiskController::class, 'history']);

Route::get('/container', [ContainerTrackingController::class, 'index']);
Route::get('/container/search', [ContainerTrackingController::class, 'search']);
Route::get('/container/stats', [ContainerTrackingController::class, 'stats']);
Route::get('/container/{containerId}', [ContainerTrackingController::class, 'show']);
Route::get('/container/{containerId}/timeline', [ContainerTrackingController::class, 'timeline']);

Route::get('/alerts', [AlertController::class, 'index']);
Route::get('/alerts/unread-count', [AlertController::class, 'unreadCount']);
Route::post('/alerts/{id}/read', [AlertController::class, 'markRead']);
Route::post('/alerts/read-all', [AlertController::class, 'markAllRead']);
Route::delete('/alerts/{id}', [AlertController::class, 'dismiss']);

Route::get('/ports', [PortController::class, 'index']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/currency', [CurrencyController::class, 'index']);

Route::get('/viz/gdp', [VizController::class, 'gdp']);
Route::get('/viz/inflation', [VizController::class, 'inflation']);
Route::get('/viz/currency', [VizController::class, 'currency']);
Route::get('/viz/risk', [VizController::class, 'risk']);

Route::get('/watchlist', [WatchlistController::class, 'index']);
Route::post('/watchlist', [WatchlistController::class, 'store']);
Route::delete('/watchlist/{countryId}', [WatchlistController::class, 'destroy']);
