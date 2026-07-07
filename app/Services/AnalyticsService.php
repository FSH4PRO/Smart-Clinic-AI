<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Compile comprehensive dashboard overview metrics for a specific clinic.
     */
    public function getOverviewMetrics(string $clinicId): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. KPI Cards data
        $todayAppointmentsCount = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            // ->whereNull('deleted_at') // Soft delete safety
            ->count();

        $monthlyRevenue = DB::table('invoices')
            ->where('clinic_id', $clinicId)
            ->where('status', 'paid') // invoice status enum matching schema
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('amount');

        $newPatientsCount = DB::table('patients')
            ->join('users', 'patients.user_id', '=', 'users.id')
            ->whereMonth('patients.created_at', Carbon::now()->month)
            ->whereYear('patients.created_at', Carbon::now()->year)
            ->whereNull('users.deleted_at')
            ->count();

        // 2. AI No-Show Risk Distribution 
        // Counts appointments categorized by high, medium, and low probability thresholds
        $noShowRiskDistribution = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', '>=', $today)
            // ->whereNull('deleted_at')
            ->select(DB::raw("
                COUNT(CASE WHEN no_show_risk >= 0.75 THEN 1 END) as high_risk,
                COUNT(CASE WHEN no_show_risk >= 0.40 AND no_show_risk < 0.75 THEN 1 END) as medium_risk,
                COUNT(CASE WHEN no_show_risk < 0.40 THEN 1 END) as low_risk
            "))
            ->first();

        // 3. AI Usage Analytics (Token consumption and total USD spend cost)
        $aiUsageStats = DB::table('ai_usage_logs')
            ->where('clinic_id', $clinicId)
            ->select(
                'feature',
                DB::raw('SUM(input_tokens) as total_input_tokens'),
                DB::raw('SUM(output_tokens) as total_output_tokens'),
                DB::raw('SUM(cost_usd) as total_cost_usd'),
                DB::raw('AVG(duration_ms) as avg_latency_ms')
            )
            ->groupBy('feature')
            ->get();

        return [
            'kpis' => [
                'today_appointments' => (int) $todayAppointmentsCount,
                'monthly_revenue'    => (float) $monthlyRevenue,
                'new_patients_this_month' => (int) $newPatientsCount,
            ],
            'no_show_risk_summary' => [
                'high'   => (int) ($noShowRiskDistribution->high_risk ?? 0),
                'medium' => (int) ($noShowRiskDistribution->medium_risk ?? 0),
                'low'    => (int) ($noShowRiskDistribution->low_risk ?? 0),
            ],
            'ai_analytics' => $aiUsageStats->map(function ($log) {
                return [
                    'feature'        => $log->feature, // triage | soap_draft | drug_check | no_show_pred
                    'total_tokens'   => (int) ($log->total_input_tokens + $log->total_output_tokens),
                    'accumulated_cost' => round((float) $log->total_cost_usd, 4),
                    'avg_latency_ms' => round((float) $log->avg_latency_ms, 0),
                ];
            })->toArray()
        ];
    }
}
