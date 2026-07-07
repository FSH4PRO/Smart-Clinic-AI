<?php

declare(strict_types=1);

namespace App\Modules\AI\Observers;

use App\Modules\AI\Jobs\PrescriptionInteractionJob;
use App\Models\Prescription;

final class PrescriptionObserver
{
    /**
     * Dispatch AI drug interaction check when prescription is saved.
     */
    public function saved(Prescription $prescription): void
    {
        PrescriptionInteractionJob::dispatch($prescription->id);
    }
}

