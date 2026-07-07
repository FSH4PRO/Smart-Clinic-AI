<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinic;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia; // 1. استيراد كلاس الـ Inertia

class AnalyticsController extends BaseController
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Retrieve global operational KPI metrics for the dashboard overview.
     */
    public function overview() // 2. أزلنا الـ JsonResponse لأن الإرجاع قد يكون Inertia Response الآن
    {
        $user = Auth::user();
        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        // 1. Enforce strict Admin-only authorization gates
        if ($userRole !== 'clinic_admin' && $userRole !== 'super_admin') {
            if (request()->header('X-Inertia')) {
                return abort(403, 'Unauthorized access.');
            }
            return $this->errorResponse(null, 'Unauthorized access: Only clinic admins or super admins can view analytics.', 403);
        }

        // 2. Resolve Clinic ID based on Blueprint relationships
        $clinicId = request()->header('X-Clinic-ID') ?? request()->query('clinic_id');

        if (!$clinicId && $userRole === 'clinic_admin') {
            $clinicId = Clinic::where('owner_id', $user->id)->value('id');
        }

        // 3. Prevent execution if no context is found
        if (!$clinicId) {
            if (request()->header('X-Inertia')) {
                return abort(400, 'Clinic context could not be resolved.');
            }
            return $this->errorResponse(null, 'Clinic context could not be resolved. Please provide a valid clinic ID.', 400);
        }

        // 4. Process calculations via service domain layers
        $data = $this->analyticsService->getOverviewMetrics($clinicId);

        // 5. الحل السحري: إذا كان الطلب قادماً من Inertia (المتصفح)
        if (request()->header('X-Inertia')) {
            return Inertia::render('<Admin/Dashboard', [
                'kpis' => $data['kpis'] ?? [],
                'noShowRiskSummary' => $data['no_show_risk_summary'] ?? [],
                'aiAnalytics' => $data['ai_analytics'] ?? [],
            ]);
        }

        // إذا كان الطلب عادياً (تطبيق الموبايل / Flutter) يعود الـ JSON الأصلي كما هو
        return $this->successResponse($data, 'Operational KPI metrics retrieved successfully.');
    }
}
