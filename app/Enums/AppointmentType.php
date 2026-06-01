<?php

namespace App\Enums;

enum AppointmentType: string
{
    case IN_PERSON = 'in_person';
    case VIDEO = 'video';
    case HOME_VISIT = 'home_visit';
}
