<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescription Order #{{ substr($prescription->id, 0, 8) }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 14px;
        }

        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }

        .clinic-details {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .meta-table td {
            width: 50%;
            vertical-align: top;
        }

        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #4b5563;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        .rx-symbol {
            font-size: 32px;
            color: #1e3a8a;
            font-weight: bold;
            margin: 15px 0 10px 0;
        }

        /* Added Styling for Prescription Items */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .medication-name {
            font-weight: bold;
            color: #111827;
            font-size: 15px;
        }

        .item-instructions {
            font-size: 12px;
            color: #4b5563;
            margin-top: 4px;
            font-style: italic;
        }

        /* Added Styling for AI Interaction warnings */
        .ai-interaction-warning {
            margin-top: 6px;
            background-color: #fef2f2;
            border-left: 3px solid #dc2626;
            padding: 6px 10px;
            color: #991b1b;
            font-size: 11px;
        }

        .notes-box {
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-top: 10px;
            font-style: italic;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="clinic-name">{{ $prescription->doctor->clinic->name ?? 'SmartClinic AI' }}</div>
        <div class="clinic-details">
            @if ($prescription->pharmacy)
                Fulfillment Center: {{ $prescription->pharmacy->name }}<br>
                Address: {{ $prescription->pharmacy->address }} | Phone: {{ $prescription->pharmacy->phone }}
            @else
                Digital Prescription Record | Unassigned Fulfillment Center
            @endif
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <div class="section-title">Patient Profile</div>
                <strong>{{ $prescription->patient->user->name }}</strong><br>
                Status: <span
                    style="text-transform: uppercase; font-weight: bold;">{{ $prescription->status }}</span><br>
                Issued Date: {{ $prescription->created_at->format('M d, Y h:i A') }}
            </td>
            <td>
                <div class="section-title">Prescribing Clinician</div>
                <strong>Dr. {{ $prescription->doctor->user->name }}</strong><br>
                Specialty: {{ $prescription->doctor->specialty }}<br>
                License No: {{ $prescription->doctor->license_number }}
            </td>
        </tr>
    </table>

    <div class="rx-symbol">Rₓ</div>

    <div class="section-title">Prescribed Medications</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Medication & Instructions</th>
                <th style="width: 15%;">Dosage</th>
                <th style="width: 25%;">Frequency</th>
                <th style="width: 15%;">Duration</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prescription->items as $item)
                <tr>
                    <td>
                        <div class="medication-name">{{ $item->drug_name }}</div>
                        @if ($item->instructions)
                            <div class="item-instructions">Directions: {{ $item->instructions }}</div>
                        @endif

                        @if ($item->ai_interaction_flag)
                            <div class="ai-interaction-warning">
                                <strong>⚠️ AI Safety Alert:</strong> {{ $item->ai_interaction_detail }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $item->dosage }}</td>
                    <td>{{ $item->frequency }}</td>
                    <td>{{ $item->duration_days }} Days</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9ca3af; padding: 20px;">
                        No medications attached to this prescription record.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Clinical Directions & Notes</div>
    @if ($prescription->notes)
        <div class="notes-box">
            {{ $prescription->notes }}
        </div>
    @else
        <p style="color: #9ca3af; font-style: italic; font-size: 12px; margin-top: 5px;">No additional clinical
            directions or routing instructions specified.</p>
    @endif

    <div class="footer">
        This document is an official encrypted digital prescription record generated by SmartClinic AI.<br>
        Prescription ID: {{ $prescription->id }}
    </div>

</body>

</html>
