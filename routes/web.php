<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\WatchlistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortMapController;
use App\Models\Country;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard.index', ['countries' => Country::all()]);
})->name('dashboard');

Route::get('/supplier-risk', function () {
    return view('supplier.index', ['suppliers' => Supplier::with('country')->orderBy('name')->get()]);
})->name('supplier.risk');

Route::get('/container-tracking', function () {
    return view('container.index');
})->name('container.tracking');

Route::get('/alerts', function () {
    return view('alert.index');
})->name('alerts');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);

Route::middleware('auth')->group(function () {
    Route::get('/watchlist', function () {
        return view('watchlist.index', ['countries' => Country::all()]);
    })->name('watchlist');

    Route::post('/api/watchlist', [WatchlistController::class, 'store'])->name('api.watchlist.store');
    Route::delete('/api/watchlist/{countryId}', [WatchlistController::class, 'destroy'])->name('api.watchlist.destroy');
});

Route::get('/api/watchlist', [WatchlistController::class, 'index'])->name('api.watchlist.index');

Route::get('/currency', function () {
    return view('currency.index', ['countries' => Country::all()]);
})->name('currency');

Route::get('/viz', function () {
    return view('viz.index', ['countries' => Country::all()]);
})->name('viz');

Route::get('/portmap', [PortMapController::class, 'index'])->name('portmap');
Route::get('/api/portmap/ports', [PortMapController::class, 'ports'])->name('portmap.ports');
Route::get('/api/portmap/vessels', [PortMapController::class, 'vessels'])->name('portmap.vessels');
Route::get('/api/portmap/search-vessels', [PortMapController::class, 'searchVessels'])->name('portmap.search-vessels');
Route::get('/api/portmap/vessel-position/{mmsi}', [PortMapController::class, 'vesselPosition'])->name('portmap.vessel-position');
Route::post('/api/portmap/track-vessel/{mmsi}', [PortMapController::class, 'trackVessel'])->name('portmap.track-vessel');
Route::post('/api/portmap/untrack-vessel/{mmsi}', [PortMapController::class, 'untrackVessel'])->name('portmap.untrack-vessel');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/ports', [AdminController::class, 'ports'])->name('ports');
    Route::post('/ports', [AdminController::class, 'storePort'])->name('ports.store');
    Route::delete('/ports/{port}', [AdminController::class, 'destroyPort'])->name('ports.destroy');
    Route::get('/articles', [AdminController::class, 'articles'])->name('articles');
    Route::post('/articles', [AdminController::class, 'storeArticle'])->name('articles.store');
    Route::delete('/articles/{article}', [AdminController::class, 'destroyArticle'])->name('articles.destroy');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/suppliers', [AdminController::class, 'suppliers'])->name('suppliers');
    Route::post('/suppliers', [AdminController::class, 'storeSupplier'])->name('suppliers.store');
    Route::delete('/suppliers/{supplier}', [AdminController::class, 'destroySupplier'])->name('suppliers.destroy');
    Route::get('/containers', [AdminController::class, 'containers'])->name('containers');
    Route::delete('/containers/{container}', [AdminController::class, 'destroyContainer'])->name('containers.destroy');
});

Route::fallback(function () {
    return redirect()->route('dashboard');
});
