<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/redirect-url', [SocialAuthController::class, 'googleRedirectUrl']);
Route::match(['get', 'post'], 'auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::middleware('auth:sanctum')->post('email/verification-notification', [AuthController::class, 'resendEmailVerification']);
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
