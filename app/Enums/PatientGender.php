<?php

namespace App\Enums;

enum PatientGender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
}
