<?php

namespace App\Http\Controllers\Api;

use App\Models\Clinic;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnalyticsController extends BaseController
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    /**
     * Render the admin dashboard page for web users.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if ($userRole !== 'clinic_admin' && $userRole !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $clinicId = request()->header('X-Clinic-ID') ?? request()->query('clinic_id');

        if (! $clinicId && $userRole === 'clinic_admin') {
            $clinicId = Clinic::query()->where('owner_id', $user->id)->value('id');
        }

        if (! $clinicId) {
            abort(400, 'Clinic context could not be resolved.');
        }

        return Inertia::render('Admin/Dashboard', $this->analyticsService->getOverviewMetrics($clinicId));
    }

    /**
     * Retrieve global operational KPI metrics for the dashboard overview.
     */
    public function overview()
    {
        $user = Auth::user();
        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if ($userRole !== 'clinic_admin' && $userRole !== 'super_admin') {
            if (request()->header('X-Inertia')) {
                abort(403, 'Unauthorized access.');
            }

            return $this->errorResponse(null, 'Unauthorized access: Only clinic admins or super admins can view analytics.', 403);
        }

        $clinicId = request()->header('X-Clinic-ID') ?? request()->query('clinic_id');

        if (! $clinicId && $userRole === 'clinic_admin') {
            $clinicId = Clinic::query()->where('owner_id', $user->id)->value('id');
        }

        if (! $clinicId) {
            if (request()->header('X-Inertia')) {
                abort(400, 'Clinic context could not be resolved.');
            }

            return $this->errorResponse(null, 'Clinic context could not be resolved. Please provide a valid clinic ID.', 400);
        }

        $data = $this->analyticsService->getOverviewMetrics($clinicId);

        return $this->successResponse($data, 'Operational KPI metrics retrieved successfully.');
    }
}
