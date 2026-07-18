<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsService
{
    /**
     * Compile comprehensive dashboard overview metrics for a specific clinic.
     */
    public function getOverviewMetrics(string $clinicId): array
    {
        $clinic = Clinic::query()->select(['id', 'name', 'subscription_plan'])->findOrFail($clinicId);

        $today = Carbon::today();
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $chartStart = $today->copy()->subDays(29);

        $todayAppointmentsCount = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->count();

        $monthlyRevenue = Invoice::query()
            ->where('clinic_id', $clinicId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth->copy()->startOfDay(), $now])
            ->sum('amount');

        $newPatientsCount = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->where('appointments.clinic_id', $clinicId)
            ->whereBetween('patients.created_at', [$startOfMonth->copy()->startOfDay(), $now])
            ->distinct('patients.id')
            ->count('patients.id');

        $monthAppointments = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$startOfMonth->toDateString(), $today->toDateString()])
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show_count',
                [AppointmentStatus::NO_SHOW->value],
            )
            ->first();

        $monthAppointmentTotal = (int) ($monthAppointments->total ?? 0);
        $monthNoShowCount = (int) ($monthAppointments->no_show_count ?? 0);
        $noShowRate = $monthAppointmentTotal > 0
            ? round(($monthNoShowCount / $monthAppointmentTotal) * 100, 1)
            : 0.0;

        $noShowRiskDistribution = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', '>=', $today)
            ->selectRaw(
                'COUNT(CASE WHEN no_show_risk >= 0.75 THEN 1 END) as high_risk, COUNT(CASE WHEN no_show_risk >= 0.40 AND no_show_risk < 0.75 THEN 1 END) as medium_risk, COUNT(CASE WHEN no_show_risk < 0.40 THEN 1 END) as low_risk',
            )
            ->first();

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
            ->orderBy('feature')
            ->get();

        $appointmentsToday = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->with(['patient.user', 'doctor.user'])
            ->orderBy('start_time')
            ->limit(8)
            ->get()
            ->map(fn(Appointment $appointment) => $this->mapAppointment($appointment))
            ->values()
            ->all();

        $riskAppointments = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', '>=', $today)
            ->whereNotNull('no_show_risk')
            ->with(['doctor.user'])
            ->orderByDesc('no_show_risk')
            ->orderBy('appointment_date')
            ->limit(3)
            ->get()
            ->map(fn(Appointment $appointment) => $this->mapRiskAppointment($appointment))
            ->values()
            ->all();

        $activeDoctors = Doctor::query()
            ->where('clinic_id', $clinicId)
            ->with('user')
            ->withCount([
                'appointments as todays_appointments_count' => function ($query) use ($today) {
                    $query->whereDate('appointment_date', $today);
                },
            ])
            ->orderByDesc('todays_appointments_count')
            ->orderBy('id')
            ->limit(4)
            ->get()
            ->map(fn(Doctor $doctor) => $this->mapDoctor($doctor))
            ->values()
            ->all();

        $recentInvoices = Invoice::query()
            ->where('clinic_id', $clinicId)
            ->with(['patient.user', 'appointment.doctor.user'])
            ->orderByRaw('COALESCE(paid_at, created_at) DESC')
            ->limit(5)
            ->get()
            ->map(fn(Invoice $invoice) => $this->mapInvoice($invoice))
            ->values()
            ->all();

        return [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'subscription_plan' => $this->enumToString($clinic->subscription_plan),
            ],
            'operational_date' => $today->format('l, F j, Y'),
            'kpis' => [
                'today_appointments' => (int) $todayAppointmentsCount,
                'monthly_revenue' => (float) $monthlyRevenue,
                'new_patients_this_month' => (int) $newPatientsCount,
                'no_show_rate' => $noShowRate,
            ],
            'no_show_risk_summary' => [
                'high' => (int) ($noShowRiskDistribution->high_risk ?? 0),
                'medium' => (int) ($noShowRiskDistribution->medium_risk ?? 0),
                'low' => (int) ($noShowRiskDistribution->low_risk ?? 0),
            ],
            'appointments_today' => $appointmentsToday,
            'no_show_risk_appointments' => $riskAppointments,
            'active_doctors' => $activeDoctors,
            'recent_invoices' => $recentInvoices,
            'appointment_chart' => $this->buildAppointmentChart($clinicId, $chartStart, $today),
            'ai_analytics' => $aiUsageStats->map(function ($log) {
                return [
                    'feature' => (string) $log->feature,
                    'total_tokens' => (int) ((int) $log->total_input_tokens + (int) $log->total_output_tokens),
                    'accumulated_cost' => round((float) $log->total_cost_usd, 4),
                    'avg_latency_ms' => round((float) $log->avg_latency_ms, 0),
                ];
            })->toArray(),
        ];
    }

    private function mapAppointment(Appointment $appointment): array
    {
        $status = $this->enumToString($appointment->status) ?? 'unknown';
        $riskScore = (float) ($appointment->no_show_risk ?? 0);

        return [
            'id' => $appointment->id,
            'time' => $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : null,
            'patient_name' => $appointment->patient?->user?->name ?? 'Unknown patient',
            'doctor_name' => $appointment->doctor?->user?->name ?? 'Unassigned doctor',
            'doctor_specialty' => $appointment->doctor?->specialty ?? 'General',
            'status' => $status,
            'status_label' => Str::headline(str_replace('_', ' ', $status)),
            'risk_score' => (int) round($riskScore * 100),
            'risk_bucket' => $this->riskBucket($riskScore),
        ];
    }

    private function mapRiskAppointment(Appointment $appointment): array
    {
        $riskScore = (float) ($appointment->no_show_risk ?? 0);
        $doctorName = $appointment->doctor?->user?->name ?? 'Unassigned doctor';

        return [
            'id' => $appointment->id,
            'time' => $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : null,
            'patient_name' => $appointment->patient?->user?->name ?? 'Unknown patient',
            'doctor_name' => $doctorName,
            'risk_score' => (int) round($riskScore * 100),
            'risk_percent' => (int) round($riskScore * 100),
            'risk_bucket' => $this->riskBucket($riskScore),
            'risk_label' => Str::headline($this->riskBucket($riskScore) . ' risk'),
        ];
    }

    private function mapDoctor(Doctor $doctor): array
    {
        $name = $doctor->user?->name ?? 'Unknown doctor';

        return [
            'id' => $doctor->id,
            'name' => $name,
            'specialty' => $doctor->specialty ?? 'General',
            'appointment_count' => (int) ($doctor->todays_appointments_count ?? 0),
            'initials' => $this->initials($name),
            'off_today' => (int) ($doctor->todays_appointments_count ?? 0) === 0,
        ];
    }

    private function mapInvoice(Invoice $invoice): array
    {
        $status = $this->enumToString($invoice->status) ?? 'pending';
        $paymentMethod = $this->enumToString($invoice->payment_method) ?? 'cash';
        $appointmentDoctor = $invoice->appointment?->doctor?->user?->name;

        return [
            'id' => $invoice->id,
            'patient_name' => $invoice->patient?->user?->name ?? 'Unknown patient',
            'doctor_name' => $appointmentDoctor ?? 'Unassigned doctor',
            'date' => optional($invoice->paid_at ?? $invoice->created_at)?->toDateTimeString(),
            'amount' => (float) $invoice->amount,
            'payment_method' => $paymentMethod,
            'payment_method_label' => Str::headline(str_replace('_', ' ', $paymentMethod)),
            'status' => $status,
            'status_label' => Str::headline(str_replace('_', ' ', $status)),
        ];
    }

    private function buildAppointmentChart(string $clinicId, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw(
                'DATE(appointment_date) as day, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show',
                [
                    AppointmentStatus::COMPLETED->value,
                    AppointmentStatus::CANCELLED->value,
                    AppointmentStatus::NO_SHOW->value,
                ],
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $completed = [];
        $cancelled = [];
        $noShow = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $dayKey = $day->toDateString();
            $chartRow = $rows->get($dayKey);

            $labels[] = $day->format('m/d');
            $completed[] = (int) ($chartRow->completed ?? 0);
            $cancelled[] = (int) ($chartRow->cancelled ?? 0);
            $noShow[] = (int) ($chartRow->no_show ?? 0);
        }

        return [
            'labels' => $labels,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
        ];
    }

    private function riskBucket(float $riskScore): string
    {
        if ($riskScore >= 0.75) {
            return 'high';
        }

        if ($riskScore >= 0.40) {
            return 'medium';
        }

        return 'low';
    }

    private function enumToString(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return null;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return 'SC';
        }

        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials ?: 'SC';
    }
}
