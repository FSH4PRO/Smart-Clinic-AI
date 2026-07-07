<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class PrescriptionIssueService
{
    public function issue(array $data): Prescription
    {
        return DB::transaction(function () use ($data) {
            /** @var Appointment $appointment */
            $appointment = Appointment::query()->findOrFail($data['appointment_id']);

            // 1. Resolve the medical record ID automatically from the appointment
            $medicalRecordId = MedicalRecord::where('appointment_id', $appointment->id)->value('id');

            if (!$medicalRecordId) {
                throw new \Exception("Cannot issue prescription: No medical record found for this appointment.");
            }

            // 2. Create the prescription header matching your exact migration columns
            $prescription = Prescription::create([
                'medical_record_id' => $medicalRecordId,
                'pharmacy_id'       => $data['pharmacy_id'], // Resolved from request payload
                'doctor_id'         => $appointment->doctor_id,
                'patient_id'        => $appointment->patient_id,
                'status'            => $data['status'] ?? 'issued',
                'notes'             => $data['notes'] ?? null,
            ]);
            try {
                // Reset keys to be completely safe and sequential [0, 1, 2...]
                $items = array_values($data['items']);

                foreach ($items as $item) {
                    $prescription->items()->create([
                        'drug_name'     => $item['drug_name'],
                        'dosage'        => $item['dosage'],
                        'frequency'     => $item['frequency'],
                        'duration_days' => (int) $item['duration_days'], // Enforce integer cast for tinyint
                        'instructions'  => $item['instructions'],
                    ]);
                }
            } catch (QueryException $e) {
                // This will stop execution and print the EXACT database error directly into Postman
                dd([
                    'Error Message' => $e->getMessage(),
                    'SQL Traced'    => $e->getSql(),
                    'Bindings Passed' => $e->getBindings()
                ]);
            } catch (\Exception $e) {
                dd('General Code Error: ' . $e->getMessage());
            }






            return $prescription;
        });
    }
}
