<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PatientHistoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PrescriptionPdfController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (لا تحتاج إلى تسجيل دخول)
|--------------------------------------------------------------------------
*/

// مسارات المصادقة (Auth)
Route::prefix('auth')->group(function () {
    // تسجيل الدخول عبر جوجل
    Route::get('google', [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('google/redirect-url', [SocialAuthController::class, 'googleRedirectUrl']);
    Route::match(['get', 'post'], 'google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

    // التسجيل وتسجيل الدخول العادي
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
});

// تفعيل البريد الإلكتروني (يجب أن يكون عاماً لكن محمي بـ signed)
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

// عرض بيانات العيادة للعامة (Public كما في البلوبرينت)
Route::get('/clinics/{slug}', [ClinicController::class, 'show']);


/*
|--------------------------------------------------------------------------
| Protected Routes (تحتاج إلى توكن Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {


    // مسارات المصادقة التي تحتاج تسجيل دخول
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // إعادة إرسال إيميل التفعيل
    Route::post('email/verification-notification', [AuthController::class, 'resendEmailVerification']);

    // البروفايل (متاح لجميع الأدوار المسجلة)
    Route::get('/me', [ProfileController::class, 'me']);

    /*
    |----------------------------------------------------------------------
    | Role-Specific Routes (مسارات مخصصة حسب الدور)
    |----------------------------------------------------------------------
    */

    Route::get('/patients/{id}/history', [PatientHistoryController::class, 'show']);

    // مسارات خاصة بالمرضى (Patients) فقط
    Route::middleware('role:patient')->group(function () {

        // AI triage chatbot endpoints
        Route::post('/v1/triage/start', [\App\Modules\AI\Http\Controllers\TriageController::class, 'start']);
        Route::post('/v1/triage/{session}/message', [\App\Modules\AI\Http\Controllers\TriageController::class, 'message']);

        // جلب أطباء العيادة (مخصصة للمريض حسب البلوبرينت)
        Route::get('/clinics/{clinic}/doctors', [ClinicController::class, 'doctors']);

        // جلب مواعيد/فترات الحجز المتاحة لطبيب معين في تاريخ معين
        Route::get('/doctors/{doctor}/slots', [\App\Http\Controllers\Api\DoctorSlotController::class, 'slots']);

        // حجز موعد
        Route::post('/appointments', [\App\Http\Controllers\Api\AppointmentBookingController::class, 'book']);


        Route::post('/invoices/{invoice}/pay', [PaymentController::class, 'pay']);
    });

    // تفاصيل الموعد (Doctor | Patient | Clinic Admin)
    Route::middleware('role:doctor,patient,clinic_admin,super_admin')->group(function () {
        Route::get('/appointments/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'show']);

        // AI triage result polling endpoint
        Route::get('/v1/triage/{session}/result', [\App\Modules\AI\Http\Controllers\TriageController::class, 'result']);

        // إلغاء موعد (Patient | Admin)
        Route::patch('/appointments/{appointment}/cancel', [\App\Http\Controllers\Api\AppointmentController::class, 'cancel']);
    });


    // مسارات خاصة بالأطباء (Doctors) فقط
    Route::middleware('role:doctor')->group(function () {
        Route::post('/medical-records', [\App\Http\Controllers\Api\MedicalRecordController::class, 'store']);
        Route::patch('/medical-records/{id}/sign', [\App\Http\Controllers\Api\MedicalRecordController::class, 'sign']);
        Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    });

    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionPdfController::class, 'download'])->middleware('role:doctor,patient');


    // مسارات خاصة بمدير العيادة (Clinic Admin) فقط
    Route::middleware('role:clinic_admin')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/admin/analytics/overview', [AnalyticsController::class, 'overview']);
    });
});
