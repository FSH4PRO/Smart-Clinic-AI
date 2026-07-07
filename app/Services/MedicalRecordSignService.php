<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicalRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MedicalRecordSignService
{
    /**
     * Finalize a medical record by setting signed_at and switching it off draft mode.
     * Uses pessimistic locking to avoid race conditions.
     */
    public function sign(string $id): MedicalRecord
    {
        return DB::transaction(function () use ($id): MedicalRecord {
            /** @var MedicalRecord $record */

            $record = MedicalRecord::query()
                ->with(['patient', 'doctor', 'appointment'])
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $record->is_draft) {
                throw new RuntimeException('Medical record is already finalized.');
            }

            if ($record->signed_at !== null) {
                throw new RuntimeException('Medical record has already been signed.');
            }

            $record->update([
                'is_draft' => false,
                'signed_at' => now(),
            ]);

            return $record->fresh(['patient', 'doctor', 'appointment']);
        });
    }
}

