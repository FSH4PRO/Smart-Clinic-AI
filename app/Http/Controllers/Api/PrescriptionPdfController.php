<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class PrescriptionPdfController extends BaseController
{

    /**
     * Generate and download a prescription as a PDF file.
     */
    public function download(Prescription $prescription)
    {
        // 1. Authorize against the PrescriptionPolicy gate

    //     dd([
    //     'Model Attributes' => $prescription->getAttributes(),
    //     'Loaded Relations' => $prescription->relationsToArray()
    // ]);
        // 2. Eager-load all structural relationships from your database schema
        $prescription->load([
            'doctor.user', 
            'patient.user', 
            'pharmacy.clinic'
        ]);

        // 3. Bind data matrix into the print view layout
        $pdf = Pdf::loadView('pdfs.prescription', compact('prescription'))
                  ->setPaper('a4', 'portrait');

        // 4. Return as a stream or file download download attachment
        return $pdf->download("prescription-{$prescription->id}.pdf");
    }
}