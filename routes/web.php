<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Modules\Auth\Controllers\LoginViewController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginViewController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginViewController::class, 'authenticate'])->name('login.store');
});

Route::middleware(['auth:web'])->group(function () {
    Route::get('/admin/analytics/overview', [AnalyticsController::class, 'overview'])->name('admin.analytics.overview');
    Route::get('/admin/dashboard', [AnalyticsController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [LoginViewController::class, 'logout'])->name('logout');
    Route::get('/', [AnalyticsController::class, 'dashboard'])->name('dashboard');
});
