<?php

namespace App\Enums;

enum AiFeature: string
{
    case TRIAGE = 'triage';
    case SOAP_DRAFT = 'soap_draft';
    case DRUG_CHECK = 'drug_check';
    case NO_SHOW_PRED = 'no_show_pred';
}
