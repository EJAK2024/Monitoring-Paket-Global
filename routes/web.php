<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PortMapController;
use App\Models\Country;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard.index', ['countries' => Country::all()]);
})->name('dashboard');

Route::get('/watchlist', function () {
    return view('watchlist.index', ['countries' => Country::all()]);
})->name('watchlist');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/ports', [AdminController::class, 'ports'])->name('ports');
    Route::get('/articles', [AdminController::class, 'articles'])->name('articles');
    Route::post('/articles', [AdminController::class, 'storeArticle'])->name('articles.store');
    Route::delete('/articles/{article}', [AdminController::class, 'destroyArticle'])->name('articles.destroy');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
});

Route::get('/portmap', [PortMapController::class, 'index'])->name('portmap');
Route::get('/api/portmap/ports', [PortMapController::class, 'ports'])->name('portmap.ports');

Route::fallback(function () {
    return redirect()->route('dashboard');
});
