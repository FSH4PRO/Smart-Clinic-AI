<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Modules\Auth\Controllers\LoginViewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginViewController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginViewController::class, 'authenticate'])->name('login.store');
});

Route::middleware(['auth:web'])->group(function () {
    Route::get('/admin/analytics/overview', [AnalyticsController::class, 'overview'])->name('admin.dashboard');
    Route::post('/logout', [LoginViewController::class, 'logout'])->name('logout');
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
});
