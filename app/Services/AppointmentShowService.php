<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AppointmentShowService
{
    public function getAccessibleAppointment(Appointment $appointment): Appointment
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw new NotFoundHttpException('Appointment not found.');
        }

        $userRole = $user->role;
        if ($userRole instanceof \BackedEnum) {
            $userRole = $userRole->value;
        }

        if ($userRole === 'patient') {
            $patient = $user->patient;
            if (! $patient instanceof Patient || (string) $appointment->patient_id !== (string) $patient->id) {
                throw new NotFoundHttpException('Appointment not found.');
            }
        }

        if ($userRole === 'doctor') {
            $doctor = $user->doctor;
            if (! $doctor instanceof Doctor || (string) $appointment->doctor_id !== (string) $doctor->id) {
                throw new NotFoundHttpException('Appointment not found.');
            }
        }

        // clinic_admin / super_admin can see any appointment

        $appointment->load([
            'patient',
            'doctor',
            'clinic',
            'branch',
            'aiTriageSession',
            'medicalRecord',
            'invoice',
        ]);

        return $appointment;
    }
}
