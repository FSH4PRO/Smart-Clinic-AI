<?php

namespace App\Enums;

enum BookingSource: string
{
    case APP = 'app';
    case WALK_IN = 'walk_in';
    case ADMIN = 'admin';
}
