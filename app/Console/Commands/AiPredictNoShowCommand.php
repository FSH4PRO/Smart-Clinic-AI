<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class AiPredictNoShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:predict-noshow';

    /**
     * The console command description.
     */
    protected $description = 'Predict no-show risk for upcoming appointments and persist into appointments.no_show_risk';

    public function handle(): int
    {
        $now = Carbon::now();
        $to = $now->copy()->addHours(48);

        $appointments = Appointment::query()
            ->whereBetween('appointment_date', [$now->toDateString(), $to->toDateString()])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->with('patient')
            ->get();

        $total = $appointments->count();
        $updated = 0;

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;

            $risk = $this->calculateRisk($appointment, $patient);

            $appointment->update([
                'no_show_risk' => $risk,
            ]);

            $updated++;
        }

        Log::info('AiPredictNoShowCommand finished', [
            'total' => $total,
            'updated' => $updated,
            'from' => $now->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $this->info(sprintf('Updated no_show_risk for %d appointments.', $updated));

        return self::SUCCESS;
    }

    /**
     * Calculates a deterministic risk probability between 0.00 and 1.00.
     *
     * Feature vector (heuristic):
     * - Past cancellations/no-shows
     * - Appointment day of week
     * - Lead time in days
     * - Appointment type
     */
    private function calculateRisk(Appointment $appointment, ?object $patient): float
    {
        // Past attendance history (very defensive; model doesn't include explicit attendance enum)
        $patientId = $patient?->id;
        if (! $patientId) {
            return 0.20;
        }

        $history = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereNotNull('cancelled_at')
            ->orWhere('status', 'no_show')
            ->get();

        $pastCount = max(1, $history->count());
        $noShowCount = (float) Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', 'no_show')
            ->count();

        $cancelCount = (float) Appointment::query()
            ->where('patient_id', $patientId)
            ->whereNotNull('cancelled_at')
            ->count();

        $base = min(1.0, 0.05 + 0.40 * ($noShowCount / $pastCount) + 0.20 * ($cancelCount / $pastCount));

        $appointmentDateTime = Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $appointment->start_time);
        $leadDays = max(0.0, $appointmentDateTime->diffInDays(Carbon::now()));

        // Longer lead time slightly increases risk.
        $leadFactor = min(0.20, 0.02 * $leadDays);

        // Day of week effect.
        $day = (int) $appointmentDateTime->dayOfWeekIso; // 1..7
        $weekdayFactor = in_array($day, [6, 7], true) ? 0.08 : 0.0;

        $typeValue = (string) $appointment->type?->value;
        $typeFactor = $typeValue === 'home_visit' ? 0.05 : 0.0;

        $risk = $base + $leadFactor + $weekdayFactor + $typeFactor;

        return round(max(0.00, min(1.00, $risk)), 2);
    }
}

