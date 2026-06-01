<?php

namespace App\Enums;

enum ClinicSubscriptionPlan: string
{
    case FREE = 'free';
    case BASIC = 'basic';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';
}
