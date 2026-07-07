<?php

declare(strict_types=1);

namespace App\Modules\AI\Observers;

use App\Modules\AI\Jobs\SoapDraftJob;
use App\Models\MedicalRecord;

final class MedicalRecordObserver
{
    /**
     * Dispatch AI SOAP draft generation right after record creation.
     */
    public function created(MedicalRecord $medicalRecord): void
    {
        // Defensive guard clauses: only generate once, for drafts.
        if (! $medicalRecord->is_draft) {
            return;
        }

        if (! empty($medicalRecord->ai_draft)) {
            return;
        }

        SoapDraftJob::dispatch($medicalRecord->id);
    }
}


