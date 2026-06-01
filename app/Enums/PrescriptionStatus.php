<?php

namespace App\Enums;

enum PrescriptionStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case DISPENSED = 'dispensed';
    case CANCELLED = 'cancelled';
}
